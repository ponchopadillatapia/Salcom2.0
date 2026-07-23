<?php

use App\Http\Controllers\APIS\EmpresaApiController;
use App\Http\Controllers\APIS\SalcomApiController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// ── Validación fiscal (usa sesión web del proveedor para guardar expediente) ──
Route::middleware(['web', 'auth.proveedor'])->group(function () {
    Route::post('/empresa', [EmpresaApiController::class, 'validar']);
});

// ── Búsqueda de código postal ──
Route::get('/codigo-postal/{cp}', function (string $cp) {
    $cp = preg_replace('/\D/', '', $cp);
    if (strlen($cp) !== 5) {
        return response()->json(['error' => 'CP inválido'], 400);
    }

    // Intentar con zippopotam
    try {
        $response = Http::timeout(5)->get("https://api.zippopotam.us/MX/{$cp}");
        if ($response->ok()) {
            $data = $response->json();
            $places = $data['places'] ?? [];
            if (count($places)) {
                return response()->json([
                    'estado' => $places[0]['state'] ?? '',
                    'municipio' => $places[0]['place name'] ?? '',
                    'ciudad' => $places[0]['place name'] ?? '',
                    'colonias' => array_map(fn ($p) => $p['place name'], $places),
                ]);
            }
        }
    } catch (Exception $e) {
    }

    return response()->json(['error' => 'No encontrado'], 404);
});

// ── API Salcom (protegida con Bearer token) ──
Route::middleware('auth.api_token')->prefix('salcom')->group(function () {
    // Resumen y análisis
    Route::get('/resumen', [SalcomApiController::class, 'resumen']);
    Route::get('/analisis', [SalcomApiController::class, 'analisis']);

    // Clientes
    Route::get('/clientes', [SalcomApiController::class, 'clientes']);
    Route::get('/clientes/{cliente}', [SalcomApiController::class, 'clienteDetalle']);

    // Proveedores
    Route::get('/proveedores', [SalcomApiController::class, 'proveedores']);
    Route::get('/proveedores/{proveedor}', [SalcomApiController::class, 'proveedorDetalle']);

    // Pedidos
    Route::get('/pedidos', [SalcomApiController::class, 'pedidos']);
    Route::get('/pedidos/{pedido}', [SalcomApiController::class, 'pedidoDetalle']);

    // Productos
    Route::get('/productos', [SalcomApiController::class, 'productos']);
    Route::get('/productos/{producto}', [SalcomApiController::class, 'productoDetalle']);

    // Facturas
    Route::get('/facturas', [SalcomApiController::class, 'facturas']);

    // Muestras
    Route::get('/muestras', [SalcomApiController::class, 'muestras']);

    // Encuestas
    Route::get('/encuestas', [SalcomApiController::class, 'encuestas']);

    // Documentos de proveedores
    Route::get('/documentos', [SalcomApiController::class, 'documentos']);
    Route::get('/documentos/{documento}/validar', [SalcomApiController::class, 'validarDocumento']);
    Route::patch('/documentos/{documento}/revisar', [SalcomApiController::class, 'revisarDocumento']);
});
