<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthAdminController extends Controller
{
    public function mostrarLogin()
    {
        return view('admin.login');
    }

    public function procesarLogin(Request $request)
    {
        $request->validate([
            'usuario'  => 'required|string',
            'password' => 'required|string',
        ]);

        $key   = 'login-admin|' . $request->ip();
        $max   = config('auth.rate_limiting.max_attempts', 5);
        $decay = config('auth.rate_limiting.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Login admin bloqueado por rate limiting', [
                'ip'       => $request->ip(),
                'segundos' => $seconds,
            ]);
            return back()
                ->with('error', "Demasiados intentos. Intenta en {$seconds} segundos.")
                ->withInput();
        }

        $admin = AdminUser::where('usuario', $request->usuario)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            RateLimiter::hit($key, $decay);
            Log::error('Login admin: credenciales incorrectas', ['usuario' => $request->usuario]);
            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        if (!$admin->activo) {
            RateLimiter::hit($key, $decay);
            return back()->with('error', 'Tu cuenta está desactivada. Contacta al administrador.')->withInput();
        }

        RateLimiter::clear($key);

        session([
            'admin_id'     => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
            'admin_usuario' => $admin->usuario,
        ]);

        Log::info('Login admin exitoso', ['usuario' => $admin->usuario]);

        return redirect('/admin/ia')->with('mensaje', 'Bienvenido ' . $admin->nombre);
    }

    public function cerrarSesion()
    {
        session()->forget([
            'admin_id', 'admin_nombre', 'admin_correo', 'admin_usuario',
        ]);

        return redirect('/login-admin')->with('mensaje', 'Sesión cerrada correctamente');
    }
}
