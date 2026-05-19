<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutenticacionProveedor
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('proveedor_id')) {
            return redirect('/login-proveedor')
                ->with('error', 'Debes iniciar sesión para acceder al portal');
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
