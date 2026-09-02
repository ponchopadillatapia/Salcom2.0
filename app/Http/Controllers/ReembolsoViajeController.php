<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\ReembolsoViaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReembolsoViajeController extends Controller
{
    public function index(Request $request)
    {
        // Proteger contra tabla inexistente en producción
        try {
            if (! Schema::hasTable('reembolsos_viaje')) {
                return view('admin.reembolsos-viaje.index', [
                    'solicitudes' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                    'kpis' => ['borradores' => 0, 'enviados' => 0, 'aprobados' => 0, 'reembolsados' => 0, 'total_pendiente' => 0],
                ]);
            }
        } catch (\Exception $e) {
            return view('admin.reembolsos-viaje.index', [
                'solicitudes' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'kpis' => ['borradores' => 0, 'enviados' => 0, 'aprobados' => 0, 'reembolsados' => 0, 'total_pendiente' => 0],
            ]);
        }

        $query = ReembolsoViaje::query();

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }
        if ($request->filled('busqueda')) {
            $b = $request->input('busqueda');
            $query->where(function ($q) use ($b) {
                $q->where('codigo_empleado', 'like', "%{$b}%")
                  ->orWhere('nombre_empleado', 'like', "%{$b}%")
                  ->orWhere('pais_destino', 'like', "%{$b}%");
            });
        }

        $solicitudes = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        $kpis = [
            'borradores' => ReembolsoViaje::where('estatus', 'borrador')->count(),
            'enviados' => ReembolsoViaje::where('estatus', 'enviado')->count(),
            'aprobados' => ReembolsoViaje::where('estatus', 'aprobado')->count(),
            'reembolsados' => ReembolsoViaje::where('estatus', 'reembolsado')->count(),
            'total_pendiente' => (float) ReembolsoViaje::where('estatus', 'enviado')->sum('total_moneda_base'),
        ];

        return view('admin.reembolsos-viaje.index', compact('solicitudes', 'kpis'));
    }

    public function crear()
    {
        $paises = ReembolsoViaje::PAISES_MONEDA;
        $conceptos = ReembolsoViaje::CONCEPTOS_GASTO;

        return view('admin.reembolsos-viaje.crear', compact('paises', 'conceptos'));
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'codigo_empleado' => 'required|string|max:50',
            'nombre_empleado' => 'required|string|max:255',
            'departamento' => 'nullable|string|max:100',
            'fecha_salida' => 'required|date',
            'fecha_regreso' => 'required|date|after_or_equal:fecha_salida',
            'pais_destino' => 'required|string|max:100',
            'moneda_destino' => 'required|string|max:10',
            'tipo_cambio' => 'required|numeric|min:0.0001',
            'gastos' => 'required|array|min:1',
            'gastos.*.concepto' => 'required|string|max:100',
            'gastos.*.monto_local' => 'required|numeric|min:0',
            'archivo_comprobantes' => 'nullable|file|mimes:pdf,jpg,jpeg,png,zip|max:20480',
            'factura_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'factura_xml' => 'nullable|file|max:5120',
            'notas' => 'nullable|string|max:1000',
        ], [
            'gastos.required' => 'Agrega al menos un concepto de gasto.',
            'gastos.*.concepto.required' => 'Cada gasto debe tener un concepto.',
            'gastos.*.monto_local.required' => 'Cada gasto debe tener un monto.',
            'tipo_cambio.required' => 'El tipo de cambio es obligatorio.',
        ]);

        $tipoCambio = (float) $request->input('tipo_cambio');
        $gastos = [];
        $totalLocal = 0;

        foreach ($request->input('gastos') as $gasto) {
            $montoLocal = (float) ($gasto['monto_local'] ?? 0);
            $montoBase = round($montoLocal * $tipoCambio, 2);
            $totalLocal += $montoLocal;
            $gastos[] = [
                'concepto' => $gasto['concepto'],
                'monto_local' => $montoLocal,
                'monto_base' => $montoBase,
            ];
        }

        $totalBase = round($totalLocal * $tipoCambio, 2);

        $pathArchivo = null;
        if ($request->hasFile('archivo_comprobantes')) {
            $pathArchivo = $request->file('archivo_comprobantes')->store('reembolsos-viaje', 'public');
        }

        $pathFacturaPdf = null;
        if ($request->hasFile('factura_pdf')) {
            $pathFacturaPdf = $request->file('factura_pdf')->store('reembolsos-viaje/facturas', 'public');
        }

        $pathFacturaXml = null;
        if ($request->hasFile('factura_xml')) {
            $pathFacturaXml = $request->file('factura_xml')->store('reembolsos-viaje/xml', 'public');
        }

        $reembolso = ReembolsoViaje::create([
            'codigo_empleado' => $request->input('codigo_empleado'),
            'nombre_empleado' => $request->input('nombre_empleado'),
            'departamento' => $request->input('departamento'),
            'fecha_salida' => $request->input('fecha_salida'),
            'fecha_regreso' => $request->input('fecha_regreso'),
            'pais_destino' => $request->input('pais_destino'),
            'moneda_destino' => $request->input('moneda_destino'),
            'tipo_cambio' => $tipoCambio,
            'moneda_base' => 'MXN',
            'gastos' => $gastos,
            'total_moneda_local' => $totalLocal,
            'total_moneda_base' => $totalBase,
            'estatus' => 'borrador',
            'archivo_comprobantes' => json_encode(array_filter([
                'comprobantes' => $pathArchivo,
                'factura_pdf' => $pathFacturaPdf,
                'factura_xml' => $pathFacturaXml,
            ])),
            'notas' => $request->input('notas'),
        ]);

        return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
            ->with('mensaje', 'Solicitud de reembolso creada como borrador.');
    }

    public function ver(ReembolsoViaje $reembolso)
    {
        $paises = ReembolsoViaje::PAISES_MONEDA;
        $conceptos = ReembolsoViaje::CONCEPTOS_GASTO;

        return view('admin.reembolsos-viaje.ver', compact('reembolso', 'paises', 'conceptos'));
    }

    public function editar(ReembolsoViaje $reembolso)
    {
        if (! $reembolso->estaEditable()) {
            return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
                ->with('error', 'Esta solicitud ya fue enviada y no se puede editar.');
        }

        $paises = ReembolsoViaje::PAISES_MONEDA;
        $conceptos = ReembolsoViaje::CONCEPTOS_GASTO;

        return view('admin.reembolsos-viaje.editar', compact('reembolso', 'paises', 'conceptos'));
    }

    public function actualizar(Request $request, ReembolsoViaje $reembolso)
    {
        if (! $reembolso->estaEditable()) {
            return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
                ->with('error', 'Esta solicitud ya fue enviada y no se puede editar.');
        }

        $request->validate([
            'codigo_empleado' => 'required|string|max:50',
            'nombre_empleado' => 'required|string|max:255',
            'departamento' => 'nullable|string|max:100',
            'fecha_salida' => 'required|date',
            'fecha_regreso' => 'required|date|after_or_equal:fecha_salida',
            'pais_destino' => 'required|string|max:100',
            'moneda_destino' => 'required|string|max:10',
            'tipo_cambio' => 'required|numeric|min:0.0001',
            'gastos' => 'required|array|min:1',
            'gastos.*.concepto' => 'required|string|max:100',
            'gastos.*.monto_local' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ]);

        $tipoCambio = (float) $request->input('tipo_cambio');
        $gastos = [];
        $totalLocal = 0;
        foreach ($request->input('gastos') as $gasto) {
            $montoLocal = (float) ($gasto['monto_local'] ?? 0);
            $totalLocal += $montoLocal;
            $gastos[] = [
                'concepto' => $gasto['concepto'],
                'monto_local' => $montoLocal,
                'monto_base' => round($montoLocal * $tipoCambio, 2),
            ];
        }
        $totalBase = round($totalLocal * $tipoCambio, 2);

        $reembolso->update([
            'codigo_empleado' => $request->input('codigo_empleado'),
            'nombre_empleado' => $request->input('nombre_empleado'),
            'departamento' => $request->input('departamento'),
            'fecha_salida' => $request->input('fecha_salida'),
            'fecha_regreso' => $request->input('fecha_regreso'),
            'pais_destino' => $request->input('pais_destino'),
            'moneda_destino' => $request->input('moneda_destino'),
            'tipo_cambio' => $tipoCambio,
            'gastos' => $gastos,
            'total_moneda_local' => $totalLocal,
            'total_moneda_base' => $totalBase,
            'notas' => $request->input('notas'),
        ]);

        return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
            ->with('mensaje', 'Solicitud actualizada correctamente.');
    }

    public function enviar(ReembolsoViaje $reembolso)
    {
        if (! $reembolso->estaEditable()) {
            return back()->with('error', 'Esta solicitud ya fue enviada.');
        }

        $reembolso->update([
            'estatus' => ReembolsoViaje::ESTATUS_ENVIADO,
            'enviado_at' => now(),
        ]);

        try {
            Alerta::create([
                'tipo' => 'reembolso_viaje_enviado',
                'modulo' => 'reembolsos_viaje',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 0,
                'titulo' => 'Reembolso de viaje enviado',
                'contenido' => $reembolso->nombre_empleado . ' envió solicitud de reembolso por $' . number_format($reembolso->total_moneda_base, 2) . ' MXN (' . $reembolso->pais_destino . ')',
                'datos' => ['reembolso_id' => $reembolso->id],
                'estatus' => 'pendiente',
                'nivel' => 'info',
            ]);
        } catch (\Exception $e) {
        }

        return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
            ->with('mensaje', 'Solicitud enviada para revisión. Ya no se puede editar.');
    }

    public function aprobar(Request $request, ReembolsoViaje $reembolso)
    {
        $reembolso->update([
            'estatus' => ReembolsoViaje::ESTATUS_APROBADO,
            'aprobado_at' => now(),
            'aprobado_por' => session('admin_id'),
            'notas_revision' => $request->input('notas_revision'),
        ]);

        return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
            ->with('mensaje', 'Reembolso aprobado.');
    }

    public function rechazar(Request $request, ReembolsoViaje $reembolso)
    {
        $request->validate(['notas_revision' => 'required|string|max:500']);

        $reembolso->update([
            'estatus' => ReembolsoViaje::ESTATUS_RECHAZADO,
            'notas_revision' => $request->input('notas_revision'),
        ]);

        return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
            ->with('mensaje', 'Reembolso rechazado.');
    }

    public function marcarReembolsado(ReembolsoViaje $reembolso)
    {
        $reembolso->update(['estatus' => ReembolsoViaje::ESTATUS_REEMBOLSADO]);

        return redirect()->route('admin.reembolsos-viaje.ver', $reembolso)
            ->with('mensaje', 'Marcado como reembolsado.');
    }

    public function exportarExcel()
    {
        $reembolsos = ReembolsoViaje::orderByDesc('created_at')->get();

        $output = "\xEF\xBB\xBF";
        $output .= "REEMBOLSOS DE VIAJE — DESGLOSE DE GASTOS\r\n";
        $output .= "Generado: " . now()->format('d/m/Y H:i') . "\r\n\r\n";
        $output .= "ID,Codigo Empleado,Nombre Empleado,Departamento,Pais Destino,Moneda,Tipo Cambio,Concepto,Monto Moneda Local,Equivalente MXN,Estatus,Fecha Solicitud\r\n";

        foreach ($reembolsos as $r) {
            $gastos = $r->gastos ?? [];
            if (empty($gastos)) {
                $output .= implode(',', [
                    $r->id,
                    '"' . $r->codigo_empleado . '"',
                    '"' . str_replace('"', '""', $r->nombre_empleado) . '"',
                    '"' . str_replace('"', '""', $r->departamento ?? '') . '"',
                    '"' . $r->pais_destino . '"',
                    $r->moneda_destino,
                    $r->tipo_cambio,
                    '""',
                    '0',
                    '0',
                    $r->estatus,
                    $r->created_at?->format('d/m/Y'),
                ]) . "\r\n";
            } else {
                foreach ($gastos as $gasto) {
                    $output .= implode(',', [
                        $r->id,
                        '"' . $r->codigo_empleado . '"',
                        '"' . str_replace('"', '""', $r->nombre_empleado) . '"',
                        '"' . str_replace('"', '""', $r->departamento ?? '') . '"',
                        '"' . $r->pais_destino . '"',
                        $r->moneda_destino,
                        $r->tipo_cambio,
                        '"' . str_replace('"', '""', $gasto['concepto'] ?? '') . '"',
                        number_format($gasto['monto_local'] ?? 0, 2, '.', ''),
                        number_format($gasto['monto_base'] ?? 0, 2, '.', ''),
                        $r->estatus,
                        $r->created_at?->format('d/m/Y'),
                    ]) . "\r\n";
                }
                // Fila de total
                $output .= implode(',', [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '"TOTAL"',
                    number_format($r->total_moneda_local, 2, '.', ''),
                    number_format($r->total_moneda_base, 2, '.', ''),
                    '',
                    '',
                ]) . "\r\n";
            }
        }

        $filename = 'Reembolsos_Viaje_' . now()->format('Y-m-d') . '.csv';

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
