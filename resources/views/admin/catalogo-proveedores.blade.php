@extends('layouts.admin')
@section('title', 'Catálogo de Proveedores')
@section('hero')
<div class="hero-band">
    <h1>Catálogo de Proveedores</h1>
    <p>Listado de proveedores registrados, agrupados por fecha de alta</p>
</div>
@endsection

@push('styles')
<style>
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
    .search-box{position:relative;flex:1;min-width:240px;max-width:420px}
    .search-box input{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:9px 12px 9px 36px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .search-box input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .search-box svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);opacity:.5}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .tbl-wrap{overflow-x:auto}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border);white-space:nowrap}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border);vertical-align:top}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:not(.date-row):hover td{background:var(--purple-subtle)}

    .moneda-pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block;white-space:nowrap}
    .moneda-pill.mxn{background:var(--purple-subtle);color:var(--purple)}
    .moneda-pill.usd{background:var(--green-bg);color:var(--green)}
    .cod-link{font-weight:700;color:var(--purple);text-decoration:none}
    .muted{color:var(--gray-muted)}
    .empty-state{padding:48px 24px;text-align:center;color:var(--gray-muted)}
</style>
@endpush

@section('content')
<div class="toolbar">
    <form method="GET" action="{{ route('admin.catalogo-proveedores') }}" class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar por código, razón social o RFC..." onchange="this.form.submit()">
    </form>
    <span class="badge-count">{{ $total }} proveedor{{ $total === 1 ? '' : 'es' }}</span>
</div>

<div class="adm-section">
    <div class="tbl-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código proveedor</th>
                    <th>Razón social</th>
                    <th>R.F.C.</th>
                    <th>Segmento contable 1</th>
                    <th>Fecha de alta</th>
                    <th>Calle</th>
                    <th>Ciudad</th>
                    <th>Código Postal</th>
                    <th>Colonia</th>
                    <th>Moneda</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agrupados as $fechaKey => $filas)
                    <tr class="date-row">
                        <td colspan="10">
                            @if($fechaKey === 'sin-fecha')
                                Sin fecha de alta
                            @else
                                {{ \Carbon\Carbon::parse($fechaKey)->locale('es')->isoFormat('DD [de] MMMM YYYY') }}
                            @endif
                        </td>
                    </tr>
                    @foreach($filas as $prov)
                        <tr>
                            <td><span class="cod-link">{{ $prov['codigo'] }}</span></td>
                            <td style="font-weight:600">{{ $prov['nombre'] }}</td>
                            <td style="font-variant-numeric:tabular-nums">{{ $prov['rfc'] }}</td>
                            <td class="muted">{{ $prov['segmento_contable'] }}</td>
                            <td class="muted">{{ $prov['fecha_alta'] ? $prov['fecha_alta']->format('d/m/Y') : '—' }}</td>
                            <td class="muted">{{ $prov['calle'] }}</td>
                            <td class="muted">{{ $prov['ciudad'] }}</td>
                            <td class="muted">{{ $prov['cp'] }}</td>
                            <td class="muted">{{ $prov['colonia'] }}</td>
                            <td>
                                <span class="moneda-pill {{ $prov['moneda'] === 'DÓLAR' ? 'usd' : 'mxn' }}">{{ $prov['moneda'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="10" class="empty-state">
                            @if($busqueda !== '')
                                No se encontraron proveedores para "{{ $busqueda }}".
                            @else
                                No hay proveedores registrados.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
