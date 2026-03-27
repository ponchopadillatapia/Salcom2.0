<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Carbon\Carbon;

class EmpresaApiController extends Controller
{
    public function form()
{
    return view('APIS.empresa');
}
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

        $parser = new Parser();

        // =========================
// VALIDACIÓN REAL CIF
// =========================

$esDocumentoSAT = str_contains($textoCif, 'CONSTANCIA DE SITUACION FISCAL');

// RFC
preg_match('/[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}/', $textoCif, $rfcMatch);
$rfc = $rfcMatch[0] ?? null;

// Nombre
preg_match('/NOMBRE.*?:\s*([A-Z\s]+)/', $textoCif, $nombreMatch);
$nombre = isset($nombreMatch[1]) ? trim($nombreMatch[1]) : null;

// Fecha
preg_match('/\d{2}\/\d{2}\/\d{4}/', $textoCif, $fechaMatch);

$fechaValida = false;

if ($fechaMatch) {
    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaMatch[0]);
    $hoy = \Carbon\Carbon::now();

    $fechaValida = ($fecha->month == $hoy->month && $fecha->year == $hoy->year);
}

// Resultado CIF
$esCifValido = $esDocumentoSAT && $rfc && $nombre;
$esCifValidoMes = $esCifValido && $fechaValida;
        // =========================
        // LEER OPINIÓN
        // =========================
        $pathOp = storage_path('app/' . $opinionRuta);

        $pdfOp = $parser->parseFile($pathOp);
        $textoOp = strtoupper($pdfOp->getText());

        // OCR también para opinión si viene escaneada
        if (trim($textoOp) == '') {

            $imagenOp = storage_path('app/opiniones/temp.png');

            exec("magick convert -density 300 \"$pathOp\" \"$imagenOp\"");

            $textoOp = (new TesseractOCR($imagenOp))
                ->executable("C:\\Program Files\\Tesseract-OCR\\tesseract.exe")
                ->lang('spa')
                ->run();

            $textoOp = strtoupper($textoOp);
        }

        $positiva = str_contains($textoOp, 'POSITIVA');

        preg_match('/\d{2}\/\d{2}\/\d{4}/', $textoOp, $fechaMatch);

        $vigente = false;

        if ($fechaMatch) {
            $fecha = Carbon::createFromFormat('d/m/Y', $fechaMatch[0]);
            $hoy = Carbon::now();

            $vigente = ($fecha->month == $hoy->month && $fecha->year == $hoy->year);
        }

        // =========================
        // SEMÁFORO
        // =========================
        if ($esCif && $rfc !== 'NO DETECTADO' && $positiva && $vigente) {
            $estado = 'verde';
        } elseif ($positiva) {
            $estado = 'amarillo';
        } else {
            $estado = 'rojo';
        }

        return response()->json([
    'mensaje' => $esCifValido ? 'CIF válido' : 'CIF inválido',
    'empresa' => [
        'rfc' => $rfc ?? 'NO DETECTADO',
        'nombre' => $nombre ?? 'NO DETECTADO',
        'cif_valido' => $esCifValido ? 'valido' : 'invalido',
        'cif_mes_actual' => $fechaValida ? 'SI' : 'NO',
        'estado' => $esCifValidoMes ? 'verde' : 'rojo'
    ]
]);
    }
}