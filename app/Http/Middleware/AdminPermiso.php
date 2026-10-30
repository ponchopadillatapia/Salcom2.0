<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;

/**
 * Control de acceso por PERMISO/sección, con rebote 403 (no redirect).
 *
 * POR QUÉ existe (defensa en profundidad): ocultar el botón del menú no basta; cualquiera
 * podría escribir la URL directa. Este middleware protege la URL: si el admin no tiene el
 * permiso requerido, corta con 403 Forbidden.
 *
 * Uso en rutas: ->middleware('admin.permiso:pagos')  (o varios: 'admin.permiso:pagos,proveedores')
 * Dirección (esDireccion) siempre pasa.
 */
class AdminPermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): mixed
    {
        $adminId = session('admin_id');
        if (! $adminId) {
            return redirect('/login-admin');
        }

        $admin = AdminUser::find($adminId);
        if (! $admin) {
            return redirect('/login-admin');
        }

        // Dirección tiene acceso total.
        if ($admin->esDireccion()) {
            return $next($request);
        }

        // ¿Tiene al menos uno de los permisos requeridos?
        foreach ($permisos as $permiso) {
            if ($admin->puedeVer($permiso)) {
                return $next($request);
            }
        }

        // Defensa en profundidad: la URL directa rebota con 403.
        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
