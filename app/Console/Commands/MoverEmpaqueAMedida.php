<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;

/**
 * Mueve CAJA/CJA/CAJITA/CJITA de tipo/marca/modelo/espec a NOMBRE_MEDIDA.
 * No inventa ni cambia casing; solo reacomoda tokens existentes.
 */
class MoverEmpaqueAMedida extends Command
{
    protected $signature = 'productos:mover-empaque-medida
        {--tipos=ME,MP : Tipos (coma-separados). ALL = todos}
        {--dry-run : Solo muestra, no escribe}';

    protected $description = 'Mueve CAJA/CJA/CAJITA/CJITA a NOMBRE_MEDIDA y reconstruye el nombre';

    public function handle(): int
    {
        $tiposOpt = strtoupper(trim((string) $this->option('tipos')));
        $todos = in_array($tiposOpt, ['ALL', '*', 'TODOS'], true);
        $tipos = $todos ? [] : array_values(array_filter(array_map(
            fn ($t) => strtoupper(trim($t)),
            explode(',', $tiposOpt)
        )));
        $dryRun = (bool) $this->option('dry-run');

        $query = Producto::query()->whereNull('deleted_at')->orderBy('codigo');
        if (! $todos) {
            $query->whereIn('tipo_producto', $tipos);
        }

        $actualizados = 0;
        $ejemplos = [];

        $query->chunkById(300, function ($rows) use (&$actualizados, &$ejemplos, $dryRun) {
            foreach ($rows as $p) {
                $campos = [
                    'nombre_tipo' => $this->tokenizar((string) $p->nombre_tipo),
                    'nombre_marca' => $this->tokenizar((string) $p->nombre_marca),
                    'nombre_modelo' => $this->tokenizar((string) $p->nombre_modelo),
                    'nombre_medida' => $this->tokenizar((string) $p->nombre_medida),
                    'nombre_especificacion' => $this->tokenizar((string) $p->nombre_especificacion),
                ];

                $empaques = [];
                foreach (['nombre_tipo', 'nombre_marca', 'nombre_modelo', 'nombre_especificacion'] as $campo) {
                    $resto = [];
                    foreach ($campos[$campo] as $tok) {
                        if ($this->esEmpaque($tok)) {
                            $empaques[] = $tok;
                        } else {
                            $resto[] = $tok;
                        }
                    }
                    $campos[$campo] = $resto;
                }

                if ($empaques === []) {
                    continue;
                }

                $medida = $campos['nombre_medida'];
                foreach (array_reverse($empaques) as $e) {
                    $dup = false;
                    foreach ($medida as $m) {
                        if (mb_strtolower($m) === mb_strtolower($e)) {
                            $dup = true;
                            break;
                        }
                    }
                    if (! $dup) {
                        array_unshift($medida, $e);
                    }
                }
                $campos['nombre_medida'] = $medida;

                if ($campos['nombre_tipo'] === []) {
                    foreach (['nombre_marca', 'nombre_modelo', 'nombre_especificacion'] as $campo) {
                        if ($campos[$campo] !== []) {
                            $campos['nombre_tipo'][] = array_shift($campos[$campo]);
                            break;
                        }
                    }
                }

                $payload = [];
                foreach ($campos as $k => $toks) {
                    $payload[$k] = $toks === [] ? null : implode(' ', $toks);
                }
                $payload['nombre'] = trim(implode(' ', array_filter([
                    $payload['nombre_tipo'],
                    $payload['nombre_marca'],
                    $payload['nombre_modelo'],
                    $payload['nombre_medida'],
                    $payload['nombre_especificacion'],
                ])));

                if (! $dryRun) {
                    Producto::where('id', $p->id)->update($payload);
                }
                $actualizados++;
                if (count($ejemplos) < 10) {
                    $ejemplos[] = "[{$p->codigo}] {$p->nombre} => {$payload['nombre']} (MED={$payload['nombre_medida']})";
                }
            }
        });

        $this->info(($dryRun ? 'DRY RUN — ' : '')."Actualizados: {$actualizados}");
        foreach ($ejemplos as $e) {
            $this->line('  '.$e);
        }

        return 0;
    }

    /** @return list<string> */
    private function tokenizar(string $texto): array
    {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
        if ($texto === '') {
            return [];
        }
        preg_match_all('/\S+/u', $texto, $m);

        return $m[0];
    }

    private function esEmpaque(string $tok): bool
    {
        return (bool) preg_match('/^(CAJA|CJA|CAJITA|CJITA)$/iu', $tok);
    }
}
