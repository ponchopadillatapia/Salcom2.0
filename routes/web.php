<?php

use App\Http\Controllers\AltaProductoController;
use App\Http\Controllers\AltaProductoPTController;
use App\Http\Controllers\AuthProveedorController;
use App\Http\Controllers\MuestraController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\PortalProveedorController;
use Illuminate\Support\Facades\Route;

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
Route::post('/perfil/foto', [PortalProveedorController::class, 'subirFoto'])->name('proveedores.perfil.foto')->middleware('auth.proveedor');
Route::get('/forecast', function () {
    return view('proveedores.forecast');
})->name('proveedores.forecast')->middleware('auth.proveedor');
Route::get('/proveedor/otif', function () {
    return view('proveedores.otif');
})->name('proveedores.otif')->middleware('auth.proveedor');

// ── Consultar OC ──
Route::get('/consultar-oc', [OrdenCompraController::class, 'mostrarConsultarOC'])->name('proveedores.oc')->middleware('auth.proveedor');
Route::post('/consultar-oc/generar', [OrdenCompraController::class, 'generarOC'])->name('proveedores.oc.generar')->middleware('auth.proveedor');
Route::get('/consultar-oc/generar', fn () => redirect()->route('proveedores.oc'))->middleware('auth.proveedor');

// ── Alta de Producto ──
Route::get('/alta-producto', [AltaProductoController::class, 'mostrarAltaProducto'])->name('proveedores.alta-producto')->middleware('auth.proveedor');
Route::get('/alta-producto/template', [AltaProductoController::class, 'descargarTemplate'])->name('proveedores.alta-producto.template')->middleware('auth.proveedor');
Route::post('/alta-producto/subir', [AltaProductoController::class, 'subirExcel'])->name('proveedores.alta-producto.subir')->middleware('auth.proveedor');
Route::post('/alta-producto/manual', [AltaProductoController::class, 'registroManual'])->name('proveedores.alta-producto.manual')->middleware('auth.proveedor');

// ── Inventario y Fiscal ──
Route::get('/proveedor/inventario', function () {
    return view('proveedores.inventario');
})->name('proveedores.inventario')->middleware('auth.proveedor');
Route::get('/proveedor/inventario/excel', [PortalProveedorController::class, 'exportarInventarioExcel'])->name('proveedores.inventario.excel')->middleware('auth.proveedor');
Route::get('/validacion-fiscal', [PortalProveedorController::class, 'mostrarValidacionFiscal'])->name('proveedores.validacion-fiscal')->middleware('auth.proveedor');
Route::get('/proveedor/fiscal', [PortalProveedorController::class, 'mostrarAltaFacturas'])->name('proveedores.fiscal')->middleware('auth.proveedor');
Route::post('/proveedor/fiscal/validar', [PortalProveedorController::class, 'validarAltaFactura'])->name('proveedores.fiscal.validar')->middleware('auth.proveedor');
Route::post('/proveedor/fiscal/subir', [PortalProveedorController::class, 'altaFactura'])->name('proveedores.fiscal.subir')->middleware('auth.proveedor');
Route::get('/proveedor/adjunto-documentos', [PortalProveedorController::class, 'mostrarAdjuntoDocumentos'])->name('proveedores.adjunto-documentos')->middleware('auth.proveedor');
Route::post('/proveedor/adjunto-documentos/subir', [PortalProveedorController::class, 'subirAdjuntoDocumentos'])->name('proveedores.adjunto-documentos.subir')->middleware('auth.proveedor');
Route::get('/identificacion-proveedor', [PortalProveedorController::class, 'mostrarIdentificacion'])->name('proveedores.identificacion')->middleware('auth.proveedor');
Route::post('/identificacion-proveedor', [PortalProveedorController::class, 'guardarIdentificacion'])->name('proveedores.identificacion.guardar')->middleware('auth.proveedor');

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
Route::get('/cliente/fiscal', function () {
    return view('clientes.fiscal');
})->name('clientes.fiscal')->middleware('auth.cliente');
Route::get('/cliente/forecast', function () {
    return view('clientes.forecast');
})->name('clientes.forecast')->middleware('auth.cliente');
Route::get('/cliente/onboarding', [PortalClienteController::class, 'mostrarOnboarding'])->name('clientes.onboarding')->middleware('auth.cliente');
Route::get('/cliente/otif', [PortalClienteController::class, 'mostrarOtif'])->name('clientes.otif')->middleware('auth.cliente');

