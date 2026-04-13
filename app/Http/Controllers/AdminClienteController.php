<?php

namespace App\Http\Controllers;

use App\Models\ClienteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminClienteController extends Controller
{
    public function mostrarAlta()
    {
        return view('clientes.alta-cliente');
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'nombre'       => 'required|string|max:255',
            'correo'       => 'required|email|unique:clientes_users,correo',
            'usuario'      => 'required|string|unique:clientes_users,usuario',
            'password'     => 'required|min:8',
            'telefono'     => 'nullable|string|max:20',
            'rfc'          => 'nullable|string|max:13',
            'tipo_persona' => 'required|string',
            'tipo_cliente' => 'required|string',
            'codigo_cliente' => 'nullable|string',
            'limite_credito' => 'nullable|numeric|min:0',
        ]);

        ClienteUser::create([
            'nombre'             => $request->nombre,
            'correo'             => $request->correo,
            'usuario'            => $request->usuario,
            'password'           => Hash::make($request->password),
            'telefono'           => $request->telefono,
            'rfc'                => $request->rfc,
            'tipo_persona'       => $request->tipo_persona,
            'tipo_cliente'       => $request->tipo_cliente,
            'codigo_cliente'     => $request->codigo_cliente,
            'limite_credito'     => $request->limite_credito,
            'credito_autorizado' => $request->has('credito_autorizado'),
        ]);

        return back()->with('mensaje', 'Cliente dado de alta correctamente: ' . $request->usuario);
    }
}
