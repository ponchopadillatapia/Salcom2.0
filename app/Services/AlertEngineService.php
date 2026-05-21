<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\Alerta;
use App\Models\AlertaConfiguracion;
use App\Models\ClienteUser;
use App\Models\ProveedorUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Motor de Alertas — Servicio central del sistema de IA proactiva.
 * Crea, envía y gestiona alertas para proveedores, clientes y admin.
 */
class AlertEngineService
{
    /**
     * Crear una alerta en el sistema.
     */
    public function crearAlerta(array $data): Alerta
    {
        $alerta = Alerta::create([
            'tipo' => $data['tipo'],
            'modulo' => $data['modulo'],
            'destinatario_tipo' => $data['destinatario_tipo'] ?? null,
            'destinatario_id' => $data['destinatario_id'] ?? null,
            'titulo' => $data['titulo'],
            'contenido' => $data['contenido'] ?? null,
            'datos' => $data['datos'] ?? null,
            'nivel' => $data['nivel'] ?? 'info',
            'estatus' => 'pendiente',
        ]);

        // Registrar en audit log
        AuditService::registrar(
            'alerta_generada',
            $data['modulo'],
            "Alerta [{$data['tipo']}]: {$data['titulo']}",
            nivel: $data['nivel'] ?? 'info'
        );

        Log::info("[AlertEngine] Alerta creada: {$data['tipo']} - {$data['titulo']}");

        return $alerta;
    }

    /**
     * Enviar una alerta por el canal configurado.
     */
    public function enviarAlerta(Alerta $alerta, ?string $canal = null): bool
    {
        $canal = $canal ?? 'portal';

        try {
            // Enviar por email si el destinatario tiene correo
            if ($canal === 'email' || $canal === 'portal') {
                $this->enviarEmail($alerta);
            }

            $alerta->update([
                'canal_enviado' => $canal,
                'estatus' => 'enviada',
            ]);

            Log::info("[AlertEngine] Alerta enviada por {$canal}: {$alerta->titulo}");

            return true;
        } catch (\Exception $e) {
            Log::error("[AlertEngine] Error enviando alerta: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Enviar alerta por email al destinatario.
     */
    private function enviarEmail(Alerta $alerta): void
    {
        $correo = null;

        if ($alerta->destinatario_tipo === 'proveedor' && $alerta->destinatario_id) {
            $prov = ProveedorUser::find($alerta->destinatario_id);
            $correo = $prov?->correo;
        } elseif ($alerta->destinatario_tipo === 'cliente' && $alerta->destinatario_id) {
            $cliente = ClienteUser::find($alerta->destinatario_id);
            $correo = $cliente?->correo;
        } elseif ($alerta->destinatario_tipo === 'admin') {
            $admin = AdminUser::find($alerta->destinatario_id ?? 1);
            $correo = $admin?->correo;
        }

        if ($correo) {
            try {
                Mail::raw(
                    $alerta->contenido ?? $alerta->titulo,
                    function ($message) use ($alerta, $correo) {
                        $message->to($correo)
                            ->subject('[Salcom IA] '.$alerta->titulo);
                    }
                );
                Log::info("[AlertEngine] Email enviado a {$correo}: {$alerta->titulo}");
            } catch (\Exception $e) {
                Log::warning("[AlertEngine] No se pudo enviar email a {$correo}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Crear y enviar alerta en un solo paso.
     */
    public function alertar(array $data, ?string $canal = null): Alerta
    {
        $alerta = $this->crearAlerta($data);
        $this->enviarAlerta($alerta, $canal);

        return $alerta;
    }

    /**
     * Obtener valor de configuración.
     */
    public function getConfig(string $clave, $default = null): mixed
    {
        return AlertaConfiguracion::get($clave, $default);
    }

    /**
     * Obtener umbral crítico de proveedor.
     */
    public function getUmbralCritico(): int
    {
        return (int) $this->getConfig('umbral_critico_proveedor', 60);
    }

    /**
     * Obtener DDI (días de inventario).
     */
    public function getDDI(): int
    {
        return (int) $this->getConfig('ddi_dias', 90);
    }

    /**
     * Obtener días de alerta para documentos.
     */
    public function getDiasAlertaDocumento(): int
    {
        return (int) $this->getConfig('dias_alerta_documento', 7);
    }

    /**
     * Obtener días de alerta urgente para documentos.
     */
    public function getDiasUrgenteDocumento(): int
    {
        return (int) $this->getConfig('dias_urgente_documento', 3);
    }

    /**
     * Verificar si ya existe una alerta activa del mismo tipo para el mismo destinatario.
     * Evita duplicados.
     */
    public function existeAlertaActiva(string $tipo, ?string $destTipo, ?int $destId): bool
    {
        return Alerta::where('tipo', $tipo)
            ->where('destinatario_tipo', $destTipo)
            ->where('destinatario_id', $destId)
            ->whereIn('estatus', ['pendiente', 'enviada'])
            ->where('created_at', '>=', now()->subDays(1))
            ->exists();
    }
}
