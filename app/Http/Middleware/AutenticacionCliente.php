<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutenticacionCliente
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('cliente_id')) {
            return redirect('/login-cliente')
                ->with('error', 'Debes iniciar sesión para acceder al portal');
        }
        return $next($request);
    }
}
