<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\APIS\EmpresaApiController;

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
    ->name('proveedores.actualizacion')
    ->middleware('auth.proveedor');

Route::put('/proveedor/actualizacion', [ProveedorController::class, 'guardarActualizacion'])
    ->name('proveedores.actualizacion.guardar')
    ->middleware('auth.proveedor');

// Dashboard
Route::get('/dashboard-proveedor', [ProveedorController::class, 'mostrarDashboard'])
    ->name('proveedores.dashboard')
    ->middleware('auth.proveedor');

// Empresa (tu hermano)
// Mostrar la página
Route::get('/empresa', [EmpresaController::class, 'form']);
// Procesar la validación (esta es la que llamará el fetch)
Route::post('/api/empresa', [EmpresaApiController::class, 'validar']);

// Portal proveedor
Route::get('/portal-proveedor', [ProveedorController::class, 'mostrarPortal'])
    ->name('proveedores.portal')
    ->middleware('auth.proveedor');
// Consultar órdenes de compra
Route::get('/consultar-oc', [ProveedorController::class, 'mostrarConsultarOC'])
    ->name('proveedores.oc')
    ->middleware('auth.proveedor');