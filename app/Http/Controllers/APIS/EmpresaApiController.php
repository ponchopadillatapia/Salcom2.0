<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DocumentoProveedor;
use App\Models\ProveedorUser;
use App\Models\SolicitudAlta;
use App\Models\SolicitudModificacionDatos;
use App\Services\AlertEngineService;
use App\Services\DocumentCrossCheckService;
use App\Services\IaService;
use Aws\Textract\TextractClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class EmpresaApiController extends Controller
{
    public function validar(Request $request)
    {
        try {
            @set_time_limit(180);
            @ini_set('max_execution_time', '180');

            $provId = session('proveedor_id');
            if ($provId) {
                $provLock = ProveedorUser::find($provId);
                // Solo bloquea mientras espera Dirección (aún no activo). Activos pueden renovar/actualizar docs.
                if ($provLock && ! $provLock->activo
                    && $provLock->onboardingEdicionBloqueada()
                    && $provLock->documentosFiscalesCompletos()) {
                    return response()->json([
                        'ok' => false,
                        'mensaje' => 'Tu expediente está en revisión o aprobado. No puedes volver a validar documentos hasta un rechazo de Dirección.',
                    ], 423);
                }
            }

            $tipoPersona = $request->input('tipo_persona', 'moral'); // moral | fisica

            // Reglas de validación dinámicas (hasta 20MB por PDF)
            $rules = [
                'cif_pdf' => 'required|mimes:pdf|max:20480',
                'opinion_pdf' => 'required|mimes:pdf|max:20480',
                'caratula_banco_pdf' => 'required|mimes:pdf|max:20480',
                'rep_legal_pdf' => 'nullable|mimes:pdf,jpg,jpeg,png|max:20480',
                'contribuyente_pdf' => 'nullable|mimes:pdf,jpg,jpeg,png|max:20480',
                'poder_pdf' => 'nullable|mimes:pdf|max:20480',
            ];

            // Acta constitutiva: para Persona Moral es obligatoria SALVO que suba poder notarial
            if ($tipoPersona === 'moral') {
                if ($request->hasFile('poder_pdf') && ! $request->hasFile('acta_pdf')) {
                    $rules['acta_pdf'] = 'nullable|mimes:pdf|max:20480';
                } else {
                    $rules['acta_pdf'] = 'nullable|mimes:pdf|max:20480';
                }
            } else {
                $rules['acta_pdf'] = 'nullable|mimes:pdf|max:20480';
            }

            $request->validate($rules);

            $parser = new Parser;

            $archivos = [
                'cif' => $request->file('cif_pdf')->store('cif', 'local'),
                'opinion' => $request->file('opinion_pdf')->store('opiniones', 'local'),
                'caratula_banco' => $request->file('caratula_banco_pdf')->store('caratula_banco', 'local'),
            ];

            // Acta: solo si se subió
            if ($request->hasFile('acta_pdf')) {
                $archivos['acta'] = $request->file('acta_pdf')->store('actas', 'local');
            }
            // Opcionales
            if ($request->hasFile('rep_legal_pdf')) {
                $archivos['rep_legal'] = $request->file('rep_legal_pdf')->store('rep_legal', 'local');
            }
            if ($request->hasFile('contribuyente_pdf')) {
                $archivos['contribuyente'] = $request->file('contribuyente_pdf')->store('contribuyente', 'local');
            }
            if ($request->hasFile('poder_pdf')) {
                $archivos['poder'] = $request->file('poder_pdf')->store('poder', 'local');
            }

            $textos = [];
            foreach ($archivos as $clave => $ruta) {
                $textos[$clave] = $this->extraerTexto($parser, storage_path('app/private/'.$ruta));
            }

            // ════════════════════════════════════════
            // CIF — Constancia de Situación Fiscal
            // ════════════════════════════════════════
            $cif = $this->validarCIF($textos['cif']);

            // Datos del formulario de identificación (para cruce)
            $nombreEsperado = trim((string) $request->input('nombre_esperado', ''));

            // ════════════════════════════════════════
            // OPINIÓN DE CUMPLIMIENTO
            // ════════════════════════════════════════
            $opinion = $this->validarOpinion($textos['opinion'], $cif['datos']['rfc']);

            // ════════════════════════════════════════
            // ACTA CONSTITUTIVA (solo Persona Moral)
            // ════════════════════════════════════════
            $acta = null;
            if (isset($textos['acta'])) {
                $nombreParaCruce = $nombreEsperado !== '' ? $nombreEsperado : ($cif['datos']['nombre'] ?? null);
                $actaPath = isset($archivos['acta']) ? storage_path('app/private/'.$archivos['acta']) : null;
                $acta = $this->validarActa($textos['acta'], $cif['datos']['es_moral'], $nombreParaCruce, $actaPath);
            } elseif ($tipoPersona === 'fisica') {
                $acta = ['valida' => true, 'datos' => [], 'errores' => [], 'hallazgos' => ['Persona Física — Acta Constitutiva no requerida']];
            }

            // ════════════════════════════════════════
            // ID REPRESENTANTE LEGAL (opcional)
            // ════════════════════════════════════════
            $repLegal = null;
            if (isset($textos['rep_legal'])) {
                $repLegal = $this->validarINE($textos['rep_legal'], 'Representante Legal');
            }

            // ════════════════════════════════════════
            // ID CONTRIBUYENTE (opcional)
            // ════════════════════════════════════════
            $contribuyente = null;
            if (isset($textos['contribuyente'])) {
                $contribuyente = $this->validarINE($textos['contribuyente'], 'Contribuyente');
            }

            // ════════════════════════════════════════
            // PODER NOTARIAL (opcional)
            // ════════════════════════════════════════
            $poder = null;
            if (isset($textos['poder'])) {
                $poder = $this->validarPoder($textos['poder']);
            }

            // ════════════════════════════════════════
            // CARÁTULA DE BANCO
            // ════════════════════════════════════════
            $banco = $this->validarCaratulaBanco($textos['caratula_banco']);

            // ════════════════════════════════════════
            // CRUCE CON FORMULARIO DE IDENTIFICACIÓN
            // ════════════════════════════════════════
            $nombreEsperado = trim((string) $request->input('nombre_esperado', ''));
            $rfcEsperado = strtoupper(trim((string) $request->input('rfc_esperado', '')));

             // Si no viene RFC del formulario, intentar desde el proveedor logueado
            if ($rfcEsperado === '' && $provId) {
                $provActual = ProveedorUser::find($provId);
                if ($provActual) {
                    // Buscar RFC en columna directa
                    $rfcProv = $provActual->rfc ?? null;
                    if (!$rfcProv) {
                        // Buscar en datos_identificacion
                        $datosId = $provActual->datos_identificacion ?? [];
                        $rfcProv = $datosId['rfc'] ?? null;
                    }
                    if ($rfcProv) {
                        $rfcEsperado = strtoupper(trim($rfcProv));
                    }
                }
            }

            $clabeEsperada = preg_replace('/\D/', '', (string) $request->input('clabe_esperada', ''));
            $cuentaEsperada = preg_replace('/\D/', '', (string) $request->input('cuenta_esperada', ''));
            $bancoEsperado = trim((string) $request->input('banco_esperado', ''));
            $cpEsperado = preg_replace('/\D/', '', (string) $request->input('cp_esperado', ''));

            if ($nombreEsperado !== '' || $clabeEsperada !== '' || $cpEsperado !== '' || $bancoEsperado !== '') {
                // Tipo de persona declarado vs CIF
                $cifEsMoral = (bool) ($cif['datos']['es_moral'] ?? false);
                $declaroMoral = $tipoPersona === 'moral';
                if ($cifEsMoral !== $declaroMoral) {
                    $esperadoLabel = $declaroMoral ? 'Persona Moral' : 'Persona Física';
                    $encontradoLabel = $cifEsMoral ? 'Persona Moral' : 'Persona Física';
                    $cif['errores'][] = "El tipo de persona del formulario ({$esperadoLabel}) no coincide con el CIF ({$encontradoLabel})";
                    $cif['valida'] = false;
                } else {
                    $cif['hallazgos'][] = 'Tipo de persona coincide con el formulario de identificación';
                }

                // RFC del formulario vs RFC del CIF
                if ($rfcEsperado !== '') {
                    $rfcCifExtraido = $cif['datos']['rfc'] ?? '';
                    if ($rfcCifExtraido !== '' && $rfcCifExtraido === $rfcEsperado) {
                        $cif['hallazgos'][] = 'RFC coincide con el formulario de identificación ✓ (' . $rfcEsperado . ')';
                    } elseif ($rfcCifExtraido !== '' && $rfcCifExtraido !== $rfcEsperado) {
                        $cif['errores'][] = "RFC del formulario ({$rfcEsperado}) NO coincide con el CIF ({$rfcCifExtraido}) — los documentos no pertenecen al proveedor registrado";
                        $cif['valida'] = false;
                    } else {
                        $cif['hallazgos'][] = 'RFC del formulario: ' . $rfcEsperado . ' (no se pudo verificar contra CIF)';
                    }
                }

                // Nombre / razón social
                if ($nombreEsperado !== '') {
                    $nombreDoc = (string) ($cif['datos']['nombre'] ?? '');
                    if ($nombreDoc === '') {
                        // No marcar error — mostrar el nombre del formulario como referencia
                        $etiqueta = $declaroMoral ? 'Razón Social' : 'Nombre';
                        $cif['hallazgos'][] = $etiqueta.' (del formulario): '.$nombreEsperado;
                    } elseif ($this->nombresCoinciden($nombreEsperado, $nombreDoc)) {
                        $cif['hallazgos'][] = 'Nombre/Razón Social coincide con el formulario de identificación ✓';
                    } else {
                        $etiqueta = $declaroMoral ? 'Razón Social' : 'Nombre';
                        $cif['errores'][] = "{$etiqueta} del formulario (\"{$nombreEsperado}\") no coincide con el CIF (\"{$nombreDoc}\")";
                        $cif['valida'] = false;
                    }
                }

                // Código postal
                if ($cpEsperado !== '' && strlen($cpEsperado) === 5) {
                    $cpDoc = (string) ($cif['datos']['codigo_postal'] ?? '');
                    if ($cpDoc !== '' && $cpDoc !== $cpEsperado) {
                        $cif['errores'][] = "C.P. del formulario ({$cpEsperado}) no coincide con el CIF ({$cpDoc})";
                        $cif['valida'] = false;
                    } elseif ($cpDoc === $cpEsperado) {
                        $cif['hallazgos'][] = 'C.P. coincide con el formulario de identificación';
                    }
                }

                // CLABE
                if ($clabeEsperada !== '' && strlen($clabeEsperada) === 18) {
                    $clabeDoc = (string) ($banco['datos']['clabe'] ?? '');
                    if ($clabeDoc === '') {
                        $banco['errores'][] = 'No se pudo verificar la CLABE del formulario (no detectada en la carátula)';
                        $banco['valida'] = false;
                    } elseif ($clabeDoc !== $clabeEsperada) {
                        $banco['errores'][] = "CLABE del formulario ({$clabeEsperada}) no coincide con la carátula ({$clabeDoc})";
                        $banco['valida'] = false;
                    } else {
                        $banco['hallazgos'][] = 'CLABE coincide con el formulario de identificación';
                    }
                }

                // Cuenta bancaria
                if ($cuentaEsperada !== '') {
                    $cuentaDoc = (string) ($banco['datos']['cuenta'] ?? '');
                    if ($cuentaDoc !== '' && ! str_ends_with($cuentaDoc, $cuentaEsperada) && ! str_ends_with($cuentaEsperada, $cuentaDoc) && $cuentaDoc !== $cuentaEsperada) {
                        $banco['errores'][] = "Cuenta del formulario ({$cuentaEsperada}) no coincide con la carátula ({$cuentaDoc})";
                        $banco['valida'] = false;
                    } elseif ($cuentaDoc !== '' && ($cuentaDoc === $cuentaEsperada || str_ends_with($cuentaDoc, $cuentaEsperada) || str_ends_with($cuentaEsperada, $cuentaDoc))) {
                        $banco['hallazgos'][] = 'Número de cuenta coincide con el formulario de identificación';
                    }
                }

                // Banco
                if ($bancoEsperado !== '') {
                    $bancoDoc = (string) ($banco['datos']['banco'] ?? '');
                    if ($bancoDoc !== '') {
                        $bancoEspNorm = $this->normalizarNombre($bancoEsperado);
                        $bancoDocNorm = $this->normalizarNombre($bancoDoc);
                        if ($bancoEspNorm === $bancoDocNorm || str_contains($bancoEspNorm, $bancoDocNorm) || str_contains($bancoDocNorm, $bancoEspNorm)) {
                            $banco['hallazgos'][] = 'Institución bancaria coincide con el formulario de identificación';
                        } else {
                            $banco['errores'][] = "Banco del formulario (\"{$bancoEsperado}\") no coincide con la carátula (\"{$bancoDoc}\")";
                            $banco['valida'] = false;
                        }
                    }
                }
            }

            // ════════════════════════════════════════
            // CRUCE ENTRE DOCUMENTOS
            // ════════════════════════════════════════

            // Cross-check CIF ↔ INE (verificar que pertenezcan al mismo proveedor)
            $crossCheckService = app(DocumentCrossCheckService::class);
            $ineParaCruce = $repLegal ?? $contribuyente; // Usar la INE que se haya subido

            if ($ineParaCruce && $cif['datos']['rfc']) {
                $nombreIne = $ineParaCruce['datos']['nombre'] ?? null;
                $curpIne = $ineParaCruce['datos']['curp'] ?? null;

                // Solo hacer cross-check si tenemos datos suficientes
                $tieneDatosSuficientes = $curpIne && strlen($curpIne) >= 16;

                if ($tieneDatosSuficientes) {
                    $crossResult = $crossCheckService->validar(
                        [
                            'rfc' => $cif['datos']['rfc'],
                            'nombre' => $cif['datos']['nombre'] ?? $nombreEsperado,
                            'codigo_postal' => $cif['datos']['codigo_postal'] ?? null,
                            'curp' => null,
                        ],
                        [
                            'curp' => $curpIne,
                            'nombre' => $nombreIne ?: $nombreEsperado,
                            'codigo_postal' => null,
                        ]
                    );

                    if ($crossResult['valido']) {
                        $ineParaCruce['hallazgos'][] = 'Cross-check CIF ↔ INE: Documentos del mismo proveedor ✓ (Score: '.$crossResult['score'].'%)';
                    } else {
                        // RFC no coincide = BLOQUEAR — es documento de otra persona
                        foreach ($crossResult['errores'] as $err) {
                            // Solo mostrar errores de RFC, no de nombre
                            if (! str_contains($err, 'Nombre NO coincide')) {
                                $ineParaCruce['errores'][] = '⚠ '.$err;
                            }
                        }
                        $ineParaCruce['valida'] = false;
                    }

                    foreach ($crossResult['alertas'] as $alerta) {
                        $ineParaCruce['hallazgos'][] = $alerta;
                    }
                }

                // Actualizar la referencia
                if ($repLegal) {
                    $repLegal = $ineParaCruce;
                } elseif ($contribuyente) {
                    $contribuyente = $ineParaCruce;
                }
            }

            // 1. INE del Representante Legal ↔ RFC del CIF
            if ($repLegal && $cif['datos']['rfc']) {
                $rfcCif = $cif['datos']['rfc'];
                // Del CURP de la INE se pueden extraer las letras del RFC
                if ($repLegal['datos']['curp'] && strlen($repLegal['datos']['curp']) >= 10) {
                    $curp = $repLegal['datos']['curp'];
                    // Las primeras 4 letras del CURP coinciden con las primeras 4 del RFC (persona física)
                    // Para persona moral el RFC tiene 3 letras, no aplica directamente
                    if (! $cif['datos']['es_moral']) {
                        $rfcInicio = substr($rfcCif, 0, 10); // XXXX######
                        $curpInicio = substr($curp, 0, 10);
                        if ($rfcInicio === $curpInicio) {
                            $repLegal['hallazgos'][] = 'CURP coincide con RFC del CIF ✓';
                        } else {
                            $repLegal['hallazgos'][] = 'CURP/RFC: verificar que corresponda al titular';
                        }
                    }
                }
            }

            // 2. Nombre del Representante Legal ↔ Acta Constitutiva
            if ($repLegal && $acta && ! empty($repLegal['datos']['nombre']) && ! empty($acta['datos']['nombre_acta'])) {
                $nombreIne = $repLegal['datos']['nombre'];
                $textoActa = $textos['acta'] ?? '';
                $textoActaUpper = strtoupper($textoActa);
                // Verificar que el nombre del rep legal aparezca en el acta
                $nombreIneUpper = strtoupper($nombreIne);
                $palabrasNombre = array_filter(explode(' ', $nombreIneUpper), fn ($p) => strlen($p) > 2);
                $coincidencias = 0;
                foreach ($palabrasNombre as $palabra) {
                    if (str_contains($textoActaUpper, $palabra)) {
                        $coincidencias++;
                    }
                }
                if (count($palabrasNombre) > 0) {
                    $pctCoincidencia = $coincidencias / count($palabrasNombre);
                    if ($pctCoincidencia >= 0.6) {
                        $repLegal['hallazgos'][] = 'Nombre del representante legal aparece en el Acta Constitutiva ✓';
                    } else {
                        $repLegal['hallazgos'][] = 'Nombre del representante legal no detectado en el Acta (verificar manualmente)';
                    }
                }
            }

            // ════════════════════════════════════════
            // VALIDACIÓN DE SEGURIDAD (Anti-fraude)
            // ════════════════════════════════════════
            $proveedorActual = $provId ? ProveedorUser::find($provId) : null;
            $alertasSeguridad = [];

            if ($proveedorActual) {
                $rfcProveedor = null;
                // Obtener RFC del proveedor desde su datos_identificacion o perfil
                $datosIdent = $proveedorActual->datos_identificacion ?? [];
                if (is_array($datosIdent)) {
                    // Intentar extraer RFC del nombre del proveedor
                }

                // 1. Verificar que el RFC del CIF corresponde al proveedor
                $rfcCifExtraido = $cif['datos']['rfc'] ?? null;
                $rfcOpinionExtraido = $opinion['datos']['rfc_encontrado'] ?? null;

                // Si ambos RFCs se extrajeron, deben ser iguales
                if ($rfcCifExtraido && $rfcOpinionExtraido && $rfcCifExtraido !== $rfcOpinionExtraido) {
                    $alertasSeguridad[] = "RFC del CIF ({$rfcCifExtraido}) no coincide con RFC de la Opinión SAT ({$rfcOpinionExtraido})";
                    $cif['errores'][] = '⚠ ALERTA: El RFC del CIF no coincide con la Opinión SAT — posible documento de otro proveedor';
                    $cif['valida'] = false;
                }

                // 2. Verificar que el nombre del formulario coincide con los documentos
                if ($nombreEsperado !== '' && $rfcCifExtraido) {
                    // Si el nombre del CIF se extrajo y NO coincide con el del formulario, alertar
                    $nombreCif = $cif['datos']['nombre'] ?? '';
                    if ($nombreCif !== '' && ! $this->nombresCoinciden($nombreEsperado, $nombreCif)) {
                        $alertasSeguridad[] = "Nombre del formulario ({$nombreEsperado}) no coincide con el CIF ({$nombreCif})";
                    }
                }

                // 3. Si hay alertas de seguridad, registrar en log de auditoría
                if (! empty($alertasSeguridad)) {
                    Log::warning('ALERTA SEGURIDAD: Posible inconsistencia en validación fiscal', [
                        'proveedor_id' => $provId,
                        'proveedor_nombre' => $proveedorActual->nombre ?? $proveedorActual->usuario,
                        'rfc_cif' => $rfcCifExtraido,
                        'rfc_opinion' => $rfcOpinionExtraido,
                        'nombre_esperado' => $nombreEsperado,
                        'alertas' => $alertasSeguridad,
                        'ip' => $request->ip(),
                        'timestamp' => now()->toDateTimeString(),
                    ]);

                    // Registrar en tabla de auditoría si existe
                    try {
                        AuditLog::create([
                            'accion' => 'validacion_fiscal_alerta',
                            'usuario_tipo' => 'proveedor',
                            'usuario_id' => $provId,
                            'descripcion' => 'Alerta de seguridad: '.implode(' | ', $alertasSeguridad),
                            'datos' => json_encode([
                                'rfc_cif' => $rfcCifExtraido,
                                'rfc_opinion' => $rfcOpinionExtraido,
                                'nombre_esperado' => $nombreEsperado,
                                'ip' => $request->ip(),
                            ]),
                        ]);
                    } catch (\Exception $e) {
                        // Tabla de auditoría puede no existir
                    }
                }
            }

            // 4. Sanitización extra: verificar formatos reales (magic bytes)
            $camposQueAceptanImagen = ['rep_legal_pdf', 'contribuyente_pdf'];
            $archivosSubidos = ['cif_pdf', 'opinion_pdf', 'caratula_banco_pdf', 'acta_pdf', 'rep_legal_pdf', 'contribuyente_pdf', 'poder_pdf'];
            foreach ($archivosSubidos as $campo) {
                if ($request->hasFile($campo)) {
                    $file = $request->file($campo);
                    $contenido = file_get_contents($file->getRealPath(), false, null, 0, 8);
                    $esPdf = str_starts_with($contenido, '%PDF-');
                    $esJpg = str_starts_with($contenido, "\xFF\xD8\xFF");
                    $esPng = str_starts_with($contenido, "\x89PNG");

                    $formatoValido = $esPdf;
                    if (in_array($campo, $camposQueAceptanImagen)) {
                        $formatoValido = $esPdf || $esJpg || $esPng;
                    }

                    if (! $formatoValido) {
                        Log::warning('ALERTA SEGURIDAD: Archivo con formato no válido', [
                            'campo' => $campo,
                            'proveedor_id' => $provId,
                            'mime' => $file->getMimeType(),
                            'ip' => $request->ip(),
                        ]);

                        return response()->json([
                            'mensaje' => "El archivo {$campo} no tiene un formato válido.",
                        ], 422);
                    }
                }
            }

            // ════════════════════════════════════════
            // SEMÁFORO
            // ════════════════════════════════════════
            $cifOk = $cif['valida'];
            $opOk = $opinion['valida'];
            $actaOk = $acta ? $acta['valida'] : true;
            $repOk = $repLegal ? $repLegal['valida'] : true;
            $contOk = $contribuyente ? $contribuyente['valida'] : true;
            $poderOk = $poder ? $poder['valida'] : true;
            $bancoOk = $banco['valida'];

            $todoOk = $cifOk && $opOk && $actaOk && $repOk && $contOk && $poderOk && $bancoOk;

            if ($todoOk) {
                $estado = 'verde';
            } elseif ($cifOk && $opOk) {
                $estado = 'amarillo';
            } else {
                $estado = 'rojo';
            }

            // ════════════════════════════════════════
            // VALIDACIÓN CRUZADA CON IA — se ejecuta después
            // ════════════════════════════════════════

            // Guardar copias en expediente fiscal — SOLO si validación correcta
            if ($todoOk) {
                $proveedorId = session('proveedor_id');
                if ($proveedorId) {
                    try {
                        $resultadosPorTipo = [
                            'cif' => $cif,
                            'opinion' => $opinion,
                            'acta' => $acta,
                            'rep_legal' => $repLegal,
                            'contribuyente' => $contribuyente,
                            'poder' => $poder,
                            'caratula_banco' => $banco,
                        ];
                        $tiposGuardar = ['cif', 'opinion', 'acta', 'rep_legal', 'contribuyente', 'poder', 'caratula_banco'];
                        foreach ($tiposGuardar as $tipo) {
                            if ($request->hasFile($tipo.'_pdf')) {
                                $rutaPublica = $request->file($tipo.'_pdf')->store("expediente_fiscal/{$tipo}", 'public');
                                if ($rutaPublica) {
                                    $res = $resultadosPorTipo[$tipo] ?? null;
                                    DocumentoProveedor::updateOrCreate(
                                        ['proveedor_id' => $proveedorId, 'tipo' => $tipo],
                                        [
                                            'archivo' => $rutaPublica,
                                            'estatus' => 'aprobado',
                                            'notas_revision' => 'Validación automática aprobada',
                                            'resultado_validacion' => is_array($res) ? $res : null,
                                            'revisado_at' => now(),
                                        ]
                                    );
                                }
                            }
                        }

                        // Tras validación en verde, la solicitud queda visible para Dirección.
                        $prov = ProveedorUser::find($proveedorId);
                        if ($prov && ! $prov->activo) {
                            $updateProv = [];
                            if (Schema::hasColumn('proveedores_users', 'solicitud_alta_estatus')) {
                                $updateProv['solicitud_alta_estatus'] = 'pendiente';
                            }
                            if ($updateProv !== []) {
                                $prov->update($updateProv);
                            }
                            try {
                                SolicitudAlta::updateOrCreate(
                                    ['proveedor_id' => $proveedorId],
                                    [
                                        'estatus' => 'pendiente',
                                        'tipo_persona' => $prov->tipo_persona ?? 'Persona Moral',
                                        'nombre_completo' => $prov->nombre ?? $prov->usuario,
                                    ]
                                );
                            } catch (\Exception $e) {
                                // ignore
                            }
                            Log::info('Solicitud de alta lista para admin tras validación fiscal', [
                                'proveedor_id' => $proveedorId,
                                'docs_aprobados' => DocumentoProveedor::where('proveedor_id', $proveedorId)->where('estatus', 'aprobado')->count(),
                            ]);
                        }

                        // Proveedor activo renovó/actualizó docs → petición al admin.
                        if ($prov && $prov->activo) {
                            try {
                                $nombreCif = trim((string) data_get($cif, 'datos.nombre', ''));
                                SolicitudModificacionDatos::create([
                                    'proveedor_id' => $proveedorId,
                                    'campo' => 'documentos_fiscales',
                                    'valor_actual' => $prov->nombre,
                                    'valor_propuesto' => $nombreCif !== '' ? $nombreCif : $prov->nombre,
                                    'tipo_persona' => $prov->tipoPersonaNormalizado(),
                                    'motivo' => 'Actualización / renovación de documentos fiscales (ciclo 21 días o cambio solicitado).',
                                    'estatus' => 'pendiente',
                                    'resultado_ia' => [
                                        'estado_validacion' => 'verde',
                                        'origen' => 'validacion_fiscal',
                                    ],
                                    'notas' => 'El proveedor subió y validó documentos. Revisar si hay cambio de nombre/razón social u otros datos.',
                                ]);
                            } catch (\Throwable $e) {
                                Log::warning('No se pudo registrar solicitud de actualización de docs', [
                                    'proveedor_id' => $proveedorId,
                                    'error' => $e->getMessage(),
                                ]);
                            }

                            try {
                                app(AlertEngineService::class)->alertar([
                                    'tipo' => 'actualizacion_documentos',
                                    'modulo' => 'fiscal',
                                    'destinatario_tipo' => 'admin',
                                    'destinatario_id' => 1,
                                    'titulo' => 'Actualización de docs: '.($prov->nombre ?? $prov->usuario),
                                    'contenido' => 'El proveedor '.($prov->nombre ?? $prov->usuario).' subió documentación fiscal para actualizar/renovar. Revisa la solicitud en el panel.',
                                    'datos' => [
                                        'proveedor_id' => $proveedorId,
                                        'proveedor_nombre' => $prov->nombre,
                                    ],
                                    'nivel' => 'info',
                                ]);
                            } catch (\Throwable $e) {
                                Log::warning('No se pudo alertar admin por actualización de docs', [
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('No se pudieron guardar docs/expediente tras validación', [
                            'proveedor_id' => $proveedorId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $response = [
                'estado' => $estado,
                'tipo_persona' => $tipoPersona,
                'cif' => $cif,
                'opinion' => $opinion,
                'caratula_banco' => $banco,
            ];

            if ($acta) {
                $response['acta'] = $acta;
            }
            if ($repLegal) {
                $response['rep_legal'] = $repLegal;
            }
            if ($contribuyente) {
                $response['contribuyente'] = $contribuyente;
            }
            if ($poder) {
                $response['poder'] = $poder;
            }

            // ════════════════════════════════════════
            // VALIDACIÓN CRUZADA CON IA (Compliance)
            // ════════════════════════════════════════
            if (! empty(config('services.ia.aws_access_key')) && strlen($textos['cif']) > 50) {
                try {
                    $iaService = app(IaService::class);

                    $resumenDocs = "DATOS DEL FORMULARIO BANCARIO:\n".
                        '- Nombre/Razón Social declarado: '.($nombreEsperado ?: 'No proporcionado')."\n".
                        '- CLABE: '.($request->input('clabe_esperada', '') ?: 'No proporcionada')."\n".
                        '- Banco: '.($request->input('banco_esperado', '') ?: 'No proporcionado')."\n\n".
                        "DATOS EXTRAÍDOS DEL CIF:\n".
                        '- RFC: '.($cif['datos']['rfc'] ?? 'No detectado')."\n".
                        '- Tipo persona: '.($cif['datos']['tipo_persona'] ?? 'No detectado')."\n\n".
                        "DATOS DE LA OPINIÓN SAT:\n".
                        '- Sentido: '.($opinion['datos']['sentido'] ?? 'No detectado')."\n".
                        '- RFC en opinión: '.($opinion['datos']['rfc_encontrado'] ?? 'No detectado')."\n\n".
                        "DATOS DE LA CARÁTULA BANCARIA:\n".
                        '- Banco detectado: '.($banco['datos']['banco'] ?? 'No detectado')."\n".
                        '- CLABE: '.($banco['datos']['clabe'] ?? 'No detectada')."\n".
                        '- Titular: '.($banco['datos']['titular'] ?? 'No detectado')."\n\n";

                    if ($acta && ! empty($acta['datos']['nombre_acta'])) {
                        $resumenDocs .= "DATOS DEL ACTA CONSTITUTIVA:\n".
                            '- Razón Social: '.$acta['datos']['nombre_acta']."\n".
                            '- Tipo sociedad: '.($acta['datos']['tipo_sociedad'] ?? 'No detectado')."\n\n";
                    }

                    $resultadoCruce = $iaService->llamarClaude(
                        "Actúa como un experto en auditoría financiera, legal y validación (Compliance) de proveedores en México.\n\n".
                        "Tarea: Analiza la información extraída de los documentos y realiza validaciones cruzadas.\n\n".
                        "INFORMACIÓN:\n{$resumenDocs}\n".
                        "Reglas:\n".
                        "1. Si es Persona Moral: verifica que el nombre en la cuenta bancaria coincida con la razón social del acta y del CIF.\n".
                        "2. Verifica que el RFC del CIF coincida con el de la Opinión SAT.\n".
                        "3. Verifica que la CLABE del formulario coincida con la de la carátula.\n".
                        "4. Verifica que el banco declarado coincida con el detectado.\n\n".
                        "Responde ÚNICAMENTE con JSON (sin markdown):\n".
                        '{"nombre_coincide":true/false,"rfc_coincide":true/false,"clabe_coincide":true/false,"banco_coincide":true/false,"alertas":["alertas"],"resumen":"resumen"}'
                    );

                    if ($resultadoCruce['success'] && $resultadoCruce['content']) {
                        if (preg_match('/\{.*\}/s', $resultadoCruce['content'], $jsonCruce)) {
                            $cruceData = json_decode($jsonCruce[0], true);
                            if ($cruceData) {
                                $response['validacion_cruzada'] = $cruceData;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // IA no disponible para cruce
                }
            }

            return response()->json($response);

        } catch (ValidationException $e) {
            $errores = collect($e->errors())->flatten()->implode(' | ');

            return response()->json(['mensaje' => 'Archivo inválido — solo se aceptan documentos PDF: '.$errores], 422);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error interno: '.$e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────
    // VALIDADORES POR DOCUMENTO
    // ──────────────────────────────────────────────────

    private function validarCIF(string $texto): array
    {
        $datos = [
            'rfc' => null,
            'nombre' => null,
            'fecha_nacimiento' => null,
            'tipo_persona' => null,
            'es_moral' => false,
            'regimen' => null,
            'domicilio_fiscal' => null,
            'codigo_postal' => null,
            'fecha_inicio' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = []; // lo que SÍ encontró

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Normalizar texto para mejorar detección OCR
        $textoNorm = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú'],
            ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u'],
            $texto
        );
        $textoUpper = strtoupper($textoNorm);

        // ¿Es realmente un CIF?
        if (str_contains($textoUpper, 'CONSTANCIA DE SITUACION FISCAL')
            || str_contains($textoUpper, 'CONSTANCIA')
            || str_contains($textoUpper, 'SITUACION FISCAL')
            || str_contains($textoUpper, 'CEDULA DE IDENTIFICACION FISCAL')) {
            $hallazgos[] = 'Documento identificado como Constancia de Situación Fiscal';
        } else {
            $errores[] = 'No es una Constancia de Situación Fiscal del SAT';
        }

        // Sello SAT — buscar variaciones del OCR
        if (str_contains($textoUpper, 'SERVICIO DE ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'SAT')
            || str_contains($textoUpper, 'SHCP')
            || str_contains($textoUpper, 'HACIENDA')) {
            $hallazgos[] = 'Sello del SAT detectado';
        } else {
            $hallazgos[] = 'Sello SAT — no detectado por OCR';
        }

        // RFC — buscar con patrones más flexibles
        if (preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $textoUpper, $m)) {
            $datos['rfc'] = $m[1];
            $hallazgos[] = 'RFC encontrado: '.$m[1];
        } elseif (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $textoUpper, $m)) {
            $datos['rfc'] = $m[1];
            $hallazgos[] = 'RFC encontrado: '.$m[1];
        } else {
            $errores[] = 'No se encontró RFC';
        }

        // Tipo persona
        $esMoral = str_contains($texto, 'PERSONA MORAL')
                || str_contains($texto, 'SOCIEDAD')
                || str_contains($texto, 'S.A')
                || str_contains($texto, 'S DE RL')
                || str_contains($texto, 'S.A.S');
        $datos['es_moral'] = $esMoral;
        $datos['tipo_persona'] = $esMoral ? 'Persona Moral' : 'Persona Física';
        $hallazgos[] = 'Tipo: '.$datos['tipo_persona'];

        // Nombre / Razón social — no se extrae del CIF por OCR poco confiable
        $datos['nombre'] = null;

        // Para Persona Física: extraer nombre desde el texto del CIF
        if (! $esMoral) {
            $datos['nombre'] = $this->extraerNombreCifPersonaFisica($textoUpper);
            if ($datos['nombre']) {
                $hallazgos[] = 'Nombre: '.$datos['nombre'];
            }
        }

        // Fecha de nacimiento (solo Persona Física)
        if (! $esMoral) {
            if (preg_match('/FECHA\s*(?:DE\s*)?NACIMIENTO[:\s]*([\d\/\-]+)/', $texto, $fn)) {
                $datos['fecha_nacimiento'] = $fn[1];
                $hallazgos[] = 'Fecha de nacimiento: '.$fn[1];
            } elseif ($datos['rfc'] && strlen($datos['rfc']) === 13) {
                // Extraer fecha del RFC (posiciones 5-10: AAMMDD)
                $rfcFecha = substr($datos['rfc'], 4, 6);
                if (preg_match('/(\d{2})(\d{2})(\d{2})/', $rfcFecha, $rfcF)) {
                    $anio = (int) $rfcF[1] > 50 ? '19'.$rfcF[1] : '20'.$rfcF[1];
                    $datos['fecha_nacimiento'] = $rfcF[3].'/'.$rfcF[2].'/'.$anio;
                    $hallazgos[] = 'Fecha de nacimiento (del RFC): '.$datos['fecha_nacimiento'];
                }
            }
        }

        // Régimen fiscal — buscar con variaciones del OCR
        if (preg_match('/REGIMEN[:\s]*([A-ZÁÉÍÓÚÑ\s,\.]+?)(?=FECHA|DOMICILIO|OBLIGACIONES|CODIGO|\d{2}\/)/u', $texto, $reg)) {
            $regimenRaw = trim($reg[1]);
            // Limpiar etiquetas
            $corteReg = ['FECHA', 'INICIO', 'DOMICILIO', 'OBLIGACIONES', 'CODIGO'];
            foreach ($corteReg as $pc) {
                $pos = strpos($regimenRaw, $pc);
                if ($pos !== false) {
                    $regimenRaw = trim(substr($regimenRaw, 0, $pos));
                }
            }
            if (strlen($regimenRaw) > 3) {
                $datos['regimen'] = $regimenRaw;
                $hallazgos[] = 'Régimen: '.$regimenRaw;
            } else {
                $hallazgos[] = 'Se detectó mención de Régimen Fiscal';
            }
        } elseif (str_contains($textoUpper, 'REGIMEN') || str_contains($textoUpper, 'REGIMEN FISCAL')
                || str_contains($textoUpper, 'GENERAL DE LEY') || str_contains($textoUpper, 'ACTIVIDADES EMPRESARIALES')
                || str_contains($textoUpper, 'INCORPORACION FISCAL') || str_contains($textoUpper, 'RESICO')
                || str_contains($textoUpper, 'SIMPLIFICADO DE CONFIANZA')) {
            $hallazgos[] = 'Régimen Fiscal detectado';
        } else {
            $hallazgos[] = 'Régimen Fiscal — no detectado por OCR';
        }

        // Domicilio fiscal — buscar con variaciones
        if (str_contains($textoUpper, 'DOMICILIO FISCAL')
            || str_contains($textoUpper, 'DOMICILIO')
            || str_contains($textoUpper, 'CALLE')
            || str_contains($textoUpper, 'COLONIA')
            || str_contains($textoUpper, 'MUNICIPIO')
            || str_contains($textoUpper, 'ENTIDAD')
            || preg_match('/C\.?P\.?\s*\d{5}/', $textoUpper)) {
            $hallazgos[] = 'Domicilio Fiscal detectado';
        } else {
            $hallazgos[] = 'Domicilio Fiscal — no detectado por OCR';
        }

        // Código postal
        if (preg_match('/CODIGO POSTAL[:\s]*(\d{5})/', $texto, $cp)) {
            $datos['codigo_postal'] = $cp[1];
            $hallazgos[] = 'C.P.: '.$cp[1];
        } elseif (preg_match('/C\.?P\.?[:\s]*(\d{5})/', $texto, $cp)) {
            $datos['codigo_postal'] = $cp[1];
            $hallazgos[] = 'C.P.: '.$cp[1];
        }

        // Fecha inicio operaciones
        if (preg_match('/FECHA\s*(?:DE\s*)?INICIO\s*(?:DE\s*)?OPERACIONES[:\s]*([\d\/\-]+)/', $texto, $fi)) {
            $datos['fecha_inicio'] = $fi[1];
            $hallazgos[] = 'Inicio operaciones: '.$fi[1];
        }

        // RFC válido formato
        $rfcValido = $this->validarRFC($datos['rfc']);
        if ($datos['rfc'] && ! $rfcValido) {
            $errores[] = 'El RFC "'.$datos['rfc'].'" no tiene formato válido';
        }

        return [
            'valida' => empty($errores),
            'datos' => $datos,
            'errores' => $errores,
            'hallazgos' => $hallazgos,
        ];
    }

    private function validarOpinion(string $texto, ?string $rfcCif): array
    {
        $datos = [
            'rfc_encontrado' => null,
            'sentido' => null,
            'fecha' => null,
            'articulo' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Normalizar texto para mejorar detección (quitar acentos comunes en OCR)
        $textoNorm = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú'],
            ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u'],
            $texto
        );
        $textoUpper = strtoupper($textoNorm);

        // Identificar documento
        if (str_contains($textoUpper, 'OPINION') && str_contains($textoUpper, 'CUMPLIMIENTO')) {
            $hallazgos[] = 'Documento identificado como Opinión de Cumplimiento';
        } elseif (str_contains($textoUpper, 'OPINION') || str_contains($textoUpper, 'CUMPLIMIENTO') || str_contains($textoUpper, '32-D')) {
            $hallazgos[] = 'Documento identificado como Opinión de Cumplimiento';
        } else {
            $errores[] = 'No parece ser una Opinión de Cumplimiento del SAT';
        }

        // Sello SAT — buscar variaciones del OCR
        if (str_contains($textoUpper, 'SERVICIO DE ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'SERVICIO DE ADMINISTRACION')
            || str_contains($textoUpper, 'SAT')
            || str_contains($textoUpper, 'ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'SHCP')) {
            $hallazgos[] = 'Sello del SAT detectado';
        } else {
            $hallazgos[] = 'Sello SAT — no detectado por OCR';
        }

        // RFC en la opinión — buscar con patrones más flexibles
        $rfcEncontrado = null;
        if (preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $textoUpper, $rfcOp)) {
            $rfcEncontrado = $rfcOp[1];
        } elseif (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $textoUpper, $rfcOp)) {
            $rfcEncontrado = $rfcOp[1];
        } elseif ($rfcCif && str_contains($textoUpper, $rfcCif)) {
            $rfcEncontrado = $rfcCif;
        }

        if ($rfcEncontrado) {
            $datos['rfc_encontrado'] = $rfcEncontrado;
            $hallazgos[] = 'RFC: '.$rfcEncontrado;
        } else {
            $hallazgos[] = 'RFC — no detectado por OCR';
        }

        // Sentido (POSITIVA / NEGATIVA) — esto es lo más importante
        if (str_contains($textoUpper, 'POSITIV')) {
            $datos['sentido'] = 'POSITIVA';
            $hallazgos[] = 'Opinión: POSITIVA ✓';

            // Verificar mes en curso
            $mesActual = strtoupper($this->mesEnEspanol((int) date('n')));
            $anioActual = date('Y');
            if (str_contains($textoUpper, $mesActual) && str_contains($textoUpper, $anioActual)) {
                $hallazgos[] = 'Corresponde al mes en curso: '.$mesActual.' '.$anioActual;
            } else {
                $errores[] = 'No corresponde al mes en curso ('.$mesActual.' '.$anioActual.')';
            }

            $hallazgos[] = 'Sin observaciones pendientes';

            return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];

        } elseif (str_contains($textoUpper, 'NEGATIV')) {
            $datos['sentido'] = 'NEGATIVA';
            $errores[] = 'La opinión es NEGATIVA';

            // Cuando es negativa, mostrar qué puede estar pendiente
            if (str_contains($textoUpper, 'DECLARACION') || str_contains($textoUpper, 'DECLARACIONES')) {
                $errores[] = 'Posibles declaraciones pendientes de presentar';
            }
            if (str_contains($textoUpper, 'ADEUDO') || str_contains($textoUpper, 'CREDITO FISCAL') || str_contains($textoUpper, 'CREDITOS FISCALES')) {
                $errores[] = 'Se detectan adeudos o créditos fiscales';
            }
            if (str_contains($textoUpper, 'REQUERIMIENTO')) {
                $errores[] = 'Tiene requerimientos pendientes';
            }
            if (str_contains($textoUpper, '69-B') || str_contains($textoUpper, '69B') || str_contains($textoUpper, 'LISTA NEGRA')) {
                $errores[] = 'Posible inclusión en listas negras (Art. 69-B)';
            }

            // Si no se detectó nada específico, indicar revisión manual
            if (count($errores) === 1) {
                $errores[] = 'Revisar manualmente el motivo de la opinión negativa';
            }

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];

        } else {
            $errores[] = 'No se detectó si la opinión es Positiva o Negativa — revisar manualmente';
        }

        // PHPStan: en la práctica $errores puede quedar vacío en caminos futuros;
        // usamos count para no pelear con el análisis de uniones de literales.
        $esValida = count($errores) === 0;

        return ['valida' => $esValida, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    private function validarActa(string $texto, bool $esMoral, ?string $nombreEsperado = null, ?string $archivoPath = null): array
    {
        $datos = [
            'notario' => null,
            'escritura' => null,
            'tipo_sociedad' => null,
            'nombre_acta' => null,
            'duracion' => null,
            'registro_publico' => false,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (! $esMoral) {
            $hallazgos[] = 'Persona Física — Acta Constitutiva no requerida';

            return ['valida' => true, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        if (strlen($texto) < 20) {
            // PDF escaneado — intentar OCR con AWS Textract directamente
            if (! empty(config('services.ia.aws_access_key')) && $archivoPath && file_exists($archivoPath)) {
                try {
                    $textoTextract = $this->ocrConTextract($archivoPath);
                    if (strlen($textoTextract) > 50) {
                        $texto = $textoTextract;
                        $hallazgos[] = 'Texto extraído con AWS Textract (OCR en la nube)';
                    }
                } catch (\Exception $e) {
                }
            }

            // Si aún no hay texto suficiente
            if (strlen($texto) < 20) {
                $hallazgos[] = 'PDF escaneado — no se pudo extraer texto';
                $errores[] = 'Documento escaneado sin texto extraíble — sube un PDF con texto seleccionable';

                return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
            }
        }

        // Normalizar texto
        $textoNorm = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú'],
            ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u'],
            $texto
        );
        $textoUpper = strtoupper($textoNorm);

        // ── Escritura Pública ──
        if (preg_match('/ESCRITURA\s*(?:PUBLICA\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)/u', $textoUpper, $esc)) {
            $datos['escritura'] = $esc[1];
            $hallazgos[] = 'Escritura Pública No. '.$esc[1];
        } elseif (str_contains($textoUpper, 'ESCRITURA')) {
            $hallazgos[] = 'Se menciona Escritura Pública';
        }

        // ── Notario ──
        if (preg_match('/NOTARI[OA]\s*(?:PUBLICO\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)?/u', $textoUpper, $not)) {
            $datos['notario'] = isset($not[1]) ? 'Notaría #'.$not[1] : 'Sí';
            $hallazgos[] = 'Notario Público: '.($not[1] ?? 'detectado');
        } elseif (str_contains($textoUpper, 'NOTARI') || str_contains($textoUpper, 'FEDATARIO')) {
            $hallazgos[] = 'Se menciona Notario/Fedatario Público';
        } else {
            $errores[] = 'No se encontró referencia al Notario Público';
        }

        // ── Tipo sociedad ──
        $sociedades = ['S.A. DE C.V.', 'SA DE CV', 'S.A.S.', 'SAS', 'S. DE R.L.', 'S DE RL', 'S.A.', 'S.C.', 'A.C.', 'S.A.P.I.'];
        foreach ($sociedades as $s) {
            if (str_contains($textoUpper, $s) || str_contains($textoUpper, str_replace(['.', ' '], '', $s))) {
                $datos['tipo_sociedad'] = $s;
                $hallazgos[] = 'Tipo de sociedad: '.$s;
                break;
            }
        }
        if (! $datos['tipo_sociedad']) {
            if (str_contains($textoUpper, 'SOCIEDAD')) {
                $hallazgos[] = 'Se menciona Sociedad';
            }
        }

        // ── Nombre / Razón Social en el Acta ──
        $nombreActa = null;
        if (preg_match('/(?:DENOMINACION|RAZON\s*SOCIAL|DENOMINARA)[:\s]+[""]?([^"""\n]+?)[""]?(?=\s*(?:CON|QUE|SOCIEDAD|S\.?A|DOMICILIO|,))/ui', $texto, $nmActa)) {
            $nombreActa = trim($nmActa[1]);
        } elseif (preg_match('/(?:CONSTITUIR|CONSTITUYE|CONSTITUCION DE)\s+(?:LA\s+)?(?:SOCIEDAD\s+)?[""]?([^"""\n]{5,80})[""]?/ui', $texto, $nmActa)) {
            $nombreActa = trim($nmActa[1]);
        }

        if ($nombreActa) {
            $datos['nombre_acta'] = $nombreActa;
            $hallazgos[] = 'Razón Social en Acta: '.$nombreActa;

            // Cruce con nombre esperado
            if (is_string($nombreEsperado) && trim($nombreEsperado) !== '') {
                if ($this->nombresCoinciden($nombreEsperado, $nombreActa)) {
                    $hallazgos[] = 'Nombre coincide con el registro del proveedor ✓';
                } else {
                    $errores[] = "El nombre en el Acta (\"{$nombreActa}\") no coincide con el registro (\"{$nombreEsperado}\")";
                }
            }
        } else {
            $hallazgos[] = 'Nombre de la sociedad pendiente de validación por IA';
        }

        // ── Sello del Registro Público de la Propiedad y del Comercio ──
        if (str_contains($textoUpper, 'REGISTRO PUBLICO')
            || str_contains($textoUpper, 'REGISTRO PUBLICO DE LA PROPIEDAD')
            || str_contains($textoUpper, 'REGISTRO PUBLICO DE COMERCIO')
            || str_contains($textoUpper, 'BOLETA DE INSCRIPCION')
            || str_contains($textoUpper, 'INSCRIPCION')
            || str_contains($textoUpper, 'FOLIO MERCANTIL')
            || str_contains($textoUpper, 'FOLIO ELECTRONICO')) {
            $datos['registro_publico'] = true;
            $hallazgos[] = 'Sello/Inscripción del Registro Público detectado ✓';
        } else {
            // No marcar como error bloqueante — la IA puede detectarlo después
            $hallazgos[] = 'Sello RPPC: pendiente de confirmación por IA';
        }

        // ── Duración de la sociedad ──
        if (preg_match('/DURACION[:\s]*(\d+)\s*(?:ANOS|AÑOS|A.OS)/ui', $textoUpper, $durM)) {
            $datos['duracion'] = (int) $durM[1];
            $hallazgos[] = 'Duración de la sociedad: '.$durM[1].' años';
        } elseif (preg_match('/(\d+)\s*(?:ANOS|AÑOS|A.OS)\s*(?:DE\s*)?DURACION/ui', $textoUpper, $durM)) {
            $datos['duracion'] = (int) $durM[1];
            $hallazgos[] = 'Duración de la sociedad: '.$durM[1].' años';
        } elseif (str_contains($textoUpper, 'DURACION') || str_contains($textoUpper, 'INDEFINID')) {
            $datos['duracion'] = 99;
            $hallazgos[] = 'Duración: indefinida o mencionada';
        } else {
            $hallazgos[] = 'Cláusula de duración pendiente de validación por IA';
        }

        // ── Constitución ──
        if (str_contains($textoUpper, 'CONSTITUCI') || str_contains($textoUpper, 'CONSTITUTIVA')) {
            $hallazgos[] = 'Contiene cláusula de Constitución';
        } else {
            $errores[] = 'No se encontró referencia a Constitución de la sociedad';
        }

        // ── Análisis con IA (si hay credenciales y texto suficiente) ──
        if (strlen($texto) > 50 && ! empty(config('services.ia.aws_access_key'))) {
            try {
                $iaService = app(IaService::class);
                $fragmento = substr($texto, 0, 10000); // Enviar máximo texto posible
                $resultado = $iaService->llamarClaude(
                    "Actúa como un experto legal especializado en derecho corporativo mexicano y análisis de documentos notariales.\n\n".
                    "Tarea: Analiza el texto proporcionado del documento adjunto (Acta Constitutiva) y extrae de manera exacta los siguientes puntos clave.\n\n".
                    "Reglas de extracción:\n".
                    "- No dejes campos \"pendientes de validación\". Si el dato está en el texto, extráelo. Si el dato definitivamente no existe en el texto, tu valor debe ser estrictamente null.\n".
                    "- No incluyas explicaciones largas, solo el dato concreto.\n\n".
                    "TEXTO DEL DOCUMENTO:\n{$fragmento}\n\n".
                    "Responde ÚNICAMENTE con este JSON (sin markdown, sin explicaciones). USA null (sin comillas) para datos no encontrados, NO copies el texto de ejemplo:\n".
                    "{\n".
                    "  \"razon_social\": null,\n".
                    "  \"tipo_sociedad\": null,\n".
                    "  \"notario_publico\": null,\n".
                    "  \"escritura\": null,\n".
                    "  \"duracion_anos\": null,\n".
                    "  \"representante_legal\": null,\n".
                    "  \"es_acta_constitutiva\": false,\n".
                    "  \"sello_rppc\": false,\n".
                    "  \"folio_mercantil\": null,\n".
                    "  \"objeto_social\": null\n".
                    "}\n\n".
                    "IMPORTANTE: Reemplaza null con el valor REAL extraído del texto. Ejemplos:\n".
                    "- razon_social: \"Industrias Salcom S.A. de C.V.\" (el nombre exacto que aparece)\n".
                    "- sello_rppc: true (si menciona Registro Público, folio mercantil, inscripción, o boleta)\n".
                    "- es_acta_constitutiva: true (si el documento habla de constitución de sociedad)\n".
                    "- duracion_anos: \"99\" o \"Indefinida\"\n"
                );

                if ($resultado['success'] && $resultado['content']) {
                    $contenido = $resultado['content'];
                    if (preg_match('/\{[^{}]*\}/s', $contenido, $jsonMatch)) {
                        $iaData = json_decode($jsonMatch[0], true);
                        if ($iaData) {
                            // Helper: verificar que no sea null ni "null" ni vacío
                            $tieneValor = fn ($v) => $v !== null && $v !== 'null' && $v !== '' && $v !== 'No encontrado';

                            // Razón Social
                            if ($tieneValor($iaData['razon_social'] ?? null)) {
                                $datos['nombre_acta'] = $iaData['razon_social'];
                                $hallazgos = array_values(array_filter($hallazgos, fn ($h) => ! str_contains($h, 'pendiente de validación') && ! str_contains($h, 'Nombre de la sociedad')));
                                $errores = array_values(array_filter($errores, fn ($e) => ! str_contains($e, 'nombre') && ! str_contains($e, 'Razón Social')));
                                $hallazgos[] = 'Razón Social: '.$iaData['razon_social'];
                            }

                            // Tipo sociedad
                            if ($tieneValor($iaData['tipo_sociedad'] ?? null)) {
                                $datos['tipo_sociedad'] = $iaData['tipo_sociedad'];
                                $hallazgos[] = 'Tipo de sociedad: '.$iaData['tipo_sociedad'];
                            }

                            // Representante Legal
                            if ($tieneValor($iaData['representante_legal'] ?? null)) {
                                $hallazgos[] = 'Representante Legal: '.$iaData['representante_legal'];
                            }

                            // Notario
                            if ($tieneValor($iaData['notario_publico'] ?? null)) {
                                $datos['notario'] = $iaData['notario_publico'];
                                $hallazgos = array_values(array_filter($hallazgos, fn ($h) => ! str_contains($h, 'Notario')));
                                $errores = array_values(array_filter($errores, fn ($e) => ! str_contains($e, 'Notario')));
                                $hallazgos[] = 'Notario Público: '.$iaData['notario_publico'];
                            }

                            // Escritura
                            if ($tieneValor($iaData['escritura'] ?? null)) {
                                $datos['escritura'] = $iaData['escritura'];
                                $hallazgos[] = 'Escritura Pública: '.$iaData['escritura'];
                            }

                            // Registro Público / Sello RPPC
                            if (isset($iaData['sello_rppc']) && $iaData['sello_rppc'] === true) {
                                $datos['registro_publico'] = true;
                                $errores = array_values(array_filter($errores, fn ($e) => ! str_contains($e, 'Registro Público')));
                                $hallazgos = array_values(array_filter($hallazgos, fn ($h) => ! str_contains($h, 'Registro Público') && ! str_contains($h, 'RPPC')));
                                $hallazgos[] = 'Registro Público de la Propiedad y del Comercio ✓';
                                if ($tieneValor($iaData['folio_mercantil'] ?? null)) {
                                    $hallazgos[] = 'Folio Mercantil: '.$iaData['folio_mercantil'];
                                }
                            } elseif (isset($iaData['sello_rppc']) && $iaData['sello_rppc'] === false) {
                                $hallazgos = array_values(array_filter($hallazgos, fn ($h) => ! str_contains($h, 'RPPC') && ! str_contains($h, 'pendiente de confirmación')));
                                $hallazgos[] = 'Registro Público: no detectado en el documento (puede ser copia simple)';
                            }

                            // Duración
                            if ($tieneValor($iaData['duracion_anos'] ?? null)) {
                                $datos['duracion'] = $iaData['duracion_anos'];
                                $hallazgos = array_values(array_filter($hallazgos, fn ($h) => ! str_contains($h, 'duración') && ! str_contains($h, 'Duración')));
                                $hallazgos[] = 'Duración: '.$iaData['duracion_anos'].(is_numeric($iaData['duracion_anos']) ? ' años' : '');
                            }

                            // Es acta constitutiva
                            if (isset($iaData['es_acta_constitutiva']) && $iaData['es_acta_constitutiva'] === true) {
                                $errores = array_values(array_filter($errores, fn ($e) => ! str_contains($e, 'Constitución')));
                                $hallazgos[] = 'Documento validado como Acta Constitutiva ✓';
                            }

                            // Objeto social
                            if ($tieneValor($iaData['objeto_social'] ?? null)) {
                                $hallazgos[] = 'Objeto Social: '.$iaData['objeto_social'];
                            }

                            // Limpiar mensajes de "pendiente de validación por IA"
                            $hallazgos = array_values(array_filter($hallazgos, fn ($h) => ! str_contains($h, 'pendiente de validación')));
                            // Limpiar hallazgos que contengan ": null"
                            $hallazgos = array_values(array_filter($hallazgos, fn ($h) => ! str_ends_with($h, ': null') && ! str_contains($h, ': null')));
                        }
                    }
                }
            } catch (\Exception $e) {
                // IA no disponible, continuar con validación regex
            }
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    /**
     * Valida un Poder Notarial — verifica facultades, origen de autoridad y tipo (individual/mancomunado).
     */
    private function validarPoder(string $texto): array
    {
        $datos = [
            'notario' => null,
            'escritura' => null,
            'otorgante' => null,
            'apoderado' => null,
            'facultades' => [],
            'es_mancomunado' => false,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Normalizar
        $textoNorm = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú'],
            ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u'],
            $texto
        );
        $textoUpper = strtoupper($textoNorm);

        // ── Identificar que es un Poder ──
        if (str_contains($textoUpper, 'PODER') && (str_contains($textoUpper, 'NOTARI') || str_contains($textoUpper, 'ESCRITURA'))) {
            $hallazgos[] = 'Documento identificado como Poder Notarial';
        } elseif (str_contains($textoUpper, 'PODER') || str_contains($textoUpper, 'APODERAD') || str_contains($textoUpper, 'MANDATO')) {
            $hallazgos[] = 'Documento identificado como Poder/Mandato';
        } else {
            $errores[] = 'No se detectó que sea un Poder Notarial';
        }

        // ── Escritura ──
        if (preg_match('/ESCRITURA\s*(?:PUBLICA\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)/u', $textoUpper, $esc)) {
            $datos['escritura'] = $esc[1];
            $hallazgos[] = 'Escritura Pública No. '.$esc[1];
        }

        // ── Notario ──
        if (preg_match('/NOTARI[OA]\s*(?:PUBLICO\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)?/u', $textoUpper, $not)) {
            $datos['notario'] = isset($not[1]) ? 'Notaría #'.$not[1] : 'Sí';
            $hallazgos[] = 'Notario Público: '.($not[1] ?? 'detectado');
        }

        // ── Origen de autoridad (Regla de la Pirámide) ──
        $tieneOrigen = false;
        if (str_contains($textoUpper, 'ADMINISTRADOR UNICO')) {
            $datos['otorgante'] = 'Administrador Único';
            $hallazgos[] = 'Otorgado por: Administrador Único ✓';
            $tieneOrigen = true;
        } elseif (str_contains($textoUpper, 'CONSEJO DE ADMINISTRACION')) {
            $datos['otorgante'] = 'Consejo de Administración';
            $hallazgos[] = 'Otorgado por: Consejo de Administración ✓';
            $tieneOrigen = true;
        } elseif (str_contains($textoUpper, 'ASAMBLEA') && str_contains($textoUpper, 'ACCIONISTAS')) {
            $datos['otorgante'] = 'Asamblea de Accionistas';
            $hallazgos[] = 'Otorgado por: Asamblea de Accionistas ✓';
            $tieneOrigen = true;
        } elseif (str_contains($textoUpper, 'DIRECTOR GENERAL') || str_contains($textoUpper, 'REPRESENTANTE LEGAL')) {
            $datos['otorgante'] = 'Director General / Representante Legal';
            $hallazgos[] = 'Otorgado por: Director General o Rep. Legal';
            $tieneOrigen = true;
        }

        if (! $tieneOrigen) {
            $errores[] = 'No se detectó el origen de autoridad del poder (Administrador Único, Consejo de Administración o Asamblea)';
        }

        // ── Antecedente (de dónde emana la autoridad) ──
        if (str_contains($textoUpper, 'ANTECEDENTE') || str_contains($textoUpper, 'EN VIRTUD')
            || str_contains($textoUpper, 'CONFORME A') || str_contains($textoUpper, 'FACULTADES QUE LE CONFIERE')
            || str_contains($textoUpper, 'ACTA CONSTITUTIVA')) {
            $hallazgos[] = 'Se menciona antecedente/origen de facultades';
        } else {
            $errores[] = 'No se encontró referencia al antecedente de la autoridad del otorgante';
        }

        // ── Matriz de Facultades ──
        $facultades = [];

        // Actos de Administración
        if (str_contains($textoUpper, 'ACTOS DE ADMINISTRACION') || str_contains($textoUpper, 'PODER GENERAL DE ADMINISTRACION')
            || str_contains($textoUpper, 'PODER DE ADMINISTRACION')) {
            $facultades[] = 'Actos de Administración (puede firmar contratos)';
        }

        // Actos de Dominio
        if (str_contains($textoUpper, 'ACTOS DE DOMINIO') || str_contains($textoUpper, 'PODER DE DOMINIO')) {
            $facultades[] = 'Actos de Dominio (puede comprar/vender bienes)';
        }

        // Títulos de Crédito / Poder Cambiario
        if (str_contains($textoUpper, 'TITULOS DE CREDITO') || str_contains($textoUpper, 'PODER CAMBIARIO')
            || str_contains($textoUpper, 'PAGARE') || str_contains($textoUpper, 'LETRA DE CAMBIO')
            || str_contains($textoUpper, 'SUSCRIBIR') || str_contains($textoUpper, 'ENDOSAR')) {
            $facultades[] = 'Títulos de Crédito / Poder Cambiario (puede firmar pagarés, abrir cuentas)';
        }

        // Pleitos y Cobranzas
        if (str_contains($textoUpper, 'PLEITOS Y COBRANZAS') || str_contains($textoUpper, 'PODER PARA PLEITOS')) {
            $facultades[] = 'Pleitos y Cobranzas (puede representar legalmente)';
        }

        // Laboral
        if (str_contains($textoUpper, 'RELACIONES LABORALES') || str_contains($textoUpper, 'MATERIA LABORAL')
            || str_contains($textoUpper, 'TRABAJADORES')) {
            $facultades[] = 'Relaciones Laborales';
        }

        if (! empty($facultades)) {
            $datos['facultades'] = $facultades;
            foreach ($facultades as $f) {
                $hallazgos[] = '✓ Facultad: '.$f;
            }
        } else {
            $errores[] = 'No se detectaron facultades específicas en el poder (Administración, Dominio, Títulos de Crédito, etc.)';
        }

        // ── Individual o Mancomunado ──
        if (str_contains($textoUpper, 'MANCOMUNAD') || str_contains($textoUpper, 'CONJUNTAMENTE')
            || str_contains($textoUpper, 'MANERA CONJUNTA') || str_contains($textoUpper, 'DE FORMA CONJUNTA')
            || str_contains($textoUpper, 'ACTUEN DE MANERA CONJUNTA')) {
            $datos['es_mancomunado'] = true;
            $errores[] = '⚠ PODER MANCOMUNADO — Se requiere firma e identificación de otra persona adicional';
        } else {
            $hallazgos[] = 'Poder Individual (no requiere firma adicional)';
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    private function validarINE(string $texto, string $etiqueta): array
    {
        $datos = [
            'nombre' => null,
            'apellido_paterno' => null,
            'apellido_materno' => null,
            'nombres' => null,
            'fecha_nacimiento' => null,
            'curp' => null,
            'clave_elector' => null,
            'vigencia' => null,
            'seccion' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (strlen($texto) < 20) {
            $hallazgos[] = 'PDF escaneado — se requiere validación con OCR';

            return ['valida' => true, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Detectar INE/IFE — ser muy flexible porque el OCR de INEs es difícil
        $esIne = str_contains($texto, 'INSTITUTO NACIONAL ELECTORAL')
              || str_contains($texto, 'INE')
              || str_contains($texto, 'IFE')
              || str_contains($texto, 'CREDENCIAL')
              || str_contains($texto, 'ELECTORAL')
              || str_contains($texto, 'ELECTOR')
              || str_contains($texto, 'VOTAR')
              || str_contains($texto, 'SECCION')
              || str_contains($texto, 'VIGENCIA')
              || preg_match('/[A-Z]{6}\d{8}[HM]\d{3}/', $texto) // Patrón de clave de elector
              || preg_match('/[A-Z]{4}\d{6}[HM][A-Z]{5}/', $texto); // Patrón de CURP

        if ($esIne) {
            $hallazgos[] = 'Documento identificado como INE/IFE';
        } else {
            // No bloquear — si tiene clave de elector o nombre, asumir que es INE
            $hallazgos[] = 'Documento aceptado como identificación oficial';
        }

        // CURP — buscar con patrón flexible
        if (preg_match('/([A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d)/', $texto, $curpM)) {
            $datos['curp'] = $curpM[1];
            $hallazgos[] = 'CURP: '.$curpM[1];
        } elseif (preg_match('/([A-Z]{4}\d{6}[HM][A-Z]{4,6}[A-Z0-9]{0,2})/', $texto, $curpM)) {
            // CURP parcial (OCR puede cortar caracteres)
            $datos['curp'] = $curpM[1];
            $hallazgos[] = 'CURP (parcial): '.$curpM[1];
        } elseif (str_contains($texto, 'CURP')) {
            $hallazgos[] = 'Se menciona CURP (no se pudo extraer el valor)';
        }

        // Nombre completo — buscar apellidos y nombre por separado
        $nombreCompleto = '';

        // Método 1: Buscar por etiquetas separadas
        if (preg_match('/APELLIDO\s*PATERNO[:\s]*([A-ZÁÉÍÓÚÑ]{2,})/u', $texto, $apP)) {
            $datos['apellido_paterno'] = trim($apP[1]);
        }
        if (preg_match('/APELLIDO\s*MATERNO[:\s]*([A-ZÁÉÍÓÚÑ]{2,})/u', $texto, $apM)) {
            $datos['apellido_materno'] = trim($apM[1]);
        }
        if (preg_match('/NOMBRE\s*(?:\(S\))?[:\s]*([A-ZÁÉÍÓÚÑ]{2,}(?:\s+[A-ZÁÉÍÓÚÑ]{2,})?)/u', $texto, $nms)) {
            $candidato = trim($nms[1]);
            // Filtrar valores que NO son nombres
            $noEsNombre = ['SEXO', 'DOMICILIO', 'CLAVE', 'SECCION', 'VIGENCIA', 'ESTADO', 'MUNICIPIO', 'FECHA', 'NACIMIENTO', 'CURP', 'INE', 'IFE', 'ELECTOR', 'CREDENCIAL', 'INSTITUTO', 'NACIONAL', 'ELECTORAL'];
            $palabras = explode(' ', $candidato);
            $esValido = true;
            foreach ($palabras as $p) {
                if (in_array($p, $noEsNombre)) {
                    $esValido = false;
                    break;
                }
            }
            if ($esValido && strlen($candidato) > 2) {
                $datos['nombres'] = $candidato;
            }
        }

        // Método 2: Si Textract devuelve líneas sueltas, buscar patrones de nombre
        if (! $datos['apellido_paterno'] && ! $datos['nombres']) {
            // Buscar 2-4 palabras en mayúsculas seguidas que parezcan nombre
            if (preg_match_all('/\b([A-ZÁÉÍÓÚÑ]{2,})\s+([A-ZÁÉÍÓÚÑ]{2,})(?:\s+([A-ZÁÉÍÓÚÑ]{2,}))?(?:\s+([A-ZÁÉÍÓÚÑ]{2,}))?\b/u', $texto, $nombreLineas, PREG_SET_ORDER)) {
                $noEsNombre = ['SEXO', 'DOMICILIO', 'CLAVE', 'SECCION', 'VIGENCIA', 'ESTADO', 'MUNICIPIO', 'FECHA', 'NACIMIENTO', 'CURP', 'INE', 'IFE', 'ELECTOR', 'CREDENCIAL', 'INSTITUTO', 'NACIONAL', 'ELECTORAL', 'REGISTRO', 'FEDERAL', 'ELECTORES', 'CREDENCIAL', 'PARA', 'VOTAR', 'APELLIDO', 'PATERNO', 'MATERNO', 'NOMBRE'];
                foreach ($nombreLineas as $match) {
                    $posibleNombre = trim($match[0]);
                    $palabras = explode(' ', $posibleNombre);
                    $esValido = true;
                    foreach ($palabras as $p) {
                        if (in_array($p, $noEsNombre) || strlen($p) < 2) {
                            $esValido = false;
                            break;
                        }
                    }
                    if ($esValido && strlen($posibleNombre) > 5) {
                        $nombreCompleto = $posibleNombre;
                        break;
                    }
                }
            }
        }

        // Construir nombre completo desde partes
        if (! $nombreCompleto && ($datos['apellido_paterno'] || $datos['nombres'])) {
            $partes = array_filter([$datos['apellido_paterno'], $datos['apellido_materno'], $datos['nombres']]);
            $nombreCompleto = implode(' ', $partes);
        }

        // Método 3: Buscar cualquier línea con NOMBRE:
        if (! $nombreCompleto) {
            if (preg_match('/NOMBRE[:\s]+([A-ZÁÉÍÓÚÑ]+(?:\s+[A-ZÁÉÍÓÚÑ]+){0,3})/u', $texto, $nomGeneral)) {
                $nombreCompleto = trim($nomGeneral[1]);
            }
        }

        // Limpiar nombre
        if ($nombreCompleto) {
            $palabrasCorte = ['DOMICILIO', 'CLAVE', 'CURP', 'FECHA', 'SECCION', 'ESTADO', 'MUNICIPIO', 'LOCALIDAD', 'VIGENCIA', 'INSTITUTO', 'NACIMIENTO', 'ELECTORAL', 'CREDENCIAL', 'NACIONAL'];
            foreach ($palabrasCorte as $pc) {
                $pos = strpos($nombreCompleto, $pc);
                if ($pos !== false) {
                    $nombreCompleto = trim(substr($nombreCompleto, 0, $pos));
                }
            }
            if (strlen($nombreCompleto) > 2) {
                $datos['nombre'] = $nombreCompleto;
                $hallazgos[] = 'Nombre: '.$nombreCompleto;
            }
        }

        // Si no se encontró nombre, intentar extraer del CURP
        if (empty($datos['nombre']) && $datos['curp'] && strlen($datos['curp']) >= 16) {
            // La CURP contiene las iniciales: AAAA (4 letras = Ap.Paterno vocal, Ap.Materno inicial, Nombre inicial)
            // No podemos reconstruir el nombre completo, pero sí indicar que se necesita verificar
            $datos['nombre'] = null;
            $hallazgos[] = 'Nombre no extraído del texto';
        } elseif (empty($datos['nombre'])) {
            $hallazgos[] = 'Nombre no detectado en el documento';
        }

        // Fecha de nacimiento
        if (preg_match('/FECHA\s*(?:DE\s*)?NACIMIENTO[:\s]*([\d\/\-\.]+)/', $texto, $fn)) {
            $datos['fecha_nacimiento'] = $fn[1];
            $hallazgos[] = 'Fecha de nacimiento: '.$fn[1];
        } elseif (preg_match('/NACIMIENTO[:\s]*([\d\/\-\.]+)/', $texto, $fn2)) {
            $datos['fecha_nacimiento'] = $fn2[1];
            $hallazgos[] = 'Fecha de nacimiento: '.$fn2[1];
        } elseif ($datos['curp'] && strlen($datos['curp']) >= 10) {
            // Extraer fecha del CURP (posiciones 5-10: AAMMDD)
            $curpFecha = substr($datos['curp'], 4, 6);
            if (preg_match('/(\d{2})(\d{2})(\d{2})/', $curpFecha, $cf)) {
                $anio = (int) $cf[1] > 50 ? '19'.$cf[1] : '20'.$cf[1];
                $datos['fecha_nacimiento'] = $cf[3].'/'.$cf[2].'/'.$anio;
                $hallazgos[] = 'Fecha de nacimiento (del CURP): '.$datos['fecha_nacimiento'];
            }
        }

        // Clave de elector
        if (preg_match('/CLAVE\s*(?:DE\s*)?ELECTOR[:\s]*([A-Z0-9]+)/', $texto, $ce)) {
            $datos['clave_elector'] = $ce[1];
            $hallazgos[] = 'Clave de elector: '.$ce[1];
        } elseif (preg_match('/([A-Z]{6}\d{8}[HM]\d{3})/', $texto, $ce2)) {
            $datos['clave_elector'] = $ce2[1];
            $hallazgos[] = 'Clave de elector: '.$ce2[1];
        }

        // Vigencia — DEBE ser un año futuro o actual (2024+), NO confundir con año de nacimiento
        $vigenciaEncontrada = false;
        $anioActual = (int) date('Y');

        // Método 1: Buscar "VIGENCIA" seguido de un año
        if (preg_match('/VIGENCIA[:\s]*(\d{4})/i', $texto, $vigM)) {
            $anioCandidate = (int) $vigM[1];
            // Solo aceptar si es un año razonable de vigencia (>= 2020)
            if ($anioCandidate >= 2020) {
                $datos['vigencia'] = (string) $anioCandidate;
                $vigenciaEncontrada = true;
            }
        }

        // Método 2: Buscar formato dd/mm/yyyy o mm/yyyy después de VIGENCIA
        if (! $vigenciaEncontrada && preg_match('/VIG(?:ENCIA)?[:\s.]*\d{0,2}[\/\-]?\d{0,2}[\/\-]?(20[2-3]\d)/', $texto, $vigM2)) {
            $datos['vigencia'] = $vigM2[1];
            $vigenciaEncontrada = true;
        }

        // Método 3: Buscar años 2024-2039 que NO estén cerca de NACIMIENTO o CURP
        if (! $vigenciaEncontrada) {
            // Buscar todos los años 202X-203X en el texto
            if (preg_match_all('/\b(20[2-3]\d)\b/', $texto, $aniosAll)) {
                foreach ($aniosAll[1] as $anioCandidate) {
                    $pos = strpos($texto, $anioCandidate);
                    // Verificar que no esté cerca de "NACIMIENTO" (dentro de 50 chars antes)
                    $contexto = substr($texto, max(0, $pos - 50), 50);
                    if (! str_contains($contexto, 'NACIMIENTO') && ! str_contains($contexto, 'NACI')
                        && (int) $anioCandidate >= $anioActual) {
                        $datos['vigencia'] = $anioCandidate;
                        $vigenciaEncontrada = true;
                        break;
                    }
                }
            }
        }

        if ($vigenciaEncontrada && $datos['vigencia']) {
            $anioVigencia = (int) $datos['vigencia'];
            if ($anioVigencia < $anioActual) {
                $errores[] = 'INE vencida — Vigencia: '.$datos['vigencia'].' (año actual: '.$anioActual.')';
                $hallazgos[] = 'Vigencia: '.$datos['vigencia'].' — VENCIDA';
            } else {
                $hallazgos[] = 'Vigencia: '.$datos['vigencia'].' — Vigente ✓';
            }
        } else {
            $hallazgos[] = 'Vigencia no detectada en el documento';
        }

        // Sección
        if (preg_match('/SECCION[:\s]*(\d+)/', $texto, $secM)) {
            $datos['seccion'] = $secM[1];
            $hallazgos[] = 'Sección: '.$secM[1];
        }

        // ── Reconstruir hallazgos en orden estandarizado ──
        $hallazgosOrdenados = [];

        // 1. Documento identificado
        $hallazgosOrdenados[] = $esIne ? 'Documento identificado como INE/IFE' : 'Documento aceptado como identificación oficial';

        // 2. CURP
        if ($datos['curp']) {
            $hallazgosOrdenados[] = 'CURP: '.$datos['curp'];
        }

        // 3. Nombre completo
        if ($datos['nombre']) {
            $hallazgosOrdenados[] = 'Nombre: '.$datos['nombre'];
        }

        // 4. Fecha de nacimiento
        if ($datos['fecha_nacimiento']) {
            $hallazgosOrdenados[] = 'Fecha de nacimiento (del CURP): '.$datos['fecha_nacimiento'];
        }

        // 5. Clave de elector
        if ($datos['clave_elector']) {
            $hallazgosOrdenados[] = 'Clave de elector: '.$datos['clave_elector'];
        }

        // 6. Vigencia
        if ($datos['vigencia']) {
            $anioActual = (int) date('Y');
            $anioVig = (int) $datos['vigencia'];
            $vigente = $anioVig >= $anioActual;
            $hallazgosOrdenados[] = 'Vigencia: '.$datos['vigencia'].' — '.($vigente ? 'Vigente ✓' : 'VENCIDA');
        } else {
            $hallazgosOrdenados[] = 'Vigencia no detectada en el documento';
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgosOrdenados];
    }

    private function validarCaratulaBanco(string $texto): array
    {
        $datos = [
            'banco' => null,
            'clabe' => null,
            'cuenta' => null,
            'titular' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Banco
        $bancos = [
            'BBVA', 'BANCOMER', 'BANAMEX', 'CITIBANAMEX', 'SANTANDER',
            'BANORTE', 'HSBC', 'SCOTIABANK', 'INBURSA', 'BAJIO',
            'AFIRME', 'MIFEL', 'BANREGIO', 'AZTECA', 'MULTIVA', 'BANCO',
        ];
        foreach ($bancos as $b) {
            if (str_contains($texto, $b)) {
                $datos['banco'] = $b;
                $hallazgos[] = 'Banco detectado: '.$b;
                break;
            }
        }
        if (! $datos['banco']) {
            $errores[] = 'No se detectó institución bancaria reconocida';
        }

        // CLABE (18 dígitos) — buscar por texto "CLABE" o directamente 18 dígitos consecutivos
        // Primero limpiar espacios entre dígitos que el OCR pueda insertar
        $textoDigitos = preg_replace('/(\d)\s+(\d)/', '$1$2', $texto);

        if (preg_match('/(?:Cuenta\s*)?CLABE[:\s]*(\d{18})/i', $textoDigitos, $clabeM)) {
            $datos['clabe'] = $clabeM[1];
            $hallazgos[] = 'CLABE interbancaria: '.$clabeM[1];
        } elseif (preg_match('/CLABE[^0-9]{0,20}(\d{18})/i', $textoDigitos, $clabeM)) {
            $datos['clabe'] = $clabeM[1];
            $hallazgos[] = 'CLABE interbancaria: '.$clabeM[1];
        } elseif (preg_match('/(\d{18})/', $textoDigitos, $clabeM)) {
            $datos['clabe'] = $clabeM[1];
            $hallazgos[] = 'CLABE interbancaria: '.$clabeM[1];
        } else {
            $errores[] = 'No se encontró CLABE interbancaria (18 dígitos)';
        }

        // Número de cuenta
        if (preg_match('/(?:CUENTA|NO\.\s*CUENTA)[:\s]*(\d{8,12})/', $texto, $ctaM)) {
            $datos['cuenta'] = $ctaM[1];
            $hallazgos[] = 'No. Cuenta: '.$ctaM[1];
        } elseif (preg_match('/\b(\d{10,11})\b/', $texto, $ctaM2)) {
            $datos['cuenta'] = $ctaM2[1];
            $hallazgos[] = 'Posible No. Cuenta: '.$ctaM2[1];
        }

        // Titular
        if (preg_match('/(?:TITULAR|BENEFICIARIO|NOMBRE)[:\s]*([A-ZÁÉÍÓÚÑ&\s,\.]+)/u', $texto, $titM)) {
            $datos['titular'] = trim($titM[1]);
            $hallazgos[] = 'Titular: '.$datos['titular'];
        } elseif (str_contains($texto, 'TITULAR') || str_contains($texto, 'NOMBRE')
               || str_contains($texto, 'CUENTA') || str_contains($texto, 'BENEFICIARIO')) {
            $hallazgos[] = 'Se detectó referencia al titular';
        } else {
            $errores[] = 'No se encontró nombre del titular de la cuenta';
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    // ──────────────────────────────────────────────────
    // UTILIDADES
    // ──────────────────────────────────────────────────

    private function extraerTexto(Parser $parser, string $path): string
    {
        // Si es imagen (JPG/PNG), enviar directo a Textract
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($path) ?: '';

        if (in_array($extension, ['jpg', 'jpeg', 'png']) || str_starts_with($mimeType, 'image/')) {
            $textoCloud = $this->ocrConTextract($path);
            if (strlen($textoCloud) > 20) {
                return $textoCloud;
            }
            // Intentar Tesseract local como fallback para imágenes
            if (class_exists('\thiagoalessio\TesseractOCR\TesseractOCR')) {
                try {
                    $tesseractPath = $this->encontrarTesseract();
                    if ($tesseractPath) {
                        $ocr = new TesseractOCR($path);
                        $ocr->executable($tesseractPath);
                        $ocr->lang('spa', 'eng');
                        $resultado = $ocr->run();
                        if (strlen(trim($resultado)) > 20) {
                            return $this->limpiarTextoOcr($resultado);
                        }
                    }
                } catch (\Exception $e) {
                }
            }

            return $textoCloud;
        }

        // Para PDFs: intentar parser de texto
        $texto = '';
        try {
            $pdf = $parser->parseFile($path);
            $texto = $pdf->getText();

            if (strlen($texto) < 20) {
                $pages = $pdf->getPages();
                $textoPages = '';
                foreach ($pages as $page) {
                    $textoPages .= $page->getText().' ';
                }
                if (strlen($textoPages) > strlen($texto)) {
                    $texto = $textoPages;
                }
            }

            $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
            $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
            $texto = preg_replace('/\s+/', ' ', $texto);
            $texto = strtoupper(trim($texto));
        } catch (\Exception $e) {
            $texto = '';
        }

        if (strlen($texto) >= 30) {
            return $texto;
        }

        // Intentar Tesseract local
        $textoOcr = $this->ocrDesdePdf($parser, $path);
        if (strlen($textoOcr) >= 30) {
            return $textoOcr;
        }

        // Último recurso: AWS Textract (OCR en la nube)
        $textoCloud = $this->ocrConTextract($path);
        if (strlen($textoCloud) > strlen($textoOcr) && strlen($textoCloud) > strlen($texto)) {
            return $textoCloud;
        }

        return strlen($textoOcr) > strlen($texto) ? $textoOcr : $texto;
    }

    /**
     * Busca Tesseract en rutas comunes.
     */
    private function encontrarTesseract(): ?string
    {
        $rutas = [
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
            'C:\\Users\\IT 2\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe',
            'C:\\Users\\IT\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
        ];
        foreach ($rutas as $ruta) {
            if (file_exists($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    /**
     * OCR con AWS Textract — lee PDFs/imágenes escaneadas en la nube.
     * No requiere Tesseract instalado en el servidor.
     */
    private function ocrConTextract(string $path): string
    {
        if (! class_exists('\Aws\Textract\TextractClient')) {
            return '';
        }

        $accessKey = config('services.ia.aws_access_key', '');
        $secretKey = config('services.ia.aws_secret_key', '');
        $region = config('services.ia.bedrock_region', 'us-east-1');

        if (empty($accessKey) || empty($secretKey)) {
            return '';
        }

        $fileSize = filesize($path);
        if ($fileSize > 10000000) { // Mayor a 10MB, no intentar
            return '';
        }

        try {
            $client = new TextractClient([
                'region' => $region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $accessKey,
                    'secret' => $secretKey,
                ],
                'http' => ['timeout' => 25, 'connect_timeout' => 5],
            ]);

            // Para PDFs multi-página: convertir a imágenes con Imagick si está disponible
            $isPdf = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf' ||
                     str_starts_with(file_get_contents($path, false, null, 0, 5), '%PDF');

            if ($isPdf && extension_loaded('imagick')) {
                return $this->ocrTextractConImagick($client, $path);
            }

            // Para imágenes o PDFs de 1 página: enviar directo
            $fileBytes = file_get_contents($path);
            if (empty($fileBytes)) {
                return '';
            }

            // Si es PDF multi-página sin Imagick, intentar de todos modos
            try {
                $response = $client->detectDocumentText([
                    'Document' => ['Bytes' => $fileBytes],
                ]);

                $texto = '';
                foreach ($response['Blocks'] as $block) {
                    if ($block['BlockType'] === 'LINE') {
                        $texto .= $block['Text'].' ';
                    }
                }

                if (strlen(trim($texto)) > 30) {
                    return $this->limpiarTextoOcr($texto);
                }
            } catch (\Exception $e) {
                Log::info('Textract detectDocumentText falló, intentando Imagick', ['error' => $e->getMessage()]);

                // Fallback: convertir PDF a imagen con Imagick y reintentar
                if ($isPdf && extension_loaded('imagick')) {
                    $textoImagick = $this->ocrTextractConImagick($client, $path);
                    if (strlen($textoImagick) > 30) {
                        return $textoImagick;
                    }
                }
            }

            return '';

        } catch (\Exception $e) {
            Log::warning('Textract OCR falló', ['error' => $e->getMessage()]);

            return '';
        }
    }

    /**
     * Convierte páginas del PDF a imágenes con Imagick y las envía a Textract una por una.
     */
    private function ocrTextractConImagick(TextractClient $client, string $pdfPath): string
    {
        $textoTotal = '';
        $tmpDir = sys_get_temp_dir();

        try {
            $imagick = new \Imagick;
            // 150 DPI + 2 páginas máx: evita timeouts del proxy de SiteGround
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath.'[0-1]');

            $numPages = $imagick->getNumberImages();
            $paginas = min($numPages, 2);

            for ($i = 0; $i < $paginas; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');

                $tmpPng = $tmpDir.'/salcom_textract_'.uniqid().'.png';
                $imagick->writeImage($tmpPng);

                $imgBytes = file_get_contents($tmpPng);
                @unlink($tmpPng);

                if (empty($imgBytes)) {
                    continue;
                }

                try {
                    $response = $client->detectDocumentText([
                        'Document' => ['Bytes' => $imgBytes],
                    ]);

                    foreach ($response['Blocks'] as $block) {
                        if ($block['BlockType'] === 'LINE') {
                            $textoTotal .= $block['Text'].' ';
                        }
                    }
                } catch (\Exception $e) {
                    Log::info('Textract página '.($i + 1).' falló', ['error' => $e->getMessage()]);
                }
            }

            $imagick->clear();
            $imagick->destroy();

        } catch (\Exception $e) {
            Log::warning('Imagick+Textract falló', ['error' => $e->getMessage()]);
        }

        return $this->limpiarTextoOcr($textoTotal);
    }

    private function limpiarTextoOcr(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);

        return strtoupper(trim($texto));
    }

    /**
     * Extrae el nombre completo de una persona física del texto OCR de la CIF.
     * Maneja etiquetas pegadas, palabras concatenadas y orden de campos.
     * Retorna formato: "APELLIDO_PATERNO APELLIDO_MATERNO NOMBRE(S)" o null.
     */
    private function extraerNombreCifPersonaFisica(string $textoUpper): ?string
    {
        // Lista de etiquetas/palabras reservadas del SAT que NO son parte del nombre
        $etiquetasSat = [
            'PRIMER APELLIDO', 'PRIMERAPELLIDO', 'PRIMER',
            'SEGUNDO APELLIDO', 'SEGUNDOAPELLIDO', 'SEGUNDO',
            'APELLIDO PATERNO', 'APELLIDO MATERNO', 'APELLIDO',
            'NOMBRE', 'NOMBRES', 'PATERNO', 'MATERNO',
            'FECHA DE INICIO DE OPERACIONES', 'FECHAINICIODEOPERACIONES',
            'FECHA INICIO DE OPERACIONES', 'FECHAINICIO',
            'DATOS DE IDENTIFICACION', 'DATOS DEL CONTRIBUYENTE',
            'IDENTIFICACION DEL CONTRIBUYENTE', 'SITUACION FISCAL',
            'CONSTANCIA', 'REGIMEN', 'DOMICILIO', 'FISCAL',
            'CONTRIBUYENTE', 'RFC', 'CURP', 'CODIGO POSTAL',
            'CLAVE', 'OBLIGACIONES', 'ESTATUS', 'PADRON', 'ACTIVO',
            'INICIO DE OPERACIONES', 'ULTIMO CAMBIO',
            'FECHA DE ULTIMO CAMBIO DE ESTADO',
        ];

        $ap1 = '';
        $ap2 = '';
        $nombres = '';

        // ── Estrategia 1: Buscar campos etiquetados ──

        // Primer Apellido / Apellido Paterno
        $patronesAp1 = [
            '/(?:PRIMER\s*APELLIDO|APELLIDO\s*PATERNO)[:\s]+([A-Z]{2,}(?:\s+[A-Z]{2,})?)/u',
            '/PRIMER\s*APELLIDO\s*:?\s*([A-Z]{2,})/u',
        ];
        foreach ($patronesAp1 as $patron) {
            if (preg_match($patron, $textoUpper, $m)) {
                $ap1 = $this->limpiarCampoNombre($m[1], $etiquetasSat);
                if ($ap1) {
                    break;
                }
            }
        }

        // Segundo Apellido / Apellido Materno
        $patronesAp2 = [
            '/(?:SEGUNDO\s*APELLIDO|APELLIDO\s*MATERNO)[:\s]+([A-Z]{2,}(?:\s+[A-Z]{2,})?)/u',
            '/SEGUNDO\s*APELLIDO\s*:?\s*([A-Z]{2,})/u',
        ];
        foreach ($patronesAp2 as $patron) {
            if (preg_match($patron, $textoUpper, $m)) {
                $ap2 = $this->limpiarCampoNombre($m[1], $etiquetasSat);
                if ($ap2) {
                    break;
                }
            }
        }

        // Nombre(s)
        $patronesNombre = [
            '/NOMBRE\s*\(?S?\)?[:\s]+([A-Z]{2,}(?:\s+[A-Z]{2,}){0,2})/u',
            '/NOMBRE\s*:?\s*([A-Z]{2,}(?:\s+[A-Z]{2,})?)/u',
        ];
        foreach ($patronesNombre as $patron) {
            if (preg_match($patron, $textoUpper, $m)) {
                $nombres = $this->limpiarCampoNombre($m[1], $etiquetasSat);
                if ($nombres) {
                    break;
                }
            }
        }

        // ── Estrategia 2: Si no encontró con etiquetas, buscar por posición relativa ──
        if (! $ap1 && ! $nombres) {
            // Buscar después de "DATOS DE IDENTIFICACION DEL CONTRIBUYENTE"
            if (preg_match('/CONTRIBUYENTE[:\s]+([A-Z]{2,}(?:\s+[A-Z]{2,}){1,5})/u', $textoUpper, $m)) {
                $candidato = $this->limpiarCampoNombre($m[1], $etiquetasSat);
                if ($candidato && strlen($candidato) > 4) {
                    // Asumir que es nombre completo
                    $partes = explode(' ', $candidato);
                    if (count($partes) >= 3) {
                        $nombres = implode(' ', array_slice($partes, 0, -2));
                        $ap1 = $partes[count($partes) - 2] ?? '';
                        $ap2 = $partes[count($partes) - 1] ?? '';
                    } else {
                        $nombres = $candidato;
                    }
                }
            }
        }

        // ── Post-procesamiento ──
        // Limpiar cada parte de etiquetas residuales
        $ap1 = $this->limpiarCampoNombre($ap1, $etiquetasSat);
        $ap2 = $this->limpiarCampoNombre($ap2, $etiquetasSat);
        $nombres = $this->limpiarCampoNombre($nombres, $etiquetasSat);

        // Detectar y separar palabras pegadas comunes (ej: "CARLOSISAAC")
        // Si una "palabra" tiene más de 10 caracteres y no es un apellido conocido,
        // intentar separarla usando el RFC como referencia
        $nombres = $this->separarPalabrasPegadas($nombres);

        // Armar nombre final: Apellido Paterno + Apellido Materno + Nombre(s)
        $partes = array_filter([$ap1, $ap2, $nombres], fn ($p) => strlen($p) > 1);

        if (empty($partes)) {
            return null;
        }

        $nombreFinal = implode(' ', $partes);

        // Sanitización final
        $nombreFinal = preg_replace('/\d{2}\/\d{2}\/\d{4}/', '', $nombreFinal); // Remover fechas
        $nombreFinal = preg_replace('/\d{4,}/', '', $nombreFinal); // Remover números largos
        $nombreFinal = preg_replace('/\s+/', ' ', $nombreFinal);
        $nombreFinal = trim($nombreFinal);

        return strlen($nombreFinal) > 3 ? $nombreFinal : null;
    }

    /**
     * Limpia un campo de nombre removiendo etiquetas del SAT.
     */
    private function limpiarCampoNombre(string $valor, array $etiquetas): string
    {
        $valor = trim($valor);

        // Ordenar etiquetas de mayor a menor longitud para evitar matches parciales
        usort($etiquetas, fn ($a, $b) => strlen($b) - strlen($a));

        foreach ($etiquetas as $etiqueta) {
            // Buscar etiqueta con o sin espacios
            $patron = str_replace(' ', '\s*', preg_quote($etiqueta, '/'));
            $valor = preg_replace('/\b'.$patron.'\b/i', '', $valor);
            // También sin word boundary (por si están pegadas)
            $valor = str_ireplace(str_replace(' ', '', $etiqueta), '', $valor);
        }

        // Limpiar residuos
        $valor = preg_replace('/[^A-ZÁÉÍÓÚÑ\s]/u', '', $valor);
        $valor = preg_replace('/\s+/', ' ', $valor);

        return trim($valor);
    }

    /**
     * Intenta separar palabras pegadas por OCR (ej: "CARLOSISAAC" → "CARLOS ISAAC").
     * Usa heurísticas de nombres comunes en español.
     */
    private function separarPalabrasPegadas(string $texto): string
    {
        if (strlen($texto) <= 10 || str_contains($texto, ' ')) {
            return $texto;
        }

        // Lista de nombres comunes para intentar separar
        $nombresComunes = [
            'CARLOS', 'MARIA', 'JOSE', 'JUAN', 'LUIS', 'MIGUEL', 'ANGEL',
            'ISAAC', 'DANIEL', 'DAVID', 'PEDRO', 'PABLO', 'EDUARDO',
            'ALFONSO', 'ALBERTO', 'ALEJANDRO', 'ANTONIO', 'ARTURO',
            'FRANCISCO', 'FERNANDO', 'GABRIEL', 'GERARDO', 'GUILLERMO',
            'HECTOR', 'HUGO', 'JAVIER', 'JORGE', 'MANUEL', 'MARIO',
            'MARTIN', 'OSCAR', 'RAFAEL', 'RAMON', 'RAUL', 'RICARDO',
            'ROBERTO', 'SERGIO', 'VICTOR', 'ANA', 'LAURA', 'SANDRA',
            'PATRICIA', 'GUADALUPE', 'ELIZABETH', 'ADRIANA', 'ROSA',
        ];

        foreach ($nombresComunes as $nombre) {
            if (str_starts_with($texto, $nombre) && strlen($texto) > strlen($nombre)) {
                $resto = substr($texto, strlen($nombre));
                if (strlen($resto) >= 2) {
                    return $nombre.' '.$this->separarPalabrasPegadas($resto);
                }
            }
        }

        return $texto;
    }

    /**
     * Extrae imágenes del PDF con pdfparser, las reconstruye con GD,
     * y las pasa a Tesseract OCR para leer el texto.
     */
    private function ocrDesdePdf(Parser $parser, string $pdfPath): string
    {
        // Buscar Tesseract en rutas comunes (Windows y Linux)
        $rutasPosibles = [
            '/usr/bin/tesseract',                                                        // Linux estándar
            '/usr/local/bin/tesseract',                                                  // Linux alternativo
            'C:\\Users\\IT 2\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe',  // PC Alfonso
            'C:\\Users\\IT\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe',    // PC Said
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];
        $tesseractPath = null;
        foreach ($rutasPosibles as $ruta) {
            if (file_exists($ruta)) {
                $tesseractPath = $ruta;
                break;
            }
        }
        if (! $tesseractPath) {
            return '';
        }

        $textoTotal = '';
        $tmpDir = sys_get_temp_dir();

        try {
            $pdf = $parser->parseFile($pdfPath);
            $imgCount = 0;

            foreach ($pdf->getObjects() as $obj) {
                $header = $obj->getHeader();
                $subtype = $header->get('Subtype');
                if (! $subtype || $subtype->getContent() !== 'Image') {
                    continue;
                }

                $content = $obj->getContent();
                if (strlen($content) < 1000) {
                    continue;
                }

                $filter = $header->get('Filter');
                $filterName = $filter ? $filter->getContent() : '';
                $width = (int) ($header->get('Width') ? $header->get('Width')->getContent() : 0);
                $height = (int) ($header->get('Height') ? $header->get('Height')->getContent() : 0);

                $tmpImage = $tmpDir.'/salcom_ocr_'.uniqid();
                $imageCreated = false;

                if ($filterName === 'DCTDecode') {
                    $tmpImage .= '.jpg';
                    file_put_contents($tmpImage, $content);
                    $imageCreated = true;
                } elseif ($filterName === 'FlateDecode' && $width > 0 && $height > 0) {
                    $tmpImage .= '.png';
                    $imageCreated = $this->reconstruirImagenGD($content, $width, $height, $header, $tmpImage);
                }

                if ($imageCreated && file_exists($tmpImage)) {
                    try {
                        $ocr = new TesseractOCR($tmpImage);
                        $ocr->executable($tesseractPath);
                        $ocr->lang('spa', 'eng');
                        $resultado = $ocr->run();
                        if (strlen(trim($resultado)) > 10) {
                            $textoTotal .= $resultado."\n";
                        }
                    } catch (\Exception $e) {
                        // OCR falló, continuar
                    } finally {
                        @unlink($tmpImage);
                    }
                }

                if (++$imgCount >= 5) {
                    break;
                }
            }
        } catch (\Exception $e) {
            return '';
        }

        $textoTotal = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $textoTotal);
        $textoTotal = preg_replace('/[^\x20-\x7E\n]/', ' ', $textoTotal);
        $textoTotal = preg_replace('/\s+/', ' ', $textoTotal);

        // Si no se pudo extraer texto de imágenes embebidas, intentar con Ghostscript
        if (strlen(trim($textoTotal)) < 30) {
            $gsPath = null;
            $gsRutas = [
                'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
                'C:\\Program Files (x86)\\gs\\gs10.07.0\\bin\\gswin32c.exe',
                '/usr/bin/gs',
                '/usr/local/bin/gs',
            ];
            foreach ($gsRutas as $ruta) {
                if (file_exists($ruta)) {
                    $gsPath = $ruta;
                    break;
                }
            }

            if ($gsPath !== null) {
                try {
                    $tmpPng = $tmpDir.'/salcom_gs_'.uniqid().'.png';
                    $cmd = sprintf(
                        '"%s" -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile="%s" "%s" 2>&1',
                        $gsPath, $tmpPng, $pdfPath
                    );
                    exec($cmd, $output, $returnCode);

                    if ($returnCode === 0 && file_exists($tmpPng)) {
                        $ocr = new TesseractOCR($tmpPng);
                        $ocr->executable($tesseractPath);
                        $ocr->lang('spa', 'eng');
                        $resultado = $ocr->run();
                        if (strlen(trim($resultado)) > 20) {
                            $textoTotal = $resultado;
                        }
                        @unlink($tmpPng);
                    }
                } catch (\Exception $e) {
                    // Ghostscript falló, continuar
                }
            }

            $textoTotal = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $textoTotal) ?: $textoTotal;
            $textoTotal = preg_replace('/[^\x20-\x7E\n]/', ' ', $textoTotal);
            $textoTotal = preg_replace('/\s+/', ' ', $textoTotal);
        }

        return strtoupper(trim($textoTotal));
    }

    private function reconstruirImagenGD(string $content, int $width, int $height, $header, string $outputPath): bool
    {
        try {
            $cs = $header->get('ColorSpace') ? $header->get('ColorSpace')->getContent() : 'DeviceRGB';
            $img = imagecreatetruecolor($width, $height);
            $pos = 0;
            $len = strlen($content);

            if ($cs === 'DeviceRGB') {
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        if ($pos + 2 >= $len) {
                            break 2;
                        }
                        $r = ord($content[$pos++]);
                        $g = ord($content[$pos++]);
                        $b = ord($content[$pos++]);
                        imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
                    }
                }
            } elseif ($cs === 'DeviceGray') {
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        if ($pos >= $len) {
                            break 2;
                        }
                        $g = ord($content[$pos++]);
                        imagesetpixel($img, $x, $y, imagecolorallocate($img, $g, $g, $g));
                    }
                }
            } else {
                imagedestroy($img);

                return false;
            }

            imagepng($img, $outputPath);
            imagedestroy($img);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validarRFC(?string $rfc): bool
    {
        if (! $rfc) {
            return false;
        }

        return (bool) preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc);
    }

    private function normalizarNombre(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre), 'UTF-8');
        $reemplazos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
        ];
        $nombre = strtr($nombre, $reemplazos);
        $nombre = preg_replace('/[^A-Z0-9\s]/', ' ', $nombre) ?? $nombre;
        $nombre = preg_replace('/\s+/', ' ', $nombre) ?? $nombre;

        return trim($nombre);
    }

    private function nombresCoinciden(string $esperado, string $encontrado): bool
    {
        $a = $this->normalizarNombre($esperado);
        $b = $this->normalizarNombre($encontrado);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        // Uno contiene al otro (útil con S.A. DE C.V. u orden de apellidos)
        if (str_contains($a, $b) || str_contains($b, $a)) {
            return true;
        }

        // Comparar tokens (orden flexible)
        $tokensA = array_values(array_filter(explode(' ', $a), fn ($t) => strlen($t) > 1));
        $tokensB = array_values(array_filter(explode(' ', $b), fn ($t) => strlen($t) > 1));

        // Manejar palabras pegadas: "CARLOSISAAC" contiene "CARLOS" y "ISAAC"
        $tokensAExpanded = $tokensA;
        $tokensBExpanded = $tokensB;
        foreach ($tokensA as $t) {
            foreach ($tokensB as $tb) {
                if (strlen($t) > strlen($tb) && str_contains($t, $tb)) {
                    $tokensAExpanded[] = $tb;
                }
            }
        }
        foreach ($tokensB as $t) {
            foreach ($tokensA as $ta) {
                if (strlen($t) > strlen($ta) && str_contains($t, $ta)) {
                    $tokensBExpanded[] = $ta;
                }
            }
        }

        if (count($tokensA) >= 2 && count($tokensB) >= 2) {
            $inter = array_intersect($tokensAExpanded, $tokensBExpanded);
            $umbral = min(count($tokensA), count($tokensB));
            if ($umbral > 0 && count($inter) / $umbral >= 0.6) {
                return true;
            }
        }

        similar_text($a, $b, $pct);

        return $pct >= 70;
    }

    private function mesEnEspanol(int $mes): string
    {
        return [
            1 => 'ENERO',    2 => 'FEBRERO',   3 => 'MARZO',
            4 => 'ABRIL',    5 => 'MAYO',      6 => 'JUNIO',
            7 => 'JULIO',    8 => 'AGOSTO',    9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ][$mes] ?? '';
    }
}
