@extends('layouts.admin')
@section('title', 'Historial de abonos')
@section('hero')
<div class="hero-band">
    <h1>Historial de abonos</h1>
    <p>Abonos y anticipos registrados por cuenta</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .doc-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
    .doc-kpi{background:var(--white);border:1.5px solid var(--border);border-radius:12px;padding:16px 18px;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:4px;transition:border-color .15s,box-shadow .15s}
    .doc-kpi:hover{border-color:var(--purple-mid,#c4b5e0)}
    .doc-kpi.active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.12)}
    .doc-kpi .dk-accent{position:absolute;top:0;left:0;width:4px;height:100%}
    .doc-kpi .dk-num{font-size:26px;font-weight:800;color:var(--gray-text);line-height:1}
    .doc-kpi .dk-lbl{font-size:12px;color:var(--gray-muted);font-weight:600}
    @media(max-width:768px){.doc-kpis{grid-template-columns:1fr}}

    .cuenta-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
    .cuenta-tab{display:flex;flex-direction:column;gap:2px;padding:12px 18px;border:1.5px solid var(--border);border-radius:12px;background:var(--white);text-decoration:none;color:inherit;min-width:170px;transition:border-color .15s,box-shadow .15s;position:relative;overflow:hidden}
    .cuenta-tab:hover{border-color:var(--purple-mid,#c4b5e0)}
    .cuenta-tab.active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.12)}
    .cuenta-tab .accent{position:absolute;top:0;left:0;width:4px;height:100%}
    .cuenta-tab .ct-serie{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase}
    .cuenta-tab .ct-nombre{font-size:14px;font-weight:700;color:var(--gray-text)}
    .cuenta-tab .ct-mon{font-size:11px;color:var(--gray-muted)}

    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin-bottom:20px}
    .filter-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:140px;flex:1}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .filter-field input{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--white)}
    .filter-field input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-nuevo{padding:10px 20px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-nuevo:hover{background:#15803d}
    .btn-nuevo .kbd{background:rgba(255,255,255,.25);border-radius:4px;padding:1px 6px;font-size:11px;font-weight:700}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 14px;text-align:left;background:var(--white);border-bottom:1px solid var(--border);white-space:nowrap}
    .admin-table td{padding:12px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pend{font-weight:700;font-variant-numeric:tabular-nums}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted);font-size:14px}
    .prov-chip{display:inline-flex;align-items:center;margin-top:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;color:var(--purple);background:var(--purple-subtle);border:1px solid rgba(107,63,160,.28);line-height:1.3}
    .tipo-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;text-transform:uppercase}
    .tipo-badge.anticipo{background:#fef3c7;color:#92400e}
    .tipo-badge.abono{background:#dbeafe;color:#1e40af}
    .totals-bar{display:flex;gap:24px;padding:14px 22px;background:var(--gray-soft);border-top:2px solid var(--border);font-size:13px;font-weight:700;justify-content:flex-end}
</style>
@endpush
@section('content')

@if(session('ok'))
    <div id="toast-ok" style="position:fixed;top:20px;right:20px;z-index:100000;background:#16a34a;color:#fff;border-radius:12px;padding:16px 22px;font-size:14px;font-weight:600;box-shadow:0 10px 40px rgba(22,163,74,.4);max-width:440px;display:flex;align-items:center;gap:12px">
        <span style="font-size:20px">✓</span>
        <span>{{ session('ok') }}</span>
    </div>
    <div class="anim" style="background:var(--green-bg);color:var(--green);border:1px solid var(--green);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('ok') }}</div>
    <script>setTimeout(function(){var t=document.getElementById('toast-ok');if(t){t.style.transition='opacity .5s';t.style.opacity='0';setTimeout(function(){t.remove();},500);}},5000);</script>
@endif
@if(session('error'))
    <div class="anim" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('error') }}</div>
@endif

{{-- Tabs de las 4 cuentas --}}
<div class="cuenta-tabs anim">
    @foreach($cuentas as $key => $c)
        <a href="{{ route('admin.historial-abonos', ['cuenta' => $key]) }}" class="cuenta-tab {{ $key === $cuentaKey ? 'active' : '' }}">
            <span class="accent" style="background:{{ $c['color'] ?? '#6B3FA0' }}"></span>
            <span class="ct-serie">Serie {{ $c['serie'] }}</span>
            <span class="ct-nombre">{{ $c['titulo'] }}</span>
            <span class="ct-mon">{{ $c['moneda_label'] }}</span>
        </a>
    @endforeach
</div>

{{-- KPIs: Todos / Cancelados / Saldo pendiente --}}
<div class="doc-kpis anim" style="animation-delay:.02s">
    <a href="{{ route('admin.historial-abonos', ['cuenta' => $cuentaKey, 'doc' => 'todos', 'q' => $buscar]) }}" class="doc-kpi {{ $doc === 'todos' ? 'active' : '' }}">
        <span class="dk-accent" style="background:#6B3FA0"></span>
        <span class="dk-num">{{ $kpiTodos }}</span>
        <span class="dk-lbl">0. Todos los documentos</span>
    </a>
    <a href="{{ route('admin.historial-abonos', ['cuenta' => $cuentaKey, 'doc' => 'cancelados', 'q' => $buscar]) }}" class="doc-kpi {{ $doc === 'cancelados' ? 'active' : '' }}">
        <span class="dk-accent" style="background:#dc2626"></span>
        <span class="dk-num">{{ $kpiCancelados }}</span>
        <span class="dk-lbl">1. Documentos cancelados</span>
    </a>
    <a href="{{ route('admin.historial-abonos', ['cuenta' => $cuentaKey, 'doc' => 'pendiente', 'q' => $buscar]) }}" class="doc-kpi {{ $doc === 'pendiente' ? 'active' : '' }}">
        <span class="dk-accent" style="background:#d97706"></span>
        <span class="dk-num">{{ $kpiPendiente }}</span>
        <span class="dk-lbl">2. Documentos con saldo pendiente</span>
    </a>
