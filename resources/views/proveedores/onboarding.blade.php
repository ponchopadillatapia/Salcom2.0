@extends('layouts.proveedor')

@section('title', 'Onboarding')

@section('hero')
<div class="hero-band">
    <h1>Onboarding</h1>
    <p>Sigue los pasos para convertirte en proveedor activo de Industrias Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .ob-header { background: var(--white); border-radius: var(--radius-lg); padding: 24px 28px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
    .ob-header h2 { font-size: 20px; color: var(--gray-text); font-weight: 700; margin-bottom: 4px; letter-spacing: -0.3px; }
    .ob-header p { font-size: 13px; color: var(--gray-muted); margin-bottom: 20px; }

    .progress-wrap { margin-bottom: 8px; }
    .progress-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--gray-text); margin-bottom: 6px; font-weight: 600; }
    .progress-bar { height: 8px; background: var(--border-light); border-radius: 999px; overflow: hidden; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, var(--purple) 0%, var(--purple-mid) 100%); border-radius: 999px; }

    .pasos-grid { display: flex; flex-direction: column; gap: 16px; }
    .paso-card { background: var(--white); border-radius: var(--radius-lg); padding: 20px 24px; display: flex; align-items: center; gap: 20px; transition: var(--transition); box-shadow: var(--shadow-sm); }
    .paso-card:hover { box-shadow: var(--shadow-md); }
    .paso-card.completado { border-left: 4px solid var(--green); }
    .paso-card.pendiente  { border-left: 4px solid var(--amber); }
    .paso-card.bloqueado  { border-left: 4px solid var(--border-light); opacity: 0.6; }

    .paso-icono { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .paso-icono.verde { background: var(--green-bg); }
    .paso-icono.ambar { background: var(--amber-bg); }
    .paso-icono.gris  { background: var(--gray-soft); }

    .paso-info { flex: 1; min-width: 0; }
    .paso-titulo { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 3px; }
    .paso-desc { font-size: 13px; color: var(--gray-muted); line-height: 1.5; }

    .paso-badge { font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
    .badge-completado { background: var(--green-bg); color: var(--green); }
    .badge-pendiente  { background: var(--amber-bg); color: var(--amber); }
    .badge-bloqueado  { background: var(--gray-soft); color: var(--gray-muted); }

    .btn-ver { padding: 7px 18px; border: 1.5px solid var(--purple); border-radius: var(--radius-pill); background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: var(--transition); text-decoration: none; display: inline-block; }
    .btn-ver:hover { background: var(--purple); color: white; transform: scale(1.03); }
    .btn-ver:active { transform: scale(0.97); }
    .btn-ver.disabled { border-color: var(--border-light); color: var(--gray-muted); cursor: not-allowed; pointer-events: none; }

    .ob-aviso { background: var(--amber-bg, #fff7ed); border: 1px solid #fcd34d; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-bottom: 16px; }
    .ob-ok { background: var(--green-bg); border: 1px solid var(--green); border-radius: 10px; padding: 12px 16px; font-size: 13px; color: var(--green); margin-bottom: 16px; }

    @media (max-width: 768px) { .paso-card { flex-wrap: wrap; } }
</style>
@endpush

@section('content')

    @if(session('error'))
    <div class="ob-aviso">{{ session('error') }}</div>
    @endif
    @if(session('mensaje'))
    <div class="ob-ok">{{ session('mensaje') }}</div>
    @endif

    @if(!($pasoActivo ?? false))
    <div class="ob-aviso">
        Completa los pasos y espera la aprobación de Dirección. El resto del portal está bloqueado.
    </div>
    @else
    <div class="ob-ok">Tu cuenta ya está activa. Puedes usar todo el portal.</div>
    @endif

    @if($pasoDocsRenovar ?? false)
    <div class="ob-aviso">
        Tus documentos fiscales están por vencer o requieren renovación. Actualízalos en Validación de documentos (no se bloquea el acceso).
    </div>
    @endif

    <div class="ob-header">
        <h2>Hola, {{ session('proveedor_nombre', 'Proveedor') }}</h2>
        <p>Completa cada paso. Cuando todo esté listo, Dirección revisará y activará tu cuenta.</p>

        <div class="progress-wrap">
            <div class="progress-label">
                <span>Progreso de onboarding</span>
                <span>{{ $completados }} de {{ $totalPasos }} pasos</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>

    <div class="pasos-grid">

        {{-- 1 Registro --}}
        <div class="paso-card completado">
            <div class="paso-icono verde"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Cuestionario de registro</div>
                <div class="paso-desc">Datos generales de tu cuenta (nombre, correo, teléfono, tipo de persona).</div>
            </div>
            <span class="paso-badge badge-completado">Completado</span>
        </div>

        {{-- 2 Datos bancarios --}}
        <div class="paso-card {{ $pasoBancarios ? 'completado' : 'pendiente' }}">
            <div class="paso-icono {{ $pasoBancarios ? 'verde' : 'ambar' }}"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="{{ $pasoBancarios ? '#059669' : '#D97706' }}" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Formulario datos bancarios</div>
                <div class="paso-desc">
                    @if($pasoBancarios)
                        Ya registraste tus datos bancarios. No es necesario volver a llenarlo (salvo actualización).
                    @else
                        Captura tu institución financiera y datos bancarios en Identificación.
                    @endif
                </div>
            </div>
            <span class="paso-badge {{ $pasoBancarios ? 'badge-completado' : 'badge-pendiente' }}">{{ $pasoBancarios ? 'Completado' : 'Pendiente' }}</span>
            @if($pasoBancarios)
                <span class="btn-ver disabled">Listo</span>
            @else
                <a href="{{ route('proveedores.identificacion') }}" class="btn-ver">Llenar</a>
            @endif
        </div>

        {{-- 3 Docs (bloqueado hasta llenar datos bancarios) --}}
        @if(! $pasoBancarios)
        <div class="paso-card bloqueado">
            <div class="paso-icono gris"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#AAA" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Validación de documentos</div>
                <div class="paso-desc">Primero completa el formulario de datos bancarios. Al guardarlo, este paso se desbloquea.</div>
            </div>
            <span class="paso-badge badge-bloqueado">Bloqueado</span>
            <span class="btn-ver disabled">Validar</span>
        </div>
        @else
        <div class="paso-card {{ $pasoDocs ? 'completado' : 'pendiente' }}">
            <div class="paso-icono {{ $pasoDocs ? 'verde' : 'ambar' }}"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="{{ $pasoDocs ? '#059669' : '#D97706' }}" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Validación de documentos</div>
                <div class="paso-desc">
                    @if($pasoDocsRenovar)
                        Documentos vigentes, pero próximos a renovar (ciclo ~21 días). Actualízalos cuando puedas.
                    @elseif($pasoDocs)
                        Documentos aprobados. Puedes renovarlos cuando el sistema te avise.
                    @else
                        Sube y valida tus documentos fiscales (CIF, opinión SAT, carátula bancaria, etc.).
                    @endif
                </div>
            </div>
            <span class="paso-badge {{ $pasoDocs ? 'badge-completado' : 'badge-pendiente' }}">
                {{ $pasoDocsRenovar ? 'Por renovar' : ($pasoDocs ? 'Completado' : 'Pendiente') }}
            </span>
            <a href="{{ route('proveedores.validacion-fiscal') }}" class="btn-ver">{{ $pasoDocs ? 'Ver / renovar' : 'Validar' }}</a>
        </div>
        @endif

        {{-- 4 Contactos --}}
        <div class="paso-card {{ $pasoContactos ? 'completado' : 'pendiente' }}">
            <div class="paso-icono {{ $pasoContactos ? 'verde' : 'ambar' }}"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="{{ $pasoContactos ? '#059669' : '#D97706' }}" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Registro de contactos</div>
                <div class="paso-desc">
                    @if($pasoContactos)
                        Ya registraste {{ $numContactos }} contactos (mínimo 2 cumplido).
                    @else
                        <strong>Obligatorio:</strong> mínimo 2 contactos. Llevas {{ $numContactos }}/2.
                    @endif
                </div>
            </div>
            <span class="paso-badge {{ $pasoContactos ? 'badge-completado' : 'badge-pendiente' }}">{{ $pasoContactos ? 'Completado' : 'Pendiente' }}</span>
            <a href="{{ route('proveedores.perfil') }}" class="btn-ver">{{ $pasoContactos ? 'Ver' : 'Registrar' }}</a>
        </div>

        {{-- 5 Direccion / activo --}}
        @if($pasoActivo)
        <div class="paso-card completado">
            <div class="paso-icono verde"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Proveedor activo</div>
                <div class="paso-desc">Dirección ya activó tu cuenta. Puedes operar en el portal.</div>
            </div>
            <span class="paso-badge badge-completado">Completado</span>
        </div>
        @elseif($pasoListoDireccion)
        <div class="paso-card pendiente">
            <div class="paso-icono ambar"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Espera validación de Dirección</div>
                <div class="paso-desc">Ya completaste datos bancarios, documentos y contactos. Tu solicitud está en revisión. El resto del sistema sigue bloqueado hasta que te den de alta.</div>
            </div>
            <span class="paso-badge badge-pendiente">En revisión</span>
            <span class="btn-ver disabled">Esperar</span>
        </div>
        @else
        <div class="paso-card bloqueado">
            <div class="paso-icono gris"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#AAA" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Espera validación de Dirección</div>
                <div class="paso-desc">Se desbloquea cuando completes datos bancarios, documentos y al menos 2 contactos.</div>
            </div>
            <span class="paso-badge badge-bloqueado">Bloqueado</span>
            <span class="btn-ver disabled">—</span>
        </div>
        @endif

    </div>

    {{-- ══════════════════════════════════════════════════════════
         ADJUNTO DE DOCUMENTOS FISCALES (solo si ya hay datos bancarios)
    ══════════════════════════════════════════════════════════════ --}}
    @if($pasoBancarios)
    <div style="margin-top:32px;">
        <div class="paso-card" style="border-left:4px solid var(--purple);flex-direction:column;align-items:stretch;">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                <div class="paso-icono" style="background:var(--purple-light);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/></svg>
                </div>
                <div>
                    <div class="paso-titulo">Adjunto de Documentos Fiscales</div>
                    <div class="paso-desc">Sube tus documentos en PDF. Se enviarán al equipo de Salcom para su validación.</div>
                </div>
            </div>

            @if(session('adj_exito'))
                <div style="background:var(--green-bg);border:1px solid var(--green);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--green);font-weight:600;margin-bottom:14px;">
                    ✓ {{ session('adj_exito') }}
                </div>
            @endif

            <form method="POST" action="{{ route('proveedores.adjunto-documentos.subir') }}" enctype="multipart/form-data" id="formAdjOnboarding">
                @csrf
                <div id="dropZone" style="background:#f0fdf4;border:2px dashed #86efac;border-radius:14px;padding:32px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:16px;" onclick="document.getElementById('adjInputMultiple').click()">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.5" style="margin-bottom:8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <div style="font-size:14px;font-weight:700;color:#059669;">Subir archivos PDF</div>
                    <div style="font-size:12px;color:var(--gray-muted);margin-top:4px;">O suelte los archivos PDF aquí</div>
                </div>
                <input type="file" id="adjInputMultiple" multiple accept=".pdf" style="display:none;" onchange="adjAgregarArchivos(this.files)">

                <div id="adjListaArchivos" style="display:none;margin-bottom:16px;"></div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                    <select id="adjTipoSelect" style="padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;" onchange="adjFiltrarTipos()">
                        <option value="" disabled selected>Selecciona tipo de persona</option>
                        <option value="fisica">Persona Física</option>
                        <option value="moral">Persona Moral</option>
                    </select>
                    <select id="adjDocSelect" style="padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;display:none;">
                    </select>
                </div>
                <button type="button" onclick="adjSubirConTipo()" style="padding:10px 14px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;width:100%;">
                    Enviar documentos
                </button>
            </form>

            {{-- Documentos ya subidos --}}
            @php
                $docsSubidos = ($proveedor && $proveedor->relationLoaded('documentos'))
                    ? $proveedor->documentos->sortByDesc('created_at')
                    : collect();
            @endphp
            @if($docsSubidos->count())
            <div style="border-top:1px solid var(--border-light);padding-top:14px;margin-top:8px;">
                <div style="font-size:12px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Documentos enviados</div>
                @foreach($docsSubidos as $doc)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);font-size:12px;">
                        <span style="font-weight:600;color:var(--gray-text);">{{ ['cif'=>'CIF','opinion'=>'Opinión SAT','acta'=>'Acta','rep_legal'=>'INE Rep. Legal','contribuyente'=>'INE Contribuyente','caratula_banco'=>'Carátula Banco'][$doc->tipo] ?? $doc->tipo }}</span>
                        <span style="color:var(--gray-muted);">{{ $doc->created_at->format('d/m/Y') }}</span>
                        <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:999px;background:{{ $doc->estatus === 'aprobado' ? 'var(--green-bg)' : ($doc->estatus === 'rechazado' ? 'var(--red-bg)' : 'var(--amber-bg)') }};color:{{ $doc->estatus === 'aprobado' ? 'var(--green)' : ($doc->estatus === 'rechazado' ? 'var(--red)' : 'var(--amber)') }};">{{ ucfirst($doc->estatus) }}</span>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

@endsection

@push('scripts')
@if($pasoBancarios ?? false)
<script>
var adjArchivos = [];

// Drag & drop
var dropZone = document.getElementById('dropZone');
if (dropZone) {
    dropZone.addEventListener('dragover', function(e) { e.preventDefault(); this.style.borderColor='#6B3FA0'; this.style.background='#f3eafa'; });
    dropZone.addEventListener('dragleave', function(e) { this.style.borderColor='#86efac'; this.style.background='#f0fdf4'; });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor='#86efac'; this.style.background='#f0fdf4';
        adjAgregarArchivos(e.dataTransfer.files);
    });
}

function adjAgregarArchivos(files) {
    var lista = document.getElementById('adjListaArchivos');
    for (var i = 0; i < files.length; i++) {
        var f = files[i];
        if (f.type !== 'application/pdf' && !f.name.toLowerCase().endsWith('.pdf')) {
            alert('Solo se aceptan archivos PDF: ' + f.name);
            continue;
        }
        adjArchivos.push(f);
    }
    adjRenderLista();
}

function adjRenderLista() {
    var lista = document.getElementById('adjListaArchivos');
    if (adjArchivos.length === 0) { lista.style.display = 'none'; return; }
    lista.style.display = 'block';
    var html = '';
    adjArchivos.forEach(function(f, i) {
        html += '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--gray-soft);border-radius:8px;margin-bottom:6px;font-size:12px;">';
        html += '<span style="color:var(--green);font-weight:600;">✓ ' + f.name + '</span>';
        html += '<button type="button" onclick="adjQuitarArchivo(' + i + ')" style="background:none;border:none;color:var(--red);font-size:14px;cursor:pointer;font-weight:700;">✕</button>';
        html += '</div>';
    });
    lista.innerHTML = html;
}

function adjQuitarArchivo(i) {
    adjArchivos.splice(i, 1);
    adjRenderLista();
}

function adjSubirConTipo() {
    if (adjArchivos.length === 0) { alert('Selecciona al menos un archivo PDF.'); return; }
    var docSelect = document.getElementById('adjDocSelect');
    if (!docSelect.value) { alert('Selecciona el tipo de documento.'); return; }

    var tipo = docSelect.value;
    var form = document.getElementById('formAdjOnboarding');
    var formData = new FormData(form);

    // Limpiar archivos previos del form
    formData.delete('cif'); formData.delete('opinion'); formData.delete('acta');
    formData.delete('rep_legal'); formData.delete('contribuyente'); formData.delete('caratula_banco');

    // Subir el primer archivo con el tipo seleccionado
    formData.append(tipo, adjArchivos[0]);

    fetch('{{ route("proveedores.adjunto-documentos.subir") }}', {
        method: 'POST',
        body: formData
    }).then(function(res) {
        if (res.redirected) { window.location.href = res.url; return; }
        window.location.reload();
    }).catch(function() { alert('Error al subir. Intenta de nuevo.'); });
}

function adjFiltrarTipos() {
    var tipoPersona = document.getElementById('adjTipoSelect').value;
    var docSelect = document.getElementById('adjDocSelect');
    docSelect.style.display = 'block';
    docSelect.innerHTML = '<option value="" disabled selected>Selecciona documento</option>';

    var docsFisica = [
        { value: 'cif', label: 'CIF / Constancia Fiscal' },
        { value: 'opinion', label: 'Opinión de Cumplimiento SAT' },
        { value: 'contribuyente', label: 'INE Contribuyente' },
        { value: 'caratula_banco', label: 'Carátula Bancaria' }
    ];

    var docsMoral = [
        { value: 'cif', label: 'CIF / Constancia Fiscal' },
        { value: 'opinion', label: 'Opinión de Cumplimiento SAT' },
        { value: 'acta', label: 'Acta Constitutiva' },
        { value: 'rep_legal', label: 'INE Representante Legal' },
        { value: 'contribuyente', label: 'INE Contribuyente' },
        { value: 'caratula_banco', label: 'Carátula Bancaria' }
    ];

    var docs = tipoPersona === 'fisica' ? docsFisica : docsMoral;
    docs.forEach(function(d) {
        var opt = document.createElement('option');
        opt.value = d.value;
        opt.textContent = d.label;
        docSelect.appendChild(opt);
    });
}
</script>
@endif
@endpush
