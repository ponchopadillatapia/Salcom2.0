<?php

namespace App\Http\Controllers;

use App\Exceptions\ProveedorApiException;
use App\Http\Requests\LoginClienteRequest;
use App\Models\ClienteUser;
use App\Services\ClienteApiService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthClienteController extends Controller
{
    private ClienteApiService $apiService;

    public function __construct(ClienteApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function mostrarLogin()
    {
        if (session('cliente_id')) {
            return redirect('/portal-cliente');
        }
        return view('clientes.login');
    }

    public function procesarLogin(LoginClienteRequest $request)
    {
        $key   = 'login-cliente|' . $request->ip();
        $max   = config('auth.rate_limiting.max_attempts', 5);
        $decay = config('auth.rate_limiting.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Login cliente bloqueado por rate limiting', [
                'ip'      => $request->ip(),
                'segundos' => $seconds,
            ]);
            return back()
                ->with('error', "Demasiados intentos. Intenta en {$seconds} segundos.")
                ->withInput();
        }

        $usuario = $request->usuario;
        $pwd     = $request->password;
        $modo    = $this->getLoginMode();

        // ── Modo local: solo BD ──
        if ($modo === 'local') {
            $datos = $this->loginViaLocal($usuario, $pwd);
            if (isset($datos['_inactivo'])) {
                RateLimiter::hit($key, $decay);
                return back()->with('error', 'Tu cuenta está desactivada. Contacta a Salcom.')->withInput();
            }
            if ($datos) {
                RateLimiter::clear($key);
                $this->guardarSesion($datos, 'local', null);
                return redirect('/portal-cliente')->with('mensaje', 'Bienvenido ' . $datos['nombre']);
            }
            RateLimiter::hit($key, $decay);
            Log::error('Login cliente: fallo local', ['usuario' => $usuario, 'modo' => 'local']);
            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        // ── Intentar API primero ──
        $apiResult = $this->apiService->loginApi($usuario, $pwd);

        if ($apiResult['success']) {
            $datos = $this->loginViaApi($apiResult);
            RateLimiter::clear($key);
            $this->guardarSesion($datos, 'api', $datos['token']);
            Log::info('Login cliente: exitoso por API', ['usuario' => $usuario]);
            return redirect('/portal-cliente')->with('mensaje', 'Bienvenido ' . $datos['nombre']);
        }

        $errorType = $apiResult['error_type'] ?? '';

        // Credenciales rechazadas por la API — no hay fallback posible
        if ($errorType === ProveedorApiException::AUTENTICACION_FALLIDA) {
            RateLimiter::hit($key, $decay);
            Log::error('Login cliente: credenciales rechazadas por API', ['usuario' => $usuario]);
            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        // Modo api estricto — sin fallback
        if ($modo === 'api') {
            RateLimiter::hit($key, $decay);
            Log::error('Login cliente: API no disponible, sin fallback', [
                'usuario'    => $usuario,
                'error_type' => $errorType,
                'modo'       => 'api',
            ]);
            return back()->with('error', $apiResult['message'])->withInput();
        }

        // ── Modo fallback: API caída → intentar BD local ──
        $erroresFallback = [
            ProveedorApiException::API_CAIDA,
            ProveedorApiException::TIMEOUT,
            ProveedorApiException::ERROR_SERVIDOR,
            ProveedorApiException::ERROR_DESCONOCIDO,
        ];

        if (in_array($errorType, $erroresFallback)) {
            Log::warning('Login cliente: fallback a BD local', [
                'usuario'    => $usuario,
                'error_type' => $errorType,
            ]);
            $datos = $this->loginViaLocal($usuario, $pwd);
            if (isset($datos['_inactivo'])) {
                RateLimiter::hit($key, $decay);
                return back()->with('error', 'Tu cuenta está desactivada. Contacta a Salcom.')->withInput();
            }
            if ($datos) {
                RateLimiter::clear($key);
                $this->guardarSesion($datos, 'local', null);
                return redirect('/portal-cliente')->with('mensaje', 'Bienvenido ' . $datos['nombre']);
            }
            RateLimiter::hit($key, $decay);
            Log::error('Login cliente: fallback local también falló', ['usuario' => $usuario]);
            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        RateLimiter::hit($key, $decay);
        Log::error('Login cliente: error no contemplado', [
            'usuario'    => $usuario,
            'error_type' => $errorType,
        ]);
        return back()->with('error', $apiResult['message'])->withInput();
    }

    public function cerrarSesion()
    {
        session()->forget([
            'cliente_id', 'cliente_nombre', 'cliente_codigo',
            'cliente_correo', 'cliente_tipo', 'cliente_token',
            'cliente_login_source',
        ]);
        return redirect('/login-cliente')->with('mensaje', 'Sesión cerrada correctamente');
    }

    // ── Private helpers ──

    private function loginViaApi(array $apiResult): array
    {
        $data = $apiResult['data'];
        return [
            'id'     => $data['usuario'] ?? null,
            'nombre' => $data['usuario'] ?? 'Cliente',
            'codigo' => $data['usuario'] ?? null,
            'correo' => $data['usuario'] ?? null,
            'tipo'   => $data['ctipocliente'] ?? null,
            'token'  => $data['tokencreado'] ?? null,
        ];
    }

    private function loginViaLocal(string $usuario, string $pwd): ?array
    {
        $cliente = ClienteUser::where('usuario', $usuario)->first();
        if (!$cliente || !Hash::check($pwd, $cliente->password)) {
            return null;
        }
        if (!$cliente->activo) {
            return ['_inactivo' => true];
        }
        return [
            'id'     => $cliente->id,
            'nombre' => $cliente->nombre,
            'codigo' => $cliente->codigo_cliente,
            'correo' => $cliente->correo,
            'tipo'   => $cliente->tipo_cliente,
            'token'  => null,
        ];
    }

    private function guardarSesion(array $datos, string $source, ?string $token): void
    {
        session([
            'cliente_id'           => $datos['id'],
            'cliente_nombre'       => $datos['nombre'],
            'cliente_codigo'       => $datos['codigo'],
            'cliente_correo'       => $datos['correo'],
            'cliente_tipo'         => $datos['tipo'],
            'cliente_token'        => $token,
            'cliente_login_source' => $source,
        ]);
    }

    private function getLoginMode(): string
    {
        $modo = config('services.cliente_api.login_mode', 'fallback');
        return in_array($modo, ['api', 'local', 'fallback']) ? $modo : 'fallback';
    }
}
