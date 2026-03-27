<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\ProveedorApiService;
use App\Models\ProveedorUser;

class ProveedorController extends Controller
{
    public function mostrarLogin()
    {
        return view('proveedores.login');
    }

    public function mostrarRegistro()
    {
        return view('proveedores.registro');
    }

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

        return redirect('/portal-proveedor')
            ->with('mensaje', 'Bienvenido ' . $proveedor->nombre);
    }

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

    public function mostrarActualizacion()
    {
        return view('proveedores.actualizacion');
    }

    public function guardarActualizacion(Request $request)
    {
        $request->validate([
            'nombre'            => 'required|string|max:255',
            'tipo_persona'      => 'required|string|max:255',
            'telefono'          => 'required|string|max:20',
            'correo'            => 'required|email',
            'password'          => 'nullable|min:8|confirmed',
            'cif'               => 'nullable|file|mimes:pdf|max:5120',
            'opinion_positiva'  => 'nullable|file|mimes:pdf|max:5120',
            'acta_constitutiva' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $proveedor = ProveedorUser::find(session('proveedor_id'));

        if ($proveedor) {
            // Actualiza datos básicos
            $proveedor->update([
                'nombre'       => $request->nombre,
                'tipo_persona' => $request->tipo_persona,
                'telefono'     => $request->telefono,
                'correo'       => $request->correo,
            ]);

            // Actualiza contraseña si se envió
            if ($request->password) {
                $proveedor->update(['password' => bcrypt($request->password)]);
            }

            // Carpeta del proveedor
            $carpeta = 'documentos/' . $proveedor->id;

            // Guarda CIF
            if ($request->hasFile('cif')) {
                $request->file('cif')->storeAs($carpeta, 'cif.pdf', 'local');
            }

            // Guarda Opinión Positiva
            if ($request->hasFile('opinion_positiva')) {
                $request->file('opinion_positiva')->storeAs($carpeta, 'opinion_positiva.pdf', 'local');
            }

            // Guarda Acta Constitutiva
            if ($request->hasFile('acta_constitutiva')) {
                $request->file('acta_constitutiva')->storeAs($carpeta, 'acta_constitutiva.pdf', 'local');
            }
        }

        return redirect('/portal-proveedor')
            ->with('mensaje', 'Datos actualizados correctamente');
    }

    public function mostrarDashboard()
    {
        return view('proveedores.dashboard');
    }

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

    public function mostrarPortal()
    {
        return view('proveedores.portal');
    }
}