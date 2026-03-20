<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RFCController extends Controller
{
    public function vista()
    {
        return view('cif');
    }

    public function validar(Request $request)
    {
        $rfc = strtoupper($request->input('rfc'));

        $regex = '/^([A-ZÑ&]{3,4})\d{6}([A-Z\d]{3})$/';

        if (!preg_match($regex, $rfc)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'RFC inválido'
            ]);
        }

        return response()->json([
            'success' => true,
            'rfc' => $rfc,
            'nombre' => 'EMPRESA DEMO SA DE CV',
            'estatus' => 'ACTIVO',
            'regimen' => 'General de Ley Personas Morales'
        ]);
    }

    public function generarCIF(Request $request)
    {
        $rfc = $request->input('rfc');

        // Generar QR (base64)
        $qr = base64_encode(
            QrCode::format('png')->size(150)->generate(
                "RFC: $rfc\nVerificación interna"
            )
        );

        $data = [
            'rfc' => $rfc,
            'nombre' => 'EMPRESA DEMO SA DE CV',
            'regimen' => 'General de Ley Personas Morales',
            'fecha' => date('d/m/Y'),
            'qr' => $qr
        ];

        $pdf = Pdf::loadView('pdf.cif', $data);

        return $pdf->download('CIF_'.$rfc.'.pdf');
    }
}