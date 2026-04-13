<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginClienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'usuario'  => 'required',
            'password' => 'required',
        ];
    }
}
