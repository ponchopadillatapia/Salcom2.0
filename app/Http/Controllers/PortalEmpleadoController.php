<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Empleado;
use App\Models\ReembolsoViaje;
use Illuminate\Http\Request;

class PortalEmpleadoController extends Controller
{
    // ── Autenticación ──

    public function mostrarLogin()
    {
        return view('empleados.login');
    }

    public function procesarLogin(Request $request)
    {
        $request->validate([
            'numero_empleado' => 'required|string|max:50',
        ], [
            'numero_empleado.required' => 'Ingresa tu número de empleado.',
        ]);

        $numero = trim($request->input('numero_empleado'));
        $empleado = Empleado::where('numero_empleado', $numero)->where('activo', true)->first();

        if (! $empleado) {
            return back()->withErrors(['numero_empleado' => 'Número de empleado no encontrado o inactivo.'])->withInput();
        }

        session([
            'empleado_id' => $empleado->id,
            'empleado_numero' => $empleado->numero_empleado,
            'empleado_nombre' => $empleado->nombre,
            'empleado_departamento' => $empleado->departamento,
        ]);

        return redirect()->route('empleados.portal');
    }

    public function cerrarSesion(Request $request)
    {
        session()->forget(['empleado_id', 'empleado_numero', 'empleado_nombre', 'empleado_departamento']);

        return redirect()->route('empleados.login');
    }

    // ── Portal ──

    public function portal()
    {
        $numero = session('empleado_numero');

        $reembolsos = Alerta::where('tipo', 'solicitud_reembolso')->orderByDesc('created_at')->get()
            ->filter(fn ($r) => ($r->datos['numero_empleado'] ?? null) == $numero)->values();

        $viajes = collect();
        try {
            $viajes = ReembolsoViaje::where('codigo_empleado', $numero)->orderByDesc('created_at')->get();
        } catch (\Exception $e) {
        }

        $gasolina = Alerta::where('tipo', 'bitacora_gasolina')->orderByDesc('created_at')->get()
            ->filter(fn ($r) => ($r->datos['numero_empleado'] ?? null) == $numero)->values();

        $empleado = Empleado::find(session('empleado_id'));

        return view('empleados.portal', compact('reembolsos', 'viajes', 'gasolina', 'numero', 'empleado'));
    }

