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
        ], [
            'nombre.required'      => 'El nombre es obligatorio.',
            'tipo_persona.required'=> 'El tipo de persona es obligatorio.',
            'telefono.required'    => 'El teléfono es obligatorio.',
            'correo.required'      => 'El correo es obligatorio.',
            'correo.email'         => 'El correo no es válido.',
            'correo.unique'        => 'Este correo ya está registrado.',
            'password.required'    => 'La contraseña es obligatoria.',
            'password.min'         => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed'   => 'Las contraseñas no coinciden.',
        ]);

        ProveedorUser::create([
            'usuario'      => $request->correo,
            'password'     => bcrypt($request->password),
            'nombre'       => $request->nombre,
            'tipo_persona' => $request->tipo_persona,
            'telefono'     => $request->telefono,
            'correo'       => $request->correo,
        ]);

        return redirect('/login-proveedor')
            ->with('mensaje', 'Registro exitoso, ahora puedes iniciar sesión');
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
        ], [
            'nombre.required'    => 'El nombre es obligatorio.',
            'telefono.required'  => 'El teléfono es obligatorio.',
            'correo.required'    => 'El correo es obligatorio.',
            'correo.email'       => 'El correo no es válido.',
            'password.min'       => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'cif.mimes'          => 'El CIF debe ser un archivo PDF.',
            'cif.max'            => 'El CIF no debe superar 5MB.',
            'opinion_positiva.mimes' => 'La Opinión Positiva debe ser un archivo PDF.',
            'opinion_positiva.max'   => 'La Opinión Positiva no debe superar 5MB.',
            'acta_constitutiva.mimes'=> 'El Acta Constitutiva debe ser un archivo PDF.',
            'acta_constitutiva.max'  => 'El Acta Constitutiva no debe superar 5MB.',
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

            $carpeta = 'documentos/' . $proveedor->id;

            if ($request->hasFile('cif')) {
                $request->file('cif')->storeAs($carpeta, 'cif.pdf', 'local');
            }

            if ($request->hasFile('opinion_positiva')) {
                $request->file('opinion_positiva')->storeAs($carpeta, 'opinion_positiva.pdf', 'local');
            }

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