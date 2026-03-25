<?php
namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Carbon\Carbon;

class EmpresaApiController extends Controller
{
    public function validar(Request $request)
    {
        $request->validate([
            'cif_pdf' => 'required|mimes:pdf',
            'opinion_pdf' => 'required|mimes:pdf',
            'acta_pdf' => 'required|mimes:pdf',
        ]);

        // Guardar archivos
        $cifRuta = $request->file('cif_pdf')->store('cif');
        $opinionRuta = $request->file('opinion_pdf')->store('opiniones');
        $request->file('acta_pdf')->store('actas');

        // Leer CIF
        $parser = new Parser();
        $pdfCif = $parser->parseFile(storage_path('app/'.$cifRuta));
        $textoCif = strtoupper($pdfCif->getText());

        preg_match('/[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}/', $textoCif, $rfcMatch);
        preg_match('/NOMBRE[:\s]+([A-Z\s]+)/', $textoCif, $nombreMatch);

        $rfc = $rfcMatch[0] ?? null;
        $nombre = $nombreMatch[1] ?? null;
        $tipo = $rfc ? (strlen($rfc) == 12 ? 'Moral' : 'Física') : null;

        // Leer Opinión
        $pdfOp = $parser->parseFile(storage_path('app/'.$opinionRuta));
        $textoOp = strtoupper($pdfOp->getText());

        $positiva = str_contains($textoOp, 'POSITIVA');

        preg_match('/\d{2}\/\d{2}\/\d{4}/', $textoOp, $fechaMatch);

        $vigente = false;
        if ($fechaMatch) {
            $fecha = Carbon::createFromFormat('d/m/Y', $fechaMatch[0]);
            $hoy = Carbon::now();
            $vigente = ($fecha->month == $hoy->month && $fecha->year == $hoy->year);
        }

        // Semáforo
        if ($rfc && $positiva && $vigente) {
            $estado = 'verde';
        } elseif ($positiva) {
            $estado = 'amarillo';
        } else {
            $estado = 'rojo';
        }

        return response()->json([
        'mensaje' => 'SI FUNCIONA',
        'empresa' => [
            'rfc' => 'PRUEBA123',
            'nombre' => 'EMPRESA DEMO',
            'tipo' => 'Moral',
            'estado' => 'verde'
        ]
    ]);
    }
}