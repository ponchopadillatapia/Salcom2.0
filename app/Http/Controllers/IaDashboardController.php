<?php

namespace App\Http\Controllers;

use App\Services\IaService;

class IaDashboardController extends Controller
{
    private IaService $iaService;

    public function __construct(IaService $iaService)
    {
        $this->iaService = $iaService;
    }

    // ══════════════════════════════════════════════
    //  PROVEEDOR — análisis automático al entrar
    // ══════════════════════════════════════════════

    public function proveedorIa()
    {
        $codigoProveedor = session('proveedor_codigo', 'PROV-001');

        // Ejecutar los 3 análisis automáticamente
        $pronostico  = $this->iaService->pronosticoDemanda($codigoProveedor);
        $inventario  = $this->iaService->optimizacionInventario();
        $proveedor   = $this->iaService->seleccionProveedor('SAL-001');

        return view('proveedores.ia-dashboard', [
            'resultadoPronostico' => $pronostico,
            'resultadoInventario' => $inventario,
            'resultadoProveedor'  => $proveedor,
            'productos'           => $this->iaService->listarProductos(),
        ]);
    }

    // ══════════════════════════════════════════════
    //  CLIENTE — análisis automático al entrar
    // ══════════════════════════════════════════════

    public function clienteIa()
    {
        $codigoCliente = session('cliente_codigo', 'CLI-001');

        // Análisis personalizado para el cliente
        $pronostico = $this->iaService->pronosticoDemanda($codigoCliente);
        $inventario = $this->iaService->optimizacionInventario();

        return view('clientes.ia-dashboard', [
            'resultadoPronostico' => $pronostico,
            'resultadoInventario' => $inventario,
        ]);
    }
}
