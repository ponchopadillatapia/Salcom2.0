<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\AgenteAduanal;
use App\Models\AnticipoAgenteAduanal;
use App\Models\FacturaAgenteAduanal;
use App\Models\OperacionComercioExterior;
use App\Models\PagoAgenteAduanal;
use App\Services\AgenteAduanalArchivoService;
use App\Services\AuditService;
use App\Services\FacturaAgentePdfExtractor;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AgenteAduanalController extends Controller
{
    public function __construct(
        private AgenteAduanalArchivoService $archivos,
        private FacturaAgentePdfExtractor $lectorPdf,
    ) {}

    public function index(Request $request)
    {
        $this->asegurarAcceso();

        $query = FacturaAgenteAduanal::query()->with(['agente', 'operacion']);

        if ($request->filled('q')) {
            $busqueda = trim((string) $request->input('q'));
            $query->where(function ($q) use ($busqueda) {
                $q->where('folio', 'like', "%{$busqueda}%")
                    ->orWhere('serie', 'like', "%{$busqueda}%")
                    ->orWhereHas('agente', function ($agente) use ($busqueda) {
                        $agente->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('agencia', 'like', "%{$busqueda}%")
                            ->orWhere('rfc', 'like', "%{$busqueda}%");
                    });
            });
        }

        if ($request->filled('agente_id')) {
            $query->where('agente_aduanal_id', (int) $request->input('agente_id'));
        }

        if ($request->filled('moneda') && array_key_exists($request->input('moneda'), FacturaAgenteAduanal::MONEDAS)) {
            $query->where('moneda', $request->input('moneda'));
        }

        if ($request->filled('estado') && array_key_exists($request->input('estado'), FacturaAgenteAduanal::ESTADOS_PAGO)) {
            $query->where('estado_pago', $request->input('estado'));
        }

        if ($request->filled('cancelacion') && array_key_exists($request->input('cancelacion'), FacturaAgenteAduanal::ESTADOS_CANCELACION)) {
            $query->where('estado_cancelacion', $request->input('cancelacion'));
        }

        if ($request->filled('tipo') && array_key_exists($request->input('tipo'), OperacionComercioExterior::TIPOS)) {
            $tipo = $request->input('tipo');
            $query->whereHas('operacion', fn ($operacion) => $operacion->where('tipo_operacion', $tipo));
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->input('hasta'));
        }

        $facturas = $query->orderByDesc('fecha')->orderByDesc('id')->paginate(20)->withQueryString();
        $agentes = AgenteAduanal::query()->orderBy('nombre')->get(['id', 'nombre', 'agencia', 'numero_patente']);
        $anticiposSinFactura = AnticipoAgenteAduanal::query()
            ->whereNull('factura_id')
            ->with(['agente', 'operacion'])
            ->orderByDesc('fecha')
            ->limit(20)
            ->get();

        $kpis = [
            'facturas' => FacturaAgenteAduanal::count(),
            'pendientes' => FacturaAgenteAduanal::where('estado_pago', 'pendiente')->count(),
            'canceladas' => FacturaAgenteAduanal::where('estado_cancelacion', 'cancelada')->count(),
        ];

        return view('admin.agentes-aduanales.index', compact('facturas', 'agentes', 'anticiposSinFactura', 'kpis'));
    }

    public function crear(Request $request)
    {
        $this->asegurarAcceso();

        $operacionSeleccionada = null;
        if ($request->filled('operacion_id')) {
            $operacionSeleccionada = OperacionComercioExterior::query()
                ->whereDoesntHave('factura')
                ->find($request->integer('operacion_id'));
        }

        return view('admin.agentes-aduanales.form', [
            'factura' => null,
            'agentes' => AgenteAduanal::query()->orderBy('nombre')->get(),
            'operaciones' => $this->operacionesSinFactura(),
            'operacionSeleccionada' => $operacionSeleccionada,
        ]);
    }

    public function leerPdf(Request $request)
    {
        $this->asegurarAcceso();
        $request->validate([
            'archivo' => ['required', 'file', 'max:'.AgenteAduanalArchivoService::MAX_KB, $this->reglaArchivo()],
        ], [
            'archivo.required' => 'Sube el PDF de la factura.',
            'archivo.max' => 'El archivo supera el límite de 10 MB.',
        ]);

        $archivo = $request->file('archivo');
        if (strtolower((string) $archivo?->getClientOriginalExtension()) !== 'pdf') {
            throw ValidationException::withMessages([
                'archivo' => 'Para leer los datos, sube el PDF que mandó el agente.',
            ]);
        }

        $lectura = $this->lecturaDePdf($archivo);

        return response()->json([
            'datos' => [
                'serie' => $lectura['serie'],
                'folio' => $lectura['folio'],
                'fecha' => $lectura['fecha'],
                'moneda' => $lectura['moneda'],
                'importe' => $lectura['importe'],
                'honorarios' => $lectura['honorarios'],
                'impuestos' => $lectura['impuestos'],
                'tipo_operacion' => $lectura['tipo_operacion'],
                'estado_cancelacion' => $lectura['estado_cancelacion'],
                'agente_aduanal_id' => $lectura['agente_id'],
                'nombre_agente' => $lectura['nombre_agente'] ?? null,
                'rfc_agente' => $lectura['rfc_emisor'] ?? null,
            ],
            'avisos' => $lectura['avisos'],
        ]);
    }

    public function guardar(Request $request)
    {
        $this->asegurarAcceso();
        $datos = $this->validarFactura($request, true);
        $agente = AgenteAduanal::findOrFail($datos['agente_aduanal_id']);
        $operacion = $this->resolverOperacion($datos, $agente);

        $guardado = null;
        if ($request->hasFile('archivo')) {
            $guardado = $this->archivos->guardar(
                $request->file('archivo'),
                'agentes-aduanales/'.$agente->id.'/facturas'
            );
        }

        try {
            $factura = DB::transaction(function () use ($datos, $agente, $operacion, $guardado) {
                $factura = FacturaAgenteAduanal::create([
                    'operacion_id' => $operacion->id,
                    'agente_aduanal_id' => $agente->id,
                    'folio' => $datos['folio'],
                    'serie' => $datos['serie'],
                    'fecha' => $datos['fecha'],
                    'moneda' => $datos['moneda'],
                    'importe' => $datos['importe'],
                    'saldo_pendiente' => $datos['saldo_pendiente'],
                    'honorarios' => $datos['honorarios'],
                    'impuestos' => $datos['impuestos'],
                    'retenciones' => null,
                    'estado_cancelacion' => $datos['estado_cancelacion'],
                    'estado_pago' => $datos['estado_pago'],
                    'sentido' => null,
                    'nombre_archivo' => $guardado['nombre_archivo'] ?? null,
                    'ruta' => $guardado['ruta'] ?? null,
                    'observaciones' => null,
                ]);

                AnticipoAgenteAduanal::query()
                    ->where('operacion_id', $operacion->id)
                    ->whereNull('factura_id')
                    ->update(['factura_id' => $factura->id]);

                return $factura;
            });
        } catch (\Throwable $e) {
            if ($guardado) {
                $this->archivos->eliminar($guardado['ruta']);
            }
            throw $e;
        }

        AuditService::registrar(
            'crear',
            'agentes_aduanales',
            'Registró la factura '.$factura->serieFolio().' del agente '.$agente->nombre.'.',
            null,
            [
                'folio' => $factura->folio,
                'serie' => $factura->serie,
                'importe' => (string) $factura->importe,
                'serie' => $factura->serie,
                'folio' => $factura->folio,
            ]
        );

        return redirect()->route('admin.agentes-aduanales.ver', $factura)
            ->with('mensaje', 'Factura registrada. Los importes quedaron como vienen en el PDF o como los capturaste.');
    }

    public function ver(FacturaAgenteAduanal $factura)
    {
        $this->asegurarAcceso();
        $factura->load(['agente', 'operacion', 'pagos.anticipo']);
        $anticipos = $this->anticiposDeFactura($factura);

        return view('admin.agentes-aduanales.ver', compact('factura', 'anticipos'));
    }

    public function editar(FacturaAgenteAduanal $factura)
    {
        $this->asegurarAcceso();
        $factura->load('operacion');

        return view('admin.agentes-aduanales.form', [
            'factura' => $factura,
            'agentes' => AgenteAduanal::query()->orderBy('nombre')->get(),
            'operaciones' => collect(),
            'operacionSeleccionada' => null,
        ]);
    }

    public function actualizar(Request $request, FacturaAgenteAduanal $factura)
    {
        $this->asegurarAcceso();
        $antes = [
            'importe' => (string) $factura->importe,
            'saldo_pendiente' => $factura->saldo_pendiente !== null ? (string) $factura->saldo_pendiente : null,
            'sentido' => $factura->sentido,
            'estado_pago' => $factura->estado_pago,
        ];
        $datos = $this->validarFactura($request, false);
        $agente = AgenteAduanal::findOrFail($datos['agente_aduanal_id']);
        $rutaAnterior = $factura->ruta;

        $guardado = null;
        if ($request->hasFile('archivo')) {
            $guardado = $this->archivos->guardar(
                $request->file('archivo'),
                'agentes-aduanales/'.$agente->id.'/facturas'
            );
        }

        try {
            DB::transaction(function () use ($factura, $datos, $agente, $guardado) {
                $cambios = [
                    'agente_aduanal_id' => $agente->id,
                    'folio' => $datos['folio'],
                    'serie' => $datos['serie'],
                    'fecha' => $datos['fecha'],
                    'moneda' => $datos['moneda'],
                    'importe' => $datos['importe'],
                    'saldo_pendiente' => $datos['saldo_pendiente'],
                    'honorarios' => $datos['honorarios'],
                    'impuestos' => $datos['impuestos'],
                    'retenciones' => null,
                    'estado_cancelacion' => $datos['estado_cancelacion'],
                    'estado_pago' => $datos['estado_pago'],
                    'sentido' => null,
                    'observaciones' => null,
                ];
                if ($guardado) {
                    $cambios['nombre_archivo'] = $guardado['nombre_archivo'];
                    $cambios['ruta'] = $guardado['ruta'];
                }
                $factura->update($cambios);

                if ($factura->operacion) {
                    $factura->operacion->update([
                        'agente_aduanal_id' => $agente->id,
                        'numero_patente' => $agente->numero_patente,
                        'tipo_operacion' => $datos['tipo_operacion'],
                        'fecha' => $datos['fecha'],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            if ($guardado) {
                $this->archivos->eliminar($guardado['ruta']);
            }
            throw $e;
        }

        if ($guardado && $rutaAnterior && $rutaAnterior !== $guardado['ruta']) {
            $this->archivos->eliminar($rutaAnterior);
        }

        AuditService::registrar(
            'editar',
            'agentes_aduanales',
            'Actualizó la factura '.$factura->serieFolio().'.',
            $antes,
            [
                'importe' => (string) $factura->importe,
                'saldo_pendiente' => $factura->saldo_pendiente !== null ? (string) $factura->saldo_pendiente : null,
                'sentido' => $factura->sentido,
                'estado_pago' => $factura->estado_pago,
            ]
        );

        return redirect()->route('admin.agentes-aduanales.ver', $factura)
            ->with('mensaje', 'Factura actualizada.');
    }

    public function descargarArchivo(FacturaAgenteAduanal $factura)
    {
        $this->asegurarAcceso();
        abort_unless($this->archivos->rutaSegura($factura->ruta), 404);
        abort_unless(Storage::disk(AgenteAduanalArchivoService::DISCO)->exists($factura->ruta), 404);

        $nombre = str_replace(['"', "\r", "\n", '\\', '/'], '', (string) $factura->nombre_archivo);

        return Storage::disk(AgenteAduanalArchivoService::DISCO)->download(
            $factura->ruta,
            $nombre !== '' ? $nombre : 'factura'
        );
    }

    public function crearAnticipo()
    {
        $this->asegurarAcceso();

        return view('admin.agentes-aduanales.anticipo', [
            'agentes' => AgenteAduanal::query()->orderBy('nombre')->get(),
            'operaciones' => OperacionComercioExterior::query()->with(['agente', 'factura'])->orderByDesc('fecha')->limit(100)->get(),
        ]);
    }

    public function guardarAnticipo(Request $request)
    {
        $this->asegurarAcceso();
        $datos = $this->validarAnticipo($request);
        $agente = AgenteAduanal::findOrFail($datos['agente_aduanal_id']);

        $anticipo = DB::transaction(function () use ($datos, $agente) {
            if (! empty($datos['operacion_id'])) {
                $operacion = OperacionComercioExterior::findOrFail($datos['operacion_id']);
                if ((int) $operacion->agente_aduanal_id !== (int) $agente->id) {
                    throw ValidationException::withMessages([
                        'operacion_id' => 'Esa operación no corresponde al agente seleccionado.',
                    ]);
                }
            } else {
                $operacion = OperacionComercioExterior::create([
                    'agente_aduanal_id' => $agente->id,
                    'numero_patente' => $agente->numero_patente,
                    'fecha' => $datos['fecha'],
                    'tipo_operacion' => $datos['tipo_operacion'],
                ]);
            }

            $facturaId = $operacion->factura?->id;

            return AnticipoAgenteAduanal::create([
                'agente_aduanal_id' => $agente->id,
                'factura_id' => $facturaId,
                'operacion_id' => $operacion->id,
                'fecha' => $datos['fecha'],
                'importe' => $datos['importe'],
                'moneda' => $datos['moneda'],
                'referencia' => $datos['referencia'],
                'observaciones' => $datos['observaciones'],
            ]);
        });

        AuditService::registrar(
            'crear',
            'agentes_aduanales',
            'Ligó un anticipo de '.$agente->nombre.' antes del pago.',
            null,
            ['anticipo_id' => $anticipo->id, 'operacion_id' => $anticipo->operacion_id, 'factura_id' => $anticipo->factura_id]
        );

        if ($anticipo->factura_id) {
            return redirect()->route('admin.agentes-aduanales.ver', $anticipo->factura_id)
                ->with('mensaje', 'Anticipo ligado a la factura. Todavía no se registró el pago.');
        }

        return redirect()->route('admin.agentes-aduanales')
            ->with('mensaje', 'Anticipo ligado a la operación. Cuando registres la factura, quedará asociado.');
    }

    public function ligarAnticipo(Request $request, FacturaAgenteAduanal $factura)
    {
        $this->asegurarAcceso();
        $datos = $this->validarAnticipo($request, false);

        $anticipo = $factura->anticipos()->create([
            'agente_aduanal_id' => $factura->agente_aduanal_id,
            'operacion_id' => $factura->operacion_id,
            'fecha' => $datos['fecha'],
            'importe' => $datos['importe'],
            'moneda' => $datos['moneda'],
            'referencia' => $datos['referencia'],
            'observaciones' => $datos['observaciones'],
        ]);

        AuditService::registrar(
            'crear',
            'agentes_aduanales',
            'Ligó un anticipo a la factura '.$factura->serieFolio().'.',
            null,
            ['anticipo_id' => $anticipo->id, 'factura_id' => $factura->id]
        );

        return redirect()->route('admin.agentes-aduanales.ver', $factura)
            ->with('mensaje', 'Anticipo ligado a la factura. El pago se registra aparte.');
    }

    public function guardarPago(Request $request, FacturaAgenteAduanal $factura)
    {
        $this->asegurarAcceso();
        $datos = $request->validate([
            'fecha_pago' => ['required', 'date'],
            'importe_pago' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'moneda_pago' => ['required', Rule::in(array_keys(FacturaAgenteAduanal::MONEDAS))],
            'direccion' => ['required', Rule::in(array_keys(PagoAgenteAduanal::DIRECCIONES))],
            'anticipo_id' => ['nullable', 'integer'],
            'referencia_pago' => ['nullable', 'string', 'max:80'],
            'observaciones_pago' => ['nullable', 'string', 'max:2000'],
        ], [
            'fecha_pago.required' => 'La fecha del pago es obligatoria.',
            'importe_pago.required' => 'El importe del pago es obligatorio.',
            'importe_pago.gt' => 'El importe del pago debe ser mayor a cero.',
            'moneda_pago.required' => 'Selecciona la moneda del pago.',
            'direccion.required' => 'Indica si Salcom paga al agente o el agente paga a Salcom.',
        ]);

        $anticipoId = $datos['anticipo_id'] ?? null;
        if ($anticipoId) {
            $anticipo = AnticipoAgenteAduanal::find($anticipoId);
            $ligado = $anticipo && (
                (int) $anticipo->factura_id === (int) $factura->id
                || ($factura->operacion_id && (int) $anticipo->operacion_id === (int) $factura->operacion_id)
            );
            if (! $ligado) {
                throw ValidationException::withMessages([
                    'anticipo_id' => 'Ese anticipo no está ligado a esta factura ni a su operación.',
                ]);
            }
        }

        $pago = $factura->pagos()->create([
            'anticipo_id' => $anticipoId ?: null,
            'agente_aduanal_id' => $factura->agente_aduanal_id,
            'fecha' => $datos['fecha_pago'],
            'importe' => $this->decimal($datos['importe_pago']),
            'moneda' => $datos['moneda_pago'],
            'direccion' => $datos['direccion'],
            'referencia' => $this->vacio($datos['referencia_pago'] ?? null),
            'observaciones' => $this->vacio($datos['observaciones_pago'] ?? null),
        ]);

        AuditService::registrar(
            'crear',
            'agentes_aduanales',
            'Registró un pago de la factura '.$factura->serieFolio().': '.$pago->direccionLabel().'.',
            null,
            ['pago_id' => $pago->id, 'direccion' => $pago->direccion, 'importe' => (string) $pago->importe]
        );

        return redirect()->route('admin.agentes-aduanales.ver', $factura)
            ->with('mensaje', 'Pago registrado con la dirección que elegiste. El saldo y el estado de la factura no se recalcularon.');
    }

    private function asegurarAcceso(): AdminUser
    {
        $admin = AdminUser::find(session('admin_id'));
        abort_unless($admin && $admin->puedeVer('agentes_aduanales'), 403);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function validarFactura(Request $request, bool $creando): array
    {
        if ($request->hasFile('archivo') && strtolower((string) $request->file('archivo')->getClientOriginalExtension()) === 'pdf') {
            $this->completarDesdePdf($request);
        }

        $request->merge([
            'serie' => $this->vacio($request->input('serie')),
            'folio' => trim((string) $request->input('folio')),
            'honorarios' => $this->vacio($request->input('honorarios')),
            'impuestos' => $this->vacio($request->input('impuestos')),
            'saldo_pendiente' => $this->vacio($request->input('saldo_pendiente')),
            'operacion_id' => $this->vacio($request->input('operacion_id')),
        ]);

        $reglas = [
            'agente_aduanal_id' => ['required', 'integer', 'exists:agentes_aduanales,id'],
            'fecha' => ['required', 'date'],
            'serie' => ['nullable', 'string', 'max:25'],
            'folio' => ['required', 'string', 'max:40'],
            'moneda' => ['required', Rule::in(array_keys(FacturaAgenteAduanal::MONEDAS))],
            'importe' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'saldo_pendiente' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'honorarios' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'impuestos' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'estado_cancelacion' => ['required', Rule::in(array_keys(FacturaAgenteAduanal::ESTADOS_CANCELACION))],
            'estado_pago' => ['required', Rule::in(array_keys(FacturaAgenteAduanal::ESTADOS_PAGO))],
            'archivo' => [$creando ? 'required' : 'nullable', 'file', 'max:'.AgenteAduanalArchivoService::MAX_KB, $this->reglaArchivo()],
            'tipo_operacion' => ['nullable', 'required_without:operacion_id', Rule::in(array_keys(OperacionComercioExterior::TIPOS))],
        ];

        if ($creando) {
            $reglas['operacion_id'] = ['nullable', 'integer', 'exists:operaciones_comercio_exterior,id'];
        } else {
            $reglas['tipo_operacion'] = ['required', Rule::in(array_keys(OperacionComercioExterior::TIPOS))];
        }

        $datos = $request->validate($reglas, [
            'agente_aduanal_id.required' => 'Selecciona el agente aduanal.',
            'fecha.required' => 'La fecha de la factura es obligatoria.',
            'folio.required' => 'El folio es obligatorio.',
            'moneda.required' => 'Selecciona la moneda.',
            'importe.required' => 'El total de la factura es obligatorio.',
            'importe.min' => 'El total no puede ser negativo.',
            'saldo_pendiente.min' => 'El saldo pendiente no puede ser negativo.',
            'tipo_operacion.required_without' => 'Indica si la operación es de importación o de exportación.',
            'tipo_operacion.required' => 'Indica si la operación es de importación o de exportación.',
            'estado_cancelacion.required' => 'Indica el estado de cancelación.',
            'estado_pago.required' => 'Indica el estado del pago.',
            'archivo.required' => 'Sube el PDF de la factura que mandó el agente.',
            'archivo.max' => 'El archivo supera el límite de 10 MB.',
        ]);

        if ($creando && strtolower((string) $request->file('archivo')?->getClientOriginalExtension()) !== 'pdf') {
            throw ValidationException::withMessages([
                'archivo' => 'La factura se registra con el PDF que mandó el agente.',
            ]);
        }

        $datos['importe'] = $this->decimal($datos['importe']);
        foreach (['saldo_pendiente', 'honorarios', 'impuestos'] as $campo) {
            $datos[$campo] = $this->decimalONull($datos[$campo] ?? null);
        }
        $datos['serie'] = $datos['serie'] ?? null;

        return $datos;
    }

    private function completarDesdePdf(Request $request): void
    {
        $archivo = $request->file('archivo');
        if (! $archivo instanceof UploadedFile) {
            return;
        }

        $lectura = $this->lecturaDePdf($archivo);
        $merge = [];
        foreach (['serie', 'folio', 'fecha', 'moneda', 'importe', 'honorarios', 'impuestos', 'tipo_operacion', 'estado_cancelacion'] as $campo) {
            if ($this->vacio($request->input($campo)) === null && ! empty($lectura[$campo])) {
                $merge[$campo] = $lectura[$campo];
            }
        }
        if ($lectura['agente_id'] && $this->vacio($request->input('agente_aduanal_id')) === null) {
            $merge['agente_aduanal_id'] = $lectura['agente_id'];
        }
        if ($lectura['leido']) {
            if ($this->vacio($request->input('estado_pago')) === null && empty($merge['estado_pago'] ?? null)) {
                $merge['estado_pago'] = 'pendiente';
            }
            if ($this->vacio($request->input('estado_cancelacion')) === null && empty($lectura['estado_cancelacion'])) {
                $merge['estado_cancelacion'] = 'vigente';
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }
        if ($lectura['avisos'] !== []) {
            session()->flash('pdf_avisos', $lectura['avisos']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function lecturaDePdf(UploadedFile $archivo): array
    {
        $leido = $this->lectorPdf->leerArchivo($archivo);
        $agente = $this->agentePorRfc($leido['rfc_emisor'] ?? null) ?? $this->agentePorNombre($leido['nombre_emisor'] ?? null);
        $leido['agente_id'] = $agente?->id;
        $leido['nombre_agente'] = $agente?->nombreVisible() ?: ($leido['nombre_emisor'] ?? null);
        if (! $agente && ! empty($leido['rfc_emisor'])) {
            $leido['avisos'][] = 'El RFC '.$leido['rfc_emisor'].' no está en los agentes registrados aquí. Selecciona el agente de la lista.';
        }

        return $leido;
    }

    private function agentePorRfc(?string $rfc): ?AgenteAduanal
    {
        $rfc = strtoupper(trim((string) $rfc));
        if ($rfc === '') {
            return null;
        }

        $agentes = AgenteAduanal::query()
            ->whereRaw('UPPER(rfc) = ?', [$rfc])
            ->get();

        return $agentes->count() === 1 ? $agentes->first() : null;
    }

    private function agentePorNombre(?string $nombre): ?AgenteAduanal
    {
        $nombre = mb_strtoupper(trim((string) $nombre), 'UTF-8');
        if (mb_strlen($nombre) < 8) {
            return null;
        }

        $agentes = AgenteAduanal::query()->get()->filter(function (AgenteAduanal $agente) use ($nombre) {
            $propio = mb_strtoupper(trim((string) $agente->nombre), 'UTF-8');
            $agencia = mb_strtoupper(trim((string) $agente->agencia), 'UTF-8');

            return ($propio !== '' && (str_contains($nombre, $propio) || str_contains($propio, $nombre)))
                || ($agencia !== '' && (str_contains($nombre, $agencia) || str_contains($agencia, $nombre)));
        })->values();

        return $agentes->count() === 1 ? $agentes->first() : null;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function resolverOperacion(array $datos, AgenteAduanal $agente): OperacionComercioExterior
    {
        if (! empty($datos['operacion_id'])) {
            $operacion = OperacionComercioExterior::findOrFail($datos['operacion_id']);
            if ((int) $operacion->agente_aduanal_id !== (int) $agente->id) {
                throw ValidationException::withMessages([
                    'operacion_id' => 'Esa operación no corresponde al agente seleccionado.',
                ]);
            }
            if ($operacion->factura) {
                throw ValidationException::withMessages([
                    'operacion_id' => 'Esa operación ya tiene una factura.',
                ]);
            }

            return $operacion;
        }

        return OperacionComercioExterior::create([
            'agente_aduanal_id' => $agente->id,
            'numero_patente' => $agente->numero_patente,
            'fecha' => $datos['fecha'],
            'tipo_operacion' => $datos['tipo_operacion'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarAnticipo(Request $request, bool $conOperacion = true): array
    {
        $reglas = [
            'fecha_anticipo' => ['required', 'date'],
            'importe_anticipo' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'moneda_anticipo' => ['required', Rule::in(array_keys(FacturaAgenteAduanal::MONEDAS))],
            'referencia_anticipo' => ['nullable', 'string', 'max:80'],
            'observaciones_anticipo' => ['nullable', 'string', 'max:2000'],
        ];

        if ($conOperacion) {
            $request->merge([
                'operacion_id' => $this->vacio($request->input('operacion_id')),
            ]);
            $reglas['agente_aduanal_id'] = ['required', 'integer', 'exists:agentes_aduanales,id'];
            $reglas['operacion_id'] = ['nullable', 'integer', 'exists:operaciones_comercio_exterior,id'];
            $reglas['tipo_operacion'] = ['nullable', 'required_without:operacion_id', Rule::in(array_keys(OperacionComercioExterior::TIPOS))];
        }

        $datos = $request->validate($reglas, [
            'agente_aduanal_id.required' => 'Selecciona el agente aduanal.',
            'fecha_anticipo.required' => 'La fecha del anticipo es obligatoria.',
            'importe_anticipo.required' => 'El importe del anticipo es obligatorio.',
            'importe_anticipo.gt' => 'El anticipo debe ser mayor a cero.',
            'moneda_anticipo.required' => 'Selecciona la moneda del anticipo.',
            'tipo_operacion.required_without' => 'Indica si la operación es de importación o de exportación.',
        ]);

        return [
            'agente_aduanal_id' => $datos['agente_aduanal_id'] ?? null,
            'operacion_id' => $datos['operacion_id'] ?? null,
            'tipo_operacion' => $datos['tipo_operacion'] ?? null,
            'fecha' => $datos['fecha_anticipo'],
            'importe' => $this->decimal($datos['importe_anticipo']),
            'moneda' => $datos['moneda_anticipo'],
            'referencia' => $this->vacio($datos['referencia_anticipo'] ?? null),
            'observaciones' => $this->vacio($datos['observaciones_anticipo'] ?? null),
        ];
    }

    /**
     * @return Collection<int, AnticipoAgenteAduanal>
     */
    private function anticiposDeFactura(FacturaAgenteAduanal $factura)
    {
        return AnticipoAgenteAduanal::query()
            ->where(function ($q) use ($factura) {
                $q->where('factura_id', $factura->id);
                if ($factura->operacion_id) {
                    $q->orWhere('operacion_id', $factura->operacion_id);
                }
            })
            ->orderByDesc('fecha')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, OperacionComercioExterior>
     */
    private function operacionesSinFactura()
    {
        return OperacionComercioExterior::query()
            ->whereDoesntHave('factura')
            ->with('agente')
            ->withCount('anticipos')
            ->orderByDesc('fecha')
            ->get();
    }

    private function reglaArchivo(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $mensaje = $this->archivos->mensajeSiInvalido($value);
            if ($mensaje !== null) {
                $fail($mensaje);
            }
        };
    }

    private function vacio(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function decimal(mixed $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    private function decimalONull(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return $this->decimal($valor);
    }
}
