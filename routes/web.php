<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\PDFController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/login-proveedor', [ProveedorController::class, 'mostrarLogin'])
    ->name('proveedores.login');

// Registro — muestra el formulario
Route::get('/proveedor/registro', [ProveedorController::class, 'mostrarRegistro'])
    ->name('proveedores.registro');

// Registro — guarda los datos
Route::post('/proveedor/registro', [ProveedorController::class, 'guardar'])
    ->name('proveedores.registro.guardar');

// Login — procesa el formulario
Route::post('/login-proveedor', [ProveedorController::class, 'procesarLogin'])
    ->name('proveedores.login.procesar');

    // Actualización — muestra el formulario
Route::get('/proveedor/actualizacion', [ProveedorController::class, 'mostrarActualizacion'])
    ->name('proveedores.actualizacion');

// Actualización — guarda los cambios
Route::put('/proveedor/actualizacion', [ProveedorController::class, 'guardarActualizacion'])
    ->name('proveedores.actualizacion.guardar');

    Route::get('/dashboard-proveedor', [ProveedorController::class, 'mostrarDashboard'])
    ->name('proveedores.dashboard');

Route::get('/empresa', [EmpresaController::class, 'form']);
Route::post('/empresa', [EmpresaController::class, 'guardar']);