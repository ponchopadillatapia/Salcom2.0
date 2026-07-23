@extends('layouts.admin')
@section('title', 'Expediente de Documentos Fiscales')
@section('hero')
<div class="hero-band">
    <h1>Expediente de Documentos Fiscales</h1>
    <p>Proveedores con documentos — entra a ver su expediente mes por mes</p>
</div>
@endsection
@push('styles')
<style>
    .exp-wrap{max-width:980px;margin:0 auto}
    .exp-filtros{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
    .exp-filtros select,.exp-filtros input{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:#fff}
    .exp-row{
        display:grid;grid-template-columns:1fr auto auto;gap:12px 16px;align-items:center;
        background:var(--white);border:1px solid var(--border-light);border-radius:10px;
        padding:14px 16px;margin-bottom:8px;text-decoration:none;color:inherit;
        transition:box-shadow .15s, border-color .15s;
    }
    .exp-row:hover{border-color:var(--purple-mid, #c4b5fd);box-shadow:0 2px 10px rgba(0,0,0,.06)}
    .exp-nombre{font-size:14px;font-weight:700;color:var(--purple);margin:0}
    .exp-meta{font-size:12px;color:var(--gray-muted);margin-top:3px}
    .exp-stats{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}
    .exp-pill{font-size:10px;font-weight:700;padding:3px 8px;border-radius:999px}
    .exp-pill.ok{background:var(--green-bg);color:var(--green)}
    .exp-pill.pend{background:var(--amber-bg);color:var(--amber)}
    .exp-pill.rej{background:var(--red-bg);color:var(--red)}
    .exp-pill.tot{background:var(--purple-subtle,#f3e8ff);color:var(--purple)}
    .exp-hora{font-size:11px;color:var(--gray-muted);white-space:nowrap;text-align:right}
    .exp-empty{text-align:center;padding:40px;color:var(--gray-muted);background:var(--white);border-radius:12px;border:1px solid var(--border-light)}
    @media(max-width:700px){
        .exp-row{grid-template-columns:1fr;gap:8px}
        .exp-stats,.exp-hora{justify-content:flex-start;text-align:left}
    }
</style>
@endpush
@section('content')
<div class="exp-wrap">

    <div class="exp-filtros">
        <form method="GET" action="{{ route('admin.expediente-fiscal') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;width:100%;">
            <input type="text" name="busqueda" value="{{ request('busqueda') }}" placeholder="Buscar proveedor...">
            <select name="persona">
                <option value="">Todas las personas</option>
                <option value="fisica" {{ request('persona') === 'fisica' ? 'selected' : '' }}>Persona Física</option>
                <option value="moral" {{ request('persona') === 'moral' ? 'selected' : '' }}>Persona Moral</option>
            </select>
            <select name="tipo">
                <option value="">Todos los tipos</option>
                @foreach($tipos as $t => $label)
                    <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="estatus">
                <option value="">Todos los estatus</option>
                <option value="aprobado" {{ request('estatus') === 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                <option value="pendiente" {{ request('estatus') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="rechazado" {{ request('estatus') === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
            </select>
            <select name="mes">
                <option value="">Todos los meses</option>
                @foreach($mesesDisponibles as $ym)
                    @php
                        try {
                            $labelMes = ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $ym)->locale('es')->translatedFormat('F Y'));
                        } catch (\Throwable $e) {
                            $labelMes = $ym;
                        }
                    @endphp
                    <option value="{{ $ym }}" {{ request('mes') === $ym ? 'selected' : '' }}>{{ $labelMes }}</option>
                @endforeach
            </select>
            <button type="submit" style="padding:8px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Filtrar</button>
            @if(request()->hasAny(['busqueda','tipo','persona','mes','estatus']))
                <a href="{{ route('admin.expediente-fiscal') }}" style="font-size:12px;color:var(--purple);font-weight:600;">Limpiar</a>
            @endif
        </form>
    </div>

    @forelse($proveedoresConDocs as $item)
        @php $p = $item['proveedor']; @endphp
        <a class="exp-row" href="{{ route('admin.expediente-fiscal.ver', $p) }}?{{ http_build_query(request()->only(['busqueda','persona','tipo','mes','estatus'])) }}">
            <div>
                <p class="exp-nombre">{{ $p->nombre ?? $p->usuario }}</p>
                <div class="exp-meta">
                    Código: {{ $p->id_proveedor ?? '—' }}
                    · {{ $p->tipo_persona ?? '—' }}
                    · {{ $item['total'] }} documento(s)
                    @if(($item['meses'] ?? 0) > 1)
                        · {{ $item['meses'] }} meses
                    @endif
                </div>
            </div>
            <div class="exp-stats">
                <span class="exp-pill tot">{{ $item['total'] }} docs</span>
                @if($item['aprobados'] > 0)<span class="exp-pill ok">{{ $item['aprobados'] }} aprob.</span>@endif
                @if($item['pendientes'] > 0)<span class="exp-pill pend">{{ $item['pendientes'] }} pend.</span>@endif
                @if($item['rechazados'] > 0)<span class="exp-pill rej">{{ $item['rechazados'] }} rech.</span>@endif
            </div>
            <div class="exp-hora">
                {{ $item['ultimo_at'] ? $item['ultimo_at']->format('d/m/Y') : '—' }}
                <div style="margin-top:2px;">{{ $item['ultimo_at'] ? $item['ultimo_at']->format('h:i a') : '' }}</div>
            </div>
        </a>
    @empty
        <div class="exp-empty">
            <p style="font-weight:600;margin:0 0 6px;">Sin proveedores con documentos</p>
            <p style="margin:0;font-size:13px;">Ajusta los filtros o espera a que un proveedor valide su expediente.</p>
        </div>
    @endforelse
</div>
@endsection
