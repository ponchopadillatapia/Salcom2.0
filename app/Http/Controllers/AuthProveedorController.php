<?php

namespace App\Http\Controllers;

use App\Exceptions\ProveedorApiException;
use App\Http\Requests\LoginProveedorRequest;
use App\Http\Requests\RegisterProveedorRequest;
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
        return view('proveedores.login');
    }

    public function mostrarRegistro()
    {
        return view('proveedores.registro');
    }

    public function procesarLogin(LoginProveedorRequest $request)
    {
        $rateLimitKey = 'login-proveedor|' . $request->ip();
        $maxAttempts  = config('auth.rate_limiting.max_attempts', 5);
        $decaySeconds = config('auth.rate_limiting.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning('Login bloqueado por rate limiting', [
                'ip'                 => $request->ip(),
                'segundos_restantes' => $seconds,
            ]);
            return back()
                ->with('error', "Demasiados intentos de inicio de sesión. Intenta de nuevo en {$seconds} segundos.")
                ->withInput();
        }

        $codigo = $request->codigo;
        $pwd    = $request->pwd;
        $modo   = $this->getLoginMode();

        if ($modo === 'local') {
            $datos = $this->loginViaLocal($codigo, $pwd);
            if ($datos) {
                RateLimiter::clear($rateLimitKey);
                $this->guardarSesion($datos, 'local', null);
                return redirect('/portal-proveedor')->with('mensaje', 'Bienvenido ' . $datos['nombre']);
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
            return redirect('/portal-proveedor')->with('mensaje', 'Bienvenido ' . $datos['nombre']);
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
                return redirect('/portal-proveedor')->with('mensaje', 'Bienvenido ' . $datos['nombre']);
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
            if (!($recaptcha['success'] ?? false)) {
                return back()->withErrors(['g-recaptcha-response' => 'Captcha inválido, inténtalo de nuevo'])->withInput();
            }
        }

        ProveedorUser::create([
            'usuario' => $request->correo, 'password' => bcrypt($request->password),
            'nombre' => $request->nombre, 'tipo_persona' => $request->tipo_persona,
            'telefono' => $request->telefono, 'correo' => $request->correo,
        ]);

        return redirect('/login-proveedor')->with('mensaje', 'Registro exitoso, ahora puedes iniciar sesión');
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
            if ($request->password) { $proveedor->update(['password' => bcrypt($request->password)]); }
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
        if (!$proveedor || !Hash::check($pwd, $proveedor->password)) { return null; }
        return ['id' => $proveedor->id, 'nombre' => $proveedor->nombre, 'codigo' => $proveedor->codigo_compras, 'correo' => $proveedor->correo, 'token' => null];
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
