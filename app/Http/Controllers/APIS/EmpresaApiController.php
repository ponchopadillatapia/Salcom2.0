<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
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
        // LEER CIF
        // =========================
        $pathCif = storage_path('app/' . $cifRuta);

        if (!file_exists($pathCif)) {
    return response()->json([
        'mensaje' => 'El archivo CIF no existe',
        'ruta' => $pathCif
    ]);
}

        try {
            $pdfCif = $parser->parseFile($pathCif);
            $textoCif = strtoupper($pdfCif->getText());
        } catch (\Exception $e) {
    return response()->json([
        'mensaje' => 'Error leyendo PDF CIF',
        'error_real' => $e->getMessage(),
        'ruta' => $pathCif
    ]);
}

        // =========================
        // VALIDACIÓN REAL CIF + RFC
        // =========================

        preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/', $textoCif, $rfcMatch);
        $rfc = $rfcMatch[1] ?? null;

        $rfcValido = $this->validarRFC($rfc);

        preg_match('/NOMBRE.*?:\s*([A-Z\s]+)/', $textoCif, $nombreMatch);
        $nombre = isset($nombreMatch[1]) ? trim($nombreMatch[1]) : null;

        preg_match('/\d{2}\/\d{2}\/\d{4}/', $textoCif, $fechaMatch);

        $fechaValida = false;

        if ($fechaMatch) {
            $fecha = Carbon::createFromFormat('d/m/Y', $fechaMatch[0]);
            $hoy = Carbon::now();

            $fechaValida = ($fecha->month == $hoy->month && $fecha->year == $hoy->year);
        }

        $esDocumentoSAT = str_contains($textoCif, 'CONSTANCIA DE SITUACION FISCAL');

        $esCifValido = $esDocumentoSAT && $rfcValido && $nombre;

        // =========================
        // LEER OPINIÓN
        // =========================
        $pathOp = storage_path('app/' . $opinionRuta);

        $pdfOp = $parser->parseFile($pathOp);
        $textoOp = strtoupper($pdfOp->getText());

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
        if ($esCifValido && $positiva && $vigente) {
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
                'rfc_valido' => $rfcValido ? 'SI' : 'NO',
                'nombre' => $nombre ?? 'NO DETECTADO',
                'cif_valido' => $esCifValido ? 'valido' : 'invalido',
                'cif_mes_actual' => $fechaValida ? 'SI' : 'NO',
                'estado' => $estado
            ]
        ]);
    }

    // ✅ SOLO ESTA FUNCIÓN FUE MEJORADA
    private function validarRFC($rfc)
    {
        if (!$rfc) return false;

        $rfc = strtoupper(trim($rfc));

        // Validar formato
        if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc)) {
            return false;
        }

        // Validar fecha interna
        $fecha = substr($rfc, strlen($rfc) == 12 ? 3 : 4, 6);

        $anio = substr($fecha, 0, 2);
        $mes = substr($fecha, 2, 2);
        $dia = substr($fecha, 4, 2);

        if (!checkdate((int)$mes, (int)$dia, (int)("20".$anio))) {
            return false;
        }

        // Validar dígito verificador SAT
        $diccionario = "0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ";

        $rfcSinDigito = substr($rfc, 0, -1);
        $digitoVerificador = substr($rfc, -1);

        $longitud = strlen($rfcSinDigito);
        $suma = 0;

        for ($i = 0; $i < $longitud; $i++) {
            $valor = strpos($diccionario, $rfcSinDigito[$i]);
            $peso = $longitud + 1 - $i;
            $suma += $valor * $peso;
        }

        $residuo = $suma % 11;
        $digitoEsperado = 11 - $residuo;

        if ($digitoEsperado == 11) {
            $digitoEsperado = 0;
        } elseif ($digitoEsperado == 10) {
            $digitoEsperado = 'A';
        }

        return (string)$digitoVerificador === (string)$digitoEsperado;
    }
}