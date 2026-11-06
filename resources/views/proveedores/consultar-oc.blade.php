@extends('layouts.proveedor')

@section('title', 'Órdenes de Compra')

@section('hero')
<div class="hero-band">
    <h1>Órdenes de Compra</h1>
    <p>Tus órdenes de compra registradas en el sistema de Industrias Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .search-bar { display: flex; gap: 12px; margin-bottom: 28px; }
    .search-input { flex: 1; border: 1.5px solid var(--border); border-radius: 10px; padding: 11px 16px; font-size: 14px; font-family: 'Nunito', sans-serif; color: var(--gray-text); background: var(--white); outline: none; transition: border-color .2s, box-shadow .2s; }
    .search-input::placeholder { color: #BDB8CC; }
    .search-input:focus { border-color: var(--purple-mid); box-shadow: 0 0 0 3px rgba(156,109,208,0.12); }
    .btn-search { padding: 11px 24px; background: var(--purple); color: var(--white); border: none; border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background .2s; }
    .btn-search:hover { background: var(--purple-dark); }

    .metrics-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    .metric-card { background: var(--white); border-radius: 12px; padding: 18px 20px; border: 0.5px solid var(--border); position: relative; overflow: hidden; }
    .metric-card .accent { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 12px 0 0 12px; }
    .metric-label { font-size: 12px; color: var(--gray-text); font-weight: 500; margin-bottom: 6px; padding-left: 8px; }
    .metric-value { font-size: 26px; font-weight: 600; color: var(--purple-dark); padding-left: 8px; line-height: 1; }

    .card { background: var(--white); border-radius: 14px; border: 0.5px solid var(--border); overflow: hidden; }
    .card-head { padding: 14px 20px; border-bottom: 0.5px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .card-head h3 { font-size: 14px; font-weight: 600; color: var(--purple-dark); }
    .btn-excel { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: #16a34a; color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; transition: background .2s; }
    .btn-excel:hover { background: #15803d; }

    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th { font-size: 11px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; text-align: left; background: var(--gray-soft); border-bottom: 0.5px solid var(--border); }
    .tabla td { padding: 12px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 0.5px solid var(--border); }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--gray-soft); }
    .monto { font-variant-numeric: tabular-nums; }

    .badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
    /* POR QUÉ colores fijos (hex) y NO variables: las variables --red/--red-bg no siempre están
       definidas en el tema del portal del proveedor, y si no existen el color sale vacío (no se veía
       el rojo). Con hex directo se ve siempre. */
    .badge-amber  { background: #fef3c7; color: #b45309; }
    .badge-green  { background: #dcfce7; color: #166534; }
    .badge-red    { background: #fee2e2; color: #dc2626; }

    .aviso { background: var(--amber-bg,#fef3cd); border: 1px solid var(--amber,#d97706); border-radius: 10px; padding: 14px; font-size: 13px; color: var(--amber); margin-bottom: 20px; }

    @media (max-width: 768px) { .metrics-row { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')

    @if($wieseError)
        <div class="aviso">{{ $wieseError }}</div>
    @endif

    {{-- BUSCADOR --}}
    <div class="search-bar">
        <input type="text" class="search-input" id="buscarFolio" placeholder="Buscar por folio, fecha o estatus...">
        <button class="btn-search" onclick="buscarOC()">Buscar</button>
    </div>

    {{-- KPIs clickeables: al picar uno, filtra la tabla por ese estatus. "Total de OC" muestra todo.
         Colores fijos (hex) para que se vean sí o sí, sin depender de variables del tema. --}}
    <div class="metrics-row">
        <div class="metric-card" style="cursor:pointer;" data-filtro="todos" onclick="filtrarOC('todos', this)">
            <div class="accent" style="background:#6B3FA0"></div>
            <div class="metric-label">Total de OC</div>
            <div class="metric-value">{{ $stats['total'] }}</div>
        </div>
        <div class="metric-card" style="cursor:pointer;" data-filtro="pendiente" onclick="filtrarOC('pendiente', this)">
            <div class="accent" style="background:#dc2626"></div>
            <div class="metric-label">Pendientes</div>
            <div class="metric-value" style="color:#dc2626;">{{ $stats['pendientes'] }}</div>
        </div>
        <div class="metric-card" style="cursor:pointer;" data-filtro="pagada" onclick="filtrarOC('pagada', this)">
            <div class="accent" style="background:#16a34a"></div>
            <div class="metric-label">Pagadas</div>
            <div class="metric-value" style="color:#16a34a;">{{ $stats['pagadas'] }}</div>
        </div>
        <div class="metric-card" style="cursor:pointer;" data-filtro="cancelada" onclick="filtrarOC('cancelada', this)">
            <div class="accent" style="background:#9ca3af"></div>
            <div class="metric-label">Canceladas</div>
            <div class="metric-value" style="color:#6b7280;">{{ $stats['canceladas'] }}</div>
        </div>
    </div>

    {{-- TABLA de OC reales de Wiese --}}
    <div class="card">
        <div class="card-head">
            <h3>Órdenes de Compra ({{ $stats['total'] }})</h3>
            <button class="btn-excel" onclick="exportarExcel('tablaOC','ordenes-compra')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </button>
        </div>
        <div style="overflow-x:auto;">
        <table class="tabla" id="tablaOC">
            <thead>
                <tr>
                    <th>Folio OC</th>
                    <th>Serie</th>
                    <th>Fecha</th>
                    <th>Vencimiento</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:right;">Pendiente</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $oc)
                <tr class="oc-row" data-estatus="{{ $oc['estatus'] }}">
                    <td><strong>{{ $oc['folio'] ?: '—' }}</strong></td>
                    <td>{{ $oc['serie'] ?: '—' }}</td>
                    <td>{{ !empty($oc['fecha']) ? \Illuminate\Support\Carbon::parse($oc['fecha'])->format('d/m/Y') : '—' }}</td>
                    <td>{{ !empty($oc['vence']) ? \Illuminate\Support\Carbon::parse($oc['vence'])->format('d/m/Y') : '—' }}</td>
                    <td class="monto" style="text-align:right;">${{ number_format($oc['total'], 2) }}</td>
                    <td class="monto" style="text-align:right;font-weight:600;">${{ number_format($oc['pendiente'], 2) }}</td>
                    <td>
                        @if($oc['estatus'] === 'pagada')
                            <span class="badge badge-green">Pagada</span>
                        @elseif($oc['estatus'] === 'cancelada')
                            <span class="badge badge-red">Cancelada</span>
                        @else
                            <span class="badge badge-red">Pendiente</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:var(--gray-muted);">
                        No se encontraron órdenes de compra registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function exportarExcel(tablaId, nombre) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    let csv = '';
    tabla.querySelectorAll('tr').forEach(fila => {
        const data = Array.from(fila.querySelectorAll('th,td')).map(c => '"' + c.textContent.trim().replace(/"/g,'""').replace(/\n/g,' ') + '"');
        csv += data.join(',') + '\n';
    });
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = nombre + '-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

// Estado de los dos filtros que trabajan juntos: el texto del buscador y el estatus del KPI.
var ocFiltroEstatus = 'todos';

// Aplica AMBOS filtros a la vez (texto + estatus) sobre cada fila de la tabla.
function aplicarFiltrosOC() {
    var texto = (document.getElementById('buscarFolio').value || '').toLowerCase();
    document.querySelectorAll('#tablaOC tbody tr.oc-row').forEach(function (fila) {
        var coincideTexto = fila.textContent.toLowerCase().includes(texto);
        var est = fila.getAttribute('data-estatus') || '';
        var coincideEstatus = (ocFiltroEstatus === 'todos') || (est === ocFiltroEstatus);
        fila.style.display = (coincideTexto && coincideEstatus) ? '' : 'none';
    });
}

// Buscador (botón y Enter).
function buscarOC() { aplicarFiltrosOC(); }
document.getElementById('buscarFolio').addEventListener('keyup', function (e) { aplicarFiltrosOC(); });

// KPI clickeable: filtra por estatus y resalta la tarjeta activa.
function filtrarOC(estatus, card) {
    ocFiltroEstatus = (ocFiltroEstatus === estatus && estatus !== 'todos') ? 'todos' : estatus;

    // Resaltar la tarjeta activa (borde morado); limpiar las demás.
    document.querySelectorAll('.metric-card').forEach(function (c) {
        c.style.boxShadow = '';
        c.style.borderColor = '';
    });
    if (ocFiltroEstatus !== 'todos' && card) {
        card.style.boxShadow = '0 0 0 2px #6B3FA0';
    }

    aplicarFiltrosOC();
}
</script>
@endpush
