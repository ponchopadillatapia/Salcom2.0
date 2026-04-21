<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IaService
{
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct()
    {
        $this->apiUrl  = config('services.anthropic.url', 'https://api.anthropic.com/v1/messages');
        $this->apiKey  = config('services.anthropic.api_key', '');
        $this->model   = config('services.anthropic.model', 'claude-sonnet-4-20250514');
        $this->timeout = config('services.anthropic.timeout', 60);
    }

    // ══════════════════════════════════════════════
    // 1. Pronóstico de demanda
    // ══════════════════════════════════════════════

    public function pronosticoDemanda(string $codigoCliente): array
    {
        $historial = $this->obtenerHistorialPedidos($codigoCliente);

        $prompt = $this->buildPrompt('pronostico_demanda', [
            'codigo_cliente' => $codigoCliente,
            'historial'      => json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);

        $resultado = $this->llamarClaude($prompt);

        return [
            'cliente'    => $codigoCliente,
            'historial'  => $historial,
            'analisis'   => $resultado,
            'generado'   => now()->toDateTimeString(),
        ];
    }

    // ══════════════════════════════════════════════
    // 2. Optimización de inventario
    // ══════════════════════════════════════════════

    public function optimizacionInventario(): array
    {
        $inventario = $this->obtenerInventarioActual();
        $demanda    = $this->obtenerDemandaProyectada();

        $prompt = $this->buildPrompt('optimizacion_inventario', [
            'inventario' => json_encode($inventario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'demanda'    => json_encode($demanda, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);

        $resultado = $this->llamarClaude($prompt);

        return [
            'inventario' => $inventario,
            'demanda'    => $demanda,
            'analisis'   => $resultado,
            'generado'   => now()->toDateTimeString(),
        ];
    }

    // ══════════════════════════════════════════════
    // 3. Selección de proveedor
    // ══════════════════════════════════════════════

    public function seleccionProveedor(string $productoId): array
    {
        $producto    = $this->obtenerProducto($productoId);
        $proveedores = $this->obtenerProveedoresProducto($productoId);

        $prompt = $this->buildPrompt('seleccion_proveedor', [
            'producto'    => json_encode($producto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'proveedores' => json_encode($proveedores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);

        $resultado = $this->llamarClaude($prompt);

        return [
            'producto'    => $producto,
            'proveedores' => $proveedores,
            'analisis'    => $resultado,
            'generado'    => now()->toDateTimeString(),
        ];
    }

    // ══════════════════════════════════════════════
    // Llamada a la API de Anthropic (Claude)
    // ══════════════════════════════════════════════

    public function llamarClaude(string $prompt): array
    {
        if (empty(trim($this->apiKey))) {
            Log::warning('IaService: API key de Anthropic no configurada');
            return [
                'success' => false,
                'content' => null,
                'error'   => 'La API key de Anthropic no está configurada. Agrega ANTHROPIC_API_KEY en tu .env',
            ];
        }

        try {
            $response = Http::asJson()
                ->timeout($this->timeout)
                ->withHeaders([
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version'  => '2023-06-01',
                ])
                ->post($this->apiUrl, [
                    'model'      => $this->model,
                    'max_tokens' => 4096,
                    'system'     => 'Eres un analista experto de Industrias Salcom, una empresa manufacturera mexicana. Responde siempre en español, de forma concisa y orientada a la acción.',
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $text = $body['content'][0]['text'] ?? '';

                return [
                    'success' => true,
                    'content' => $text,
                    'error'   => null,
                ];
            }

            Log::error('IaService: error de API Anthropic', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            $errorBody = $response->json();
            $errorMsg  = $errorBody['error']['message'] ?? null;

            return [
                'success' => false,
                'content' => null,
                'error'   => $errorMsg
                    ? 'Error de Claude: ' . $errorMsg
                    : 'Error de la API de Anthropic (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('IaService: excepción al llamar Anthropic', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'content' => null,
                'error'   => 'No se pudo conectar con la API de Anthropic: ' . $e->getMessage(),
            ];
        }
    }

    // ══════════════════════════════════════════════
    // Construcción de prompts
    // ══════════════════════════════════════════════

    private function buildPrompt(string $tipo, array $datos): string
    {
        return match ($tipo) {
            'pronostico_demanda' => <<<PROMPT
Analiza el historial de pedidos del cliente {$datos['codigo_cliente']} y genera un pronóstico de demanda para los próximos 3 meses.

HISTORIAL DE PEDIDOS:
{$datos['historial']}

Responde con formato estructurado:
1. **Resumen del patrón**: Describe la tendencia y estacionalidad detectada.
2. **Pronóstico mensual**: Tabla con mes, cantidad estimada y nivel de confianza.
3. **Productos clave**: Los 3 productos con mayor probabilidad de reorden.
4. **Recomendaciones**: Acciones concretas para el equipo de ventas y planeación.
5. **Riesgos**: Factores que podrían alterar el pronóstico.
PROMPT,

            'optimizacion_inventario' => <<<PROMPT
Analiza el inventario actual y la demanda proyectada para optimizar los niveles de stock.

INVENTARIO ACTUAL:
{$datos['inventario']}

DEMANDA PROYECTADA (próximos 3 meses):
{$datos['demanda']}

Responde con formato estructurado:
1. **Alertas críticas**: Productos en riesgo de desabasto (stock < 2 semanas de demanda).
2. **Punto de reorden**: Para cada producto, cuándo y cuánto reordenar (ROP y EOQ simplificado).
3. **Sobrestock**: Productos con exceso de inventario y recomendación.
4. **Ahorro estimado**: Impacto financiero de las optimizaciones propuestas.
5. **Plan de acción**: Prioridades para las próximas 2 semanas.
PROMPT,

            'seleccion_proveedor' => <<<PROMPT
Dado el siguiente producto y los proveedores disponibles, recomienda el mejor proveedor considerando costo, tiempo de entrega, calidad y confiabilidad.

PRODUCTO REQUERIDO:
{$datos['producto']}

PROVEEDORES DISPONIBLES:
{$datos['proveedores']}

Responde con formato estructurado:
1. **Recomendación principal**: Proveedor seleccionado y justificación.
2. **Comparativa**: Tabla comparativa de todos los proveedores (costo, tiempo, calidad, score).
3. **Proveedor alternativo**: Segunda opción en caso de que el principal no pueda cumplir.
4. **Negociación**: Puntos de negociación sugeridos para obtener mejor precio o condiciones.
5. **Riesgos**: Riesgos identificados con el proveedor seleccionado.
PROMPT,

            default => $datos['prompt'] ?? '',
        };
    }

    // ══════════════════════════════════════════════
    // Datos mockeados (reemplazar con API de Alan)
    // ══════════════════════════════════════════════

    public function obtenerHistorialPedidos(string $codigoCliente): array
    {
        return [
            ['pedido' => 'PED-2025-089', 'fecha' => '2025-11-15', 'productos' => [
                ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad' => 500, 'unidad' => 'kg', 'precio_unitario' => 85.00],
                ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'cantidad' => 200, 'unidad' => 'lt', 'precio_unitario' => 42.50],
            ], 'total' => 51_000.00],
            ['pedido' => 'PED-2025-102', 'fecha' => '2025-12-03', 'productos' => [
                ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad' => 600, 'unidad' => 'kg', 'precio_unitario' => 85.00],
                ['sku' => 'SAL-005', 'nombre' => 'Pigmento base agua', 'cantidad' => 100, 'unidad' => 'kg', 'precio_unitario' => 120.00],
            ], 'total' => 63_000.00],
            ['pedido' => 'PED-2026-008', 'fecha' => '2026-01-10', 'productos' => [
                ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad' => 550, 'unidad' => 'kg', 'precio_unitario' => 85.00],
                ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'cantidad' => 250, 'unidad' => 'lt', 'precio_unitario' => 42.50],
                ['sku' => 'SAL-005', 'nombre' => 'Pigmento base agua', 'cantidad' => 80, 'unidad' => 'kg', 'precio_unitario' => 120.00],
            ], 'total' => 67_975.00],
            ['pedido' => 'PED-2026-021', 'fecha' => '2026-02-05', 'productos' => [
                ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad' => 700, 'unidad' => 'kg', 'precio_unitario' => 85.00],
                ['sku' => 'SAL-007', 'nombre' => 'Catalizador rápido', 'cantidad' => 50, 'unidad' => 'kg', 'precio_unitario' => 210.00],
            ], 'total' => 70_000.00],
            ['pedido' => 'PED-2026-035', 'fecha' => '2026-03-12', 'productos' => [
                ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad' => 750, 'unidad' => 'kg', 'precio_unitario' => 85.00],
                ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'cantidad' => 300, 'unidad' => 'lt', 'precio_unitario' => 42.50],
                ['sku' => 'SAL-005', 'nombre' => 'Pigmento base agua', 'cantidad' => 120, 'unidad' => 'kg', 'precio_unitario' => 120.00],
            ], 'total' => 90_900.00],
            ['pedido' => 'PED-2026-048', 'fecha' => '2026-04-02', 'productos' => [
                ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad' => 800, 'unidad' => 'kg', 'precio_unitario' => 85.00],
                ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'cantidad' => 280, 'unidad' => 'lt', 'precio_unitario' => 42.50],
                ['sku' => 'SAL-007', 'nombre' => 'Catalizador rápido', 'cantidad' => 60, 'unidad' => 'kg', 'precio_unitario' => 210.00],
            ], 'total' => 92_500.00],
        ];
    }

    public function obtenerInventarioActual(): array
    {
        return [
            ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'stock_actual' => 1200, 'unidad' => 'kg', 'costo_unitario' => 65.00, 'ubicacion' => 'Almacén A-12'],
            ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'stock_actual' => 150, 'unidad' => 'lt', 'costo_unitario' => 32.00, 'ubicacion' => 'Almacén B-03'],
            ['sku' => 'SAL-005', 'nombre' => 'Pigmento base agua', 'stock_actual' => 300, 'unidad' => 'kg', 'costo_unitario' => 95.00, 'ubicacion' => 'Almacén A-07'],
            ['sku' => 'SAL-007', 'nombre' => 'Catalizador rápido', 'stock_actual' => 25, 'unidad' => 'kg', 'costo_unitario' => 170.00, 'ubicacion' => 'Almacén C-01'],
            ['sku' => 'SAL-009', 'nombre' => 'Aditivo antioxidante', 'stock_actual' => 500, 'unidad' => 'kg', 'costo_unitario' => 55.00, 'ubicacion' => 'Almacén A-15'],
            ['sku' => 'SAL-011', 'nombre' => 'Fibra de refuerzo', 'stock_actual' => 80, 'unidad' => 'rollo', 'costo_unitario' => 320.00, 'ubicacion' => 'Almacén D-02'],
        ];
    }

    public function obtenerDemandaProyectada(): array
    {
        return [
            ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'demanda_mensual' => 750, 'unidad' => 'kg', 'tendencia' => 'creciente'],
            ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'demanda_mensual' => 280, 'unidad' => 'lt', 'tendencia' => 'estable'],
            ['sku' => 'SAL-005', 'nombre' => 'Pigmento base agua', 'demanda_mensual' => 100, 'unidad' => 'kg', 'tendencia' => 'creciente'],
            ['sku' => 'SAL-007', 'nombre' => 'Catalizador rápido', 'demanda_mensual' => 55, 'unidad' => 'kg', 'tendencia' => 'creciente'],
            ['sku' => 'SAL-009', 'nombre' => 'Aditivo antioxidante', 'demanda_mensual' => 40, 'unidad' => 'kg', 'tendencia' => 'decreciente'],
            ['sku' => 'SAL-011', 'nombre' => 'Fibra de refuerzo', 'demanda_mensual' => 30, 'unidad' => 'rollo', 'tendencia' => 'estable'],
        ];
    }

    public function obtenerProducto(string $productoId): array
    {
        $productos = [
            'SAL-001' => ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad_requerida' => 800, 'unidad' => 'kg', 'especificaciones' => 'Viscosidad 12000-15000 cP, color transparente, vida útil mín. 12 meses'],
            'SAL-003' => ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'cantidad_requerida' => 300, 'unidad' => 'lt', 'especificaciones' => 'Pureza mín. 99.5%, punto de ebullición 76-78°C'],
            'SAL-007' => ['sku' => 'SAL-007', 'nombre' => 'Catalizador rápido', 'cantidad_requerida' => 60, 'unidad' => 'kg', 'especificaciones' => 'Tiempo de gel 8-12 min, temperatura de activación 25°C'],
        ];

        return $productos[$productoId] ?? $productos['SAL-001'];
    }

    public function obtenerProveedoresProducto(string $productoId): array
    {
        return [
            [
                'codigo' => 'PROV-101', 'nombre' => 'Químicos del Norte S.A.',
                'precio_unitario' => 62.50, 'moneda' => 'MXN',
                'tiempo_entrega_dias' => 5, 'moq' => 200,
                'calificacion' => 4.5, 'entregas_a_tiempo' => '94%',
                'ubicacion' => 'Monterrey, NL', 'certificaciones' => ['ISO 9001', 'ISO 14001'],
            ],
            [
                'codigo' => 'PROV-205', 'nombre' => 'Resinas Industriales MX',
                'precio_unitario' => 58.00, 'moneda' => 'MXN',
                'tiempo_entrega_dias' => 8, 'moq' => 500,
                'calificacion' => 4.2, 'entregas_a_tiempo' => '88%',
                'ubicacion' => 'Querétaro, QRO', 'certificaciones' => ['ISO 9001'],
            ],
            [
                'codigo' => 'PROV-312', 'nombre' => 'ChemSupply International',
                'precio_unitario' => 55.00, 'moneda' => 'MXN',
                'tiempo_entrega_dias' => 15, 'moq' => 1000,
                'calificacion' => 4.7, 'entregas_a_tiempo' => '97%',
                'ubicacion' => 'Houston, TX (importación)', 'certificaciones' => ['ISO 9001', 'ISO 14001', 'REACH'],
            ],
            [
                'codigo' => 'PROV-089', 'nombre' => 'Polímeros del Bajío',
                'precio_unitario' => 67.00, 'moneda' => 'MXN',
                'tiempo_entrega_dias' => 3, 'moq' => 100,
                'calificacion' => 4.0, 'entregas_a_tiempo' => '91%',
                'ubicacion' => 'León, GTO', 'certificaciones' => ['ISO 9001'],
            ],
        ];
    }

    // ══════════════════════════════════════════════
    // Listados para la UI
    // ══════════════════════════════════════════════

    public function listarClientes(): array
    {
        return [
            ['codigo' => 'CLI-001', 'nombre' => 'Manufacturas del Pacífico'],
            ['codigo' => 'CLI-002', 'nombre' => 'Grupo Industrial Azteca'],
            ['codigo' => 'CLI-003', 'nombre' => 'Plásticos y Derivados SA'],
        ];
    }

    public function listarProductos(): array
    {
        return [
            ['sku' => 'SAL-001', 'nombre' => 'Resina epóxica industrial'],
            ['sku' => 'SAL-003', 'nombre' => 'Solvente grado técnico'],
            ['sku' => 'SAL-007', 'nombre' => 'Catalizador rápido'],
        ];
    }
}
