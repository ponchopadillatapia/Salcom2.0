<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required',
            'pwd'    => 'required',
        ];
    }
}
