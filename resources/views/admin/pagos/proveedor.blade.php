@extends('layouts.admin')
@section('title', 'Armar pago — '.$proveedor->nombre)
@section('hero')
<div class="hero-band">
    <h1>Armar pago</h1>
    <p>{{ $proveedor->nombre }} · {{ $codigo }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}
    .pag-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px;line-height:1.45}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .pag-alert.warn{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber)}
    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:var(--shadow-sm)}
    .adm-section-head{padding:14px 18px;background:var(--gray-soft);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .adm-section-head h4{margin:0;font-size:14px;font-weight:700;color:var(--gray-text)}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 14px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:top}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block;margin:1px}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.bad{background:var(--red-bg);color:var(--red)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.neut{background:var(--purple-subtle);color:var(--purple)}
    .form-row{display:flex;flex-wrap:wrap;gap:12px;padding:16px 18px;align-items:flex-end}
    .form-field{display:flex;flex-direction:column;gap:4px}
    .form-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .form-field input,.form-field textarea{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit}
    .btn-primary{padding:10px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
    .btn-primary:disabled{opacity:.45;cursor:not-allowed}
    .aviso{color:var(--amber);font-size:11px;display:block;margin-top:2px}
    .empty{padding:48px 20px;text-align:center;color:var(--gray-muted)}
</style>
@endpush
@section('content')
<a class="pag-back anim" href="{{ route('admin.pagos') }}">← Volver a pagos</a>

@if(session('error'))
    <div class="pag-alert err anim">{{ session('error') }}</div>
@endif

@if($expediente['ok'])
    <div class="pag-alert ok anim">Expediente fiscal OK — se puede armar el lote.</div>
@else
    <div class="pag-alert err anim">
        <strong>Bloqueado:</strong> no se puede crear el pago hasta completar/aprobar expediente.
        <ul style="margin:8px 0 0;padding-left:18px;">
            @foreach($expediente['motivos'] as $m)
                <li>{{ $m }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.pagos.store') }}" id="formPago">
    @csrf
    <input type="hidden" name="codigo_proveedor" value="{{ $codigo }}">

    <div class="adm-section anim">
        <div class="adm-section-head"><h4>Facturas pendientes ({{ $facturas->count() }})</h4></div>
        @if($facturas->isEmpty())
            <div class="empty">Sin facturas pendientes para este proveedor.</div>
        @else
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll" {{ $expediente['ok'] ? '' : 'disabled' }}></th>
                            <th>Folio / UUID</th>
                            <th>Flete</th>
                            <th>Régimen</th>
                            <th>Total</th>
                            <th>Retenciones</th>
                            <th>Neto</th>
                            <th>Avisos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facturas as $f)
                            <tr>
                                <td>
                                    <input type="checkbox" name="factura_ids[]" value="{{ $f->id }}" class="chk-fact"
                                        {{ $expediente['ok'] ? '' : 'disabled' }}>
                                </td>
                                <td>
                                    <strong>{{ $f->folio_cfdi ?: '—' }}</strong><br>
                                    <span style="color:var(--gray-muted);font-size:11px;">{{ $f->uuid_cfdi ?: 'sin UUID' }}</span>
                                </td>
                                <td>
                                    @if($f->es_fletera)
                                        <span class="pill warn">Flete</span>
                                    @else
                                        <span class="pill neut">No</span>
                                    @endif
                                </td>
                                <td>{{ $f->regimen_fiscal ?: '—' }}</td>
                                <td style="font-weight:700;">${{ number_format((float)$f->total, 2) }}</td>
                                <td>
                                    IVA ${{ number_format((float)($f->retencion_iva ?? 0), 2) }}<br>
                                    ISR ${{ number_format((float)($f->retencion_isr ?? 0), 2) }}
                                </td>
                                <td style="font-weight:700;">${{ number_format((float)$f->neto_pago, 2) }}</td>
                                <td>
                                    @forelse($f->avisos_pago as $a)
                                        <span class="aviso">• {{ $a }}</span>
                                    @empty
                                        <span class="pill ok">OK</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="adm-section anim">
        <div class="adm-section-head"><h4>Datos del lote</h4></div>
        <div class="form-row">
            <div class="form-field">
                <label>Fecha de pago (opcional)</label>
                <input type="date" name="fecha_pago" value="{{ old('fecha_pago') }}" {{ $expediente['ok'] ? '' : 'disabled' }}>
            </div>
            <div class="form-field" style="flex:1;min-width:220px;">
                <label>Notas</label>
                <input type="text" name="notas" value="{{ old('notas') }}" maxlength="1000" placeholder="Ej. Pago quincena julio"
                    {{ $expediente['ok'] ? '' : 'disabled' }}>
            </div>
            <button type="submit" class="btn-primary" id="btnCrear" {{ $expediente['ok'] && $facturas->isNotEmpty() ? '' : 'disabled' }}>
                Crear lote (borrador)
            </button>
        </div>
        <p class="aviso" style="padding:0 18px 14px;color:var(--gray-muted);">
            Si pones fecha al confirmar, las facturas pasan a <strong>pagada</strong>; si no, a <strong>programada</strong>.
        </p>
    </div>
</form>
<script>
document.getElementById('checkAll')?.addEventListener('change', function () {
    document.querySelectorAll('.chk-fact').forEach(c => { c.checked = this.checked; });
});
</script>
@endsection
