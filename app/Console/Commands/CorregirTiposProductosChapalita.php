<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Corrige tipo_producto (y clasifs PT) leyendo Productos Chapalita + Claificacion de Productos.
 * Clasif1 del SAP es la fuente de tipo; precio no se toca.
 */
class CorregirTiposProductosChapalita extends Command
{
    protected $signature = 'productos:corregir-tipos
        {chapalita : Ruta a Productos Chapalita.xlsx}
        {clasificacion : Ruta a Claificacion de Productos.xlsx (6 pestañas Clasif1..6)}
        {--dry-run : Solo reporta cambios, no escribe en BD}
        {--limit=0 : Procesar solo N productos (0 = todos)}';

    protected $description = 'Corrige tipo_producto de productos migrados usando Clasif1 del Excel SAP + diccionario de clasificación';

    /** Clasif1 (texto SAP) → tipo_producto del portal — alineado con Wiese Clasif6 */
    private array $mapaTipo = [
        'PRODUCTO TERMINADO' => 'PT',
        'PTT' => 'PTT',
        'P' => 'PT',
        'MATERIA PRIMA' => 'MP',
        'MIP' => 'MP',
        'RP' => 'RP',
        'RP3' => 'RP',
        'RPR' => 'RP',
        'MPI' => 'MPI',
        'ME' => 'ME',
        'MEI' => 'MEI',
        'GASTOS' => 'GA',
        'GAS' => 'GAS',
        'REFACCIONES' => 'REF',
        'REF' => 'REF',
        'HERRAMIENTAS' => 'HER',
        'HER' => 'HER',
        'INSUMOS' => 'INS',
        'INS' => 'INS',
        'SERVICIO' => 'SER',
        'SER' => 'SER',
        'MAQUINARIA' => 'MM',
        'MANO DE OBRA' => 'MO',
        'MS' => 'MS',
        'MT' => 'MT',
        'PZA' => 'PZA',
        'ERC' => 'MM',
        'ERROR SISTEMA' => 'MM',
        'UIL' => 'MM',
        'RET' => 'RET',
        'PR' => 'PR',
        'PN' => 'MM',
        'S' => 'MM',
    ];

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');

        $chapalita = $this->argument('chapalita');
        $clasifPath = $this->argument('clasificacion');
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        foreach ([$chapalita, $clasifPath] as $path) {
            if (! file_exists($path)) {
                $this->error("Archivo no encontrado: {$path}");

                return 1;
            }
        }

        $this->info('Cargando diccionarios Clasif1..6...');
        $maps = $this->cargarDiccionarios($clasifPath);
        $this->info('Clasif1 valores: '.count($maps[1]));

        // Cache CSV liviano (solo columnas necesarias) para no reventar memoria
        $csvPath = storage_path('app/chapalita_tipos_cache.csv');
        if (! file_exists($csvPath) || filemtime($csvPath) < filemtime($chapalita)) {
            $this->info('Generando cache CSV desde Chapalita (una vez)...');
            $this->generarCacheCsv($chapalita, $csvPath);
            $this->info("Cache listo: {$csvPath}");
        } else {
            $this->info("Usando cache CSV existente: {$csvPath}");
        }

        $fh = fopen($csvPath, 'r');
        if (! $fh) {
            $this->error('No se pudo abrir el cache CSV');

            return 1;
        }

        $header = fgetcsv($fh);
        if (! $header) {
            $this->error('Cache CSV vacío');
            fclose($fh);

            return 1;
        }
        $idx = array_flip($header);

