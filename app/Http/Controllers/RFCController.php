<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RFCController extends Controller
{
    public function validarRFC_API(Request $request)
    {
        $rfc = $request->input('rfc');

        // Validación de formato primero
        if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc)) {
            return response()->json([
                'valido' => false,
                'mensaje' => 'Formato de RFC incorrecto'
            ]);
        }

        // Simulación de API (puedes cambiar por una real)
        // Ejemplo:
        /*
        $response = Http::get('https://api.com/rfc', [
            'rfc' => $rfc
        ]);
        */

        // Simulación de respuesta
        return response()->json([
            'valido' => true,
            'mensaje' => 'RFC válido',
            'rfc' => $rfc
        ]);
    }
}
