<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutenticacionAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_id')) {
            return redirect('/login-admin')
                ->with('error', 'Debes iniciar sesión para acceder al panel de administración');
        }

        $response = $next($request);

        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }
}
