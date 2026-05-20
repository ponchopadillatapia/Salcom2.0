<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutenticacionAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('admin_id')) {
            return redirect('/login-admin')
                ->with('error', 'Debes iniciar sesión para acceder al panel de administración');
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