    // ── Empleado: registrar bitácora de gasolina ──
    public function guardarGasolina(Request $request)
    {
        $request->validate([
            'cantidad_litros' => 'nullable|numeric|min:0',
            'rendimiento' => 'nullable|numeric|min:0',
            'monto' => 'required|string|max:20',
            'vehiculo' => 'nullable|string|max:100',
            'kilometraje' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:255',
            'factura_gasolina' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $pathFactura = $request->hasFile('factura_gasolina')
            ? $request->file('factura_gasolina')->store('bitacora-gasolina', 'public')
            : null;

        Alerta::create([
            'tipo' => 'bitacora_gasolina',
            'modulo' => 'gasolina',
            'destinatario_tipo' => 'admin',
            'destinatario_id' => 0,
            'titulo' => 'Gasolina: $' . $request->input('monto') . ' — ' . session('empleado_nombre'),
            'contenido' => ($request->input('vehiculo') ?? '') . ' | ' . now()->format('Y-m-d'),
            'datos' => [
                'fecha' => now()->format('Y-m-d'),
                'numero_empleado' => session('empleado_numero'),
                'empleado' => session('empleado_nombre'),
                'cantidad_litros' => $request->input('cantidad_litros'),
                'rendimiento' => $request->input('rendimiento'),
                'monto' => $request->input('monto'),
                'vehiculo' => $request->input('vehiculo'),
                'kilometraje' => $request->input('kilometraje'),
                'notas' => $request->input('notas'),
                'factura' => $pathFactura,
            ],
            'estatus' => 'pendiente',
            'nivel' => 'info',
        ]);

        return redirect()->route('empleados.portal')->with('mensaje', 'Registro de gasolina guardado.');
    }

    // ── Empleado: registrar reembolso ──
    public function guardarReembolso(Request $request)
    {
        $empleado = Empleado::find(session('empleado_id'));

        // Si es de ruta/gasolina, debe tener bitácora primero
        if ($empleado && $empleado->requiere_gasolina) {
            $suBitacora = Alerta::where('tipo', 'bitacora_gasolina')->get()
                ->first(fn ($r) => ($r->datos['numero_empleado'] ?? null) == session('empleado_numero'));
            if (! $suBitacora) {
                return back()->withErrors(['general' => 'Debes registrar primero tu Bitácora de Gasolina antes de pedir un reembolso.'])->withInput();
            }
        }

        $request->validate([
            'categoria' => 'required|string|in:gasto_general,gasolina,computo,viaticos_nacional',
            'razon_social' => 'required|string|in:Industrias Salcom S.A. de C.V.,Franfoods S.A. de C.V.',
            'metodo_pago_empresa' => 'required|string|in:bbva,inntec',
            'monto' => 'required|string|max:20',
            'concepto' => 'required|string|max:255',
            'archivo_factura' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'archivo_materialidad' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $pathFactura = $request->file('archivo_factura')->store('reembolsos/facturas', 'public');
        $pathMaterialidad = $request->hasFile('archivo_materialidad')
            ? $request->file('archivo_materialidad')->store('reembolsos/materialidad', 'public')
            : null;

        Alerta::create([
            'tipo' => 'solicitud_reembolso',
            'modulo' => 'reembolsos',
            'destinatario_tipo' => 'admin',
            'destinatario_id' => 0,
            'titulo' => 'Reembolso: $' . $request->input('monto') . ' — ' . session('empleado_nombre'),
            'contenido' => $request->input('concepto'),
            'datos' => [
                'categoria' => $request->input('categoria'),
                'razon_social' => $request->input('razon_social'),
                'metodo_pago_empresa' => $request->input('metodo_pago_empresa'),
                'monto' => $request->input('monto'),
                'concepto' => $request->input('concepto'),
                'solicitante' => session('empleado_nombre'),
                'numero_empleado' => session('empleado_numero'),
                'numero_cuenta' => $empleado->numero_cuenta ?? null,
                'titular_cuenta' => $empleado->titular_cuenta ?? null,
                'fecha_factura' => now()->format('Y-m-d'),
                'archivo_factura' => $pathFactura,
                'archivo_materialidad' => $pathMaterialidad,
            ],
            'estatus' => 'pendiente',
            'nivel' => 'info',
        ]);

        return redirect()->route('empleados.portal')->with('mensaje', 'Reembolso enviado correctamente.');
    }

    // ── Empleado: registrar reembolso de viaje ──
    public function crearViaje()
    {
        $paises = ReembolsoViaje::PAISES_MONEDA;
        $conceptos = ReembolsoViaje::CONCEPTOS_GASTO;
        $empleado = Empleado::find(session('empleado_id'));

        return view('empleados.viaje-crear', compact('paises', 'conceptos', 'empleado'));
    }

    public function guardarViaje(Request $request)
    {
        $request->validate([
            'fecha_salida' => 'required|date',
            'fecha_regreso' => 'required|date|after_or_equal:fecha_salida',
            'pais_destino' => 'required|string|max:100',
            'moneda_destino' => 'required|string|max:10',
            'tipo_cambio' => 'required|numeric|min:0.0001',
            'gastos' => 'required|array|min:1',
            'gastos.*.concepto' => 'required|string|max:100',
            'gastos.*.monto_local' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ], [
            'gastos.required' => 'Agrega al menos un concepto de gasto.',
        ]);

        // POR QUÉ: convertimos cada gasto a MXN al momento de guardar para que el
        // total en moneda base quede fijo aunque el tipo de cambio cambie después.
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

        ReembolsoViaje::create([
            'codigo_empleado' => session('empleado_numero'),
            'nombre_empleado' => session('empleado_nombre'),
            'departamento' => session('empleado_departamento'),
            'fecha_salida' => $request->input('fecha_salida'),
            'fecha_regreso' => $request->input('fecha_regreso'),
            'pais_destino' => $request->input('pais_destino'),
            'moneda_destino' => $request->input('moneda_destino'),
            'tipo_cambio' => $tipoCambio,
            'moneda_base' => 'MXN',
            'gastos' => $gastos,
            'total_moneda_local' => $totalLocal,
            'total_moneda_base' => round($totalLocal * $tipoCambio, 2),
            'estatus' => 'borrador',
            'notas' => $request->input('notas'),
        ]);

        return redirect()->route('empleados.portal')->with('mensaje', 'Reembolso de viaje creado como borrador.');
    }

    // ── Buscar empleado por número (para autocompletar en reembolsos) ──
    // POR QUÉ: el admin captura reembolsos y necesita que al teclear el número
    // se llenen solos los datos de tarjeta que ya están dados de alta, sin re-escribirlos.
    public function buscarPorNumero(string $numero)
    {
        $empleado = Empleado::where('numero_empleado', trim($numero))->first();

        if (! $empleado) {
            return response()->json(['encontrado' => false], 404);
        }

        return response()->json([
            'encontrado' => true,
            'nombre' => $empleado->nombre,
            'numero_cuenta' => $empleado->numero_cuenta,
            'titular_cuenta' => $empleado->titular_cuenta,
            'departamento' => $empleado->departamento,
            'requiere_gasolina' => (bool) $empleado->requiere_gasolina,
        ]);
    }

    // ── Admin: Gestión de empleados ──

    public function adminIndex(Request $request)
    {
        $query = Empleado::query()->orderBy('nombre');

        if ($request->filled('busqueda')) {
            $b = $request->input('busqueda');
            $query->where(function ($q) use ($b) {
                $q->where('numero_empleado', 'like', "%{$b}%")
                  ->orWhere('nombre', 'like', "%{$b}%")
                  ->orWhere('departamento', 'like', "%{$b}%");
            });
        }

        $empleados = $query->paginate(30)->withQueryString();

        return view('admin.empleados.index', compact('empleados'));
    }

    public function adminGuardar(Request $request)
    {
        $request->validate([
            'numero_empleado' => 'required|string|max:50|unique:empleados,numero_empleado',
            'nombre' => 'required|string|max:255',
            'departamento' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:255',
            'numero_cuenta' => 'nullable|string|max:30',
            'titular_cuenta' => 'nullable|string|max:255',
        ], [
            'numero_empleado.unique' => 'Ese número de empleado ya existe.',
            'numero_empleado.required' => 'El número de empleado es obligatorio.',
            'nombre.required' => 'El nombre es obligatorio.',
        ]);

        Empleado::create([
            'numero_empleado' => trim($request->input('numero_empleado')),
            'nombre' => $request->input('nombre'),
            'departamento' => $request->input('departamento'),
            'correo' => $request->input('correo'),
            'numero_cuenta' => $request->input('numero_cuenta'),
            'titular_cuenta' => $request->input('titular_cuenta'),
            'requiere_gasolina' => $request->boolean('requiere_gasolina'),
            'activo' => true,
        ]);

        return redirect()->route('admin.empleados')->with('mensaje', 'Empleado dado de alta correctamente.');
    }

    public function adminToggle(Empleado $empleado)
    {
        $empleado->update(['activo' => ! $empleado->activo]);

        return redirect()->route('admin.empleados')
            ->with('mensaje', 'Empleado ' . ($empleado->activo ? 'activado' : 'desactivado') . '.');
    }

    public function adminActualizar(Request $request, Empleado $empleado)
    {
        $request->validate([
            'numero_empleado' => 'required|string|max:50|unique:empleados,numero_empleado,' . $empleado->id,
            'nombre' => 'required|string|max:255',
            'departamento' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:255',
            'numero_cuenta' => 'nullable|string|max:30',
            'titular_cuenta' => 'nullable|string|max:255',
        ]);

        $empleado->update([
            'numero_empleado' => trim($request->input('numero_empleado')),
            'nombre' => $request->input('nombre'),
            'departamento' => $request->input('departamento'),
            'correo' => $request->input('correo'),
            'numero_cuenta' => $request->input('numero_cuenta'),
            'titular_cuenta' => $request->input('titular_cuenta'),
            'requiere_gasolina' => $request->boolean('requiere_gasolina'),
        ]);

        return redirect()->route('admin.empleados')->with('mensaje', 'Empleado actualizado.');
    }

    public function adminEliminar(Empleado $empleado)
    {
        $empleado->delete();

        return redirect()->route('admin.empleados')->with('mensaje', 'Empleado eliminado.');
    }
}
