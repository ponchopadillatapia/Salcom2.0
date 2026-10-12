<?php

namespace App\Services;

use App\Mail\PagoConfirmadoProveedor;
use App\Models\Alerta;
use App\Models\DocumentoProveedor;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorFactura;
use App\Models\ProveedorUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PagoProveedorService
{
    /** Meses máximos de vigencia para CIF / Opinión SAT (proxy hasta regla de Karen). */
    public const MESES_VIGENCIA_DOCS = 12;

    /**
     * @return array{ok: bool, motivos: list<string>}
     */
    /**
     * Evalúa el expediente de un PAGO (decide solo vs multi-proveedor).
     * En multi: junta los faltantes de TODOS los proveedores, prefijando el nombre de cada
     * uno para saber a quién le falta qué. En normal: evalúa al proveedor del pago.
     *
     * @return array{ok: bool, motivos: list<string>}
     */
    public function evaluarExpedientePago(PagoProveedor $pago): array
    {
        if (! $pago->es_multi_proveedor) {
            return $pago->proveedor
                ? $this->evaluarExpediente($pago->proveedor)
                : ['ok' => false, 'motivos' => ['Sin proveedor']];
        }

        $motivos = [];
        foreach ($this->proveedoresDelPago($pago) as $prov) {
            $res = $this->evaluarExpediente($prov);
            foreach ($res['motivos'] as $m) {
                $motivos[] = $prov->nombre.': '.$m;
            }
        }

        return ['ok' => $motivos === [], 'motivos' => $motivos];
    }

    public function evaluarExpediente(ProveedorUser $proveedor): array
    {
        // Excepción para pruebas: SAID001 siempre OK
        $codigo = $proveedor->id_proveedor ?: $proveedor->codigo;
        if ($codigo === 'SAID001') {
            return ['ok' => true, 'motivos' => []];
        }

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
     * Documentos fiscales que se muestran (y adjuntan) en el borrador de Formato para pago.
     * Opinión y constancia salen del expediente validado; el formato de pago del lote
     * se genera aquí (o se toma del expediente si ya existe ese tipo).
     *
     * @return list<array{
     *   clave: string,
     *   label: string,
     *   ok: bool,
     *   origen: string|null,
     *   documento_id: int|null,
     *   archivo: string|null,
     *   nombre: string|null,
     *   url: string|null,
     *   estatus: string|null
     * }>
     */
    public function documentosFiscalesParaPago(PagoProveedor $pago): array
    {
        $proveedor = $pago->proveedor;
        $opinion = $proveedor ? $this->ultimoDocumentoAprobado($proveedor, ['opinion', 'opinion_cumplimiento']) : null;
        $constancia = $proveedor ? $this->ultimoDocumentoAprobado($proveedor, ['cif', 'constancia_fiscal']) : null;
        $formatoExpediente = $proveedor ? $this->ultimoDocumentoAprobado($proveedor, ['formato_pago']) : null;

        return [
            $this->slotDocumentoFiscal(
                clave: 'opinion',
                label: 'Opinión positiva',
                doc: $opinion,
            ),
            $formatoExpediente
                ? $this->slotDocumentoFiscal(
                    clave: 'formato_pago',
                    label: 'Formato de pago',
                    doc: $formatoExpediente,
                )
                : [
                    'clave' => 'formato_pago',
                    'label' => 'Formato de pago',
                    'ok' => true,
                    'origen' => 'lote',
                    'documento_id' => null,
                    'archivo' => null,
                    'nombre' => 'Formato_para_pago_lote_'.$pago->id.'.pdf',
                    'url' => route('admin.pagos.reporte-resumen', ['pago' => $pago, 'ver' => 1]),
                    'estatus' => 'generado',
                ],
            $this->slotDocumentoFiscal(
                clave: 'constancia',
                label: 'Constancia de Situación Fiscal',
                doc: $constancia,
            ),
        ];
    }

    /**
     * Devuelve los ProveedorUser (locales, con documentos) involucrados en un pago.
     * En un pago multi-proveedor son varios (uno por cada codigo_proveedor distinto de las
     * líneas); en uno normal, es solo el proveedor del pago.
     *
     * @return list<ProveedorUser>
     */
    private function proveedoresDelPago(PagoProveedor $pago): array
    {
        $pago->loadMissing('lineas');
        $codigos = $pago->lineas
            ->map(fn ($l) => (string) ($l->codigo_proveedor ?: $pago->codigo_proveedor))
            ->filter()
            ->unique()
            ->values();

        // Mapa codigo -> nombre tomado de las líneas (por si el proveedor no está en local).
        $nombres = $pago->lineas
            ->mapWithKeys(fn ($l) => [(string) ($l->codigo_proveedor ?: $pago->codigo_proveedor) => (string) ($l->nombre_proveedor ?? '')]);

        $provs = [];
        foreach ($codigos as $codigo) {
            $prov = ProveedorUser::porCualquierCodigo($codigo)->with('documentos')->first();
            if (! $prov) {
                // POR QUÉ: un proveedor del pago puede no existir en local (vino de Wiese y
                // nunca se guardó). Para que SÍ aparezca en el expediente (Karen: todos los
                // involucrados), armamos uno EN MEMORIA con su nombre y documentos vacíos
                // (se verá como "falta", que es lo correcto: no tiene expediente local).
                $prov = new ProveedorUser;
                $prov->codigo = $codigo;
                $prov->id_proveedor = $codigo;
                $prov->nombre = $nombres[$codigo] ?? $codigo;
                $prov->setRelation('documentos', collect());
            }
            $provs[] = $prov;
        }

        return $provs;
    }

    /**
     * Documentos fiscales (opinión + constancia) de TODOS los proveedores de un pago múltiple.
     * POR QUÉ: Karen pidió que el expediente de un pago que junta varios proveedores incluya
     * los papeles fiscales de cada uno, etiquetados con el nombre del proveedor para no
     * confundirlos. El "Formato de pago" (el PDF) es uno solo para todo el pago.
     *
     * @return list<array<string, mixed>>
     */
    private function documentosFiscalesMultiProveedor(PagoProveedor $pago): array
    {
        $items = [];

        // Formato de pago: uno solo para todo el pago (el PDF gigante).
        $items[] = [
            'clave' => 'formato_pago',
            'label' => 'Formato de pago (todos los proveedores)',
            'ok' => true,
            'origen' => 'lote',
            'documento_id' => null,
            'archivo' => null,
            'nombre' => 'Formato_para_pago_lote_'.$pago->id.'.pdf',
            'url' => route('admin.pagos.reporte-resumen', ['pago' => $pago, 'ver' => 1]),
            'estatus' => 'generado',
        ];

        // Opinión + constancia de CADA proveedor involucrado, etiquetadas con su nombre.
        foreach ($this->proveedoresDelPago($pago) as $prov) {
            $opinion = $this->ultimoDocumentoAprobado($prov, ['opinion', 'opinion_cumplimiento']);
            $constancia = $this->ultimoDocumentoAprobado($prov, ['cif', 'constancia_fiscal']);

            $items[] = $this->slotDocumentoFiscal(
                'opinion_'.$prov->id,
                'Opinión positiva — '.$prov->nombre,
                $opinion
            );
            $items[] = $this->slotDocumentoFiscal(
                'constancia_'.$prov->id,
                'Constancia de Situación Fiscal — '.$prov->nombre,
                $constancia
            );
        }

        return $items;
    }

    /**
     * Expediente de Pago completo: TODOS los documentos que respaldan el pago,
     * agrupados en secciones. Junta lo que ya existe en el sistema (facturas,
     * formato de pago, opinión, CIF, carátula bancaria) + los adjuntos manuales
     * (póliza de Contpaqi, hojas engrapadas, etc.).
     *
     * @return array<int, array{grupo: string, items: list<array<string, mixed>>}>
     */
    public function documentosExpedientePago(PagoProveedor $pago): array
    {
        $pago->loadMissing(['lineas.factura', 'proveedor.documentos']);
        $proveedor = $pago->proveedor;

        // 1) Facturas del lote (PDF, XML y OC de cada una).
        $itemsFactura = [];
        foreach ($pago->lineas as $linea) {
            $f = $linea->factura;
            if (! $f) {
                continue;
            }
            $folio = $this->folioFacturaDisplay($f);
            if ($f->archivo_pdf) {
                $itemsFactura[] = $this->slotArchivo('Factura '.$folio.' (PDF)', $f->archivo_pdf);
            }
            if ($f->archivo_xml) {
                $itemsFactura[] = $this->slotArchivo('Factura '.$folio.' (XML)', $f->archivo_xml);
            }
            if ($f->archivo_oc) {
                $itemsFactura[] = $this->slotArchivo('Orden de compra '.$folio, $f->archivo_oc);
            }
        }

        // 2) Formato de pago + fiscales (opinión, CIF).
        // POR QUÉ dos caminos: en un pago MULTI-proveedor, Karen pidió que el expediente
        // lleve los fiscales de TODOS los proveedores involucrados, no solo el principal.
        $fiscales = $pago->es_multi_proveedor
            ? $this->documentosFiscalesMultiProveedor($pago)
            : $this->documentosFiscalesParaPago($pago);

        // 3) Carátula de banco. En multi: la de cada proveedor involucrado.
        $itemsBanco = [];
        if ($pago->es_multi_proveedor) {
            foreach ($this->proveedoresDelPago($pago) as $prov) {
                $caratula = $this->ultimoDocumentoAprobado($prov, ['caratula_banco']);
                if ($caratula) {
                    $itemsBanco[] = $this->slotDocumentoFiscal(
                        'caratula_banco_'.$prov->id,
                        'Carátula de banco — '.$prov->nombre,
                        $caratula
                    );
                }
            }
        } else {
            $caratula = $proveedor ? $this->ultimoDocumentoAprobado($proveedor, ['caratula_banco']) : null;
            if ($caratula) {
                $itemsBanco[] = $this->slotDocumentoFiscal('caratula_banco', 'Carátula de banco', $caratula);
            }
        }

        // 4) Documentos adjuntos manuales (póliza de Contpaqi, hojas engrapadas, etc.).
        $itemsAdjuntos = [];
        foreach (($pago->documentos_adjuntos ?? []) as $i => $adj) {
            if (! is_array($adj) || empty($adj['archivo'])) {
                continue;
            }
            $itemsAdjuntos[] = [
                'clave' => 'adjunto_'.$i,
                'label' => $adj['nombre'] ?? ($adj['tipo'] ?? 'Documento adjunto'),
                'ok' => true,
                'origen' => 'adjunto',
                'archivo' => $adj['archivo'],
                'url' => \Illuminate\Support\Facades\Storage::disk('public')->exists($adj['archivo'])
                    ? asset('storage/'.$adj['archivo'])
                    : null,
                'indice' => $i,
            ];
        }

        return [
            ['grupo' => 'Factura(s)', 'items' => $itemsFactura],
            ['grupo' => 'Formato de pago y fiscales', 'items' => $fiscales],
            ['grupo' => 'Datos bancarios', 'items' => $itemsBanco],
            ['grupo' => 'Adjuntos (póliza, hojas engrapadas, etc.)', 'items' => $itemsAdjuntos],
        ];
    }

    /** Slot genérico para un archivo por ruta (factura PDF/XML, OC, adjuntos). */
    private function slotArchivo(string $label, ?string $archivo): array
    {
        $archivo = ltrim((string) $archivo, '/');
        $url = null;
        if ($archivo !== '') {
            foreach (['public', 'local'] as $disk) {
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($archivo)) {
                    $url = $disk === 'public' ? asset('storage/'.$archivo) : null;
                    break;
                }
            }
        }

        return [
            'clave' => 'archivo',
            'label' => $label,
            'ok' => $archivo !== '',
            'origen' => 'factura',
            'archivo' => $archivo,
            'url' => $url,
        ];
    }

    /**
     * Copia al lote los PDFs del expediente y genera el formato de pago si hace falta.
     *
     * @param  list<array<string, mixed>>  $slots
     * @return array{paths: list<string>, slots: list<array<string, mixed>>}
     */
    public function materializarDocumentosFiscales(PagoProveedor $pago, array $slots): array
    {
        $paths = [];
        $salida = [];

        foreach ($slots as $slot) {
            $path = null;
            $origen = $slot['origen'] ?? null;

            if ($origen === 'expediente' && ! empty($slot['documento_id'])) {
                $doc = DocumentoProveedor::find($slot['documento_id']);
                if ($doc) {
                    $path = $this->copiarDocumentoAPago($pago, $doc, (string) $slot['clave']);
                }
            } elseif (($slot['clave'] ?? '') === 'formato_pago') {
                $path = $this->generarFormatoPagoPdf($pago);
            }

            if (is_string($path) && $path !== '') {
                $paths[] = $path;
                $slot['path'] = $path;
                $slot['ok'] = true;
            }

            $salida[] = $slot;
        }

        return [
            'paths' => array_values(array_unique($paths)),
            'slots' => $salida,
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
        // Sí restamos lo ya pagado (p. ej. anticipos aplicados) para mostrar el saldo real pendiente.
        $saldo = (float) $factura->total - (float) $factura->monto_pagado;

        return round(max($saldo, 0), 2);
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
    /**
     * Crea UN pago que agrupa facturas de VARIOS proveedores (carrito multi-proveedor).
     *
     * POR QUÉ: en la vida real el "Formato para pago" junta facturas de distintos proveedores
     * en una sola lista con un gran total. Aquí se crea un solo PagoProveedor marcado como
     * multi-proveedor; cada línea (pago_proveedor_facturas) guarda su propio proveedor
     * (codigo + nombre) para que el PDF agrupe por proveedor y el gran total sea la suma.
     *
     * @param  array<string, array{nombre:string, rfc:string, folios: array<string, array>}>  $carrito
     *         estructura de sesión: [codigo_proveedor => ['nombre','rfc','folios'=>[...]]]
     * @param  callable(string, list<string>): list<int>  $materializar
     *         función que recibe (codigo, folios) y devuelve los IDs locales de factura.
     */
    public function crearPagoMultiProveedor(array $carrito, callable $materializar, ?int $adminId): PagoProveedor
    {
        if ($carrito === []) {
            throw new InvalidArgumentException('El carrito está vacío.');
        }

        // 1) Materializar las facturas de cada proveedor y juntar sus IDs locales,
        //    recordando a qué proveedor pertenece cada factura.
        $facturaIdsPorProveedor = []; // [codigo => [ids...]]
        $nombrePorCodigo = [];
        foreach ($carrito as $codigo => $info) {
            $folios = array_keys($info['folios'] ?? []);
            if ($folios === []) {
                continue;
            }
            $ids = $materializar((string) $codigo, array_map('strval', $folios));
            if ($ids !== []) {
                $facturaIdsPorProveedor[(string) $codigo] = $ids;
                $nombrePorCodigo[(string) $codigo] = (string) ($info['nombre'] ?? $codigo);
            }
        }

        if ($facturaIdsPorProveedor === []) {
            throw new InvalidArgumentException('No se pudieron preparar las facturas del carrito (¿Wiese respondió?).');
        }

        // El "proveedor principal" del pago es el primero (solo para la cabecera/relación;
        // el detalle real va por línea). es_multi_proveedor = true.
        $codigoPrincipal = array_key_first($facturaIdsPorProveedor);
        $proveedorPrincipal = ProveedorUser::porCualquierCodigo($codigoPrincipal)->first();

        return DB::transaction(function () use ($facturaIdsPorProveedor, $nombrePorCodigo, $proveedorPrincipal, $codigoPrincipal, $adminId) {
            $pago = PagoProveedor::create([
                'proveedor_id' => $proveedorPrincipal?->id,
                'codigo_proveedor' => $codigoPrincipal,
                'es_multi_proveedor' => true,
                'tipo' => 'facturas',
                'estatus' => 'borrador',
                'creado_por' => $adminId,
            ]);

            $sumSub = $sumIva = $sumRetIva = $sumRetIsr = $sumTotal = $sumNeto = 0;
            $numFacturas = 0;

            foreach ($facturaIdsPorProveedor as $codigo => $ids) {
                $facturas = Factura::query()
                    ->whereIn('id', $ids)
                    ->where('codigo_proveedor', $codigo)
                    ->get();

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
                        'codigo_proveedor' => $codigo,
                        'nombre_proveedor' => $nombrePorCodigo[$codigo] ?? $codigo,
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
                    $numFacturas++;
                }
            }

            $pago->update([
                'num_facturas' => $numFacturas,
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
            try {
                $datosConfirmacion = $this->datosConfirmacionDesdeFacturas($pago);
            } catch (InvalidArgumentException $e) {
                // Para pruebas: si es SAID001, usar datos default
                if ($pago->codigo_proveedor === 'SAID001') {
                    $datosConfirmacion = [
                        'forma_pago' => '03',
                        'metodo_pago' => 'PPD',
                        'uso_cfdi' => 'G03',
                        'regimen' => '601',
                        'producto' => '01010101',
                    ];
                } else {
                    throw $e;
                }
            }
        }

        // Saltar validación de datos fiscales para SAID001 (pruebas)
        if ($pago->codigo_proveedor !== 'SAID001') {
            foreach (['forma_pago', 'metodo_pago', 'uso_cfdi', 'regimen', 'producto'] as $campo) {
                if (trim((string) ($datosConfirmacion[$campo] ?? '')) === '') {
                    throw new InvalidArgumentException(
                        'Faltan datos fiscales en las facturas (forma, método, uso CFDI, régimen o concepto). Vuelve a dar de alta con un XML completo.'
                    );
                }
            }
        }

        $docsFiscales = $this->materializarDocumentosFiscales($pago, $this->documentosFiscalesParaPago($pago));
        $comprobantes = array_values(array_unique(array_merge($docsFiscales['paths'], $comprobantes)));
        $datosConfirmacion['documentos_fiscales'] = $docsFiscales['slots'];

        $pagoConfirmado = DB::transaction(function () use ($pago, $adminId, $comprobantes, $fechaPago, $datosConfirmacion) {
            if ($fechaPago) {
                $pago->fecha_pago = Carbon::parse($fechaPago);
            } else {
                $pago->fecha_pago = now();
            }

            // Pagos solo PROGRAMA — no marca como pagada ni toca monto_pagado
            // El pago real se hace en Abonos al proveedor
            $nuevoEstatusFactura = 'programada';

            foreach ($pago->lineas as $linea) {
                $factura = $linea->factura;
                if (! $factura || ! in_array($factura->estatus, ['pendiente', 'programada'])) {
                    $folioRef = $linea->folio_cfdi ?? $linea->factura_id;
                    $estActual = $factura->estatus ?? 'desconocido';
                    throw new InvalidArgumentException("La factura {$folioRef} ya no se puede programar (está en \"{$estActual}\"). Solo se programan facturas pendientes.");
                }
                $factura->update([
                    'estatus' => $nuevoEstatusFactura,
                ]);
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

        // Alerta para admin: pago programado
        try {
            $montoFmt = number_format((float) $pagoConfirmado->monto_total, 2);
            Alerta::create([
                'tipo' => 'pago_programado',
                'modulo' => 'pagos',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => $adminId,
                'titulo' => 'Pago programado',
                'contenido' => "{$pagoConfirmado->num_facturas} factura(s) programadas por \${$montoFmt}. Proveedor: {$pagoConfirmado->codigo_proveedor}.",
                'nivel' => 'info',
                'estatus' => 'nueva',
                'datos' => [
                    'pago_id' => $pagoConfirmado->id,
                    'codigo_proveedor' => $pagoConfirmado->codigo_proveedor,
                    'monto' => (float) $pagoConfirmado->monto_total,
                    'num_facturas' => (int) $pagoConfirmado->num_facturas,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Formato] No se pudo crear alerta admin: ' . $e->getMessage());
        }

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

        $estatusFactura = 'programada';
        $titulo = 'Pago programado';
        $montoFmt = number_format((float) $pago->monto_total, 2);
        $contenido = "Salcom programó el pago de {$pago->num_facturas} factura(s) por \${$montoFmt}. Próximamente se realizará el depósito.";

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
        // POR QUÉ 'programada' y no 'pendiente': el flujo es pendiente → programada → pagada → liquidada.
        // "Pago a proveedor" (paso 2) recibe las facturas que YA pasaron por "Formato para pago"
        // (paso 1), que las dejó en estatus 'programada'. Antes buscaba 'pendiente' (estatus viejo,
        // de antes de las APIs) y por eso no aparecían las facturas ya programadas.
        /** @var Collection $resultado */
        $resultado = Factura::query()
            ->selectRaw('codigo_proveedor, count(*) as num_facturas, sum(total) as monto_total, max(created_at) as ultima_factura_at, min(fecha_vencimiento) as proximo_vencimiento')
            ->where('estatus', 'programada')
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
                    'proximo_vencimiento' => $row->proximo_vencimiento
                        ? Carbon::parse($row->proximo_vencimiento)
                        : null,
                    'expediente' => $prov ? $this->evaluarExpediente($prov) : ['ok' => false, 'motivos' => ['Proveedor no encontrado']],
                    'notif_sin_leer' => $notifSinLeer,
                ];
            });

        return $resultado;
    }

    /**
     * Lista para "Formato para pago": TODOS los proveedores de Wiese (5,685),
     * pero mostrando ARRIBA a los que tienen facturas pendientes (con su conteo/monto).
     *
     * POR QUÉ: Karen/Contabilidad necesita poder buscar a CUALQUIER proveedor de Wiese
     * para registrarle un pago desde cero, no solo a los que ya deben dinero. Pero los
     * que sí tienen pendientes deben salir primero (es lo urgente por pagar).
     *
     * Estrategia: se toman los pendientes locales (ya calculados) y se "fusionan" con la
     * lista de Wiese por CÓDIGO. Si un proveedor de Wiese tiene pendientes, hereda sus
     * datos (num_facturas, monto, notifs); si no, va con ceros. Orden final:
     *   1) primero los que tienen pendientes, por factura más reciente
     *   2) luego el resto de Wiese, alfabético
     * Si Wiese no responde (sin VPN), cae de vuelta a solo-pendientes (no rompe la pantalla).
     */
    public function proveedoresParaFormatoPago(): Collection
    {
        // 1) Pendientes locales, indexados por código para búsqueda rápida.
        $pendientes = $this->proveedoresConPendientes()->keyBy('codigo');

        // 2) Traer los proveedores de Wiese. Si falla, devolvemos solo los pendientes.
        try {
            $res = app(ProveedorApiService::class)->listarProveedoresWiese();
        } catch (\Throwable $e) {
            Log::warning('[FormatoPago] Wiese no disponible, muestro solo pendientes: '.$e->getMessage());
            return $pendientes->values();
        }

        if (! ($res['success'] ?? false)) {
            return $pendientes->values();
        }

        // 3) Fusionar: cada proveedor de Wiese, con sus pendientes si los tiene.
        $wiese = collect($res['data']['items'] ?? [])
            ->map(function ($p) use ($pendientes) {
                $codigo = trim((string) ($p['codigo'] ?? $p['Codigo'] ?? ''));
                $nombre = trim((string) ($p['nombre'] ?? $p['Nombre'] ?? ''));

                // Descartar basura de Wiese (vacíos, "0", o el genérico "(Ninguno)").
                if ($codigo === '' || $codigo === '0' || $nombre === ''
                    || stripos($codigo, 'ninguno') !== false
                    || stripos($nombre, 'ninguno') !== false) {
                    return null;
                }

                // ¿Este proveedor de Wiese tiene facturas pendientes locales?
                $pend = $pendientes->get($codigo);
                if ($pend) {
                    // Ya trae num_facturas/monto/notifs/expediente calculados; solo aseguramos el nombre de Wiese.
                    $pend->nombre = $pend->nombre ?: $nombre;

                    return $pend;
                }

                // Sin pendientes: fila "en cero" para poder buscarlo y registrarle pago desde cero.
                return (object) [
                    'codigo' => $codigo,
                    'proveedor' => null,
                    'nombre' => $nombre,
                    'num_facturas' => 0,
                    'monto_total' => 0.0,
                    'ultima_factura_at' => null,
                    'proximo_vencimiento' => null,
                    'expediente' => ['ok' => false, 'motivos' => ['Sin facturas pendientes']],
                    'notif_sin_leer' => 0,
                ];
            })
            ->filter(); // quita los null (basura)

        // 4) Ordenar: primero CON pendientes (el que DEBE MÁS arriba, por monto), luego el resto alfabético.
        $conPendientes = $wiese->filter(fn ($r) => ($r->num_facturas ?? 0) > 0)
            ->sortByDesc(fn ($r) => (float) ($r->monto_total ?? 0));
        $sinPendientes = $wiese->filter(fn ($r) => ($r->num_facturas ?? 0) === 0)
            ->sortBy(fn ($r) => mb_strtolower($r->nombre));

        return $conPendientes->concat($sinPendientes)->values();
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

        // POR QUÉ: si el pago agrupa VARIOS proveedores (carrito), el PDF B1 lleva una fila
        // de banco por proveedor en "Datos del pago". Aquí armamos ese arreglo agrupando las
        // líneas por proveedor y resolviendo el banco/cuenta/clabe/rfc de cada uno. Para pagos
        // normales (un proveedor) esto queda vacío y el PDF usa los campos sueltos de siempre.
        $proveedoresPago = [];
        if ($pago->es_multi_proveedor) {
            $proveedoresPago = $this->filasProveedoresMulti($pago);
        }

        return [
            'pago' => $pago,
            'esMulti' => (bool) $pago->es_multi_proveedor,
            'proveedoresPago' => $proveedoresPago, // filas B1 (solo multi-proveedor)
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

    /**
     * Arma las filas "Datos del pago" para un formato multi-proveedor (B1): una por proveedor,
     * con su banco/cuenta/clabe/rfc y el subtotal de sus facturas en este pago.
     *
     * @return list<array<string, mixed>>
     */
    private function filasProveedoresMulti(PagoProveedor $pago): array
    {
        // Agrupar las líneas del pago por código de proveedor.
        $porProveedor = $pago->lineas->groupBy(fn ($l) => (string) ($l->codigo_proveedor ?? $pago->codigo_proveedor));

        // Cache de datos bancarios por código (una consulta local por proveedor distinto).
        $filas = [];
        foreach ($porProveedor as $codigo => $lineas) {
            $prov = ProveedorUser::porCualquierCodigo((string) $codigo)->first();
            $db = is_array($prov?->datos_identificacion) ? $prov->datos_identificacion : [];
            $nombre = (string) ($lineas->first()->nombre_proveedor ?: ($prov?->nombre ?? $codigo));

            $subtotal = (float) $lineas->sum('neto');
            if ($subtotal <= 0) {
                $subtotal = (float) $lineas->sum('total');
            }

            $foliosProv = $lineas->map(fn ($l) => (string) $l->folio_cfdi)
                ->filter(fn ($v) => $v !== '' && ! $this->pareceUuid($v))
                ->unique()->values()->all();

            $filas[] = [
                'codigo' => (string) $codigo,
                'nombre' => $nombre,
                'banco' => trim((string) ($db['banco'] ?? '')),
                'cuenta' => preg_replace('/\D/', '', (string) ($db['cuenta'] ?? '')) ?: '',
                'clabe' => preg_replace('/\D/', '', (string) ($db['clabe'] ?? '')) ?: '',
                'swift' => trim((string) ($db['swift'] ?? $db['Swift'] ?? '')),
                'rfc' => strtoupper(trim((string) ($db['rfc'] ?? $db['RFC'] ?? ''))),
                'folios' => implode(', ', $foliosProv),
                'subtotal' => $subtotal,
            ];
        }

        return $filas;
    }

    /**
     * @param  list<string>  $tipos
     */
    private function ultimoDocumentoAprobado(ProveedorUser $proveedor, array $tipos): ?DocumentoProveedor
    {
        $proveedor->loadMissing('documentos');

        /** @var DocumentoProveedor|null $doc */
        $doc = $proveedor->documentos
            ->whereIn('tipo', $tipos)
            ->where('estatus', 'aprobado')
            ->sortByDesc(fn (DocumentoProveedor $d) => $d->revisado_at ?? $d->updated_at ?? $d->created_at)
            ->first();

        return $doc instanceof DocumentoProveedor ? $doc : null;
    }

    /**
     * @return array{
     *   clave: string,
     *   label: string,
     *   ok: bool,
     *   origen: string|null,
     *   documento_id: int|null,
     *   archivo: string|null,
     *   nombre: string|null,
     *   url: string|null,
     *   estatus: string|null
     * }
     */
    private function slotDocumentoFiscal(string $clave, string $label, ?DocumentoProveedor $doc): array
    {
        if (! $doc) {
            return [
                'clave' => $clave,
                'label' => $label,
                'ok' => false,
                'origen' => null,
                'documento_id' => null,
                'archivo' => null,
                'nombre' => null,
                'url' => null,
                'estatus' => null,
            ];
        }

        return [
            'clave' => $clave,
            'label' => $label,
            'ok' => true,
            'origen' => 'expediente',
            'documento_id' => $doc->id,
            'archivo' => $doc->archivo,
            'nombre' => basename((string) $doc->archivo) ?: $label.'.pdf',
            'url' => $this->urlArchivoDocumento($doc),
            'estatus' => $doc->estatus,
        ];
    }

    private function urlArchivoDocumento(DocumentoProveedor $doc): ?string
    {
        $archivo = ltrim((string) $doc->archivo, '/');
        if ($archivo === '') {
            return null;
        }

        if (Storage::disk('public')->exists($archivo)) {
            return asset('storage/'.$archivo);
        }

        return route('admin.expediente-fiscal.descargar', $doc);
    }

    private function copiarDocumentoAPago(PagoProveedor $pago, DocumentoProveedor $doc, string $clave): ?string
    {
        $contenido = $this->contenidoArchivoDocumento($doc);
        if ($contenido === null) {
            return null;
        }

        $ext = strtolower((string) pathinfo((string) $doc->archivo, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'pdf';
        }

        $dest = 'pagos_comprobantes/'.$pago->id.'/'.$clave.'.'.$ext;
        Storage::disk('public')->put($dest, $contenido);

        return $dest;
    }

    private function contenidoArchivoDocumento(DocumentoProveedor $doc): ?string
    {
        $archivo = ltrim((string) $doc->archivo, '/');
        if ($archivo === '') {
            return null;
        }

        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($archivo)) {
                    return Storage::disk($disk)->get($archivo);
                }
            } catch (\Throwable $e) {
                Log::warning('[PagoProveedor] No se pudo leer documento fiscal', [
                    'disk' => $disk,
                    'archivo' => $archivo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ([storage_path('app/public/'.$archivo), storage_path('app/'.$archivo)] as $path) {
            if (is_file($path)) {
                $contenido = file_get_contents($path);

                return $contenido === false ? null : $contenido;
            }
        }

        return null;
    }

    private function generarFormatoPagoPdf(PagoProveedor $pago): ?string
    {
        try {
            $data = $this->datosReporteResumen($pago);
            $pdf = Pdf::loadView('admin.pagos.reporte-resumen-pdf', $data)
                ->setPaper('letter', 'landscape');
            $dest = 'pagos_comprobantes/'.$pago->id.'/formato_pago.pdf';
            Storage::disk('public')->put($dest, $pdf->output());

            return $dest;
        } catch (\Throwable $e) {
            Log::warning('[PagoProveedor] No se pudo generar formato de pago PDF: '.$e->getMessage());

            return null;
        }
    }
}
