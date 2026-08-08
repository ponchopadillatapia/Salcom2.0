<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Brenda:
 * - Chapalita empieza con CAJA/CAJITA/CJITA → nombre debe empezar igual.
 * - VERSION* / vN / BAJO PEDIDO → al final.
 */
class FixCajaInicioVersion extends Command
{
    protected $signature = 'productos:fix-caja-inicio-version
        {archivo? : Ruta a Productos Chapalita.xlsx}
        {--tipos=ME,MP : Tipos (ALL = todos)}
        {--dry-run : Solo muestra}';

    protected $description = 'Restaura CAJA/CAJITA/CJITA al inicio (si Chapalita) y mueve VERSION/BAJO PEDIDO al final';

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');

        $archivo = $this->argument('archivo')
            ?: 'C:/Users/IT/Desktop/productos salcom/Productos Chapalita.xlsx';
        if (! file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");

            return 1;
        }

        $tiposOpt = strtoupper(trim((string) $this->option('tipos')));
        $todos = in_array($tiposOpt, ['ALL', '*', 'TODOS'], true);
        $tipos = $todos ? [] : array_values(array_filter(array_map(
            fn ($t) => strtoupper(trim($t)),
            explode(',', $tiposOpt)
        )));
        $dry = (bool) $this->option('dry-run');

        $this->info('Cargando Chapalita...');
        $chap = $this->leerChapalita($archivo);

        $query = Producto::query()->whereNull('deleted_at')->orderBy('codigo');
        if (! $todos) {
            $query->whereIn('tipo_producto', $tipos);
        }

        $nCaja = 0;
        $nVer = 0;
        $skip = 0;

        $query->chunkById(250, function ($rows) use ($chap, $dry, &$nCaja, &$nVer, &$skip) {
            foreach ($rows as $p) {
                $codigo = (string) $p->codigo;
                $chapName = $chap[$codigo] ?? null;
                $fuente = ($chapName !== null && $chapName !== '') ? $chapName : trim((string) $p->nombre);
                if ($fuente === '') {
                    $skip++;

                    continue;
                }

                $empaqueInicio = (bool) preg_match('/^(CAJA|CAJITA|CJITA)(\s|$)/iu', $fuente);
                $tokens = $this->tokenizar($fuente);
                [$body, $cola] = $this->sacarCola($tokens);

                if (! $empaqueInicio) {
                    if ($cola === []) {
                        continue;
                    }
                    $fuenteBd = trim((string) $p->nombre);
                    $tokens = $this->tokenizar($fuenteBd);
                    [$body, $cola] = $this->sacarCola($tokens);
                    if ($cola === []) {
                        continue;
                    }

                    $payload = [
                        'nombre_tipo' => $p->nombre_tipo,
                        'nombre_marca' => $p->nombre_marca,
                        'nombre_modelo' => $p->nombre_modelo,
                        'nombre_medida' => $p->nombre_medida,
                        'nombre_especificacion' => $p->nombre_especificacion,
                    ];
                    foreach (['nombre_tipo', 'nombre_marca', 'nombre_modelo', 'nombre_medida', 'nombre_especificacion'] as $campo) {
                        $toks = $this->tokenizar((string) ($payload[$campo] ?? ''));
                        [$r] = $this->sacarCola($toks);
                        $payload[$campo] = $r === [] ? null : implode(' ', $r);
                    }
                    $esp = $this->tokenizar((string) ($payload['nombre_especificacion'] ?? ''));
                    foreach ($cola as $t) {
                        $esp[] = $t;
                    }
                    $payload['nombre_especificacion'] = $esp === [] ? null : implode(' ', $esp);
                    $payload['nombre'] = trim(implode(' ', array_merge($body, $cola)));

                    if (! $this->mismosTokens($fuenteBd, $payload['nombre'])) {
                        $skip++;

                        continue;
                    }

                    if (! $dry) {
                        Producto::where('id', $p->id)->update($payload);
                    }
                    $nVer++;

                    continue;
                }

                $payload = $this->partsDesdeTokens($body, $cola, true);
                if (! $this->mismosTokens($fuente, $payload['nombre'])) {
                    $skip++;

                    continue;
                }
                $first = $this->tokenizar($payload['nombre'])[0] ?? '';
                if (! preg_match('/^(CAJA|CAJITA|CJITA)$/iu', $first)) {
                    $skip++;

                    continue;
                }

                if (! $dry) {
                    Producto::where('id', $p->id)->update($payload);
                }
                $nCaja++;
                if ($cola !== []) {
                    $nVer++;
                }
            }
        });

