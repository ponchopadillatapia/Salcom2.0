<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutenticacionProveedor
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('proveedor_id')) {
            return redirect('/login-proveedor')
                ->with('error', 'Debes iniciar sesión para acceder al portal');
        }

        $response = $next($request);

        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }
}
