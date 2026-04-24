<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthProveedorController;
use App\Http\Controllers\PortalProveedorController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\AltaProductoController;
use App\Http\Controllers\APIS\EmpresaApiController;
use App\Http\Controllers\MuestraController;

Route::get('/', function () {
    return view('inicio');
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
Route::get('/forecast', function () { return view('proveedores.forecast'); })->name('proveedores.forecast')->middleware('auth.proveedor');

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

// ── Portal de Clientes ──
use App\Http\Controllers\AuthClienteController;
use App\Http\Controllers\PortalClienteController;

Route::get('/login-cliente', [AuthClienteController::class, 'mostrarLogin'])->name('clientes.login');
Route::post('/login-cliente', [AuthClienteController::class, 'procesarLogin'])->name('clientes.login.procesar');
Route::post('/logout-cliente', [AuthClienteController::class, 'cerrarSesion'])->name('clientes.logout');
Route::get('/portal-cliente', [PortalClienteController::class, 'mostrarPortal'])->name('clientes.portal')->middleware('auth.cliente');
Route::get('/cliente/dashboard', [PortalClienteController::class, 'mostrarDashboard'])->name('clientes.dashboard')->middleware('auth.cliente');
Route::get('/cliente/catalogo', [PortalClienteController::class, 'mostrarCatalogo'])->name('clientes.catalogo')->middleware('auth.cliente');
Route::get('/cliente/pedidos', [PortalClienteController::class, 'mostrarPedidos'])->name('clientes.pedidos')->middleware('auth.cliente');
Route::get('/cliente/estado-cuenta', [PortalClienteController::class, 'mostrarEstadoCuenta'])->name('clientes.estado-cuenta')->middleware('auth.cliente');
Route::get('/cliente/tracking', [PortalClienteController::class, 'mostrarTracking'])->name('clientes.tracking')->middleware('auth.cliente');
Route::get('/cliente/perfil', [PortalClienteController::class, 'mostrarPerfil'])->name('clientes.perfil')->middleware('auth.cliente');
Route::get('/cliente/forecast', function () { return view('clientes.forecast'); })->name('clientes.forecast')->middleware('auth.cliente');

// ── Auth Admin ──
use App\Http\Controllers\AuthAdminController;
Route::get('/login-admin', [AuthAdminController::class, 'mostrarLogin'])->name('admin.login');
Route::post('/login-admin', [AuthAdminController::class, 'procesarLogin'])->name('admin.login.procesar');
Route::post('/logout-admin', [AuthAdminController::class, 'cerrarSesion'])->name('admin.logout');

// ── Admin: Alta de Clientes (interno Salcom) ──
use App\Http\Controllers\AdminClienteController;
Route::get('/admin/cliente/alta', [AdminClienteController::class, 'mostrarAlta'])->name('admin.cliente.alta')->middleware('auth.admin');
Route::post('/admin/cliente/alta', [AdminClienteController::class, 'guardar'])->name('admin.cliente.guardar')->middleware('auth.admin');

// ── Admin: Panel (Dashboard, Clientes, Encuestas, Pedidos, Proveedores) ──
use App\Http\Controllers\AdminPanelController;
Route::get('/admin/dashboard', [AdminPanelController::class, 'dashboard'])->name('admin.dashboard')->middleware('auth.admin');
Route::get('/admin/clientes', [AdminPanelController::class, 'clientes'])->name('admin.clientes')->middleware('auth.admin');
Route::patch('/admin/clientes/{cliente}/toggle', [AdminPanelController::class, 'toggleCliente'])->name('admin.clientes.toggle')->middleware('auth.admin');
Route::get('/admin/encuestas', [AdminPanelController::class, 'encuestas'])->name('admin.encuestas')->middleware('auth.admin');
Route::get('/admin/pedidos', [AdminPanelController::class, 'pedidos'])->name('admin.pedidos')->middleware('auth.admin');

// ── Encuesta de satisfacción ──
Route::get('/cliente/encuesta', [PortalClienteController::class, 'mostrarEncuesta'])->name('clientes.encuesta')->middleware('auth.cliente');
Route::post('/cliente/encuesta', [PortalClienteController::class, 'guardarEncuesta'])->name('clientes.encuesta.guardar')->middleware('auth.cliente');

// ── Módulo de IA (Proveedor — análisis automático) ──
use App\Http\Controllers\IaDashboardController;

Route::get('/proveedor/ia', [IaDashboardController::class, 'proveedorIa'])->name('proveedores.ia')->middleware('auth.proveedor');

// ── Módulo de IA (Cliente — análisis automático) ──
Route::get('/cliente/ia', [IaDashboardController::class, 'clienteIa'])->name('clientes.ia')->middleware('auth.cliente');

// ── Módulo de IA (Admin — dashboard con formularios) ──
Route::get('/admin/ia', [IaDashboardController::class, 'adminIa'])->name('admin.ia')->middleware('auth.admin');
Route::post('/admin/ia/pronostico', [IaDashboardController::class, 'adminPronostico'])->name('admin.ia.pronostico')->middleware('auth.admin');
Route::post('/admin/ia/inventario', [IaDashboardController::class, 'adminInventario'])->name('admin.ia.inventario')->middleware('auth.admin');
Route::post('/admin/ia/proveedor', [IaDashboardController::class, 'adminProveedor'])->name('admin.ia.proveedor')->middleware('auth.admin');

// ── Contactos del proveedor ──
Route::post('/proveedor/contactos', [PortalProveedorController::class, 'guardarContacto'])->name('proveedores.contactos.guardar')->middleware('auth.proveedor');
Route::delete('/proveedor/contactos/{contacto}', [PortalProveedorController::class, 'eliminarContacto'])->name('proveedores.contactos.eliminar')->middleware('auth.proveedor');

// ── Aviso de privacidad ──
Route::get('/aviso-privacidad', function () { return view('aviso-privacidad'); })->name('aviso.privacidad');
Route::post('/proveedor/aviso-privacidad', [PortalProveedorController::class, 'aceptarAvisoPrivacidad'])->name('proveedores.aviso.aceptar')->middleware('auth.proveedor');

// ── Admin: Proveedores con score ──
Route::get('/admin/proveedores', [AdminPanelController::class, 'proveedores'])->name('admin.proveedores')->middleware('auth.admin');

// ── Validación RFC (AJAX) ──
Route::post('/admin/cliente/validar-rfc', [AdminClienteController::class, 'validarRfc'])->name('admin.cliente.validar-rfc');

// ── Gestión de Pedidos (estatus + notificaciones) ──
use App\Http\Controllers\PedidoController;
Route::patch('/pedido/{pedido}/estatus', [PedidoController::class, 'cambiarEstatus'])->name('pedidos.cambiar-estatus');
Route::post('/pedido/tracking', [PedidoController::class, 'tracking'])->name('pedidos.tracking');

