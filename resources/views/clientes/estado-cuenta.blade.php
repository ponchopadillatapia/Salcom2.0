@extends('layouts.cliente')
@section('title', 'Estado de Cuenta')
@section('hero')
<div class="hero-band">
    <h1>Estado de cuenta</h1>
    <p>Movimientos del periodo al estilo <strong>auxiliar de cuenta</strong>: cargos, abonos, saldo acumulado, vencimientos y tipo de cambio. Relacionado con tus <a class="cli-hero-link" href="{{ route('clientes.tracking') }}">pedidos</a> y facturas CFDI.</p>
</div>
@endsection

@push('styles')
<style>
    .cli-hero-link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .cli-hero-link:hover { text-decoration: underline; }
    .cli-notice { font-size: 12px; font-weight: 600; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber); padding: 10px 14px; border-radius: var(--radius-lg); margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px; }

    .summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-bottom:22px}
    .sum-card{background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);padding:20px 22px;box-shadow: var(--shadow-sm);transition: var(--transition)}
    .sum-card:hover{box-shadow: var(--shadow-md);border-color: rgba(107, 63, 160, 0.15)}
    .sum-label{font-size:11px;font-weight:700;color:var(--gray-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px}
    .sum-val{font-size:22px;font-weight:700;color:var(--gray-text);letter-spacing:-0.03em}
    .sum-sub{font-size:12px;color:var(--gray-muted);margin-top:8px;line-height:1.45}

    .info-banner{background: var(--purple-subtle);border:1px solid #d4c5e8;border-radius: var(--radius-lg);padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;font-size:13px;color:#4A2070}
    .info-banner svg{flex-shrink:0}
    .info-banner strong{font-weight:700}

    .contado-banner{background: var(--amber-bg);border:1px solid var(--amber);border-radius: var(--radius-lg);padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;font-size:13px;color:#92400e}

    .status-row{display:flex;align-items:flex-start;gap:14px;margin-bottom:22px;flex-wrap:wrap;padding:16px 18px;background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);box-shadow: var(--shadow-sm)}
    .status-item{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-text)}
    .status-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
    .dot-ok{background:var(--green)}.dot-review{background:var(--amber)}.dot-blocked{background:var(--red)}

    .ec-tabs{display:flex;gap:4px;margin-bottom:0;border-bottom:1px solid var(--border-light);padding:0 4px;background:var(--gray-soft);border-radius: var(--radius-lg) var(--radius-lg) 0 0}
    .ec-tab{font-size:13px;font-weight:600;padding:10px 16px;border:none;background:transparent;color:var(--gray-muted);cursor:pointer;font-family:inherit;border-radius:8px 8px 0 0}
    .ec-tab:hover{color:var(--gray-text)}
    .ec-tab[aria-selected="true"]{background:var(--white);color:var(--purple);box-shadow:0 -1px 0 var(--white)}

    .ec-panel{display:none;padding:0}
    .ec-panel[data-ec-active="true"]{display:block}

    .ec-filters{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px 18px;padding:16px 18px;border-bottom:1px solid var(--border-light);background:var(--white)}
    .ec-field{display:flex;flex-direction:column;gap:6px}
    .ec-field label{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .ec-field input,.ec-field select{border:1px solid var(--border-light);border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white)}
    .ec-field input:focus,.ec-field select:focus{outline:none;border-color:var(--purple-mid)}
    .ec-field-check{flex-direction:row;align-items:center;gap:8px;padding-top:20px}
    .ec-field-check label{text-transform:none;font-size:13px;font-weight:600;color:var(--gray-text)}
    .ec-field-actions{display:flex;align-items:flex-end;gap:10px}
    .btn-ec-primary{display:inline-flex;align-items:center;justify-content:center;padding:9px 18px;font-size:13px;font-weight:600;color:#fff;background:var(--purple);border:none;border-radius:8px;cursor:pointer;font-family:inherit}
    .btn-ec-primary:hover{filter:brightness(1.05)}
    .ec-radio-group{display:flex;flex-wrap:wrap;gap:12px 18px;padding:12px 18px;border-bottom:1px solid var(--border-light);background:var(--gray-soft);font-size:12px}
    .ec-radio-group span{font-weight:700;color:var(--gray-muted);margin-right:8px}
    .ec-radio-inline{display:inline-flex;align-items:center;gap:6px;cursor:pointer;color:var(--gray-text)}
    .ec-radio-inline input{accent-color:var(--purple)}

    .card{background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);overflow:hidden;box-shadow: var(--shadow-sm);transition: var(--transition)}
    .card:hover{box-shadow: var(--shadow-md)}
    .card-head{padding:16px 20px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:var(--gray-soft)}
    .card-head h3{font-size:15px;font-weight:700;color:var(--gray-text);margin:0;letter-spacing:-0.02em}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:12px;font-weight:600;color:var(--green);background: var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;font-family:inherit;transition: var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}

    .ec-report-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:16px;padding:16px 18px;border-bottom:1px solid var(--border-light);background:linear-gradient(180deg,var(--white) 0%,var(--gray-soft) 100%)}
    .ec-report-meta{font-size:13px;color:var(--gray-text);line-height:1.6}
    .ec-report-meta code{font-size:12px;background:var(--purple-subtle);padding:2px 8px;border-radius:4px;font-weight:600;color:var(--purple)}
    .ec-saldo-ini{text-align:right}
    .ec-saldo-ini .lbl{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .ec-saldo-ini .val{font-size:20px;font-weight:700;color:var(--gray-text);font-variant-numeric:tabular-nums;margin-top:4px}

    .cli-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
    .tabla{width:100%;min-width:920px;border-collapse:collapse;font-size:12px}
    .tabla th{font-size:10px;font-weight:700;color:var(--gray-muted);padding:10px 12px;text-align:left;border-bottom:1px solid var(--border-light);text-transform:uppercase;letter-spacing:.45px;white-space:nowrap}
    .tabla td{padding:10px 12px;color:var(--gray-text);border-bottom:1px solid var(--border-light);vertical-align:top}
    .tabla tr:last-child td{border-bottom:none}
    .tabla tbody tr:hover td{background: var(--purple-subtle)}
    .tabla .folio{font-weight:600;color: var(--purple);white-space:nowrap}
    .tabla .tipo{font-weight:700;font-variant-numeric:tabular-nums;color:var(--gray-muted);width:3rem}
    .tabla .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
    .tabla .neg{color:#15803d}
    .tabla .doc-pend{font-weight:600;color:var(--amber)}

    .badge{font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;display:inline-block}
    .badge-pagada{background: var(--green-bg);color: var(--green)}
    .badge-pendiente{background: var(--amber-bg);color: var(--amber)}
    .badge-vencida{background: var(--red-bg);color: var(--red)}

    .ec-class-placeholder{padding:28px 20px;color:var(--gray-muted);font-size:14px;line-height:1.55;max-width:560px}

    @media(max-width:768px){.summary-grid{grid-template-columns:1fr}.ec-saldo-ini{text-align:left;width:100%}}
</style>
@endpush

@section('content')
<div class="cli-notice" role="note">Datos de demostración · Integración API pendiente</div>

@php
    $tipo = session('cliente_tipo', 'minorista');
    $creditoAutorizado = false;
    $codigoCliente = session('cliente_codigo', '—');
    $nombreCliente = session('cliente_nombre', 'Cliente');
@endphp

@if(!$creditoAutorizado)
<div class="contado-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span><strong>Cliente nuevo — Pagos de contado.</strong> Tu cuenta aún no tiene crédito autorizado. Los pedidos se procesan contra pago.</span>
</div>
@endif

<div class="info-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4A2070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span><strong>{{ $nombreCliente }}</strong> · Código {{ $codigoCliente }} · {{ ucfirst($tipo) }}</span>
</div>

<div class="summary-grid">
    <div class="sum-card">
        <div class="sum-label">Tipo de cliente</div>
        <div class="sum-val">{{ ucfirst($tipo) }}</div>
        <div class="sum-sub">Clasificación comercial</div>
    </div>
    <div class="sum-card">
        <div class="sum-label">Límite de crédito</div>
        <div class="sum-val">{{ $creditoAutorizado ? '$50,000.00' : 'N/A' }}</div>
        <div class="sum-sub">{{ $creditoAutorizado ? 'Crédito autorizado' : 'Sin crédito — Contado' }}</div>
    </div>
    <div class="sum-card">
        <div class="sum-label">Saldo al corte (demo)</div>
        <div class="sum-val" id="sumSaldoCorte">—</div>
        <div class="sum-sub" id="sumSaldoSub">Según movimientos filtrados</div>
    </div>
</div>

<div class="status-row">
    <div class="status-item"><div class="status-dot dot-review"></div><strong>Por liquidar</strong></div>
    <div class="status-item" style="color:var(--gray-muted);max-width:720px;line-height:1.5">Documentos con saldo pendiente aparecen marcados en la columna de vencimiento.</div>
</div>

<div class="card" style="padding:0">
    <div class="ec-tabs" role="tablist">
        <button type="button" class="ec-tab" role="tab" id="ecTab1" aria-selected="true" aria-controls="ecPanel1" data-ec-tab="principal">1 Principal</button>
        <button type="button" class="ec-tab" role="tab" id="ecTab2" aria-selected="false" aria-controls="ecPanel2" data-ec-tab="clasif">2 Clasificaciones</button>
    </div>

    <div class="ec-panel" id="ecPanel1" role="tabpanel" aria-labelledby="ecTab1" data-ec-active="true">
        <div class="ec-filters">
            <div class="ec-field">
                <label for="ecFechaIni">Fecha inicial</label>
                <input type="date" id="ecFechaIni" name="fecha_ini">
            </div>
            <div class="ec-field">
                <label for="ecFechaFin">Fecha final</label>
                <input type="date" id="ecFechaFin" name="fecha_fin">
            </div>
            <div class="ec-field">
                <label for="ecMoneda">Moneda</label>
                <select id="ecMoneda" name="moneda" aria-label="Moneda">
                    <option value="MXN" selected>Peso mexicano (MXN)</option>
                    <option value="USD">Dólar estadounidense (USD)</option>
                </select>
            </div>
            <div class="ec-field ec-field-check">
                <input type="checkbox" id="ecSoloPend" name="solo_pendiente">
                <label for="ecSoloPend">Solo con saldo pendiente</label>
            </div>
            <div class="ec-field ec-field-actions">
                <button type="button" class="btn-ec-primary" id="ecAplicar">Consultar periodo</button>
            </div>
        </div>
        <div class="ec-radio-group" role="group" aria-label="Alcance del listado">
            <span>Listado:</span>
            <label class="ec-radio-inline"><input type="radio" name="ec_listado" value="todos" checked> Todos los movimientos</label>
            <label class="ec-radio-inline"><input type="radio" name="ec_listado" value="facturas"> Solo facturas y NC</label>
            <label class="ec-radio-inline"><input type="radio" name="ec_listado" value="cobros"> Solo cobros y pagos</label>
        </div>

        <div class="card-head" style="border-top:none">
            <h3>Estado de cuenta — movimientos</h3>
            <button type="button" class="btn-export" id="btnExportMovs">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar CSV
            </button>
        </div>

        <div class="ec-report-head">
            <div class="ec-report-meta">
                <div><strong>Cliente:</strong> <code>{{ $codigoCliente }}</code></div>
                <div><strong>Nombre:</strong> {{ $nombreCliente }}</div>
                <div style="margin-top:8px;font-size:12px;color:var(--gray-muted)">Periodo: <span id="ecPeriodoTxt">—</span> · Moneda: <span id="ecMonedaTxt">MXN</span></div>
            </div>
            <div class="ec-saldo-ini">
                <div class="lbl">Saldo inicial</div>
                <div class="val" id="ecSaldoIniVal">$0.00</div>
            </div>
        </div>

        <div class="cli-table-scroll">
            <table class="tabla" id="tablaMovimientos">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Documento</th>
                        <th>Descripción</th>
                        <th class="num">Cargo</th>
                        <th class="num">Abono</th>
                        <th class="num">Saldo</th>
                        <th>Vencimiento</th>
                        <th class="num">T. cambio</th>
                        <th>Ref.</th>
                    </tr>
                </thead>
                <tbody id="movimientosBody"></tbody>
            </table>
        </div>
    </div>

    <div class="ec-panel" id="ecPanel2" role="tabpanel" aria-labelledby="ecTab2" data-ec-active="false">
        <div class="ec-class-placeholder">
            Aquí irán filtros por <strong>clasificaciones comerciales</strong> (zona, sector, agente, lista de precios), alineados al reporte administrativo. En el portal B2B el cliente suele ver solo su cuenta; esta pestaña queda lista para cuando la API envíe segmentos.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const codigoCliente = @json($codigoCliente);
    const nombreCliente = @json($nombreCliente);

    /** ISO date YYYY-MM-DD — demo movements */
    const movimientosBase = [
        { d: '2026-04-28', tipo: 'FG', doc: '001220', desc: 'Factura CFDI 4.0', cargo: 9802.00, abono: 0, venc: '2026-06-27', tc: 1, ref: '55201', pend: false },
        { d: '2026-05-02', tipo: 'RBBV', doc: '88412', desc: 'REP BBVA — aplicación pago 4.0', cargo: 0, abono: 9802.00, venc: '—', tc: 1, ref: '55288', pend: false },
        { d: '2026-05-04', tipo: 'FG', doc: '001236', desc: 'Factura CFDI 4.0', cargo: 5481.00, abono: 0, venc: '2026-07-03', tc: 1, ref: '56279', pend: true },
        { d: '2026-05-05', tipo: 'FG', doc: '001237', desc: 'Factura CFDI 4.0', cargo: 6786.00, abono: 0, venc: '2026-07-04', tc: 1, ref: '56310', pend: true },
        { d: '2026-05-06', tipo: 'DEVG', doc: '612', desc: 'Devolución sobre venta', cargo: 0, abono: 3024.00, venc: '—', tc: 1, ref: '56402', pend: false },
        { d: '2026-05-07', tipo: 'GNC', doc: '9980', desc: 'Nota de crédito CFDI 4.0', cargo: 0, abono: 144215.73, venc: '—', tc: 1, ref: '56444', pend: false },
        { d: '2026-05-08', tipo: 'FG', doc: '001238', desc: 'Factura CFDI 4.0', cargo: 5452.00, abono: 0, venc: '2026-07-07', tc: 1, ref: '56501', pend: true },
        { d: '2026-05-09', tipo: 'RBBV', doc: '88490', desc: 'REP BBVA — aplicación pago 4.0', cargo: 0, abono: 5000.00, venc: '—', tc: 1, ref: '56522', pend: false },
    ];

    const saldoInicialPeriodo = 12450.00;

    const fmtMoney = n => '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtDate = iso => {
        const [y, m, da] = iso.split('-');
        return da + '/' + m + '/' + y;
    };

    function defaultDates() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const last = new Date(y, now.getMonth() + 1, 0).getDate();
        document.getElementById('ecFechaIni').value = y + '-' + m + '-01';
        document.getElementById('ecFechaFin').value = y + '-' + m + '-' + String(last).padStart(2, '0');
    }

    function parseListadoFilter() {
        const r = document.querySelector('input[name="ec_listado"]:checked');
        return r ? r.value : 'todos';
    }

    function tipoMatchesListado(row, listado) {
        if (listado === 'todos') return true;
        const cobros = ['RBBV', 'REP', 'COB'];
        const fact = ['FG', 'GNC', 'DEVG', 'NC'];
        if (listado === 'cobros') return cobros.some(p => row.tipo.startsWith(p)) || row.desc.toLowerCase().includes('pago');
        if (listado === 'facturas') return fact.includes(row.tipo) || row.tipo === 'FG';
        return true;
    }

    function filterRows() {
        const ini = document.getElementById('ecFechaIni').value;
        const fin = document.getElementById('ecFechaFin').value;
        const soloPend = document.getElementById('ecSoloPend').checked;
        const listado = parseListadoFilter();
        let rows = movimientosBase.filter(r => r.d >= ini && r.d <= fin);
        rows = rows.filter(r => tipoMatchesListado(r, listado));
        if (soloPend) rows = rows.filter(r => r.pend);
        return rows.sort((a, b) => a.d.localeCompare(b.d) || a.doc.localeCompare(b.doc, undefined, { numeric: true }));
    }

    function computeRunning(rows) {
        let saldo = saldoInicialPeriodo;
        return rows.map(r => {
            saldo += r.cargo - r.abono;
            return { ...r, saldo };
        });
    }

    function render() {
        const ini = document.getElementById('ecFechaIni').value;
        const fin = document.getElementById('ecFechaFin').value;
        const moneda = document.getElementById('ecMoneda').value;
        document.getElementById('ecPeriodoTxt').textContent = fmtDate(ini) + ' — ' + fmtDate(fin);
        document.getElementById('ecMonedaTxt').textContent = moneda;
        document.getElementById('ecSaldoIniVal').textContent = fmtMoney(saldoInicialPeriodo);

        const filtered = filterRows();
        const withSaldo = computeRunning(filtered);
        const body = document.getElementById('movimientosBody');

        if (!withSaldo.length) {
            body.innerHTML = '<tr><td colspan="10" style="text-align:center;color:var(--gray-muted);padding:36px 16px">No hay movimientos en el periodo con los filtros seleccionados.</td></tr>';
        } else {
            body.innerHTML = withSaldo.map(r => {
                const vencClass = r.pend ? 'doc-pend' : '';
                const abonoCell = r.abono > 0 ? '<span class="neg">' + fmtMoney(-r.abono) + '</span>' : '—';
                return '<tr>' +
                    '<td>' + fmtDate(r.d) + '</td>' +
                    '<td class="tipo">' + r.tipo + '</td>' +
                    '<td class="folio">' + r.doc + '</td>' +
                    '<td>' + r.desc + '</td>' +
                    '<td class="num">' + (r.cargo > 0 ? fmtMoney(r.cargo) : '—') + '</td>' +
                    '<td class="num">' + abonoCell + '</td>' +
                    '<td class="num">' + fmtMoney(r.saldo) + '</td>' +
                    '<td class="' + vencClass + '">' + (r.venc === '—' ? '—' : fmtDate(r.venc)) + '</td>' +
                    '<td class="num">' + r.tc.toFixed(4) + '</td>' +
                    '<td>(' + (r.pend ? '1' : '0') + ') ' + r.ref + '</td>' +
                    '</tr>';
            }).join('');
        }

        const ultimo = withSaldo.length ? withSaldo[withSaldo.length - 1].saldo : saldoInicialPeriodo;
        document.getElementById('sumSaldoCorte').textContent = fmtMoney(ultimo);
        document.getElementById('sumSaldoSub').textContent = withSaldo.length
            ? (filtered.length + ' movimiento' + (filtered.length === 1 ? '' : 's') + ' en periodo')
            : 'Sin movimientos en el filtro';
    }

    function exportCsv() {
        const table = document.getElementById('tablaMovimientos');
        if (!table) return;
        const rows = table.querySelectorAll('tr');
        const csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('th, td');
            const rowData = [];
            cols.forEach(col => {
                const t = col.innerText.replace(/"/g, '""').trim().replace(/\s+/g, ' ');
                rowData.push('"' + t + '"');
            });
            csv.push(rowData.join(','));
        });
        const blob = new Blob(['\uFEFF' + 'Cliente,' + codigoCliente + '\nNombre,' + nombreCliente + '\n\n' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'Estado_cuenta_' + codigoCliente + '_' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click();
    }

    document.getElementById('ecAplicar').addEventListener('click', render);
    document.getElementById('btnExportMovs').addEventListener('click', exportCsv);
    document.getElementById('ecSoloPend').addEventListener('change', render);
    document.getElementById('ecMoneda').addEventListener('change', render);
    document.querySelectorAll('input[name="ec_listado"]').forEach(el => el.addEventListener('change', render));

    document.querySelectorAll('[data-ec-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
            const principal = btn.getAttribute('data-ec-tab') === 'principal';
            document.getElementById('ecTab1').setAttribute('aria-selected', principal ? 'true' : 'false');
            document.getElementById('ecTab2').setAttribute('aria-selected', principal ? 'false' : 'true');
            document.getElementById('ecPanel1').setAttribute('data-ec-active', principal ? 'true' : 'false');
            document.getElementById('ecPanel2').setAttribute('data-ec-active', principal ? 'false' : 'true');
        });
    });

    defaultDates();
    render();
})();
</script>
@endpush
