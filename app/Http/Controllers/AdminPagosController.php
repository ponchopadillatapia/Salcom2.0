<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Services\PagoProveedorService;
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
        ]);

        $proveedor = ProveedorUser::whereCodigo($data['codigo_proveedor'])->firstOrFail();

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

        $prefill = [
            'forma_pago' => '',
            'metodo_pago' => '',
            'uso_cfdi' => '',
            'regimen' => '',
            'producto' => '',
        ];
        $primera = $pago->lineas->first()?->factura;
        if ($primera) {
            $det = is_array($primera->validacion_detalle) ? $primera->validacion_detalle : [];
            $prefill['forma_pago'] = (string) ($det['forma_pago'] ?? '');
            $prefill['metodo_pago'] = (string) ($det['metodo_pago'] ?? '');
            $prefill['uso_cfdi'] = (string) ($det['uso_cfdi'] ?? '');
            $prefill['regimen'] = (string) ($primera->regimen_fiscal ?: ($det['regimen_fiscal'] ?? ''));
            $prefill['producto'] = (string) ($det['producto'] ?? $det['descripcion'] ?? $primera->notas ?? '');
        }

        return view('admin.pagos.show', compact('pago', 'expediente', 'prefill'));
    }

    public function confirmar(Request $request, PagoProveedor $pago)
    {
        $formas = array_keys(config('facturas.formas_pago', []));
        $metodos = array_keys(config('facturas.metodos_pago', []));
        $usos = array_keys(config('facturas.usos_cfdi', []));
        $regimenes = array_keys(config('facturas.regimenes_aceptados', []));

        $request->validate([
            'fecha_pago' => 'nullable|date',
            'forma_pago' => 'required|string|in:'.implode(',', $formas ?: ['01', '02', '03', '04', '28', '99']),
            'metodo_pago' => 'required|string|in:'.implode(',', $metodos ?: ['PUE', 'PPD']),
            'uso_cfdi' => 'required|string|in:'.implode(',', $usos ?: ['G01', 'G03', 'P01']),
            'regimen' => 'required|string|in:'.implode(',', $regimenes ?: array_keys(config('facturas.regimenes', []))),
            'producto' => 'required|string|max:255',
            'comprobantes' => 'required|array|min:1',
            'comprobantes.*' => 'file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
        ], [
            'forma_pago.required' => 'Selecciona la forma de pago.',
            'metodo_pago.required' => 'Selecciona el método de pago.',
            'uso_cfdi.required' => 'Selecciona el uso de CFDI.',
            'regimen.required' => 'Selecciona el régimen.',
            'producto.required' => 'Indica el producto / concepto.',
            'comprobantes.required' => 'Sube al menos un documento para confirmar el pago.',
            'comprobantes.min' => 'Sube al menos un documento para confirmar el pago.',
        ]);

        $paths = [];
        foreach ($request->file('comprobantes', []) as $file) {
            $paths[] = $file->store('pagos_comprobantes/'.$pago->id, 'public');
        }

        $datos = [
            'forma_pago' => $request->input('forma_pago'),
            'metodo_pago' => $request->input('metodo_pago'),
            'uso_cfdi' => $request->input('uso_cfdi'),
            'regimen' => $request->input('regimen'),
            'producto' => trim((string) $request->input('producto')),
        ];

        try {
            $this->pagos->confirmar(
                $pago,
                session('admin_id'),
                $paths,
                $request->input('fecha_pago'),
                $datos
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('mensaje', 'Pago confirmado. Datos fiscales y documentos guardados.');
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
}
