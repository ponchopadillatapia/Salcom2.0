<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Services\IaService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Restaura descripciones originales desde Productos Chapalita.xlsx
 * y opcionalmente las ordena en TIPO/MARCA/MODELO/MEDIDA/ESPECIFICACION
 * SIN cambiar mayúsculas/minúsculas, SIN omitir tokens y SIN “corregir” nombres.
 */
class RestaurarDescripcionesChapalita extends Command
{
    protected $signature = 'productos:restaurar-descripciones
        {archivo? : Ruta a Productos Chapalita.xlsx (default: Desktop/productos salcom/...)}
        {--tipos=ME,MP : Tipos a restaurar (coma-separados). Usa ALL para todos}
        {--dry-run : Solo muestra cambios, no escribe}
        {--limit=0 : Procesar solo N productos (0 = todos)}
        {--offset=0 : Saltar N productos (para lotes)}
        {--con-ia : Separar en 5 campos con IA + validación de tokens}
        {--deterministico : Separar con reglas locales (sin IA; no inventa ni omite tokens)}
        {--chunk=10 : Tamaño de lote para IA}
        {--solo-sin-desglose : Solo productos sin marca/modelo/medida/espec desglosados}';

    protected $description = 'Restaura ItemName original de Chapalita en ME/MP (casing e identificadores intactos)';

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');

        $archivo = $this->argument('archivo')
            ?: 'C:/Users/IT/Desktop/productos salcom/Productos Chapalita.xlsx';
        $tiposOpt = strtoupper(trim((string) $this->option('tipos')));
        $todos = in_array($tiposOpt, ['ALL', '*', 'TODOS'], true);
        $tipos = $todos ? [] : array_values(array_filter(array_map(
            fn ($t) => strtoupper(trim($t)),
            explode(',', $tiposOpt)
        )));
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $conIa = (bool) $this->option('con-ia');
        $deterministico = (bool) $this->option('deterministico');
        $chunk = max(1, (int) $this->option('chunk'));
        $soloSinDesglose = (bool) $this->option('solo-sin-desglose');

        if (! file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");

            return 1;
        }

        $this->info('Leyendo Chapalita: '.$archivo);
        $origen = $this->leerChapalita($archivo);
        $this->info('Filas Chapalita con código: '.count($origen));

        $query = Producto::query()
            ->whereNull('deleted_at')
            ->orderBy('codigo');

        if (! $todos) {
            $query->whereIn('tipo_producto', $tipos);
        }

