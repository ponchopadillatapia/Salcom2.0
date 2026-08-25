@extends('layouts.admin')
@section('title', 'Pagos — '.$proveedor->nombre)
@section('hero')
<div class="hero-band">
    <h1>{{ $proveedor->nombre }}</h1>
    <p>Facturas pendientes · {{ $codigo }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .pag-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:16px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:100px}
    .adm-summary-pct{font-size:42px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .adm-summary-metrics{flex:1;display:flex;gap:28px;flex-wrap:wrap}
    .adm-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .adm-metric-val{font-size:22px;font-weight:700;color:var(--green)}

    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px;line-height:1.45}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .pag-alert.warn{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber)}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:18px;box-shadow:var(--shadow-sm)}
    .adm-section-head{padding:14px 18px;background:var(--gray-soft);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .adm-section-head h4{margin:0;font-size:14px;font-weight:700;color:var(--gray-text)}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 14px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:top}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .date-row td{background:var(--purple-subtle)!important;font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block;margin:1px}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.neut{background:var(--purple-subtle);color:var(--purple)}
    .aviso{color:var(--amber);font-size:11px;display:block;margin-top:2px}
    .empty{padding:48px 20px;text-align:center;color:var(--gray-muted)}

    .btn-pagar{padding:7px 14px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;white-space:nowrap}
    .btn-pagar:hover{background:#22a34d;color:#fff}
    .btn-pagar:disabled{opacity:.45;cursor:not-allowed}
    .btn-outline-pag{padding:7px 14px;background:#fff;color:var(--purple);border:1.5px solid var(--purple);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer}
    .btn-outline-pag:disabled{opacity:.45;cursor:not-allowed}
    .bulk-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border-light);background:var(--gray-soft)}
    .bulk-bar .sel-meta{font-size:12px;color:var(--gray-muted);font-weight:600}
    .bulk-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
    .bulk-actions input[type=date]{border:1.5px solid var(--border);border-radius:8px;padding:7px 10px;font-size:12px;font-family:inherit}
    .chk{width:16px;height:16px;accent-color:var(--purple);cursor:pointer}
    .btn-docs{padding:6px 12px;background:var(--purple-subtle);color:var(--purple);border:1px solid var(--purple);border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap}
    .btn-docs:hover{background:var(--purple);color:#fff}
    .btn-ver{padding:5px 10px;background:none;color:var(--purple);border:1px solid var(--border);border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
    .btn-ver:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .dias-count{font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2;white-space:nowrap}
    .dias-count.warn{color:var(--amber)}
    .dias-count.late{color:var(--red)}
    .dias-sub{font-size:10px;color:var(--gray-muted);margin-top:2px;white-space:nowrap}
    .actions-cell{display:flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end}

    .doc-panel{display:none;background:var(--gray-soft);padding:16px 18px;border-bottom:1px solid var(--border)}
    .doc-panel.open{display:block}
    .doc-panel h5{margin:0 0 10px;font-size:13px;font-weight:700;color:var(--gray-text)}
    .doc-list{display:flex;flex-wrap:wrap;gap:10px}
    .doc-link{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:var(--white);border:1px solid var(--border);border-radius:8px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none}
    .doc-link:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .doc-link.disabled{opacity:.4;pointer-events:none;color:var(--gray-muted)}
    .doc-link svg{flex-shrink:0}
</style>
@endpush
@section('content')
@php
    $monto = $facturas->sum(fn ($f) => (float) $f->total);
@endphp

<a class="pag-back anim" href="{{ route('admin.pagos') }}">← Volver a proveedores</a>

@if(session('error'))
    <div class="pag-alert err anim">{{ session('error') }}</div>
@endif
@if(session('mensaje'))
    <div class="pag-alert ok anim">{{ session('mensaje') }}</div>
@endif

@unless($expediente['ok'])
    <div class="pag-alert warn anim">
        Expediente incompleto (solo aviso): {{ implode(' · ', $expediente['motivos']) }}
    </div>
@endunless

@php
    $anticiposActivos = \App\Models\AnticipoProveedor::where('codigo_proveedor', $codigo)
        ->where('estatus', 'pagado')
        ->get();
@endphp
@if($anticiposActivos->count() > 0)
    {{-- Panel de anticipos oculto - se muestra al dar "Pagar seleccionadas" --}}
    <div id="modal-anticipos" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:14px;max-width:680px;width:100%;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2)">
            <div style="padding:18px 22px;background:#f3e8ff;border-bottom:1px solid #c4b5fd;border-radius:14px 14px 0 0">
                <h3 style="margin:0;font-size:16px;font-weight:700;color:#5b21b6">Anticipos activos</h3>
                <p style="margin:4px 0 0;font-size:12px;color:#7c3aed">¿Quieres aplicar algún anticipo a las facturas seleccionadas?</p>
            </div>
            <div style="padding:16px 22px" id="anticipos-lista-modal">
                @php $anticiposOrdenados = $anticiposActivos->sortByDesc('fecha'); @endphp
                @foreach($anticiposOrdenados as $ant)
                    <div style="margin-bottom:8px;padding:14px 16px;background:#faf5ff;border-radius:10px;border:1px solid #e9d5ff" id="anticipo-row-{{ $ant->id }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#6B3FA0">{{ $ant->folio_general }}</div>
                                <div style="font-size:12px;color:#7c3aed;margin-top:2px">${{ number_format((float)$ant->total_banco, 2) }} · {{ $ant->fecha?->format('d/m/Y') }}</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px">
                                <select class="select-factura-modal" id="sel-ant-{{ $ant->id }}" style="font-size:13px;padding:10px 14px;border:1.5px solid #c4b5fd;border-radius:8px;background:#fff;min-width:280px;color:#5b21b6;font-weight:500">
                                    <option value="">Seleccionar factura...</option>
                                </select>
                                <button type="button" onclick="aplicarAnticipo({{ $ant->id }})" style="font-size:13px;padding:10px 20px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;white-space:nowrap">Aplicar</button>
                            </div>
                        </div>
                        <div id="msg-ant-{{ $ant->id }}" style="display:none;margin-top:8px;padding:8px 12px;background:#ecfdf5;border:1px solid #059669;border-radius:6px;font-size:12px;color:#059669;font-weight:600"></div>
                    </div>
                @endforeach
            </div>
            <div style="padding:16px 22px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="cerrarModalAnticipos()" style="padding:10px 20px;background:var(--gray-soft);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Cerrar</button>
                <button type="button" onclick="cerrarYPagar()" style="padding:10px 20px;background:#059669;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit">Continuar con el pago →</button>
            </div>
        </div>
    </div>
@endif

<div class="adm-summary anim">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ $facturas->count() }}</div>
        <div class="adm-summary-label">Facturas pendientes</div>
    </div>
    <div class="adm-summary-metrics">
        <div>
            <div class="adm-metric-label">Monto total</div>
            <div class="adm-metric-val">${{ number_format($monto, 2) }}</div>
        </div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
        <a href="{{ route('admin.pagos.estado-cuenta', $codigo) }}" class="doc-link" style="padding:10px 16px" title="Descargar estado de cuenta histórico">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Estado de cuenta
        </a>
    </div>
