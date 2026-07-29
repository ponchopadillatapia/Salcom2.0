@extends('layouts.admin')
@section('title', 'Pagos — '.$proveedor->nombre)
@section('hero')
<div class="hero-band">
    <h1>{{ $proveedor->nombre }}</h1>
    <p>Facturas pendientes · {{ $codigo }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .pag-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:16px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:100px}
    .adm-summary-pct{font-size:42px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .adm-summary-metrics{flex:1;display:flex;gap:28px;flex-wrap:wrap}
    .adm-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .adm-metric-val{font-size:22px;font-weight:700;color:var(--green)}

    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px;line-height:1.45}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .pag-alert.warn{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber)}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:var(--shadow-sm)}
    .adm-section-head{padding:14px 18px;background:var(--gray-soft);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .adm-section-head h4{margin:0;font-size:14px;font-weight:700;color:var(--gray-text)}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 14px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:top}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .date-row td{background:var(--purple-subtle)!important;font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block;margin:1px}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.neut{background:var(--purple-subtle);color:var(--purple)}
    .aviso{color:var(--amber);font-size:11px;display:block;margin-top:2px}
    .empty{padding:48px 20px;text-align:center;color:var(--gray-muted)}

    .btn-pagar{padding:7px 14px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;white-space:nowrap}
    .btn-pagar:hover{background:#22a34d;color:#fff}
    .btn-docs{padding:6px 12px;background:var(--purple-subtle);color:var(--purple);border:1px solid var(--purple);border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap}
    .btn-docs:hover{background:var(--purple);color:#fff}
    .btn-ver{padding:5px 10px;background:none;color:var(--purple);border:1px solid var(--border);border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
    .btn-ver:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .actions-cell{display:flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end}

    .doc-panel{display:none;background:var(--gray-soft);padding:16px 18px;border-bottom:1px solid var(--border)}
    .doc-panel.open{display:block}
    .doc-panel h5{margin:0 0 10px;font-size:13px;font-weight:700;color:var(--gray-text)}
    .doc-list{display:flex;flex-wrap:wrap;gap:10px}
    .doc-link{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:var(--white);border:1px solid var(--border);border-radius:8px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none}
    .doc-link:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .doc-link.disabled{opacity:.4;pointer-events:none;color:var(--gray-muted)}
    .doc-link svg{flex-shrink:0}
</style>
@endpush
@section('content')
@php
    $monto = $facturas->sum(fn ($f) => (float) $f->total);
@endphp

<a class="pag-back anim" href="{{ route('admin.pagos') }}">← Volver a proveedores</a>

@if(session('error'))
    <div class="pag-alert err anim">{{ session('error') }}</div>
@endif
@if(session('mensaje'))
    <div class="pag-alert ok anim">{{ session('mensaje') }}</div>
@endif

@unless($expediente['ok'])
    <div class="pag-alert warn anim">
        Expediente incompleto (solo aviso): {{ implode(' · ', $expediente['motivos']) }}
    </div>
@endunless

<div class="adm-summary anim">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ $facturas->count() }}</div>
        <div class="adm-summary-label">Facturas pendientes</div>
    </div>
    <div class="adm-summary-metrics">
        <div>
            <div class="adm-metric-label">Monto total</div>
            <div class="adm-metric-val">${{ number_format($monto, 2) }}</div>
        </div>
    </div>
</div>

<div class="adm-section anim">
    <div class="adm-section-head">
        <div>
            <h4>Facturas pendientes</h4>
            <div class="adm-section-meta">{{ $facturas->count() }} resultado{{ $facturas->count() !== 1 ? 's' : '' }} · agrupados por fecha de alta</div>
        </div>
    </div>
    @if($facturas->isEmpty())
        <div class="empty">Sin facturas pendientes para este proveedor.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Flete</th>
                        <th>Total</th>
                        <th>Retenciones</th>
                        <th>Neto</th>
                        <th>Hora alta</th>
                        <th>Avisos</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lastDate = null; @endphp
                    @foreach($facturas as $f)
                        @php $currentDate = $f->created_at ? $f->created_at->format('Y-m-d') : null; @endphp
                        @if($currentDate !== $lastDate)
                            <tr class="date-row">
                                <td colspan="8">{{ $f->created_at ? $f->created_at->locale('es')->isoFormat('DD [de] MMMM YYYY') : 'Sin fecha' }}</td>
                            </tr>
                            @php $lastDate = $currentDate; @endphp
                        @endif
                        <tr>
                            <td>
                                <strong style="color:var(--purple);">{{ $f->folio_cfdi ?: '—' }}</strong><br>
                                <span style="font-size:10px;color:var(--gray-muted);">{{ $f->uuid_cfdi ? \Illuminate\Support\Str::limit($f->uuid_cfdi, 20) : '—' }}</span>
                            </td>
                            <td>
                                @if($f->es_fletera)
                                    <span class="pill warn">Flete</span>
                                @else
                                    <span class="pill neut">No</span>
                                @endif
                            </td>
                            <td class="monto">${{ number_format((float)$f->total, 2) }}</td>
                            <td style="font-size:12px;">
                                IVA ${{ number_format((float)($f->retencion_iva ?? 0), 2) }}<br>
                                ISR ${{ number_format((float)($f->retencion_isr ?? 0), 2) }}
                            </td>
                            <td class="monto">${{ number_format((float)$f->neto_pago, 2) }}</td>
                            <td style="font-size:12px;color:var(--gray-muted);">{{ $f->created_at?->format('H:i') ?? '—' }}</td>
                            <td>
                                @forelse($f->avisos_pago as $a)
                                    <span class="aviso">• {{ $a }}</span>
                                @empty
                                    <span class="pill ok">OK</span>
                                @endforelse
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button type="button" class="btn-ver" onclick="toggleDocs({{ $f->id }})">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        Ver docs
                                    </button>
                                    <form method="POST" action="{{ route('admin.pagos.store') }}" style="display:inline;" onsubmit="return confirm('¿Crear pago con esta factura?');">
                                        @csrf
                                        <input type="hidden" name="codigo_proveedor" value="{{ $codigo }}">
                                        <input type="hidden" name="factura_ids[]" value="{{ $f->id }}">
                                        <button type="submit" class="btn-pagar">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                            Pagar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" style="padding:0;border:none;">
                                <div class="doc-panel" id="docs-{{ $f->id }}">
                                    <h5>Documentos adjuntos — {{ $f->folio_cfdi }}</h5>
                                    <div class="doc-list">
                                        @if($f->archivo_pdf)
                                            <a class="doc-link" href="{{ asset('storage/'.$f->archivo_pdf) }}" target="_blank">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                PDF
                                            </a>
                                        @else
                                            <span class="doc-link disabled">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                PDF (no adjunto)
                                            </span>
                                        @endif
                                        @if($f->archivo_xml)
                                            <a class="doc-link" href="{{ asset('storage/'.$f->archivo_xml) }}" target="_blank">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                                XML
                                            </a>
                                        @else
                                            <span class="doc-link disabled">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                                XML (no adjunto)
                                            </span>
                                        @endif
                                        @if($f->archivo_oc)
                                            <a class="doc-link" href="{{ asset('storage/'.$f->archivo_oc) }}" target="_blank">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="18" rx="2" ry="2"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                                                Orden de compra
                                            </a>
                                        @else
                                            <span class="doc-link disabled">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="18" rx="2" ry="2"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                                                OC (no adjunta)
                                            </span>
                                        @endif
                                        <a class="btn-docs" href="#">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                            Subir documentos
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
<script>
function toggleDocs(id) {
    var el = document.getElementById('docs-' + id);
    if (el) el.classList.toggle('open');
}
</script>
@endsection