// ── Auth Admin ──
use App\Http\Controllers\AuthAdminController;

Route::get('/login-admin', [AuthAdminController::class, 'mostrarLogin'])->name('admin.login');
Route::post('/login-admin', [AuthAdminController::class, 'procesarLogin'])->name('admin.login.procesar');
Route::post('/logout-admin', [AuthAdminController::class, 'cerrarSesion'])->name('admin.logout');
Route::get('/admin/perfil', [AuthAdminController::class, 'mostrarPerfil'])->name('admin.perfil')->middleware('auth.admin');
Route::post('/admin/perfil', [AuthAdminController::class, 'actualizarPerfil'])->name('admin.perfil.actualizar')->middleware('auth.admin');
Route::post('/admin/perfil/password', [AuthAdminController::class, 'cambiarPassword'])->name('admin.perfil.password')->middleware('auth.admin');
Route::post('/admin/perfil/foto', [AuthAdminController::class, 'subirFoto'])->name('admin.perfil.foto')->middleware('auth.admin');
Route::get('/admin/administradores', [AuthAdminController::class, 'mostrarAdministradores'])->name('admin.administradores')->middleware('auth.admin');
Route::post('/admin/administradores', [AuthAdminController::class, 'guardarAdministrador'])->name('admin.administradores.guardar')->middleware('auth.admin');

// ── Admin: Alta de Clientes (interno Salcom) ──
use App\Http\Controllers\AdminClienteController;

Route::get('/admin/cliente/alta', [AdminClienteController::class, 'mostrarAlta'])->name('admin.cliente.alta')->middleware('auth.admin');
Route::post('/admin/cliente/alta', [AdminClienteController::class, 'guardar'])->name('admin.cliente.guardar')->middleware('auth.admin');

// ── Admin: Panel (Dashboard, Clientes, Encuestas, Pedidos, Proveedores) ──
use App\Http\Controllers\AdminPanelController;

Route::get('/admin/dashboard', [AdminPanelController::class, 'dashboard'])->name('admin.dashboard')->middleware('auth.admin');
Route::get('/admin/clientes', [AdminPanelController::class, 'clientes'])->name('admin.clientes')->middleware('auth.admin');
Route::patch('/admin/clientes/{cliente}/toggle', [AdminPanelController::class, 'toggleCliente'])->name('admin.clientes.toggle')->middleware('auth.admin');
Route::delete('/admin/clientes/{cliente}', [AdminPanelController::class, 'eliminarCliente'])->name('admin.clientes.eliminar')->middleware('auth.admin');
Route::delete('/admin/proveedores/{proveedor}', [AdminPanelController::class, 'eliminarProveedor'])->name('admin.proveedores.eliminar')->middleware('auth.admin');
Route::get('/admin/encuestas', [AdminPanelController::class, 'encuestas'])->name('admin.encuestas')->middleware('auth.admin');
Route::get('/admin/pedidos', [AdminPanelController::class, 'pedidos'])->name('admin.pedidos')->middleware('auth.admin');
Route::get('/admin/pedidos/excel', [AdminPanelController::class, 'pedidosExcel'])->name('admin.pedidos.excel')->middleware('auth.admin');

