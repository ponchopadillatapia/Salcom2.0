<?php

use App\Http\Controllers\APIS\SalcomApiController;
use Illuminate\Support\Facades\Route;

// ── API Salcom (protegida con Bearer token) ──
Route::middleware('auth.api_token')->prefix('salcom')->group(function () {
    // Resumen y análisis
    Route::get('/resumen',   [SalcomApiController::class, 'resumen']);
    Route::get('/analisis',  [SalcomApiController::class, 'analisis']);

    // Clientes
    Route::get('/clientes',              [SalcomApiController::class, 'clientes']);
    Route::get('/clientes/{cliente}',    [SalcomApiController::class, 'clienteDetalle']);

    // Proveedores
    Route::get('/proveedores',               [SalcomApiController::class, 'proveedores']);
    Route::get('/proveedores/{proveedor}',   [SalcomApiController::class, 'proveedorDetalle']);

    // Pedidos
    Route::get('/pedidos',             [SalcomApiController::class, 'pedidos']);
    Route::get('/pedidos/{pedido}',    [SalcomApiController::class, 'pedidoDetalle']);

    // Productos
    Route::get('/productos',              [SalcomApiController::class, 'productos']);
    Route::get('/productos/{producto}',   [SalcomApiController::class, 'productoDetalle']);

    // Facturas
    Route::get('/facturas', [SalcomApiController::class, 'facturas']);

    // Muestras
    Route::get('/muestras', [SalcomApiController::class, 'muestras']);

    // Encuestas
    Route::get('/encuestas', [SalcomApiController::class, 'encuestas']);

    // Documentos de proveedores
    Route::get('/documentos',                        [SalcomApiController::class, 'documentos']);
    Route::get('/documentos/{documento}/validar',    [SalcomApiController::class, 'validarDocumento']);
    Route::patch('/documentos/{documento}/revisar',  [SalcomApiController::class, 'revisarDocumento']);
});
