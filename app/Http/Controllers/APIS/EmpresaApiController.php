<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use App\Services\DocumentValidationService;
use Illuminate\Http\Request;

class EmpresaApiController extends Controller
{
    private DocumentValidationService $validationService;

    public function __construct(DocumentValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function validar(Request $request)
    {
        try {
            $request->validate([
                'cif_pdf'            => 'required|mimes:pdf|max:10240',
                'opinion_pdf'        => 'required|mimes:pdf|max:10240',
                'acta_pdf'           => 'required|mimes:pdf|max:10240',
                'rep_legal_pdf'      => 'required|mimes:pdf|max:10240',
                'contribuyente_pdf'  => 'required|mimes:pdf|max:10240',
                'caratula_banco_pdf' => 'required|mimes:pdf|max:10240',
            ]);

            $archivos = [
                'cif'            => $request->file('cif_pdf')->store('cif', 'local'),
                'opinion'        => $request->file('opinion_pdf')->store('opiniones', 'local'),
                'acta'           => $request->file('acta_pdf')->store('actas', 'local'),
                'rep_legal'      => $request->file('rep_legal_pdf')->store('rep_legal', 'local'),
                'contribuyente'  => $request->file('contribuyente_pdf')->store('contribuyente', 'local'),
                'caratula_banco' => $request->file('caratula_banco_pdf')->store('caratula_banco', 'local'),
            ];

            $resultado = $this->validationService->validarTodos($archivos);

            return response()->json($resultado);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errores = collect($e->errors())->flatten()->implode(' | ');
            return response()->json(['mensaje' => 'Archivo inválido: ' . $errores], 422);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
