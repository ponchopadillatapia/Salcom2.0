@extends('layouts.cliente')
@section('title', 'Fiscal')
@section('hero')
<div class="hero-band">
    <h1>Fiscal</h1>
    <p>Documentación de tu empresa para facturación (CFDI), crédito y cumplimiento con Industrias Salcom</p>
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
    .fiscal-perfil-hint{font-size:12px;color:var(--gray-muted);margin-top:8px}
    .fiscal-perfil-hint a{color:var(--purple);font-weight:600}
    @media(max-width:768px){.fiscal-grid,.form-row{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

{{-- Estado actual — alineado con el KPI del inicio del portal (6/6) --}}
<div class="fiscal-grid">
    <div class="fiscal-card">
        <h4>Carpeta fiscal del cliente — Estado actual</h4>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Constancia de situación fiscal (CIF)</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Opinión de cumplimiento SAT</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Identificación fiscal / uso de CFDI</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">INE del representante o apoderado</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Carátula bancaria (pagos y devoluciones)</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
        <div class="fiscal-status">
            <div class="fiscal-dot ok"></div>
            <div class="fiscal-name">Comprobante de domicilio fiscal</div>
            <span class="fiscal-badge ok">Vigente</span>
        </div>
    </div>

    <div class="fiscal-card">
        <h4>Resumen</h4>
        <div style="text-align:center;padding:20px 0;">
            <div style="font-size:48px;font-weight:700;color:var(--green);">6/6</div>
            <div style="font-size:13px;color:var(--gray-muted);margin-top:8px;">Documentos al día</div>
        </div>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-light);">
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                <span style="color:var(--gray-muted);">Cliente</span>
                <span style="font-weight:700;color:var(--gray-text);">{{ session('cliente_codigo', '—') }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                <span style="color:var(--gray-muted);">Vigentes</span>
                <span style="font-weight:700;color:var(--green);">6</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                <span style="color:var(--gray-muted);">Por vencer (7 días)</span>
                <span style="font-weight:700;color:var(--amber);">0</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;">
                <span style="color:var(--gray-muted);">Vencidos</span>
                <span style="font-weight:700;color:var(--red);">0</span>
            </div>
        </div>
    </div>
</div>

<div class="fiscal-form">
    <h4>Enviar documento fiscal a contabilidad</h4>
    <p>Sube un archivo actualizado relacionado con tu registro como cliente. Contabilidad lo revisará para facturación y condiciones comerciales (respuesta orientativa 24–48 h).</p>

    <form method="POST" action="#" enctype="multipart/form-data">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Tipo de documento</label>
                <select name="tipo_documento">
                    <option value="">Selecciona...</option>
                    <option value="cif">Constancia de situación fiscal (CIF)</option>
                    <option value="opinion">Opinión de cumplimiento SAT</option>
                    <option value="cfdi_uso">Constancia de obligaciones / uso CFDI</option>
                    <option value="rep_legal">INE representante o apoderado</option>
                    <option value="caratula_banco">Carátula bancaria</option>
                    <option value="comprobante_domicilio">Comprobante de domicilio fiscal</option>
                </select>
            </div>
            <div class="form-group">
                <label>RFC (referencia)</label>
                <input type="text" name="rfc" placeholder="Ej: ABC123456XYZ" value="" readonly style="background:var(--gray-soft);">
                <div class="fiscal-perfil-hint">El RFC registrado en tu cuenta se gestiona en <a href="{{ route('clientes.perfil') }}">Mi perfil</a>.</div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Archivo PDF</label>
                <input type="file" name="archivo" accept=".pdf">
            </div>
            <div class="form-group">
                <label>Notas (opcional)</label>
                <input type="text" name="notas" placeholder="Ej: Renovación CIF — mayo 2026">
            </div>
        </div>
        <button type="submit" class="btn-submit" disabled style="opacity:0.6;cursor:not-allowed;">
            Enviar a contabilidad — Módulo pendiente
        </button>
    </form>
</div>

<div class="fiscal-note">
    El envío directo a contabilidad está en desarrollo. Mientras tanto puedes consultar el estado aquí y revisar tus datos en Mi perfil y en <a href="{{ route('clientes.estado-cuenta') }}" style="color:var(--purple);font-weight:600;">Estado de cuenta</a> para facturas y saldos.
</div>

@endsection
