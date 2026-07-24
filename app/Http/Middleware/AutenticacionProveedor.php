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
        'proveedores.validacion-fiscal.api',
        'proveedores.identificacion',
        'proveedores.identificacion.guardar',
        'proveedores.actualizacion',
        'proveedores.actualizacion.guardar',
        'proveedores.adjunto-documentos',
        'proveedores.adjunto-documentos.subir',
        'proveedores.logout',
        'proveedores.aviso.aceptar',
        'proveedores.alertas.recientes',
        'proveedores.alertas.leer',
        'aviso.privacidad',
    ];

    /** Rutas permitidas si ya está activo pero aún le faltan los 2 contactos. */
    private array $rutasSoloContactos = [
        'proveedores.onboarding',
        'proveedores.perfil',
        'proveedores.perfil.foto',
        'proveedores.contactos.guardar',
        'proveedores.contactos.eliminar',
        'proveedores.logout',
        'proveedores.aviso.aceptar',
        'proveedores.alertas.recientes',
        'proveedores.alertas.leer',
        'aviso.privacidad',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! session('proveedor_id')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['mensaje' => 'Sesión expirada. Vuelve a iniciar sesión.'], 401);
            }

            return redirect('/login-proveedor')
                ->with('error', 'Debes iniciar sesión para acceder al portal');
        }

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $activo = $proveedor ? (bool) $proveedor->activo : false;
        $debeContactos = $this->debeCompletarContactos($proveedor);

        View::share('proveedorPortalActivo', $activo && ! $debeContactos);
        View::share('proveedorPortal', $proveedor);
        View::share('proveedorDebeContactos', $debeContactos);

        $ruta = optional($request->route())->getName();

        if (! $activo) {
            $permitida = $ruta && (
                in_array($ruta, $this->rutasOnboarding, true)
                || str_starts_with((string) $ruta, 'proveedores.identificacion')
            );

            if (! $permitida) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'mensaje' => 'Tu cuenta aún no está activa. Completa el onboarding y espera la aprobación de Dirección.',
                    ], 403);
                }

                return redirect()
                    ->route('proveedores.onboarding')
                    ->with('error', 'Tu cuenta aún no está activa. Completa el onboarding y espera la aprobación de Dirección.');
            }
        } elseif ($debeContactos) {
            $permitida = $ruta && in_array($ruta, $this->rutasSoloContactos, true);
            if (! $permitida) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'mensaje' => 'Debes registrar mínimo 2 contactos antes de usar el resto del portal.',
                    ], 403);
                }

                return redirect()
                    ->route('proveedores.perfil')
                    ->with('error_contacto', 'Debes registrar mínimo 2 contactos antes de usar el resto del portal.');
            }
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /** Activo pero sin 2 contactos, y ya pasó por formulario de alta (no espejos ADMIN). */
    private function debeCompletarContactos(?ProveedorUser $proveedor): bool
    {
        if (! $proveedor || ! $proveedor->activo) {
            return false;
        }
        if ($proveedor->contactosSuficientes()) {
            return false;
        }

        $codigo = (string) ($proveedor->id_proveedor ?? '');
        if (str_starts_with($codigo, 'ADMIN-')) {
            return false;
        }

        return $proveedor->tieneFormularioIdentificacion()
            || $proveedor->tieneFormularioDatosBancarios();
    }
}
