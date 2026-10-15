<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Services\AuditService;
use App\Services\PagoProveedorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class AdminPagosController extends Controller
{
    public function __construct(private PagoProveedorService $pagos) {}

    public function index(Request $request)
    {
        // ENFOQUE BÚSQUEDA (Opción 3): NO se lista a los 5,686 ni se precarga nada.
        // El usuario BUSCA un proveedor (código/nombre/RFC); se filtra sobre el directorio
        // de Wiese (que ya viene en memoria de una sola llamada) y al abrir el proveedor
        // se consultan sus facturas EN VIVO de Wiese. Así siempre está fresco y nunca se traba.
        $q = trim((string) $request->query('q', ''));
        $codigo = trim((string) $request->query('codigo', ''));
        $expediente = '';

        $hayBusqueda = $q !== '' || $codigo !== '';

        // KPIs desactivados en este enfoque (no tiene sentido calcularlos sobre búsqueda).
        $kpiSinRevisar = 0;
        $kpiExpOk = 0;
        $kpiExpPend = 0;
        $kpiTotales = 0;

        $filtrada = collect();
        if ($hayBusqueda) {
            // Traer el directorio de Wiese (1 sola llamada) y filtrar por lo buscado.
            // Se busca en CÓDIGO, NOMBRE y RFC. Se mapea a objetos simples con lo que la vista usa.
            $needle = mb_strtolower($q !== '' ? $q : $codigo);
            try {
                $res = app(\App\Services\ProveedorApiService::class)->listarProveedoresWiese();
            } catch (\Throwable $e) {
                $res = ['success' => false];
            }
            if ($res['success'] ?? false) {
                $filtrada = collect($res['data']['items'] ?? [])
                    ->map(function ($p) {
                        $cod = trim((string) ($p['codigo'] ?? $p['Codigo'] ?? ''));
                        $nom = trim((string) ($p['nombre'] ?? $p['Nombre'] ?? ''));
                        $rfc = trim((string) ($p['rfc'] ?? $p['Rfc'] ?? ''));
                        $mon = (string) ($p['moneda'] ?? $p['Moneda'] ?? '');
                        // Descartar basura ("(Ninguno)", vacíos).
                        if ($cod === '' || $nom === '' || stripos($cod, 'ninguno') !== false) {
                            return null;
                        }

                        return (object) [
                            'codigo' => $cod,
                            'nombre' => $nom,
                            'rfc' => $rfc,
                            'moneda' => $mon === '2' ? 'USD' : 'MXN',
                            // La vista muestra estas; en búsqueda no sabemos el conteo real
                            // (se ve en vivo al abrir el proveedor). Van en 0 aquí.
                            'num_facturas' => 0,
                            'monto_total' => 0.0,
                            'notif_sin_leer' => 0,
                            'expediente' => ['ok' => false, 'motivos' => []],
                            'ultima_factura_at' => null,
                        ];
                    })
                    ->filter()
                    ->filter(function ($r) use ($needle) {
                        return str_contains(mb_strtolower($r->nombre), $needle)
                            || str_contains(mb_strtolower($r->codigo), $needle)
                            || str_contains(mb_strtolower($r->rfc), $needle);
                    })
                    ->sortBy('nombre')
                    ->values();

                // POR QUÉ: antes la búsqueda mostraba TODOS los proveedores que coincidían con
                // el texto (ej. 4 "ORPACK/TRANSFORPAPER"), aunque solo 1 tuviera facturas por
                // pagar. Said pidió mostrar solo los que SÍ tienen facturas pendientes.
                // Para saberlo hay que preguntarle a Wiese las facturas de cada uno (por RFC) y
                // quedarnos con los que tengan al menos una pendiente. De paso llenamos el
                // conteo y el monto reales (antes iban en 0 porque no se consultaban aquí).
                // POR QUÉ por CÓDIGO y no por RFC: antes se consultaban las facturas de cada
                // proveedor por RFC y se descartaban los de 0. Pero proveedores con RFC vacío
                // (ej. SHEN FA 21306085) salían en la tabla global (que busca por código) y NO
                // en la lupa (que buscaba por RFC): incoherente. Ahora traemos TODAS las facturas
                // pendientes UNA sola vez (endpoint global) y las agrupamos por código; el conteo
                // y el monto salen de ahí. Así la lupa y la tabla muestran lo mismo.
                $api = app(\App\Services\ProveedorApiService::class);
                $porCodigo = collect();
                try {
                    $resTodas = $api->listarFacturasPendientesTodos();
                    if ($resTodas['success'] ?? false) {
                        $porCodigo = collect($resTodas['data']['items'] ?? [])->groupBy('codigo');
                    }
                } catch (\Throwable $e) {
                    // Si Wiese falla, dejamos conteos en 0 (no tumbamos la búsqueda).
                }

                $filtrada = $filtrada
                    ->map(function ($r) use ($porCodigo) {
                        $facturas = $porCodigo->get($r->codigo, collect());
                        $r->num_facturas = $facturas->count();
                        $r->monto_total = (float) $facturas->sum('saldo');

                        return $r;
                    })
                    // Quedarnos SOLO con los que tienen al menos una factura pendiente.
                    ->filter(fn ($r) => $r->num_facturas > 0)
                    ->values();
            }
        }

        // Paginar los resultados de la búsqueda (50 por página).
        $porPagina = 50;
        $pagina = max(1, (int) $request->query('page', 1));
        $itemsPagina = $filtrada->slice(($pagina - 1) * $porPagina, $porPagina)->values();

        $proveedoresPendientes = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsPagina,
            $filtrada->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // POR QUÉ: en "Formato para pago" se listan los formatos YA creados, del más nuevo al
        // más viejo, para consultarlos y ver su estatus. Se agrupan por fecha (separador date-row)
        // en la vista. Se traen los últimos 100 para no cargar de más.
        $formatosCreados = PagoProveedor::with('proveedor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        // ── TABLA GLOBAL estilo reporte Wiese (facturas pendientes de TODOS los proveedores) ──
        // POR QUÉ en vivo y NO precargado: Said no quiere duplicar datos en la BD local (riesgo de
        // desincronía con Wiese). Alan ajustó el endpoint ListarDocumentosRFC para que, con RFC
        // VACÍO, devuelva las facturas pendientes de TODOS los proveedores en UNA sola llamada.
        // Así Wiese sigue siendo la única fuente de verdad y no se cuelga (una llamada, no 5,690).
        // Si el endpoint aún no acepta RFC vacío (Alan no lo ha liberado), la tabla sale vacía con
        // aviso y la página NO se rompe (el buscador por proveedor sigue funcionando igual).
        $tablaProveedores = collect();
        $tablaError = null;
        try {
            $resTabla = app(\App\Services\ProveedorApiService::class)->listarFacturasPendientesTodos();
            if ($resTabla['success'] ?? false) {
                // Agrupar las facturas por proveedor (código) y ordenar los bloques por NOMBRE
                // del proveedor (orden alfabético), como pidió Brenda.
                $tablaProveedores = collect($resTabla['data']['items'] ?? [])
                    ->groupBy('codigo')
                    ->sortBy(fn ($facturas) => mb_strtoupper((string) ($facturas->first()['nombre'] ?? '')));
            } else {
                $tablaError = $resTabla['message'] ?? 'No se pudo traer la lista de facturas pendientes.';
            }
        } catch (\Throwable $e) {
            $tablaError = 'Wiese no respondió al traer la lista global: '.$e->getMessage();
        }

        return view('admin.pagos.index', compact(
            'proveedoresPendientes',
            'kpiSinRevisar',
            'kpiExpOk',
            'kpiExpPend',
            'kpiTotales',
            'q',
            'codigo',
            'expediente',
            'formatosCreados',
            'tablaProveedores',
            'tablaError'
        ));
    }

    public function proveedor(string $codigo)
    {
        // Buscar primero en la base local. Si no está, es un proveedor que solo vive en
        // Wiese (los 5,685): lo resolvemos desde la API y armamos un ProveedorUser EN MEMORIA
        // (sin guardarlo) para que la pantalla cargue y se le pueda registrar el pago.
        // POR QUÉ: antes hacía firstOrFail() y tronaba con 404 para cualquier proveedor de Wiese.
        $proveedor = ProveedorUser::porCualquierCodigo($codigo)->first();

        if (! $proveedor) {
            $proveedor = $this->proveedorWieseEnMemoria($codigo);
        }

        // firstOrFail real: si NO está ni en local ni en Wiese, entonces sí es 404.
        if (! $proveedor) {
            abort(404, 'Proveedor no encontrado en el sistema local ni en Wiese.');
        }

        // POR QUÉ: un proveedor que ya existía en local (de pruebas viejas) pudo haberse
        // guardado SIN banco/cuenta/clabe. Como esos datos ahora los trae la API de Wiese,
        // al abrir el proveedor los refrescamos desde Wiese si faltan, para que el Formato
        // para pago los muestre. Solo toca si existe en local Y le falta el dato (no pisa
        // datos buenos ni hace trabajo de más).
        if ($proveedor->exists) {
            $this->refrescarDatosBancariosDesdeWiese($proveedor, $codigo);
        }

        $expediente = $this->pagos->evaluarExpediente($proveedor);

        // Al abrir el proveedor, se marcan como vistas las notifs de pago pendiente
        Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->where('tipo', 'factura_pago_pendiente')
            ->where('datos->codigo_proveedor', $codigo)
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->update(['estatus' => 'leida', 'leida_at' => now()]);

        // Facturas PENDIENTES desde Wiese (por RFC), EN VIVO. Son las que se pagan directamente
        // (con checkbox). YA NO se importan todas a local al abrir (eso tronaba con proveedores
        // grandes como ORPACK y duplicaba la vista). Al pagar, se materializan en local SOLO las
        // seleccionadas (ver store()). Aquí solo se LEE de Wiese, en tiempo real.
        $facturasWiese = collect();
        $wieseError = null;
        $rfc = '';
        $di = $proveedor->datos_identificacion;
        if (is_array($di)) {
            $rfc = trim((string) ($di['rfc'] ?? ''));
        }
        // POR QUÉ: Wiese sigue reportando una factura como pendiente hasta que se salda ALLÁ
        // (en el abono). Pero en cuanto se le genera un Formato para pago, queda ligada a un
        // pago local. Sin este filtro, la misma factura seguía apareciendo y se podían generar
        // N formatos de la misma (se veían 6 expedientes del mismo monto). Aquí juntamos los
        // folios que YA están en un pago local NO cancelado de este proveedor, para ocultarlos
        // de la lista de "pendientes".
        $foliosEnProceso = \App\Models\PagoProveedorFactura::query()
            ->whereHas('pago', function ($q) use ($codigo) {
                $q->where('codigo_proveedor', $codigo)
                    ->where('estatus', '!=', 'cancelado');
            })
            ->pluck('folio_cfdi')
            ->map(fn ($f) => (string) $f)
            ->filter()
            ->unique()
            ->all();

        // POR QUÉ por CÓDIGO y no por RFC: la tabla global de "Formato para pago" trae las facturas
        // del endpoint ListarDocumentos (todos los proveedores) y las agrupa por CÓDIGO. Si aquí
        // buscáramos por RFC, proveedores como SHEN FA (RFC vacío o que el endpoint por RFC no
        // encuentra) saldrían con "0 facturas" aunque en la tabla sí aparezcan. Usar el MISMO
        // endpoint filtrando por código garantiza que lo que se ve en la tabla se puede seleccionar.
        try {
            $res = app(\App\Services\ProveedorApiService::class)
                ->listarFacturasPendientesTodos('2020-01-01', '2035-12-31', null, $codigo);
            if ($res['success'] ?? false) {
                // Más recientes arriba, y ocultar las que YA están en un pago local no cancelado
                // (evita duplicar formatos de la misma factura).
                // Orden: por fecha de VENCIMIENTO ascendente = las que vencen primero (las más
                // viejas/urgentes) arriba. Las sin fecha de vencimiento van al final.
                $facturasWiese = collect($res['data']['items'] ?? [])
                    ->reject(fn ($f) => in_array((string) ($f['folio'] ?? ''), $foliosEnProceso, true))
                    ->sortBy(fn ($f) => $f['fecha_vence'] ?? '9999-12-31')
                    ->values();
            } else {
                $wieseError = $res['message'] ?? 'No se pudieron cargar las facturas de Wiese.';
            }
        } catch (\Throwable $e) {
            $wieseError = 'No se pudo conectar con Wiese (¿VPN activa?).';
        }

        return view('admin.pagos.proveedor', compact('proveedor', 'codigo', 'expediente', 'facturasWiese', 'wieseError', 'rfc'));
    }

    /**
     * Materializa en local SOLO las facturas de Wiese que el usuario seleccionó (por folio).
     * Se llama al PAGAR (store), no al abrir. Como son pocas (2-3), es instantáneo y no
     * truena con proveedores grandes. Devuelve los IDs locales para armar el lote de pago.
     *
     * POR QUÉ: el flujo de pago (crearLote/confirmar) necesita registros locales con id.
     * En vez de copiar TODAS las facturas del proveedor (lento y se desactualiza), copiamos
     * al vuelo solo las que se van a pagar, con datos frescos de Wiese en ese momento.
     *
     * @param  list<string>  $folios  folios de Wiese seleccionados
     * @return list<int>  IDs locales de las facturas materializadas
     */
    private function materializarFacturasSeleccionadas(string $codigo, array $folios): array
    {
        $proveedor = ProveedorUser::porCualquierCodigo($codigo)->first()
            ?? $this->proveedorWieseEnMemoria($codigo);
        if (! $proveedor) {
            return [];
        }

        // Traer facturas de Wiese EN VIVO (frescas al momento de pagar), POR CÓDIGO.
        // POR QUÉ por código y NO por RFC: igual que el detalle del proveedor, buscar por RFC
        // fallaba para proveedores con RFC vacío (SHEN FA, etc.) y el carrito quedaba vacío
        // aunque la factura sí existiera. Usamos el mismo endpoint ListarDocumentos filtrando
        // por código, para que lo que se ve en la tabla se pueda agregar al carrito.
        try {
            $res = app(\App\Services\ProveedorApiService::class)
                ->listarFacturasPendientesTodos('2020-01-01', '2035-12-31', null, $codigo);
        } catch (\Throwable $e) {
            return [];
        }
        if (! ($res['success'] ?? false)) {
            return [];
        }

        // Solo las seleccionadas (por folio). Ya vienen pendientes (saldo > 0) del endpoint.
        $foliosSel = array_map('strval', $folios);
        $seleccionadas = collect($res['data']['items'] ?? [])
            ->filter(fn ($f) => in_array((string) ($f['folio'] ?? ''), $foliosSel, true));

        if ($seleccionadas->isEmpty()) {
            return [];
        }

        // Asegurar el proveedor local (para enlazar por código).
        ProveedorUser::porCualquierCodigo($codigo)->first()
            ?? $this->asegurarProveedorLocalDesdeWiese($codigo);

        $ids = [];
        foreach ($seleccionadas as $f) {
            $folio = (string) ($f['folio'] ?? '');
            $total = (float) ($f['total'] ?? 0);
            $saldo = (float) ($f['saldo'] ?? 0);
            $pagado = max($total - $saldo, 0);

            $factura = Factura::withTrashed()->firstOrNew([
                'folio_cfdi' => $folio !== '' ? $folio : ('WIESE-'.($f['id_documento'] ?? uniqid())),
                'codigo_proveedor' => $codigo,
            ]);
            $factura->fill([
                'monto' => $total,
                'monto_iva' => 0,
                'total' => $total,
                'monto_pagado' => $pagado,
                'estatus' => 'pendiente',
                'regimen_fiscal' => $factura->regimen_fiscal ?: '601',
                'fecha_vencimiento' => ! empty($f['fecha_vence']) ? \Illuminate\Support\Carbon::parse($f['fecha_vence'])->toDateString() : null,
                'notas' => 'De Wiese · serie '.($f['serie'] ?? '').' · idDoc '.($f['id_documento'] ?? ''),
                // Datos fiscales por defecto (Wiese no los da en este endpoint).
                'validacion_detalle' => array_merge(
                    is_array($factura->validacion_detalle) ? $factura->validacion_detalle : [],
                    [
                        'forma_pago' => '03',
                        'metodo_pago' => 'PUE',
                        'uso_cfdi' => 'G03',
                        'regimen_fiscal' => '601',
                        'producto' => 'Importado de Wiese',
                    ]
                ),
            ]);
            if ($factura->trashed()) {
                $factura->restore();
            }
            $factura->save();
            $ids[] = $factura->id;
        }

        return $ids;
    }

    /**
     * Arma un ProveedorUser EN MEMORIA (no guardado en la base) a partir de los datos
     * de Wiese, para proveedores que solo existen allá (los 5,685).
     *
     * POR QUÉ no se guarda: el detalle de pago solo necesita mostrar el nombre y evaluar
     * expediente. Crear el registro local recién aquí ensuciaría la base con miles de
     * proveedores. Si más adelante se le registra un pago, ese flujo ya crea/enlaza lo que necesite.
     *
     * @return ProveedorUser|null  null si tampoco está en Wiese (o Wiese no responde).
     */
    private function proveedorWieseEnMemoria(string $codigo): ?ProveedorUser
    {
        try {
            $res = app(\App\Services\ProveedorApiService::class)->buscarProveedorWiese($codigo);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[PagoProveedor] Wiese no disponible al abrir proveedor: '.$e->getMessage());

            return null;
        }

        if (! ($res['success'] ?? false)) {
            return null;
        }

        $d = $res['data'] ?? [];
        // Wiese usa nombres de campo variados; se cubren mayúsculas/minúsculas.
        $nombre = trim((string) ($d['nombre'] ?? $d['Nombre'] ?? $d['crazonsocial'] ?? $d['CRAZONSOCIAL'] ?? ''));
        $rfc = trim((string) ($d['rfc'] ?? $d['Rfc'] ?? $d['crfc'] ?? $d['CRFC'] ?? ''));
        $monRaw = (string) ($d['moneda'] ?? $d['Moneda'] ?? $d['cidmoneda'] ?? $d['CIDMONEDA'] ?? '');
        $moneda = $monRaw === '2' ? 'DOLLAR' : 'MXN';

        // POR QUÉ: el banco/cuenta/clabe del proveedor no está en columnas dedicadas de Wiese;
        // Salcom los guarda en los campos comodín CTEXTOEXTRA1/2/3 de admClientes (Alan confirmó
        // con ORPACK: 1=banco, 2=cuenta, 3=clabe). El endpoint C# los devuelve como banco/cuenta/
        // clabe; aquí los recogemos para que viajen hasta el proveedor local y salgan en el PDF.
        // POR QUÉ dos nombres: según el endpoint, Wiese nombra estos campos distinto.
        // - ListarProveedorWeb (clase ProveedorWeb) los manda como banco/cuenta/clabe.
        // - BuscarPorRFC / BuscarPorCodigo (clase ClienteProveedor) los manda como
        //   textoExtra1/2/3 (son los mismos CTEXTOEXTRA1/2/3 de admClientes).
        // Leemos ambos para que el dato aparezca sin importar por cuál puerta vino.
        $banco = trim((string) ($d['banco'] ?? $d['Banco'] ?? $d['textoExtra1'] ?? $d['TextoExtra1'] ?? ''));
        $cuenta = trim((string) ($d['cuenta'] ?? $d['Cuenta'] ?? $d['textoExtra2'] ?? $d['TextoExtra2'] ?? ''));
        $clabe = trim((string) ($d['clabe'] ?? $d['Clabe'] ?? $d['textoExtra3'] ?? $d['TextoExtra3'] ?? ''));

        if ($nombre === '') {
            return null; // sin nombre no hay proveedor válido
        }

        // Modelo NO persistido: se rellenan solo los campos que la vista y evaluarExpediente usan.
        $prov = new ProveedorUser;
        $prov->codigo = $codigo;
        $prov->id_proveedor = $codigo;
        $prov->nombre = $nombre;
        $prov->moneda = $moneda;
        $prov->datos_identificacion = [
            'rfc' => $rfc,
            'banco' => $banco,
            'cuenta' => $cuenta,
            'clabe' => $clabe,
        ];
        // Relación documentos vacía en memoria: evita consultas y evaluarExpediente lo trata
        // como expediente incompleto (correcto: un proveedor de Wiese no tiene expediente local).
        $prov->setRelation('documentos', collect());

        return $prov;
    }

    /**
     * Refresca banco/cuenta/clabe de un proveedor local YA EXISTENTE con los datos de Wiese.
     *
     * POR QUÉ: proveedores guardados en pruebas viejas pueden no tener estos datos (antes
     * Wiese no los exponía). Ahora sí los trae; al abrir el proveedor los completamos para
     * que el Formato para pago los muestre. Solo escribe si FALTA el dato en local y SÍ viene
     * de Wiese (no pisa datos buenos; si Wiese está caído, no hace nada).
     */
    private function refrescarDatosBancariosDesdeWiese(ProveedorUser $proveedor, string $codigo): void
    {
        $di = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];

        // Si ya tiene los tres, no hay nada que hacer.
        $faltaAlguno = empty($di['banco']) || empty($di['cuenta']) || empty($di['clabe']);
        if (! $faltaAlguno) {
            return;
        }

        // Traer el proveedor fresco de Wiese (en memoria ya resuelve banco/cuenta/clabe).
        $fresco = $this->proveedorWieseEnMemoria($codigo);
        if (! $fresco) {
            return; // Wiese caído o no encontrado: dejamos lo que haya, sin romper.
        }
        $diFresco = is_array($fresco->datos_identificacion) ? $fresco->datos_identificacion : [];

        // Completar SOLO lo que falte, con lo que traiga Wiese (no vacíos).
        $cambio = false;
        foreach (['banco', 'cuenta', 'clabe'] as $campo) {
            if (empty($di[$campo]) && ! empty($diFresco[$campo])) {
                $di[$campo] = $diFresco[$campo];
                $cambio = true;
            }
        }

        if ($cambio) {
            $proveedor->datos_identificacion = $di;
            $proveedor->save();
        }
    }

    /**
     * Igual que proveedorWieseEnMemoria(), pero GUARDA el proveedor en la base local.
     * Se usa al registrar el PRIMER pago de un proveedor que solo existía en Wiese:
     * el flujo de pago necesita un ProveedorUser con id real para enlazar el lote.
     *
     * @return ProveedorUser|null  null si no se pudo resolver desde Wiese.
     */
    private function asegurarProveedorLocalDesdeWiese(string $codigo): ?ProveedorUser
    {
        $enMemoria = $this->proveedorWieseEnMemoria($codigo);
        if (! $enMemoria) {
            return null;
        }

        // Crear el registro local con los datos de Wiese. Se marca inactivo y sin
        // expediente aprobado (es correcto: aún no pasó el onboarding). El pago se puede
        // registrar igual; el expediente solo es aviso, no bloquea el borrador.
        $di = is_array($enMemoria->datos_identificacion) ? $enMemoria->datos_identificacion : [];

        return ProveedorUser::create([
            // usuario/password son obligatorios en la tabla. Este proveedor "nace" solo para
            // el flujo de pago (no inicia sesión); usuario único por código, password aleatorio.
            'usuario' => 'wiese_'.$codigo,
            'password' => bcrypt(\Illuminate\Support\Str::random(32)),
            'codigo' => $codigo,
            'id_proveedor' => $codigo,
            'nombre' => $enMemoria->nombre,
            'rfc' => trim((string) ($di['rfc'] ?? '')),
            'moneda' => $enMemoria->moneda,
            'datos_identificacion' => $di,
            'activo' => false,
        ]);
    }

    /** Campanita admin: facturas nuevas pendientes de pago. */
    public function alertasJson()
    {
        $tipos = ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'];

        $sinLeer = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->whereIn('tipo', $tipos)
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->count();

        $items = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->whereIn('tipo', $tipos)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(function (Alerta $a) {
                $codigo = $a->datos['codigo_proveedor'] ?? null;
                $url = match ($a->tipo) {
                    'pago_programado' => route('admin.pagos'),
                    'pago_realizado' => route('admin.pago-proveedores'),
                    'abono_interno_registrado' => route('admin.historial-abonos'),
                    default => $codigo ? route('admin.pagos.proveedor', $codigo) : route('admin.pagos'),
                };

                return [
                    'id' => $a->id,
                    'titulo' => $a->titulo,
                    'contenido' => $a->contenido,
                    'leida' => in_array($a->estatus, ['leida', 'accionada'], true),
                    'hace' => optional($a->created_at)->diffForHumans(),
                    'url' => $url,
                ];
            });

        return response()
            ->json(['sin_leer' => $sinLeer, 'items' => $items])
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    public function marcarAlertaLeida(Alerta $alerta)
    {
        $tiposPermitidos = ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'];
        if ($alerta->destinatario_tipo !== 'admin' || !in_array($alerta->tipo, $tiposPermitidos)) {
            return response()->json(['ok' => false, 'mensaje' => 'No autorizado'], 403);
        }

        if (! in_array($alerta->estatus, ['leida', 'accionada'], true)) {
            $alerta->update(['estatus' => 'leida', 'leida_at' => now()]);
        }

        $codigo = $alerta->datos['codigo_proveedor'] ?? null;
        $sinLeer = Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->where('tipo', 'factura_pago_pendiente')
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->count();

        return response()->json([
            'ok' => true,
            'sin_leer' => $sinLeer,
            'id' => $alerta->id,
            'url' => $codigo ? route('admin.pagos.proveedor', $codigo) : route('admin.pagos'),
        ]);
    }

    /** Marca todas las alertas de la campanita admin como leídas al abrirla. */
    public function marcarTodasAlertasLeidas()
    {
        $tipos = ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'];

        Alerta::query()
            ->where('destinatario_tipo', 'admin')
            ->whereIn('tipo', $tipos)
            ->whereNotIn('estatus', ['leida', 'accionada'])
            ->update(['estatus' => 'leida', 'leida_at' => now()]);

        return response()->json([
            'ok' => true,
            'sin_leer' => 0,
        ]);
    }

    public function store(Request $request)
    {
        // Ahora se seleccionan facturas de WIESE (en vivo). Cada checkbox manda el FOLIO
        // de la factura de Wiese en `folios[]`. Al pagar, materializamos en local SOLO
        // esas facturas seleccionadas (no todas), y luego creamos el lote con sus IDs locales.
        $data = $request->validate([
            'codigo_proveedor' => 'required|string',
            'folios' => 'required|array|min:1',
            'folios.*' => 'string',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:1000',
            'confirmar' => 'nullable|boolean',
        ]);

        // Buscar el proveedor en local. Si no está (solo vive en Wiese), lo creamos AHORA
        // en la base local para poder enlazar el pago.
        $proveedor = ProveedorUser::porCualquierCodigo($data['codigo_proveedor'])->first();
        if (! $proveedor) {
            $proveedor = $this->asegurarProveedorLocalDesdeWiese($data['codigo_proveedor']);
        }
        if (! $proveedor) {
            return back()->withInput()->with('error', 'No se encontró el proveedor ni en local ni en Wiese.');
        }
        $autoConfirmar = $request->boolean('confirmar');

        // Materializar en local SOLO las facturas de Wiese seleccionadas (por folio).
        // Devuelve los IDs locales para pasarlos a crearLote. Rápido: son 2-3, no miles.
        $facturaIds = $this->materializarFacturasSeleccionadas($data['codigo_proveedor'], $data['folios']);
        if ($facturaIds === []) {
            return back()->withInput()->with('error', 'No se pudieron preparar las facturas seleccionadas (¿Wiese respondió?).');
        }

        try {
            $pago = $this->pagos->crearLote(
                $proveedor,
                $facturaIds,
                $data['fecha_pago'] ?? null,
                $data['notas'] ?? null,
                session('admin_id')
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($autoConfirmar) {
            try {
                $this->pagos->confirmar(
                    $pago,
                    session('admin_id'),
                    [],
                    $data['fecha_pago'] ?? null,
                    []
                );
                $pago = $pago->fresh();

                return redirect()
                    ->route('admin.pagos.show', ['pago' => $pago, 'descargar_reporte' => 1])
                    ->with('mensaje', 'Pago confirmado automáticamente. Descargando reporte resumen…');
            } catch (InvalidArgumentException $e) {
                return redirect()
                    ->route('admin.pagos.show', $pago)
                    ->with('error', $e->getMessage());
            }
        }

        return redirect()
            ->route('admin.pagos.show', $pago)
            ->with('mensaje', 'Pago creado en borrador. Revisa y confirma.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CARRITO MULTI-PROVEEDOR
    // POR QUÉ: en la vida real, un "Formato para pago" junta facturas de VARIOS
    // proveedores en una sola lista (ORPACK, Álvaro, etc.) con un gran total. El carrito
    // persiste la selección en sesión mientras el usuario navega de un proveedor a otro;
    // al final genera UN pago que agrupa todo (ver carritoGenerar).
    // ──────────────────────────────────────────────────────────────────────────

    /** Clave de sesión del carrito. */
    private const CARRITO_KEY = 'pagos_carrito';

    /** Agrega las facturas seleccionadas de UN proveedor al carrito (en sesión). */
    public function carritoAgregar(Request $request)
    {
        $data = $request->validate([
            'codigo_proveedor' => 'required|string',
            'folios' => 'required|array|min:1',
            'folios.*' => 'string',
        ]);

        $codigo = $data['codigo_proveedor'];

        // Resolver datos del proveedor (nombre, rfc) y las facturas frescas de Wiese,
        // para guardar en el carrito el detalle (total/saldo/serie/fecha) y no re-consultar
        // Wiese cada vez que se abre el carrito.
        $proveedor = ProveedorUser::porCualquierCodigo($codigo)->first()
            ?? $this->proveedorWieseEnMemoria($codigo);
        if (! $proveedor) {
            return back()->with('error', 'No se encontró el proveedor en Wiese.');
        }
        $di = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];
        $rfc = trim((string) ($di['rfc'] ?? ''));

        // Traer facturas de Wiese para capturar los datos de las seleccionadas.
        // POR QUÉ por CÓDIGO y no por RFC: listarFacturasProveedorPorRFC pega al 250 directo
        // (docsUrl), que SiteGround no alcanza, así que el carrito quedaba en 0 facturas en
        // producción. Además fallaba con proveedores de RFC vacío. Usamos el MISMO método que
        // la tabla y la materialización (listarFacturasPendientesTodos filtrado por código),
        // que ya pasa por el puente 5009 en producción.
        $facturasWiese = collect();
        try {
            $res = app(\App\Services\ProveedorApiService::class)
                ->listarFacturasPendientesTodos('2020-01-01', '2035-12-31', null, $codigo);
            if ($res['success'] ?? false) {
                $facturasWiese = collect($res['data']['items'] ?? [])->where('pendiente', true);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo conectar con Wiese (¿VPN activa?).');
        }

        $carrito = session(self::CARRITO_KEY, []);
        $foliosSel = array_map('strval', $data['folios']);

        // Inicializar la entrada del proveedor si no existe.
        if (! isset($carrito[$codigo])) {
            $carrito[$codigo] = [
                'nombre' => $proveedor->nombre,
                'rfc' => $rfc,
                'banco' => (string) ($di['banco'] ?? ''),
                'cuenta' => (string) ($di['cuenta'] ?? ''),
                'clabe' => (string) ($di['clabe'] ?? ''),
                'moneda' => $proveedor->moneda,
                'folios' => [],
            ];
        }

        $agregadas = 0;
        foreach ($facturasWiese as $f) {
            $folio = (string) ($f['folio'] ?? '');
            if ($folio === '' || ! in_array($folio, $foliosSel, true)) {
                continue;
            }
            // Guardar el detalle de la factura (idempotente: si ya estaba, se actualiza).
            $carrito[$codigo]['folios'][$folio] = [
                'folio' => $folio,
                'serie' => (string) ($f['serie'] ?? ''),
                'total' => (float) ($f['total'] ?? 0),
                'saldo' => (float) ($f['saldo'] ?? 0),
                'moneda' => (string) ($f['moneda'] ?? 'MXN'),
                'fecha_factura' => $f['fecha_factura'] ?? null,
                'fecha_vence' => $f['fecha_vence'] ?? null,
                'id_documento' => $f['id_documento'] ?? null,
            ];
            $agregadas++;
        }

        session([self::CARRITO_KEY => $carrito]);

        return redirect()
            ->route('admin.pagos.carrito')
            ->with('mensaje', "Se agregaron {$agregadas} factura(s) de {$proveedor->nombre} al carrito.");
    }

    /** Muestra el carrito: todo lo seleccionado, agrupado por proveedor, con gran total. */
    public function carritoVer()
    {
        $carrito = session(self::CARRITO_KEY, []);

        // Armar estructura para la vista + totales por moneda.
        $grupos = [];
        $granTotalMxn = 0.0;
        $granTotalUsd = 0.0;
        $totalFacturas = 0;
        foreach ($carrito as $codigo => $info) {
            $subtotal = 0.0;
            foreach ($info['folios'] as $f) {
                $subtotal += (float) $f['saldo'];
                $totalFacturas++;
                if (($f['moneda'] ?? 'MXN') === 'USD') {
                    $granTotalUsd += (float) $f['saldo'];
                } else {
                    $granTotalMxn += (float) $f['saldo'];
                }
            }
            $grupos[] = [
                'codigo' => $codigo,
                'nombre' => $info['nombre'],
                'rfc' => $info['rfc'],
                'banco' => $info['banco'] ?? '',
                'subtotal' => $subtotal,
                'folios' => array_values($info['folios']),
            ];
        }

        return view('admin.pagos.carrito', compact('grupos', 'granTotalMxn', 'granTotalUsd', 'totalFacturas'));
    }

    /** Quita una factura (o un proveedor completo) del carrito. */
    public function carritoQuitar(Request $request)
    {
        $data = $request->validate([
            'codigo_proveedor' => 'required|string',
            'folio' => 'nullable|string', // si viene vacío, se quita el proveedor completo
        ]);

        $carrito = session(self::CARRITO_KEY, []);
        $codigo = $data['codigo_proveedor'];

        if (isset($carrito[$codigo])) {
            if (! empty($data['folio'])) {
                unset($carrito[$codigo]['folios'][$data['folio']]);
                // Si el proveedor se quedó sin facturas, quitarlo entero.
                if (empty($carrito[$codigo]['folios'])) {
                    unset($carrito[$codigo]);
                }
            } else {
                unset($carrito[$codigo]);
            }
        }

        session([self::CARRITO_KEY => $carrito]);

        return back()->with('mensaje', 'Factura quitada del carrito.');
    }

    /** Vacía el carrito completo. */
    public function carritoVaciar()
    {
        session()->forget(self::CARRITO_KEY);

        return redirect()->route('admin.pagos.carrito')->with('mensaje', 'Carrito vaciado.');
    }

    /**
     * Genera UN pago que agrupa TODAS las facturas del carrito (de varios proveedores).
     * Materializa en local las facturas de cada proveedor, crea un PagoProveedor marcado
     * como multi-proveedor, y cada línea guarda su propio codigo/nombre de proveedor.
     */
    public function carritoGenerar(Request $request)
    {
        $carrito = session(self::CARRITO_KEY, []);
        if (empty($carrito)) {
            return redirect()->route('admin.pagos.carrito')->with('error', 'El carrito está vacío.');
        }

        // Departamento: define en qué carpeta del archivero (expedientes) queda este pago.
        $data = $request->validate([
            'departamento' => 'required|string|in:'.implode(',', array_keys(PagoProveedor::DEPARTAMENTOS)),
        ]);

        try {
            $pago = $this->pagos->crearPagoMultiProveedor($carrito, function (string $codigo, array $folios) {
                // Reutiliza la materialización existente (por proveedor), que ya trae datos
                // frescos de Wiese y crea los registros locales de factura.
                return $this->materializarFacturasSeleccionadas($codigo, $folios);
            }, session('admin_id'));
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.pagos.carrito')->with('error', $e->getMessage());
        }

        // Guardar el departamento elegido en el pago (para agrupar el expediente).
        $pago->update(['departamento' => $data['departamento']]);

        // Carrito cumplido: se vacía.
        session()->forget(self::CARRITO_KEY);

        return redirect()
            ->route('admin.pagos.show', $pago)
            ->with('mensaje', 'Formato de pago multi-proveedor creado en borrador. Revisa y confirma.');
    }

    public function show(PagoProveedor $pago)
    {
        $pago->load(['lineas.factura', 'proveedor.documentos']);
        // POR QUÉ: en un pago multi-proveedor el expediente debe evaluar a TODOS los
        // proveedores involucrados (Karen), no solo al principal. evaluarExpedientePago
        // decide solo/multi internamente.
        $expediente = $this->pagos->evaluarExpedientePago($pago);

        $datosAuto = null;
        $errorDatosAuto = null;
        $docsFiscales = $this->pagos->documentosFiscalesParaPago($pago);
        if ($pago->esBorrador()) {
            try {
                $datosAuto = $this->pagos->datosConfirmacionDesdeFacturas($pago);
            } catch (InvalidArgumentException $e) {
                $errorDatosAuto = $e->getMessage();
            }
        }

        $tieneMasFacturasPendientes = $pago->codigo_proveedor
            ? Factura::query()
                ->where('codigo_proveedor', $pago->codigo_proveedor)
                ->where('estatus', 'pendiente')
                ->exists()
            : false;

        // Facturas del lote precargadas (PDF/XML) para verlas a mano y verificar.
        $facturasLote = [];
        foreach ($pago->lineas as $linea) {
            $f = $linea->factura;
            if (! $f) {
                continue;
            }
            $folio = $this->pagos->folioFacturaDisplay($f);
            $pdfUrl = $f->archivo_pdf && \Illuminate\Support\Facades\Storage::disk('public')->exists($f->archivo_pdf)
                ? asset('storage/'.$f->archivo_pdf) : null;
            $xmlUrl = $f->archivo_xml && \Illuminate\Support\Facades\Storage::disk('public')->exists($f->archivo_xml)
                ? asset('storage/'.$f->archivo_xml) : null;
            $facturasLote[] = [
                'folio' => $folio,
                'total' => (float) $f->total,
                'pdf_url' => $pdfUrl,
                'xml_url' => $xmlUrl,
                'tiene_archivo' => $pdfUrl !== null || $xmlUrl !== null,
            ];
        }

        return view('admin.pagos.show', compact(
            'pago',
            'expediente',
            'datosAuto',
            'errorDatosAuto',
            'tieneMasFacturasPendientes',
            'docsFiscales',
            'facturasLote'
        ));
    }

    public function confirmar(Request $request, PagoProveedor $pago)
    {
        $request->validate([
            'fecha_pago' => 'nullable|date',
        ]);

        $paths = [];

        try {
            $this->pagos->confirmar(
                $pago,
                session('admin_id'),
                $paths,
                $request->input('fecha_pago'),
                []
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.pagos.show', ['pago' => $pago, 'descargar_reporte' => 1])
            ->with('mensaje', 'Pago confirmado. Descargando reporte resumen…');
    }

    public function cancelar(PagoProveedor $pago)
    {
        try {
            $this->pagos->cancelarBorrador($pago);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.pagos')->with('mensaje', 'Borrador cancelado.');
    }

    public function excel(PagoProveedor $pago)
    {
        $lines = $this->pagos->filasExcel($pago);
        $filename = 'Pago_'.$pago->codigo_proveedor.'_lote'.$pago->id.'_'.now()->format('Y-m-d').'.csv';

        $output = "\xEF\xBB\xBF";
        foreach ($lines as $line) {
            $output .= collect($line)->map(function ($cell) {
                $cell = (string) $cell;
                if (str_contains($cell, ',') || str_contains($cell, '"') || str_contains($cell, "\n")) {
                    return '"'.str_replace('"', '""', $cell).'"';
                }

                return $cell;
            })->implode(',')."\r\n";
        }

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /** PDF «REPORTE RESUMEN DE PAGOS» (formato Contabilidad / FCONA). */
    public function reporteResumen(PagoProveedor $pago)
    {
        $pago->load(['lineas.factura', 'proveedor']);
        $data = $this->pagos->datosReporteResumen($pago);
        $filename = 'Formato_para_pago_'.$pago->codigo_proveedor.'_lote'.$pago->id.'_'.now()->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('admin.pagos.reporte-resumen-pdf', $data)
            ->setPaper('letter', 'landscape');

        // Auto-descarga al confirmar; botón "Ver" abre inline en el navegador.
        if (request()->boolean('ver')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Exporta el LAYOUT para el banco como el EXCEL macro "Pagos Mixtos" (.xlsm), pre-llenado
     * con las facturas de este formato de pago.
     *
     * POR QUÉ Excel y no .txt directo (petición de Said/Alexa): Alexa descarga este Excel, lo
     * REVISA, y con el botón "Generar archivo" (macro VBA de la plantilla) produce ella misma el
     * .txt que sube a BBVA NetCash. Así conserva su paso de revisión/confirmación. El Excel sale
     * con el mismo formato (encabezados azules, columnas) y las macros intactas porque se parte
     * de la plantilla y solo se escriben valores. (El método generarTxt() sigue existiendo por si
     * se quiere el .txt directo en el futuro.)
     */
    public function exportarLayoutBanco(PagoProveedor $pago)
    {
        $svc = app(\App\Services\LayoutBancarioService::class);
        $ruta = $svc->generarXlsm($pago);

        // Nombre amigable con el proveedor/lote y fecha. Extensión .xlsm (lleva macros).
        $filename = 'Pagos_Mixtos_lote'.$pago->id.'_'.now()->format('Y-m-d').'.xlsm';

        // deleteFileAfterSend: el .xlsm es temporal; se borra solo cuando termina la descarga.
        return response()->download($ruta, $filename, [
            'Content-Type' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        ])->deleteFileAfterSend(true);
    }

    /** Estado de cuenta histórico del proveedor (CSV). */
    public function estadoCuenta(string $codigo)
    {
        // Igual que proveedor(): si no está en local, resolver desde Wiese (en memoria).
        $proveedor = ProveedorUser::porCualquierCodigo($codigo)->first()
            ?? $this->proveedorWieseEnMemoria($codigo);
        if (! $proveedor) {
            abort(404, 'Proveedor no encontrado en el sistema local ni en Wiese.');
        }
        $facturas = Factura::query()
            ->where('codigo_proveedor', $codigo)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'Estado_Cuenta_' . $codigo . '_' . now()->format('Y-m-d') . '.csv';

        $output = "\xEF\xBB\xBF"; // BOM UTF-8
        $output .= "ESTADO DE CUENTA - " . $proveedor->nombre . " (" . $codigo . ")\r\n";
        $output .= "Generado: " . now()->format('d/m/Y H:i') . "\r\n\r\n";
        $output .= "Fecha,Folio,Total,Monto Pagado,Saldo,Estatus,Última actualización\r\n";

        foreach ($facturas as $f) {
            $saldo = round((float) $f->total - (float) $f->monto_pagado, 2);
            $output .= implode(',', [
                $f->created_at?->format('d/m/Y') ?? '',
                '"' . ($f->folio_cfdi ?: $f->id) . '"',
                number_format((float) $f->total, 2, '.', ''),
                number_format((float) $f->monto_pagado, 2, '.', ''),
                number_format($saldo, 2, '.', ''),
                $f->estatus,
                $f->updated_at?->format('d/m/Y H:i') ?? '',
            ]) . "\r\n";
        }

        $totalFacturado = $facturas->sum('total');
        $totalPagado = $facturas->sum('monto_pagado');
        $totalSaldo = round($totalFacturado - $totalPagado, 2);

        $output .= "\r\n";
        $output .= ",TOTALES," . number_format($totalFacturado, 2, '.', '') . "," . number_format($totalPagado, 2, '.', '') . "," . number_format($totalSaldo, 2, '.', '') . ",,\r\n";

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    // ── Expediente de Pago ──

    /** NIVEL 1 — Archivero: lista de proveedores (carpetas), con cuántos expedientes tiene cada uno. */
    public function expedientes(Request $request)
    {
        $busqueda = trim((string) $request->input('busqueda', ''));

        $query = PagoProveedor::with('proveedor')
            ->where('estatus', 'confirmado'); // solo pagos confirmados tienen expediente

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('codigo_proveedor', 'like', "%{$busqueda}%")
                    ->orWhereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$busqueda}%"));
            });
        }

        $pagos = $query->get();

        // POR QUÉ por DEPARTAMENTO: dirección pidió que el archivero se organice en carpetas de
        // departamento (otros, mantenimiento, compras, imex, produccion, auditoria, acatlan).
        // Cada carpeta junta los expedientes de ese depto. Los pagos sin departamento (viejos)
        // caen en "Sin departamento". Al abrir una carpeta se ven los expedientes (por mes).
        $labels = \App\Models\PagoProveedor::DEPARTAMENTOS;
        $proveedores = $pagos->groupBy(fn ($p) => $p->departamento ?: 'sin_depto')
            ->map(function ($exps, $deptoKey) use ($labels) {
                $noVistos = $exps->filter(function ($e) {
                    $dc = is_array($e->datos_confirmacion) ? $e->datos_confirmacion : [];

                    return empty($dc['visto_archivero']);
                })->count();

                return (object) [
                    'nombre' => $labels[$deptoKey] ?? 'Sin departamento',
                    'departamento' => $deptoKey,
                    'total' => $exps->count(),
                    'pendientes' => $exps->where('estatus_autorizacion', 'pendiente')->count(),
                    'no_vistos' => $noVistos,
                    'monto_total' => $exps->sum('monto_total'),
                    'ultimo_at' => $exps->max(fn ($e) => $e->confirmado_at ?? $e->created_at),
                ];
            })
            ->sortByDesc('ultimo_at')
            ->values();

        $total = $pagos->count();

        return view('admin.pagos.expedientes', compact('proveedores', 'busqueda', 'total'));
    }

    /** NIVEL 2 — Expedientes de un proveedor, agrupados por mes (mes actual abierto). */
    public function expedientesProveedor(Request $request, ProveedorUser $proveedor)
    {
        $estatus = $request->input('estatus', ''); // '' | pendiente | autorizado | rechazado
        // POR QUÉ: la carpeta "Pagos múltiples" del archivero enlaza aquí con ?multi=1.
        // En ese modo mostramos TODOS los pagos multi-proveedor (no filtramos por proveedor,
        // porque agrupan a varios). En modo normal, mostramos los de ESTE proveedor pero
        // EXCLUYENDO los multi (para que no se mezclen con sus pagos individuales).
        $soloMulti = $request->boolean('multi');

        $query = PagoProveedor::with('proveedor')
            ->where('estatus', 'confirmado')
            ->orderByDesc('confirmado_at')
            ->orderByDesc('id');

        if ($soloMulti) {
            $query->where('es_multi_proveedor', true);
        } else {
            $query->where('proveedor_id', $proveedor->id)
                ->where('es_multi_proveedor', false);
        }

        if (in_array($estatus, ['pendiente', 'autorizado', 'rechazado'], true)) {
            $query->where('estatus_autorizacion', $estatus);
        }

        $pagos = $query->get();

        // Marcar qué expedientes NO se habían visto (para el punto rojo estilo WhatsApp),
        // ANTES de marcarlos como vistos.
        $idsNoVistos = $pagos->filter(function ($p) {
            $dc = is_array($p->datos_confirmacion) ? $p->datos_confirmacion : [];

            return empty($dc['visto_archivero']);
        })->pluck('id')->all();

        // "Visto" estilo WhatsApp: al abrir el proveedor, sus expedientes quedan marcados
        // como vistos (flag en datos_confirmacion). No cambia estatus ni autorización.
        try {
            foreach ($pagos as $p) {
                $dc = is_array($p->datos_confirmacion) ? $p->datos_confirmacion : [];
                if (empty($dc['visto_archivero'])) {
                    $dc['visto_archivero'] = true;
                    $p->datos_confirmacion = $dc;
                    $p->saveQuietly();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo marcar expedientes como vistos: '.$e->getMessage());
        }

        // Agrupar por MES/AÑO: el mes actual abierto, los anteriores como carpetas.
        $grupos = $pagos->groupBy(function ($p) {
            $fecha = $p->confirmado_at ?? $p->created_at;

            return $fecha ? $fecha->format('Y-m') : 'sin-fecha';
        });

        $mesActual = now()->format('Y-m');
        $total = $pagos->count();

        // URL base para los filtros de estatus (esta vista también la usa el flujo por depto).
        $urlFiltro = $soloMulti
            ? route('admin.expedientes-pago.proveedor', ['proveedor' => $proveedor->id, 'multi' => 1])
            : route('admin.expedientes-pago.proveedor', $proveedor->id);

        return view('admin.pagos.expedientes-proveedor', compact('proveedor', 'grupos', 'estatus', 'total', 'mesActual', 'idsNoVistos', 'urlFiltro'));
    }

    /**
     * NIVEL 2 (por DEPARTAMENTO) — expedientes de un departamento, agrupados por mes.
     * POR QUÉ: el archivero ahora agrupa por departamento; al abrir una carpeta se ven sus
     * expedientes. Reutiliza la vista de nivel 2 (expedientes-proveedor) pasándole un objeto
     * "proveedor" simulado con el nombre del departamento (la vista solo usa ->nombre).
     */
    public function expedientesDepartamento(Request $request, string $departamento)
    {
        $estatus = $request->input('estatus', '');
        $labels = PagoProveedor::DEPARTAMENTOS;

        // 'sin_depto' representa los pagos viejos sin departamento (columna null).
        $query = PagoProveedor::with('proveedor')
            ->where('estatus', 'confirmado')
            ->when(
                $departamento === 'sin_depto',
                fn ($q) => $q->whereNull('departamento'),
                fn ($q) => $q->where('departamento', $departamento)
            )
            ->orderByDesc('confirmado_at')
            ->orderByDesc('id');

        if (in_array($estatus, ['pendiente', 'autorizado', 'rechazado'], true)) {
            $query->where('estatus_autorizacion', $estatus);
        }

        $pagos = $query->get();

        $idsNoVistos = $pagos->filter(function ($p) {
            $dc = is_array($p->datos_confirmacion) ? $p->datos_confirmacion : [];

            return empty($dc['visto_archivero']);
        })->pluck('id')->all();

        try {
            foreach ($pagos as $p) {
                $dc = is_array($p->datos_confirmacion) ? $p->datos_confirmacion : [];
                if (empty($dc['visto_archivero'])) {
                    $dc['visto_archivero'] = true;
                    $p->datos_confirmacion = $dc;
                    $p->saveQuietly();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo marcar expedientes como vistos: '.$e->getMessage());
        }

        $grupos = $pagos->groupBy(function ($p) {
            $fecha = $p->confirmado_at ?? $p->created_at;

            return $fecha ? $fecha->format('Y-m') : 'sin-fecha';
        });

        // Objeto "proveedor" simulado: la vista nivel-2 solo usa ->nombre para el título.
        $proveedor = (object) ['nombre' => $labels[$departamento] ?? 'Sin departamento'];
        $mesActual = now()->format('Y-m');
        $total = $pagos->count();

        // URL base de filtros apuntando a ESTE departamento (no a un proveedor).
        $urlFiltro = route('admin.expedientes-pago.departamento', ['departamento' => $departamento]);

        return view('admin.pagos.expedientes-proveedor', compact('proveedor', 'grupos', 'estatus', 'total', 'mesActual', 'idsNoVistos', 'urlFiltro'));
    }

    /** Pantalla del expediente: todos los documentos del pago juntos + autorización. */
    public function expediente(PagoProveedor $pago)
    {
        // POR QUÉ este candado: el expediente es para quien AUTORIZA (Alexa/Dirección), no para
        // quien solo arma el Formato para pago. Se quitó el botón desde la pantalla del pago,
        // pero además bloqueamos la URL directa: si el admin no tiene permiso 'pagos', 403.
        $adminId = session('admin_id');
        $admin = $adminId ? \App\Models\AdminUser::find($adminId) : null;
        if ($admin && ! $admin->puedeVer('pagos')) {
            abort(403, 'No tienes permiso para ver expedientes de pago.');
        }

        $pago->load(['lineas.factura', 'proveedor']);
        $grupos = $this->pagos->documentosExpedientePago($pago);

        // POR QUÉ: Alexa (correo) pide ver ANTES de autorizar: qué se compró, hasta cuándo se
        // puede pagar (fecha de vencimiento), y los datos del pago. Armamos ese resumen aquí.
        $vencimientos = $pago->lineas
            ->map(fn ($l) => $l->factura?->fecha_vencimiento)
            ->filter()
            ->sort()
            ->values();
        $resumenExpediente = [
            // "Qué se compró": el concepto/notas del pago; si no hay, aviso de capturarlo.
            'concepto' => trim((string) ($pago->notas ?? '')) ?: null,
            // Fecha límite de pago = el vencimiento MÁS PRÓXIMO de las facturas del lote.
            'vence' => $vencimientos->first(),
            'num_proveedores' => $pago->lineas->pluck('codigo_proveedor')->filter()->unique()->count(),
            'num_facturas' => (int) $pago->num_facturas,
        ];

        return view('admin.pagos.expediente', compact('pago', 'grupos', 'resumenExpediente'));
    }

    /** Adjuntar un documento manual al expediente (póliza de Contpaqi, hojas engrapadas, etc.). */
    public function adjuntarDocumento(Request $request, PagoProveedor $pago)
    {
        $data = $request->validate([
            'tipo' => 'required|string|max:100',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png,xml|max:20480',
        ]);

        $ruta = $request->file('archivo')->store('pagos_comprobantes/'.$pago->id.'/adjuntos', 'public');

        $adjuntos = $pago->documentos_adjuntos ?? [];
        $adjuntos[] = [
            'tipo' => $data['tipo'],
            'nombre' => $data['tipo'].' — '.$request->file('archivo')->getClientOriginalName(),
            'archivo' => $ruta,
            'subido_por' => session('admin_nombre'),
            'subido_at' => now()->toDateTimeString(),
        ];
        $pago->update(['documentos_adjuntos' => $adjuntos]);

        AuditService::registrar('adjuntar', 'pagos', "Adjuntó {$data['tipo']} al expediente de pago #{$pago->id}");

        return back()->with('mensaje', 'Documento adjuntado al expediente.');
    }

    /** Eliminar un adjunto manual del expediente. */
    public function eliminarAdjunto(PagoProveedor $pago, int $indice)
    {
        $adjuntos = $pago->documentos_adjuntos ?? [];
        if (isset($adjuntos[$indice])) {
            if (! empty($adjuntos[$indice]['archivo'])) {
                Storage::disk('public')->delete($adjuntos[$indice]['archivo']);
            }
            unset($adjuntos[$indice]);
            $pago->update(['documentos_adjuntos' => array_values($adjuntos)]);
            AuditService::registrar('eliminar', 'pagos', "Eliminó un adjunto del expediente de pago #{$pago->id}");
        }

        return back()->with('mensaje', 'Documento eliminado del expediente.');
    }

    /** Autorizar el expediente de pago (firma digital de Sandra/Karen). */
    public function autorizarExpediente(Request $request, PagoProveedor $pago)
    {
        $request->validate([
            'notas' => 'nullable|string|max:1000',
        ]);

        $pago->load(['lineas.factura', 'proveedor']);

        $autorizadoPor = session('admin_nombre') ?? 'Admin';
        $fechaHora = now();
        $ip = $request->ip();
        $notas = $request->input('notas');
        $folioAut = 'AUT-'.str_pad((string) $pago->id, 5, '0', STR_PAD_LEFT);

        // Sello electrónico: hash SHA-256 de la cadena original (datos del pago + quién + cuándo).
        // Actúa como "timbre local" que garantiza integridad e identidad.
        $cadenaOriginal = implode('|', [
            'EXP:'.$pago->id,
            'PROV:'.$pago->codigo_proveedor,
            'MONTO:'.number_format((float) $pago->monto_total, 2, '.', ''),
            'FACT:'.$pago->num_facturas,
            'POR:'.$autorizadoPor,
            'ADMIN:'.session('admin_id'),
            'FECHA:'.$fechaHora->toIso8601String(),
            'IP:'.$ip,
        ]);
        $hash = hash('sha256', $cadenaOriginal.'|'.config('app.key'));

        // Generar el PDF de autorización timbrado y guardarlo en el expediente.
        try {
            $pdf = Pdf::loadView('admin.pagos.autorizacion-pdf', [
                'pago' => $pago,
                'folioAut' => $folioAut,
                'autorizadoPor' => $autorizadoPor,
                'fechaHora' => $fechaHora->format('d/m/Y H:i:s'),
                'ip' => $ip,
                'notas' => $notas,
                'hash' => $hash,
            ])->setPaper('letter');

            $rutaPdf = 'pagos_comprobantes/'.$pago->id.'/adjuntos/autorizacion_'.$folioAut.'.pdf';
            Storage::disk('public')->put($rutaPdf, $pdf->output());

            $adjuntos = $pago->documentos_adjuntos ?? [];
            $adjuntos[] = [
                'tipo' => 'Autorización timbrada',
                'nombre' => 'Autorización '.$folioAut.' (sellada)',
                'archivo' => $rutaPdf,
                'subido_por' => $autorizadoPor,
                'subido_at' => $fechaHora->toDateTimeString(),
                'hash' => $hash,
            ];
            $pago->documentos_adjuntos = $adjuntos;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Autorizacion] No se pudo generar el PDF timbrado: '.$e->getMessage());
        }

        $pago->fill([
            'estatus_autorizacion' => 'autorizado',
            'autorizado_por' => session('admin_id'),
            'autorizado_por_nombre' => $autorizadoPor,
            'autorizado_at' => $fechaHora,
            'notas_autorizacion' => $notas,
        ])->save();

        AuditService::registrar(
            'autorizar',
            'pagos',
            $autorizadoPor.' autorizó el expediente de pago #'.$pago->id,
            null,
            ['pago_id' => $pago->id, 'monto' => (float) $pago->monto_total, 'hash' => $hash]
        );

        return back()->with('mensaje', 'Expediente autorizado. Se generó el comprobante de autorización sellado.');
    }

    /** Rechazar el expediente de pago. */
    public function rechazarExpediente(Request $request, PagoProveedor $pago)
    {
        $request->validate(['notas' => 'nullable|string|max:1000']);

        $pago->update([
            'estatus_autorizacion' => 'rechazado',
            'autorizado_por' => session('admin_id'),
            'autorizado_por_nombre' => session('admin_nombre'),
            'autorizado_at' => now(),
            'notas_autorizacion' => $request->input('notas'),
        ]);

        AuditService::registrar(
            'rechazar',
            'pagos',
            (session('admin_nombre') ?? 'Admin').' rechazó el expediente de pago #'.$pago->id,
            null,
            ['pago_id' => $pago->id, 'motivo' => $request->input('notas')]
        );

        return back()->with('mensaje', 'Expediente rechazado.');
    }
}
