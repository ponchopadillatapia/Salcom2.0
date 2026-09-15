@extends('layouts.admin')
@section('title', 'Expediente de Pago')
@section('hero')
<div class="hero-band">
    <h1>Expediente de Pago #{{ $pago->id }}</h1>
    <p>{{ $pago->proveedor->nombre ?? $pago->codigo_proveedor }} · {{ $pago->num_facturas }} factura(s) · ${{ number_format((float) $pago->monto_total, 2) }}</p>
</div>
@endsection

@push('styles')
<style>
    .exp-wrap{max-width:1000px}
    .exp-back{display:inline-flex;align-items:center;gap:6px;color:var(--purple);font-weight:600;font-size:13px;text-decoration:none;margin-bottom:16px}
    .exp-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px}
    .exp-estado{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:700;padding:6px 16px;border-radius:999px;text-transform:uppercase;letter-spacing:.3px}
    .exp-estado.pendiente{background:#fffbeb;color:#d97706}
    .exp-estado.autorizado{background:#ecfdf5;color:#059669}
    .exp-estado.rechazado{background:#fef2f2;color:#dc2626}
    .exp-estado .dot{width:8px;height:8px;border-radius:50%;background:currentColor}

    /* Grupo = sección tipo carpeta */
    .exp-grupo{margin-bottom:26px}
    .exp-grupo-head{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--gray-text);margin-bottom:12px;text-transform:uppercase;letter-spacing:.4px}
    .exp-grupo-head svg{color:var(--purple)}
    .exp-grupo-count{font-weight:600;font-size:11px;color:var(--gray-muted);background:var(--purple-subtle,#f3e8ff);padding:2px 9px;border-radius:999px;color:var(--purple)}

    /* Grid de tarjetas de documento */
    .exp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
    .doc-card{position:relative;display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px;
        background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px 14px 16px;
        text-decoration:none;transition:transform .15s,box-shadow .15s,border-color .15s;box-shadow:var(--shadow-sm)}
    .doc-card:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(107,63,160,.12);border-color:var(--purple-mid,#c4b5fd)}
    .doc-card .file-ico{width:46px;height:46px;color:var(--purple)}
    .doc-card .file-name{font-size:12.5px;font-weight:600;color:var(--gray-text);line-height:1.3;word-break:break-word}
    .doc-card .file-ext{font-size:10px;font-weight:700;color:var(--purple);background:var(--purple-subtle,#f3e8ff);padding:2px 8px;border-radius:999px;text-transform:uppercase}
    /* Documento faltante (no cargado aún) */
    .doc-card.falta{border-style:dashed;background:var(--gray-soft,#fafafa);box-shadow:none;cursor:default}
    .doc-card.falta:hover{transform:none;box-shadow:none;border-color:var(--border)}
    .doc-card.falta .file-ico{color:#d97706}
    .doc-card.falta .file-name{color:var(--gray-muted)}
    .doc-card .quitar{position:absolute;top:8px;right:8px;background:none;border:none;color:var(--gray-muted);cursor:pointer;padding:2px;border-radius:6px;line-height:0}
    .doc-card .quitar:hover{color:var(--red);background:#fef2f2}

    /* Tarjeta "adjuntar" */
    .doc-card-add{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;min-height:130px;
        border:2px dashed var(--purple-mid,#c4b5fd);border-radius:14px;background:transparent;color:var(--purple);
        font-size:12.5px;font-weight:600;cursor:pointer}
    .doc-card-add:hover{background:var(--purple-subtle,#f3e8ff)}

    /* Modal adjuntar */
    .adj-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center}
    .adj-modal.open{display:flex}
    .adj-box{background:var(--white);border-radius:14px;padding:22px;width:min(420px,90vw);box-shadow:0 20px 50px rgba(0,0,0,.25)}
    .adj-box h3{margin:0 0 14px;font-size:15px;color:var(--gray-text)}
    .adj-box label{display:block;font-size:12px;font-weight:600;color:var(--gray-text);margin:10px 0 5px}
    .adj-box input{width:100%;border:1px solid var(--border);border-radius:8px;padding:9px;font-size:13px;font-family:inherit}
    .adj-box .adj-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:16px}

    .btn{padding:9px 18px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .btn-purple{background:var(--purple);color:#fff}
    .btn-ghost{background:var(--gray-soft,#f3f4f6);color:var(--gray-text)}
    .btn-green{background:var(--green);color:#fff}
    .btn-red{background:var(--red);color:#fff}

    /* Autorización */
    .exp-auth{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;box-shadow:var(--shadow-sm);margin-top:8px}
    .exp-auth h3{font-size:14px;font-weight:700;color:var(--gray-text);margin:0 0 12px}
    .exp-auth textarea{width:100%;border:1px solid var(--border);border-radius:8px;padding:10px;font-size:13px;font-family:inherit;margin-bottom:12px}
    .exp-auth-actions{display:flex;gap:10px;flex-wrap:wrap}
    .exp-firma{border-radius:10px;padding:14px 16px;font-size:13px;line-height:1.5}
    .exp-firma.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
    .exp-firma.rej{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
</style>
@endpush

@section('content')
@php
    // Icono de archivo (SVG) reutilizable.
    $fileIcon = '<svg class="file-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
    $estAut = $pago->estatus_autorizacion ?? 'pendiente';
    $estLabel = ['pendiente' => 'Pendiente de autorizar', 'autorizado' => 'Autorizado', 'rechazado' => 'Rechazado'][$estAut] ?? $estAut;
@endphp

<div class="exp-wrap">
    <a href="{{ route('admin.pagos.show', $pago) }}" class="exp-back">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Volver al pago
    </a>

    @if(session('mensaje'))<div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('mensaje') }}</div>@endif
    @if(session('error'))<div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('error') }}</div>@endif

    <div class="exp-top">
        <div style="font-size:13px;color:var(--gray-muted);">
            Fecha de pago: {{ $pago->fecha_pago?->format('d/m/Y') ?? '—' }}
        </div>
        <span class="exp-estado {{ $estAut }}"><span class="dot"></span>{{ $estLabel }}</span>
    </div>

    {{-- ── Documentos del expediente (tarjetas tipo carpeta) ── --}}
    @foreach($grupos as $grupo)
        @php $esAdjuntos = str_contains($grupo['grupo'], 'Adjuntos'); @endphp
        <div class="exp-grupo">
            <div class="exp-grupo-head">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                {{ $grupo['grupo'] }}
                <span class="exp-grupo-count">{{ count($grupo['items']) }}</span>
            </div>

            <div class="exp-grid">
                @foreach($grupo['items'] as $item)
                    @php
                        $ok = ! empty($item['ok']);
                        $ext = $item['archivo'] ?? '' ? strtoupper(pathinfo($item['archivo'], PATHINFO_EXTENSION) ?: 'PDF') : 'PDF';
                    @endphp
                    @if($ok && !empty($item['url']))
                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="doc-card">
                            {!! $fileIcon !!}
                            <span class="file-name">{{ $item['label'] }}</span>
                            <span class="file-ext">{{ $ext }}</span>
                            @if(($item['origen'] ?? '') === 'adjunto' && isset($item['indice']))
                                <button type="button" class="quitar" title="Quitar"
                                    onclick="event.preventDefault();document.getElementById('del-{{ $item['indice'] }}').submit();">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            @endif
                        </a>
                        @if(($item['origen'] ?? '') === 'adjunto' && isset($item['indice']))
                            <form method="POST" id="del-{{ $item['indice'] }}" action="{{ route('admin.pagos.expediente.adjunto.eliminar', ['pago' => $pago, 'indice' => $item['indice']]) }}" onsubmit="return confirm('¿Quitar este documento del expediente?')" style="display:none">
                                @csrf @method('DELETE')
                            </form>
                        @endif
                    @else
                        <div class="doc-card falta">
                            {!! $fileIcon !!}
                            <span class="file-name">{{ $item['label'] }}</span>
                            <span class="file-ext" style="background:#fef3c7;color:#d97706;">Falta</span>
                        </div>
                    @endif
                @endforeach

                {{-- Tarjeta para adjuntar (solo en la sección de adjuntos) --}}
                @if($esAdjuntos)
                    <button type="button" class="doc-card-add" onclick="document.getElementById('adjModal').classList.add('open')">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Adjuntar documento
                    </button>
                @endif

                @if(count($grupo['items']) === 0 && !$esAdjuntos)
                    <div style="grid-column:1/-1;color:var(--gray-muted);font-size:12px;padding:6px 0;">Sin documentos en esta sección.</div>
                @endif
            </div>
        </div>
    @endforeach

    {{-- ── Autorización (firma de Sandra / Karen) ── --}}
    <div class="exp-auth">
        <h3>Autorización</h3>
        @if($pago->estaAutorizado())
            <div class="exp-firma ok">
                Autorizado por <strong>{{ $pago->autorizado_por_nombre ?? 'Admin' }}</strong>
                el {{ $pago->autorizado_at?->format('d/m/Y H:i') }}
                @if($pago->notas_autorizacion)<br><span style="font-size:12px;">Notas: {{ $pago->notas_autorizacion }}</span>@endif
            </div>
        @elseif($pago->autorizacionRechazada())
            <div class="exp-firma rej">
                Rechazado por <strong>{{ $pago->autorizado_por_nombre ?? 'Admin' }}</strong>
                el {{ $pago->autorizado_at?->format('d/m/Y H:i') }}
                @if($pago->notas_autorizacion)<br><span style="font-size:12px;">Motivo: {{ $pago->notas_autorizacion }}</span>@endif
            </div>
        @else
            <p style="font-size:12px;color:var(--gray-muted);margin:0 0 10px;">Revisa los documentos del expediente y autoriza o rechaza el pago. Al autorizar, el sistema genera un <strong>comprobante de autorización sellado</strong> automáticamente (no necesitas subir ningún PDF).</p>
            <form method="POST" action="{{ route('admin.pagos.expediente.autorizar', $pago) }}">
                @csrf
                <textarea name="notas" rows="2" placeholder="Notas (opcional)"></textarea>
                <div class="exp-auth-actions">
                    <button type="submit" class="btn btn-green" onclick="return confirm('¿Autorizar este pago?')">Autorizar pago</button>
                    <button type="submit" class="btn btn-red" formaction="{{ route('admin.pagos.expediente.rechazar', $pago) }}" onclick="return confirm('¿Rechazar este expediente?')">Rechazar</button>
                </div>
            </form>
        @endif
    </div>
</div>

{{-- Modal adjuntar documento --}}
<div class="adj-modal" id="adjModal">
    <div class="adj-box">
        <h3>Adjuntar documento al expediente</h3>
        <form method="POST" action="{{ route('admin.pagos.expediente.adjuntar', $pago) }}" enctype="multipart/form-data">
            @csrf
            <label>Tipo de documento</label>
            <input type="text" name="tipo" placeholder="Ej. Póliza Contpaqi, Hoja de autorización..." required>
            <label>Archivo (PDF, imagen o XML)</label>
            <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.xml" required>
            <div class="adj-actions">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('adjModal').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-purple">Adjuntar</button>
            </div>
        </form>
    </div>
</div>
@endsection
