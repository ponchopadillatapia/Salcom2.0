<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorFactura;
use App\Models\ProveedorUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        $total = (float) $factura->total;
        $ret = (float) ($factura->retencion_iva ?? 0) + (float) ($factura->retencion_isr ?? 0);

        // Si total ya viene neto del CFDI, no restar de nuevo: usamos total como base de pago
        // y reportamos retenciones aparte. Neto mostrado = total - retenciones solo si total parece bruto.
        // Convención v1: neto a pagar = total (CFDI) ; retenciones informativas.
        // Contabilidad puede ajustar. Preferimos: total - retenciones si retenciones > 0 y total >= ret.
        if ($ret > 0 && $total >= $ret) {
            return round($total - $ret, 2);
        }

        return round($total, 2);
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
        $exp = $this->evaluarExpediente($proveedor);
        if (! $exp['ok']) {
            throw new InvalidArgumentException('No se puede armar el pago: '.implode('; ', $exp['motivos']));
        }

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

    public function confirmar(PagoProveedor $pago, ?int $adminId): PagoProveedor
    {
        if (! $pago->esBorrador()) {
            throw new InvalidArgumentException('Solo se pueden confirmar lotes en borrador.');
        }

        $pago->load('lineas.factura', 'proveedor');
        if ($pago->proveedor) {
            $exp = $this->evaluarExpediente($pago->proveedor);
            if (! $exp['ok']) {
                throw new InvalidArgumentException('Expediente bloquea la confirmación: '.implode('; ', $exp['motivos']));
            }
        }

        return DB::transaction(function () use ($pago, $adminId) {
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
                'confirmado_por' => $adminId,
                'confirmado_at' => now(),
            ]);

            return $pago->fresh(['lineas', 'proveedor']);
        });
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
     *
     * @return Collection<int, object>
     */
    public function proveedoresConPendientes(): Collection
    {
        return Factura::query()
            ->selectRaw('codigo_proveedor, count(*) as num_facturas, sum(total) as monto_total')
            ->where('estatus', 'pendiente')
            ->whereNotNull('codigo_proveedor')
            ->groupBy('codigo_proveedor')
            ->orderByDesc('monto_total')
            ->get()
            ->map(function ($row) {
                $prov = ProveedorUser::whereCodigo('=', $row->codigo_proveedor)->first();

                return (object) [
                    'codigo' => $row->codigo_proveedor,
                    'proveedor' => $prov,
                    'nombre' => $prov?->nombre ?? $row->codigo_proveedor,
                    'num_facturas' => (int) $row->num_facturas,
                    'monto_total' => (float) $row->monto_total,
                    'expediente' => $prov ? $this->evaluarExpediente($prov) : ['ok' => false, 'motivos' => ['Proveedor no encontrado']],
                ];
            });
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
}