        $statsTipoNuevo = [];
        $statsCambios = [];
        $sinClasif = 0;
        $noEnBd = 0;
        $iguales = 0;
        $cambiados = 0;
        $tipoCambios = 0;
        $procesados = 0;
        $ejemplos = [];

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($fh)) !== false) {
            $codigo = trim((string) ($row[$idx['ItemCode']] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $procesados++;
            $bar->advance();

            if ($limit > 0 && $procesados > $limit) {
                $procesados--;
                break;
            }

            $cid1 = trim((string) ($row[$idx['C1']] ?? ''));
            $label1 = ($cid1 !== '' && $cid1 !== '0') ? ($maps[1][$cid1] ?? null) : null;
            $tipoNuevo = $this->tipoDesdeClasif1($label1, $codigo);
            $statsTipoNuevo[$tipoNuevo] = ($statsTipoNuevo[$tipoNuevo] ?? 0) + 1;
            if ($label1 === null) {
                $sinClasif++;
            }

            $departamento = $label1;
            $linea = $this->resolver($maps[2], $row[$idx['C2']] ?? null);
            $subfamilia = $this->resolver($maps[3], $row[$idx['C3']] ?? null);
            $canal = $this->resolver($maps[4], $row[$idx['C4']] ?? null);
            $vendedor = $this->resolver($maps[5], $row[$idx['C5']] ?? null);
            $modulo = $this->resolver($maps[6], $row[$idx['C6']] ?? null);
            $familia = $this->extraerFamilia(trim((string) ($row[$idx['Grupo']] ?? '')));
            if ($familia === '' && $subfamilia) {
                $familia = strtoupper($subfamilia);
            }

            $producto = Producto::withTrashed()->where('codigo', $codigo)->first();
            if (! $producto) {
                $noEnBd++;

                continue;
            }

            $tipoViejo = strtoupper(trim((string) ($producto->tipo_producto ?: '')));
            $tipoCambia = $tipoViejo !== $tipoNuevo;
            $clasifCambia = (string) $producto->departamento !== (string) ($departamento ?? '')
                || (string) $producto->linea !== (string) ($linea ?? '')
                || (string) $producto->subfamilia_pt !== (string) ($subfamilia ?? '')
                || (string) $producto->canal !== (string) ($canal ?? '')
                || (string) $producto->vendedor !== (string) ($vendedor ?? '')
                || (string) $producto->modulo !== (string) ($modulo ?? '')
                || ($label1 && (string) $producto->categoria !== (string) $label1);

            if (! $tipoCambia && ! $clasifCambia) {
                $iguales++;

                continue;
            }

            if ($tipoCambia) {
                $tipoCambios++;
                $clave = ($tipoViejo ?: '?').' → '.$tipoNuevo;
                $statsCambios[$clave] = ($statsCambios[$clave] ?? 0) + 1;
                if (count($ejemplos) < 40) {
                    $nombre = mb_substr(trim((string) ($row[$idx['ItemName']] ?? $producto->nombre)), 0, 45);
                    $ejemplos[] = compact('codigo', 'nombre', 'tipoViejo', 'tipoNuevo', 'label1');
                }
            }

            if (! $dryRun) {
                $producto->tipo_producto = $tipoNuevo;
                $producto->categoria = $label1 ?: $tipoNuevo;
                if ($familia !== '') {
                    $producto->familia = $familia;
                }
                $producto->departamento = $departamento;
                $producto->linea = $linea;
                $producto->subfamilia_pt = $subfamilia;
                $producto->canal = $canal;
                $producto->vendedor = $vendedor;
                $producto->modulo = $modulo;
                $producto->save();
            }

            $cambiados++;
        }

        fclose($fh);
        $bar->finish();
        $this->newLine(2);

        $this->info($dryRun ? '=== DRY RUN (nada escrito) ===' : '=== APLICADO ===');
        $this->info("Procesados Excel: {$procesados}");
        $this->info("Sin Clasif1 (fallback código): {$sinClasif}");
        $this->info("No están en BD: {$noEnBd}");
        $this->info("Sin cambio: {$iguales}");
        $this->info('Filas a actualizar (tipo y/o clasifs): '.$cambiados);
        $this->info("De esas, cambio de tipo_producto: {$tipoCambios}");

        $this->newLine();
        $this->info('Distribución tipo_producto NUEVA (desde Clasif1):');
        arsort($statsTipoNuevo);
        foreach ($statsTipoNuevo as $t => $n) {
            $this->line("  {$t}: {$n}");
        }

        $this->newLine();
        $this->info('Cambios de tipo (viejo → nuevo):');
        arsort($statsCambios);
        foreach ($statsCambios as $k => $n) {
            $this->line("  {$k}: {$n}");
        }

        if ($ejemplos !== []) {
            $this->newLine();
            $this->info('Ejemplos de cambio de tipo:');
            foreach ($ejemplos as $e) {
                $this->line("  {$e['codigo']} | {$e['tipoViejo']}→{$e['tipoNuevo']} | Clasif1=".($e['label1'] ?? '—')." | {$e['nombre']}");
            }
        }

        return 0;
    }

    private function generarCacheCsv(string $chapalita, string $csvPath): void
    {
        ini_set('memory_limit', '1024M');
        $reader = IOFactory::createReaderForFile($chapalita);
        $reader->setReadDataOnly(true);
        $ss = $reader->load($chapalita);
        $sheet = $ss->getActiveSheet();
        $highest = $sheet->getHighestRow();

        $headers = [];
        for ($c = 1; $c <= 45; $c++) {
            $headers[$c] = strtoupper(trim((string) $sheet->getCell([$c, 1])->getValue()));
        }
        $find = fn (string $n) => array_search(strtoupper($n), $headers, true);

        $cols = [
            'ItemCode' => $find('ITEMCODE'),
            'ItemName' => $find('ITEMNAME'),
            'Grupo' => $find('REFCODIGOGRUPOARTICULOS'),
            'C1' => $find('CIDVALORCLASIFICACION1'),
            'C2' => $find('CIDVALORCLASIFICACION2'),
            'C3' => $find('CIDVALORCLASIFICACION3'),
            'C4' => $find('CIDVALORCLASIFICACION4'),
            'C5' => $find('CIDVALORCLASIFICACION5'),
            'C6' => $find('CIDVALORCLASIFICACION6'),
        ];

        $out = fopen($csvPath, 'w');
        fputcsv($out, array_keys($cols));

        for ($r = 2; $r <= $highest; $r++) {
            $code = trim((string) $sheet->getCell([$cols['ItemCode'], $r])->getValue());
            if ($code === '') {
                continue;
            }
            $line = [];
            foreach ($cols as $key => $c) {
                $line[] = $c !== false ? trim((string) $sheet->getCell([$c, $r])->getValue()) : '';
            }
            fputcsv($out, $line);
            if ($r % 2000 === 0) {
                $this->line("  cache fila {$r}/{$highest}");
            }
        }

        fclose($out);
        $ss->disconnectWorksheets();
        unset($ss);
        gc_collect_cycles();
    }

    private function tipoDesdeClasif1(?string $label1, string $codigo): string
    {
        if ($label1 !== null && $label1 !== '') {
            $key = strtoupper(trim($label1));
            if (isset($this->mapaTipo[$key])) {
                return $this->mapaTipo[$key];
            }
        }

        return $this->tipoPorPrefijo($codigo);
    }

    private function tipoPorPrefijo(string $codigo): string
    {
        $c = strtoupper($codigo);
        if (str_starts_with($c, 'MPI') || str_starts_with($c, 'FMPI') || str_starts_with($c, 'EMPI') || str_starts_with($c, 'NMPI') || str_starts_with($c, 'MPIDA') || str_starts_with($c, 'MPIVA')) {
            return 'MPI';
        }
        if (str_starts_with($c, 'ME')) {
            return 'ME';
        }
        if (str_starts_with($c, 'MP') || str_starts_with($c, 'RP')) {
            return 'MP';
        }
        // Servicios / muestras: no son PT aunque empiecen con M
        if (str_starts_with($c, 'MS') || str_starts_with($c, 'MUE') || str_starts_with($c, 'MO') || str_starts_with($c, 'MM') || str_starts_with($c, 'MZ')) {
            return 'MM';
        }
        // PT comercial: E/M/N + letras (ej. MAEHO17, EAEHO64) — excluye numéricos tipo 550…
        if (preg_match('/^[EMN][A-Z]/', $c)) {
            return 'PT';
        }

        return 'MM';
    }

    private function resolver(array $map, mixed $cid): ?string
    {
        $cid = trim((string) $cid);
        if ($cid === '' || $cid === '0') {
            return null;
        }

        return $map[$cid] ?? null;
    }

    private function extraerFamilia(string $grupo): string
    {
        if ($grupo === '') {
            return '';
        }
        $parts = explode('-', $grupo, 2);

        return count($parts) === 2
            ? strtoupper(trim($parts[1]))
            : strtoupper(trim($grupo));
    }

    /** @return array<int, array<string, string>> */
    private function cargarDiccionarios(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $ss = $reader->load($path);
        $maps = [];
        for ($i = 1; $i <= 6; $i++) {
            $maps[$i] = [];
            $sheet = $ss->getSheetByName("Clasif{$i}");
            if (! $sheet) {
                continue;
            }
            $highest = $sheet->getHighestRow();
            for ($r = 2; $r <= $highest; $r++) {
                $id = trim((string) $sheet->getCell([1, $r])->getValue());
                $val = trim((string) $sheet->getCell([2, $r])->getValue());
                if ($id !== '') {
                    $maps[$i][$id] = $val;
                }
            }
        }
        $ss->disconnectWorksheets();

        return $maps;
    }
}
