@extends('layouts.admin')
@section('title', 'Pago a proveedores')
@section('hero')
<div class="hero-band">
    <h1>Pago a proveedores</h1>
    <p>Abonos / pólizas Contpaqi · 8969 nacional · 2026 dólar</p>
</div>
@endsection
@push('styles')
<style>
    .pp-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:18px}
    .pp-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
    .pp-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:14px 16px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
    .pp-card:hover,.pp-card.is-active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.1)}
    .pp-card .serie{font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:var(--gray-muted)}
    .pp-card h3{margin:6px 0 4px;font-size:14px;font-weight:700;color:var(--gray-text)}
    .pp-card p{margin:0;font-size:12px;color:var(--gray-muted);line-height:1.35}
    .pp-card .count{margin-top:10px;font-size:20px;font-weight:800;color:var(--purple)}
    .btn-nuevo{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:var(--purple);color:#fff;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none}
    .btn-nuevo:hover{background:var(--purple-dark);color:#fff}
    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:16px}
    .filter-form{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:160px;flex:1}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .filter-field input,.filter-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit}
    .btn-primary{padding:9px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px}
    .btn-outline{padding:9px 14px;background:#fff;border:1.5px solid var(--border);border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;color:var(--gray-text)}
    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 14px;font-size:13px;border-bottom:1px solid var(--border)}
    .admin-table tr{cursor:pointer}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.bad{background:var(--red-bg);color:var(--red)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .empty{padding:40px;text-align:center;color:var(--gray-muted)}
    @media(max-width:960px){.pp-cards{grid-template-columns:1fr 1fr}}
    @media(max-width:640px){.pp-cards{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('ok'))
    <div class="pag-alert ok">{{ session('ok') }}</div>
@endif

<div class="pp-toolbar">
    <div style="font-size:13px;color:var(--gray-muted)">Selecciona una póliza o crea un abono nuevo</div>
    <a href="{{ route('admin.pago-proveedores.nuevo') }}" class="btn-nuevo">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo pago
    </a>
</div>

<div class="pp-cards">
@foreach($polizas as $key => $p)
    <a href="{{ route('admin.pago-proveedores', ['poliza' => $key]) }}" class="pp-card {{ $filtro === $key ? 'is-active' : '' }}">
        <div class="serie" style="color:{{ $p['color'] }}">Serie {{ $p['serie'] }} · {{ $p['moneda'] }}</div>
        <h3>{{ $p['titulo'] }}</h3>
        <p>{{ $p['descripcion'] }}</p>
        <div class="count">{{ (int) ($conteos[$key] ?? 0) }}</div>
    </a>
@endforeach
</div>

<div class="filters-panel">
    <form method="get" class="filter-form">
        <div class="filter-field">
            <label>Buscar</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Proveedor, folio, serie…">
        </div>
        <div class="filter-field">
            <label>Póliza</label>
            <select name="poliza">
                <option value="">Todas</option>
                @foreach($polizas as $key => $p)
                    <option value="{{ $key }}" @selected($filtro === $key)>{{ $p['titulo'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filtrar</button>
        <a href="{{ route('admin.pago-proveedores') }}" class="btn-outline">Limpiar</a>
    </form>
</div>

<div class="adm-section">
    <div class="tbl-wrap" style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Serie / Folio</th>
                    <th>Póliza</th>
                    <th>Proveedor</th>
                    <th>Moneda</th>
                    <th>Tipo cambio</th>
                    <th>Pago</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
            @forelse($abonos as $a)
                @php $meta = $polizas[$a->poliza_key] ?? null; @endphp
                <tr onclick="window.location='{{ route('admin.pago-proveedores.show', $a) }}'">
                    <td>{{ optional($a->fecha)->format('d/m/Y') }}</td>
                    <td><strong>{{ $a->serie }}</strong> · {{ $a->folio }}</td>
                    <td>{{ $meta['titulo'] ?? $a->poliza_key }}</td>
                    <td>
                        <div style="font-weight:600">{{ $a->nombre_proveedor }}</div>
                        <div style="font-size:11px;color:var(--gray-muted)">{{ $a->codigo_proveedor }}</div>
                    </td>
                    <td>{{ $a->moneda }}</td>
                    <td>{{ number_format((float)$a->tipo_cambio, 4) }}</td>
                    <td class="monto">${{ number_format((float)$a->monto_pago, 2) }}</td>
                    <td>
                        @if($a->estatus === 'guardado')
                            <span class="pill ok">Guardado</span>
                        @elseif($a->estatus === 'borrador')
                            <span class="pill warn">Borrador</span>
                        @else
                            <span class="pill bad">Cancelado</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">Aún no hay abonos. Pulsa <strong>Nuevo pago</strong> y elige la póliza.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($abonos->hasPages())
        <div style="padding:14px">{{ $abonos->links() }}</div>
    @endif
</div>
@endsection
