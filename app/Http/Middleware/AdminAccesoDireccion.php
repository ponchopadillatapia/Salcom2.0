<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;

/**
 * Bloquea el acceso a las secciones exclusivas de Dirección.
 * Los usuarios restringidos (Brenda) solo pueden ver Productos y Anticipos;
 * si intentan entrar a otra ruta se les redirige con aviso.
 * POR QUÉ: dirección pidió que Brenda solo gestione productos y anticipos.
 */
class AdminAccesoDireccion
{
    public function handle(Request $request, Closure $next)
    {
        $admin = AdminUser::find(session('admin_id'));

        if ($admin && $admin->esRestringido()) {
            return redirect('/admin/productos')
                ->with('error', 'No tienes acceso a esta sección. Solo puedes ver Productos y Anticipos.');
        }

        return $next($request);
    }
}
