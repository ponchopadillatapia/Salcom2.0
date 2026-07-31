<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Services\IaService;
use Illuminate\Console\Command;

class FormatearFallidos extends Command
{
    protected $signature = 'productos:formatear-fallidos';

    protected $description = 'Reprocesar solo los productos que fallaron (IDs en storage/app/productos_fallidos.txt)';

    public function handle(): int
    {
        $file = storage_path('app/productos_fallidos.txt');
        if (! file_exists($file)) {
            $this->error('No hay archivo de fallidos.');

            return 1;
        }

        $ids = array_filter(array_map('trim', file($file)));
        $ids = array_map('intval', $ids);
        $ids = array_unique($ids);

        $this->info('Reprocesando '.count($ids).' productos fallidos...');

        $productos = Producto::whereIn('id', $ids)->get();
        $iaService = new IaService;
        $lotes = $productos->chunk(20);
        $procesados = 0;
        $errores = 0;
        $nuevosFallidos = [];

        foreach ($lotes as $lote) {
            $productosTexto = '';
            foreach ($lote as $prod) {
                $productosTexto .= "{$prod->id}. {$prod->nombre}\n";
            }

            $prompt = "Reordena cada nombre de producto al formato: TIPO MARCA MODELO MEDIDA ESPECIFICACION.

FORMATO FINAL: TIPO + MARCA + MODELO + MEDIDA + ESPECIFICACION (concatenados con espacio)

CAMPOS:
- TIPO = Que es
- MARCA = Fabricante
- MODELO = Referencia alfanumerica del fabricante
- MEDIDA = Peso/volumen/presentacion con numeros
- ESPECIFICACION = Aroma/color/detalle extra + codigos

REGLAS ESTRICTAS:
- RESPETA mayusculas y minusculas EXACTAS del original
- NO agregar ni omitir palabras/letras/identificadores
- NO corregir typos (HIPOCHICLE se queda HIPOCHICLE)
- NO traducir
- Solo reordena tokens del original

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
                $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

                $iaResult = json_decode($contenido, true);
                if (! $iaResult && preg_match('/\{.*\}/s', $contenido, $matches)) {
                    $iaResult = json_decode($matches[0], true);
                }

                if ($iaResult && isset($iaResult['productos'])) {
                    foreach ($iaResult['productos'] as $item) {
                        $id = (int) ($item['id'] ?? 0);
                        $nuevoNombre = trim($item['nombre'] ?? '');
                        $original = $lote->firstWhere('id', $id);
                        if ($id && $nuevoNombre && $original && $this->mismosTokens((string) $original->nombre, $nuevoNombre)) {
                            Producto::where('id', $id)->update(['nombre' => $nuevoNombre]);
                            $procesados++;
                        } elseif ($id) {
                            $errores++;
                            $nuevosFallidos[] = $id;
                        }
                    }
                } else {
                    $errores += $lote->count();
                    foreach ($lote as $p) {
                        $nuevosFallidos[] = $p->id;
                    }
                }
            } else {
                $errores += $lote->count();
                foreach ($lote as $p) {
                    $nuevosFallidos[] = $p->id;
                }
            }

            $this->info("Progreso: {$procesados} OK, {$errores} errores");
        }

        if (! empty($nuevosFallidos)) {
            file_put_contents($file, implode("\n", array_unique($nuevosFallidos)));
            $this->warn('Quedan '.count(array_unique($nuevosFallidos)).' que siguen fallando.');
        } else {
            unlink($file);
            $this->info('Todos procesados. Archivo de fallidos eliminado.');
        }

        $this->info("=== RESULTADO: {$procesados} formateados, {$errores} errores ===");

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
            $tokens = array_map(fn ($x) => mb_strtolower($x), $m[0] ?? []);
            sort($tokens);

            return $tokens;
        };

        return $norm($original) === $norm($nuevo);
    }
}
