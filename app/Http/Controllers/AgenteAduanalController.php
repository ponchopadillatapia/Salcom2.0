<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Aduana;
use App\Models\AgenteAduanal;
use App\Models\DocumentoAgenteAduanal;
use App\Models\EncargoConferido;
use App\Services\AgenteAduanalArchivoService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AgenteAduanalController extends Controller
{
    public function __construct(private AgenteAduanalArchivoService $archivos) {}

    public function index(Request $request)
    {
        $this->asegurarAcceso();

        $query = AgenteAduanal::query()->with('aduanas');

        if ($request->filled('q')) {
            $busqueda = trim((string) $request->input('q'));
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('rfc', 'like', "%{$busqueda}%")
                    ->orWhere('numero_patente', 'like', "%{$busqueda}%")
                    ->orWhere('agencia', 'like', "%{$busqueda}%")
                    ->orWhere('contacto_nombre', 'like', "%{$busqueda}%")
                    ->orWhereHas('aduanas', function ($aduanas) use ($busqueda) {
                        $aduanas->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('clave', 'like', "%{$busqueda}%");
                    });
            });
        }

        if ($request->input('estado') === 'activo') {
            $query->where('activo', true);
        } elseif ($request->input('estado') === 'inactivo') {
            $query->where('activo', false);
        }

        if ($request->filled('verificacion') && array_key_exists($request->input('verificacion'), AgenteAduanal::VERIFICACIONES)) {
            $query->where('estado_verificacion', $request->input('verificacion'));
        }

        $tipo = (string) $request->input('tipo');
        if ($tipo === 'importacion') {
            $query->whereIn('tipo_operacion', ['importacion', 'ambas']);
        } elseif ($tipo === 'exportacion') {
            $query->whereIn('tipo_operacion', ['exportacion', 'ambas']);
        } elseif ($tipo === 'ambas') {
            $query->where('tipo_operacion', 'ambas');
        }

        if ($request->filled('aduana_id')) {
            $aduanaId = (int) $request->input('aduana_id');
            $query->whereHas('aduanas', fn ($q) => $q->where('aduanas.id', $aduanaId));
        }

        $agentes = $query->orderBy('nombre')->paginate(20)->withQueryString();
        $aduanas = Aduana::query()->where('activo', true)->orderBy('clave')->get();
        $kpis = [
            'total' => AgenteAduanal::count(),
            'activos' => AgenteAduanal::where('activo', true)->count(),
            'sin_verificar' => AgenteAduanal::where('estado_verificacion', 'no_verificado')->count(),
        ];

        return view('admin.agentes-aduanales.index', compact('agentes', 'aduanas', 'kpis'));
    }

    public function crear()
    {
        $this->asegurarAcceso();

        return view('admin.agentes-aduanales.form', [
            'agente' => null,
            'aduanas' => Aduana::query()->where('activo', true)->orderBy('clave')->get(),
        ]);
    }

    public function guardar(Request $request)
    {
        $this->asegurarAcceso();
        $datos = $this->validarAgente($request);
        $aduanaIds = $datos['aduanas'];
        unset($datos['aduanas']);

        $agente = DB::transaction(function () use ($datos, $aduanaIds) {
            $agente = AgenteAduanal::create($datos);
            $agente->aduanas()->sync($aduanaIds);

            return $agente;
        });

        AuditService::registrar(
            'crear',
            'agentes_aduanales',
            'Registró al agente aduanal '.$agente->nombre.' (patente '.$agente->numero_patente.').',
            null,
            $this->resumenAuditoria($agente),
        );

        $mensaje = $agente->estado_verificacion === 'no_verificado'
            ? 'Agente registrado. La patente quedó como no verificada hasta que alguien consulte la fuente y lo capture aquí.'
            : 'Agente registrado. La verificación quedó capturada con la fuente indicada; este sistema no la consultó.';

        return redirect()->route('admin.agentes-aduanales.ver', $agente)->with('mensaje', $mensaje);
    }

    public function ver(AgenteAduanal $agente)
    {
        $this->asegurarAcceso();
        $agente->load(['aduanas', 'documentos', 'encargos', 'operaciones.aduana']);

        return view('admin.agentes-aduanales.ver', compact('agente'));
    }

    public function editar(AgenteAduanal $agente)
    {
        $this->asegurarAcceso();
        $agente->load('aduanas');

        return view('admin.agentes-aduanales.form', [
            'agente' => $agente,
            'aduanas' => Aduana::query()->where('activo', true)->orderBy('clave')->get(),
        ]);
    }

    public function actualizar(Request $request, AgenteAduanal $agente)
    {
        $this->asegurarAcceso();
        $antes = $this->resumenAuditoria($agente);
        $datos = $this->validarAgente($request, $agente);
        $aduanaIds = $datos['aduanas'];
        unset($datos['aduanas']);

        DB::transaction(function () use ($agente, $datos, $aduanaIds) {
            $agente->update($datos);
            $agente->aduanas()->sync($aduanaIds);
        });

        AuditService::registrar(
            'editar',
            'agentes_aduanales',
            'Actualizó al agente aduanal '.$agente->nombre.' (patente '.$agente->numero_patente.').',
            $antes,
            $this->resumenAuditoria($agente->fresh('aduanas')),
        );

        return redirect()->route('admin.agentes-aduanales.ver', $agente)
            ->with('mensaje', 'Datos del agente actualizados.');
    }

    public function desactivar(AgenteAduanal $agente)
    {
        $this->asegurarAcceso();
        $agente->update(['activo' => false]);

        AuditService::registrar('desactivar', 'agentes_aduanales', 'Desactivó al agente '.$agente->nombre.'.');

        return redirect()->route('admin.agentes-aduanales.ver', $agente)
            ->with('mensaje', 'El agente quedó inactivo. Sus documentos y encargos se conservan. La verificación no cambió.');
    }

    public function reactivar(AgenteAduanal $agente)
    {
        $this->asegurarAcceso();
        $agente->update(['activo' => true]);

        AuditService::registrar('reactivar', 'agentes_aduanales', 'Reactivó al agente '.$agente->nombre.'.');

        return redirect()->route('admin.agentes-aduanales.ver', $agente)
            ->with('mensaje', 'El agente quedó activo de nuevo. Eso no cambia su estado de verificación.');
    }

    public function guardarDocumento(Request $request, AgenteAduanal $agente)
    {
        $admin = $this->asegurarAcceso();
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(DocumentoAgenteAduanal::TIPOS))],
            'archivo' => ['required', 'file', 'max:'.AgenteAduanalArchivoService::MAX_KB, $this->reglaArchivo()],
            'fecha_vencimiento' => ['nullable', 'date'],
            'observaciones_documento' => ['nullable', 'string', 'max:2000'],
        ], [
            'tipo.required' => 'Selecciona el tipo de documento.',
            'archivo.required' => 'Selecciona un archivo PDF, JPG o PNG.',
            'archivo.max' => 'El archivo supera el límite de 10 MB.',
        ]);

        $guardado = $this->archivos->guardar(
            $request->file('archivo'),
            'agentes-aduanales/'.$agente->id.'/documentos'
        );

        $documento = $agente->documentos()->create([
            'tipo' => $datos['tipo'],
            'nombre_archivo' => $guardado['nombre_archivo'],
            'ruta' => $guardado['ruta'],
            'fecha_carga' => now()->toDateString(),
            'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
            'observaciones' => $datos['observaciones_documento'] ?? null,
            'subido_por' => $admin->id,
        ]);

        AuditService::registrar(
            'crear',
            'agentes_aduanales',
            'Cargó un documento ('.$documento->tipoLabel().') del agente '.$agente->nombre.'.',
            null,
            ['tipo' => $documento->tipo, 'nombre_archivo' => $documento->nombre_archivo]
        );

        return redirect(route('admin.agentes-aduanales.ver', $agente).'#documentos')
            ->with('mensaje', 'Documento cargado.');
    }

    public function descargarDocumento(AgenteAduanal $agente, DocumentoAgenteAduanal $documento)
    {
        $this->asegurarAcceso();
        abort_unless($documento->agente_aduanal_id === $agente->id, 404);

        return $this->descargarPrivado($documento->ruta, $documento->nombre_archivo);
    }

    public function eliminarDocumento(AgenteAduanal $agente, DocumentoAgenteAduanal $documento)
    {
        $this->asegurarAcceso();
        abort_unless($documento->agente_aduanal_id === $agente->id, 404);

        $this->archivos->eliminar($documento->ruta);
        $nombre = $documento->nombre_archivo;
        $documento->delete();

        AuditService::registrar(
            'eliminar',
            'agentes_aduanales',
            'Eliminó el documento '.$nombre.' del agente '.$agente->nombre.'.'
        );

        return redirect(route('admin.agentes-aduanales.ver', $agente).'#documentos')
            ->with('mensaje', 'Documento eliminado.');
    }

    public function guardarEncargo(Request $request, AgenteAduanal $agente)
    {
        $this->asegurarAcceso();
        $datos = $this->validarEncargo($request);

        $guardado = null;
        if ($request->hasFile('archivo_acuse')) {
            $guardado = $this->archivos->guardar(
                $request->file('archivo_acuse'),
                'agentes-aduanales/'.$agente->id.'/acuses'
            );
        }

        $encargo = $agente->encargos()->create([
            'numero_patente' => $agente->numero_patente,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_termino' => $datos['fecha_termino'] ?? null,
            'estado' => $datos['estado'],
            'numero_acuse' => $datos['numero_acuse'] ?? null,
            'nombre_acuse' => $guardado['nombre_archivo'] ?? null,
            'ruta_acuse' => $guardado['ruta'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
        ]);

        AuditService::registrar(
            'crear',
            'agentes_aduanales',
            'Registró un encargo conferido interno del agente '.$agente->nombre.' (patente '.$encargo->numero_patente.').',
            null,
            ['estado' => $encargo->estado, 'numero_acuse' => $encargo->numero_acuse]
        );

        return redirect(route('admin.agentes-aduanales.ver', $agente).'#encargos')
            ->with('mensaje', 'Encargo registrado en Salcom. No se envió ningún trámite al SAT.');
    }

    public function actualizarEncargo(Request $request, AgenteAduanal $agente, EncargoConferido $encargo)
    {
        $this->asegurarAcceso();
        abort_unless($encargo->agente_aduanal_id === $agente->id, 404);

        $datos = $this->validarEncargo($request);
        $cambios = [
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_termino' => $datos['fecha_termino'] ?? null,
            'estado' => $datos['estado'],
            'numero_acuse' => $datos['numero_acuse'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
        ];

        if ($request->hasFile('archivo_acuse')) {
            $guardado = $this->archivos->guardar(
                $request->file('archivo_acuse'),
                'agentes-aduanales/'.$agente->id.'/acuses'
            );
            $this->archivos->eliminar($encargo->ruta_acuse);
            $cambios['nombre_acuse'] = $guardado['nombre_archivo'];
            $cambios['ruta_acuse'] = $guardado['ruta'];
        }

        $encargo->update($cambios);

        AuditService::registrar(
            'editar',
            'agentes_aduanales',
            'Actualizó un encargo conferido interno del agente '.$agente->nombre.'.',
            null,
            ['estado' => $encargo->estado, 'numero_acuse' => $encargo->numero_acuse]
        );

        return redirect(route('admin.agentes-aduanales.ver', $agente).'#encargos')
            ->with('mensaje', 'Encargo actualizado en el registro interno.');
    }

    public function descargarAcuse(AgenteAduanal $agente, EncargoConferido $encargo)
    {
        $this->asegurarAcceso();
        abort_unless($encargo->agente_aduanal_id === $agente->id, 404);
        abort_unless(is_string($encargo->ruta_acuse) && $encargo->ruta_acuse !== '', 404);

        return $this->descargarPrivado($encargo->ruta_acuse, $encargo->nombre_acuse ?: 'acuse.pdf');
    }

    public function eliminarEncargo(AgenteAduanal $agente, EncargoConferido $encargo)
    {
        $this->asegurarAcceso();
        abort_unless($encargo->agente_aduanal_id === $agente->id, 404);

        $this->archivos->eliminar($encargo->ruta_acuse);
        $encargo->delete();

        AuditService::registrar(
            'eliminar',
            'agentes_aduanales',
            'Eliminó un encargo conferido interno del agente '.$agente->nombre.'.'
        );

        return redirect(route('admin.agentes-aduanales.ver', $agente).'#encargos')
            ->with('mensaje', 'Encargo eliminado del registro interno.');
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
    private function validarAgente(Request $request, ?AgenteAduanal $agente = null): array
    {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'rfc' => strtoupper(trim((string) $request->input('rfc'))),
            'numero_patente' => trim((string) $request->input('numero_patente')),
            'agencia' => trim((string) $request->input('agencia')),
        ]);

        if ($request->input('estado_verificacion') === 'no_verificado') {
            $request->merge([
                'fecha_ultima_verificacion' => null,
                'fuente_verificacion' => null,
                'fuente_detalle' => null,
            ]);
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'rfc' => [
                'required',
                'string',
                'regex:/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/u',
                Rule::unique('agentes_aduanales', 'rfc')->ignore($agente?->id),
            ],
            'numero_patente' => [
                'required',
                'digits:4',
                Rule::unique('agentes_aduanales', 'numero_patente')->ignore($agente?->id),
            ],
            'agencia' => ['nullable', 'string', 'max:180'],
            'tipo_operacion' => ['required', Rule::in(array_keys(AgenteAduanal::TIPOS_OPERACION))],
            'contacto_nombre' => ['nullable', 'string', 'max:180'],
            'contacto_correo' => ['nullable', 'email', 'max:180'],
            'contacto_telefono' => ['nullable', 'string', 'max:20'],
            'contacto_celular' => ['nullable', 'string', 'max:20'],
            'activo' => ['required', 'boolean'],
            'estado_verificacion' => ['required', Rule::in(array_keys(AgenteAduanal::VERIFICACIONES))],
            'fecha_ultima_verificacion' => ['nullable', 'date', 'before_or_equal:today', 'required_unless:estado_verificacion,no_verificado'],
            'fuente_verificacion' => ['nullable', Rule::in(array_keys(AgenteAduanal::FUENTES)), 'required_unless:estado_verificacion,no_verificado'],
            'fuente_detalle' => ['nullable', 'string', 'max:120', 'required_if:fuente_verificacion,otra'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'aduanas' => ['nullable', 'array'],
            'aduanas.*' => ['integer', 'exists:aduanas,id'],
        ], [
            'nombre.required' => 'El nombre del agente es obligatorio.',
            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.regex' => 'El RFC debe ser de persona física (13 caracteres).',
            'rfc.unique' => 'Ese RFC ya está registrado.',
            'numero_patente.required' => 'El número de patente es obligatorio.',
            'numero_patente.digits' => 'La patente aduanal debe tener 4 dígitos.',
            'numero_patente.unique' => 'Esa patente ya está registrada.',
            'tipo_operacion.required' => 'Selecciona el tipo de operación.',
            'fecha_ultima_verificacion.required_unless' => 'Indica la fecha en que se consultó la fuente.',
            'fecha_ultima_verificacion.before_or_equal' => 'La fecha de verificación no puede ser futura.',
            'fuente_verificacion.required_unless' => 'Indica la fuente que se consultó.',
            'fuente_detalle.required_if' => 'Describe la fuente de verificación.',
            'contacto_correo.email' => 'El correo del contacto no es válido.',
        ]);

        if ($datos['estado_verificacion'] === 'no_verificado') {
            $datos['fecha_ultima_verificacion'] = null;
            $datos['fuente_verificacion'] = null;
            $datos['fuente_detalle'] = null;
        } elseif (($datos['fuente_verificacion'] ?? null) !== 'otra') {
            $datos['fuente_detalle'] = null;
        }

        $datos['agencia'] = $datos['agencia'] !== '' ? $datos['agencia'] : null;
        $datos['aduanas'] = array_values(array_unique(array_map('intval', $datos['aduanas'] ?? [])));
        $datos['activo'] = (bool) $datos['activo'];

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function validarEncargo(Request $request): array
    {
        return $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_termino' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', Rule::in(array_keys(EncargoConferido::ESTADOS))],
            'numero_acuse' => ['nullable', 'string', 'max:80'],
            'archivo_acuse' => ['nullable', 'file', 'max:'.AgenteAduanalArchivoService::MAX_KB, $this->reglaArchivo()],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'fecha_inicio.required' => 'La fecha de inicio del encargo es obligatoria.',
            'fecha_termino.after_or_equal' => 'La fecha de término no puede ser anterior al inicio.',
            'estado.required' => 'Selecciona el estado del encargo.',
            'archivo_acuse.max' => 'El acuse supera el límite de 10 MB.',
        ]);
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

    private function descargarPrivado(string $ruta, string $nombre)
    {
        abort_unless($this->archivos->rutaSegura($ruta), 404);
        abort_unless(Storage::disk(AgenteAduanalArchivoService::DISCO)->exists($ruta), 404);

        $nombre = str_replace(['"', "\r", "\n", '\\', '/'], '', $nombre);
        if ($nombre === '') {
            $nombre = 'documento';
        }

        return Storage::disk(AgenteAduanalArchivoService::DISCO)->download($ruta, $nombre);
    }

    /**
     * @return array<string, mixed>
     */
    private function resumenAuditoria(AgenteAduanal $agente): array
    {
        return [
            'nombre' => $agente->nombre,
            'rfc' => $agente->rfc,
            'numero_patente' => $agente->numero_patente,
            'agencia' => $agente->agencia,
            'tipo_operacion' => $agente->tipo_operacion,
            'activo' => $agente->activo,
            'estado_verificacion' => $agente->estado_verificacion,
            'fecha_ultima_verificacion' => $agente->fecha_ultima_verificacion?->toDateString(),
            'fuente_verificacion' => $agente->fuente_verificacion,
            'aduanas' => $agente->relationLoaded('aduanas')
                ? $agente->aduanas->pluck('clave')->all()
                : $agente->aduanas()->pluck('clave')->all(),
        ];
    }
}
