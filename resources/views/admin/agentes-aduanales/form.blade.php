@extends('layouts.admin')
@section('title', $factura ? 'Editar factura' : 'Registrar factura')

@push('styles')
@include('admin.agentes-aduanales._estilos')
@endpush

@section('content')
@php
    $mostrarDatos = (bool) $factura || $errors->any() || filled(old('folio')) || filled(old('importe')) || filled(old('fecha'));
@endphp

<p style="margin-bottom:12px;"><a class="aa-back" href="{{ $factura ? route('admin.agentes-aduanales.ver', $factura) : route('admin.agentes-aduanales') }}">← Volver</a></p>

<div class="aa-header">
    <div>
        <h2>{{ $factura ? 'Editar factura' : 'Registrar factura' }}</h2>
        <p class="aa-lead">{{ $factura ? 'Los datos quedan a la vista. Si cambias el PDF, se vuelven a leer.' : 'Sube el PDF. Los datos del archivo aparecen en cuanto se leen.' }}</p>
    </div>
</div>

@if($errors->any())
<div class="aa-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $factura ? route('admin.agentes-aduanales.actualizar', $factura) : route('admin.agentes-aduanales.guardar') }}" enctype="multipart/form-data">
    @csrf
    @if($factura) @method('PUT') @endif

    <label class="aa-drop" id="aa-drop" for="archivo">
        <input class="aa-file" type="file" id="archivo" name="archivo" accept="application/pdf,.pdf" @if(! $factura) required @endif>
        <span class="aa-drop-kicker">PDF de la factura</span>
        <span class="aa-drop-title" id="aa-pdf-nombre">{{ $factura?->nombre_archivo ?: 'Suelta aquí el PDF del agente' }}</span>
        <span class="aa-drop-sub">{{ $factura ? 'Si subes otro PDF, reemplaza el archivo y vuelve a leer los datos.' : 'Del PDF salen el agente, la serie, el folio, la fecha, la moneda, el total, los honorarios y los impuestos.' }}</span>
        <span class="aa-drop-status {{ session('pdf_avisos') ? 'is-ok' : '' }}" id="aa-pdf-aviso">{{ session('pdf_avisos') ? implode(' ', session('pdf_avisos')) : '' }}</span>
    </label>

    <div class="aa-card" style="margin-top:16px;">
        <h3>Para completar</h3>
        <p class="aa-help" style="margin-bottom:14px;">El agente se selecciona si el RFC del PDF ya está en la lista. La operación se llena si el PDF dice importación o exportación. El saldo se captura aquí.</p>
        <div class="aa-grid">
            <div class="aa-field">
                <label for="agente_aduanal_id">Agente aduanal <span class="aa-req">*</span></label>
                <select class="aa-select" id="agente_aduanal_id" name="agente_aduanal_id" required>
                    <option value="">Selecciona</option>
                    @foreach($agentes as $agente)
                    <option value="{{ $agente->id }}" @selected((string) old('agente_aduanal_id', $factura?->agente_aduanal_id ?? $operacionSeleccionada?->agente_aduanal_id) === (string) $agente->id)>{{ $agente->nombreVisible() }} · {{ $agente->numero_patente }}</option>
                    @endforeach
                </select>
                <span class="aa-help" id="aa-agente-leido"></span>
            </div>
            <div class="aa-field">
                <label for="tipo_operacion">Operación <span class="aa-req">*</span></label>
                <select class="aa-select" id="tipo_operacion" name="tipo_operacion">
                    <option value="">Selecciona</option>
                    @foreach(\App\Models\OperacionComercioExterior::TIPOS as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('tipo_operacion', $factura?->operacion?->tipo_operacion ?? $operacionSeleccionada?->tipo_operacion) === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            @if(! $factura && ($operaciones->isNotEmpty() || old('operacion_id') || $operacionSeleccionada))
            <div class="aa-field full">
                <label for="operacion_id">Operación que ya tiene anticipo</label>
                <select class="aa-select" id="operacion_id" name="operacion_id">
                    <option value="">Ninguna: esta factura abre una operación nueva</option>
                    @foreach($operaciones as $operacion)
                    <option value="{{ $operacion->id }}" @selected((string) old('operacion_id', $operacionSeleccionada?->id) === (string) $operacion->id)>
                        {{ $operacion->agente?->nombreVisible() }} · {{ $operacion->tipoLabel() }} · {{ $operacion->fecha?->format('d/m/Y') ?: 'sin fecha' }}
                        @if($operacion->anticipos_count) · {{ $operacion->anticipos_count }} {{ $operacion->anticipos_count === 1 ? 'anticipo' : 'anticipos' }} @endif
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="aa-field">
                <label for="saldo_pendiente">Saldo pendiente</label>
                <input class="aa-input" type="number" id="saldo_pendiente" name="saldo_pendiente" min="0" step="0.01" value="{{ old('saldo_pendiente', $factura?->saldo_pendiente ?? '') }}" placeholder="Se captura, no se calcula">
            </div>
        </div>
    </div>

    <section class="aa-card" id="aa-datos" {{ $mostrarDatos ? '' : 'hidden' }}>
        <h3>Datos leídos</h3>
        <p class="aa-resumen" id="aa-resumen">@if($factura){{ $factura->serieFolio() }} · {{ $factura->fecha?->format('d/m/Y') }} · {{ $factura->textoMonto('importe') }}@endif</p>
        <div class="aa-grid">
            <div class="aa-field">
                <label for="fecha">Fecha <span class="aa-req">*</span></label>
                <input class="aa-input" type="date" id="fecha" name="fecha" value="{{ old('fecha', $factura?->fecha?->format('Y-m-d')) }}" required>
            </div>
            <div class="aa-field">
                <label for="moneda">Moneda <span class="aa-req">*</span></label>
                <select class="aa-select" id="moneda" name="moneda" required>
                    <option value="">Selecciona</option>
                    @foreach(\App\Models\FacturaAgenteAduanal::MONEDAS as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('moneda', $factura?->moneda ?? '') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="serie">Serie</label>
                <input class="aa-input" type="text" id="serie" name="serie" value="{{ old('serie', $factura?->serie ?? '') }}" maxlength="25">
            </div>
            <div class="aa-field">
                <label for="folio">Folio <span class="aa-req">*</span></label>
                <input class="aa-input" type="text" id="folio" name="folio" value="{{ old('folio', $factura?->folio ?? '') }}" maxlength="40" required>
            </div>
            <div class="aa-field">
                <label for="importe">Total <span class="aa-req">*</span></label>
                <input class="aa-input" type="number" id="importe" name="importe" min="0" step="0.01" value="{{ old('importe', $factura?->importe ?? '') }}" required>
            </div>
            <div class="aa-field">
                <label for="honorarios">Honorarios</label>
                <input class="aa-input" type="number" id="honorarios" name="honorarios" min="0" step="0.01" value="{{ old('honorarios', $factura?->honorarios ?? '') }}">
            </div>
            <div class="aa-field">
                <label for="impuestos">Impuestos</label>
                <input class="aa-input" type="number" id="impuestos" name="impuestos" min="0" step="0.01" value="{{ old('impuestos', $factura?->impuestos ?? '') }}">
            </div>
            <div class="aa-field">
                <label for="estado_cancelacion">Estado de cancelación <span class="aa-req">*</span></label>
                <select class="aa-select" id="estado_cancelacion" name="estado_cancelacion" required>
                    @foreach(\App\Models\FacturaAgenteAduanal::ESTADOS_CANCELACION as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('estado_cancelacion', $factura?->estado_cancelacion ?? 'vigente') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aa-field">
                <label for="estado_pago">Estado del pago <span class="aa-req">*</span></label>
                <select class="aa-select" id="estado_pago" name="estado_pago" required>
                    @foreach(\App\Models\FacturaAgenteAduanal::ESTADOS_PAGO as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('estado_pago', $factura?->estado_pago ?? 'pendiente') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <div class="aa-actions" style="margin-top:4px;">
        <button type="submit" class="aa-btn">{{ $factura ? 'Guardar cambios' : 'Registrar factura' }}</button>
        <a class="aa-btn aa-btn-sec" href="{{ $factura ? route('admin.agentes-aduanales.ver', $factura) : route('admin.agentes-aduanales') }}">Cancelar</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const archivo = document.getElementById('archivo');
    const aviso = document.getElementById('aa-pdf-aviso');
    const nombre = document.getElementById('aa-pdf-nombre');
    const drop = document.getElementById('aa-drop');
    const panel = document.getElementById('aa-datos');
    const resumen = document.getElementById('aa-resumen');
    if (!archivo || !drop) return;

    ['dragenter', 'dragover'].forEach(function (evento) {
        drop.addEventListener(evento, function (e) {
            e.preventDefault();
            drop.classList.add('is-over');
        });
    });
    ['dragleave', 'drop'].forEach(function (evento) {
        drop.addEventListener(evento, function (e) {
            e.preventDefault();
            drop.classList.remove('is-over');
        });
    });
    drop.addEventListener('drop', function (e) {
        const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (!file) return;
        const lista = new DataTransfer();
        lista.items.add(file);
        archivo.files = lista.files;
        archivo.dispatchEvent(new Event('change'));
    });

    archivo.addEventListener('change', async function () {
        const elegido = this.files && this.files[0];
        if (!elegido) return;
        nombre.textContent = elegido.name;
        if (!elegido.name.toLowerCase().endsWith('.pdf')) {
            aviso.className = 'aa-drop-status is-error';
            aviso.textContent = 'Sube el PDF que mandó el agente.';
            return;
        }
        aviso.className = 'aa-drop-status is-wait';
        aviso.textContent = 'Leyendo el PDF…';
        const datos = new FormData();
        datos.append('archivo', elegido);
        datos.append('_token', document.querySelector('input[name=_token]').value);
        try {
            const respuesta = await fetch(@json(route('admin.agentes-aduanales.facturas.leer')), {
                method: 'POST',
                body: datos,
                headers: { 'Accept': 'application/json' }
            });
            const json = await respuesta.json();
            if (!respuesta.ok) {
                aviso.className = 'aa-drop-status is-error';
                aviso.textContent = json.errors && json.errors.archivo ? json.errors.archivo[0] : 'No se pudo leer el PDF.';
                panel.hidden = false;
                return;
            }
            const campos = json.datos || {};
            ['serie', 'folio', 'fecha', 'moneda', 'importe', 'honorarios', 'impuestos', 'tipo_operacion', 'estado_cancelacion', 'agente_aduanal_id'].forEach(function (campo) {
                if (!campos[campo]) return;
                const input = document.getElementById(campo);
                if (input) input.value = campos[campo];
            });
            const agente = document.getElementById('agente_aduanal_id');
            const leido = document.getElementById('aa-agente-leido');
            if (agente && campos.nombre_agente && !agente.value) {
                const buscado = String(campos.nombre_agente).toUpperCase();
                Array.from(agente.options).forEach(function (opcion) {
                    const texto = opcion.text.toUpperCase();
                    if (texto.indexOf(buscado) !== -1 || buscado.indexOf(texto.split('·')[0].trim()) !== -1) {
                        agente.value = opcion.value;
                    }
                });
            }
            if (leido) {
                if (agente && agente.value) {
                    leido.textContent = agente.options[agente.selectedIndex].text;
                } else if (campos.nombre_agente) {
                    leido.textContent = 'En el PDF: ' + campos.nombre_agente;
                } else if (campos.rfc_agente) {
                    leido.textContent = 'RFC en el PDF: ' + campos.rfc_agente;
                }
            }
            const partes = [];
            if (campos.folio) partes.push((campos.serie ? campos.serie + '-' : '') + campos.folio);
            if (campos.fecha) {
                const trozos = String(campos.fecha).split('-');
                partes.push(trozos.length === 3 ? trozos[2] + '/' + trozos[1] + '/' + trozos[0] : campos.fecha);
            }
            if (campos.moneda) partes.push(campos.moneda);
            if (campos.importe) partes.push('Total ' + Number(campos.importe).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            resumen.textContent = partes.join(' · ');
            panel.hidden = false;
            aviso.className = 'aa-drop-status is-ok';
            aviso.textContent = (json.avisos || []).join(' ') || 'Datos leídos del PDF.';
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (error) {
            aviso.className = 'aa-drop-status is-error';
            aviso.textContent = 'No se pudo leer el PDF. Captura los datos en el recuadro de abajo.';
            panel.hidden = false;
        }
    });
})();
</script>
@endpush
