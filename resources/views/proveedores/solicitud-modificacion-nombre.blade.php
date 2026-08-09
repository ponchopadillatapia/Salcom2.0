@extends('layouts.proveedor')

@section('title', 'Solicitar cambio de nombre')

@section('content')
@php
    $esMoral = str_contains(mb_strtolower((string) ($proveedor->tipo_persona ?? '')), 'moral');
    $etiqueta = $esMoral ? 'razón social' : 'nombre';
@endphp

<style>
    .mod-wrap{max-width:720px;margin:0 auto}
    .mod-card{background:#fff;border:1px solid var(--gray-border,#e5e7eb);border-radius:14px;padding:22px 24px;margin-bottom:16px}
    .mod-card h2{font-size:18px;font-weight:700;margin:0 0 6px}
    .mod-card p.lead{font-size:13px;color:var(--gray-muted,#6b7280);margin:0 0 18px;line-height:1.5}
    .mod-field{margin-bottom:14px}
    .mod-field label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px}
    .mod-field input[type=text],.mod-field textarea{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font:inherit}
    .mod-field input[type=file]{font-size:13px}
    .mod-hint{font-size:11px;color:#6b7280;margin-top:4px}
    .mod-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
    .btn-mod{padding:10px 18px;border-radius:10px;border:none;font-weight:600;font-size:13px;cursor:pointer;background:var(--purple,#6B3FA0);color:#fff;text-decoration:none;display:inline-block}
    .btn-mod.sec{background:#fff;color:#374151;border:1px solid #d1d5db}
    .alert-mod{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}
    .alert-mod.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
    .alert-mod.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
    .hist{font-size:12px;width:100%;border-collapse:collapse}
    .hist th,.hist td{padding:8px;border-bottom:1px solid #f3f4f6;text-align:left}
    .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600}
    .badge.ok{background:#dcfce7;color:#166534}
    .badge.bad{background:#fee2e2;color:#991b1b}
    .badge.wait{background:#fef3c7;color:#92400e}
</style>

<div class="mod-wrap">
    <div class="mod-card">
        <h2>Solicitar cambio de {{ $etiqueta }}</h2>
        <p class="lead">
            Por seguridad (como en el SAT), el nombre o razón social no se cambia libremente.
            Debes subir la documentación fiscal actualizada; el sistema la valida (reglas + IA)
            y solo entonces aplica el cambio.
        </p>

        @if(session('error'))
            <div class="alert-mod err">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-mod err">
                <ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if($pendiente)
            <div class="alert-mod err">Ya hay una solicitud en proceso. Espera el resultado antes de enviar otra.</div>
        @else
            <form method="POST" action="{{ route('proveedores.perfil.solicitud-nombre.enviar') }}" enctype="multipart/form-data">
                @csrf
                <div class="mod-field">
                    <label>Valor actual</label>
                    <input type="text" value="{{ $proveedor->nombre }}" disabled>
                </div>
                <div class="mod-field">
                    <label>Nuevo {{ $etiqueta }} *</label>
                    <input type="text" name="valor_propuesto" value="{{ old('valor_propuesto') }}" required maxlength="255" placeholder="Como aparece en tu Constancia SAT">
                </div>
                <div class="mod-field">
                    <label>Motivo del cambio</label>
                    <textarea name="motivo" rows="3" maxlength="1000" placeholder="Ej. actualización en el SAT / error en el alta / cambio de razón social">{{ old('motivo') }}</textarea>
                </div>
                <div class="mod-field">
                    <label>Constancia de Situación Fiscal (PDF) *</label>
                    <input type="file" name="cif_pdf" accept="application/pdf,.pdf" required>
                    <div class="mod-hint">Debe ser la CSF vigente donde ya aparezca el nombre o razón social nuevos.</div>
                </div>
                @if($esMoral)
                <div class="mod-field">
                    <label>Acta Constitutiva (PDF) *</label>
                    <input type="file" name="acta_pdf" accept="application/pdf,.pdf" required>
                    <div class="mod-hint">Obligatoria para Persona Moral.</div>
                </div>
                @endif
                <div class="mod-actions">
                    <button type="submit" class="btn-mod">Enviar y validar</button>
                    <a href="{{ route('proveedores.perfil') }}" class="btn-mod sec">Cancelar</a>
                </div>
            </form>
        @endif
    </div>

    @if($historial->count())
    <div class="mod-card">
        <h2 style="font-size:15px;margin-bottom:12px">Historial de solicitudes</h2>
        <table class="hist">
            <thead>
                <tr><th>Fecha</th><th>Propuesto</th><th>Estatus</th><th>Detalle</th></tr>
            </thead>
            <tbody>
            @foreach($historial as $h)
                <tr>
                    <td>{{ $h->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $h->valor_propuesto }}</td>
                    <td>
                        @if($h->estatus === 'aprobada')
                            <span class="badge ok">Aprobada</span>
                        @elseif($h->estatus === 'rechazada')
                            <span class="badge bad">Rechazada</span>
                        @else
                            <span class="badge wait">Pendiente</span>
                        @endif
                    </td>
                    <td style="color:#6b7280;max-width:260px">{{ \Illuminate\Support\Str::limit($h->notas, 120) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
