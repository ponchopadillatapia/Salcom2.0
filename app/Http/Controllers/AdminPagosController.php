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

    public function index()
    {
        $proveedoresPendientes = $this->pagos->proveedoresConPendientes();

        return view('admin.pagos.index', compact('proveedoresPendientes'));
    }

    public function proveedor(string $codigo)
    {
        $proveedor = ProveedorUser::whereCodigo($codigo)->firstOrFail();
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
                    $f->validacion_detalle = $vd;
                    $f->saveQuietly();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo marcar facturas como vistas: '.$e->getMessage());
        }

        return view('admin.pagos.proveedor', compact('proveedor', 'codigo', 'facturas', 'expediente', 'idsFacturasNoVistas'));
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

        return view('admin.pagos.show', compact(
            'pago',
            'expediente',
            'datosAuto',
            'errorDatosAuto',
            'tieneMasFacturasPendientes',
            'docsFiscales'
        ));
    }

    public function confirmar(Request $request, PagoProveedor $pago)
    {
        $request->validate([
            'fecha_pago' => 'nullable|date',
            'comprobantes' => 'nullable|array',
            'comprobantes.*' => 'file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
        ]);

        $paths = [];
        foreach ($request->file('comprobantes', []) as $file) {
            $paths[] = $file->store('pagos_comprobantes/'.$pago->id, 'public');
        }

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
        $proveedor = ProveedorUser::whereCodigo($codigo)->firstOrFail();
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
        $request->validate(['notas' => 'nullable|string|max:1000']);

        $pago->update([
            'estatus_autorizacion' => 'autorizado',
            'autorizado_por' => session('admin_id'),
            'autorizado_por_nombre' => session('admin_nombre'),
            'autorizado_at' => now(),
            'notas_autorizacion' => $request->input('notas'),
        ]);

        AuditService::registrar(
            'autorizar',
            'pagos',
            (session('admin_nombre') ?? 'Admin').' autorizó el expediente de pago #'.$pago->id,
            null,
            ['pago_id' => $pago->id, 'monto' => (float) $pago->monto_total]
        );

        return back()->with('mensaje', 'Expediente autorizado.');
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
