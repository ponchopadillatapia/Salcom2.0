@extends('layouts.proveedor')

@section('title', 'Consultar OC')

@section('hero')
<div class="hero-band">
    <h1>Consultar Órdenes de Compra</h1>
    <p>Revisa tus órdenes de compra generadas automáticamente por la IA</p>
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
    .metric-sub { font-size: 11px; color: #AAA; padding-left: 8px; margin-top: 4px; }

    .card { background: var(--white); border-radius: 14px; border: 0.5px solid var(--border); overflow: hidden; }
    .card-head { padding: 14px 20px; border-bottom: 0.5px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .card-head h3 { font-size: 14px; font-weight: 600; color: var(--purple-dark); }
    .card-head-right { display: flex; align-items: center; gap: 10px; }
    .btn-excel { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: #16a34a; color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; transition: background .2s; }
    .btn-excel:hover { background: #15803d; }

    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th { font-size: 11px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; text-align: left; background: var(--gray-soft); border-bottom: 0.5px solid var(--border); }
    .tabla td { padding: 12px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 0.5px solid var(--border); }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--gray-soft); }

    .badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
    .badge-green  { background: var(--green-bg); color: var(--green); }
    .badge-amber  { background: var(--amber-bg); color: var(--amber); }
    .badge-blue   { background: var(--blue-bg);  color: var(--blue); }

    .oc-gen-section { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 22px; margin-bottom: 28px; }
    .btn-generar { display: inline-block; padding: 10px 22px; background: var(--purple); color: var(--white); font-size: 13px; font-weight: 600; border-radius: 999px; border: none; cursor: pointer; transition: background .2s, transform .1s; }
    .btn-generar:hover { background: var(--purple-dark); transform: translateY(-1px); }
    .alert-success { background: var(--green-bg); border: 1px solid var(--green); border-radius: 8px; padding: 10px 16px; font-size: 13px; color: var(--green); margin-bottom: 16px; }
    .alert-info { background: var(--blue-bg); border: 1px solid var(--blue); border-radius: 8px; padding: 10px 16px; font-size: 13px; color: var(--blue); margin-bottom: 16px; }

    @media (max-width: 768px) { .metrics-row { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')

    @if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
    @endif

    {{-- ═══ Generar OC ═══ --}}
    <div class="oc-gen-section">
        <h3 style="font-size: 14px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px;">Generación automática de OC</h3>
        <div style="background: var(--purple-subtle); border-radius: 10px; padding: 14px 18px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: var(--gray-muted); font-weight: 600; margin-bottom: 4px;">Fórmula</div>
            <div style="font-size: 13px; color: var(--gray-text); font-weight: 600;">OC sugerida = (Consumo promedio anual / 2) + Necesidades adicionales</div>
        </div>
        <p style="font-size: 12px; color: var(--gray-muted); margin-bottom: 16px;">
            La IA analiza el inventario actual y genera una OC con los productos que están bajo el mínimo. Se auto-aprueba inmediatamente.
        </p>
        <form method="POST" action="{{ route('proveedores.oc.generar') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-generar">Generar OC ahora</button>
        </form>
    </div>

    {{-- BUSCADOR --}}
    <div class="search-bar">
        <input type="text" class="search-input" id="buscarFolio" placeholder="Buscar por número de folio o producto...">
        <button class="btn-search" onclick="buscarOC()">Buscar</button>
    </div>

    {{-- MÉTRICAS --}}
    <div class="metrics-row">
        <div class="metric-card">
            <div class="accent" style="background:var(--purple)"></div>
            <div class="metric-label">OC Abiertas</div>
            <div class="metric-value">{{ $stats['abiertas'] }}</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--green)"></div>
            <div class="metric-label">OC Completadas</div>
            <div class="metric-value">{{ $stats['completadas'] }}</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--amber)"></div>
            <div class="metric-label">OC En proceso</div>
            <div class="metric-value">{{ $stats['en_proceso'] }}</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--blue)"></div>
            <div class="metric-label">Monto total</div>
            <div class="metric-value">${{ number_format($stats['monto_total'], 0) }}</div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="card">
        <div class="card-head">
            <h3>Órdenes de Compra</h3>
            <div class="card-head-right">
                <button class="btn-excel" onclick="exportarExcel('tablaOC','ordenes-compra')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar Excel
                </button>
            </div>
        </div>
        <div style="overflow-x:auto;">
        <table class="tabla" id="tablaOC">
            <thead>
                <tr><th>OC #</th><th>Fecha</th><th>Productos</th><th>Monto</th><th>Motivo</th><th>Estatus</th></tr>
            </thead>
            <tbody>
                @forelse($ordenes as $oc)
                <tr>
                    <td><strong>#{{ $oc->id }}</strong></td>
                    <td>{{ $oc->created_at->format('d/m/Y') }}</td>
                    <td style="font-size:12px;">
                        @foreach($oc->productos ?? [] as $prod)
                            <strong>{{ $prod['codigo'] ?? '' }}</strong> {{ $prod['nombre'] ?? '' }} ({{ $prod['cantidad'] ?? 0 }} {{ $prod['unidad'] ?? '' }})<br>
                        @endforeach
                    </td>
                    <td style="font-weight:600;">${{ number_format($oc->monto_estimado, 2) }}</td>
                    <td style="font-size:12px;color:var(--gray-muted);">{{ Str::limit($oc->motivo, 40) }}</td>
                    <td>
                        @if($oc->estatus === 'aprobada')
                            <span class="badge badge-green">Aprobada</span>
                        @elseif($oc->estatus === 'en_proceso')
                            <span class="badge badge-amber">En proceso</span>
                        @elseif($oc->estatus === 'completada')
                            <span class="badge badge-blue">Completada</span>
                        @else
                            <span class="badge badge-amber">{{ ucfirst($oc->estatus) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:32px;color:var(--gray-muted);">
                        No hay órdenes de compra. Se generarán automáticamente cuando el inventario lo requiera.
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

function buscarOC() {
    const texto = document.getElementById('buscarFolio').value.toLowerCase();
    document.querySelectorAll('#tablaOC tbody tr').forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
    });
}
document.getElementById('buscarFolio').addEventListener('keyup', e => { if (e.key === 'Enter') buscarOC(); });
</script>
@endpush
