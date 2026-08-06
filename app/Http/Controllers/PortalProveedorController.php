<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Alerta;
use App\Models\ContactoProveedor;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Models\SolicitudAlta;
use App\Services\AltaFacturaValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $pasoListoDireccion = $pasoBancarios && $pasoDocs && $pasoContactos;
            $pasoActivo = (bool) $proveedor->activo;
            $onboardingBloqueado = $proveedor->onboardingEdicionBloqueada();
            $estatusAlta = $proveedor->solicitud_alta_estatus ?? null;

            $completados = (int) $pasoRegistro + (int) $pasoBancarios + (int) $pasoDocs + (int) $pasoContactos + (int) ($pasoListoDireccion && $pasoActivo ? 1 : 0);
            $totalPasos = 5;
            $pct = (int) round(100 * $completados / $totalPasos);

            return view('proveedores.onboarding', compact(
                'proveedor',
                'pasoRegistro',
                'pasoBancarios',
                'pasoDocs',
                'pasoDocsRenovar',
                'numContactos',
                'pasoContactos',
                'pasoListoDireccion',
                'pasoActivo',
                'onboardingBloqueado',
                'estatusAlta',
                'completados',
                'totalPasos',
                'pct'
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

        $base = Factura::query()
            ->when($codigo, fn ($q) => $q->where('codigo_proveedor', $codigo))
            ->where('estatus', '!=', 'rechazada');

        if ($request->filled('fecha_desde')) {
            $base->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $base->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        $kpis = [
            'pendientes' => (clone $base)->where('estatus', 'pendiente')->count(),
            'programadas' => (clone $base)->whereIn('estatus', ['programada', 'aprobada', 'validada'])->count(),
            'pagadas' => (clone $base)->where('estatus', 'pagada')->count(),
            'totales' => (clone $base)->count(),
        ];

        $query = clone $base;

        $buscar = trim((string) $request->input('q', ''));
        $campo = $request->input('campo', 'folio');
        if ($buscar !== '') {
            if ($campo === 'monto') {
                $query->where('total', 'like', '%'.str_replace([',', '$'], '', $buscar).'%');
            } elseif ($campo === 'estatus') {
                $query->where('estatus', 'like', '%'.$buscar.'%');
            } else {
                $query->where(function ($q) use ($buscar) {
                    $q->where('folio_cfdi', 'like', '%'.$buscar.'%')
                        ->orWhere('uuid_cfdi', 'like', '%'.$buscar.'%');
                });
            }
        }

        $facturas = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $filtros = [
            'fecha_desde' => $request->input('fecha_desde', ''),
            'fecha_hasta' => $request->input('fecha_hasta', ''),
            'q' => $buscar,
            'campo' => $campo,
        ];

        return view('proveedores.facturas', compact('proveedor', 'facturas', 'filtros', 'codigo', 'kpis'));
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

        return view('proveedores.perfil', compact('proveedor', 'contactos', 'minContactos', 'faltanContactos'));
    }

    public function actualizarPerfil(Request $request)
    {
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

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->with('error', 'Proveedor no encontrado.');
        }

        $proveedor->update([
            'nombre' => $request->nombre,
            'tipo_persona' => $request->tipo_persona,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
        ]);

        if ($request->filled('password')) {
            $proveedor->update(['password' => bcrypt($request->password)]);
        }

        session([
            'proveedor_nombre' => $proveedor->nombre,
            'proveedor_correo' => $proveedor->correo,
        ]);

        return redirect()->route('proveedores.perfil')->with('mensaje', 'Datos actualizados correctamente.');
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
            'cuenta.regex' => 'La cuenta solo acepta dígitos (5 a 20).',
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
                    'tipo_persona' => $data['tipo_persona'],
                    'nombre' => $nombreEsperado !== '' ? $nombreEsperado : $proveedor->nombre,
                ];
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
                    $proveedor->update([
                        'tipo_persona' => $data['tipo_persona'],
                        'nombre' => $nombreEsperado !== '' ? $nombreEsperado : $proveedor->nombre,
                    ]);
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

        return redirect()->route('proveedores.onboarding')->with('mensaje', $mensaje);
    }

    public function mostrarValidacionFiscal()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor && ! $proveedor->tieneFormularioDatosBancarios()) {
            return redirect()->route('proveedores.onboarding')
                ->with('error', 'Primero completa el formulario de datos bancarios.');
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
        $pendiente = session('fiscal_pendiente');
        $puedeSubir = is_array($pendiente)
            && ! empty($pendiente['aprobado'])
            && ! empty($pendiente['token'])
            && ($pendiente['proveedor_id'] ?? null) === ($proveedor?->id);

        return view('proveedores.fiscal', compact(
            'facturas',
            'rfcProveedor',
            'proveedor',
            'puedeSubir',
            'pendiente'
        ));
    }

    /**
     * Paso 1: validar PDF + XML (sin registrar). Guarda temporales en sesión.
     */
    public function validarAltaFactura(Request $request, AltaFacturaValidationService $validator)
    {
        $naturaleza = $request->input('naturaleza'); // producto | servicio
        $tipoProducto = strtoupper((string) $request->input('tipo_producto', ''));
        $requiereOc = $naturaleza === 'producto' && in_array($tipoProducto, ['ME', 'MP'], true);

        $request->validate([
            'naturaleza' => 'required|in:producto,servicio',
            'tipo_producto' => 'required_if:naturaleza,producto|nullable|in:ME,MP,MN',
            'archivo' => 'required|file|mimes:pdf|max:10240',
            'archivo_xml' => 'required|file|extensions:xml|max:5120',
            'archivo_oc' => ($requiereOc ? 'required' : 'nullable').'|file|mimes:pdf|max:10240',
            'es_fletera' => 'required|in:0,1',
        ], [
            'naturaleza.required' => 'Indica si es producto o servicio.',
            'tipo_producto.required_if' => 'Selecciona el tipo de producto (ME, MP o Mantenimiento).',
            'archivo.required' => 'La factura en PDF es obligatoria.',
            'archivo.mimes' => 'La factura debe ser un archivo PDF.',
            'archivo_xml.required' => 'El XML de la factura es obligatorio.',
            'archivo_xml.extensions' => 'El archivo CFDI debe ser un XML válido (.xml).',
            'archivo_oc.required' => 'Para productos ME o MP la orden de compra (OC) es obligatoria.',
            'archivo_oc.mimes' => 'La orden de compra debe ser un archivo PDF.',
            'es_fletera.required' => 'Indica si la factura es de flete o no.',
        ]);

        if ($naturaleza === 'servicio') {
            $tipoProducto = 'SERVICIO';
        }

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->withErrors(['archivo' => 'Sesión de proveedor no válida. Vuelve a iniciar sesión.']);
        }

        $this->limpiarFiscalPendiente();

        $pdf = $request->file('archivo');
        $xmlFile = $request->file('archivo_xml');

        $pdfContent = file_get_contents($pdf->getRealPath());
        if (! str_starts_with($pdfContent, '%PDF')) {
            return back()->withInput()->with('fiscal_resultado', [
                'aprobado' => false,
                'mensaje' => 'El archivo PDF no es válido.',
                'errores' => ['El archivo de factura no es un PDF real.'],
                'checklist' => [],
            ]);
        }

        if ($xmlFile->getSize() < 1) {
            return back()->withInput()->with('fiscal_resultado', [
                'aprobado' => false,
                'mensaje' => 'La factura no pasó la validación.',
                'errores' => [
                    'El archivo XML está vacío (0 bytes). Descarga de nuevo el CFDI desde el portal del emisor o el SAT.',
                ],
                'checklist' => [],
            ]);
        }

        $xmlContent = file_get_contents($xmlFile->getRealPath());
        if ($xmlContent === false || trim($xmlContent) === '' || ! str_contains($xmlContent, '<')) {
            return back()->withInput()->with('fiscal_resultado', [
                'aprobado' => false,
                'mensaje' => 'La factura no pasó la validación.',
                'errores' => [
                    'El archivo XML no contiene un CFDI legible. Verifica que sea el XML timbrado.',
                ],
                'checklist' => [],
            ]);
        }

        $esFletera = $request->input('es_fletera') === '1';
        $rfcProveedor = $this->rfcProveedorSesion($proveedor);
        $ocBinary = $request->hasFile('archivo_oc')
            ? file_get_contents($request->file('archivo_oc')->getRealPath())
            : null;
        $resultado = $validator->validar($xmlContent, $esFletera, $rfcProveedor, $pdfContent, $ocBinary);

        // Usar el indicador efectivo tras corrección por conceptos del XML
        $esFleteraEfectivo = (bool) ($resultado['datos']['es_fletera'] ?? $esFletera);

        if (! $resultado['aprobado']) {
            return back()->withInput()->with('fiscal_resultado', [
                'aprobado' => false,
                'estatus' => $resultado['estatus'] ?? 'rechazada',
                'mensaje' => $resultado['mensaje'] ?? 'La factura no pasó la validación. Corrige los errores y vuelve a validar.',
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias'],
                'checklist' => $resultado['checklist'],
                'datos' => $resultado['datos'],
            ]);
        }

        $token = bin2hex(random_bytes(16));
        $tempDir = 'temp-fiscal/'.$proveedor->id.'/'.$token;
        $pathPdf = $pdf->storeAs($tempDir, 'factura.pdf');
        $pathXml = $xmlFile->storeAs($tempDir, 'factura.xml');
        $pathOc = null;
        if ($request->hasFile('archivo_oc')) {
            $pathOc = $request->file('archivo_oc')->storeAs($tempDir, 'oc.pdf');
        }

        $pendiente = [
            'token' => $token,
            'proveedor_id' => $proveedor->id,
            'aprobado' => true,
            'path_pdf' => $pathPdf,
            'path_xml' => $pathXml,
            'path_oc' => $pathOc,
            'es_fletera' => $esFleteraEfectivo,
            'es_me_mp' => $requiereOc,
            'requiere_oc' => $requiereOc,
            'naturaleza' => $naturaleza,
            'tipo_producto' => $tipoProducto,
            'resultado' => $resultado,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ];

        // Validación en verde → registrar de inmediato como pendiente
        try {
            $this->registrarFacturaDesdePendiente($proveedor, $pendiente);
        } catch (\InvalidArgumentException $e) {
            Storage::disk('local')->deleteDirectory($tempDir);

            return back()->withInput()->with('fiscal_resultado', [
                'aprobado' => false,
                'estatus' => 'rechazada',
                'mensaje' => 'La factura no se pudo registrar.',
                'errores' => [$e->getMessage()],
                'advertencias' => $resultado['advertencias'],
                'checklist' => $resultado['checklist'],
                'datos' => $resultado['datos'],
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->deleteDirectory($tempDir);
            Log::error('[AltaFactura] Error al registrar: '.$e->getMessage());

            return back()->withInput()->withErrors([
                'archivo' => 'Error al guardar la factura. Intenta de nuevo.',
            ]);
        }

        Storage::disk('local')->deleteDirectory($tempDir);
        session()->forget('fiscal_pendiente');

        $estatus = $resultado['estatus'] ?? 'aprobada';
        $msgExito = match ($estatus) {
            'aprobada_con_observaciones' => 'Factura registrada con observaciones (sin OC). Queda en estatus pendiente.',
            default => 'Factura validada y registrada. Queda en estatus pendiente.',
        };

        return redirect()
            ->route('proveedores.facturas')
            ->with('exito', $msgExito);
    }

    /**
     * Compat: ruta antigua «Subir». Reenvía a validar+registrar si aún hay sesión.
     */
    public function altaFactura(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->withErrors(['archivo' => 'Sesión de proveedor no válida. Vuelve a iniciar sesión.']);
        }

        $pendiente = session('fiscal_pendiente');
        if (! is_array($pendiente)
            || empty($pendiente['aprobado'])
            || ($pendiente['proveedor_id'] ?? null) !== $proveedor->id
            || empty($pendiente['token'])
            || ($pendiente['expires_at'] ?? 0) < now()->timestamp
        ) {
            return back()->withErrors([
                'archivo' => 'Adjunta PDF + XML y pulsa «Validar y registrar».',
            ]);
        }

        try {
            $this->registrarFacturaDesdePendiente($proveedor, $pendiente);
        } catch (\InvalidArgumentException $e) {
            $this->limpiarFiscalPendiente();

            return back()->with('fiscal_resultado', [
                'aprobado' => false,
                'mensaje' => 'La factura no se pudo registrar.',
                'errores' => [$e->getMessage()],
                'checklist' => $pendiente['resultado']['checklist'] ?? [],
                'datos' => $pendiente['resultado']['datos'] ?? [],
            ]);
        } catch (\Throwable $e) {
            $this->limpiarFiscalPendiente();
            Log::error('[AltaFactura] Error al registrar: '.$e->getMessage());

            return back()->withErrors(['archivo' => 'Error al guardar la factura. Intenta de nuevo.']);
        }

        $this->limpiarFiscalPendiente();

        return redirect()
            ->route('proveedores.facturas')
            ->with('exito', 'Factura registrada. Queda en estatus pendiente.');
    }

    /**
     * Persiste factura en estatus pendiente desde archivos temporales + resultado de validación.
     *
     * @param  array<string, mixed>  $pendiente
     */
    private function registrarFacturaDesdePendiente(ProveedorUser $proveedor, array $pendiente): Factura
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

        $folio = $uuid
            ?: trim(($datos['serie'] ?? '').($datos['folio'] ?? ''))
            ?: ('TMP-'.uniqid());
        $folioCfdi = $folio;
        if (Factura::where('folio_cfdi', $folioCfdi)->exists()) {
            $folioCfdi = $folioCfdi.'-'.substr(uniqid(), -4);
        }

        $dias = (int) config('facturas.dias_vencimiento', 30);
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
                'naturaleza' => $pendiente['naturaleza'] ?? null,
                'tipo_producto' => $pendiente['tipo_producto'] ?? null,
                'es_me_mp' => (bool) ($pendiente['es_me_mp'] ?? false),
                'validado_at' => now()->toIso8601String(),
            ],
        ]);

        try {
            $nombreProv = $proveedor->nombre ?: $codigoProv;
            $totalFmt = number_format($total, 2);
            app(\App\Services\AlertEngineService::class)->crearAlerta([
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

        return $factura;
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
