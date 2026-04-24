<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProveedorUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IaService
{
    private string $provider;
    private string $region;
    private string $accessKey;
    private string $secretKey;
    private string $model;
    private int $timeout;

    public function __construct()
    {
        $this->provider  = config('services.ia.provider', 'bedrock'); // bedrock | anthropic
        $this->region    = config('services.ia.bedrock_region', 'us-east-1');
        $this->accessKey = config('services.ia.aws_access_key', '');
        $this->secretKey = config('services.ia.aws_secret_key', '');
        $this->model     = config('services.ia.model', 'anthropic.claude-3-5-sonnet-20241022-v2:0');
        $this->timeout   = config('services.ia.timeout', 60);
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
    // Llamada a la IA (Amazon Bedrock o Anthropic)
    // ══════════════════════════════════════════════

    public function llamarClaude(string $prompt): array
    {
        if ($this->provider === 'bedrock') {
            return $this->llamarBedrock($prompt);
        }

        return $this->llamarAnthropicDirecto($prompt);
    }

    /**
     * Amazon Bedrock — Claude via AWS Signature V4
     */
    private function llamarBedrock(string $prompt): array
    {
        if (empty($this->accessKey) || empty($this->secretKey)) {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'Credenciales de AWS no configuradas. Agrega IA_AWS_ACCESS_KEY e IA_AWS_SECRET_KEY en tu .env',
            ];
        }

        $service  = 'bedrock';
        $host     = "bedrock-runtime.{$this->region}.amazonaws.com";
        $endpoint = "https://{$host}/model/{$this->model}/invoke";

        $body = json_encode([
            'anthropic_version' => 'bedrock-2023-05-31',
            'max_tokens'        => 4096,
            'system'            => 'Eres un analista experto de Industrias Salcom, una empresa manufacturera mexicana. Responde siempre en español, de forma concisa y orientada a la acción.',
            'messages'          => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        try {
            $now       = gmdate('Ymd\THis\Z');
            $date      = gmdate('Ymd');
            $scope     = "{$date}/{$this->region}/{$service}/aws4_request";
            $headers   = [
                'content-type' => 'application/json',
                'host'         => $host,
                'x-amz-date'  => $now,
            ];

            $canonicalUri     = "/model/{$this->model}/invoke";
            $canonicalQuery   = '';
            $canonicalHeaders = '';
            $signedHeaders    = '';
            ksort($headers);
            foreach ($headers as $k => $v) {
                $canonicalHeaders .= strtolower($k) . ':' . trim($v) . "\n";
                $signedHeaders    .= ($signedHeaders ? ';' : '') . strtolower($k);
            }
            $payloadHash     = hash('sha256', $body);
            $canonicalRequest = "POST\n{$canonicalUri}\n{$canonicalQuery}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

            $stringToSign = "AWS4-HMAC-SHA256\n{$now}\n{$scope}\n" . hash('sha256', $canonicalRequest);

            $kDate    = hash_hmac('sha256', $date, "AWS4{$this->secretKey}", true);
            $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
            $kService = hash_hmac('sha256', $service, $kRegion, true);
            $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
            $signature = hash_hmac('sha256', $stringToSign, $kSigning);

            $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type'  => 'application/json',
                    'X-Amz-Date'   => $now,
                    'Authorization' => $authHeader,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                return ['success' => true, 'content' => $text, 'error' => null];
            }

            Log::error('IaService: error de Bedrock', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            $errorMsg = $response->json()['message'] ?? null;

            return [
                'success' => false,
                'content' => null,
                'error'   => $errorMsg
                    ? 'Error de Bedrock: ' . $errorMsg
                    : 'Error de Amazon Bedrock (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            Log::error('IaService: excepción Bedrock', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'content' => null,
                'error'   => 'No se pudo conectar con Amazon Bedrock: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Anthropic directo (fallback)
     */
    private function llamarAnthropicDirecto(string $prompt): array
    {
        $apiKey = config('services.anthropic.api_key', '');
        $apiUrl = config('services.anthropic.url', 'https://api.anthropic.com/v1/messages');
        $model  = config('services.anthropic.model', 'claude-sonnet-4-20250514');

        if (empty(trim($apiKey))) {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'API key de Anthropic no configurada.',
            ];
        }

        try {
            $response = Http::asJson()
                ->timeout($this->timeout)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->post($apiUrl, [
                    'model'      => $model,
                    'max_tokens' => 4096,
                    'system'     => 'Eres un analista experto de Industrias Salcom, una empresa manufacturera mexicana. Responde siempre en español, de forma concisa y orientada a la acción.',
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ]);

            if ($response->successful()) {
                $text = $response->json()['content'][0]['text'] ?? '';
                return ['success' => true, 'content' => $text, 'error' => null];
            }

            $errorMsg = $response->json()['error']['message'] ?? null;
            return [
                'success' => false,
                'content' => null,
                'error'   => $errorMsg ? 'Error de Claude: ' . $errorMsg : 'Error HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'content' => null, 'error' => 'Error: ' . $e->getMessage()];
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
    // Datos reales desde la base de datos
    // ══════════════════════════════════════════════

    /**
     * Obtiene el historial de pedidos reales de un cliente (últimos 6 meses).
     */
    public function obtenerHistorialPedidos(string $codigoCliente): array
    {
        $pedidos = Pedido::where('codigo_cliente', $codigoCliente)
            ->where('created_at', '>=', now()->subMonths(6))
            ->orderBy('created_at', 'asc')
            ->get();

        if ($pedidos->isEmpty()) {
            return [];
        }

        return $pedidos->map(function ($pedido) {
            $productos = collect($pedido->productos ?? [])->map(function ($p) {
                return [
                    'sku'             => $p['sku'] ?? $p['codigo'] ?? 'N/A',
                    'nombre'          => $p['nombre'] ?? 'Sin nombre',
                    'cantidad'        => $p['cantidad'] ?? 0,
                    'unidad'          => $p['unidad'] ?? 'pz',
                    'precio_unitario' => $p['precio'] ?? $p['precio_unitario'] ?? 0,
                ];
            })->toArray();

            return [
                'pedido'    => $pedido->folio,
                'fecha'     => $pedido->created_at->format('Y-m-d'),
                'productos' => $productos,
                'total'     => (float) $pedido->total,
            ];
        })->toArray();
    }

    /**
     * Obtiene el inventario actual desde la tabla de productos.
     */
    public function obtenerInventarioActual(): array
    {
        return Producto::where('activo', true)
            ->orderBy('codigo')
            ->get()
            ->map(function ($p) {
                return [
                    'sku'            => $p->codigo,
                    'nombre'         => $p->nombre,
                    'stock_actual'   => (int) $p->stock,
                    'unidad'         => $p->unidad_venta,
                    'costo_unitario' => (float) $p->precio,
                    'ubicacion'      => 'Almacén principal',
                ];
            })->toArray();
    }

    /**
     * Calcula la demanda proyectada basada en el promedio de los últimos 3 meses de pedidos.
     */
    public function obtenerDemandaProyectada(): array
    {
        $productos = Producto::where('activo', true)->get();
        $tresMesesAtras = now()->subMonths(3);

        return $productos->map(function ($producto) use ($tresMesesAtras) {
            // Buscar pedidos de los últimos 3 meses que contengan este producto
            $pedidos = Pedido::where('created_at', '>=', $tresMesesAtras)
                ->whereNotIn('estatus', ['cancelado'])
                ->get();

            $cantidadTotal = 0;
            $mesesConPedido = 0;
            $cantidadesPorMes = [];

            foreach ($pedidos as $pedido) {
                $items = collect($pedido->productos ?? []);
                $item = $items->first(function ($p) use ($producto) {
                    return ($p['sku'] ?? $p['codigo'] ?? '') === $producto->codigo;
                });

                if ($item) {
                    $mes = $pedido->created_at->format('Y-m');
                    $cantidadesPorMes[$mes] = ($cantidadesPorMes[$mes] ?? 0) + ($item['cantidad'] ?? 0);
                    $cantidadTotal += ($item['cantidad'] ?? 0);
                }
            }

            $mesesConPedido = count($cantidadesPorMes);
            $demandaMensual = $mesesConPedido > 0 ? round($cantidadTotal / $mesesConPedido) : 0;

            // Determinar tendencia comparando primer y último mes
            $tendencia = 'estable';
            if ($mesesConPedido >= 2) {
                $valores = array_values($cantidadesPorMes);
                $primero = $valores[0];
                $ultimo  = end($valores);
                if ($ultimo > $primero * 1.1) {
                    $tendencia = 'creciente';
                } elseif ($ultimo < $primero * 0.9) {
                    $tendencia = 'decreciente';
                }
            }

            return [
                'sku'              => $producto->codigo,
                'nombre'           => $producto->nombre,
                'demanda_mensual'  => $demandaMensual,
                'unidad'           => $producto->unidad_venta,
                'tendencia'        => $tendencia,
            ];
        })->toArray();
    }

    /**
     * Obtiene los datos de un producto desde la BD.
     */
    public function obtenerProducto(string $productoId): array
    {
        $producto = Producto::where('codigo', $productoId)->first();

        if (!$producto) {
            return [
                'sku'                 => $productoId,
                'nombre'              => 'Producto no encontrado',
                'cantidad_requerida'  => 0,
                'unidad'              => 'N/A',
                'especificaciones'    => 'N/A',
            ];
        }

        // Calcular cantidad requerida basada en demanda promedio
        $demanda = $this->obtenerDemandaProyectada();
        $demItem = collect($demanda)->firstWhere('sku', $producto->codigo);
        $cantidadRequerida = $demItem ? $demItem['demanda_mensual'] : 0;

        return [
            'sku'                => $producto->codigo,
            'nombre'             => $producto->nombre,
            'cantidad_requerida' => $cantidadRequerida,
            'unidad'             => $producto->unidad_venta,
            'especificaciones'   => $producto->descripcion ?? 'Sin especificaciones',
        ];
    }

    /**
     * Obtiene proveedores activos con su score para comparación.
     */
    public function obtenerProveedoresProducto(string $productoId): array
    {
        $proveedores = ProveedorUser::where('activo', true)
            ->where('score_total', '>', 0)
            ->orderBy('score_total', 'desc')
            ->limit(10)
            ->get();

        if ($proveedores->isEmpty()) {
            // Si no hay proveedores con score, traer todos los activos
            $proveedores = ProveedorUser::where('activo', true)
                ->orderBy('nombre')
                ->limit(10)
                ->get();
        }

        return $proveedores->map(function ($prov) {
            return [
                'codigo'              => $prov->codigo_compras,
                'nombre'              => $prov->nombre,
                'precio_unitario'     => 0, // No tenemos precio por producto-proveedor aún
                'moneda'              => 'MXN',
                'tiempo_entrega_dias' => 0, // Pendiente de implementar
                'moq'                 => 0,
                'calificacion'        => (float) $prov->score_total,
                'entregas_a_tiempo'   => $prov->score_entrega > 0
                    ? round($prov->score_entrega) . '%'
                    : 'Sin datos',
                'ubicacion'           => 'México',
                'certificaciones'     => [],
            ];
        })->toArray();
    }

    // ══════════════════════════════════════════════
    // Listados para la UI (ahora desde BD)
    // ══════════════════════════════════════════════

    public function listarClientes(): array
    {
        return \App\Models\ClienteUser::select('codigo_cliente as codigo', 'nombre')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    public function listarProductos(): array
    {
        return Producto::select('codigo as sku', 'nombre')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }
}
