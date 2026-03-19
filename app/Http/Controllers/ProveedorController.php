<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        // Verifica el captcha con Google
        $recaptcha = Http::post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret_key'),
            'response' => $request->input('g-recaptcha-response'),
        ])->json();

        if (!$recaptcha['success']) {
            return back()
                ->withErrors(['g-recaptcha-response' => 'Captcha inválido, inténtalo de nuevo'])
                ->withInput();
        }

        $request->validate([
            'nombre'   => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'correo'   => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Por ahora confirmamos que todo llega bien
        return response()->json($request->except('password', 'password_confirmation'));
    }
}