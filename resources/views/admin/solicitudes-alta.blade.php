@extends('layouts.admin')
@section('title', 'Solicitudes de Alta')
@section('hero')
<div class="hero-band">
    <h1>Solicitudes de Alta</h1>
    <p>Proveedores pendientes de aprobación por Dirección</p>
</div>
@endsection
@push('styles')
<style>
    .sol-grid{max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:16px}
    .sol-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px 24px;box-shadow:var(--shadow-sm);transition:var(--transition)}
    .sol-card:hover{box-shadow:var(--shadow-md)}
    .sol-card.pendiente{border-left:4px solid var(--amber)}
    .sol-card.aprobada{border-left:4px solid var(--green)}
    .sol-card.rechazada{border-left:4px solid var(--red)}
    .sol-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px}
    .sol-nombre{font-size:16px;font-weight:700;color:var(--gray-text)}
    .sol-badge{font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;text-transform:uppercase}
    .sol-badge.pendiente{background:var(--amber-bg);color:var(--amber)}
    .sol-badge.aprobada,.sol-badge.con-datos{background:var(--green-bg);color:var(--green)}
    .sol-badge.rechazada,.sol-badge.sin-datos{background:var(--red-bg);color:var(--red)}
    .sol-meta{font-size:12px;color:var(--gray-muted);margin-bottom:12px}
    .sol-datos{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px 16px;font-size:12px;margin-bottom:14px}
    .sol-dato-label{font-weight:600;color:var(--gray-muted)}
    .sol-dato-value{color:var(--gray-text)}
    .sol-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .btn-revisar{padding:7px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
    .btn-aprobar{padding:7px 16px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-rechazar{padding:7px 16px;background:var(--red);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-revisar:hover,.btn-aprobar:hover,.btn-rechazar:hover{opacity:.9}
    .sol-empty{text-align:center;padding:40px;color:var(--gray-muted);font-size:14px}
    .sol-filtros{display:flex;gap:8px;margin-bottom:16px}
    .sol-filtro-btn{padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1.5px solid var(--border);background:var(--white);color:var(--gray-text);text-decoration:none;cursor:pointer}
    .sol-filtro-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    @media(max-width:768px){.sol-datos{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')
<div class="sol-grid" id="solGrid">

    <div id="solFlashOk" style="display:{{ session('mensaje') ? 'block' : 'none' }};background:var(--green-bg);border:1px solid var(--green);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--green);font-weight:600;">
        {{ session('mensaje') }}
    </div>
    <div id="solFlashErr" style="display:{{ session('error') ? 'block' : 'none' }};background:var(--red-bg);border:1px solid var(--red);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--red);font-weight:600;">
        {{ session('error') }}
    </div>

    {{-- Filtros --}}
    <div class="sol-filtros">
        <a href="{{ route('admin.solicitudes-alta', ['filtro' => 'todas']) }}" class="sol-filtro-btn {{ ($filtro ?? 'todas') === 'todas' ? 'active' : '' }}" data-filtro-todas>Todas (<span id="solCountTodas">{{ ($pendientes ?? collect())->count() }}</span>)</a>
        <a href="{{ route('admin.solicitudes-alta', ['filtro' => 'con_datos']) }}" class="sol-filtro-btn {{ ($filtro ?? '') === 'con_datos' ? 'active' : '' }}">Con datos (<span id="solCountCon">{{ $conteoConDatos ?? 0 }}</span>)</a>
        <a href="{{ route('admin.solicitudes-alta', ['filtro' => 'sin_datos']) }}" class="sol-filtro-btn {{ ($filtro ?? '') === 'sin_datos' ? 'active' : '' }}">Sin datos (<span id="solCountSin">{{ $conteoSinDatos ?? 0 }}</span>)</a>
    </div>

    {{-- Lista de proveedores pendientes --}}
    <div id="solCards">
    @forelse($pendientes ?? collect() as $item)
        @php $prov = $item->proveedor; @endphp
        <div class="sol-card pendiente" data-proveedor-id="{{ $prov->id }}" data-con-datos="{{ $item->con_datos ? '1' : '0' }}">
            <div class="sol-head">
                <div>
                    <div class="sol-nombre">{{ $prov->nombre ?? $prov->usuario }}</div>
                    <div class="sol-meta">
                        {{ $prov->tipo_persona ?? '—' }} ·
                        Código: {{ $prov->id_proveedor ?? '—' }} ·
                        Registrado: {{ $prov->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>
                </div>
                <span class="sol-badge {{ ($item->docs_ok ?? false) ? 'con-datos' : 'pendiente' }}">{{ ($item->docs_ok ?? false) ? 'Listo' : 'Con validación' }}</span>
            </div>

            <div class="sol-datos">
                <div><span class="sol-dato-label">Correo:</span> <span class="sol-dato-value">{{ $prov->correo ?? '—' }}</span></div>
                <div><span class="sol-dato-label">Teléfono:</span> <span class="sol-dato-value">{{ $prov->telefono ?? '—' }}</span></div>
                <div><span class="sol-dato-label">Formulario:</span> <span class="sol-dato-value">{{ $item->formulario ? '✓ Enviado' : '✕ Pendiente' }}</span></div>
                <div><span class="sol-dato-label">Datos bancarios:</span> <span class="sol-dato-value">{{ $item->bancarios ? '✓ Completo' : '✕ Pendiente' }}</span></div>
                <div><span class="sol-dato-label">Documentos:</span> <span class="sol-dato-value">{{ $item->docs_count }} aprobado(s){{ ($item->docs_ok ?? false) ? ' ✓' : '' }}</span></div>
                <div><span class="sol-dato-label">Contactos:</span> <span class="sol-dato-value">{{ $item->num_contactos }} registrados</span></div>
            </div>

            <div class="sol-actions">
                <form method="POST" action="{{ route('admin.solicitudes-alta.aprobar') }}" class="sol-action-form" data-confirm="¿Aprobar y activar a {{ addslashes($prov->nombre ?? $prov->usuario) }}?">
                    @csrf
                    <input type="hidden" name="proveedor_id" value="{{ $prov->id }}">
                    <button type="submit" class="btn-aprobar">✓ Aprobar</button>
                </form>
                <form method="POST" action="{{ route('admin.solicitudes-alta.rechazar') }}" class="sol-action-form" data-confirm="¿Rechazar la solicitud de {{ addslashes($prov->nombre ?? $prov->usuario) }}?&#10;&#10;No se elimina la cuenta: el proveedor sigue registrado e inactivo y deberá volver a llenar datos bancarios y documentos.">
                    @csrf
                    <input type="hidden" name="proveedor_id" value="{{ $prov->id }}">
                    <button type="submit" class="btn-rechazar">✕ Rechazar</button>
                </form>
                <a href="{{ route('admin.solicitudes-alta.ver', $prov->id) }}" class="btn-revisar">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Ver
                </a>
            </div>
        </div>
    @empty
        <div class="sol-empty" id="solEmpty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <p style="margin-top:12px;">No hay proveedores pendientes de aprobación.</p>
        </div>
    @endforelse
    </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
    // Si el navegador restaura la página desde caché, forzar recarga limpia.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) location.reload();
    });

    function showFlash(ok, text) {
        var okEl = document.getElementById('solFlashOk');
        var errEl = document.getElementById('solFlashErr');
        if (okEl) { okEl.style.display = ok ? 'block' : 'none'; if (ok) okEl.textContent = text; }
        if (errEl) { errEl.style.display = ok ? 'none' : 'block'; if (!ok) errEl.textContent = text; }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function refreshCounts() {
        var cards = document.querySelectorAll('#solCards .sol-card');
        var con = 0, sin = 0;
        cards.forEach(function (c) {
            if (c.getAttribute('data-con-datos') === '1') con++; else sin++;
        });
        var t = document.getElementById('solCountTodas');
        var cEl = document.getElementById('solCountCon');
        var sEl = document.getElementById('solCountSin');
        if (t) t.textContent = String(cards.length);
        if (cEl) cEl.textContent = String(con);
        if (sEl) sEl.textContent = String(sin);

        if (cards.length === 0) {
            var wrap = document.getElementById('solCards');
            if (wrap && !document.getElementById('solEmpty')) {
                wrap.innerHTML = '<div class="sol-empty" id="solEmpty"><p style="margin-top:12px;">No hay proveedores pendientes de aprobación.</p></div>';
            }
        }
    }

    document.querySelectorAll('.sol-action-form').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var msg = form.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) return;

            var card = form.closest('.sol-card');
            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            var fd = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().then(function (data) {
                    return { status: res.status, data: data };
                }).catch(function () {
                    return { status: res.status, data: { ok: false, mensaje: 'No se pudo procesar la solicitud.' } };
                });
            }).then(function (r) {
                if (r.data && r.data.ok) {
                    showFlash(true, r.data.mensaje || 'Listo.');
                    if (card) card.remove();
                    refreshCounts();
                } else {
                    showFlash(false, (r.data && r.data.mensaje) || 'No se pudo completar la acción.');
                    if (btn) btn.disabled = false;
                }
            }).catch(function () {
                showFlash(false, 'Error de red. Intenta de nuevo.');
                if (btn) btn.disabled = false;
            });
        });
    });
})();
</script>
@endpush
