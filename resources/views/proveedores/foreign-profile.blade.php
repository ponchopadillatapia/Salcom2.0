@extends('layouts.proveedor-extranjero')
@section('title', 'My Profile')

@section('content')
<div style="max-width:720px;margin:0 auto;">
    <h1 style="font-size:22px;font-weight:700;color:var(--gray-text);margin-bottom:4px;">My Profile</h1>
    <p style="font-size:14px;color:var(--gray-muted);margin-bottom:22px;">Your account information.</p>

    <div style="background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px;box-shadow:var(--shadow-sm);">
        @php
            $di = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];
        @endphp
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;font-size:13px;">
            <div><div style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;">Company name</div><div>{{ $proveedor->nombre ?: '—' }}</div></div>
            <div><div style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;">Tax ID number</div><div>{{ $perfil->tax_id_number ?? ($di['tax_id_number'] ?? '—') }}</div></div>
            <div><div style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;">Username</div><div>{{ $proveedor->usuario ?: '—' }}</div></div>
            <div><div style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;">Email</div><div>{{ $proveedor->correo ?: '—' }}</div></div>
            <div><div style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;">Telephone</div><div>{{ $proveedor->telefono ?: '—' }}</div></div>
            <div><div style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;">Status</div><div>{{ $proveedor->activo ? 'Active' : 'Under review' }}</div></div>
        </div>
    </div>

    <form method="POST" action="{{ route('proveedores.perfil.actualizar') }}" style="background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px;box-shadow:var(--shadow-sm);margin-top:16px;">
        @csrf
        <h3 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:16px;">Update contact</h3>
        {{-- Reusa el endpoint de actualización de perfil existente; solo mandamos los campos editables. --}}
        <input type="hidden" name="nombre" value="{{ $proveedor->nombre }}">
        <input type="hidden" name="tipo_persona" value="{{ $proveedor->tipo_persona }}">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--gray-muted);margin-bottom:6px;">Telephone</label>
                <input type="text" name="telefono" value="{{ $proveedor->telefono }}" required style="width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:var(--gray-muted);margin-bottom:6px;">Email</label>
                <input type="email" name="correo" value="{{ $proveedor->correo }}" required style="width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;">
            </div>
        </div>
        <button type="submit" style="padding:10px 22px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">Save changes</button>
    </form>
</div>
@endsection
