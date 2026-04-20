<?php

use App\Http\Controllers\APIS\SalcomApiController;
use Illuminate\Support\Facades\Route;

// ── API Salcom (protegida con Bearer token) ──
Route::middleware('auth.api_token')->prefix('salcom')->group(function () {
    Route::get('/resumen',      [SalcomApiController::class, 'resumen']);
    Route::get('/clientes',     [SalcomApiController::class, 'clientes']);
    Route::get('/proveedores',  [SalcomApiController::class, 'proveedores']);
    Route::get('/pedidos',      [SalcomApiController::class, 'pedidos']);
    Route::get('/productos',    [SalcomApiController::class, 'productos']);
    Route::get('/encuestas',    [SalcomApiController::class, 'encuestas']);
});
