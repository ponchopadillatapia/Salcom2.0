<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Auditoría.
 * Registra acciones del sistema para trazabilidad y seguridad.
 *
 * Uso:
 *   AuditService::registrar('login', 'auth', 'Inicio de sesión exitoso');
 *   AuditService::registrar('editar', 'proveedores', 'Actualizó RFC', $antes, $despues);
 */
class AuditService
{
    /**
     * Registra una acción en el audit log.
     */
    public static function registrar(
        string $accion,
        string $modulo,
        string $descripcion,
        ?array $datosBefore = null,
        ?array $datosAfter = null,
        string $nivel = 'info'
    ): AuditLog {
        $request = request();

        // Detectar usuario actual desde la sesión
        $usuarioTipo = 'sistema';
        $usuarioId = null;
        $usuarioNombre = null;

        if (session('admin_id')) {
            $usuarioTipo = 'admin';
            $usuarioId = session('admin_id');
            $usuarioNombre = session('admin_nombre');
        } elseif (session('proveedor_id')) {
            $usuarioTipo = 'proveedor';
            $usuarioId = session('proveedor_id');
            $usuarioNombre = session('proveedor_nombre');
        } elseif (session('cliente_id')) {
            $usuarioTipo = 'cliente';
            $usuarioId = session('cliente_id');
            $usuarioNombre = session('cliente_nombre');
        }

        $audit = AuditLog::create([
            'accion' => $accion,
            'modulo' => $modulo,
            'usuario_tipo' => $usuarioTipo,
            'usuario_id' => $usuarioId,
            'usuario_nombre' => $usuarioNombre,
            'descripcion' => $descripcion,
            'datos_antes' => $datosBefore,
            'datos_despues' => $datosAfter,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'nivel' => $nivel,
        ]);

        // También registrar en el log de Laravel para redundancia
        $logMessage = "[AUDIT] [{$nivel}] {$usuarioTipo}:{$usuarioId} | {$accion} | {$modulo} | {$descripcion}";

        match ($nivel) {
            'critical' => Log::critical($logMessage),
            'error' => Log::error($logMessage),
            'warning' => Log::warning($logMessage),
            default => Log::info($logMessage),
        };

        return $audit;
    }

    /**
     * Registra un intento de login exitoso.
     */
    public static function loginExitoso(string $tipo, int $id, string $nombre): void
    {
        session(["temp_audit_{$tipo}_id" => $id, "temp_audit_{$tipo}_nombre" => $nombre]);

        self::registrar('login', 'auth', "Inicio de sesión exitoso: {$nombre} ({$tipo})");
    }

    /**
     * Registra un intento de login fallido.
     */
    public static function loginFallido(string $tipo, string $correo): void
    {
        self::registrar(
            'login_fallido',
            'auth',
            "Intento de login fallido para: {$correo} ({$tipo})",
            nivel: 'warning'
        );
    }

    /**
     * Registra cuando se activa el rate limiting.
     */
    public static function rateLimitActivado(string $tipo, string $correo): void
    {
        self::registrar(
            'rate_limit',
            'auth',
            "Rate limiting activado para: {$correo} ({$tipo})",
            nivel: 'warning'
        );
    }

    /**
     * Registra un logout.
     */
    public static function logout(string $tipo): void
    {
        self::registrar('logout', 'auth', "Cierre de sesión ({$tipo})");
    }

    /**
     * Registra creación de un registro.
     */
    public static function crear(string $modulo, string $descripcion, ?array $datos = null): void
    {
        self::registrar('crear', $modulo, $descripcion, datosAfter: $datos);
    }

    /**
     * Registra edición de un registro.
     */
    public static function editar(string $modulo, string $descripcion, ?array $antes = null, ?array $despues = null): void
    {
        self::registrar('editar', $modulo, $descripcion, $antes, $despues);
    }

    /**
     * Registra eliminación de un registro.
     */
    public static function eliminar(string $modulo, string $descripcion, ?array $datos = null): void
    {
        self::registrar('eliminar', $modulo, $descripcion, datosBefore: $datos, nivel: 'warning');
    }

    /**
     * Registra un evento de seguridad crítico.
     */
    public static function seguridadCritica(string $modulo, string $descripcion): void
    {
        self::registrar('seguridad', $modulo, $descripcion, nivel: 'critical');
    }
}