// ── Encuesta de satisfacción ──
Route::get('/cliente/encuesta', [PortalClienteController::class, 'mostrarEncuesta'])->name('clientes.encuesta')->middleware('auth.cliente');
Route::post('/cliente/encuesta', [PortalClienteController::class, 'guardarEncuesta'])->name('clientes.encuesta.guardar')->middleware('auth.cliente');

// ── Módulo de IA (Proveedor — con botones) ──
use App\Http\Controllers\IaDashboardController;

Route::get('/proveedor/ia', [IaDashboardController::class, 'proveedorIa'])->name('proveedores.ia')->middleware('auth.proveedor');
Route::post('/proveedor/ia/pronostico', [IaDashboardController::class, 'proveedorPronostico'])->name('proveedores.ia.pronostico')->middleware('auth.proveedor');
Route::post('/proveedor/ia/inventario', [IaDashboardController::class, 'proveedorInventario'])->name('proveedores.ia.inventario')->middleware('auth.proveedor');
Route::post('/proveedor/ia/proveedor', [IaDashboardController::class, 'proveedorProveedor'])->name('proveedores.ia.proveedor')->middleware('auth.proveedor');

// ── Módulo de IA (Cliente — con botones) ──
Route::get('/cliente/ia', [IaDashboardController::class, 'clienteIa'])->name('clientes.ia')->middleware('auth.cliente');
Route::post('/cliente/ia/documentacion', [IaDashboardController::class, 'clienteDocumentacion'])->name('clientes.ia.documentacion')->middleware('auth.cliente');
Route::post('/cliente/ia/pronostico', [IaDashboardController::class, 'clientePronostico'])->name('clientes.ia.pronostico')->middleware('auth.cliente');
Route::post('/cliente/ia/inventario', [IaDashboardController::class, 'clienteInventario'])->name('clientes.ia.inventario')->middleware('auth.cliente');

// ── Módulo de IA (Admin — dashboard con formularios) ──
Route::get('/admin/ia', [IaDashboardController::class, 'adminIa'])->name('admin.ia')->middleware('auth.admin');
Route::post('/admin/ia/pronostico', [IaDashboardController::class, 'adminPronostico'])->name('admin.ia.pronostico')->middleware('auth.admin');
Route::post('/admin/ia/inventario', [IaDashboardController::class, 'adminInventario'])->name('admin.ia.inventario')->middleware('auth.admin');
Route::post('/admin/ia/proveedor', [IaDashboardController::class, 'adminProveedor'])->name('admin.ia.proveedor')->middleware('auth.admin');

// ── Contactos del proveedor ──
Route::get('/proveedor/alertas/recientes', [PortalProveedorController::class, 'alertasRecientesJson'])->name('proveedores.alertas.recientes')->middleware('auth.proveedor');
Route::post('/proveedor/alertas/{alerta}/leer', [PortalProveedorController::class, 'marcarAlertaLeida'])->name('proveedores.alertas.leer')->middleware('auth.proveedor');
Route::post('/proveedor/contactos', [PortalProveedorController::class, 'guardarContacto'])->name('proveedores.contactos.guardar')->middleware('auth.proveedor');
Route::delete('/proveedor/contactos/{contacto}', [PortalProveedorController::class, 'eliminarContacto'])->name('proveedores.contactos.eliminar')->middleware('auth.proveedor');

// ── Encuesta de servicio (proveedor evalúa a Salcom) ──
Route::get('/proveedor/encuesta', [PortalProveedorController::class, 'mostrarEncuesta'])->name('proveedores.encuesta')->middleware('auth.proveedor');
Route::post('/proveedor/encuesta', [PortalProveedorController::class, 'guardarEncuestaProveedor'])->name('proveedores.encuesta.guardar')->middleware('auth.proveedor');

// ── Aviso de privacidad ──
Route::get('/aviso-privacidad', function () {
    return view('aviso-privacidad');
})->name('aviso.privacidad');
Route::post('/proveedor/aviso-privacidad', [PortalProveedorController::class, 'aceptarAvisoPrivacidad'])->name('proveedores.aviso.aceptar')->middleware('auth.proveedor');

