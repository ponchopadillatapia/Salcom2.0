@extends('layouts.cliente')
@section('title', 'Encuesta de Satisfacción')
@section('hero')
<div class="hero-band"><h1>Encuesta de Satisfacción</h1><p>Tu opinión nos ayuda a mejorar</p></div>
@endsection
@push('styles')
<style>
    .enc-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:32px;max-width:600px;width:100%;margin:0 auto}
    .enc-title{font-size:18px;font-weight:700;color:var(--gray-text);margin-bottom:4px}.enc-sub{font-size:13px;color:var(--gray-muted);margin-bottom:24px}
    .question{margin-bottom:24px}.q-label{font-size:14px;font-weight:600;color:var(--gray-text);margin-bottom:8px}
    .stars{display:flex;align-items:center;gap:4px}
    .star{width:36px;height:36px;cursor:pointer;border:none;background:none;font-size:24px;color:#d1d5db;transition:color .1s}
    .star.active{color:#f59e0b}
    .star:hover{color:#f59e0b}
    .star:focus-visible{outline:3px solid rgba(107,63,160,.25);border-radius:8px}
    .rating-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .rating-hint{font-size:12px;color:var(--gray-muted)}
    .field-error{font-size:12px;color:#dc2626;margin-top:8px;display:none}
    .field-error.show{display:block}
    .radio-group{display:flex;flex-direction:column;gap:8px}.radio-item{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-text);cursor:pointer}.radio-item input{accent-color:#6B3FA0}
    .textarea{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;resize:vertical;min-height:80px}.textarea:focus{border-color:#6B3FA0;box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-send{padding:12px 32px;background:#6B3FA0;color:#fff;border:none;border-radius:10px;font-size:14px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s}.btn-send:hover{background:#4A2070}
    .btn-send[disabled]{opacity:.65;cursor:not-allowed}
    .success-card{background:#ecfdf5;border:1px solid #059669;border-radius:12px;padding:32px;text-align:center;max-width:600px;width:100%;margin:0 auto}
    .success-card h3{color:#059669;font-size:18px;margin-bottom:8px}.success-card p{font-size:14px;color:#6b7280}
    .error-list{background:#fef2f2;border:1px solid #ef4444;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#dc2626}
    .error-list ul{margin:0;padding-left:18px}
</style>
@endpush
@section('content')

@if(session('encuesta_guardada'))
    <div class="success-card">
        <h3>¡Gracias por tu opinión!</h3>
        <p>Tu retroalimentación nos ayuda a mejorar nuestro servicio. Si tienes alguna duda, contacta a tu ejecutivo de cuenta.</p>
    </div>
@else
    <form method="POST" action="{{ route('clientes.encuesta.guardar') }}" class="enc-card" id="encForm">
        @csrf
        <div class="enc-title">¿Cómo fue tu experiencia?</div>
        <div class="enc-sub">Completa la encuesta para ayudarnos a mejorar</div>

        @if($errors->any())
            <div class="error-list">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(request('pedido_id'))
            <input type="hidden" name="pedido_id" value="{{ request('pedido_id') }}">
        @endif

        <input type="hidden" name="calificacion" id="calificacionInput" value="{{ old('calificacion') }}">

        <div class="question">
            <div class="q-label">Calificación general</div>
            <div class="rating-row">
                <div class="stars" id="stars" role="radiogroup" aria-label="Calificación general">
                    <button type="button" class="star" data-value="1" aria-label="1 estrella" aria-checked="false" role="radio">★</button>
                    <button type="button" class="star" data-value="2" aria-label="2 estrellas" aria-checked="false" role="radio">★</button>
                    <button type="button" class="star" data-value="3" aria-label="3 estrellas" aria-checked="false" role="radio">★</button>
                    <button type="button" class="star" data-value="4" aria-label="4 estrellas" aria-checked="false" role="radio">★</button>
                    <button type="button" class="star" data-value="5" aria-label="5 estrellas" aria-checked="false" role="radio">★</button>
                </div>
                <div class="rating-hint" id="ratingHint" aria-live="polite"></div>
            </div>
            <div class="field-error" id="ratingError">Selecciona una calificación (1 a 5) para poder enviar.</div>
        </div>
        <div class="question">
            <div class="q-label">Tiempo de entrega</div>
            <div class="radio-group">
                <label class="radio-item"><input type="radio" name="tiempo_entrega" value="rapido" {{ old('tiempo_entrega')=='rapido'?'checked':'' }}>Más rápido de lo esperado</label>
                <label class="radio-item"><input type="radio" name="tiempo_entrega" value="normal" {{ old('tiempo_entrega','normal')=='normal'?'checked':'' }}>Dentro del tiempo estimado</label>
                <label class="radio-item"><input type="radio" name="tiempo_entrega" value="lento" {{ old('tiempo_entrega')=='lento'?'checked':'' }}>Más lento de lo esperado</label>
            </div>
        </div>
        <div class="question">
            <div class="q-label">Calidad del producto</div>
            <div class="radio-group">
                <label class="radio-item"><input type="radio" name="calidad_producto" value="excelente" {{ old('calidad_producto','excelente')=='excelente'?'checked':'' }}>Excelente</label>
                <label class="radio-item"><input type="radio" name="calidad_producto" value="buena" {{ old('calidad_producto')=='buena'?'checked':'' }}>Buena</label>
                <label class="radio-item"><input type="radio" name="calidad_producto" value="regular" {{ old('calidad_producto')=='regular'?'checked':'' }}>Regular</label>
                <label class="radio-item"><input type="radio" name="calidad_producto" value="mala" {{ old('calidad_producto')=='mala'?'checked':'' }}>Mala</label>
            </div>
        </div>
        <div class="question">
            <div class="q-label">Comentarios adicionales</div>
            <textarea class="textarea" name="comentarios" id="comentarios" placeholder="Cuéntanos tu experiencia..." maxlength="500">{{ old('comentarios') }}</textarea>
            <div class="rating-hint"><span id="comentariosCount">0</span>/500</div>
        </div>
        <button type="submit" class="btn-send" id="submitBtn">Enviar encuesta</button>
    </form>
@endif

@endsection
@push('scripts')
<script>
(function(){
    const input = document.getElementById('calificacionInput');
    const stars = document.querySelectorAll('#stars .star');
    const hint = document.getElementById('ratingHint');
    const ratingError = document.getElementById('ratingError');
    const form = document.getElementById('encForm');
    const submitBtn = document.getElementById('submitBtn');
    const comentarios = document.getElementById('comentarios');
    const comentariosCount = document.getElementById('comentariosCount');
    let rating = parseInt(input?.value) || 0;

    function ratingText(n){
        if(!n) return 'Selecciona 1 a 5';
        if(n === 5) return 'Excelente';
        if(n === 4) return 'Muy buena';
        if(n === 3) return 'Buena';
        if(n === 2) return 'Regular';
        return 'Mala';
    }

    function render() {
        stars.forEach(function(s, i) {
            const active = i < rating;
            s.classList.toggle('active', active);
            s.setAttribute('aria-checked', active ? 'true' : 'false');
            s.tabIndex = (rating ? (i === rating - 1) : (i === 0)) ? 0 : -1;
        });
        if(hint) hint.textContent = rating ? (rating + '/5 — ' + ratingText(rating)) : ratingText(0);
        if(ratingError) ratingError.classList.toggle('show', !rating);
    }

    stars.forEach(function(s) {
        s.addEventListener('click', function() {
            rating = parseInt(this.getAttribute('data-value'));
            input.value = rating;
            render();
        });
        s.addEventListener('keydown', function(e){
            const key = e.key;
            if(key === 'ArrowRight' || key === 'ArrowUp'){
                e.preventDefault();
                rating = Math.min(5, (rating || 1) + 1);
                input.value = rating;
                render();
                stars[rating - 1]?.focus();
            } else if(key === 'ArrowLeft' || key === 'ArrowDown'){
                e.preventDefault();
                rating = Math.max(1, (rating || 1) - 1);
                input.value = rating;
                render();
                stars[rating - 1]?.focus();
            } else if(key === 'Enter' || key === ' '){
                e.preventDefault();
                rating = parseInt(this.getAttribute('data-value'));
                input.value = rating;
                render();
            }
        });
    });

    if(comentarios && comentariosCount){
        const syncCount = function(){ comentariosCount.textContent = String(comentarios.value.length || 0); };
        comentarios.addEventListener('input', syncCount);
        syncCount();
    }

    if(form){
        form.addEventListener('submit', function(e){
            if(!rating){
                e.preventDefault();
                if(ratingError) ratingError.classList.add('show');
                stars[0]?.focus();
                return;
            }
            if(submitBtn){
                submitBtn.disabled = true;
                submitBtn.textContent = 'Enviando…';
            }
        });
    }

    render();
})();
</script>
@endpush
