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

    private array $columnasObligatorias = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'PRODUCCION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'OBSERVACIONES'];

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
        $spreadsheet = new Spreadsheet;

        // ═══ HOJA PRINCIPAL: Productos ═══
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        // Headers — NOMBRE dividido en 5 partes para forzar el orden correcto
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'PRODUCCION', 'FAMILIA', 'TIPO_PRODUCTO', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'MARCA_PRODUCTO', 'MODELO_PRODUCTO', 'MEDIDA_PRODUCTO', 'MATERIAL', 'COLOR', 'VOLTAJE', 'ESPECIFICACIONES', 'OBSERVACIONES'];
        $obligatorios = ['CODIGO' => true, 'NOMBRE_TIPO' => true, 'NOMBRE_MARCA' => true, 'NOMBRE_MODELO' => true, 'NOMBRE_MEDIDA' => true, 'NOMBRE_ESPECIFICACION' => true, 'PRODUCCION' => true, 'FAMILIA' => true, 'TIPO_PRODUCTO' => true, 'SUBFAMILIA' => false, 'UNIDAD_MEDIDA' => true, 'PRECIO' => false, 'CLAVE_SAT' => false, 'LOTE' => false, 'PEDIMENTO' => false, 'MARCA_PRODUCTO' => false, 'MODELO_PRODUCTO' => false, 'MEDIDA_PRODUCTO' => false, 'MATERIAL' => false, 'COLOR' => false, 'VOLTAJE' => false, 'ESPECIFICACIONES' => false, 'OBSERVACIONES' => true];
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

        // Ejemplos (filas 2-6) — CORRECTAMENTE LLENADOS, pasan validación al 100%
        // Columnas: CODIGO, NOMBRE_TIPO, NOMBRE_MARCA, NOMBRE_MODELO, NOMBRE_MEDIDA, NOMBRE_ESPECIFICACION, PRODUCCION, FAMILIA, TIPO_PRODUCTO, SUBFAMILIA, UNIDAD_MEDIDA, PRECIO, CLAVE_SAT, LOTE, PEDIMENTO, MARCA_PRODUCTO, MODELO_PRODUCTO, MEDIDA_PRODUCTO, MATERIAL, COLOR, VOLTAJE, ESPECIFICACIONES, OBSERVACIONES
        $ejemplos = [
            ['MPI0538', 'RESINA EPOXICA', 'SKF', 'IND-500', '500ML', 'TRANSPARENTE INDUSTRIAL', 'MPI', 'MATERIA PRIMA', 'MPI', 'RESINAS', 'KG', '$150.50', '10191509', 'SI', 'SI', 'SKF', 'IND-500', '500ML', 'LIQUIDO', 'TRANSPARENTE', 'N/A', 'USO INDUSTRIAL GRADO ALIMENTICIO', 'IMPORTACION CHINA PEDIMENTO 26 0001 3000001'],
            ['ME0201', 'CAJA CORRUGADA', 'KRAFT', 'CP-40', '40X30X25', 'DOBLE PARED IMPRESA', 'ME', 'MATERIAL EMPAQUE', 'ME', 'CAJAS', 'PZA', '$18.50', '48191500', 'NO', 'NO', 'KRAFT', 'CP-40', '40X30X25', 'CARTON', 'KRAFT', 'N/A', 'IMPRESION 2 TINTAS', 'PROVEEDOR NACIONAL ENTREGA 5 DIAS'],
            ['MN0045', 'MOTOR ELECTRICO', 'WEG', 'W22', '3HP', 'TRIFASICO 220/440V', 'MN', 'MANTENIMIENTO', 'MN', 'MOTORES', 'PZA', '$8,500.00', '26101500', 'NO', 'NO', 'WEG', 'W22', '3HP', 'ACERO', '', '220/440V', 'TRIFASICO CLASE F IP55', 'PARA LINEA 3 PRODUCCION'],
            ['MPI0539', 'PIGMENTO ORGANICO', 'ALPHA', 'ORG-R180', '180G/274ML', 'CONCENTRADO ROJO', 'MPI', 'MATERIA PRIMA', 'MPI', 'PIGMENTOS', 'KG', '$320.00', '12161800', 'SI', 'SI', 'ALPHA', 'ORG-R180', '180G', 'POLVO', 'ROJO', 'N/A', 'ALTA RESISTENCIA UV', 'PEDIMENTO REQUERIDO 26 0001 3000045'],
            ['ME0202', 'ETIQUETA ADHESIVA', '3M', 'T-100', '10X5CM', 'BLANCO MATE TERMICA', 'ME', 'MATERIAL EMPAQUE', 'ME', 'ETIQUETAS', 'CAJA', '$85.00', '55121600', 'NO', 'NO', '3M', 'T-100', '10X5CM', 'PAPEL TERMICO', 'BLANCO', 'N/A', '1000 PZA POR ROLLO', 'ENTREGA SEMANAL LUNES Y JUEVES'],
        ];

        foreach ($ejemplos as $rowIdx => $ejemplo) {
            $col = 'A';
            foreach ($ejemplo as $val) {
                $sheet->setCellValue($col.($rowIdx + 2), $val);
                $sheet->getStyle($col.($rowIdx + 2))->getFont()->getColor()->setRGB('888888');
                $col++;
            }
        }

        // ═══ HOJA OCULTA: Listas de validación ═══
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

        // ═══ VALIDACIONES en hoja principal ═══
        // Columnas: A=CODIGO, B=NOMBRE_TIPO, C=NOMBRE_MARCA, D=NOMBRE_MODELO, E=NOMBRE_MEDIDA,
        //           F=NOMBRE_ESPECIFICACION, G=PRODUCCION, H=FAMILIA, I=TIPO_PRODUCTO, J=SUBFAMILIA,
        //           K=UNIDAD_MEDIDA, L=PRECIO, M=CLAVE_SAT, N=LOTE, O=PEDIMENTO,
        //           P=MARCA_PRODUCTO, Q=MODELO_PRODUCTO, R=MEDIDA_PRODUCTO, S=MATERIAL, T=COLOR,
        //           U=VOLTAJE, V=ESPECIFICACIONES, W=OBSERVACIONES
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // Dropdown FAMILIA (columna H)
        $familiaCount = count($familias);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('H'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Familia no válida');
            $validation->setError('Selecciona una familia del catálogo oficial.');
            $validation->setFormula1('_Listas!$A$1:$A$'.$familiaCount);
        }

        // Dropdown UNIDAD_MEDIDA (columna K)
        $unidadCount = count($unidades);
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('K'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Unidad no válida');
            $validation->setError('Solo: KG, PZA o CAJA');
            $validation->setFormula1('_Listas!$B$1:$B$'.$unidadCount);
        }

        // Dropdown PRODUCCION (columna G)
        $listSheet->setCellValue('C1', 'MPI');
        $listSheet->setCellValue('C2', 'ME');
        $listSheet->setCellValue('C3', 'MN');
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('G'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Producción no válida');
            $validation->setError('Valores válidos: MPI (Materia Prima Importación), ME (Material Empaque), MN (Mantenimiento)');
            $validation->setFormula1('_Listas!$C$1:$C$3');
        }

        // Dropdown TIPO_PRODUCTO (columna I)
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('I'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Tipo de producto no válido');
            $validation->setError('Valores válidos: MPI, ME o MN');
            $validation->setFormula1('_Listas!$C$1:$C$3');
        }

        // Dropdown LOTE (columna N)
        $listSheet->setCellValue('D1', 'SI');
        $listSheet->setCellValue('D2', 'NO');
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('N'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Valor no válido');
            $validation->setError('Solo SI o NO');
            $validation->setFormula1('_Listas!$D$1:$D$2');
        }

        // Dropdown PEDIMENTO (columna O)
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('O'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Valor no válido');
            $validation->setError('Solo SI o NO');
            $validation->setFormula1('_Listas!$D$1:$D$2');
        }

        // Dropdown VOLTAJE (columna U)
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
            $validation = $sheet->getCell('U'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Voltaje no válido');
            $validation->setError('Selecciona un voltaje del listado: 110V, 127V, 220V, 220/440V, 440V, 480V, 12VDC, 24VDC, 3HP, 5HP, 10HP, 60Hz, N/A');
            $validation->setFormula1('_Listas!$E$1:$E$14');
        }

        // Validación PRECIO (columna L) — solo números > 0
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('L'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_DECIMAL);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Precio inválido');
            $validation->setError('El precio debe ser un número mayor a 0.');
            $validation->setOperator(DataValidation::OPERATOR_GREATERTHAN);
            $validation->setFormula1('0');
        }

        // Validación NOMBRE_TIPO (columna B) — máximo 40 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('B'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Tipo muy largo');
            $validation->setError('El tipo no debe exceder 40 caracteres.');
            $validation->setOperator(DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('40');
        }

        // Validación MARCA_PRODUCTO (columna P) — máximo 30 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('P'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Marca muy larga');
            $validation->setError('La marca no debe exceder 30 caracteres.');
            $validation->setOperator(DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('30');
        }

        // Validación MODELO_PRODUCTO (columna Q) — máximo 30 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('Q'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Modelo muy largo');
            $validation->setError('El modelo no debe exceder 30 caracteres.');
            $validation->setOperator(DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('30');
        }

        // Validación ESPECIFICACIONES (columna V) — máximo 100 caracteres
        for ($row = 2; $row <= 100; $row++) {
            $validation = $sheet->getCell('V'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_TEXTLENGTH);
            $validation->setErrorStyle(DataValidation::STYLE_WARNING);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Especificaciones muy largas');
            $validation->setError('Las especificaciones no deben exceder 100 caracteres.');
            $validation->setOperator(DataValidation::OPERATOR_LESSTHANOREQUAL);
            $validation->setFormula1('100');
        }

        // ═══ HOJA DE INSTRUCCIONES ═══
        $instrSheet = $spreadsheet->createSheet();
        $instrSheet->setTitle('Instrucciones');
        $instrSheet->getColumnDimension('A')->setWidth(90);
        $instrSheet->getColumnDimension('B')->setWidth(40);

        // Título
        $instrSheet->setCellValue('A1', 'INSTRUCCIONES PARA LLENAR EL TEMPLATE DE ALTA DE PRODUCTO');
        $instrSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instrSheet->getStyle('A1')->getFont()->getColor()->setRGB('6B3FA0');

        $row = 3;

        // Sección: Colores
        $instrSheet->setCellValue('A'.$row, '═══ SIGNIFICADO DE LOS COLORES EN EL HEADER ═══');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;
        $instrSheet->setCellValue('A'.$row, 'MORADO OSCURO = Campo OBLIGATORIO (debes llenarlo siempre)');
        $instrSheet->getStyle('A'.$row)->getFont()->getColor()->setRGB('6B3FA0');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        $instrSheet->setCellValue('A'.$row, 'MORADO CLARO = Campo OPCIONAL (puedes dejarlo vacío)');
        $instrSheet->getStyle('A'.$row)->getFont()->getColor()->setRGB('9B7BC7');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row += 2;

        // Sección: Campos obligatorios
        $instrSheet->setCellValue('A'.$row, '═══ CAMPOS OBLIGATORIOS (siempre llenar) ═══');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;
        $obligatoriosTexto = [
            'CODIGO — Código único del producto (ej: MPI0538, ME0201)',
            'NOMBRE — En MAYÚSCULAS, formato: [TIPO] + [MARCA] + [MODELO] + [MEDIDA] + [ESPECIFICACIÓN]',
            'PRODUCCION — Seleccionar del dropdown: MPI, ME o MN',
            'FAMILIA — Seleccionar del dropdown (catálogo oficial)',
            'TIPO_PRODUCTO — Seleccionar del dropdown: MPI, ME o MN (igual que PRODUCCION)',
            'UNIDAD_MEDIDA — Seleccionar del dropdown: KG, PZA o CAJA',
            'OBSERVACIONES — Notas importantes del producto (siempre obligatorio)',
        ];
        foreach ($obligatoriosTexto as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
            $row++;
        }
        $row++;

        // Sección: Qué es MPI, ME, MN
        $instrSheet->setCellValue('A'.$row, '═══ ¿QUÉ SIGNIFICAN MPI, ME Y MN? ═══');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;
        $tipos = [
            'MPI = Materia Prima Importación — Productos importados que requieren LOTE y PEDIMENTO',
            'ME  = Material de Empaque — Cajas, etiquetas, bolsas, material de empaque',
            'MN  = Mantenimiento — Motores, refacciones, herramientas, equipo industrial',
        ];
        foreach ($tipos as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
            $instrSheet->getStyle('A'.$row)->getFont()->setBold(true);
            $row++;
        }
        $row++;

        // Sección: Campos condicionales
        $instrSheet->setCellValue('A'.$row, '═══ CAMPOS CONDICIONALES (solo si es MPI) ═══');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;
        $instrSheet->setCellValue('A'.$row, 'LOTE — Obligatorio SOLO si PRODUCCION = MPI. Seleccionar SI o NO del dropdown.');
        $row++;
        $instrSheet->setCellValue('A'.$row, 'PEDIMENTO — Obligatorio SOLO si PRODUCCION = MPI. Seleccionar SI o NO del dropdown.');
        $row++;
        $instrSheet->setCellValue('A'.$row, 'Si tu producto NO es MPI, puedes dejar LOTE y PEDIMENTO vacíos.');
        $instrSheet->getStyle('A'.$row)->getFont()->getColor()->setRGB('888888');
        $row += 2;

        // Sección: Campos opcionales
        $instrSheet->setCellValue('A'.$row, '═══ CAMPOS OPCIONALES ═══');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;
        $opcionales = [
            'PRECIO — Número con punto decimal (ej: 150.50). Déjalo vacío si no lo tienes.',
            'CLAVE_SAT — Código SAT para facturación (ej: 10191509)',
            'SUBFAMILIA — Subcategoría del producto (ej: RESINAS, MOTORES)',
            'MARCA — Marca del producto en MAYÚSCULAS (ej: WEG, SKF, 3M)',
            'MODELO — Modelo o referencia (ej: W22, IND-500)',
            'MEDIDA — Dimensión o capacidad (ej: 500ML, 3HP, 40X30X25)',
            'MATERIAL — De qué está hecho (ej: ACERO, LIQUIDO, CARTON)',
            'COLOR — Color del producto (ej: ROJO, BLANCO, TRANSPARENTE)',
            'VOLTAJE — Solo valores eléctricos reales (ej: 220V, 110/220V, 440V, 3HP)',
            'ESPECIFICACIONES — Detalles técnicos adicionales',
        ];
        foreach ($opcionales as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
            $row++;
        }
        $row++;

        // Sección: Formato del NOMBRE
        $instrSheet->setCellValue('A'.$row, '═══ CÓMO ESCRIBIR EL NOMBRE CORRECTAMENTE ═══');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;
        $nombreReglas = [
            'Formato OBLIGATORIO: [TIPO] + [MARCA] + [MODELO] + [MEDIDA] + [ESPECIFICACIÓN]',
            'Mínimo 4 palabras, todo en MAYÚSCULAS, sin acentos',
            'Debe contener al menos un dato técnico (número, medida, modelo)',
            '',
            'EJEMPLOS CORRECTOS:',
            '  RESINA EPOXICA SKF INDUSTRIAL 500ML TRANSPARENTE',
            '  MOTOR ELECTRICO WEG W22 3HP 220/440V TRIFASICO',
            '  CAJA CORRUGADA KRAFT 40X30X25 DOBLE PARED',
            '  PIGMENTO ORGANICO ALPHA ROJO 180G CONCENTRADO',
            '',
            'EJEMPLOS INCORRECTOS:',
            '  resina epoxica (minúsculas — debe ser MAYÚSCULAS)',
            '  MOTOR (solo 1 palabra — mínimo 4)',
            '  Résina epóxica (acentos — no se permiten)',
            '  PRODUCTO GENERICO (muy vago — necesita marca/modelo/medida)',
        ];
        foreach ($nombreReglas as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
            $row++;
        }
        $row++;

        // Sección: Cómo corregir errores
        $instrSheet->setCellValue('A'.$row, '═══ SI LA IA RECHAZA TU ARCHIVO ═══');
        $instrSheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $instrSheet->getStyle('A'.$row)->getFont()->getColor()->setRGB('CC0000');
        $row++;
        $correccion = [
            '1. Descarga el Excel con correcciones que te genera el sistema',
            '2. Las celdas en ROJO son las que tienen error',
            '3. La columna ERRORES_IA te dice exactamente qué corregir',
            '4. Corrige los campos marcados siguiendo las instrucciones',
            '5. Vuelve a subir el archivo corregido',
            '',
            'ERRORES COMUNES Y CÓMO CORREGIRLOS:',
            '  "Campo obligatorio vacío" → Llena el campo, no puede estar vacío',
            '  "Debe estar en MAYÚSCULAS" → Escribe todo en MAYÚSCULAS sin acentos',
            '  "Nomenclatura incompleta" → Agrega más datos: [TIPO] [MARCA] [MODELO] [MEDIDA]',
            '  "Familia no está en catálogo" → Usa el dropdown, no escribas a mano',
            '  "Unidad no válida" → Solo KG, PZA o CAJA (usa el dropdown)',
            '  "Voltaje inválido" → Debe ser número+unidad: 220V, 110/220V, 3HP',
            '  "LOTE obligatorio para MPI" → Si es MPI, selecciona SI o NO en LOTE',
            '  "PEDIMENTO obligatorio para MPI" → Si es MPI, selecciona SI o NO en PEDIMENTO',
            '  "DUPLICADO" → Ese producto ya existe, usa un nombre diferente',
        ];
        foreach ($correccion as $texto) {
            $instrSheet->setCellValue('A'.$row, $texto);
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
            return back()->with('error', '❌ No se pudo leer el archivo. Asegúrate de que sea un Excel (.xlsx) o CSV válido. Error: '.$e->getMessage());
        }

        if (empty($productos)) {
            return back()->with('error', 'El archivo está vacío o no tiene productos. Descarga el template, borra los ejemplos en gris y llena tus productos. Columnas obligatorias: CODIGO, NOMBRE_TIPO, NOMBRE_MARCA, NOMBRE_MODELO, NOMBRE_MEDIDA, NOMBRE_ESPECIFICACION, PRODUCCION, FAMILIA, TIPO_PRODUCTO, UNIDAD_MEDIDA, OBSERVACIONES.');
        }

        // Verificar que el archivo tenga las columnas correctas
        $primeraFila = $productos[0] ?? [];
        $columnasPresentes = array_keys($primeraFila);
        $columnasFaltantes = array_diff($this->columnasObligatorias, $columnasPresentes);
        if (! empty($columnasFaltantes)) {
            return back()->with('error', 'El archivo no tiene las columnas correctas. Faltan: '.implode(', ', $columnasFaltantes).'. Descarga el template oficial y úsalo como base.');
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
        // Después de las reglas básicas, Claude revisa contexto y coherencia del NOMBRE
        try {
            $iaService = new IaService;
            $productosParaIA = array_slice($productos, 0, 20);

            // Preparar productos con su fila real del Excel para que la IA no se confunda
            $productosConFila = [];
            foreach ($productosParaIA as $idx => $prod) {
                $filaReal = $idx + 2; // +2 porque fila 1 es header
                $nombreCompleto = trim(($prod['NOMBRE_TIPO'] ?? '').' '.($prod['NOMBRE_MARCA'] ?? '').' '.($prod['NOMBRE_MODELO'] ?? '').' '.($prod['NOMBRE_MEDIDA'] ?? '').' '.($prod['NOMBRE_ESPECIFICACION'] ?? ''));
                $productosConFila[] = [
                    'fila_excel' => $filaReal,
                    'nombre_completo' => $nombreCompleto,
                    'nombre_tipo' => $prod['NOMBRE_TIPO'] ?? '',
                    'nombre_marca' => $prod['NOMBRE_MARCA'] ?? '',
                    'nombre_modelo' => $prod['NOMBRE_MODELO'] ?? '',
                    'nombre_medida' => $prod['NOMBRE_MEDIDA'] ?? '',
                    'nombre_especificacion' => $prod['NOMBRE_ESPECIFICACION'] ?? '',
                ];
            }

            $prompt = 'Eres el validador de productos de Industrias Salcom.

El NOMBRE del producto está dividido en 5 campos separados (el orden ya está garantizado por la estructura):
- NOMBRE_TIPO: Qué es el producto
- NOMBRE_MARCA: Quién lo fabrica  
- NOMBRE_MODELO: Referencia del fabricante
- NOMBRE_MEDIDA: Tamaño/capacidad
- NOMBRE_ESPECIFICACION: Características adicionales

SOLO marca error si:
1. Un campo tiene contenido que es CLARAMENTE de otro campo (ej: "500ML" en NOMBRE_MARCA es obviamente una medida, no una marca)
2. El contenido es texto completamente sin sentido o ilegible
3. Hay una incoherencia OBVIA (ej: NOMBRE_TIPO dice "AGUA" pero la familia es "ELECTRICO")

NO marques error por:
- Orden (el orden ya está forzado por las columnas)
- Contenido que PODRÍA ser de otro campo pero no estás seguro
- Marcas que no conoces (pueden ser marcas reales)
- Modelos con formato inusual

Sé MUY CONSERVADOR. Si tienes duda, NO marques error.

Productos:
'.json_encode($productosConFila, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'

Responde SOLO JSON:
{"errores_ia": [{"fila": <fila_excel>, "campo": "NOMBRE_MARCA", "error": "explicación"}]}

Si todo está bien o tienes duda: {"errores_ia": []}';

            $resultado = $iaService->llamarClaude($prompt);

            if ($resultado['success'] && $resultado['content']) {
                $contenido = $resultado['content'];
                $contenido = preg_replace('/```json\s*/', '', $contenido);
                $contenido = preg_replace('/```\s*/', '', $contenido);
                $contenido = trim($contenido);

                $iaResult = json_decode($contenido, true);
                if ($iaResult && ! empty($iaResult['errores_ia'])) {
                    foreach ($iaResult['errores_ia'] as $errIA) {
                        $filaIA = (int) ($errIA['fila'] ?? 0);
                        $errorTexto = $errIA['error'] ?? '';
                        $campoIA = $errIA['campo'] ?? 'NOMBRE';

                        // La IA a veces se equivoca de fila. Intentar encontrar la fila real
                        // buscando el contenido mencionado en el error dentro de los productos
                        $filaReal = $filaIA; // Por defecto confiar en la IA

                        // Si la fila está fuera de rango, saltar
                        if ($filaReal < 2 || $filaReal > count($productos) + 1) {
                            continue;
                        }

                        // Verificar que el contenido del error coincida con el producto de esa fila
                        $idxProducto = $filaReal - 2;
                        if (isset($productos[$idxProducto])) {
                            // Buscar si alguna palabra clave del error está en el producto
                            $productoTexto = implode(' ', array_values($productos[$idxProducto]));
                            $mencionaContenido = false;

                            // Extraer palabras entre comillas del error para verificar
                            preg_match_all("/['\"]([^'\"]+)['\"]/", $errorTexto, $matches);
                            foreach ($matches[1] ?? [] as $palabra) {
                                if (strlen($palabra) >= 3 && stripos($productoTexto, $palabra) !== false) {
                                    $mencionaContenido = true;
                                    break;
                                }
                            }

                            // Si el error menciona contenido que NO está en la fila indicada,
                            // buscar en qué fila realmente está
                            if (!$mencionaContenido && !empty($matches[1])) {
                                foreach ($productos as $buscarIdx => $buscarProd) {
                                    $buscarTexto = implode(' ', array_values($buscarProd));
                                    foreach ($matches[1] as $palabra) {
                                        if (strlen($palabra) >= 3 && stripos($buscarTexto, $palabra) !== false) {
                                            $filaReal = $buscarIdx + 2;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }

                        // Determinar el campo correcto para colorear en el Excel
                        // La IA puede devolver "NOMBRE" genérico pero mencionar un campo específico en el error
                        $campoFinal = $campoIA;
                        if ($campoFinal === 'NOMBRE' || $campoFinal === 'GENERAL') {
                            // Buscar si el error menciona un campo específico
                            if (stripos($errorTexto, 'NOMBRE_MARCA') !== false || stripos($errorTexto, 'MARCA') !== false) {
                                $campoFinal = 'NOMBRE_MARCA';
                            } elseif (stripos($errorTexto, 'NOMBRE_MODELO') !== false || stripos($errorTexto, 'MODELO') !== false) {
                                $campoFinal = 'NOMBRE_MODELO';
                            } elseif (stripos($errorTexto, 'NOMBRE_MEDIDA') !== false || stripos($errorTexto, 'MEDIDA') !== false) {
                                $campoFinal = 'NOMBRE_MEDIDA';
                            } elseif (stripos($errorTexto, 'NOMBRE_ESPECIFICACION') !== false || stripos($errorTexto, 'ESPECIFICACION') !== false) {
                                $campoFinal = 'NOMBRE_ESPECIFICACION';
                            } elseif (stripos($errorTexto, 'NOMBRE_TIPO') !== false || stripos($errorTexto, 'TIPO') !== false) {
                                $campoFinal = 'NOMBRE_TIPO';
                            }
                        }

                        $errores[] = [
                            'fila' => $filaReal,
                            'campo' => $campoFinal,
                            'error' => $errorTexto,
                        ];

                        $yaTeníaError = false;
                        $erroresAntesDeIA = count($errores) - 1; // -1 porque acabamos de agregar uno
                        for ($ei = 0; $ei < $erroresAntesDeIA; $ei++) {
                            if ($errores[$ei]['fila'] === $filaReal) {
                                $yaTeníaError = true;
                                break;
                            }
                        }
                        if (! $yaTeníaError) {
                            $conError++;
                            $validos = max(0, $validos - 1);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Si Claude falla, seguimos con las reglas básicas (no bloqueamos el flujo)
            Log::warning('[Alta Producto] IA no disponible: '.$e->getMessage());
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

        // Tiene errores — generar Excel con correcciones (solo celdas en rojo)
        $fullPath = $this->generarExcelConErrores($productos, $errores);
        $relativePath = str_replace(storage_path('app/public/'), '', $fullPath);

        $errorMsg = "❌ El Excel tiene {$conError} producto(s) con errores.\n\n";
        $errorMsg .= "Las celdas con error están marcadas en ROJO en el Excel descargable.\n";
        $errorMsg .= "Corrige los campos señalados y vuelve a subir.\n\n";
        $errorMsg .= "ERRORES ENCONTRADOS:\n";
        foreach (array_slice($errores, 0, 20) as $err) {
            // Separar el error de la solución (CÓMO CORREGIR)
            $partes = explode('CÓMO CORREGIR:', $err['error']);
            $problema = trim($partes[0]);
            $solucion = isset($partes[1]) ? trim($partes[1]) : '';

            if ($solucion) {
                $errorMsg .= "• Fila {$err['fila']} → {$err['campo']}: {$problema}\n";
                $errorMsg .= "  ✅ SOLUCIÓN: {$solucion}\n";
            } else {
                // Para errores de IA que tienen "Orden correcto sugerido:"
                $partesIA = explode('Orden correcto sugerido:', $err['error']);
                $problemaIA = trim($partesIA[0]);
                $sugerenciaIA = isset($partesIA[1]) ? trim($partesIA[1]) : '';

                $errorMsg .= "• Fila {$err['fila']} → {$err['campo']}: {$problemaIA}\n";
                if ($sugerenciaIA) {
                    $errorMsg .= "  ✅ DEBE IR ASÍ: {$sugerenciaIA}\n";
                }
            }
        }
        if (count($errores) > 20) {
            $errorMsg .= "\n... y ".(count($errores) - 20)." errores más.\n";
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

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Validación IA');

        // Headers — solo los datos del producto, SIN columnas de estatus/errores
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'PRODUCCION', 'FAMILIA', 'TIPO_PRODUCTO', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'MARCA_PRODUCTO', 'MODELO_PRODUCTO', 'MEDIDA_PRODUCTO', 'MATERIAL', 'COLOR', 'VOLTAJE', 'ESPECIFICACIONES', 'OBSERVACIONES'];
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
            'NOMBRE_MEDIDA' => 'E', 'NOMBRE_ESPECIFICACION' => 'F', 'PRODUCCION' => 'G',
            'FAMILIA' => 'H', 'TIPO_PRODUCTO' => 'I', 'SUBFAMILIA' => 'J', 'UNIDAD_MEDIDA' => 'K',
            'PRECIO' => 'L', 'CLAVE_SAT' => 'M', 'LOTE' => 'N', 'PEDIMENTO' => 'O',
            'MARCA_PRODUCTO' => 'P', 'MODELO_PRODUCTO' => 'Q', 'MEDIDA_PRODUCTO' => 'R',
            'MATERIAL' => 'S', 'COLOR' => 'T', 'VOLTAJE' => 'U', 'ESPECIFICACIONES' => 'V',
            'OBSERVACIONES' => 'W',
            // Aliases para errores de IA que usan nombres genéricos
            'NOMBRE' => 'B', 'MARCA' => 'C', 'MODELO' => 'D', 'MEDIDA' => 'E',
            'ESPECIFICACION' => 'F', 'GENERAL' => 'B',
        ];

        // Datos
        foreach ($productos as $index => $producto) {
            $fila = $index + 2;
            $excelRow = $index + 2;

            $col = 'A';
            foreach (['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'PRODUCCION', 'FAMILIA', 'TIPO_PRODUCTO', 'SUBFAMILIA', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'MARCA_PRODUCTO', 'MODELO_PRODUCTO', 'MEDIDA_PRODUCTO', 'MATERIAL', 'COLOR', 'VOLTAJE', 'ESPECIFICACIONES', 'OBSERVACIONES'] as $campo) {
                $sheet->setCellValue($col.$excelRow, $producto[$campo] ?? '');
                $col++;
            }

            if (isset($erroresPorFila[$fila])) {
                // Colorear las celdas específicas que tienen error en ROJO
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
                $sugerencia = match($campo) {
                    'CODIGO' => 'Escribe un código único (ej: MPI0538, ME0201)',
                    'NOMBRE_TIPO' => 'Escribe QUÉ ES el producto (ej: RESINA EPOXICA, MOTOR ELECTRICO, CAJA CORRUGADA)',
                    'NOMBRE_MARCA' => 'Escribe QUIÉN lo fabrica (ej: WEG, SKF, 3M, ALPHA, KRAFT)',
                    'NOMBRE_MODELO' => 'Escribe la REFERENCIA del fabricante (ej: W22, IND-500, CP-40, T-100)',
                    'NOMBRE_MEDIDA' => 'Escribe el TAMAÑO o capacidad con números (ej: 500ML, 3HP, 40X30X25, 180G)',
                    'NOMBRE_ESPECIFICACION' => 'Escribe CARACTERÍSTICAS adicionales (ej: TRIFASICO, TRANSPARENTE, DOBLE PARED)',
                    'PRODUCCION' => 'Selecciona del dropdown: MPI (Materia Prima), ME (Empaque) o MN (Mantenimiento)',
                    'FAMILIA' => 'Selecciona del dropdown una familia del catálogo oficial',
                    'TIPO_PRODUCTO' => 'Selecciona del dropdown: MPI, ME o MN (igual que PRODUCCION)',
                    'UNIDAD_MEDIDA' => 'Selecciona del dropdown: KG, PZA o CAJA',
                    'OBSERVACIONES' => 'Escribe notas relevantes del producto en MAYÚSCULAS (ej: PROVEEDOR NACIONAL ENTREGA 5 DIAS)',
                    default => 'Este campo es obligatorio, no puede estar vacío',
                };
                $errores[] = [
                    'fila' => $fila,
                    'campo' => $campo,
                    'error' => "Campo obligatorio vacío. CÓMO CORREGIR: {$sugerencia}",
                ];
            }
        }

        $nombre = trim($producto['NOMBRE'] ?? '');
        $familia = strtoupper(trim($producto['FAMILIA'] ?? ''));
        $subfamilia = strtoupper(trim($producto['SUBFAMILIA'] ?? ''));
        $unidad = strtoupper(trim($producto['UNIDAD_MEDIDA'] ?? ''));
        $precio = $producto['PRECIO'] ?? '';

        // ═══ 1.5 CODIGO — Solo alfanumérico y guiones ═══
        $codigo = trim($producto['CODIGO'] ?? '');
        if ($codigo) {
            if (! preg_match('/^[A-Za-z0-9\-_]+$/', $codigo)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'CODIGO',
                    'error' => "Código inválido: '{$codigo}'. Solo se permiten letras, números y guiones. CÓMO CORREGIR: Usa un código como MPI0538, ME0201, MN-045. Sin comillas, signos de interrogación ni caracteres especiales.",
                ];
            }
        }

        // ═══ 2. NOMENCLATURA — Construir NOMBRE desde las 5 partes ═══

        // ═══ 2. NOMENCLATURA — Construir NOMBRE desde las 5 partes ═══
        // Las 5 partes — validar con valor ORIGINAL, luego convertir
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

        // Validar cada parte individualmente con el valor ORIGINAL (para detectar minúsculas)
        $partesNombre = [
            'NOMBRE_TIPO' => ['valor' => $nombreTipoRaw, 'desc' => 'Qué es el producto (ej: RESINA EPOXICA, MOTOR ELECTRICO, CAJA CORRUGADA)'],
            'NOMBRE_MARCA' => ['valor' => $nombreMarcaRaw, 'desc' => 'Quién lo fabrica (ej: WEG, SKF, 3M, ALPHA, KRAFT)'],
            'NOMBRE_MODELO' => ['valor' => $nombreModeloRaw, 'desc' => 'Referencia del fabricante (ej: W22, IND-500, CP-40, T-100)'],
            'NOMBRE_MEDIDA' => ['valor' => $nombreMedidaRaw, 'desc' => 'Tamaño o capacidad (ej: 500ML, 3HP, 40X30X25, 180G)'],
            'NOMBRE_ESPECIFICACION' => ['valor' => $nombreEspecRaw, 'desc' => 'Características adicionales (ej: TRIFASICO, TRANSPARENTE, DOBLE PARED)'],
        ];

        foreach ($partesNombre as $campo => $info) {
            if (! empty($info['valor'])) {
                // Debe ser MAYÚSCULAS
                if ($info['valor'] !== strtoupper($info['valor'])) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => $campo,
                        'error' => "Debe estar en MAYÚSCULAS sin acentos. Recibido: '{$info['valor']}'. CÓMO CORREGIR: {$info['desc']}",
                    ];
                }
                // No caracteres especiales
                if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`"\'?_@^!]/', $info['valor'])) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => $campo,
                        'error' => "Contiene caracteres no permitidos. Solo letras, números, espacios, / - . () CÓMO CORREGIR: {$info['desc']}",
                    ];
                }
                // Detectar texto basura (consonantes sin vocales)
                $letras = preg_replace('/[\d\-\/\.\s]/', '', $info['valor']);
                if (strlen($letras) >= 5 && ! preg_match('/[AEIOU]/i', $letras)) {
                    $errores[] = [
                        'fila' => $fila,
                        'campo' => $campo,
                        'error' => "Texto ilegible detectado: '{$info['valor']}'. CÓMO CORREGIR: {$info['desc']}",
                    ];
                }
            }
        }

        // NOMBRE_MEDIDA debe contener al menos un número
        if ($nombreMedidaRaw && ! preg_match('/\d/', $nombreMedidaRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'NOMBRE_MEDIDA',
                'error' => "La medida debe contener un valor numérico. Recibido: '{$nombreMedidaRaw}'. CÓMO CORREGIR: Escribe tamaño con números (ej: 500ML, 3HP, 40X30X25, 10X5CM)",
            ];
        }

        $familiaRaw = trim($producto['FAMILIA'] ?? '');
        $familia = strtoupper($familiaRaw);
        $subfamilia = strtoupper(trim($producto['SUBFAMILIA'] ?? ''));
        $unidadRaw = trim($producto['UNIDAD_MEDIDA'] ?? '');
        $unidad = strtoupper($unidadRaw);
        $precio = trim($producto['PRECIO'] ?? '');
        $produccionRaw = trim($producto['PRODUCCION'] ?? '');
        $tipoProductoRaw = trim($producto['TIPO_PRODUCTO'] ?? '');

        // ═══ 2.5 PRODUCCION — Debe ser exactamente MPI, ME o MN en MAYÚSCULAS ═══
        if ($produccionRaw && $produccionRaw !== strtoupper($produccionRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'PRODUCCION',
                'error' => "Debe estar en MAYÚSCULAS. Recibido: '{$produccionRaw}'. CÓMO CORREGIR: Selecciona del dropdown: MPI, ME o MN",
            ];
        }
        if ($produccionRaw && ! in_array(strtoupper($produccionRaw), ['MPI', 'ME', 'MN'])) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'PRODUCCION',
                'error' => "Valor no válido: '{$produccionRaw}'. CÓMO CORREGIR: Solo se acepta MPI (Materia Prima Importación), ME (Material Empaque) o MN (Mantenimiento)",
            ];
        }

        // ═══ 2.6 TIPO_PRODUCTO — Debe ser exactamente MPI, ME o MN ═══
        if ($tipoProductoRaw && ! in_array(strtoupper($tipoProductoRaw), ['MPI', 'ME', 'MN'])) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'TIPO_PRODUCTO',
                'error' => "Valor no válido: '{$tipoProductoRaw}'. CÓMO CORREGIR: Solo MPI, ME o MN",
            ];
        }

        // ═══ 2.7 UNIDAD_MEDIDA — Debe estar en MAYÚSCULAS y ser válida ═══
        if ($unidadRaw && $unidadRaw !== strtoupper($unidadRaw)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Debe estar en MAYÚSCULAS. Recibido: '{$unidadRaw}'. CÓMO CORREGIR: Selecciona del dropdown: KG, PZA o CAJA",
            ];
        }

        // ═══ 3. FAMILIA — Debe ser de la lista oficial ═══
        if ($familia && ! in_array($familia, $this->familiasValidas)) {
            $sugerencia = $this->buscarFamiliaSimilar($familia);
            $errores[] = [
                'fila' => $fila,
                'campo' => 'FAMILIA',
                'error' => "Familia '{$familia}' no está en el catálogo oficial.".
                    ($sugerencia ? " ¿Quisiste decir '{$sugerencia}'?" : ' Familias válidas: '.implode(', ', array_slice($this->familiasValidas, 0, 10)).'...'),
            ];
        }

        // ═══ 4. UNIDAD DE MEDIDA — Lista oficial ═══
        if ($unidad && ! in_array($unidad, $this->unidadesValidas)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'UNIDAD_MEDIDA',
                'error' => "Unidad '{$unidad}' no válida. CÓMO CORREGIR: Solo se acepta KG, PZA o CAJA (selecciona del dropdown)",
            ];
        }

        // ═══ 5. PRECIO — Numérico y razonable (opcional, pero si viene debe ser válido) ═══
        if ($precio !== '' && $precio !== null) {
            $precioLimpio = str_replace([',', '$', ' '], '', $precio);
            // Aceptar signo $ al inicio (se limpia automáticamente)
            if (! is_numeric($precioLimpio)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'PRECIO',
                    'error' => "El precio debe ser numérico (ej: \$150.50 o 150.50). Recibido: '{$precio}'",
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

        // ═══ 6. DUPLICADOS INTELIGENTES ═══
        if ($nombre) {
            // Buscar duplicado exacto
            $nombreNorm = strtoupper(str_replace(' ', '', Str::ascii($nombre)));
            $existe = Producto::whereRaw("UPPER(REPLACE(nombre, ' ', '')) = ?", [$nombreNorm])->exists();
            if ($existe) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'NOMBRE',
                    'error' => 'DUPLICADO: Este producto ya existe en el catálogo',
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

        // ═══ 7. CAMPOS OPCIONALES — Validar formato si vienen ═══
        $marca = trim($producto['MARCA'] ?? '');
        if ($marca && $marca !== strtoupper(Str::ascii($marca))) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'MARCA',
                'error' => 'La marca debe estar en MAYÚSCULAS sin acentos',
            ];
        }

        // Validar VOLTAJE — si viene, debe ser del dropdown o un valor eléctrico válido
        $voltaje = trim($producto['VOLTAJE'] ?? '');
        if ($voltaje) {
            $voltajesValidos = ['110V', '127V', '220V', '220/440V', '110/220V', '440V', '480V', '12VDC', '24VDC', '3HP', '5HP', '10HP', '60Hz', 'N/A'];
            $voltajeUpper = strtoupper($voltaje);
            if (! in_array($voltajeUpper, $voltajesValidos) && ! preg_match('/^\d+[\d\/\.]*\s*(V|VDC|VAC|Hz|W|KW|HP|A)(\s*[\/-]\s*\d+[\d\/\.]*\s*(V|VDC|VAC|Hz|W|KW|HP|A)?)*$/i', $voltaje)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'VOLTAJE',
                    'error' => "Voltaje inválido: '{$voltaje}'. CÓMO CORREGIR: Selecciona del dropdown (110V, 220V, 220/440V, 440V, 12VDC, 24VDC, 3HP, 5HP, 10HP, 60Hz, N/A). No escribas texto libre.",
                ];
            }
        }

        // Validar MODELO — no debe tener caracteres especiales raros ni ser texto basura
        $modelo = trim($producto['MODELO'] ?? $producto['MODELO_PRODUCTO'] ?? '');
        if ($modelo) {
            if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`"\'?@^!]/', $modelo)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'MODELO_PRODUCTO',
                    'error' => "Modelo inválido: '{$modelo}'. Contiene caracteres especiales. CÓMO CORREGIR: Solo letras, números y guiones. Ejemplo: W22, IND-500, ORG-R180",
                ];
            }
            // Detectar basura (muchas consonantes sin vocales)
            $modeloLetras = preg_replace('/[\d\-\/\.]/', '', $modelo);
            if (strlen($modeloLetras) >= 5 && ! preg_match('/[AEIOUaeiou]/', $modeloLetras)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'MODELO_PRODUCTO',
                    'error' => "Modelo ilegible: '{$modelo}'. CÓMO CORREGIR: Escribe el modelo real del fabricante. Ejemplo: W22, CP-40, T-100",
                ];
            }
            // Debe estar en MAYÚSCULAS
            if ($modelo !== strtoupper($modelo)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'MODELO_PRODUCTO',
                    'error' => "Debe estar en MAYÚSCULAS. Recibido: '{$modelo}'. CÓMO CORREGIR: Escribe en MAYÚSCULAS",
                ];
            }
        }

        // Validar COLOR — si viene, no debe ser un número ni texto sin sentido
        $color = trim($producto['COLOR'] ?? '');
        if ($color && preg_match('/^\d+$/', $color)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'COLOR',
                'error' => "Color inválido: '{$color}'. Debe ser un nombre de color (ej: ROJO, BLANCO, TRANSPARENTE).",
            ];
        }

        // Validar MEDIDA — si viene, debe tener números o unidades
        $medida = trim($producto['MEDIDA'] ?? '');
        if ($medida && ! preg_match('/\d|MM|CM|MT|ML|LT|KG|GR|GAL|IN|PZA|HP/i', $medida)) {
            $errores[] = [
                'fila' => $fila,
                'campo' => 'MEDIDA',
                'error' => "Medida inválida: '{$medida}'. Debe incluir un valor numérico o unidad (ej: 500ML, 3HP, 40X30X25, 10X5CM).",
            ];
        }

        // ═══ 8. LOTE y PEDIMENTO — Obligatorios solo si PRODUCCION = MPI ═══
        $produccion = strtoupper(trim($producto['PRODUCCION'] ?? ''));
        if ($produccion === 'MPI') {
            $lote = strtoupper(trim($producto['LOTE'] ?? ''));
            if (empty($lote)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'LOTE',
                    'error' => 'LOTE es obligatorio para productos MPI (Materia Prima Importación). Selecciona SI o NO.',
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

        // ═══ 9. OBSERVACIONES — Debe ser texto legible, profesional, no basura ═══
        $observaciones = trim($producto['OBSERVACIONES'] ?? '');
        if ($observaciones) {
            // Rechazar caracteres especiales raros
            if (preg_match('/[#$%&*=+{}\[\]|\\\\<>~`"\'?@^]/', $observaciones)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => "Contiene caracteres especiales no permitidos. CÓMO CORREGIR: Escribe texto normal sin símbolos raros. Ejemplo: PROVEEDOR NACIONAL ENTREGA 5 DIAS",
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
                    'error' => "Texto ilegible o sin sentido detectado. CÓMO CORREGIR: Escribe observaciones claras y profesionales. Ejemplo: IMPORTACION CHINA PEDIMENTO 26 0001 3000001",
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
                        'error' => "Contenido inapropiado detectado. CÓMO CORREGIR: Las observaciones deben ser profesionales y relevantes al producto. Ejemplo: PARA LINEA 3 PRODUCCION",
                    ];
                    break;
                }
            }

            // Debe tener al menos 2 palabras legibles
            if (count($palabrasObs) < 2) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => "Observaciones muy cortas. CÓMO CORREGIR: Escribe al menos 2 palabras descriptivas. Ejemplo: PROVEEDOR NACIONAL",
                ];
            }

            // Debe estar en MAYÚSCULAS
            if ($observaciones !== mb_strtoupper($observaciones)) {
                $errores[] = [
                    'fila' => $fila,
                    'campo' => 'OBSERVACIONES',
                    'error' => "Debe estar en MAYÚSCULAS. CÓMO CORREGIR: Escribe todo en MAYÚSCULAS. Recibido: '{$observaciones}'",
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
