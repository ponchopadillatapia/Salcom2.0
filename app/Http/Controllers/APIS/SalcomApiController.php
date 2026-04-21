<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use App\Models\ClienteUser;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Muestra;
use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProveedorUser;
use App\Models\TrackingPedido;
use App\Services\DocumentValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalcomApiController extends Controller
{
    // ══════════════════════════════════════════════
    //  RESUMEN GENERAL
    // ══════════════════════════════════════════════

    public function resumen(): JsonResponse
    {
        return response()->json([
            'clientes' => [
                'total'   => ClienteUser::count(),
                'activos' => ClienteUser::where('activo', true)->count(),
            ],
            'proveedores' => [
                'total'   => ProveedorUser::count(),
                'activos' => ProveedorUser::where('activo', true)->count(),
            ],
            'pedidos' => [
                'total'       => Pedido::count(),
                'por_estatus' => Pedido::selectRaw('estatus, count(*) as total')
                    ->groupBy('estatus')->pluck('total', 'estatus'),
                'monto_total' => Pedido::sum('total'),
            ],
            'encuestas' => [
                'total'              => Encuesta::count(),
                'calificacion_prom'  => round((float) Encuesta::avg('calificacion'), 1),
            ],
            'productos' => [
                'total'     => Producto::count(),
                'activos'   => Producto::where('activo', true)->count(),
                'sin_stock' => Producto::where('stock', '<=', 0)->count(),
            ],
            'facturas' => [
                'total'     => Factura::count(),
                'pendientes' => Factura::where('estatus', 'pendiente')->count(),
                'monto_pendiente' => Factura::where('estatus', 'pendiente')->sum('total'),
            ],
            'muestras' => [
                'total'      => Muestra::count(),
                'en_proceso' => Muestra::whereNotIn('etapa', ['aprobado', 'rechazado'])->count(),
                'aprobadas'  => Muestra::where('etapa', 'aprobado')->count(),
                'rechazadas' => Muestra::where('etapa', 'rechazado')->count(),
            ],
            'documentos' => [
                'pendientes'  => DocumentoProveedor::where('estatus', 'pendiente')->count(),
                'aprobados'   => DocumentoProveedor::where('estatus', 'aprobado')->count(),
                'rechazados'  => DocumentoProveedor::where('estatus', 'rechazado')->count(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════
    //  CLIENTES
    // ══════════════════════════════════════════════

    public function clientes(Request $request): JsonResponse
    {
        $query = ClienteUser::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('correo', 'like', "%{$busqueda}%")
                  ->orWhere('codigo_cliente', 'like', "%{$busqueda}%");
            });
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $clientes = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get(['id', 'nombre', 'correo', 'usuario', 'telefono', 'rfc',
                    'tipo_persona', 'codigo_cliente', 'tipo_cliente',
                    'credito_autorizado', 'limite_credito', 'activo', 'created_at']);

        return response()->json(['total' => $clientes->count(), 'data' => $clientes]);
    }

    public function clienteDetalle(ClienteUser $cliente): JsonResponse
    {
        $pedidos   = Pedido::where('codigo_cliente', $cliente->codigo_cliente)->orderBy('created_at', 'desc')->limit(20)->get();
        $encuestas = Encuesta::where('codigo_cliente', $cliente->codigo_cliente)->orderBy('created_at', 'desc')->limit(10)->get();
        $facturas  = Factura::where('codigo_cliente', $cliente->codigo_cliente)->orderBy('created_at', 'desc')->limit(10)->get();

        return response()->json([
            'cliente'   => $cliente->makeHidden(['password', 'remember_token']),
            'pedidos'   => ['total' => $pedidos->count(), 'monto_total' => $pedidos->sum('total'), 'data' => $pedidos],
            'encuestas' => ['total' => $encuestas->count(), 'calificacion_prom' => round((float) $encuestas->avg('calificacion'), 1), 'data' => $encuestas],
            'facturas'  => ['total' => $facturas->count(), 'monto_total' => $facturas->sum('total'), 'data' => $facturas],
        ]);
    }

    // ══════════════════════════════════════════════
    //  PROVEEDORES
    // ══════════════════════════════════════════════

    public function proveedores(Request $request): JsonResponse
    {
        $query = ProveedorUser::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('correo', 'like', "%{$busqueda}%")
                  ->orWhere('codigo_compras', 'like', "%{$busqueda}%");
            });
        }

        $proveedores = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get(['id', 'usuario', 'codigo_compras', 'nombre',
                    'tipo_persona', 'telefono', 'correo', 'activo', 'created_at']);

        return response()->json(['total' => $proveedores->count(), 'data' => $proveedores]);
    }

    public function proveedorDetalle(ProveedorUser $proveedor): JsonResponse
    {
        $documentos = DocumentoProveedor::where('proveedor_id', $proveedor->id)->get();
        $muestras   = Muestra::where('proveedor', 'like', "%{$proveedor->nombre}%")->orderBy('created_at', 'desc')->limit(10)->get();
        $facturas   = Factura::where('codigo_proveedor', $proveedor->codigo_compras)->orderBy('created_at', 'desc')->limit(10)->get();

        return response()->json([
            'proveedor'  => $proveedor->makeHidden(['password']),
            'documentos' => ['total' => $documentos->count(), 'por_estatus' => $documentos->groupBy('estatus')->map->count(), 'data' => $documentos],
            'muestras'   => ['total' => $muestras->count(), 'data' => $muestras],
            'facturas'   => ['total' => $facturas->count(), 'monto_total' => $facturas->sum('total'), 'data' => $facturas],
        ]);
    }

    // ══════════════════════════════════════════════
    //  PEDIDOS
    // ══════════════════════════════════════════════

    public function pedidos(Request $request): JsonResponse
    {
        $query = Pedido::query();

        if ($estatus = $request->input('estatus')) {
            $query->where('estatus', $estatus);
        }
        if ($cliente = $request->input('cliente')) {
            $query->where('codigo_cliente', $cliente);
        }

        $pedidos = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json(['total' => $pedidos->count(), 'data' => $pedidos]);
    }

    public function pedidoDetalle(Pedido $pedido): JsonResponse
    {
        $tracking = TrackingPedido::where('pedido_id', $pedido->id)->orderBy('fecha', 'desc')->get();
        $facturas = Factura::where('pedido_id', $pedido->id)->get();

        return response()->json([
            'pedido'   => $pedido,
            'tracking' => $tracking,
            'facturas' => $facturas,
        ]);
    }

    // ══════════════════════════════════════════════
    //  PRODUCTOS
    // ══════════════════════════════════════════════

    public function productos(Request $request): JsonResponse
    {
        $query = Producto::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('codigo', 'like', "%{$busqueda}%");
            });
        }
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }
        if ($request->input('sin_stock')) {
            $query->where('stock', '<=', 0);
        }

        $productos = $query->orderBy('nombre')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json(['total' => $productos->count(), 'data' => $productos]);
    }

    public function productoDetalle(Producto $producto): JsonResponse
    {
        // Pedidos que incluyen este producto (buscar en JSON)
        $pedidos = Pedido::where('productos', 'like', "%{$producto->codigo}%")
            ->orWhere('productos', 'like', "%{$producto->nombre}%")
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get(['id', 'folio', 'codigo_cliente', 'nombre_cliente', 'total', 'estatus', 'created_at']);

        return response()->json([
            'producto'       => $producto,
            'pedidos_recientes' => $pedidos,
            'total_pedidos'  => $pedidos->count(),
        ]);
    }

    // ══════════════════════════════════════════════
    //  FACTURAS
    // ══════════════════════════════════════════════

    public function facturas(Request $request): JsonResponse
    {
        $query = Factura::query();

        if ($estatus = $request->input('estatus')) {
            $query->where('estatus', $estatus);
        }
        if ($cliente = $request->input('cliente')) {
            $query->where('codigo_cliente', $cliente);
        }
        if ($proveedor = $request->input('proveedor')) {
            $query->where('codigo_proveedor', $proveedor);
        }
        if ($request->input('vencidas')) {
            $query->where('estatus', 'pendiente')->where('fecha_vencimiento', '<', now());
        }

        $facturas = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json(['total' => $facturas->count(), 'data' => $facturas]);
    }

    // ══════════════════════════════════════════════
    //  MUESTRAS
    // ══════════════════════════════════════════════

    public function muestras(Request $request): JsonResponse
    {
        $query = Muestra::query();

        if ($etapa = $request->input('etapa')) {
            $query->where('etapa', $etapa);
        }
        if ($proveedor = $request->input('proveedor')) {
            $query->where('proveedor', 'like', "%{$proveedor}%");
        }

        $muestras = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json(['total' => $muestras->count(), 'data' => $muestras]);
    }

    // ══════════════════════════════════════════════
    //  ENCUESTAS
    // ══════════════════════════════════════════════

    public function encuestas(Request $request): JsonResponse
    {
        $query = Encuesta::query();

        if ($cliente = $request->input('cliente')) {
            $query->where('codigo_cliente', $cliente);
        }

        $encuestas = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get();

        $promedios = [
            'calificacion'     => Encuesta::avg('calificacion'),
            'tiempo_entrega'   => Encuesta::avg('tiempo_entrega'),
            'calidad_producto' => Encuesta::avg('calidad_producto'),
            'total_encuestas'  => Encuesta::count(),
        ];

        return response()->json(['promedios' => $promedios, 'data' => $encuestas]);
    }

    // ══════════════════════════════════════════════
    //  DOCUMENTOS DE PROVEEDORES
    // ══════════════════════════════════════════════

    public function documentos(Request $request): JsonResponse
    {
        $query = DocumentoProveedor::with('proveedor:id,nombre,correo,codigo_compras');

        if ($estatus = $request->input('estatus')) {
            $query->where('estatus', $estatus);
        }
        if ($proveedor = $request->input('proveedor_id')) {
            $query->where('proveedor_id', $proveedor);
        }
        if ($tipo = $request->input('tipo')) {
            $query->where('tipo', $tipo);
        }

        $docs = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json([
            'resumen' => [
                'pendientes' => DocumentoProveedor::where('estatus', 'pendiente')->count(),
                'aprobados'  => DocumentoProveedor::where('estatus', 'aprobado')->count(),
                'rechazados' => DocumentoProveedor::where('estatus', 'rechazado')->count(),
            ],
            'total' => $docs->count(),
            'data'  => $docs,
        ]);
    }

    public function validarDocumento(DocumentoProveedor $documento): JsonResponse
    {
        $ruta = storage_path('app/private/' . $documento->archivo);

        if (!file_exists($ruta)) {
            return response()->json(['error' => 'Archivo no encontrado: ' . $documento->archivo], 404);
        }

        $service = app(DocumentValidationService::class);

        $resultado = match ($documento->tipo) {
            'cif'            => $service->validarCIF($this->extraerTexto($ruta)),
            'opinion'        => $service->validarOpinion($this->extraerTexto($ruta), null),
            'acta'           => $service->validarActa($this->extraerTexto($ruta), true),
            'rep_legal'      => $service->validarINE($this->extraerTexto($ruta), 'representante'),
            'contribuyente'  => $service->validarINE($this->extraerTexto($ruta), 'contribuyente'),
            'caratula_banco' => $service->validarCaratulaBanco($this->extraerTexto($ruta)),
            default          => ['error' => 'Tipo de documento no reconocido'],
        };

        $documento->update(['resultado_validacion' => $resultado]);

        return response()->json([
            'documento_id' => $documento->id,
            'tipo'         => $documento->tipo,
            'resultado'    => $resultado,
        ]);
    }

    public function revisarDocumento(Request $request, DocumentoProveedor $documento): JsonResponse
    {
        $request->validate([
            'estatus' => 'required|in:aprobado,rechazado',
            'notas'   => 'nullable|string|max:2000',
        ]);

        $documento->update([
            'estatus'        => $request->input('estatus'),
            'notas_revision' => $request->input('notas'),
            'revisado_at'    => now(),
        ]);

        return response()->json([
            'mensaje'      => 'Documento ' . $documento->id . ' marcado como ' . $request->input('estatus'),
            'documento_id' => $documento->id,
            'estatus'      => $documento->estatus,
        ]);
    }

    // ══════════════════════════════════════════════
    //  ANÁLISIS / TENDENCIAS
    // ══════════════════════════════════════════════

    public function analisis(): JsonResponse
    {
        // Pedidos por mes (últimos 6 meses)
        $pedidosPorMes = Pedido::selectRaw("strftime('%Y-%m', created_at) as mes, count(*) as total, sum(total) as monto")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw("strftime('%Y-%m', created_at)")
            ->orderBy('mes')
            ->get();

        // Top clientes por monto
        $topClientes = Pedido::selectRaw('codigo_cliente, nombre_cliente, count(*) as pedidos, sum(total) as monto_total')
            ->groupBy('codigo_cliente', 'nombre_cliente')
            ->orderByDesc('monto_total')
            ->limit(10)
            ->get();

        // Productos con stock bajo
        $stockBajo = Producto::where('activo', true)
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->get(['codigo', 'nombre', 'stock', 'unidad_venta', 'precio']);

        // Facturas vencidas
        $facturasVencidas = Factura::where('estatus', 'pendiente')
            ->where('fecha_vencimiento', '<', now())
            ->get(['folio_cfdi', 'codigo_cliente', 'codigo_proveedor', 'total', 'fecha_vencimiento']);

        // Muestras en proceso
        $muestrasActivas = Muestra::whereNotIn('etapa', ['aprobado', 'rechazado'])
            ->get(['lote', 'producto', 'proveedor', 'etapa', 'created_at']);

        return response()->json([
            'pedidos_por_mes'   => $pedidosPorMes,
            'top_clientes'      => $topClientes,
            'stock_bajo'        => $stockBajo,
            'facturas_vencidas' => $facturasVencidas,
            'muestras_activas'  => $muestrasActivas,
        ]);
    }

    // ── Helper ──

    private function extraerTexto(string $path): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $texto = $parser->parseFile($path)->getText();
            $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
            $texto = preg_replace('/\s+/', ' ', $texto);
            return strtoupper(trim($texto));
        } catch (\Exception $e) {
            return '';
        }
    }
}
