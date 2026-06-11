@extends('layouts.admin')
@section('title', 'Negocio')
@section('hero')
<div class="hero-band">
    <h1>Negocio</h1>
    <p>Compras y adeudos con proveedores</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px}
    .kpi{background:var(--white);border:2px solid var(--border-light);border-radius:14px;padding:22px;position:relative;overflow:hidden;cursor:pointer;text-decoration:none;color:inherit;transition:all .15s;display:block;width:100%;text-align:left;font-family:inherit}
    .kpi:hover{border-color:var(--purple);box-shadow:0 4px 16px rgba(107,63,160,.12)}
    .kpi.active{border-color:var(--purple);background:var(--purple-subtle);box-shadow:0 4px 16px rgba(107,63,160,.15)}
    .kpi .bar{position:absolute;bottom:0;left:0;right:0;height:4px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
    .kpi-val{font-size:28px;font-weight:800;color:var(--gray-text);line-height:1;font-variant-numeric:tabular-nums}
    .kpi-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .kpi-count{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(0,0,0,.08);margin-left:6px;vertical-align:middle}
    .kpi.active .kpi-count{background:rgba(107,63,160,.15);color:var(--purple)}

    .neg-detail{display:none;margin-bottom:20px}
    .neg-detail.active{display:block}
    .section-meta{background:var(--white);border:1px solid var(--border-light);border-radius:12px;padding:14px 20px;margin-bottom:14px;font-size:13px;color:var(--gray-muted);display:flex;align-items:center;gap:8px}
    .section-meta strong{color:var(--purple);font-weight:700}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.03)}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:14px 18px;text-align:left;border-bottom:2px solid var(--purple-light);background:var(--white)}
    .admin-table th.num{text-align:right}
    .admin-table td{padding:14px 18px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .admin-table td.num{text-align:right;font-variant-numeric:tabular-nums}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr{transition:background .12s}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .admin-table tfoot td{font-weight:700;background:var(--purple-subtle);border-top:2px solid var(--purple-light);color:var(--purple);font-size:13px}
    .code-col{font-weight:700;color:var(--purple);font-size:12px}
    .name-col{font-weight:600}
    .empty-state{text-align:center;padding:40px 20px;color:var(--gray-muted);font-size:14px;background:var(--white);border:1px solid var(--border);border-radius:12px}

    .prov-contact-link{color:var(--gray-text);font-weight:600;text-decoration:none;cursor:pointer}
    .prov-contact-link:hover{color:var(--purple);text-decoration:underline}
    .contact-popup{position:fixed;z-index:9999;background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;box-shadow:0 8px 32px rgba(0,0,0,.15);min-width:280px;max-width:340px}
    .contact-popup h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0 0 4px}
    .contact-popup .cp-code{font-size:11px;color:var(--gray-muted);margin-bottom:14px}
    .contact-popup .cp-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-top:1px solid var(--border-light);font-size:13px}
    .contact-popup .cp-row:first-of-type{border-top:none}
    .contact-popup .cp-label{font-size:11px;color:var(--gray-muted);font-weight:600;min-width:60px}
    .contact-popup .cp-val{color:var(--gray-text)}
    .contact-popup .cp-val a{color:var(--purple);text-decoration:none;font-weight:600}
    .contact-popup .cp-val a:hover{text-decoration:underline}
    .contact-popup .cp-close{position:absolute;top:10px;right:12px;background:none;border:none;font-size:18px;color:var(--gray-muted);cursor:pointer;line-height:1}
    .contact-popup .cp-close:hover{color:var(--red)}

    @media(max-width:900px){.kpi-grid{grid-template-columns:1fr}.admin-table-wrap{overflow-x:auto}}
</style>
@endpush
@section('content')

<div class="kpi-grid anim">
    <button type="button" class="kpi neg-tab-btn" data-tab="deudas" onclick="switchNegocio('deudas', this)">
        <div class="bar" style="background:var(--red)"></div>
        <div class="kpi-label">Adeudos <span class="kpi-count">{{ $deudasCount }}</span></div>
        <div class="kpi-val">${{ number_format($deudasTotal, 0) }}</div>
        <div class="kpi-sub">{{ count($proveedoresDeudas) }} proveedor{{ count($proveedoresDeudas) !== 1 ? 'es' : '' }} con adeudo</div>
    </button>
    <button type="button" class="kpi neg-tab-btn" data-tab="ventas" onclick="switchNegocio('ventas', this)">
        <div class="bar" style="background:var(--green)"></div>
        <div class="kpi-label">Compras totales <span class="kpi-count">{{ $ventasCount }}</span></div>
        <div class="kpi-val">${{ number_format($ventasTotales, 0) }}</div>
        <div class="kpi-sub">{{ count($proveedoresVentas) }} proveedor{{ count($proveedoresVentas) !== 1 ? 'es' : '' }}</div>
    </button>
    <button type="button" class="kpi neg-tab-btn" data-tab="cobrado" onclick="switchNegocio('cobrado', this)">
        <div class="bar" style="background:var(--purple)"></div>
        <div class="kpi-label">Pagado <span class="kpi-count">{{ $cobradoCount }}</span></div>
        <div class="kpi-val">${{ number_format($cobradoTotal, 0) }}</div>
        <div class="kpi-sub">{{ count($proveedoresCobrado) }} proveedor{{ count($proveedoresCobrado) !== 1 ? 'es' : '' }} pagados</div>
    </button>
</div>

