<?php

namespace App\Http\Controllers;

use App\Models\AbonoProveedor;
use App\Models\Factura;
use App\Models\ProveedorUser;
use App\Services\PagoProveedorService;
use App\Services\ProveedorApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminPagoProveedoresController extends Controller
{
    /**
     * Devuelve los proveedores REALES de Wiese en el MISMO formato que esperan las vistas
     * del flujo de pago (objetos con ->codigo, ->nombre, ->moneda, y ->datos_identificacion['rfc']).
     *
     * POR QUÃ‰: el flujo (formato/pago/abono) mostraba proveedores LOCALES (de prueba). Los reales
     * viven en Wiese; este helper los trae vÃ­a ProveedorApiService y los adapta a objetos con las
     * mismas propiedades que usaban las vistas, para no tener que reescribir las plantillas.
     * Si Wiese no responde (VPN/servidor), cae de vuelta a los proveedores locales (no rompe).
     *
     * @return \Illuminate\Support\Collection
     */
    private function proveedoresParaFlujoPago()
    {
        try {
            $res = app(ProveedorApiService::class)->listarProveedoresWiese();
            if ($res['success'] ?? false) {
                return collect($res['data']['items'] ?? [])
                    ->map(function ($p) {
                        $codigo = (string) ($p['codigo'] ?? $p['Codigo'] ?? '');
                        $monRaw = (string) ($p['moneda'] ?? $p['Moneda'] ?? '');
                        // Wiese: "1"=MXN, "2"=USD. El modelo local usa 'MXN'/'DOLLAR'.
                        $moneda = $monRaw === '2' ? 'DOLLAR' : 'MXN';
                        // Objeto anÃ³nimo con las MISMAS propiedades que la vista espera de ProveedorUser.
                        return (object) [
                            'id' => null, // Wiese no tiene id local; el flujo se enlaza por cÃ³digo.
                            'codigo' => $codigo,
                            'id_proveedor' => $codigo,
                            'nombre' => (string) ($p['nombre'] ?? $p['Nombre'] ?? ''),
                            'moneda' => $moneda,
                            // POR QUÃ‰: el banco/cuenta/clabe del proveedor no existe en columnas
                            // dedicadas de Wiese; Salcom los guarda en los campos comodÃ­n
                            // CTEXTOEXTRA1/2/3 de admClientes (Alan lo confirmÃ³). El endpoint C#
                            // los devuelve como banco/cuenta/clabe y aquÃ­ los metemos en
                            // datos_identificacion, que es de donde el Formato para pago los lee.
                            // POR QUÃ‰ dos nombres: ListarProveedorWeb los manda como
                            // banco/cuenta/clabe, pero BuscarPorRFC/BuscarPorCodigo (clase
                            // ClienteProveedor) los manda como textoExtra1/2/3. Leemos ambos.
                            'datos_identificacion' => [
                                'rfc' => (string) ($p['rfc'] ?? $p['Rfc'] ?? ''),
                                'banco' => (string) ($p['banco'] ?? $p['Banco'] ?? $p['textoExtra1'] ?? $p['TextoExtra1'] ?? ''),
                                'cuenta' => (string) ($p['cuenta'] ?? $p['Cuenta'] ?? $p['textoExtra2'] ?? $p['TextoExtra2'] ?? ''),
                                'clabe' => (string) ($p['clabe'] ?? $p['Clabe'] ?? $p['textoExtra3'] ?? $p['TextoExtra3'] ?? ''),
                            ],
                        ];
                    })
                    // Descartar registros basura: cÃ³digo/nombre vacÃ­os, "0", o el literal "(Ninguno)"
                    // (Wiese devuelve un registro genÃ©rico con cÃ³digo y nombre = "(Ninguno)").
                    ->filter(function ($p) {
                        $cod = trim($p->codigo);
                        $nom = trim($p->nombre);
                        if ($cod === '' || $cod === '0' || $nom === '') {
                            return false;
                        }
                        // Descartar el registro genÃ©rico "(Ninguno)" venga en cÃ³digo o nombre.
                        return stripos($cod, 'ninguno') === false
                            && stripos($nom, 'ninguno') === false;
                    })
                    ->sortBy('nombre')
                    ->values();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[FlujoPago] Wiese no disponible, uso proveedores locales: '.$e->getMessage());
        }

        // Fallback: si Wiese no responde, usar los locales (para no romper el flujo).
        return ProveedorUser::query()
            ->select('id', 'codigo', 'nombre', 'moneda', 'id_proveedor', 'datos_identificacion')
            ->orderBy('nombre')
            ->get();
    }

    // "PAGO A PROVEEDOR" ELIMINADO (2026-10-16): los métodos index/nuevo/create/
    // facturasJson/store/show/comprobantePago/cancelar se quitaron. El flujo nuevo marca
    // las facturas como pagadas al subir el comprobante del banco (CEP) en el expediente.
    // Se conservan: abonoInterno*, historialAbonos y anticipos* (abajo).

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // ABONO AL PROVEEDOR (Paso 3 â€” registro interno)
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    /** Listado de facturas pagadas para confirmar abono interno. */
    public function abonoInterno(Request $request)
    {
        $cuentaKey = trim((string) $request->input('cuenta', ''));
        $cuentaConfig = config("polizas_pago.{$cuentaKey}");

        // Proveedores REALES de Wiese (antes salÃ­an los locales de prueba).
        $proveedores = $this->proveedoresParaFlujoPago();

        // Folio precargado = siguiente consecutivo de la cuenta de WieseBanco.
        // POR QUÃ‰: el Folio del abono debe ser EL MISMO nÃºmero (NUM) que tomarÃ¡ el movimiento
        // en WieseBanco (el Quicken), para que coincidan. Por ahora todo mapea a BBVA 8969.
        $folioSugerido = $this->folioSugeridoWieseBanco();

        return view('admin.abono-proveedor.index', compact('proveedores', 'cuentaKey', 'cuentaConfig', 'folioSugerido'));
    }

    /**
     * Devuelve el siguiente folio (NUM) de la cuenta de WieseBanco, para precargar el campo
     * "Folio" del abono y que coincida con el movimiento que se crearÃ¡ en el Quicken.
     * Por ahora todas las cuentas del abono mapean a BBVA 8969 (la Ãºnica de WieseBanco).
     */
    private function folioSugeridoWieseBanco(): int
    {
        $cuenta = \App\Models\CuentaBancaria::where('activo', true)->where('clave_corta', '8969')->first();

        return $cuenta ? ((int) $cuenta->consecutivo_actual + 1) : 1;
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

        // Cuentas (las 4 pÃ³lizas). Cuenta activa = la seleccionada o la primera.
        $cuentas = config('polizas_pago');
        $cuentaKey = trim((string) $request->input('cuenta', ''));
        if (! isset($cuentas[$cuentaKey])) {
            $cuentaKey = array_key_first($cuentas);
        }
        $cuentaConfig = $cuentas[$cuentaKey];

        // Nombres de proveedores para resolver razÃ³n social
        $provNombres = ProveedorUser::pluck('nombre', 'codigo');

        // â”€â”€ Fuente 1: Anticipos registrados desde Abono al proveedor (cuentas USD) â”€â”€
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

        // â”€â”€ Fuente 2: Abonos internos estampados en facturas (cuentas por tÃ­tulo) â”€â”€
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

        // â”€â”€ Combinar y resolver razÃ³n social + aplicar filtros de texto/fecha â”€â”€
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

        // â”€â”€ KPIs (contadores) â”€â”€
        $kpiTodos = $baseRegistros->count();
        $kpiCancelados = $baseRegistros->where('cancelado', 1)->count();
        $kpiPendiente = $baseRegistros->filter(fn ($r) => (float) $r['pendiente'] > 0)->count();

        // â”€â”€ Filtro por tipo de documento (tab) â”€â”€
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

    /** Confirmar abono interno con nÃºmero de pÃ³liza. */
    public function abonoInternoConfirmar(Request $request)
    {
        $data = $request->validate([
            'factura_ids' => 'required|array|min:1',
            'factura_ids.*' => 'integer',
            // POR QUÃ‰ ya no es required: el folio real lo asigna Contpaqi al guardar (consecutivo
            // en tiempo real). El campo del formulario trae "Se asigna al guardar", no un folio.
            'poliza' => 'nullable|string|max:60',
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
            'fecha.before' => 'La fecha debe ser un dÃ­a anterior a hoy.',
            'referencia.required' => 'Escribe en Referencia las facturas o nÃºmero de compra.',
        ]);

        // El folio real lo pondrÃ¡ Contpaqi; mientras, usamos un texto marcador para los avisos.
        $data['poliza'] = trim((string) ($data['poliza'] ?? '')) ?: '(se asigna en Contpaqi)';
        if ($data['poliza'] === 'Se asigna al guardar') {
            $data['poliza'] = '(se asigna en Contpaqi)';
        }

        // ValidaciÃ³n de secuencia: solo facturas "pagada" pueden liquidarse aquÃ­.
        if ($errorSecuencia = $this->validarSecuenciaFacturas($data['factura_ids'], 'pagada', 'el abono al proveedor')) {
            return back()->with('error', $errorSecuencia.' La factura se marca como pagada al subir el comprobante del banco (CEP) en el expediente de pago.');
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

        // â”€â”€ Reflejar en WieseBanco + Contpaqi (sin duplicar procesos, pedido de Sandra) â”€â”€
        // POR QUÃ‰ aquÃ­: este es el momento del "saldar en 0". Por cada factura liquidada se crea
        // un movimiento en WieseBanco (con su NUM consecutivo) y se registra el pago en Contpaqi.
        // Envuelto en try/catch: si WieseBanco/Contpaqi fallara, NO debe tumbar el abono de Karen.
        try {
            app(\App\Services\WieseBancoContpaqiService::class)
                ->reflejarFacturasLiquidadas($facturas, $data['codigo_proveedor']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[AbonoInterno] No se pudo reflejar en WieseBanco/Contpaqi: '.$e->getMessage());
        }

        // NotificaciÃ³n para admin (campanita + badge en sidebar)
        try {
            $montoTotal = $facturas->sum('monto_pagado');
            $montoFmt = number_format((float) $montoTotal, 2);
            \App\Models\Alerta::create([
                'tipo' => 'abono_interno_registrado',
                'modulo' => 'abono_proveedor',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => session('admin_id'),
                'titulo' => 'Abono registrado',
                'contenido' => "PÃ³liza {$data['poliza']} â€” {$facturas->count()} factura(s) liquidadas por \${$montoFmt}. Proveedor: {$data['codigo_proveedor']}.",
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

        // NotificaciÃ³n para proveedor: facturas liquidadas
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
            ->with('ok', 'Abono registrado. PÃ³liza: ' . $data['poliza'] . ' â€” ' . $facturas->count() . ' factura(s) liquidadas.');
    }

    /**
     * Guardar el abono como ANTICIPO cuando no hay OC/factura en el sistema.
     * Se dispara desde el botÃ³n "Guardar" en la pestaÃ±a InformaciÃ³n Adicional.
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
            'fecha.before' => 'La fecha debe ser un dÃ­a anterior a hoy.',
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
            'folio' => $data['poliza'], // el folio/nÂº pÃ³liza que capturÃ³
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
            'folio_general' => $data['referencia'], // nÃºmero de orden
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
            ->with('ok', 'Pago registrado como anticipo. Referencia/Orden: ' . $data['referencia'] . ' â€” $' . number_format($total, 2) . '.');
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // ANTICIPOS
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    public function anticiposIndex(Request $request)
    {
        $proveedores = ProveedorUser::query()
            ->select('id', 'codigo', 'nombre', 'id_proveedor', 'datos_identificacion')
            ->orderBy('nombre')
            ->get();

        // Departamentos que hacen anticipos (para los filtros/tabs).
        $departamentos = ['Compras Nacional', 'LogÃ­stica', 'Mantenimiento'];

        $depto = trim((string) $request->query('departamento', ''));
        $buscar = trim((string) $request->query('q', ''));

        // Acceso por departamento: si el admin es de un Ã¡rea, solo ve la suya.
        $deptoAdmin = $this->departamentoDelAdmin();
        $accesoRestringido = $deptoAdmin !== null;
        if ($accesoRestringido) {
            $depto = $deptoAdmin;
            $departamentos = [$deptoAdmin]; // solo muestra su chip
        }

        $base = \App\Models\AnticipoProveedor::query()
            ->when($depto !== '', fn ($qb) => $qb->where('departamento', $depto))
            ->when($buscar !== '', function ($qb) use ($buscar) {
                $qb->where(function ($w) use ($buscar) {
                    $w->where('folio_general', 'like', "%{$buscar}%")
                        ->orWhere('nombre_proveedor', 'like', "%{$buscar}%")
                        ->orWhere('codigo_proveedor', 'like', "%{$buscar}%");
                });
            });

        $anticipos = (clone $base)
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        // KPIs por departamento (conteo y monto total) â€” respetan la bÃºsqueda pero no el filtro de depto.
        $baseKpi = \App\Models\AnticipoProveedor::query()
            ->when($buscar !== '', function ($qb) use ($buscar) {
                $qb->where(function ($w) use ($buscar) {
                    $w->where('folio_general', 'like', "%{$buscar}%")
                        ->orWhere('nombre_proveedor', 'like', "%{$buscar}%")
                        ->orWhere('codigo_proveedor', 'like', "%{$buscar}%");
                });
            });

        $kpiPorDepto = [];
        foreach ($departamentos as $d) {
            $q = (clone $baseKpi)->where('departamento', $d);
            $kpiPorDepto[$d] = [
                'count' => (clone $q)->count(),
                'monto' => (float) (clone $q)->sum('total_banco'),
            ];
        }
        $kpiTodos = [
            'count' => (clone $baseKpi)->count(),
            'monto' => (float) (clone $baseKpi)->sum('total_banco'),
        ];

        return view('admin.anticipos.index', compact(
            'proveedores', 'anticipos', 'departamentos', 'depto', 'buscar', 'kpiPorDepto', 'kpiTodos', 'accesoRestringido'
        ));
    }

    /**
     * Exporta los anticipos a CSV, respetando el filtro de departamento y bÃºsqueda.
     */
    public function anticiposExcel(Request $request)
    {
        $depto = trim((string) $request->query('departamento', ''));
        $buscar = trim((string) $request->query('q', ''));

        // Si el admin es de un departamento, forzar su departamento (acceso por rol).
        $deptoForzado = $this->departamentoDelAdmin();
        if ($deptoForzado !== null) {
            $depto = $deptoForzado;
        }

        $anticipos = \App\Models\AnticipoProveedor::query()
            ->when($depto !== '', fn ($qb) => $qb->where('departamento', $depto))
            ->when($buscar !== '', function ($qb) use ($buscar) {
                $qb->where(function ($w) use ($buscar) {
                    $w->where('folio_general', 'like', "%{$buscar}%")
                        ->orWhere('nombre_proveedor', 'like', "%{$buscar}%")
                        ->orWhere('codigo_proveedor', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ['Reporte de Anticipos'.($depto !== '' ? ' â€” '.$depto : '')];
        $lines[] = ['Generado', now()->format('d/m/Y H:i')];
        $lines[] = [];
        $lines[] = ['Fecha', 'Folio general', 'Departamento', 'Proveedor', 'CÃ³digo', 'RFC', 'Banco', 'Importe', 'IVA', 'Total', 'Estatus', 'UUID CFDI', 'Concepto'];

        $totalGeneral = 0.0;
        foreach ($anticipos as $a) {
            $totalGeneral += (float) $a->total_banco;
            $lines[] = [
                optional($a->fecha)->format('d/m/Y'),
                $a->folio_general,
                $a->departamento,
                $a->nombre_proveedor,
                $a->codigo_proveedor,
                $a->rfc_proveedor,
                $a->banco,
                number_format((float) $a->importe, 2, '.', ''),
                number_format((float) $a->iva, 2, '.', ''),
                number_format((float) $a->total_banco, 2, '.', ''),
                ucfirst($a->estatus),
                $a->uuid_cfdi,
                $a->concepto,
            ];
        }
        $lines[] = [];
        $lines[] = ['', '', '', '', '', '', 'TOTAL', '', '', number_format($totalGeneral, 2, '.', '')];

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }
        rewind($handle);
        $output = stream_get_contents($handle);
        fclose($handle);

        $sufijo = $depto !== '' ? '_'.\Illuminate\Support\Str::slug($depto) : '';
        $filename = 'Anticipos'.$sufijo.'_'.now()->format('Y-m-d').'.csv';

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Devuelve el departamento asociado al rol del admin logueado, o null si
     * es gerente/admin (ve todo) o su rol no corresponde a un departamento.
     */
    private function departamentoDelAdmin(): ?string
    {
        $adminId = session('admin_id');
        if (! $adminId) {
            return null;
        }
        $admin = \App\Models\AdminUser::find($adminId);
        if (! $admin || in_array($admin->rol, ['gerente', 'admin'], true)) {
            return null;
        }

        // Mapa rol -> departamento de anticipos.
        $mapa = [
            'compras_nacional' => 'Compras Nacional',
            'logistica' => 'LogÃ­stica',
            'mantenimiento' => 'Mantenimiento',
        ];

        return $mapa[$admin->rol] ?? null;
    }

    public function anticiposStore(Request $request)
    {
        // Limpiar comas de los campos numÃ©ricos antes de validar
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
            'moneda' => 'nullable|string|max:10',
            'tipo_cambio' => 'nullable|string|max:20',
            'uuid_cfdi' => 'nullable|string|max:36',
            'xml_anticipo' => 'nullable|file|mimetypes:text/xml,application/xml,text/plain,application/pdf|max:10240',
        ]);

        $proveedor = ProveedorUser::findOrFail($data['proveedor_id']);
        $iva = (float) ($data['iva'] ?? 0);
        $importe = (float) $data['importe'];
        $totalBanco = $importe + $iva;

        // Folio fijo de contabilidad
        $folio = 'FCONA-0040';

        // UUID del CFDI de anticipo: si suben el XML o PDF, lo extraemos (fuente confiable);
        // si no, usamos el capturado a mano. El archivo manda sobre el texto manual.
        $uuidCfdi = trim((string) ($data['uuid_cfdi'] ?? '')) ?: null;
        $xmlPath = null;
        if ($request->hasFile('xml_anticipo')) {
            $archivo = $request->file('xml_anticipo');
            $esPdf = strtolower($archivo->getClientOriginalExtension()) === 'pdf'
                || $archivo->getMimeType() === 'application/pdf';

            if ($esPdf) {
                // Leer texto del PDF y extraer el UUID (formato "UUID:...").
                $uuidDelArchivo = $this->extraerUuidDePdf($archivo);
                // Disco PRIVADO (local): anticipos llevan datos fiscales.
                $xmlPath = $archivo->store('anticipos/pdf', 'local');
            } else {
                $xmlContent = file_get_contents($archivo->getRealPath());
                $uuidDelArchivo = $this->extraerUuidDeXml((string) $xmlContent);
                $xmlPath = $archivo->store('anticipos/xml', 'local');
            }

            if ($uuidDelArchivo) {
                $uuidCfdi = $uuidDelArchivo;
            }
        }
        $uuidCfdi = $uuidCfdi ? strtoupper($uuidCfdi) : null;

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
            'uuid_cfdi' => $uuidCfdi,
            'departamento' => $data['departamento'],
            'fecha' => $data['fecha'],
            'concepto' => $data['concepto'],
            'estatus' => 'pagado',
            'creado_por' => session('admin_id'),
            'datos' => array_filter([
                'xml_anticipo' => $xmlPath,
                'moneda' => $data['moneda'] ?? 'MXN',
                'tipo_cambio' => $data['tipo_cambio'] ?? '1.0000',
            ]),
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

        // El nombre incluye el folio_general (identificador Ãºnico del anticipo) y su id,
        // para poder validar al adjuntarlo que el formato corresponde al anticipo correcto.
        $folioGeneralSlug = \Illuminate\Support\Str::slug($anticipo->folio_general ?: 'anticipo', '-');

        return $pdf->download("Formato_Anticipo_{$anticipo->id}_{$folioGeneralSlug}_{$anticipo->codigo_proveedor}.pdf");
    }

    /**
     * Sirve el PDF del formato de anticipo adjuntado (el que se subiÃ³ al aplicar).
     * Se entrega vÃ­a Laravel para no depender del acceso directo a /storage.
     */
    public function anticiposFormatoAdjunto(\App\Models\AnticipoProveedor $anticipo)
    {
        $ruta = $anticipo->datos['formato_pdf'] ?? null;

        // Disco PRIVADO primero; 'public' como respaldo para anticipos viejos pre-migraciÃ³n.
        $disco = null;
        foreach (['local', 'public'] as $d) {
            if ($ruta && \Illuminate\Support\Facades\Storage::disk($d)->exists($ruta)) {
                $disco = $d;
                break;
            }
        }
        if (! $disco) {
            abort(404, 'No hay formato de anticipo adjuntado.');
        }

        $nombre = 'Formato_Anticipo_'.$anticipo->id.'_'.($anticipo->folio_general ?: 'anticipo').'.pdf';

        return response()->file(
            \Illuminate\Support\Facades\Storage::disk($disco)->path($ruta),
            ['Content-Disposition' => 'inline; filename="'.$nombre.'"']
        );
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
            $msg = 'Este anticipo ya estÃ¡ '.$anticipo->estatus.'. No se puede aplicar de nuevo.';
            return $wantsJson
                ? response()->json(['ok' => false, 'mensaje' => $msg], 422)
                : back()->withErrors($msg);
        }

        // Validar que el CONTENIDO del PDF corresponda a ESTE anticipo.
        // Leemos el texto del PDF y exigimos que contenga el folio_general del anticipo
        // (identificador visible en el formato). Respaldo: el cÃ³digo del proveedor.
        $textoPdf = $this->extraerTextoPdfNormalizado($request->file('formato_pdf'));
        if ($textoPdf === null) {
            $msg = 'No se pudo leer el contenido del PDF. AsegÃºrate de adjuntar el formato de anticipo generado por el sistema (no una imagen escaneada).';
            return $wantsJson
                ? response()->json(['ok' => false, 'mensaje' => $msg], 422)
                : back()->withErrors($msg);
        }

        $folioNorm = $this->normalizarIdentificador($anticipo->folio_general);
        $codigoNorm = $this->normalizarIdentificador($anticipo->codigo_proveedor);
        $coincide = ($folioNorm !== '' && str_contains($textoPdf, $folioNorm))
            || ($codigoNorm !== '' && str_contains($textoPdf, $codigoNorm));

        if (! $coincide) {
            $msg = "El formato PDF adjunto no corresponde a este anticipo ({$anticipo->folio_general}). El documento no menciona este anticipo ni el proveedor. Descarga y adjunta el formato de este anticipo.";
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
            // Disco PRIVADO (local): formato de anticipo sensible.
            $filePath = $request->file('formato_pdf')->store('anticipos', 'local');
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
            $msg = 'El anticipo ya estÃ¡ cancelado.';
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
            throw new InvalidArgumentException('PÃ³liza no vÃ¡lida.');
        }

        return $meta;
    }

    /**
     * Lee el texto de un PDF (subido) y lo devuelve normalizado:
     * mayÃºsculas y solo alfanumÃ©rico (sin espacios/guiones/acentos), para
     * comparar identificadores de forma robusta. Devuelve null si no se pudo leer.
     */
    private function extraerTextoPdfNormalizado(\Illuminate\Http\UploadedFile $archivo): ?string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $texto = $parser->parseFile($archivo->getRealPath())->getText();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[FormatoPDF] No se pudo leer el PDF: '.$e->getMessage());
            return null;
        }

        if (trim((string) $texto) === '') {
            return null;
        }

        // Normalizar: quitar acentos, pasar a mayÃºsculas, dejar solo A-Z0-9.
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtoupper($texto);

        return preg_replace('/[^A-Z0-9]/', '', $texto);
    }

    /** Normaliza un identificador a solo A-Z0-9 mayÃºsculas para comparar. */
    private function normalizarIdentificador(?string $valor): string
    {
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $valor) ?: (string) $valor;
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($valor));
    }

    /**
     * Extrae el UUID (Folio Fiscal) del texto de un PDF de CFDI.
     * Busca el patrÃ³n UUID de 36 caracteres. Devuelve mayÃºsculas o null.
     */
    private function extraerUuidDePdf(\Illuminate\Http\UploadedFile $archivo): ?string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $texto = $parser->parseFile($archivo->getRealPath())->getText();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[AnticipoPDF] No se pudo leer el PDF: '.$e->getMessage());
            return null;
        }

        // El UUID puede venir como "UUID:xxxx", "Folio Fiscal xxxx" o suelto.
        if (preg_match('/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12})/', (string) $texto, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Extrae el UUID del TimbreFiscalDigital de un XML CFDI.
     * Devuelve el UUID en mayÃºsculas o null si no se encuentra.
     */
    private function extraerUuidDeXml(string $xmlContent): ?string
    {
        $xmlContent = trim($xmlContent);
        if ($xmlContent === '' || ! str_contains($xmlContent, '<')) {
            return null;
        }

        libxml_use_internal_errors(true);
        try {
            $xml = new \SimpleXMLElement($xmlContent);
        } catch (\Throwable) {
            // Respaldo por regex si el XML no parsea limpio.
            if (preg_match('/UUID\s*=\s*"([0-9A-Fa-f-]{36})"/', $xmlContent, $m)) {
                return strtoupper($m[1]);
            }
            return null;
        }

        $nodos = $xml->xpath("//*[local-name()='TimbreFiscalDigital']");
        if ($nodos && isset($nodos[0])) {
            $uuid = (string) ($nodos[0]->attributes()['UUID'] ?? '');
            if ($uuid !== '') {
                return strtoupper($uuid);
            }
        }

        if (preg_match('/UUID\s*=\s*"([0-9A-Fa-f-]{36})"/', $xmlContent, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Orden del flujo de pago. Cada estatus solo puede avanzar al siguiente.
     * pendiente â†’ programada â†’ pagada â†’ liquidada
     */
    private const ORDEN_FLUJO = ['pendiente', 'programada', 'pagada', 'liquidada'];

    /**
     * Valida que todas las facturas solicitadas estÃ©n en el estatus esperado
     * para este paso del flujo. Si alguna estÃ¡ en otro estatus, devuelve un
     * mensaje que explica quÃ© paso falta, en lugar de descartarla en silencio.
     *
     * @param  array<int>  $facturaIds
     * @return string|null  Mensaje de error, o null si la secuencia es vÃ¡lida.
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
            'pagada' => 'Pagada (comprobante del banco subido)',
            'liquidada' => 'Liquidada (Abono al proveedor)',
        ];

        $detalles = $fueraDeSecuencia->map(function ($f) use ($posRequerida, $etiquetas) {
            $folio = $f->folio_cfdi ?: ('FAC-'.$f->id);
            $posActual = array_search($f->estatus, self::ORDEN_FLUJO, true);
            $estatusActual = $etiquetas[$f->estatus] ?? ucfirst($f->estatus);

            if ($posActual === false) {
                return "{$folio} tiene un estatus no vÃ¡lido ({$f->estatus}).";
            }
            if ($posActual < $posRequerida) {
                return "{$folio} aÃºn estÃ¡ en \"{$estatusActual}\"; falta completar el paso anterior antes de continuar.";
            }
            return "{$folio} ya pasÃ³ este paso (estÃ¡ en \"{$estatusActual}\"). No se puede repetir.";
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
