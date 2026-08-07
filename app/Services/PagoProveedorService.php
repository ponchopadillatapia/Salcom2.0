<?php

namespace App\Services;

use App\Mail\PagoConfirmadoProveedor;
use App\Models\Alerta;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorFactura;
use App\Models\ProveedorUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PagoProveedorService
{
    /** Meses máximos de vigencia para CIF / Opinión SAT (proxy hasta regla de Karen). */
    public const MESES_VIGENCIA_DOCS = 12;

    /**
     * @return array{ok: bool, motivos: list<string>}
     */
    public function evaluarExpediente(ProveedorUser $proveedor): array
    {
        $proveedor->loadMissing('documentos');
        $motivos = [];

        if (! $proveedor->documentosFiscalesCompletos()) {
            $faltantes = [];
            foreach ($proveedor->documentosRequeridos() as $tipo => $label) {
                $doc = $proveedor->documentos->firstWhere('tipo', $tipo);
                if (! $doc || $doc->estatus !== 'aprobado') {
                    $faltantes[] = $label;
                }
            }
            $motivos[] = 'Expediente incompleto o no aprobado: '.implode(', ', $faltantes ?: ['documentos requeridos']);
        }

        foreach (['cif' => 'CIF', 'opinion' => 'Opinión SAT'] as $tipo => $label) {
            $doc = $proveedor->documentos
                ->where('tipo', $tipo)
                ->where('estatus', 'aprobado')
                ->sortByDesc(fn ($d) => $d->revisado_at ?? $d->updated_at ?? $d->created_at)
                ->first();

            if (! $doc) {
                continue;
            }
            $desde = $doc->revisado_at ?? $doc->updated_at ?? $doc->created_at;
            if ($desde && $desde->lt(now()->subMonths(self::MESES_VIGENCIA_DOCS))) {
                $motivos[] = "{$label} desactualizado (más de ".self::MESES_VIGENCIA_DOCS.' meses)';
            }
        }

        return [
            'ok' => $motivos === [],
            'motivos' => $motivos,
        ];
    }

    /**
     * Avisos por factura (no bloquean; informan en UI/Excel).
     *
     * @return list<string>
     */
    public function avisosFactura(Factura $factura): array
    {
        $avisos = [];
        $detalle = is_array($factura->validacion_detalle) ? $factura->validacion_detalle : [];

        $esFlete = (bool) $factura->es_fletera
            || (bool) ($detalle['es_flete'] ?? false)
            || (bool) ($detalle['conceptos']['flete'] ?? false);

        $retIva = (float) ($factura->retencion_iva ?? 0);
        $retIsr = (float) ($factura->retencion_isr ?? 0);

        if ($esFlete && $retIva <= 0) {
            $avisos[] = 'Flete/fletera: se esperaba retención de IVA y el XML no trae monto';
        }

        $esComision = (bool) ($detalle['es_comision'] ?? false)
            || (bool) ($detalle['conceptos']['comision'] ?? false);
        $rfc = (string) ($detalle['rfc_emisor'] ?? '');
        $esFisica = strlen(preg_replace('/[^A-Z0-9&Ñ]/i', '', strtoupper($rfc))) === 13;
        if ($esComision && $esFisica && $retIva <= 0 && $retIsr <= 0) {
            $avisos[] = 'Comisión persona física: se esperaba retención y no hay montos';
        }

        $servicioLike = $esFlete || $esComision
            || str_contains(strtolower((string) ($detalle['tipo_concepto'] ?? '')), 'servicio');

        if (! $factura->archivo_oc && ! $servicioLike) {
            $avisos[] = 'Sin orden de compra adjunta (revisar si aplica)';
        }

        foreach (['forma_pago' => 'Forma de pago', 'metodo_pago' => 'Método de pago', 'uso_cfdi' => 'Uso CFDI'] as $k => $label) {
            $val = $detalle[$k] ?? $detalle[strtoupper($k)] ?? null;
            if ($val === null || $val === '') {
                $avisos[] = "{$label}: no capturado en validación";
            }
        }

        if (! $factura->regimen_fiscal) {
            $avisos[] = 'Régimen fiscal vacío';
        }

        return $avisos;
    }

    public function netoFactura(Factura $factura): float
    {
        // Total CFDI ya viene neto (SubTotal + IVA − retenciones). No restar retenciones otra vez.
        return round((float) $factura->total, 2);
    }

    /** Suma de facturas aún adeudadas al proveedor (pendiente / programada). */
    public function saldoPendienteProveedor(?string $codigo): float
    {
        if (! $codigo) {
            return 0.0;
        }

        return round((float) Factura::query()
            ->where('codigo_proveedor', $codigo)
            ->whereIn('estatus', ['pendiente', 'programada'])
            ->sum('total'), 2);
    }

    /** Folio de factura (Serie+Folio), nunca el UUID fiscal. */
    public function folioFacturaDisplay(Factura $factura): string
    {
        $det = is_array($factura->validacion_detalle) ? $factura->validacion_detalle : [];
        $serie = trim((string) ($det['serie'] ?? ''));
        $folio = trim((string) ($det['folio'] ?? ''));
        $compuesto = trim($serie.$folio);
        if ($compuesto !== '') {
            return $compuesto;
        }

        $stored = trim((string) ($factura->folio_cfdi ?? ''));
        if ($stored !== '' && ! $this->pareceUuid($stored)) {
            return $stored;
        }

        return '—';
    }

    private function pareceUuid(string $valor): bool
    {
        return (bool) preg_match(
            '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i',
            $valor
        );
    }

    /**
     * @param  list<int>  $facturaIds
     */
    public function crearLote(
        ProveedorUser $proveedor,
        array $facturaIds,
        ?string $fechaPago,
        ?string $notas,
        ?int $adminId
    ): PagoProveedor {
        // El expediente bloquea solo al confirmar; el borrador se puede armar para revisar.

        $facturaIds = array_values(array_unique(array_map('intval', $facturaIds)));
        if ($facturaIds === []) {
            throw new InvalidArgumentException('Selecciona al menos una factura.');
        }

        $codigo = (string) ($proveedor->id_proveedor ?? '');

        $facturas = Factura::query()
            ->whereIn('id', $facturaIds)
            ->where('codigo_proveedor', $codigo)
            ->where('estatus', 'pendiente')
            ->get();

        if ($facturas->count() !== count($facturaIds)) {
            throw new InvalidArgumentException('Hay facturas inválidas, de otro proveedor o que ya no están pendientes.');
        }

        return DB::transaction(function () use ($proveedor, $facturas, $fechaPago, $notas, $adminId, $codigo) {
            $pago = PagoProveedor::create([
                'proveedor_id' => $proveedor->id,
                'codigo_proveedor' => $codigo,
                'tipo' => 'facturas',
                'estatus' => 'borrador',
                'fecha_pago' => $fechaPago ?: null,
                'notas' => $notas,
                'creado_por' => $adminId,
            ]);

            $sumSub = 0;
            $sumIva = 0;
            $sumRetIva = 0;
            $sumRetIsr = 0;
            $sumTotal = 0;
            $sumNeto = 0;

            foreach ($facturas as $f) {
                $neto = $this->netoFactura($f);
                $retIva = (float) ($f->retencion_iva ?? 0);
                $retIsr = (float) ($f->retencion_isr ?? 0);
                $monto = (float) $f->monto;
                $iva = (float) $f->monto_iva;
                $total = (float) $f->total;

                PagoProveedorFactura::create([
                    'pago_id' => $pago->id,
                    'factura_id' => $f->id,
                    'folio_cfdi' => $f->folio_cfdi,
                    'uuid_cfdi' => $f->uuid_cfdi,
                    'es_fletera' => (bool) $f->es_fletera,
                    'regimen_fiscal' => $f->regimen_fiscal,
                    'monto' => $monto,
                    'monto_iva' => $iva,
                    'retencion_iva' => $retIva,
                    'retencion_isr' => $retIsr,
                    'total' => $total,
                    'neto' => $neto,
                    'avisos' => $this->avisosFactura($f),
                ]);

                $sumSub += $monto;
                $sumIva += $iva;
                $sumRetIva += $retIva;
                $sumRetIsr += $retIsr;
                $sumTotal += $total;
                $sumNeto += $neto;
            }

            $pago->update([
                'num_facturas' => $facturas->count(),
                'monto_subtotal' => round($sumSub, 2),
                'monto_iva' => round($sumIva, 2),
                'monto_retencion_iva' => round($sumRetIva, 2),
                'monto_retencion_isr' => round($sumRetIsr, 2),
                'monto_total' => round($sumTotal, 2),
                'monto_neto' => round($sumNeto, 2),
            ]);

            return $pago->fresh(['lineas', 'proveedor']);
        });
    }

    public function confirmar(
        PagoProveedor $pago,
        ?int $adminId,
        array $comprobantes = [],
        ?string $fechaPago = null,
        array $datosConfirmacion = []
    ): PagoProveedor {
        if (! $pago->esBorrador()) {
            throw new InvalidArgumentException('Solo se pueden confirmar pagos en borrador.');
        }

        $pago->load('lineas.factura', 'proveedor');

        if ($datosConfirmacion === []) {
            $datosConfirmacion = $this->datosConfirmacionDesdeFacturas($pago);
        }

        foreach (['forma_pago', 'metodo_pago', 'uso_cfdi', 'regimen', 'producto'] as $campo) {
            if (trim((string) ($datosConfirmacion[$campo] ?? '')) === '') {
                throw new InvalidArgumentException(
                    'Faltan datos fiscales en las facturas (forma, método, uso CFDI, régimen o concepto). Vuelve a dar de alta con un XML completo.'
                );
            }
        }

        $pagoConfirmado = DB::transaction(function () use ($pago, $adminId, $comprobantes, $fechaPago, $datosConfirmacion) {
            if ($fechaPago) {
                $pago->fecha_pago = Carbon::parse($fechaPago);
            }

            $nuevoEstatusFactura = $pago->fecha_pago ? 'pagada' : 'programada';

            foreach ($pago->lineas as $linea) {
                $factura = $linea->factura;
                if (! $factura || $factura->estatus !== 'pendiente') {
                    throw new InvalidArgumentException('La factura '.($linea->folio_cfdi ?? $linea->factura_id).' ya no está pendiente.');
                }
                $factura->update(['estatus' => $nuevoEstatusFactura]);
            }

            $pago->update([
                'estatus' => 'confirmado',
                'comprobantes' => $comprobantes !== [] ? $comprobantes : ($pago->comprobantes ?? []),
                'datos_confirmacion' => $datosConfirmacion,
                'confirmado_por' => $adminId,
                'confirmado_at' => now(),
                'fecha_pago' => $pago->fecha_pago,
            ]);

            return $pago->fresh(['lineas', 'proveedor']);
        });

        $this->notificarPagoConfirmadoAlProveedor($pagoConfirmado);

        return $pagoConfirmado;
    }

    /**
     * Arma datos fiscales del lote desde las facturas (deben ser homogéneos).
     *
     * @return array{forma_pago: string, metodo_pago: string, uso_cfdi: string, regimen: string, producto: string}
     */
    public function datosConfirmacionDesdeFacturas(PagoProveedor $pago): array
    {
        $pago->loadMissing('lineas.factura');
        $filas = [];

        foreach ($pago->lineas as $linea) {
            $f = $linea->factura;
            if (! $f) {
                continue;
            }
            $det = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
            $filas[] = [
                'forma_pago' => trim((string) ($det['forma_pago'] ?? '')),
                'metodo_pago' => trim((string) ($det['metodo_pago'] ?? '')),
                'uso_cfdi' => trim((string) ($det['uso_cfdi'] ?? '')),
                'regimen' => trim((string) ($f->regimen_fiscal ?: ($det['regimen_fiscal'] ?? ''))),
                'producto' => trim((string) ($det['producto'] ?? $det['descripcion'] ?? '')),
                'folio' => (string) ($f->folio_cfdi ?: $f->id),
            ];
        }

        if ($filas === []) {
            throw new InvalidArgumentException('El lote no tiene facturas válidas.');
        }

        foreach (['forma_pago', 'metodo_pago', 'uso_cfdi'] as $campo) {
            $vals = array_unique(array_filter(array_column($filas, $campo)));
            if (count($vals) > 1) {
                $label = match ($campo) {
                    'forma_pago' => 'forma de pago',
                    'metodo_pago' => 'método de pago',
                    default => 'uso CFDI',
                };
                throw new InvalidArgumentException(
                    "Las facturas del lote tienen distinta {$label}. Selecciona facturas homogéneas o corrige el alta."
                );
            }
            if ($vals === []) {
                throw new InvalidArgumentException(
                    "Hay facturas sin {$campo} (dato del XML en Alta). No se puede confirmar automáticamente."
                );
            }
        }

        $productos = array_values(array_unique(array_filter(array_column($filas, 'producto'))));
        $producto = match (count($productos)) {
            0 => 'Varios conceptos',
            1 => $productos[0],
            default => Str::limit(implode(' · ', $productos), 250),
        };

        $regimenes = array_values(array_unique(array_filter(array_column($filas, 'regimen'))));
        $regimen = $regimenes[0] ?? '';

        return [
            'forma_pago' => $filas[0]['forma_pago'],
            'metodo_pago' => $filas[0]['metodo_pago'],
            'uso_cfdi' => $filas[0]['uso_cfdi'],
            'regimen' => $regimen,
            'producto' => $producto,
        ];
    }

    /**
     * Campanita + correo al proveedor cuando Contabilidad confirma el lote.
     */
    private function notificarPagoConfirmadoAlProveedor(PagoProveedor $pago): void
    {
        $proveedor = $pago->proveedor;
        if (! $proveedor) {
            return;
        }

        $estatusFactura = $pago->fecha_pago ? 'pagada' : 'programada';
        $titulo = $estatusFactura === 'pagada'
            ? 'Pago confirmado'
            : 'Pago programado';
        $montoFmt = number_format((float) $pago->monto_total, 2);
        $contenido = $estatusFactura === 'pagada'
            ? "Salcom confirmó el pago de {$pago->num_facturas} factura(s) por \${$montoFmt}."
            : "Salcom programó el pago de {$pago->num_facturas} factura(s) por \${$montoFmt}.";

        try {
            app(AlertEngineService::class)->crearAlerta([
                'tipo' => 'pago_confirmado',
                'modulo' => 'pagos',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $proveedor->id,
                'titulo' => $titulo,
                'contenido' => $contenido,
                'datos' => [
                    'pago_id' => $pago->id,
                    'estatus_factura' => $estatusFactura,
                    'num_facturas' => (int) $pago->num_facturas,
                    'monto_total' => (float) $pago->monto_total,
                ],
                'nivel' => 'info',
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PagoProveedor] No se pudo crear alerta de pago confirmado: '.$e->getMessage());
        }

        $correo = trim((string) ($proveedor->correo ?? ''));
        if ($correo === '') {
            return;
        }

        try {
            Mail::to($correo)->send(new PagoConfirmadoProveedor(
                nombreProveedor: (string) ($proveedor->nombre ?: $proveedor->usuario ?: 'Proveedor'),
                estatusFactura: $estatusFactura,
                numFacturas: (int) $pago->num_facturas,
                montoTotal: (float) $pago->monto_total,
                fechaPago: $pago->fecha_pago?->format('d/m/Y'),
                urlPagos: route('proveedores.payment-history'),
            ));
        } catch (\Throwable $e) {
            Log::warning('[PagoProveedor] No se pudo enviar correo de pago confirmado: '.$e->getMessage());
        }
    }

    public function cancelarBorrador(PagoProveedor $pago): void
    {
        if (! $pago->esBorrador()) {
            throw new InvalidArgumentException('Solo se pueden cancelar borradores.');
        }
        $pago->update(['estatus' => 'cancelado']);
    }

    /**
     * Proveedores con facturas pendientes (para lista).
     */
    public function proveedoresConPendientes(): Collection
    {
        /** @var Collection $resultado */
        $resultado = Factura::query()
            ->selectRaw('codigo_proveedor, count(*) as num_facturas, sum(total) as monto_total, max(created_at) as ultima_factura_at')
            ->where('estatus', 'pendiente')
            ->whereNotNull('codigo_proveedor')
            ->groupBy('codigo_proveedor')
            ->orderByDesc('ultima_factura_at')
            ->get()
            ->map(function ($row) {
                $prov = ProveedorUser::whereCodigo('=', $row->codigo_proveedor)->first();
                $notifSinLeer = Alerta::query()
                    ->where('destinatario_tipo', 'admin')
                    ->where('tipo', 'factura_pago_pendiente')
                    ->where('datos->codigo_proveedor', $row->codigo_proveedor)
                    ->whereNotIn('estatus', ['leida', 'accionada'])
                    ->count();

                $ultimaAt = $row->ultima_factura_at
                    ? Carbon::parse($row->ultima_factura_at)
                    : null;

                return (object) [
                    'codigo' => $row->codigo_proveedor,
                    'proveedor' => $prov,
                    'nombre' => $prov?->nombre ?? $row->codigo_proveedor,
                    'num_facturas' => (int) $row->num_facturas,
                    'monto_total' => (float) $row->monto_total,
                    'ultima_factura_at' => $ultimaAt,
                    'expediente' => $prov ? $this->evaluarExpediente($prov) : ['ok' => false, 'motivos' => ['Proveedor no encontrado']],
                    'notif_sin_leer' => $notifSinLeer,
                ];
            });

        return $resultado;
    }

    /**
     * Filas CSV del reporte de folios.
     *
     * @return list<list<string>>
     */
    public function filasExcel(PagoProveedor $pago): array
    {
        $pago->loadMissing(['lineas', 'proveedor']);
        $nombre = $pago->proveedor?->nombre ?? $pago->codigo_proveedor;

        $lines = [
            ['INDUSTRIAS SALCOM S.A. DE C.V.'],
            ['REPORTE PARA PAGO A PROVEEDOR'],
            ['Lote #'.$pago->id, 'Estatus: '.$pago->estatus, 'Generado: '.now()->format('d/m/Y H:i')],
            ['Proveedor:', $nombre, 'Código:', $pago->codigo_proveedor],
            ['Fecha pago:', $pago->fecha_pago?->format('d/m/Y') ?? '—', 'Tipo:', $pago->tipo],
            [],
            [
                'FOLIO',
                'UUID',
                'FLETE',
                'REGIMEN',
                'SUBTOTAL',
                'IVA',
                'RET_IVA',
                'RET_ISR',
                'TOTAL',
                'NETO',
                'AVISOS',
            ],
        ];

        foreach ($pago->lineas as $l) {
            $lines[] = [
                (string) ($l->folio_cfdi ?? ''),
                (string) ($l->uuid_cfdi ?? ''),
                $l->es_fletera ? 'SI' : 'NO',
                (string) ($l->regimen_fiscal ?? ''),
                number_format((float) $l->monto, 2, '.', ''),
                number_format((float) $l->monto_iva, 2, '.', ''),
                number_format((float) $l->retencion_iva, 2, '.', ''),
                number_format((float) $l->retencion_isr, 2, '.', ''),
                number_format((float) $l->total, 2, '.', ''),
                number_format((float) $l->neto, 2, '.', ''),
                implode(' | ', $l->avisos ?? []),
            ];
        }

        $lines[] = [];
        $lines[] = [
            '', '', '', 'TOTALES',
            number_format((float) $pago->monto_subtotal, 2, '.', ''),
            number_format((float) $pago->monto_iva, 2, '.', ''),
            number_format((float) $pago->monto_retencion_iva, 2, '.', ''),
            number_format((float) $pago->monto_retencion_isr, 2, '.', ''),
            number_format((float) $pago->monto_total, 2, '.', ''),
            number_format((float) $pago->monto_neto, 2, '.', ''),
            '',
        ];

        return $lines;
    }

    /**
     * Datos para PDF «REPORTE RESUMEN DE PAGOS» (1 fila = 1 pago con N folios).
     *
     * @return array<string, mixed>
     */
    public function datosReporteResumen(PagoProveedor $pago): array
    {
        $pago->loadMissing(['lineas.factura', 'proveedor']);
        $prov = $pago->proveedor;
        $db = is_array($prov?->datos_identificacion) ? $prov->datos_identificacion : [];

        $folios = [];
        foreach ($pago->lineas as $linea) {
            $f = $linea->factura;
            if ($f) {
                $folios[] = $this->folioFacturaDisplay($f);
            } elseif (! empty($linea->folio_cfdi) && ! $this->pareceUuid((string) $linea->folio_cfdi)) {
                $folios[] = (string) $linea->folio_cfdi;
            }
        }
        $folios = array_values(array_unique(array_filter($folios, fn ($v) => $v !== '—' && $v !== '')));

        $importe = (float) $pago->monto_neto;
        if ($importe <= 0) {
            $importe = (float) $pago->monto_total;
        }

        return [
            'pago' => $pago,
            'banco' => trim((string) ($db['banco'] ?? '')),
            'cuenta' => preg_replace('/\D/', '', (string) ($db['cuenta'] ?? '')) ?: '',
            'clabe' => preg_replace('/\D/', '', (string) ($db['clabe'] ?? '')) ?: '',
            'swift' => trim((string) ($db['swift'] ?? $db['Swift'] ?? '')),
            'rfc' => strtoupper(trim((string) ($db['rfc'] ?? $db['RFC'] ?? ''))),
            'nombreProveedor' => (string) ($prov?->nombre ?: $pago->codigo_proveedor),
            'foliosGenerales' => implode(', ', $folios),
            'importe' => $importe,
            'iva' => (float) $pago->monto_iva,
            'totalBanco' => $importe,
            'totalPagar' => $importe,
        ];
    }
}
