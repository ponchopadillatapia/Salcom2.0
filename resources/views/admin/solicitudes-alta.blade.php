@extends('layouts.admin')

@section('title', 'Solicitudes de alta')

@section('hero')
<div class="hero-band">
    <h1>Solicitudes de alta</h1>
    <p>Contabilidad y Dirección revisan a mano el formulario, bancarios y documentos antes de aprobar.</p>
</div>
@endsection

@push('styles')
<style>
    .sa-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; align-items: center; }
    .sa-filter {
        padding: 8px 14px; font-size: 12px; font-weight: 600; border: 1.5px solid var(--border);
        border-radius: 8px; background: var(--white); color: var(--gray-text); text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .sa-filter:hover { border-color: var(--purple); color: var(--purple); }
    .sa-filter.active { background: var(--purple); color: #fff; border-color: var(--purple); }
    .sa-count { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: rgba(0,0,0,.08); }
    .sa-filter.active .sa-count { background: rgba(255,255,255,.25); }

    .sa-panel { background: var(--white); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
    .sa-table { width: 100%; border-collapse: collapse; }
    .sa-table th {
        font-size: 11px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase;
        letter-spacing: .4px; padding: 12px 16px; text-align: left; background: var(--gray-soft);
        border-bottom: 1px solid var(--border);
    }
    .sa-table td { padding: 14px 16px; font-size: 13px; color: var(--gray-text); border-bottom: 1px solid var(--border); vertical-align: middle; }
    .sa-table tr:last-child td { border-bottom: none; }
    .sa-table tbody tr:hover td { background: var(--purple-subtle); }

    .sa-nombre { font-weight: 700; color: var(--gray-text); }
    .sa-meta { font-size: 12px; color: var(--gray-muted); margin-top: 2px; }

    .sa-paso { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 999px; margin: 2px 2px 2px 0; white-space: nowrap; }
    .sa-paso.ok { background: var(--green-bg); color: var(--green); }
    .sa-paso.no { background: var(--amber-bg); color: var(--amber); }

    .sa-link {
        display: inline-block; padding: 8px 14px; background: var(--purple); color: #fff; border-radius: 8px;
        font-size: 12px; font-weight: 700; text-decoration: none;
    }
    .sa-link:hover { filter: brightness(.95); color: #fff; }

    .sa-empty { padding: 40px 24px; text-align: center; color: var(--gray-muted); font-size: 14px; }
    .sa-flash { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }
    .sa-flash.ok { background: var(--green-bg); color: var(--green); border: 1px solid var(--green); }
    .sa-flash.err { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

    @media (max-width: 900px) {
        .sa-table { display: block; overflow-x: auto; }
    }
</style>
@endpush

@section('content')

@if(session('mensaje'))
<div class="sa-flash ok">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
<div class="sa-flash err">{{ session('error') }}</div>
@endif

<div class="sa-toolbar">
    <a href="{{ route('admin.solicitudes-alta', ['filtro' => 'todas']) }}" class="sa-filter {{ ($filtro ?? 'todas') === 'todas' ? 'active' : '' }}">
        Todas <span class="sa-count">{{ ($conteoConDatos ?? 0) + ($conteoSinDatos ?? 0) }}</span>
    </a>
    <a href="{{ route('admin.solicitudes-alta', ['filtro' => 'con_datos']) }}" class="sa-filter {{ ($filtro ?? '') === 'con_datos' ? 'active' : '' }}">
        Con datos enviados <span class="sa-count">{{ $conteoConDatos ?? 0 }}</span>
    </a>
    <a href="{{ route('admin.solicitudes-alta', ['filtro' => 'sin_datos']) }}" class="sa-filter {{ ($filtro ?? '') === 'sin_datos' ? 'active' : '' }}">
        Sin datos aún <span class="sa-count">{{ $conteoSinDatos ?? 0 }}</span>
    </a>
</div>

<div class="sa-panel">
    @if(($pendientes ?? collect())->isEmpty())
        <div class="sa-empty">No hay solicitudes con este filtro.</div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Registro</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendientes as $row)
            @php $p = $row->proveedor; @endphp
            <tr>
                <td>
                    <div class="sa-nombre">{{ $p->nombre ?? $p->usuario }}</div>
                    <div class="sa-meta">
                        #{{ $p->id }}
                        @if($p->id_proveedor) · ID {{ $p->id_proveedor }}@endif
                        · {{ $p->correo }}
                    </div>
                    <div class="sa-meta">{{ $p->tipo_persona }} · {{ $p->telefono }}</div>
                </td>
                <td>
                    <div class="sa-meta">{{ optional($p->created_at)->format('d/m/Y H:i') ?? '—' }}</div>
                </td>
                <td>
                    <a href="{{ route('admin.solicitudes-alta.detalle', $p) }}" class="sa-link">Revisar</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
