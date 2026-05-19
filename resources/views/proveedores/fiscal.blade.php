@extends('layouts.proveedor')
@section('title', 'Fiscal')
@section('hero')
<div class="hero-band">
    <h1>Fiscal</h1>
    <p>Envía tu información fiscal a contabilidad de Industrias Salcom</p>
</div>
@endsection
@push('styles')
<style>
    .fiscal-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
    .fiscal-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px}
    .fiscal-card h4{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:16px}
    .fiscal-status{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .fiscal-status:last-child{border-bottom:none}
    .fiscal-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
    .fiscal-dot.ok{background:var(--green)}
    .fiscal-dot.pending{background:var(--amber)}
    .fiscal-dot.expired{background:var(--red)}
    .fiscal-name{flex:1;font-weight:600;color:var(--gray-text)}
    .fiscal-badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px}
    .fiscal-badge.ok{background:var(--green-bg);color:var(--green)}
    .fiscal-badge.pending{background:var(--amber-bg);color:var(--amber)}
    .fiscal-badge.expired{background:var(--red-bg);color:var(--red)}

    .fiscal-form{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;margin-bottom:24px}
    .fiscal-form h4{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:6px}
    .fiscal-form p{font-size:13px;color:var(--gray-muted);margin-bottom:20px}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
    .form-group{display:flex;flex-direction:column;gap:6px}
    .form-group label{font-size:12px;font-weight:600;color:var(--gray-muted)}
    .form-group input,.form-group select{border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .form-group input:focus,.form-group select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .form-group input[type="file"]{padding:8px;font-size:12px}
    .btn-submit{padding:10px 24px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:var(--transition)}
    .btn-submit:hover{background:var(--purple-dark);transform:translateY(-1px)}
    .fiscal-note{font-size:11px;color:var(--gray-muted);text-align:center;margin-top:16px;padding:12px;background:var(--gray-soft);border-radius:10px}
    @media(max-width:768px){.fiscal-grid,.form-row{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

{{-- Estado actual de documentos --}}
<div class="fiscal-grid">
    <div class="fiscal-card">
        <h4>Documentos fiscales — Estado actual</h4>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Constancia de Situación Fiscal (CIF)</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Opinión de cumplimiento SAT</div>
            <span class="fiscal-badge ok" style="font-size:12px;padding:4px 12px;font-weight:700;">POSITIVA</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Acta constitutiva</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot pending"></div>
            <div class="fiscal-name">INE Representante legal</div>
            <span class="fiscal-badge pending">Por vencer</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Carátula bancaria</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot expired"></div>
            <div class="fiscal-name">Comprobante de domicilio</div>
            <span class="fiscal-badge expired">Vencido</span>
        </div>
    </div>

    <div class="fiscal-card">
        <h4>Resumen</h4>
        <div style="text-align:center;padding:20px 0;">
            <div style="font-size:48px;font-weight:700;color:var(--amber);">5/6</div>
            <div style="font-size:13px;color:var(--gray-muted);margin-top:8px;">Documentos al día</div>
        </div>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-light);">
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                <span style="color:var(--gray-muted);">Vigentes</span>
                <span style="font-weight:700;color:var(--green);">4</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                <span style="color:var(--gray-muted);">Por vencer (7 días)</span>
                <span style="font-weight:700;color:var(--amber);">1</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                <span style="color:var(--gray-muted);">Vencidos</span>
                <span style="font-weight:700;color:var(--red);">1</span>
            </div>
        </div>
    </div>
</div>

{{-- Formulario para subir documentos de facturación --}}
<div class="fiscal-form">
    <h4>Subir documentos de facturación</h4>
    <p>Sube tu OC, factura (PDF), XML y comprobante de pago. La IA los validará automáticamente.</p>

    <form method="POST" action="{{ route('proveedores.fiscal.subir') }}" enctype="multipart/form-data" id="formFiscal">
        @csrf
        <input type="hidden" name="tipo_documento" value="facturacion">
        <div class="form-row">
            <div class="form-group">
                <label>Orden de Compra (PDF)</label>
                <input type="file" name="archivo_oc" accept=".pdf">
            </div>
            <div class="form-group">
                <label>Factura (PDF)</label>
                <input type="file" name="archivo" accept=".pdf" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>XML de la factura</label>
                <input type="file" name="archivo_xml" accept=".xml">
            </div>
            <div class="form-group">
                <label>Comprobante de pago (PDF)</label>
                <input type="file" name="archivo_pago" accept=".pdf">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Notas (opcional)</label>
                <input type="text" name="notas" placeholder="Ej: Factura correspondiente a OC #10045">
                <input type="hidden" name="rfc" value="{{ session('proveedor_rfc', '') }}">
            </div>
            <div class="form-group" style="justify-content:flex-end;">
                <button type="submit" class="btn-submit">Subir y validar</button>
            </div>
        </div>
    </form>

    @if(session('fiscal_resultado'))
        @php $res = session('fiscal_resultado'); @endphp
        <div style="margin-top:16px;padding:16px;border-radius:10px;background:{{ $res['aprobado'] ? 'var(--green-bg)' : 'var(--red-bg)' }};border:1px solid {{ $res['aprobado'] ? 'var(--green)' : 'var(--red)' }};">
            <div style="font-size:13px;font-weight:700;color:{{ $res['aprobado'] ? 'var(--green)' : 'var(--red)' }};margin-bottom:6px;">
                {{ $res['aprobado'] ? 'Documentos aprobados' : 'Documentos rechazados' }}
            </div>
            <div style="font-size:12px;color:var(--gray-text);">{{ $res['mensaje'] }}</div>
        </div>
    @endif

    @if($errors->any())
        <div style="margin-top:16px;padding:16px;border-radius:10px;background:var(--red-bg);border:1px solid var(--red);">
            <div style="font-size:13px;font-weight:700;color:var(--red);margin-bottom:6px;">Error</div>
            @foreach($errors->all() as $error)
                <div style="font-size:12px;color:var(--gray-text);">{{ $error }}</div>
            @endforeach
        </div>
    @endif
</div>

@endsection
