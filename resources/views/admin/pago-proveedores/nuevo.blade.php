@extends('layouts.admin')
@section('title', 'Nuevo pago — Elegir póliza')
@section('hero')
<div class="hero-band">
    <h1>Nuevo pago</h1>
    <p>Selecciona la póliza con la que vas a pagar</p>
</div>
@endsection
@push('styles')
<style>
    .pol-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;max-width:980px}
    .pol-card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:22px;text-decoration:none;color:inherit;display:block;transition:border-color .15s,box-shadow .15s,transform .15s}
    .pol-card:hover{border-color:var(--purple);box-shadow:0 8px 24px rgba(107,63,160,.12);transform:translateY(-2px)}
    .pol-serie{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;padding:4px 10px;border-radius:999px;background:var(--purple-subtle);color:var(--purple)}
    .pol-card h2{margin:14px 0 8px;font-size:18px;font-weight:800;color:var(--gray-text)}
    .pol-card p{margin:0 0 14px;font-size:13px;color:var(--gray-muted);line-height:1.45}
    .pol-meta{display:flex;flex-wrap:wrap;gap:8px}
    .pol-chip{font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;background:var(--gray-soft);color:var(--gray-text)}
    .back{display:inline-flex;margin-bottom:16px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    @media(max-width:720px){.pol-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<a class="back" href="{{ route('admin.pago-proveedores') }}">← Volver al listado</a>

<div class="pol-grid">
@foreach($polizas as $key => $p)
    <a class="pol-card" href="{{ route('admin.pago-proveedores.create', $key) }}">
        <span class="pol-serie" style="background:{{ $p['color'] }}22;color:{{ $p['color'] }}">Serie {{ $p['serie'] }}</span>
        <h2>{{ $p['titulo'] }}</h2>
        <p>{{ $p['descripcion'] }}</p>
        <div class="pol-meta">
            <span class="pol-chip">Concepto: {{ $p['concepto'] }}</span>
            <span class="pol-chip">{{ $p['moneda_label'] }} ({{ $p['moneda'] }})</span>
        </div>
    </a>
@endforeach
</div>
@endsection
