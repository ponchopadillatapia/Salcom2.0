<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\BitacoraGasolina;
use Illuminate\Console\Command;

class MigrarBitacoraGasolina extends Command
{
    protected $signature = 'bitacora:migrar';

    protected $description = 'Copia los registros de gasolina de la tabla alertas (JSON) a la tabla bitacoras_gasolina. No borra los originales.';

    public function handle(): int
    {
        $viejos = Alerta::where('tipo', 'bitacora_gasolina')->orderBy('created_at')->get();
        $this->info("Registros encontrados en alertas: {$viejos->count()}");

        $copiados = 0;
        $saltados = 0;

        foreach ($viejos as $a) {
            $d = is_array($a->datos) ? $a->datos : [];

            // POR QUÉ: evitamos duplicar si se corre el comando dos veces. Comparamos por un conjunto
            // de campos que juntos identifican el registro (empleado + fecha + monto + created_at original).
            $fecha = $d['fecha'] ?? ($a->created_at ? $a->created_at->format('Y-m-d') : now()->format('Y-m-d'));
            $monto = (float) str_replace(['$', ','], '', (string) ($d['monto'] ?? '0'));

            $yaExiste = BitacoraGasolina::where('numero_empleado', $d['numero_empleado'] ?? null)
                ->where('fecha', $fecha)
                ->where('monto', $monto)
                ->where('empleado', $d['empleado'] ?? '')
                ->exists();

            if ($yaExiste) {
                $saltados++;
                continue;
            }

            $nuevo = BitacoraGasolina::create([
                'fecha' => $fecha,
                'numero_empleado' => $d['numero_empleado'] ?? null,
                'empleado' => $d['empleado'] ?? 'Sin nombre',
                'cantidad_litros' => $d['cantidad_litros'] ?? null,
                'rendimiento' => $d['rendimiento'] ?? null,
                'monto' => $monto,
                'vehiculo' => $d['vehiculo'] ?? null,
                'kilometraje' => $d['kilometraje'] ?? null,
                'notas' => $d['notas'] ?? null,
                'factura' => $d['factura'] ?? null,
                'origen' => 'migrado',
            ]);

            // Conservamos la fecha de creación original para no alterar el histórico.
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
