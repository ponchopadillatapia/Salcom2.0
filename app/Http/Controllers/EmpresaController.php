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
        // Llamar a la lógica real
        $api = new EmpresaApiController();

        $response = $api->validar($request);

        return $response;
    }
}