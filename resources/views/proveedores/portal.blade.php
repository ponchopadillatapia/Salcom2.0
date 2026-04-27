@extends('layouts.proveedor')

@section('title', 'Inicio')

@section('hero')
<div class="hero-band">
    <h1>Hola, {{ session('proveedor_nombre', 'Proveedor') }}</h1>
    <p>Bienvenido al Portal de Proveedores de Industrias Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .pp-wrap {
        max-width: 1140px;
        margin: 0 auto;
    }
    .pp-top-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .pp-card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: 10px;
        padding: 20px;
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
    }
    .pp-card:hover {
        border-color: var(--purple-mid);
        box-shadow: 0 2px 8px rgba(107, 63, 160, 0.1);
    }
    .pp-card h4 {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-text);
        margin-bottom: 12px;
    }
    .pp-stat-val {
        font-size: 28px;
        font-weight: 700;
        color: var(--gray-text);
        line-height: 1;
    }
    .pp-stat-label {
        font-size: 12px;
        color: var(--gray-muted);
        margin-top: 4px;
    }
    .pp-stat-highlight {
        color: var(--amber);
    }
    .pp-week-panel {
        display: none;
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
        overflow: hidden;
    }
    .pp-week-panel.active {
        display: block;
    }
    .pp-week-panel h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-text);
        margin-bottom: 14px;
    }
    .pp-week-panel .pp-close-btn {
        float: right;
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: var(--gray-muted);
        line-height: 1;
    }
    .pp-week-section {
        margin-bottom: 14px;
    }
    .pp-week-section h5 {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .pp-week-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .pp-week-row:last-child {
        border-bottom: none;
    }
    .pp-mid-grid {
        display: grid;
        grid-template-columns: 1fr 200px 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .pp-cal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .pp-cal-header h4 {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-text);
    }
    .pp-cal-nav {
        display: flex;
        gap: 4px;
    }
    .pp-cal-nav button {
        width: 28px;
        height: 28px;
        border: 1px solid var(--border-light);
        border-radius: 6px;
        background: var(--white);
        cursor: pointer;
        font-size: 14px;
        color: var(--gray-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.1s;
    }
    .pp-cal-nav button:hover {
        background: var(--purple-light);
        color: var(--purple);
        border-color: var(--border-light);
    }
    .pp-cal-month {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-text);
    }
    .pp-cal-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .pp-cal-table th {
        font-weight: 600;
        color: var(--gray-muted);
        padding: 6px 2px;
        text-align: center;
        font-size: 11px;
        text-transform: uppercase;
    }
    .pp-cal-table td {
        padding: 5px 2px;
        text-align: center;
        color: var(--gray-muted);
        cursor: default;
    }
    .pp-cal-table td.pp-today {
        background: var(--purple);
        color: var(--white);
        border-radius: 6px;
        font-weight: 600;
    }
    .pp-cal-table td.pp-has-data {
        cursor: pointer;
        font-weight: 600;
        color: var(--gray-text);
    }
    .pp-cal-table td.pp-has-data:hover {
        background: var(--purple-light);
        border-radius: 6px;
    }
    .pp-cal-week {
        font-size: 11px;
        color: var(--purple);
        font-weight: 700;
    }
    .pp-score-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 20px;
    }
    .pp-score-donut {
        position: relative;
        width: 120px;
        height: 120px;
    }
    .pp-score-donut canvas {
        position: absolute;
        top: 0;
        left: 0;
    }
    .pp-score-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    .pp-score-num {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
    }
    .pp-score-lbl {
        font-size: 10px;
        color: var(--gray-muted);
        margin-top: 2px;
    }
    .pp-score-legend {
        width: 100%;
        font-size: 11px;
        color: var(--gray-muted);
    }
    .pp-score-legend-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
    }
    .pp-score-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .pp-score-legend-val {
        margin-left: auto;
        font-weight: 700;
    }
    .pp-list-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .pp-list-item:last-child {
        border-bottom: none;
    }
    .pp-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .pp-dot-g {
        background: var(--green);
    }
    .pp-dot-a {
        background: var(--amber);
    }
    .pp-dot-x {
        background: #d1d5db;
    }
    .pp-list-text {
        flex: 1;
        color: var(--gray-text);
    }
    .pp-list-time {
        font-size: 11px;
        color: var(--gray-muted);
    }
    .pp-card-link {
        font-size: 12px;
        color: var(--purple);
        font-weight: 600;
        text-decoration: none;
    }
    .pp-card-link:hover {
        text-decoration: underline;
    }
    .pp-forecast-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .pp-forecast-card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: 10px;
        padding: 20px;
    }
    .pp-forecast-card h4 {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-text);
        margin-bottom: 14px;
    }
    .pp-forecast-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .pp-forecast-row:last-child {
        border-bottom: none;
    }
    .pp-forecast-name {
        flex: 1;
        font-weight: 600;
        color: var(--gray-text);
    }
    .pp-forecast-bar {
        width: 60px;
        height: 6px;
        background: var(--border-light);
        border-radius: 3px;
        overflow: hidden;
    }
    .pp-forecast-fill {
        height: 100%;
        border-radius: 3px;
    }
    .pp-forecast-trend {
        font-size: 12px;
        font-weight: 700;
        width: 50px;
        text-align: right;
    }
    .pp-trend-up {
        color: var(--green);
    }
    .pp-trend-down {
        color: var(--red);
    }
    .pp-trend-flat {
        color: var(--gray-muted);
    }
    .pp-quick-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
    }
    .pp-quick-card {
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .pp-quick-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--purple-light);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    @media (max-width: 1100px) {
        .pp-mid-grid {
            grid-template-columns: 1fr 1fr;
        }
        .pp-mid-grid .pp-card:nth-child(2) {
            grid-column: span 2;
        }
    }
    @media (max-width: 900px) {
        .pp-top-grid {
            grid-template-columns: 1fr 1fr;
        }
        .pp-quick-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 600px) {
        .pp-mid-grid {
            grid-template-columns: 1fr;
        }
        .pp-mid-grid .pp-card:nth-child(2) {
            grid-column: span 1;
        }
        .pp-forecast-grid {
            grid-template-columns: 1fr;
        }
        .pp-quick-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="pp-wrap">
    <div class="pp-top-grid">
        <a href="{{ route('proveedores.business') }}" class="pp-card">
            <h4>Mi negocio</h4>
            <div class="pp-stat-val pp-stat-highlight">5</div>
            <div class="pp-stat-label">Tareas pendientes por atender</div>
        </a>
        <a href="{{ route('proveedores.oc') }}" class="pp-card">
            <h4>OC abiertas</h4>
            <div class="pp-stat-val">3</div>
            <div class="pp-stat-label">Datos de prueba</div>
        </a>
        <a href="{{ route('proveedores.payment-history') }}" class="pp-card">
            <h4>Facturas pendientes</h4>
            <div class="pp-stat-val">—</div>
            <div class="pp-stat-label">Pendiente de API</div>
        </a>
        <a href="{{ route('proveedores.onboarding') }}" class="pp-card">
            <h4>Onboarding</h4>
            <div class="pp-stat-val">40%</div>
            <div class="pp-stat-label">2 de 5 pasos</div>
        </a>
    </div>

    <div class="pp-week-panel" id="weekPanel">
        <button type="button" class="pp-close-btn" onclick="closeWeekPanel()">✕</button>
        <h4 id="weekTitle">Semana W1</h4>
        <div class="pp-week-section">
            <h5>Órdenes de compra</h5>
            <div id="weekOC"></div>
        </div>
        <div class="pp-week-section">
            <h5>Pagos</h5>
            <div id="weekPagos"></div>
        </div>
        <div class="pp-week-section">
            <h5>Facturas</h5>
            <div id="weekFacturas"></div>
        </div>
    </div>

    <div class="pp-mid-grid">
        <div class="pp-card">
            <div class="pp-cal-header">
                <h4>Calendario</h4>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="pp-cal-nav">
                        <button type="button" onclick="calPrev()">◀</button>
                        <button type="button" onclick="calNext()">▶</button>
                    </div>
                    <span class="pp-cal-month" id="calMonth"></span>
                </div>
            </div>
            <table class="pp-cal-table" id="calTable">
                <thead>
                    <tr>
                        <th>WK</th>
                        <th>DOM</th>
                        <th>LUN</th>
                        <th>MAR</th>
                        <th>MIÉ</th>
                        <th>JUE</th>
                        <th>VIE</th>
                        <th>SÁB</th>
                    </tr>
                </thead>
                <tbody id="calBody"></tbody>
            </table>
        </div>

        <a href="{{ route('proveedores.forecast') }}" class="pp-card pp-score-card">
            <h4 style="font-size: 13px; font-weight: 600; color: var(--gray-text); margin-bottom: 8px; align-self: flex-start;">Mi score</h4>
            <div class="pp-score-donut">
                <canvas id="scoreDonut" width="120" height="120"></canvas>
                <div class="pp-score-center">
                    <div class="pp-score-num" style="color: var(--green)">0%</div>
                    <div class="pp-score-lbl">Total</div>
                </div>
            </div>
            <div class="pp-score-legend">
                <div class="pp-score-legend-row">
                    <div class="pp-score-legend-dot" style="background: var(--green)"></div>
                    Entrega
                    <span class="pp-score-legend-val">0%</span>
                </div>
                <div class="pp-score-legend-row">
                    <div class="pp-score-legend-dot" style="background: var(--purple)"></div>
                    Puntualidad
                    <span class="pp-score-legend-val">0%</span>
                </div>
            </div>
        </a>

        <div class="pp-card">
            <h4>
                Actividad reciente
                <a href="{{ route('proveedores.dashboard') }}" class="pp-card-link" style="float: right">Ver todo →</a>
            </h4>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-g"></div>
                <div class="pp-list-text">OC #10045 generada</div>
                <div class="pp-list-time">Pendiente</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-a"></div>
                <div class="pp-list-text">Factura en revisión</div>
                <div class="pp-list-time">Pendiente</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-g"></div>
                <div class="pp-list-text">Pago programado</div>
                <div class="pp-list-time">Pendiente</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-x"></div>
                <div class="pp-list-text">Documentos verificados</div>
                <div class="pp-list-time">Completado</div>
            </div>
        </div>

        <div class="pp-card">
            <h4>
                Onboarding
                <a href="{{ route('proveedores.onboarding') }}" class="pp-card-link" style="float: right">Ver →</a>
            </h4>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-g"></div>
                <div class="pp-list-text">Registro de proveedor</div>
                <div class="pp-list-time">Completado</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-g"></div>
                <div class="pp-list-text">Documentos fiscales</div>
                <div class="pp-list-time">Completado</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-a"></div>
                <div class="pp-list-text">Validación por Salcom</div>
                <div class="pp-list-time">En revisión</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-x"></div>
                <div class="pp-list-text">Primera OC</div>
                <div class="pp-list-time">Pendiente</div>
            </div>
        </div>
    </div>

    <div class="pp-forecast-grid">
        <div class="pp-forecast-card">
            <h4>Productos al alza</h4>
            <div class="pp-forecast-row">
                <span class="pp-forecast-name">Resina epóxica</span>
                <div class="pp-forecast-bar"><div class="pp-forecast-fill" style="width: 92%; background: var(--green)"></div></div>
                <span class="pp-forecast-trend pp-trend-up">↑ +12%</span>
            </div>
            <div class="pp-forecast-row">
                <span class="pp-forecast-name">Solvente técnico</span>
                <div class="pp-forecast-bar"><div class="pp-forecast-fill" style="width: 78%; background: var(--green)"></div></div>
                <span class="pp-forecast-trend pp-trend-up">↑ +8%</span>
            </div>
            <div class="pp-forecast-row">
                <span class="pp-forecast-name">Pigmento base agua</span>
                <div class="pp-forecast-bar"><div class="pp-forecast-fill" style="width: 65%; background: var(--green)"></div></div>
                <span class="pp-forecast-trend pp-trend-up">↑ +5%</span>
            </div>
            <p style="font-size: 11px; color: var(--gray-muted); margin-top: 8px">Basado en historial de pedidos · Datos de prueba</p>
        </div>
        <div class="pp-forecast-card">
            <h4>Productos a la baja</h4>
            <div class="pp-forecast-row">
                <span class="pp-forecast-name">Aditivo antioxidante</span>
                <div class="pp-forecast-bar"><div class="pp-forecast-fill" style="width: 35%; background: var(--red)"></div></div>
                <span class="pp-forecast-trend pp-trend-down">↓ -15%</span>
            </div>
            <div class="pp-forecast-row">
                <span class="pp-forecast-name">Catalizador rápido</span>
                <div class="pp-forecast-bar"><div class="pp-forecast-fill" style="width: 50%; background: var(--amber)"></div></div>
                <span class="pp-forecast-trend pp-trend-down">↓ -5%</span>
            </div>
            <div class="pp-forecast-row">
                <span class="pp-forecast-name">Fibra de refuerzo</span>
                <div class="pp-forecast-bar"><div class="pp-forecast-fill" style="width: 55%; background: var(--gray-muted)"></div></div>
                <span class="pp-forecast-trend pp-trend-flat">→ Estable</span>
            </div>
            <p style="font-size: 11px; color: var(--gray-muted); margin-top: 8px">Basado en historial de pedidos · Datos de prueba</p>
        </div>
    </div>

    <div class="pp-quick-grid">
        <a href="{{ route('proveedores.ia') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg>
            </div>
            <div>
                <div style="font-weight: 600; color: var(--gray-text); font-size: 14px">Dashboard IA</div>
                <div style="font-size: 12px; color: var(--gray-muted)">Análisis con Claude</div>
            </div>
        </a>
        <a href="{{ route('proveedores.oc') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
                <div style="font-weight: 600; color: var(--gray-text); font-size: 14px">Consultar OC</div>
                <div style="font-size: 12px; color: var(--gray-muted)">Órdenes de compra</div>
            </div>
        </a>
        <a href="{{ route('proveedores.payment-history') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div style="font-weight: 600; color: var(--gray-text); font-size: 14px">Historial de pagos</div>
                <div style="font-size: 12px; color: var(--gray-muted)">Pagos y facturas</div>
            </div>
        </a>
        <a href="{{ route('proveedores.alta-producto') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div>
                <div style="font-weight: 600; color: var(--gray-text); font-size: 14px">Alta de producto</div>
                <div style="font-size: 12px; color: var(--gray-muted)">Nuevo producto</div>
            </div>
        </a>
        <a href="{{ route('muestras.crear') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <div>
                <div style="font-weight: 600; color: var(--gray-text); font-size: 14px">Envío de muestras</div>
                <div style="font-size: 12px; color: var(--gray-muted)">Registro y seguimiento</div>
            </div>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const regDate = new Date('2026-03-25');
let calYear = new Date().getFullYear();
let calMon = new Date().getMonth();

const weekData = {
    1: { oc: [{ f: '#10045', m: '$12,500' }], pagos: [{ r: 'PAG-001', m: '$12,500' }], facturas: [{ f: 'FAC-001', m: '$12,500' }] },
    2: { oc: [{ f: '#10046', m: '$8,200' }], pagos: [], facturas: [{ f: 'FAC-002', m: '$8,200' }] },
    3: { oc: [{ f: '#10047', m: '$27,300' }, { f: '#10048', m: '$5,800' }], pagos: [{ r: 'PAG-002', m: '$33,100' }], facturas: [] },
    4: { oc: [], pagos: [{ r: 'PAG-003', m: '$15,100' }], facturas: [{ f: 'FAC-003', m: '$15,100' }] },
};

function getSupplierWeek(date) {
    const diff = date - regDate;
    if (diff < 0) return null;
    return Math.floor(diff / (7 * 24 * 60 * 60 * 1000)) + 1;
}

function renderCal() {
    document.getElementById('calMonth').textContent = MESES[calMon] + ' ' + calYear;
    const first = new Date(calYear, calMon, 1);
    const last = new Date(calYear, calMon + 1, 0);
    const startDay = first.getDay();
    const today = new Date();
    let html = '';
    let day = 1;
    for (let row = 0; row < 6; row++) {
        if (day > last.getDate()) break;
        const weekStart = new Date(calYear, calMon, day);
        const wk = getSupplierWeek(weekStart);
        html += '<tr>';
        html += '<td class="pp-cal-week">' + (wk ? 'W' + wk : '') + '</td>';
        for (let col = 0; col < 7; col++) {
            if (row === 0 && col < startDay) {
                html += '<td></td>';
                continue;
            }
            if (day > last.getDate()) {
                html += '<td></td>';
                continue;
            }
            const d = new Date(calYear, calMon, day);
            const isToday = d.toDateString() === today.toDateString();
            const sw = getSupplierWeek(d);
            const hasData = sw && weekData[sw];
            let cls = '';
            if (isToday) cls = 'pp-today';
            else if (hasData) cls = 'pp-has-data';
            const onclick = hasData ? ' onclick="showWeek(' + sw + ')"' : '';
            html += '<td class="' + cls + '"' + onclick + '>' + day + '</td>';
            day++;
        }
        html += '</tr>';
    }
    document.getElementById('calBody').innerHTML = html;
}

function calPrev() {
    calMon--;
    if (calMon < 0) {
        calMon = 11;
        calYear--;
    }
    renderCal();
}
function calNext() {
    calMon++;
    if (calMon > 11) {
        calMon = 0;
        calYear++;
    }
    renderCal();
}

function showWeek(wk) {
    const d = weekData[wk] || { oc: [], pagos: [], facturas: [] };
    document.getElementById('weekTitle').textContent = 'Semana W' + wk;
    document.getElementById('weekOC').innerHTML = d.oc.length
        ? d.oc.map((o) => '<div class="pp-week-row"><span>' + o.f + '</span><span>' + o.m + '</span></div>').join('')
        : '<div style="font-size:13px;color:var(--gray-muted);">Sin órdenes esta semana</div>';
    document.getElementById('weekPagos').innerHTML = d.pagos.length
        ? d.pagos.map((p) => '<div class="pp-week-row"><span>' + p.r + '</span><span>' + p.m + '</span></div>').join('')
        : '<div style="font-size:13px;color:var(--gray-muted);">Sin pagos esta semana</div>';
    document.getElementById('weekFacturas').innerHTML = d.facturas.length
        ? d.facturas.map((f) => '<div class="pp-week-row"><span>' + f.f + '</span><span>' + f.m + '</span></div>').join('')
        : '<div style="font-size:13px;color:var(--gray-muted);">Sin facturas esta semana</div>';
    document.getElementById('weekPanel').classList.add('active');
}

function closeWeekPanel() {
    document.getElementById('weekPanel').classList.remove('active');
}

renderCal();

(function () {
    const c = document.getElementById('scoreDonut');
    if (!c) return;
    const ctx = c.getContext('2d');
    const cx = 60;
    const cy = 60;
    const r = 48;
    const sw = 14;
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = sw;
    ctx.stroke();
    const segs = [
        { pct: 0.5, color: '#059669' },
        { pct: 0.5, color: '#6B3FA0' },
    ];
    let start = -Math.PI / 2;
    segs.forEach((s) => {
        const end = start + Math.PI * 2 * s.pct - 0.04;
        ctx.beginPath();
        ctx.arc(cx, cy, r, start, end);
        ctx.strokeStyle = s.color;
        ctx.lineWidth = sw;
        ctx.lineCap = 'round';
        ctx.stroke();
        start = end + 0.04;
    });
})();
</script>
@endpush
