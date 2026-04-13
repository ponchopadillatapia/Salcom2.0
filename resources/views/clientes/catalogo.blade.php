@extends('layouts.cliente')
@section('title', 'Catálogo')
@section('hero')
<div class="hero-band"><h1>Catálogo de Productos</h1><p>Consulta productos disponibles y precios según tu tipo de cliente</p></div>
@endsection
@push('styles')
<style>
    .info-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:32px;text-align:center;max-width:600px;margin:40px auto}
    .info-card h3{font-size:18px;font-weight:700;color:var(--gray-text);margin-bottom:8px}
    .info-card p{font-size:14px;color:var(--gray-muted);line-height:1.6}
    .info-badge{display:inline-block;margin-top:16px;font-size:12px;font-weight:600;padding:4px 14px;border-radius:999px;background:var(--amber-bg);color:var(--amber)}
</style>
@endpush
@section('content')
<div class="info-card">
    <h3>Catálogo pendiente de conexión</h3>
    <p>El catálogo de productos se conectará a la API de Alan cuando esté disponible. Los precios se mostrarán dinámicamente según tu tipo de cliente: <strong>{{ session('cliente_tipo', '—') }}</strong>.</p>
    <span class="info-badge">Pendiente de API</span>
</div>
@endsection
