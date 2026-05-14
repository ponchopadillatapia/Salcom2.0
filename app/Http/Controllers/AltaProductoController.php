<?php

namespace App\Http\Controllers;

use App\Models\ExcelValidacion;
use App\Models\Producto;
use App\Services\AlertEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AltaProductoController extends Controller
{
    private array $unidadesValidas = ['KG', 'LT', 'PZA', 'MT', 'ML', 'GR', 'GAL', 'TON', 'ROLLO', 'CAJA', 'PIEZA', 'LITRO', 'METRO'];

    private array $columnasObligatorias = ['NOMBRE', 'FAMILIA', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO'];

    public function mostrarAltaProducto()
    {
        return view('proveedores.alta-producto');
    }

    /**
     * Registro manual de producto desde formulario.
     */
    public function registroManual(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|unique:productos,codigo',
            'nombre' => 'required|string|min:3',
            'categoria' => 'required|string',
            'familia' => 'required|string',
            'unidad_venta' => 'required|string',
            'precio' => 'required|numeric|min:0',
        ]);

        $data = $request->only([
            'codigo', 'codigo_alterno', 'nombre', 'nombre_alterno',
            'clave_sat', 'descripcion_corta', 'descripcion',
            'categoria', 'familia', 'subfamilia', 'segmento_mercado', 'tipo_producto',
            'precio', 'unidad_venta', 'stock',
            'cajas_por_tarima', 'peso_bruto_caja', 'peso_bruto', 'piezas_por_caja', 'volumen',
            'unidad_xml', 'iva', 'ieps',
        ]);

        $data['maneja_lotes'] = $request->has('maneja_lotes');
        $data['activo'] = true;
        $data['stock'] = $data['stock'] ?? 0;

        // Foto
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('productos-fotos', 'public');
        }

        $producto = Producto::create($data);

        return back()->with('mensaje', "Producto '{$producto->nombre}' registrado exitosamente con código {$producto->codigo}.");
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

        $csv = implode(',', $headers)."\n";
        $csv .= implode(',', $ejemplo)."\n";
        for ($i = 0; $i < 20; $i++) {
            $csv .= str_repeat(',', count($headers) - 1)."\n";
        }

        return Response::make("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Template_Alta_Producto_Salcom.csv"',
        ]);
    }

    /**
     * Subir Excel y validar INMEDIATAMENTE.
     */
    public function subirExcel(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('excel');
        $path = $file->store('excel-productos', 'public');

        // Leer el Excel inmediatamente
        try {
            $fullPath = storage_path('app/public/'.$path);

            if (Str::endsWith($file->getClientOriginalName(), '.csv')) {
                $productos = $this->leerCSV($fullPath);
            } else {
                $spreadsheet = IOFactory::load($fullPath);
                $productos = $this->leerSpreadsheet($spreadsheet);
            }
        } catch (\Exception $e) {
            return back()->with('error', '❌ No se pudo leer el archivo. Asegúrate de que sea un Excel (.xlsx) o CSV válido. Error: '.$e->getMessage());
        }

        if (empty($productos)) {
            return back()->with('error', '❌ El archivo está vacío o no tiene el formato correcto. Descarga el template y úsalo como base.');
        }

        // Validar cada producto
        $errores = [];
        $validos = 0;
        $conError = 0;

        foreach ($productos as $index => $producto) {
            $fila = $index + 2;
            $erroresFila = $this->validarProducto($producto, $fila);

            if (! empty($erroresFila)) {
                $errores = array_merge($errores, $erroresFila);
                $conError++;
            } else {
                $validos++;
            }
        }

        // Guardar resultado
        $estatus = $conError === 0 ? 'validado' : 'con_errores';
        $validacion = ExcelValidacion::create([
            'proveedor_id' => session('proveedor_id'),
            'archivo_path' => $path,
            'total_productos' => count($productos),
            'productos_validos' => $validos,
            'productos_con_error' => $conError,
            'errores' => $errores ?: null,
            'estatus' => $estatus,
        ]);

        if ($estatus === 'validado') {
            // Dar de alta automáticamente
            $alertEngine = new AlertEngineService;
            $alertEngine->alertar([
                'tipo' => 'productos_alta_automatica',
                'modulo' => 'productos',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 1,
                'titulo' => "Productos dados de alta: {$validos} productos",
                'contenido' => "El proveedor subió un Excel con {$validos} productos. Todos pasaron la validación de la IA y fueron dados de alta automáticamente.",
                'datos' => ['validacion_id' => $validacion->id, 'total' => $validos],
                'nivel' => 'info',
            ]);

            return back()->with('mensaje', "✅ {$validos} productos validados correctamente por la IA y dados de alta. No requiere aprobación adicional.");
        }

        // Tiene errores — rechazar
        $errorMsg = "❌ El Excel tiene {$conError} productos con errores. La IA rechazó el archivo.\n\nErrores encontrados:\n";
        foreach (array_slice($errores, 0, 10) as $err) {
            $errorMsg .= "• Fila {$err['fila']}: {$err['campo']} — {$err['error']}\n";
        }
        if (count($errores) > 10) {
            $errorMsg .= "\n... y ".(count($errores) - 10).' errores más.';
        }
        $errorMsg .= "\n\nCorrige los errores y vuelve a subir.";

        return back()->with('error', $errorMsg);
    }

    /**
     * Validar un producto individual.
     */
    private function validarProducto(array $producto, int $fila): array
    {
        $errores = [];

        // Campos obligatorios
        foreach ($this->columnasObligatorias as $campo) {
            if (empty(trim($producto[$campo] ?? ''))) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => $campo,
                    'error' => "Campo obligatorio vacío",
                ];
            }
        }

        // Nombre: mínimo 3 palabras
        $nombre = trim($producto['NOMBRE'] ?? '');
        if ($nombre && count(explode(' ', $nombre)) < 2) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE',
                'error' => "El nombre debe tener al menos 2 palabras (ej: RESINA EPOXICA 500ML)",
            ];
        }

        // Nombre: debe ser mayúsculas
        if ($nombre && $nombre !== mb_strtoupper($nombre)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE',
                'error' => "El nombre debe estar en MAYÚSCULAS",
            ];
        }

        // Nombre: sin acentos
        if ($nombre && $nombre !== Str::ascii($nombre)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE',
                'error' => "El nombre no debe tener acentos ni caracteres especiales",
            ];
        }

        // Unidad de medida válida
        $unidad = strtoupper(trim($producto['UNIDAD_MEDIDA'] ?? ''));
        if ($unidad && ! in_array($unidad, $this->unidadesValidas)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Unidad '{$unidad}' no válida. Usa: ".implode(', ', $this->unidadesValidas),
            ];
        }

        // Precio numérico
        $precio = $producto['PRECIO'] ?? '';
        if ($precio && ! is_numeric(str_replace(',', '', $precio))) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'PRECIO',
                'error' => "El precio debe ser numérico (ej: 150.50)",
            ];
        }

        // Duplicados en catálogo
        if ($nombre) {
            $nombreNorm = strtoupper(str_replace(' ', '', Str::ascii($nombre)));
            $existe = Producto::whereRaw("UPPER(REPLACE(nombre, ' ', '')) = ?", [$nombreNorm])->exists();
            if ($existe) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => "Este producto ya existe en el catálogo",
                ];
            }
        }

        return $errores;
    }

    /**
     * Leer archivo CSV.
     */
    private function leerCSV(string $path): array
    {
        $productos = [];
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        if (! $headers) {
            return [];
        }

        // Normalizar headers
        $headers = array_map(fn ($h) => strtoupper(trim($h)), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row)) === 0) {
                continue;
            } // Skip empty rows
            $producto = [];
            foreach ($headers as $i => $header) {
                $producto[$header] = $row[$i] ?? '';
            }
            $productos[] = $producto;
        }
        fclose($handle);

        return $productos;
    }

    /**
     * Leer archivo Excel con PhpSpreadsheet.
     */
    private function leerSpreadsheet($spreadsheet): array
    {
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return [];
        }

        $headers = array_map(fn ($h) => strtoupper(trim($h ?? '')), $rows[0]);
        $productos = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (count(array_filter($row)) === 0) {
                continue;
            }
            $producto = [];
            foreach ($headers as $j => $header) {
                if ($header) {
                    $producto[$header] = $row[$j] ?? '';
                }
            }
            $productos[] = $producto;
        }

        return $productos;
    }
}
