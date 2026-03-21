<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\ProveedorApiService;

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

    // Procesa el login con la API de Alan
    public function procesarLogin(Request $request)
    {
        $request->validate([
            'usuario'  => 'required',
            'password' => 'required',
        ]);

        $apiService = new ProveedorApiService();
        $resultado  = $apiService->login(
            $request->usuario,
            $request->password
        );

        if (isset($resultado['usuario']) && isset($resultado['tokencreado'])) {
            session([
                'proveedor_token'  => $resultado['tokencreado'],
                'proveedor_codigo' => $resultado['usuario']['codigo'],
                'proveedor_nombre' => $resultado['usuario']['nombre'],
                'proveedor_id'     => $resultado['usuario']['id'],
            ]);

            return redirect('/dashboard-proveedor')
                ->with('mensaje', 'Bienvenido ' . $resultado['usuario']['nombre']);
        }

        return back()->with('error', 'Credenciales incorrectas')->withInput();
    }

    // Guarda el proveedor nuevo
    public function guardar(Request $request)
    {
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

        return redirect('/cif')
            ->with('mensaje', 'Registro exitoso, por favor valida tus datos fiscales');
    }

    // Muestra el formulario de actualización
    public function mostrarActualizacion()
    {
        return view('proveedores.actualizacion');
    }

    // Guarda los cambios de actualización
    public function guardarActualizacion(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'correo'   => 'required|email',
            'password' => 'nullable|min:8|confirmed',
        ]);

        return redirect('/cif')
            ->with('mensaje', 'Datos actualizados, por favor valida tus datos fiscales');
    }
}