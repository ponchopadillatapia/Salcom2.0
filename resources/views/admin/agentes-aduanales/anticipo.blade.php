@extends('layouts.admin')
@section('title', 'Registrar anticipo')

@push('styles')
@include('admin.agentes-aduanales._estilos')
@endpush

@section('content')
<p style="margin-bottom:12px;"><a class="aa-back" href="{{ route('admin.agentes-aduanales') }}">← Facturas</a></p>

<div class="aa-header">
    <div>
        <h2>Registrar anticipo</h2>
        <p class="aa-lead">Queda ligado a la operación. Si esa operación ya tiene factura, también queda ligado a ella. El pago se registra después.</p>
    </div>
</div>

@if($errors->any())
<div class="aa-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('admin.agentes-aduanales.anticipos.guardar') }}" class="aa-card">
    @csrf
    <div class="aa-grid">
        <div class="aa-field">
            <label for="agente_aduanal_id">Agente aduanal <span class="aa-req">*</span></label>
            <select class="aa-select" id="agente_aduanal_id" name="agente_aduanal_id" required>
                <option value="">Selecciona</option>
                @foreach($agentes as $agente)
                <option value="{{ $agente->id }}" @selected((string) old('agente_aduanal_id') === (string) $agente->id)>{{ $agente->nombreVisible() }}</option>
                @endforeach
            </select>
        </div>
        <div class="aa-field">
            <label for="operacion_id">Operación existente</label>
            <select class="aa-select" id="operacion_id" name="operacion_id">
                <option value="">Crear la operación con este anticipo</option>
                @foreach($operaciones as $operacion)
                <option value="{{ $operacion->id }}" @selected((string) old('operacion_id') === (string) $operacion->id)>
                    {{ $operacion->agente?->nombreVisible() }} · {{ $operacion->tipoLabel() }} · {{ $operacion->fecha?->format('d/m/Y') ?: 'sin fecha' }}
                    {{ $operacion->factura ? '· con factura' : '' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="aa-field">
            <label for="tipo_operacion">Operación, si es nueva</label>
            <select class="aa-select" id="tipo_operacion" name="tipo_operacion">
                <option value="">Selecciona</option>
                @foreach(\App\Models\OperacionComercioExterior::TIPOS as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('tipo_operacion') === $clave)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="aa-field">
            <label for="fecha_anticipo">Fecha <span class="aa-req">*</span></label>
            <input class="aa-input" type="date" id="fecha_anticipo" name="fecha_anticipo" value="{{ old('fecha_anticipo') }}" required>
        </div>
        <div class="aa-field">
            <label for="importe_anticipo">Importe <span class="aa-req">*</span></label>
            <input class="aa-input" type="number" id="importe_anticipo" name="importe_anticipo" min="0.01" step="0.01" value="{{ old('importe_anticipo') }}" required>
        </div>
        <div class="aa-field">
            <label for="moneda_anticipo">Moneda <span class="aa-req">*</span></label>
            <select class="aa-select" id="moneda_anticipo" name="moneda_anticipo" required>
                <option value="">Selecciona</option>
                @foreach(\App\Models\FacturaAgenteAduanal::MONEDAS as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('moneda_anticipo') === $clave)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="aa-field">
            <label for="referencia_anticipo">Referencia</label>
            <input class="aa-input" type="text" id="referencia_anticipo" name="referencia_anticipo" value="{{ old('referencia_anticipo') }}" maxlength="80">
        </div>
        <div class="aa-field full">
            <label for="observaciones_anticipo">Observaciones</label>
            <textarea class="aa-textarea" id="observaciones_anticipo" name="observaciones_anticipo" rows="3">{{ old('observaciones_anticipo') }}</textarea>
        </div>
    </div>
    <div class="aa-actions" style="margin-top:18px;">
        <button type="submit" class="aa-btn">Ligar anticipo</button>
        <a class="aa-btn aa-btn-sec" href="{{ route('admin.agentes-aduanales') }}">Cancelar</a>
    </div>
</form>
@endsection
