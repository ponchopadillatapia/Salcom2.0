@extends('layouts.cliente')
@section('title', 'Mi Perfil')
@section('hero')
<div class="hero-band"><h1>Mi Perfil</h1><p>Consulta y actualiza tu información de cliente</p></div>
@endsection

@push('styles')
<style>
    .perfil-header{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:20px;display:flex;align-items:center;gap:20px}
    .perfil-avatar{width:56px;height:56px;border-radius:50%;background:#6B3FA0;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;flex-shrink:0}
    .perfil-name{font-size:18px;font-weight:700;color:var(--gray-text)}.perfil-meta{font-size:13px;color:var(--gray-muted);margin-top:2px}
    .perfil-actions{margin-left:auto}
    .btn-edit{padding:8px 20px;border:1px solid #6B3FA0;border-radius:8px;background:none;color:#6B3FA0;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:all .15s}
    .btn-edit:hover{background:#6B3FA0;color:#fff}

    .perfil-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
    .perfil-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:24px}
    .perfil-card h3{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:18px;display:flex;align-items:center;gap:8px}
    .info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}.info-row:last-child{border-bottom:none}
    .info-label{font-size:13px;color:var(--gray-muted)}.info-value{font-size:13px;color:var(--gray-text);font-weight:600;text-align:right}

    .doc-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}.doc-item:last-child{border-bottom:none}
    .doc-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .doc-icon.ok{background:#ecfdf5}.doc-icon.pending{background:#fffbeb}.doc-icon.na{background:#f3f4f6}
    .doc-name{font-size:13px;color:var(--gray-text);flex:1}
    .doc-badge{font-size:10px;font-weight:600;padding:3px 8px;border-radius:999px}
    .doc-badge.ok{background:#ecfdf5;color:#059669}.doc-badge.pending{background:#fffbeb;color:#d97706}.doc-badge.na{background:#f3f4f6;color:#9ca3af}

    .status-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:999px}
    .status-active{background:#ecfdf5;color:#059669}.status-inactive{background:#fef2f2;color:#dc2626}

    @media(max-width:768px){.perfil-grid{grid-template-columns:1fr}.perfil-header{flex-wrap:wrap}}
</style>
@endpush

@section('content')
    <div class="perfil-header">
        <div class="perfil-avatar">{{ strtoupper(substr($cliente->nombre ?? session('cliente_nombre', 'C'), 0, 1)) }}</div>
        <div>
            <div class="perfil-name">{{ $cliente->nombre ?? session('cliente_nombre', '—') }}</div>
            <div class="perfil-meta">Código: {{ $cliente->codigo_cliente ?? session('cliente_codigo', '—') }} · {{ ucfirst($cliente->tipo_cliente ?? session('cliente_tipo', '—')) }}</div>
        </div>
        <div class="perfil-actions">
            <button class="btn-edit" onclick="alert('Edición de datos pendiente de implementar')">Editar datos</button>
        </div>
    </div>

    <div class="perfil-grid">
        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Información General
            </h3>
            <div class="info-row"><span class="info-label">Nombre</span><span class="info-value">{{ $cliente->nombre ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">Usuario</span><span class="info-value">{{ $cliente->usuario ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">Correo</span><span class="info-value">{{ $cliente->correo ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">Teléfono</span><span class="info-value">{{ $cliente->telefono ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">RFC</span><span class="info-value">{{ $cliente->rfc ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">Tipo de persona</span><span class="info-value">{{ $cliente->tipo_persona ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">Código de cliente</span><span class="info-value">{{ $cliente->codigo_cliente ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">Tipo de cliente</span><span class="info-value">{{ ucfirst($cliente->tipo_cliente ?? '—') }}</span></div>
        </div>

        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>
                Documentos y Verificación
            </h3>
            <div class="doc-item">
                <div class="doc-icon ok"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
                <span class="doc-name">RFC verificado</span>
                <span class="doc-badge ok">Verificado</span>
            </div>
            <div class="doc-item">
                <div class="doc-icon pending"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <span class="doc-name">Constancia de situación fiscal</span>
                <span class="doc-badge pending">Pendiente</span>
            </div>
            <div class="doc-item">
                <div class="doc-icon pending"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <span class="doc-name">Comprobante de domicilio</span>
                <span class="doc-badge pending">Pendiente</span>
            </div>
            <div class="doc-item">
                <div class="doc-icon na"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
                <span class="doc-name">Crédito autorizado</span>
                <span class="doc-badge na">{{ $cliente && $cliente->credito_autorizado ? 'Sí' : 'No — Contado' }}</span>
            </div>

            <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:16px">
                <div class="info-row"><span class="info-label">Estado</span><span class="info-value">@if($cliente && $cliente->activo)<span class="status-badge status-active">● Activo</span>@else<span class="status-badge status-inactive">● Inactivo</span>@endif</span></div>
                <div class="info-row"><span class="info-label">Miembro desde</span><span class="info-value">{{ $cliente && $cliente->created_at ? $cliente->created_at->format('d/m/Y') : '—' }}</span></div>
                <div class="info-row"><span class="info-label">Última actualización</span><span class="info-value">{{ $cliente && $cliente->updated_at ? $cliente->updated_at->format('d/m/Y H:i') : '—' }}</span></div>
                <div class="info-row"><span class="info-label">ID interno</span><span class="info-value">#{{ $cliente->id ?? session('cliente_id', '—') }}</span></div>
            </div>
        </div>
    </div>
@endsection
