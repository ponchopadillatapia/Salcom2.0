<?php

namespace App\Console\Commands;

use App\Services\ProveedorApiService;
use Illuminate\Console\Command;

class WieseListarOcCommand extends Command
{
    protected $signature = 'wiese:listar-oc
                            {codigo : Código del proveedor en Wiese (ej. M213015002)}
                            {--desde=2025-01-01 : fechaInicio}
                            {--hasta=2026-12-31 : fechaFin}';

    protected $description = 'Prueba Login servicio Wiese + ListaDocumentosOCPorProveedorFechas';

    public function handle(ProveedorApiService $api): int
    {
        $codigo = (string) $this->argument('codigo');
        $desde = (string) $this->option('desde');
        $hasta = (string) $this->option('hasta');

        $this->info('URL docs: '.(config('services.proveedor_api.docs_url') ?: config('services.proveedor_api.url') ?: '(vacía)'));
        $this->info("Consultando OC de {$codigo} ({$desde} → {$hasta})…");

        $result = $api->listarDocumentosOCPorProveedorFechas($codigo, $desde, $hasta);

        if (! ($result['success'] ?? false)) {
            $this->error(($result['message'] ?? 'Error').' ['.($result['error_type'] ?? '?').']');

            return self::FAILURE;
        }

        $total = (int) ($result['data']['total'] ?? 0);
        $this->info("OK — {$total} documento(s)");

        $items = $result['data']['items'] ?? [];
        foreach (array_slice($items, 0, 5) as $i => $doc) {
            if (! is_array($doc)) {
                continue;
            }
            $this->line(sprintf(
                '  #%d folio=%s%s fecha=%s total=%s razon=%s',
                $i + 1,
                $doc['cseriedocumento'] ?? '',
                $doc['cfolio'] ?? '—',
                $doc['cfecha'] ?? '—',
                $doc['ctotal'] ?? '—',
                $doc['crazonsocial'] ?? '—'
            ));
        }

        if ($total > 5) {
            $this->line('  …');
        }

        return self::SUCCESS;
    }
}
