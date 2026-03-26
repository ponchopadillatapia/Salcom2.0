<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutenticacionProveedor
{
    public function handle(Request $request, Closure $next)
    {
        // Si no hay sesión activa, manda al login
        if (!session('proveedor_id')) {
            return redirect('/login-proveedor')
                ->with('error', 'Debes iniciar sesión para acceder al portal');
        }

        return $next($request);
    }
}