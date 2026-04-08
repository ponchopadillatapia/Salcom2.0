<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\APIS\EmpresaApiController;

Route::get('/', function () {
    return view('welcome');
});

// ── Login proveedor ──
Route::get('/login-proveedor', [ProveedorController::class, 'mostrarLogin'])
    ->name('proveedores.login');
Route::post('/login-proveedor', [ProveedorController::class, 'procesarLogin'])
    ->name('proveedores.login.procesar');
Route::post('/logout-proveedor', [ProveedorController::class, 'cerrarSesion'])
    ->name('proveedores.logout');

// ── Registro ──
Route::get('/proveedor/registro', [ProveedorController::class, 'mostrarRegistro'])
    ->name('proveedores.registro');
Route::post('/proveedor/registro', [ProveedorController::class, 'guardar'])
    ->name('proveedores.registro.guardar');

// ── Actualización ──
Route::get('/proveedor/actualizacion', [ProveedorController::class, 'mostrarActualizacion'])
    ->name('proveedores.actualizacion')
    ->middleware('auth.proveedor');
Route::put('/proveedor/actualizacion', [ProveedorController::class, 'guardarActualizacion'])
    ->name('proveedores.actualizacion.guardar')
    ->middleware('auth.proveedor');

// ── Dashboard ──
Route::get('/dashboard-proveedor', [ProveedorController::class, 'mostrarDashboard'])
    ->name('proveedores.dashboard')
    ->middleware('auth.proveedor');

// ── Validación de documentos (Alfonso) ──
Route::get('/validacion-fiscal', function () {
    return view('APIS.empresa');
})->name('empresa.form');

Route::post('/api/empresa', [EmpresaApiController::class, 'validar'])
    ->name('empresa.validar');

// ── Portal proveedor (Said) ──
Route::get('/portal-proveedor', [ProveedorController::class, 'mostrarPortal'])
    ->name('proveedores.portal')
    ->middleware('auth.proveedor');
Route::get('/consultar-oc', [ProveedorController::class, 'mostrarConsultarOC'])
    ->name('proveedores.oc')
    ->middleware('auth.proveedor');
Route::get('/onboarding', [ProveedorController::class, 'mostrarOnboarding'])
    ->name('proveedores.onboarding')
    ->middleware('auth.proveedor');
Route::get('/business', [ProveedorController::class, 'mostrarBusiness'])
    ->name('proveedores.business')
    ->middleware('auth.proveedor');
Route::get('/alta-producto', [ProveedorController::class, 'mostrarAltaProducto'])
    ->name('proveedores.alta-producto')
    ->middleware('auth.proveedor');
