<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\EmpresaController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/empresa', [EmpresaController::class, 'form']);
Route::post('/empresa', [EmpresaController::class, 'guardar']);
Route::get('/ver-pdf', [EmpresaController::class, 'verPDF']);

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
Route::post('/validar-rfc', [RFCController::class, 'validar']);
Route::post('/generar-cif', [RFCController::class, 'generarCIF']);
Route::get('/rfc', function () {
    return view('APIS.rfc');
});

Route::get('/cif', function () {
    return view('APIS.cif');
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

    Route::get('/dashboard-proveedor', [ProveedorController::class, 'mostrarDashboard'])
    ->name('proveedores.dashboard');

//Opinión positiva del mes actual
Route::get('/opinion', [OpinionController::class, 'form']);
Route::post('/opinion', [OpinionController::class, 'validar']);

