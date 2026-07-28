@extends('layouts.admin')
@section('title', 'Lote de pago #'.$pago->id)
@section('hero')
<div class="hero-band">
    <h1>Lote de pago #{{ $pago->id }}</h1>
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
    .pag-actions{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px}
    .btn{padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center}
    .btn-primary{background:var(--purple);color:#fff}
    .btn-export{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .btn-danger{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .btn-outline{background:#fff;color:var(--gray-text);border:1.5px solid var(--border)}
    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:var(--shadow-sm)}
    .adm-section-head{padding:14px 18px;background:var(--gray-soft);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
    .adm-section-head h4{margin:0;font-size:14px;font-weight:700}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;padding:16px 18px}
    .stat label{display:block;font-size:11px;color:var(--gray-muted);text-transform:uppercase;font-weight:600}
    .stat strong{font-size:18px}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 14px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:top}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.neut{background:var(--purple-subtle);color:var(--purple)}
    .aviso{color:var(--amber);font-size:11px;display:block}
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

@if(!$expediente['ok'] && $pago->esBorrador())
    <div class="pag-alert err anim">
        Expediente con problemas (no conviene confirmar):
        {{ implode(' · ', $expediente['motivos']) }}
    </div>
@endif

<div class="pag-actions anim">
    <a class="btn btn-export" href="{{ route('admin.pagos.excel', $pago) }}">Descargar Excel (folios)</a>
    @if($pago->esBorrador())
        <form method="POST" action="{{ route('admin.pagos.confirmar', $pago) }}" onsubmit="return confirm('¿Confirmar este lote? Las facturas cambiarán de estatus.');">
            @csrf
            <button type="submit" class="btn btn-primary">Confirmar pago (Administración)</button>
        </form>
        <form method="POST" action="{{ route('admin.pagos.cancelar', $pago) }}" onsubmit="return confirm('¿Cancelar borrador?');">
            @csrf
            <button type="submit" class="btn btn-danger">Cancelar borrador</button>
        </form>
    @endif
    @if($pago->codigo_proveedor)
        <a class="btn btn-outline" href="{{ route('admin.pagos.proveedor', $pago->codigo_proveedor) }}">Más facturas del proveedor</a>
    @endif
</div>

<div class="adm-section anim">
    <div class="adm-section-head">
        <h4>Resumen</h4>
        <span class="pill {{ $pago->estatus === 'confirmado' ? 'ok' : 'warn' }}">{{ $pago->estatus }}</span>
    </div>
    <div class="stats">
        <div class="stat"><label>Facturas</label><strong>{{ $pago->num_facturas }}</strong></div>
        <div class="stat"><label>Subtotal</label><strong>${{ number_format((float)$pago->monto_subtotal, 2) }}</strong></div>
        <div class="stat"><label>IVA</label><strong>${{ number_format((float)$pago->monto_iva, 2) }}</strong></div>
        <div class="stat"><label>Ret. IVA</label><strong>${{ number_format((float)$pago->monto_retencion_iva, 2) }}</strong></div>
        <div class="stat"><label>Ret. ISR</label><strong>${{ number_format((float)$pago->monto_retencion_isr, 2) }}</strong></div>
        <div class="stat"><label>Total CFDI</label><strong>${{ number_format((float)$pago->monto_total, 2) }}</strong></div>
        <div class="stat"><label>Neto estimado</label><strong>${{ number_format((float)$pago->monto_neto, 2) }}</strong></div>
        <div class="stat"><label>Fecha pago</label><strong>{{ $pago->fecha_pago?->format('d/m/Y') ?? '—' }}</strong></div>
    </div>
    @if($pago->notas)
        <p style="padding:0 18px 16px;font-size:13px;color:var(--gray-muted);margin:0;"><strong>Notas:</strong> {{ $pago->notas }}</p>
    @endif
</div>

<div class="adm-section anim">
    <div class="adm-section-head"><h4>Folios del lote</h4></div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>UUID</th>
                    <th>Flete</th>
                    <th>Régimen</th>
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
                        <td>{{ $l->regimen_fiscal ?: '—' }}</td>
                        <td>${{ number_format((float)$l->total, 2) }}</td>
                        <td>IVA ${{ number_format((float)$l->retencion_iva, 2) }} / ISR ${{ number_format((float)$l->retencion_isr, 2) }}</td>
                        <td>${{ number_format((float)$l->neto, 2) }}</td>
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
        </table>
    </div>
</div>
@endsection
