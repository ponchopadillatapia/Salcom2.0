<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarMigracionLote;
use App\Models\ExcelValidacion;
use App\Models\MigracionMasiva;
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
    private array $unidadesValidas = ['PZA', 'CAJA', 'SET', 'KG', 'TONELADA', 'METRO', 'LITRO', 'PAR', 'PACK', 'CUBETA', 'TIRA', 'NA'];

    private array $columnasObligatorias = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'TIPO_PRODUCTO'];

    /**
     * Campos obligatorios adicionales según TIPO_PRODUCTO.
     */
    private array $obligatoriosPorTipo = [
        'MPI' => ['UNIDAD_MEDIDA'],
        'ME' => [],
        'MP' => [],
        'PT' => ['FAMILIA'],
    ];

    private array $familiasValidas = [
        'AEROSOLES', 'LIMPIEZA', 'INSECTICIDAS', 'HERRAMIENTAS', 'REFACCIONES',
        'EMPAQUE', 'MATERIA PRIMA', 'CONSUMIBLE', 'LUBRICANTES', 'ADHESIVOS',
        'PINTURAS', 'SOLVENTES', 'RESINAS', 'PIGMENTOS', 'ADITIVOS',
        'MOTORES', 'BOMBAS', 'VALVULAS', 'TUBERIAS', 'TORNILLERIA',
        'MATERIAL EMPAQUE', 'PRODUCTO TERMINADO', 'INSUMOS',
        'QUIMICOS', 'ELECTRICO', 'FERRETERIA', 'MANTENIMIENTO', 'SEGURIDAD',
        'MAQUINARIA', 'MATERIA PRIMA DE IMPORTACION',
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
        $data['proveedor_nombre'] = session('proveedor_nombre') ?? session('admin_nombre') ?? 'Sistema';
        $data['proveedor_tipo'] = session('proveedor_id') ? 'proveedor' : 'admin';

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

        // === HOJA PRINCIPAL: Productos ===
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        // Headers - sin columnas redundantes
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE', 'DEPARTAMENTO', 'LINEA', 'SUBFAMILIA', 'CANAL', 'VENDEDOR', 'MODULO'];
        $obligatorios = ['CODIGO' => true, 'NOMBRE_TIPO' => true, 'NOMBRE_MARCA' => true, 'NOMBRE_MODELO' => true, 'NOMBRE_MEDIDA' => true, 'NOMBRE_ESPECIFICACION' => true, 'FAMILIA' => true, 'TIPO_PRODUCTO' => true, 'UNIDAD_MEDIDA' => false, 'PRECIO' => false, 'CLAVE_SAT' => false, 'LOTE' => false, 'PEDIMENTO' => false, 'VOLTAJE' => false, 'DEPARTAMENTO' => false, 'LINEA' => false, 'SUBFAMILIA' => false, 'CANAL' => false, 'VENDEDOR' => false, 'MODULO' => false];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            if ($obligatorios[$header]) {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6B3FA0');
            } else {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('9B7BC7');
            }
            $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Template limpio - sin ejemplo en fila 2

        // Formato moneda ($) para columna PRECIO (J) - se ve el $ pero es numero
        $sheet->getStyle('J2:J101')->getNumberFormat()->setFormatCode('$#,##0.00');

        // === HOJA OCULTA: Listas de validacion ===
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

        // === VALIDACIONES en hoja principal ===
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
        $tiposProducto = ['MPI', 'ME', 'MN', 'MP', 'PT', 'RP', 'CONTABLE', 'GASTOS', 'REFACCIONES', 'HERRAMIENTAS', 'MAQUINARIA', 'MUESTRAS', 'INSUMOS', 'EQUIPO', 'SEGURIDAD', 'VEHICULOS', 'MOLDES', 'SERVICIOS'];
        foreach ($tiposProducto as $i => $tipo) {
            $listSheet->setCellValue('C' . ($i + 1), $tipo);
        }
        $tipoCount = count($tiposProducto);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('H'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Tipo de producto no valido');
            $validation->setError('Selecciona un tipo de producto del listado');
            $validation->setFormula1('_Listas!$C$1:$C$' . $tipoCount);
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

        // Dropdowns para las 6 clasificaciones (columnas O-T)
        // DEPARTAMENTO (O) - Clasif1
        $deptos = ['GASTOS','HERRAMIENTAS','INSUMOS','MANO DE OBRA','MAQUINARIA Y EQUIPO','MATERIALES','ME','MI','MN','MO','MP','MPI','MS','PAPELERIA','PT','REFACCIONES','RP','SEGURIDAD','SERVICIOS','VEHICULOS'];
        foreach ($deptos as $i => $v) { $listSheet->setCellValue('F'.($i+1), $v); }
        $deptoCount = count($deptos);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('O'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('_Listas!$F$1:$F$'.$deptoCount);
        }

        // LINEA (P) - Clasif2
        $lineas = ['Aerosoles','Aromatizante Solido','Breeze Matic','Canastilla','Clip On','Cono Gel','Desinfectante','Difusor Electrico','Dispensador','Gel Aromatizante','Hang Air','Insecticida','Lavatrastes','Limpiador','Liquido Goteador','Metered','Micro Can','Mini Spray','Pastilla','Tapete'];
        foreach ($lineas as $i => $v) { $listSheet->setCellValue('G'.($i+1), $v); }
        $lineaCount = count($lineas);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('P'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('_Listas!$G$1:$G$'.$lineaCount);
        }

        // SUBFAMILIA (Q) - Clasif3
        $subfamilias = ['Abrillantador 400ml','Abrillantador 8oz','Accesorios Plasticos','Aerosol 10oz','Aerosol 19oz','Aerosol 8oz','Aerosol Hogar 400ml','Aerosol Metered Institucional','Breeze Matic','Canastilla','Clip On','Cono Gel 170g','Difusor Electrico','Dispensador','Gel 70g','Hang Air','Insecticida','Lavatrastes','Limpiador Sanitario','Liquido Goteador','Metered 180g','Micro Can','Mini Spray','Pastilla Alambre','Pastilla Azul','Pastilla Barra','Pastilla Cloro','Tapete Anti-Salpicadura','Tapete Con Pastilla','Tapete Liso','Tapete Storm'];
        foreach ($subfamilias as $i => $v) { $listSheet->setCellValue('H'.($i+1), $v); }
        $subCount = count($subfamilias);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('Q'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('_Listas!$H$1:$H$'.$subCount);
        }

        // CANAL (R) - Clasif4
        $canales = ['Autoservicio','Abarrotera','Exportacion','Ferretero','Institucional','Mayoreo'];
        foreach ($canales as $i => $v) { $listSheet->setCellValue('I'.($i+1), $v); }
        $canalCount = count($canales);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('R'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('_Listas!$I$1:$I$'.$canalCount);
        }

        // VENDEDOR (S) - Clasif5
        $vendedores = ['Alexandre Cominu','Ana Barrera','Directo Exportacion','Directo Mexico','Francisco Alvarez','Guillermo Quiroz','Imelda Lopez INST','Jesus Sesma','Jorge Ornelas','Luis Ibarra','Marco Vargas','Omar Garcia'];
        foreach ($vendedores as $i => $v) { $listSheet->setCellValue('J'.($i+1), $v); }
        $vendCount = count($vendedores);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('S'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('_Listas!$J$1:$J$'.$vendCount);
        }

        // MODULO (T) - Clasif6
        $modulos = ['AEROSOL','AROMATIZANTE','BREEZE MATIC','CANASTILLA','DISPENSADOR','ENSAMBLES','HERRAMIENTAS','INSECTICIDA','LAVATRASTES','LIMPIADOR','LIQUIDO','MAQUINARIA','PASTILLA','REFACCIONES','TAPETE'];
        foreach ($modulos as $i => $v) { $listSheet->setCellValue('K'.($i+1), $v); }
        $modCount = count($modulos);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('T'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('_Listas!$K$1:$K$'.$modCount);
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

        // === HOJA DE INSTRUCCIONES ===
        $instrSheet = $spreadsheet->createSheet();
        $instrSheet->setTitle('Instrucciones');
        $instrSheet->getColumnDimension('A')->setWidth(90);

        $instrSheet->setCellValue('A1', 'INSTRUCCIONES - ALTA DE PRODUCTO');
        $instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instrSheet->getStyle('A1')->getFont()->getColor()->setRGB('6B3FA0');

        $row = 3;
        $reglas = [
            '=== COLORES DEL HEADER ===',
            'Morado oscuro = Obligatorio (siempre llenar)',
            'Morado claro = Opcional (puedes dejarlo vacio)',
            '',
            '=== COLUMNAS DEL EXCEL (en orden) ===',
            'CODIGO - Codigo unico del producto (ej: MPI0538, ME0201)',
            'NOMBRE_TIPO - Que es el producto (ej: MOTOR ELECTRICO, RESINA EPOXICA)',
            '  IMPORTANTE: Minimo 2 palabras (PINTURA VINILICA, no solo PINTURA)',
            'NOMBRE_MARCA - Quien lo fabrica (ej: WEG, SKF, 3M, ALPHA)',
            'NOMBRE_MODELO - Referencia del fabricante (ej: W22, IND-500, CP-40)',
            'NOMBRE_MEDIDA - Tamano con numeros (ej: 500ML, 3HP, 30CMX30CM, 40X30X25)',
            'NOMBRE_ESPECIFICACION - Detalle adicional (ej: TRIFASICO, TRANSPARENTE)',
            'FAMILIA - Seleccionar del dropdown (ej: MATERIA PRIMA, MANTENIMIENTO)',
            'TIPO_PRODUCTO - MPI (Materia Prima), ME (Empaque), MN (Mantenimiento)',
            'UNIDAD_MEDIDA - Solo KG, PZA o CAJA',
            'PRECIO - Opcional. Con $ y decimales (ej: $150.50)',
            'CLAVE_SAT - Opcional. Codigo SAT (ej: 10191509)',
            'LOTE - SI o NO. Obligatorio solo si TIPO_PRODUCTO = MPI',
            'PEDIMENTO - SI o NO. Obligatorio solo si TIPO_PRODUCTO = MPI',
            'VOLTAJE - Opcional. Seleccionar del dropdown (ej: 220V, 220/440V)',
            '',
            '=== QUE SIGNIFICAN MPI, ME Y MN ===',
            'MPI = Materia Prima Importacion - Requiere LOTE y PEDIMENTO',
            'ME = Material de Empaque - Cajas, etiquetas, bolsas',
            'MN = Mantenimiento - Motores, refacciones, herramientas',
            '',
            '=== REGLAS GENERALES ===',
            'Todo en MAYUSCULAS, sin acentos ni caracteres especiales',
            'NOMBRE_TIPO debe tener MINIMO 2 PALABRAS (ej: PINTURA VINILICA, no solo PINTURA)',
            'NOMBRE_MEDIDA debe tener numeros (500ML, 3HP, 30CMX30CM, 40X30X25). Para dimensiones usa X sin espacios: 30CMX30CM',
            'NOMBRE_ESPECIFICACION no debe repetir datos de otros campos',
            'NOMBRE_TIPO no puede ser una marca (WEG, SKF van en NOMBRE_MARCA)',
            'NOMBRE_MARCA no puede ser una medida (3HP, 500ML van en NOMBRE_MEDIDA)',
            'PRECIO debe llevar $ al inicio (ej: $150.50). Si no sabes el precio, dejalo vacio',
            'No repetir productos que ya existen en el catalogo',
            '',
            '=== SI LA IA RECHAZA TU ARCHIVO ===',
            '1. Las celdas con error se marcan en ROJO en el Excel descargable',
            '2. En la pagina web te dice exactamente que corregir',
            '3. Corrige los campos marcados y vuelve a subir',
            '',
            '=== COMO EMPEZAR ===',
            'Empieza a llenar tus productos desde la fila 2.',
            'NO borres ni insertes filas porque pierdes los dropdowns.',
            'Usa los dropdowns para FAMILIA, TIPO_PRODUCTO, UNIDAD_MEDIDA, LOTE, PEDIMENTO y VOLTAJE.',
        ];

        foreach ($reglas as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
            if (str_starts_with($texto, '===')) {
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

            // Identificar filas con error de DUPLICADO - no enviarlas a la IA
            $filasDuplicadas = [];
            foreach ($errores as $err) {
                if (str_contains($err['error'], 'DUPLICADO')) {
                    $filasDuplicadas[] = $err['fila'];
                }
            }

            foreach ($productos as $index => $prod) {
                $fila = $index + 2;
                // No enviar duplicados a la IA - ya pasaron validacion antes
                if (in_array($fila, $filasDuplicadas)) {
                    continue;
                }
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

            if (! empty($productosParaIA)) {
                // Enviar en lotes de 15 para no exceder tokens
                $lotes = array_chunk($productosParaIA, 15);
                foreach ($lotes as $lote) {
                    $prompt = 'Eres un detector de CAMPOS CRUZADOS en productos industriales. Tu UNICO trabajo es detectar si un dato esta en el campo INCORRECTO (puesto donde no va).

DEFINICION DE CAMPOS:
- NOMBRE_TIPO = Que ES el producto (ej: MOTOR ELECTRICO, PINTURA VINILICA, CAJA CARTON)
- NOMBRE_MARCA = EMPRESA que lo fabrica (ej: WEG, COMEX, 3M, APPLE, SAMSUNG)
- NOMBRE_MODELO = Referencia/linea del fabricante (ej: W22, VIN-100, IPHONE 15)
- NOMBRE_MEDIDA = Tamano/capacidad con NUMEROS (ej: 19LT, 500ML, 3HP, 220V)
- NOMBRE_ESPECIFICACION = Caracteristicas adicionales (ej: TRIFASICO, BLANCO MATE, DOBLE PARED)

SOLO marca error si detectas alguno de estos casos:
1. Una MARCA conocida esta en NOMBRE_TIPO (ej: "WEG" en tipo, deberia estar en marca)
2. Una MEDIDA con numeros esta en NOMBRE_MARCA o NOMBRE_TIPO (ej: "19LT" en marca)
3. Un TIPO DE PRODUCTO esta en NOMBRE_MARCA (ej: "PINTURA VINILICA" en marca)
4. NOMBRE_ESPECIFICACION tiene el MISMO texto EXACTO que NOMBRE_MEDIDA (dato duplicado)
5. Una MARCA conocida esta en NOMBRE_MODELO (ej: "COMEX" en modelo, deberia ser marca)

NO marques error por:
- Que el nombre del tipo sea corto o generico (eso ya lo valida PHP)
- Que la especificacion sea parecida pero no identica a otro campo
- Opinion sobre si un nombre es "suficientemente descriptivo"
- Formato, mayusculas, caracteres (eso ya lo valida PHP)

Para cada error incluye "sugerencia" con el valor correcto en MAYUSCULAS.
Si NO sabes el valor real, pon ejemplo: "(EJEMPLO: VALOR)"

Productos: '.json_encode($lote, JSON_UNESCAPED_UNICODE).'

Responde UNICAMENTE JSON valido, sin markdown:
{"errores_ia": [{"fila": N, "campo": "NOMBRE_X", "error": "explicacion corta", "sugerencia": "VALOR"}]}
Si todo correcto: {"errores_ia": []}';

                    $resultado = $iaService->llamarClaude($prompt);
                    if ($resultado['success'] && $resultado['content']) {
                        $contenido = preg_replace('/```json\s*/', '', $resultado['content']);
                        $contenido = preg_replace('/```\s*/', '', $contenido);
                        $iaResult = json_decode(trim($contenido), true);
                        if ($iaResult && ! empty($iaResult['errores_ia'])) {
                            foreach ($iaResult['errores_ia'] as $errIA) {
                                $filaIA = (int) ($errIA['fila'] ?? 0);
                                if ($filaIA < 2) {
                                    continue;
                                }
                                $campoIA = $errIA['campo'] ?? 'NOMBRE_TIPO';

                                // No duplicar errores que PHP ya detecto
                                $yaExiste = false;
                                foreach ($errores as $eEx) {
                                    if ($eEx['fila'] === $filaIA && $eEx['campo'] === $campoIA) {
                                        $yaExiste = true;
                                        break;
                                    }
                                }
                                if (! $yaExiste) {
                                    $idx = $filaIA - 2;

                                    // VERIFICAR con PHP que el error de la IA sea real
                                    // La IA puede alucinar - confirmar los datos reales del Excel
                                    if (isset($productos[$idx])) {
                                        $errorTexto = strtolower($errIA['error'] ?? '');
                                        $prod = $productos[$idx];

                                        // Si dice "identico" o "duplicado" entre especificacion y medida, verificar
                                        if (str_contains($errorTexto, 'identic') || str_contains($errorTexto, 'duplica') || str_contains($errorTexto, 'mismo texto')) {
                                            $medida = strtoupper(trim($prod['NOMBRE_MEDIDA'] ?? ''));
                                            $espec = strtoupper(trim($prod['NOMBRE_ESPECIFICACION'] ?? ''));
                                            if ($medida !== $espec) {
                                                continue; // Falso positivo - NO son identicos
                                            }
                                        }

                                        // Si dice que hay una medida (numeros) en NOMBRE_MARCA, verificar que NOMBRE_MARCA realmente tenga numeros
                                        if ($campoIA === 'NOMBRE_MARCA' && (str_contains($errorTexto, 'medida') || str_contains($errorTexto, 'numero'))) {
                                            $valorMarca = trim($prod['NOMBRE_MARCA'] ?? '');
                                            if (!preg_match('/\d/', $valorMarca)) {
                                                continue; // Falso positivo - NOMBRE_MARCA no tiene numeros
                                            }
                                        }

                                        // Si dice que hay una medida en NOMBRE_TIPO, verificar que NOMBRE_TIPO realmente tenga numeros
                                        if ($campoIA === 'NOMBRE_TIPO' && (str_contains($errorTexto, 'medida') || str_contains($errorTexto, 'numero'))) {
                                            $valorTipo = trim($prod['NOMBRE_TIPO'] ?? '');
                                            if (!preg_match('/\d/', $valorTipo)) {
                                                continue; // Falso positivo - NOMBRE_TIPO no tiene numeros
                                            }
                                        }

                                        // Si dice que hay una medida en NOMBRE_MEDIDA pero el error reporta un valor que NO es el real, descartar
                                        if ($campoIA === 'NOMBRE_MEDIDA' && (str_contains($errorTexto, 'marca') || str_contains($errorTexto, 'campo'))) {
                                            // Verificar que la sugerencia de la IA no sea el valor de OTRA fila
                                            $sugerenciaIA = strtoupper(trim($errIA['sugerencia'] ?? ''));
                                            $medidaReal = strtoupper(trim($prod['NOMBRE_MEDIDA'] ?? ''));
                                            // Si la IA dice "pon X" pero X no tiene relacion con esta fila, descartar
                                            if ($sugerenciaIA && $sugerenciaIA !== $medidaReal && !str_contains($medidaReal, $sugerenciaIA)) {
                                                // Verificar si la sugerencia es el valor de NOMBRE_MEDIDA de otra fila (confusion)
                                                $esDeOtraFila = false;
                                                foreach ($productos as $otroProd) {
                                                    if (strtoupper(trim($otroProd['NOMBRE_MEDIDA'] ?? '')) === $sugerenciaIA && $otroProd !== $prod) {
                                                        $esDeOtraFila = true;
                                                        break;
                                                    }
                                                }
                                                if ($esDeOtraFila) {
                                                    continue; // La IA confundio filas
                                                }
                                            }
                                        }

                                        // Si dice que hay una marca en NOMBRE_TIPO, verificar que realmente sea una marca conocida
                                        if ($campoIA === 'NOMBRE_TIPO' && str_contains($errorTexto, 'marca')) {
                                            $valorTipo = strtoupper(trim($prod['NOMBRE_TIPO'] ?? ''));
                                            $marcasTop = ['WEG', 'SKF', '3M', 'ALPHA', 'SIEMENS', 'ABB', 'SCHNEIDER', 'BOSCH', 'SAMSUNG', 'APPLE', 'LG', 'SONY', 'COMEX', 'TRUPER', 'PEMEX', 'DUPONT', 'HENKEL', 'DE LA ROSA', 'BIMBO', 'NESTLE'];
                                            if (!in_array($valorTipo, $marcasTop)) {
                                                continue; // No es una marca conocida - falso positivo
                                            }
                                        }
                                    }

                                    // Si la sugerencia es igual al valor actual, descartar (falso positivo)
                                    $sugerencia = $errIA['sugerencia'] ?? null;
                                    if ($sugerencia) {
                                        if (isset($productos[$idx])) {
                                            $campoKey = $campoIA;
                                            $valorActual = strtoupper(trim($productos[$idx][$campoKey] ?? ''));
                                            $sugerenciaLimpia = strtoupper(trim($sugerencia));
                                            if ($valorActual === $sugerenciaLimpia) {
                                                continue; // La IA sugiere lo mismo que ya tiene - falso positivo
                                            }
                                        }
                                    }

                                    // Si la IA dice que una marca está en NOMBRE_MODELO, pero NOMBRE_MARCA ya tiene esa marca, es falso positivo
                                    if ($campoIA === 'NOMBRE_MODELO' && str_contains($errorTexto, 'marca')) {
                                        $valorModelo = strtoupper(trim($prod['NOMBRE_MODELO'] ?? ''));
                                        $valorMarca = strtoupper(trim($prod['NOMBRE_MARCA'] ?? ''));
                                        // Si NOMBRE_MODELO tiene un código numérico, es válido (ej: "20012420 ABRILLANTADOR 400ML")
                                        if (preg_match('/\d{4,}/', $valorModelo)) {
                                            continue; // Tiene código numérico - es válido
                                        }
                                        // Si la marca que sugiere ya está en NOMBRE_MARCA, falso positivo
                                        $sugerenciaIA2 = strtoupper(trim($errIA['sugerencia'] ?? ''));
                                        if ($sugerenciaIA2 && str_contains($valorMarca, $sugerenciaIA2)) {
                                            continue; // Ya está en NOMBRE_MARCA
                                        }
                                    }

                                    // Si la IA dice que hay una medida en NOMBRE_MARCA, verificar que NOMBRE_MARCA realmente tenga números
                                    // Excluir marcas como "SURE SCENTS", "ANGEL OF MINE" que no tienen números
                                    if ($campoIA === 'NOMBRE_MEDIDA' && str_contains($errorTexto, 'NOMBRE_MARCA')) {
                                        $valorMarcaReal = trim($prod['NOMBRE_MARCA'] ?? '');
                                        if (!preg_match('/\d/', $valorMarcaReal)) {
                                            continue; // NOMBRE_MARCA no tiene números - falso positivo
                                        }
                                    }

                                    $mensajeError = 'IA: '.($errIA['error'] ?? 'Campo con dato incorrecto');
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
            // TODOS validados - dar de alta todos
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
                        'proveedor_nombre' => session('proveedor_nombre') ?? session('admin_nombre') ?? 'Sistema',
                        'proveedor_tipo' => session('proveedor_id') ? 'proveedor' : 'admin',
                        'departamento' => strtoupper(trim($prod['DEPARTAMENTO'] ?? '')),
                        'linea' => trim($prod['LINEA'] ?? ''),
                        'subfamilia_pt' => trim($prod['SUBFAMILIA'] ?? ''),
                        'canal' => trim($prod['CANAL'] ?? ''),
                        'vendedor' => trim($prod['VENDEDOR'] ?? ''),
                        'modulo' => strtoupper(trim($prod['MODULO'] ?? '')),
                    ]
                );
            }

            $listaProductos = [];
            foreach ($productos as $prod) {
                $codigo = strtoupper(trim($prod['CODIGO'] ?? ''));
                $nombre = strtoupper(trim($prod['NOMBRE_TIPO'] ?? '')).' '.strtoupper(trim($prod['NOMBRE_MARCA'] ?? '')).' '.strtoupper(trim($prod['NOMBRE_MODELO'] ?? ''));
                $listaProductos[] = "{$codigo} - {$nombre}";
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

            $mensajeExito = "OK - {$validos} producto(s) validados y dados de alta en el catalogo:\n";
            foreach ($listaProductos as $item) {
                $mensajeExito .= "* {$item}\n";
            }

            return back()->with('mensaje', $mensajeExito);
        }

        // Hay errores - dar de alta los validos y mostrar errores de los demas
        $filasConErrorFinal = array_unique(array_column($errores, 'fila'));
        $productosAlta = [];
        foreach ($productos as $index => $prod) {
            $fila = $index + 2;
            if (!in_array($fila, $filasConErrorFinal)) {
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
                        'proveedor_nombre' => session('proveedor_nombre') ?? session('admin_nombre') ?? 'Sistema',
                        'proveedor_tipo' => session('proveedor_id') ? 'proveedor' : 'admin',
                        'departamento' => strtoupper(trim($prod['DEPARTAMENTO'] ?? '')),
                        'linea' => trim($prod['LINEA'] ?? ''),
                        'subfamilia_pt' => trim($prod['SUBFAMILIA'] ?? ''),
                        'canal' => trim($prod['CANAL'] ?? ''),
                        'vendedor' => trim($prod['VENDEDOR'] ?? ''),
                        'modulo' => strtoupper(trim($prod['MODULO'] ?? '')),
                    ]
                );
                $codigo = strtoupper(trim($prod['CODIGO'] ?? ''));
                $nombre = strtoupper(trim($prod['NOMBRE_TIPO'] ?? '')).' '.strtoupper(trim($prod['NOMBRE_MARCA'] ?? '')).' '.strtoupper(trim($prod['NOMBRE_MODELO'] ?? ''));
                $productosAlta[] = ['fila' => $fila, 'texto' => "{$codigo} - {$nombre}"];
            }
        }

        // Tiene errores - generar Excel con correcciones (solo celdas en rojo)

        $fullPath = $this->generarExcelConErrores($productos, $errores);
        $relativePath = str_replace(storage_path('app/public/'), '', $fullPath);

        // Ordenar errores por numero de fila
        usort($errores, function ($a, $b) {
            return $a['fila'] <=> $b['fila'];
        });

        $errorMsg = "ERROR: El Excel tiene {$conError} producto(s) con errores.\n\n";
        $errorMsg .= "Las celdas con error estan marcadas en ROJO en el Excel descargable.\n";
        $errorMsg .= "Corrige los campos senalados y vuelve a subir.\n\n";
        $errorMsg .= "ERRORES ENCONTRADOS:\n";
        foreach ($errores as $err) {
            $errorTextoLimpio = $err['error'];
            $errorTextoLimpio = str_replace('COMO CORREGIR: ', '-> ', $errorTextoLimpio);
            // Resaltar la CORRECCION con un tag HTML para que el proveedor la identifique
            if (str_contains($errorTextoLimpio, '|| CORRECCION:')) {
                $partes = explode('|| CORRECCION:', $errorTextoLimpio);
                $explicacion = htmlspecialchars(trim($partes[0]));
                $correccion = htmlspecialchars(trim($partes[1] ?? ''));
                $errorMsg .= "* Fila {$err['fila']} - {$err['campo']}: {$explicacion} <span class=\"correccion-tag\">PON: {$correccion}</span>\n";
            } elseif (str_contains($errorTextoLimpio, '-> ')) {
                // Errores de PHP con sugerencia despues de ->
                $partes = explode('-> ', $errorTextoLimpio, 2);
                $explicacion = htmlspecialchars(trim($partes[0]));
                $sugerenciaPHP = trim($partes[1] ?? '');
                // Extraer solo el ejemplo concreto si hay uno entre parentesis o al inicio
                if (preg_match('/\(ej:\s*([^)]+)\)/', $sugerenciaPHP, $matches)) {
                    $ejemploConcreto = htmlspecialchars(trim($matches[1]));
                    $sugerenciaPHP = htmlspecialchars($sugerenciaPHP);
                    $errorMsg .= "* Fila {$err['fila']} - {$err['campo']}: {$explicacion} <span class=\"correccion-tag\">EJEMPLO: {$ejemploConcreto}</span>\n";
                } else {
                    $sugerenciaPHP = htmlspecialchars($sugerenciaPHP);
                    $errorMsg .= "* Fila {$err['fila']} - {$err['campo']}: {$explicacion} <span class=\"correccion-tag\">PON: {$sugerenciaPHP}</span>\n";
                }
            } else {
                $errorTextoLimpio = htmlspecialchars($errorTextoLimpio);
                $errorMsg .= "* Fila {$err['fila']} - {$err['campo']}: {$errorTextoLimpio}\n";
            }
        }

        // Si hubo productos que si se dieron de alta, agregar mensaje de exito
        $mensajeExito = null;
        if (!empty($productosAlta)) {
            $mensajeExito = "OK - ".count($productosAlta)." producto(s) SI se dieron de alta:\n";
            foreach ($productosAlta as $item) {
                $mensajeExito .= "* Fila {$item['fila']} - {$item['texto']}\n";
            }
        }

        $response = back()
            ->with('error', $errorMsg)
            ->with('archivo_correcciones', $relativePath);

        if ($mensajeExito) {
            $response = $response->with('mensaje', $mensajeExito);
        }

        return $response;
    }

    /**
     * Generar Excel XLSX con colores - celdas con error en rojo, aprobadas en verde.
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
        $sheet->setTitle('Productos');

        // Headers con mismo formato del template (morado)
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE', 'DEPARTAMENTO', 'LINEA', 'SUBFAMILIA', 'CANAL', 'VENDEDOR', 'MODULO'];
        $obligatorios = ['CODIGO' => true, 'NOMBRE_TIPO' => true, 'NOMBRE_MARCA' => true, 'NOMBRE_MODELO' => true, 'NOMBRE_MEDIDA' => true, 'NOMBRE_ESPECIFICACION' => true, 'FAMILIA' => true, 'TIPO_PRODUCTO' => true, 'UNIDAD_MEDIDA' => false, 'PRECIO' => false, 'CLAVE_SAT' => false, 'LOTE' => false, 'PEDIMENTO' => false, 'VOLTAJE' => false, 'DEPARTAMENTO' => false, 'LINEA' => false, 'SUBFAMILIA' => false, 'CANAL' => false, 'VENDEDOR' => false, 'MODULO' => false];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            if ($obligatorios[$header]) {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6B3FA0');
            } else {
                $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('9B7BC7');
            }
            $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Hoja oculta con listas de validacion
        $listSheet = $spreadsheet->createSheet();
        $listSheet->setTitle('_Listas');

        $familias = $this->familiasValidas;
        foreach ($familias as $i => $fam) {
            $listSheet->setCellValue('A'.($i + 1), $fam);
        }
        $unidades = $this->unidadesValidas;
        foreach ($unidades as $i => $uni) {
            $listSheet->setCellValue('B'.($i + 1), $uni);
        }
        $tiposProducto2 = ['MPI', 'ME', 'MN', 'MP', 'PT', 'RP', 'CONTABLE', 'GASTOS', 'REFACCIONES', 'HERRAMIENTAS', 'MAQUINARIA', 'MUESTRAS', 'INSUMOS', 'EQUIPO', 'SEGURIDAD', 'VEHICULOS', 'MOLDES', 'SERVICIOS'];
        foreach ($tiposProducto2 as $i => $tipo) {
            $listSheet->setCellValue('C' . ($i + 1), $tipo);
        }
        $listSheet->setCellValue('D1', 'SI');
        $listSheet->setCellValue('D2', 'NO');
        // Voltajes
        $voltajes = ['110V', '127V', '220V', '220/440V', '110/220V', '440V', '480V', '12VDC', '24VDC', '3HP', '5HP', '10HP', '60Hz', 'N/A'];
        foreach ($voltajes as $i => $volt) {
            $listSheet->setCellValue('E'.($i + 1), $volt);
        }
        $listSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        // Volver a hoja principal
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // Mapeo de columnas
        $colMap = [
            'CODIGO' => 'A', 'NOMBRE_TIPO' => 'B', 'NOMBRE_MARCA' => 'C', 'NOMBRE_MODELO' => 'D',
            'NOMBRE_MEDIDA' => 'E', 'NOMBRE_ESPECIFICACION' => 'F', 'FAMILIA' => 'G',
            'TIPO_PRODUCTO' => 'H', 'UNIDAD_MEDIDA' => 'I', 'PRECIO' => 'J', 'CLAVE_SAT' => 'K',
            'LOTE' => 'L', 'PEDIMENTO' => 'M', 'VOLTAJE' => 'N',
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
                foreach ($erroresPorFila[$fila] as $err) {
                    $campoError = $err['campo'];
                    if (isset($colMap[$campoError])) {
                        $cellRef = $colMap[$campoError].$excelRow;
                        $sheet->getStyle($cellRef)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
                        $sheet->getStyle($cellRef)->getFont()->getColor()->setRGB('CC0000');
                    }
                }
            } else {
                // Fila sin error = se dio de alta. Marcar toda la fila en verde claro.
                $sheet->getStyle("A{$excelRow}:N{$excelRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1FAE5');
                $sheet->getStyle("A{$excelRow}:N{$excelRow}")->getFont()->getColor()->setRGB('065F46');
            }
        }

        // Dropdowns - FAMILIA (G), TIPO_PRODUCTO (H), UNIDAD_MEDIDA (I), LOTE (L), PEDIMENTO (M)
        $familiaCount = count($familias);
        $unidadCount = count($unidades);
        $maxRow = count($productos) + 10;

        for ($row = 2; $row <= $maxRow; $row++) {
            // FAMILIA
            $v = $sheet->getCell('G'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$A$1:$A$'.$familiaCount);

            // TIPO_PRODUCTO
            $v = $sheet->getCell('H'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$C$1:$C$3');

            // UNIDAD_MEDIDA
            $v = $sheet->getCell('I'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$B$1:$B$'.$unidadCount);

            // LOTE
            $v = $sheet->getCell('L'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$D$1:$D$2');

            // PEDIMENTO
            $v = $sheet->getCell('M'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$D$1:$D$2');

            // VOLTAJE
            $v = $sheet->getCell('N'.$row)->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true);
            $v->setFormula1('_Listas!$E$1:$E$'.count($voltajes));
        }

        // Formato moneda para PRECIO
        $sheet->getStyle('J2:J'.$maxRow)->getNumberFormat()->setFormatCode('$#,##0.00');

        // Guardar como XLSX
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/public/excel-correcciones/Correcciones_'.date('Y-m-d_His').'.xlsx');

        $dir = dirname($tempPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Validar un producto individual - Reglas de estandarizacion industrial.
     *
     * Nomenclatura: [TIPO] + [MARCA] + [MODELO/MEDIDA] + [ESPECIFICACIÃ"N]
     * Ejemplo correcto: BALERO SKF 6205 2RS
     * Ejemplo correcto: MOTOR WEG 3HP 220/440V TRIFASICO
     * Ejemplo correcto: INSECTICIDA MT XTERM BIO S/AROMA 180G/274ML C/12
     */
    private function validarProducto(array $producto, int $fila): array
    {
        $errores = [];

        // === 1. CAMPOS OBLIGATORIOS BASE ===
        foreach ($this->columnasObligatorias as $campo) {
            if (empty(trim($producto[$campo] ?? ''))) {
                $sugerencia = match ($campo) {
                    'CODIGO' => 'Escribe un codigo unico (ej: MPI0538, ME0201)',
                    'NOMBRE_TIPO' => 'Escribe QUE ES el producto (ej: RESINA EPOXICA, MOTOR ELECTRICO, CAJA CORRUGADA)',
                    'NOMBRE_MARCA' => 'Escribe QUIEN lo fabrica (ej: WEG, SKF, 3M, ALPHA, KRAFT)',
                    'NOMBRE_MODELO' => 'Escribe la REFERENCIA del fabricante (ej: W22, IND-500, CP-40, T-100)',
                    'NOMBRE_MEDIDA' => 'Escribe el TAMANO o capacidad con numeros (ej: 500ML, 3HP, 30CMX30CM, 40X30X25)',
                    'NOMBRE_ESPECIFICACION' => 'Escribe CARACTERISTICAS adicionales (ej: TRIFASICO, TRANSPARENTE, DOBLE PARED)',
                    'FAMILIA' => 'Selecciona del dropdown una familia del catalogo oficial',
                    'TIPO_PRODUCTO' => 'Selecciona del dropdown: MPI, ME, MN, MP, PT, etc.',
                    'UNIDAD_MEDIDA' => 'Selecciona del dropdown: KG, PZA, CAJA, etc.',
                    default => 'Este campo es obligatorio, no puede estar vacio',
                };
                $errores[] = [
                    'fila' => $fila,
                    'campo' => $campo,
                    'error' => "Campo obligatorio vacio. COMO CORREGIR: {$sugerencia}",
                ];
            }
        }

        // === 1b. CAMPOS OBLIGATORIOS ADICIONALES POR TIPO_PRODUCTO ===
        $tipoProductoParaOblig = strtoupper(trim($producto['TIPO_PRODUCTO'] ?? ''));
        $camposExtra = $this->obligatoriosPorTipo[$tipoProductoParaOblig] ?? [];
        foreach ($camposExtra as $campo) {
            if (empty(trim($producto[$campo] ?? ''))) {
                $sugerencia = match ($campo) {
                    'FAMILIA' => 'Para PT es obligatorio. Selecciona del dropdown.',
                    'UNIDAD_MEDIDA' => 'Para MPI es obligatorio. Selecciona: KG, PZA, CAJA, etc.',
                    default => 'Este campo es obligatorio para ' . $tipoProductoParaOblig,
                };
                $errores[] = [
                    'fila' => $fila,
                    'campo' => $campo,
                    'error' => "Campo obligatorio para {$tipoProductoParaOblig}. COMO CORREGIR: {$sugerencia}",
                ];
            }
        }

        $nombre = trim($producto['NOMBRE'] ?? '');
        $familia = strtoupper(trim($producto['FAMILIA'] ?? ''));
        $subfamilia = strtoupper(trim($producto['SUBFAMILIA'] ?? ''));
        $unidad = strtoupper(trim($producto['UNIDAD_MEDIDA'] ?? ''));
        $precio = $producto['PRECIO'] ?? '';

        // === VALIDAR CÓDIGO VS TIPO_PRODUCTO ===
        $codigoRaw = strtoupper(trim($producto['CODIGO'] ?? ''));
        $tipoProductoVal = strtoupper(trim($producto['TIPO_PRODUCTO'] ?? ''));
        if ($codigoRaw && $tipoProductoVal) {
            $tipoEsperado = $this->inferirTipoPorCodigo($codigoRaw);
            if ($tipoEsperado && $tipoEsperado !== $tipoProductoVal) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'TIPO_PRODUCTO',
                    'error' => "El codigo '{$codigoRaw}' corresponde a '{$tipoEsperado}' pero pusiste '{$tipoProductoVal}'. -> Pon: {$tipoEsperado}",
                ];
            }
        }

        // === 1.5 CODIGO - Solo alfanumerico y guiones ===
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

        // === 2. NOMENCLATURA - Construir NOMBRE desde las 5 partes ===

        // === 2. NOMENCLATURA - Construir NOMBRE desde las 5 partes ===
        // Las 5 partes - validar con valor ORIGINAL, luego convertir
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
                // Excluir medidas con formato de dimensiones (30CMX30CM, 40X30X25, etc.)
                $esFormatoDimensiones = preg_match('/\d+\s*(CM|MM|MT|LT|ML|KG|GR|PZA)?\s*X\s*\d+/i', $info['valor']);
                if (!$esFormatoDimensiones) {
                    // Intentar limpiar espacios - si sin espacios es un formato valido, sugerir correccion
                    $valorSinEspacios = strtoupper(str_replace(' ', '', $info['valor']));
                    $esFormatoDimensionesLimpio = preg_match('/^\d+(CM|MM|MT|LT|ML|KG|GR|PZA)?X\d+(CM|MM|MT|LT|ML|KG|GR|PZA)?$/i', $valorSinEspacios);

                    if ($esFormatoDimensionesLimpio && $campo === 'NOMBRE_MEDIDA') {
                        // Es una medida con dimensiones pero MAL escrita (con espacios de mas)
                        $errores[] = [
                            'fila' => $fila,
                            'campo' => $campo,
                            'error' => "Medida con formato incorrecto: '{$info['valor']}'. Las dimensiones van sin espacios. -> Pon: {$valorSinEspacios}",
                        ];
                    } else {
                        $letras = preg_replace('/[\d\-\/\.\s]/', '', $info['valor']);
                        if (strlen($letras) >= 5 && ! preg_match('/[AEIOU]/i', $letras)) {
                            // Si es NOMBRE_MEDIDA, verificar que no sea una abreviación válida de presentación
                            if ($campo === 'NOMBRE_MEDIDA') {
                                // Patrones válidos: C/12PZS, C/6PCS, C/12, PZS, PZ, etc.
                                $esPresentacionValida = preg_match('/C\/\d+(PZS|PZ|PCS)?\.?/i', $info['valor']);
                                if ($esPresentacionValida) {
                                    continue; // Es válido, no marcar error
                                }
                            }

                            // Intentar sugerir correccion para NOMBRE_MEDIDA
                            $sugerenciaLimpia = '';
                            if ($campo === 'NOMBRE_MEDIDA') {
                                // Quitar espacios y ver si tiene numeros+unidad
                                $limpio = strtoupper(str_replace(' ', '', $info['valor']));
                                if (preg_match('/\d/', $limpio)) {
                                    $sugerenciaLimpia = $limpio;
                                }
                            }
                            $mensajeError = "Texto ilegible detectado: '{$info['valor']}'.";
                            if ($sugerenciaLimpia) {
                                $mensajeError .= " -> Pon: {$sugerenciaLimpia}";
                            } else {
                                $mensajeError .= " COMO CORREGIR: {$info['desc']}";
                            }
                            $errores[] = [
                                'fila' => $fila,
                                'campo' => $campo,
                                'error' => $mensajeError,
                            ];
                        }
                    }
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
            $todasSonAdjetivos = ! empty($palabrasTipo) && count(array_diff($palabrasTipo, $soloAdjetivos)) === 0;
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
                'error' => "La medida debe contener un valor numerico. Recibido: '{$nombreMedidaRaw}'. ->  Escribe tamano con numeros (ej: 500ML, 3HP, 30CMX30CM, 40X30X25)",
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
        if ($nombreMedidaRaw && preg_match('/^[A-Z]{2,}-\d+$/i', $nombreMedidaRaw) && ! preg_match('/\d+(MM|CM|LT|ML|KG|GR|HP|V|W)/i', $nombreMedidaRaw)) {
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

        // NOMBRE_MARCA no debe ser una palabra suelta que describe un tipo de producto
        $palabrasTipo = ['PINTURA', 'MOTOR', 'BOMBA', 'CAJA', 'CINTA', 'BOLSA', 'ACEITE', 'RESINA', 'PIGMENTO', 'ETIQUETA', 'TALADRO', 'FLEJE', 'SOLVENTE', 'ADHESIVO', 'CEMENTO', 'INSECTICIDA', 'DETERGENTE', 'TORNILLO', 'TUERCA', 'CABLE', 'FOCO', 'LAMPARA', 'VALVULA', 'TUBERIA', 'BALERO', 'RODAMIENTO'];
        if ($nombreMarcaRaw && in_array(strtoupper(trim($nombreMarcaRaw)), $palabrasTipo)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MARCA',
                'error' => "'{$nombreMarcaRaw}' es un TIPO DE PRODUCTO, no una marca. ->  NOMBRE_MARCA debe ser QUIEN fabrica el producto (ej: COMEX, TRUPER, WEG). El tipo va en NOMBRE_TIPO.",
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

        // NOMBRE_TIPO - Detectar orden incorrecto (adjetivo antes de sustantivo)
        // Ejemplo: "INDUSTRIAL SOLVENTE" deberia ser "SOLVENTE INDUSTRIAL"
        if ($nombreTipoRaw) {
            $adjetivosIndustriales = ['INDUSTRIAL', 'ELECTRICO', 'ELECTRICA', 'VINILICA', 'VINILICO', 'CENTRIFUGA', 'CENTRIFUGO', 'ADHESIVA', 'ADHESIVO', 'CORRUGADA', 'CORRUGADO', 'EPOXICA', 'EPOXICÓ', 'ORGANICO', 'ORGANICA', 'ESTRUCTURAL', 'HIDRAULICA', 'HIDRAULICO', 'NEUMATICA', 'NEUMATICO', 'TERMICO', 'TERMICA', 'INOXIDABLE', 'GALVANIZADO', 'GALVANIZADA'];
            $palabrasTipoArr = explode(' ', strtoupper(trim($nombreTipoRaw)));
            if (count($palabrasTipoArr) >= 2) {
                $primeraPalabra = $palabrasTipoArr[0];
                $segundaPalabra = $palabrasTipoArr[1];
                // Si la primera palabra es un adjetivo y la segunda es un sustantivo, esta volteado
                if (in_array($primeraPalabra, $adjetivosIndustriales) && !in_array($segundaPalabra, $adjetivosIndustriales)) {
                    $sugerenciaOrden = $segundaPalabra.' '.$primeraPalabra;
                    if (count($palabrasTipoArr) > 2) {
                        $sugerenciaOrden .= ' '.implode(' ', array_slice($palabrasTipoArr, 2));
                    }
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => 'NOMBRE_TIPO',
                        'error' => "'{$nombreTipoRaw}' tiene las palabras en orden incorrecto. El sustantivo va primero. -> Pon: {$sugerenciaOrden}",
                    ];
                }
            }
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

        // === 2.5 TIPO_PRODUCTO - Debe ser exactamente MPI, ME o MN en MAYUSCULAS ===
        if ($tipoProductoRaw && $tipoProductoRaw !== strtoupper($tipoProductoRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'TIPO_PRODUCTO',
                'error' => "Debe estar en MAYUSCULAS. Recibido: '{$tipoProductoRaw}'. COMO CORREGIR: Selecciona del dropdown: MPI, ME o MN",
            ];
        }
        if ($tipoProductoRaw && ! in_array(strtoupper($tipoProductoRaw), ['MPI', 'ME', 'MN', 'MP', 'PT', 'RP', 'CONTABLE', 'GASTOS', 'REFACCIONES', 'HERRAMIENTAS', 'MAQUINARIA', 'MUESTRAS', 'INSUMOS', 'EQUIPO', 'SEGURIDAD', 'VEHICULOS', 'MOLDES', 'SERVICIOS'])) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'TIPO_PRODUCTO',
                'error' => "Valor no valido: '{$tipoProductoRaw}'. COMO CORREGIR: Solo MPI (Materia Prima Importacion), ME (Material Empaque) o MN (Mantenimiento)",
            ];
        }

        // === 2.7 UNIDAD_MEDIDA - Debe estar en MAYUSCULAS y ser valida ===
        if ($unidadRaw && $unidadRaw !== strtoupper($unidadRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Debe estar en MAYUSCULAS. Recibido: '{$unidadRaw}'. COMO CORREGIR: Selecciona del dropdown: KG, PZA o CAJA",
            ];
        }

        // === 3. FAMILIA - Debe ser de la lista oficial ===
        if ($familia && ! in_array($familia, $this->familiasValidas)) {
            $sugerencia = $this->buscarFamiliaSimilar($familia);
            $errores[] = [
                'fila' => $fila,
                'campo' => 'FAMILIA',
                'error' => "Familia '{$familia}' no esta en el catalogo oficial.".
                    ($sugerencia ? " ?Quisiste decir '{$sugerencia}'?" : ' Familias validas: '.implode(', ', array_slice($this->familiasValidas, 0, 10)).'...'),
            ];
        }

        // === 4. UNIDAD DE MEDIDA - Lista oficial ===
        if ($unidad && ! in_array($unidad, $this->unidadesValidas)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Unidad '{$unidad}' no valida. COMO CORREGIR: Solo se acepta KG, PZA o CAJA (selecciona del dropdown)",
            ];
        }

        // === 5. PRECIO - Numerico y razonable (opcional, pero si viene debe ser valido) ===
        if ($precio !== '') {
            $precioStr = trim((string) $precio);
            // Si esta vacio despues de trim, no validar
            if ($precioStr === '' || $precioStr === '0') {
                // Precio vacio o 0 - no es error, es opcional
            } elseif (str_starts_with($precioStr, '$')) {
                // Tiene $ - validar que el resto sea numerico
                $precioLimpio = str_replace([',', '$', ' '], '', $precioStr);
                if (! is_numeric($precioLimpio)) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => 'PRECIO',
                        'error' => "El precio no es valido. Debe ser un numero con \$ (ej: \$150.50). Recibido: '{$precioStr}'",
                    ];
                } else {
                    $precioNum = (float) $precioLimpio;
                    if ($precioNum <= 0) {
                        $errores[] = [
                            'fila' => $fila,
                            'campo' => 'PRECIO',
                            'error' => "El precio debe ser mayor a \$0. Recibido: '{$precioStr}'",
                        ];
                    } elseif ($precioNum > 5000000) {
                        $errores[] = [
                            'fila' => $fila,
                            'campo' => 'PRECIO',
                            'error' => "Precio extremadamente alto ({$precioStr}). Verifica que sea correcto.",
                        ];
                    }
                }
            } elseif (is_numeric(str_replace([',', ' '], '', $precioStr))) {
                // Es un numero sin $ - marcar error
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PRECIO',
                    'error' => "El precio debe llevar el signo \$ al inicio (ej: \$150.50, \$2,800.00). Recibido: '{$precioStr}'. -> Pon: \${$precioStr}",
                ];
            } else {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PRECIO',
                    'error' => "Precio invalido. Debe llevar \$ al inicio (ej: \$150.50, \$2,800.00). Recibido: '{$precioStr}'",
                ];
            }
        }

        // === 6. DUPLICADOS INTELIGENTES ===
        if ($nombre) {
            // Buscar duplicado exacto (sin espacios)
            $nombreNorm = strtoupper(str_replace(' ', '', Str::ascii($nombre)));
            $existe = Producto::whereRaw("UPPER(REPLACE(nombre, ' ', '')) = ?", [$nombreNorm])->exists();
            if ($existe) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => 'DUPLICADO: Este producto ya existe en el catalogo',
                ];
            } else {
                // Buscar duplicado con palabras en diferente orden
                // "INDUSTRIAL SOLVENTE" = "SOLVENTE INDUSTRIAL"
                $palabrasNombre = explode(' ', strtoupper(Str::ascii($nombre)));
                sort($palabrasNombre);
                $nombreOrdenado = implode(' ', $palabrasNombre);

                $productosExistentes = Producto::select('nombre', 'codigo')->get();
                $duplicadoOrden = null;
                foreach ($productosExistentes as $prodExistente) {
                    $palabrasExistente = explode(' ', strtoupper(Str::ascii($prodExistente->nombre)));
                    sort($palabrasExistente);
                    $existenteOrdenado = implode(' ', $palabrasExistente);
                    if ($nombreOrdenado === $existenteOrdenado) {
                        $duplicadoOrden = $prodExistente;
                        break;
                    }
                }

                if ($duplicadoOrden) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => 'NOMBRE',
                        'error' => "DUPLICADO: Ya existe '{$duplicadoOrden->nombre}' ({$duplicadoOrden->codigo}) con las mismas palabras en diferente orden.",
                    ];
                } else {
                    // Solo bloquea duplicados exactos o con palabras en diferente orden
                    // No bloquea "posibles" duplicados por similitud parcial
                }
            }
        }

        // === 7. CAMPOS OPCIONALES - Validar formato si vienen ===
        $marca = trim($producto['MARCA'] ?? '');
        if ($marca && $marca !== strtoupper(Str::ascii($marca))) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'MARCA',
                'error' => 'La marca debe estar en MAYUSCULAS sin acentos',
            ];
        }

        // Validar VOLTAJE - si viene, debe ser del dropdown o un valor electrico valido
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

        // Validar MODELO - no debe tener caracteres especiales raros ni ser texto basura
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

        // Validar COLOR - si viene, no debe ser un numero ni texto sin sentido
        $color = trim($producto['COLOR'] ?? '');
        if ($color && preg_match('/^\d+$/', $color)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'COLOR',
                'error' => "Color invalido: '{$color}'. Debe ser un nombre de color (ej: ROJO, BLANCO, TRANSPARENTE).",
            ];
        }

        // Validar MEDIDA - si viene, debe tener numeros o unidades
        $medida = trim($producto['MEDIDA'] ?? '');
        if ($medida && ! preg_match('/\d|MM|CM|MT|ML|LT|KG|GR|GAL|IN|PZA|HP/i', $medida)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'MEDIDA',
                'error' => "Medida invalida: '{$medida}'. Debe incluir un valor numerico o unidad (ej: 500ML, 3HP, 40X30X25, 10X5CM).",
            ];
        }

        // === 8. LOTE y PEDIMENTO - Obligatorios solo si TIPO_PRODUCTO = MPI ===
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

        // === 9. OBSERVACIONES - Debe ser texto legible, profesional, no basura ===
        $observaciones = trim($producto['OBSERVACIONES'] ?? '');
        if ($observaciones) {
            // Rechazar caracteres especiales raros
            if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`"\'?@^]/', $observaciones)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => 'Contiene caracteres especiales no permitidos. COMO CORREGIR: Escribe texto normal sin simbolos raros. Ejemplo: PROVEEDOR NACIONAL ENTREGA 5 DIAS',
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
                    'error' => 'Texto ilegible o sin sentido detectado. COMO CORREGIR: Escribe observaciones claras y profesionales. Ejemplo: IMPORTACION CHINA PEDIMENTO 26 0001 3000001',
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
                        'error' => 'Contenido inapropiado detectado. COMO CORREGIR: Las observaciones deben ser profesionales y relevantes al producto. Ejemplo: PARA LINEA 3 PRODUCCION',
                    ];
                    break;
                }
            }

            // Debe tener al menos 2 palabras legibles
            if (count($palabrasObs) < 2) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => 'Observaciones muy cortas. COMO CORREGIR: Escribe al menos 2 palabras descriptivas. Ejemplo: PROVEEDOR NACIONAL',
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

    // ════════════════════════════════════════════════════════════════════════════
    // ═══ MIGRACIÓN MASIVA ═══
    // Métodos para la migración de ~3,000 productos del sistema viejo al nuevo.
    // La IA procesa cada producto en lotes de 50 en background.
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Mostrar la página de migración masiva con historial y progreso.
     */
    public function mostrarMigracionMasiva()
    {
        // Migración activa (pendiente o procesando)
        $migracionActiva = MigracionMasiva::whereIn('estatus', ['pendiente', 'procesando'])
            ->latest()
            ->first();

        // Historial de migraciones
        $migraciones = MigracionMasiva::latest()->limit(20)->get();

        return view('admin.migracion-masiva', compact('migracionActiva', 'migraciones'));
    }

    /**
     * Subir Excel del sistema viejo (SAP) e iniciar migración masiva en background.
     * Acepta columnas del sistema viejo: ItemCode, ItemName, RefCodigoGrupoArticulos, etc.
     * Divide los productos en lotes de 50 y despacha jobs a la queue 'migraciones'.
     */
    public function subirMigracion(Request $request)
    {
        // El Excel puede ser muy grande (23,000+ filas) - dar tiempo suficiente para leerlo
        set_time_limit(300);

        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        // Verificar que no haya una migración en curso
        $enCurso = MigracionMasiva::whereIn('estatus', ['pendiente', 'procesando'])->exists();
        if ($enCurso) {
            return back()->with('error', 'Ya hay una migración en curso. Espera a que termine antes de iniciar otra.');
        }

        $file = $request->file('excel');
        $path = $file->store('migraciones-masivas', 'public');

        // Leer el Excel
        try {
            $fullPath = storage_path('app/public/' . $path);

            if (Str::endsWith($file->getClientOriginalName(), '.csv')) {
                $productos = $this->leerCSV($fullPath);
            } else {
                $spreadsheet = IOFactory::load($fullPath);
                $productos = $this->leerSpreadsheet($spreadsheet);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo leer el archivo. Asegúrate de que sea un Excel (.xlsx) o CSV válido. Error: ' . $e->getMessage());
        }

        if (empty($productos)) {
            return back()->with('error', 'El archivo está vacío o no tiene productos.');
        }

        // Detectar formato del Excel y extraer datos relevantes
        $productosParaMigrar = $this->extraerProductosMigracion($productos);

        if (empty($productosParaMigrar)) {
            return back()->with('error', 'No se encontraron columnas reconocibles. El Excel debe tener al menos una columna de código (ItemCode/CODIGO) y una de nombre (ItemName/NOMBRE). Descarga el template de migración y pega tus datos ahí.');
        }

        // Determinar modo: completo (todos los productos del Excel)
        $totalProductos = count($productosParaMigrar);

        if ($totalProductos > 500) {
            return back()->with('error', 'El Excel tiene ' . $totalProductos . ' productos. El máximo por archivo es 500. Copia solo 50-500 filas del Excel bruto a un Excel en blanco y sube ese.');
        }

        // Obtener admin_id de la sesión
        $adminId = session('admin_id', 1);

        // Dividir en lotes de 20 (menos productos por lote = mejor respuesta de la IA)
        $lotes = array_chunk($productosParaMigrar, 20);
        $totalLotes = count($lotes);

        // Crear registro de migración
        $migracion = MigracionMasiva::create([
            'admin_id' => $adminId,
            'archivo_path' => $path,
            'total_productos' => count($productosParaMigrar),
            'productos_procesados' => 0,
            'productos_error' => 0,
            'lotes_total' => $totalLotes,
            'lotes_completados' => 0,
            'estatus' => 'pendiente',
        ]);

        // Crear archivo JSON vacío para acumular resultados de cada lote
        $resultadoJsonPath = 'migraciones-masivas/resultado_' . $migracion->id . '.json';
        $resultadoFullPath = storage_path('app/public/' . $resultadoJsonPath);
        file_put_contents($resultadoFullPath, json_encode([], JSON_UNESCAPED_UNICODE));

        // Despachar un job por cada lote
        foreach ($lotes as $index => $lote) {
            ProcesarMigracionLote::dispatch(
                $migracion->id,
                $index + 1,
                $lote
            );
        }

        Log::info("[MigracionMasiva] Iniciada migración #{$migracion->id}: " . count($productosParaMigrar) . " productos en {$totalLotes} lotes");

        return back()->with('mensaje', "Migración iniciada: " . count($productosParaMigrar) . " productos se procesarán en {$totalLotes} lotes. El progreso se actualizará automáticamente.");
    }

    /**
     * Detectar formato del Excel (SAP o genérico) y extraer código + nombre + grupo de cada producto.
     */
    private function extraerProductosMigracion(array $productos): array
    {
        if (empty($productos)) {
            return [];
        }

        $primera = $productos[0];
        $headers = array_keys($primera);

        // Detectar columna de CÓDIGO
        $colCodigo = null;
        foreach (['ITEMCODE', 'CODIGO', 'SKU', 'COD', 'CLAVE', 'CODE'] as $posible) {
            if (in_array($posible, $headers)) {
                $colCodigo = $posible;
                break;
            }
        }

        // Detectar columna de NOMBRE
        $colNombre = null;
        foreach (['ITEMNAME', 'NOMBRE', 'DESCRIPCION', 'NAME', 'PRODUCTO', 'ARTICULO'] as $posible) {
            if (in_array($posible, $headers)) {
                $colNombre = $posible;
                break;
            }
        }

        // Detectar columna de GRUPO/FAMILIA
        $colGrupo = null;
        foreach (['REFCODIGOGRUPOARTICULOS', 'ITEMSGROUPCODE', 'GRUPO', 'FAMILIA', 'CATEGORIA', 'GROUP'] as $posible) {
            if (in_array($posible, $headers)) {
                $colGrupo = $posible;
                break;
            }
        }

        // Detectar columna de CLAVE SAT
        $colClaveSat = null;
        foreach (['REFE_CODIGO_ARTICULOS_SAT', 'NCMCODE', 'CLAVE_SAT', 'SAT', 'CLAVESAT'] as $posible) {
            if (in_array($posible, $headers)) {
                $colClaveSat = $posible;
                break;
            }
        }

        // Detectar columna de LOTE (ManageBatchNumbers)
        $colLote = null;
        foreach (['MANAGEBATCHNUMBERS', 'LOTE', 'MANEJA_LOTES'] as $posible) {
            if (in_array($posible, $headers)) {
                $colLote = $posible;
                break;
            }
        }

        // Detectar columna de UNIDAD (PurchaseUnit o SalesUnit)
        $colUnidad = null;
        foreach (['PURCHASEUNIT', 'SALESUNIT', 'UNIDAD', 'UNIDAD_MEDIDA', 'UOM'] as $posible) {
            if (in_array($posible, $headers)) {
                $colUnidad = $posible;
                break;
            }
        }

        if (!$colCodigo || !$colNombre) {
            return [];
        }

        $resultado = [];
        foreach ($productos as $prod) {
            $codigo = trim($prod[$colCodigo] ?? '');
            $nombre = trim($prod[$colNombre] ?? '');

            if (empty($codigo) && empty($nombre)) {
                continue;
            }

            // Convertir LOTE: tYES -> SI, tNO -> NO
            $loteRaw = strtoupper(trim($prod[$colLote] ?? ''));
            $lote = '';
            if ($loteRaw === 'TYES' || $loteRaw === 'SI') {
                $lote = 'SI';
            } elseif ($loteRaw === 'TNO' || $loteRaw === 'NO') {
                $lote = 'NO';
            }

            // Convertir UNIDAD: XBX -> CAJA, etc.
            $unidadRaw = strtoupper(trim($prod[$colUnidad] ?? ''));
            $unidad = 'CAJA'; // default
            if ($unidadRaw === 'XBX' || $unidadRaw === 'CJ' || $unidadRaw === 'CAJA') {
                $unidad = 'CAJA';
            } elseif ($unidadRaw === 'KG' || $unidadRaw === 'KGM') {
                $unidad = 'KG';
            } elseif ($unidadRaw === 'PZA' || $unidadRaw === 'EA' || $unidadRaw === 'PZ' || $unidadRaw === 'UNI') {
                $unidad = 'PZA';
            }

            $resultado[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'grupo' => trim($prod[$colGrupo] ?? ''),
                'clave_sat' => trim($prod[$colClaveSat] ?? ''),
                'lote' => $lote,
                'unidad_medida' => $unidad,
            ];
        }

        return $resultado;
    }

    /**
     * Devolver estado actual de una migración (para AJAX polling).
     */
    public function estadoMigracion($id)
    {
        $migracion = MigracionMasiva::findOrFail($id);

        return response()->json([
            'id' => $migracion->id,
            'estatus' => $migracion->estatus,
            'total_productos' => $migracion->total_productos,
            'productos_procesados' => $migracion->productos_procesados,
            'productos_error' => $migracion->productos_error,
            'lotes_total' => $migracion->lotes_total,
            'lotes_completados' => $migracion->lotes_completados,
            'porcentaje' => $migracion->porcentaje,
            'resultado_path' => $migracion->resultado_path,
            'descarga_url' => $migracion->resultado_path
                ? route('admin.migracion.descargar', $migracion->id)
                : null,
        ]);
    }

    /**
     * Descargar el Excel de resultado de una migración completada.
     */
    public function descargarResultado($id)
    {
        $migracion = MigracionMasiva::findOrFail($id);

        if (!$migracion->resultado_path) {
            return back()->with('error', 'Esta migración aún no tiene un archivo de resultado.');
        }

        $fullPath = storage_path('app/public/' . $migracion->resultado_path);

        if (!file_exists($fullPath)) {
            return back()->with('error', 'El archivo de resultado no se encontró en el servidor.');
        }

        return response()->download($fullPath, 'Migracion_Resultado_' . $migracion->id . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Descargar template de migración con headers del sistema viejo.
     * Máximo 500 filas para control de la IA.
     */
    public function descargarTemplateMigracion()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        // Headers completos del Excel bruto de SAP (para que solo copien filas sin filtrar columnas)
        $headers = ['ItemCode', 'ItemName', 'ItemsGroupCode', 'RefCodigoGrupoArticulos', 'NCMCode', 'Refe_Codigo_Articulos_SAT', 'ManageBatchNumbers', 'ManageSerialNumbers', 'PurchaseItem', 'SalesItem', 'InventoryItem', 'BarCode', 'IndirectTax', 'WTLiable', 'VatLiable', 'Mainsupplier', 'PurchaseUnit'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6B3FA0');
            $sheet->getStyle($col . '1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Hoja de instrucciones
        $instrSheet = $spreadsheet->createSheet();
        $instrSheet->setTitle('Instrucciones');
        $instrSheet->getColumnDimension('A')->setWidth(80);

        $instrSheet->setCellValue('A1', 'INSTRUCCIONES - MIGRACION MASIVA');
        $instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instrSheet->getStyle('A1')->getFont()->getColor()->setRGB('6B3FA0');

        $row = 3;
        $instrucciones = [
            '=== COMO USAR ESTE TEMPLATE ===',
            '1. Abre el Excel del sistema viejo (SAP) con los productos',
            '2. Selecciona de 50 a 500 filas de datos (sin contar el header)',
            '3. Copia las filas completas (todas las columnas) y pegalas aqui desde la fila 2',
            '4. Si el Excel bruto tiene mas columnas que este template, no importa: el sistema solo lee las que necesita',
            '5. Guarda y sube este archivo en la pagina de Migracion Masiva',
            '6. Espera a que la IA procese y descarga el resultado',
            '',
            '=== QUE LEE EL SISTEMA ===',
            'Solo usa estas columnas (las demas las ignora):',
            '- ItemCode = Codigo del producto',
            '- ItemName = Nombre completo (la IA lo separa en tipo, marca, modelo, medida, especificacion)',
            '- RefCodigoGrupoArticulos = Grupo/categoria (se usa para NOMBRE_MODELO)',
            '- Refe_Codigo_Articulos_SAT o NCMCode = Clave SAT',
            '- ManageBatchNumbers = Lote (tYES=SI, tNO=NO)',
            '- PurchaseUnit = Unidad (XBX=CAJA)',
            '',
            '=== IMPORTANTE ===',
            'NO borres los headers de la fila 1.',
            'NO dejes filas vacias entre los datos.',
            'Puedes pegar filas con TODAS las columnas del Excel bruto - el sistema ignora las que no necesita.',
            'Maximo recomendado: 500 filas por archivo para no saturar la IA.',
        ];

        foreach ($instrucciones as $texto) {
            $instrSheet->setCellValue('A' . $row, $texto);
            if (str_starts_with($texto, '===')) {
                $instrSheet->getStyle('A' . $row)->getFont()->setBold(true);
            }
            $row++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'migracion_template_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'Template_Migracion_Masiva.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Inferir el TIPO_PRODUCTO esperado según el prefijo del código.
     * Retorna null si no se puede determinar (no marca error).
     */
    private function inferirTipoPorCodigo(string $codigo): ?string
    {
        $codigo = strtoupper($codigo);

        // MPI y variantes
        if (str_starts_with($codigo, 'MPI') || str_starts_with($codigo, 'FMPI') || str_starts_with($codigo, 'EMPI') || str_starts_with($codigo, 'NMPI') || str_starts_with($codigo, 'MPIDA') || str_starts_with($codigo, 'MPIVA')) {
            return 'MPI';
        }

        // ME (Material Empaque) — antes de MP para que no lo agarre MP
        if (str_starts_with($codigo, 'ME')) {
            return 'ME';
        }

        // MP (Materia Prima) — solo si después de MP viene un número
        if (preg_match('/^MP\d/', $codigo)) {
            return 'MP';
        }

        // MUE (Muestras) — antes de PT para que no lo agarre el regex de E/M/N
        if (str_starts_with($codigo, 'MUE')) {
            return 'MUESTRAS';
        }

        // MS (Servicios)
        if (str_starts_with($codigo, 'MS')) {
            return 'SERVICIOS';
        }

        // PT (Producto Terminado) — códigos que empiezan con E/M/N + letras
        if (preg_match('/^[EMN][A-Z]{2}/', $codigo)) {
            return 'PT';
        }

        // RP
        if (str_starts_with($codigo, 'RP')) {
            return 'RP';
        }

        // Herramientas
        if (str_starts_with($codigo, 'HER') || str_starts_with($codigo, 'HET') || str_starts_with($codigo, 'CM')) {
            return 'HERRAMIENTAS';
        }

        // Refacciones
        if (str_starts_with($codigo, 'BL') || str_starts_with($codigo, 'CN') || str_starts_with($codigo, 'RI') || str_starts_with($codigo, '500') || str_starts_with($codigo, '101')) {
            return 'REFACCIONES';
        }

        // Maquinaria
        if (str_starts_with($codigo, '123') || str_starts_with($codigo, 'MI')) {
            return 'MAQUINARIA';
        }

        // Contable
        if (str_starts_with($codigo, '550')) {
            return 'CONTABLE';
        }

        // Gastos
        if (preg_match('/^6[2-3][0-9]/', $codigo)) {
            return 'GASTOS';
        }

        // Seguridad
        if (str_starts_with($codigo, 'SEGG')) {
            return 'SEGURIDAD';
        }

        // No se puede determinar — no marcar error
        return null;
    }
}
