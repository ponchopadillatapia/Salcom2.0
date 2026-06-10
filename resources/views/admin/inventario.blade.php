@extends('layouts.admin')
@section('title', 'Inventario')
@section('hero')
<div class="hero-band">
    <h1>Inventario</h1>
    <p>Control de stock y disponibilidad de productos</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .inv-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
    .inv-kpi{background:var(--white);border:2px solid var(--border-light);border-radius:14px;padding:22px;text-align:center;position:relative;overflow:hidden;cursor:pointer;transition:var(--transition)}
    .inv-kpi:hover{border-color:var(--purple);box-shadow:0 4px 16px rgba(107,63,160,0.12);transform:translateY(-2px)}
    .inv-kpi.active-filter{border-color:var(--purple);box-shadow:0 4px 16px rgba(107,63,160,0.18);background:var(--purple-subtle)}
    .inv-kpi .bar{position:absolute;top:0;left:0;right:0;height:4px}
    .inv-kpi-val{font-size:30px;font-weight:800;line-height:1;margin-bottom:6px}
    .inv-kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px}

    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden}
    .table-head{padding:16px 22px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between}
    .table-head .filter-label{font-size:12px;color:var(--purple);font-weight:600}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .badge-stock{font-size:11px;font-weight:600;padding:4px 12px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .stock-bar{width:80px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .stock-fill{height:100%;border-radius:3px}

    .prov-link{color:var(--purple);font-weight:600;cursor:pointer;text-decoration:none;transition:color .15s}
    .prov-link:hover{color:var(--purple-dark);text-decoration:underline}

    /* Modal proveedor */
    .prov-modal-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:9999;align-items:center;justify-content:center}
    .prov-modal-overlay.show{display:flex}
    .prov-modal{background:var(--white);border-radius:18px;padding:28px 32px;width:360px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,0.15);position:relative;animation:fadeUp .25s ease}
    .prov-modal-close{position:absolute;top:14px;right:14px;background:none;border:none;cursor:pointer;color:var(--gray-muted);font-size:20px;line-height:1;padding:4px 8px;border-radius:6px;transition:background .15s}
    .prov-modal-close:hover{background:var(--gray-soft);color:var(--gray-text)}
    .prov-modal h3{font-size:16px;font-weight:700;color:var(--gray-text);margin-bottom:4px}
    .prov-modal .prov-tipo{font-size:12px;color:var(--gray-muted);margin-bottom:18px}
    .prov-modal-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-light)}
    .prov-modal-row:last-child{border-bottom:none}
    .prov-modal-row svg{flex-shrink:0;color:var(--purple)}
    .prov-modal-row .prov-info-label{font-size:11px;color:var(--gray-muted);font-weight:600}
    .prov-modal-row .prov-info-value{font-size:14px;color:var(--gray-text);font-weight:600}
    .prov-modal-row a{color:var(--purple);text-decoration:none;font-weight:600;font-size:14px}
    .prov-modal-row a:hover{text-decoration:underline}

    .tbl tr.hidden-row{display:none}

    @media(max-width:900px){.inv-kpis{grid-template-columns:1fr 1fr}}
    @media(max-width:768px){.table-card{overflow-x:auto}}
</style>
@endpush
@section('content')

<div class="inv-kpis anim">
    <div class="inv-kpi" data-filter="out" onclick="filterTable('out')">
        <div class="bar" style="background:var(--red)"></div>
        <div class="inv-kpi-val" style="color:var(--red)">{{ $sinStock }}</div>
        <div class="inv-kpi-label">Agotados</div>
    </div>
    <div class="inv-kpi" data-filter="low" onclick="filterTable('low')">
        <div class="bar" style="background:var(--amber)"></div>
        <div class="inv-kpi-val" style="color:var(--amber)">{{ $stockBajo }}</div>
        <div class="inv-kpi-label">Stock Bajo</div>
    </div>
    <div class="inv-kpi" data-filter="ok" onclick="filterTable('ok')">
        <div class="bar" style="background:var(--green)"></div>
        <div class="inv-kpi-val" style="color:var(--green)">{{ $stockOk }}</div>
        <div class="inv-kpi-label">Stock OK</div>
    </div>
    <div class="inv-kpi" data-filter="all" onclick="filterTable('all')">
        <div class="bar" style="background:var(--purple)"></div>
        <div class="inv-kpi-val" style="color:var(--purple)">{{ $productos->count() }}</div>
        <div class="inv-kpi-label">Productos</div>
    </div>