</div>

<div class="adm-section anim">
    <div class="adm-section-head">
        <div>
            <h4>Facturas pendientes</h4>
            <div class="adm-section-meta">{{ $facturas->count() }} resultado{{ $facturas->count() !== 1 ? 's' : '' }} · agrupados por fecha de alta</div>
        </div>
    </div>
    @if($facturas->isEmpty())
        <div class="empty">Sin facturas pendientes para este proveedor.</div>
    @else
        <form method="POST" action="{{ route('admin.pagos.store') }}" id="formPagarLote">
            @csrf
            <input type="hidden" name="codigo_proveedor" value="{{ $codigo }}">
            <div class="bulk-bar">
                <div class="sel-meta"><span id="selCount">0</span> seleccionada(s)</div>
                <div class="bulk-actions">
                    <button type="submit" name="confirmar" value="1" class="btn-pagar" id="btnConfirmar" disabled
                        onclick="return confirm('¿Pagar y confirmar las facturas seleccionadas? Se usarán los datos del XML.');">
                        Pagar seleccionadas
                    </button>
                </div>
            </div>
            <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="chk" id="chkAll" title="Seleccionar todas"></th>
                        <th>Folio</th>
                        <th>Vencimiento</th>
                        <th>Flete</th>
                        <th>Régimen</th>
                        <th>Proveedor</th>
                        <th>Retenciones</th>
                        <th>Subtotal</th>
                        <th>Total</th>
                        <th>Saldo</th>
                        <th>Status</th>
                        <th>Hora</th>
                        <th style="text-align:right;">Docs</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lastDate = null; @endphp
                    @foreach($facturas as $f)
                        @php
                            $currentDate = $f->created_at ? $f->created_at->format('Y-m-d') : null;
                            $saldo = (float) $f->neto_pago;
                        @endphp
                        @if($currentDate !== $lastDate)
                            <tr class="date-row">
                                <td colspan="12">{{ $f->created_at ? $f->created_at->locale('es')->isoFormat('DD [de] MMMM YYYY') : 'Sin fecha' }}</td>
                            </tr>
                            @php $lastDate = $currentDate; @endphp
                        @endif
                        <tr>
                            <td><input type="checkbox" class="chk fact-chk" name="factura_ids[]" value="{{ $f->id }}"></td>
                            <td>
                                <strong style="color:var(--purple);">{{ $f->folio_display }}</strong>
                            </td>
                            <td>
                                @include('partials.celda-vencimiento', [
                                    'fecha' => $f->fecha_vencimiento,
                                    'plazo' => $f->dias_plazo,
                                ])
                            </td>
                            <td>
                                @if($f->es_fletera)
                                    <span class="pill warn">Sí</span>
                                @else
                                    <span class="pill neut">No</span>
                                @endif
                            </td>
                            <td>{{ $f->regimen_fiscal ?: '—' }}</td>
                            <td style="max-width:160px;">
                                <div style="font-weight:600;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $proveedor->nombre }}</div>
                                <div style="font-size:10px;color:var(--gray-muted);">{{ $codigo }}</div>
                            </td>
                            <td style="font-size:12px;white-space:nowrap;">
                                IVA ${{ number_format((float)($f->retencion_iva ?? 0), 2) }}<br>
                                ISR ${{ number_format((float)($f->retencion_isr ?? 0), 2) }}
                            </td>
                            <td class="monto">${{ number_format((float)$f->monto, 2) }}</td>
                            <td class="monto">${{ number_format((float)$f->total, 2) }}</td>
                            <td class="monto">${{ number_format($saldo, 2) }}</td>
                            <td style="min-width:180px;">
                                <span class="pill warn" style="margin-bottom:4px;">{{ ucfirst($f->estatus) }}</span>
                                @forelse($f->avisos_pago as $a)
                                    <span class="aviso">• {{ $a }}</span>
                                @empty
                                    <span class="pill ok">OK</span>
                                @endforelse
                            </td>
                            <td style="font-size:11px;color:var(--gray-muted);white-space:nowrap">{{ $f->created_at?->format('h:i a') ?? '—' }}</td>
                            <td>
                                <div class="actions-cell">
                                    <button type="button" class="btn-ver" onclick="toggleDocs({{ $f->id }})">
                                        Docs
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="13" style="padding:0;border:none;">
                                <div class="doc-panel" id="docs-{{ $f->id }}">
                                    <h5>Documentos adjuntos — {{ $f->folio_display }}</h5>
                                    <div class="doc-list">
                                        @if($f->archivo_pdf)
                                            <a class="doc-link" href="{{ asset('storage/'.$f->archivo_pdf) }}" target="_blank">PDF</a>
                                        @else
                                            <span class="doc-link disabled">PDF (no adjunto)</span>
                                        @endif
                                        @if($f->archivo_xml)
                                            <a class="doc-link" href="{{ asset('storage/'.$f->archivo_xml) }}" target="_blank">XML</a>
                                        @else
                                            <span class="doc-link disabled">XML (no adjunto)</span>
                                        @endif
                                        @if($f->archivo_oc)
                                            <a class="doc-link" href="{{ asset('storage/'.$f->archivo_oc) }}" target="_blank">Orden de compra</a>
                                        @else
                                            <span class="doc-link disabled">OC (no adjunta)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </form>
    @endif
