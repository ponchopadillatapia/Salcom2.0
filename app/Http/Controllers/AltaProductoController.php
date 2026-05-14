<?php

namespace App\Http\Controllers;

use App\Jobs\ValidarExcelProducto;
use App\Models\ExcelValidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AltaProductoController extends Controller
{
    public function mostrarAltaProducto()
    {
        return view('proveedores.alta-producto');
    }

    /**
     * Descargar template Excel (CSV con headers).
     */
    public function descargarTemplate()
    {
        $headers = [
            'NOMBRE', 'FAMILIA', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO',
            'MARCA', 'MODELO', 'MEDIDA', 'MATERIAL', 'COLOR', 'VOLTAJE', 'ESPECIFICACIONES',
        ];

        $ejemplo = [
            'RESINA EPOXICA INDUSTRIAL 500ML', 'QUIMICOS', 'RESINAS', 'KG', '150.50',
            'GENERICO', 'IND-500', '500ML', 'LIQUIDO', '', '', 'USO INDUSTRIAL',
        ];

        $csv = implode(',', $headers) . "\n";
        $csv .= implode(',', $ejemplo) . "\n";
        // Filas vacías para que llenen
        for ($i = 0; $i < 20; $i++) {
            $csv .= str_repeat(',', count($headers) - 1) . "\n";
        }

        return Response::make("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Template_Alta_Producto_Salcom.csv"',
        ]);
    }

    /**
     * Subir Excel para validación por IA.
     */
    public function subirExcel(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('excel');
        $path = $file->store('excel-productos', 'public');

        // Crear registro de validación
        $validacion = ExcelValidacion::create([
            'proveedor_id' => session('proveedor_id'),
            'archivo_path' => $path,
            'estatus' => 'procesando',
        ]);

        // Despachar job de validación
        ValidarExcelProducto::dispatch($validacion->id);

        return back()->with('mensaje', '✅ Excel subido correctamente. La IA está validando tu archivo. Te notificaremos cuando esté listo.');
    }
}
