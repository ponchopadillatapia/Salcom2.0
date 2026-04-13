@extends('layouts.cliente')
@section('title', 'Dashboard')
@section('hero')
<div class="hero-band"><h1>Dashboard</h1><p>{{ session('cliente_nombre', 'Cliente') }} — {{ now()->format('d/m/Y') }}</p></div>
@endsection
@push('styles')
<style>
    .metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
    .metric{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:20px}
    .metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:6px}.metric-val{font-size:24px;font-weight:700;color:var(--gray-text)}.metric-sub{font-size:11px;color:#9ca3af;margin-top:4px}
    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:16px}
    .card-head{padding:14px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--gray-text)}
    .card-body{padding:20px}.empty{text-align:center;color:#9ca3af;padding:32px;font-size:13px}
</style>
@endpush
@section('content')
<div class="metrics">
    <div class="metric"><div class="metric-label">Pedidos activos</div><div class="metric-val">—</div><div class="metric-sub">Pendiente de API</div></div>
    <div class="metric"><div class="metric-label">Facturas CFDI</div><div class="metric-val">—</div><div class="metric-sub">Pendiente de API</div></div>
    <div class="metric"><div class="metric-label">Estado de cuenta</div><div class="metric-val">Contado</div><div class="metric-sub">Sin crédito autorizado</div></div>
</div>
<div class="card"><div class="card-head">Pedidos recientes</div><div class="card-body"><div class="empty">Pendiente de API — Los pedidos se mostrarán cuando se conecte la API de Alan</div></div></div>
<div class="card"><div class="card-head">Facturas CFDI</div><div class="card-body"><div class="empty">Pendiente de API — Las facturas CFDI se generan automáticamente desde el ERP</div></div></div>
@endsection
