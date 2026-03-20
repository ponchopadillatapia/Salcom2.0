<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RFCController;

Route::get('/', function () {
    return view('welcome');
});

/*
use App\Http\Controllers\RFCController;

Route::get('/validar-rfc', [RFCController::class, 'validarRFC_API']);
*/
// Login
Route::get('/login-proveedor', [ProveedorController::class, 'mostrarLogin'])
    ->name('proveedores.login');

// Registro — muestra el formulario
Route::get('/proveedor/registro', [ProveedorController::class, 'mostrarRegistro'])
    ->name('proveedores.registro');

// Registro — guarda los datos
Route::post('/proveedor/registro', [ProveedorController::class, 'guardar'])
    ->name('proveedores.registro.guardar');

//Validación RFC
Route::get('/cif', [RFCController::class, 'vista']);
Route::get('/validar-rfc', [RFCController::class, 'validar']);
Route::get('/generar-cif', [RFCController::class, 'generarCIF']);
Route::get('/rfc', function () {
    return view('APIS.rfc');
});

// Login — procesa el formulario
Route::post('/login-proveedor', [ProveedorController::class, 'procesarLogin'])
    ->name('proveedores.login.procesar');

    // Actualización — muestra el formulario
Route::get('/proveedor/actualizacion', [ProveedorController::class, 'mostrarActualizacion'])
    ->name('proveedores.actualizacion');

// Actualización — guarda los cambios
Route::put('/proveedor/actualizacion', [ProveedorController::class, 'guardarActualizacion'])
    ->name('proveedores.actualizacion.guardar');