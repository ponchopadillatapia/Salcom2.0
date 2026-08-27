@extends('layouts.admin')
@section('title', 'Reembolso #' . $reembolso->id)

@push('styles')
<style>
    .rv-wrap { max-width: 880px; }
    .rv-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 24px; margin-bottom: 20px; }
    .rv-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px; }
    .rv-detail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .rv-field { }
    .rv-field .lbl { font-size: 11px; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
    .rv-field .val { font-size: 14px; font-weight: 600; color: var(--gray-text); }
    .rv-gastos-table { width: 100%; border-collapse: collapse; }
    .rv-gastos-table th { font-size: 11px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border-light); }
    .rv-gastos-table td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid var(--border-light); }
    .rv-gastos-table tfoot td { font-weight: 700; background: var(--gray-soft); }
    .badge-est { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .badge-borrador { background: #f3f4f6; color: #6b7280; }
    .badge-pendiente { background: #fef3c7; color: #92400e; }
    .badge-aprobado { background: #dcfce7; color: #166534; }
    .badge-rechazado { background: #fee2e2; color: #991b1b; }
    .badge-pagado { background: #dbeafe; color: #1e40af; }
    .rv-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
    .rv-actions form { display: inline; }
    .btn-action { padding: 9px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; }
    .btn-enviar { background: #2563eb; color: #fff; }
    .btn-aprobar { background: #059669; color: #fff; }
    .btn-rechazar { background: #dc2626; color: #fff; }
    .btn-reembolsar { background: var(--purple); color: #fff; }
    @media(max-width:768px) { .rv-detail-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="rv-wrap">
    @if(session('mensaje'))
    <div style="background:#ecfdf5;border:1px solid #059669;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#059669;font-size:13px;font-weight:600;">{{ session('mensaje') }}</div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#dc2626;font-size:13px;font-weight:600;">{{ session('error') }}</div>
    @endif

    <div style="margin-bottom:16px;">
        <a href="{{ route('admin.reembolsos-viaje') }}" style="font-size:13px;color:var(--purple);font-weight:600;text-decoration:none;">← Volver al listado</a>
    </div>

    {{-- Header con estatus --}}
    <div class="rv-card">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <h3 style="margin:0;">Solicitud #{{ $reembolso->id }}</h3>
            <span class="badge-est {{ $reembolso->badgeClass() }}">{{ $reembolso->estatusLabel() }}</span>
        </div>

        <div class="rv-detail-grid" style="margin-top:16px;">
            <div class="rv-field"><div class="lbl">Empleado</div><div class="val">{{ $reembolso->nombre_empleado }}</div></div>
            <div class="rv-field"><div class="lbl">Código</div><div class="val">{{ $reembolso->codigo_empleado }}</div></div>
            <div class="rv-field"><div class="lbl">Departamento</div><div class="val">{{ $reembolso->departamento ?: '—' }}</div></div>
            <div class="rv-field"><div class="lbl">País destino</div><div class="val">{{ $reembolso->pais_destino }}</div></div>
            <div class="rv-field"><div class="lbl">Moneda destino</div><div class="val">{{ $reembolso->moneda_destino }}</div></div>
            <div class="rv-field"><div class="lbl">Tipo de cambio</div><div class="val">1 {{ $reembolso->moneda_destino }} = {{ $reembolso->tipo_cambio }} MXN</div></div>
        </div>

        @if($reembolso->notas)
        <div style="background:var(--gray-soft);border-radius:8px;padding:12px 14px;font-size:12px;color:var(--gray-text);margin-top:8px;">
            <strong>Notas:</strong> {{ $reembolso->notas }}
        </div>
        @endif
        @if($reembolso->notas_revision)
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;font-size:12px;color:#991b1b;margin-top:8px;">
            <strong>Notas de revisión:</strong> {{ $reembolso->notas_revision }}
        </div>
        @endif
    </div>

    {{-- Tabla de gastos --}}
    <div class="rv-card">
        <h3>Desglose de Gastos</h3>
        <table class="rv-gastos-table">
            <thead><tr><th>Concepto</th><th>Monto {{ $reembolso->moneda_destino }}</th><th>Equivalente MXN</th></tr></thead>
            <tbody>
                @foreach($reembolso->gastos ?? [] as $gasto)
                <tr>
                    <td>{{ $gasto['concepto'] ?? '—' }}</td>
                    <td>{{ number_format($gasto['monto_local'] ?? 0, 2) }}</td>
                    <td>${{ number_format($gasto['monto_base'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td>{{ number_format($reembolso->total_moneda_local, 2) }} {{ $reembolso->moneda_destino }}</td>
                    <td>${{ number_format($reembolso->total_moneda_base, 2) }} MXN</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Acciones --}}
    <div class="rv-card">
        <h3>Acciones</h3>
        <div class="rv-actions">
            @if($reembolso->estatus === 'borrador')
                <form method="POST" action="{{ route('admin.reembolsos-viaje.enviar', $reembolso) }}">
                    @csrf
                    <button type="submit" class="btn-action btn-enviar" onclick="return confirm('¿Enviar solicitud? Ya no podrás editar.');">Enviar Solicitud</button>
                </form>
            @endif

            @if($reembolso->estatus === 'enviado')
                <form method="POST" action="{{ route('admin.reembolsos-viaje.aprobar', $reembolso) }}">
                    @csrf
                    <input type="hidden" name="notas_revision" value="">
                    <button type="submit" class="btn-action btn-aprobar">✓ Aprobar</button>
                </form>
                <form method="POST" action="{{ route('admin.reembolsos-viaje.rechazar', $reembolso) }}" onsubmit="var n=prompt('Motivo del rechazo:');if(!n)return false;this.querySelector('[name=notas_revision]').value=n;">
                    @csrf
                    <input type="hidden" name="notas_revision" value="">
                    <button type="submit" class="btn-action btn-rechazar">✗ Rechazar</button>
                </form>
            @endif

            @if($reembolso->estatus === 'aprobado')
                <form method="POST" action="{{ route('admin.reembolsos-viaje.reembolsado', $reembolso) }}">
                    @csrf
                    <button type="submit" class="btn-action btn-reembolsar">💳 Marcar como reembolsado</button>
                </form>
            @endif
        </div>

        @if($reembolso->archivo_comprobantes)
        @php
            $archivos = json_decode($reembolso->archivo_comprobantes, true);
            if (!is_array($archivos)) $archivos = ['comprobantes' => $reembolso->archivo_comprobantes];
        @endphp
        <div style="margin-top:16px;display:flex;gap:14px;flex-wrap:wrap;">
            @if(!empty($archivos['factura_pdf']))
            <a href="{{ asset('storage/' . $archivos['factura_pdf']) }}" target="_blank" style="color:var(--purple);font-size:13px;font-weight:600;text-decoration:none;">📄 Factura PDF</a>
            @endif
            @if(!empty($archivos['factura_xml']))
            <a href="{{ asset('storage/' . $archivos['factura_xml']) }}" target="_blank" style="color:var(--purple);font-size:13px;font-weight:600;text-decoration:none;">📋 Factura XML</a>
            @endif
            @if(!empty($archivos['comprobantes']))
            <a href="{{ asset('storage/' . $archivos['comprobantes']) }}" target="_blank" style="color:var(--purple);font-size:13px;font-weight:600;text-decoration:none;">📎 Comprobantes</a>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
