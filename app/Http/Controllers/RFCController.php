<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RFCController extends Controller
{
    public function validar(Request $request)
    {
        $rfc = strtoupper($request->input('rfc'));

        // Validación de formato RFC (persona física y moral)
        $regex = '/^([A-ZÑ&]{3,4})\d{6}([A-Z\d]{3})$/';

        if (!preg_match($regex, $rfc)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'RFC inválido (formato incorrecto)'
            ]);
        }

        // Simulación tipo SAT
        return response()->json([
            'success' => true,
            'rfc' => $rfc,
            'nombre' => 'EMPRESA DEMO SA DE CV',
            'estatus' => 'ACTIVO',
            'regimen' => 'General de Ley Personas Morales',
            'mensaje' => 'RFC válido en padrón SAT'
        ]);
    }
}
