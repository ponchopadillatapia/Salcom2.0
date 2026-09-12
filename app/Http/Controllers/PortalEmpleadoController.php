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
