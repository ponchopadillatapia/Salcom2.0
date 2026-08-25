@extends('layouts.admin')
@section('title', 'Pago #'.$pago->id)
@section('hero')
<div class="hero-band">
    <h1>Pago #{{ $pago->id }}</h1>
    <p>{{ $pago->proveedor?->nombre ?? $pago->codigo_proveedor }} · {{ $pago->estatus }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}
    .pag-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .pag-alert.warn{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber)}
    .pag-actions{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px}
    .btn{padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center}
    .btn-primary{background:var(--purple);color:#fff}
    .btn-export{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .btn-danger{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .btn-outline{background:#fff;color:var(--gray-text);border:1.5px solid var(--border)}
    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:var(--shadow-sm)}
    .adm-section-head{padding:14px 18px;background:var(--gray-soft);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
    .adm-section-head h4{margin:0;font-size:14px;font-weight:700}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;padding:16px 18px}
    .stat label{display:block;font-size:11px;color:var(--gray-muted);text-transform:uppercase;font-weight:600}
    .stat strong{font-size:18px}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 14px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:top}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .admin-table tfoot td{background:var(--gray-soft);font-weight:700;border-bottom:none;border-top:2px solid var(--border);font-size:12px}
    .admin-table .meta-muted{font-weight:600;color:var(--gray-muted);font-size:12px}
    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .aviso{color:var(--amber);font-size:11px;display:block}
    .monto{font-variant-numeric:tabular-nums}
    .confirm-box{padding:18px}
    .form-row{display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;margin-bottom:14px}
    .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}
    .form-field{display:flex;flex-direction:column;gap:4px}
    .form-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .form-field input[type=date],.form-field input[type=file],.form-field input[type=text],.form-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;background:#fff}
    .form-field input[type=file]{min-width:220px}
    .form-field select{min-height:38px}
    .hint{font-size:12px;color:var(--gray-muted);margin:0 0 14px}
    .doc-list{display:flex;flex-wrap:wrap;gap:10px;padding:16px 18px}
    .doc-link{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:var(--white);border:1px solid var(--border);border-radius:8px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none}
    .doc-link:hover{border-color:var(--purple);background:var(--purple-subtle)}
</style>
@endpush
@section('content')
<a class="pag-back anim" href="{{ route('admin.pagos') }}">← Volver a pagos</a>

@if(session('mensaje'))
    <div class="pag-alert ok anim">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
    <div class="pag-alert err anim">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="pag-alert err anim">{{ $errors->first() }}</div>
@endif

@if(!$expediente['ok'] && $pago->esBorrador())
    <div class="pag-alert warn anim">
        Expediente incompleto (aviso, no bloquea):
        {{ implode(' · ', $expediente['motivos']) }}
    </div>
@endif

<div class="pag-actions anim">
    @if($pago->esBorrador())
        <form method="POST" action="{{ route('admin.pagos.cancelar', $pago) }}" onsubmit="return confirm('¿Cancelar este borrador?');">
            @csrf
            <button type="submit" class="btn btn-danger">Cancelar borrador</button>
        </form>
    @else
        <a class="btn btn-primary" href="{{ route('admin.pagos.reporte-resumen', ['pago' => $pago, 'ver' => 1]) }}" target="_blank" rel="noopener">Ver reporte resumen</a>
    @endif
</div>

@php
    $dc = $pago->esBorrador() ? ($datosAuto ?? []) : ($pago->datos_confirmacion ?? []);
@endphp

@if($pago->esBorrador())
<div class="adm-section anim">
    <div class="adm-section-head">
        <div>
            <h4>Confirmar pago</h4>
            <div class="adm-section-meta">Datos fiscales tomados del XML — sin formulario manual</div>
        </div>
    </div>
    <div class="confirm-box">
        @if(!empty($errorDatosAuto))
            <div class="pag-alert err" style="margin:0 0 14px;">{{ $errorDatosAuto }}</div>
        @endif
        <form method="POST" action="{{ route('admin.pagos.confirmar', $pago) }}" enctype="multipart/form-data" onsubmit="return confirm('¿Confirmar? Las facturas pasarán a programada.');">
            @csrf
            <input type="hidden" name="fecha_pago" value="{{ now()->format('Y-m-d') }}">
            <div class="form-grid">
                <div class="form-field">
                    <label>Fecha</label>
                    <input type="date" value="{{ now()->format('Y-m-d') }}" readonly style="background:var(--gray-soft)">
                </div>
                <div class="form-field">
                    <label>Comprobantes (opcional)</label>
                    <input type="file" name="comprobantes[]" accept=".pdf,.jpg,.jpeg,.png,.xml" multiple>
                </div>
            </div>
            <p class="hint" style="margin-top:12px;">Al confirmar, las facturas pasan a estatus «programada».</p>
            <div style="margin-top:14px;">
                <button type="submit" class="btn btn-primary" @disabled(!empty($errorDatosAuto))>Confirmar pago</button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="adm-section anim">
    <div class="adm-section-head">
        <h4>Detalle del pago</h4>
        <span class="pill {{ $pago->estatus === 'confirmado' ? 'ok' : 'warn' }}">{{ $pago->estatus }}</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>UUID</th>
                    <th>Flete</th>
                    <th>Régimen</th>
                    <th>Forma</th>
                    <th>Método</th>
                    <th>Uso CFDI</th>
                    <th>Total</th>
                    <th>Retenciones</th>
                    <th>Neto</th>
                    <th>Avisos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pago->lineas as $l)
                    <tr>
                        <td>{{ $l->folio_cfdi ?: '—' }}</td>
                        <td style="font-size:11px;color:var(--gray-muted);">{{ $l->uuid_cfdi ?: '—' }}</td>
                        <td>{{ $l->es_fletera ? 'Sí' : 'No' }}</td>
                        <td>{{ $l->regimen_fiscal ?: ($dc['regimen'] ?? '—') }}</td>
                        <td>{{ $dc['forma_pago'] ?? '—' }}</td>
                        <td>{{ $dc['metodo_pago'] ?? '—' }}</td>
                        <td>{{ $dc['uso_cfdi'] ?? '—' }}</td>
                        <td class="monto">${{ number_format((float)$l->total, 2) }}</td>
                        <td class="monto">IVA ${{ number_format((float)$l->retencion_iva, 2) }} / ISR ${{ number_format((float)$l->retencion_isr, 2) }}</td>
                        <td class="monto">${{ number_format((float)$l->neto, 2) }}</td>
                        <td>
                            @forelse(($l->avisos ?? []) as $a)
                                <span class="aviso">• {{ $a }}</span>
                            @empty
                                —
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="meta-muted">
                        {{ $pago->num_facturas }} factura{{ $pago->num_facturas === 1 ? '' : 's' }}
                        · Subtotal ${{ number_format((float)$pago->monto_subtotal, 2) }}
                        · IVA ${{ number_format((float)$pago->monto_iva, 2) }}
                        · Fecha {{ $pago->fecha_pago?->format('d/m/Y') ?? '—' }}
                        @if(!empty($dc['producto']))
                            · {{ $dc['producto'] }}
                        @endif
                    </td>
                    <td class="monto">${{ number_format((float)$pago->monto_total, 2) }}</td>
                    <td class="monto">IVA ${{ number_format((float)$pago->monto_retencion_iva, 2) }} / ISR ${{ number_format((float)$pago->monto_retencion_isr, 2) }}</td>
                    <td class="monto">${{ number_format((float)$pago->monto_neto, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if($pago->notas)
        <p style="padding:12px 18px 0;font-size:13px;color:var(--gray-muted);margin:0;"><strong>Notas:</strong> {{ $pago->notas }}</p>
    @endif
    @if(!empty($pago->comprobantes))
        <div class="doc-list">
            @foreach($pago->comprobantes as $i => $path)
                <a class="doc-link" href="{{ asset('storage/'.$path) }}" target="_blank">Documento {{ $i + 1 }}</a>
            @endforeach
        </div>
    @endif
</div>
@endsection
@push('scripts')
@if(request('descargar_reporte'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.location.href = @json(route('admin.pagos.reporte-resumen', $pago));
});
</script>
@endif
@endpush
