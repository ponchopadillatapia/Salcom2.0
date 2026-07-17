<?php

namespace App\Http\Controllers;

use App\Models\ContactoProveedor;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\ProveedorUser;
use App\Services\AltaFacturaValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PortalProveedorController extends Controller
{
    public function mostrarPortal()
    {
        return view('proveedores.portal');
    }

    public function mostrarDashboard()
    {
        return view('proveedores.dashboard');
    }

    public function mostrarOnboarding()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));

        // Si entró como admin, proveedor_id antes era el id de admin_users (sin fila en proveedores_users).
        // No redirigir a login: eso causa rebote (login -> portal) y parece que onboarding no carga.
        if (! $proveedor) {
            $admin = \App\Models\AdminUser::find(session('proveedor_id'));
            if ($admin && $admin->rol === 'admin') {
                $existente = ProveedorUser::where('usuario', $admin->usuario)->first();
                if ($existente) {
                    $proveedor = $existente;
                } else {
                    $datos = [
                        'usuario' => $admin->usuario,
                        'nombre' => $admin->nombre,
                        'correo' => $admin->correo ?: ($admin->usuario.'@salcom.local'),
                        'password' => $admin->password,
                        'tipo_persona' => 'Persona Moral',
                        'activo' => true,
                    ];
                    if (\Illuminate\Support\Facades\Schema::hasColumn('proveedores_users', 'id_proveedor')) {
                        $datos['id_proveedor'] = 'ADMIN-'.$admin->id;
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('proveedores_users', 'codigo_compras')) {
                        $datos['codigo_compras'] = 'ADMIN-'.$admin->id;
                    }
                    $proveedor = new ProveedorUser;
                    $proveedor->forceFill($datos)->save();
                    $proveedor = $proveedor->fresh();
                }

                session([
                    'proveedor_id' => $proveedor->id,
                    'proveedor_nombre' => $proveedor->nombre ?? $admin->nombre,
                    'proveedor_codigo' => $proveedor->id_proveedor ?? $proveedor->codigo_compras ?? ('ADMIN-'.$admin->id),
                    'proveedor_correo' => $proveedor->correo ?? $admin->correo,
                ]);
            }
        }

        if (! $proveedor) {
            return redirect('/portal-proveedor')
                ->with('error', 'No se pudo cargar el onboarding. Cierra sesión e inicia de nuevo.');
        }

        try {
            $proveedor->load(['documentos', 'contactos']);
        } catch (\Exception $e) {
            // Si falla, el proveedor seguirá sin documentos cargados
        }

        $pasoRegistro = true;
        $pasoBancarios = $proveedor->tieneFormularioDatosBancarios();
        $pasoDocs = $proveedor->documentosFiscalesCompletos();
        $pasoDocsRenovar = $proveedor->documentosPorRenovar();
        $numContactos = $proveedor->contactos?->count() ?? $proveedor->contactos()->count();
        $pasoContactos = $numContactos >= 2;
        $pasoListoDireccion = $pasoBancarios && $pasoDocs && $pasoContactos;
        $pasoActivo = (bool) $proveedor->activo;

        $completados = (int) $pasoRegistro + (int) $pasoBancarios + (int) $pasoDocs + (int) $pasoContactos + (int) ($pasoListoDireccion && $pasoActivo ? 1 : 0);
        $totalPasos = 5;
        $pct = (int) round(100 * $completados / $totalPasos);

        return view('proveedores.onboarding', compact(
            'proveedor',
            'pasoRegistro',
            'pasoBancarios',
            'pasoDocs',
            'pasoDocsRenovar',
            'numContactos',
            'pasoContactos',
            'pasoListoDireccion',
            'pasoActivo',
            'completados',
            'totalPasos',
            'pct'
        ));
    }

    public function mostrarBusiness()
    {
        return view('proveedores.business');
    }

    public function mostrarPaymentHistory()
    {
        return view('proveedores.payment-history');
    }

    public function mostrarEncuesta()
    {
        return view('proveedores.encuesta');
    }

    public function guardarEncuestaProveedor(Request $request)
    {
        $request->validate([
            'calificacion' => 'required|integer|min:1|max:5',
            'comunicacion' => 'required|string',
            'pago_tiempo' => 'required|string',
            'proceso_oc' => 'required|string',
            'recomendaria' => 'required|string',
            'comentarios' => 'nullable|string|max:2000',
        ]);

        Encuesta::create([
            'codigo_cliente' => session('proveedor_codigo', 'PROV-'.session('proveedor_id')),
            'calificacion' => $request->input('calificacion'),
            'tiempo_entrega' => array_search($request->input('pago_tiempo'), ['siempre' => 1, 'casi_siempre' => 2, 'a_veces' => 3, 'nunca' => 4]) ?: 2,
            'calidad_producto' => array_search($request->input('comunicacion'), ['excelente' => 1, 'buena' => 2, 'regular' => 3, 'mala' => 4]) ?: 2,
            'comentarios' => $request->input('comentarios'),
        ]);

        return redirect()->route('proveedores.encuesta')->with('encuesta_guardada', true);
    }

    public function mostrarPerfil()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $contactos = $proveedor ? $proveedor->contactos()->orderBy('nombre')->get() : collect();

        return view('proveedores.perfil', compact('proveedor', 'contactos'));
    }

    public function subirFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (!$proveedor) {
            return back()->with('error', 'Proveedor no encontrado.');
        }

        // Eliminar foto anterior si existe
        if ($proveedor->foto && \Storage::disk('public')->exists($proveedor->foto)) {
            \Storage::disk('public')->delete($proveedor->foto);
        }

        $path = $request->file('foto')->store('proveedores-fotos', 'public');
        $proveedor->update(['foto' => $path]);

        return back()->with('mensaje', 'Foto actualizada correctamente.');
    }

    // ── Contactos ──

    public function guardarContacto(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'rol' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
        ]);

        ContactoProveedor::create([
            'proveedor_id' => session('proveedor_id'),
            'nombre' => $request->nombre,
            'rol' => $request->rol,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
        ]);

        return back()->with('mensaje', 'Contacto agregado correctamente.');
    }

    public function eliminarContacto(Request $request, ContactoProveedor $contacto)
    {
        if ($contacto->proveedor_id != session('proveedor_id')) {
            abort(403);
        }

        // Verificar contraseña del proveedor
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $password = $request->input('password') ?? $request->query('password');

        if (! $proveedor || ! $password || ! Hash::check($password, $proveedor->password)) {
            return back()->with('error_contacto', 'Contraseña incorrecta. No se eliminó el contacto.');
        }

        $contacto->delete();

        return back()->with('mensaje', 'Contacto eliminado.');
    }

    // ── Aviso de privacidad ──

    public function aceptarAvisoPrivacidad()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));

        if ($proveedor) {
            $proveedor->update([
                'aviso_privacidad_aceptado' => true,
                'aviso_privacidad_fecha' => now(),
            ]);
        }

        return back()->with('mensaje', 'Aviso de privacidad aceptado correctamente.');
    }

    /**
     * Exportar inventario a Excel XLSX con colores por familia.
     */
    public function exportarInventarioExcel()
    {
        $ddi = 90;
        $items = [
            ['SAL-001', 'Resina epóxica industrial', 'GUANGZHOU FASHI', 850, 260, 15, 0, 'KG', 85.00, 'Resinas'],
            ['SAL-003', 'Solvente grado técnico', 'INTERFLEX GROUP', 320, 180, 12, 0, 'LT', 42.50, 'Solventes'],
            ['SAL-005', 'Pigmento base agua', 'ALPHA AROMATICS', 90, 120, 20, 0, 'KG', 120.00, 'Pigmentos'],
            ['SAL-007', 'Catalizador rápido', 'QINGDAO GREENO', 45, 60, 18, 30, 'KG', 210.00, 'Aditivos'],
            ['SAL-009', 'Aditivo antioxidante', 'RECOCHEMIC INC', 0, 40, 25, 0, 'KG', 55.00, 'Aditivos'],
            ['SAL-011', 'Fibra de refuerzo', 'SALCOM INDUSTRIE', 900, 150, 10, 100, 'KG', 320.00, 'Refuerzos'],
            ['SAL-015', 'Adhesivo estructural', 'DCC GROUP COMP', 220, 90, 14, 0, 'KG', 180.00, 'Resinas'],
            ['SAL-018', 'Disolvente especial', 'HANGZHOU HUALIC', 15, 35, 22, 0, 'LT', 65.00, 'Solventes'],
            ['SAL-020', 'Sellador industrial', 'BOBSON HYGIENE', 0, 45, 16, 0, 'KG', 95.00, 'Selladores'],
            ['SAL-022', 'Recubrimiento base', 'NINGBO REVIEW T', 0, 30, 30, 0, 'KG', 78.00, 'Pigmentos'],
        ];

        $coloresGrupo = [
            'Resinas' => 'E3F2FD', 'Solventes' => 'FFF9C4', 'Pigmentos' => 'F3E5F5',
            'Aditivos' => 'E8F5E9', 'Refuerzos' => 'FFF3E0', 'Selladores' => 'E0F7FA',
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Máximos y Mínimos');

        // Headers
        $headers = ['GRUPO', 'CÓDIGO', 'PRODUCTO', 'PROVEEDOR', 'U.M.', 'PRECIO', 'EXISTENCIA', '%', 'CONSUMO/MES', 'CONSUMO ALTO', 'CONSUMO DIARIO', 'VENTAS/MES', 'STOCK MÍNIMO', 'STOCK MÁXIMO', 'DÍAS INVENTARIO', 'DÍAS ENTREGA', 'PEND. RECIBIR', 'CANTIDAD A PEDIR', 'COBERTURA', 'ESTADO'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $sheet->getStyle($col.'1')->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4A4A4A');
            $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Data rows with colors
        $rowNum = 2;
        foreach ($items as [$codigo, $nombre, $proveedor, $existencia, $consumoMes, $diasEntrega, $pendRecibir, $um, $precio, $grupo]) {
            $consumoDiario = round($consumoMes / 30, 2);
            $stockMinimo = round($consumoDiario * $diasEntrega);
            $stockMaximo = round($consumoDiario * $ddi);
            $diasInventario = $consumoDiario > 0 ? round($existencia / $consumoDiario) : 0;
            $totalAPedir = max(0, $stockMaximo - $existencia - $pendRecibir);
            $cobertura = $consumoDiario > 0 ? round($existencia / $consumoDiario) : 0;
            $ventasMes = $consumoMes * $precio;
            $porcentajeUso = $stockMaximo > 0 ? round(($existencia / $stockMaximo) * 100) : 0;
            $consumoAltoMes = round($consumoMes * 1.3);

            if ($existencia <= 0) {
                $estado = 'Agotado';
            } elseif ($existencia < $stockMinimo) {
                $estado = 'Bajo mínimo';
            } elseif ($existencia > $stockMaximo) {
                $estado = 'Sobre stock';
            } else {
                $estado = 'OK';
            }

            $data = [$grupo, $codigo, $nombre, $proveedor, $um, '$'.number_format($precio, 2), number_format($existencia), $porcentajeUso.'%', number_format($consumoMes), number_format($consumoAltoMes), number_format($consumoDiario, 2), '$'.number_format($ventasMes, 2), number_format($stockMinimo), number_format($stockMaximo), $diasInventario.' días', $diasEntrega.' días', number_format($pendRecibir), number_format($totalAPedir), $cobertura.' días', $estado];

            $col = 'A';
            foreach ($data as $val) {
                $sheet->setCellValue($col.$rowNum, $val);
                $col++;
            }

            // Aplicar color de fondo por familia
            $color = array_key_exists($grupo, $coloresGrupo) ? $coloresGrupo[$grupo] : 'FFFFFF';
            $sheet->getStyle("A{$rowNum}:T{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);

            $rowNum++;
        }

        // Auto-size
        foreach (range('A', 'T') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'inv_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'Stock_Maximos_Minimos_'.date('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function mostrarIdentificacion()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $identificacion = session('identificacion_proveedor')
            ?? ($proveedor?->datos_identificacion ?? []);

        if ($identificacion && ! session('identificacion_proveedor')) {
            session(['identificacion_proveedor' => $identificacion]);
        }

        return view('proveedores.identificacion_proveedor', compact('identificacion'));
    }

    public function guardarIdentificacion(Request $request)
    {
        $esFisica = $request->input('tipo_persona') === 'Persona Física';
        $esMoral = $request->input('tipo_persona') === 'Persona Moral';

        $rules = [
            'fecha' => 'required|date',
            'tipo_persona' => 'required|in:Persona Física,Persona Moral',
            'calle' => 'nullable|string|max:255',
            'num_exterior' => 'nullable|string|max:50',
            'num_interior' => 'nullable|string|max:50',
            'colonia' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'pais' => 'nullable|string|max:100',
            'cp' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:30',
            'celular' => 'nullable|string|max:30',
            'telefono2' => 'nullable|string|max:30',
            'extension' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'clabe' => 'nullable|string|max:18',
            'cuenta' => 'nullable|string|max:30',
            'banco' => 'nullable|string|max:255',
            'docs' => 'nullable|array',
            'nombre_firma' => 'nullable|string|max:255',
        ];

        if ($esFisica) {
            $rules['apellido_paterno'] = 'required|string|max:100';
            $rules['apellido_materno'] = 'nullable|string|max:100';
            $rules['nombres'] = 'required|string|max:150';
            $rules['razon_social'] = 'nullable|string|max:255';
        }

        if ($esMoral) {
            $rules['razon_social'] = 'required|string|max:255';
            $rules['apellido_paterno'] = 'nullable|string|max:100';
            $rules['apellido_materno'] = 'nullable|string|max:100';
            $rules['nombres'] = 'nullable|string|max:150';
        }

        $data = $request->validate($rules);

        $nombreEsperado = $esMoral
            ? trim($data['razon_social'] ?? '')
            : trim(implode(' ', array_filter([
                $data['apellido_paterno'] ?? '',
                $data['apellido_materno'] ?? '',
                $data['nombres'] ?? '',
            ])));

        $payload = array_merge($data, [
            'tipo_clave' => $esMoral ? 'moral' : 'fisica',
            'nombre_esperado' => $nombreEsperado,
        ]);

        session(['identificacion_proveedor' => $payload]);

        // Guardar solicitud de alta en la BD para el panel admin
        try {
            \App\Models\SolicitudAlta::create([
                'proveedor_id' => session('proveedor_id'),
                'tipo_persona' => $data['tipo_persona'],
                'nombre_completo' => $nombreEsperado,
                'razon_social' => $data['razon_social'] ?? null,
                'apellido_paterno' => $data['apellido_paterno'] ?? null,
                'apellido_materno' => $data['apellido_materno'] ?? null,
                'nombres' => $data['nombres'] ?? null,
                'calle' => $data['calle'] ?? null,
                'num_exterior' => $data['num_exterior'] ?? null,
                'num_interior' => $data['num_interior'] ?? null,
                'colonia' => $data['colonia'] ?? null,
                'municipio' => $data['municipio'] ?? null,
                'estado' => $data['estado'] ?? null,
                'ciudad' => $data['ciudad'] ?? null,
                'pais' => $data['pais'] ?? null,
                'cp' => $data['cp'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'celular' => $data['celular'] ?? null,
                'telefono2' => $data['telefono2'] ?? null,
                'extension' => $data['extension'] ?? null,
                'correo' => $data['correo'] ?? null,
                'clabe' => $data['clabe'] ?? null,
                'cuenta' => $data['cuenta'] ?? null,
                'banco' => $data['banco'] ?? null,
                'docs_marcados' => $data['docs'] ?? [],
                'nombre_firma' => $data['nombre_firma'] ?? null,
            ]);
        } catch (\Exception $e) {
            // La tabla aún no existe — se creará al correr migraciones
        }

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor) {
            try {
                $proveedor->update(['datos_identificacion' => $payload]);
            } catch (\Exception $e) {
                // La columna datos_identificacion puede no existir aún en producción
            }
        }

        return redirect()->route('proveedores.identificacion')
            ->with('exito', 'Se enviaron los documentos para validar correctamente. Tu solicitud fue recibida por el equipo de Industrias Salcom.');
    }

    public function mostrarValidacionFiscal()
    {
        $identificacion = session('identificacion_proveedor');
        if (! $identificacion) {
            $proveedor = ProveedorUser::find(session('proveedor_id'));
            $identificacion = $proveedor?->datos_identificacion;
            if ($identificacion) {
                session(['identificacion_proveedor' => $identificacion]);
            }
        }

        $solicitudId = null;

        return view('APIS.empresa', compact('identificacion', 'solicitudId'));
    }

    /**
     * Alta de facturas — formulario con historial reciente.
     */
    public function mostrarAltaFacturas()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $codigo = $proveedor?->id_proveedor ?: session('proveedor_codigo');

        $facturas = Factura::query()
            ->when($codigo, fn ($q) => $q->where('codigo_proveedor', $codigo))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $stats = [
            'total' => $facturas->count(),
            'pendientes' => $facturas->where('estatus', 'pendiente')->count(),
            'rechazadas' => $facturas->where('estatus', 'rechazada')->count(),
            'fleteras' => $facturas->where('es_fletera', true)->count(),
        ];

        $rfcProveedor = $this->rfcProveedorSesion($proveedor);

        return view('proveedores.fiscal', compact('facturas', 'stats', 'rfcProveedor', 'proveedor'));
    }

    /**
     * Alta de factura: XML + PDF, validación de régimen / fletera / retenciones.
     */
    public function altaFactura(Request $request, AltaFacturaValidationService $validator)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:pdf|max:10240',
            'archivo_xml' => 'required|file|mimes:xml|max:5120',
            'archivo_oc' => 'nullable|file|mimes:pdf|max:10240',
            'es_fletera' => 'required|in:0,1',
            'notas' => 'nullable|string|max:500',
        ], [
            'archivo.required' => 'La factura en PDF es obligatoria.',
            'archivo_xml.required' => 'El XML de la factura es obligatorio.',
            'es_fletera.required' => 'Indica si la factura es de fletera o no.',
        ]);

        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return back()->withErrors(['archivo' => 'Sesión de proveedor no válida. Vuelve a iniciar sesión.']);
        }

        $pdf = $request->file('archivo');
        $xmlFile = $request->file('archivo_xml');

        $pdfContent = file_get_contents($pdf->getRealPath());
        if (! str_starts_with($pdfContent, '%PDF')) {
            return back()->withInput()->with('fiscal_resultado', [
                'aprobado' => false,
                'mensaje' => 'El archivo PDF no es válido.',
                'errores' => ['El archivo de factura no es un PDF real.'],
                'checklist' => [],
            ]);
        }

        $xmlContent = file_get_contents($xmlFile->getRealPath());
        $esFletera = $request->input('es_fletera') === '1';
        $rfcProveedor = $this->rfcProveedorSesion($proveedor);

        $resultado = $validator->validar($xmlContent, $esFletera, $rfcProveedor);

        $dir = 'facturas-proveedor/'.$proveedor->id;
        $pathPdf = $pdf->store($dir, 'public');
        $pathXml = $xmlFile->store($dir, 'public');
        $pathOc = $request->hasFile('archivo_oc')
            ? $request->file('archivo_oc')->store($dir, 'public')
            : null;

        $datos = $resultado['datos'];
        $folio = $datos['uuid']
            ?: trim(($datos['serie'] ?? '').($datos['folio'] ?? ''))
            ?: ('TMP-'.uniqid());

        // Evitar choque con unique folio_cfdi si UUID ya falló por duplicado
        if (! $resultado['aprobado'] && $datos['uuid'] && Factura::where('uuid_cfdi', $datos['uuid'])->exists()) {
            Storage::disk('public')->delete(array_filter([$pathPdf, $pathXml, $pathOc]));

            return back()->withInput()->with('fiscal_resultado', [
                'aprobado' => false,
                'mensaje' => 'La factura fue rechazada.',
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias'],
                'checklist' => $resultado['checklist'],
                'datos' => $datos,
            ]);
        }

        $dias = (int) config('facturas.dias_vencimiento', 30);
        $codigoProv = $proveedor->id_proveedor ?: session('proveedor_codigo');

        if ($resultado['aprobado']) {
            // Si folio_cfdi ya existe con otro UUID, generar uno derivado
            $folioCfdi = $folio;
            if (Factura::where('folio_cfdi', $folioCfdi)->exists()) {
                $folioCfdi = $folioCfdi.'-'.substr(uniqid(), -4);
            }

            Factura::create([
                'folio_cfdi' => $folioCfdi,
                'uuid_cfdi' => $datos['uuid'],
                'codigo_proveedor' => $codigoProv,
                'regimen_fiscal' => $datos['regimen_fiscal'],
                'es_fletera' => $esFletera,
                'monto' => $datos['subtotal'],
                'monto_iva' => $datos['iva'],
                'retencion_iva' => $datos['retencion_iva'],
                'retencion_isr' => $datos['retencion_isr'],
                'total' => $datos['total'] ?: ($datos['subtotal'] + $datos['iva']),
                'estatus' => 'pendiente',
                'fecha_vencimiento' => now()->addDays($dias)->toDateString(),
                'archivo_pdf' => $pathPdf,
                'archivo_xml' => $pathXml,
                'archivo_oc' => $pathOc,
                'notas' => $request->input('notas'),
                'validacion_detalle' => [
                    'checklist' => $resultado['checklist'],
                    'advertencias' => $resultado['advertencias'],
                    'retencion_esperada' => $datos['retencion_esperada'],
                    'rfc_emisor' => $datos['rfc_emisor'],
                    'regimen_nombre' => $datos['regimen_nombre'],
                    'validado_at' => now()->toIso8601String(),
                ],
            ]);

            return back()->with('fiscal_resultado', [
                'aprobado' => true,
                'mensaje' => 'Factura validada y registrada correctamente. Queda pendiente de revisión contable.',
                'errores' => [],
                'advertencias' => $resultado['advertencias'],
                'checklist' => $resultado['checklist'],
                'datos' => $datos,
            ]);
        }

        // Rechazada: guardar registro para trazabilidad
        $folioRechazo = 'RECH-'.strtoupper(substr(uniqid(), -8));
        Factura::create([
            'folio_cfdi' => $folioRechazo,
            'uuid_cfdi' => null,
            'codigo_proveedor' => $codigoProv,
            'regimen_fiscal' => $datos['regimen_fiscal'],
            'es_fletera' => $esFletera,
            'monto' => $datos['subtotal'] ?: 0,
            'monto_iva' => $datos['iva'] ?: 0,
            'retencion_iva' => $datos['retencion_iva'] ?: 0,
            'retencion_isr' => $datos['retencion_isr'] ?: 0,
            'total' => $datos['total'] ?: 0,
            'estatus' => 'rechazada',
            'fecha_vencimiento' => null,
            'archivo_pdf' => $pathPdf,
            'archivo_xml' => $pathXml,
            'archivo_oc' => $pathOc,
            'notas' => $request->input('notas'),
            'validacion_detalle' => [
                'checklist' => $resultado['checklist'],
                'errores' => $resultado['errores'],
                'advertencias' => $resultado['advertencias'],
                'datos_parciales' => $datos,
                'validado_at' => now()->toIso8601String(),
            ],
        ]);

        return back()->withInput()->with('fiscal_resultado', [
            'aprobado' => false,
            'mensaje' => 'La factura fue rechazada por validación fiscal.',
            'errores' => $resultado['errores'],
            'advertencias' => $resultado['advertencias'],
            'checklist' => $resultado['checklist'],
            'datos' => $datos,
        ]);
    }

    /**
     * RFC del proveedor desde identificación o input.
     */
    private function rfcProveedorSesion(?ProveedorUser $proveedor): ?string
    {
        if (! $proveedor) {
            return session('proveedor_rfc') ?: null;
        }

        $datos = $proveedor->datos_identificacion ?? [];
        $rfc = $datos['rfc'] ?? $datos['RFC'] ?? null;
        if ($rfc) {
            return strtoupper(trim((string) $rfc));
        }

        return session('proveedor_rfc') ?: null;
    }

    /**
     * Subir documento fiscal de onboarding (CIF, opinión, etc.).
     */
    public function subirDocumentoFiscal(Request $request)
    {
        $request->validate([
            'tipo_documento' => 'required|string',
            'archivo' => 'required|file|mimes:pdf|max:10240',
        ]);

        $tipo = $request->input('tipo_documento');
        $rfc = $request->input('rfc', '');
        $notas = $request->input('notas', '');
        $provId = session('proveedor_id');

        $path = $request->file('archivo')->store('documentos-fiscales', 'public');

        $errores = [];
        $archivo = $request->file('archivo');

        if ($archivo->getSize() < 1024) {
            $errores[] = 'El archivo parece estar vacío o corrupto.';
        }

        $contenido = file_get_contents($archivo->getRealPath());
        if (! str_starts_with($contenido, '%PDF')) {
            $errores[] = 'El archivo no es un PDF válido.';
        }

        if ($rfc && ! preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i', $rfc)) {
            $errores[] = "El RFC '{$rfc}' no tiene un formato válido. Debe ser 12 o 13 caracteres (ej: ABC123456XY7).";
        }

        $tipoLabel = match ($tipo) {
            'cif' => 'Constancia de Situación Fiscal',
            'opinion' => 'Opinión de cumplimiento SAT',
            'acta' => 'Acta constitutiva',
            'rep_legal' => 'INE Representante legal',
            'caratula_banco' => 'Carátula bancaria',
            'comprobante_domicilio' => 'Comprobante de domicilio',
            default => ucfirst($tipo),
        };

        if (empty($errores)) {
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $provId, 'tipo' => $tipo],
                [
                    'archivo' => $path,
                    'estatus' => 'aprobado',
                    'notas_revision' => 'Validado automáticamente por IA. '.($notas ?: ''),
                    'revisado_at' => now(),
                ]
            );

            return back()->with('fiscal_resultado', [
                'aprobado' => true,
                'mensaje' => "{$tipoLabel} validado correctamente. El documento cumple con los requisitos y fue aprobado automáticamente.",
            ]);
        }

        DocumentoProveedor::updateOrCreate(
            ['proveedor_id' => $provId, 'tipo' => $tipo],
            [
                'archivo' => $path,
                'estatus' => 'rechazado',
                'notas_revision' => 'Rechazado por IA: '.implode(' | ', $errores),
                'revisado_at' => now(),
            ]
        );

        return back()->with('fiscal_resultado', [
            'aprobado' => false,
            'mensaje' => 'El documento fue rechazado. Errores: '.implode(' ', $errores).' Corrige y vuelve a subir.',
        ]);
    }
}
