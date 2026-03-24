<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Carbon\Carbon;

class EmpresaController extends Controller
{
    public function form()
    {
        return view('APIS.empresa');
    }
public function guardar(Request $request)
{
    $request->validate([
        'rfc' => 'required',
        'nombre' => 'required',
        'opinion_pdf' => 'required|mimes:pdf',
        'acta_pdf' => 'required|mimes:pdf',
    ]);

    // Guardar PDF
    $rutaPDF = $request->file('opinion_pdf')->store('opiniones');

    // Leer PDF
    $parser = new Parser();
    $pdf = $parser->parseFile(storage_path('app/'.$rutaPDF));
    $texto = strtoupper($pdf->getText());

    // Validar palabra POSITIVA
    $esPositiva = str_contains($texto, 'POSITIVA');

    // Buscar fecha en el PDF
    preg_match('/\d{2}\/\d{2}\/\d{4}/', $texto, $matches);

    $fechaValida = false;

    if ($matches) {
        $fechaPDF = Carbon::createFromFormat('d/m/Y', $matches[0]);
        $hoy = Carbon::now();

        if ($fechaPDF->month == $hoy->month && $fechaPDF->year == $hoy->year) {
            $fechaValida = true;
        }
    }

    // Resultado final
    if ($esPositiva && $fechaValida) {
        $estado = 'correcto';
        $mensaje = 'Opinión positiva del mes actual';
    } elseif ($esPositiva) {
        $estado = 'advertencia';
        $mensaje = 'Es positiva pero no es del mes actual';
    } else {
        $estado = 'error';
        $mensaje = 'Opinión no positiva';
    }

    // Guardar acta
    $request->file('acta_pdf')->store('actas');

    return back()->with([
        'mensaje' => $mensaje,
        'estado' => $estado
    ]);
    
}
}
