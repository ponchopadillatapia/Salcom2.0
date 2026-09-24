<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AutenticacionAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('admin_id')) {
            return redirect('/login-admin')
                ->with('error', 'Debes iniciar sesión para acceder al panel de administración');
        }

        // Compartir con TODAS las vistas admin quién es Dirección y quién restringido,
        // para mostrar/ocultar secciones del menú según el usuario.
        $adminUser = AdminUser::find(session('admin_id'));
        View::share('adminEsDireccion', $adminUser ? $adminUser->esDireccion() : false);
        View::share('adminEsRestringido', $adminUser ? $adminUser->esRestringido() : false);
        View::share('adminUser', $adminUser);

        // Control de acceso centralizado para usuarios restringidos (Brenda):
        // solo pueden entrar a Productos y Anticipos; el resto se bloquea aquí
        // para no tener que marcar cada ruta una por una.
        if ($adminUser && $adminUser->esRestringido()) {
            $path = $request->path(); // ej: "admin/pedidos"
            $rutasPermitidas = [
                'admin/productos',
                'admin/anticipos',
                'admin/perfil',
            ];
            $permitida = false;
            foreach ($rutasPermitidas as $permitidaBase) {
                if ($path === $permitidaBase || str_starts_with($path, $permitidaBase.'/')) {
                    $permitida = true;
                    break;
                }
            }
            // El logout siempre permitido para que pueda cerrar sesión
            $esLogout = $request->is('logout-admin') || $request->routeIs('admin.logout');

            if (! $permitida && ! $esLogout) {
                return redirect('/admin/productos')
                    ->with('error', 'Solo tienes acceso a Productos y Anticipos.');
            }
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
