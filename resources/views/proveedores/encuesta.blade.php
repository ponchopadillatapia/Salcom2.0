@extends('layouts.proveedor')

@section('title', 'Encuesta de Servicio')

@section('hero')
<div class="hero-band">
    <h1>Encuesta de Servicio</h1>
    <p>Ayúdanos a mejorar — Tu opinión es importante para Industrias Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .enc-card{background:var(--white);border-radius:var(--radius-lg);padding:32px;box-shadow:var(--shadow-sm);max-width:700px;margin:0 auto}
    .enc-card h3{font-size:18px;font-weight:700;color:var(--gray-text);margin-bottom:4px;letter-spacing:-0.3px}
    .enc-card .enc-sub{font-size:14px;color:var(--gray-muted);margin-bottom:28px}

    .enc-field{margin-bottom:24px}
    .enc-field label{display:block;font-size:14px;font-weight:600;color:var(--gray-text);margin-bottom:8px}
    .enc-field .enc-hint{font-size:12px;color:var(--gray-muted);margin-bottom:8px}

    /* Estrellas */
    .stars{display:flex;gap:6px;margin-top:4px}
    .stars input{display:none}
    .stars label{cursor:pointer;font-size:28px;color:var(--border-light);transition:var(--transition)}
    .stars label:hover,.stars label:hover~label,.stars input:checked~label{color:var(--amber)}
    .stars{direction:rtl}

    /* Radio pills */
    .radio-pills{display:flex;gap:8px;flex-wrap:wrap}
    .radio-pills input{display:none}
    .radio-pills label{padding:8px 18px;border:1.5px solid var(--border-light);border-radius:var(--radius-pill);font-size:13px;font-weight:500;color:var(--gray-text);cursor:pointer;transition:var(--transition)}
    .radio-pills label:hover{border-color:var(--purple-mid);color:var(--purple)}
    .radio-pills input:checked+label{background:var(--purple);color:#fff;border-color:var(--purple)}

    /* Textarea */
    .enc-textarea{width:100%;border:1.5px solid var(--border-light);border-radius:var(--radius);padding:14px 16px;font-size:14px;font-family:inherit;color:var(--gray-text);resize:vertical;min-height:100px;outline:none;transition:var(--transition)}
    .enc-textarea:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,0.12)}

    .btn-enviar{width:100%;padding:14px;background:var(--purple);color:#fff;border:none;border-radius:var(--radius-pill);font-size:16px;font-family:inherit;font-weight:600;cursor:pointer;transition:var(--transition);margin-top:8px}
    .btn-enviar:hover{background:var(--purple-dark);transform:scale(1.02)}
    .btn-enviar:active{transform:scale(0.97)}

    .enc-success{background:var(--green-bg);border:1px solid var(--green);border-radius:var(--radius);padding:20px;text-align:center;margin-bottom:20px}
    .enc-success h4{color:var(--green);font-size:16px;margin-bottom:4px}
    .enc-success p{font-size:13px;color:var(--gray-muted)}
</style>
@endpush

@section('content')

<div class="enc-card">
    @if(session('encuesta_guardada'))
        <div class="enc-success">
            <h4>Gracias por tu retroalimentación</h4>
            <p>Tu respuesta nos ayuda a mejorar nuestro servicio.</p>
        </div>
    @endif

    <h3>Evalúa nuestro servicio</h3>
    <p class="enc-sub">Queremos saber cómo ha sido tu experiencia como proveedor de Industrias Salcom. Tus respuestas son confidenciales.</p>

    <form method="POST" action="{{ route('proveedores.encuesta.guardar') }}">
        @csrf

        {{-- Calificación general --}}
        <div class="enc-field">
            <label>Calificación general del servicio de Salcom</label>
            <div class="stars">
                <input type="radio" name="calificacion" id="star5" value="5" required><label for="star5">★</label>
                <input type="radio" name="calificacion" id="star4" value="4"><label for="star4">★</label>
                <input type="radio" name="calificacion" id="star3" value="3"><label for="star3">★</label>
                <input type="radio" name="calificacion" id="star2" value="2"><label for="star2">★</label>
                <input type="radio" name="calificacion" id="star1" value="1"><label for="star1">★</label>
            </div>
        </div>

        {{-- Comunicación --}}
        <div class="enc-field">
            <label>¿Cómo calificas la comunicación con Salcom?</label>
            <div class="radio-pills">
                <input type="radio" name="comunicacion" id="com1" value="excelente" required><label for="com1">Excelente</label>
                <input type="radio" name="comunicacion" id="com2" value="buena"><label for="com2">Buena</label>
                <input type="radio" name="comunicacion" id="com3" value="regular"><label for="com3">Regular</label>
                <input type="radio" name="comunicacion" id="com4" value="mala"><label for="com4">Mala</label>
            </div>
        </div>

        {{-- Pago a tiempo --}}
        <div class="enc-field">
            <label>¿Los pagos se realizan a tiempo?</label>
            <div class="radio-pills">
                <input type="radio" name="pago_tiempo" id="pago1" value="siempre" required><label for="pago1">Siempre</label>
                <input type="radio" name="pago_tiempo" id="pago2" value="casi_siempre"><label for="pago2">Casi siempre</label>
                <input type="radio" name="pago_tiempo" id="pago3" value="a_veces"><label for="pago3">A veces</label>
                <input type="radio" name="pago_tiempo" id="pago4" value="nunca"><label for="pago4">Nunca</label>
            </div>
        </div>

        {{-- Proceso de OC --}}
        <div class="enc-field">
            <label>¿El proceso de órdenes de compra es claro?</label>
            <div class="radio-pills">
                <input type="radio" name="proceso_oc" id="oc1" value="muy_claro" required><label for="oc1">Muy claro</label>
                <input type="radio" name="proceso_oc" id="oc2" value="claro"><label for="oc2">Claro</label>
                <input type="radio" name="proceso_oc" id="oc3" value="confuso"><label for="oc3">Confuso</label>
                <input type="radio" name="proceso_oc" id="oc4" value="muy_confuso"><label for="oc4">Muy confuso</label>
            </div>
        </div>

        {{-- Recomendaría --}}
        <div class="enc-field">
            <label>¿Recomendarías trabajar con Industrias Salcom?</label>
            <div class="radio-pills">
                <input type="radio" name="recomendaria" id="rec1" value="si" required><label for="rec1">Sí, definitivamente</label>
                <input type="radio" name="recomendaria" id="rec2" value="probablemente"><label for="rec2">Probablemente</label>
                <input type="radio" name="recomendaria" id="rec3" value="no_seguro"><label for="rec3">No estoy seguro</label>
                <input type="radio" name="recomendaria" id="rec4" value="no"><label for="rec4">No</label>
            </div>
        </div>

        {{-- Comentarios --}}
        <div class="enc-field">
            <label>¿Qué podemos mejorar?</label>
            <p class="enc-hint">Cuéntanos qué te gustaría que cambiara o mejorara en nuestro servicio.</p>
            <textarea name="comentarios" class="enc-textarea" placeholder="Escribe tus sugerencias aquí..." maxlength="2000">{{ old('comentarios') }}</textarea>
        </div>

        <button type="submit" class="btn-enviar">Enviar encuesta</button>
    </form>
</div>

@endsection
