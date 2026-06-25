<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarProductosExcel extends Command
{
    protected $signature = 'productos:importar {archivo} {--dry-run : Solo muestra qué haría sin insertar}';
    protected $description = 'Importar productos desde el Excel bruto de la BD vieja (Productos Chapalita). Filtra cuentas contables y gastos.';

    // No se ignora nada - se suben todos los productos
    private array $prefijosIgnorar = [];

    // Mapeo de unidades SAT a unidades del sistema
    private array $mapeoUnidades = [
        'H87' => 'PZA',
        'PCE' => 'PZA',
        'XBX' => 'CAJA',
        'E4'  => 'KG',
        'MTR' => 'METRO',
        'XKI' => 'SET',
        'LTR' => 'LITRO',
        'XPK' => 'PACK',
        'NA'  => 'NA',
        'E48' => 'PZA',
        'C62' => 'PZA',
        'XBJ' => 'CUBETA',
        'PR'  => 'PAR',
        'A76' => 'PACK',
        'TA'  => 'TONELADA',
        'X44' => 'PZA',
        'SR'  => 'TIRA',
        'X3H' => 'PZA',
        'ACT' => 'PZA',
    ];

    public function handle(): int
    {
        $archivo = $this->argument('archivo');
        $dryRun = $this->option('dry-run');

        if (!file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");
            return 1;
        }

        $this->info("Leyendo Excel: {$archivo}");
        $this->info("Usando lectura por chunks para no saturar memoria...");

        // Leer con reader optimizado (read_only + solo columnas necesarias)
        $reader = IOFactory::createReaderForFile($archivo);
        $reader->setReadDataOnly(true);

        // Cargar solo la primera hoja
        $spreadsheet = $reader->load($archivo);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $this->info("Total filas detectadas: {$highestRow}");

        // Leer headers (fila 1)
        $headers = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headers[] = strtoupper(trim($cell->getValue() ?? ''));
            }
        }

        // Encontrar columnas
        $colCode = array_search('ITEMCODE', $headers);
        $colName = array_search('ITEMNAME', $headers);
        $colGrupo = array_search('REFCODIGOGRUPOARTICULOS', $headers);
        $colSAT = array_search('REFE_CODIGO_ARTICULOS_SAT', $headers);
        $colLote = array_search('MANAGEBATCHNUMBERS', $headers);
        $colUnidad = array_search('PURCHASEUNIT', $headers);

        if ($colCode === false) $colCode = 0;
        if ($colName === false) $colName = 2;

        $this->info("Columnas: Code={$colCode}, Name={$colName}");

        $insertados = 0;
        $actualizados = 0;
        $ignorados = 0;
        $vacios = 0;

        // Procesar en chunks de 500 filas para no saturar memoria
        $chunkSize = 500;
        $bar = $this->output->createProgressBar($highestRow - 1);
        $bar->start();

        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            $endRow = min($startRow + $chunkSize - 1, $highestRow);

            for ($rowNum = $startRow; $rowNum <= $endRow; $rowNum++) {
                $bar->advance();

                $codigo = trim($sheet->getCell([$colCode + 1, $rowNum])->getValue() ?? '');
                $nombre = trim($sheet->getCell([$colName + 1, $rowNum])->getValue() ?? '');

                if (empty($codigo) && empty($nombre)) {
                    $vacios++;
                    continue;
                }

                if ($this->debeIgnorar($codigo)) {
                    $ignorados++;
                    continue;
                }

                $grupo = $colGrupo !== false ? trim($sheet->getCell([$colGrupo + 1, $rowNum])->getValue() ?? '') : '';
                $claveSat = $colSAT !== false ? trim($sheet->getCell([$colSAT + 1, $rowNum])->getValue() ?? '') : '';
                $loteRaw = $colLote !== false ? strtoupper(trim($sheet->getCell([$colLote + 1, $rowNum])->getValue() ?? '')) : '';
                $unidadRaw = $colUnidad !== false ? strtoupper(trim($sheet->getCell([$colUnidad + 1, $rowNum])->getValue() ?? '')) : '';
                $clasificacion = trim($sheet->getCell([2, $rowNum])->getValue() ?? ''); // Columna B

                $unidad = $this->mapeoUnidades[$unidadRaw] ?? 'PZA';
                $lote = ($loteRaw === 'TYES' || $loteRaw === 'SI');
                $tipoProducto = $this->determinarTipo($clasificacion, $codigo);
                $familia = $this->extraerFamilia($grupo);

                if (!$dryRun) {
                    $producto = Producto::updateOrCreate(
                        ['codigo' => $codigo],
                        [
                            'nombre' => $nombre,
                            'categoria' => $tipoProducto,
                            'familia' => $familia,
                            'tipo_producto' => $tipoProducto,
                            'unidad_venta' => $unidad,
                            'clave_sat' => $claveSat,
                            'maneja_lotes' => $lote,
                            'precio' => 0,
                            'stock' => 0,
                            'activo' => true,
                            'proveedor_nombre' => 'Sistema (migración)',
                            'proveedor_tipo' => 'admin',
                        ]
                    );

                    if ($producto->wasRecentlyCreated) {
                        $insertados++;
                    } else {
                        $actualizados++;
                    }
                } else {
                    $insertados++;
                }
            }

            // Liberar memoria entre chunks
            gc_collect_cycles();
        }

        $bar->finish();
        $this->newLine(2);

        // Asignar categorías por prefijo de código
        if (!$dryRun) {
            $this->info("Asignando categorías por prefijo...");
            Producto::where('codigo', 'LIKE', 'RP%')->whereNull('deleted_at')->update(['categoria' => 'RP']);
            Producto::where('codigo', 'LIKE', '550%')->whereNull('deleted_at')->update(['categoria' => 'CONTABLE']);
            Producto::whereRaw("(codigo LIKE '500%' OR codigo LIKE '101%' OR codigo LIKE 'BL%' OR codigo LIKE 'CN%' OR codigo LIKE 'RI%')")->whereNull('deleted_at')->update(['categoria' => 'REFACCIONES']);
            Producto::whereRaw("(codigo LIKE '620%' OR codigo LIKE '621%' OR codigo LIKE '622%' OR codigo LIKE '623%' OR codigo LIKE '624%' OR codigo LIKE '626%' OR codigo LIKE '627%' OR codigo LIKE '628%' OR codigo LIKE '629%' OR codigo LIKE '631%' OR codigo LIKE '634%')")->whereNull('deleted_at')->update(['categoria' => 'GASTOS']);
            Producto::whereRaw("(codigo LIKE '123%' OR codigo LIKE 'MI%')")->whereNull('deleted_at')->update(['categoria' => 'MAQUINARIA']);
            Producto::whereRaw("(codigo LIKE 'HER%' OR codigo LIKE 'HET%' OR codigo LIKE 'CM%')")->whereNull('deleted_at')->update(['categoria' => 'HERRAMIENTAS']);
            Producto::whereRaw("(codigo LIKE 'MUE%' OR codigo LIKE '520%')")->whereNull('deleted_at')->update(['categoria' => 'MUESTRAS']);
            Producto::whereRaw("(codigo LIKE 'MO%' OR codigo LIKE 'MZ%' OR codigo LIKE 'MM%')")->whereNull('deleted_at')->update(['categoria' => 'INSUMOS']);
            Producto::where('codigo', 'LIKE', '120%')->whereNull('deleted_at')->update(['categoria' => 'VEHICULOS']);
            Producto::whereRaw("(codigo LIKE '121%' OR codigo LIKE '122%')")->whereNull('deleted_at')->update(['categoria' => 'EQUIPO']);
            Producto::where('codigo', 'LIKE', '590%')->whereNull('deleted_at')->update(['categoria' => 'MOLDES']);
            Producto::where('codigo', 'LIKE', 'SEGG%')->whereNull('deleted_at')->update(['categoria' => 'SEGURIDAD']);
            Producto::where('codigo', 'LIKE', 'MS%')->where('categoria', 'MN')->whereNull('deleted_at')->update(['categoria' => 'SERVICIOS']);
            $this->info("Categorías asignadas.");
        }

        $modo = $dryRun ? '(DRY RUN - nada se guardó)' : '';
        $this->info("=== RESULTADO {$modo} ===");
        $this->info("Total filas: " . ($highestRow - 1));
        $this->info("Insertados: {$insertados}");
        $this->info("Actualizados: {$actualizados}");
        $this->info("Ignorados (cuentas/gastos): {$ignorados}");
        $this->info("Vacíos: {$vacios}");

        return 0;
    }

    private function debeIgnorar(string $codigo): bool
    {
        foreach ($this->prefijosIgnorar as $prefijo) {
            if (str_starts_with($codigo, $prefijo)) {
                return true;
            }
        }
        return false;
    }

    private function determinarTipo(string $clasificacion, string $codigo): string
    {
        // Si Alan lo clasificó, usar eso
        if ($clasificacion) {
            return match (strtoupper($clasificacion)) {
                'PT' => 'PT',
                'MP' => 'MP',
                'MPI' => 'MPI',
                'ME' => 'ME',
                'SERVICIOS' => 'MN',
                'MUESTRAS' => 'MN',
                'ENSAMBLES' => 'ME',
                default => 'MN',
            };
        }

        // Inferir del código
        $codigoUpper = strtoupper($codigo);
        if (str_starts_with($codigoUpper, 'MPI') || str_starts_with($codigoUpper, 'FMPI')) return 'MPI';
        if (str_starts_with($codigoUpper, 'ME')) return 'ME';
        if (str_starts_with($codigoUpper, 'MP')) return 'MP';
        if (str_starts_with($codigoUpper, 'MS')) return 'MN';
        if (str_starts_with($codigoUpper, 'RP')) return 'MP';
        if (str_starts_with($codigoUpper, 'MUE')) return 'MN';
        if (preg_match('/^[EMN][A-Z]/', $codigoUpper)) return 'PT';

        return 'MN';
    }

    private function extraerFamilia(string $grupo): string
    {
        if (empty($grupo)) return '';

        // El grupo viene como "20-Abrillantador 400ml" — extraer la parte después del guión
        $parts = explode('-', $grupo, 2);
        if (count($parts) === 2) {
            return strtoupper(trim($parts[1]));
        }

        return strtoupper(trim($grupo));
    }
}
