<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Reembolso;
use Illuminate\Console\Command;

class MigrarReembolsos extends Command
{
    protected $signature = 'reembolsos:migrar';

    protected $description = 'Copia los reembolsos de la tabla alertas (JSON) a la tabla reembolsos. No borra los originales.';

    public function handle(): int
    {
        $viejos = Alerta::where('tipo', 'solicitud_reembolso')->orderBy('created_at')->get();
        $this->info("Reembolsos encontrados en alertas: {$viejos->count()}");

        $copiados = 0;
        $saltados = 0;

        foreach ($viejos as $a) {
            $d = is_array($a->datos) ? $a->datos : [];

            $monto = (float) str_replace(['$', ','], '', (string) ($d['monto'] ?? '0'));
            $numEmp = $d['numero_empleado'] ?? null;
            $concepto = $d['concepto'] ?? ($a->contenido ?? 'Sin concepto');

            // POR QUÉ: evitamos duplicar si se corre dos veces. Identificamos por empleado + monto +
            // concepto + fecha de creación original.
            $yaExiste = Reembolso::where('numero_empleado', $numEmp)
                ->where('monto', $monto)
                ->where('concepto', $concepto)
                ->where('created_at', $a->created_at)
                ->exists();

            if ($yaExiste) {
                $saltados++;
                continue;
            }

            $nuevo = Reembolso::create([
                'categoria' => $d['categoria'] ?? 'gasto_general',
                'razon_social' => $d['razon_social'] ?? '',
                'metodo_pago_empresa' => $d['metodo_pago_empresa'] ?? '',
                'uso_cfdi' => $d['uso_cfdi'] ?? null,
                'forma_pago' => $d['forma_pago'] ?? null,
                'metodo_pago' => $d['metodo_pago'] ?? null,
                'monto' => $monto,
                'concepto' => $concepto,
                'solicitante' => $d['solicitante'] ?? 'Sin nombre',
                'numero_empleado' => $numEmp,
                'numero_cuenta' => $d['numero_cuenta'] ?? null,
                'titular_cuenta' => $d['titular_cuenta'] ?? null,
                'fecha_factura' => $d['fecha_factura'] ?? null,
                'archivo_factura' => $d['archivo_factura'] ?? null,
                'archivo_xml' => $d['archivo_xml'] ?? null,
                'archivo_materialidad' => $d['archivo_materialidad'] ?? null,
                'notas' => $d['notas'] ?? null,
                // La autorización vivía dentro del JSON; la pasamos a columnas reales.
                'autorizado' => ! empty($d['autorizado_sandra']),
                'autorizado_fecha' => $d['autorizado_sandra_fecha'] ?? null,
                'estatus' => $a->estatus ?? 'pendiente',
                'origen' => 'migrado',
            ]);

            // Conservamos las fechas originales para no alterar el histórico.
            if ($a->created_at) {
                $nuevo->created_at = $a->created_at;
                $nuevo->updated_at = $a->updated_at ?? $a->created_at;
                $nuevo->save();
            }

            $copiados++;
        }

        $this->info("Copiados: {$copiados} | Ya existían (saltados): {$saltados}");
        $this->info('Listo. Los registros originales en alertas NO se tocaron.');

        return self::SUCCESS;
    }
}
