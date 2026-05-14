<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\AlertaConfiguracion;
use App\Models\OcBorrador;
use App\Services\AlertEngineService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function index(Request $request)
    {
        $query = Alerta::orderByDesc('created_at');

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->nivel) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->estatus) {
            $query->where('estatus', $request->estatus);
        }

        $alertas = $query->paginate(20);
        $tipos = Alerta::select('tipo')->distinct()->pluck('tipo');
        $stats = [
            'total' => Alerta::count(),
            'pendientes' => Alerta::where('estatus', 'pendiente')->count(),
            'criticas' => Alerta::where('nivel', 'critical')->count(),
            'hoy' => Alerta::whereDate('created_at', today())->count(),
        ];

        return view('admin.alertas.index', compact('alertas', 'tipos', 'stats'));
    }

    public function configuracion()
    {
        $configs = AlertaConfiguracion::orderBy('clave')->get();

        return view('admin.alertas.configuracion', compact('configs'));
    }

    public function guardarConfig(Request $request)
    {
        $configs = $request->input('configs', []);

        foreach ($configs as $clave => $valor) {
            $anterior = AlertaConfiguracion::get($clave);
            AlertaConfiguracion::set($clave, $valor, session('admin_id'));

            if ($anterior !== $valor) {
                AuditService::editar('alertas', "Configuración '{$clave}' cambiada", ['valor' => $anterior], ['valor' => $valor]);
            }
        }

        return back()->with('mensaje', 'Configuración guardada correctamente.');
    }

    public function ocBorradores()
    {
        $borradores = OcBorrador::with('proveedor')
            ->orderByDesc('created_at')
            ->paginate(15);

        $stats = [
            'pendientes' => OcBorrador::where('estatus', 'pendiente')->count(),
            'aprobadas' => OcBorrador::where('estatus', 'aprobada')->count(),
            'monto_pendiente' => OcBorrador::where('estatus', 'pendiente')->sum('monto_estimado'),
        ];

        return view('admin.alertas.oc-borradores', compact('borradores', 'stats'));
    }

    public function aprobarOC(int $id)
    {
        // Las OC automáticas ya se auto-aprueban por IA.
        // Este método solo existe para OC manuales o casos excepcionales.
        $oc = OcBorrador::findOrFail($id);

        if ($oc->estatus === 'aprobada') {
            return back()->with('mensaje', "OC #{$oc->id} ya fue aprobada automáticamente por IA.");
        }

        $oc->update([
            'estatus' => 'aprobada',
            'aprobada_por' => session('admin_id'),
            'aprobada_at' => now(),
        ]);

        // Notificar al proveedor
        $alertEngine = new AlertEngineService;
        $alertEngine->alertar([
            'tipo' => 'oc_nueva',
            'modulo' => 'inventario',
            'destinatario_tipo' => 'proveedor',
            'destinatario_id' => $oc->proveedor_id,
            'titulo' => 'Nueva OC asignada',
            'contenido' => 'Se aprobó una orden de compra por $' . number_format($oc->monto_estimado, 2) . '. Revisa los detalles en Consultar OC.',
            'datos' => ['oc_id' => $oc->id, 'monto' => $oc->monto_estimado],
            'nivel' => 'info',
        ]);

        AuditService::registrar('aprobar_oc', 'inventario', "OC #{$oc->id} aprobada manualmente. Monto: \${$oc->monto_estimado}");

        return back()->with('mensaje', "OC #{$oc->id} aprobada. Se notificó al proveedor.");
    }

    public function rechazarOC(Request $request, int $id)
    {
        $oc = OcBorrador::findOrFail($id);
        $oc->update([
            'estatus' => 'rechazada',
            'notas' => $request->input('notas', 'Rechazada por admin'),
        ]);

        AuditService::registrar('rechazar_oc', 'inventario', "OC #{$oc->id} rechazada.");

        return back()->with('mensaje', "OC #{$oc->id} rechazada.");
    }
}
