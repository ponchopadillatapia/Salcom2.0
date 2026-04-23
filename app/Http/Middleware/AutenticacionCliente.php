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

        $response = $next($request);

        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }
}
