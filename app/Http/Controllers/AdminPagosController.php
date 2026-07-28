<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Services\PagoProveedorService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminPagosController extends Controller
{
    public function __construct(private PagoProveedorService $pagos) {}

    public function index()
    {
        $proveedoresPendientes = $this->pagos->proveedoresConPendientes();
        $lotes = PagoProveedor::with('proveedor')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return view('admin.pagos.index', compact('proveedoresPendientes', 'lotes'));
    }

    public function proveedor(string $codigo)
    {
        $proveedor = ProveedorUser::whereCodigo($codigo)->firstOrFail();
        $expediente = $this->pagos->evaluarExpediente($proveedor);

        $facturas = Factura::query()
            ->where('codigo_proveedor', $codigo)
            ->where('estatus', 'pendiente')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Factura $f) {
                $f->avisos_pago = $this->pagos->avisosFactura($f);
                $f->neto_pago = $this->pagos->netoFactura($f);

                return $f;
            });

        return view('admin.pagos.proveedor', compact('proveedor', 'codigo', 'facturas', 'expediente'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo_proveedor' => 'required|string',
            'factura_ids' => 'required|array|min:1',
            'factura_ids.*' => 'integer',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string|max:1000',
        ]);

        $proveedor = ProveedorUser::whereCodigo($data['codigo_proveedor'])->firstOrFail();

        try {
            $pago = $this->pagos->crearLote(
                $proveedor,
                $data['factura_ids'],
                $data['fecha_pago'] ?? null,
                $data['notas'] ?? null,
                session('admin_id')
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.pagos.show', $pago)
            ->with('mensaje', 'Lote de pago creado en borrador. Revisa y confirma.');
    }

    public function show(PagoProveedor $pago)
    {
        $pago->load(['lineas.factura', 'proveedor']);
        $expediente = $pago->proveedor
            ? $this->pagos->evaluarExpediente($pago->proveedor)
            : ['ok' => false, 'motivos' => ['Sin proveedor']];

        return view('admin.pagos.show', compact('pago', 'expediente'));
    }

    public function confirmar(PagoProveedor $pago)
    {
        try {
            $this->pagos->confirmar($pago, session('admin_id'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('mensaje', 'Pago confirmado. Facturas actualizadas.');
    }

    public function cancelar(PagoProveedor $pago)
    {
        try {
            $this->pagos->cancelarBorrador($pago);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.pagos')->with('mensaje', 'Borrador cancelado.');
    }

    public function excel(PagoProveedor $pago)
    {
        $lines = $this->pagos->filasExcel($pago);
        $filename = 'Pago_'.$pago->codigo_proveedor.'_lote'.$pago->id.'_'.now()->format('Y-m-d').'.csv';

        $output = "\xEF\xBB\xBF";
        foreach ($lines as $line) {
            $output .= collect($line)->map(function ($cell) {
                $cell = (string) $cell;
                if (str_contains($cell, ',') || str_contains($cell, '"') || str_contains($cell, "\n")) {
                    return '"'.str_replace('"', '""', $cell).'"';
                }

                return $cell;
            })->implode(',')."\r\n";
        }

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