@php
    $paneles = [
        'ventas' => ['label' => 'Compras totales', 'lista' => $proveedoresVentas, 'desc' => 'Compras a proveedores (facturas sin cancelar)'],
        'deudas' => ['label' => 'Adeudos', 'lista' => $proveedoresDeudas, 'desc' => 'Facturas pendientes de pago a proveedores'],
        'cobrado' => ['label' => 'Pagado', 'lista' => $proveedoresCobrado, 'desc' => 'Facturas ya pagadas a proveedores'],
    ];
@endphp

@foreach($paneles as $key => $panel)
<div class="neg-detail anim" id="panel-{{ $key }}">
    <div class="section-meta">
        <strong>{{ $panel['label'] }}</strong> — {{ $panel['desc'] }}
    </div>
    @if(count($panel['lista']))
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th>Categoría</th>
                    <th class="num">Score</th>
                    <th class="num">Facturas</th>
                    <th class="num">Monto</th>
                    <th>Hora</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($panel['lista'] as $prov)
                <tr>
                    <td class="code-col">{{ $prov['codigo'] ?? '—' }}</td>
                    <td class="name-col">
                        <a href="#" class="prov-contact-link" onclick="mostrarContacto(this, event)" 
                           data-nombre="{{ $prov['nombre'] }}" 
                           data-correo="{{ $prov['correo'] ?? '' }}" 
                           data-telefono="{{ $prov['telefono'] ?? '' }}" 
                           data-codigo="{{ $prov['codigo'] ?? '' }}">{{ $prov['nombre'] }}</a>
                    </td>
                    <td style="font-size:12px;color:var(--gray-muted);font-weight:600;">{{ $prov['categoria'] ?? '—' }}</td>
                    <td class="num">
                        @php
                            $scoreVal = $prov['score'] > 0 ? number_format($prov['score'], 0) : 0;
                            $scoreColor = $prov['score'] >= 85 ? 'var(--green)' : ($prov['score'] >= 60 ? 'var(--amber)' : 'var(--red)');
                        @endphp
                        @if($prov['score'] > 0)
                            <span style="color:{{ $scoreColor }};font-weight:700;">{{ $scoreVal }}%</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="num">{{ $prov['facturas'] }}</td>
                    <td class="num">${{ number_format($prov['monto'], 2) }}</td>
                    <td style="font-size:11px;color:var(--gray-muted);white-space:nowrap;">{{ $prov['ultima_hora'] ?? '—' }}</td>
                    <td>
                        @if($prov['codigo'])
                        <a href="{{ route('admin.proveedor-facturas', $prov['codigo']) }}" style="font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;">Ver facturas →</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Total ({{ count($panel['lista']) }} proveedores)</td>
                    <td class="num">{{ collect($panel['lista'])->sum('facturas') }}</td>
                    <td class="num">${{ number_format(collect($panel['lista'])->sum('monto'), 2) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="empty-state">No hay facturas de proveedores en esta categoría.</div>
    @endif
</div>
@endforeach

@endsection
@push('scripts')
<script>
function switchNegocio(tab, btn) {
    var panel = document.getElementById('panel-' + tab);
    var wasActive = btn.classList.contains('active');

    document.querySelectorAll('.neg-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.neg-detail').forEach(function(p) { p.classList.remove('active'); });

    if (wasActive) {
        return;
    }

    btn.classList.add('active');
    if (panel) {
        panel.classList.add('active');
    }
}

// Auto-desplegar Deudas al cargar
document.addEventListener('DOMContentLoaded', function() {
    var btnDeudas = document.querySelector('.neg-tab-btn[data-tab="deudas"]');
    if (btnDeudas) {
        switchNegocio('deudas', btnDeudas);
    }
});

function mostrarContacto(el, e) {
    e.preventDefault();
    // Cerrar popup anterior si existe
    var viejo = document.getElementById('contactPopup');
    if (viejo) viejo.remove();

    var nombre = el.getAttribute('data-nombre');
    var correo = el.getAttribute('data-correo');
    var telefono = el.getAttribute('data-telefono');
    var codigo = el.getAttribute('data-codigo');

    var popup = document.createElement('div');
    popup.id = 'contactPopup';
    popup.className = 'contact-popup';

    var html = '<button class="cp-close" onclick="document.getElementById(\'contactPopup\').remove()">&times;</button>';
    html += '<h4>' + nombre + '</h4>';
    html += '<div class="cp-code">Código: ' + (codigo || '—') + '</div>';
    html += '<div class="cp-row"><span class="cp-label">Correo</span><span class="cp-val">' + (correo ? '<a href="mailto:' + correo + '">' + correo + '</a>' : '—') + '</span></div>';
    html += '<div class="cp-row"><span class="cp-label">Teléfono</span><span class="cp-val">' + (telefono ? '<a href="tel:' + telefono + '">' + telefono + '</a>' : '—') + '</span></div>';

    popup.innerHTML = html;

    // Posicionar cerca del click
    var rect = el.getBoundingClientRect();
    popup.style.top = (rect.bottom + window.scrollY + 8) + 'px';
    popup.style.left = Math.min(rect.left, window.innerWidth - 360) + 'px';

    document.body.appendChild(popup);

    // Cerrar al hacer clic fuera
    setTimeout(function() {
        document.addEventListener('click', function cerrar(ev) {
            if (!popup.contains(ev.target) && ev.target !== el) {
                popup.remove();
                document.removeEventListener('click', cerrar);
            }
        });
    }, 100);
}
</script>
@endpush
