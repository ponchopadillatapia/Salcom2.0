@extends('layouts.admin')
@section('title', 'Reorden de Materia Prima')
@section('hero')
<div class="hero-band">
    <h1>Reorden de Materia Prima</h1>
    <p>Órdenes de compra automáticas pendientes de aprobación</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}
    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:100px}
    .adm-summary-pct{font-size:42px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .adm-summary-metrics{flex:1;display:flex;gap:24px;flex-wrap:wrap}
    .adm-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .adm-metric-val{font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}

    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.urgente{background:var(--red-bg);color:var(--red)}
    .badge-est.normal{background:var(--green-bg);color:var(--green)}

    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:var(--transition)}
    .btn-primary:hover{opacity:.9;transform:scale(1.02)}
    .btn-primary:active{transform:scale(.97)}

    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green);font-weight:500}
    .alert-error{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--red-bg);border:1px solid var(--red);color:var(--red);font-weight:500}

    .action-cards{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
    .action-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px}
    .action-card h4{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:12px}
    .action-card p{font-size:12px;color:var(--gray-muted);margin-bottom:14px}

    .file-input-wrap{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .file-input-wrap input[type="file"]{font-size:12px;font-family:inherit;color:var(--gray-text)}

    .link-detail{color:var(--purple);font-weight:600;text-decoration:none;font-size:12px}
    .link-detail:hover{text-decoration:underline}

    @media(max-width:768px){
        .action-cards{grid-template-columns:1fr}
        .admin-table-wrap{overflow-x:auto}
        .adm-summary{flex-direction:column;align-items:flex-start}
    }
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
    <div class="alert-error">{{ session('error') }}</div>
@endif

{{-- Resumen superior --}}
<div class="adm-summary anim">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ $totalPendientes }}</div>
        <div class="adm-summary-label">OC Pendientes</div>
    </div>
    <div class="adm-summary-metrics">
        <div>
            <div class="adm-metric-label">Monto total estimado</div>
            <div class="adm-metric-val" style="color:var(--purple)">${{ number_format($montoTotalEstimado, 2) }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Productos urgentes</div>
            <div class="adm-metric-val" style="color:{{ $productosUrgentes > 0 ? 'var(--red)' : 'var(--green)' }}">{{ $productosUrgentes }}</div>
        </div>
    </div>
</div>

{{-- Acciones: Ejecutar reorden e Importar stock mínimos --}}
<div class="action-cards anim" style="animation-delay:.04s">
    <div class="action-card">
        <h4>Ejecutar proceso de reorden</h4>
        <p>Evalúa todos los productos activos y genera OC automáticas para los que requieran reorden.</p>
        <form method="POST" action="{{ route('admin.reorden-oc.ejecutar') }}">
            @csrf
            <button type="submit" class="btn-primary" onclick="return confirm('¿Ejecutar el proceso de reorden ahora?')">
                Ejecutar reorden
            </button>
        </form>
    </div>
    <div class="action-card">
        <h4>Importar stock mínimos</h4>
        <p>Archivo Excel/CSV con columnas: Código de producto (A) y Stock mínimo (B).</p>
        <form method="POST" action="{{ route('admin.reorden-oc.importar-minimos') }}" enctype="multipart/form-data">
            @csrf
            <div class="file-input-wrap">
                <input type="file" name="archivo" accept=".xlsx,.xls,.csv" required>
                <button type="submit" class="btn-primary">Importar</button>
            </div>
        </form>
    </div>
</div>

{{-- Tabla de OC pendientes --}}
<div class="admin-table-wrap anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Órdenes de Compra Pendientes</h4>
            <div class="adm-section-meta">{{ $totalPendientes }} órdenes generadas automáticamente</div>
        </div>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Productos</th>
                <th>Monto estimado</th>
                <th>Fecha</th>
                <th>Urgencia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($ordenes as $oc)
            @php
                $productos = $oc->productos ?? [];
                $cantidadProductos = count($productos);
                $tieneUrgente = collect($productos)->where('urgente', true)->count() > 0;
            @endphp
            <tr>
                <td style="font-weight:600;">{{ $oc->proveedor->nombre ?? $oc->proveedor->usuario ?? '—' }}</td>
                <td style="font-weight:600;font-variant-numeric:tabular-nums">{{ $cantidadProductos }}</td>
                <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($oc->monto_estimado, 2) }}</td>
                <td>{{ $oc->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    @if($tieneUrgente)
                        <span class="badge-est urgente">Urgente</span>
                    @else
                        <span class="badge-est normal">Normal</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.reorden-oc.show', $oc->id) }}" class="link-detail">Ver detalle →</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:32px;color:var(--gray-muted);">
                    No hay órdenes de compra pendientes. Ejecuta el proceso de reorden para generar nuevas OC.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
