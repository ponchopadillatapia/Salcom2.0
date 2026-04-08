<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'       => 'required|string|max:255',
            'tipo_persona' => 'required|string|max:255',
            'telefono'     => 'required|string|max:20',
            'correo'       => 'required|email|unique:proveedores_users,correo',
            'password'     => 'required|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'      => 'El nombre es obligatorio.',
            'tipo_persona.required'=> 'El tipo de persona es obligatorio.',
            'telefono.required'    => 'El teléfono es obligatorio.',
            'correo.required'      => 'El correo es obligatorio.',
            'correo.email'         => 'El correo no es válido.',
            'correo.unique'        => 'Este correo ya está registrado.',
            'password.required'    => 'La contraseña es obligatoria.',
            'password.min'         => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed'   => 'Las contraseñas no coinciden.',
        ];
    }
}
