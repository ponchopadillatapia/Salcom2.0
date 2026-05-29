<?php

namespace App\Http\Controllers;

use App\Models\ExcelValidacion;
use App\Models\Producto;
use App\Services\AlertEngineService;
use App\Services\IaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AltaProductoController extends Controller
{
    private array $unidadesValidas = ['KG', 'PZA', 'CAJA'];

    private array $columnasObligatorias = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA'];

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

    public function mostrarAltaProductoAdmin()
    {
        return view('admin.alta-producto');
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

        return back()->with('mensaje', "Producto '{$producto->nombre}' registrado exitosamente con codigo {$producto->codigo}.");
    }

    /**
     * Descargar template Excel (.xlsx) con validaciones internas.
     * - Dropdowns para FAMILIA, SUBFAMILIA, UNIDAD_MEDIDA
     * - Headers protegidos
     * - Ejemplos de referencia
     */
    public function descargarTemplate()
    {
        $spreadsheet = new Spreadsheet;

        // â*â*â* HOJA PRINCIPAL: Productos â*â*â*
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        // Headers â€" sin columnas redundantes
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE'];
        $obligatorios = ['CODIGO' => true, 'NOMBRE_TIPO' => true, 'NOMBRE_MARCA' => true, 'NOMBRE_MODELO' => true, 'NOMBRE_MEDIDA' => true, 'NOMBRE_ESPECIFICACION' => true, 'FAMILIA' => true, 'TIPO_PRODUCTO' => true, 'UNIDAD_MEDIDA' => true, 'PRECIO' => false, 'CLAVE_SAT' => false, 'LOTE' => false, 'PEDIMENTO' => false, 'VOLTAJE' => false];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            if ($obligatorios[$header] ?? false) {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6B3FA0');
            } else {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('9B7BC7');
            }
            $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Ejemplos (filas 2-6)
        $ejemplos = [
            ['MPI0538', 'PINTURA VINILICA', 'COMEX', 'VIN-100', '19LT', 'BLANCO MATE INTERIOR', 'MATERIA PRIMA', 'MPI', 'KG', '$450.00', '10191509', 'SI', 'SI', 'N/A'],
            ['ME0201', 'CAJA CARTON', 'BIOPAPPEL', 'BC-300', '40X30X25', 'CORRUGADA DOBLE PARED', 'MATERIAL EMPAQUE', 'ME', 'PZA', '$22.50', '48191500', 'NO', 'NO', 'N/A'],
            ['MN0045', 'BOMBA AGUA', 'TRUPER', 'BOAG-1HP', '1HP', 'CENTRIFUGA 127V', 'MANTENIMIENTO', 'MN', 'PZA', '$2,800.00', '26101500', 'NO', 'NO', '127V'],
            ['MPI0539', 'ACEITE MOTOR', 'PEMEX', 'ULTRA-5W30', '5LT', 'SINTETICO GASOLINA', 'MATERIA PRIMA', 'MPI', 'KG', '$380.00', '12161800', 'SI', 'SI', 'N/A'],
            ['ME0202', 'CINTA ADHESIVA', 'JANEL', 'TR-48', '48MMX150M', 'TRANSPARENTE EMPAQUE', 'MATERIAL EMPAQUE', 'ME', 'CAJA', '$95.00', '55121600', 'NO', 'NO', 'N/A'],
        ];

        foreach ($ejemplos as $rowIdx => $ejemplo) {
            $col = 'A';
            foreach ($ejemplo as $val) {
                $sheet->setCellValue($col.($rowIdx + 2), $val);
                $sheet->getStyle($col.($rowIdx + 2))->getFont()->getColor()->setRGB('888888');
                $col++;
            }
        }

        // â*â*â* HOJA OCULTA: Listas de validacion â*â*â*
        $listSheet = $spreadsheet->createSheet();
        $listSheet->setTitle('_Listas');

        // Familias
        $familias = $this->familiasValidas;
        foreach ($familias as $i => $fam) {
            $listSheet->setCellValue('A'.($i + 1), $fam);
        }

        // Unidades
        $unidades = $this->unidadesValidas;
        foreach ($unidades as $i => $uni) {
            $listSheet->setCellValue('B'.($i + 1), $uni);
        }

        // Ocultar la hoja de listas
        $listSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        // â*â*â* VALIDACIONES en hoja principal â*â*â*
        // Columnas: A=CODIGO, B=NOMBRE_TIPO, C=NOMBRE_MARCA, D=NOMBRE_MODELO, E=NOMBRE_MEDIDA,
        //           F=NOMBRE_ESPECIFICACION, G=FAMILIA, H=TIPO_PRODUCTO, I=UNIDAD_MEDIDA,
        //           J=PRECIO, K=CLAVE_SAT, L=LOTE, M=PEDIMENTO, N=VOLTAJE
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // Dropdown FAMILIA (columna G)
        $familiaCount = count($familias);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('G'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Familia no valida');
            $validation->setError('Selecciona una familia del catalogo oficial.');
            $validation->setFormula1('_Listas!$A$1:$A$'.$familiaCount);
        }

        // Dropdown UNIDAD_MEDIDA (columna I)
        $unidadCount = count($unidades);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('I'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Unidad no valida');
            $validation->setError('Solo: KG, PZA o CAJA');
            $validation->setFormula1('_Listas!$B$1:$B$'.$unidadCount);
        }

        // Dropdown TIPO_PRODUCTO (columna H)
        $listSheet->setCellValue('C1', 'MPI');
        $listSheet->setCellValue('C2', 'ME');
        $listSheet->setCellValue('C3', 'MN');
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('H'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Tipo de producto no valido');
            $validation->setError('Valores validos: MPI (Materia Prima Importacion), ME (Material Empaque), MN (Mantenimiento)');
            $validation->setFormula1('_Listas!$C$1:$C$3');
        }

        // Dropdown LOTE (columna L)
        $listSheet->setCellValue('D1', 'SI');
        $listSheet->setCellValue('D2', 'NO');
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('L'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Valor no valido');
            $validation->setError('Solo SI o NO');
            $validation->setFormula1('_Listas!$D$1:$D$2');
        }

        // Dropdown PEDIMENTO (columna M)
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('M'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Valor no valido');
            $validation->setError('Solo SI o NO');
            $validation->setFormula1('_Listas!$D$1:$D$2');
        }

        // Dropdown VOLTAJE (columna N)
        $listSheet->setCellValue('E1', '110V');
        $listSheet->setCellValue('E2', '127V');
        $listSheet->setCellValue('E3', '220V');
        $listSheet->setCellValue('E4', '220/440V');
        $listSheet->setCellValue('E5', '110/220V');
        $listSheet->setCellValue('E6', '440V');
        $listSheet->setCellValue('E7', '480V');
        $listSheet->setCellValue('E8', '12VDC');
        $listSheet->setCellValue('E9', '24VDC');
        $listSheet->setCellValue('E10', '3HP');
        $listSheet->setCellValue('E11', '5HP');
        $listSheet->setCellValue('E12', '10HP');
        $listSheet->setCellValue('E13', '60Hz');
        $listSheet->setCellValue('E14', 'N/A');
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('N'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Voltaje no valido');
            $validation->setError('Selecciona un voltaje del listado');
            $validation->setFormula1('_Listas!$E$1:$E$14');
        }

        // Validacion PRECIO (columna J)
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('J'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_DECIMAL);
            $validation->setErrorStyle(DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Precio invalido');
            $validation->setError('El precio debe ser un numero mayor a 0.');
            $validation->setOperator(DataValidation::OPERATOR_GREATERTHAN);
            $validation->setFormula1('0');
        }

        // â*â*â* HOJA DE INSTRUCCIONES â*â*â*
        $instrSheet = $spreadsheet->createSheet();
        $instrSheet->setTitle('Instrucciones');
        $instrSheet->getColumnDimension('A')->setWidth(90);

        $instrSheet->setCellValue('A1', 'INSTRUCCIONES â€" ALTA DE PRODUCTO');
        $instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instrSheet->getStyle('A1')->getFont()->getColor()->setRGB('6B3FA0');

        $row = 3;
        $reglas = [
            'â*â*â* COLORES DEL HEADER â*â*â*',
            'Morado oscuro = Obligatorio (siempre llenar)',
            'Morado claro = Opcional (puedes dejarlo vacio)',
            '',
            'â*â*â* COLUMNAS DEL EXCEL (en orden) â*â*â*',
            'CODIGO â€" Codigo unico del producto (ej: MPI0538, ME0201)',
            'NOMBRE_TIPO â€" Que es el producto (ej: MOTOR ELECTRICO, RESINA EPOXICA)',
            'NOMBRE_MARCA â€" Quien lo fabrica (ej: WEG, SKF, 3M, ALPHA)',
            'NOMBRE_MODELO â€" Referencia del fabricante (ej: W22, IND-500, CP-40)',
            'NOMBRE_MEDIDA â€" Tamano con numeros (ej: 500ML, 3HP, 40X30X25)',
            'NOMBRE_ESPECIFICACION â€" Detalle adicional (ej: TRIFASICO, TRANSPARENTE)',
            'FAMILIA â€" Seleccionar del dropdown (ej: MATERIA PRIMA, MANTENIMIENTO)',
            'TIPO_PRODUCTO â€" MPI (Materia Prima), ME (Empaque), MN (Mantenimiento)',
            'UNIDAD_MEDIDA â€" Solo KG, PZA o CAJA',
            'PRECIO â€" Opcional. Con $ y decimales (ej: $150.50)',
            'CLAVE_SAT â€" Opcional. Codigo SAT (ej: 10191509)',
            'LOTE â€" SI o NO. Obligatorio solo si TIPO_PRODUCTO = MPI',
            'PEDIMENTO â€" SI o NO. Obligatorio solo si TIPO_PRODUCTO = MPI',
            'VOLTAJE â€" Opcional. Seleccionar del dropdown (ej: 220V, 220/440V)',
            '',
            'â*â*â* QUE SIGNIFICAN MPI, ME Y MN â*â*â*',
            'MPI = Materia Prima Importacion â€" Requiere LOTE y PEDIMENTO',
            'ME = Material de Empaque â€" Cajas, etiquetas, bolsas',
            'MN = Mantenimiento â€" Motores, refacciones, herramientas',
            '',
            'â*â*â* REGLAS GENERALES â*â*â*',
            'Todo en MAYUSCULAS, sin acentos ni caracteres especiales',
            'NOMBRE_MEDIDA debe tener numeros (500ML, 3HP, 40X30X25)',
            'NOMBRE_TIPO no puede ser una marca (WEG, SKF van en NOMBRE_MARCA)',
            'NOMBRE_MARCA no puede ser una medida (3HP, 500ML van en NOMBRE_MEDIDA)',
            'No repetir productos que ya existen en el catalogo',
            '',
            'â*â*â* SI LA IA RECHAZA TU ARCHIVO â*â*â*',
            '1. Las celdas con error se marcan en ROJO en el Excel descargable',
            '2. En la pagina web te dice exactamente que corregir',
            '3. Corrige los campos marcados y vuelve a subir',
        ];

        foreach ($reglas as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
            if (str_starts_with($texto, 'â*â*â*')) {
                $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
            }
            $row++;
        }

        // Volver a la hoja principal
        $spreadsheet->setActiveSheetIndex(0);

        // Generar y descargar
        $writer = new Xlsx($spreadsheet);

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
            return back()->with('error', 'ERROR  No se pudo leer el archivo. Asegurate de que sea un Excel (.xlsx) o CSV valido. Error: '.$e->getMessage());
        }

        if (empty($productos)) {
            return back()->with('error', 'El archivo esta vacio o no tiene productos. Descarga el template, borra los ejemplos en gris y llena tus productos. Columnas obligatorias: CODIGO, NOMBRE_TIPO, NOMBRE_MARCA, NOMBRE_MODELO, NOMBRE_MEDIDA, NOMBRE_ESPECIFICACION, PRODUCCION, FAMILIA, TIPO_PRODUCTO, UNIDAD_MEDIDA, OBSERVACIONES.');
        }

        // Verificar que el archivo tenga las columnas correctas
        $primeraFila = $productos[0] ?? [];
        $columnasPresentes = array_keys($primeraFila);
        $columnasFaltantes = array_diff($this->columnasObligatorias, $columnasPresentes);
        if (! empty($columnasFaltantes)) {
            return back()->with('error', 'El archivo no tiene las columnas correctas. Faltan: '.implode(', ', $columnasFaltantes).'. Descarga el template oficial y usalo como base.');
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

        // === VALIDACION CON IA - TODAS LAS FILAS ===
        // La IA valida TODAS las filas para detectar campos cruzados
        // (ej: una especificacion en NOMBRE_TIPO, una medida repetida en NOMBRE_ESPECIFICACION)
        try {
            $iaService = new IaService;
            $productosParaIA = [];
            foreach ($productos as $index => $prod) {
                $fila = $index + 2;
                $productosParaIA[] = [
                    'fila_excel' => $fila,
                    'nombre_tipo' => $prod['NOMBRE_TIPO'] ?? '',
                    'nombre_marca' => $prod['NOMBRE_MARCA'] ?? '',
                    'nombre_modelo' => $prod['NOMBRE_MODELO'] ?? '',
                    'nombre_medida' => $prod['NOMBRE_MEDIDA'] ?? '',
                    'nombre_especificacion' => $prod['NOMBRE_ESPECIFICACION'] ?? '',
                    'familia' => $prod['FAMILIA'] ?? '',
                    'tipo_producto' => $prod['TIPO_PRODUCTO'] ?? '',
                ];
            }

            if (!empty($productosParaIA)) {
                // Enviar en lotes de 15 para no exceder tokens
                $lotes = array_chunk($productosParaIA, 15);
                foreach ($lotes as $lote) {
                    $prompt = 'Eres un validador estricto de nomenclatura industrial para productos de Salcom. Valida estos productos verificando que CADA CAMPO tenga el contenido CORRECTO segun su definicion:

DEFINICION DE CAMPOS:
- NOMBRE_TIPO = Que ES el producto de forma especifica (ej: TELEFONO CELULAR, PINTURA VINILICA, MOTOR ELECTRICO, LAPTOP, TABLET, CAJA CARTON). Debe ser el tipo/categoria del producto, NO una marca, NO una especificacion.
- NOMBRE_MARCA = La EMPRESA/FABRICANTE (ej: APPLE, SAMSUNG, COMEX, WEG, TRUPER). Es quien fabrica o vende el producto. NO confundir con lineas de producto (IPHONE no es marca, es linea de APPLE).
- NOMBRE_MODELO = Referencia o LINEA del fabricante (ej: IPHONE 15, PRO MAX, VIN-100, W22, GALAXY S24). Puede ser alfanumerico. Incluye nombres de linea de producto.
- NOMBRE_MEDIDA = Tamano/capacidad con NUMEROS (ej: 6.5 PULGADAS, 19LT, 500ML, 3HP, 256GB). DEBE tener numeros.
- NOMBRE_ESPECIFICACION = Caracteristicas TECNICAS adicionales que diferencian el producto (ej: COLOR NEGRO TITANIO, 256GB RAM 8GB, TRIFASICO 220V, CORRUGADA DOBLE PARED). Son detalles tecnicos, colores, capacidades, materiales.

TIPOS DE PRODUCTO YA APROBADOS (si NOMBRE_TIPO es alguno de estos, NO lo marques como error):
TELEFONO CELULAR, TELEFONO MOVIL, SMARTPHONE, PINTURA VINILICA, PINTURA ESMALTE, MOTOR ELECTRICO, BOMBA AGUA, BOMBA CENTRIFUGA, ACEITE MOTOR, CINTA ADHESIVA, CAJA CARTON, CAJA CORRUGADA, LAPTOP, TABLET, COMPUTADORA, IMPRESORA, MONITOR, TECLADO, MOUSE, CABLE ELECTRICO, FOCO LED, LAMPARA, TORNILLO, TUERCA, RESINA EPOXICA, PIGMENTO ORGANICO, SOLVENTE INDUSTRIAL, ADHESIVO ESTRUCTURAL, BOLSA PLASTICO, ETIQUETA ADHESIVA, FLEJE ACERO, TALADRO ROTOMARTILLO, INSECTICIDA, DETERGENTE INDUSTRIAL

REGLAS CRITICAS:
1. Si NOMBRE_ESPECIFICACION es IGUAL o muy similar a NOMBRE_MEDIDA = ERROR
2. Si NOMBRE_TIPO contiene SOLO adjetivos sin sustantivo (BLANCO, MATE, GRANDE) = ERROR
3. Si NOMBRE_TIPO es UNA SOLA PALABRA muy generica (CELULAR, PINTURA, MOTOR, CAJA) = ERROR, debe tener al menos 2 palabras
4. Cada campo debe tener informacion DISTINTA, no repetir datos de otro campo
5. NOMBRE_MODELO puede contener nombres de linea de producto (IPHONE, GALAXY, COROLLA) - esto NO es marca ni especificacion
6. NOMBRE_ESPECIFICACION debe ser algo que el producto TIENE (color, capacidad, material, voltaje)
7. Si NOMBRE_TIPO tiene 2+ palabras y describe QUE ES el producto = VALIDO (TELEFONO MOVIL, PINTURA VINILICA, MOTOR ELECTRICO son CORRECTOS, NO los rechaces)
8. NO rechaces un NOMBRE_TIPO que esta en la lista de tipos aprobados
9. MOVIL, ELECTRICO, VINILICA, CENTRIFUGA son calificadores validos del tipo, NO son "adjetivos de acabado"

OBLIGATORIO - SUGERENCIA:
Para CADA error, SIEMPRE incluye el campo "sugerencia" con el valor EXACTO que deberia ir en esa celda.
El proveedor va a COPIAR Y PEGAR tu sugerencia directamente, asi que debe ser el valor final correcto en MAYUSCULAS.
Usa TODA la informacion disponible del producto (los otros campos) para inferir la mejor sugerencia posible.

Productos: '.json_encode($lote, JSON_UNESCAPED_UNICODE).'

Responde UNICAMENTE JSON valido, sin markdown, sin texto extra:
{"errores_ia": [{"fila": N, "campo": "NOMBRE_X", "error": "explicacion", "sugerencia": "VALOR EXACTO PARA COPIAR"}]}
Si todo esta correcto: {"errores_ia": []}';

                    $resultado = $iaService->llamarClaude($prompt);
                    if ($resultado['success'] && $resultado['content']) {
                        $contenido = preg_replace('/```json\s*/', '', $resultado['content']);
                        $contenido = preg_replace('/```\s*/', '', $contenido);
                        $iaResult = json_decode(trim($contenido), true);
                        if ($iaResult && !empty($iaResult['errores_ia'])) {
                            // Lista de tipos aprobados - si la IA rechaza uno de estos, ignorar el error
                            $tiposAprobados = ['TELEFONO CELULAR', 'TELEFONO MOVIL', 'SMARTPHONE', 'PINTURA VINILICA', 'PINTURA ESMALTE', 'MOTOR ELECTRICO', 'BOMBA AGUA', 'BOMBA CENTRIFUGA', 'ACEITE MOTOR', 'CINTA ADHESIVA', 'CAJA CARTON', 'CAJA CORRUGADA', 'LAPTOP', 'TABLET', 'COMPUTADORA', 'IMPRESORA', 'MONITOR', 'TECLADO', 'MOUSE', 'CABLE ELECTRICO', 'FOCO LED', 'LAMPARA', 'TORNILLO', 'TUERCA', 'RESINA EPOXICA', 'PIGMENTO ORGANICO', 'SOLVENTE INDUSTRIAL', 'ADHESIVO ESTRUCTURAL', 'BOLSA PLASTICO', 'ETIQUETA ADHESIVA', 'FLEJE ACERO', 'TALADRO ROTOMARTILLO', 'INSECTICIDA', 'DETERGENTE INDUSTRIAL', 'CELULAR', 'TELEFONO'];

                            foreach ($iaResult['errores_ia'] as $errIA) {
                                $filaIA = (int) ($errIA['fila'] ?? 0);
                                if ($filaIA < 2) continue;
                                $campoIA = $errIA['campo'] ?? 'NOMBRE_TIPO';

                                // Si la IA rechaza NOMBRE_TIPO pero el valor esta en la lista aprobada, ignorar
                                if ($campoIA === 'NOMBRE_TIPO') {
                                    $idx = $filaIA - 2;
                                    if (isset($productos[$idx])) {
                                        $tipoActual = strtoupper(trim($productos[$idx]['NOMBRE_TIPO'] ?? ''));
                                        if (in_array($tipoActual, $tiposAprobados)) {
                                            continue; // Ignorar este error - el tipo es valido
                                        }
                                    }
                                }

                                // No duplicar errores que PHP ya detecto
                                $yaExiste = false;
                                foreach ($errores as $eEx) {
                                    if ($eEx['fila'] === $filaIA && $eEx['campo'] === $campoIA) { $yaExiste = true; break; }
                                }
                                if (!$yaExiste) {
                                    $mensajeError = 'IA: '.($errIA['error'] ?? 'Campo con contenido incorrecto');
                                    $sugerencia = $errIA['sugerencia'] ?? null;
                                    if ($sugerencia) {
                                        $mensajeError .= " || CORRECCION: {$sugerencia}";
                                    }
                                    $errores[] = ['fila' => $filaIA, 'campo' => $campoIA, 'error' => $mensajeError];
                                }
                            }
                        }
                    }
                }
            }

            // Recalcular conteos despues de IA
            $filasConErrorFinal = array_unique(array_column($errores, 'fila'));
            $conError = count($filasConErrorFinal);
            $validos = count($productos) - $conError;

        } catch (\Exception $e) {
            Log::warning('[Alta Producto] IA validacion: '.$e->getMessage());
        }

        // Guardar resultado
        $estatus = $conError === 0 ? 'validado' : 'con_errores';
        $validacion = ExcelValidacion::create([
            'proveedor_id' => session('proveedor_id') ?? session('admin_id'),
            'archivo_path' => $path,
            'total_productos' => count($productos),
            'productos_validos' => $validos,
            'productos_con_error' => $conError,
            'errores' => $errores ?: null,
            'estatus' => $estatus,
        ]);

        if ($estatus === 'validado') {
            // Dar de alta automaticamente â€" crear productos en la BD
            foreach ($productos as $prod) {
                $nombreCompleto = trim(
                    strtoupper(trim($prod['NOMBRE_TIPO'] ?? '')).' '.
                    strtoupper(trim($prod['NOMBRE_MARCA'] ?? '')).' '.
                    strtoupper(trim($prod['NOMBRE_MODELO'] ?? '')).' '.
                    strtoupper(trim($prod['NOMBRE_MEDIDA'] ?? '')).' '.
                    strtoupper(trim($prod['NOMBRE_ESPECIFICACION'] ?? ''))
                );

                Producto::updateOrCreate(
                    ['codigo' => strtoupper(trim($prod['CODIGO'] ?? ''))],
                    [
                        'nombre' => $nombreCompleto,
                        'categoria' => strtoupper(trim($prod['TIPO_PRODUCTO'] ?? '')),
                        'familia' => strtoupper(trim($prod['FAMILIA'] ?? '')),
                        'tipo_producto' => strtoupper(trim($prod['TIPO_PRODUCTO'] ?? '')),
                        'unidad_venta' => strtoupper(trim($prod['UNIDAD_MEDIDA'] ?? '')),
                        'precio' => (float) str_replace(['$', ','], '', $prod['PRECIO'] ?? '0'),
                        'clave_sat' => trim($prod['CLAVE_SAT'] ?? ''),
                        'maneja_lotes' => strtoupper(trim($prod['LOTE'] ?? '')) === 'SI',
                        'activo' => true,
                        'stock' => 0,
                    ]
                );
            }

            $alertEngine = new AlertEngineService;
            $alertEngine->alertar([
                'tipo' => 'productos_alta_automatica',
                'modulo' => 'productos',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 1,
                'titulo' => "Productos dados de alta: {$validos} productos",
                'contenido' => "Se dieron de alta {$validos} productos en el catalogo.",
                'datos' => ['validacion_id' => $validacion->id, 'total' => $validos],
                'nivel' => 'info',
            ]);

            return back()->with('mensaje', "OK - {$validos} productos validados y dados de alta en el catalogo.");
        }

        // Tiene errores - generar Excel con correcciones (solo celdas en rojo)

        $fullPath = $this->generarExcelConErrores($productos, $errores);
        $relativePath = str_replace(storage_path('app/public/'), '', $fullPath);

        $errorMsg = "ERROR: El Excel tiene {$conError} producto(s) con errores.\n\n";
        $errorMsg .= "Las celdas con error estan marcadas en ROJO en el Excel descargable.\n";
        $errorMsg .= "Corrige los campos senalados y vuelve a subir.\n\n";
        $errorMsg .= "ERRORES ENCONTRADOS:\n";
        foreach (array_slice($errores, 0, 20) as $err) {
            $errorTextoLimpio = $err['error'];
            // Quitar "COMO CORREGIR:" y dejarlo como una sola linea clara
            $errorTextoLimpio = str_replace('COMO CORREGIR: ', '-> ', $errorTextoLimpio);
            $errorMsg .= "* Fila {$err['fila']} - {$err['campo']}: {$errorTextoLimpio}\n";
        }
        if (count($errores) > 20) {
            $errorMsg .= "\n... y ".(count($errores) - 20)." errores mas.\n";
        }

        return back()
            ->with('error', $errorMsg)
            ->with('archivo_correcciones', $relativePath);
    }

    /**
     * Generar Excel XLSX con colores â€" celdas con error en rojo, aprobadas en verde.
     */
    private function generarExcelConErrores(array $productos, array $errores): string
    {
        // Agrupar errores por fila
        $erroresPorFila = [];
        foreach ($errores as $err) {
            $erroresPorFila[$err['fila']][] = ['campo' => $err['campo'], 'error' => $err['error']];
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Validacion IA');

        // Headers â€" solo los datos del producto, SIN columnas de estatus/errores
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4A4A4A');
            $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Mapeo de columnas para colorear celdas con error
        $colMap = [
            'CODIGO' => 'A', 'NOMBRE_TIPO' => 'B', 'NOMBRE_MARCA' => 'C', 'NOMBRE_MODELO' => 'D',
            'NOMBRE_MEDIDA' => 'E', 'NOMBRE_ESPECIFICACION' => 'F', 'FAMILIA' => 'G',
            'TIPO_PRODUCTO' => 'H', 'UNIDAD_MEDIDA' => 'I', 'PRECIO' => 'J', 'CLAVE_SAT' => 'K',
            'LOTE' => 'L', 'PEDIMENTO' => 'M', 'VOLTAJE' => 'N',
            // Aliases
            'NOMBRE' => 'B', 'MARCA' => 'C', 'MODELO' => 'D', 'MEDIDA' => 'E',
            'ESPECIFICACION' => 'F', 'GENERAL' => 'B', 'PRODUCCION' => 'H',
        ];

        // Datos
        foreach ($productos as $index => $producto) {
            $fila = $index + 2;
            $excelRow = $index + 2;

            $col = 'A';
            foreach (['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE'] as $campo) {
                $sheet->setCellValue($col.$excelRow, $producto[$campo] ?? '');
                $col++;
            }

            if (isset($erroresPorFila[$fila])) {
                // Colorear las celdas especificas que tienen error en ROJO
                foreach ($erroresPorFila[$fila] as $err) {
                    $campoError = $err['campo'];
                    if (isset($colMap[$campoError])) {
                        $cellRef = $colMap[$campoError].$excelRow;
                        $sheet->getStyle($cellRef)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
                        $sheet->getStyle($cellRef)->getFont()->getColor()->setRGB('CC0000');
                    }
                }
            }
        }

        // Auto-size columns
        foreach (range('A', 'T') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Guardar como XLSX
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/public/excel-correcciones/Correcciones_'.date('Y-m-d_His').'.xlsx');

        // Crear directorio si no existe
        $dir = dirname($tempPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Validar un producto individual â€" Reglas de estandarizacion industrial.
     *
     * Nomenclatura: [TIPO] + [MARCA] + [MODELO/MEDIDA] + [ESPECIFICACIÃ"N]
     * Ejemplo correcto: BALERO SKF 6205 2RS
     * Ejemplo correcto: MOTOR WEG 3HP 220/440V TRIFASICO
     * Ejemplo correcto: INSECTICIDA MT XTERM BIO S/AROMA 180G/274ML C/12
     */
    private function validarProducto(array $producto, int $fila): array
    {
        $errores = [];

        // â*â*â* 1. CAMPOS OBLIGATORIOS â*â*â*
        foreach ($this->columnasObligatorias as $campo) {
            if (empty(trim($producto[$campo] ?? ''))) {
                $sugerencia = match($campo) {
                    'CODIGO' => 'Escribe un codigo unico (ej: MPI0538, ME0201)',
                    'NOMBRE_TIPO' => 'Escribe QUE ES el producto (ej: RESINA EPOXICA, MOTOR ELECTRICO, CAJA CORRUGADA)',
                    'NOMBRE_MARCA' => 'Escribe QUIEN lo fabrica (ej: WEG, SKF, 3M, ALPHA, KRAFT)',
                    'NOMBRE_MODELO' => 'Escribe la REFERENCIA del fabricante (ej: W22, IND-500, CP-40, T-100)',
                    'NOMBRE_MEDIDA' => 'Escribe el TAMANO o capacidad con numeros (ej: 500ML, 3HP, 40X30X25, 180G)',
                    'NOMBRE_ESPECIFICACION' => 'Escribe CARACTERISTICAS adicionales (ej: TRIFASICO, TRANSPARENTE, DOBLE PARED)',
                    'FAMILIA' => 'Selecciona del dropdown una familia del catalogo oficial',
                    'TIPO_PRODUCTO' => 'Selecciona del dropdown: MPI (Materia Prima), ME (Empaque) o MN (Mantenimiento)',
                    'UNIDAD_MEDIDA' => 'Selecciona del dropdown: KG, PZA o CAJA',
                    'OBSERVACIONES' => 'Escribe notas relevantes del producto en MAYUSCULAS (ej: PROVEEDOR NACIONAL ENTREGA 5 DIAS)',
                    default => 'Este campo es obligatorio, no puede estar vacio',
                };
                $errores[] = [
                    'fila' => $fila,
                    'campo' => $campo,
                    'error' => "Campo obligatorio vacio. COMO CORREGIR: {$sugerencia}",
                ];
            }
        }

        $nombre = trim($producto['NOMBRE'] ?? '');
        $familia = strtoupper(trim($producto['FAMILIA'] ?? ''));
        $subfamilia = strtoupper(trim($producto['SUBFAMILIA'] ?? ''));
        $unidad = strtoupper(trim($producto['UNIDAD_MEDIDA'] ?? ''));
        $precio = $producto['PRECIO'] ?? '';

        // â*â*â* 1.5 CODIGO â€" Solo alfanumerico y guiones â*â*â*
        $codigo = trim($producto['CODIGO'] ?? '');
        if ($codigo) {
            if (! preg_match('/^[A-Za-z0-9\-_]+$/', $codigo)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'CODIGO',
                    'error' => "Codigo invalido: '{$codigo}'. Solo se permiten letras, numeros y guiones. COMO CORREGIR: Usa un codigo como MPI0538, ME0201, MN-045. Sin comillas, signos de interrogacion ni caracteres especiales.",
                ];
            }
        }

        // â*â*â* 2. NOMENCLATURA â€" Construir NOMBRE desde las 5 partes â*â*â*

        // â*â*â* 2. NOMENCLATURA â€" Construir NOMBRE desde las 5 partes â*â*â*
        // Las 5 partes â€" validar con valor ORIGINAL, luego convertir
        $nombreTipoRaw = trim($producto['NOMBRE_TIPO'] ?? '');
        $nombreMarcaRaw = trim($producto['NOMBRE_MARCA'] ?? '');
        $nombreModeloRaw = trim($producto['NOMBRE_MODELO'] ?? '');
        $nombreMedidaRaw = trim($producto['NOMBRE_MEDIDA'] ?? '');
        $nombreEspecRaw = trim($producto['NOMBRE_ESPECIFICACION'] ?? '');

        $nombreTipo = strtoupper($nombreTipoRaw);
        $nombreMarca = strtoupper($nombreMarcaRaw);
        $nombreModelo = strtoupper($nombreModeloRaw);
        $nombreMedida = strtoupper($nombreMedidaRaw);
        $nombreEspec = strtoupper($nombreEspecRaw);

        // Construir nombre completo
        $nombre = trim("{$nombreTipo} {$nombreMarca} {$nombreModelo} {$nombreMedida} {$nombreEspec}");

        // Validar cada parte individualmente con el valor ORIGINAL (para detectar minusculas)
        $partesNombre = [
            'NOMBRE_TIPO' => ['valor' => $nombreTipoRaw, 'desc' => 'Que es el producto (ej: RESINA EPOXICA, MOTOR ELECTRICO, CAJA CORRUGADA)'],
            'NOMBRE_MARCA' => ['valor' => $nombreMarcaRaw, 'desc' => 'Quien lo fabrica (ej: WEG, SKF, 3M, ALPHA, KRAFT)'],
            'NOMBRE_MODELO' => ['valor' => $nombreModeloRaw, 'desc' => 'Referencia del fabricante (ej: W22, IND-500, CP-40, T-100)'],
            'NOMBRE_MEDIDA' => ['valor' => $nombreMedidaRaw, 'desc' => 'Tamano o capacidad (ej: 500ML, 3HP, 40X30X25, 180G)'],
            'NOMBRE_ESPECIFICACION' => ['valor' => $nombreEspecRaw, 'desc' => 'Caracteristicas adicionales (ej: TRIFASICO, TRANSPARENTE, DOBLE PARED)'],
        ];

        foreach ($partesNombre as $campo => $info) {
            if (! empty($info['valor'])) {
                // Debe ser MAYUSCULAS
                if ($info['valor'] !== strtoupper($info['valor'])) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => $campo,
                        'error' => "Debe estar en MAYUSCULAS sin acentos. Recibido: '{$info['valor']}'. COMO CORREGIR: {$info['desc']}",
                    ];
                }
                // No caracteres especiales
                if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`"\'?_@^!]/', $info['valor'])) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => $campo,
                        'error' => "Contiene caracteres no permitidos. Solo letras, numeros, espacios, / - . () COMO CORREGIR: {$info['desc']}",
                    ];
                }
                // Detectar texto basura (consonantes sin vocales)
                $letras = preg_replace('/[\d\-\/\.\s]/', '', $info['valor']);
                if (strlen($letras) >= 5 && ! preg_match('/[AEIOU]/i', $letras)) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => $campo,
                        'error' => "Texto ilegible detectado: '{$info['valor']}'. COMO CORREGIR: {$info['desc']}",
                    ];
                }
            }
        }

        // NOMBRE_MEDIDA debe contener al menos un numero
        $marcasConocidas = ['WEG', 'SKF', '3M', 'ALPHA', 'KRAFT', 'SIEMENS', 'GRUNDFOS', 'ABB', 'SCHNEIDER', 'BOSCH', 'SAMSUNG', 'APPLE', 'LG', 'SONY', 'HUNTSMAN', 'SMURFIT', 'IPEX', 'LANXESS', 'PLASTIMAX', 'DCC', 'INTERFLEX', 'BOBSON', 'NINGBO', 'GUANGZHOU', 'HANGZHOU', 'QINGDAO', 'RECOCHEMIC', 'COMEX', 'TRUPER', 'PEMEX', 'BIOPAPPEL', 'JANEL', 'HERDEZ', 'BIMBO', 'CEMEX', 'JUMEX', 'LALA', 'MASECA', 'DEACERO', 'NACOBRE', 'CONDUMEX', 'IUSA', 'URREA', 'PRETUL', 'FESTER', 'BEREL', 'SHERWIN', 'DUPONT', 'HENKEL'];

        // NOMBRE_ESPECIFICACION no puede ser igual a NOMBRE_MEDIDA (campo repetido/cruzado)
        if ($nombreEspecRaw && $nombreMedidaRaw && strtoupper(trim($nombreEspecRaw)) === strtoupper(trim($nombreMedidaRaw))) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_ESPECIFICACION',
                'error' => "'{$nombreEspecRaw}' es IGUAL a NOMBRE_MEDIDA. Campos cruzados/repetidos. NOMBRE_ESPECIFICACION debe ser una caracteristica diferente (ej: BLANCO MATE, TRIFASICO, DOBLE PARED). La medida ya esta en NOMBRE_MEDIDA.",
            ];
        }

        // NOMBRE_TIPO no puede ser solo adjetivos/especificaciones sin decir QUE ES el producto
        if ($nombreTipoRaw) {
            $soloAdjetivos = ['BLANCO', 'NEGRO', 'ROJO', 'AZUL', 'VERDE', 'AMARILLO', 'TRANSPARENTE', 'MATE', 'BRILLANTE', 'SATINADO', 'INTERIOR', 'EXTERIOR', 'INDUSTRIAL', 'PROFESIONAL', 'PREMIUM', 'DOBLE', 'TRIPLE', 'GRUESO', 'DELGADO', 'GRANDE', 'CHICO', 'MEDIANO'];
            $palabrasTipo = explode(' ', strtoupper(trim($nombreTipoRaw)));
            $todasSonAdjetivos = !empty($palabrasTipo) && count(array_diff($palabrasTipo, $soloAdjetivos)) === 0;
            if ($todasSonAdjetivos) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE_TIPO',
                    'error' => "'{$nombreTipoRaw}' son adjetivos/especificaciones, no un tipo de producto. NOMBRE_TIPO debe decir QUE ES (ej: PINTURA VINILICA, MOTOR ELECTRICO). Las caracteristicas van en NOMBRE_ESPECIFICACION.",
                ];
            }
        }

        // NOMBRE_ESPECIFICACION no puede ser solo una medida con numeros y unidad
        if ($nombreEspecRaw && preg_match('/^\d+\s*(LT|ML|KG|GR|GAL|HP|CM|MM|MT|PZA|V|W|HZ)$/i', $nombreEspecRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_ESPECIFICACION',
                'error' => "'{$nombreEspecRaw}' es una MEDIDA, no una especificacion. Las medidas van en NOMBRE_MEDIDA. NOMBRE_ESPECIFICACION debe ser una caracteristica (ej: BLANCO MATE, CENTRIFUGA, DOBLE PARED).",
            ];
        }

        if ($nombreMedidaRaw && ! preg_match('/\d/', $nombreMedidaRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MEDIDA',
                'error' => "La medida debe contener un valor numerico. Recibido: '{$nombreMedidaRaw}'. ->  Escribe tamano con numeros (ej: 500ML, 3HP, 40X30X25)",
            ];
        }

        // NOMBRE_MODELO no debe ser una medida pura (solo numeros+unidad como 48MMX150M, 19LT, 5LT)
        if ($nombreModeloRaw && preg_match('/^\d+\s*(MM|CM|MT|LT|ML|KG|GR|GAL|HP|V|W|HZ|PZA)/i', $nombreModeloRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MODELO',
                'error' => "'{$nombreModeloRaw}' parece una MEDIDA, no un modelo. ->  NOMBRE_MODELO debe ser la referencia del fabricante (ej: VIN-100, BOAG-1HP, TR-48). La medida va en NOMBRE_MEDIDA.",
            ];
        }
        // NOMBRE_MODELO no debe ser formato de medida con X (40X30X25, 48MMX150M)
        if ($nombreModeloRaw && preg_match('/^\d+\w*X\d+/i', $nombreModeloRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MODELO',
                'error' => "'{$nombreModeloRaw}' parece una MEDIDA (dimensiones), no un modelo. ->  NOMBRE_MODELO debe ser la referencia del fabricante (ej: VIN-100, BC-300). Las dimensiones van en NOMBRE_MEDIDA.",
            ];
        }
        // NOMBRE_MODELO no debe ser una marca conocida
        if ($nombreModeloRaw && in_array(strtoupper($nombreModeloRaw), $marcasConocidas)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MODELO',
                'error' => "'{$nombreModeloRaw}' es una MARCA, no un modelo. ->  NOMBRE_MODELO debe ser la referencia del fabricante (ej: VIN-100, BOAG-1HP). La marca va en NOMBRE_MARCA.",
            ];
        }

        // NOMBRE_MEDIDA no debe ser un codigo de modelo (patron: letras-numeros corto sin unidad)
        if ($nombreMedidaRaw && preg_match('/^[A-Z]{2,}-\d+$/i', $nombreMedidaRaw) && !preg_match('/\d+(MM|CM|LT|ML|KG|GR|HP|V|W)/i', $nombreMedidaRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MEDIDA',
                'error' => "'{$nombreMedidaRaw}' parece un MODELO, no una medida. ->  NOMBRE_MEDIDA debe ser tamano/capacidad con numeros (ej: 19LT, 48MMX150M, 5KG). Los modelos van en NOMBRE_MODELO.",
            ];
        }

        // NOMBRE_ESPECIFICACION no debe ser un codigo de modelo (BC-300, TR-48, VIN-100)
        if ($nombreEspecRaw && preg_match('/^[A-Z]{1,5}-?\d{1,4}$/i', $nombreEspecRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_ESPECIFICACION',
                'error' => "'{$nombreEspecRaw}' parece un MODELO, no una especificacion. ->  NOMBRE_ESPECIFICACION debe ser detalle del producto (ej: BLANCO MATE, CORRUGADA DOBLE PARED). Los modelos van en NOMBRE_MODELO.",
            ];
        }

        // NOMBRE_MARCA no debe ser un tipo de producto
        $tiposProducto = ['PINTURA VINILICA', 'PINTURA ESMALTE', 'CAJA CARTON', 'CAJA CORRUGADA', 'MOTOR ELECTRICO', 'BOMBA AGUA', 'BOMBA CENTRIFUGA', 'ACEITE MOTOR', 'CINTA ADHESIVA', 'BOLSA PLASTICO', 'CEMENTO GRIS', 'RESINA EPOXICA', 'PIGMENTO ORGANICO', 'ETIQUETA ADHESIVA', 'TALADRO ROTOMARTILLO', 'FLEJE ACERO', 'SOLVENTE INDUSTRIAL', 'ADHESIVO ESTRUCTURAL'];
        if ($nombreMarcaRaw && in_array(strtoupper($nombreMarcaRaw), $tiposProducto)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MARCA',
                'error' => "'{$nombreMarcaRaw}' es un TIPO DE PRODUCTO, no una marca. ->  NOMBRE_MARCA debe ser el fabricante (ej: COMEX, TRUPER, JANEL). El tipo va en NOMBRE_TIPO.",
            ];
        }

        // NOMBRE_MARCA no debe ser una medida (ampliar regex para detectar mas formatos)
        if ($nombreMarcaRaw && preg_match('/^\d+\s*(ML|KG|LT|HP|CM|MM|GR|GAL|PZA|G|V|W|HZ|MT)$/i', $nombreMarcaRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MARCA',
                'error' => "'{$nombreMarcaRaw}' es una MEDIDA, no una marca. ->  NOMBRE_MARCA debe ser quien fabrica el producto (ej: COMEX, TRUPER, PEMEX). La medida va en NOMBRE_MEDIDA.",
            ];
        }
        // NOMBRE_MARCA formato medida con unidad pegada (5LT, 19LT, 500ML)
        if ($nombreMarcaRaw && preg_match('/^\d+[A-Z]{1,3}$/i', $nombreMarcaRaw) && preg_match('/\d+(LT|ML|KG|GR|HP|CM|MM|MT|GAL|PZA)/i', $nombreMarcaRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MARCA',
                'error' => "'{$nombreMarcaRaw}' es una MEDIDA, no una marca. ->  NOMBRE_MARCA debe ser el fabricante (ej: COMEX, TRUPER). La medida va en NOMBRE_MEDIDA.",
            ];
        }

        // NOMBRE_TIPO no debe ser una marca conocida
        if ($nombreTipoRaw && in_array(strtoupper($nombreTipoRaw), $marcasConocidas)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_TIPO',
                'error' => "'{$nombreTipoRaw}' es una MARCA, no un tipo de producto. ->  NOMBRE_TIPO debe decir QUE ES (ej: PINTURA VINILICA, BOMBA AGUA). La marca va en NOMBRE_MARCA.",
            ];
        }

        // NOMBRE_ESPECIFICACION validaciones
        $tiposConocidos = ['MOTOR ELECTRICO', 'RESINA EPOXICA', 'CAJA CORRUGADA', 'CAJA CARTON', 'PIGMENTO ORGANICO', 'ETIQUETA ADHESIVA', 'BOMBA CENTRIFUGA', 'BOMBA AGUA', 'PINTURA VINILICA', 'PINTURA ESMALTE', 'ACEITE MOTOR', 'CINTA ADHESIVA', 'BOLSA PLASTICO', 'CEMENTO GRIS'];
        if ($nombreEspecRaw && in_array(strtoupper($nombreEspecRaw), $tiposConocidos)) {
            $errores[] = ['fila' => $fila, 'campo' => 'NOMBRE_ESPECIFICACION', 'error' => "'{$nombreEspecRaw}' es un TIPO DE PRODUCTO, no una especificacion. El tipo va en NOMBRE_TIPO."];
        }
        if ($nombreEspecRaw && preg_match('/^\d+\s*(MM|CM|MT|LT|ML|KG|GR|GAL|HP|V|W|HZ|PZA)$/i', $nombreEspecRaw)) {
            $errores[] = ['fila' => $fila, 'campo' => 'NOMBRE_ESPECIFICACION', 'error' => "'{$nombreEspecRaw}' es una MEDIDA, no una especificacion. La medida va en NOMBRE_MEDIDA."];
        }
        if ($nombreEspecRaw && preg_match('/^[A-Z]{1,5}-?\d{1,4}$/i', $nombreEspecRaw)) {
            $errores[] = ['fila' => $fila, 'campo' => 'NOMBRE_ESPECIFICACION', 'error' => "'{$nombreEspecRaw}' parece un MODELO, no una especificacion. Los modelos van en NOMBRE_MODELO."];
        }

        $familiaRaw = trim($producto['FAMILIA'] ?? '');
        if ($nombreEspecRaw && in_array(strtoupper($nombreEspecRaw), $tiposConocidos)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_ESPECIFICACION',
                'error' => "'{$nombreEspecRaw}' es un TIPO DE PRODUCTO, no una especificacion. ->  NOMBRE_ESPECIFICACION debe ser detalle (ej: BLANCO MATE, CENTRIFUGA 127V). El tipo va en NOMBRE_TIPO.",
            ];
        }

        $familiaRaw = trim($producto['FAMILIA'] ?? '');
        $familia = strtoupper($familiaRaw);
        $subfamilia = strtoupper(trim($producto['SUBFAMILIA'] ?? ''));
        $unidadRaw = trim($producto['UNIDAD_MEDIDA'] ?? '');
        $unidad = strtoupper($unidadRaw);
        $precio = trim($producto['PRECIO'] ?? '');
        $tipoProductoRaw = trim($producto['TIPO_PRODUCTO'] ?? '');

        // â*â*â* 2.5 TIPO_PRODUCTO â€" Debe ser exactamente MPI, ME o MN en MAYUSCULAS â*â*â*
        if ($tipoProductoRaw && $tipoProductoRaw !== strtoupper($tipoProductoRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'TIPO_PRODUCTO',
                'error' => "Debe estar en MAYUSCULAS. Recibido: '{$tipoProductoRaw}'. COMO CORREGIR: Selecciona del dropdown: MPI, ME o MN",
            ];
        }
        if ($tipoProductoRaw && ! in_array(strtoupper($tipoProductoRaw), ['MPI', 'ME', 'MN'])) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'TIPO_PRODUCTO',
                'error' => "Valor no valido: '{$tipoProductoRaw}'. COMO CORREGIR: Solo MPI (Materia Prima Importacion), ME (Material Empaque) o MN (Mantenimiento)",
            ];
        }

        // â*â*â* 2.7 UNIDAD_MEDIDA â€" Debe estar en MAYUSCULAS y ser valida â*â*â*
        if ($unidadRaw && $unidadRaw !== strtoupper($unidadRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Debe estar en MAYUSCULAS. Recibido: '{$unidadRaw}'. COMO CORREGIR: Selecciona del dropdown: KG, PZA o CAJA",
            ];
        }

        // â*â*â* 3. FAMILIA â€" Debe ser de la lista oficial â*â*â*
        if ($familia && ! in_array($familia, $this->familiasValidas)) {
            $sugerencia = $this->buscarFamiliaSimilar($familia);
            $errores[] = [
                'fila' => $fila,
                'campo' => 'FAMILIA',
                'error' => "Familia '{$familia}' no esta en el catalogo oficial.".
                    ($sugerencia ? " ?Quisiste decir '{$sugerencia}'?" : ' Familias validas: '.implode(', ', array_slice($this->familiasValidas, 0, 10)).'...'),
            ];
        }

        // â*â*â* 4. UNIDAD DE MEDIDA â€" Lista oficial â*â*â*
        if ($unidad && ! in_array($unidad, $this->unidadesValidas)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Unidad '{$unidad}' no valida. COMO CORREGIR: Solo se acepta KG, PZA o CAJA (selecciona del dropdown)",
            ];
        }

        // â*â*â* 5. PRECIO â€" Numerico y razonable (opcional, pero si viene debe ser valido) â*â*â*
        if ($precio !== '' && $precio !== null) {
            $precioLimpio = str_replace([',', '$', ' '], '', $precio);
            // Aceptar signo $ al inicio (se limpia automaticamente)
            if (! is_numeric($precioLimpio)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PRECIO',
                    'error' => "El precio debe ser numerico (ej: \$150.50 o 150.50). Recibido: '{$precio}'",
                ];
            } else {
                $precioNum = (float) $precioLimpio;
                if ($precioNum <= 0) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => 'PRECIO',
                        'error' => "El precio debe ser mayor a 0. Recibido: '{$precio}'",
                    ];
                } elseif ($precioNum > 5000000) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => 'PRECIO',
                        'error' => "Precio extremadamente alto (\${$precioNum}). Verifica que sea correcto.",
                    ];
                }
            }
        }

        // â*â*â* 6. DUPLICADOS INTELIGENTES â*â*â*
        if ($nombre) {
            // Buscar duplicado exacto
            $nombreNorm = strtoupper(str_replace(' ', '', Str::ascii($nombre)));
            $existe = Producto::whereRaw("UPPER(REPLACE(nombre, ' ', '')) = ?", [$nombreNorm])->exists();
            if ($existe) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => 'DUPLICADO: Este producto ya existe en el catalogo',
                ];
            } else {
                // Buscar similares (primeras 3 palabras iguales = posible duplicado)
                $primeras3 = implode(' ', array_slice(explode(' ', strtoupper(Str::ascii($nombre))), 0, 3));
                if (strlen($primeras3) > 5) {
                    $similar = Producto::where('nombre', 'LIKE', $primeras3.'%')->first();
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

        // â*â*â* 7. CAMPOS OPCIONALES â€" Validar formato si vienen â*â*â*
        $marca = trim($producto['MARCA'] ?? '');
        if ($marca && $marca !== strtoupper(Str::ascii($marca))) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'MARCA',
                'error' => 'La marca debe estar en MAYUSCULAS sin acentos',
            ];
        }

        // Validar VOLTAJE â€" si viene, debe ser del dropdown o un valor electrico valido
        $voltaje = trim($producto['VOLTAJE'] ?? '');
        if ($voltaje) {
            $voltajesValidos = ['110V', '127V', '220V', '220/440V', '110/220V', '440V', '480V', '12VDC', '24VDC', '3HP', '5HP', '10HP', '60Hz', 'N/A'];
            $voltajeUpper = strtoupper($voltaje);
            if (! in_array($voltajeUpper, $voltajesValidos) && ! preg_match('/^\d+[\d\/\.]*\s*(V|VDC|VAC|Hz|W|KW|HP|A)(\s*[\/-]\s*\d+[\d\/\.]*\s*(V|VDC|VAC|Hz|W|KW|HP|A)?)*$/i', $voltaje)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'VOLTAJE',
                    'error' => "Voltaje invalido: '{$voltaje}'. COMO CORREGIR: Selecciona del dropdown (110V, 220V, 220/440V, 440V, 12VDC, 24VDC, 3HP, 5HP, 10HP, 60Hz, N/A). No escribas texto libre.",
                ];
            }
        }

        // Validar MODELO â€" no debe tener caracteres especiales raros ni ser texto basura
        $modelo = trim($producto['MODELO'] ?? $producto['MODELO_PRODUCTO'] ?? '');
        if ($modelo) {
            if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`"\'?@^!]/', $modelo)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'MODELO_PRODUCTO',
                    'error' => "Modelo invalido: '{$modelo}'. Contiene caracteres especiales. COMO CORREGIR: Solo letras, numeros y guiones. Ejemplo: W22, IND-500, ORG-R180",
                ];
            }
            // Detectar basura (muchas consonantes sin vocales)
            $modeloLetras = preg_replace('/[\d\-\/\.]/', '', $modelo);
            if (strlen($modeloLetras) >= 5 && ! preg_match('/[AEIOUaeiou]/', $modeloLetras)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'MODELO_PRODUCTO',
                    'error' => "Modelo ilegible: '{$modelo}'. COMO CORREGIR: Escribe el modelo real del fabricante. Ejemplo: W22, CP-40, T-100",
                ];
            }
            // Debe estar en MAYUSCULAS
            if ($modelo !== strtoupper($modelo)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'MODELO_PRODUCTO',
                    'error' => "Debe estar en MAYUSCULAS. Recibido: '{$modelo}'. COMO CORREGIR: Escribe en MAYUSCULAS",
                ];
            }
        }

        // Validar COLOR â€" si viene, no debe ser un numero ni texto sin sentido
        $color = trim($producto['COLOR'] ?? '');
        if ($color && preg_match('/^\d+$/', $color)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'COLOR',
                'error' => "Color invalido: '{$color}'. Debe ser un nombre de color (ej: ROJO, BLANCO, TRANSPARENTE).",
            ];
        }

        // Validar MEDIDA â€" si viene, debe tener numeros o unidades
        $medida = trim($producto['MEDIDA'] ?? '');
        if ($medida && ! preg_match('/\d|MM|CM|MT|ML|LT|KG|GR|GAL|IN|PZA|HP/i', $medida)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'MEDIDA',
                'error' => "Medida invalida: '{$medida}'. Debe incluir un valor numerico o unidad (ej: 500ML, 3HP, 40X30X25, 10X5CM).",
            ];
        }

        // â*â*â* 8. LOTE y PEDIMENTO â€" Obligatorios solo si TIPO_PRODUCTO = MPI â*â*â*
        $tipoProducto = strtoupper(trim($producto['TIPO_PRODUCTO'] ?? ''));
        if ($tipoProducto === 'MPI') {
            $lote = strtoupper(trim($producto['LOTE'] ?? ''));
            if (empty($lote)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'LOTE',
                    'error' => 'LOTE es obligatorio para productos MPI (Materia Prima Importacion). Selecciona SI o NO.',
                ];
            }

            $pedimento = strtoupper(trim($producto['PEDIMENTO'] ?? ''));
            if (empty($pedimento)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PEDIMENTO',
                    'error' => 'PEDIMENTO es obligatorio para productos MPI. Selecciona SI o NO.',
                ];
            }
        }

        // â*â*â* 9. OBSERVACIONES â€" Debe ser texto legible, profesional, no basura â*â*â*
        $observaciones = trim($producto['OBSERVACIONES'] ?? '');
        if ($observaciones) {
            // Rechazar caracteres especiales raros
            if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`"\'?@^]/', $observaciones)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => "Contiene caracteres especiales no permitidos. COMO CORREGIR: Escribe texto normal sin simbolos raros. Ejemplo: PROVEEDOR NACIONAL ENTREGA 5 DIAS",
                ];
            }

            // Detectar texto ilegible (muchas consonantes seguidas sin vocales = basura)
            $palabrasObs = explode(' ', $observaciones);
            $palabrasBasura = 0;
            foreach ($palabrasObs as $p) {
                $pLetras = preg_replace('/\d/', '', $p);
                if (strlen($pLetras) >= 4 && ! preg_match('/[AEIOUaeiou]/', $pLetras)) {
                    $palabrasBasura++;
                }
            }
            if ($palabrasBasura >= 1) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => "Texto ilegible o sin sentido detectado. COMO CORREGIR: Escribe observaciones claras y profesionales. Ejemplo: IMPORTACION CHINA PEDIMENTO 26 0001 3000001",
                ];
            }

            // Detectar contenido inapropiado/ofensivo
            $palabrasProhibidas = ['GAY', 'PUTO', 'PENDEJO', 'IDIOTA', 'ESTUPIDO', 'MIERDA', 'CHINGA', 'VERGA', 'CULO', 'PERRA', 'CABRON', 'JOTO', 'MARICA', 'PINCHE'];
            $obsUpper = strtoupper($observaciones);
            foreach ($palabrasProhibidas as $prohibida) {
                if (str_contains($obsUpper, $prohibida)) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => 'OBSERVACIONES',
                        'error' => "Contenido inapropiado detectado. COMO CORREGIR: Las observaciones deben ser profesionales y relevantes al producto. Ejemplo: PARA LINEA 3 PRODUCCION",
                    ];
                    break;
                }
            }

            // Debe tener al menos 2 palabras legibles
            if (count($palabrasObs) < 2) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => "Observaciones muy cortas. COMO CORREGIR: Escribe al menos 2 palabras descriptivas. Ejemplo: PROVEEDOR NACIONAL",
                ];
            }

            // Debe estar en MAYUSCULAS
            if ($observaciones !== mb_strtoupper($observaciones)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => "Debe estar en MAYUSCULAS. COMO CORREGIR: Escribe todo en MAYUSCULAS. Recibido: '{$observaciones}'",
                ];
            }
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

        // Normalizar headers (quitar BOM residual, espacios, poner mayusculas)
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
