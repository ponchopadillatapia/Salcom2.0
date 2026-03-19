<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    // Muestra el formulario de login
    public function mostrarLogin()
    {
        return view('proveedores.login');
    }

    // Muestra el formulario de registro
    public function mostrarRegistro()
    {
        return view('proveedores.registro');
    }

    // Guarda el proveedor nuevo en la base de datos
    public function guardar(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'correo'   => 'required|email',
            'password' => 'required|min:8',
        ]);

        // Por ahora solo mostramos los datos para verificar que llegan
        return response()->json($request->all());
    }
}
