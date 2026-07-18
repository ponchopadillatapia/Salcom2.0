@extends('layouts.admin')
@section('title', 'Expediente de Documentos Fiscales')
@section('hero')
<div class="hero-band">
    <h1>Expediente de Documentos Fiscales</h1>
    <p>Documentos subidos por proveedores — descarga y valida su información</p>
</div>
@endsection
@push('styles')
<style>
    .exp-wrap{max-width:1100px;margin:0 auto}
    .exp-filtros{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
    .exp-filtros select,.exp-filtros input{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit}
    .exp-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px 24px;margin-bottom:14px;box-shadow:var(--shadow-sm)}
    .exp-card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
    .exp-card-nombre{font-size:15px;font-weight:700;color:var(--gray-text)}
    .exp-card-meta{font-size:12px;color:var(--gray-muted)}
    .exp-docs{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px}
    .exp-doc{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:var(--gray-soft);border-radius:10px;font-size:12px}
    .exp-doc-info{display:flex;align-items:center;gap:8px}
    .exp-doc-tipo{font-weight:600;color:var(--gray-text)}
    .exp-doc-fecha{color:var(--gray-muted);font-size:11px}
    .exp-doc-badge{font-size:10px;font-weight:700;padding:3px 8px;border-radius:999px}
    .exp-doc-badge.pendiente{background:var(--amber-bg);color:var(--amber)}
    .exp-doc-badge.aprobado{background:var(--green-bg);color:var(--green)}
    .exp-doc-badge.rechazado{background:var(--red-bg);color:var(--red)}
    .btn-descargar{padding:5px 12px;border:1.5px solid var(--purple);border-radius:8px;background:none;color:var(--purple);font-size:11px;font-weight:600;text-decoration:none;transition:var(--transition);display:inline-flex;align-items:center;gap:4px}
    .btn-descargar:hover{background:var(--purple);color:#fff}
    .exp-empty{text-align:center;padding:40px;color:var(--gray-muted)}
</style>
@endpush
@section('content')
<div class="exp-wrap">

    <div class="exp-filtros">
        <form method="GET" action="{{ route('admin.expediente-fiscal') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <input type="text" name="busqueda" value="{{ request('busqueda') }}" placeholder="Buscar proveedor...">
            <select name="persona">
                <option value="">Todos</option>
                <option value="fisica" {{ request('persona') === 'fisica' ? 'selected' : '' }}>Persona Física</option>
                <option value="moral" {{ request('persona') === 'moral' ? 'selected' : '' }}>Persona Moral</option>
            </select>
            <select name="tipo">
                <option value="">Todos los tipos</option>
                @foreach($tipos as $t => $label)
                    <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" style="padding:8px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Filtrar</button>
            @if(request()->hasAny(['busqueda','tipo','persona']))
                <a href="{{ route('admin.expediente-fiscal') }}" style="font-size:12px;color:var(--purple);font-weight:600;">Limpiar</a>
            @endif
        </form>
    </div>

    @forelse($proveedoresConDocs as $item)
        <div class="exp-card">
            <div class="exp-card-head">
                <div>
                    <div class="exp-card-nombre">{{ $item['proveedor']->nombre ?? $item['proveedor']->usuario }}</div>
                    <div class="exp-card-meta">Código: {{ $item['proveedor']->id_proveedor ?? '—' }} · {{ $item['proveedor']->tipo_persona ?? '—' }} · {{ $item['documentos']->count() }} documento(s)</div>
                </div>
            </div>
            <div class="exp-docs">
                @foreach($item['documentos'] as $doc)
                    <div class="exp-doc">
                        <div class="exp-doc-info">
                            <span class="exp-doc-badge {{ $doc->estatus }}">{{ ucfirst($doc->estatus) }}</span>
                            <div>
                                <div class="exp-doc-tipo">{{ $tipos[$doc->tipo] ?? ucfirst($doc->tipo) }}</div>
                                <div class="exp-doc-fecha">{{ $doc->created_at->format('d/m/Y') }}</div>
                            </div>
                        </div>
                        <a href="{{ route('admin.expediente-fiscal.descargar', $doc) }}" class="btn-descargar">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Descargar
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="exp-empty">
            <p>No hay documentos fiscales subidos por proveedores.</p>
        </div>
    @endforelse
</div>
@endsection
