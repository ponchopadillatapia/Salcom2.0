<?php

namespace App\Console\Commands;

use App\Mail\OpinionPositivaAviso;
use App\Models\DocumentoProveedor;
use App\Models\ProveedorUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarAvisoOpinionPositiva extends Command
{
    protected $signature = 'salcom:aviso-opinion';

    protected $description = 'Envía correo a proveedores que no tienen opinión positiva vigente';

    public function handle(): int
    {
        $proveedores = ProveedorUser::where('activo', true)->get();
        $enviados = 0;

        foreach ($proveedores as $prov) {
            if (empty($prov->correo)) {
                continue;
            }

            $doc = DocumentoProveedor::where('proveedor_id', $prov->id)
                ->where('tipo', 'opinion')
                ->latest()
                ->first();

            $estatus = $doc ? $doc->estatus : 'sin_documento';

            // Solo enviar si NO está aprobado
            if ($estatus === 'aprobado') {
                continue;
            }

            try {
                Mail::to($prov->correo)->send(
                    new OpinionPositivaAviso($prov->nombre ?? $prov->usuario, $estatus)
                );
                $enviados++;
                $this->info("Correo enviado a: {$prov->correo} ({$prov->nombre}) — Estado: {$estatus}");
                Log::info('Aviso opinión positiva enviado', ['proveedor' => $prov->correo, 'estatus' => $estatus]);
            } catch (\Exception $e) {
                $this->error("Error enviando a {$prov->correo}: {$e->getMessage()}");
                Log::error('Error aviso opinión positiva', ['proveedor' => $prov->correo, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Total correos enviados: {$enviados}");

        return Command::SUCCESS;
    }
}
