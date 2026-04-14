@extends('layouts.cliente')
@section('title', 'Encuesta de Satisfacción')
@section('hero')
<div class="hero-band"><h1>Encuesta de Satisfacción</h1><p>Tu opinión nos ayuda a mejorar</p></div>
@endsection
@push('styles')
<style>
    .enc-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:32px;max-width:600px}
    .enc-title{font-size:18px;font-weight:700;color:var(--gray-text);margin-bottom:4px}.enc-sub{font-size:13px;color:var(--gray-muted);margin-bottom:24px}
    .question{margin-bottom:24px}.q-label{font-size:14px;font-weight:600;color:var(--gray-text);margin-bottom:8px}
    .stars{display:flex;gap:4px}.star{width:36px;height:36px;cursor:pointer;border:none;background:none;font-size:24px;color:#d1d5db;transition:color .1s}.star.active{color:#f59e0b}.star:hover{color:#f59e0b}
    .radio-group{display:flex;flex-direction:column;gap:8px}.radio-item{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-text);cursor:pointer}.radio-item input{accent-color:#6B3FA0}
    .textarea{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;resize:vertical;min-height:80px}.textarea:focus{border-color:#6B3FA0;box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-send{padding:12px 32px;background:#6B3FA0;color:#fff;border:none;border-radius:10px;font-size:14px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s}.btn-send:hover{background:#4A2070}
    .success-card{background:#ecfdf5;border:1px solid #059669;border-radius:12px;padding:32px;text-align:center;max-width:600px}
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
            <div class="stars" id="stars">
                <button type="button" class="star" data-value="1" aria-label="1 estrella">★</button>
                <button type="button" class="star" data-value="2" aria-label="2 estrellas">★</button>
                <button type="button" class="star" data-value="3" aria-label="3 estrellas">★</button>
                <button type="button" class="star" data-value="4" aria-label="4 estrellas">★</button>
                <button type="button" class="star" data-value="5" aria-label="5 estrellas">★</button>
            </div>
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
            <textarea class="textarea" name="comentarios" placeholder="Cuéntanos tu experiencia...">{{ old('comentarios') }}</textarea>
        </div>
        <button type="submit" class="btn-send">Enviar encuesta</button>
    </form>
@endif

@endsection
@push('scripts')
<script>
(function(){
    const input = document.getElementById('calificacionInput');
    const stars = document.querySelectorAll('#stars .star');
    let rating = parseInt(input?.value) || 0;

    function render() {
        stars.forEach(function(s, i) { s.classList.toggle('active', i < rating); });
    }

    stars.forEach(function(s) {
        s.addEventListener('click', function() {
            rating = parseInt(this.getAttribute('data-value'));
            input.value = rating;
            render();
        });
    });

    render();
})();
</script>
@endpush
