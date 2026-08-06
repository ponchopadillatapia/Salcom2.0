<?php

namespace Database\Seeders;

use App\Models\Alerta;
use App\Models\Factura;
use App\Models\ProveedorUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Solo local: 10 proveedores fake con facturas pendientes para probar Pagos.
 * php artisan db:seed --class=PagosFakeSeeder
 */
class PagosFakeSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = [
            'Aceros del Pacífico SA de CV',
            'Químicos Guadalajara SPR',
            'Empaques del Norte SA',
            'Transportes Rápidos Bajío',
            'Refacciones Industriales MX',
            'Plásticos Corregidora SA',
            'Servicios de Flete Express',
            'Suministros Oficina León',
            'Herramientas Torrey SA de CV',
            'Lubricantes Centro Occidente',
        ];

        foreach ($nombres as $i => $nombre) {
            $n = $i + 1;
            $codigo = sprintf('FAKE%04d', $n);
            $usuario = 'FAKEPAGO'.$n;

            $prov = ProveedorUser::withTrashed()->where('usuario', $usuario)->first();
            if ($prov) {
                if ($prov->trashed()) {
                    $prov->restore();
                }
                $prov->update([
                    'id_proveedor' => $codigo,
                    'nombre' => $nombre,
                    'activo' => true,
                    'password' => Hash::make('salcom2026'),
                ]);
            } else {
                $prov = ProveedorUser::create([
                    'usuario' => $usuario,
                    'password' => Hash::make('salcom2026'),
                    'id_proveedor' => $codigo,
                    'nombre' => $nombre,
                    'tipo_persona' => $n % 3 === 0 ? 'Persona Física' : 'Persona Moral',
                    'telefono' => '33'.str_pad((string) (10000000 + $n), 8, '0', STR_PAD_LEFT),
                    'correo' => 'fake.pago'.$n.'@salcom.test',
                    'activo' => true,
                ]);
            }

            // Limpia facturas fake previas de este código
            Factura::withTrashed()
                ->where('codigo_proveedor', $codigo)
                ->where('folio_cfdi', 'like', 'FAKE-PAGO-%')
                ->forceDelete();

            $cuantas = 1 + ($n % 3); // 1–3 facturas
            for ($f = 1; $f <= $cuantas; $f++) {
                $subtotal = round(1500 + ($n * 350) + ($f * 220.5), 2);
                $iva = round($subtotal * 0.16, 2);
                $retIva = ($n === 7) ? round($subtotal * 0.04, 2) : 0; // uno con retención (flete-ish)
                $retIsr = 0;
                $total = round($subtotal + $iva - $retIva - $retIsr, 2);

                $folio = sprintf('FAKE-PAGO-%02d-%02d', $n, $f);
                $docs = $this->crearDocsFake($codigo, $folio, $nombre, $total);

                $factura = Factura::create([
                    'folio_cfdi' => $folio,
                    'uuid_cfdi' => (string) Str::uuid(),
                    'codigo_proveedor' => $codigo,
                    'regimen_fiscal' => $n % 2 === 0 ? '601' : '612',
                    'es_fletera' => $n === 7,
                    'monto' => $subtotal,
                    'monto_iva' => $iva,
                    'retencion_iva' => $retIva,
                    'retencion_isr' => $retIsr,
                    'total' => $total,
                    'estatus' => 'pendiente',
                    'fecha_vencimiento' => now()->addDays(15 + $n),
                    'archivo_pdf' => $docs['pdf'],
                    'archivo_xml' => $docs['xml'],
                    'archivo_oc' => $docs['oc'],
                    'notas' => 'Factura fake para prueba de Pagos (local)',
                    'validacion_detalle' => [
                        'forma_pago' => '03',
                        'metodo_pago' => 'PUE',
                        'uso_cfdi' => 'G03',
                        'producto' => 'Materiales / servicios de prueba FAKE',
                    ],
                ]);
                $factura->timestamps = false;
                $factura->created_at = now()->subDays(($n * 2) + $f)->setTime(9 + $f, 15 + ($n % 40));
                $factura->updated_at = $factura->created_at;
                $factura->save();
            }
        }

        $this->command?->info('OK: 10 proveedores FAKE0001–FAKE0010 con facturas pendientes.');

        // Notificaciones fake para probar campanita / burbuja roja
        Alerta::where('tipo', 'factura_pago_pendiente')
            ->where('destinatario_tipo', 'admin')
            ->where('titulo', 'like', 'Nueva factura de %')
            ->where('contenido', 'like', '%FAKE-PAGO-%')
            ->delete();

        foreach ($nombres as $i => $nombre) {
            $n = $i + 1;
            $codigo = sprintf('FAKE%04d', $n);
            $facts = Factura::where('codigo_proveedor', $codigo)
                ->where('folio_cfdi', 'like', 'FAKE-PAGO-%')
                ->where('estatus', 'pendiente')
                ->get();
            foreach ($facts as $fact) {
                Alerta::create([
                    'tipo' => 'factura_pago_pendiente',
                    'modulo' => 'pagos',
                    'destinatario_tipo' => 'admin',
                    'destinatario_id' => 1,
                    'titulo' => "Nueva factura de {$nombre}",
                    'contenido' => "Folio {$fact->folio_cfdi} · \$".number_format((float) $fact->total, 2).' · pendiente de pago',
                    'datos' => [
                        'codigo_proveedor' => $codigo,
                        'folio_cfdi' => $fact->folio_cfdi,
                    ],
                    'nivel' => 'info',
                    'estatus' => 'pendiente',
                ]);
            }
        }

        $this->command?->info('OK: notificaciones de pago fake creadas.');
    }

    /**
     * Crea PDF/XML/OC ficticios en storage para poder probar "Ver docs".
     *
     * @return array{pdf: string, xml: string, oc: string}
     */
    private function crearDocsFake(string $codigo, string $folio, string $nombre, float $total): array
    {
        $dir = storage_path('app/public/facturas_fake/'.$codigo);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $base = $dir.'/'.$folio;
        $rel = 'facturas_fake/'.$codigo.'/'.$folio;

        $pdfBody = "Factura FAKE de prueba\nProveedor: {$nombre}\nFolio: {$folio}\nTotal: \$".number_format($total, 2)."\n(Documento ficticio local)";
        $pdf = "%PDF-1.4\n1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n"
            ."2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n"
            ."3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources<< /Font<< /F1 5 0 R >> >> >>endobj\n"
            .'4 0 obj<< /Length '.strlen($pdfBody)." >>stream\nBT /F1 12 Tf 50 700 Td (".str_replace(["\n", '(', ')'], [' / ', '', ''], $pdfBody).") Tj ET\nendstream\nendobj\n"
            ."5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\nxref\n0 6\n0000000000 65535 f \ntrailer<< /Size 6 /Root 1 0 R >>\nstartxref\n0\n%%EOF\n";
        file_put_contents($base.'.pdf', $pdf);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Folio="'.$folio.'" Total="'.number_format($total, 2, '.', '').'" Version="4.0">'
            .'<cfdi:Emisor Nombre="'.htmlspecialchars($nombre, ENT_XML1).'"/>'
            .'<cfdi:Conceptos><cfdi:Concepto Descripcion="Concepto fake de prueba" Importe="'.number_format($total, 2, '.', '').'"/></cfdi:Conceptos>'
            .'</cfdi:Comprobante>'."\n";
        file_put_contents($base.'.xml', $xml);

        $oc = "%PDF-1.4\n1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n"
            ."2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n"
            ."3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources<< /Font<< /F1 5 0 R >> >> >>endobj\n"
            ."4 0 obj<< /Length 80 >>stream\nBT /F1 14 Tf 50 720 Td (Orden de compra FAKE - {$folio}) Tj ET\nendstream\nendobj\n"
            ."5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\nxref\n0 6\ntrailer<< /Size 6 /Root 1 0 R >>\nstartxref\n0\n%%EOF\n";
        file_put_contents($base.'_oc.pdf', $oc);

        return [
            'pdf' => $rel.'.pdf',
            'xml' => $rel.'.xml',
            'oc' => $rel.'_oc.pdf',
        ];
    }
}
