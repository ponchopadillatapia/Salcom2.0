<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\AlertEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AltaProductoPTController extends Controller
{
    private array $unidadesValidas = ['PZA', 'CAJA', 'SET', 'KG', 'TONELADA', 'METRO', 'LITRO', 'PAR', 'PACK', 'CUBETA', 'TIRA', 'NA'];

    private array $columnasObligatorias = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO'];

    private array $familiasValidas = [
        'AEROSOLES', 'LIMPIEZA', 'INSECTICIDAS', 'HERRAMIENTAS', 'REFACCIONES',
        'EMPAQUE', 'MATERIA PRIMA', 'CONSUMIBLE', 'LUBRICANTES', 'ADHESIVOS',
        'PINTURAS', 'SOLVENTES', 'RESINAS', 'PIGMENTOS', 'ADITIVOS',
        'MOTORES', 'BOMBAS', 'VALVULAS', 'TUBERIAS', 'TORNILLERIA',
        'MATERIAL EMPAQUE', 'PRODUCTO TERMINADO', 'INSUMOS',
        'QUIMICOS', 'ELECTRICO', 'FERRETERIA', 'MANTENIMIENTO', 'SEGURIDAD',
        'MAQUINARIA', 'MATERIA PRIMA DE IMPORTACION',
    ];

    // Clasificaciones PT (6 dropdowns)
    private array $departamentos = ['GASTOS', 'HERRAMIENTAS', 'INSUMOS', 'MANO DE OBRA', 'MAQUINARIA Y EQUIPO', 'MATERIALES', 'ME', 'MI', 'MN', 'MO', 'MP', 'MPI', 'MS', 'PAPELERIA', 'PT', 'REFACCIONES', 'RP', 'SEGURIDAD', 'SERVICIOS', 'VEHICULOS'];

    private array $lineas = ['Aerosoles', 'Aromatizante Solido', 'Breeze Matic', 'Canastilla', 'Clip On', 'Cono Gel', 'Desinfectante', 'Difusor Electrico', 'Dispensador', 'Gel Aromatizante', 'Hang Air', 'Insecticida', 'Lavatrastes', 'Limpiador', 'Liquido Goteador', 'Metered', 'Micro Can', 'Mini Spray', 'Pastilla', 'Tapete'];

    private array $subfamilias = ['Abrillantador 400ml', 'Abrillantador 8oz', 'Accesorios Plasticos', 'Aerosol 10oz', 'Aerosol 19oz', 'Aerosol 8oz', 'Aerosol Hogar 400ml', 'Aerosol Metered Institucional', 'Breeze Matic', 'Canastilla', 'Clip On', 'Cono Gel 170g', 'Difusor Electrico', 'Dispensador', 'Gel 70g', 'Hang Air', 'Insecticida', 'Lavatrastes', 'Limpiador Sanitario', 'Liquido Goteador', 'Metered 180g', 'Micro Can', 'Mini Spray', 'Pastilla Alambre', 'Pastilla Azul', 'Pastilla Barra', 'Pastilla Cloro', 'Tapete Anti-Salpicadura', 'Tapete Con Pastilla', 'Tapete Liso', 'Tapete Storm'];

    private array $canales = ['Autoservicio', 'Abarrotera', 'Exportacion', 'Ferretero', 'Institucional', 'Mayoreo'];

    private array $vendedores = ['Alexandre Cominu', 'Ana Barrera', 'Directo Exportacion', 'Directo Mexico', 'Francisco Alvarez', 'Guillermo Quiroz', 'Imelda Lopez INST', 'Jesus Sesma', 'Jorge Ornelas', 'Luis Ibarra', 'Marco Vargas', 'Omar Garcia'];

    private array $modulos = ['AEROSOL', 'AROMATIZANTE', 'BREEZE MATIC', 'CANASTILLA', 'DISPENSADOR', 'ENSAMBLES', 'HERRAMIENTAS', 'INSECTICIDA', 'LAVATRASTES', 'LIMPIADOR', 'LIQUIDO', 'MAQUINARIA', 'PASTILLA', 'REFACCIONES', 'TAPETE'];

    public function mostrar()
    {
        return view('admin.alta-producto-pt');
    }

    public function descargarTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos PT');

        // Headers
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE', 'DEPARTAMENTO', 'LINEA', 'SUBFAMILIA', 'CANAL', 'VENDEDOR', 'MODULO'];
        $obligatorios = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO'];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            if (in_array($header, $obligatorios)) {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6B3FA0');
            } else {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('9B7BC7');
            }
            $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Formato moneda para PRECIO (J)
        $sheet->getStyle('J2:J101')->getNumberFormat()->setFormatCode('$#,##0.00');

        // === HOJA OCULTA: Listas de validacion ===
        $listSheet = $spreadsheet->createSheet();
        $listSheet->setTitle('_Listas');

        // Familias (col A)
        foreach ($this->familiasValidas as $i => $fam) {
            $listSheet->setCellValue('A'.($i + 1), $fam);
        }

        // Unidades (col B)
        foreach ($this->unidadesValidas as $i => $uni) {
            $listSheet->setCellValue('B'.($i + 1), $uni);
        }

        // TIPO_PRODUCTO fijo PT (col C)
        $listSheet->setCellValue('C1', 'PT');

        // SI/NO (col D)
        $listSheet->setCellValue('D1', 'SI');
        $listSheet->setCellValue('D2', 'NO');

        // Voltaje (col E)
        $voltajes = ['110V', '127V', '220V', '220/440V', '110/220V', '440V', '480V', '12VDC', '24VDC', '3HP', '5HP', '10HP', '60Hz', 'N/A'];
        foreach ($voltajes as $i => $v) {
            $listSheet->setCellValue('E'.($i + 1), $v);
        }

        // DEPARTAMENTO (col F)
        foreach ($this->departamentos as $i => $v) {
            $listSheet->setCellValue('F'.($i + 1), $v);
        }

        // LINEA (col G)
        foreach ($this->lineas as $i => $v) {
            $listSheet->setCellValue('G'.($i + 1), $v);
        }

        // SUBFAMILIA (col H)
        foreach ($this->subfamilias as $i => $v) {
            $listSheet->setCellValue('H'.($i + 1), $v);
        }

        // CANAL (col I)
        foreach ($this->canales as $i => $v) {
            $listSheet->setCellValue('I'.($i + 1), $v);
        }

        // VENDEDOR (col J)
        foreach ($this->vendedores as $i => $v) {
            $listSheet->setCellValue('J'.($i + 1), $v);
        }

        // MODULO (col K)
        foreach ($this->modulos as $i => $v) {
            $listSheet->setCellValue('K'.($i + 1), $v);
        }

        $listSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        // === VALIDACIONES en hoja principal ===
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // Dropdown FAMILIA (G)
        $familiaCount = count($this->familiasValidas);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('G'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setErrorStyle(DataValidation::STYLE_STOP)->setShowErrorMessage(true);
            $v->setErrorTitle('Familia no valida')->setError('Selecciona una familia del catalogo.');
            $v->setFormula1('_Listas!$A$1:$A$'.$familiaCount);
        }

        // Dropdown TIPO_PRODUCTO (H) - fijo PT
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('H'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setErrorStyle(DataValidation::STYLE_STOP)->setShowErrorMessage(true);
            $v->setErrorTitle('Tipo no valido')->setError('Solo PT para este template.');
            $v->setFormula1('_Listas!$C$1:$C$1');
        }
        // Pre-fill PT
        for ($row = 2; $row <= 100; $row++) {
            $sheet->setCellValue('H'.$row, 'PT');
        }

        // Dropdown UNIDAD_MEDIDA (I)
        $unidadCount = count($this->unidadesValidas);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('I'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setErrorStyle(DataValidation::STYLE_STOP)->setShowErrorMessage(true);
            $v->setErrorTitle('Unidad no valida')->setError('Selecciona una unidad.');
            $v->setFormula1('_Listas!$B$1:$B$'.$unidadCount);
        }

        // Dropdown LOTE (L)
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('L'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$D$1:$D$2');
        }

        // Dropdown PEDIMENTO (M)
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('M'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$D$1:$D$2');
        }

        // Dropdown VOLTAJE (N)
        $voltajeCount = count($voltajes);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('N'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$E$1:$E$'.$voltajeCount);
        }

        // Dropdown DEPARTAMENTO (O)
        $deptoCount = count($this->departamentos);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('O'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$F$1:$F$'.$deptoCount);
        }

        // Dropdown LINEA (P)
        $lineaCount = count($this->lineas);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('P'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$G$1:$G$'.$lineaCount);
        }

        // Dropdown SUBFAMILIA (Q)
        $subCount = count($this->subfamilias);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('Q'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$H$1:$H$'.$subCount);
        }

        // Dropdown CANAL (R)
        $canalCount = count($this->canales);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('R'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$I$1:$I$'.$canalCount);
        }

        // Dropdown VENDEDOR (S)
        $vendCount = count($this->vendedores);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('S'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$J$1:$J$'.$vendCount);
        }

        // Dropdown MODULO (T)
        $modCount = count($this->modulos);
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('T'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$K$1:$K$'.$modCount);
        }

        // Validacion PRECIO (J)
        for ($row = 2; $row <= 100; $row++) {
            $v = $sheet->getCell('J'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_DECIMAL)->setAllowBlank(true);
            $v->setErrorStyle(DataValidation::STYLE_WARNING)->setShowErrorMessage(true);
            $v->setErrorTitle('Precio invalido')->setError('El precio debe ser mayor a 0.');
            $v->setOperator(DataValidation::OPERATOR_GREATERTHAN)->setFormula1('0');
        }

        // === HOJA DE INSTRUCCIONES ===
        $instrSheet = $spreadsheet->createSheet();
        $instrSheet->setTitle('Instrucciones');
        $instrSheet->getColumnDimension('A')->setWidth(90);

        $instrSheet->setCellValue('A1', 'INSTRUCCIONES - ALTA DE PRODUCTO TERMINADO (PT)');
        $instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instrSheet->getStyle('A1')->getFont()->getColor()->setRGB('6B3FA0');

        $row = 3;
        $reglas = [
            '=== COLORES DEL HEADER ===',
            'Morado oscuro = Obligatorio (siempre llenar)',
            'Morado claro = Opcional (puedes dejarlo vacio)',
            '',
            '=== ESTE TEMPLATE ES EXCLUSIVO PARA PRODUCTO TERMINADO (PT) ===',
            'Los codigos PT empiezan con E, M o N seguido de letras (ej: EAEHO, MAEDC, NAEHO)',
            'La columna TIPO_PRODUCTO ya viene pre-llenada como PT.',
            '',
            '=== COLUMNAS DEL EXCEL ===',
            'CODIGO - Codigo unico PT (ej: EAEHO001, MAEDC100, NAEHO050)',
            'NOMBRE_TIPO - Que es el producto (ej: AEROSOL AROMATIZANTE)',
            'NOMBRE_MARCA - Marca (ej: GLADE, WIESE, OUST)',
            'NOMBRE_MODELO - Referencia (ej: FRESH, CLASSIC)',
            'NOMBRE_MEDIDA - Tamano (ej: 400ML, 19OZ, 170G)',
            'NOMBRE_ESPECIFICACION - Detalle (ej: LAVANDA, CITRICO)',
            'FAMILIA - Seleccionar del dropdown',
            'TIPO_PRODUCTO - PT (ya pre-llenado)',
            'UNIDAD_MEDIDA - PZA, CAJA, etc.',
            'PRECIO - Opcional ($150.50)',
            'CLAVE_SAT - Opcional',
            'LOTE - SI o NO',
            'PEDIMENTO - SI o NO',
            'VOLTAJE - Opcional',
            '',
            '=== CLASIFICACIONES EXCLUSIVAS PT (con dropdown) ===',
            'DEPARTAMENTO - Clasificacion 1 del producto',
            'LINEA - Clasificacion 2 (linea de producto)',
            'SUBFAMILIA - Clasificacion 3 (subfamilia)',
            'CANAL - Clasificacion 4 (canal de venta)',
            'VENDEDOR - Clasificacion 5 (vendedor asignado)',
            'MODULO - Clasificacion 6 (modulo de produccion)',
            '',
            '=== REGLAS ===',
            'Todo en MAYUSCULAS, sin acentos',
            'NOMBRE_TIPO debe tener minimo 2 palabras',
            'Usa los dropdowns para las clasificaciones',
            'No borres ni insertes filas para conservar los dropdowns',
        ];

        foreach ($reglas as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
            if (str_starts_with($texto, '===')) {
                $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
            }
            $row++;
        }

        // Volver a hoja principal
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'template_pt_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'Template_Alta_Producto_PT_Salcom.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function subirExcel(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('excel');
        $path = $file->store('excel-productos-pt', 'public');

        try {
            $fullPath = storage_path('app/public/'.$path);
            if (Str::endsWith($file->getClientOriginalName(), '.csv')) {
                $productos = $this->leerCSV($fullPath);
            } else {
                $spreadsheet = IOFactory::load($fullPath);
                $productos = $this->leerSpreadsheet($spreadsheet);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo leer el archivo. Asegurate de que sea un Excel (.xlsx) o CSV valido. Error: '.$e->getMessage());
        }

        if (empty($productos)) {
            return back()->with('error', 'El archivo esta vacio o no tiene productos.');
        }

        // Verificar columnas obligatorias
        $primeraFila = $productos[0] ?? [];
        $columnasPresentes = array_keys($primeraFila);
        $columnasFaltantes = array_diff($this->columnasObligatorias, $columnasPresentes);
        if (! empty($columnasFaltantes)) {
            return back()->with('error', 'Faltan columnas: '.implode(', ', $columnasFaltantes).'. Usa el template oficial de PT.');
        }

        // Validar cada producto
        $errores = [];
        $validos = 0;
        $conError = 0;

        foreach ($productos as $index => $producto) {
            $fila = $index + 2;
            $erroresFila = $this->validarProductoPT($producto, $fila);
            if (! empty($erroresFila)) {
                $errores = array_merge($errores, $erroresFila);
                $conError++;
            } else {
                $validos++;
            }
        }

        // Si hay errores, generar Excel con correcciones
        if (! empty($errores)) {
            $archivoErrores = $this->generarExcelConErrores($productos, $errores);
            $mensajeError = "Se encontraron errores en {$conError} producto(s):\n\n";
            $porFila = [];
            foreach ($errores as $err) {
                $porFila[$err['fila']][] = $err['error'];
            }
            foreach ($porFila as $fila => $errs) {
                $mensajeError .= "Fila {$fila}: ".implode(' | ', $errs)."\n";
            }

            return back()->with('error', $mensajeError)->with('archivo_correcciones', $archivoErrores);
        }

        // Todo valido: guardar productos
        $creados = 0;
        foreach ($productos as $producto) {
            $codigo = strtoupper(trim($producto['CODIGO'] ?? ''));
            if (empty($codigo)) {
                continue;
            }

            // Construir nombre compuesto
            $partes = array_filter([
                strtoupper(trim($producto['NOMBRE_TIPO'] ?? '')),
                strtoupper(trim($producto['NOMBRE_MARCA'] ?? '')),
                strtoupper(trim($producto['NOMBRE_MODELO'] ?? '')),
                strtoupper(trim($producto['NOMBRE_MEDIDA'] ?? '')),
                strtoupper(trim($producto['NOMBRE_ESPECIFICACION'] ?? '')),
            ]);
            $nombre = implode(' ', $partes);

            $precio = $producto['PRECIO'] ?? null;
            if ($precio) {
                $precio = (float) str_replace(['$', ',', ' '], '', $precio);
            }

            Producto::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'nombre_tipo' => strtoupper(trim($producto['NOMBRE_TIPO'] ?? '')),
                    'nombre_marca' => strtoupper(trim($producto['NOMBRE_MARCA'] ?? '')),
                    'nombre_modelo' => strtoupper(trim($producto['NOMBRE_MODELO'] ?? '')),
                    'nombre_medida' => strtoupper(trim($producto['NOMBRE_MEDIDA'] ?? '')),
                    'nombre_especificacion' => strtoupper(trim($producto['NOMBRE_ESPECIFICACION'] ?? '')),
                    'familia' => strtoupper(trim($producto['FAMILIA'] ?? '')),
                    'tipo_producto' => 'PT',
                    'unidad_venta' => strtoupper(trim($producto['UNIDAD_MEDIDA'] ?? 'PZA')),
                    'precio' => $precio,
                    'clave_sat' => trim($producto['CLAVE_SAT'] ?? ''),
                    'maneja_lotes' => strtoupper(trim($producto['LOTE'] ?? '')) === 'SI',
                    'pedimento' => strtoupper(trim($producto['PEDIMENTO'] ?? '')) === 'SI',
                    'voltaje' => trim($producto['VOLTAJE'] ?? ''),
                    'departamento' => trim($producto['DEPARTAMENTO'] ?? ''),
                    'linea' => trim($producto['LINEA'] ?? ''),
                    'subfamilia_pt' => trim($producto['SUBFAMILIA'] ?? ''),
                    'canal' => trim($producto['CANAL'] ?? ''),
                    'vendedor' => trim($producto['VENDEDOR'] ?? ''),
                    'modulo' => trim($producto['MODULO'] ?? ''),
                    'activo' => true,
                    'proveedor_nombre' => session('admin_nombre') ?? 'Admin',
                    'proveedor_tipo' => 'admin',
                ]
            );
            $creados++;
        }

        // Disparar alertas (si el motor expone verificación post-alta)
        try {
            $engine = app(AlertEngineService::class);
            if (method_exists($engine, 'verificarTodo')) {
                $engine->verificarTodo();
            }
        } catch (\Exception $e) {
        }

        return back()->with('mensaje', "Se dieron de alta {$creados} producto(s) terminado(s) exitosamente.");
    }

    private function validarProductoPT(array $producto, int $fila): array
    {
        $errores = [];
        $codigo = strtoupper(trim($producto['CODIGO'] ?? ''));

        // Codigo obligatorio
        if (empty($codigo)) {
            $errores[] = ['fila' => $fila, 'campo' => 'CODIGO', 'error' => 'CODIGO es obligatorio'];

            return $errores;
        }

        // Validar que sea codigo PT (E/M/N + letras)
        if (! preg_match('/^[EMN][A-Z]{2}/', $codigo)) {
            $errores[] = ['fila' => $fila, 'campo' => 'CODIGO', 'error' => "Codigo '{$codigo}' no parece PT. Los codigos PT empiezan con E/M/N + letras (ej: EAEHO, MAEDC)."];
        }

        // Duplicado en BD
        if (Producto::where('codigo', $codigo)->exists()) {
            $errores[] = ['fila' => $fila, 'campo' => 'CODIGO', 'error' => "DUPLICADO: '{$codigo}' ya existe en el catalogo."];
        }

        // Campos obligatorios
        $camposReq = ['NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA'];
        foreach ($camposReq as $campo) {
            if (empty(trim($producto[$campo] ?? ''))) {
                $errores[] = ['fila' => $fila, 'campo' => $campo, 'error' => "{$campo} es obligatorio"];
            }
        }

        // NOMBRE_TIPO minimo 2 palabras
        $tipo = trim($producto['NOMBRE_TIPO'] ?? '');
        if (! empty($tipo) && str_word_count($tipo) < 2) {
            $errores[] = ['fila' => $fila, 'campo' => 'NOMBRE_TIPO', 'error' => "NOMBRE_TIPO debe tener minimo 2 palabras ('{$tipo}')"];
        }

        // NOMBRE_MEDIDA debe tener numeros
        $medida = trim($producto['NOMBRE_MEDIDA'] ?? '');
        if (! empty($medida) && ! preg_match('/\d/', $medida)) {
            $errores[] = ['fila' => $fila, 'campo' => 'NOMBRE_MEDIDA', 'error' => "NOMBRE_MEDIDA debe contener numeros ('{$medida}')"];
        }

        // Validar FAMILIA contra catalogo
        $familia = strtoupper(trim($producto['FAMILIA'] ?? ''));
        if (! empty($familia) && ! in_array($familia, $this->familiasValidas)) {
            $errores[] = ['fila' => $fila, 'campo' => 'FAMILIA', 'error' => "FAMILIA '{$familia}' no esta en el catalogo."];
        }

        return $errores;
    }

    private function generarExcelConErrores(array $productos, array $errores): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Correcciones PT');

        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE', 'DEPARTAMENTO', 'LINEA', 'SUBFAMILIA', 'CANAL', 'VENDEDOR', 'MODULO', 'ERRORES'];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $col++;
        }

        // Mapear errores por fila
        $erroresPorFila = [];
        $camposConError = [];
        foreach ($errores as $err) {
            $erroresPorFila[$err['fila']][] = $err['error'];
            $camposConError[$err['fila']][] = $err['campo'];
        }

        foreach ($productos as $index => $producto) {
            $fila = $index + 2;
            $col = 'A';
            $dataHeaders = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE', 'DEPARTAMENTO', 'LINEA', 'SUBFAMILIA', 'CANAL', 'VENDEDOR', 'MODULO'];
            foreach ($dataHeaders as $h) {
                $sheet->setCellValue($col.$fila, $producto[$h] ?? '');
                $col++;
            }
            // Errores en ultima columna
            if (isset($erroresPorFila[$fila])) {
                $sheet->setCellValue('U'.$fila, implode(' | ', $erroresPorFila[$fila]));
                // Marcar celdas con error en rojo
                foreach ($camposConError[$fila] ?? [] as $campo) {
                    $colIdx = array_search($campo, $dataHeaders);
                    if ($colIdx !== false) {
                        $cellCol = chr(65 + $colIdx);
                        $sheet->getStyle($cellCol.$fila)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
                    }
                }
            }
        }

        foreach (range('A', 'U') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'correcciones_pt_'.date('Ymd_His').'.xlsx';
        $savePath = 'excel-correcciones/'.$filename;
        $fullPath = storage_path('app/public/'.$savePath);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        $writer->save($fullPath);

        return $savePath;
    }

    private function leerCSV(string $path): array
    {
        $productos = [];
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $headers = array_map(fn ($h) => strtoupper(trim($h)), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }
            $producto = array_combine($headers, $row);
            if (! empty(trim($producto['CODIGO'] ?? ''))) {
                $productos[] = $producto;
            }
        }
        fclose($handle);

        return $productos;
    }

    private function leerSpreadsheet($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheet(0);
        $productos = [];
        $headers = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = trim((string) $cell->getValue());
            }

            if (empty($headers)) {
                $headers = array_map('strtoupper', $rowData);

                continue;
            }

            // Ignorar filas vacias
            if (empty(array_filter($rowData))) {
                continue;
            }

            $producto = [];
            foreach ($headers as $i => $header) {
                $producto[$header] = $rowData[$i] ?? '';
            }

            if (! empty(trim($producto['CODIGO'] ?? ''))) {
                $productos[] = $producto;
            }
        }

        return $productos;
    }
}
