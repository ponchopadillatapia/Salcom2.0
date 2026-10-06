@extends('layouts.admin')
@section('title', 'Agentes Aduanales')

@push('styles')
@include('admin.agentes-aduanales._estilos')
@endpush

@section('content')
@if(session('mensaje'))
<div class="aa-ok">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
<div class="aa-error">{{ session('error') }}</div>
@endif

<div class="aa-aviso">
    Catálogo interno de Industrias Salcom. Capturar una patente no comprueba que esté vigente. Este módulo no consulta al SAT, a la ANAM ni a CAAAREM, y no guarda e.firma, certificados ni llaves privadas.
</div>

<div class="aa-kpis">
    <div class="aa-kpi"><div class="num">{{ $kpis['total'] }}</div><div class="lbl">Agentes</div></div>
    <div class="aa-kpi"><div class="num">{{ $kpis['activos'] }}</div><div class="lbl">Activos</div></div>
    <div class="aa-kpi"><div class="num">{{ $kpis['sin_verificar'] }}</div><div class="lbl">Sin verificación</div></div>
</div>

<div class="aa-header">
    <div>
        <h2>Agentes Aduanales</h2>
        <p class="aa-mini" style="margin-top:4px;">{{ $agentes->total() }} {{ $agentes->total() === 1 ? 'resultado' : 'resultados' }}</p>
    </div>
    <a href="{{ route('admin.agentes-aduanales.crear') }}" class="aa-btn">+ Crear agente</a>
</div>

<form method="GET" action="{{ route('admin.agentes-aduanales') }}" class="aa-card">
    <div class="aa-filters">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, RFC, patente o agencia" aria-label="Buscar agente">
        <select name="estado" aria-label="Estado interno">
            <option value="">Estado interno</option>
            <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
            <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
        </select>
        <select name="verificacion" aria-label="Estado de verificación">
            <option value="">Verificación</option>
            @foreach(\App\Models\AgenteAduanal::VERIFICACIONES as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected(request('verificacion') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <select name="tipo" aria-label="Tipo de operación">
            <option value="">Tipo de operación</option>
            <option value="importacion" @selected(request('tipo') === 'importacion')>Importación</option>
            <option value="exportacion" @selected(request('tipo') === 'exportacion')>Exportación</option>
            <option value="ambas" @selected(request('tipo') === 'ambas')>Ambas</option>
        </select>
        <select name="aduana_id" aria-label="Aduana">
            <option value="">Todas las aduanas</option>
            @foreach($aduanas as $aduana)
            <option value="{{ $aduana->id }}" @selected((string) request('aduana_id') === (string) $aduana->id)>{{ $aduana->etiqueta() }}</option>
            @endforeach
        </select>
        <button type="submit" class="aa-btn">Filtrar</button>
        @if(request()->hasAny(['q', 'estado', 'verificacion', 'tipo', 'aduana_id']))
        <a href="{{ route('admin.agentes-aduanales') }}" class="aa-link">Limpiar</a>
        @endif
    </div>
</form>

@if($agentes->isEmpty())
<div class="aa-card aa-empty">No hay agentes que coincidan con la búsqueda.</div>
@else
<div class="aa-table-wrap">
    <table class="aa-table">
        <thead>
            <tr>
                <th>Agente</th>
                <th>Agencia</th>
                <th>RFC</th>
                <th>Patente</th>
                <th>Aduanas</th>
                <th>Operación</th>
                <th>Estado interno</th>
                <th>Verificación</th>
                <th>Última verificación</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($agentes as $agente)
            <tr>
                <td><strong>{{ $agente->nombre }}</strong></td>
                <td>{{ $agente->agencia ?: '—' }}</td>
                <td>{{ $agente->rfc }}</td>
                <td><strong>{{ $agente->numero_patente }}</strong></td>
                <td>
                    @if($agente->aduanas->isEmpty())
                    —
                    @else
                    {{ $agente->aduanas->take(2)->map->etiqueta()->join(' · ') }}
                    @if($agente->aduanas->count() > 2)
                    <span class="aa-mini">+{{ $agente->aduanas->count() - 2 }}</span>
                    @endif
                    @endif
                </td>
                <td>{{ $agente->tipoOperacionLabel() }}</td>
                <td>
                    <span class="aa-badge {{ $agente->activo ? 'aa-b-activo' : 'aa-b-inactivo' }}">{{ $agente->activo ? 'Activo' : 'Inactivo' }}</span>
                </td>
                <td>
                    <span class="aa-badge {{ $agente->estado_verificacion === 'verificado' ? 'aa-b-ok' : ($agente->estado_verificacion === 'no_localizado' ? 'aa-b-pend' : 'aa-b-no') }}">{{ $agente->verificacionLabel() }}</span>
                    @if($agente->fuenteLabel())
                    <div class="aa-mini">{{ $agente->fuenteLabel() }}</div>
                    @endif
                </td>
                <td>{{ $agente->fecha_ultima_verificacion?->format('d/m/Y') ?: '—' }}</td>
                <td>
                    <div class="aa-links">
                        <a class="aa-link" href="{{ route('admin.agentes-aduanales.ver', $agente) }}">Ver</a>
                        <a class="aa-link" href="{{ route('admin.agentes-aduanales.editar', $agente) }}">Editar</a>
                        @if($agente->activo)
                        <form method="POST" action="{{ route('admin.agentes-aduanales.desactivar', $agente) }}" onsubmit="return confirm('¿Desactivar a este agente? Sus documentos se conservan.');">
                            @csrf
                            <button type="submit" class="aa-link" style="color:#b45309;">Desactivar</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('admin.agentes-aduanales.reactivar', $agente) }}">
                            @csrf
                            <button type="submit" class="aa-link" style="color:#047857;">Reactivar</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div style="margin-top:16px;">{{ $agentes->links() }}</div>
@endif
@endsection
