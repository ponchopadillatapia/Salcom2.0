use App\Models\Factura;
$f = Factura::where("folio_cfdi","like","%93150%")->orWhere("uuid_cfdi","1155BA44-3E97-4039-9DE4-0B603E29ABF1")->get();
if ($f->isEmpty()) { echo "No se encontro la factura.\n"; }
foreach ($f as $fac) { echo "Borrando id={$fac->id} folio={$fac->folio_cfdi}\n"; $fac->forceDelete(); }
echo "Facturas restantes: ".Factura::count()."\n";
