@extends('layouts.admin')
@section('title', 'Solicitudes de Alta')
@section('hero')
<div class="hero-band">
    <h1>Solicitudes de Alta</h1>
    <p>Proveedores que enviaron su formato de identificación para validación</p>
</div>
@endsection
@push('styles')
<style>
    .sol-grid{max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:16px}
    .sol-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px 24px;box-shadow:var(--shadow-sm);transition:var(--transition)}
    .sol-card:hover{box-shadow:var(--shadow-md)}
    .sol-card.pendiente{border-left:4px solid var(--amber)}
    .sol-card.aprobada{border-left:4px solid var(--green)}
    .sol-card.rechazada{border-left:4px solid var(--red)}
    .sol-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px}
    .sol-nombre{font-size:16px;font-weight:700;color:var(--gray-text)}
    .sol-badge{font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;text-transform:uppercase}
    .sol-badge.pendiente{background:var(--amber-bg);color:var(--amber)}
    .sol-badge.aprobada{background:var(--green-bg);color:var(--green)}
    .sol-badge.rechazada{background:var(--red-bg);color:var(--red)}
    .sol-meta{font-size:12px;color:var(--gray-muted);margin-bottom:12px}
    .sol-datos{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px 16px;font-size:12px;margin-bottom:14px}
    .sol-dato-label{font-weight:600;color:var(--gray-muted)}
    .sol-dato-value{color:var(--gray-text)}
    .sol-docs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
    .sol-doc-tag{font-size:11px;padding:3px 10px;border-radius:8px;background:var(--purple-light);color:var(--purple);font-weight:600}
    .sol-actions{display:flex;gap:8px;align-items:center}
    .btn-aprobar{padding:6px 16px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-rechazar{padding:6px 16px;background:var(--red);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-aprobar:hover{opacity:.9}.btn-rechazar:hover{opacity:.9}
    .sol-empty{text-align:center;padding:40px;color:var(--gray-muted);font-size:14px}
    @media(max-width:768px){.sol-datos{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')
<div class="sol-grid">

    @if(session('mensaje'))
        <div style="background:var(--green-bg);border:1px solid var(--green);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--green);font-weight:600;">
            {{ session('mensaje') }}
        </div>
    @endif

    @forelse($solicitudes as $sol)
        <div class="sol-card {{ $sol->estatus }}">
            <div class="sol-head">
                <div>
                    <div class="sol-nombre">{{ $sol->nombre_completo ?: '—' }}</div>
                    <div class="sol-meta">
                        {{ $sol->tipo_persona }} ·
                        Proveedor: {{ $sol->proveedor?->nombre ?? $sol->proveedor?->usuario ?? '#'.$sol->proveedor_id }} ·
                        Enviado: {{ $sol->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
                <span class="sol-badge {{ $sol->estatus }}">{{ ucfirst($sol->estatus) }}</span>
            </div>

            <div class="sol-datos">
                <div><span class="sol-dato-label">Correo:</span> <span class="sol-dato-value">{{ $sol->correo ?? '—' }}</span></div>
                <div><span class="sol-dato-label">Teléfono:</span> <span class="sol-dato-value">{{ $sol->telefono ?? '—' }}</span></div>
                <div><span class="sol-dato-label">Celular:</span> <span class="sol-dato-value">{{ $sol->celular ?? '—' }}</span></div>
                <div><span class="sol-dato-label">Dirección:</span> <span class="sol-dato-value">{{ implode(', ', array_filter([$sol->calle, $sol->num_exterior, $sol->colonia, $sol->municipio, $sol->estado])) ?: '—' }}</span></div>
                <div><span class="sol-dato-label">C.P.:</span> <span class="sol-dato-value">{{ $sol->cp ?? '—' }}</span></div>
                <div><span class="sol-dato-label">País:</span> <span class="sol-dato-value">{{ $sol->pais ?? '—' }}</span></div>
                <div><span class="sol-dato-label">CLABE:</span> <span class="sol-dato-value">{{ $sol->clabe ?? '—' }}</span></div>
                <div><span class="sol-dato-label">Cuenta:</span> <span class="sol-dato-value">{{ $sol->cuenta ?? '—' }}</span></div>
                <div><span class="sol-dato-label">Banco:</span> <span class="sol-dato-value">{{ $sol->banco ?? '—' }}</span></div>
                @if($sol->nombre_firma)
                    <div><span class="sol-dato-label">Firma:</span> <span class="sol-dato-value">{{ $sol->nombre_firma }}</span></div>
                @endif
            </div>

            @if($sol->docs_marcados && count($sol->docs_marcados))
                <div class="sol-docs">
                    @php
                        $docsLabels = [
                            'acta_constitutiva' => 'Acta Constitutiva',
                            'id_rep_legal' => 'ID Rep. Legal',
                            'id_contribuyente' => 'ID Contribuyente',
                            'constancia_fiscal' => 'CIF',
                            'opinion_cumplimiento' => 'Opinión SAT',
                            'caratula_banco' => 'Carátula Banco',
                        ];
                    @endphp
                    @foreach($sol->docs_marcados as $doc)
                        <span class="sol-doc-tag">{{ $docsLabels[$doc] ?? $doc }}</span>
                    @endforeach
                </div>
            @endif

            @if($sol->estatus === 'pendiente')
                <div class="sol-actions">
                    <a href="{{ route('admin.solicitudes.revisar', $sol) }}" class="btn-aprobar" style="background:var(--purple);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Revisar documentos
                    </a>
                    <form method="POST" action="{{ route('admin.solicitudes.aprobar', $sol) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-aprobar">✓ Aprobar</button>
                    </form>
                    <form method="POST" action="{{ route('admin.solicitudes.rechazar', $sol) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-rechazar">✕ Rechazar</button>
                    </form>
                </div>
            @endif

            @if($sol->notas_admin)
                <div style="margin-top:8px;font-size:12px;color:var(--gray-muted);font-style:italic;">Notas: {{ $sol->notas_admin }}</div>
            @endif
        </div>
    @empty
        <div class="sol-empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p style="margin-top:12px;">No hay solicitudes de alta pendientes.</p>
        </div>
    @endforelse
</div>
@endsection
