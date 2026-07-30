@extends('layouts.proveedor')

@section('title', 'Mi Perfil')

@section('hero')
<div class="hero-band">
    <h1>Mi Perfil</h1>
    <p>Consulta y actualiza tu información de proveedor</p>
</div>
@endsection

@push('styles')
<style>
    .perfil-header { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
    .perfil-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: var(--white); flex-shrink: 0; position: relative; cursor: pointer; overflow: hidden; transition: all .15s; }
    .perfil-avatar:hover { opacity: .85; }
    .perfil-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .perfil-avatar .avatar-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s; border-radius: 50%; }
    .perfil-avatar:hover .avatar-overlay { opacity: 1; }
    .perfil-name { font-size: 18px; font-weight: 700; color: var(--gray-text); }
    .perfil-meta { font-size: 13px; color: var(--gray-muted); margin-top: 2px; }
    .perfil-actions { margin-left: auto; }
    .btn-edit { padding: 8px 20px; border: 1px solid var(--purple); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all .15s; }
    .btn-edit:hover { background: var(--purple); color: var(--white); }
    .btn-lapiz{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid var(--border);border-radius:8px;background:var(--white);color:var(--purple);cursor:pointer;margin-left:auto;transition:all .15s}
    .btn-lapiz:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .perfil-card h3{justify-content:flex-start}
    .perfil-edit-form{display:none;margin-top:4px}
    .perfil-edit-form.open{display:block}
    .perfil-view.hidden{display:none}
    .edit-field{display:flex;flex-direction:column;gap:4px;padding:10px 0;border-bottom:1px solid var(--border)}
    .edit-field:last-of-type{border-bottom:none}
    .edit-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .edit-field input,.edit-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:#fff}
    .edit-field input:focus,.edit-field select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .edit-actions{display:flex;gap:8px;margin-top:14px;justify-content:flex-end}
    .btn-save{padding:8px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-cancel{padding:8px 16px;background:#fff;color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}

    .perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .perfil-card { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
    .perfil-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 13px; color: var(--gray-muted); }
    .info-value { font-size: 13px; color: var(--gray-text); font-weight: 600; text-align: right; }

    .status-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
    .status-active { background: var(--green-bg); color: var(--green); }
    .status-pending { background: var(--amber-bg); color: var(--amber); }
    .status-inactive { background: var(--red-bg); color: var(--red); }

    /* Contactos */
    .contactos-section{margin-top:24px}
    .contactos-section h3{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .contactos-table{width:100%;border-collapse:collapse;background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .contactos-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .contactos-table td{padding:10px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .contactos-table tr:last-child td{border-bottom:none}
    .contactos-table tr:hover td{background:var(--purple-subtle)}
    .rol-badge{font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px;background:var(--purple-light);color:var(--purple);text-transform:capitalize}
    .btn-delete{padding:4px 10px;font-size:11px;border:1px solid var(--red);border-radius:6px;background:none;color:var(--red);cursor:pointer;font-family:inherit;font-weight:600}
    .btn-delete:hover{background:var(--red-bg)}
    .add-contact-form{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;margin-top:12px}
    .add-contact-form h4{font-size:14px;font-weight:600;color:var(--gray-text);margin-bottom:14px}
    .form-row-contact{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
    .form-row-contact .fg{display:flex;flex-direction:column;gap:4px;flex:1;min-width:140px}
    .form-row-contact .fg label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px}
    .form-row-contact .fg input,.form-row-contact .fg select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none}
    .form-row-contact .fg input:focus,.form-row-contact .fg select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-add{padding:8px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;white-space:nowrap}
    .btn-add:hover{background:var(--purple-dark)}
    .alert{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px}
    .alert-success{background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green)}
    .aviso-link{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--purple);text-decoration:none;font-weight:500;margin-top:8px}
    .aviso-link:hover{text-decoration:underline}

    @media (max-width: 768px) { .perfil-grid { grid-template-columns: 1fr; } .perfil-header { flex-wrap: wrap; } }
</style>
@endpush

@section('content')
    <div class="perfil-header">
        <form id="fotoForm" method="POST" action="{{ route('proveedores.perfil.foto') }}" enctype="multipart/form-data" style="display:inline;">
            @csrf
            <div class="perfil-avatar" onclick="document.getElementById('fotoInput').click()" title="Cambiar foto">
                @if($proveedor && $proveedor->foto)
                    <img src="{{ asset('storage/' . $proveedor->foto) }}" alt="Foto">
                @else
                    {{ strtoupper(substr($proveedor->nombre ?? session('proveedor_nombre', 'P'), 0, 1)) }}
                @endif
                <div class="avatar-overlay">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </div>
            </div>
            <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none;" onchange="document.getElementById('fotoForm').submit()">
        </form>
        <div>
            <div class="perfil-name">{{ $proveedor->nombre ?? session('proveedor_nombre', '—') }}</div>
            <div class="perfil-meta">
                ID Proveedor: {{ $proveedor->id_proveedor ?? session('proveedor_codigo', '—') }}
                · #{{ $proveedor->id ?? session('proveedor_id', '—') }}
                · {{ $proveedor->correo ?? session('proveedor_correo', '—') }}
            </div>
        </div>
    </div>

    @if(session('mensaje'))
        <div class="alert alert-success">{{ session('mensaje') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert" style="background:var(--red-bg);border:1px solid #fca5a5;color:var(--red)">
            <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="perfil-grid">
        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Información General
                <button type="button" class="btn-lapiz" id="btnEditarPerfil" title="Editar datos" onclick="toggleEditarPerfil(true)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </button>
            </h3>

            <div class="perfil-view {{ $errors->any() ? 'hidden' : '' }}" id="perfilView">
                <div class="info-row">
                    <span class="info-label">Nombre</span>
                    <span class="info-value">{{ $proveedor->nombre ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Usuario</span>
                    <span class="info-value">{{ $proveedor->usuario ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Correo</span>
                    <span class="info-value">{{ $proveedor->correo ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">{{ $proveedor->telefono ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tipo de persona</span>
                    <span class="info-value">{{ $proveedor->tipo_persona ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ID sistema</span>
                    <span class="info-value">#{{ $proveedor->id ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ID Proveedor</span>
                    <span class="info-value">{{ $proveedor->id_proveedor ?? '—' }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('proveedores.perfil.actualizar') }}" class="perfil-edit-form {{ $errors->any() ? 'open' : '' }}" id="perfilEditForm">
                @csrf
                <div class="edit-field">
                    <label>Nombre</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $proveedor->nombre ?? '') }}" required maxlength="255">
                </div>
                <div class="edit-field">
                    <label>Usuario (no editable)</label>
                    <input type="text" value="{{ $proveedor->usuario ?? '—' }}" disabled>
                </div>
                <div class="edit-field">
                    <label>Correo</label>
                    <input type="email" name="correo" value="{{ old('correo', $proveedor->correo ?? '') }}" required>
                </div>
                <div class="edit-field">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" value="{{ old('telefono', $proveedor->telefono ?? '') }}" required maxlength="20">
                </div>
                <div class="edit-field">
                    <label>Tipo de persona</label>
                    @php $tipo = old('tipo_persona', $proveedor->tipo_persona ?? ''); @endphp
                    <select name="tipo_persona" required>
                        <option value="Persona Física" {{ $tipo === 'Persona Física' ? 'selected' : '' }}>Persona Física</option>
                        <option value="Persona Moral" {{ $tipo === 'Persona Moral' ? 'selected' : '' }}>Persona Moral</option>
                    </select>
                </div>
                <div class="edit-field">
                    <label>Nueva contraseña (opcional)</label>
                    <input type="password" name="password" placeholder="Dejar vacío para no cambiar" autocomplete="new-password">
                </div>
                <div class="edit-field">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña" autocomplete="new-password">
                </div>
                <div class="edit-actions">
                    <button type="button" class="btn-cancel" onclick="toggleEditarPerfil(false)">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>

        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Estado de Cuenta
            </h3>
            <div class="info-row">
                <span class="info-label">Estado</span>
                <span class="info-value">
                    @if($proveedor && $proveedor->activo)
                        <span class="status-badge status-active">● Activo</span>
                    @else
                        <span class="status-badge status-inactive">● Inactivo</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Fuente de login</span>
                <span class="info-value">{{ session('proveedor_login_source', 'local') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Token API</span>
                <span class="info-value">{{ session('proveedor_token') ? 'Activo' : 'No disponible' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Miembro desde</span>
                <span class="info-value">{{ $proveedor && $proveedor->created_at ? $proveedor->created_at->format('d/m/Y') : '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Última actualización</span>
                <span class="info-value">{{ $proveedor && $proveedor->updated_at ? $proveedor->updated_at->format('d/m/Y H:i') : '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">ID interno</span>
                <span class="info-value">#{{ $proveedor->id ?? session('proveedor_id', '—') }}</span>
            </div>
        </div>
    </div>

    {{-- Contactos de la empresa --}}
    <div class="contactos-section">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Contactos de la empresa
        </h3>

        @php
            $nContactos = $contactos->count();
            $faltan = $faltanContactos ?? max(0, 2 - $nContactos);
        @endphp

        @if($faltan > 0)
            <div class="alert" style="background:#fff7ed;border:1px solid #fcd34d;color:#92400e;margin-bottom:14px;">
                <strong>Obligatorio:</strong> debes registrar mínimo <strong>2 contactos</strong>.
                Llevas {{ $nContactos }}/2 — falta{{ $faltan === 1 ? '' : 'n' }} {{ $faltan }}.
            </div>
        @else
            <div class="alert alert-success" style="margin-bottom:14px;">Ya tienes {{ $nContactos }} contactos (mínimo cumplido).</div>
        @endif

        @if(session('error_contacto'))
            <div class="alert" style="background:var(--red-bg);border:1px solid #fca5a5;color:var(--red)">{{ session('error_contacto') }}</div>
        @endif
        @if ($errors->any() && old('rol'))
            <div class="alert" style="background:var(--red-bg);border:1px solid #fca5a5;color:var(--red)">
                <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if($contactos->count())
        <table class="contactos-table">
            <thead>
                <tr><th>Nombre</th><th>Rol</th><th>Teléfono</th><th>Correo</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($contactos as $c)
                <tr>
                    <td style="font-weight:600">{{ $c->nombre }}</td>
                    <td><span class="rol-badge">{{ $c->rol }}</span></td>
                    <td>{{ $c->telefono ?? '—' }}</td>
                    <td>{{ $c->correo ?? '—' }}</td>
                    <td>
                        <button type="button" class="btn-delete" onclick="confirmarEliminar({{ $c->id }}, '{{ addslashes($c->nombre) }}')">Eliminar</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <p style="font-size:13px;color:var(--gray-muted);margin-bottom:12px">No hay contactos. Debes agregar al menos 2 para completar el onboarding.</p>
        @endif

        <div class="add-contact-form">
            <h4>Agregar contacto {{ $faltan > 0 ? '(obligatorio — '.$nContactos.'/2)' : '' }}</h4>
            <form method="POST" action="{{ route('proveedores.contactos.guardar') }}" id="formContacto">
                @csrf
                <div class="form-row-contact">
                    <div class="fg">
                        <label>Nombre <span style="color:#DC2626">*</span></label>
                        <input type="text" name="nombre" placeholder="Apellido(s) Nombre(s)" required maxlength="255" value="{{ old('nombre') }}">
                    </div>
                    <div class="fg">
                        <label>Rol <span style="color:#DC2626">*</span></label>
                        <select name="rol" required>
                            <option value="">Seleccionar…</option>
                            <option value="calidad" {{ old('rol')==='calidad'?'selected':'' }}>Calidad</option>
                            <option value="ventas" {{ old('rol')==='ventas'?'selected':'' }}>Ventas</option>
                            <option value="compras" {{ old('rol')==='compras'?'selected':'' }}>Compras</option>
                            <option value="logistica" {{ old('rol')==='logistica'?'selected':'' }}>Logística</option>
                            <option value="administracion" {{ old('rol')==='administracion'?'selected':'' }}>Administración</option>
                            <option value="produccion" {{ old('rol')==='produccion'?'selected':'' }}>Producción</option>
                            <option value="direccion" {{ old('rol')==='direccion'?'selected':'' }}>Dirección</option>
                            <option value="otro" {{ old('rol')==='otro'?'selected':'' }}>Otro</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Teléfono <span style="color:#DC2626">*</span></label>
                        <input type="tel" name="telefono" id="contacto_tel" placeholder="10 dígitos" required maxlength="10" inputmode="numeric" pattern="[0-9]{10}" value="{{ old('telefono') }}">
                    </div>
                    <div class="fg">
                        <label>Correo <span style="color:#DC2626">*</span></label>
                        <input type="email" name="correo" placeholder="correo@empresa.com" required value="{{ old('correo') }}">
                    </div>
                    <button type="submit" class="btn-add">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Aviso de privacidad --}}
    <div style="margin-top:24px;text-align:center;">

    {{-- Modal de confirmación para eliminar contacto --}}
    <div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:500;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:16px;padding:32px;max-width:400px;width:90%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.15);">
            <div style="width:48px;height:48px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h3 style="font-size:16px;font-weight:700;color:#1a1a2e;margin-bottom:4px">¿Eliminar contacto?</h3>
            <p id="deleteContactName" style="font-size:13px;color:#6b7280;margin-bottom:16px"></p>
            <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;text-align:left">Ingresa tu contraseña para confirmar</label>
            <input type="password" id="deletePassword" placeholder="Tu contraseña" style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none;margin-bottom:16px" onfocus="this.style.borderColor='#6B3FA0'" onblur="this.style.borderColor='#e5e7eb'">
            <div style="display:flex;gap:10px">
                <button onclick="cerrarDeleteModal()" style="flex:1;padding:10px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;color:#6b7280">Cancelar</button>
                <button onclick="ejecutarEliminar()" style="flex:1;padding:10px;border:none;border-radius:8px;background:#dc2626;color:#fff;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer">Eliminar</button>
            </div>
            <p id="deleteError" style="font-size:12px;color:#dc2626;margin-top:8px;display:none">Contraseña incorrecta</p>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display:none">
        @csrf @method('DELETE')
        <input type="hidden" name="password" id="deleteFormPassword">
    </form>

@endsection

@push('scripts')
<script>
function toggleEditarPerfil(abrir) {
    var view = document.getElementById('perfilView');
    var form = document.getElementById('perfilEditForm');
    var btn = document.getElementById('btnEditarPerfil');
    if (!view || !form) return;
    if (abrir) {
        view.classList.add('hidden');
        form.classList.add('open');
        if (btn) btn.style.display = 'none';
    } else {
        view.classList.remove('hidden');
        form.classList.remove('open');
        if (btn) btn.style.display = '';
    }
}

@if($errors->any())
document.addEventListener('DOMContentLoaded', function () {
    toggleEditarPerfil(true);
});
@endif

let deleteContactId = null;

function confirmarEliminar(id, nombre) {
    deleteContactId = id;
    document.getElementById('deleteContactName').textContent = 'Se eliminará a "' + nombre + '" de la lista de contactos.';
    document.getElementById('deletePassword').value = '';
    document.getElementById('deleteError').style.display = 'none';
    document.getElementById('deleteModal').style.display = 'flex';
    setTimeout(() => document.getElementById('deletePassword').focus(), 100);
}

function cerrarDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deleteContactId = null;
}

function ejecutarEliminar() {
    const pwd = document.getElementById('deletePassword').value;
    if (!pwd) {
        document.getElementById('deleteError').textContent = 'Ingresa tu contraseña';
        document.getElementById('deleteError').style.display = 'block';
        return;
    }
    const form = document.getElementById('deleteForm');
    form.action = '/proveedor/contactos/' + deleteContactId + '?password=' + encodeURIComponent(pwd);
    document.getElementById('deleteFormPassword').value = pwd;
    form.submit();
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) cerrarDeleteModal();
});

var telContacto = document.getElementById('contacto_tel');
if (telContacto) {
    telContacto.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
}
</script>
@endpush
