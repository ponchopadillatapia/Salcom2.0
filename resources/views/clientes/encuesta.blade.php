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
    .success-card{display:none;background:#ecfdf5;border:1px solid #059669;border-radius:12px;padding:32px;text-align:center;max-width:600px}
    .success-card h3{color:#059669;font-size:18px;margin-bottom:8px}.success-card p{font-size:14px;color:#6b7280}
</style>
@endpush
@section('content')
<div class="enc-card" id="encForm">
    <div class="enc-title">¿Cómo fue tu experiencia?</div>
    <div class="enc-sub">Pedido: PED-2026-001 — Entregado el 04/04/2026</div>
    <div class="question">
        <div class="q-label">Calificación general</div>
        <div class="stars" id="stars">
            <button class="star" onclick="setStars(1)">★</button><button class="star" onclick="setStars(2)">★</button><button class="star" onclick="setStars(3)">★</button><button class="star" onclick="setStars(4)">★</button><button class="star" onclick="setStars(5)">★</button>
        </div>
    </div>
    <div class="question">
        <div class="q-label">Tiempo de entrega</div>
        <div class="radio-group">
            <label class="radio-item"><input type="radio" name="tiempo" value="rapido">Más rápido de lo esperado</label>
            <label class="radio-item"><input type="radio" name="tiempo" value="normal" checked>Dentro del tiempo estimado</label>
            <label class="radio-item"><input type="radio" name="tiempo" value="lento">Más lento de lo esperado</label>
        </div>
    </div>
    <div class="question">
        <div class="q-label">Calidad del producto</div>
        <div class="radio-group">
            <label class="radio-item"><input type="radio" name="calidad" value="excelente" checked>Excelente</label>
            <label class="radio-item"><input type="radio" name="calidad" value="buena">Buena</label>
            <label class="radio-item"><input type="radio" name="calidad" value="regular">Regular</label>
            <label class="radio-item"><input type="radio" name="calidad" value="mala">Mala</label>
        </div>
    </div>
    <div class="question">
        <div class="q-label">Comentarios adicionales</div>
        <textarea class="textarea" id="comentarios" placeholder="Cuéntanos tu experiencia..."></textarea>
    </div>
    <button class="btn-send" onclick="enviarEncuesta()">Enviar encuesta</button>
</div>
<div class="success-card" id="encSuccess">
    <h3>¡Gracias por tu opinión!</h3>
    <p>Tu retroalimentación nos ayuda a mejorar nuestro servicio. Si tienes alguna duda, contacta a tu ejecutivo de cuenta.</p>
</div>
@endsection
@push('scripts')
<script>
let rating=0;
function setStars(n){rating=n;document.querySelectorAll('#stars .star').forEach((s,i)=>{s.classList.toggle('active',i<n)})}
function enviarEncuesta(){if(!rating){alert('Selecciona una calificación');return}document.getElementById('encForm').style.display='none';document.getElementById('encSuccess').style.display='block'}
</script>
@endpush
