<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginClienteRequest;
use App\Models\ClienteUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthClienteController extends Controller
{
    public function mostrarLogin()
    {
        return view('clientes.login');
    }

    public function procesarLogin(LoginClienteRequest $request)
    {
        $key = 'login-cliente|' . $request->ip();
        $max = config('auth.rate_limiting.max_attempts', 5);
        $decay = config('auth.rate_limiting.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Login cliente bloqueado por rate limiting', ['ip' => $request->ip(), 'segundos' => $seconds]);
            return back()->with('error', "Demasiados intentos. Intenta en {$seconds} segundos.")->withInput();
        }

        $cliente = ClienteUser::where('usuario', $request->usuario)->first();

        if (!$cliente || !Hash::check($request->password, $cliente->password)) {
            RateLimiter::hit($key, $decay);
            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        if (!$cliente->activo) {
            RateLimiter::hit($key, $decay);
            return back()->with('error', 'Tu cuenta está desactivada. Contacta a Salcom.')->withInput();
        }

        RateLimiter::clear($key);
        session([
            'cliente_id'     => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'cliente_codigo' => $cliente->codigo_cliente,
            'cliente_correo' => $cliente->correo,
            'cliente_tipo'   => $cliente->tipo_cliente,
        ]);

        return redirect('/portal-cliente')->with('mensaje', 'Bienvenido ' . $cliente->nombre);
    }

    public function cerrarSesion()
    {
        session()->forget(['cliente_id', 'cliente_nombre', 'cliente_codigo', 'cliente_correo', 'cliente_tipo']);
        return redirect('/login-cliente')->with('mensaje', 'Sesión cerrada correctamente');
    }
}
