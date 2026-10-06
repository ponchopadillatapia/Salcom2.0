@extends('layouts.admin')
@section('title', $agente->nombre)

@push('styles')
@include('admin.agentes-aduanales._estilos')
@endpush

@php
    $badgeVerificacion = $agente->estado_verificacion === 'verificado' ? 'aa-b-ok' : ($agente->estado_verificacion === 'no_localizado' ? 'aa-b-pend' : 'aa-b-no');
    $badgeEncargo = [
        'pendiente' => 'aa-b-pend',
        'aceptado' => 'aa-b-ok',
        'rechazado' => 'aa-b-rev',
        'revocado' => 'aa-b-rev',
        'vencido' => 'aa-b-no',
    ];
@endphp

@section('content')
<p style="margin-bottom:12px;"><a class="aa-back" href="{{ route('admin.agentes-aduanales') }}">← Agentes Aduanales</a></p>

@if(session('mensaje'))
<div class="aa-ok">{{ session('mensaje') }}</div>
@endif
@if($errors->any())
<div class="aa-error">
    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="aa-header">
    <div>
        <h2>{{ $agente->nombre }}</h2>
        <p class="aa-mini" style="margin-top:6px;">Patente {{ $agente->numero_patente }} · RFC {{ $agente->rfc }}</p>
        <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
            <span class="aa-badge {{ $agente->activo ? 'aa-b-activo' : 'aa-b-inactivo' }}">{{ $agente->activo ? 'Activo' : 'Inactivo' }}</span>
            <span class="aa-badge {{ $badgeVerificacion }}">{{ $agente->verificacionLabel() }}</span>
            <span class="aa-badge aa-b-no">{{ $agente->tipoOperacionLabel() }}</span>
        </div>
    </div>
    <div class="aa-actions">
        <a class="aa-btn aa-btn-sec" href="{{ route('admin.agentes-aduanales.editar', $agente) }}">Editar</a>
        @if($agente->activo)
        <form method="POST" action="{{ route('admin.agentes-aduanales.desactivar', $agente) }}" onsubmit="return confirm('¿Desactivar a este agente? Sus documentos se conservan.');">
            @csrf
            <button type="submit" class="aa-btn aa-btn-warn">Desactivar</button>
        </form>
        @else
        <form method="POST" action="{{ route('admin.agentes-aduanales.reactivar', $agente) }}">
            @csrf
            <button type="submit" class="aa-btn aa-btn-ok">Reactivar</button>
        </form>
        @endif
    </div>
</div>

<div class="aa-card">
    <h3>Datos generales</h3>
    <dl class="aa-dl">
        <dt>Nombre</dt><dd>{{ $agente->nombre }}</dd>
        <dt>RFC</dt><dd>{{ $agente->rfc }}</dd>
        <dt>Patente</dt><dd>{{ $agente->numero_patente }}</dd>
        <dt>Agencia o sociedad</dt><dd>{{ $agente->agencia ?: '—' }}</dd>
        <dt>Tipo de operación</dt><dd>{{ $agente->tipoOperacionLabel() }}</dd>
    </dl>
</div>

<div class="aa-card">
    <h3>Contacto</h3>
    <dl class="aa-dl">
        <dt>Contacto operativo</dt><dd>{{ $agente->contacto_nombre ?: '—' }}</dd>
        <dt>Correo</dt><dd>{{ $agente->contacto_correo ?: '—' }}</dd>
        <dt>Teléfono</dt><dd>{{ $agente->contacto_telefono ?: '—' }}</dd>
        <dt>Celular</dt><dd>{{ $agente->contacto_celular ?: '—' }}</dd>
    </dl>
</div>

<div class="aa-card">
    <h3>Aduanas donde opera</h3>
    @if($agente->aduanas->isEmpty())
    <p class="aa-mini">Sin aduanas capturadas.</p>
    @else
    <ul style="margin:0;padding-left:18px;font-size:13px;">
        @foreach($agente->aduanas as $aduana)
        <li>{{ $aduana->etiqueta() }}</li>
        @endforeach
    </ul>
    @endif
</div>

<div class="aa-card">
    <h3>Estado</h3>
    <dl class="aa-dl">
        <dt>Estado interno</dt><dd>{{ $agente->activo ? 'Activo' : 'Inactivo' }}</dd>
        <dt>Verificación</dt><dd>{{ $agente->verificacionLabel() }}</dd>
        <dt>Última verificación</dt><dd>{{ $agente->fecha_ultima_verificacion?->format('d/m/Y') ?: '—' }}</dd>
        <dt>Fuente</dt><dd>{{ $agente->fuenteLabel() ?: '—' }}</dd>
        <dt>Observaciones</dt><dd>{!! $agente->observaciones ? nl2br(e($agente->observaciones)) : '—' !!}</dd>
    </dl>
