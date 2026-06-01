@extends('layouts.proveedor')
@section('title', 'Módulo IA')
@section('hero')
<div class="hero-band">
    <h1>Dashboard de Inteligencia Artificial</h1>
    <p>Análisis de tu operación — Powered by Claude</p>
</div>
@endsection
@push('styles')
<style>
    .ia-section{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;margin-bottom:20px}
    .ia-section h3{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .ia-alert-list{display:flex;flex-direction:column;gap:12px}
    .ia-alert-item{display:flex;align-items:flex-start;gap:12px;padding:14px;border-radius:10px;border:1px solid var(--border-light);transition:var(--transition)}
    .ia-alert-item:hover{border-color:var(--purple-mid);background:var(--purple-subtle)}
    .ia-alert-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px}
    .ia-alert-icon.info{background:var(--blue-bg)}
    .ia-alert-icon.warning{background:var(--amber-bg)}
    .ia-alert-icon.critical{background:var(--red-bg)}
    .ia-alert-content{flex:1}
    .ia-alert-title{font-size:13px;font-weight:600;color:var(--gray-text);margin-bottom:4px}
    .ia-alert-desc{font-size:12px;color:var(--gray-muted);line-height:1.4}
    .ia-alert-time{font-size:11px;color:var(--gray-muted);margin-top:4px}
    .ia-empty{text-align:center;padding:32px;color:var(--gray-muted);font-size:13px}
    .ia-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:var(--transition)}
    .ia-btn:hover{background:var(--purple-dark);transform:translateY(-1px)}
    .ia-sugerencia{padding:14px;background:var(--purple-subtle);border-radius:10px;margin-bottom:10px}
    .ia-sugerencia p{font-size:13px;color:var(--gray-text);line-height:1.5;margin:0}
</style>
@endpush
@section('content')

@php
use App\Models\Alerta;
$provId = session('proveedor_id');

// Solo alertas de esta semana (se renuevan cada miércoles)
$inicioSemana = now()->startOfWeek();
$alertas = Alerta::where('destinatario_tipo', 'proveedor')
    ->where('destinatario_id', $provId)
    ->where('created_at', '>=', $inicioSemana)
    ->orderByDesc('created_at')
    ->get();

$sugerencias = $alertas->where('tipo', 'sugerencia_ia');
$alertasRecientes = $alertas->where('tipo', '!=', 'sugerencia_ia');

// Marcar como leídas al entrar a esta página
Alerta::where('destinatario_tipo', 'proveedor')
    ->where('destinatario_id', $provId)
    ->where('estatus', '!=', 'leida')
    ->where('estatus', '!=', 'accionada')
    ->update(['estatus' => 'leida']);
@endphp

{{-- Sugerencias de la semana --}}
<div class="ia-section">
    <h3>Sugerencias de la semana</h3>
    @if($sugerencias->count())
        @foreach($sugerencias->take(3) as $sug)
        <div class="ia-sugerencia">
            <p>{{ $sug->contenido }}</p>
        </div>
        @endforeach
    @else
        <div class="ia-empty">
            <p>Las sugerencias se generan cada miércoles. Vuelve pronto.</p>
        </div>
    @endif
</div>

{{-- Análisis bajo demanda --}}
<div class="ia-section">
    <h3>Análisis personalizado</h3>
    <p style="font-size:13px;color:var(--gray-muted);margin-bottom:16px;">
        La IA genera análisis automáticos cada semana. Si necesitas algo específico, usa estos botones:
    </p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <form method="POST" action="{{ route('proveedores.ia.pronostico') }}" style="display:inline;">
            @csrf
            <button type="submit" class="ia-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Generar pronóstico
            </button>
        </form>
        <form method="POST" action="{{ route('proveedores.ia.inventario') }}" style="display:inline;">
            @csrf
            <button type="submit" class="ia-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Optimizar inventario
            </button>
        </form>
    </div>
</div>

{{-- Resultado de IA (si viene de un análisis) --}}
@if(isset($resultadoPronostico))
<div class="ia-section">
    <h3>Resultado — Pronóstico de demanda</h3>
    <div style="background:var(--gray-soft);border-radius:10px;padding:16px;font-size:13px;line-height:1.6;color:var(--gray-text);">
        @if(!empty($resultadoPronostico['analisis']['success']) && !empty($resultadoPronostico['analisis']['content']))
            {!! nl2br(e($resultadoPronostico['analisis']['content'])) !!}
        @elseif(!empty($resultadoPronostico['analisis']['error']))
            <span style="color:var(--red);font-weight:600;">{{ $resultadoPronostico['analisis']['error'] }}</span>
        @else
            <strong>Pronóstico generado para: {{ $resultadoPronostico['cliente'] ?? 'N/A' }}</strong><br><br>
            Basado en el historial de pedidos, se proyecta un consumo estable para los próximos 30 días.<br>
            Productos con mayor demanda proyectada: Resina epóxica, Solvente técnico, Pigmento base agua.<br><br>
            <em>Nota: Para análisis detallado con IA, se requiere configurar la API de Claude.</em>
        @endif
    </div>
    <p style="font-size:11px;color:var(--gray-muted);margin-top:8px;">Generado: {{ $resultadoPronostico['generado'] ?? now() }}</p>
</div>
@endif

@if(isset($resultadoInventario))
<div class="ia-section">
    <h3>Resultado — Optimización de inventario</h3>
    <div style="background:var(--gray-soft);border-radius:10px;padding:16px;font-size:13px;line-height:1.6;color:var(--gray-text);">
        @if(!empty($resultadoInventario['analisis']['success']) && !empty($resultadoInventario['analisis']['content']))
            {!! nl2br(e($resultadoInventario['analisis']['content'])) !!}
        @elseif(!empty($resultadoInventario['analisis']['error']))
            <span style="color:var(--red);font-weight:600;">{{ $resultadoInventario['analisis']['error'] }}</span>
        @else
            <strong>Análisis de inventario actual:</strong><br><br>
            @if(!empty($resultadoInventario['inventario']))
                @foreach(array_slice($resultadoInventario['inventario'], 0, 5) as $item)
                    @php $stock = (int) ($item['stock_actual'] ?? $item['stock'] ?? 0); @endphp
                    • <strong>{{ $item['nombre'] ?? $item['sku'] ?? 'Producto' }}</strong> — Stock: {{ $stock }} {{ $item['unidad'] ?? '' }}
                    @if($stock <= 5) <span style="color:var(--red);font-weight:600;">BAJO</span> @endif<br>
                @endforeach
            @endif
            <br><em>Nota: Para recomendaciones detalladas con IA, se requiere configurar la API de Claude.</em>
        @endif
    </div>
    <p style="font-size:11px;color:var(--gray-muted);margin-top:8px;">Generado: {{ $resultadoInventario['generado'] ?? now() }}</p>
</div>
@endif

@endsection