// ── Admin: Proveedores con score ──
Route::get('/admin/proveedores', [AdminPanelController::class, 'proveedores'])->name('admin.proveedores')->middleware('auth.admin');
Route::get('/admin/solicitudes-alta', [AdminPanelController::class, 'solicitudesAlta'])->name('admin.solicitudes-alta')->middleware('auth.admin');
Route::get('/admin/solicitudes-alta/{proveedor}/revisar', [AdminPanelController::class, 'revisarSolicitud'])->name('admin.solicitudes.revisar')->middleware('auth.admin');
Route::get('/admin/solicitudes-alta/{proveedor}/ver', [AdminPanelController::class, 'verDocumentosAprobadosSolicitud'])->name('admin.solicitudes-alta.ver')->middleware('auth.admin');
Route::post('/admin/solicitudes-alta/aprobar', [AdminPanelController::class, 'aprobarSolicitudAlta'])->name('admin.solicitudes-alta.aprobar')->middleware('auth.admin');
Route::post('/admin/solicitudes-alta/rechazar', [AdminPanelController::class, 'rechazarSolicitudAlta'])->name('admin.solicitudes-alta.rechazar')->middleware('auth.admin');
Route::get('/admin/proveedores/{codigo}/facturas', [AdminPanelController::class, 'proveedorFacturas'])->name('admin.proveedor-facturas')->middleware('auth.admin');
Route::get('/admin/proveedores/facturas-pendientes/excel', [AdminPanelController::class, 'facturasPendientesExcel'])->name('admin.facturas-pendientes.excel')->middleware('auth.admin');
Route::get('/admin/productos', [AdminPanelController::class, 'productos'])->name('admin.productos')->middleware('auth.admin');
Route::get('/admin/productos/excel', [AdminPanelController::class, 'productosExcel'])->name('admin.productos.excel')->middleware('auth.admin');
Route::post('/admin/productos/{id}/actualizar', [AdminPanelController::class, 'actualizarProducto'])->name('admin.productos.actualizar')->middleware('auth.admin');
Route::post('/admin/productos/{id}/borrar', [AdminPanelController::class, 'borrarProducto'])->name('admin.productos.borrar')->middleware('auth.admin');
Route::post('/admin/productos/{id}/toggle-activo', [AdminPanelController::class, 'toggleActivoProducto'])->name('admin.productos.toggle-activo')->middleware('auth.admin');
Route::get('/admin/productos/{id}/detalle', [AdminPanelController::class, 'productoDetalle'])->name('admin.productos.detalle')->middleware('auth.admin');

// ── Admin: Alta de Producto (compras + mantenimiento) ──
Route::get('/admin/alta-producto', [AltaProductoController::class, 'mostrarAltaProductoAdmin'])->name('admin.alta-producto')->middleware('auth.admin');
Route::get('/admin/alta-producto/template', [AltaProductoController::class, 'descargarTemplateNacional'])->name('admin.alta-producto.template')->middleware('auth.admin');
Route::get('/admin/alta-producto/template-mpi', [AltaProductoController::class, 'descargarTemplateMPI'])->name('admin.alta-producto.template-mpi')->middleware('auth.admin');
Route::post('/admin/alta-producto/concatenar-proveedor', [AltaProductoController::class, 'concatenarProveedorNacional'])->name('admin.alta-producto.concatenar-proveedor')->middleware('auth.admin');
Route::post('/admin/alta-producto/subir', [AltaProductoController::class, 'subirExcel'])->name('admin.alta-producto.subir')->middleware('auth.admin');
Route::post('/admin/alta-producto/subir-mpi', [AltaProductoController::class, 'subirExcelMPI'])->name('admin.alta-producto.subir-mpi')->middleware('auth.admin');