</div>
<script>
function toggleDocs(id) {
    var el = document.getElementById('docs-' + id);
    if (el) el.classList.toggle('open');
}

var tieneAnticipos = {{ $anticiposActivos->count() > 0 ? 'true' : 'false' }};

function cerrarModalAnticipos() {
    document.getElementById('modal-anticipos').style.display = 'none';
}

function cerrarYPagar() {
    // Verificar que no haya anticipos con factura seleccionada sin aplicar
    var sinAplicar = false;
    document.querySelectorAll('.select-factura-modal').forEach(function(sel) {
        var row = sel.closest('[id^="anticipo-row-"]');
        if (row && row.style.opacity !== '0.4' && sel.value !== '') {
            sinAplicar = true;
        }
    });
    if (sinAplicar) {
        alert('Tienes un anticipo con factura seleccionada sin aplicar. Aplícalo o quita la selección para continuar.');
        return;
    }
    cerrarModalAnticipos();
    document.getElementById('formPagarLote').submit();
}

(function () {
    var all = document.getElementById('chkAll');
    var boxes = function () { return Array.prototype.slice.call(document.querySelectorAll('.fact-chk')); };
    var countEl = document.getElementById('selCount');
    var btnC = document.getElementById('btnConfirmar');

    function sync() {
        var checked = boxes().filter(function (b) { return b.checked; });
        if (countEl) countEl.textContent = checked.length;
        if (btnC) btnC.disabled = checked.length === 0;
        if (all) {
            var total = boxes().length;
            all.checked = total > 0 && checked.length === total;
            all.indeterminate = checked.length > 0 && checked.length < total;
        }
    }
    if (all) all.addEventListener('change', function () {
        boxes().forEach(function (b) { b.checked = all.checked; });
        sync();
    });
    boxes().forEach(function (b) { b.addEventListener('change', sync); });
    sync();

    // Interceptar el botón de pagar para mostrar modal de anticipos
    if (btnC && tieneAnticipos) {
        btnC.removeAttribute('onclick');
        btnC.setAttribute('type', 'button');
        btnC.addEventListener('click', function (e) {
            e.preventDefault();
            // Obtener facturas seleccionadas
            var seleccionadas = boxes().filter(function (b) { return b.checked; });
            if (seleccionadas.length === 0) {
                alert('Selecciona al menos una factura.');
                return;
            }
            // Llenar dropdowns del modal con solo las facturas seleccionadas
            var opciones = '<option value="">Seleccionar factura...</option>';
            seleccionadas.forEach(function (b) {
                var tr = b.closest('tr');
                var folio = tr.querySelector('td:nth-child(2) strong').textContent.trim();
                var total = tr.querySelector('.monto').textContent.trim();
                opciones += '<option value="' + b.value + '">' + folio + ' — ' + total + '</option>';
            });
            document.querySelectorAll('.select-factura-modal').forEach(function (sel) {
                sel.innerHTML = opciones;
            });
            // Mostrar modal
            document.getElementById('modal-anticipos').style.display = 'flex';
        });
    }
})();

// Cerrar modal al click fuera
var modalAnt = document.getElementById('modal-anticipos');
if (modalAnt) {
    modalAnt.addEventListener('click', function (e) {
        if (e.target === modalAnt) cerrarModalAnticipos();
    });
}

// Aplicar anticipo por AJAX (sin recargar)
function aplicarAnticipo(anticipoId) {
    var sel = document.getElementById('sel-ant-' + anticipoId);
    var facturaId = sel.value;
    if (!facturaId) {
        alert('Selecciona una factura primero.');
        return;
    }
    if (!confirm('¿Aplicar este anticipo a la factura seleccionada?')) return;

    fetch('/admin/anticipos/' + anticipoId + '/aplicar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ factura_id: facturaId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            var row = document.getElementById('anticipo-row-' + anticipoId);
            row.style.opacity = '0.4';
            row.style.pointerEvents = 'none';
            var msg = document.getElementById('msg-ant-' + anticipoId);
            msg.style.display = 'block';
            msg.textContent = '✓ ' + data.mensaje;
        } else {
            alert(data.mensaje || 'Error al aplicar.');
        }
    })
    .catch(function() { alert('Error de conexión.'); });
}
</script>
@endsection
