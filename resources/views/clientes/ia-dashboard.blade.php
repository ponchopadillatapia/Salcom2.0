@extends('layouts.cliente')
@section('title', 'Dashboard IA')
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

    .ia-result{margin-top:20px;padding-top:18px;border-top:1px solid var(--border-light)}
    .ia-result-header{display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap}
    .ia-result-header h4{font-size:14px;font-weight:700;color:var(--gray-text)}
    .ia-result-time{font-size:11px;color:var(--gray-muted);margin-left:auto}
    .ia-response{background:var(--gray-soft);border-radius:10px;padding:16px 18px;font-size:13px;line-height:1.7;color:var(--gray-text);max-height:560px;overflow-y:auto}
    .ia-response h1,.ia-response h2,.ia-response h3{color:var(--purple-dark);margin:16px 0 8px;font-weight:700}
    .ia-response h1{font-size:17px}.ia-response h2{font-size:15px}.ia-response h3{font-size:14px}
    .ia-response p{margin-bottom:10px}
    .ia-response strong{color:var(--gray-text);font-weight:700}
    .ia-response ul,.ia-response ol{padding-left:18px;margin-bottom:10px}
    .ia-response li{margin-bottom:4px}
    .ia-response table{width:100%;border-collapse:collapse;margin:12px 0;font-size:12px}
    .ia-response table th{background:var(--purple-light);color:var(--purple-dark);font-weight:700;padding:8px 10px;text-align:left;border:1px solid var(--border-light)}
    .ia-response table td{padding:8px 10px;border:1px solid var(--border-light)}
    .ia-response blockquote{border-left:3px solid var(--purple);padding-left:14px;margin:10px 0;color:var(--gray-muted);font-style:italic}
    .ia-error{background:var(--red-bg);border:1px solid rgba(255,59,48,0.2);border-radius:10px;padding:14px 16px;font-size:13px;color:var(--red)}
    .data-preview{margin-top:12px}
    .data-preview summary{font-size:12px;font-weight:600;color:var(--gray-muted);cursor:pointer;padding:6px 0}
    .data-preview pre{background:var(--gray-soft);border:1px solid var(--border-light);border-radius:10px;padding:12px 14px;font-size:11px;color:var(--gray-text);overflow-x:auto;max-height:220px;overflow-y:auto}
</style>
@endpush
@section('content')

@php
use App\Models\Alerta;
use Illuminate\Support\Str;
$clienteId = session('cliente_id');
$alertas = $clienteId
    ? Alerta::where('destinatario_tipo', 'cliente')
        ->where('destinatario_id', $clienteId)
        ->orderByDesc('created_at')
        ->limit(10)
        ->get()
    : collect();
$sugerencias = $alertas->where('tipo', 'sugerencia_ia');
$alertasRecientes = $alertas->where('tipo', '!=', 'sugerencia_ia');
@endphp

{{-- Validación de documentos (mes actual) — checklist automático vía IA --}}
<div class="ia-section">
    <h3>📄 Validación de documentos (mes actual)</h3>
    <p style="font-size:13px;color:var(--gray-muted);margin-bottom:16px;line-height:1.5;">
        La IA arma un mensaje de validación con base en tu expediente y el checklist del <strong>mes en curso ({{ now()->format('m/Y') }})</strong>: documentos faltantes, pendientes de revisión o que deben actualizarse según política del portal.
    </p>
    <form method="POST" action="{{ route('clientes.ia.documentacion') }}" style="display:inline;">
        @csrf
        <button type="submit" class="ia-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Generar mensaje de validación
        </button>
    </form>

    @if(isset($resultadoDocumentacion))
    <div class="ia-result">
        <div class="ia-result-header">
            <h4>Mensaje para tu empresa</h4>
            <span class="ia-result-time">{{ $resultadoDocumentacion['generado'] ?? '' }}</span>
        </div>
        @if($resultadoDocumentacion['analisis']['success'] ?? false)
            <div class="ia-response">{!! Str::markdown($resultadoDocumentacion['analisis']['content']) !!}</div>
        @else
            <div class="ia-error">{{ $resultadoDocumentacion['analisis']['error'] ?? 'No se pudo generar el mensaje.' }}</div>
        @endif
        <details class="data-preview">
            <summary>Ver checklist usado por la IA</summary>
            <pre>{{ json_encode($resultadoDocumentacion['checklist'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>
    @endif
</div>

{{-- Alertas recientes --}}
<div class="ia-section">
    <h3>🔔 Alertas recientes</h3>
    @if($alertasRecientes->count())
        <div class="ia-alert-list">
            @foreach($alertasRecientes->take(5) as $alerta)
            <div class="ia-alert-item">
                <div class="ia-alert-icon {{ $alerta->nivel }}">
                    {{ $alerta->nivel === 'critical' ? '🚨' : ($alerta->nivel === 'warning' ? '⚠️' : 'ℹ️') }}
                </div>
                <div class="ia-alert-content">
                    <div class="ia-alert-title">{{ $alerta->titulo }}</div>
                    <div class="ia-alert-desc">{{ Str::limit($alerta->contenido, 150) }}</div>
                    <div class="ia-alert-time">{{ $alerta->created_at->diffForHumans() }}</div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="ia-empty">
            <p> No hay alertas pendientes. Todo está en orden.</p>
        </div>
    @endif
</div>

{{-- Sugerencias de la semana --}}
<div class="ia-section">
    <h3>💡 Sugerencias de la semana</h3>
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
    <h3>🧠 Análisis personalizado</h3>
    <p style="font-size:13px;color:var(--gray-muted);margin-bottom:16px;line-height:1.5;">
        La IA genera análisis automáticos cada semana. Si necesitas algo específico, usa estos botones:
    </p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <form method="POST" action="{{ route('clientes.ia.pronostico') }}" style="display:inline;">
            @csrf
            <button type="submit" class="ia-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Generar pronóstico
            </button>
        </form>
        <form method="POST" action="{{ route('clientes.ia.inventario') }}" style="display:inline;">
            @csrf
            <button type="submit" class="ia-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Analizar disponibilidad
            </button>
        </form>
    </div>

    @if(isset($resultadoPronostico))
    <div class="ia-result">
        <div class="ia-result-header">
            <h4>Pronóstico de demanda</h4>
            <span class="ia-result-time">{{ $resultadoPronostico['generado'] }}</span>
        </div>
        @if($resultadoPronostico['analisis']['success'] ?? false)
            <div class="ia-response">{!! Str::markdown($resultadoPronostico['analisis']['content']) !!}</div>
        @else
            <div class="ia-error">{{ $resultadoPronostico['analisis']['error'] }}</div>
        @endif
        <details class="data-preview">
            <summary>Ver historial de pedidos analizado</summary>
            <pre>{{ json_encode($resultadoPronostico['historial'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>
    @endif

    @if(isset($resultadoInventario))
    <div class="ia-result" @if(isset($resultadoPronostico)) style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border-light)" @endif>
        <div class="ia-result-header">
            <h4>Disponibilidad de productos</h4>
            <span class="ia-result-time">{{ $resultadoInventario['generado'] }}</span>
        </div>
        @if($resultadoInventario['analisis']['success'] ?? false)
            <div class="ia-response">{!! Str::markdown($resultadoInventario['analisis']['content']) !!}</div>
        @else
            <div class="ia-error">{{ $resultadoInventario['analisis']['error'] }}</div>
        @endif
    </div>
    @endif
</div>

@endsection
