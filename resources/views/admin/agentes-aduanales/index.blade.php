@extends('layouts.admin')
@section('title', 'Agentes Aduanales')

@push('styles')
@include('admin.agentes-aduanales._estilos')
@endpush

@section('content')
@if(session('mensaje'))
<div class="aa-ok">{{ session('mensaje') }}</div>
@endif
@if($errors->any())
<div class="aa-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="aa-header">
    <div>
        <h2>Facturas de agentes aduanales</h2>
        <p class="aa-lead">Importación y exportación. {{ $facturas->total() }} {{ $facturas->total() === 1 ? 'factura en esta consulta' : 'facturas en esta consulta' }}.</p>
    </div>
    <div class="aa-actions">
        <a href="{{ route('admin.agentes-aduanales.anticipos.crear') }}" class="aa-btn aa-btn-sec">Registrar anticipo</a>
        <a href="{{ route('admin.agentes-aduanales.crear') }}" class="aa-btn">Registrar factura</a>
    </div>
</div>

<div class="aa-kpis">
    <div class="aa-kpi"><div class="num">{{ $kpis['facturas'] }}</div><div class="lbl">Facturas</div></div>
    <div class="aa-kpi pend"><div class="num">{{ $kpis['pendientes'] }}</div><div class="lbl">Pago pendiente</div></div>
    <div class="aa-kpi rev"><div class="num">{{ $kpis['canceladas'] }}</div><div class="lbl">Canceladas</div></div>
</div>

<form method="GET" action="{{ route('admin.agentes-aduanales') }}" class="aa-card">
    <h3>Filtros</h3>
    <div class="aa-filters">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Serie, folio o agente" aria-label="Buscar por serie, folio o agente">
        <select name="agente_id" aria-label="Agente aduanal">
            <option value="">Todos los agentes</option>
            @foreach($agentes as $agente)
            <option value="{{ $agente->id }}" @selected((string) request('agente_id') === (string) $agente->id)>{{ $agente->nombreVisible() }}</option>
            @endforeach
        </select>
        <input type="date" name="desde" value="{{ request('desde') }}" aria-label="Fecha desde">
        <input type="date" name="hasta" value="{{ request('hasta') }}" aria-label="Fecha hasta">
        <select name="moneda" aria-label="Moneda">
            <option value="">Moneda</option>
            @foreach(\App\Models\FacturaAgenteAduanal::MONEDAS as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected(request('moneda') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <select name="tipo" aria-label="Tipo de operación">
            <option value="">Importación o exportación</option>
            @foreach(\App\Models\OperacionComercioExterior::TIPOS as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected(request('tipo') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <select name="estado" aria-label="Estado del pago">
            <option value="">Estado del pago</option>
            @foreach(\App\Models\FacturaAgenteAduanal::ESTADOS_PAGO as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected(request('estado') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <select name="cancelacion" aria-label="Estado de cancelación">
            <option value="">Cancelación</option>
            @foreach(\App\Models\FacturaAgenteAduanal::ESTADOS_CANCELACION as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected(request('cancelacion') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <button type="submit" class="aa-btn">Filtrar</button>
        @if(request()->hasAny(['q', 'agente_id', 'desde', 'hasta', 'moneda', 'tipo', 'estado', 'cancelacion']))
        <a href="{{ route('admin.agentes-aduanales') }}" class="aa-link">Limpiar</a>
        @endif
    </div>
</form>

@if($facturas->isEmpty())
<div class="aa-card aa-empty">No hay facturas que coincidan con la búsqueda.</div>
@else
<div class="aa-table-wrap">
    <table class="aa-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Serie</th>
                <th>Folio</th>
                <th>Agente aduanal</th>
                <th>Operación</th>
                <th class="aa-num">Total</th>
                <th class="aa-num">Saldo pendiente</th>
                <th class="aa-num">Honorarios</th>
                <th class="aa-num">Impuestos</th>
                <th>Cancelación</th>
                <th>Estado del pago</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($facturas as $factura)
            <tr>
                <td>{{ $factura->fecha?->format('d/m/Y') ?: '—' }}</td>
                <td>{{ $factura->serie ?: '—' }}</td>
                <td><strong>{{ $factura->folio ?: '—' }}</strong></td>
                <td>{{ $factura->agente?->nombreVisible() ?: '—' }}</td>
                <td>{{ $factura->operacion?->tipoLabel() ?: '—' }}</td>
                <td class="aa-num">{{ $factura->textoMonto('importe') }}</td>
                <td class="aa-num">{{ $factura->textoMonto('saldo_pendiente') }}</td>
                <td class="aa-num">{{ $factura->textoMonto('honorarios') }}</td>
                <td class="aa-num">{{ $factura->textoMonto('impuestos') }}</td>
                <td><span class="aa-badge {{ $factura->estado_cancelacion === 'cancelada' ? 'aa-b-rev' : 'aa-b-activo' }}">{{ $factura->estadoCancelacionLabel() }}</span></td>
                <td><span class="aa-badge {{ $factura->estado_pago === 'pagado' ? 'aa-b-activo' : ($factura->estado_pago === 'parcial' ? 'aa-b-ok' : 'aa-b-pend') }}">{{ $factura->estadoPagoLabel() }}</span></td>
                <td><a class="aa-link" href="{{ route('admin.agentes-aduanales.ver', $factura) }}">Ver</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div style="margin-top:16px;">{{ $facturas->links() }}</div>
@endif

@if($anticiposSinFactura->isNotEmpty())
<div class="aa-card" style="margin-top:16px;">
    <h3>Anticipos ligados a una operación, todavía sin factura</h3>
    <div class="aa-table-wrap">
        <table class="aa-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Agente aduanal</th>
                    <th>Operación</th>
                    <th class="aa-num">Importe</th>
                    <th>Referencia</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($anticiposSinFactura as $anticipo)
                <tr>
                    <td>{{ $anticipo->fecha?->format('d/m/Y') ?: '—' }}</td>
                    <td>{{ $anticipo->agente?->nombreVisible() ?: '—' }}</td>
                    <td>{{ $anticipo->operacion?->tipoLabel() ?: '—' }}</td>
                    <td class="aa-num">{{ $anticipo->textoImporte() }}</td>
                    <td>{{ $anticipo->referencia ?: '—' }}</td>
                    <td>
                        @if($anticipo->operacion_id)
                        <a class="aa-link" href="{{ route('admin.agentes-aduanales.crear', ['operacion_id' => $anticipo->operacion_id]) }}">Registrar factura</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
