@extends('layouts.admin')
@section('title', 'Factura '.$factura->serieFolio())

@push('styles')
@include('admin.agentes-aduanales._estilos')
@endpush

@section('content')
<p style="margin-bottom:12px;"><a class="aa-back" href="{{ route('admin.agentes-aduanales') }}">← Facturas</a></p>

@if(session('mensaje'))
<div class="aa-ok">{{ session('mensaje') }}</div>
@endif
@if($errors->any())
<div class="aa-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if(session('pdf_avisos'))
<div class="aa-aviso">
    @foreach(session('pdf_avisos') as $aviso)
    <div>{{ $aviso }}</div>
    @endforeach
</div>
@endif

<div class="aa-header">
    <div>
        <h2>{{ $factura->serieFolio() }}</h2>
        <p class="aa-lead">{{ $factura->agente?->nombreVisible() }} · {{ $factura->operacion?->tipoLabel() }} · {{ $factura->fecha?->format('d/m/Y') ?: 'sin fecha' }}</p>
        <div class="aa-badges">
            <span class="aa-badge {{ $factura->estado_cancelacion === 'cancelada' ? 'aa-b-rev' : 'aa-b-activo' }}">{{ $factura->estadoCancelacionLabel() }}</span>
            <span class="aa-badge {{ $factura->estado_pago === 'pagado' ? 'aa-b-activo' : ($factura->estado_pago === 'parcial' ? 'aa-b-ok' : 'aa-b-pend') }}">{{ $factura->estadoPagoLabel() }}</span>
        </div>
    </div>
    <a class="aa-btn aa-btn-sec" href="{{ route('admin.agentes-aduanales.editar', $factura) }}">Editar factura</a>
</div>

<div class="aa-tiles">
    <div class="aa-tile principal"><div class="lbl">Total</div><div class="val">{{ $factura->textoMonto('importe') }}</div></div>
    <div class="aa-tile"><div class="lbl">Saldo pendiente</div><div class="val">{{ $factura->textoMonto('saldo_pendiente') }}</div></div>
    <div class="aa-tile"><div class="lbl">Honorarios</div><div class="val">{{ $factura->textoMonto('honorarios') }}</div></div>
    <div class="aa-tile"><div class="lbl">Impuestos</div><div class="val">{{ $factura->textoMonto('impuestos') }}</div></div>
</div>

<div class="aa-card">
    <h3>Datos de la factura</h3>
    <dl class="aa-dl">
        <dt>Fecha</dt><dd>{{ $factura->fecha?->format('d/m/Y') ?: '—' }}</dd>
        <dt>Serie y folio</dt><dd>{{ $factura->serieFolio() }}</dd>
        <dt>Agente aduanal</dt><dd>{{ $factura->agente?->nombreVisible() ?: '—' }}</dd>
        <dt>Operación</dt><dd>{{ $factura->operacion?->tipoLabel() ?: '—' }}</dd>
        <dt>Archivo</dt>
        <dd>
            @if($factura->ruta)
            <a class="aa-link" href="{{ route('admin.agentes-aduanales.archivo', $factura) }}">{{ $factura->nombre_archivo ?: 'Descargar' }}</a>
            @else
            —
            @endif
        </dd>
    </dl>
</div>