// ── Admin: Alta de Producto Mantenimiento (CM/BL/CIL/CN) ──
Route::get('/admin/alta-producto-mto', [AltaProductoController::class, 'mostrarAltaProductoMTO'])->name('admin.alta-producto-mto')->middleware('auth.admin');
Route::get('/admin/alta-producto-mto/template', [AltaProductoController::class, 'descargarTemplateMTO'])->name('admin.alta-producto-mto.template')->middleware('auth.admin');
Route::post('/admin/alta-producto-mto/subir', [AltaProductoController::class, 'subirExcelMTO'])->name('admin.alta-producto-mto.subir')->middleware('auth.admin');

// ── Admin: Alta de Producto Terminado (PT) ──
Route::get('/admin/alta-producto-pt', [AltaProductoPTController::class, 'mostrar'])->name('admin.alta-producto-pt')->middleware('auth.admin');
Route::get('/admin/alta-producto-pt/template', [AltaProductoPTController::class, 'descargarTemplate'])->name('admin.alta-producto-pt.template')->middleware('auth.admin');
Route::post('/admin/alta-producto-pt/subir', [AltaProductoPTController::class, 'subirExcel'])->name('admin.alta-producto-pt.subir')->middleware('auth.admin');

// ── Admin: Migración Masiva (productos del sistema viejo → formato nuevo con IA) ──
Route::get('/admin/migracion-masiva', [AltaProductoController::class, 'mostrarMigracionMasiva'])->name('admin.migracion-masiva')->middleware('auth.admin');
Route::post('/admin/migracion-masiva/subir', [AltaProductoController::class, 'subirMigracion'])->name('admin.migracion-masiva.subir')->middleware('auth.admin');
Route::get('/admin/migracion-masiva/{id}/estado', [AltaProductoController::class, 'estadoMigracion'])->name('admin.migracion-masiva.estado')->middleware('auth.admin');
Route::get('/admin/migracion-masiva/{id}/descargar', [AltaProductoController::class, 'descargarResultado'])->name('admin.migracion.descargar')->middleware('auth.admin');
Route::get('/admin/migracion-masiva/template', [AltaProductoController::class, 'descargarTemplateMigracion'])->name('admin.migracion.template')->middleware('auth.admin');

Route::get('/admin/facturas', [AdminPanelController::class, 'facturas'])->name('admin.facturas')->middleware('auth.admin');
Route::get('/admin/facturas/excel', [AdminPanelController::class, 'facturasExcel'])->name('admin.facturas.excel')->middleware('auth.admin');
Route::get('/admin/documentos', [AdminPanelController::class, 'documentos'])->name('admin.documentos')->middleware('auth.admin');
Route::get('/admin/expediente-fiscal', [AdminPanelController::class, 'expedienteFiscal'])->name('admin.expediente-fiscal')->middleware('auth.admin');
Route::get('/admin/expediente-fiscal/proveedor/{proveedor}', [AdminPanelController::class, 'expedienteFiscalVer'])->name('admin.expediente-fiscal.ver')->middleware('auth.admin');
Route::get('/admin/expediente-fiscal/{documento}/descargar', [AdminPanelController::class, 'descargarDocumentoFiscal'])->name('admin.expediente-fiscal.descargar')->middleware('auth.admin');
Route::get('/admin/documentos/excel', [AdminPanelController::class, 'documentosExcel'])->name('admin.documentos.excel')->middleware('auth.admin');
Route::get('/admin/negocio', [AdminPanelController::class, 'negocio'])->name('admin.negocio')->middleware('auth.admin');
Route::get('/admin/otif', [AdminPanelController::class, 'otif'])->name('admin.otif')->middleware('auth.admin');
Route::get('/admin/inventario', [AdminPanelController::class, 'inventario'])->name('admin.inventario')->middleware('auth.admin');

