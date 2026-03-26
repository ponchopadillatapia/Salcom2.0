<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Services\ProveedorApiService;
use App\Models\ProveedorUser;

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

    // Procesa el login con usuario y contraseña propios
    public function procesarLogin(Request $request)
    {
        $request->validate([
            'codigo' => 'required',
            'pwd'    => 'required',
        ]);

        $proveedor = ProveedorUser::where('usuario', $request->codigo)->first();

        if (!$proveedor || !Hash::check($request->pwd, $proveedor->password)) {
            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        session([
            'proveedor_id'     => $proveedor->id,
            'proveedor_nombre' => $proveedor->nombre,
            'proveedor_codigo' => $proveedor->codigo_compras,
            'proveedor_correo' => $proveedor->correo,
        ]);

        return redirect('/dashboard-proveedor')
            ->with('mensaje', 'Bienvenido ' . $proveedor->nombre);
    }

    // Guarda el proveedor nuevo en la BD
    public function guardar(Request $request)
    {
        // CAPTCHA DESACTIVADO TEMPORALMENTE PARA PRUEBAS
        // $recaptcha = Http::post('https://www.google.com/recaptcha/api/siteverify', [
        //     'secret'   => config('services.recaptcha.secret_key'),
        //     'response' => $request->input('g-recaptcha-response'),
        // ])->json();

        // if (!$recaptcha['success']) {
        //     return back()
        //         ->withErrors(['g-recaptcha-response' => 'Captcha inválido, inténtalo de nuevo'])
        //         ->withInput();
        // }

        $request->validate([
            'nombre'       => 'required|string|max:255',
            'tipo_persona' => 'required|string|max:255',
            'telefono'     => 'required|string|max:20',
            'correo'       => 'required|email|unique:proveedores_users,correo',
            'password'     => 'required|min:8|confirmed',
        ]);

        ProveedorUser::create([
            'usuario'      => $request->correo,
            'password'     => bcrypt($request->password),
            'nombre'       => $request->nombre,
            'tipo_persona' => $request->tipo_persona,
            'telefono'     => $request->telefono,
            'correo'       => $request->correo,
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
            'nombre'       => 'required|string|max:255',
            'tipo_persona' => 'required|string|max:255',
            'telefono'     => 'required|string|max:20',
            'correo'       => 'required|email',
            'password'     => 'nullable|min:8|confirmed',
        ]);

        $proveedor = ProveedorUser::find(session('proveedor_id'));

        if ($proveedor) {
            $proveedor->update([
                'nombre'       => $request->nombre,
                'tipo_persona' => $request->tipo_persona,
                'telefono'     => $request->telefono,
                'correo'       => $request->correo,
            ]);

            if ($request->password) {
                $proveedor->update(['password' => bcrypt($request->password)]);
            }
        }

        return redirect('/cif')
            ->with('mensaje', 'Datos actualizados, por favor valida tus datos fiscales');
    }

    public function mostrarDashboard()
    {
        return view('proveedores.dashboard');
    }

    // Cierra la sesión del proveedor
    public function cerrarSesion()
    {
    session()->forget([
        'proveedor_id',
        'proveedor_nombre',
        'proveedor_codigo',
        'proveedor_correo',
    ]);

    return redirect('/login-proveedor')
        ->with('mensaje', 'Sesión cerrada correctamente');
    }

}