</div>

<div class="table-card">
    <div class="table-head">
        <span>Detalle de productos</span>
        <span class="filter-label" id="filterLabel">Mostrando: Todos</span>
    </div>
    <table class="tbl">
        <thead><tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Proveedor</th><th>Precio</th><th>Stock</th><th>Nivel</th><th>Estado</th></tr></thead>
        <tbody>
        @php $maxStock = $productos->max('stock') ?: 1; @endphp
        @foreach($productos as $p)
            @php
                $cls = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok');
                $lbl = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK');
                $pct = round(($p->stock / $maxStock) * 100);
                $barColor = $p->stock <= 0 ? '#ff3b30' : ($p->stock < 50 ? '#ff9f0a' : '#34c759');
                $provNombre = $p->proveedor_nombre ?: 'Sin asignar';
                // Buscar datos de contacto del proveedor
                $provUser = $p->proveedor_nombre ? \App\Models\ProveedorUser::where('nombre', 'LIKE', '%'.$p->proveedor_nombre.'%')->first() : null;
                $provTelefono = $provUser->telefono ?? '—';
                $provCorreo = $provUser->correo ?? '—';
            @endphp
            <tr data-stock-level="{{ $cls }}">
                <td style="font-weight:700;color:var(--purple)">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td style="color:var(--gray-muted)">{{ $p->categoria }}</td>
                <td>
                    <span class="prov-link" onclick="showProvModal('{{ addslashes($provNombre) }}', '{{ $p->proveedor_tipo ?? 'Proveedor' }}', '{{ $provTelefono }}', '{{ $provCorreo }}')">
                        {{ $provNombre }}
                    </span>
                </td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($p->precio, 2) }}</td>
                <td style="font-weight:700">{{ number_format($p->stock) }}</td>
                <td><div class="stock-bar"><div class="stock-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div></div></td>
                <td><span class="badge-stock {{ $cls }}">{{ $lbl }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Modal de contacto del proveedor --}}
<div class="prov-modal-overlay" id="provModal" onclick="closeProvModal(event)">
    <div class="prov-modal">
        <button class="prov-modal-close" onclick="document.getElementById('provModal').classList.remove('show')">&times;</button>
        <h3 id="provModalName">—</h3>
        <div class="prov-tipo" id="provModalTipo">Proveedor</div>
        <div class="prov-modal-row">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <div>
                <div class="prov-info-label">Teléfono</div>
                <div class="prov-info-value" id="provModalTel">—</div>
            </div>
        </div>
        <div class="prov-modal-row">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <div>
                <div class="prov-info-label">Correo electrónico</div>
                <a id="provModalCorreo" href="#">—</a>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
let currentFilter = 'all';

function filterTable(level) {
    currentFilter = level;
    const rows = document.querySelectorAll('.tbl tbody tr');
    const labels = { all: 'Todos', out: 'Agotados', low: 'Stock Bajo', ok: 'Stock OK' };

    // Actualizar KPI activo
    document.querySelectorAll('.inv-kpi').forEach(kpi => {
        kpi.classList.toggle('active-filter', kpi.dataset.filter === level);
    });

    // Filtrar filas
    rows.forEach(row => {
        if (level === 'all' || row.dataset.stockLevel === level) {
            row.classList.remove('hidden-row');
        } else {
            row.classList.add('hidden-row');
        }
    });

    document.getElementById('filterLabel').textContent = 'Mostrando: ' + labels[level];
}

function showProvModal(nombre, tipo, telefono, correo) {
    document.getElementById('provModalName').textContent = nombre;
    document.getElementById('provModalTipo').textContent = tipo;
    document.getElementById('provModalTel').textContent = telefono;
    const correoEl = document.getElementById('provModalCorreo');
    correoEl.textContent = correo;
    correoEl.href = correo !== '—' ? 'mailto:' + correo : '#';
    document.getElementById('provModal').classList.add('show');
}

function closeProvModal(e) {
    if (e.target === document.getElementById('provModal')) {
        document.getElementById('provModal').classList.remove('show');
    }
}

// Empezar mostrando todos
document.addEventListener('DOMContentLoaded', function() {
    filterTable('all');
});
</script>
@endpush
