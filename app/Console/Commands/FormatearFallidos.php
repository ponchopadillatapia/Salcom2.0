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
        if (!file_exists($file)) {
            $this->error("No hay archivo de fallidos.");
            return 1;
        }

        $ids = array_filter(array_map('trim', file($file)));
        $ids = array_map('intval', $ids);
        $ids = array_unique($ids);

        $this->info("Reprocesando " . count($ids) . " productos fallidos...");

        $productos = Producto::whereIn('id', $ids)->get();
        $iaService = new IaService();
        $lotes = $productos->chunk(20);
        $procesados = 0;
        $errores = 0;
        $nuevosFallidos = [];

        foreach ($lotes as $lote) {
            $productosTexto = "";
            foreach ($lote as $prod) {
                $productosTexto .= "{$prod->id}. {$prod->nombre}\n";
            }

            $prompt = "Reordena cada nombre de producto al formato: TIPO MARCA MODELO MEDIDA ESPECIFICACION. Todo en MAYUSCULAS.

FORMATO FINAL: TIPO + MARCA + MODELO + MEDIDA + ESPECIFICACION (concatenados con espacio)

CAMPOS:
- TIPO = Que es (AEROSOL, ABRILLANTADOR DE MUEBLES, LUSTRADOR, PC, SWITCH, etc.)
- MARCA = Fabricante (WIESE, NILODOR, DELL, GREAT VALUE, etc.)
- MODELO = Referencia alfanumerica del fabricante (ej: OPTIPLEX 7020, TW-680)
- MEDIDA = Peso/volumen/presentacion con numeros (ej: 9.5OZ C/12, 323G C/6, 16GB)
- ESPECIFICACION = Aroma/color/detalle extra + codigos del fabricante al final (ej: LEMON 15AEL, NARANJA, LIGHT GREY 3004450990NS)

REGLAS:
- TODO en MAYUSCULAS
- Si el nombre empieza con un codigo alfanumerico (ej: 15AEL, 3004450990NS, REM00260, 20012420), MOVERLO AL FINAL como parte de la especificacion
- NO agregar palabras que no esten en el original
- NO corregir typos
- NO traducir
- Si el nombre ya esta bien ordenado, dejarlo igual solo en mayusculas
- Quitar caracteres sueltos como / al final

PRODUCTOS:
{$productosTexto}

Responde UNICAMENTE JSON valido sin markdown:
{\"productos\": [{\"id\": 36301, \"nombre\": \"NOMBRE FORMATEADO\"}]}";

            $resultado = $iaService->llamarClaude($prompt);

            if ($resultado['success'] && $resultado['content']) {
                $contenido = $resultado['content'];
                $contenido = preg_replace('/```json\s*/s', '', $contenido);
                $contenido = preg_replace('/```\s*/s', '', $contenido);
                $contenido = trim($contenido);
                $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

                $iaResult = json_decode($contenido, true);
                if (!$iaResult && preg_match('/\{.*\}/s', $contenido, $matches)) {
                    $iaResult = json_decode($matches[0], true);
                }

                if ($iaResult && isset($iaResult['productos'])) {
                    foreach ($iaResult['productos'] as $item) {
                        $id = (int) ($item['id'] ?? 0);
                        $nuevoNombre = strtoupper(trim($item['nombre'] ?? ''));
                        if ($id && $nuevoNombre) {
                            Producto::where('id', $id)->update(['nombre' => $nuevoNombre]);
                            $procesados++;
                        }
                    }
                } else {
                    $errores += $lote->count();
                    foreach ($lote as $p) { $nuevosFallidos[] = $p->id; }
                }
            } else {
                $errores += $lote->count();
                foreach ($lote as $p) { $nuevosFallidos[] = $p->id; }
            }

            $this->info("Progreso: {$procesados} OK, {$errores} errores");
        }

        // Guardar los que siguen fallando
        if (!empty($nuevosFallidos)) {
            file_put_contents($file, implode("\n", $nuevosFallidos));
            $this->warn("Quedan " . count($nuevosFallidos) . " que siguen fallando.");
        } else {
            unlink($file);
            $this->info("Todos procesados. Archivo de fallidos eliminado.");
        }

        $this->info("=== RESULTADO: {$procesados} formateados, {$errores} errores ===");
        return 0;
    }
}
