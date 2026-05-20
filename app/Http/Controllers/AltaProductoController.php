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
    private array $unidadesValidas = ['KG', 'LT', 'PZA', 'MT', 'ML', 'GR', 'GAL', 'TON', 'ROLLO', 'CAJA', 'PIEZA', 'LITRO', 'METRO', 'IN', 'CM', 'MM', 'M3', 'JUEGO', 'PAR', 'BOLSA', 'CUBETA', 'TAMBOR', 'SACO'];

    private array $columnasObligatorias = ['NOMBRE', 'FAMILIA', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO'];

    private array $familiasValidas = [
        'QUIMICOS', 'ELECTRICO', 'FERRETERIA', 'MANTENIMIENTO', 'SEGURIDAD',
        'EMPAQUE', 'MATERIA PRIMA', 'CONSUMIBLE', 'LUBRICANTES', 'ADHESIVOS',
        'PINTURAS', 'SOLVENTES', 'RESINAS', 'PIGMENTOS', 'ADITIVOS',
        'AEROSOLES', 'INSECTICIDAS', 'LIMPIEZA', 'HERRAMIENTAS', 'REFACCIONES',
        'MOTORES', 'BOMBAS', 'VALVULAS', 'TUBERIAS', 'TORNILLERIA',
        'MATERIAL EMPAQUE', 'PRODUCTO TERMINADO', 'INSUMOS',
    ];

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
     * Descargar template Excel (.xlsx) con validaciones internas.
     * - Dropdowns para FAMILIA, SUBFAMILIA, UNIDAD_MEDIDA
     * - Headers protegidos
     * - Ejemplos de referencia
     */
    public function descargarTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ═══ HOJA PRINCIPAL: Productos ═══
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        // Headers
        $headers = ['NOMBRE', 'FAMILIA', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO', 'MARCA', 'MODELO', 'MEDIDA', 'MATERIAL', 'COLOR', 'VOLTAJE', 'ESPECIFICACIONES'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('6B3FA0');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Ejemplos (filas 2-6)
        $ejemplos = [
            ['RESINA EPOXICA INDUSTRIAL 500ML', 'RESINAS', 'ADHESIVOS', 'KG', '150.50', 'GENERICO', 'IND-500', '500ML', 'LIQUIDO', '', '', 'USO INDUSTRIAL'],
            ['MOTOR WEG 3HP 220/440V TRIFASICO', 'MOTORES', 'ELECTRICO', 'PZA', '8500.00', 'WEG', 'W22', '3HP', 'ACERO', '', '220/440V', 'TRIFASICO C40'],
            ['BALERO SKF 6205 2RS', 'REFACCIONES', 'MANTENIMIENTO', 'PZA', '185.00', 'SKF', '6205', '25MM', 'ACERO', '', '', 'DOBLE SELLO'],
            ['INSECTICIDA MT XTERM BIO S/AROMA 180G C/12', 'AEROSOLES', 'INSECTICIDAS', 'CAJA', '320.00', 'XTERM', 'BIO', '180G/274ML', '', '', '', 'SIN AROMA CAJA 12 PIEZAS'],
            ['SOLVENTE GRADO TECNICO 20LT INDUSTRIAL', 'SOLVENTES', 'QUIMICOS', 'LT', '42.50', 'GENERICO', 'GT-20', '20LT', 'LIQUIDO', '', '', 'ALTA PUREZA'],
        ];

        foreach ($ejemplos as $rowIdx => $ejemplo) {
            $col = 'A';
            foreach ($ejemplo as $val) {
                $sheet->setCellValue($col . ($rowIdx + 2), $val);
                $sheet->getStyle($col . ($rowIdx + 2))->getFont()->getColor()->setRGB('888888');
                $col++;
            }
        }

        // ═══ HOJA OCULTA: Listas de validación ═══
        $listSheet = $spreadsheet->createSheet();
        $listSheet->setTitle('_Listas');

        // Familias
        $familias = $this->familiasValidas;
        foreach ($familias as $i => $fam) {
            $listSheet->setCellValue('A' . ($i + 1), $fam);
        }

        // Unidades
        $unidades = $this->unidadesValidas;
        foreach ($unidades as $i => $uni) {
            $listSheet->setCellValue('B' . ($i + 1), $uni);
        }

        // Ocultar la hoja de listas
        $listSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        // ═══ VALIDACIONES en hoja principal ═══
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // Dropdown FAMILIA (columna B, filas 2-100)
        $familiaCount = count($familias);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('B' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Familia no válida');
            $validation->setError('Selecciona una familia del catálogo oficial.');
            $validation->setFormula1('_Listas!$A$1:$A$' . $familiaCount);
        }

        // Dropdown UNIDAD_MEDIDA (columna D, filas 2-100)
        $unidadCount = count($unidades);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('D' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Unidad no válida');
            $validation->setError('Selecciona una unidad de medida oficial.');
            $validation->setFormula1('_Listas!$B$1:$B$' . $unidadCount);
        }

        // Validación PRECIO (columna E) — solo números > 0
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('E' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DECIMAL);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Precio inválido');
            $validation->setError('El precio debe ser un número mayor a 0.');
            $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_GREATERTHAN);
            $validation->setFormula1('0');
        }

        // Validación NOMBRE (columna A) — máximo 80 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('A' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Nombre muy largo');
            $validation->setError('El nombre no debe exceder 80 caracteres.');
            $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('80');
        }

        // Validación FAMILIA (columna B) — máximo 30 caracteres
        // (ya tiene dropdown, no necesita longitud adicional)

        // Validación MARCA (columna F) — máximo 30 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('F' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Marca muy larga');
            $validation->setError('La marca no debe exceder 30 caracteres.');
            $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('30');
        }

        // Validación MODELO (columna G) — máximo 30 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('G' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Modelo muy largo');
            $validation->setError('El modelo no debe exceder 30 caracteres.');
            $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('30');
        }

        // Validación ESPECIFICACIONES (columna L) — máximo 100 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('L' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Especificaciones muy largas');
            $validation->setError('Las especificaciones no deben exceder 100 caracteres.');
            $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('100');
        }

        // ═══ HOJA DE INSTRUCCIONES ═══
        $instrSheet = $spreadsheet->createSheet();
        $instrSheet->setTitle('Instrucciones');
        $instrSheet->setCellValue('A1', 'REGLAS PARA LLENAR EL TEMPLATE');
        $instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $reglas = [
            'NOMBRE: Mínimo 3 palabras en MAYÚSCULAS. Formato: [TIPO] [MARCA] [MODELO/MEDIDA]',
            'NOMBRE: Sin acentos ni símbolos raros. Solo letras, números, / - . ()',
            'FAMILIA: Seleccionar del dropdown (catálogo oficial)',
            'SUBFAMILIA: Texto libre en MAYÚSCULAS',
            'UNIDAD_MEDIDA: Seleccionar del dropdown (KG, LT, PZA, CAJA, etc.)',
            'PRECIO: Número con punto decimal (ej: 150.50)',
            'MARCA: En MAYÚSCULAS sin acentos',
            '',
            'EJEMPLOS CORRECTOS:',
            '  RESINA EPOXICA INDUSTRIAL 500ML',
            '  MOTOR WEG 3HP 220/440V TRIFASICO',
            '  BALERO SKF 6205 2RS',
            '  INSECTICIDA MT XTERM BIO S/AROMA 180G C/12',
            '',
            'EJEMPLOS INCORRECTOS:',
            '  resina (minúsculas)',
            '  Producto (muy genérico)',
            '  Résina epóxica (acentos)',
            '  MOTOR (solo 1 palabra)',
        ];

        foreach ($reglas as $i => $regla) {
            $instrSheet->setCellValue('A' . ($i + 3), $regla);
        }
        $instrSheet->getColumnDimension('A')->setWidth(70);

        // Volver a la hoja principal
        $spreadsheet->setActiveSheetIndex(0);

        // Generar y descargar
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $tempFile = tempnam(sys_get_temp_dir(), 'template_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'Template_Alta_Producto_Salcom.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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
            return back()->with('error', 'El archivo está vacío o no tiene el formato correcto. Descarga el template y úsalo como base. Las columnas obligatorias son: NOMBRE, FAMILIA, SUBFAMILIA, UNIDAD_MEDIDA, PRECIO.');
        }

        // Verificar que el archivo tenga las columnas correctas
        $primeraFila = $productos[0] ?? [];
        $columnasPresentes = array_keys($primeraFila);
        $columnasFaltantes = array_diff($this->columnasObligatorias, $columnasPresentes);
        if (!empty($columnasFaltantes)) {
            return back()->with('error', 'El archivo no tiene las columnas correctas. Faltan: ' . implode(', ', $columnasFaltantes) . '. Descarga el template oficial y úsalo como base.');
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

        // ═══ VALIDACIÓN CON IA (Claude) — Capa inteligente ═══
        // Después de las reglas básicas, Claude revisa contexto y coherencia
        try {
            $iaService = new \App\Services\IaService;
            $productosParaIA = array_slice($productos, 0, 20); // Máximo 20 para no exceder tokens

            $prompt = "Eres el sistema de validación de productos de Industrias Salcom, una empresa manufacturera mexicana.

Revisa estos productos que un proveedor quiere dar de alta. Para cada uno indica si hay problemas de:
1. Coherencia (ej: un MOTOR en familia QUIMICOS no tiene sentido)
2. Nomenclatura incompleta (debe ser: [TIPO] [MARCA] [MODELO/MEDIDA] [ESPECIFICACIÓN])
3. Posibles duplicados entre ellos mismos
4. Datos sospechosos (precio muy alto/bajo para el tipo de producto)

Productos a revisar:
" . json_encode($productosParaIA, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

Responde SOLO en formato JSON así (sin markdown, sin explicaciones extra):
{\"errores_ia\": [{\"fila\": 2, \"campo\": \"NOMBRE\", \"error\": \"descripción del problema\"}]}

Si todo está bien, responde: {\"errores_ia\": []}";

            $resultado = $iaService->llamarClaude($prompt);

            if ($resultado['success'] && $resultado['content']) {
                // Intentar parsear la respuesta JSON de Claude
                $contenido = $resultado['content'];
                // Limpiar posible markdown
                $contenido = preg_replace('/```json\s*/', '', $contenido);
                $contenido = preg_replace('/```\s*/', '', $contenido);
                $contenido = trim($contenido);

                $iaResult = json_decode($contenido, true);
                if ($iaResult && !empty($iaResult['errores_ia'])) {
                    foreach ($iaResult['errores_ia'] as $errIA) {
                        $fila = $errIA['fila'] ?? 0;
                        if ($fila >= 2) {
                            $errores[] = [
                                'fila' => $fila,
                                'campo' => $errIA['campo'] ?? 'GENERAL',
                                'error' => '[IA] ' . ($errIA['error'] ?? 'Error detectado por IA'),
                            ];
                            // Solo incrementar conError si esta fila no tenía errores antes
                            $yaTeníaError = false;
                            foreach ($errores as $e) {
                                if ($e['fila'] === $fila && !str_starts_with($e['error'], '[IA]')) {
                                    $yaTeníaError = true;
                                    break;
                                }
                            }
                            if (!$yaTeníaError) {
                                $conError++;
                                $validos = max(0, $validos - 1);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Si Claude falla, seguimos con las reglas básicas (no bloqueamos el flujo)
            \Illuminate\Support\Facades\Log::warning("[Alta Producto] IA no disponible: " . $e->getMessage());
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

        // Tiene errores — generar Excel con correcciones (coloreado)
        $fullPath = $this->generarExcelConErrores($productos, $errores);
        $relativePath = str_replace(storage_path('app/public/'), '', $fullPath);

        $errorMsg = "El Excel tiene {$conError} productos con errores. La IA rechazó el archivo.\n\n";
        $errorMsg .= "Descarga el Excel con correcciones — las celdas con error están marcadas en rojo.\n\n";
        $errorMsg .= "Errores encontrados:\n";
        foreach (array_slice($errores, 0, 15) as $err) {
            $errorMsg .= "• Fila {$err['fila']}: {$err['campo']} — {$err['error']}\n";
        }
        if (count($errores) > 15) {
            $errorMsg .= "\n... y " . (count($errores) - 15) . " errores más (ver Excel).";
        }

        return back()
            ->with('error', $errorMsg)
            ->with('archivo_correcciones', $relativePath);
    }

    /**
     * Generar Excel XLSX con colores — celdas con error en rojo, aprobadas en verde.
     */
    private function generarExcelConErrores(array $productos, array $errores): string
    {
        // Agrupar errores por fila
        $erroresPorFila = [];
        foreach ($errores as $err) {
            $erroresPorFila[$err['fila']][] = ['campo' => $err['campo'], 'error' => $err['error']];
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Validación IA');

        // Headers
        $headers = ['NOMBRE', 'FAMILIA', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO', 'MARCA', 'MODELO', 'MEDIDA', 'MATERIAL', 'COLOR', 'VOLTAJE', 'ESPECIFICACIONES', 'ESTATUS_IA', 'ERRORES_IA'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4A4A4A');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Mapeo de columnas para colorear celdas con error
        $colMap = ['NOMBRE' => 'A', 'FAMILIA' => 'B', 'SUBFAMILIA' => 'C', 'UNIDAD_MEDIDA' => 'D', 'PRECIO' => 'E', 'MARCA' => 'F', 'MODELO' => 'G', 'MEDIDA' => 'H', 'MATERIAL' => 'I', 'COLOR' => 'J', 'VOLTAJE' => 'K', 'ESPECIFICACIONES' => 'L'];

        // Datos
        foreach ($productos as $index => $producto) {
            $fila = $index + 2;
            $excelRow = $index + 2;

            $col = 'A';
            foreach (['NOMBRE', 'FAMILIA', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO', 'MARCA', 'MODELO', 'MEDIDA', 'MATERIAL', 'COLOR', 'VOLTAJE', 'ESPECIFICACIONES'] as $campo) {
                $sheet->setCellValue($col . $excelRow, $producto[$campo] ?? '');
                $col++;
            }

            if (isset($erroresPorFila[$fila])) {
                // RECHAZADO — fila con errores
                $sheet->setCellValue('M' . $excelRow, 'RECHAZADO');
                $sheet->getStyle('M' . $excelRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF4444');
                $sheet->getStyle('M' . $excelRow)->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('M' . $excelRow)->getFont()->setBold(true);

                $mensajesError = array_map(fn($e) => $e['campo'] . ': ' . $e['error'], $erroresPorFila[$fila]);
                $sheet->setCellValue('N' . $excelRow, implode(' | ', $mensajesError));
                $sheet->getStyle('N' . $excelRow)->getFont()->getColor()->setRGB('CC0000');

                // Colorear las celdas específicas que tienen error
                foreach ($erroresPorFila[$fila] as $err) {
                    if (isset($colMap[$err['campo']])) {
                        $cellRef = $colMap[$err['campo']] . $excelRow;
                        $sheet->getStyle($cellRef)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
                        $sheet->getStyle($cellRef)->getFont()->getColor()->setRGB('CC0000');
                    }
                }
            } else {
                // APROBADO
                $sheet->setCellValue('M' . $excelRow, 'APROBADO');
                $sheet->getStyle('M' . $excelRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('44CC44');
                $sheet->getStyle('M' . $excelRow)->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('M' . $excelRow)->getFont()->setBold(true);
                $sheet->setCellValue('N' . $excelRow, '');
            }
        }

        // Auto-size columns
        foreach (range('A', 'N') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Guardar como XLSX
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempPath = storage_path('app/public/excel-correcciones/Correcciones_' . date('Y-m-d_His') . '.xlsx');

        // Crear directorio si no existe
        $dir = dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Validar un producto individual — Reglas de estandarización industrial.
     *
     * Nomenclatura: [TIPO] + [MARCA] + [MODELO/MEDIDA] + [ESPECIFICACIÓN]
     * Ejemplo correcto: BALERO SKF 6205 2RS
     * Ejemplo correcto: MOTOR WEG 3HP 220/440V TRIFASICO
     * Ejemplo correcto: INSECTICIDA MT XTERM BIO S/AROMA 180G/274ML C/12
     */
    private function validarProducto(array $producto, int $fila): array
    {
        $errores = [];

        // ═══ 1. CAMPOS OBLIGATORIOS ═══
        foreach ($this->columnasObligatorias as $campo) {
            if (empty(trim($producto[$campo] ?? ''))) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => $campo,
                    'error' => "Campo obligatorio vacío",
                ];
            }
        }

        $nombre = trim($producto['NOMBRE'] ?? '');
        $familia = strtoupper(trim($producto['FAMILIA'] ?? ''));
        $subfamilia = strtoupper(trim($producto['SUBFAMILIA'] ?? ''));
        $unidad = strtoupper(trim($producto['UNIDAD_MEDIDA'] ?? ''));
        $precio = $producto['PRECIO'] ?? '';

        // ═══ 2. NOMENCLATURA — Reglas estrictas ═══
        if ($nombre) {
            // Debe ser MAYÚSCULAS
            if ($nombre !== mb_strtoupper($nombre)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => "Debe estar en MAYÚSCULAS y seguir el orden: [TIPO] + [MARCA] + [MODELO] + [MEDIDA] + [ESPECIFICACIÓN]. Recibido: '{$nombre}'. Ejemplo correcto: IPHONE APPLE 18PRO CELULAR",
                ];
            }

            // Sin acentos ni caracteres especiales (excepto / - . números)
            $nombreLimpio = Str::ascii($nombre);
            if ($nombre !== $nombreLimpio) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => "No usar acentos ni caracteres especiales. Usa: '{$nombreLimpio}'",
                ];
            }

            // Mínimo 3 palabras (TIPO + MARCA + algo más)
            $palabras = explode(' ', trim($nombre));
            if (count($palabras) < 3) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => "Nomenclatura incompleta. Formato obligatorio: [TIPO] + [MARCA] + [MODELO] + [MEDIDA] + [ESPECIFICACIÓN]. Ejemplo: MOTOR WEG 3HP 220/440V TRIFASICO",
                ];
            }

            // No usar palabras ambiguas o genéricas solas
            $palabrasProhibidas = ['PRODUCTO', 'MATERIAL', 'ARTICULO', 'COSA', 'ITEM', 'PIEZA GENERICA', 'VARIOS', 'OTRO', 'MISCELANEO'];
            foreach ($palabrasProhibidas as $prohibida) {
                if ($nombre === $prohibida || $nombre === strtoupper($prohibida)) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => 'NOMBRE',
                        'error' => "Nombre demasiado genérico ('{$nombre}'). Orden obligatorio: [TIPO] + [MARCA] + [MODELO] + [MEDIDA] + [ESPECIFICACIÓN]. Ejemplo: BALERO SKF 6205 2RS",
                    ];
                    break;
                }
            }

            // No usar abreviaciones no estándar (detectar patrones sospechosos)
            if (preg_match('/\b[A-Z]{1}\b/', $nombre) && !preg_match('/\b[CXLM]\//', $nombre)) {
                // Letras sueltas que no son parte de medidas (C/12, X, L, M)
                // Solo advertencia si hay muchas letras sueltas
            }

            // Verificar que no tenga símbolos raros
            if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`]/', $nombre)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => "Contiene símbolos no permitidos. Solo se aceptan: letras, números, / - . ()",
                ];
            }
        }

        // ═══ 3. FAMILIA — Debe ser de la lista oficial ═══
        if ($familia && !in_array($familia, $this->familiasValidas)) {
            $sugerencia = $this->buscarFamiliaSimilar($familia);
            $errores[] = [
                'fila' => $fila,
                'campo' => 'FAMILIA',
                'error' => "Familia '{$familia}' no está en el catálogo oficial." .
                    ($sugerencia ? " ¿Quisiste decir '{$sugerencia}'?" : " Familias válidas: " . implode(', ', array_slice($this->familiasValidas, 0, 10)) . '...'),
            ];
        }

        // ═══ 4. UNIDAD DE MEDIDA — Lista oficial ═══
        if ($unidad && !in_array($unidad, $this->unidadesValidas)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Unidad '{$unidad}' no válida. Unidades oficiales: " . implode(', ', $this->unidadesValidas),
            ];
        }

        // ═══ 5. PRECIO — Numérico y razonable ═══
        if ($precio) {
            $precioNum = (float) str_replace([',', '$'], '', $precio);
            if (!is_numeric(str_replace([',', '$'], '', $precio))) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PRECIO',
                    'error' => "El precio debe ser numérico (ej: 150.50). Recibido: '{$precio}'",
                ];
            } elseif ($precioNum <= 0) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PRECIO',
                    'error' => "El precio debe ser mayor a 0",
                ];
            } elseif ($precioNum > 1000000) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PRECIO',
                    'error' => "Precio sospechosamente alto (\${$precioNum}). Verifica que sea correcto.",
                ];
            }
        }

        // ═══ 6. DUPLICADOS INTELIGENTES ═══
        if ($nombre) {
            // Buscar duplicado exacto
            $nombreNorm = strtoupper(str_replace(' ', '', Str::ascii($nombre)));
            $existe = Producto::whereRaw("UPPER(REPLACE(nombre, ' ', '')) = ?", [$nombreNorm])->exists();
            if ($existe) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => "DUPLICADO: Este producto ya existe en el catálogo",
                ];
            } else {
                // Buscar similares (primeras 3 palabras iguales = posible duplicado)
                $primeras3 = implode(' ', array_slice(explode(' ', strtoupper(Str::ascii($nombre))), 0, 3));
                if (strlen($primeras3) > 5) {
                    $similar = Producto::where('nombre', 'LIKE', $primeras3 . '%')->first();
                    if ($similar) {
                        $errores[] = [
                            'fila' => $fila,
                            'campo' => 'NOMBRE',
                            'error' => "POSIBLE DUPLICADO: Ya existe '{$similar->nombre}' ({$similar->codigo}). Verifica que no sea el mismo producto.",
                        ];
                    }
                }
            }
        }

        // ═══ 7. CAMPOS OPCIONALES — Validar formato si vienen ═══
        $marca = trim($producto['MARCA'] ?? '');
        if ($marca && $marca !== strtoupper(Str::ascii($marca))) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'MARCA',
                'error' => "La marca debe estar en MAYÚSCULAS sin acentos",
            ];
        }

        return $errores;
    }

    /**
     * Buscar familia similar (para sugerencias).
     */
    private function buscarFamiliaSimilar(string $familia): ?string
    {
        $mejorMatch = null;
        $mejorScore = 0;

        foreach ($this->familiasValidas as $valida) {
            similar_text($familia, $valida, $score);
            if ($score > $mejorScore && $score > 60) {
                $mejorScore = $score;
                $mejorMatch = $valida;
            }
        }

        return $mejorMatch;
    }

    /**
     * Leer archivo CSV.
     */
    private function leerCSV(string $path): array
    {
        $productos = [];
        $handle = fopen($path, 'r');

        // Limpiar BOM si existe
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            return [];
        }

        // Normalizar headers (quitar BOM residual, espacios, poner mayúsculas)
        $headers = array_map(fn ($h) => strtoupper(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h))), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row)) === 0) {
                continue;
            }
            $producto = [];
            foreach ($headers as $i => $header) {
                if ($header) {
                    $producto[$header] = $row[$i] ?? '';
                }
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