// ── Áreas con control de rol ──
Route::get('/admin/materia-prima', [AdminPanelController::class, 'materiaPrima'])->name('admin.materia-prima')->middleware(['auth.admin', 'admin.rol:materia_prima']);
Route::get('/admin/materia-prima/nuevo', [AdminPanelController::class, 'materiaPrimaCrear'])->name('admin.materia-prima.crear')->middleware(['auth.admin', 'admin.rol:materia_prima']);
Route::post('/admin/materia-prima/nuevo', [AdminPanelController::class, 'materiaPrimaGuardar'])->name('admin.materia-prima.guardar')->middleware(['auth.admin', 'admin.rol:materia_prima']);
Route::get('/admin/material-empaque', [AdminPanelController::class, 'materialEmpaque'])->name('admin.material-empaque')->middleware(['auth.admin', 'admin.rol:material_empaque']);
Route::get('/admin/fiscal', [AdminPanelController::class, 'fiscal'])->name('admin.fiscal')->middleware('auth.admin');
Route::get('/admin/opinion-positiva', [AdminPanelController::class, 'opinionPositiva'])->name('admin.opinion-positiva')->middleware('auth.admin');
Route::get('/admin/gestion-compras', [AdminPanelController::class, 'gestionCompras'])->name('admin.gestion-compras')->middleware('auth.admin');
Route::get('/admin/gestion-compras/excel/opinion', [AdminPanelController::class, 'exportOpinion'])->name('admin.export-opinion')->middleware('auth.admin');
Route::get('/admin/gestion-compras/excel/autorizacion', [AdminPanelController::class, 'exportAutorizacion'])->name('admin.export-autorizacion')->middleware('auth.admin');
Route::get('/admin/gestion-compras/excel/dias-inventario', [AdminPanelController::class, 'exportDiasInventario'])->name('admin.export-dias-inventario')->middleware('auth.admin');
Route::get('/admin/gestion-compras/excel/costos', [AdminPanelController::class, 'exportCostos'])->name('admin.export-costos')->middleware('auth.admin');
Route::post('/admin/gestion-compras/enviar-avisos-opinion', [AdminPanelController::class, 'enviarAvisosOpinion'])->name('admin.enviar-avisos-opinion')->middleware('auth.admin');
Route::post('/admin/gestion-compras/autorizar-proveedor', [AdminPanelController::class, 'autorizarProveedor'])->name('admin.autorizar-proveedor')->middleware('auth.admin');
Route::post('/admin/gestion-compras/autorizar-costo', [AdminPanelController::class, 'autorizarCosto'])->name('admin.autorizar-costo')->middleware('auth.admin');
Route::post('/admin/gestion-compras/crear-oc', [AdminPanelController::class, 'crearOC'])->name('admin.crear-oc')->middleware('auth.admin');
Route::get('/admin/reporte-proveedores', [AdminPanelController::class, 'reporteProveedores'])->name('admin.reporte-proveedores')->middleware('auth.admin');
Route::get('/admin/reporte-proveedores/excel', [AdminPanelController::class, 'reporteProveedoresExcel'])->name('admin.reporte-proveedores.excel')->middleware('auth.admin');
Route::get('/admin/reporte-proveedores/corte', [AdminPanelController::class, 'reporteCorte'])->name('admin.reporte-corte')->middleware('auth.admin');
Route::get('/admin/reporte-proveedores/corte/excel', [AdminPanelController::class, 'reporteCorteExcel'])->name('admin.reporte-corte.excel')->middleware('auth.admin');

// ── Validación RFC (AJAX) ──
Route::post('/admin/cliente/validar-rfc', [AdminClienteController::class, 'validarRfc'])->name('admin.cliente.validar-rfc')->middleware('auth.admin');

// ── Gestión de Pedidos (estatus + notificaciones) ──
use App\Http\Controllers\PedidoController;

Route::patch('/pedido/{pedido}/estatus', [PedidoController::class, 'cambiarEstatus'])->name('pedidos.cambiar-estatus');
Route::post('/pedido/tracking', [PedidoController::class, 'tracking'])->name('pedidos.tracking');
