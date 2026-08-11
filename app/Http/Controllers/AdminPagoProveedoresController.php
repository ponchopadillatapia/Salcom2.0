<?php

namespace App\Http\Controllers;

use App\Models\AbonoProveedor;
use App\Models\Factura;
use App\Models\ProveedorUser;
use App\Services\PagoProveedorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminPagoProveedoresController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $poliza = trim((string) $request->query('poliza', '')); // texto libre
        $agente = trim((string) $request->query('agente', '')); // dropdown Contpaqi key
        $estatus = trim((string) $request->query('estatus', '')); // cancelado|borrador|guardado|''
        $tiposAgente = config('polizas_pago');

        $base = AbonoProveedor::query();
        $kpiCancelados = (clone $base)->where('estatus', 'cancelado')->count();
        $kpiBorradores = (clone $base)->where('estatus', 'borrador')->count();
        $kpiGuardados = (clone $base)->where('estatus', 'guardado')->count();
        $kpiTotales = (clone $base)->count();

        // KPIs de estatus de abono → listado de abonos
        if ($estatus !== '' && in_array($estatus, ['cancelado', 'borrador', 'guardado'], true)) {
            $abonos = AbonoProveedor::query()
                ->with('proveedor')
                ->where('estatus', $estatus)
                ->when($agente !== '' && isset($tiposAgente[$agente]), fn ($qb) => $qb->where('poliza_key', $agente))
                ->when($poliza !== '', fn ($qb) => $qb->where('agente', 'like', "%{$poliza}%"))
                ->when($q !== '', function ($qb) use ($q) {
                    $qb->where(function ($w) use ($q) {
                        $w->where('codigo_proveedor', 'like', "%{$q}%")
                            ->orWhere('nombre_proveedor', 'like', "%{$q}%")
                            ->orWhere('folio', 'like', "%{$q}%")
                            ->orWhere('serie', 'like', "%{$q}%")
                            ->orWhere('agente', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString();

            return view('admin.pago-proveedores.index', compact(
                'abonos',
                'q',
                'poliza',
                'agente',
                'tiposAgente',
                'estatus',
                'kpiCancelados',
                'kpiBorradores',
                'kpiGuardados',
                'kpiTotales'
            ) + ['proveedoresPendientes' => collect(), 'modo' => 'abonos']);
        }

        // Vista principal: proveedores pendientes (como Pagos al proveedor)
        $proveedoresPendientes = app(PagoProveedorService::class)->proveedoresConPendientes();

        if ($poliza !== '') {
            $codigosConPoliza = AbonoProveedor::query()
                ->where('agente', 'like', "%{$poliza}%")
                ->pluck('codigo_proveedor')
                ->unique()
                ->filter()
                ->all();
            $proveedoresPendientes = $proveedoresPendientes
                ->filter(fn ($r) => in_array($r->codigo, $codigosConPoliza, true))
                ->values();
        }

        if ($agente !== '' && isset($tiposAgente[$agente])) {
            $meta = $tiposAgente[$agente];
            $monedaWant = strtoupper((string) ($meta['moneda'] ?? 'MXN'));
            $codigosAgente = AbonoProveedor::query()
                ->where('poliza_key', $agente)
                ->pluck('codigo_proveedor')
                ->unique()
                ->filter()
                ->all();

            $proveedoresPendientes = $proveedoresPendientes->filter(function ($r) use ($monedaWant, $codigosAgente) {
                if (in_array($r->codigo, $codigosAgente, true)) {
                    return true;
                }
                $mon = $r->proveedor?->monedaNormalizada() ?? 'MXN';
                if ($monedaWant === 'USD') {
                    return $mon === 'DOLLAR';
                }

                return $mon === 'MXN';
            })->values();
        }

        return view('admin.pago-proveedores.index', compact(
            'proveedoresPendientes',
            'q',
            'poliza',
            'agente',
            'tiposAgente',
            'estatus',
            'kpiCancelados',
            'kpiBorradores',
            'kpiGuardados',
            'kpiTotales'
        ) + ['abonos' => null, 'modo' => 'proveedores']);
    }

    /** Ya no hay pantalla de 4 tarjetas: el agente se elige en el dropdown del listado. */
    public function nuevo(Request $request)
    {
        return redirect()->route('admin.pago-proveedores');
    }

    /** Formulario Abono Prov Contpaqi (agente = {poliza} en la URL). */
    public function create(Request $request, string $poliza)
    {
        try {
            $meta = $this->polizaOrFail($poliza);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.pago-proveedores')->with('error', $e->getMessage());
        }

        $codigoPref = trim((string) $request->query('codigo', ''));
        $proveedores = ProveedorUser::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->orderBy('moneda')
            ->get(['id', 'nombre', 'codigo', 'id_proveedor', 'moneda', 'datos_identificacion']);

        $folioSiguiente = $this->siguienteFolio($meta['serie'], $meta['key']);

        $proveedorIdPref = null;
        if ($codigoPref !== '') {
            $match = $proveedores->first(function ($p) use ($codigoPref) {
                return ($p->id_proveedor ?: $p->codigo) === $codigoPref;
            });
            $proveedorIdPref = $match?->id;
        }

        return view('admin.pago-proveedores.form', [
            'poliza' => $meta,
            'proveedores' => $proveedores,
            'folioSiguiente' => $folioSiguiente,
            'abono' => null,
            'facturasPendientes' => collect(),
            'modo' => 'create',
            'proveedorIdPref' => $proveedorIdPref,
            'codigoPref' => $codigoPref,
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
                $saldo = round((float) $f->total - (float) $f->monto_pagado, 2);

                return [
                    'id' => $f->id,
                    'fecha' => optional($f->created_at)->format('Y-m-d'),
                    'fecha_fmt' => optional($f->created_at)->format('d/m/Y'),
                    'serie' => $serie !== '' ? $serie : 'FAC',
                    'folio' => $folio,
                    'concepto' => 'Compra',
                    'referencia' => $f->uuid_cfdi,
                    'total' => $saldo,
                    'total_factura' => (float) $f->total,
                    'monto_pagado' => (float) $f->monto_pagado,
                    'moneda' => $moneda,
                    'sistema_origen' => 'SALCOM',
                ];
            })
            ->filter(fn ($item) => $item['total'] > 0)
            ->values();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'poliza_key' => 'required|string',
            'fecha' => 'required|date',
            'proveedor_id' => 'required|integer|exists:proveedores_users,id',
            'agente' => 'nullable|string|max:120',
            'poliza' => 'nullable|string|max:120',
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
                    'agente' => trim((string) ($data['poliza'] ?? $data['agente'] ?? '')) ?: null,
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

                // Descontar pagos de las facturas
                foreach ($facturas as $f) {
                    $importe = isset($data['importes'][$f->id])
                        ? (float) $data['importes'][$f->id]
                        : (float) $f->total;

                    $nuevoPagado = round((float) $f->monto_pagado + $importe, 2);
                    $f->monto_pagado = $nuevoPagado;

                    // Si ya se cubrió el total, marcar como pagada
                    if ($nuevoPagado >= (float) $f->total) {
                        $f->estatus = 'pagada';
                    }

                    $f->save();
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
