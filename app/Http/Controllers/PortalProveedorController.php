<?php

namespace App\Http\Controllers;

class PortalProveedorController extends Controller
{
    public function mostrarPortal()
    {
        return view('proveedores.portal');
    }

    public function mostrarDashboard()
    {
        return view('proveedores.dashboard');
    }

    public function mostrarOnboarding()
    {
        return view('proveedores.onboarding');
    }

    public function mostrarBusiness()
    {
        return view('proveedores.business');
    }

    public function mostrarPaymentHistory()
    {
        return view('proveedores.payment-history');
    }

    public function mostrarPerfil()
    {
        return view('proveedores.perfil');
    }
}
