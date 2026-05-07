@extends('layouts.admin')
@section('title', 'Facturas')
@section('hero')
<div class="hero-band">
    <h1>Facturas</h1>
    <p>Control de facturación — clientes y proveedores</p>
</div>
@endsection
@push('styles')
<style>
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap}
    .filter-btn{padding:8px 16px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-block}
    .filter-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .filter-btn.danger{background:var(--red);color:#fff;border-color:var(--red)}

    .fact-tabs{display:flex;gap:4px;background:var(--gray-soft);border-radius:12px;padding:4px;margin-bottom:20px;width:fit-content}
    .fact-tab{padding:10px 24px;font-size:13px;font-weight:600;color:var(--gray-muted);cursor:pointer;border:none;background:none;border-radius:10px;font-family:inherit;transition:all .2s}
    .fact-tab:hover{color:var(--purple);background:rgba(107,63,160,.06)}
    .fact-tab.active{color:var(--purple);background:var(--white);box-shadow:0 1px 4px rgba(0,0,0,.06)}
    .fact-panel{display:none}.fact-panel.active{display:block}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-est.pagada{background:var(--green-bg);color:var(--green)}
    .badge-est.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-est.cancelada{background:var(--red-bg);color:var(--red)}
    .badge-vencida{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--red-bg);color:var(--red);margin-left:6px}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500}
    .section-count{font-size:12px;color:var(--gray-muted);font-weight:500;margin-bottom:12px}
    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')

<div class="toolbar">
    <div class="filter-group">
        <a href="{{ route('admin.facturas') }}" class="filter-btn {{ !$estatus && !$vencidas ? 'active' : '' }}">Todas</a>
        <a href="{{ route('admin.facturas', ['estatus' => 'pendiente']) }}" class="filter-btn {{ $estatus === 'pendiente' ? 'active' : '' }}">Pendientes</a>
        <a href="{{ route('admin.facturas', ['estatus' => 'pagada']) }}" class="filter-btn {{ $estatus === 'pagada' ? 'active' : '' }}">Pagadas</a>
        <a href="{{ route('admin.facturas', ['estatus' => 'cancelada']) }}" class="filter-btn {{ $estatus === 'cancelada' ? 'active' : '' }}">Canceladas</a>
        <a href="{{ route('admin.facturas', ['vencidas' => '1']) }}" class="filter-btn {{ $vencidas ? 'danger' : '' }}">Vencidas</a>
    </div>
</div>

<div class="fact-tabs">
    <button class="fact-tab active" onclick="switchFactTab('clientes')">Clientes ({{ $facturasClientes->count() }})</button>
    <button class="fact-tab" onclick="switchFactTab('proveedores')">Proveedores ({{ $facturasProveedores->count() }})</button>
</div>

{{-- ═══ FACTURAS DE CLIENTES ═══ --}}
<div class="fact-panel active" id="panel-clientes">
    <div class="admin-table-wrap">
    @if($facturasClientes->count())
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Folio CFDI</th>
                    <th>Cliente</th>
                    <th>Monto</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Estatus</th>
                    <th>Vencimiento</th>
                </tr>
            </thead>
            <tbody>
            @foreach($facturasClientes as $f)
                @php $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast(); @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi }}</td>
                    <td>{{ $f->codigo_cliente }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto, 2) }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto_iva, 2) }}</td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                    <td>
                        <span class="badge-est {{ $f->estatus }}">{{ ucfirst($f->estatus) }}</span>
                        @if($vencida)<span class="badge-vencida">VENCIDA</span>@endif
                    </td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">
                        {{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No hay facturas de clientes con ese filtro</p></div>
    @endif
    </div>
</div>

{{-- ═══ FACTURAS DE PROVEEDORES ═══ --}}
<div class="fact-panel" id="panel-proveedores">
    <div class="admin-table-wrap">
    @if($facturasProveedores->count())
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Folio CFDI</th>
                    <th>Proveedor</th>
                    <th>Monto</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Estatus</th>
                    <th>Vencimiento</th>
                </tr>
            </thead>
            <tbody>
            @foreach($facturasProveedores as $f)
                @php $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast(); @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi }}</td>
                    <td>{{ $f->codigo_proveedor }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto, 2) }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto_iva, 2) }}</td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                    <td>
                        <span class="badge-est {{ $f->estatus }}">{{ ucfirst($f->estatus) }}</span>
                        @if($vencida)<span class="badge-vencida">VENCIDA</span>@endif
                    </td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">
                        {{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No hay facturas de proveedores con ese filtro</p></div>
    @endif
    </div>
</div>

@endsection
@push('scripts')
<script>
function switchFactTab(tab) {
    document.querySelectorAll('.fact-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.fact-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
@endpush
