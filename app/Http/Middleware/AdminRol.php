<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;

class AdminRol
{
    /**
     * Verifica que el admin tenga el rol requerido.
     * El rol 'gerente' tiene acceso a todo.
     *
     * Uso en rutas: ->middleware('admin.rol:materia_prima')
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $adminId = session('admin_id');
        if (! $adminId) {
            return redirect('/login-admin');
        }

        $admin = AdminUser::find($adminId);
        if (! $admin) {
            return redirect('/login-admin');
        }

        // Gerente tiene acceso a todo
        if ($admin->rol === 'gerente') {
            return $next($request);
        }

        // Verificar si el rol del admin está en los roles permitidos
        if (in_array($admin->rol, $roles)) {
            return $next($request);
        }

        return redirect('/admin/dashboard')->with('error', 'No tienes acceso a esta sección.');
    }
}
