<?php

namespace App\Http\Middleware;

use App\Models\ProveedorUser;
use App\Support\PortalModulos;
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
        'proveedores.perfil.actualizar',
        'proveedores.perfil.solicitud-nombre',
        'proveedores.perfil.solicitud-nombre.enviar',
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
        'proveedores.confirmar-cuenta-wiese',
        'proveedores.logout',
        'proveedores.aviso.aceptar',
        'proveedores.alertas.recientes',
        'proveedores.alertas.leer',
        'aviso.privacidad',
    ];

    /**
     * Rutas permitidas para un proveedor EXTRANJERO.
     * POR QUÉ: su portal es independiente; NO puede ver facturas MX, OC, onboarding mexicano,
     * Wiese, identificación fiscal, módulo IA, etc. Solo su propio portal + utilidades comunes.
     */
    private array $rutasExtranjero = [
        'proveedores.foreign-portal',
        'proveedores.foreign-invoices',
        'proveedores.foreign-po',
        'proveedores.foreign-profile',
        'proveedores.foreign-onboarding',
        'proveedores.foreign-onboarding.profile',
        'proveedores.foreign-onboarding.security',
        'proveedores.foreign-onboarding.document',
        'proveedores.documento.archivo',
        // Comunes (compartidas sin problema)
        'proveedores.perfil.actualizar',
        'proveedores.logout',
        'proveedores.aviso.aceptar',
        'proveedores.alertas.recientes',
        'proveedores.alertas.leer',
        'proveedores.alertas.leer-todas',
        'aviso.privacidad',
    ];

    /** Rutas permitidas si ya está activo pero aún le faltan los 2 contactos. */
    private array $rutasSoloContactos = [
        'proveedores.onboarding',
        'proveedores.perfil',
        'proveedores.perfil.foto',
        'proveedores.perfil.actualizar',
        'proveedores.perfil.solicitud-nombre',
        'proveedores.perfil.solicitud-nombre.enviar',
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

        // POR QUÉ: los proveedores extranjeros (importación) ven todo el portal en inglés y los
        // nacionales en español. Fijamos el locale explícitamente (el default de la app es 'en',
        // así que NO basta con no tocar nada: hay que forzar 'es' para el nacional).
        app()->setLocale($proveedor && $proveedor->esExtranjero() ? 'en' : 'es');

        View::share('proveedorPortalActivo', $activo && ! $debeContactos);
        View::share('proveedorPortal', $proveedor);
        View::share('proveedorDebeContactos', $debeContactos);

        $ruta = optional($request->route())->getName();

        // AISLAMIENTO EXTRANJERO: su portal es independiente y NO pasa por las reglas mexicanas
        // (onboarding MX, 2 contactos, módulos). Si la ruta es suya → sigue; si no → a su portal.
        if ($proveedor && $proveedor->esExtranjero()) {
            if ($ruta && in_array($ruta, $this->rutasExtranjero, true)) {
                // Ruta válida del extranjero: salta toda la lógica nacional de abajo.
                $response = $next($request);
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');

                return $response;
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['mensaje' => 'Not available for your account.'], 403);
            }

            // Cualquier ruta nacional → de vuelta a su portal.
            return redirect()->route('proveedores.foreign-portal');
        }

        // Un nacional NO puede entrar a las rutas del portal extranjero.
        if ($proveedor && $ruta && str_starts_with($ruta, 'proveedores.foreign-')) {
            return redirect()->route('proveedores.portal');
        }

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
        } elseif (PortalModulos::rutaBloqueada($ruta)) {
            $mensaje = 'Este módulo aún no está disponible.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['mensaje' => $mensaje], 403);
            }

            return redirect()
                ->route(PortalModulos::destino())
                ->with('modulo_cerrado', $mensaje);
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
