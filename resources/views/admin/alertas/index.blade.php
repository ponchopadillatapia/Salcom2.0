@extends('layouts.admin')
@section('title', 'Alertas IA')
@section('hero')
<div class="hero-band">
    <h1>🔔 Centro de Alertas — IA Proactiva</h1>
    <p>Todas las alertas generadas por el sistema de inteligencia artificial</p>
</div>
@endsection
@push('styles')
<style>
    .alert-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .alert-stat{background:var(--white);border:1px solid var(--border-light);border-radius:12px;padding:18px;text-align:center}
    .alert-stat-val{font-size:28px;font-weight:700;color:var(--gray-text)}
    .alert-stat-label{font-size:12px;color:var(--gray-muted);margin-top:4px}
    .alert-filters{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
    .alert-filters select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:12px;font-family:inherit}
    .alert-card{background:var(--white);border:1px solid var(--border-light);border-radius:12px;overflow:hidden}
    .alert-table{width:100%;border-collapse:collapse}
    .alert-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;padding:10px 14px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .alert-table td{padding:12px 14px;font-size:12px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .alert-table tr:hover td{background:var(--purple-subtle)}
    .badge-nivel{font-size:10px;font-weight:600;padding:3px 8px;border-radius:999px}
    .badge-nivel.info{background:var(--blue-bg);color:var(--blue)}
    .badge-nivel.warning{background:var(--amber-bg);color:var(--amber)}
    .badge-nivel.critical{background:var(--red-bg);color:var(--red)}
    .badge-estatus{font-size:10px;font-weight:600;padding:3px 8px;border-radius:999px}
    .badge-estatus.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.enviada{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.leida{background:var(--green-bg);color:var(--green)}
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    @media(max-width:768px){.alert-stats{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')

<div class="alert-stats">
    <div class="alert-stat">
        <div class="alert-stat-val">{{ $stats['total'] }}</div>
        <div class="alert-stat-label">Total alertas</div>
    </div>
    <div class="alert-stat">
        <div class="alert-stat-val" style="color:var(--amber)">{{ $stats['pendientes'] }}</div>
        <div class="alert-stat-label">Pendientes</div>
    </div>
    <div class="alert-stat">
        <div class="alert-stat-val" style="color:var(--red)">{{ $stats['criticas'] }}</div>
        <div class="alert-stat-label">Críticas</div>
    </div>
    <div class="alert-stat">
        <div class="alert-stat-val" style="color:var(--green)">{{ $stats['hoy'] }}</div>
        <div class="alert-stat-label">Hoy</div>
    </div>
</div>

<form method="GET" class="alert-filters">
    <select name="tipo" onchange="this.form.submit()">
        <option value="">Todos los tipos</option>
        @foreach($tipos as $t)
        <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>{{ $t }}</option>
        @endforeach
    </select>
    <select name="nivel" onchange="this.form.submit()">
        <option value="">Todos los niveles</option>
        <option value="info" {{ request('nivel') === 'info' ? 'selected' : '' }}>Info</option>
        <option value="warning" {{ request('nivel') === 'warning' ? 'selected' : '' }}>Warning</option>
        <option value="critical" {{ request('nivel') === 'critical' ? 'selected' : '' }}>Critical</option>
    </select>
    <select name="estatus" onchange="this.form.submit()">
        <option value="">Todos los estatus</option>
        <option value="pendiente" {{ request('estatus') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
        <option value="enviada" {{ request('estatus') === 'enviada' ? 'selected' : '' }}>Enviada</option>
        <option value="leida" {{ request('estatus') === 'leida' ? 'selected' : '' }}>Leída</option>
    </select>
</form>

<div class="alert-card">
    <table class="alert-table">
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Tipo</th>
                <th>Título</th>
                <th>Destinatario</th>
                <th>Canal</th>
                <th>Estatus</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alertas as $a)
            <tr>
                <td><span class="badge-nivel {{ $a->nivel }}">{{ $a->nivel }}</span></td>
                <td style="font-size:11px;color:var(--gray-muted)">{{ $a->tipo }}</td>
                <td style="font-weight:600;max-width:300px;">{{ Str::limit($a->titulo, 60) }}</td>
                <td style="font-size:11px;">{{ $a->destinatario_tipo }} #{{ $a->destinatario_id }}</td>
                <td style="font-size:11px;">{{ $a->canal_enviado ?? '—' }}</td>
                <td><span class="badge-estatus {{ $a->estatus }}">{{ $a->estatus }}</span></td>
                <td style="font-size:11px;color:var(--gray-muted)">{{ $a->created_at->format('d/m H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-muted)">No hay alertas</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($alertas->hasPages())
    <div class="pagination-wrap">{{ $alertas->appends(request()->query())->links() }}</div>
    @endif
</div>

@endsection
