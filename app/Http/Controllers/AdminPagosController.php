<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Services\PagoProveedorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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

        return view('admin.pagos.proveedor', compact('proveedor', 'codigo', 'facturas', 'expediente'));
    }

    /** Campanita admin: facturas nuevas pendientes de pago. */
    public function alertasJson()
    {
        $sinLeer = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->where('tipo', 'factura_pago_pendiente')
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->count();

        $items = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->where('tipo', 'factura_pago_pendiente')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(function (Alerta $a) {
                $codigo = $a->datos['codigo_proveedor'] ?? null;

                return [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'contenido' => $a->contenido,
                    'leida' => in_array($a->estatus, ['leida', 'accionada'], true),
                    'hace' => optional($a->created_at)->diffForHumans(),
                    'url' => $codigo ? route('admin.pagos.proveedor', $codigo) : route('admin.pagos'),
                ];
            });

        return response()
            ->json(['sin_leer' => $sinLeer, 'items' => $items])
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    public function marcarAlertaLeida(Alerta $alerta)
    {
        if ($alerta->destinatario_tipo !== 'admin' || $alerta->tipo !== 'factura_pago_pendiente') {
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
        $pago->load(['lineas.factura', 'proveedor']);
        $expediente = $pago->proveedor
            ? $this->pagos->evaluarExpediente($pago->proveedor)
            : ['ok' => false, 'motivos' => ['Sin proveedor']];

        $datosAuto = null;
        $errorDatosAuto = null;
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
            'tieneMasFacturasPendientes'
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
        $filename = 'Reporte_Resumen_Pagos_'.$pago->codigo_proveedor.'_lote'.$pago->id.'_'.now()->format('Y-m-d').'.pdf';

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
}
