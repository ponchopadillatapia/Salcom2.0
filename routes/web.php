<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\EmpresaController;

Route::get('/', function () {
    return view('welcome');
});

// Login
Route::get('/login-proveedor', [ProveedorController::class, 'mostrarLogin'])
    ->name('proveedores.login');

Route::post('/login-proveedor', [ProveedorController::class, 'procesarLogin'])
    ->name('proveedores.login.procesar');

// Cerrar sesión
Route::post('/logout-proveedor', [ProveedorController::class, 'cerrarSesion'])
    ->name('proveedores.logout');

// Registro
Route::get('/proveedor/registro', [ProveedorController::class, 'mostrarRegistro'])
    ->name('proveedores.registro');

Route::post('/proveedor/registro', [ProveedorController::class, 'guardar'])
    ->name('proveedores.registro.guardar');

// Actualización
Route::get('/proveedor/actualizacion', [ProveedorController::class, 'mostrarActualizacion'])
    ->name('proveedores.actualizacion');

Route::put('/proveedor/actualizacion', [ProveedorController::class, 'guardarActualizacion'])
    ->name('proveedores.actualizacion.guardar');
//dashboard
Route::get('/dashboard-proveedor', [ProveedorController::class, 'mostrarDashboard'])
    ->name('proveedores.dashboard')
    ->middleware('auth.proveedor');

// Empresa (tu hermano)
Route::get('/empresa', [EmpresaController::class, 'form']);
Route::post('/empresa', [EmpresaController::class, 'guardar']); 

