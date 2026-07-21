@extends('layouts.admin')

@section('title', 'Documentos validados')

@section('hero')
<div class="hero-band">
    <h1>Documentos correctos</h1>
    <p>{{ $proveedor->nombre ?? $proveedor->usuario }} — solo se muestran los que ya validaron bien</p>
</div>
@endsection

@push('styles')
<style>
    .ver-wrap { max-width: 780px; margin: 0 auto; }
    .ver-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--purple); text-decoration: none; margin-bottom: 16px; }
    .ver-back:hover { text-decoration: underline; }
    .ver-meta { background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; font-size: 13px; color: var(--gray-muted); }
    .ver-meta strong { color: var(--gray-text); }
    .seccion-doc {
        border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 0.65rem;
        border: 1px solid var(--border-light); background: var(--white); border-left: 4px solid var(--green);
    }
    .seccion-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
    .seccion-titulo { font-weight: 700; font-size: 0.9rem; color: var(--gray-text); flex: 1; }
    .status-pill { font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; background: #ecfdf5; color: #059669; }
    .detalle-item { font-size: 0.82rem; padding: 4px 0; display: flex; align-items: flex-start; gap: 0.5rem; color: #047857; line-height: 1.4; }
    .ver-empty { text-align: center; padding: 40px 20px; background: var(--white); border-radius: 14px; border: 1px solid var(--border-light); color: var(--gray-muted); font-size: 14px; }
    .ver-actions { margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-ver-detalle { padding: 10px 18px; background: var(--purple); color: #fff; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; }
    .btn-ver-detalle:hover { opacity: .92; color: #fff; }
</style>
@endpush

@section('content')
<div class="ver-wrap">
    <a href="{{ route('admin.solicitudes-alta') }}" class="ver-back">← Volver a solicitudes</a>

    <div class="ver-meta">
        <strong>{{ $proveedor->nombre ?? $proveedor->usuario }}</strong>
        · {{ $proveedor->tipo_persona ?? '—' }}
        · Código: {{ $proveedor->id_proveedor ?? '—' }}
        · Correo: {{ $proveedor->correo ?? '—' }}
    </div>

    @if($docsAprobados->isEmpty())
        <div class="ver-empty">
            <p style="font-weight:600;color:var(--gray-text);margin-bottom:6px;">Sin documentos correctos aún</p>
            <p style="margin:0;">Este proveedor no tiene documentos con estatus <strong>aprobado</strong>. Cuando valide bien en el flujo de documentos, aparecerán aquí.</p>
        </div>
    @else
        <p style="font-size:13px;color:var(--gray-muted);margin-bottom:12px;">
            Mostrando {{ $docsAprobados->count() }} documento(s) validado(s) correctamente. Los que fallaron o están pendientes no se listan.
        </p>
        @foreach($docsAprobados as $doc)
            <div class="seccion-doc">
                <div class="seccion-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span class="seccion-titulo">{{ $tiposLabel[$doc->tipo] ?? ucfirst($doc->tipo) }}</span>
                    <span class="status-pill">Aprobado</span>
                </div>
                <div class="detalle-item">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ $doc->notas_revision ?: 'Validación correcta' }}
                </div>
                @if($doc->revisado_at)
                <div class="detalle-item" style="color:var(--gray-muted);">
                    Revisado: {{ $doc->revisado_at->format('d/m/Y H:i') }}
                </div>
                @endif
                @if($doc->archivo)
                <div style="margin-top:8px;">
                    <a href="{{ asset('storage/'.$doc->archivo) }}" target="_blank" style="font-size:12px;font-weight:600;color:var(--purple);">Abrir PDF</a>
                </div>
                @endif
            </div>
        @endforeach
    @endif

    <div class="ver-actions">
        <a href="{{ route('admin.solicitudes-alta.detalle', $proveedor) }}" class="btn-ver-detalle">Ver expediente completo</a>
    </div>
</div>
@endsection
