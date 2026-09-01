<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Alerta;
use App\Models\ContactoProveedor;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\Producto;
use App\Models\ProveedorUser;
use App\Models\SolicitudAlta;
use App\Services\AlertEngineService;
use App\Services\AltaFacturaValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PortalProveedorController extends Controller
{
    public function mostrarPortal()
    {
        return view('proveedores.portal');
    }

    public function mostrarDashboard()
    {
        return view('proveedores.dashboard');
    }

    public function mostrarOnboarding()
    {
        try {
            $proveedor = ProveedorUser::find(session('proveedor_id'));

            // Si entró como admin, proveedor_id antes era el id de admin_users (sin fila en proveedores_users).
            // No redirigir a login: eso causa rebote (login -> portal) y parece que onboarding no carga.
            if (! $proveedor) {
                $admin = AdminUser::find(session('proveedor_id'));
                if ($admin && $admin->rol === 'admin') {
                    $existente = ProveedorUser::where('usuario', $admin->usuario)->first();
                    if ($existente) {
                        $proveedor = $existente;
                    } else {
                        $datos = [
                            'usuario' => $admin->usuario,
                            'nombre' => $admin->nombre,
                            'correo' => $admin->correo ?: ($admin->usuario.'@salcom.local'),
                            'password' => $admin->password,
                            'tipo_persona' => 'Persona Moral',
                            'activo' => true,
                        ];
                        if (Schema::hasColumn('proveedores_users', 'id_proveedor')) {
                            $datos['id_proveedor'] = 'ADMIN-'.$admin->id;
                        } elseif (Schema::hasColumn('proveedores_users', 'codigo_compras')) {
                            $datos['codigo_compras'] = 'ADMIN-'.$admin->id;
                        }
                        $proveedor = new ProveedorUser;
                        $proveedor->forceFill($datos)->save();
                        $proveedor = $proveedor->fresh();
                    }

                    session([
                        'proveedor_id' => $proveedor->id,
                        'proveedor_nombre' => $proveedor->nombre ?? $admin->nombre,
                        'proveedor_codigo' => $proveedor->id_proveedor ?? $proveedor->codigo_compras ?? ('ADMIN-'.$admin->id),
                        'proveedor_correo' => $proveedor->correo ?? $admin->correo,
                    ]);
                }
            }

            if (! $proveedor) {
                return redirect('/portal-proveedor')
                    ->with('error', 'No se pudo cargar el onboarding. Cierra sesión e inicia de nuevo.');
            }

            try {
                $proveedor->load(['documentos', 'contactos']);
            } catch (\Exception $e) {
                // Si falla, el proveedor seguirá sin documentos cargados
            }

            $pasoRegistro = true;
            try {
                $pasoBancarios = $proveedor->tieneFormularioDatosBancarios();
            } catch (\Exception $e) {
                $pasoBancarios = false;
            }

            // Detectar si tiene cuentas duales (MXN + USD) pendientes de confirmar
            // Aparece siempre que el proveedor opere en dólares y no haya confirmado
            $cuentasDualPendientes = false;
            $datosBancariosResumen = [];
            if ($proveedor->esMonedaDollar()) {
                $di = $proveedor->datos_identificacion ?? [];
                $yaConfirmadas = ! empty($di['cuentas_dual_confirmadas']);
                if (! $yaConfirmadas) {
                    $cuentasDualPendientes = true;
                    $datosBancariosResumen = [
                        'banco_mxn' => $di['banco'] ?? '—',
                        'clabe_mxn' => $di['clabe'] ?? '—',
                        'cuenta_mxn' => $di['cuenta'] ?? '—',
                        'banco_usd' => $di['banco_usd'] ?? '—',
                        'clabe_usd' => $di['clabe_usd'] ?? '—',
                        'cuenta_usd' => $di['cuenta_usd'] ?? '—',
                    ];
                }
            }
            try {
                $pasoDocs = $proveedor->documentosFiscalesCompletos();
            } catch (\Exception $e) {
                $pasoDocs = false;
            }
            try {
                $pasoDocsRenovar = $proveedor->documentosPorRenovar();
            } catch (\Exception $e) {
                $pasoDocsRenovar = false;
            }
            try {
                $numContactos = $proveedor->relationLoaded('contactos')
                    ? $proveedor->contactos->count()
                    : $proveedor->contactos()->count();
            } catch (\Exception $e) {
                $numContactos = 0;
            }
            $pasoContactos = $numContactos >= 2;

            // Paso 5: Confirmación cuenta Wiese (después de docs)
            $pasoWiese = $proveedor->cuentaWieseConfirmada();
            $cuentasWiese = $proveedor->cuentasWiese();
            $wiesePendiente = false;
            $wieseError = null;

            // Si docs están completos pero cuenta Wiese no confirmada, buscar por RFC
            if ($pasoDocs && !$pasoWiese && empty($cuentasWiese)) {
                $rfcProveedor = strtoupper(trim((string) $proveedor->rfc));
                if (!empty($rfcProveedor)) {
                    try {
                        $wieseApi = app(\App\Services\ProveedorApiService::class);
                        $resultado = $wieseApi->buscarProveedorPorRFC($rfcProveedor);
                        if ($resultado['success'] && !empty($resultado['data']['cuentas'])) {
                            $cuentasWiese = $resultado['data']['cuentas'];
                            // Guardar las cuentas encontradas
                            $datos = $proveedor->datos_identificacion ?? [];
                            $datos['cuentas_wiese'] = $cuentasWiese;
                            $proveedor->update(['datos_identificacion' => $datos]);
                            $wiesePendiente = true;
                        } elseif ($resultado['success'] && empty($resultado['data']['cuentas'])) {
                            // RFC no encontrado en Wiese — proveedor nuevo, se auto-confirma
                            $datos = $proveedor->datos_identificacion ?? [];
                            $datos['cuenta_wiese_confirmada'] = true;
                            $datos['cuenta_wiese_nueva'] = true;
                            $proveedor->update(['datos_identificacion' => $datos]);
                            $pasoWiese = true;
                        }
                    } catch (\Exception $e) {
                        $wieseError = 'No se pudo conectar con el sistema para verificar tu cuenta.';
                    }
                } else {
                    $wieseError = 'No tienes RFC registrado. Actualiza tu perfil.';
                }
            } elseif ($pasoDocs && !$pasoWiese && !empty($cuentasWiese)) {
                $wiesePendiente = true;
            }

            $pasoListoDireccion = $pasoBancarios && $pasoDocs && $pasoContactos && $pasoWiese && !$cuentasDualPendientes;
            $pasoActivo = (bool) $proveedor->activo;
            $onboardingBloqueado = $proveedor->onboardingEdicionBloqueada();
            $estatusAlta = $proveedor->solicitud_alta_estatus ?? null;

            $completados = (int) $pasoRegistro + (int) $pasoBancarios + (int) $pasoDocs + (int) $pasoContactos + (int) $pasoWiese + (int) ($pasoListoDireccion && $pasoActivo ? 1 : 0);
            $totalPasos = 6;
            $pct = (int) round(100 * $completados / $totalPasos);

            return view('proveedores.onboarding', compact(
                'proveedor',
                'pasoRegistro',
                'pasoBancarios',
                'pasoDocs',
                'pasoDocsRenovar',
                'numContactos',
                'pasoContactos',
                'pasoWiese',
                'cuentasWiese',
                'wiesePendiente',
                'wieseError',
                'pasoListoDireccion',
                'pasoActivo',
                'onboardingBloqueado',
                'estatusAlta',
                'completados',
                'totalPasos',
                'pct',
                'cuentasDualPendientes',
                'datosBancariosResumen'
            ));

        } catch (\Exception $e) {
            Log::error('Onboarding error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            // Fallback: mostrar onboarding básico sin datos calculados
            $proveedor = ProveedorUser::find(session('proveedor_id'));

            return view('proveedores.onboarding', [
                'proveedor' => $proveedor,
                'pasoRegistro' => true,
                'pasoBancarios' => false,
                'pasoDocs' => false,
                'pasoDocsRenovar' => false,
                'numContactos' => 0,
                'pasoContactos' => false,
                'pasoListoDireccion' => false,
                'pasoActivo' => false,
                'onboardingBloqueado' => false,
                'estatusAlta' => null,
                'completados' => 1,
                'totalPasos' => 5,
                'pct' => 20,
                'cuentasDualPendientes' => false,
                'datosBancariosResumen' => [],
            ]);
        }
    }

    public function mostrarBusiness()
    {
        return view('proveedores.business');
    }

    public function mostrarPaymentHistory(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $codigo = $proveedor?->id_proveedor ?: session('proveedor_codigo');

        $base = PagoProveedor::query()
            ->when($proveedor?->id, fn ($q) => $q->where('proveedor_id', $proveedor->id))
            ->when(! $proveedor?->id && $codigo, fn ($q) => $q->where('codigo_proveedor', $codigo));

        if ($request->filled('fecha_desde')) {
            $base->whereDate('fecha_pago', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $base->whereDate('fecha_pago', '<=', $request->input('fecha_hasta'));
        }

        $kpis = [
            'proceso' => (clone $base)->where('estatus', 'borrador')->count(),
            'confirmados' => (clone $base)->where('estatus', 'confirmado')->count(),
            'cancelados' => (clone $base)->where('estatus', 'cancelado')->count(),
            'totales' => (clone $base)->count(),
            'monto_pagado' => (float) (clone $base)->where('estatus', 'confirmado')->sum('monto_neto'),
        ];

        $query = clone $base;
        $buscar = trim((string) $request->input('q', ''));
        $campo = $request->input('campo', 'cheque');
        if ($buscar !== '') {
            if ($campo === 'monto') {
                $clean = str_replace([',', '$'], '', $buscar);
                $query->where(function ($q) use ($clean) {
                    $q->where('monto_neto', 'like', '%'.$clean.'%')
                        ->orWhere('monto_total', 'like', '%'.$clean.'%');
                });
            } elseif ($campo === 'facturas') {
                $query->where('num_facturas', 'like', '%'.$buscar.'%');
            } elseif ($campo === 'estatus') {
                $query->where('estatus', 'like', '%'.$buscar.'%');
            } else {
                $query->where(function ($q) use ($buscar) {
                    $q->where('id', 'like', '%'.$buscar.'%')
                        ->orWhere('datos_confirmacion->numero_cheque', 'like', '%'.$buscar.'%')
                        ->orWhere('datos_confirmacion->no_cheque', 'like', '%'.$buscar.'%');
                });
            }
        }

        $pagos = $query->orderByDesc('fecha_pago')->orderByDesc('id')->paginate(30)->withQueryString();

        $filtros = [
            'fecha_desde' => $request->input('fecha_desde', ''),
            'fecha_hasta' => $request->input('fecha_hasta', ''),
            'q' => $buscar,
            'campo' => $campo,
        ];

        return view('proveedores.payment-history', compact('proveedor', 'pagos', 'kpis', 'filtros', 'codigo'));
    }

    /** Listado de todas las facturas del proveedor (menú Facturas). */
    public function mostrarFacturas(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $codigo = $proveedor?->id_proveedor ?: session('proveedor_codigo');

        $baseAll = Factura::query()
            ->when($codigo, fn ($q) => $q->where('codigo_proveedor', $codigo));

        if ($request->filled('fecha_desde')) {
            $baseAll->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $baseAll->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        // Listado por defecto sin rechazadas; el KPI rojo sí las cuenta.
        $base = (clone $baseAll)->where('estatus', '!=', 'rechazada');

        // Total abonado al proveedor
        $totalAbonado = (clone $base)->sum('monto_pagado');
        $numAbonadas = (clone $base)->where('monto_pagado', '>', 0)->where('estatus', 'pendiente')->count();
        $totalPorCobrar = (clone $base)->whereIn('estatus', ['pendiente', 'programada'])
            ->selectRaw('SUM(total - monto_pagado) as pendiente')->value('pendiente') ?? 0;

        $kpis = [
            'rechazadas' => (clone $baseAll)->where('estatus', 'rechazada')->count(),
            'pendientes' => (clone $base)->where('estatus', 'pendiente')->where('monto_pagado', 0)->count(),
            'abonadas' => $numAbonadas,
            'pagadas' => (clone $base)->whereIn('estatus', ['pagada', 'programada'])->count(),
            'totales' => (clone $base)->count(),
            'abonado' => (float) $totalAbonado,
            'por_cobrar' => (float) $totalPorCobrar,
        ];

        $buscar = trim((string) $request->input('q', ''));
        $campo = $request->input('campo', 'folio');
        $filtrandoRechazadas = $campo === 'estatus' && str_contains(mb_strtolower($buscar), 'rechaz');

        $query = $filtrandoRechazadas ? clone $baseAll : clone $base;
        if ($buscar !== '') {
            if ($campo === 'monto') {
                $query->where('total', 'like', '%'.str_replace([',', '$'], '', $buscar).'%');
            } elseif ($campo === 'estatus') {
                if (str_contains(mb_strtolower($buscar), 'abonad')) {
                    // Filtro especial: facturas con abono parcial (monto_pagado > 0 y estatus pendiente)
                    $query->where('monto_pagado', '>', 0)->where('estatus', 'pendiente');
                } else {
                    $query->where('estatus', 'like', '%'.$buscar.'%');
                }
            } else {
                $query->where(function ($q) use ($buscar) {
                    $q->where('folio_cfdi', 'like', '%'.$buscar.'%')
                        ->orWhere('uuid_cfdi', 'like', '%'.$buscar.'%');
                });
            }
        }

        $facturas = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        $filtros = [
            'fecha_desde' => $request->input('fecha_desde', ''),
            'fecha_hasta' => $request->input('fecha_hasta', ''),
            'q' => $buscar,
            'campo' => $campo,
        ];

        // === Facturas Wiese (API) ===
        $wieseFacturas = collect();
        $wieseTotal = 0;
        $wieseError = null;
        $wieseKpis = ['pendientes' => 0, 'pagadas' => 0, 'canceladas' => 0, 'totales' => 0];

        $wieseCodigo = trim((string) ($proveedor?->id_proveedor ?? $proveedor?->codigo ?? ''));
        if ($wieseCodigo !== '') {
            try {
                $wieseApi = app(\App\Services\ProveedorApiService::class);
                $fechaInicio = $request->input('fecha_desde', now()->subYear()->startOfYear()->format('Y-m-d'));
                $fechaFin = $request->input('fecha_hasta', now()->format('Y-m-d'));
                $ocResult = $wieseApi->listarDocumentosOCPorProveedorFechas($wieseCodigo, $fechaInicio, $fechaFin);
                if ($ocResult['success'] ?? false) {
                    $all = collect($ocResult['data']['items'] ?? []);

                    // Asignar estatus: cancelado=1 → cancelada, pendiente>0 → pendiente, else → pagada
                    $all = $all->map(function ($doc) {
                        if (($doc['ccancelado'] ?? 0) == 1) {
                            $doc['_estatus'] = 'cancelada';
                        } elseif (($doc['cpendiente'] ?? 0) > 0) {
                            $doc['_estatus'] = 'pendiente';
                        } else {
                            $doc['_estatus'] = 'pagada';
                        }
                        return $doc;
                    })->values();

                    // KPIs Wiese
                    $wieseKpis['pendientes'] = $all->where('_estatus', 'pendiente')->count();
                    $wieseKpis['pagadas'] = $all->where('_estatus', 'pagada')->count();
                    $wieseKpis['canceladas'] = $all->where('_estatus', 'cancelada')->count();
                    $wieseKpis['totales'] = $all->count();

                    // Filtrar por estatus si viene en query
                    $filtroEstatus = $request->input('wiese_estatus', '');
                    if ($filtroEstatus !== '') {
                        $all = $all->where('_estatus', $filtroEstatus);
                    }

                    $wieseTotal = $all->count();
                    $wieseFacturas = $all->take(100)->values();
                } else {
                    $wieseError = $ocResult['message'] ?? 'No se pudieron cargar las facturas.';
                }
            } catch (\Exception $e) {
                $wieseError = 'No se pudo conectar con el sistema de facturación.';
            }
        }

        return view('proveedores.facturas', compact('proveedor', 'facturas', 'filtros', 'codigo', 'kpis', 'wieseFacturas', 'wieseTotal', 'wieseError', 'wieseKpis'));
    }

    /** KPIs de facturas en JSON (polling en tiempo real). */
    public function facturasKpisJson()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $codigo = $proveedor?->id_proveedor ?: session('proveedor_codigo');

        $base = Factura::query()
            ->when($codigo, fn ($q) => $q->where('codigo_proveedor', $codigo));

        return response()->json([
            'rechazadas' => (clone $base)->where('estatus', 'rechazada')->count(),
            'pendientes' => (clone $base)->where('estatus', 'pendiente')->where('monto_pagado', 0)->count(),
            'abonadas' => (clone $base)->where('monto_pagado', '>', 0)->where('estatus', 'pendiente')->count(),
            'pagadas' => (clone $base)->whereIn('estatus', ['pagada', 'programada'])->count(),
            'totales' => (clone $base)->where('estatus', '!=', 'rechazada')->count(),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }

    public function facturasExcel(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $codigo = $proveedor?->id_proveedor ?: session('proveedor_codigo');

        $query = Factura::query()
            ->when($codigo, fn ($q) => $q->where('codigo_proveedor', $codigo))
            ->where('estatus', '!=', 'rechazada')
            ->orderByDesc('created_at');

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        $rows = $query->get();
        $filename = 'Facturas_'.($codigo ?: 'proveedor').'_'.now()->format('Y-m-d').'.csv';
        $output = "\xEF\xBB\xBF";
        $output .= "Fecha,Folio,UUID,Flete,Total,Estatus\r\n";
        foreach ($rows as $f) {
            $output .= implode(',', [
                $f->created_at?->format('d/m/Y') ?? '',
                '"'.str_replace('"', '""', (string) $f->folio_cfdi).'"',
                '"'.str_replace('"', '""', (string) $f->uuid_cfdi).'"',
                $f->es_fletera ? 'Si' : 'No',
                number_format((float) $f->total, 2, '.', ''),
                $f->estatus,
            ])."\r\n";
        }

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /** Campanita: alertas recientes en JSON (polling sin recargar). */
    public function alertasRecientesJson()
    {
        // El layout hace polling cada 1.5–3 s. Sin reflash, esas peticiones
        // se comen los mensajes flash (validación de factura, errores, etc.).
        session()->reflash();

        $proveedorId = session('proveedor_id');
        if (! $proveedorId) {
            return response()->json(['sin_leer' => 0, 'items' => []], 401);
        }

        $sinLeer = Alerta::where('destinatario_tipo', 'proveedor')
            ->where('destinatario_id', $proveedorId)
            ->where('estatus', '!=', 'leida')
            ->where('estatus', '!=', 'accionada')
            ->count();

        // Solo no leídas: al marcar desaparecen del dropdown
        $items = Alerta::where('destinatario_tipo', 'proveedor')
            ->where('destinatario_id', $proveedorId)
            ->where('estatus', '!=', 'leida')
            ->where('estatus', '!=', 'accionada')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'titulo', 'contenido', 'estatus', 'tipo', 'created_at'])
            ->map(fn (Alerta $a) => [
                'id' => $a->id,
                'titulo' => $a->titulo,
                'contenido' => $a->contenido,
                'estatus' => $a->estatus,
                'tipo' => $a->tipo,
                'leida' => false,
                'hace' => optional($a->created_at)->diffForHumans(),
            ]);

        $proveedor = ProveedorUser::find($proveedorId);
        $onboarding = [
            'activo' => (bool) ($proveedor?->activo),
            'estatus' => $proveedor?->solicitud_alta_estatus,
            'bloqueado' => $proveedor?->onboardingEdicionBloqueada() ?? false,
        ];

        return response()
            ->json(['sin_leer' => $sinLeer, 'items' => $items, 'onboarding' => $onboarding])
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    /** Marca una alerta como leída al hacer clic en la campanita. */
    public function marcarAlertaLeida(Alerta $alerta)
    {
        $proveedorId = (int) session('proveedor_id');
        if (! $proveedorId
            || $alerta->destinatario_tipo !== 'proveedor'
            || (int) $alerta->destinatario_id !== $proveedorId) {
            return response()->json(['ok' => false, 'mensaje' => 'No autorizado'], 403);
        }

        if (! in_array($alerta->estatus, ['leida', 'accionada'], true)) {
            $alerta->update([
                'estatus' => 'leida',
                'leida_at' => now(),
            ]);
        }

        $sinLeer = Alerta::where('destinatario_tipo', 'proveedor')
            ->where('destinatario_id', $proveedorId)
            ->where('estatus', '!=', 'leida')
            ->where('estatus', '!=', 'accionada')
            ->count();

        return response()->json([
            'ok' => true,
            'sin_leer' => $sinLeer,
            'id' => $alerta->id,
        ]);
    }

    public function mostrarEncuesta()
    {
        return view('proveedores.encuesta');
    }

    public function guardarEncuestaProveedor(Request $request)
    {
        $request->validate([
            'calificacion' => 'required|integer|min:1|max:5',
            'comunicacion' => 'required|string',
            'pago_tiempo' => 'required|string',
            'proceso_oc' => 'required|string',
            'recomendaria' => 'required|string',
            'comentarios' => 'nullable|string|max:2000',
        ]);

        Encuesta::create([
            'codigo_cliente' => session('proveedor_codigo', 'PROV-'.session('proveedor_id')),
            'calificacion' => $request->input('calificacion'),
            'tiempo_entrega' => array_search($request->input('pago_tiempo'), ['siempre' => 1, 'casi_siempre' => 2, 'a_veces' => 3, 'nunca' => 4]) ?: 2,
            'calidad_producto' => array_search($request->input('comunicacion'), ['excelente' => 1, 'buena' => 2, 'regular' => 3, 'mala' => 4]) ?: 2,
            'comentarios' => $request->input('comentarios'),
        ]);

        return redirect()->route('proveedores.encuesta')->with('encuesta_guardada', true);
    }

    public function mostrarPerfil()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $contactos = $proveedor ? $proveedor->contactos()->orderBy('nombre')->get() : collect();
        $minContactos = 2;
        $faltanContactos = max(0, $minContactos - $contactos->count());
        $solicitudNombrePendiente = null;
        if ($proveedor) {
            try {
                $solicitudNombrePendiente = \App\Models\SolicitudModificacionDatos::where('proveedor_id', $proveedor->id)
                    ->where('estatus', 'pendiente')
                    ->latest()
                    ->first();
            } catch (\Throwable) {
                $solicitudNombrePendiente = null;
            }
        }

        return view('proveedores.perfil', compact(
            'proveedor',
            'contactos',
            'minContactos',
            'faltanContactos',
            'solicitudNombrePendiente'
        ));
    }

    public function actualizarPerfil(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->with('error', 'Proveedor no encontrado.');
        }

        if ($error = $this->errorSiIntentaCambiarTipoPersona($proveedor, $request->input('tipo_persona'))) {
            return back()->withErrors(['tipo_persona' => $error])->withInput();
        }

        if ($proveedor->tipoPersonaBloqueado()) {
            $request->merge(['tipo_persona' => $proveedor->tipoPersonaNormalizado()]);
        }

        // Nombre/razón social se cambia solo vía solicitud con documentos + IA.
        if (filled(trim((string) $proveedor->nombre))) {
            $enviado = trim((string) $request->input('nombre', ''));
            if ($enviado !== '' && mb_strtoupper($enviado) !== mb_strtoupper(trim((string) $proveedor->nombre))) {
                return back()->withErrors([
                    'nombre' => 'El nombre o razón social no se puede cambiar aquí. Usa “Solicitar cambio” y adjunta la documentación fiscal.',
                ])->withInput();
            }
            $request->merge(['nombre' => $proveedor->nombre]);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_persona' => 'required|in:Persona Física,Persona Moral',
            'telefono' => 'required|string|max:20',
            'correo' => 'required|email|max:255',
            'password' => 'nullable|min:8|confirmed',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'tipo_persona.required' => 'El tipo de persona es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no es válido.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $data = [
            'telefono' => $request->telefono,
            'correo' => $request->correo,
        ];
        if (! filled(trim((string) $proveedor->nombre))) {
            $data['nombre'] = $request->nombre;
        }
        if (! $proveedor->tipoPersonaBloqueado()) {
            $data['tipo_persona'] = $request->tipo_persona;
        }

        $proveedor->update($data);

        if ($request->filled('password')) {
            $proveedor->update(['password' => bcrypt($request->password)]);
        }

        session([
            'proveedor_nombre' => $proveedor->fresh()->nombre,
            'proveedor_correo' => $proveedor->fresh()->correo,
        ]);

        return redirect()->route('proveedores.perfil')->with('mensaje', 'Datos actualizados correctamente.');
    }

    public function mostrarSolicitudModificacionNombre(\App\Services\SolicitudModificacionDatosService $service)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return redirect()->route('proveedores.login');
        }

        $pendiente = \App\Models\SolicitudModificacionDatos::where('proveedor_id', $proveedor->id)
            ->where('estatus', 'pendiente')
            ->latest()
            ->first();
        $historial = \App\Models\SolicitudModificacionDatos::where('proveedor_id', $proveedor->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('proveedores.solicitud-modificacion-nombre', compact('proveedor', 'pendiente', 'historial'));
    }

    public function enviarSolicitudModificacionNombre(
        Request $request,
        \App\Services\SolicitudModificacionDatosService $service
    ) {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return redirect()->route('proveedores.login');
        }

        $esMoral = str_contains(mb_strtolower((string) $proveedor->tipo_persona), 'moral');

        $request->validate([
            'valor_propuesto' => 'required|string|min:3|max:255',
            'motivo' => 'nullable|string|max:1000',
            'cif_pdf' => 'required|file|mimes:pdf|max:10240',
            'acta_pdf' => ($esMoral ? 'required' : 'nullable').'|file|mimes:pdf|max:10240',
        ], [
            'valor_propuesto.required' => 'Indica el nuevo nombre o razón social.',
            'cif_pdf.required' => 'Debes subir la Constancia de Situación Fiscal actualizada.',
            'acta_pdf.required' => 'Como Persona Moral debes subir el Acta Constitutiva.',
            'cif_pdf.mimes' => 'La constancia debe ser PDF.',
            'acta_pdf.mimes' => 'El acta debe ser PDF.',
        ]);

        $result = $service->crearYValidar(
            $proveedor,
            (string) $request->input('valor_propuesto'),
            $request->input('motivo'),
            $request->file('cif_pdf'),
            $request->file('acta_pdf'),
        );

        if ($result['ok']) {
            session(['proveedor_nombre' => $proveedor->fresh()->nombre]);

            return redirect()
                ->route('proveedores.perfil')
                ->with('mensaje', $result['mensaje']);
        }

        return redirect()
            ->route('proveedores.perfil.solicitud-nombre')
            ->with('error', $result['mensaje'])
            ->withInput();
    }

    public function subirFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->with('error', 'Proveedor no encontrado.');
        }

        // Eliminar foto anterior si existe
        if ($proveedor->foto && \Storage::disk('public')->exists($proveedor->foto)) {
            \Storage::disk('public')->delete($proveedor->foto);
        }

        $path = $request->file('foto')->store('proveedores-fotos', 'public');
        $proveedor->update(['foto' => $path]);

        return back()->with('mensaje', 'Foto actualizada correctamente.');
    }

    // ── Contactos ──

    public function guardarContacto(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}\s\.,;#\-\/()&°\'\"]+$/u'],
            'rol' => 'required|string|max:100',
            'telefono' => ['required', 'regex:/^[0-9]{10}$/'],
            'correo' => 'required|email|max:255',
        ], [
            'nombre.required' => 'El nombre del contacto es obligatorio.',
            'nombre.regex' => 'El nombre no puede contener emojis.',
            'rol.required' => 'El rol es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe tener exactamente 10 dígitos.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no es válido.',
        ]);

        ContactoProveedor::create([
            'proveedor_id' => session('proveedor_id'),
            'nombre' => $request->nombre,
            'rol' => $request->rol,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
        ]);

        $total = ContactoProveedor::where('proveedor_id', session('proveedor_id'))->count();
        $faltan = max(0, 2 - $total);

        if ($faltan > 0) {
            return back()->with('mensaje', "Contacto agregado ({$total}/2). Falta".($faltan === 1 ? '' : 'n')." {$faltan} más. El mínimo son 2.");
        }

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor && ! $proveedor->activo) {
            return redirect()->route('proveedores.onboarding')
                ->with('mensaje', 'Ya tienes 2 contactos registrados. Continúa con el onboarding.');
        }

        return back()->with('mensaje', 'Contacto agregado correctamente. Ya cumpliste el mínimo de 2.');
    }

    public function eliminarContacto(Request $request, ContactoProveedor $contacto)
    {
        if ($contacto->proveedor_id != session('proveedor_id')) {
            abort(403);
        }

        // Verificar contraseña del proveedor
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $password = $request->input('password') ?? $request->query('password');

        if (! $proveedor || ! $password || ! Hash::check($password, $proveedor->password)) {
            return back()->with('error_contacto', 'Contraseña incorrecta. No se eliminó el contacto.');
        }

        $total = ContactoProveedor::where('proveedor_id', session('proveedor_id'))->count();
        if ($total <= 2) {
            $codigo = (string) ($proveedor->id_proveedor ?? '');
            $esAdminEspejo = str_starts_with($codigo, 'ADMIN-');
            if (! $esAdminEspejo && ($proveedor->tieneFormularioIdentificacion() || $proveedor->tieneFormularioDatosBancarios() || ! $proveedor->activo)) {
                return back()->with('error_contacto', 'Debes mantener mínimo 2 contactos. No puedes eliminar este contacto.');
            }
        }

        $contacto->delete();

        return back()->with('mensaje', 'Contacto eliminado.');
    }

    // ── Aviso de privacidad ──

    public function aceptarAvisoPrivacidad()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));

        if ($proveedor) {
            $proveedor->update([
                'aviso_privacidad_aceptado' => true,
                'aviso_privacidad_fecha' => now(),
            ]);
        }

        return back()->with('mensaje', 'Aviso de privacidad aceptado correctamente.');
    }

    /**
     * Listado de productos del proveedor autenticado (Mis productos).
     */
    public function mostrarMisProductos(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $nombreProveedor = $proveedor?->nombre ?: session('proveedor_nombre', '');
        $proveedorId = (int) session('proveedor_id');

        $base = Producto::query()->where(function ($q) use ($proveedorId, $nombreProveedor) {
            $tieneFiltro = false;

            if ($proveedorId > 0) {
                $q->whereHas('preciosProveedor', fn ($pq) => $pq->where('proveedor_id', $proveedorId));
                $tieneFiltro = true;
            }

            if ($nombreProveedor !== '') {
                $metodo = $tieneFiltro ? 'orWhere' : 'where';
                $q->{$metodo}(function ($q2) use ($nombreProveedor) {
                    $q2->where('proveedor_tipo', 'proveedor')
                        ->where('proveedor_nombre', $nombreProveedor);
                });
                $tieneFiltro = true;
            }

            if (! $tieneFiltro) {
                $q->whereRaw('1 = 0');
            }
        });

        $kpis = [
            'totales' => (clone $base)->count(),
            'activos' => (clone $base)->where('activo', true)->count(),
            'inactivos' => (clone $base)->where('activo', false)->count(),
            'sin_precio' => (clone $base)->where(function ($q) {
                $q->whereNull('precio')->orWhere('precio', '<=', 0);
            })->count(),
        ];

        $query = clone $base;

        $buscar = trim((string) $request->input('q', ''));
        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('tipo_producto', 'like', "%{$buscar}%")
                    ->orWhere('familia', 'like', "%{$buscar}%")
                    ->orWhere('categoria', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_producto', $request->input('tipo'));
        }

        if ($request->input('activo') === '1') {
            $query->where('activo', true);
        } elseif ($request->input('activo') === '0') {
            $query->where('activo', false);
        }

        $productos = $query->with(['preciosProveedor' => function ($q) use ($proveedorId) {
            if ($proveedorId > 0) {
                $q->where('proveedor_id', $proveedorId);
            }
        }])->orderByDesc('created_at')->paginate(30)->withQueryString();

        $tipos = (clone $base)->whereNotNull('tipo_producto')
            ->where('tipo_producto', '!=', '')
            ->distinct()
            ->orderBy('tipo_producto')
            ->pluck('tipo_producto');

        $filtros = [
            'q' => $buscar,
            'tipo' => $request->input('tipo', ''),
            'activo' => $request->input('activo', ''),
        ];

        return view('proveedores.mis-productos', compact(
            'proveedor',
            'productos',
            'kpis',
            'tipos',
            'filtros',
            'proveedorId'
        ));
    }

    /**
     * Exportar inventario a Excel XLSX con colores por familia.
     */
    public function exportarInventarioExcel()
    {
        $ddi = 90;
        $items = [
            ['SAL-001', 'Resina epóxica industrial', 'GUANGZHOU FASHI', 850, 260, 15, 0, 'KG', 85.00, 'Resinas'],
            ['SAL-003', 'Solvente grado técnico', 'INTERFLEX GROUP', 320, 180, 12, 0, 'LT', 42.50, 'Solventes'],
            ['SAL-005', 'Pigmento base agua', 'ALPHA AROMATICS', 90, 120, 20, 0, 'KG', 120.00, 'Pigmentos'],
            ['SAL-007', 'Catalizador rápido', 'QINGDAO GREENO', 45, 60, 18, 30, 'KG', 210.00, 'Aditivos'],
            ['SAL-009', 'Aditivo antioxidante', 'RECOCHEMIC INC', 0, 40, 25, 0, 'KG', 55.00, 'Aditivos'],
            ['SAL-011', 'Fibra de refuerzo', 'SALCOM INDUSTRIE', 900, 150, 10, 100, 'KG', 320.00, 'Refuerzos'],
            ['SAL-015', 'Adhesivo estructural', 'DCC GROUP COMP', 220, 90, 14, 0, 'KG', 180.00, 'Resinas'],
            ['SAL-018', 'Disolvente especial', 'HANGZHOU HUALIC', 15, 35, 22, 0, 'LT', 65.00, 'Solventes'],
            ['SAL-020', 'Sellador industrial', 'BOBSON HYGIENE', 0, 45, 16, 0, 'KG', 95.00, 'Selladores'],
            ['SAL-022', 'Recubrimiento base', 'NINGBO REVIEW T', 0, 30, 30, 0, 'KG', 78.00, 'Pigmentos'],
        ];

        $coloresGrupo = [
            'Resinas' => 'E3F2FD', 'Solventes' => 'FFF9C4', 'Pigmentos' => 'F3E5F5',
            'Aditivos' => 'E8F5E9', 'Refuerzos' => 'FFF3E0', 'Selladores' => 'E0F7FA',
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Máximos y Mínimos');

        // Headers
        $headers = ['GRUPO', 'CÓDIGO', 'PRODUCTO', 'PROVEEDOR', 'U.M.', 'PRECIO', 'EXISTENCIA', '%', 'CONSUMO/MES', 'CONSUMO ALTO', 'CONSUMO DIARIO', 'VENTAS/MES', 'STOCK MÍNIMO', 'STOCK MÁXIMO', 'DÍAS INVENTARIO', 'DÍAS ENTREGA', 'PEND. RECIBIR', 'CANTIDAD A PEDIR', 'COBERTURA', 'ESTADO'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $sheet->getStyle($col.'1')->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4A4A4A');
            $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Data rows with colors
        $rowNum = 2;
        foreach ($items as [$codigo, $nombre, $proveedor, $existencia, $consumoMes, $diasEntrega, $pendRecibir, $um, $precio, $grupo]) {
            $consumoDiario = round($consumoMes / 30, 2);
            $stockMinimo = round($consumoDiario * $diasEntrega);
            $stockMaximo = round($consumoDiario * $ddi);
            $diasInventario = $consumoDiario > 0 ? round($existencia / $consumoDiario) : 0;
            $totalAPedir = max(0, $stockMaximo - $existencia - $pendRecibir);
            $cobertura = $consumoDiario > 0 ? round($existencia / $consumoDiario) : 0;
            $ventasMes = $consumoMes * $precio;
            $porcentajeUso = $stockMaximo > 0 ? round(($existencia / $stockMaximo) * 100) : 0;
            $consumoAltoMes = round($consumoMes * 1.3);

            if ($existencia <= 0) {
                $estado = 'Agotado';
            } elseif ($existencia < $stockMinimo) {
                $estado = 'Bajo mínimo';
            } elseif ($existencia > $stockMaximo) {
                $estado = 'Sobre stock';
            } else {
                $estado = 'OK';
            }

            $data = [$grupo, $codigo, $nombre, $proveedor, $um, '$'.number_format($precio, 2), number_format($existencia), $porcentajeUso.'%', number_format($consumoMes), number_format($consumoAltoMes), number_format($consumoDiario, 2), '$'.number_format($ventasMes, 2), number_format($stockMinimo), number_format($stockMaximo), $diasInventario.' días', $diasEntrega.' días', number_format($pendRecibir), number_format($totalAPedir), $cobertura.' días', $estado];

            $col = 'A';
            foreach ($data as $val) {
                $sheet->setCellValue($col.$rowNum, $val);
                $col++;
            }

            // Aplicar color de fondo por familia
            $color = array_key_exists($grupo, $coloresGrupo) ? $coloresGrupo[$grupo] : 'FFFFFF';
            $sheet->getStyle("A{$rowNum}:T{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);

            $rowNum++;
        }

        // Auto-size
        foreach (range('A', 'T') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'inv_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'Stock_Maximos_Minimos_'.date('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Confirmar o rechazar cuentas bancarias duales (MXN + USD) desde onboarding.
     */
    public function confirmarCuentasDual(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'No se encontró tu cuenta.');
        }

        $accion = $request->input('accion'); // 'confirmar' o 'corregir'

        if ($accion === 'confirmar') {
            $datos = $proveedor->datos_identificacion ?? [];
            $datos['cuentas_dual_confirmadas'] = true;
            $proveedor->update(['datos_identificacion' => $datos]);

            // Crear alerta interna de confirmación
            try {
                Alerta::create([
                    'tipo' => 'confirmacion_cuentas_dual',
                    'modulo' => 'onboarding',
                    'destinatario_tipo' => 'proveedor',
                    'destinatario_id' => $proveedor->id,
                    'titulo' => 'Cuentas bancarias MXN y USD confirmadas',
                    'contenido' => 'Confirmaste tus 2 cuentas bancarias: MXN (' . ($datos['banco'] ?? '') . ') y USD (' . ($datos['banco_usd'] ?? '') . ').',
                    'datos' => [
                        'banco_mxn' => $datos['banco'] ?? null,
                        'clabe_mxn' => $datos['clabe'] ?? null,
                        'banco_usd' => $datos['banco_usd'] ?? null,
                        'clabe_usd' => $datos['clabe_usd'] ?? null,
                    ],
                    'estatus' => 'pendiente',
                    'nivel' => 'info',
                ]);
            } catch (\Exception $e) {
                // Tabla alertas puede no existir
            }

            return redirect()->route('proveedores.onboarding')
                ->with('mensaje', 'Cuentas bancarias confirmadas correctamente. Puedes continuar con el siguiente paso.');
        }

        // Si rechaza, redirigir al formulario para corregir
        return redirect()->route('proveedores.identificacion')
            ->with('error', 'Revisa y corrige tus datos bancarios (MXN y USD).');
    }

    /**
     * Confirmar cuenta(s) Wiese encontradas por RFC (paso 5 del onboarding).
     */
    public function confirmarCuentaWiese(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (!$proveedor) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'No se encontró tu cuenta.');
        }

        $accion = $request->input('accion'); // 'confirmar' o 'no_es_mia'

        if ($accion === 'confirmar') {
            $datos = $proveedor->datos_identificacion ?? [];
            $datos['cuenta_wiese_confirmada'] = true;
            $proveedor->update(['datos_identificacion' => $datos]);

            // Asignar el código del proveedor si viene de las cuentas encontradas
            $cuentas = $datos['cuentas_wiese'] ?? [];
            if (!empty($cuentas) && !empty($cuentas[0]['codigo'])) {
                $proveedor->update(['id_proveedor' => $cuentas[0]['codigo']]);
            }

            return redirect()->route('proveedores.onboarding')
                ->with('mensaje', 'Cuenta confirmada correctamente. Tu expediente está listo para revisión de Dirección.');
        }

        // Si dice que no es suya, marcar como "requiere atención" para que admin investigue
        $datos = $proveedor->datos_identificacion ?? [];
        $datos['cuenta_wiese_disputada'] = true;
        $proveedor->update(['datos_identificacion' => $datos]);

        return redirect()->route('proveedores.onboarding')
            ->with('error', 'Se notificará al equipo de Salcom para verificar tu cuenta. Mientras tanto, no puedes avanzar.');
    }

    public function mostrarIdentificacion()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));

        if ($proveedor && $proveedor->onboardingEdicionBloqueada() && $proveedor->tieneFormularioDatosBancarios()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Tu expediente está en revisión o ya fue aprobado. No puedes editar el formulario hasta que Dirección rechace o autorice cambios.');
        }

        // Si admin rechazó y limpió datos, no reutilizar sesión vieja
        if ($proveedor && empty($proveedor->datos_identificacion)) {
            session()->forget('identificacion_proveedor');
        }

        $identificacion = session('identificacion_proveedor')
            ?? ($proveedor !== null ? ($proveedor->datos_identificacion ?? []) : []);
        if (! is_array($identificacion)) {
            $identificacion = $proveedor !== null
                ? ($proveedor->datos_identificacion ?? [])
                : [];
        }

        // Precargar tipo de persona desde el registro de la cuenta
        if (empty($identificacion['tipo_persona']) && $proveedor !== null && $proveedor->tipo_persona) {
            $identificacion['tipo_persona'] = $this->normalizarTipoPersona($proveedor->tipo_persona);
        }

        if ($identificacion && ! session('identificacion_proveedor')) {
            session(['identificacion_proveedor' => $identificacion]);
        }

        $tieneDocsAprobados = false;
        if ($proveedor) {
            try {
                $tieneDocsAprobados = $proveedor->documentos()
                    ->where('estatus', 'aprobado')
                    ->exists();
            } catch (\Exception $e) {
                $tieneDocsAprobados = false;
            }
        }

        return view('proveedores.identificacion_proveedor', compact(
            'identificacion',
            'proveedor',
            'tieneDocsAprobados'
        ));
    }

    public function guardarIdentificacion(Request $request)
    {
        $proveedorPre = ProveedorUser::find(session('proveedor_id'));
        if ($proveedorPre && $proveedorPre->onboardingEdicionBloqueada() && $proveedorPre->tieneFormularioDatosBancarios()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'No puedes modificar el formulario: tu solicitud está en revisión o ya fue aprobada.');
        }

        if ($proveedorPre && ($error = $this->errorSiIntentaCambiarTipoPersona($proveedorPre, $request->input('tipo_persona')))) {
            return back()->withErrors(['tipo_persona' => $error])->withInput();
        }

        if ($proveedorPre && $proveedorPre->tipoPersonaBloqueado()) {
            $request->merge(['tipo_persona' => $proveedorPre->tipoPersonaNormalizado()]);
        }

        $esFisica = $request->input('tipo_persona') === 'Persona Física';
        $esMoral = $request->input('tipo_persona') === 'Persona Moral';

        $sinEmoji = 'regex:/^[\p{L}\p{N}\s\.,;#\-\/()&°\'\"]+$/u';
        $soloTexto = ['required', 'string', 'max:255', $sinEmoji];

        $rules = [
            'fecha' => 'required|date',
            'tipo_persona' => 'required|in:Persona Física,Persona Moral',
            'calle' => $soloTexto,
            'num_exterior' => ['required', 'string', 'max:50', $sinEmoji],
            'num_interior' => ['nullable', 'string', 'max:50', $sinEmoji],
            'colonia' => $soloTexto,
            'municipio' => $soloTexto,
            'estado' => $soloTexto,
            'ciudad' => $soloTexto,
            'pais' => $soloTexto,
            'cp' => ['required', 'regex:/^[0-9]{5}$/'],
            'telefono' => ['required', 'regex:/^[0-9]{10}$/'],
            'celular' => ['required', 'regex:/^[0-9]{10}$/'],
            'telefono2' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'extension' => ['nullable', 'regex:/^[0-9]{1,6}$/'],
            'correo' => 'required|email|max:255',
            'rfc' => ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/'],
            'clabe' => ['required', 'regex:/^[0-9]{18}$/'],
            'cuenta' => ['required', 'regex:/^[0-9]{5,20}$/'],
            'banco' => 'required|string|max:255|not_in:Otro',
            'docs' => [
                'required',
                'array',
                function (string $attribute, mixed $value, \Closure $fail) use ($esMoral) {
                    $docs = is_array($value) ? $value : [];
                    $requeridos = [
                        'id_rep_legal' => 'Identificación oficial del representante legal',
                        'id_contribuyente' => 'Identificación oficial del contribuyente',
                        'constancia_fiscal' => 'Constancia de Situación Fiscal',
                        'opinion_cumplimiento' => 'Opinión de Cumplimiento',
                        'caratula_banco' => 'Carátula de banco',
                    ];
                    if ($esMoral) {
                        $requeridos = ['acta_constitutiva' => 'Acta Constitutiva'] + $requeridos;
                    }
                    $faltan = [];
                    foreach ($requeridos as $clave => $etiqueta) {
                        if (! in_array($clave, $docs, true)) {
                            $faltan[] = $etiqueta;
                        }
                    }
                    if ($faltan !== []) {
                        $fail('Debes marcar todos los documentos obligatorios. Faltan: '.implode(', ', $faltan).'.');
                    }
                },
            ],
            'nombre_firma' => $soloTexto,
        ];

        // Si el proveedor opera en dólares, los campos USD son opcionales en este formulario
        // La confirmación se hace desde el onboarding
        $proveedorMoneda = $proveedorPre ? $proveedorPre->esMonedaDollar() : false;
        if ($proveedorMoneda) {
            $rules['clabe_usd'] = ['nullable', 'regex:/^[0-9]{18}$/'];
            $rules['cuenta_usd'] = ['nullable', 'regex:/^[0-9]{5,20}$/'];
            $rules['banco_usd'] = 'nullable|string|max:255';
        }

        if ($esFisica) {
            $rules['apellido_paterno'] = ['required', 'string', 'max:100', $sinEmoji];
            $rules['apellido_materno'] = ['required', 'string', 'max:100', $sinEmoji];
            $rules['nombres'] = ['required', 'string', 'max:150', $sinEmoji];
            $rules['razon_social'] = 'nullable|string|max:255';
        }

        if ($esMoral) {
            $rules['razon_social'] = ['required', 'string', 'max:255', $sinEmoji];
            $rules['apellido_paterno'] = 'nullable|string|max:100';
            $rules['apellido_materno'] = 'nullable|string|max:100';
            $rules['nombres'] = 'nullable|string|max:150';
        }

        $data = $request->validate($rules, [
            'required' => 'El campo :attribute es obligatorio.',
            'docs.required' => 'Debes marcar todos los documentos obligatorios.',
            'regex' => 'El campo :attribute tiene un formato inválido.',
            'telefono.regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos.',
            'celular.regex' => 'El celular debe tener exactamente 10 dígitos numéricos.',
            'telefono2.regex' => 'El teléfono 2 debe tener exactamente 10 dígitos numéricos.',
            'clabe.regex' => 'La CLABE debe tener exactamente 18 dígitos numéricos.',
            'clabe_usd.regex' => 'La CLABE USD debe tener exactamente 18 dígitos numéricos.',
            'clabe_usd.required' => 'La CLABE de la cuenta en dólares es obligatoria.',
            'cuenta.regex' => 'La cuenta solo acepta dígitos (5 a 20).',
            'cuenta_usd.regex' => 'La cuenta USD solo acepta dígitos (5 a 20).',
            'cuenta_usd.required' => 'El número de cuenta en dólares es obligatorio.',
            'banco_usd.required' => 'Selecciona el banco de la cuenta en dólares.',
            'banco_usd.not_in' => 'Selecciona un banco válido para la cuenta USD.',
            'cp.regex' => 'El C.P. debe tener exactamente 5 dígitos.',
            'sinEmoji' => 'No se permiten emojis ni caracteres especiales.',
            'banco.not_in' => 'Selecciona un banco de la lista.',
        ]);

        $nombreEsperado = $esMoral
            ? trim($data['razon_social'] ?? '')
            : trim(implode(' ', array_filter([
                $data['apellido_paterno'] ?? '',
                $data['apellido_materno'] ?? '',
                $data['nombres'] ?? '',
            ])));

        $payload = array_merge($data, [
            'tipo_clave' => $esMoral ? 'moral' : 'fisica',
            'nombre_esperado' => $nombreEsperado,
        ]);

        // Incluir datos USD si aplica (la confirmación se hace desde onboarding, no aquí)
        if ($proveedorMoneda && ! empty($data['clabe_usd'])) {
            $payload['clabe_usd'] = $data['clabe_usd'];
            $payload['cuenta_usd'] = $data['cuenta_usd'];
            $payload['banco_usd'] = $data['banco_usd'];
            // No marcar cuentas_dual_confirmadas aquí — se confirma en onboarding
            unset($payload['cuentas_dual_confirmadas']);
        }

        session(['identificacion_proveedor' => $payload]);

        // Guardar / actualizar solicitud de alta (una por proveedor)
        try {
            SolicitudAlta::updateOrCreate(
                ['proveedor_id' => session('proveedor_id')],
                [
                    'estatus' => 'pendiente',
                    'notas_admin' => null,
                    'tipo_persona' => $data['tipo_persona'],
                    'nombre_completo' => $nombreEsperado,
                    'razon_social' => $data['razon_social'] ?? null,
                    'apellido_paterno' => $data['apellido_paterno'] ?? null,
                    'apellido_materno' => $data['apellido_materno'] ?? null,
                    'nombres' => $data['nombres'] ?? null,
                    'calle' => $data['calle'] ?? null,
                    'num_exterior' => $data['num_exterior'] ?? null,
                    'num_interior' => $data['num_interior'] ?? null,
                    'colonia' => $data['colonia'] ?? null,
                    'municipio' => $data['municipio'] ?? null,
                    'estado' => $data['estado'] ?? null,
                    'ciudad' => $data['ciudad'] ?? null,
                    'pais' => $data['pais'] ?? null,
                    'cp' => $data['cp'] ?? null,
                    'telefono' => $data['telefono'] ?? null,
                    'celular' => $data['celular'] ?? null,
                    'telefono2' => $data['telefono2'] ?? null,
                    'extension' => $data['extension'] ?? null,
                    'correo' => $data['correo'] ?? null,
                    'clabe' => $data['clabe'] ?? null,
                    'cuenta' => $data['cuenta'] ?? null,
                    'banco' => $data['banco'] ?? null,
                    'docs_marcados' => $data['docs'] ?? [],
                    'nombre_firma' => $data['nombre_firma'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            // La tabla aún no existe — se creará al correr migraciones
        }

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $yaTenia = $proveedor && $proveedor->tieneFormularioDatosBancarios();
        $datosAnteriores = is_array($proveedor?->datos_identificacion) ? $proveedor->datos_identificacion : [];
        $cambioCritico = $yaTenia && $this->cambioCriticoIdentificacion($datosAnteriores, $payload);
        $docsInvalidated = false;

        if ($proveedor) {
            try {
                $updateDatos = [
                    'datos_identificacion' => $payload,
                    'nombre' => $nombreEsperado !== '' ? $nombreEsperado : $proveedor->nombre,
                ];
                if (! $proveedor->tipoPersonaBloqueado()) {
                    $updateDatos['tipo_persona'] = $data['tipo_persona'];
                }
                if (! empty($data['correo'])) {
                    $updateDatos['correo'] = $data['correo'];
                }
                if (! empty($data['telefono'])) {
                    $updateDatos['telefono'] = $data['telefono'];
                }
                if (Schema::hasColumn('proveedores_users', 'solicitud_alta_estatus')) {
                    $updateDatos['solicitud_alta_estatus'] = 'pendiente';
                }
                $proveedor->update($updateDatos);
                $proveedor->refresh();
                session([
                    'proveedor_nombre' => $proveedor->nombre,
                    'proveedor_correo' => $proveedor->correo,
                ]);
            } catch (\Exception $e) {
                // La columna datos_identificacion puede no existir aún en producción
                try {
                    $fallback = [
                        'nombre' => $nombreEsperado !== '' ? $nombreEsperado : $proveedor->nombre,
                    ];
                    if (! $proveedor->tipoPersonaBloqueado()) {
                        $fallback['tipo_persona'] = $data['tipo_persona'];
                    }
                    $proveedor->update($fallback);
                    session(['proveedor_nombre' => $proveedor->fresh()->nombre]);
                } catch (\Exception $e2) {
                    // ignore
                }
            }

            // Si cambió banco/CLABE/nombre/tipo y ya tenía docs aprobados → invalidar para forzar revalidación
            if ($cambioCritico) {
                $docsInvalidated = $this->invalidarDocumentosTrasCambioIdentificacion($proveedor->id);
            }
        }

        $mensaje = $yaTenia
            ? 'Datos bancarios actualizados correctamente.'
            : 'Formulario de datos bancarios guardado. Ya puedes continuar con la validación de documentos.';

        if ($docsInvalidated) {
            $mensaje .= ' Como cambiaste datos críticos (banco, CLABE, nombre o tipo de persona), los documentos previamente validados quedaron pendientes: vuelve a Validar documentos.';
        }

        // Si tiene cuentas duales, avisar que debe confirmar en onboarding
        if ($proveedorMoneda && ! empty($payload['clabe_usd'])) {
            $mensaje .= ' Regresa al onboarding para confirmar tus cuentas bancarias (MXN y USD) antes de continuar.';
        }

        return redirect()->route('proveedores.onboarding')->with('mensaje', $mensaje);
    }

    public function mostrarValidacionFiscal()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor && ! $proveedor->tieneFormularioDatosBancarios()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Primero completa el formulario de datos bancarios.');
        }
        // Bloquear si tiene cuentas duales sin confirmar
        if ($proveedor && $proveedor->esMonedaDollar()) {
            $di = $proveedor->datos_identificacion ?? [];
            if (! empty($di['clabe']) && ! empty($di['clabe_usd']) && empty($di['cuentas_dual_confirmadas'])) {
                return redirect()->route('proveedores.onboarding')
                    ->with('error', 'Confirma tus cuentas bancarias (MXN y USD) en el onboarding antes de continuar.');
            }
        }
        if ($proveedor && $proveedor->onboardingEdicionBloqueada() && $proveedor->documentosFiscalesCompletos()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Tu expediente está en revisión o ya fue aprobado. No puedes volver a subir documentos hasta un rechazo de Dirección.');
        }

        $identificacion = session('identificacion_proveedor');
        if (! $identificacion) {
            $identificacion = $proveedor?->datos_identificacion;
            if ($identificacion) {
                session(['identificacion_proveedor' => $identificacion]);
            }
        }

        $solicitudId = null;
        $onboardingBloqueado = $proveedor?->onboardingEdicionBloqueada() ?? false;

        return view('APIS.empresa', compact('identificacion', 'solicitudId', 'onboardingBloqueado'));
    }

    public function mostrarAdjuntoDocumentos()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor && ! $proveedor->tieneFormularioDatosBancarios()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Primero completa el formulario de datos bancarios.');
        }
        if ($proveedor && $proveedor->onboardingEdicionBloqueada()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Tu expediente está en revisión. No puedes subir documentos nuevos.');
        }

        $documentos = $proveedor ? $proveedor->documentos()->orderByDesc('created_at')->get() : collect();

        $tiposLabel = [
            'cif' => 'CIF / Constancia fiscal',
            'opinion' => 'Opinión SAT',
            'acta' => 'Acta constitutiva',
            'rep_legal' => 'INE Rep. legal',
            'contribuyente' => 'INE Contribuyente',
            'caratula_banco' => 'Carátula bancaria',
        ];

        return view('proveedores.adjunto-documentos', compact('documentos', 'tiposLabel'));
    }

    public function subirAdjuntoDocumentos(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor && ! $proveedor->tieneFormularioDatosBancarios()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Primero completa el formulario de datos bancarios.');
        }
        if ($proveedor && $proveedor->onboardingEdicionBloqueada()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Tu expediente está en revisión. No puedes subir documentos nuevos.');
        }

        $tipos = ['cif', 'opinion', 'acta', 'rep_legal', 'contribuyente', 'caratula_banco'];
        $subidos = 0;

        foreach ($tipos as $tipo) {
            if ($request->hasFile($tipo)) {
                $request->validate([$tipo => 'mimes:pdf|max:10240']);

                $archivo = $request->file($tipo);
                $ruta = $archivo->store("expediente_fiscal/{$tipo}", 'public');

                DocumentoProveedor::updateOrCreate(
                    ['proveedor_id' => session('proveedor_id'), 'tipo' => $tipo],
                    ['archivo' => $ruta, 'estatus' => 'pendiente', 'notas_revision' => null, 'revisado_at' => null]
                );
                $subidos++;
            }
        }

        if ($subidos === 0) {
            return back()->withErrors(['general' => 'Selecciona al menos un documento PDF para subir.']);
        }

        return back()->with('adj_exito', "Se subieron {$subidos} documento(s) correctamente. El equipo de Salcom los revisará.");
    }

    /**
     * Alta de facturas — formulario con historial reciente (solo las ya subidas).
     */
    public function mostrarAltaFacturas()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $codigo = $proveedor?->id_proveedor ?: session('proveedor_codigo');

        $facturas = Factura::query()
            ->when($codigo, fn ($q) => $q->where('codigo_proveedor', $codigo))
            ->where('estatus', '!=', 'rechazada')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $rfcProveedor = $this->rfcProveedorSesion($proveedor);
        $pendiente = $this->fiscalPendienteVigente($proveedor);

        $res = session()->pull('fiscal_ui_resultado');
        if (! is_array($res)) {
            $res = session('fiscal_resultado');
        }

        // Los temporales solo deben verse en el redirect inmediato tras Validar
        // (éxito o error). Un GET fresco (F5 o volver a entrar) inicia vacío.
        // No depende del flash de Laravel: el polling de alertas lo consume.
        $esRetornoDeValidacion = (bool) session()->pull('fiscal_ui_keep')
            || is_array($res)
            || session()->has('errors');
        if (is_array($pendiente) && ! $esRetornoDeValidacion) {
            $this->limpiarFiscalPendiente();
            $pendiente = null;
        }

        $puedeSubir = is_array($pendiente) && ! empty($pendiente['aprobado']);
        $tieneArchivosPendientes = is_array($pendiente)
            && ! empty($pendiente['path_pdf'])
            && ! empty($pendiente['path_xml']);
        $mesEnCurso = now()->locale('es')->translatedFormat('F Y');

        return view('proveedores.fiscal', compact(
            'facturas',
            'rfcProveedor',
            'proveedor',
            'puedeSubir',
            'tieneArchivosPendientes',
            'pendiente',
            'mesEnCurso',
            'res'
        ));
    }

    /**
     * Paso 1: validar PDF + XML (sin registrar). Guarda temporales en sesión
     * también si falla, para que sigan visibles tras Validar y se puedan reemplazar.
     */
    public function validarAltaFactura(Request $request, AltaFacturaValidationService $validator)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->withErrors(['archivo' => 'Sesión de proveedor no válida. Vuelve a iniciar sesión.']);
        }

        $prev = $this->fiscalPendienteVigente($proveedor);
        $tienePrev = is_array($prev);

        try {
            $request->validate([
                'archivo' => ($tienePrev ? 'nullable' : 'required').'|file|extensions:pdf|max:10240',
                'archivo_xml' => ($tienePrev ? 'nullable' : 'required').'|file|extensions:xml|max:5120',
                'archivo_oc' => 'nullable|file|extensions:pdf|max:10240',
                'es_fletera' => 'nullable|in:0,1',
            ], [
                'archivo.required' => 'La factura en PDF es obligatoria.',
                'archivo.extensions' => 'La factura debe ser un archivo PDF.',
                'archivo_xml.required' => 'El XML de la factura es obligatorio.',
                'archivo_xml.extensions' => 'El archivo CFDI debe ser un XML válido (.xml).',
                'archivo_oc.extensions' => 'La orden de compra debe ser un archivo PDF.',
            ]);
        } catch (ValidationException $e) {
            $this->conservarUiFiscalTrasRedirect();
            throw $e;
        }

        $disk = Storage::disk('local');
        $pdfUpload = $request->file('archivo');
        $xmlUpload = $request->file('archivo_xml');
        $ocUpload = $request->file('archivo_oc');

        if (! $pdfUpload && ! $tienePrev) {
            $this->conservarUiFiscalTrasRedirect();

            return back()->withErrors(['archivo' => 'La factura en PDF es obligatoria.']);
        }
        if (! $xmlUpload && ! $tienePrev) {
            $this->conservarUiFiscalTrasRedirect();

            return back()->withErrors(['archivo_xml' => 'El XML de la factura es obligatorio.']);
        }

        // Contenido a validar: nuevo upload o temporal previo
        if ($pdfUpload) {
            $pdfContent = file_get_contents($pdfUpload->getRealPath());
            $nombrePdf = $pdfUpload->getClientOriginalName();
        } else {
            $pdfContent = $disk->get($prev['path_pdf']);
            $nombrePdf = $prev['nombre_pdf'] ?? 'factura.pdf';
        }

        if ($xmlUpload) {
            $xmlContent = file_get_contents($xmlUpload->getRealPath());
            $nombreXml = $xmlUpload->getClientOriginalName();
            $xmlSize = $xmlUpload->getSize();
        } else {
            $xmlContent = $disk->get($prev['path_xml']);
            $nombreXml = $prev['nombre_xml'] ?? 'factura.xml';
            $xmlSize = strlen($xmlContent);
        }

        if ($ocUpload) {
            $ocBinary = file_get_contents($ocUpload->getRealPath());
            $nombreOc = $ocUpload->getClientOriginalName();
        } elseif ($tienePrev && ! empty($prev['path_oc']) && $disk->exists($prev['path_oc'])) {
            $ocBinary = $disk->get($prev['path_oc']);
            $nombreOc = $prev['nombre_oc'] ?? 'oc.pdf';
        } else {
            $ocBinary = null;
            $nombreOc = null;
        }

        // Persistir temporales antes de validar para que no “desaparezcan” al recargar
        $this->guardarFiscalTemporal(
            $proveedor,
            (string) $pdfContent,
            (string) $xmlContent,
            $ocBinary === null ? null : (string) $ocBinary,
            $nombrePdf,
            $nombreXml,
            $nombreOc,
        );

        if (! str_starts_with((string) $pdfContent, '%PDF')) {
            return $this->redirectConResultadoFiscal([
                'aprobado' => false,
                'mensaje' => 'El archivo PDF no es válido.',
                'errores' => ['El archivo de factura no es un PDF real.'],
                'checklist' => [],
                'archivos_retenidos' => true,
            ]);
        }

        if ($xmlSize < 1 || $xmlContent === false || trim((string) $xmlContent) === '' || ! str_contains((string) $xmlContent, '<')) {
            return $this->redirectConResultadoFiscal([
                'aprobado' => false,
                'mensaje' => 'La factura no pasó la validación.',
                'errores' => [
                    'El archivo XML está vacío o no contiene un CFDI legible. Descarga de nuevo el XML timbrado.',
                ],
                'checklist' => [],
                'archivos_retenidos' => true,
            ]);
        }

        $esFletera = $request->input('es_fletera') === '1';
        $rfcProveedor = $this->rfcProveedorSesion($proveedor);
        $resultado = $validator->validar((string) $xmlContent, $esFletera, $rfcProveedor, (string) $pdfContent, $ocBinary);
        $esFleteraEfectivo = (bool) ($resultado['datos']['es_fletera'] ?? $esFletera);

        $this->actualizarFiscalPendiente([
            'aprobado' => (bool) ($resultado['aprobado'] ?? false),
            'es_fletera' => $esFleteraEfectivo,
            'resultado' => $resultado,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        if (! $resultado['aprobado']) {
            return $this->redirectConResultadoFiscal([
                'aprobado' => false,
                'estatus' => $resultado['estatus'] ?? 'rechazada',
                'mensaje' => ($resultado['mensaje'] ?? 'La factura no pasó la validación. Corrige los errores y vuelve a validar.')
                    .' Los archivos se conservaron: puedes corregir y pulsar «Validar» de nuevo sin re-adjuntarlos.',
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias'],
                'checklist' => $resultado['checklist'],
                'datos' => $resultado['datos'],
                'archivos_retenidos' => true,
            ]);
        }

        return $this->redirectConResultadoFiscal([
            'aprobado' => true,
            'estatus' => $resultado['estatus'] ?? 'aprobada',
            'mensaje' => ($resultado['mensaje'] ?? 'Validación correcta.').'',
            'errores' => [],
            'advertencias' => $resultado['advertencias'] ?? [],
            'checklist' => $resultado['checklist'] ?? [],
            'datos' => $resultado['datos'] ?? [],
            'listo_para_subir' => true,
            'archivos_retenidos' => true,
        ]);
    }

    /**
     * Paso 2: registrar factura ya validada (archivos temporales de sesión).
     */
    public function altaFactura(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->withErrors(['archivo' => 'Sesión de proveedor no válida. Vuelve a iniciar sesión.']);
        }

        $pendiente = $this->fiscalPendienteVigente($proveedor);
        if (! is_array($pendiente) || empty($pendiente['aprobado'])) {
            return back()->withErrors([
                'archivo' => 'Primero valida la factura. Adjunta PDF + XML y pulsa «Validar».',
            ]);
        }

        $plazos = config('facturas.plazos_dias', [30, 45, 60, 90, 120, 150, 360]);
        $maxDias = (int) config('facturas.plazos_dias_max', 3650);
        $opcionPlazo = $request->input('dias_plazo');

        try {
            if ($opcionPlazo === 'otro') {
                $request->validate([
                    'dias_plazo_otro' => ['required', 'integer', 'min:1', 'max:'.$maxDias],
                ], [
                    'dias_plazo_otro.required' => 'Escribe la cantidad de días del plazo.',
                    'dias_plazo_otro.integer' => 'La cantidad de días debe ser un número entero.',
                    'dias_plazo_otro.min' => 'El plazo debe ser de al menos 1 día.',
                    'dias_plazo_otro.max' => 'El plazo no puede ser mayor a '.$maxDias.' días.',
                ]);
                $diasPlazo = (int) $request->input('dias_plazo_otro');
            } else {
                $request->validate([
                    'dias_plazo' => ['required', Rule::in($plazos)],
                ], [
                    'dias_plazo.required' => 'Selecciona los días de plazo antes de subir.',
                    'dias_plazo.in' => 'Selecciona un plazo de la lista o elige «Otro».',
                ]);
                $diasPlazo = (int) $opcionPlazo;
            }
        } catch (ValidationException $e) {
            $this->conservarUiFiscalTrasRedirect();
            throw $e;
        }

        $resultado = $pendiente['resultado'] ?? [];

        try {
            $this->registrarFacturaDesdePendiente($proveedor, $pendiente, $diasPlazo);
        } catch (\InvalidArgumentException $e) {
            $this->limpiarFiscalPendiente();

            return $this->redirectConResultadoFiscal([
                'aprobado' => false,
                'estatus' => 'rechazada',
                'mensaje' => 'La factura no se pudo registrar.',
                'errores' => [$e->getMessage()],
                'advertencias' => $resultado['advertencias'] ?? [],
                'checklist' => $resultado['checklist'] ?? [],
                'datos' => $resultado['datos'] ?? [],
            ]);
        } catch (\Throwable $e) {
            $this->limpiarFiscalPendiente();
            Log::error('[AltaFactura] Error al registrar: '.$e->getMessage());

            return back()->withErrors(['archivo' => 'Error al guardar la factura. Intenta de nuevo.']);
        }

        $this->limpiarFiscalPendiente();

        $estatus = $resultado['estatus'] ?? 'aprobada';
        $mensaje = "Factura registrada correctamente a {$diasPlazo} días. Queda pendiente de revisión contable.";
        $datos = $resultado['datos'] ?? [];
        $datos['dias_plazo'] = $diasPlazo;

        return $this->redirectConResultadoFiscal([
            'aprobado' => true,
            'estatus' => $estatus,
            'mensaje' => $mensaje,
            'errores' => [],
            'advertencias' => $resultado['advertencias'] ?? [],
            'checklist' => $resultado['checklist'] ?? [],
            'datos' => $datos,
            'registrada' => true,
        ]);
    }

    /**
     * Persiste factura en estatus pendiente desde archivos temporales + resultado de validación.
     *
     * @param  array<string, mixed>  $pendiente
     */
    private function registrarFacturaDesdePendiente(ProveedorUser $proveedor, array $pendiente, int $diasPlazo): Factura
    {
        $resultado = $pendiente['resultado'] ?? null;
        if (! is_array($resultado) || empty($resultado['aprobado'])) {
            throw new \InvalidArgumentException('La validación ya no es válida. Vuelve a validar.');
        }

        $datos = $resultado['datos'] ?? [];
        $uuid = $datos['uuid'] ?? null;

        if ($uuid && Factura::where('uuid_cfdi', $uuid)->exists()) {
            throw new \InvalidArgumentException('Esta factura (UUID) ya fue registrada anteriormente.');
        }

        $disk = Storage::disk('local');
        if (empty($pendiente['path_pdf']) || empty($pendiente['path_xml'])
            || ! $disk->exists($pendiente['path_pdf']) || ! $disk->exists($pendiente['path_xml'])) {
            throw new \InvalidArgumentException('Los archivos temporales expiraron. Vuelve a validar.');
        }

        $dir = 'facturas-proveedor/'.$proveedor->id;
        $finalPdf = $dir.'/'.uniqid('pdf_', true).'.pdf';
        $finalXml = $dir.'/'.uniqid('xml_', true).'.xml';
        Storage::disk('public')->put($finalPdf, $disk->get($pendiente['path_pdf']));
        Storage::disk('public')->put($finalXml, $disk->get($pendiente['path_xml']));

        $finalOc = null;
        if (! empty($pendiente['path_oc']) && $disk->exists($pendiente['path_oc'])) {
            $finalOc = $dir.'/'.uniqid('oc_', true).'.pdf';
            Storage::disk('public')->put($finalOc, $disk->get($pendiente['path_oc']));
        }

        $folioFactura = trim(($datos['serie'] ?? '').($datos['folio'] ?? ''));
        if ($folioFactura === '') {
            $folioFactura = $uuid ?: ('TMP-'.uniqid());
        }
        $folioCfdi = $folioFactura;
        if (Factura::where('folio_cfdi', $folioCfdi)->exists()) {
            $folioCfdi = $folioCfdi.'-'.substr(uniqid(), -4);
        }
        $maxDias = (int) config('facturas.plazos_dias_max', 3650);
        if ($diasPlazo < 1 || $diasPlazo > $maxDias) {
            throw new \InvalidArgumentException('El plazo de días no es válido.');
        }
        $dias = $diasPlazo;
        $codigoProv = $proveedor->id_proveedor ?: session('proveedor_codigo') ?: ('P'.$proveedor->id);
        $esFletera = (bool) ($pendiente['es_fletera'] ?? false);
        $total = (float) (($datos['total'] ?? 0) ?: (($datos['subtotal'] ?? 0) + ($datos['iva'] ?? 0)));

        $factura = Factura::create([
            'folio_cfdi' => $folioCfdi,
            'uuid_cfdi' => $uuid,
            'codigo_proveedor' => $codigoProv,
            'regimen_fiscal' => $datos['regimen_fiscal'] ?? null,
            'es_fletera' => $esFletera,
            'monto' => $datos['subtotal'] ?? 0,
            'monto_iva' => $datos['iva'] ?? 0,
            'retencion_iva' => $datos['retencion_iva'] ?? 0,
            'retencion_isr' => $datos['retencion_isr'] ?? 0,
            'total' => $total,
            'estatus' => 'pendiente',
            'fecha_vencimiento' => now()->addDays($dias)->toDateString(),
            'dias_plazo' => $dias,
            'archivo_pdf' => $finalPdf,
            'archivo_xml' => $finalXml,
            'archivo_oc' => $finalOc,
            'notas' => null,
            'validacion_detalle' => [
                'estatus' => $resultado['estatus'] ?? null,
                'checklist' => $resultado['checklist'] ?? [],
                'advertencias' => $resultado['advertencias'] ?? [],
                'retencion_esperada' => $datos['retencion_esperada'] ?? null,
                'rfc_emisor' => $datos['rfc_emisor'] ?? null,
                'regimen_nombre' => $datos['regimen_nombre'] ?? null,
                'serie' => $datos['serie'] ?? null,
                'folio' => $datos['folio'] ?? null,
                'forma_pago' => $datos['forma_pago'] ?? null,
                'metodo_pago' => $datos['metodo_pago'] ?? null,
                'uso_cfdi' => $datos['uso_cfdi'] ?? null,
                'producto' => $datos['producto'] ?? null,
                'cfdi_relacionados' => $datos['cfdi_relacionados'] ?? [],
                'tipo_relacion' => $datos['tipo_relacion'] ?? null,
                'pdf_cruce' => $datos['pdf_cruce'] ?? null,
                'oc_cruce' => $datos['oc_cruce'] ?? null,
                'naturaleza' => $pendiente['naturaleza'] ?? null,
                'tipo_producto' => $pendiente['tipo_producto'] ?? null,
                'es_me_mp' => (bool) ($pendiente['es_me_mp'] ?? false),
                'dias_plazo' => $dias,
                'validado_at' => now()->toIso8601String(),
            ],
        ]);

        try {
            $nombreProv = $proveedor->nombre ?: $codigoProv;
            $totalFmt = number_format($total, 2);
            app(AlertEngineService::class)->crearAlerta([
                'tipo' => 'factura_pago_pendiente',
                'modulo' => 'pagos',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 1,
                'titulo' => "Nueva factura de {$nombreProv}",
                'contenido' => "Folio {$folioCfdi} · \${$totalFmt} · pendiente de pago",
                'datos' => [
                    'codigo_proveedor' => (string) $codigoProv,
                    'folio_cfdi' => $folioCfdi,
                ],
                'nivel' => 'info',
            ]);
        } catch (\Throwable $e) {
            // No bloquear el alta si falla la notificación
        }

        // Auto-ligar anticipos timbrados que esta factura aplique (CfdiRelacionados 07),
        // con respaldo por número de OC/folio_general.
        try {
            $this->autoLigarAnticipos($factura, $datos, (string) $codigoProv);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[AutoAnticipo] No se pudo auto-ligar: '.$e->getMessage());
        }

        return $factura;
    }

    /**
     * Liga automáticamente anticipos del proveedor a la factura recién creada.
     * Prioridad: (1) por UUID relacionado del CFDI (TipoRelacion 07);
     * (2) respaldo por número de OC (folio_general del anticipo) si aparece en la factura.
     * Al ligar, descuenta el monto del anticipo del saldo de la factura.
     *
     * @param  array<string, mixed>  $datos
     */
    private function autoLigarAnticipos(Factura $factura, array $datos, string $codigoProv): void
    {
        // 1) Match por UUID de CfdiRelacionados (solo si TipoRelacion es 07 = aplicación de anticipo).
        $tipoRelacion = (string) ($datos['tipo_relacion'] ?? '');
        $uuidsRel = array_values(array_filter(array_map(
            fn ($u) => strtoupper(trim((string) $u)),
            (array) ($datos['cfdi_relacionados'] ?? [])
        )));

        $anticipos = collect();

        if ($tipoRelacion === '07' && ! empty($uuidsRel)) {
            $anticipos = \App\Models\AnticipoProveedor::query()
                ->where('codigo_proveedor', $codigoProv)
                ->where('estatus', 'pagado')
                ->whereNotNull('uuid_cfdi')
                ->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(uuid_cfdi)'), $uuidsRel)
                ->get();
        }

        // 2) Respaldo por número de OC: si el folio de la factura o su serie/folio
        //    contienen el folio_general del anticipo. Solo si no hubo match por UUID.
        if ($anticipos->isEmpty()) {
            $refFactura = strtoupper(trim(
                (string) ($factura->folio_cfdi ?? '').' '.
                (string) ($datos['serie'] ?? '').' '.
                (string) ($datos['folio'] ?? '')
            ));

            if ($refFactura !== '') {
                $candidatos = \App\Models\AnticipoProveedor::query()
                    ->where('codigo_proveedor', $codigoProv)
                    ->where('estatus', 'pagado')
                    ->whereNotNull('folio_general')
                    ->get();

                $anticipos = $candidatos->filter(function ($ant) use ($refFactura) {
                    $fg = strtoupper(trim((string) $ant->folio_general));
                    return $fg !== '' && str_contains($refFactura, $fg);
                })->values();
            }
        }

        if ($anticipos->isEmpty()) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($anticipos, $factura, $tipoRelacion) {
            foreach ($anticipos as $anticipo) {
                $factura->refresh();
                $saldoFactura = round((float) $factura->total - (float) $factura->monto_pagado, 2);
                if ($saldoFactura <= 0) {
                    break; // ya no queda saldo por cubrir
                }

                // No aplicar más del saldo disponible de la factura.
                $montoAplicar = min(round((float) $anticipo->total_banco, 2), $saldoFactura);
                if ($montoAplicar <= 0) {
                    continue;
                }

                $nuevoPagado = round((float) $factura->monto_pagado + $montoAplicar, 2);
                $factura->monto_pagado = $nuevoPagado;
                if ($nuevoPagado >= (float) $factura->total) {
                    $factura->estatus = 'pagada';
                }
                $factura->save();

                $anticipo->update([
                    'estatus' => 'aplicado',
                    'factura_id' => $factura->id,
                    'monto_aplicado' => $montoAplicar,
                    'datos' => array_merge($anticipo->datos ?? [], [
                        'aplicado_auto' => true,
                        'aplicado_via' => $tipoRelacion === '07' ? 'uuid_cfdi' : 'folio_oc',
                        'aplicado_at' => now()->toDateTimeString(),
                        'factura_folio' => $factura->folio_cfdi,
                    ]),
                ]);

                // Alerta para admin: anticipo ligado automáticamente.
                try {
                    Alerta::create([
                        'tipo' => 'anticipo_autoligado',
                        'modulo' => 'anticipos',
                        'destinatario_tipo' => 'admin',
                        'destinatario_id' => 1,
                        'titulo' => 'Anticipo aplicado automáticamente',
                        'contenido' => 'El anticipo '.($anticipo->folio_general ?: $anticipo->uuid_cfdi).' por $'.number_format($montoAplicar, 2).' se aplicó a la factura '.($factura->folio_cfdi ?: ('FAC-'.$factura->id)).'.',
                        'nivel' => 'info',
                        'estatus' => 'nueva',
                        'datos' => [
                            'anticipo_id' => $anticipo->id,
                            'factura_id' => $factura->id,
                            'monto' => $montoAplicar,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    // no bloquear
                }
            }
        });
    }

    private function conservarUiFiscalTrasRedirect(): void
    {
        session(['fiscal_ui_keep' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function redirectConResultadoFiscal(array $payload)
    {
        $this->conservarUiFiscalTrasRedirect();
        session(['fiscal_ui_resultado' => $payload]);

        return back()->with('fiscal_resultado', $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fiscalPendienteVigente(?ProveedorUser $proveedor): ?array
    {
        if (! $proveedor) {
            return null;
        }

        $pendiente = session('fiscal_pendiente');
        if (! is_array($pendiente)
            || empty($pendiente['token'])
            || empty($pendiente['path_pdf'])
            || empty($pendiente['path_xml'])
            || ($pendiente['proveedor_id'] ?? null) != $proveedor->id
            || ($pendiente['expires_at'] ?? 0) < now()->timestamp
        ) {
            return null;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($pendiente['path_pdf']) || ! $disk->exists($pendiente['path_xml'])) {
            return null;
        }

        return $pendiente;
    }

    /**
     * Guarda PDF/XML/(OC) en disco local y sesión (aprobado=false hasta validar).
     */
    private function guardarFiscalTemporal(
        ProveedorUser $proveedor,
        string $pdfContent,
        string $xmlContent,
        ?string $ocBinary,
        string $nombrePdf,
        string $nombreXml,
        ?string $nombreOc,
    ): void {
        $this->limpiarFiscalPendiente();

        $token = bin2hex(random_bytes(16));
        $tempDir = 'temp-fiscal/'.$proveedor->id.'/'.$token;
        $disk = Storage::disk('local');

        $pathPdf = $tempDir.'/factura.pdf';
        $pathXml = $tempDir.'/factura.xml';
        $disk->put($pathPdf, $pdfContent);
        $disk->put($pathXml, $xmlContent);

        $pathOc = null;
        if ($ocBinary !== null && $ocBinary !== '') {
            $pathOc = $tempDir.'/oc.pdf';
            $disk->put($pathOc, $ocBinary);
        }

        session(['fiscal_pendiente' => [
            'token' => $token,
            'proveedor_id' => $proveedor->id,
            'aprobado' => false,
            'path_pdf' => $pathPdf,
            'path_xml' => $pathXml,
            'path_oc' => $pathOc,
            'nombre_pdf' => $nombrePdf,
            'nombre_xml' => $nombreXml,
            'nombre_oc' => $nombreOc,
            'es_fletera' => false,
            'es_me_mp' => false,
            'requiere_oc' => false,
            'naturaleza' => null,
            'tipo_producto' => null,
            'resultado' => null,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]]);
    }

    /**
     * @param  array<string, mixed>  $campos
     */
    private function actualizarFiscalPendiente(array $campos): void
    {
        $pendiente = session('fiscal_pendiente');
        if (! is_array($pendiente)) {
            return;
        }

        session(['fiscal_pendiente' => array_merge($pendiente, $campos)]);
    }

    private function limpiarFiscalPendiente(): void
    {
        $pendiente = session('fiscal_pendiente');
        if (is_array($pendiente) && ! empty($pendiente['path_pdf'])) {
            $dir = dirname($pendiente['path_pdf']);
            Storage::disk('local')->deleteDirectory($dir);
        }
        session()->forget('fiscal_pendiente');
    }

    /**
     * RFC del proveedor desde identificación o input.
     */
    private function rfcProveedorSesion(?ProveedorUser $proveedor): ?string
    {
        if (! $proveedor) {
            return session('proveedor_rfc') ?: null;
        }

        if (! empty($proveedor->rfc)) {
            return strtoupper(trim((string) $proveedor->rfc));
        }

        $datos = $proveedor->datos_identificacion ?? [];
        $rfc = $datos['rfc'] ?? $datos['RFC'] ?? null;
        if ($rfc) {
            return strtoupper(trim((string) $rfc));
        }

        return session('proveedor_rfc') ?: null;
    }

    /**
     * Subir documento fiscal de onboarding (CIF, opinión, etc.).
     */
    public function subirDocumentoFiscal(Request $request)
    {
        $provId = session('proveedor_id');
        $proveedorLock = ProveedorUser::find($provId);
        if ($proveedorLock && $proveedorLock->onboardingEdicionBloqueada()) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Tu expediente está en revisión o aprobado. No puedes subir documentos hasta un rechazo de Dirección.',
            ], 423);
        }

        $request->validate([
            'tipo_documento' => 'required|string',
            'archivo' => 'required|file|mimes:pdf|max:10240',
        ]);

        $tipo = $request->input('tipo_documento');
        $rfc = $request->input('rfc', '');
        $notas = $request->input('notas', '');

        $path = $request->file('archivo')->store('documentos-fiscales', 'public');

        $errores = [];
        $archivo = $request->file('archivo');

        if ($archivo->getSize() < 1024) {
            $errores[] = 'El archivo parece estar vacío o corrupto.';
        }

        $contenido = file_get_contents($archivo->getRealPath());
        if (! str_starts_with($contenido, '%PDF')) {
            $errores[] = 'El archivo no es un PDF válido.';
        }

        if ($rfc && ! preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i', $rfc)) {
            $errores[] = "El RFC '{$rfc}' no tiene un formato válido. Debe ser 12 o 13 caracteres (ej: ABC123456XY7).";
        }

        $tipoLabel = match ($tipo) {
            'cif' => 'Constancia de Situación Fiscal',
            'opinion' => 'Opinión de cumplimiento SAT',
            'acta' => 'Acta constitutiva',
            'rep_legal' => 'INE Representante legal',
            'caratula_banco' => 'Carátula bancaria',
            'comprobante_domicilio' => 'Comprobante de domicilio',
            default => ucfirst($tipo),
        };

        if (empty($errores)) {
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $provId, 'tipo' => $tipo],
                [
                    'archivo' => $path,
                    'estatus' => 'aprobado',
                    'notas_revision' => 'Validado automáticamente por IA. '.($notas ?: ''),
                    'revisado_at' => now(),
                ]
            );

            return back()->with('fiscal_resultado', [
                'aprobado' => true,
                'mensaje' => "{$tipoLabel} validado correctamente. El documento cumple con los requisitos y fue aprobado automáticamente.",
            ]);
        }

        DocumentoProveedor::updateOrCreate(
            ['proveedor_id' => $provId, 'tipo' => $tipo],
            [
                'archivo' => $path,
                'estatus' => 'rechazado',
                'notas_revision' => 'Rechazado por IA: '.implode(' | ', $errores),
                'revisado_at' => now(),
            ]
        );

        return back()->with('fiscal_resultado', [
            'aprobado' => false,
            'mensaje' => 'El documento fue rechazado. Errores: '.implode(' ', $errores).' Corrige y vuelve a subir.',
        ]);
    }

    private function normalizarTipoPersona(?string $tipo): string
    {
        $tipo = trim((string) $tipo);
        if ($tipo === 'Persona Física' || $tipo === 'Persona Moral') {
            return $tipo;
        }

        $lower = mb_strtolower($tipo);
        if (str_contains($lower, 'moral')) {
            return 'Persona Moral';
        }
        if (str_contains($lower, 'fís') || str_contains($lower, 'fis')) {
            return 'Persona Física';
        }

        return $tipo;
    }

    /** Null = ok. String = mensaje de error si intentan cambiar un tipo ya fijado. */
    private function errorSiIntentaCambiarTipoPersona(ProveedorUser $proveedor, mixed $enviado): ?string
    {
        if (! $proveedor->tipoPersonaBloqueado()) {
            return null;
        }

        $nuevo = $this->normalizarTipoPersona(is_string($enviado) ? $enviado : '');
        $actual = $proveedor->tipoPersonaNormalizado();
        if ($nuevo === '' || $nuevo === $actual) {
            return null;
        }

        return 'El tipo de persona ya quedó fijado y no se puede cambiar (como en el SAT). Si hay un error de registro, contacta a Compras.';
    }

    /** Campos que, si cambian, invalidan la validación fiscal ya hecha. */
    private function firmaCriticaIdentificacion(array $datos): array
    {
        $esMoral = ($datos['tipo_persona'] ?? '') === 'Persona Moral';
        $nombre = $esMoral
            ? trim((string) ($datos['razon_social'] ?? $datos['nombre_esperado'] ?? ''))
            : trim(implode(' ', array_filter([
                $datos['apellido_paterno'] ?? '',
                $datos['apellido_materno'] ?? '',
                $datos['nombres'] ?? '',
            ])));

        if ($nombre === '' && ! empty($datos['nombre_esperado'])) {
            $nombre = trim((string) $datos['nombre_esperado']);
        }

        return [
            'tipo_persona' => trim((string) ($datos['tipo_persona'] ?? '')),
            'banco' => mb_strtolower(trim((string) ($datos['banco'] ?? ''))),
            'clabe' => preg_replace('/\D/', '', (string) ($datos['clabe'] ?? '')),
            'cuenta' => preg_replace('/\D/', '', (string) ($datos['cuenta'] ?? '')),
            'banco_usd' => mb_strtolower(trim((string) ($datos['banco_usd'] ?? ''))),
            'clabe_usd' => preg_replace('/\D/', '', (string) ($datos['clabe_usd'] ?? '')),
            'cuenta_usd' => preg_replace('/\D/', '', (string) ($datos['cuenta_usd'] ?? '')),
            'nombre' => mb_strtolower(preg_replace('/\s+/', ' ', $nombre)),
            'cp' => preg_replace('/\D/', '', (string) ($datos['cp'] ?? '')),
        ];
    }

    private function cambioCriticoIdentificacion(array $antes, array $despues): bool
    {
        if ($antes === []) {
            return false;
        }

        return $this->firmaCriticaIdentificacion($antes) !== $this->firmaCriticaIdentificacion($despues);
    }

    /** @return bool true si invalidó al menos un documento aprobado */
    private function invalidarDocumentosTrasCambioIdentificacion(int $proveedorId): bool
    {
        try {
            $afectados = DocumentoProveedor::where('proveedor_id', $proveedorId)
                ->where('estatus', 'aprobado')
                ->update([
                    'estatus' => 'pendiente',
                    'notas_revision' => 'Invalidado automáticamente: el proveedor modificó datos bancarios o de identidad después de la validación. Debe volver a validar documentos.',
                    'revisado_at' => null,
                ]);

            return $afectados > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
