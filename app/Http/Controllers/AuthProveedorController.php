<?php

namespace App\Http\Controllers;

use App\Exceptions\ProveedorApiException;
use App\Http\Requests\LoginProveedorRequest;
use App\Http\Requests\RegisterProveedorRequest;
use App\Models\AdminUser;
use App\Models\ProveedorUser;
use App\Services\ProveedorApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthProveedorController extends Controller
{
    private ProveedorApiService $apiService;

    public function __construct(ProveedorApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function mostrarLogin()
    {
        if (session('proveedor_id')) {
            return $this->redirectTrasLoginProveedor(session('proveedor_nombre', 'Proveedor'));
        }

        return view('proveedores.login');
    }

    public function mostrarRegistro()
    {
        return view('proveedores.registro');
    }

    public function procesarLogin(LoginProveedorRequest $request)
    {
        $rateLimitKey = 'login-proveedor|'.$request->ip();
        $maxAttempts = config('auth.rate_limiting.max_attempts', 5);
        $decaySeconds = config('auth.rate_limiting.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning('Login bloqueado por rate limiting', [
                'ip' => $request->ip(),
                'segundos_restantes' => $seconds,
            ]);

            return back()
                ->with('error', "Demasiados intentos de inicio de sesión. Intenta de nuevo en {$seconds} segundos.")
                ->withInput();
        }

        $codigo = $request->codigo;
        $pwd = $request->pwd;
        $modo = $this->getLoginMode();

        if ($modo === 'local') {
            $datos = $this->loginViaLocal($codigo, $pwd);
            if ($datos) {
                RateLimiter::clear($rateLimitKey);
                $this->guardarSesion($datos, 'local', null);

                return $this->redirectTrasLoginProveedor($datos['nombre']);
            }
            RateLimiter::hit($rateLimitKey, $decaySeconds);
            Log::error('Login: fallo local', ['codigo' => $codigo, 'modo' => 'local']);

            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        $apiResult = $this->apiService->loginApi($codigo, $pwd);

        if ($apiResult['success']) {
            $datos = $this->loginViaApi($apiResult);
            RateLimiter::clear($rateLimitKey);
            $this->guardarSesion($datos, 'api', $datos['token']);
            Log::info('Login: exitoso por API', ['codigo' => $codigo]);

            return $this->redirectTrasLoginProveedor($datos['nombre']);
        }

        $errorType = $apiResult['error_type'] ?? '';

        if ($errorType === ProveedorApiException::AUTENTICACION_FALLIDA) {
            RateLimiter::hit($rateLimitKey, $decaySeconds);
            Log::error('Login: credenciales rechazadas por API', ['codigo' => $codigo]);

            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        if ($modo === 'api') {
            RateLimiter::hit($rateLimitKey, $decaySeconds);
            Log::error('Login: API no disponible, sin fallback', ['codigo' => $codigo, 'error_type' => $errorType, 'modo' => 'api']);

            return back()->with('error', $apiResult['message'])->withInput();
        }

        $erroresFallback = [
            ProveedorApiException::API_CAIDA, ProveedorApiException::TIMEOUT,
            ProveedorApiException::ERROR_SERVIDOR, ProveedorApiException::ERROR_DESCONOCIDO,
        ];

        if (in_array($errorType, $erroresFallback)) {
            Log::warning('Login: fallback a BD local', ['codigo' => $codigo, 'error_type' => $errorType]);
            $datos = $this->loginViaLocal($codigo, $pwd);
            if ($datos) {
                RateLimiter::clear($rateLimitKey);
                $this->guardarSesion($datos, 'local', null);

                return $this->redirectTrasLoginProveedor($datos['nombre']);
            }
            RateLimiter::hit($rateLimitKey, $decaySeconds);
            Log::error('Login: fallback local también falló', ['codigo' => $codigo]);

            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);
        Log::error('Login: error no contemplado', ['codigo' => $codigo, 'error_type' => $errorType]);

        return back()->with('error', $apiResult['message'])->withInput();
    }

    public function guardar(RegisterProveedorRequest $request)
    {
        $recaptchaSecret = config('services.recaptcha.secret_key');
        if ($recaptchaSecret) {
            $recaptcha = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $recaptchaSecret, 'response' => $request->input('g-recaptcha-response'), 'remoteip' => $request->ip(),
            ])->json();
            if (! ($recaptcha['success'] ?? false)) {
                return back()->withErrors(['g-recaptcha-response' => 'Captcha inválido, inténtalo de nuevo'])->withInput();
            }
        }

        ProveedorUser::create([
            'usuario' => $request->correo, 'password' => bcrypt($request->password),
            'nombre' => $request->nombre, 'tipo_persona' => $request->tipo_persona,
            'telefono' => $request->telefono, 'correo' => $request->correo,
            'activo' => false,
        ]);

        // Enviar correo de bienvenida al proveedor
        try {
            $correoProveedor = $request->correo;
            $nombreProveedor = $request->nombre;

            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($correoProveedor, $nombreProveedor) {
                $message->to($correoProveedor, $nombreProveedor)
                    ->subject('Bienvenido a Industrias Salcom — Registro exitoso')
                    ->html(
                        '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">' .
                        '<div style="text-align:center;margin-bottom:20px;">' .
                        '<h2 style="color:#6B3FA0;margin:0;">Industrias Salcom</h2>' .
                        '<p style="color:#666;font-size:12px;">Portal de Proveedores</p>' .
                        '</div>' .
                        '<div style="background:#f9fafb;border-radius:12px;padding:24px;border:1px solid #e5e7eb;">' .
                        '<h3 style="color:#1a1a2e;margin-top:0;">¡Hola ' . htmlspecialchars($nombreProveedor) . '!</h3>' .
                        '<p style="color:#4b5563;">Tu registro como proveedor fue exitoso. Para completar tu alta, inicia sesión y sigue los pasos del onboarding:</p>' .
                        '<ol style="color:#4b5563;line-height:2;">' .
                        '<li>Formulario de datos bancarios</li>' .
                        '<li>Validación de documentos fiscales</li>' .
                        '<li>Registro de contactos</li>' .
                        '</ol>' .
                        '<p style="color:#4b5563;">Una vez completados, el equipo de Dirección revisará y activará tu cuenta.</p>' .
                        '<div style="text-align:center;margin-top:20px;">' .
                        '<a href="https://salcomlink.mx/login-proveedor" style="background:#6B3FA0;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:14px;">Iniciar sesión</a>' .
                        '</div>' .
                        '</div>' .
                        '<p style="text-align:center;color:#999;font-size:11px;margin-top:20px;">Industrias Salcom S.A. de C.V. · Este correo fue enviado automáticamente.</p>' .
                        '</div>'
                    );
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo enviar correo de bienvenida: ' . $e->getMessage());
        }

        return redirect('/login-proveedor')->with('mensaje', 'Registro exitoso. Inicia sesión y completa tu onboarding para que Dirección active tu cuenta.');
    }

    public function mostrarActualizacion()
    {
        return view('proveedores.actualizacion');
    }

    public function guardarActualizacion(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255', 'tipo_persona' => 'required|string|max:255',
            'telefono' => 'required|string|max:20', 'correo' => 'required|email',
            'password' => 'nullable|min:8|confirmed',
        ], [
            'nombre.required' => 'El nombre es obligatorio.', 'telefono.required' => 'El teléfono es obligatorio.',
            'correo.required' => 'El correo es obligatorio.', 'correo.email' => 'El correo no es válido.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.', 'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor) {
            $proveedor->update(['nombre' => $request->nombre, 'tipo_persona' => $request->tipo_persona, 'telefono' => $request->telefono, 'correo' => $request->correo]);
            if ($request->password) {
                $proveedor->update(['password' => bcrypt($request->password)]);
            }
        }

        return redirect('/empresa')->with('mensaje', 'Datos actualizados, ahora sube tus documentos fiscales');
    }

    public function cerrarSesion()
    {
        session()->forget(['proveedor_id', 'proveedor_nombre', 'proveedor_codigo', 'proveedor_correo', 'proveedor_token', 'proveedor_login_source']);

        return redirect('/login-proveedor')->with('mensaje', 'Sesión cerrada correctamente');
    }

    // ── Private helpers ──

    private function loginViaApi(array $apiResult): array
    {
        $data = $apiResult['data'];

        return ['id' => $data['usuario'] ?? null, 'nombre' => $data['usuario'] ?? 'Proveedor', 'codigo' => $data['usuario'] ?? null, 'correo' => $data['usuario'] ?? null, 'token' => $data['tokencreado'] ?? null];
    }

    private function loginViaLocal(string $codigo, string $pwd): ?array
    {
        $proveedor = ProveedorUser::where('usuario', $codigo)->first();
        if ($proveedor && Hash::check($pwd, $proveedor->password)) {
            return ['id' => $proveedor->id, 'nombre' => $proveedor->nombre, 'codigo' => $proveedor->id_proveedor, 'correo' => $proveedor->correo, 'token' => null];
        }

        // Super admins (jesus, alex, fred) pueden entrar al portal de proveedores.
        // Se crea/usa un ProveedorUser espejo para que onboarding y demás páginas no fallen.
        $admin = AdminUser::where(function ($q) use ($codigo) {
            $q->where('usuario', $codigo)->orWhere('correo', $codigo);
        })->first();

        if ($admin && Hash::check($pwd, $admin->password) && $admin->rol === 'admin') {
            $proveedor = $this->asegurarProveedorEspejoAdmin($admin);

            return [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre ?? $admin->nombre,
                'codigo' => $proveedor->id_proveedor ?? $proveedor->codigo_compras ?? ('ADMIN-'.$admin->id),
                'correo' => $proveedor->correo ?? $admin->correo,
                'token' => null,
            ];
        }

        return null;
    }

    private function asegurarProveedorEspejoAdmin(AdminUser $admin): ProveedorUser
    {
        $existente = ProveedorUser::where('usuario', $admin->usuario)->first();
        if ($existente) {
            return $existente;
        }

        $datos = [
            'usuario' => $admin->usuario,
            'nombre' => $admin->nombre,
            'correo' => $admin->correo ?: ($admin->usuario.'@salcom.local'),
            'password' => $admin->password,
            'tipo_persona' => 'Persona Moral',
            'activo' => true,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('proveedores_users', 'id_proveedor')) {
            $datos['id_proveedor'] = 'ADMIN-'.$admin->id;
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('proveedores_users', 'codigo_compras')) {
            $datos['codigo_compras'] = 'ADMIN-'.$admin->id;
        }

        $proveedor = new ProveedorUser;
        $proveedor->forceFill($datos)->save();

        return $proveedor->fresh();
    }

    private function redirectTrasLoginProveedor(string $nombre)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor && ! $proveedor->activo) {
            return redirect()->route('proveedores.onboarding')
                ->with('mensaje', 'Bienvenido '.$nombre.'. Completa tu onboarding para que Dirección active tu cuenta.');
        }

        return redirect('/portal-proveedor')->with('mensaje', 'Bienvenido '.$nombre);
    }

    private function guardarSesion(array $datos, string $source, ?string $token): void
    {
        session(['proveedor_id' => $datos['id'], 'proveedor_nombre' => $datos['nombre'], 'proveedor_codigo' => $datos['codigo'], 'proveedor_correo' => $datos['correo'], 'proveedor_token' => $token, 'proveedor_login_source' => $source]);
    }

    private function getLoginMode(): string
    {
        $modo = config('services.proveedor_api.login_mode', 'fallback');

        return in_array($modo, ['api', 'local', 'fallback']) ? $modo : 'fallback';
    }
}
