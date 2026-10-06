@extends('layouts.admin')
@section('title', $agente ? 'Editar agente aduanal' : 'Crear agente aduanal')

@push('styles')
@include('admin.agentes-aduanales._estilos')
@endpush

@section('content')
<p style="margin-bottom:12px;"><a class="aa-back" href="{{ $agente ? route('admin.agentes-aduanales.ver', $agente) : route('admin.agentes-aduanales') }}">← Volver</a></p>

@if($errors->any())
<div class="aa-error">
    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="aa-aviso">
    Los datos se guardan solo en Industrias Salcom. El número de patente es obligatorio, pero capturarlo no afirma que la patente esté vigente. El estado interno (activo o inactivo) es independiente de la verificación.
</div>

@php
    $seleccionadas = collect(old('aduanas', $agente ? $agente?->aduanas->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
    $verificacion = old('estado_verificacion', $agente?->estado_verificacion ?? 'no_verificado');
    $fuente = old('fuente_verificacion', $agente?->fuente_verificacion ?? '');
@endphp

<form method="POST" action="{{ $agente ? route('admin.agentes-aduanales.actualizar', $agente) : route('admin.agentes-aduanales.guardar') }}">
    @csrf
    @if($agente) @method('PUT') @endif

    <div class="aa-card">
        <h3>Datos generales</h3>
        <div class="aa-grid">
            <div class="aa-field full">
                <label for="nombre">Nombre completo <span class="aa-req">*</span></label>
                <input class="aa-input" id="nombre" name="nombre" required maxlength="180" value="{{ old('nombre', $agente?->nombre ?? '') }}">
            </div>
            <div class="aa-field">
                <label for="rfc">RFC <span class="aa-req">*</span></label>
                <input class="aa-input" id="rfc" name="rfc" required maxlength="13" value="{{ old('rfc', $agente?->rfc ?? '') }}" style="text-transform:uppercase;">
                <span class="aa-help">Persona física, 13 caracteres.</span>
            </div>
            <div class="aa-field">
                <label for="numero_patente">Número de patente aduanal <span class="aa-req">*</span></label>
                <input class="aa-input" id="numero_patente" name="numero_patente" required inputmode="numeric" maxlength="4" pattern="\d{4}" value="{{ old('numero_patente', $agente?->numero_patente ?? '') }}">
                <span class="aa-help">4 dígitos. No se consulta el padrón al guardarlo.</span>
            </div>
            <div class="aa-field">
                <label for="agencia">Agencia o sociedad</label>
                <input class="aa-input" id="agencia" name="agencia" maxlength="180" value="{{ old('agencia', $agente?->agencia ?? '') }}">
            </div>
            <div class="aa-field">
                <label for="tipo_operacion">Tipo de operación <span class="aa-req">*</span></label>
                <select class="aa-select" id="tipo_operacion" name="tipo_operacion" required>
                    <option value="">Selecciona</option>
                    @foreach(\App\Models\AgenteAduanal::TIPOS_OPERACION as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('tipo_operacion', $agente?->tipo_operacion ?? '') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="aa-card">
        <h3>Contacto</h3>
        <div class="aa-grid">
            <div class="aa-field">
                <label for="contacto_nombre">Nombre del contacto operativo</label>
                <input class="aa-input" id="contacto_nombre" name="contacto_nombre" maxlength="180" value="{{ old('contacto_nombre', $agente?->contacto_nombre ?? '') }}">
            </div>
            <div class="aa-field">
                <label for="contacto_correo">Correo</label>
                <input class="aa-input" type="email" id="contacto_correo" name="contacto_correo" maxlength="180" value="{{ old('contacto_correo', $agente?->contacto_correo ?? '') }}">
            </div>
            <div class="aa-field">
                <label for="contacto_telefono">Teléfono</label>
                <input class="aa-input" id="contacto_telefono" name="contacto_telefono" maxlength="20" value="{{ old('contacto_telefono', $agente?->contacto_telefono ?? '') }}">
            </div>
            <div class="aa-field">
                <label for="contacto_celular">Celular</label>
                <input class="aa-input" id="contacto_celular" name="contacto_celular" maxlength="20" value="{{ old('contacto_celular', $agente?->contacto_celular ?? '') }}">
            </div>
        </div>
    </div>

    <div class="aa-card">
        <h3>Operación</h3>
        <p class="aa-help" style="margin-bottom:10px;">Un agente puede operar en varias aduanas. El listado es una copia local del catálogo público c_Aduana; no se descarga del SAT al guardar.</p>
        <div class="aa-aduanas-box">
            <input class="aa-input" id="filtro_aduanas" type="search" placeholder="Filtrar aduanas" style="width:100%;" aria-label="Filtrar aduanas">
            <div class="aa-aduanas" id="lista_aduanas">
                @foreach($aduanas as $aduana)
                <label data-texto="{{ strtolower($aduana->etiqueta()) }}">
                    <input type="checkbox" name="aduanas[]" value="{{ $aduana->id }}" @checked(in_array($aduana->id, $seleccionadas, true))>
                    <span>{{ $aduana->etiqueta() }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="aa-card">
        <h3>Estado</h3>
        <div class="aa-grid">
            <div class="aa-field">
                <label for="activo">Estado interno <span class="aa-req">*</span></label>
                <select class="aa-select" id="activo" name="activo" required>
                    <option value="1" @selected((string) old('activo', ($agente?->activo ?? true) ? '1' : '0') === '1')>Activo</option>
                    <option value="0" @selected((string) old('activo', ($agente?->activo ?? true) ? '1' : '0') === '0')>Inactivo</option>
                </select>
            </div>
            <div class="aa-field">
                <label for="estado_verificacion">Estado de verificación <span class="aa-req">*</span></label>
                <select class="aa-select" id="estado_verificacion" name="estado_verificacion" required>
                    @foreach(\App\Models\AgenteAduanal::VERIFICACIONES as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected($verificacion === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <span class="aa-help">«Verificado» solo si alguien de Salcom ya consultó la fuente y captura esa consulta.</span>
            </div>
            <div class="aa-field" id="campo_fecha">
                <label for="fecha_ultima_verificacion">Fecha de última verificación</label>
                <input class="aa-input" type="date" id="fecha_ultima_verificacion" name="fecha_ultima_verificacion" max="{{ now()->toDateString() }}" value="{{ old('fecha_ultima_verificacion', $agente?->fecha_ultima_verificacion?->format('Y-m-d') ?? '') }}">
            </div>
            <div class="aa-field" id="campo_fuente">
                <label for="fuente_verificacion">Fuente de verificación</label>
                <select class="aa-select" id="fuente_verificacion" name="fuente_verificacion">
                    <option value="">Selecciona</option>
                    @foreach(\App\Models\AgenteAduanal::FUENTES as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected($fuente === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field full" id="campo_detalle">
                <label for="fuente_detalle">Detalle de la fuente</label>
                <input class="aa-input" id="fuente_detalle" name="fuente_detalle" maxlength="120" value="{{ old('fuente_detalle', $agente?->fuente_detalle ?? '') }}">
            </div>
            <div class="aa-field full">
                <label for="observaciones">Observaciones</label>
                <textarea class="aa-textarea" id="observaciones" name="observaciones" rows="4" maxlength="2000">{{ old('observaciones', $agente?->observaciones ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="aa-actions">
        <button type="submit" class="aa-btn">{{ $agente ? 'Guardar cambios' : 'Crear agente' }}</button>
        <a class="aa-btn aa-btn-sec" href="{{ $agente ? route('admin.agentes-aduanales.ver', $agente) : route('admin.agentes-aduanales') }}">Cancelar</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var filtro = document.getElementById('filtro_aduanas');
    var lista = document.getElementById('lista_aduanas');
    if (filtro && lista) {
        filtro.addEventListener('input', function () {
            var texto = filtro.value.toLowerCase().trim();
            lista.querySelectorAll('label').forEach(function (label) {
                label.style.display = label.getAttribute('data-texto').indexOf(texto) !== -1 ? '' : 'none';
            });
        });
    }

    var verificacion = document.getElementById('estado_verificacion');
    var fuente = document.getElementById('fuente_verificacion');
    function sincronizar() {
        var exige = verificacion && verificacion.value !== 'no_verificado';
        document.getElementById('campo_fecha').style.display = exige ? '' : 'none';
        document.getElementById('campo_fuente').style.display = exige ? '' : 'none';
        var otra = exige && fuente && fuente.value === 'otra';
        document.getElementById('campo_detalle').style.display = otra ? '' : 'none';
    }
    if (verificacion) verificacion.addEventListener('change', sincronizar);
    if (fuente) fuente.addEventListener('change', sincronizar);
    sincronizar();
})();
</script>
@endpush
