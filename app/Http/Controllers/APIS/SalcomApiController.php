<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use App\Models\ClienteUser;
use App\Models\Encuesta;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProveedorUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalcomApiController extends Controller
{
    // ── Clientes ──

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

        return response()->json([
            'total' => $clientes->count(),
            'data'  => $clientes,
        ]);
    }

    // ── Proveedores ──

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

        return response()->json([
            'total' => $proveedores->count(),
            'data'  => $proveedores,
        ]);
    }

    // ── Pedidos ──

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

        return response()->json([
            'total' => $pedidos->count(),
            'data'  => $pedidos,
        ]);
    }

    // ── Productos ──

    public function productos(Request $request): JsonResponse
    {
        $query = Producto::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('codigo', 'like', "%{$busqueda}%");
            });
        }

        $productos = $query->orderBy('nombre')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json([
            'total' => $productos->count(),
            'data'  => $productos,
        ]);
    }

    // ── Encuestas ──

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

        return response()->json([
            'promedios' => $promedios,
            'data'      => $encuestas,
        ]);
    }

    // ── Resumen general ──

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
            ],
            'encuestas' => [
                'total'              => Encuesta::count(),
                'calificacion_prom'  => round((float) Encuesta::avg('calificacion'), 1),
            ],
            'productos' => [
                'total'   => Producto::count(),
                'activos' => Producto::where('activo', true)->count(),
            ],
        ]);
    }
}