</div>

<div class="aa-card" id="documentos">
    <h3>Documentos</h3>
    <p class="aa-help" style="margin-bottom:12px;">PDF, JPG o PNG, hasta 10 MB. Se guardan en un disco privado y solo se descargan con sesión de administración. No se aceptan e.firma, .cer, .key ni contraseñas del SAT.</p>

    @if($agente->documentos->isEmpty())
    <p class="aa-mini" style="margin-bottom:14px;">Todavía no hay documentos.</p>
    @else
    <div class="aa-table-wrap" style="margin-bottom:16px;">
        <table class="aa-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Archivo</th>
                    <th>Carga</th>
                    <th>Vencimiento</th>
                    <th>Observaciones</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($agente->documentos as $documento)
                <tr>
                    <td>{{ $documento->tipoLabel() }}</td>
                    <td>{{ $documento->nombre_archivo }}</td>
                    <td>{{ $documento->fecha_carga?->format('d/m/Y') }}</td>
                    <td>{{ $documento->fecha_vencimiento?->format('d/m/Y') ?: '—' }}</td>
                    <td>{{ $documento->observaciones ?: '—' }}</td>
                    <td>
                        <div class="aa-links">
                            <a class="aa-link" href="{{ route('admin.agentes-aduanales.documentos.descargar', [$agente, $documento]) }}">Descargar</a>
                            <form method="POST" action="{{ route('admin.agentes-aduanales.documentos.eliminar', [$agente, $documento]) }}" onsubmit="return confirm('¿Eliminar este documento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="aa-btn-danger">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.agentes-aduanales.documentos.guardar', $agente) }}" enctype="multipart/form-data">
        @csrf
        <div class="aa-grid">
            <div class="aa-field">
                <label for="tipo">Tipo de documento <span class="aa-req">*</span></label>
                <select class="aa-select" id="tipo" name="tipo" required>
                    <option value="">Selecciona</option>
                    @foreach(\App\Models\DocumentoAgenteAduanal::TIPOS as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('tipo') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="archivo">Archivo <span class="aa-req">*</span></label>
                <input class="aa-input" type="file" id="archivo" name="archivo" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            </div>
            <div class="aa-field">
                <label for="fecha_vencimiento">Fecha de vencimiento</label>
                <input class="aa-input" type="date" id="fecha_vencimiento" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}">
            </div>
            <div class="aa-field">
                <label for="observaciones_doc">Observaciones</label>
                <input class="aa-input" id="observaciones_doc" name="observaciones_documento" maxlength="2000" value="{{ old('observaciones_documento') }}">
            </div>
        </div>
        <div style="margin-top:12px;">
            <button type="submit" class="aa-btn">Cargar documento</button>
        </div>
    </form>
</div>