        $this->info(($dry ? 'DRY RUN — ' : '')."CAJA/CAJITA/CJITA al inicio: {$nCaja}");
        $this->info(($dry ? 'DRY RUN — ' : '')."VERSION/BAJO PEDIDO al final: {$nVer}");
        $this->info("Omitidos: {$skip}");

        return 0;
    }

    /** @return array<string, string> */
    private function leerChapalita(string $archivo): array
    {
        $reader = IOFactory::createReaderForFile($archivo);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($archivo)->getActiveSheet();
        $out = [];
        for ($r = 2; $r <= $sheet->getHighestRow(); $r++) {
            $c = trim((string) $sheet->getCell([1, $r])->getValue());
            if ($c !== '') {
                $out[$c] = trim((string) $sheet->getCell([2, $r])->getValue());
            }
        }

        return $out;
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

    private function esVersionToken(string $tok): bool
    {
        return (bool) (preg_match('/version/iu', $tok)
            || preg_match('/^v\d+$/iu', $tok)
            || preg_match('/^ver\d+$/iu', $tok));
    }

    private function esMedidaToken(string $tok): bool
    {
        if (preg_match('/^\d+[.,]?\d*(PZS?|PZ|PCS|OZ|KG|GRS?|ML|LT|CM|MM|G)\.?$/iu', $tok)) {
            return true;
        }
        if (preg_match('/^\d+([\/\*xX]\d+)/u', $tok)) {
            return true;
        }
        if (preg_match('/^C\/\d+/iu', $tok)) {
            return true;
        }
        if (preg_match('/^\d+([.,]\d+)?$/u', $tok)) {
            return true;
        }
        $u = mb_strtoupper($tok);

        return in_array($u, ['PZA', 'PZAS', 'PZ', 'PCS', 'OZ', 'KG', 'GR', 'GRS', 'ML', 'CM', 'MM', 'G'], true);
    }

    /** @param  list<string>  $tokens
     * @return array{0: list<string>, 1: list<string>}
     */
    private function sacarCola(array $tokens): array
    {
        $cola = [];
        $resto = $tokens;
        $changed = true;
        while ($changed) {
            $changed = false;
            for ($i = 0; $i < count($resto) - 1; $i++) {
                if (mb_strtolower($resto[$i]) === 'bajo' && mb_strtolower($resto[$i + 1]) === 'pedido') {
                    $cola[] = $resto[$i];
                    $cola[] = $resto[$i + 1];
                    array_splice($resto, $i, 2);
                    $changed = true;
                    break;
                }
            }
        }
        $out = [];
        for ($i = 0; $i < count($resto); $i++) {
            $t = $resto[$i];
            if ($this->esVersionToken($t)) {
                $cola[] = $t;
                if (isset($resto[$i + 1]) && preg_match('/^\d+$/', $resto[$i + 1])) {
                    $cola[] = $resto[$i + 1];
                    $i++;
                }
            } else {
                $out[] = $t;
            }
        }

        return [$out, $cola];
    }

    private function mismosTokens(string $a, string $b): bool
    {
        $n = function (string $t): array {
            $x = array_map(fn ($z) => mb_strtolower($z), $this->tokenizar($t));
            sort($x);

            return $x;
        };

        return $n($a) === $n($b);
    }

    /** @param  list<string>  $body
     * @param  list<string>  $cola
     * @return array<string, string|null>
     */
    private function partsDesdeTokens(array $body, array $cola, bool $empaqueInicio): array
    {
        $tipo = [];
        $medida = [];
        $espec = [];
        foreach ($body as $idx => $t) {
            if ($empaqueInicio && $idx === 0) {
                $tipo[] = $t;

                continue;
            }
            if ($this->esMedidaToken($t)) {
                $medida[] = $t;

                continue;
            }
            if ($empaqueInicio && count($tipo) < 3 && $medida === [] && $espec === []) {
                $tipo[] = $t;

                continue;
            }
            if (! $empaqueInicio && count($tipo) < 3 && $medida === [] && $espec === []) {
                $tipo[] = $t;

                continue;
            }
            $espec[] = $t;
        }
        if ($tipo === [] && $espec !== []) {
            $tipo[] = array_shift($espec);
        }
        foreach ($cola as $t) {
            $espec[] = $t;
        }

        return [
            'nombre' => trim(implode(' ', array_merge($body, $cola))),
            'nombre_tipo' => implode(' ', $tipo) ?: null,
            'nombre_marca' => null,
            'nombre_modelo' => null,
            'nombre_medida' => implode(' ', $medida) ?: null,
            'nombre_especificacion' => implode(' ', $espec) ?: null,
        ];
    }
}
