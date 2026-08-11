@extends('layouts.admin')
@section('title', 'Abono '.$abono->etiquetaFolio())
@section('hero')
<div class="hero-band">
    <h1>Abono {{ $abono->etiquetaFolio() }}</h1>
    <p>{{ $poliza['titulo'] ?? $abono->concepto }} · {{ $abono->nombre_proveedor }}</p>
</div>
@endsection
@push('styles')
<style>
    .back{display:inline-flex;margin-bottom:12px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .cq-wrap{background:#fff;border:1px solid #d1d5db;border-radius:8px;overflow:hidden}
    .cq-titlebar{background:linear-gradient(180deg,#eef2ff,#e0e7ff);border-bottom:1px solid #c7d2fe;padding:10px 14px;font-size:13px;font-weight:700;color:#312e81;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
    .cq-head{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:16px;border-bottom:1px solid #e5e7eb}
    .kv label{display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:2px}
    .kv div{font-size:13px;font-weight:600;color:#111827}
    .cq-table{width:100%;border-collapse:collapse;font-size:13px}
    .cq-table th{background:#eef2ff;color:#3730a3;font-size:11px;text-transform:uppercase;padding:10px 12px;text-align:left;border-bottom:1px solid #c7d2fe}
    .cq-table td{padding:10px 12px;border-bottom:1px solid #f3f4f6}
    .cq-foot{padding:14px 16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;background:#f9fafb}
    .monto{font-size:20px;font-weight:800;color:#166534}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.bad{background:var(--red-bg);color:var(--red)}
    .btn-cancel{padding:8px 14px;background:#fff;border:1px solid #ef4444;color:#b91c1c;border-radius:8px;font-weight:600;font-size:12px;cursor:pointer}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    @media(max-width:800px){.cq-head{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')
<a class="back" href="{{ route('admin.pago-proveedores') }}">← Volver al listado</a>

@if(session('ok'))
    <div class="pag-alert ok">{{ session('ok') }}</div>
@endif
@if(session('error'))
    <div class="pag-alert err">{{ session('error') }}</div>
@endif

<div class="cq-wrap">
    <div class="cq-titlebar">
        <span>Abono Prov. · {{ $abono->concepto }} · {{ $abono->serie }}-{{ $abono->folio }}</span>
        @if($abono->estatus === 'guardado')
            <span class="pill ok">Guardado</span>
        @elseif($abono->estatus === 'borrador')
            <span class="pill warn">Borrador</span>
        @else
            <span class="pill bad">Cancelado</span>
        @endif
    </div>

    <div class="cq-head">
        <div class="kv"><label>Fecha</label><div>{{ optional($abono->fecha)->format('d/m/Y') }}</div></div>
        <div class="kv"><label>Proveedor</label><div>{{ $abono->codigo_proveedor }} — {{ $abono->nombre_proveedor }}</div></div>
        <div class="kv"><label>Moneda</label><div>{{ $abono->moneda }}</div></div>
        <div class="kv"><label>Tipo de cambio</label><div>{{ number_format((float)$abono->tipo_cambio, 4) }}</div></div>
        <div class="kv"><label>Cuenta bancaria</label><div>{{ $abono->cuenta_bancaria ?: '(Ninguno)' }}</div></div>
        <div class="kv"><label>Póliza</label><div>{{ $poliza['titulo'] ?? $abono->poliza_key }}</div></div>
        <div class="kv" style="grid-column:span 2"><label>Notas</label><div>{{ $abono->notas ?: '—' }}</div></div>
    </div>

    <div style="overflow-x:auto">
        <table class="cq-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Serie</th>
                    <th>Folio</th>
                    <th>Concepto</th>
                    <th>Referencia</th>
                    <th>Pago</th>
                    <th>Sistema origen</th>
                </tr>
            </thead>
            <tbody>
            @foreach($abono->documentos as $d)
                <tr>
                    <td>{{ optional($d->fecha_doc)->format('d/m/Y') }}</td>
                    <td>{{ $d->serie_doc }}</td>
                    <td>{{ $d->folio_doc }}</td>
                    <td>{{ $d->concepto_doc }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $d->referencia }}">{{ $d->referencia ?: '—' }}</td>
                    <td style="font-weight:700">${{ number_format((float)$d->importe_pago, 2) }}</td>
                    <td>{{ $d->sistema_origen }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="cq-foot">
        <div>
            @if($abono->estatus !== 'cancelado')
            <form method="post" action="{{ route('admin.pago-proveedores.cancelar', $abono) }}" onsubmit="return confirm('¿Cancelar este abono?')">
                @csrf
                <button type="submit" class="btn-cancel">Cancelar abono</button>
            </form>
            @endif
        </div>
        <div class="monto">Pago: ${{ number_format((float)$abono->monto_pago, 2) }} {{ $abono->moneda }}</div>
    </div>
</div>
@endsection