        if ($soloSinDesglose) {
            // Sin ningún campo de desglose lleno (aparte de nombre_tipo)
            $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('nombre_marca')->orWhere('nombre_marca', '');
                })->where(function ($q2) {
                    $q2->whereNull('nombre_modelo')->orWhere('nombre_modelo', '');
                })->where(function ($q2) {
                    $q2->whereNull('nombre_medida')->orWhere('nombre_medida', '');
                })->where(function ($q2) {
                    $q2->whereNull('nombre_especificacion')->orWhere('nombre_especificacion', '');
                });
            });
        }

        if ($offset > 0) {
            $query->offset($offset);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $productos = $query->get(['id', 'codigo', 'nombre', 'nombre_tipo', 'nombre_marca', 'nombre_modelo', 'nombre_medida', 'nombre_especificacion', 'tipo_producto']);
        $scopeLabel = $todos ? 'ALL' : implode(',', $tipos);
        $this->info("Productos en BD ({$scopeLabel}): ".$productos->count());
        if ($dryRun) {
            $this->warn('*** DRY RUN — no se guarda nada ***');
        }

        $ia = ($conIa && ! $deterministico) ? new IaService : null;
        $actualizados = 0;
        $sinOrigen = 0;
        $iguales = 0;
        $iaOk = 0;
        $iaFail = 0;
        $detOk = 0;
        $ejemplos = [];

        $pendientesIa = [];

        foreach ($productos as $prod) {
            $codigo = (string) $prod->codigo;
            $itemName = $origen[$codigo]['nombre'] ?? null;

            if ($itemName === null || $itemName === '') {
                // Si ya restauramos antes, usar el nombre actual de BD
                $itemName = trim((string) $prod->nombre);
                if ($itemName === '') {
                    $sinOrigen++;

                    continue;
                }
            }

            $nombreActual = trim((string) $prod->nombre);
            $nombreNuevo = $itemName; // casing exacto del Excel / BD

            $payload = [
                'nombre' => $nombreNuevo,
            ];

            // Sin IA ni determinístico: restaura nombre exacto.
            if (! $conIa && ! $deterministico) {
                $partsConcat = trim(implode(' ', array_filter([
                    trim((string) $prod->nombre_tipo),
                    trim((string) $prod->nombre_marca),
                    trim((string) $prod->nombre_modelo),
                    trim((string) $prod->nombre_medida),
                    trim((string) $prod->nombre_especificacion),
                ], fn ($v) => $v !== '')));
                $tieneExtras = trim((string) $prod->nombre_marca) !== ''
                    || trim((string) $prod->nombre_modelo) !== ''
                    || trim((string) $prod->nombre_medida) !== ''
                    || trim((string) $prod->nombre_especificacion) !== '';

                if ($tieneExtras && $this->mismosTokens($nombreNuevo, $partsConcat)) {
                    $payload['nombre'] = $nombreNuevo;
                } else {
                    $payload['nombre_tipo'] = $nombreNuevo;
                    $payload['nombre_marca'] = null;
                    $payload['nombre_modelo'] = null;
                    $payload['nombre_medida'] = null;
                    $payload['nombre_especificacion'] = null;
                }
            }

            $cambioNombre = $nombreActual !== $nombreNuevo;
            if (! $cambioNombre && ! $conIa && ! $deterministico) {
                $iguales++;
            }

            if ($conIa || $deterministico) {
                $pendientesIa[] = [
                    'producto' => $prod,
                    'item_name' => $nombreNuevo,
                    'grupo' => $origen[$codigo]['grupo'] ?? '',
                ];
            } else {
                if (! $dryRun) {
                    Producto::where('id', $prod->id)->update($payload);
                }
                $actualizados++;
                if (count($ejemplos) < 12 && $cambioNombre) {
                    $ejemplos[] = [
                        'codigo' => $codigo,
                        'antes' => $nombreActual,
                        'despues' => $nombreNuevo,
                    ];
                }
            }
        }

        if (($conIa || $deterministico) && $pendientesIa) {
            $modo = $deterministico ? 'determinístico (sin inventar tokens)' : 'IA + fallback determinístico';
            $this->info("Separando con {$modo}...");
            $bar = $this->output->createProgressBar(count($pendientesIa));
            $bar->start();

            $lotes = $deterministico
                ? array_chunk($pendientesIa, 50) // sin IA, lotes solo para progreso
                : array_chunk($pendientesIa, $chunk);

            foreach ($lotes as $lote) {
                $resultado = [];
                if ($conIa && ! $deterministico) {
                    $resultado = $this->separarConIa($ia, $lote);
                }

                foreach ($lote as $item) {
                    $bar->advance();
                    $prod = $item['producto'];
                    $original = $item['item_name'];
                    $codigo = (string) $prod->codigo;
                    $parts = $resultado[$codigo] ?? null;
                    $via = 'ia';

                    $payload = ['nombre' => $original];

                    if (! ($parts && $this->validarYRemapearPartes($original, $parts))) {
                        $parts = $this->separarDeterministico($original);
                        $via = 'det';
                    }

                    if ($parts && $this->validarYRemapearPartes($original, $parts)) {
                        $payload['nombre_tipo'] = $parts['nombre_tipo'] !== '' ? $parts['nombre_tipo'] : null;
                        $payload['nombre_marca'] = $parts['nombre_marca'] !== '' ? $parts['nombre_marca'] : null;
                        $payload['nombre_modelo'] = $parts['nombre_modelo'] !== '' ? $parts['nombre_modelo'] : null;
                        $payload['nombre_medida'] = $parts['nombre_medida'] !== '' ? $parts['nombre_medida'] : null;
                        $payload['nombre_especificacion'] = $parts['nombre_especificacion'] !== '' ? $parts['nombre_especificacion'] : null;
                        $payload['nombre'] = trim(implode(' ', array_filter([
                            $payload['nombre_tipo'],
                            $payload['nombre_marca'],
                            $payload['nombre_modelo'],
                            $payload['nombre_medida'],
                            $payload['nombre_especificacion'],
                        ])));
                        if ($via === 'ia') {
                            $iaOk++;
                        } else {
                            $detOk++;
                        }
                    } else {
                        // Último recurso imposible de omitir tokens: todo en tipo
                        $payload['nombre_tipo'] = $original;
                        $payload['nombre_marca'] = null;
                        $payload['nombre_modelo'] = null;
                        $payload['nombre_medida'] = null;
                        $payload['nombre_especificacion'] = null;
                        $iaFail++;
                    }

                    if (! $dryRun) {
                        Producto::where('id', $prod->id)->update($payload);
                    }
                    $actualizados++;

                    if (count($ejemplos) < 12) {
                        $ejemplos[] = [
                            'codigo' => $codigo,
                            'antes' => (string) $prod->nombre,
                            'despues' => ($payload['nombre_tipo'] ?? '').' | '.($payload['nombre_marca'] ?? '').' | '.($payload['nombre_modelo'] ?? '').' | '.($payload['nombre_medida'] ?? '').' | '.($payload['nombre_especificacion'] ?? ''),
                        ];
                    }
                }
            }
            $bar->finish();
            $this->newLine(2);
        }

        $this->newLine();
        $this->info('=== RESULTADO ===');
        $this->info('Actualizados: '.$actualizados);
        $this->info('Sin cambio de nombre: '.$iguales);
        $this->info('Sin fila/nombre: '.$sinOrigen);
        if ($conIa || $deterministico) {
            $this->info("IA OK: {$iaOk}");
            $this->info("Determinístico OK: {$detOk}");
            $this->info("Fallback duro (solo nombre_tipo): {$iaFail}");
        }

        if ($ejemplos) {
            $this->newLine();
            $this->info('Ejemplos de cambio:');
            foreach ($ejemplos as $ex) {
                $this->line("  [{$ex['codigo']}]");
                $this->line('    ANTES: '.$ex['antes']);
                $this->info('    AHORA: '.$ex['despues']);
            }
        }

        return 0;
    }

    /**
     * @return array<string, array{nombre: string, grupo: string}>
     */
    private function leerChapalita(string $archivo): array
    {
        $reader = IOFactory::createReaderForFile($archivo);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($archivo)->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $headers = [];
        for ($c = 1; $c <= 50; $c++) {
            $headers[$c] = strtoupper(trim((string) $sheet->getCell([$c, 1])->getValue()));
        }

        $colCode = array_search('ITEMCODE', $headers, true) ?: 1;
        $colName = array_search('ITEMNAME', $headers, true) ?: 2;
        $colGrupo = array_search('REFCODIGOGRUPOARTICULOS', $headers, true);

        $out = [];
        for ($r = 2; $r <= $highestRow; $r++) {
            $codigo = trim((string) $sheet->getCell([$colCode, $r])->getValue());
            if ($codigo === '') {
                continue;
            }
            $nombre = trim((string) $sheet->getCell([$colName, $r])->getValue());
            $grupo = $colGrupo ? trim((string) $sheet->getCell([$colGrupo, $r])->getValue()) : '';
            $out[$codigo] = [
                'nombre' => $nombre,
                'grupo' => $grupo,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array{producto: Producto, item_name: string, grupo: string}>  $lote
     * @return array<string, array{nombre_tipo: string, nombre_marca: string, nombre_modelo: string, nombre_medida: string, nombre_especificacion: string}>
     */
    private function separarConIa(IaService $ia, array $lote): array
    {
        $texto = '';
        $tokensPorCodigo = [];
        foreach ($lote as $item) {
            $codigo = (string) $item['producto']->codigo;
            $tokens = $this->tokenizar($item['item_name']);
            $tokensPorCodigo[$codigo] = $tokens;
            $lista = '';
            foreach ($tokens as $i => $tok) {
                $lista .= "{$i}:{$tok}\n";
            }
            $texto .= "CODIGO: {$codigo}\nGRUPO: {$item['grupo']}\nTOKENS (usa SOLO estos índices, todos exactamente una vez):\n{$lista}---\n";
        }

        $prompt = <<<PROMPT
Asigna CADA token (por índice) a UN campo del formato:
nombre_tipo / nombre_marca / nombre_modelo / nombre_medida / nombre_especificacion

Significado:
- nombre_tipo = qué ES el producto (BOBINA, CJA, ETIQ, Pastilla, FRAG, GAS, etc.)
- nombre_marca = fabricante/marca (WIESE, GV, AIROMA, etc.) si aparece
- nombre_modelo = referencia/código/modelo/grupo (MBP38505, UA-100E, etc.)
- nombre_medida = tamaño/cantidad con números (12 PZA, 60 GRS, 10oz, 5KG, C/12)
- nombre_especificacion = aroma/color/detalle/resto (HIPOCHICLE, b/p, version2, bajo volumen, etc.)

REGLAS:
1. Responde SOLO índices enteros de la lista TOKENS. NUNCA escribas texto nuevo.
2. Cada índice del 0 al N-1 debe aparecer EXACTAMENTE una vez en algún campo.
3. No inventes índices. No omitas índices.
4. Dentro de cada campo, pon los índices en el orden original (ascendente).
5. Si no hay marca/modelo/medida claros, deja ese array vacío [] y mueve esos tokens a tipo o especificacion.

PRODUCTOS:
{$texto}

Responde SOLO JSON válido sin markdown:
{"productos":[{"codigo":"ME0093","nombre_tipo":[2],"nombre_marca":[],"nombre_modelo":[3],"nombre_medida":[4,5],"nombre_especificacion":[0,1,6]}]}
PROMPT;

        $resultado = $ia->llamarClaude($prompt);
        if (! ($resultado['success'] ?? false) || empty($resultado['content'])) {
            return [];
        }

        $contenido = (string) $resultado['content'];
        $contenido = preg_replace('/```json\s*/s', '', $contenido) ?? $contenido;
        $contenido = preg_replace('/```\s*/s', '', $contenido) ?? $contenido;
        $contenido = trim($contenido);
        $json = json_decode($contenido, true);
        if (! is_array($json) && preg_match('/\{.*\}/s', $contenido, $m)) {
            $json = json_decode($m[0], true);
        }
        if (! is_array($json) || empty($json['productos']) || ! is_array($json['productos'])) {
            return [];
        }

        $map = [];
        foreach ($json['productos'] as $row) {
            $codigo = (string) ($row['codigo'] ?? '');
            if ($codigo === '' || ! isset($tokensPorCodigo[$codigo])) {
                continue;
            }
            $tokens = $tokensPorCodigo[$codigo];
            $camposIdx = [
                'nombre_tipo' => $row['nombre_tipo'] ?? $row['NOMBRE_TIPO'] ?? [],
                'nombre_marca' => $row['nombre_marca'] ?? $row['NOMBRE_MARCA'] ?? [],
                'nombre_modelo' => $row['nombre_modelo'] ?? $row['NOMBRE_MODELO'] ?? [],
                'nombre_medida' => $row['nombre_medida'] ?? $row['NOMBRE_MEDIDA'] ?? [],
                'nombre_especificacion' => $row['nombre_especificacion'] ?? $row['NOMBRE_ESPECIFICACION'] ?? [],
            ];

            $parts = $this->armarPartesDesdeIndices($tokens, $camposIdx);
            if ($parts !== null) {
                $map[$codigo] = $parts;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $camposIdx
     * @return array{nombre_tipo: string, nombre_marca: string, nombre_modelo: string, nombre_medida: string, nombre_especificacion: string}|null
     */
    private function armarPartesDesdeIndices(array $tokens, array $camposIdx): ?array
    {
        $n = count($tokens);
        $usados = [];
        $parts = [];

        foreach (['nombre_tipo', 'nombre_marca', 'nombre_modelo', 'nombre_medida', 'nombre_especificacion'] as $campo) {
            $idxs = $camposIdx[$campo] ?? [];
            if (! is_array($idxs)) {
                return null;
            }
            $idxs = array_map('intval', $idxs);
            sort($idxs);
            $palabras = [];
            foreach ($idxs as $i) {
                if ($i < 0 || $i >= $n || isset($usados[$i])) {
                    return null;
                }
                $usados[$i] = true;
                $palabras[] = $tokens[$i];
            }
            $parts[$campo] = implode(' ', $palabras);
        }

        if (count($usados) !== $n) {
            return null; // faltaron índices
        }

        return $parts;
    }

    /**
     * Valida que las partes no inventen/omitán tokens y remapea al casing del original.
     * (Fallback si la IA devolvió texto en vez de índices.)
     *
     * @param  array{nombre_tipo: string, nombre_marca: string, nombre_modelo: string, nombre_medida: string, nombre_especificacion: string}  $parts
     */
    private function validarYRemapearPartes(string $original, array &$parts): bool
    {
        $origTokens = $this->tokenizar($original);
        if ($origTokens === []) {
            return false;
        }

        $campos = ['nombre_tipo', 'nombre_marca', 'nombre_modelo', 'nombre_medida', 'nombre_especificacion'];
        $pool = $origTokens;

        foreach ($campos as $campo) {
            $raw = trim((string) ($parts[$campo] ?? ''));
            if ($raw === '') {
                $parts[$campo] = '';

                continue;
            }
            $tokensCampo = $this->tokenizar($raw);
            $reconstruido = [];
            foreach ($tokensCampo as $tok) {
                $idx = $this->buscarTokenInsensitive($pool, $tok);
                if ($idx === null) {
                    return false;
                }
                $reconstruido[] = $pool[$idx];
                array_splice($pool, $idx, 1);
            }
            $parts[$campo] = implode(' ', $reconstruido);
        }

        return $pool === [];
    }

    /**
     * @return list<string>
     */
    private function tokenizar(string $texto): array
    {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
        if ($texto === '') {
            return [];
        }
        preg_match_all('/\S+/u', $texto, $m);

        return $m[0] ?? [];
    }

    /**
     * Desglose local garantizado: usa TODOS los tokens del original, sin inventar ni cambiar casing.
     *
     * @return array{nombre_tipo: string, nombre_marca: string, nombre_modelo: string, nombre_medida: string, nombre_especificacion: string}
     */
    private function separarDeterministico(string $original): array
    {
        $tokens = $this->tokenizar($original);
        $medida = [];
        $otros = [];

        foreach ($tokens as $tok) {
            if ($this->esTokenMedida($tok)) {
                $medida[] = $tok;
            } else {
                $otros[] = $tok;
            }
        }

        $tipo = [];
        $marca = [];
        $modelo = [];
        $espec = [];

        foreach ($otros as $tok) {
            if ($marca === [] && $this->esMarcaConocida($tok)) {
                $marca[] = $tok;

                continue;
            }
            if ($this->esTokenModelo($tok) && $tipo !== []) {
                $modelo[] = $tok;

                continue;
            }
            if (count($tipo) < 3 && $modelo === [] && $espec === []) {
                $tipo[] = $tok;

                continue;
            }
            if ($tipo === []) {
                $tipo[] = $tok;
            } else {
                $espec[] = $tok;
            }
        }

        if ($tipo === [] && $espec !== []) {
            $tipo[] = array_shift($espec);
        }
        if ($tipo === [] && $modelo !== []) {
            $tipo[] = array_shift($modelo);
        }
        if ($tipo === [] && $marca !== []) {
            $tipo = $marca;
            $marca = [];
        }
        if ($tipo === [] && $medida !== []) {
            // Solo había medidas: la primera medida pasa a tipo para no dejar estructura vacía
            $tipo[] = array_shift($medida);
        }

        return [
            'nombre_tipo' => implode(' ', $tipo),
            'nombre_marca' => implode(' ', $marca),
            'nombre_modelo' => implode(' ', $modelo),
            'nombre_medida' => implode(' ', $medida),
            'nombre_especificacion' => implode(' ', $espec),
        ];
    }

    private function esTokenMedida(string $tok): bool
    {
        // Códigos alfanuméricos (ME1300, UA-100E) NO son medida
        if (preg_match('/[A-Za-z]/u', $tok) && preg_match('/\d/u', $tok)) {
            // Sí es medida si es número+unidad pegada: 12PZS, 323g, 10oz, 9.5OZ
            if (preg_match('/^\d+[.,]?\d*(PZS?|PZ|PCS|OZ|KG|GRS?|ML|LT|L|CM|MM|MT|G)\.?$/iu', $tok)) {
                return true;
            }
            // 12/12, 4*36, 40X30X25
            if (preg_match('/^\d+([\/\*xX]\d+)+([A-Za-z]+)?$/u', $tok)) {
                return true;
            }

            return false;
        }

        if (preg_match('/^\d+([.,]\d+)?%?$/u', $tok)) {
            return true;
        }
        if (preg_match('/^C\/\d+/iu', $tok)) {
            return true;
        }

        $u = mb_strtoupper($tok);

        return in_array($u, ['PZA', 'PZAS', 'PZ', 'PCS', 'OZ', 'KG', 'GR', 'GRS', 'ML', 'LT', 'L', 'CM', 'MM', 'MT', 'G', 'GRS.'], true);
    }

    private function esTokenModelo(string $tok): bool
    {
        if (preg_match('/[A-Za-z]/u', $tok) && preg_match('/\d/u', $tok)) {
            return ! $this->esTokenMedida($tok);
        }

        return (bool) preg_match('/^[A-Z]{1,6}-[A-Z0-9]+$/iu', $tok);
    }

    private function esMarcaConocida(string $tok): bool
    {
        static $marcas = null;
        if ($marcas === null) {
            $marcas = array_flip(array_map('mb_strtoupper', [
                'WIESE', 'GV', 'SELECTO', 'GREAT', 'VALUE', 'JAZZEE', 'NILODOR', 'NILOTRON',
                'AIROMA', 'SURESCENTS', 'SURE', 'SCENTS', 'HOMELINE', 'TRUELIVING', 'GLAMOUROSO',
                'VECTAIR', 'ALKOSTO', 'IMPEK', 'KRAFT', 'GENERICA', 'GENERIC', 'SKF', 'WEG',
                '3M', 'ALPHA', 'DELL', 'SAMSUNG', 'APPLE', 'LG', 'BOSCH', 'ABB', 'SIEMENS',
                'MASTER', 'BCA', 'ARMAR',
            ]));
        }

        return isset($marcas[mb_strtoupper($tok)]);
    }

    /**
     * @param  list<string>  $pool
     */
    private function buscarTokenInsensitive(array $pool, string $needle): ?int
    {
        $n = mb_strtolower($needle);
        foreach ($pool as $i => $tok) {
            if (mb_strtolower($tok) === $n) {
                return $i;
            }
        }

        return null;
    }

    private function mismosTokens(string $a, string $b): bool
    {
        $norm = function (string $t): array {
            $toks = $this->tokenizar($t);
            $toks = array_map(fn ($x) => mb_strtolower($x), $toks);
            sort($toks);

            return $toks;
        };

        return $norm($a) === $norm($b);
    }
}
