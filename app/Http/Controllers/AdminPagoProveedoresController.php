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
        $kpiPagados = (clone $base)->whereIn('estatus', ['guardado', 'borrador', 'pagado'])->count();
        $kpiTotales = (clone $base)->count();
        $kpiFacturasPendientes = \App\Models\Factura::whereIn('estatus', ['pendiente', 'programada'])->count();
        $kpiMontoPendiente = \App\Models\Factura::whereIn('estatus', ['pendiente', 'programada'])
            ->selectRaw('SUM(total - monto_pagado) as total')->value('total') ?? 0;

        // KPIs de estatus de abono → proveedores agrupados por fecha (mismo estilo que vista principal)
        if ($estatus !== '' && in_array($estatus, ['cancelado', 'pagado', 'borrador', 'guardado'], true)) {
            $estatusFiltro = $estatus === 'pagado' ? ['guardado', 'borrador', 'pagado'] : [$estatus];

            // Si piden ver abonos de un proveedor específico, mostrar tabla de abonos
            if ($request->query('ver_abonos') && $q !== '') {
                $abonos = AbonoProveedor::query()
                    ->with('documentos')
                    ->whereIn('estatus', $estatusFiltro)
                    ->where(function ($w) use ($q) {
                        $w->where('codigo_proveedor', 'like', "%{$q}%")
                            ->orWhere('nombre_proveedor', 'like', "%{$q}%");
                    })
                    ->when($agente !== '' && isset($tiposAgente[$agente]), fn ($qb) => $qb->where('poliza_key', $agente))
                    ->orderByDesc('fecha')
                    ->orderByDesc('id')
                    ->paginate(50)
                    ->withQueryString();

                return view('admin.pago-proveedores.index', compact(
                    'abonos',
                    'q',
                    'poliza',
                    'agente',
                    'tiposAgente',
                    'estatus',
                    'kpiCancelados',
                    'kpiPagados',
                    'kpiTotales',
                    'kpiFacturasPendientes',
                    'kpiMontoPendiente'
                ) + ['proveedoresPendientes' => collect(), 'modo' => 'abonos']);
            }

            // Obtener proveedores que tienen abonos con ese estatus
            $abonosQuery = AbonoProveedor::query()
                ->whereIn('estatus', $estatusFiltro)
                ->when($agente !== '' && isset($tiposAgente[$agente]), fn ($qb) => $qb->where('poliza_key', $agente));

            $proveedoresFiltrados = $abonosQuery
                ->selectRaw('codigo_proveedor, count(*) as num_facturas, sum(monto_pago) as monto_total, max(created_at) as ultima_factura_at')
                ->whereNotNull('codigo_proveedor')
                ->groupBy('codigo_proveedor')
                ->orderByDesc('ultima_factura_at')
                ->get()
                ->map(function ($row) use ($estatusFiltro) {
                    $prov = ProveedorUser::where('codigo', $row->codigo_proveedor)->first();
                    $notifSinLeer = \App\Models\Alerta::query()
                        ->where('destinatario_tipo', 'admin')
                        ->where('tipo', 'factura_pago_pendiente')
                        ->where('datos->codigo_proveedor', $row->codigo_proveedor)
                        ->whereNotIn('estatus', ['leida', 'accionada'])
                        ->count();
                    $ultimaAt = $row->ultima_factura_at ? \Carbon\Carbon::parse($row->ultima_factura_at) : null;
                    return (object) [
                        'codigo' => $row->codigo_proveedor,
                        'proveedor' => $prov,
                        'nombre' => $prov?->nombre ?? $row->codigo_proveedor,
                        'num_facturas' => (int) $row->num_facturas,
                        'monto_total' => (float) $row->monto_total,
                        'ultima_factura_at' => $ultimaAt,
                        'proximo_vencimiento' => null,
                        'expediente' => ['ok' => true, 'motivos' => []],
                        'notif_sin_leer' => $notifSinLeer,
                    ];
                });

            return view('admin.pago-proveedores.index', compact(
                'q',
                'poliza',
                'agente',
                'tiposAgente',
                'estatus',
                'kpiCancelados',
                'kpiPagados',
                'kpiTotales',
                'kpiFacturasPendientes',
                'kpiMontoPendiente'
            ) + ['proveedoresPendientes' => $proveedoresFiltrados, 'abonos' => null, 'modo' => 'proveedores']);
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
            'kpiPagados',
            'kpiTotales',
            'kpiFacturasPendientes',
            'kpiMontoPendiente'
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
            ->where('estatus', 'programada')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (Factura $f) {
                $vd = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
                $moneda = strtoupper((string) ($vd['moneda'] ?? $vd['cfdi']['moneda'] ?? 'MXN'));
                $serie = (string) ($vd['serie'] ?? $vd['cfdi']['serie'] ?? '');
                $folio = (string) ($f->folio_cfdi ?: ($vd['folio'] ?? $f->id));
                $saldo = round((float) $f->total - (float) $f->monto_pagado, 2);
                $restantes = $f->diasRestantes();
                $diasLabel = $restantes === null
                    ? '—'
                    : ($restantes > 0 ? $restantes.' días' : ($restantes === 0 ? 'Vence hoy' : 'Vencida ('.abs($restantes).')'));

                return [
                    'id' => $f->id,
                    'fecha' => optional($f->created_at)->format('Y-m-d'),
                    'fecha_fmt' => optional($f->created_at)->format('d/m/Y'),
                    'hora' => optional($f->created_at)->format('h:i a'),
                    'serie' => $serie !== '' ? $serie : 'FAC',
                    'folio' => $folio,
                    'concepto' => 'Compra',
                    'referencia' => $f->uuid_cfdi,
                    'total' => $saldo,
                    'total_factura' => (float) $f->total,
                    'monto_pagado' => (float) $f->monto_pagado,
                    'moneda' => $moneda,
                    'sistema_origen' => 'SALCOM',
                    'fecha_vencimiento_fmt' => optional($f->fecha_vencimiento)->format('d/m/Y'),
                    'dias_restantes' => $restantes,
                    'dias_plazo' => $f->dias_plazo,
                    'dias_label' => $diasLabel,
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
            'formato_pago' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $meta = $this->polizaOrFail($data['poliza_key']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $proveedor = ProveedorUser::query()->findOrFail($data['proveedor_id']);
        $codigo = $proveedor->id_proveedor ?: $proveedor->codigo;

        // Validación de secuencia: solo facturas "programada" pueden pagarse aquí.
        if ($errorSecuencia = $this->validarSecuenciaFacturas($data['factura_ids'], 'programada', 'el pago a proveedor')) {
            return back()->withInput()->with('error', $errorSecuencia.' Primero genera el pago en "Pagos al proveedor".');
        }

        $facturas = Factura::query()
            ->whereIn('id', $data['factura_ids'])
            ->where('codigo_proveedor', $codigo)
            ->where('estatus', 'programada')
            ->get();

        if ($facturas->isEmpty()) {
            return back()->withInput()->with('error', 'No hay facturas programadas para abonar. Primero genera el pago en "Pagos al proveedor".');
        }

        $estatus = 'pagado';
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

        // Guardar el formato de pago adjunto
        if ($request->hasFile('formato_pago')) {
            $formatoPath = $request->file('formato_pago')->store('pagos_formatos/' . $abono->id, 'public');
            $abono->update(['cuenta_bancaria' => ($abono->cuenta_bancaria ? $abono->cuenta_bancaria . ' | ' : '') . 'formato:' . $formatoPath]);
        }

        // Crear alerta para el proveedor (notificación en tiempo real)
        try {
            $montoFmt = number_format((float) $abono->monto_pago, 2);
            \App\Models\Alerta::create([
                'tipo' => 'pago_confirmado',
                'modulo' => 'abonos',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $proveedor->id,
                'titulo' => 'Pago recibido',
                'contenido' => "Salcom registró un pago por \${$montoFmt} " . $meta['moneda'] . " a tus facturas.",
                'nivel' => 'info',
                'estatus' => 'nueva',
                'datos' => [
                    'abono_id' => $abono->id,
                    'monto' => (float) $abono->monto_pago,
                    'moneda' => $meta['moneda'],
                    'num_facturas' => $facturas->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Abono] No se pudo crear alerta proveedor: ' . $e->getMessage());
        }

        // Crear alerta para admin (campanita + badge)
        try {
            $montoFmt = number_format((float) $abono->monto_pago, 2);
            \App\Models\Alerta::create([
                'tipo' => 'pago_realizado',
                'modulo' => 'pago_proveedor',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => session('admin_id'),
                'titulo' => 'Pago realizado',
                'contenido' => "Pago por \${$montoFmt} {$meta['moneda']} a {$abono->nombre_proveedor} ({$abono->codigo_proveedor}). {$facturas->count()} factura(s).",
                'nivel' => 'info',
                'estatus' => 'nueva',
                'datos' => [
                    'abono_id' => $abono->id,
                    'codigo_proveedor' => $abono->codigo_proveedor,
                    'monto' => (float) $abono->monto_pago,
                    'moneda' => $meta['moneda'],
                    'cuenta' => $meta['titulo'],
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Abono] No se pudo crear alerta admin: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.pago-proveedores.show', $abono)
            ->with('ok', 'Pago registrado (' . $abono->etiquetaFolio() . '). Las facturas fueron actualizadas.');
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

        // Revertir los pagos de las facturas
        $abono->load('documentos');
        foreach ($abono->documentos as $doc) {
            $factura = Factura::find($doc->factura_id);
            if ($factura) {
                $nuevoMontoPagado = max(0, round((float) $factura->monto_pagado - (float) $doc->importe_pago, 2));
                $factura->monto_pagado = $nuevoMontoPagado;
                // Si estaba pagada y ahora tiene saldo, volver a pendiente
                if ($factura->estatus === 'pagada' && $nuevoMontoPagado < (float) $factura->total) {
                    $factura->estatus = 'pendiente';
                }
                $factura->save();
            }
        }

        $abono->update(['estatus' => 'cancelado']);

        return back()->with('ok', 'Abono cancelado y pagos revertidos.');
    }

    // ═══════════════════════════════════════════
    // ABONO AL PROVEEDOR (Paso 3 — registro interno)
    // ═══════════════════════════════════════════

    /** Listado de facturas pagadas para confirmar abono interno. */
    public function abonoInterno(Request $request)
    {
        $cuentaKey = trim((string) $request->input('cuenta', ''));
        $cuentaConfig = config("polizas_pago.{$cuentaKey}");

        $proveedores = ProveedorUser::query()
            ->select('codigo', 'nombre', 'moneda')
            ->orderBy('nombre')
            ->get();

        return view('admin.abono-proveedor.index', compact('proveedores', 'cuentaKey', 'cuentaConfig'));
    }

    public function abonoInternoFacturas(Request $request)
    {
        $codigo = trim((string) $request->input('codigo', ''));
        if ($codigo === '') {
            return response()->json(['facturas' => []]);
        }

        $facturas = Factura::query()
            ->where('codigo_proveedor', $codigo)
            ->where('estatus', 'pagada')
            ->where('monto_pagado', '>', 0)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'folio_cfdi' => $f->folio_cfdi,
                'total' => (float) $f->total,
                'monto_pagado' => (float) $f->monto_pagado,
                'fecha_pago' => $f->updated_at?->format('d/m/Y h:i a'),
            ]);

        return response()->json(['facturas' => $facturas]);
    }

    public function historialAbonos(Request $request)
    {
        $buscar = trim((string) $request->input('q', ''));
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        // Cuentas (las 4 pólizas). Cuenta activa = la seleccionada o la primera.
        $cuentas = config('polizas_pago');
        $cuentaKey = trim((string) $request->input('cuenta', ''));
        if (! isset($cuentas[$cuentaKey])) {
            $cuentaKey = array_key_first($cuentas);
        }
        $cuentaConfig = $cuentas[$cuentaKey];

        // Nombres de proveedores para resolver razón social
        $provNombres = ProveedorUser::pluck('nombre', 'codigo');

        // ── Fuente 1: Anticipos registrados desde Abono al proveedor (cuentas USD) ──
        $anticipos = \App\Models\AnticipoProveedor::query()
            ->whereNotNull('datos')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($a) use ($cuentaKey) {
                $d = is_array($a->datos) ? $a->datos : [];
                return ($d['origen'] ?? '') === 'abono_proveedor' && ($d['cuenta_key'] ?? '') === $cuentaKey;
            })
            ->map(function ($a) {
                $d = is_array($a->datos) ? $a->datos : [];
                return [
                    'tipo' => 'anticipo',
                    'fecha' => $a->fecha,
                    'serie' => $d['serie'] ?? '',
                    'folio' => $a->folio,
                    'codigo' => $a->codigo_proveedor,
                    'razon' => $a->nombre_proveedor,
                    'total' => (float) $a->total_banco,
                    'moneda' => $d['moneda'] ?? 'USD',
                    'tipo_cambio' => $d['tipo_cambio'] ?? '',
                    'pendiente' => $a->saldoPendiente(),
                    'referencia' => $a->folio_general,
                    'estatus' => $a->estatus,
                    'cancelado' => $a->estatus === 'cancelado' ? 1 : 0,
                    'registrado' => $a->created_at?->format('d/m/Y h:i a'),
                    'anticipo_id' => $a->id,
                ];
            });

        // ── Fuente 2: Abonos internos estampados en facturas (cuentas por título) ──
        $tituloCuenta = $cuentaConfig['titulo'] ?? '';
        $facturasAbono = Factura::query()
            ->where('estatus', 'liquidada')
            ->whereNotNull('validacion_detalle')
            ->orderByDesc('updated_at')
            ->get()
            ->filter(function ($f) use ($tituloCuenta) {
                $vd = is_array($f->validacion_detalle) ? ($f->validacion_detalle['abono_interno'] ?? []) : [];
                return ($vd['cuenta'] ?? '') === $tituloCuenta;
            })
            ->map(function ($f) {
                $vd = is_array($f->validacion_detalle) ? ($f->validacion_detalle['abono_interno'] ?? []) : [];
                return [
                    'tipo' => 'abono',
                    'fecha' => isset($vd['fecha']) ? \Illuminate\Support\Carbon::parse($vd['fecha']) : $f->updated_at,
                    'serie' => $vd['serie'] ?? '',
                    'folio' => $vd['poliza'] ?? '',
                    'codigo' => $f->codigo_proveedor,
                    'razon' => null, // se resuelve en la vista
                    'total' => (float) $f->monto_pagado,
                    'moneda' => $vd['moneda'] ?? 'MXN',
                    'tipo_cambio' => $vd['tipo_cambio'] ?? '1.0000',
                    'pendiente' => 0.0,
                    'referencia' => $f->folio_cfdi ? ('FAC-' . $f->folio_cfdi) : ('FAC-' . $f->id),
                    'estatus' => $f->estatus,
                    'cancelado' => $f->estatus === 'cancelada' ? 1 : 0,
                    'registrado' => $vd['registrado_at'] ?? $f->updated_at?->format('d/m/Y h:i a'),
                    'factura_id' => $f->id,
                ];
            });

        // ── Combinar y resolver razón social + aplicar filtros de texto/fecha ──
        $baseRegistros = $anticipos->concat($facturasAbono)
            ->map(function ($r) use ($provNombres) {
                if (empty($r['razon'])) {
                    $r['razon'] = $provNombres[$r['codigo']] ?? $r['codigo'];
                }
                return $r;
            })
            ->filter(function ($r) use ($buscar, $desde, $hasta) {
                if ($buscar !== '') {
                    $hay = stripos((string) $r['codigo'], $buscar) !== false
                        || stripos((string) $r['razon'], $buscar) !== false
                        || stripos((string) $r['folio'], $buscar) !== false
                        || stripos((string) $r['referencia'], $buscar) !== false;
                    if (! $hay) return false;
                }
                $fecha = $r['fecha'] instanceof \Illuminate\Support\Carbon ? $r['fecha'] : \Illuminate\Support\Carbon::parse($r['fecha']);
                if ($desde && $fecha->lt(\Illuminate\Support\Carbon::parse($desde)->startOfDay())) return false;
                if ($hasta && $fecha->gt(\Illuminate\Support\Carbon::parse($hasta)->endOfDay())) return false;
                return true;
            })
            ->sortByDesc(function ($r) {
                return $r['fecha'] instanceof \Illuminate\Support\Carbon ? $r['fecha']->timestamp : strtotime((string) $r['fecha']);
            })
            ->values();

        // ── KPIs (contadores) ──
        $kpiTodos = $baseRegistros->count();
        $kpiCancelados = $baseRegistros->where('cancelado', 1)->count();
        $kpiPendiente = $baseRegistros->filter(fn ($r) => (float) $r['pendiente'] > 0)->count();

        // ── Filtro por tipo de documento (tab) ──
        $doc = trim((string) $request->input('doc', 'todos'));
        $registros = $baseRegistros;
        if ($doc === 'cancelados') {
            $registros = $baseRegistros->where('cancelado', 1)->values();
        } elseif ($doc === 'pendiente') {
            $registros = $baseRegistros->filter(fn ($r) => (float) $r['pendiente'] > 0)->values();
        }

        // Totales del listado filtrado
        $totalRegistros = $registros->count();
        $sumaTotal = $registros->sum('total');
        $sumaPendiente = $registros->sum('pendiente');

        return view('admin.historial-abonos', compact(
            'registros', 'cuentas', 'cuentaKey', 'cuentaConfig',
            'buscar', 'totalRegistros', 'sumaTotal', 'sumaPendiente',
            'doc', 'kpiTodos', 'kpiCancelados', 'kpiPendiente'
        ));
    }

    /** Confirmar abono interno con número de póliza. */
    public function abonoInternoConfirmar(Request $request)
    {
        $data = $request->validate([
            'factura_ids' => 'required|array|min:1',
            'factura_ids.*' => 'integer',
            'poliza' => 'required|string|max:60',
            'fecha' => 'required|date|before:today',
            'serie' => 'nullable|string|max:20',
            'cuenta' => 'nullable|string|max:60',
            'cuenta_key' => 'nullable|string|max:30',
            'codigo_proveedor' => 'required|string|max:30',
            'moneda' => 'nullable|string|max:10',
            'tipo_cambio' => 'nullable|string|max:20',
            'notas' => 'nullable|string|max:500',
            'referencia' => 'required|string|max:120',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'fecha.before' => 'La fecha debe ser un día anterior a hoy.',
            'referencia.required' => 'Escribe en Referencia las facturas o número de compra.',
        ]);

        // Validación de secuencia: solo facturas "pagada" pueden liquidarse aquí.
        if ($errorSecuencia = $this->validarSecuenciaFacturas($data['factura_ids'], 'pagada', 'el abono al proveedor')) {
            return back()->with('error', $errorSecuencia.' El pago debe registrarse antes en "Pago a proveedor".');
        }

        $facturas = Factura::whereIn('id', $data['factura_ids'])->where('estatus', 'pagada')->get();

        if ($facturas->isEmpty()) {
            return back()->with('error', 'No se encontraron facturas pagadas para confirmar.');
        }

        foreach ($facturas as $f) {
            $vd = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
            $vd['abono_interno'] = [
                'poliza' => $data['poliza'],
                'fecha' => $data['fecha'],
                'serie' => $data['serie'] ?? null,
                'cuenta' => config("polizas_pago.{$data['cuenta_key']}.titulo", $data['cuenta'] ?? null),
                'moneda' => $data['moneda'] ?? 'MXN',
                'tipo_cambio' => $data['tipo_cambio'] ?? '1.0000',
                'notas' => $data['notas'] ?? null,
                'referencia' => $data['referencia'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'admin_id' => session('admin_id'),
                'registrado_at' => now()->toDateTimeString(),
            ];
            $f->validacion_detalle = $vd;
            $f->estatus = 'liquidada';
            $f->save();
        }

        // Notificación para admin (campanita + badge en sidebar)
        try {
            $montoTotal = $facturas->sum('monto_pagado');
            $montoFmt = number_format((float) $montoTotal, 2);
            \App\Models\Alerta::create([
                'tipo' => 'abono_interno_registrado',
                'modulo' => 'abono_proveedor',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => session('admin_id'),
                'titulo' => 'Abono registrado',
                'contenido' => "Póliza {$data['poliza']} — {$facturas->count()} factura(s) liquidadas por \${$montoFmt}. Proveedor: {$data['codigo_proveedor']}.",
                'nivel' => 'info',
                'estatus' => 'nueva',
                'datos' => [
                    'poliza' => $data['poliza'],
                    'codigo_proveedor' => $data['codigo_proveedor'],
                    'monto' => (float) $montoTotal,
                    'num_facturas' => $facturas->count(),
                    'cuenta' => config("polizas_pago.{$data['cuenta_key']}.titulo", ''),
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[AbonoInterno] No se pudo crear alerta admin: ' . $e->getMessage());
        }

        // Notificación para proveedor: facturas liquidadas
        try {
            $proveedor = \App\Models\ProveedorUser::where('codigo', $data['codigo_proveedor'])->first();
            if ($proveedor) {
                $montoTotal = $facturas->sum('monto_pagado');
                $montoFmt = number_format((float) $montoTotal, 2);
                \App\Models\Alerta::create([
                    'tipo' => 'facturas_liquidadas',
                    'modulo' => 'abono_proveedor',
                    'destinatario_tipo' => 'proveedor',
                    'destinatario_id' => $proveedor->id,
                    'titulo' => 'Facturas liquidadas',
                    'contenido' => "{$facturas->count()} factura(s) por \${$montoFmt} han sido liquidadas. Ya no tienes adeudo por estas facturas.",
                    'nivel' => 'info',
                    'estatus' => 'nueva',
                    'datos' => [
                        'poliza' => $data['poliza'],
                        'monto' => (float) $montoTotal,
                        'num_facturas' => $facturas->count(),
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[AbonoInterno] No se pudo crear alerta proveedor: ' . $e->getMessage());
        }

        return redirect()->route('admin.historial-abonos', ['cuenta' => $data['cuenta_key'] ?? ''])
            ->with('ok', 'Abono registrado. Póliza: ' . $data['poliza'] . ' — ' . $facturas->count() . ' factura(s) liquidadas.');
    }

    /**
     * Guardar el abono como ANTICIPO cuando no hay OC/factura en el sistema.
     * Se dispara desde el botón "Guardar" en la pestaña Información Adicional.
     */
    public function abonoInternoAnticipo(Request $request)
    {
        // Limpiar comas del monto
        $request->merge([
            'total_manual' => str_replace([',', '$'], '', (string) $request->input('total_manual', '')),
        ]);

        $data = $request->validate([
            'poliza' => 'required|string|max:60',
            'fecha' => 'required|date|before:today',
            'serie' => 'nullable|string|max:20',
            'cuenta_key' => 'nullable|string|max:30',
            'codigo_proveedor' => 'required|string|max:30',
            'moneda' => 'nullable|string|max:10',
            'tipo_cambio' => 'nullable|string|max:20',
            'total_manual' => 'required|numeric|min:0.01',
            'referencia' => 'required|string|max:120',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'fecha.before' => 'La fecha debe ser un día anterior a hoy.',
        ]);

        // Anticipo permitido en cualquier cuenta: cuando las facturas mostradas no corresponden al pago
        $proveedor = ProveedorUser::where('codigo', $data['codigo_proveedor'])->first();
        if (! $proveedor) {
            return back()->with('error', 'Proveedor no encontrado.');
        }

        $total = (float) $data['total_manual'];
        $cuentaTitulo = config("polizas_pago.{$data['cuenta_key']}.titulo", '');
        $cuentaConcepto = config("polizas_pago.{$data['cuenta_key']}.concepto", 'Abono');
        $di = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];

        $anticipo = \App\Models\AnticipoProveedor::create([
            'folio' => $data['poliza'], // el folio/nº póliza que capturó
            'proveedor_id' => $proveedor->id,
            'codigo_proveedor' => $proveedor->id_proveedor ?: $proveedor->codigo,
            'nombre_proveedor' => $proveedor->nombre,
            'rfc_proveedor' => strtoupper($di['rfc'] ?? ''),
            'banco' => $di['banco'] ?? null,
            'cuenta_banco' => $di['cuenta'] ?? null,
            'clabe' => $di['clabe'] ?? null,
            'importe' => $total,
            'iva' => 0,
            'total_banco' => $total,
            'folio_general' => $data['referencia'], // número de orden
            'departamento' => 'Compras',
            'fecha' => $data['fecha'],
            'concepto' => $cuentaConcepto,
            'estatus' => 'pagado',
            'creado_por' => session('admin_id'),
            'datos' => [
                'origen' => 'abono_proveedor',
                'cuenta_key' => $data['cuenta_key'] ?? null,
                'cuenta_titulo' => $cuentaTitulo,
                'serie' => $data['serie'] ?? null,
                'poliza' => $data['poliza'],
                'moneda' => $data['moneda'] ?? 'MXN',
                'tipo_cambio' => $data['tipo_cambio'] ?? '1.0000',
                'referencia' => $data['referencia'],
                'observaciones' => $data['observaciones'] ?? null,
                'registrado_at' => now()->toDateTimeString(),
            ],
        ]);

        // Alerta admin
        try {
            \App\Models\Alerta::create([
                'tipo' => 'anticipo_registrado',
                'modulo' => 'abono_proveedor',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => session('admin_id'),
                'titulo' => 'Anticipo registrado',
                'contenido' => "Anticipo por \$" . number_format($total, 2) . " a {$proveedor->nombre}. Referencia/Orden: {$data['referencia']}.",
                'nivel' => 'info',
                'estatus' => 'nueva',
                'datos' => [
                    'anticipo_id' => $anticipo->id,
                    'poliza' => $data['poliza'],
                    'referencia' => $data['referencia'],
                    'monto' => $total,
                    'cuenta' => $cuentaTitulo,
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[AbonoAnticipo] No se pudo crear alerta: ' . $e->getMessage());
        }

        return redirect()->route('admin.historial-abonos', ['cuenta' => $data['cuenta_key'] ?? ''])
            ->with('ok', 'Pago registrado como anticipo. Referencia/Orden: ' . $data['referencia'] . ' — $' . number_format($total, 2) . '.');
    }

    // ═══════════════════════════════════════════
    // ANTICIPOS
    // ═══════════════════════════════════════════

    public function anticiposIndex(Request $request)
    {
        $proveedores = ProveedorUser::query()
            ->select('id', 'codigo', 'nombre', 'id_proveedor', 'datos_identificacion')
            ->orderBy('nombre')
            ->get();

        $anticipos = \App\Models\AnticipoProveedor::query()
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.anticipos.index', compact('proveedores', 'anticipos'));
    }

    public function anticiposStore(Request $request)
    {
        // Limpiar comas de los campos numéricos antes de validar
        $request->merge([
            'importe' => str_replace(',', '', (string) $request->input('importe', '')),
            'iva' => str_replace([',', '$'], '', (string) $request->input('iva', '0')),
        ]);

        $data = $request->validate([
            'proveedor_id' => 'required|integer|exists:proveedores_users,id',
            'banco' => 'nullable|string|max:80',
            'cuenta_banco' => 'nullable|string|max:30',
            'clabe' => 'nullable|string|max:20',
            'importe' => 'required|numeric|min:0.01',
            'iva' => 'nullable|numeric|min:0',
            'rfc' => 'nullable|string|max:20',
            'folio_general' => 'required|string|max:120',
            'departamento' => 'required|string|max:60',
            'fecha' => 'required|date',
            'concepto' => 'required|string|max:1000',
        ]);

        $proveedor = ProveedorUser::findOrFail($data['proveedor_id']);
        $iva = (float) ($data['iva'] ?? 0);
        $importe = (float) $data['importe'];
        $totalBanco = $importe + $iva;

        // Folio fijo de contabilidad
        $folio = 'FCONA-0040';

        $anticipo = \App\Models\AnticipoProveedor::create([
            'folio' => $folio,
            'proveedor_id' => $proveedor->id,
            'codigo_proveedor' => $proveedor->id_proveedor ?: $proveedor->codigo,
            'nombre_proveedor' => $proveedor->nombre,
            'rfc_proveedor' => strtoupper($data['rfc']),
            'banco' => $data['banco'],
            'cuenta_banco' => $data['cuenta_banco'],
            'clabe' => $data['clabe'],
            'importe' => $importe,
            'iva' => $iva,
            'total_banco' => $totalBanco,
            'folio_general' => $data['folio_general'],
            'departamento' => $data['departamento'],
            'fecha' => $data['fecha'],
            'concepto' => $data['concepto'],
            'estatus' => 'pagado',
            'creado_por' => session('admin_id'),
        ]);

        return redirect()->route('admin.anticipos', ['creado' => $anticipo->id])->with('ok', "Anticipo {$folio} registrado por \$" . number_format($totalBanco, 2) . " a {$proveedor->nombre}.");
    }

    public function anticiposFormato(\App\Models\AnticipoProveedor $anticipo)
    {
        $data = [
            'anticipo' => $anticipo,
            'titulo' => 'FORMATO PARA ANTICIPO',
            'folio' => $anticipo->folio,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.anticipos.formato-pdf', $data)
            ->setPaper('letter', 'landscape');

        return $pdf->download("Formato_Anticipo_{$anticipo->folio}_{$anticipo->codigo_proveedor}.pdf");
    }

    public function anticiposAplicar(Request $request, \App\Models\AnticipoProveedor $anticipo)
    {
        $data = $request->validate([
            'factura_id' => 'required|integer|exists:facturas,id',
            'formato_pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

        $wantsJson = $request->ajax() || $request->wantsJson();

        // No se puede reaplicar un anticipo ya aplicado o cancelado.
        if (in_array($anticipo->estatus, ['aplicado', 'cancelado'], true)) {
            $msg = 'Este anticipo ya está '.$anticipo->estatus.'. No se puede aplicar de nuevo.';
            return $wantsJson
                ? response()->json(['ok' => false, 'mensaje' => $msg], 422)
                : back()->withErrors($msg);
        }

        $factura = Factura::findOrFail($data['factura_id']);

        // Saldo real de la factura antes de aplicar el anticipo.
        $saldoFactura = round((float) $factura->total - (float) $factura->monto_pagado, 2);
        $montoAnticipo = round((float) $anticipo->total_banco, 2);

        // Validar que el anticipo no exceda el saldo pendiente de la factura.
        if ($montoAnticipo > $saldoFactura) {
            $msg = "El monto del anticipo (\$" . number_format($montoAnticipo, 2) . ") excede el saldo pendiente de la factura (\$" . number_format($saldoFactura, 2) . "). No se puede aplicar.";
            return $wantsJson
                ? response()->json(['ok' => false, 'mensaje' => $msg], 422)
                : back()->withErrors($msg);
        }

        // Almacenar el archivo PDF
        $filePath = null;
        if ($request->hasFile('formato_pdf')) {
            $filePath = $request->file('formato_pdf')->store('anticipos', 'public');
        }

        // Aplicar: descontar del saldo real de la factura (como un pago parcial).
        DB::transaction(function () use ($anticipo, $factura, $montoAnticipo, $filePath) {
            $pagadoPrevio = (float) $factura->monto_pagado;
            $estatusPrevio = $factura->estatus;
            $nuevoPagado = round($pagadoPrevio + $montoAnticipo, 2);

            $factura->monto_pagado = $nuevoPagado;
            // Si el anticipo cubre el total, la factura queda pagada.
            if ($nuevoPagado >= (float) $factura->total) {
                $factura->estatus = 'pagada';
            }
            $factura->save();

            $anticipo->update([
                'estatus' => 'aplicado',
                'factura_id' => $factura->id,
                'monto_aplicado' => $montoAnticipo,
                'datos' => array_merge($anticipo->datos ?? [], [
                    'aplicado_at' => now()->toDateTimeString(),
                    'aplicado_por' => session('admin_id'),
                    'factura_folio' => $factura->folio_cfdi,
                    'formato_pdf' => $filePath,
                    // Guardamos el estado previo para poder revertir al cancelar.
                    'factura_monto_pagado_previo' => $pagadoPrevio,
                    'factura_estatus_previo' => $estatusPrevio,
                ]),
            ]);
        });

        $factura->refresh();
        $saldoRestante = round((float) $factura->total - (float) $factura->monto_pagado, 2);
        $folioFac = $factura->folio_cfdi ?: 'FAC-'.$factura->id;
        $msgOk = "Anticipo {$anticipo->folio_general} aplicado a factura {$folioFac}. Saldo restante: \$" . number_format($saldoRestante, 2) . ".";

        if ($wantsJson) {
            return response()->json([
                'ok' => true,
                'mensaje' => $msgOk,
                'factura' => [
                    'id' => $factura->id,
                    'monto_pagado' => (float) $factura->monto_pagado,
                    'saldo' => $saldoRestante,
                    'estatus' => $factura->estatus,
                ],
            ]);
        }

        return back()->with('ok', $msgOk);
    }

    /**
     * Cancelar un anticipo aplicado: revierte el descuento en la factura.
     */
    public function anticiposCancelar(Request $request, \App\Models\AnticipoProveedor $anticipo)
    {
        $wantsJson = $request->ajax() || $request->wantsJson();

        if ($anticipo->estatus === 'cancelado') {
            $msg = 'El anticipo ya está cancelado.';
            return $wantsJson ? response()->json(['ok' => false, 'mensaje' => $msg], 422) : back()->with('error', $msg);
        }

        DB::transaction(function () use ($anticipo) {
            // Solo revertir la factura si el anticipo estaba aplicado a una.
            if ($anticipo->estatus === 'aplicado' && $anticipo->factura_id) {
                $factura = Factura::find($anticipo->factura_id);
                if ($factura) {
                    $datos = is_array($anticipo->datos) ? $anticipo->datos : [];
                    // Preferimos restaurar el estado previo guardado; si no existe,
                    // restamos el monto aplicado como respaldo.
                    if (array_key_exists('factura_monto_pagado_previo', $datos)) {
                        $factura->monto_pagado = round((float) $datos['factura_monto_pagado_previo'], 2);
                        $factura->estatus = $datos['factura_estatus_previo'] ?? $factura->estatus;
                    } else {
                        $factura->monto_pagado = max(0, round((float) $factura->monto_pagado - (float) $anticipo->monto_aplicado, 2));
                        if ($factura->estatus === 'pagada' && (float) $factura->monto_pagado < (float) $factura->total) {
                            $factura->estatus = 'programada';
                        }
                    }
                    $factura->save();
                }
            }

            $anticipo->update([
                'estatus' => 'cancelado',
                'factura_id' => null,
                'monto_aplicado' => 0,
                'datos' => array_merge($anticipo->datos ?? [], [
                    'cancelado_at' => now()->toDateTimeString(),
                    'cancelado_por' => session('admin_id'),
                ]),
            ]);
        });

        $msg = "Anticipo {$anticipo->folio_general} cancelado. El descuento en la factura fue revertido.";
        return $wantsJson ? response()->json(['ok' => true, 'mensaje' => $msg]) : back()->with('ok', $msg);
    }

    private function polizaOrFail(string $key): array
    {
        $meta = config('polizas_pago.'.$key);
        if (! is_array($meta)) {
            throw new InvalidArgumentException('Póliza no válida.');
        }

        return $meta;
    }

    /**
     * Orden del flujo de pago. Cada estatus solo puede avanzar al siguiente.
     * pendiente → programada → pagada → liquidada
     */
    private const ORDEN_FLUJO = ['pendiente', 'programada', 'pagada', 'liquidada'];

    /**
     * Valida que todas las facturas solicitadas estén en el estatus esperado
     * para este paso del flujo. Si alguna está en otro estatus, devuelve un
     * mensaje que explica qué paso falta, en lugar de descartarla en silencio.
     *
     * @param  array<int>  $facturaIds
     * @return string|null  Mensaje de error, o null si la secuencia es válida.
     */
    private function validarSecuenciaFacturas(array $facturaIds, string $estatusRequerido, string $nombrePaso): ?string
    {
        $facturas = Factura::query()
            ->whereIn('id', $facturaIds)
            ->get(['id', 'folio_cfdi', 'estatus']);

        if ($facturas->isEmpty()) {
            return "No se encontraron facturas para {$nombrePaso}.";
        }

        $fueraDeSecuencia = $facturas->filter(fn ($f) => $f->estatus !== $estatusRequerido);
        if ($fueraDeSecuencia->isEmpty()) {
            return null;
        }

        $posRequerida = array_search($estatusRequerido, self::ORDEN_FLUJO, true);
        $etiquetas = [
            'pendiente' => 'Pendiente',
            'programada' => 'Programada (Formato para pago)',
            'pagada' => 'Pagada (Pago a proveedor)',
            'liquidada' => 'Liquidada (Abono al proveedor)',
        ];

        $detalles = $fueraDeSecuencia->map(function ($f) use ($posRequerida, $etiquetas) {
            $folio = $f->folio_cfdi ?: ('FAC-'.$f->id);
            $posActual = array_search($f->estatus, self::ORDEN_FLUJO, true);
            $estatusActual = $etiquetas[$f->estatus] ?? ucfirst($f->estatus);

            if ($posActual === false) {
                return "{$folio} tiene un estatus no válido ({$f->estatus}).";
            }
            if ($posActual < $posRequerida) {
                return "{$folio} aún está en \"{$estatusActual}\"; falta completar el paso anterior antes de continuar.";
            }
            return "{$folio} ya pasó este paso (está en \"{$estatusActual}\"). No se puede repetir.";
        })->values()->all();

        return 'No se puede continuar, hay facturas fuera de secuencia: '.implode(' ', $detalles);
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
