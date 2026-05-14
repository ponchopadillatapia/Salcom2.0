@extends('layouts.admin')
@section('title', 'Configuración IA')
@section('hero')
<div class="hero-band">
    <h1>⚙️ Configuración — IA Proactiva</h1>
    <p>Ajusta los umbrales y parámetros del sistema de alertas</p>
</div>
@endsection
@push('styles')
<style>
    .config-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px;margin-bottom:20px}
    .config-card h3{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:16px}
    .config-row{display:grid;grid-template-columns:1fr 120px;gap:16px;align-items:center;padding:12px 0;border-bottom:1px solid var(--border-light)}
    .config-row:last-child{border-bottom:none}
    .config-label{font-size:13px;font-weight:600;color:var(--gray-text)}
    .config-desc{font-size:11px;color:var(--gray-muted);margin-top:2px}
    .config-input{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;width:100%;text-align:center}
    .config-input:focus{border-color:var(--purple);outline:none;box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-save{padding:10px 24px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;margin-top:16px}
    .btn-save:hover{background:var(--purple-dark)}
    .alert-success{background:var(--green-bg);border:1px solid var(--green);border-radius:8px;padding:10px 16px;font-size:13px;color:var(--green);margin-bottom:16px}
</style>
@endpush
@section('content')

@if(session('mensaje'))
<div class="alert-success">{{ session('mensaje') }}</div>
@endif

<form method="POST" action="{{ route('admin.alertas.config.guardar') }}">
    @csrf
    <div class="config-card">
        <h3>Umbrales de alertas</h3>
        @foreach($configs as $config)
        <div class="config-row">
            <div>
                <div class="config-label">{{ $config->descripcion ?? $config->clave }}</div>
                <div class="config-desc">Clave: {{ $config->clave }}</div>
            </div>
            <input type="text" name="configs[{{ $config->clave }}]" value="{{ $config->valor }}" class="config-input">
        </div>
        @endforeach
    </div>
    <button type="submit" class="btn-save">Guardar configuración</button>
</form>

@endsection
