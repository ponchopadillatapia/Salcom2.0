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
Route::get('/validar-rfc', [RFCController::class, 'validar']);
Route::get('/rfc', function () {
    return view('APIS.rfc');
});