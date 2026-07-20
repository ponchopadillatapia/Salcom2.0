<?php

namespace App\Http\Middleware;

use App\Models\ProveedorUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AutenticacionProveedor
{
    /** Rutas permitidas mientras el proveedor no está activo (onboarding). */
    private array $rutasOnboarding = [
        'proveedores.onboarding',
        'proveedores.perfil',
        'proveedores.perfil.foto',
        'proveedores.contactos.guardar',
        'proveedores.contactos.eliminar',
        'proveedores.validacion-fiscal',
        'proveedores.identificacion',
        'proveedores.identificacion.guardar',
        'proveedores.actualizacion',
        'proveedores.actualizacion.guardar',
        'proveedores.adjunto-documentos',
        'proveedores.adjunto-documentos.subir',
        'proveedores.logout',
        'proveedores.aviso.aceptar',
        'aviso.privacidad',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! session('proveedor_id')) {
            return redirect('/login-proveedor')
                ->with('error', 'Debes iniciar sesión para acceder al portal');
        }

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $activo = $proveedor ? (bool) $proveedor->activo : false;

        View::share('proveedorPortalActivo', $activo);
        View::share('proveedorPortal', $proveedor);

        if (! $activo) {
            $ruta = optional($request->route())->getName();
            $permitida = $ruta && (
                in_array($ruta, $this->rutasOnboarding, true)
                || str_starts_with((string) $ruta, 'proveedores.identificacion')
            );

            if (! $permitida) {
                return redirect()
                    ->route('proveedores.onboarding')
                    ->with('error', 'Tu cuenta aún no está activa. Completa el onboarding y espera la aprobación de Dirección.');
            }
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