</div>

{{-- Filtros + botón Nuevo --}}
<div class="filters-panel anim" style="animation-delay:.03s">
    <form method="get" class="filter-form">
        <input type="hidden" name="cuenta" value="{{ $cuentaKey }}">
        <input type="hidden" name="doc" value="{{ $doc }}">
        <div class="filter-field" style="flex:2">
            <label>Buscar</label>
            <input type="text" name="q" value="{{ $buscar }}" placeholder="Folio, referencia, código o proveedor...">
        </div>
        <div class="filter-field">
            <label>Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}">
        </div>
        <div class="filter-field">
            <label>Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}">
        </div>
        <button type="submit" class="btn-primary">Filtrar</button>
        @if($buscar !== '' || request('desde') || request('hasta'))
            <a href="{{ route('admin.historial-abonos', ['cuenta' => $cuentaKey]) }}" style="font-size:12px;color:var(--purple);font-weight:600;text-decoration:none;align-self:center">✕ Limpiar</a>
        @endif
        <a href="{{ route('admin.abono-proveedor', ['cuenta' => $cuentaKey]) }}" class="btn-nuevo" title="Nuevo abono (Ins)">
            + Nuevo <span class="kbd">Ins</span>
        </a>
    </form>
</div>

{{-- Tabla --}}
<div class="adm-section anim" style="animation-delay:.06s">
    <div class="adm-section-head">
        <div>
            <h4>{{ $cuentaConfig['titulo'] }}</h4>
            <div class="adm-section-meta">{{ $totalRegistros }} registro{{ $totalRegistros !== 1 ? 's' : '' }} · abonos y anticipos</div>
        </div>
    </div>

    @if($registros->isEmpty())
        <div class="empty-state">
            No hay abonos ni anticipos registrados en esta cuenta.<br>
            <a href="{{ route('admin.abono-proveedor', ['cuenta' => $cuentaKey]) }}" style="color:var(--purple);font-weight:700;text-decoration:none;margin-top:8px;display:inline-block">+ Registrar el primero</a>
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Serie</th>
                        <th>Folio</th>
                        <th>Razón social</th>
                        <th style="text-align:right">Total</th>
                        <th>Nombre de la Moneda</th>
                        <th style="text-align:right">Pendiente</th>
                        <th>Referencia</th>
                        <th style="text-align:center">Cancelado</th>
                        <th style="text-align:right">Tipo de cambio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registros as $r)
                        @php
                            $fecha = $r['fecha'] instanceof \Illuminate\Support\Carbon ? $r['fecha'] : \Illuminate\Support\Carbon::parse($r['fecha']);
                            $pend = (float) $r['pendiente'];
                            $cancelado = (int) ($r['cancelado'] ?? 0);
                            $tc = $r['tipo_cambio'] ?? '';
                            $monNombre = $r['moneda'] === 'USD' ? 'DÓLAR AMERICANO' : ($r['moneda'] === 'MXN' ? 'PESO MEXICANO' : $r['moneda']);
                        @endphp
                        <tr style="{{ $cancelado ? 'opacity:.55' : '' }}">
                            <td><span class="tipo-badge {{ $r['tipo'] }}">{{ $r['tipo'] }}</span></td>
                            <td style="white-space:nowrap">{{ $fecha->format('d/m/Y') }}</td>
                            <td>{{ $r['serie'] ?: '—' }}</td>
                            <td style="font-weight:700;color:var(--purple)">{{ $r['folio'] ?: '—' }}</td>
                            <td>
                                <div style="font-weight:600">{{ $r['razon'] }}</div>
                                <span class="prov-chip">{{ $r['codigo'] }}</span>
                            </td>
                            <td style="text-align:right" class="monto">${{ number_format((float) $r['total'], 2) }}</td>
                            <td style="white-space:nowrap">{{ $monNombre }}</td>
                            <td class="pend" style="text-align:right;color:{{ $pend > 0 ? '#d97706' : '#16a34a' }}">
                                ${{ number_format($pend, 2) }}
                            </td>
                            <td style="color:var(--gray-muted)">{{ $r['referencia'] ?: '—' }}</td>
                            <td style="text-align:center">
                                @if($cancelado)
                                    <span style="color:#dc2626;font-weight:700">Sí</span>
                                @else
                                    <span style="color:var(--gray-muted)">0</span>
                                @endif
                            </td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums">{{ $tc !== '' ? number_format((float) $tc, 4) : '1.0000' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="totals-bar">
            <span>Total: <span class="monto">${{ number_format($sumaTotal, 2) }}</span></span>
            <span>Pendiente: <span style="color:{{ $sumaPendiente > 0 ? '#d97706' : '#16a34a' }}">${{ number_format($sumaPendiente, 2) }}</span></span>
        </div>
    @endif
</div>

@endsection
@push('scripts')
<script>
// Atajo tecla Insert = Nuevo abono
document.addEventListener('keydown', function(e) {
    if (e.key === 'Insert') {
        e.preventDefault();
        window.location.href = @json(route('admin.abono-proveedor', ['cuenta' => $cuentaKey]));
    }
});
</script>
@endpush
