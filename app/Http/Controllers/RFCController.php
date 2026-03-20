<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class RFCController extends Controller
{
    public function vista()
    {
        return view('cif');
    }

    public function validar(Request $request)
    {
        $rfc = strtoupper($request->rfc);

        $regex = '/^([A-ZÑ&]{3,4})\d{6}([A-Z\d]{3})$/';

        if (!preg_match($regex, $rfc)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'RFC inválido'
            ]);
        }

        // Simulación tipo SAT
        return response()->json([
            'success' => true,
            'rfc' => $rfc,
            'nombre' => 'EMPRESA DEMO SA DE CV',
            'regimen' => 'General de Ley Personas Morales',
            'estatus' => 'ACTIVO'
        ]);
    }

    public function generarCIF(Request $request)
    {
        $data = [
            'rfc' => $request->rfc,
            'nombre' => 'EMPRESA DEMO SA DE CV',
            'regimen' => 'General de Ley Personas Morales',
            'fecha' => date('d/m/Y'),
            'mes' => date('F Y')
        ];

        $pdf = Pdf::loadView('pdf.cif', $data);

        return $pdf->download('CIF_'.$data['rfc'].'.pdf');
    }
}
