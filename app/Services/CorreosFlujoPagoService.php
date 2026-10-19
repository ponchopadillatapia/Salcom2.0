<?php

namespace App\Services;

use App\Mail\NotificacionFlujoPago;
use App\Models\PagoProveedor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Centraliza los CORREOS AUTOMÁTICOS del flujo de pago (petición de Alexa).
 *
 * POR QUÉ un servicio aparte: los correos se disparan desde distintos puntos del flujo
 * (generar formato, autorizar, rechazar, marcar pagado). Tener la lógica en un solo lugar evita
 * repetir y facilita cambiar textos/destinatarios. Todos los envíos van en try/catch: si el correo
 * falla, NO debe tumbar la operación (el pago/autorización se guarda igual; solo se loguea).
 *
 * Los destinatarios se leen de config/correos_pago.php (editable sin tocar código).
 */
class CorreosFlujoPagoService
{
    /**
     * CORREO 1 — Un depa generó un formato para pago → avisar al AUTORIZADOR (Alexa).
     */
    public function formatoGenerado(PagoProveedor $pago): void
    {
        $para = trim((string) config('correos_pago.autorizador', ''));
        if ($para === '') {
            return;
        }

        $depto = PagoProveedor::DEPARTAMENTOS[$pago->departamento] ?? ($pago->departamento ?: '—');
        $monto = number_format((float) $pago->monto_total, 2);

        $this->enviar(
            $para,
            asunto: 'Nuevo formato para pago pendiente de autorizar',
            titulo: 'Formato para pago pendiente de autorizar',
            lineas: [
                "El departamento <strong>{$depto}</strong> generó un formato para pago.",
                "Lote <strong>#{$pago->id}</strong> · {$pago->num_facturas} factura(s) · total <strong>\${$monto}</strong>.",
                'Entra al expediente de pago para revisarlo y autorizar o rechazar.',
            ],
            urlBoton: $this->url('admin.pagos.expediente', $pago),
            textoBoton: 'Ver expediente',
        );
    }

    /**
     * CORREO 2 — Alexa AUTORIZÓ → avisar al PAGADOR (Karen) para que descargue el layout y pague.
     */
    public function formatoAutorizado(PagoProveedor $pago): void
    {
        $para = trim((string) config('correos_pago.pagador', ''));
        if ($para === '') {
            return;
        }

        $monto = number_format((float) $pago->monto_total, 2);

        $this->enviar(
            $para,
            asunto: 'Formato para pago AUTORIZADO — listo para pagar',
            titulo: 'Formato para pago autorizado',
            lineas: [
                "El lote <strong>#{$pago->id}</strong> ({$pago->num_facturas} factura(s), \${$monto}) fue autorizado.",
                'Ya puedes descargar el layout del banco desde el expediente y realizar el pago.',
            ],
            urlBoton: $this->url('admin.pagos.expediente', $pago),
            textoBoton: 'Ir al expediente',
        );
    }

    /**
     * CORREO 3 — Alexa RECHAZÓ → avisar al DEPARTAMENTO que generó el formato, con el motivo.
     */
    public function formatoRechazado(PagoProveedor $pago, ?string $motivo): void
    {
        $para = $this->correoDepartamento($pago->departamento);
        if ($para === '') {
            return;
        }

        $depto = PagoProveedor::DEPARTAMENTOS[$pago->departamento] ?? ($pago->departamento ?: '—');
        $motivoTxt = trim((string) $motivo) !== '' ? $motivo : 'No se indicó motivo.';

        $this->enviar(
            $para,
            asunto: 'Tu formato para pago fue RECHAZADO',
            titulo: 'Formato para pago rechazado',
            lineas: [
                "El formato para pago del departamento <strong>{$depto}</strong> (lote #{$pago->id}) fue rechazado.",
                "<strong>Motivo:</strong> {$motivoTxt}",
                'Las facturas volvieron a estar pendientes. Corrige lo indicado y vuelve a generar el formato.',
            ],
        );
    }

    /**
     * CORREO 4 — Karen subió el comprobante del banco → facturas PAGADAS. Avisar a CADA
     * departamento dueño de las facturas pagadas.
     *
     * @param  array<string, array{num:int, monto:float}>  $porDepto  [depto => ['num'=>, 'monto'=>]]
     */
    public function facturasPagadas(PagoProveedor $pago, array $porDepto): void
    {
        foreach ($porDepto as $depto => $info) {
            $para = $this->correoDepartamento($depto);
            if ($para === '') {
                continue;
            }
            $nombreDepto = PagoProveedor::DEPARTAMENTOS[$depto] ?? ($depto ?: '—');
            $monto = number_format((float) ($info['monto'] ?? 0), 2);
            $num = (int) ($info['num'] ?? 0);

            $this->enviar(
                $para,
                asunto: 'Se realizó el pago de tus facturas',
                titulo: 'Pago realizado',
                lineas: [
                    "Se realizó el pago de <strong>{$num} factura(s)</strong> del departamento <strong>{$nombreDepto}</strong>.",
                    "Monto pagado: <strong>\${$monto}</strong>.",
                    'Ya puedes revisar el detalle en el portal de Salcom.',
                ],
            );
        }
    }

    // ── Helpers privados ──

    private function correoDepartamento(?string $clave): string
    {
        $mapa = (array) config('correos_pago.departamentos', []);

        return trim((string) ($mapa[$clave] ?? ''));
    }

    /** Genera una URL absoluta a una ruta del pago; si falla, devuelve null (sin botón). */
    private function url(string $ruta, PagoProveedor $pago): ?string
    {
        try {
            return route($ruta, $pago);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $lineas
     */
    private function enviar(string $para, string $asunto, string $titulo, array $lineas, ?string $urlBoton = null, ?string $textoBoton = null): void
    {
        try {
            Mail::to($para)->send(new NotificacionFlujoPago($asunto, $titulo, $lineas, $urlBoton, $textoBoton));
        } catch (\Throwable $e) {
            // POR QUÉ no relanzar: el correo es secundario; si falla, la operación de negocio ya
            // quedó guardada. Solo dejamos rastro para revisar la config de correo.
            Log::warning('[CorreosFlujoPago] No se pudo enviar correo', [
                'para' => $para,
                'asunto' => $asunto,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
