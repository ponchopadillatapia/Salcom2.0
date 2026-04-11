<?php

namespace App\Http\Controllers;

use App\Models\Muestra;
use Illuminate\Http\Request;

class MuestraController extends Controller
{
    /** Formulario de envío de muestras */
    public function crear()
    {
        return view('muestras.crear');
    }

    /** Guardar nueva muestra */
    public function guardar(Request $request)
    {
        $request->validate([
            'lote'                => 'required|string|max:50',
            'producto'            => 'required|string|max:255',
            'proveedor'           => 'required|string|max:255',
            'proveedor_contacto'  => 'nullable|string|max:255',
            'descripcion'         => 'nullable|string|max:1000',
            'cantidad'            => 'required|integer|min:1',
            'unidad'              => 'required|string|max:30',
            'dias_validacion'     => 'nullable|integer|min:1|max:60',
        ]);

        $muestra = Muestra::create([
            'lote'               => $request->lote,
            'producto'           => $request->producto,
            'proveedor'          => $request->proveedor,
            'proveedor_contacto' => $request->proveedor_contacto,
            'descripcion'        => $request->descripcion,
            'cantidad'           => $request->cantidad,
            'unidad'             => $request->unidad,
            'dias_validacion'    => $request->dias_validacion ?? 15,
            'etapa'              => 'registro',
            'fecha_registro'     => now(),
        ]);

        return redirect()->route('muestras.admin')->with('exito', 'Muestra registrada: Lote ' . $muestra->lote);
    }

    /** Panel admin — lista todas las muestras con su estado */
    public function admin()
    {
        $muestras = Muestra::orderByDesc('created_at')->get();

        // Avanzar etapas automáticamente por fecha
        foreach ($muestras as $muestra) {
            $muestra->avanzarEtapa();
        }

        // Recargar después de avanzar
        $muestras = Muestra::orderByDesc('created_at')->get();

        return view('muestras.admin', compact('muestras'));
    }

    /** Aprobar muestra manualmente */
    public function aprobar(Muestra $muestra)
    {
        $muestra->update([
            'etapa'            => 'aprobado',
            'fecha_resolucion' => now(),
        ]);

        return redirect()->route('muestras.admin')->with('exito', 'Muestra Lote ' . $muestra->lote . ' APROBADA');
    }

    /** Rechazar muestra — vuelve a envío de muestras */
    public function rechazar(Request $request, Muestra $muestra)
    {
        $request->validate(['motivo_rechazo' => 'required|string|max:500']);

        $muestra->update([
            'etapa'            => 'rechazado',
            'fecha_resolucion' => now(),
            'motivo_rechazo'   => $request->motivo_rechazo,
        ]);

        return redirect()->route('muestras.admin')->with('exito', 'Muestra Lote ' . $muestra->lote . ' RECHAZADA');
    }

    /** Reiniciar muestra rechazada — nuevo ciclo */
    public function reiniciar(Muestra $muestra)
    {
        $muestra->update([
            'etapa'              => 'registro',
            'fecha_registro'     => now(),
            'fecha_recepcion'    => null,
            'fecha_validacion'   => null,
            'fecha_laboratorio'  => null,
            'fecha_piso'         => null,
            'fecha_estabilidad'  => null,
            'fecha_resolucion'   => null,
            'motivo_rechazo'     => null,
            'notas'              => ($muestra->notas ? $muestra->notas . "\n" : '') . '[Reiniciado el ' . now()->format('d/m/Y H:i') . ']',
        ]);

        return redirect()->route('muestras.admin')->with('exito', 'Muestra Lote ' . $muestra->lote . ' reiniciada');
    }
}
