<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProveedorController;

use App\Http\Controllers\AuthProveedorController;
use App\Http\Controllers\PortalProveedorController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\AltaProductoController;
use App\Http\Controllers\EmpresaController;

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

// ── Auth: Login / Registro / Logout ──
Route::get('/login-proveedor', [AuthProveedorController::class, 'mostrarLogin'])->name('proveedores.login');
Route::post('/login-proveedor', [AuthProveedorController::class, 'procesarLogin'])->name('proveedores.login.procesar');
Route::post('/logout-proveedor', [AuthProveedorController::class, 'cerrarSesion'])->name('proveedores.logout');
Route::get('/proveedor/registro', [AuthProveedorController::class, 'mostrarRegistro'])->name('proveedores.registro');
Route::post('/proveedor/registro', [AuthProveedorController::class, 'guardar'])->name('proveedores.registro.guardar');
Route::get('/proveedor/actualizacion', [AuthProveedorController::class, 'mostrarActualizacion'])->name('proveedores.actualizacion')->middleware('auth.proveedor');
Route::put('/proveedor/actualizacion', [AuthProveedorController::class, 'guardarActualizacion'])->name('proveedores.actualizacion.guardar')->middleware('auth.proveedor');

// ── Portal / Dashboard / Onboarding / Business ──
Route::get('/portal-proveedor', [PortalProveedorController::class, 'mostrarPortal'])->name('proveedores.portal')->middleware('auth.proveedor');
Route::get('/dashboard-proveedor', [PortalProveedorController::class, 'mostrarDashboard'])->name('proveedores.dashboard')->middleware('auth.proveedor');
Route::get('/onboarding', [PortalProveedorController::class, 'mostrarOnboarding'])->name('proveedores.onboarding')->middleware('auth.proveedor');
Route::get('/business', [PortalProveedorController::class, 'mostrarBusiness'])->name('proveedores.business')->middleware('auth.proveedor');

// ── Consultar OC ──
Route::get('/consultar-oc', [OrdenCompraController::class, 'mostrarConsultarOC'])->name('proveedores.oc')->middleware('auth.proveedor');

// ── Alta de Producto ──
Route::get('/alta-producto', [AltaProductoController::class, 'mostrarAltaProducto'])->name('proveedores.alta-producto')->middleware('auth.proveedor');

// ── Empresa (Alfonso) ──
Route::get('/empresa', [EmpresaController::class, 'form']);
Route::post('/api/empresa', [EmpresaApiController::class, 'validar']);

