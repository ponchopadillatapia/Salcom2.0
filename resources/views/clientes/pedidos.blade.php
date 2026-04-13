@extends('layouts.cliente')
@section('title', 'Mis Pedidos')
@section('hero')
<div class="hero-band"><h1>Mis Pedidos</h1><p>Consulta el estatus de tus pedidos y seguimiento de entregas</p></div>
@endsection
@push('styles')
<style>
    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .tabla{width:100%;border-collapse:collapse}
    .tabla th{font-size:12px;font-weight:600;color:var(--gray-muted);padding:14px 20px;text-align:left;border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:0.5px}
    .tabla td{padding:14px 20px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .empty-row td{text-align:center;color:#9ca3af;padding:40px 20px}
    .badge-api{font-size:11px;color:var(--amber);font-weight:600;background:var(--amber-bg);padding:3px 10px;border-radius:999px;display:inline-block;margin-bottom:16px}
</style>
@endpush
@section('content')
<span class="badge-api">⚠ Pendiente de API</span>
<div class="card">
    <table class="tabla">
        <thead><tr><th>Folio</th><th>Fecha</th><th>Productos</th><th>Total</th><th>Estatus</th></tr></thead>
        <tbody><tr class="empty-row"><td colspan="5">Los pedidos se mostrarán cuando se conecte la API de Alan</td></tr></tbody>
    </table>
</div>
@endsection
