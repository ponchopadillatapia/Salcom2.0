<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutenticacionEmpleado
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('empleado_id')) {
            return redirect()->route('empleados.login')
                ->with('error', 'Inicia sesión con tu número de empleado.');
        }

        return $next($request);
    }
}