<div class="aa-card">
    <h3>Anticipos</h3>
    @if($anticipos->isEmpty())
    <p class="aa-help" style="margin-bottom:14px;">Todavía no hay un anticipo ligado. Se puede registrar aquí, antes del pago.</p>
    @else
    <div class="aa-table-wrap" style="margin-bottom:14px;">
        <table class="aa-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="aa-num">Importe</th>
                    <th>Referencia</th>
                    <th>Ligado a</th>
                </tr>
            </thead>
            <tbody>
                @foreach($anticipos as $anticipo)
                <tr>
                    <td>{{ $anticipo->fecha?->format('d/m/Y') ?: '—' }}</td>
                    <td class="aa-num">{{ $anticipo->textoImporte() }}</td>
                    <td>{{ $anticipo->referencia ?: '—' }}</td>
                    <td>
                        @if($anticipo->factura_id && $anticipo->operacion_id)
                        Factura y operación
                        @elseif($anticipo->factura_id)
                        Factura
                        @else
                        Operación
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.agentes-aduanales.anticipos.ligar', $factura) }}">
        @csrf
        <div class="aa-grid">
            <div class="aa-field">
                <label for="fecha_anticipo">Fecha del anticipo</label>
                <input class="aa-input" type="date" id="fecha_anticipo" name="fecha_anticipo" value="{{ old('fecha_anticipo') }}" required>
            </div>
            <div class="aa-field">
                <label for="importe_anticipo">Importe</label>
                <input class="aa-input" type="number" id="importe_anticipo" name="importe_anticipo" min="0.01" step="0.01" value="{{ old('importe_anticipo') }}" required>
            </div>
            <div class="aa-field">
                <label for="moneda_anticipo">Moneda</label>
                <select class="aa-select" id="moneda_anticipo" name="moneda_anticipo" required>
                    @foreach(\App\Models\FacturaAgenteAduanal::MONEDAS as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('moneda_anticipo', $factura->moneda) === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="referencia_anticipo">Referencia</label>
                <input class="aa-input" type="text" id="referencia_anticipo" name="referencia_anticipo" value="{{ old('referencia_anticipo') }}" maxlength="80">
            </div>
            <div class="aa-field full">
                <label for="observaciones_anticipo">Observaciones</label>
                <textarea class="aa-textarea" id="observaciones_anticipo" name="observaciones_anticipo" rows="2">{{ old('observaciones_anticipo') }}</textarea>
            </div>
        </div>
        <button type="submit" class="aa-btn" style="margin-top:12px;">Ligar anticipo</button>
    </form>
</div>

<div class="aa-card">
    <h3>Pagos</h3>
    <p class="aa-help" style="margin-bottom:12px;">En cada pago se elige quién paga. El saldo de la factura se queda como está.</p>
    @if($factura->pagos->isEmpty())
    <p class="aa-mini" style="margin-bottom:12px;">Todavía no hay pagos registrados.</p>
    @else
    <div class="aa-table-wrap" style="margin-bottom:14px;">
        <table class="aa-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="aa-num">Importe</th>
                    <th>Dirección</th>
                    <th>Anticipo</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->pagos as $pago)
                <tr>
                    <td>{{ $pago->fecha?->format('d/m/Y') ?: '—' }}</td>
                    <td class="aa-num">{{ $pago->textoImporte() }}</td>
                    <td>{{ $pago->direccionLabel() }}</td>
                    <td>{{ $pago->anticipo ? $pago->anticipo->textoImporte() : '—' }}</td>
                    <td>{{ $pago->referencia ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.agentes-aduanales.pagos.guardar', $factura) }}">
        @csrf
        <div class="aa-grid">
            <div class="aa-field">
                <label for="fecha_pago">Fecha del pago</label>
                <input class="aa-input" type="date" id="fecha_pago" name="fecha_pago" value="{{ old('fecha_pago') }}" required>
            </div>
            <div class="aa-field">
                <label for="importe_pago">Importe</label>
                <input class="aa-input" type="number" id="importe_pago" name="importe_pago" min="0.01" step="0.01" value="{{ old('importe_pago') }}" required>
            </div>
            <div class="aa-field">
                <label for="moneda_pago">Moneda</label>
                <select class="aa-select" id="moneda_pago" name="moneda_pago" required>
                    @foreach(\App\Models\FacturaAgenteAduanal::MONEDAS as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('moneda_pago', $factura->moneda) === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="direccion">Quién paga <span class="aa-req">*</span></label>
                <select class="aa-select" id="direccion" name="direccion" required>
                    <option value="">Selecciona</option>
                    @foreach(\App\Models\PagoAgenteAduanal::DIRECCIONES as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('direccion') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="anticipo_id">Anticipo ya ligado</label>
                <select class="aa-select" id="anticipo_id" name="anticipo_id">
                    <option value="">Sin anticipo</option>
                    @foreach($anticipos as $anticipo)
                    <option value="{{ $anticipo->id }}" @selected((string) old('anticipo_id') === (string) $anticipo->id)>{{ $anticipo->fecha?->format('d/m/Y') }} · {{ $anticipo->textoImporte() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="referencia_pago">Referencia</label>
                <input class="aa-input" type="text" id="referencia_pago" name="referencia_pago" value="{{ old('referencia_pago') }}" maxlength="80">
            </div>
            <div class="aa-field full">
                <label for="observaciones_pago">Observaciones</label>
                <textarea class="aa-textarea" id="observaciones_pago" name="observaciones_pago" rows="2">{{ old('observaciones_pago') }}</textarea>
            </div>
        </div>
        <button type="submit" class="aa-btn" style="margin-top:12px;">Registrar pago</button>
    </form>
</div>
@endsection
