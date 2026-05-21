@extends('layouts.cliente')
@section('title', 'Dashboard')
@section('hero')
<div class="hero-band">
    <h1>Bienvenido, {{ session('cliente_nombre', 'Cliente') }}</h1>
    <p>Código: {{ session('cliente_codigo', '—') }} — {{ now()->format('d/m/Y') }}</p>
</div>
@endsection

@php
    $c = $cliente ?? null;
    $limite = $c && $c->limite_credito !== null ? (float) $c->limite_credito : null;
    $diasCred = $c?->dias_credito;
    $credAut = (bool) ($c?->credito_autorizado);
    $saldoDemo = 17719.0;
    $pctUso = $limite && $limite > 0 ? min(100, round(($saldoDemo / $limite) * 100, 1)) : null;
@endphp

@push('styles')
<style>
    .pp-wrap{max-width:1140px;margin:0 auto}
    .pp-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
    .pp-card{background:var(--white);border:2px solid var(--purple);border-radius:var(--radius-lg);padding:22px;transition:var(--transition);box-shadow:var(--shadow-sm)}
    .pp-card:hover{border-color:var(--purple-dark);box-shadow:var(--shadow-md)}
    .pp-card h4{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:16px}
    .pp-negocio-row{margin-bottom:12px}
    .pp-negocio-label{font-size:12px;color:var(--gray-muted);font-weight:500;margin-bottom:2px}
    .pp-negocio-value{font-size:24px;font-weight:700;color:var(--gray-text);display:flex;align-items:baseline;gap:10px}
    .pp-variation{font-size:14px;font-weight:700}
    .pp-variation-up{color:var(--green)}
    .pp-variation-down{color:var(--red)}
    .pp-detail-link{font-size:13px;color:var(--blue);font-weight:600;text-decoration:none;display:inline-block;margin-top:8px}
    .pp-detail-link:hover{text-decoration:underline}
    .pp-list-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .pp-list-item:last-child{border-bottom:none}
    .pp-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .pp-dot-green{background:var(--green)}.pp-dot-amber{background:var(--amber)}.pp-dot-red{background:var(--red)}
    .pp-list-text{flex:1;color:var(--gray-text);font-weight:500}
    .pp-list-status{font-size:11px;color:var(--gray-muted);font-weight:500}
    .pp-quick-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
    .pp-quick-card{text-decoration:none;display:flex;align-items:center;gap:10px;padding:12px !important}
    .pp-quick-icon{width:42px;height:42px;border-radius:12px;background:var(--purple-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:var(--transition)}
    .pp-quick-card:hover .pp-quick-icon{background:var(--purple);box-shadow:0 2px 8px rgba(107,63,160,0.25)}
    .pp-quick-card:hover .pp-quick-icon svg{stroke:white}
    .pp-quick-title{font-weight:600;color:var(--gray-text);font-size:13px}
    .pp-quick-sub{font-size:11px;color:var(--gray-muted);margin-top:2px}
    @media(max-width:768px){.pp-grid-2{grid-template-columns:1fr !important}.pp-quick-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:480px){.pp-quick-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="pp-wrap">

    {{-- ═══ ROW 1: Pedidos + Facturado + Saldo + Crédito (4 cards) ═══ --}}
    <div class="pp-grid-2" style="grid-template-columns:repeat(4,minmax(0,1fr));align-items:stretch;">
        {{-- Pedidos --}}
        <a href="{{ route('clientes.pedidos') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Pedidos</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Este mes</div>
                <div class="pp-negocio-value" style="font-size:20px;">
                    5
                    <span class="pp-variation pp-variation-up" style="font-size:14px;">↑ +12%</span>
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Último</div>
                <div class="pp-negocio-value" style="font-size:14px;color:var(--purple);">PED-2026-005</div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>

        {{-- Facturado --}}
        <a href="{{ route('clientes.estado-cuenta') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Facturado</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Total CFDI</div>
                <div class="pp-negocio-value" style="color:var(--green);font-size:20px;">
                    $30,618
                    <span class="pp-variation pp-variation-up" style="font-size:14px;">↑ +8%</span>
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Facturas emitidas</div>
                <div class="pp-negocio-value" style="font-size:20px;">6</div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>

        {{-- Saldo --}}
        <a href="{{ route('clientes.estado-cuenta') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Saldo</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Pendiente</div>
                <div class="pp-negocio-value" style="color:var(--red);font-size:20px;">
                    $17,719
                    <span class="pp-variation pp-variation-down" style="font-size:14px;">↓ -3%</span>
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Facturas pendientes</div>
                <div class="pp-negocio-value" style="color:var(--amber);font-size:20px;">3</div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>

        {{-- Crédito --}}
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Crédito</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Límite</div>
                <div class="pp-negocio-value" style="font-size:20px;">
                    @if($limite !== null)
                        ${{ number_format($limite, 0, '.', ',') }}
                    @else
                        <span style="color:var(--gray-muted);">—</span>
                    @endif
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Estado</div>
                <div class="pp-negocio-value" style="font-size:14px;">
                    @if($credAut)
                        <span style="color:var(--green);">Autorizado · {{ $diasCred ?? '—' }} días</span>
                    @else
                        <span style="color:var(--gray-muted);">Sin crédito</span>
                    @endif
                </div>
            </div>
            @if($pctUso !== null)
                <div style="margin-top:auto;">
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--gray-muted);margin-bottom:4px;">
                        <span>Uso</span><span style="font-weight:700;color:var(--gray-text);">{{ $pctUso }}%</span>
                    </div>
                    <div style="height:6px;background:var(--border-light);border-radius:3px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pctUso }}%;background:linear-gradient(90deg,var(--purple),#9C6DD0);border-radius:3px;"></div>
                    </div>
                </div>
            @else
                <span class="pp-detail-link" style="margin-top:auto;color:var(--gray-muted);font-size:11px;">Pendiente de asignar</span>
            @endif
        </div>
    </div>

    {{-- ═══ ROW 2: Últimos pedidos + Pendientes + Estado de cuenta (3 cards) ═══ --}}
    <div class="pp-grid-2" style="grid-template-columns:1fr 1fr 1fr;align-items:stretch;">
        {{-- Últimos pedidos --}}
        <div class="pp-card" style="padding:16px;">
            <h4 style="font-size:13px;margin-bottom:10px;">Últimos pedidos</h4>
            <a href="{{ route('clientes.pedidos') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot" style="background:var(--purple);"></div>
                <div class="pp-list-text" style="font-size:11px;">PED-2026-005 — En validación</div>
                <div class="pp-list-status" style="font-size:9px;">09/04</div>
            </a>
            <a href="{{ route('clientes.pedidos') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text" style="font-size:11px;">PED-2026-004 — Autorizado</div>
                <div class="pp-list-status" style="font-size:9px;">07/04</div>
            </a>
            <a href="{{ route('clientes.pedidos') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text" style="font-size:11px;">PED-2026-003 — En producción</div>
                <div class="pp-list-status" style="font-size:9px;">05/04</div>
            </a>
            <a href="{{ route('clientes.pedidos') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text" style="font-size:11px;">PED-2026-002 — Entregado</div>
                <div class="pp-list-status" style="font-size:9px;">01/04</div>
            </a>
        </div>

        {{-- Pendientes --}}
        <div class="pp-card" style="padding:16px;">
            <h4 style="font-size:13px;margin-bottom:10px;">Pendientes</h4>
            <a href="{{ route('clientes.estado-cuenta') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-red"></div>
                <div class="pp-list-text" style="font-size:11px;">Factura por pagar</div>
                <div class="pp-list-status" style="font-size:9px;">Vencida</div>
            </a>
            <a href="{{ route('clientes.fiscal') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text" style="font-size:11px;">Documentación fiscal</div>
                <div class="pp-list-status" style="font-size:9px;">Revisar</div>
            </a>
            <a href="{{ route('clientes.tracking') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text" style="font-size:11px;">Confirmar recepción</div>
                <div class="pp-list-status" style="font-size:9px;">Acción</div>
            </a>
            <a href="{{ route('clientes.encuesta') }}" class="pp-list-item" style="padding:6px 0;text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text" style="font-size:11px;">Encuesta pendiente</div>
                <div class="pp-list-status" style="font-size:9px;">Nuevo</div>
            </a>
        </div>

        {{-- Estado de cuenta --}}
        <a href="{{ route('clientes.estado-cuenta') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="padding:16px;height:100%;display:flex;flex-direction:column;cursor:pointer;">
            <h4 style="font-size:13px;margin-bottom:10px;">Estado de cuenta</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Facturado (YTD)</div>
                <div class="pp-negocio-value" style="color:var(--green);font-size:18px;">
                    $30,618
                    <span class="pp-variation pp-variation-up" style="font-size:12px;">↑ +8%</span>
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Pagado</div>
                <div class="pp-negocio-value" style="font-size:18px;">$12,899</div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Por pagar</div>
                <div class="pp-negocio-value" style="color:var(--red);font-size:18px;">
                    $17,719
                    <span class="pp-variation pp-variation-down" style="font-size:12px;">↓ -3%</span>
                </div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>
    </div>

    {{-- ═══ ROW 3: Quick access grid ═══ --}}
    <div class="pp-quick-grid">
        <a href="{{ route('clientes.pedidos') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Mis pedidos</div>
                <div class="pp-quick-sub">Seguimiento</div>
            </div>
        </a>
        <a href="{{ route('clientes.estado-cuenta') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Estado de cuenta</div>
                <div class="pp-quick-sub">Facturas y pagos</div>
            </div>
        </a>
        <a href="{{ route('clientes.catalogo') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Catálogo</div>
                <div class="pp-quick-sub">Productos</div>
            </div>
        </a>
        <a href="{{ route('clientes.forecast') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Forecast</div>
                <div class="pp-quick-sub">Tendencias</div>
            </div>
        </a>
        <a href="{{ route('clientes.tracking') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Tracking</div>
                <div class="pp-quick-sub">Envíos en ruta</div>
            </div>
        </a>
        <a href="{{ route('clientes.ia') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Dashboard IA</div>
                <div class="pp-quick-sub">Análisis inteligente</div>
            </div>
        </a>
    </div>

</div>
@endsection
