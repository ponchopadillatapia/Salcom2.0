<?php

namespace App\Http\Controllers;

use App\Models\AbonoProveedor;
use App\Models\Factura;
use App\Models\ProveedorUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminPagoProveedoresController extends Controller
{
    public function index(Request $request)
    {
        $polizas = config('polizas_pago');
        $filtro = (string) $request->query('poliza', '');
        $q = trim((string) $request->query('q', ''));

        $abonos = AbonoProveedor::query()
            ->with('proveedor')
            ->when($filtro !== '' && isset($polizas[$filtro]), fn ($qb) => $qb->where('poliza_key', $filtro))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('codigo_proveedor', 'like', "%{$q}%")
                        ->orWhere('nombre_proveedor', 'like', "%{$q}%")
                        ->orWhere('folio', 'like', "%{$q}%")
                        ->orWhere('serie', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $conteos = AbonoProveedor::query()
            ->selectRaw('poliza_key, count(*) as total')
            ->groupBy('poliza_key')
            ->pluck('total', 'poliza_key');

        return view('admin.pago-proveedores.index', compact('abonos', 'polizas', 'filtro', 'q', 'conteos'));
    }

    /** Paso 1: elegir póliza (Nuevo pago → Póliza). */
    public function nuevo()
    {
        $polizas = config('polizas_pago');

        return view('admin.pago-proveedores.nuevo', compact('polizas'));
    }

    /** Paso 2: formulario tipo Abono Prov Contpaqi. */
    public function create(string $poliza)
    {
        try {
            $meta = $this->polizaOrFail($poliza);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.pago-proveedores.nuevo')->with('error', $e->getMessage());
        }
        $proveedores = ProveedorUser::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->orderBy('moneda')
            ->get(['id', 'nombre', 'codigo', 'id_proveedor', 'moneda', 'datos_identificacion']);

        $folioSiguiente = $this->siguienteFolio($meta['serie'], $meta['key']);

        return view('admin.pago-proveedores.form', [
            'poliza' => $meta,
            'proveedores' => $proveedores,
            'folioSiguiente' => $folioSiguiente,
            'abono' => null,
            'facturasPendientes' => collect(),
            'modo' => 'create',
        ]);
    }

    public function facturasJson(Request $request)
    {
        $codigo = trim((string) $request->query('codigo', ''));
        $proveedorId = (int) $request->query('proveedor_id', 0);

        if ($proveedorId > 0) {
            $prov = ProveedorUser::query()->find($proveedorId);
            if ($prov) {
                $codigo = $prov->id_proveedor ?: $prov->codigo;
            }
        }

        if ($codigo === '') {
            return response()->json(['items' => []]);
        }

        $items = Factura::query()
            ->where('codigo_proveedor', $codigo)
            ->where('estatus', 'pendiente')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (Factura $f) {
                $vd = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
                $moneda = strtoupper((string) ($vd['moneda'] ?? $vd['cfdi']['moneda'] ?? 'MXN'));
                $serie = (string) ($vd['serie'] ?? $vd['cfdi']['serie'] ?? '');
                $folio = (string) ($f->folio_cfdi ?: ($vd['folio'] ?? $f->id));

                return [
                    'id' => $f->id,
                    'fecha' => optional($f->created_at)->format('Y-m-d'),
                    'fecha_fmt' => optional($f->created_at)->format('d/m/Y'),
                    'serie' => $serie !== '' ? $serie : 'FAC',
                    'folio' => $folio,
                    'concepto' => 'Compra',
                    'referencia' => $f->uuid_cfdi,
                    'total' => (float) $f->total,
                    'moneda' => $moneda,
                    'sistema_origen' => 'SALCOM',
                ];
            })
            ->values();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'poliza_key' => 'required|string',
            'fecha' => 'required|date',
            'proveedor_id' => 'required|integer|exists:proveedores_users,id',
            'tipo_cambio' => 'nullable|numeric|min:0',
            'cuenta_bancaria' => 'nullable|string|max:120',
            'notas' => 'nullable|string|max:2000',
            'factura_ids' => 'required|array|min:1',
            'factura_ids.*' => 'integer',
            'importes' => 'nullable|array',
            'importes.*' => 'nullable|numeric|min:0',
            'accion' => 'nullable|in:borrador,guardar',
        ]);

        try {
            $meta = $this->polizaOrFail($data['poliza_key']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $proveedor = ProveedorUser::query()->findOrFail($data['proveedor_id']);
        $codigo = $proveedor->id_proveedor ?: $proveedor->codigo;
        $facturas = Factura::query()
            ->whereIn('id', $data['factura_ids'])
            ->where('codigo_proveedor', $codigo)
            ->where('estatus', 'pendiente')
            ->get();

        if ($facturas->isEmpty()) {
            return back()->withInput()->with('error', 'Selecciona al menos una factura pendiente válida.');
        }

        $estatus = ($data['accion'] ?? 'guardar') === 'borrador' ? 'borrador' : 'guardado';
        $tc = $data['tipo_cambio'] ?? $meta['tipo_cambio_default'] ?? 1;
        if ($tc === null || $tc === '') {
            $tc = 1;
        }

        try {
            $abono = DB::transaction(function () use ($data, $meta, $proveedor, $codigo, $facturas, $estatus, $tc) {
                $folio = $this->siguienteFolio($meta['serie'], $meta['key']);
                $lineas = [];
                $total = 0.0;

                foreach ($facturas as $f) {
                    $importe = isset($data['importes'][$f->id])
                        ? (float) $data['importes'][$f->id]
                        : (float) $f->total;
                    $total += $importe;
                    $vd = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
                    $lineas[] = [
                        'factura_id' => $f->id,
                        'fecha_doc' => optional($f->created_at)->toDateString(),
                        'serie_doc' => (string) ($vd['serie'] ?? $vd['cfdi']['serie'] ?? 'FAC'),
                        'folio_doc' => (string) ($f->folio_cfdi ?: $f->id),
                        'concepto_doc' => 'Compra',
                        'referencia' => $f->uuid_cfdi,
                        'importe_pago' => round($importe, 2),
                        'sistema_origen' => 'SALCOM',
                        'detalle' => [
                            'total_factura' => (float) $f->total,
                            'moneda' => $vd['moneda'] ?? $vd['cfdi']['moneda'] ?? $meta['moneda'],
                        ],
                    ];
                }

                $abono = AbonoProveedor::create([
                    'poliza_key' => $meta['key'],
                    'serie' => $meta['serie'],
                    'folio' => $folio,
                    'concepto' => $meta['concepto'],
                    'fecha' => $data['fecha'],
                    'proveedor_id' => $proveedor->id,
                    'codigo_proveedor' => $codigo,
                    'nombre_proveedor' => $proveedor->nombre,
                    'moneda' => $meta['moneda'],
                    'tipo_cambio' => $tc,
                    'cuenta_bancaria' => $data['cuenta_bancaria'] ?? null,
                    'estatus' => $estatus,
                    'monto_pago' => round($total, 2),
                    'notas' => $data['notas'] ?? null,
                    'creado_por' => auth()->id(),
                ]);

                foreach ($lineas as $linea) {
                    $abono->documentos()->create($linea);
                }

                return $abono;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'No se pudo guardar el abono: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.pago-proveedores.show', $abono)
            ->with('ok', $estatus === 'borrador'
                ? 'Borrador guardado ('.$abono->etiquetaFolio().').'
                : 'Abono guardado ('.$abono->etiquetaFolio().').');
    }

    public function show(AbonoProveedor $abono)
    {
        $abono->load(['documentos.factura', 'proveedor']);
        $poliza = $abono->poliza() ?? ['titulo' => $abono->poliza_key, 'concepto' => $abono->concepto];

        return view('admin.pago-proveedores.show', compact('abono', 'poliza'));
    }

    public function cancelar(AbonoProveedor $abono)
    {
        if ($abono->estatus === 'cancelado') {
            return back()->with('error', 'Ya está cancelado.');
        }

        $abono->update(['estatus' => 'cancelado']);

        return back()->with('ok', 'Abono cancelado.');
    }

    private function polizaOrFail(string $key): array
    {
        $meta = config('polizas_pago.'.$key);
        if (! is_array($meta)) {
            throw new InvalidArgumentException('Póliza no válida.');
        }

        return $meta;
    }

    private function siguienteFolio(string $serie, string $polizaKey): int
    {
        $max = (int) AbonoProveedor::query()
            ->where('serie', $serie)
            ->where('poliza_key', $polizaKey)
            ->max('folio');

        return $max + 1;
    }
}
