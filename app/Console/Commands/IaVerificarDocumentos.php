<?php

namespace App\Console\Commands;

use App\Models\DocumentoProveedor;
use App\Models\ProveedorUser;
use App\Services\AlertEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Verifica vencimiento de documentos fiscales de proveedores.
 * - 7 días antes: alerta al proveedor
 * - 3 días antes: alerta urgente al proveedor + admin
 * - Vencido: alerta crítica al admin
 *
 * Se ejecuta diariamente a las 07:00
 */
class IaVerificarDocumentos extends Command
{
    protected $signature = 'ia:verificar-documentos';

    protected $description = 'Verifica vencimiento de documentos fiscales y envía alertas';

    private AlertEngineService $alertEngine;

    public function __construct()
    {
        parent::__construct();
        $this->alertEngine = new AlertEngineService;
    }

    public function handle(): int
    {
        $this->info('🔍 Verificando documentos fiscales...');

        $diasAlerta = $this->alertEngine->getDiasAlertaDocumento();
        $diasUrgente = $this->alertEngine->getDiasUrgenteDocumento();
        $hoy = now();

        $proveedores = ProveedorUser::where('activo', true)->get();
        $alertasGeneradas = 0;

        foreach ($proveedores as $proveedor) {
            $documentos = DocumentoProveedor::where('proveedor_id', $proveedor->id)->get();

            $docsPorVencer = [];

            foreach ($documentos as $doc) {
                // Calcular fecha de vencimiento (fecha de carga + 30 días)
                $fechaVencimiento = $doc->created_at->addDays(30);
                $diasRestantes = $hoy->diffInDays($fechaVencimiento, false);

                // Ya venció
                if ($diasRestantes < 0) {
                    if (! $this->alertEngine->existeAlertaActiva('documento_vencido', 'admin', 1)) {
                        $this->alertEngine->alertar([
                            'tipo' => 'documento_vencido',
                            'modulo' => 'fiscal',
                            'destinatario_tipo' => 'admin',
                            'destinatario_id' => 1,
                            'titulo' => "Documento vencido: {$proveedor->nombre}",
                            'contenido' => "El documento '{$this->tipoDocLabel($doc->tipo)}' del proveedor {$proveedor->nombre} venció hace ".abs($diasRestantes).' días.',
                            'datos' => [
                                'proveedor_id' => $proveedor->id,
                                'proveedor_nombre' => $proveedor->nombre,
                                'documento_tipo' => $doc->tipo,
                                'fecha_vencimiento' => $fechaVencimiento->format('d/m/Y'),
                                'dias_vencido' => abs($diasRestantes),
                            ],
                            'nivel' => 'critical',
                        ]);
                        $alertasGeneradas++;
                    }
                }
                // Vence en 3 días (urgente)
                elseif ($diasRestantes <= $diasUrgente && $diasRestantes > 0) {
                    $docsPorVencer[] = $doc;

                    if (! $this->alertEngine->existeAlertaActiva('documento_urgente', 'proveedor', $proveedor->id)) {
                        $this->alertEngine->alertar([
                            'tipo' => 'documento_urgente',
                            'modulo' => 'fiscal',
                            'destinatario_tipo' => 'proveedor',
                            'destinatario_id' => $proveedor->id,
                            'titulo' => "⚠️ URGENTE: Tu {$this->tipoDocLabel($doc->tipo)} vence en {$diasRestantes} días",
                            'contenido' => "Tu documento '{$this->tipoDocLabel($doc->tipo)}' vence el {$fechaVencimiento->format('d/m/Y')}. Renuévalo HOY para evitar bloqueo de tu cuenta.",
                            'datos' => [
                                'proveedor_id' => $proveedor->id,
                                'documento_tipo' => $doc->tipo,
                                'fecha_vencimiento' => $fechaVencimiento->format('d/m/Y'),
                                'dias_restantes' => $diasRestantes,
                            ],
                            'nivel' => 'warning',
                        ]);

                        // Copia al admin
                        $this->alertEngine->alertar([
                            'tipo' => 'documento_urgente',
                            'modulo' => 'fiscal',
                            'destinatario_tipo' => 'admin',
                            'destinatario_id' => 1,
                            'titulo' => "Documento urgente: {$proveedor->nombre} - {$this->tipoDocLabel($doc->tipo)}",
                            'contenido' => "El proveedor {$proveedor->nombre} tiene el documento '{$this->tipoDocLabel($doc->tipo)}' por vencer en {$diasRestantes} días.",
                            'datos' => [
                                'proveedor_id' => $proveedor->id,
                                'proveedor_nombre' => $proveedor->nombre,
                                'documento_tipo' => $doc->tipo,
                                'dias_restantes' => $diasRestantes,
                            ],
                            'nivel' => 'warning',
                        ]);
                        $alertasGeneradas += 2;
                    }
                }
                // Vence en 7 días (primera alerta)
                elseif ($diasRestantes <= $diasAlerta && $diasRestantes > $diasUrgente) {
                    $docsPorVencer[] = $doc;

                    if (! $this->alertEngine->existeAlertaActiva('documento_por_vencer', 'proveedor', $proveedor->id)) {
                        $this->alertEngine->alertar([
                            'tipo' => 'documento_por_vencer',
                            'modulo' => 'fiscal',
                            'destinatario_tipo' => 'proveedor',
                            'destinatario_id' => $proveedor->id,
                            'titulo' => "📋 Tu {$this->tipoDocLabel($doc->tipo)} vence en {$diasRestantes} días",
                            'contenido' => "Recuerda renovar tu documento '{$this->tipoDocLabel($doc->tipo)}' antes del {$fechaVencimiento->format('d/m/Y')}. Puedes subirlo desde la sección Fiscal de tu portal.",
                            'datos' => [
                                'proveedor_id' => $proveedor->id,
                                'documento_tipo' => $doc->tipo,
                                'fecha_vencimiento' => $fechaVencimiento->format('d/m/Y'),
                                'dias_restantes' => $diasRestantes,
                            ],
                            'nivel' => 'info',
                        ]);
                        $alertasGeneradas++;
                    }
                }
            }

            // Consolidar si tiene 3+ documentos por vencer en la misma semana
            if (count($docsPorVencer) >= 3) {
                $tipos = collect($docsPorVencer)->pluck('tipo')->map(fn ($t) => $this->tipoDocLabel($t))->join(', ');

                $this->alertEngine->alertar([
                    'tipo' => 'documentos_multiples_vencer',
                    'modulo' => 'fiscal',
                    'destinatario_tipo' => 'proveedor',
                    'destinatario_id' => $proveedor->id,
                    'titulo' => '⚠️ Tienes '.count($docsPorVencer).' documentos por vencer esta semana',
                    'contenido' => "Los siguientes documentos requieren renovación: {$tipos}. Accede a la sección Fiscal para actualizarlos.",
                    'datos' => [
                        'proveedor_id' => $proveedor->id,
                        'documentos' => collect($docsPorVencer)->pluck('tipo')->toArray(),
                        'total' => count($docsPorVencer),
                    ],
                    'nivel' => 'warning',
                ]);
                $alertasGeneradas++;
            }
        }

        $this->info("✅ Verificación completada. {$alertasGeneradas} alertas generadas.");
        Log::info("[ia:verificar-documentos] Completado. Alertas: {$alertasGeneradas}");

        return Command::SUCCESS;
    }

    /**
     * Convertir tipo de documento a label legible.
     */
    private function tipoDocLabel(string $tipo): string
    {
        return match ($tipo) {
            'cif' => 'Constancia de Situación Fiscal',
            'opinion' => 'Opinión de cumplimiento SAT',
            'acta' => 'Acta constitutiva',
            'rep_legal' => 'INE Representante legal',
            'contribuyente' => 'Cédula de contribuyente',
            'caratula_banco' => 'Carátula bancaria',
            default => ucfirst(str_replace('_', ' ', $tipo)),
        };
    }
}
