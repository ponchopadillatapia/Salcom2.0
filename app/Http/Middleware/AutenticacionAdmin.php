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

        // Control de acceso centralizado para usuarios restringidos.
        // Cada usuario tiene sus secciones permitidas; el resto se bloquea aquí
        // para no tener que marcar cada ruta una por una.
        if ($adminUser && $adminUser->esRestringido()) {
            $path = $request->path(); // ej: "admin/pedidos"
            $secciones = $adminUser->seccionesPermitidas();

            // Mapa: sección interna → prefijos de ruta que abarca
            $rutasPorSeccion = [
                'productos'   => ['admin/productos', 'admin/alta-producto', 'admin/alta-producto-mto', 'admin/alta-producto-pt', 'admin/migracion-masiva'],
                'anticipos'   => ['admin/anticipos'],
                'pagos'       => ['admin/pagos', 'admin/pago-proveedores', 'admin/abono-proveedor', 'admin/historial-abonos', 'admin/expedientes-pago'],
                'proveedores' => ['admin/proveedores', 'admin/catalogo-proveedores', 'admin/solicitudes-alta', 'admin/solicitudes-docs', 'admin/expediente-fiscal', 'admin/proveedor-facturas'],
                // reembolsos: 3 módulos de reembolso a empleados + alta/gestión de empleados (Nayeli)
                'reembolsos'  => ['admin/reembolsos', 'admin/reembolsos-viaje', 'admin/bitacora-gasolina', 'admin/empleados'],
            ];

            // Rutas que cualquier usuario logueado puede tocar (perfil, salir)
            $rutasBase = ['admin/perfil'];
            $permitidas = $rutasBase;
            foreach ($secciones as $sec) {
                $permitidas = array_merge($permitidas, $rutasPorSeccion[$sec] ?? []);
            }

            $permitida = false;
            foreach ($permitidas as $base) {
                if ($path === $base || str_starts_with($path, $base.'/')) {
                    $permitida = true;
                    break;
                }
            }
            $esLogout = $request->is('logout-admin') || $request->routeIs('admin.logout');

            if (! $permitida && ! $esLogout) {
                // Redirigir a la primera sección que sí puede ver
                $destino = in_array('productos', $secciones, true) ? '/admin/productos'
                    : (in_array('pagos', $secciones, true) ? '/admin/pagos'
                    : (in_array('proveedores', $secciones, true) ? '/admin/proveedores'
                    : (in_array('reembolsos', $secciones, true) ? '/admin/reembolsos' : '/admin/perfil')));

                return redirect($destino)->with('error', 'No tienes acceso a esa sección.');
            }
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
