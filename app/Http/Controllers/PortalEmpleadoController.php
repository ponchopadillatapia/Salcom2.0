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

        return view('empleados.portal', compact('reembolsos', 'viajes', 'gasolina', 'numero'));
    }
}
