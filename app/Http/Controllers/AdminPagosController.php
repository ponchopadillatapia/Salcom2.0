<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Services\AuditService;
use App\Services\PagoProveedorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class AdminPagosController extends Controller
{
    public function __construct(private PagoProveedorService $pagos) {}

    public function index(Request $request)
    {
        // POR QUÉ: la vista lista TODOS los proveedores de Wiese (5,685), con los que tienen
        // facturas pendientes arriba. Como son miles, se pagina de 50 en 50 para no reventar
        // el navegador. El filtrado (búsqueda) y la paginación se hacen aquí, no en la vista.
        $q = trim((string) $request->query('q', ''));
        $codigo = trim((string) $request->query('codigo', ''));
        $expediente = trim((string) $request->query('expediente', '')); // '' | ok | pendiente | sin_revisar

        // Lista completa fusionada (Wiese + pendientes locales, pendientes arriba).
        $todos = $this->pagos->proveedoresParaFormatoPago();

        // KPIs: se calculan solo sobre los que tienen facturas pendientes (no sobre los 5,685).
        $conPendientes = $todos->filter(fn ($r) => ($r->num_facturas ?? 0) > 0);
        $kpiSinRevisar = $conPendientes->filter(fn ($r) => ($r->notif_sin_leer ?? 0) > 0)->count();
        $kpiExpOk = $conPendientes->filter(fn ($r) => ! empty($r->expediente['ok']))->count();
        $kpiExpPend = $conPendientes->filter(fn ($r) => empty($r->expediente['ok']))->count();
        $kpiTotales = $conPendientes->count();

        // Aplicar filtros de búsqueda sobre TODA la lista.
        $filtrada = $todos;
        if ($q !== '') {
            $filtrada = $filtrada->filter(fn ($r) => str_contains(mb_strtolower($r->nombre), mb_strtolower($q))
                || str_contains((string) $r->codigo, $q));
        }
        if ($codigo !== '') {
            $filtrada = $filtrada->filter(fn ($r) => str_contains((string) $r->codigo, $codigo));
        }
        if ($expediente === 'sin_revisar') {
            $filtrada = $filtrada->filter(fn ($r) => ($r->notif_sin_leer ?? 0) > 0);
        } elseif ($expediente === 'ok') {
            $filtrada = $filtrada->filter(fn ($r) => ! empty($r->expediente['ok']));
        } elseif ($expediente === 'pendiente') {
            $filtrada = $filtrada->filter(fn ($r) => empty($r->expediente['ok']));
        }
        $filtrada = $filtrada->values();

        // Paginar la colección de 50 en 50. Como es una Collection (no query), se usa
        // LengthAwarePaginator manualmente: se corta la página actual con slice().
        $porPagina = 50;
        $pagina = max(1, (int) $request->query('page', 1));
        $itemsPagina = $filtrada->slice(($pagina - 1) * $porPagina, $porPagina)->values();

        $proveedoresPendientes = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsPagina,
            $filtrada->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.pagos.index', compact(
            'proveedoresPendientes',
            'kpiSinRevisar',
            'kpiExpOk',
            'kpiExpPend',
            'kpiTotales',
            'q',
            'codigo',
            'expediente'
        ));
    }

    public function proveedor(string $codigo)
    {
        // Buscar primero en la base local. Si no está, es un proveedor que solo vive en
        // Wiese (los 5,685): lo resolvemos desde la API y armamos un ProveedorUser EN MEMORIA
        // (sin guardarlo) para que la pantalla cargue y se le pueda registrar el pago.
        // POR QUÉ: antes hacía firstOrFail() y tronaba con 404 para cualquier proveedor de Wiese.
        $proveedor = ProveedorUser::porCualquierCodigo($codigo)->first();

        if (! $proveedor) {
            $proveedor = $this->proveedorWieseEnMemoria($codigo);
        }

        // firstOrFail real: si NO está ni en local ni en Wiese, entonces sí es 404.
        if (! $proveedor) {
            abort(404, 'Proveedor no encontrado en el sistema local ni en Wiese.');
        }

        $expediente = $this->pagos->evaluarExpediente($proveedor);

        // Al abrir el proveedor, se marcan como vistas las notifs de pago pendiente
        Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->where('tipo', 'factura_pago_pendiente')
            ->where('datos->codigo_proveedor', $codigo)
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->update(['estatus' => 'leida', 'leida_at' => now()]);

        $facturas = Factura::query()
            ->where('codigo_proveedor', $codigo)
            ->where('estatus', 'pendiente')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Factura $f) {
                $f->avisos_pago = $this->pagos->avisosFactura($f);
                $f->neto_pago = $this->pagos->netoFactura($f);
                $f->folio_display = $this->pagos->folioFacturaDisplay($f);

                return $f;
            });

        // Patrón "visto": punto rojo en las facturas nuevas sin ver.
        // Capturamos las no vistas ANTES de marcarlas, y luego las marcamos como vistas.
        $idsFacturasNoVistas = $facturas->filter(function (Factura $f) {
            $vd = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];

            return empty($vd['visto_pago']);
        })->pluck('id')->all();

        try {
            foreach ($facturas as $f) {
                $vd = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
                if (empty($vd['visto_pago'])) {
                    $vd['visto_pago'] = true;
                    // Actualizamos SOLO la columna validacion_detalle con una consulta directa,
                    // para no arrastrar los atributos calculados (avisos_pago, neto_pago,
                    // folio_display) que le pegamos arriba y que NO existen como columnas.
                    Factura::whereKey($f->id)->update(['validacion_detalle' => $vd]);
                    // Reflejamos el cambio en el objeto en memoria por si la vista lo consulta.
                    $f->validacion_detalle = $vd;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo marcar facturas como vistas: '.$e->getMessage());
        }

        // Facturas PENDIENTES desde Wiese (por RFC). POR QUÉ: las facturas reales de compra
        // viven en Wiese; el endpoint ListarDocumentosRFC (de Alan) las trae con su saldo.
        // Solo se muestran las de saldo > 0 (lo que se debe pagar). Si no hay RFC o Wiese
        // no responde, la sección simplemente no aparece (no rompe la pantalla).
        $facturasWiese = collect();
        $wieseError = null;
        $rfc = '';
        $di = $proveedor->datos_identificacion;
        if (is_array($di)) {
            $rfc = trim((string) ($di['rfc'] ?? ''));
        }
        if ($rfc !== '') {
            try {
                $res = app(\App\Services\ProveedorApiService::class)->listarFacturasProveedorPorRFC($rfc);
                if ($res['success'] ?? false) {
                    // Solo pendientes (saldo > 0), más recientes arriba.
                    $facturasWiese = collect($res['data']['items'] ?? [])
                        ->where('pendiente', true)
                        ->sortByDesc('fecha_factura')
                        ->values();
                } else {
                    $wieseError = $res['message'] ?? 'No se pudieron cargar las facturas de Wiese.';
                }
            } catch (\Throwable $e) {
                $wieseError = 'No se pudo conectar con Wiese (¿VPN activa?).';
            }
        }

        return view('admin.pagos.proveedor', compact('proveedor', 'codigo', 'facturas', 'expediente', 'idsFacturasNoVistas', 'facturasWiese', 'wieseError', 'rfc'));
    }

    /**
     * Arma un ProveedorUser EN MEMORIA (no guardado en la base) a partir de los datos
     * de Wiese, para proveedores que solo existen allá (los 5,685).
     *
     * POR QUÉ no se guarda: el detalle de pago solo necesita mostrar el nombre y evaluar
     * expediente. Crear el registro local recién aquí ensuciaría la base con miles de
     * proveedores. Si más adelante se le registra un pago, ese flujo ya crea/enlaza lo que necesite.
     *
     * @return ProveedorUser|null  null si tampoco está en Wiese (o Wiese no responde).
     */
    private function proveedorWieseEnMemoria(string $codigo): ?ProveedorUser
    {
        try {
            $res = app(\App\Services\ProveedorApiService::class)->buscarProveedorWiese($codigo);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[PagoProveedor] Wiese no disponible al abrir proveedor: '.$e->getMessage());

            return null;
        }

        if (! ($res['success'] ?? false)) {
            return null;
        }

        $d = $res['data'] ?? [];
        // Wiese usa nombres de campo variados; se cubren mayúsculas/minúsculas.
        $nombre = trim((string) ($d['nombre'] ?? $d['Nombre'] ?? $d['crazonsocial'] ?? $d['CRAZONSOCIAL'] ?? ''));
        $rfc = trim((string) ($d['rfc'] ?? $d['Rfc'] ?? $d['crfc'] ?? $d['CRFC'] ?? ''));
        $monRaw = (string) ($d['moneda'] ?? $d['Moneda'] ?? $d['cidmoneda'] ?? $d['CIDMONEDA'] ?? '');
        $moneda = $monRaw === '2' ? 'DOLLAR' : 'MXN';

        if ($nombre === '') {
            return null; // sin nombre no hay proveedor válido
        }

        // Modelo NO persistido: se rellenan solo los campos que la vista y evaluarExpediente usan.
        $prov = new ProveedorUser;
        $prov->codigo = $codigo;
        $prov->id_proveedor = $codigo;
        $prov->nombre = $nombre;
        $prov->moneda = $moneda;
        $prov->datos_identificacion = ['rfc' => $rfc];
        // Relación documentos vacía en memoria: evita consultas y evaluarExpediente lo trata
        // como expediente incompleto (correcto: un proveedor de Wiese no tiene expediente local).
        $prov->setRelation('documentos', collect());

        return $prov;
    }

    /** Campanita admin: facturas nuevas pendientes de pago. */
    public function alertasJson()
    {
        $tipos = ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'];

        $sinLeer = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->whereIn('tipo', $tipos)
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->count();

        $items = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->whereIn('tipo', $tipos)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(function (Alerta $a) {
                $codigo = $a->datos['codigo_proveedor'] ?? null;
                $url = match ($a->tipo) {
                    'pago_programado' => route('admin.pagos'),
                    'pago_realizado' => route('admin.pago-proveedores'),
                    'abono_interno_registrado' => route('admin.historial-abonos'),
                    default => $codigo ? route('admin.pagos.proveedor', $codigo) : route('admin.pagos'),
                };

                return [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'contenido' => $a->contenido,
                    'leida' => in_array($a->estatus, ['leida', 'accionada'], true),
                    'hace' => optional($a->created_at)->diffForHumans(),
                    'url' => $url,
                ];
            });

        return response()
            ->json(['sin_leer' => $sinLeer, 'items' => $items])
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    public function marcarAlertaLeida(Alerta $alerta)
    {
        $tiposPermitidos = ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'];
        if ($alerta->destinatario_tipo !== 'admin' || !in_array($alerta->tipo, $tiposPermitidos)) {
            return response()->json(['ok' => false, 'mensaje' => 'No autorizado'], 403);
        }

        if (! in_array($alerta->estatus, ['leida', 'accionada'], true)) {
            $alerta->update(['estatus' => 'leida', 'leida_at' => now()]);
        }

        $codigo = $alerta->datos['codigo_proveedor'] ?? null;
        $sinLeer = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->where('tipo', 'factura_pago_pendiente')
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->count();

        return response()->json([
            'ok' => true,
            'sin_leer' => $sinLeer,
            'id' => $alerta->id,
            'url' => $codigo ? route('admin.pagos.proveedor', $codigo) : route('admin.pagos'),
        ]);
    }

    /** Marca todas las alertas de la campanita admin como leídas al abrirla. */
    public function marcarTodasAlertasLeidas()
    {
        $tipos = ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'];

        Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->whereIn('tipo', $tipos)
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->update(['estatus' => 'leida', 'leida_at' => now()]);

        return response()->json([
            'ok' => true,
            'sin_leer' => 0,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo_proveedor' => 'required|string',
            'factura_ids' => 'required|array|min:1',
            'factura_ids.*' => 'integer',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:1000',
            'confirmar' => 'nullable|boolean',
        ]);

        $proveedor = ProveedorUser::whereCodigo($data['codigo_proveedor'])->firstOrFail();
        $autoConfirmar = $request->boolean('confirmar');

        try {
            $pago = $this->pagos->crearLote(
                $proveedor,
                $data['factura_ids'],
                $data['fecha_pago'] ?? null,
                $data['notas'] ?? null,
                session('admin_id')
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($autoConfirmar) {
            try {
                $this->pagos->confirmar(
                    $pago,
                    session('admin_id'),
                    [],
                    $data['fecha_pago'] ?? null,
                    []
                );
                $pago = $pago->fresh();

                return redirect()
                    ->route('admin.pagos.show', ['pago' => $pago, 'descargar_reporte' => 1])
                    ->with('mensaje', 'Pago confirmado automáticamente. Descargando reporte resumen…');
            } catch (InvalidArgumentException $e) {
                return redirect()
                    ->route('admin.pagos.show', $pago)
                    ->with('error', $e->getMessage());
            }
        }

        return redirect()
            ->route('admin.pagos.show', $pago)
            ->with('mensaje', 'Pago creado en borrador. Revisa y confirma.');
    }

    public function show(PagoProveedor $pago)
    {
        $pago->load(['lineas.factura', 'proveedor.documentos']);
        $expediente = $pago->proveedor
            ? $this->pagos->evaluarExpediente($pago->proveedor)
            : ['ok' => false, 'motivos' => ['Sin proveedor']];

        $datosAuto = null;
        $errorDatosAuto = null;
        $docsFiscales = $this->pagos->documentosFiscalesParaPago($pago);
        if ($pago->esBorrador()) {
            try {
                $datosAuto = $this->pagos->datosConfirmacionDesdeFacturas($pago);
            } catch (InvalidArgumentException $e) {
                $errorDatosAuto = $e->getMessage();
            }
        }

        $tieneMasFacturasPendientes = $pago->codigo_proveedor
            ? Factura::query()
                ->where('codigo_proveedor', $pago->codigo_proveedor)
                ->where('estatus', 'pendiente')
                ->exists()
            : false;

        // Facturas del lote precargadas (PDF/XML) para verlas a mano y verificar.
        $facturasLote = [];
        foreach ($pago->lineas as $linea) {
            $f = $linea->factura;
            if (! $f) {
                continue;
            }
            $folio = $this->pagos->folioFacturaDisplay($f);
            $pdfUrl = $f->archivo_pdf && \Illuminate\Support\Facades\Storage::disk('public')->exists($f->archivo_pdf)
                ? asset('storage/'.$f->archivo_pdf) : null;
            $xmlUrl = $f->archivo_xml && \Illuminate\Support\Facades\Storage::disk('public')->exists($f->archivo_xml)
                ? asset('storage/'.$f->archivo_xml) : null;
            $facturasLote[] = [
                'folio' => $folio,
                'total' => (float) $f->total,
                'pdf_url' => $pdfUrl,
                'xml_url' => $xmlUrl,
                'tiene_archivo' => $pdfUrl !== null || $xmlUrl !== null,
            ];
        }

        return view('admin.pagos.show', compact(
            'pago',
            'expediente',
            'datosAuto',
            'errorDatosAuto',
            'tieneMasFacturasPendientes',
            'docsFiscales',
            'facturasLote'
        ));
    }

    public function confirmar(Request $request, PagoProveedor $pago)
    {
        $request->validate([
            'fecha_pago' => 'nullable|date',
        ]);

        $paths = [];

        try {
            $this->pagos->confirmar(
                $pago,
                session('admin_id'),
                $paths,
                $request->input('fecha_pago'),
                []
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.pagos.show', ['pago' => $pago, 'descargar_reporte' => 1])
            ->with('mensaje', 'Pago confirmado. Descargando reporte resumen…');
    }

    public function cancelar(PagoProveedor $pago)
    {
        try {
            $this->pagos->cancelarBorrador($pago);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.pagos')->with('mensaje', 'Borrador cancelado.');
    }

    public function excel(PagoProveedor $pago)
    {
        $lines = $this->pagos->filasExcel($pago);
        $filename = 'Pago_'.$pago->codigo_proveedor.'_lote'.$pago->id.'_'.now()->format('Y-m-d').'.csv';

        $output = "\xEF\xBB\xBF";
        foreach ($lines as $line) {
            $output .= collect($line)->map(function ($cell) {
                $cell = (string) $cell;
                if (str_contains($cell, ',') || str_contains($cell, '"') || str_contains($cell, "\n")) {
                    return '"'.str_replace('"', '""', $cell).'"';
                }

                return $cell;
            })->implode(',')."\r\n";
        }

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /** PDF «REPORTE RESUMEN DE PAGOS» (formato Contabilidad / FCONA). */
    public function reporteResumen(PagoProveedor $pago)
    {
        $pago->load(['lineas.factura', 'proveedor']);
        $data = $this->pagos->datosReporteResumen($pago);
        $filename = 'Formato_para_pago_'.$pago->codigo_proveedor.'_lote'.$pago->id.'_'.now()->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('admin.pagos.reporte-resumen-pdf', $data)
            ->setPaper('letter', 'landscape');

        // Auto-descarga al confirmar; botón "Ver" abre inline en el navegador.
        if (request()->boolean('ver')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /** Estado de cuenta histórico del proveedor (CSV). */
    public function estadoCuenta(string $codigo)
    {
        // Igual que proveedor(): si no está en local, resolver desde Wiese (en memoria).
        $proveedor = ProveedorUser::porCualquierCodigo($codigo)->first()
            ?? $this->proveedorWieseEnMemoria($codigo);
        if (! $proveedor) {
            abort(404, 'Proveedor no encontrado en el sistema local ni en Wiese.');
        }
        $facturas = Factura::query()
            ->where('codigo_proveedor', $codigo)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'Estado_Cuenta_' . $codigo . '_' . now()->format('Y-m-d') . '.csv';

        $output = "\xEF\xBB\xBF"; // BOM UTF-8
        $output .= "ESTADO DE CUENTA - " . $proveedor->nombre . " (" . $codigo . ")\r\n";
        $output .= "Generado: " . now()->format('d/m/Y H:i') . "\r\n\r\n";
        $output .= "Fecha,Folio,Total,Monto Pagado,Saldo,Estatus,Última actualización\r\n";

        foreach ($facturas as $f) {
            $saldo = round((float) $f->total - (float) $f->monto_pagado, 2);
            $output .= implode(',', [
                $f->created_at?->format('d/m/Y') ?? '',
                '"' . ($f->folio_cfdi ?: $f->id) . '"',
                number_format((float) $f->total, 2, '.', ''),
                number_format((float) $f->monto_pagado, 2, '.', ''),
                number_format($saldo, 2, '.', ''),
                $f->estatus,
                $f->updated_at?->format('d/m/Y H:i') ?? '',
            ]) . "\r\n";
        }

        $totalFacturado = $facturas->sum('total');
        $totalPagado = $facturas->sum('monto_pagado');
        $totalSaldo = round($totalFacturado - $totalPagado, 2);

        $output .= "\r\n";
        $output .= ",TOTALES," . number_format($totalFacturado, 2, '.', '') . "," . number_format($totalPagado, 2, '.', '') . "," . number_format($totalSaldo, 2, '.', '') . ",,\r\n";

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    // ── Expediente de Pago ──

    /** NIVEL 1 — Archivero: lista de proveedores (carpetas), con cuántos expedientes tiene cada uno. */
    public function expedientes(Request $request)
    {
        $busqueda = trim((string) $request->input('busqueda', ''));

        $query = PagoProveedor::with('proveedor')
            ->where('estatus', 'confirmado'); // solo pagos confirmados tienen expediente

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('codigo_proveedor', 'like', "%{$busqueda}%")
                    ->orWhereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$busqueda}%"));
            });
        }

        $pagos = $query->get();

        // Agrupar por proveedor. Cada carpeta = un proveedor con sus totales.
        // Ordenados por el expediente MÁS RECIENTE arriba (como van llegando).
        // Punto azul: el proveedor tiene expedientes que aún NO se han abierto (estilo WhatsApp).
        $proveedores = $pagos->groupBy(fn ($p) => $p->proveedor->nombre ?? $p->codigo_proveedor ?? 'Sin proveedor')
            ->map(function ($exps, $nombre) {
                $primero = $exps->first();
                $noVistos = $exps->filter(function ($e) {
                    $dc = is_array($e->datos_confirmacion) ? $e->datos_confirmacion : [];

                    return empty($dc['visto_archivero']);
                })->count();

                return (object) [
                    'nombre' => $nombre,
                    'codigo' => $primero->codigo_proveedor,
                    'proveedor_id' => $primero->proveedor_id,
                    'total' => $exps->count(),
                    'pendientes' => $exps->where('estatus_autorizacion', 'pendiente')->count(),
                    'no_vistos' => $noVistos,
                    'monto_total' => $exps->sum('monto_total'),
                    'ultimo_at' => $exps->max(fn ($e) => $e->confirmado_at ?? $e->created_at),
                ];
            })
            ->sortByDesc('ultimo_at')
            ->values();

        $total = $pagos->count();

        return view('admin.pagos.expedientes', compact('proveedores', 'busqueda', 'total'));
    }

    /** NIVEL 2 — Expedientes de un proveedor, agrupados por mes (mes actual abierto). */
    public function expedientesProveedor(Request $request, ProveedorUser $proveedor)
    {
        $estatus = $request->input('estatus', ''); // '' | pendiente | autorizado | rechazado

        $query = PagoProveedor::with('proveedor')
            ->where('estatus', 'confirmado')
            ->where('proveedor_id', $proveedor->id)
            ->orderByDesc('confirmado_at')
            ->orderByDesc('id');

        if (in_array($estatus, ['pendiente', 'autorizado', 'rechazado'], true)) {
            $query->where('estatus_autorizacion', $estatus);
        }

        $pagos = $query->get();

        // Marcar qué expedientes NO se habían visto (para el punto rojo estilo WhatsApp),
        // ANTES de marcarlos como vistos.
        $idsNoVistos = $pagos->filter(function ($p) {
            $dc = is_array($p->datos_confirmacion) ? $p->datos_confirmacion : [];

            return empty($dc['visto_archivero']);
        })->pluck('id')->all();

        // "Visto" estilo WhatsApp: al abrir el proveedor, sus expedientes quedan marcados
        // como vistos (flag en datos_confirmacion). No cambia estatus ni autorización.
        try {
            foreach ($pagos as $p) {
                $dc = is_array($p->datos_confirmacion) ? $p->datos_confirmacion : [];
                if (empty($dc['visto_archivero'])) {
                    $dc['visto_archivero'] = true;
                    $p->datos_confirmacion = $dc;
                    $p->saveQuietly();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo marcar expedientes como vistos: '.$e->getMessage());
        }

        // Agrupar por MES/AÑO: el mes actual abierto, los anteriores como carpetas.
        $grupos = $pagos->groupBy(function ($p) {
            $fecha = $p->confirmado_at ?? $p->created_at;

            return $fecha ? $fecha->format('Y-m') : 'sin-fecha';
        });

        $mesActual = now()->format('Y-m');
        $total = $pagos->count();

        return view('admin.pagos.expedientes-proveedor', compact('proveedor', 'grupos', 'estatus', 'total', 'mesActual', 'idsNoVistos'));
    }

    /** Pantalla del expediente: todos los documentos del pago juntos + autorización. */
    public function expediente(PagoProveedor $pago)
    {
        $pago->load(['lineas.factura', 'proveedor']);
        $grupos = $this->pagos->documentosExpedientePago($pago);

        return view('admin.pagos.expediente', compact('pago', 'grupos'));
    }

    /** Adjuntar un documento manual al expediente (póliza de Contpaqi, hojas engrapadas, etc.). */
    public function adjuntarDocumento(Request $request, PagoProveedor $pago)
    {
        $data = $request->validate([
            'tipo' => 'required|string|max:100',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png,xml|max:20480',
        ]);

        $ruta = $request->file('archivo')->store('pagos_comprobantes/'.$pago->id.'/adjuntos', 'public');

        $adjuntos = $pago->documentos_adjuntos ?? [];
        $adjuntos[] = [
            'tipo' => $data['tipo'],
            'nombre' => $data['tipo'].' — '.$request->file('archivo')->getClientOriginalName(),
            'archivo' => $ruta,
            'subido_por' => session('admin_nombre'),
            'subido_at' => now()->toDateTimeString(),
        ];
        $pago->update(['documentos_adjuntos' => $adjuntos]);

        AuditService::registrar('adjuntar', 'pagos', "Adjuntó {$data['tipo']} al expediente de pago #{$pago->id}");

        return back()->with('mensaje', 'Documento adjuntado al expediente.');
    }

    /** Eliminar un adjunto manual del expediente. */
    public function eliminarAdjunto(PagoProveedor $pago, int $indice)
    {
        $adjuntos = $pago->documentos_adjuntos ?? [];
        if (isset($adjuntos[$indice])) {
            if (! empty($adjuntos[$indice]['archivo'])) {
                Storage::disk('public')->delete($adjuntos[$indice]['archivo']);
            }
            unset($adjuntos[$indice]);
            $pago->update(['documentos_adjuntos' => array_values($adjuntos)]);
            AuditService::registrar('eliminar', 'pagos', "Eliminó un adjunto del expediente de pago #{$pago->id}");
        }

        return back()->with('mensaje', 'Documento eliminado del expediente.');
    }

    /** Autorizar el expediente de pago (firma digital de Sandra/Karen). */
    public function autorizarExpediente(Request $request, PagoProveedor $pago)
    {
        $request->validate([
            'notas' => 'nullable|string|max:1000',
        ]);

        $pago->load(['lineas.factura', 'proveedor']);

        $autorizadoPor = session('admin_nombre') ?? 'Admin';
        $fechaHora = now();
        $ip = $request->ip();
        $notas = $request->input('notas');
        $folioAut = 'AUT-'.str_pad((string) $pago->id, 5, '0', STR_PAD_LEFT);

        // Sello electrónico: hash SHA-256 de la cadena original (datos del pago + quién + cuándo).
        // Actúa como "timbre local" que garantiza integridad e identidad.
        $cadenaOriginal = implode('|', [
            'EXP:'.$pago->id,
            'PROV:'.$pago->codigo_proveedor,
            'MONTO:'.number_format((float) $pago->monto_total, 2, '.', ''),
            'FACT:'.$pago->num_facturas,
            'POR:'.$autorizadoPor,
            'ADMIN:'.session('admin_id'),
            'FECHA:'.$fechaHora->toIso8601String(),
            'IP:'.$ip,
        ]);
        $hash = hash('sha256', $cadenaOriginal.'|'.config('app.key'));

        // Generar el PDF de autorización timbrado y guardarlo en el expediente.
        try {
            $pdf = Pdf::loadView('admin.pagos.autorizacion-pdf', [
                'pago' => $pago,
                'folioAut' => $folioAut,
                'autorizadoPor' => $autorizadoPor,
                'fechaHora' => $fechaHora->format('d/m/Y H:i:s'),
                'ip' => $ip,
                'notas' => $notas,
                'hash' => $hash,
            ])->setPaper('letter');

            $rutaPdf = 'pagos_comprobantes/'.$pago->id.'/adjuntos/autorizacion_'.$folioAut.'.pdf';
            Storage::disk('public')->put($rutaPdf, $pdf->output());

            $adjuntos = $pago->documentos_adjuntos ?? [];
            $adjuntos[] = [
                'tipo' => 'Autorización timbrada',
                'nombre' => 'Autorización '.$folioAut.' (sellada)',
                'archivo' => $rutaPdf,
                'subido_por' => $autorizadoPor,
                'subido_at' => $fechaHora->toDateTimeString(),
                'hash' => $hash,
            ];
            $pago->documentos_adjuntos = $adjuntos;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Autorizacion] No se pudo generar el PDF timbrado: '.$e->getMessage());
        }

        $pago->fill([
            'estatus_autorizacion' => 'autorizado',
            'autorizado_por' => session('admin_id'),
            'autorizado_por_nombre' => $autorizadoPor,
            'autorizado_at' => $fechaHora,
            'notas_autorizacion' => $notas,
        ])->save();

        AuditService::registrar(
            'autorizar',
            'pagos',
            $autorizadoPor.' autorizó el expediente de pago #'.$pago->id,
            null,
            ['pago_id' => $pago->id, 'monto' => (float) $pago->monto_total, 'hash' => $hash]
        );

        return back()->with('mensaje', 'Expediente autorizado. Se generó el comprobante de autorización sellado.');
    }

    /** Rechazar el expediente de pago. */
    public function rechazarExpediente(Request $request, PagoProveedor $pago)
    {
        $request->validate(['notas' => 'nullable|string|max:1000']);

        $pago->update([
            'estatus_autorizacion' => 'rechazado',
            'autorizado_por' => session('admin_id'),
            'autorizado_por_nombre' => session('admin_nombre'),
            'autorizado_at' => now(),
            'notas_autorizacion' => $request->input('notas'),
        ]);

        AuditService::registrar(
            'rechazar',
            'pagos',
            (session('admin_nombre') ?? 'Admin').' rechazó el expediente de pago #'.$pago->id,
            null,
            ['pago_id' => $pago->id, 'motivo' => $request->input('notas')]
        );

        return back()->with('mensaje', 'Expediente rechazado.');
    }
}
