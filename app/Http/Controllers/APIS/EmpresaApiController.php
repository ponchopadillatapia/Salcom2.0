<?php
namespace App\Http\Controllers\APIS;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class EmpresaApiController extends Controller
{
    public function validar(Request $request)
    {
        try {
            $request->validate([
                'cif_pdf'     => 'required|mimes:pdf',
                'opinion_pdf' => 'required|mimes:pdf',
                'acta_pdf'    => 'required|mimes:pdf',
            ]);

            $parser = new Parser();

            // Guardar archivos en disco local
            $cifRuta     = $request->file('cif_pdf')->store('cif', 'local');
            $opinionRuta = $request->file('opinion_pdf')->store('opiniones', 'local');
            $actaRuta    = $request->file('acta_pdf')->store('actas', 'local');

            // Rutas absolutas para leer
            $pathCif   = storage_path('app/private/cif/' . basename($cifRuta));
            $pathOp    = storage_path('app/private/opiniones/' . basename($opinionRuta));
            $pathActa  = storage_path('app/private/actas/' . basename($actaRuta));

            // --- LEER CIF ---
            $textoCif = strtoupper($parser->parseFile($pathCif)->getText());

            preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/', $textoCif, $rfcMatch);
            $rfc = $rfcMatch[1] ?? null;

            preg_match('/NOMBRE.?:\s([A-Z\s]+)/', $textoCif, $nombreMatch);
            $nombre = isset($nombreMatch[1]) ? trim($nombreMatch[1]) : 'DESCONOCIDO';

            $esCifValido = str_contains($textoCif, 'CONSTANCIA DE SITUACION FISCAL');
            $rfcValido   = $this->validarRFC($rfc);

            // --- LEER OPINIÓN ---
            $textoOp   = strtoupper($parser->parseFile($pathOp)->getText());
            $erroresOp = [];

            if (!str_contains($textoOp, 'CUMPLIMIENTO DE OBLIGACIONES FISCALES')) $erroresOp[] = 'No es documento SAT';
            if (!str_contains($textoOp, 'POSITIVA')) $erroresOp[] = 'No es positiva';
            if ($rfc && !str_contains($textoOp, $rfc)) $erroresOp[] = 'RFC no coincide';

            // --- LEER ACTA ---
            $textoActa   = strtoupper($parser->parseFile($pathActa)->getText());
            $erroresActa = [];

            if (!str_contains($textoActa, 'ACTA') && !str_contains($textoActa, 'CONSTITUTIVA')) $erroresActa[] = 'No parece Acta Constitutiva';
            if ($rfc && !str_contains($textoActa, $rfc)) $erroresActa[] = 'RFC no encontrado en Acta';

            // --- SEMÁFORO ---
            $opinionOk = empty($erroresOp);
            $actaOk    = empty($erroresActa);

            if ($esCifValido && $opinionOk && $actaOk) {
                $estado = 'verde';
            } elseif ($opinionOk) {
                $estado = 'amarillo';
            } else {
                $estado = 'rojo';
            }

            return response()->json([
                'empresa' => [
                    'rfc'       => $rfc,
                    'nombre'    => $nombre,
                    'rfc_valido'=> $rfcValido ? 'SI' : 'NO',
                    'cif_valido'=> $esCifValido ? 'SI' : 'NO',
                    'estado'    => $estado
                ],
                'opinion' => [
                    'valida'  => $opinionOk,
                    'errores' => $erroresOp
                ],
                'acta' => [
                    'valida'  => $actaOk,
                    'errores' => $erroresActa
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function validarRFC($rfc)
    {
        if (!$rfc) return false;
        $regex = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/';
        return preg_match($regex, $rfc);
    }
}