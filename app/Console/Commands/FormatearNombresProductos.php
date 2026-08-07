<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Services\IaService;
use Illuminate\Console\Command;

class FormatearNombresProductos extends Command
{
    protected $signature = 'productos:formatear {--limit=50 : Cantidad de productos a procesar} {--offset=0 : Desde qué producto empezar} {--categoria= : Filtrar por categoría} {--dry-run : Solo muestra sin guardar}';

    protected $description = 'Reformatear nombres al formato TIPO MARCA MODELO MEDIDA ESPECIFICACION (sin cambiar casing ni inventar palabras)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $dryRun = $this->option('dry-run');
        $categoria = $this->option('categoria');

        $query = Producto::whereNull('deleted_at')->orderBy('id');
        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        $productos = $query->offset($offset)->limit($limit)->get();

        $this->info("Procesando {$productos->count()} productos (offset: {$offset}, limit: {$limit})");
        if ($dryRun) {
            $this->info('*** DRY RUN - no se guardará nada ***');
        }

        $iaService = new IaService;
        $lotes = $productos->chunk(20);
        $procesados = 0;
        $errores = 0;

        foreach ($lotes as $lote) {
            $productosTexto = '';
            $ids = [];
            foreach ($lote as $prod) {
                $productosTexto .= "{$prod->id}. {$prod->nombre}\n";
                $ids[] = $prod->id;
            }

            $prompt = "Reordena cada nombre de producto al formato: TIPO MARCA MODELO MEDIDA ESPECIFICACION.

FORMATO FINAL: TIPO + MARCA + MODELO + MEDIDA + ESPECIFICACION (concatenados con espacio)

CAMPOS:
- TIPO = Que es (Aerosol, Abrillantador de Muebles, LUSTRADOR, PC, SWITCH, etc.)
- MARCA = Fabricante (WIESE, NILODOR, DELL, GREAT VALUE, etc.)
- MODELO = Referencia alfanumerica del fabricante (ej: OPTIPLEX 7020, TW-680)
- MEDIDA = Peso/volumen/presentacion con numeros (ej: 9.5OZ C/12, 323G C/6, 16GB)
- ESPECIFICACION = Aroma/color/detalle extra + codigos del fabricante al final

REGLAS ESTRICTAS:
- RESPETA mayusculas y minusculas EXACTAS del original (no fuerces MAYUSCULAS)
- NO agregar palabras que no esten en el original
- NO omitir letras, palabras ni identificadores (ej: HIPOCHICLE, version2, b/p)
- NO corregir typos ni “arreglar” nombres (si dice HIPOCHICLE dejalo HIPOCHICLE; NUNCA lo cambies a hipoclorito u otro)
- NO traducir
- Solo reordena tokens del original
- Si el nombre ya esta bien ordenado, dejalo igual con el mismo casing
- Quitar solo un slash suelto al final si existe

PRODUCTOS:
{$productosTexto}

Responde UNICAMENTE JSON valido sin markdown:
{\"productos\": [{\"id\": 36301, \"nombre\": \"Nombre Formateado\"}]}";

            $resultado = $iaService->llamarClaude($prompt);

            if ($resultado['success'] && $resultado['content']) {
                $contenido = $resultado['content'];
                $contenido = preg_replace('/```json\s*/s', '', $contenido);
                $contenido = preg_replace('/```\s*/s', '', $contenido);
                $contenido = trim($contenido);

                // Remove any BOM or invisible chars
                $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

                $iaResult = json_decode($contenido, true);

                // If failed, try extracting JSON block
                if (! $iaResult) {
                    if (preg_match('/\{.*\}/s', $contenido, $matches)) {
                        $iaResult = json_decode($matches[0], true);
                    }
                }

                if (! $iaResult) {
                    $this->error('JSON error: '.json_last_error_msg());
                    $this->error('Primeros 200: '.substr($contenido, 0, 200));
                }

                if ($iaResult && isset($iaResult['productos'])) {
                    foreach ($iaResult['productos'] as $item) {
                        $id = (int) ($item['id'] ?? 0);
                        $nuevoNombre = trim($item['nombre'] ?? '');
                        if ($id && $nuevoNombre) {
                            $original = $lote->firstWhere('id', $id);
                            // Rechaza si la IA inventó/omitió tokens (p.ej. hipochicle→hipoclorito)
                            if ($original && ! $this->mismosTokens((string) $original->nombre, $nuevoNombre)) {
                                $this->warn("  [{$id}] rechazado: tokens no coinciden con el original");
                                $errores++;

                                continue;
                            }
                            if (! $dryRun) {
                                Producto::where('id', $id)->update(['nombre' => $nuevoNombre]);
                            }
                            $procesados++;
                            if ($dryRun) {
                                $original = $lote->firstWhere('id', $id);
                                if ($original) {
                                    $this->line("  [{$id}] {$original->nombre}");
                                    $this->info("     -> {$nuevoNombre}");
                                } else {
                                    $this->info("     -> [{$id}] {$nuevoNombre}");
                                }
                            }
                        }
                    }
                } else {
                    $errores += $lote->count();
                    $failedIds = $lote->pluck('id')->toArray();
                    file_put_contents(storage_path('app/productos_fallidos.txt'), implode("\n", $failedIds)."\n", FILE_APPEND);
                    $this->error('Lote no parseable. IDs guardados.');
                }
            } else {
                $errores += $lote->count();
                $failedIds = $lote->pluck('id')->toArray();
                file_put_contents(storage_path('app/productos_fallidos.txt'), implode("\n", $failedIds)."\n", FILE_APPEND);
                $this->error('Error IA: '.($resultado['error'] ?? 'desconocido'));
            }

            $this->info("Progreso: {$procesados} formateados, {$errores} errores");
        }

        $this->newLine();
        $this->info('=== RESULTADO ===');
        $this->info("Formateados: {$procesados}");
        $this->info("Errores: {$errores}");

        return 0;
    }

    private function mismosTokens(string $original, string $nuevo): bool
    {
        $norm = static function (string $t): array {
            $t = trim(preg_replace('/\s+/u', ' ', $t) ?? $t);
            if ($t === '') {
                return [];
            }
            preg_match_all('/\S+/u', $t, $m);
            $tokens = array_map(fn ($x) => mb_strtolower($x), $m[0]);
            sort($tokens);

            return $tokens;
        };

        return $norm($original) === $norm($nuevo);
    }
}
