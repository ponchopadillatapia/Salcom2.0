@extends('layouts.cliente')

@section('title', 'Inicio')

@section('hero')
<div class="hero-band">
    <h1>Hola, {{ session('cliente_nombre', 'Cliente') }}</h1>
    <p>Bienvenido al Portal de Clientes de Industrias Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .portal-home {
        max-width: 1140px;
        margin: 0 auto;
    }
    .portal-top-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .portal-card {
        background: var(--white);
        border: none;
        border-radius: var(--radius-lg);
        padding: 20px 22px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .portal-card:hover {
        box-shadow: var(--shadow-md);
    }
    .portal-card h4 {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-muted);
        margin-bottom: 8px;
        letter-spacing: -0.1px;
    }
    .portal-stat-val {
        font-size: 28px;
        font-weight: 700;
        color: var(--gray-text);
        line-height: 1;
        letter-spacing: -0.5px;
    }
    .portal-stat-label {
        font-size: 12px;
        color: var(--gray-muted);
        margin-top: 6px;
        font-weight: 400;
    }
    .portal-quick-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .portal-quick-card {
        background: var(--white);
        border: none;
        border-radius: var(--radius-lg);
        padding: 20px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .portal-quick-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    .portal-quick-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--purple-light);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .portal-quick-title {
        font-weight: 600;
        color: var(--gray-text);
        font-size: 14px;
        letter-spacing: -0.2px;
    }
    .portal-quick-desc {
        font-size: 12px;
        color: var(--gray-muted);
        font-weight: 400;
    }
    .portal-section-label {
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-text);
        letter-spacing: -0.2px;
        margin-bottom: 14px;
    }
    .portal-forecast-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .portal-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        font-size: 13px;
        border-bottom: 1px solid var(--border-light);
    }
    .portal-row:last-child {
        border-bottom: none;
    }
    .portal-activity-box {
        background: var(--white);
        border: none;
        border-radius: var(--radius-lg);
        padding: 16px 22px;
        box-shadow: var(--shadow-sm);
    }
    .portal-activity-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .portal-activity-row:last-child {
        border-bottom: none;
    }
    .portal-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    @media (max-width: 900px) {
        .portal-top-grid { grid-template-columns: 1fr 1fr; }
        .portal-quick-grid { grid-template-columns: 1fr; }
        .portal-forecast-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="portal-home">
    <div class="portal-top-grid">
        <div class="portal-card">
            <h4>Pedidos activos</h4>
            <div class="portal-stat-val">3</div>
            <div class="portal-stat-label">En proceso</div>
        </div>
        <div class="portal-card">
            <h4>Último pedido</h4>
            <div class="portal-stat-val">PED-005</div>
            <div class="portal-stat-label">09/04/2026</div>
        </div>
        <div class="portal-card">
            <h4>Saldo pendiente</h4>
            <div class="portal-stat-val">$0.00</div>
            <div class="portal-stat-label">Al corriente</div>
        </div>
        <div class="portal-card">
            <h4>Tipo de cliente</h4>
            <div class="portal-stat-val">{{ ucfirst(session('cliente_tipo', '—')) }}</div>
            <div class="portal-stat-label">Clasificación</div>
        </div>
    </div>

    <div class="portal-section-label">Acceso rápido</div>
    <div class="portal-quick-grid" style="margin-bottom: 24px">
        <a href="{{ route('clientes.ia') }}" class="portal-quick-card">
            <div class="portal-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg>
            </div>
            <div>
                <div class="portal-quick-title">Dashboard IA</div>
                <div class="portal-quick-desc">Análisis con Claude</div>
            </div>
        </a>
        <a href="{{ route('clientes.forecast') }}" class="portal-quick-card">
            <div class="portal-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <div class="portal-quick-title">Forecast</div>
                <div class="portal-quick-desc">Tendencias de compras</div>
            </div>
        </a>
        <a href="{{ route('clientes.catalogo') }}" class="portal-quick-card">
            <div class="portal-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div>
                <div class="portal-quick-title">Catálogo</div>
                <div class="portal-quick-desc">Productos y precios</div>
            </div>
        </a>
        <a href="{{ route('clientes.pedidos') }}" class="portal-quick-card">
            <div class="portal-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
                <div class="portal-quick-title">Mis Pedidos</div>
                <div class="portal-quick-desc">Estatus y seguimiento</div>
            </div>
        </a>
        <a href="{{ route('clientes.estado-cuenta') }}" class="portal-quick-card">
            <div class="portal-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="portal-quick-title">Estado de cuenta</div>
                <div class="portal-quick-desc">Facturas y saldos</div>
            </div>
        </a>
        <a href="{{ route('clientes.tracking') }}" class="portal-quick-card">
            <div class="portal-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="portal-quick-title">Tracking</div>
                <div class="portal-quick-desc">Seguimiento de envíos</div>
            </div>
        </a>
    </div>

    <div class="portal-section-label">Forecast — Tendencias de tus compras</div>
    <div class="portal-forecast-grid">
        <div class="portal-card">
            <h4 style="color: var(--green)">Al alza</h4>
            <div class="portal-row"><span style="flex:1;font-weight:600">Resina epóxica</span><span style="font-size:12px;font-weight:700;color:var(--green)">↑ +12%</span></div>
            <div class="portal-row"><span style="flex:1;font-weight:600">Solvente técnico</span><span style="font-size:12px;font-weight:700;color:var(--green)">↑ +8%</span></div>
            <div class="portal-row"><span style="flex:1;font-weight:600">Pigmento base agua</span><span style="font-size:12px;font-weight:700;color:var(--green)">↑ +5%</span></div>
            <a href="{{ route('clientes.forecast') }}" style="display:block;text-align:right;font-size:12px;color:var(--purple);font-weight:600;text-decoration:none;margin-top:8px">Ver todo →</a>
        </div>
        <div class="portal-card">
            <h4 style="color: var(--red)">A la baja</h4>
            <div class="portal-row"><span style="flex:1;font-weight:600">Aditivo antioxidante</span><span style="font-size:12px;font-weight:700;color:var(--red)">↓ -15%</span></div>
            <div class="portal-row"><span style="flex:1;font-weight:600">Catalizador rápido</span><span style="font-size:12px;font-weight:700;color:var(--red)">↓ -5%</span></div>
            <a href="{{ route('clientes.forecast') }}" style="display:block;text-align:right;font-size:12px;color:var(--purple);font-weight:600;text-decoration:none;margin-top:8px">Ver todo →</a>
        </div>
    </div>

    <div class="portal-section-label">Actividad reciente</div>
    <div class="portal-activity-box">
        <div class="portal-activity-row">
            <div class="portal-dot" style="background: var(--green)"></div>
            <span style="flex:1">PED-2026-004 autorizado por área comercial</span>
            <span style="font-size:11px;color:var(--gray-muted)">07/04</span>
        </div>
        <div class="portal-activity-row">
            <div class="portal-dot" style="background: var(--amber)"></div>
            <span style="flex:1">Factura CFDI-A-001236 pendiente de pago</span>
            <span style="font-size:11px;color:var(--gray-muted)">05/04</span>
        </div>
        <div class="portal-activity-row">
            <div class="portal-dot" style="background: var(--blue)"></div>
            <span style="flex:1">PED-2026-002 salió de planta</span>
            <span style="font-size:11px;color:var(--gray-muted)">06/04</span>
        </div>
    </div>
</div>
@endsection
