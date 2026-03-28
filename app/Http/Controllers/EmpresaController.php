<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\APIS\EmpresaApiController;

class EmpresaController extends Controller
{
    public function form()
    {
        return view('APIS.empresa');
    }

    public function guardar(Request $request)
    {
        // Aquí SOLO rediriges a la lógica real
        $api = new EmpresaApiController();

        return $api->validar($request);
    }
}