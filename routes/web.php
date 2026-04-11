<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthProveedorController;
use App\Http\Controllers\PortalProveedorController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\AltaProductoController;
use App\Http\Controllers\APIS\EmpresaApiController;
use App\Http\Controllers\MuestraController;

Route::get('/', function () {
    return view('welcome');
});

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
Route::get('/payment-history', [PortalProveedorController::class, 'mostrarPaymentHistory'])->name('proveedores.payment-history')->middleware('auth.proveedor');
Route::get('/perfil', [PortalProveedorController::class, 'mostrarPerfil'])->name('proveedores.perfil')->middleware('auth.proveedor');

// ── Consultar OC ──
Route::get('/consultar-oc', [OrdenCompraController::class, 'mostrarConsultarOC'])->name('proveedores.oc')->middleware('auth.proveedor');

// ── Alta de Producto ──
Route::get('/alta-producto', [AltaProductoController::class, 'mostrarAltaProducto'])->name('proveedores.alta-producto')->middleware('auth.proveedor');

// ── Validación de documentos fiscales (Alfonso) ──
Route::get('/validacion-fiscal', function () {
    return view('APIS.empresa');
})->name('empresa.form');

Route::post('/api/empresa', [EmpresaApiController::class, 'validar'])->name('empresa.validar');

// ── Envío de Muestras (Alfonso) ──
Route::get('/muestras/nueva', [MuestraController::class, 'crear'])->name('muestras.crear');
Route::post('/muestras', [MuestraController::class, 'guardar'])->name('muestras.guardar');
Route::get('/muestras/admin', [MuestraController::class, 'admin'])->name('muestras.admin');
Route::patch('/muestras/{muestra}/aprobar', [MuestraController::class, 'aprobar'])->name('muestras.aprobar');
Route::patch('/muestras/{muestra}/rechazar', [MuestraController::class, 'rechazar'])->name('muestras.rechazar');
Route::patch('/muestras/{muestra}/reiniciar', [MuestraController::class, 'reiniciar'])->name('muestras.reiniciar');
