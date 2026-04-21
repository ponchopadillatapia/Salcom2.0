<?php

namespace App\Http\Controllers;

use App\Models\ContactoProveedor;
use App\Models\ProveedorUser;
use Illuminate\Http\Request;

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
        $proveedor = \App\Models\ProveedorUser::find(session('proveedor_id'));
        return view('proveedores.onboarding', compact('proveedor'));
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
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $contactos = $proveedor ? $proveedor->contactos()->orderBy('nombre')->get() : collect();

        return view('proveedores.perfil', compact('proveedor', 'contactos'));
    }

    // ── Contactos ──

    public function guardarContacto(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:255',
            'rol'      => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'correo'   => 'nullable|email|max:255',
        ]);

        ContactoProveedor::create([
            'proveedor_id' => session('proveedor_id'),
            'nombre'       => $request->nombre,
            'rol'          => $request->rol,
            'telefono'     => $request->telefono,
            'correo'       => $request->correo,
        ]);

        return back()->with('mensaje', 'Contacto agregado correctamente.');
    }

    public function eliminarContacto(ContactoProveedor $contacto)
    {
        if ($contacto->proveedor_id != session('proveedor_id')) {
            abort(403);
        }

        $contacto->delete();

        return back()->with('mensaje', 'Contacto eliminado.');
    }

    // ── Aviso de privacidad ──

    public function aceptarAvisoPrivacidad()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));

        if ($proveedor) {
            $proveedor->update([
                'aviso_privacidad_aceptado' => true,
                'aviso_privacidad_fecha'    => now(),
            ]);
        }

        return back()->with('mensaje', 'Aviso de privacidad aceptado correctamente.');
    }
}
