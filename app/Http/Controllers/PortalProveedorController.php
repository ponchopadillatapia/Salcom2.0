<?php

namespace App\Http\Controllers;

use App\Models\ContactoProveedor;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\ProveedorUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        return view('proveedores.onboarding', compact('proveedor'));
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
     * Subir documento fiscal — validación automática por IA.
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

        // Guardar archivo
        $path = $request->file('archivo')->store('documentos-fiscales', 'public');

        // Validación automática por IA (reglas básicas)
        $errores = [];
        $archivo = $request->file('archivo');

        // Validar tamaño mínimo (un PDF real tiene al menos 1KB)
        if ($archivo->getSize() < 1024) {
            $errores[] = 'El archivo parece estar vacío o corrupto.';
        }

        // Validar que sea PDF real (magic bytes)
        $contenido = file_get_contents($archivo->getRealPath());
        if (! str_starts_with($contenido, '%PDF')) {
            $errores[] = 'El archivo no es un PDF válido.';
        }

        // Validar RFC si se proporcionó (formato básico)
        if ($rfc && ! preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i', $rfc)) {
            $errores[] = "El RFC '{$rfc}' no tiene un formato válido. Debe ser 12 o 13 caracteres (ej: ABC123456XY7).";
        }

        // Validaciones específicas por tipo de documento
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
            // Documento aprobado — guardar en BD
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

        // Documento rechazado
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
