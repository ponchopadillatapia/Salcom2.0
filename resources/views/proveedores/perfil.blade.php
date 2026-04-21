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
    .perfil-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: var(--white); flex-shrink: 0; }
    .perfil-name { font-size: 18px; font-weight: 700; color: var(--gray-text); }
    .perfil-meta { font-size: 13px; color: var(--gray-muted); margin-top: 2px; }
    .perfil-actions { margin-left: auto; }
    .btn-edit { padding: 8px 20px; border: 1px solid var(--purple); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all .15s; }
    .btn-edit:hover { background: var(--purple); color: var(--white); }

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
        <div class="perfil-avatar">{{ strtoupper(substr($proveedor->nombre ?? session('proveedor_nombre', 'P'), 0, 1)) }}</div>
        <div>
            <div class="perfil-name">{{ $proveedor->nombre ?? session('proveedor_nombre', '—') }}</div>
            <div class="perfil-meta">
                Código: {{ $proveedor->codigo_compras ?? session('proveedor_codigo', '—') }}
                · {{ $proveedor->correo ?? session('proveedor_correo', '—') }}
            </div>
        </div>
        <div class="perfil-actions">
            <a href="{{ route('proveedores.actualizacion') }}" class="btn-edit">Editar datos</a>
        </div>
    </div>

    <div class="perfil-grid">
        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Información General
            </h3>
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
                <span class="info-label">Código de compras</span>
                <span class="info-value">{{ $proveedor->codigo_compras ?? '—' }}</span>
            </div>
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

        @if(session('mensaje'))
            <div class="alert alert-success">{{ session('mensaje') }}</div>
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
                        <form method="POST" action="{{ route('proveedores.contactos.eliminar', $c) }}" onsubmit="return confirm('¿Eliminar este contacto?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-delete">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <p style="font-size:13px;color:var(--gray-muted);margin-bottom:12px">No hay contactos registrados. Agrega a tu equipo.</p>
        @endif

        <div class="add-contact-form">
            <h4>Agregar contacto</h4>
            <form method="POST" action="{{ route('proveedores.contactos.guardar') }}">
                @csrf
                <div class="form-row-contact">
                    <div class="fg">
                        <label>Nombre</label>
                        <input type="text" name="nombre" placeholder="Nombre completo" required>
                    </div>
                    <div class="fg">
                        <label>Rol</label>
                        <select name="rol" required>
                            <option value="">Seleccionar…</option>
                            <option value="calidad">Calidad</option>
                            <option value="ventas">Ventas</option>
                            <option value="compras">Compras</option>
                            <option value="logistica">Logística</option>
                            <option value="administracion">Administración</option>
                            <option value="produccion">Producción</option>
                            <option value="direccion">Dirección</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" placeholder="10 dígitos">
                    </div>
                    <div class="fg">
                        <label>Correo</label>
                        <input type="email" name="correo" placeholder="correo@empresa.com">
                    </div>
                    <button type="submit" class="btn-add">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Aviso de privacidad --}}
    <div style="margin-top:24px;text-align:center;">
        <a href="{{ route('aviso.privacidad') }}" class="aviso-link" target="_blank">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Aviso de Privacidad — Industrias Salcom S.A. de C.V.
        </a>
        @if($proveedor && $proveedor->aviso_privacidad_aceptado)
            <p style="font-size:11px;color:var(--green);margin-top:4px">✓ Aceptado el {{ $proveedor->aviso_privacidad_fecha?->format('d/m/Y H:i') }}</p>
        @else
            <form method="POST" action="{{ route('proveedores.aviso.aceptar') }}" style="margin-top:8px">
                @csrf
                <button type="submit" class="btn-add" style="font-size:12px;padding:6px 16px">Aceptar aviso de privacidad</button>
            </form>
        @endif
    </div>
@endsection