<div class="aa-card" id="encargos">
    <h3>Encargos conferidos</h3>
    <div class="aa-aviso">
        Registro interno. Salcom no envía este trámite al SAT, no actualiza el padrón y no genera un acuse oficial. El número de acuse y el archivo son los que alguien de la empresa captura o adjunta.
    </div>

    @forelse($agente->encargos as $encargo)
    <div class="aa-encargo">
        <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <strong>Patente {{ $encargo->numero_patente }}</strong>
            <span class="aa-badge {{ $badgeEncargo[$encargo->estadoEfectivo()] ?? 'aa-b-no' }}">{{ $encargo->estadoLabel() }}</span>
        </div>
        @if($encargo->estado === 'aceptado' && $encargo->estadoEfectivo() === 'vencido')
        <p class="aa-help" style="margin-bottom:8px;">La fecha de término capturada ya pasó, por eso aquí se muestra como Vencido. El SAT no fue consultado.</p>
        @endif
        <form method="POST" action="{{ route('admin.agentes-aduanales.encargos.actualizar', [$agente, $encargo]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="aa-grid">
                <div class="aa-field">
                    <label>Fecha de inicio</label>
                    <input class="aa-input" type="date" name="fecha_inicio" required value="{{ $encargo->fecha_inicio->format('Y-m-d') }}">
                </div>
                <div class="aa-field">
                    <label>Fecha de término</label>
                    <input class="aa-input" type="date" name="fecha_termino" value="{{ $encargo->fecha_termino?->format('Y-m-d') }}">
                </div>
                <div class="aa-field">
                    <label>Estado capturado</label>
                    <select class="aa-select" name="estado" required>
                        @foreach(\App\Models\EncargoConferido::ESTADOS as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($encargo->estado === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aa-field">
                    <label>Número o referencia del acuse</label>
                    <input class="aa-input" name="numero_acuse" maxlength="80" value="{{ $encargo->numero_acuse }}">
                </div>
                <div class="aa-field">
                    <label>Archivo del acuse</label>
                    <input class="aa-input" type="file" name="archivo_acuse" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                    @if($encargo->ruta_acuse)
                    <a class="aa-link" href="{{ route('admin.agentes-aduanales.encargos.acuse', [$agente, $encargo]) }}">Descargar {{ $encargo->nombre_acuse }}</a>
                    @endif
                </div>
                <div class="aa-field">
                    <label>Observaciones</label>
                    <input class="aa-input" name="observaciones" maxlength="2000" value="{{ $encargo->observaciones }}">
                </div>
            </div>
            <div class="aa-actions" style="margin-top:10px;">
                <button type="submit" class="aa-btn aa-btn-sec">Actualizar encargo</button>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.agentes-aduanales.encargos.eliminar', [$agente, $encargo]) }}" style="margin-top:8px;" onsubmit="return confirm('¿Eliminar este encargo del registro interno?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="aa-btn-danger">Eliminar encargo</button>
        </form>
    </div>
    @empty
    <p class="aa-mini" style="margin-bottom:14px;">Todavía no hay encargos conferidos registrados.</p>
    @endforelse

    <h3 style="margin-top:8px;">Registrar encargo</h3>
    <form method="POST" action="{{ route('admin.agentes-aduanales.encargos.guardar', $agente) }}" enctype="multipart/form-data">
        @csrf
        <div class="aa-grid">
            <div class="aa-field">
                <label>Agente</label>
                <input class="aa-input" value="{{ $agente->nombre }}" disabled>
            </div>
            <div class="aa-field">
                <label>Número de patente</label>
                <input class="aa-input" value="{{ $agente->numero_patente }}" disabled>
                <span class="aa-help">Se guarda la patente actual del agente. Si después cambia, este encargo conserva la de hoy.</span>
            </div>
            <div class="aa-field">
                <label for="enc_inicio">Fecha de inicio <span class="aa-req">*</span></label>
                <input class="aa-input" type="date" id="enc_inicio" name="fecha_inicio" required value="{{ old('fecha_inicio') }}">
            </div>
            <div class="aa-field">
                <label for="enc_termino">Fecha de término</label>
                <input class="aa-input" type="date" id="enc_termino" name="fecha_termino" value="{{ old('fecha_termino') }}">
            </div>
            <div class="aa-field">
                <label for="enc_estado">Estado <span class="aa-req">*</span></label>
                <select class="aa-select" id="enc_estado" name="estado" required>
                    @foreach(\App\Models\EncargoConferido::ESTADOS as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('estado', 'pendiente') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="enc_acuse">Número o referencia del acuse</label>
                <input class="aa-input" id="enc_acuse" name="numero_acuse" maxlength="80" value="{{ old('numero_acuse') }}">
            </div>
            <div class="aa-field">
                <label for="enc_archivo">Archivo del acuse</label>
                <input class="aa-input" type="file" id="enc_archivo" name="archivo_acuse" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            </div>
            <div class="aa-field">
                <label for="enc_obs">Observaciones</label>
                <input class="aa-input" id="enc_obs" name="observaciones" maxlength="2000" value="{{ old('observaciones') }}">
            </div>
        </div>
        <div style="margin-top:12px;">
            <button type="submit" class="aa-btn">Registrar encargo</button>
        </div>
    </form>
</div>

<div class="aa-card" id="operaciones">
    <h3>Operaciones de comercio exterior</h3>
    <p class="aa-help" style="margin-bottom:12px;">Aún no existe un módulo de pedimentos. Esta relación ya está preparada para ligar después cada importación o exportación con el agente, la patente, la aduana y el número de pedimento. Los pedidos de venta del sistema no son pedimentos.</p>
    @if($agente->operaciones->isEmpty())
    <p class="aa-mini">Sin operaciones ligadas a este agente.</p>
    @else
    <div class="aa-table-wrap">
        <table class="aa-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Pedimento</th>
                    <th>Patente</th>
                    <th>Aduana</th>
                    <th>Fecha</th>
                    <th>Proveedor o cliente</th>
                    <th>Mercancía</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agente->operaciones as $operacion)
                <tr>
                    <td>{{ $operacion->tipoLabel() }}</td>
                    <td>{{ $operacion->numero_pedimento ?: '—' }}</td>
                    <td>{{ $operacion->numero_patente ?: '—' }}</td>
                    <td>{{ $operacion->aduana?->etiqueta() ?: '—' }}</td>
                    <td>{{ $operacion->fecha?->format('d/m/Y') ?: '—' }}</td>
                    <td>{{ $operacion->contraparte ?: '—' }}</td>
                    <td>{{ $operacion->mercancia ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
