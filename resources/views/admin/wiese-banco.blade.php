@extends('layouts.admin')
@section('title', 'WieseBanco — '.$bancoNombre)
@section('hero')
<div class="hero-band">
    <h1>{{ $bancoNombre }}</h1>
    <p>Registro bancario WieseBanco</p>
</div>
@endsection
@push('styles')
<style>
    /* ===== Paleta Quicken (replicada de las capturas) ===== */
    :root {
        /* Paleta LILA/MORADO (en vez de azul), para que combine con el resto del sistema. */
        --qk-azul-barra: #ede6f7;      /* barra lila clara del título */
        --qk-azul-borde: #c4b0e0;      /* borde lila */
        --qk-fila-azul: #f4eefb;       /* bandeado lila clarito */
        --qk-fila-blanca: #ffffff;
        --qk-activa: #e0cff5;          /* fila que se está capturando (lila) */
        --qk-texto: #2d2440;
        --qk-gris-cat: #7a6b8d;        /* la Category va en gris lila */
        --qk-encabezado: #d8c9ef;      /* fondo encabezados de columna (lila) */
    }

    .qk-registro {
        background: #fff;
        border: 1px solid var(--qk-azul-borde);
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 230px);
        min-height: 440px;
        font-family: Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Cabecera azul: nombre de la cuenta + pestañas Register/Overview */
    .qk-top {
        background: linear-gradient(#f4eefb, var(--qk-azul-barra));
        border-bottom: 1px solid var(--qk-azul-borde);
        display: flex;
        align-items: flex-end;
        gap: 14px;
        padding: 8px 12px 0;
    }
    .qk-cuenta {
        font-weight: 700;
        font-size: 13px;
        color: #4a2078;
        padding: 4px 10px 8px;
    }
    .qk-tabs { display: flex; gap: 2px; }
    .qk-tab {
        font-size: 12px;
        padding: 5px 16px;
        border: 1px solid var(--qk-azul-borde);
        border-bottom: none;
        border-radius: 6px 6px 0 0;
        background: #e0d3f2;
        color: #5b3a86;
        cursor: default;
    }
    .qk-tab.activa { background: #fff; font-weight: 700; color: #4a2078; }

    /* Barra de acciones: Delete | Find | Transfer | ... */
    .qk-acciones {
        display: flex;
        align-items: center;
        gap: 2px;
        background: #eef3fa;
        border-bottom: 1px solid var(--qk-azul-borde);
        padding: 4px 10px;
        flex-wrap: wrap;
    }
    .qk-accion {
        font-size: 12px;
        color: #5b3a86;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 4px;
        padding: 3px 9px;
        cursor: pointer;
    }
    .qk-accion:hover { background: #dbe7f7; border-color: var(--qk-azul-borde); }
    .qk-accion[disabled] { opacity: .45; cursor: not-allowed; }
    .qk-accion-sep { width: 1px; height: 16px; background: var(--qk-azul-borde); margin: 0 4px; }

    /* Zona de scroll de la hoja */
    .qk-sheet-wrap {
        flex: 1;
        overflow: auto;
        overscroll-behavior: contain;
        background: #fff;
    }
    .qk-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        font-size: 12px;
        color: var(--qk-texto);
        table-layout: fixed;
    }
    /* La columna Memo es la flexible: se estira o encoge para que todo quepa. */
    .c-memo { width: auto; }
    .qk-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--qk-encabezado);
        color: #4a2078;
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        padding: 6px 10px;
        border-right: 1px solid #b4c6e0;
        border-bottom: 1px solid #93aacb;
        white-space: nowrap;
    }
    .qk-table thead th.r { text-align: right; }
    .qk-table thead th.c { text-align: center; }

    /* Bandeado azul/blanco de las filas guardadas */
    .qk-table tbody tr.guardada:nth-child(odd) td { background: var(--qk-fila-azul); }
    .qk-table tbody tr.guardada:nth-child(even) td { background: var(--qk-fila-blanca); }
    .qk-table tbody td {
        border-right: 1px solid #e2e9f3;
        border-bottom: 1px solid #e2e9f3;
        padding: 0;
        vertical-align: top;
        height: 42px;
    }
    .qk-table tbody td.r .qk-static,
    .qk-table tbody td.r .qk-cell { text-align: right; font-variant-numeric: tabular-nums; }

    /* Celda de texto fijo (fila guardada) con Payee arriba y Category gris abajo */
    .qk-static { padding: 4px 10px; line-height: 1.3; }
    .qk-payee-top { color: var(--qk-texto); }
    .qk-cat-bottom { color: var(--qk-gris-cat); font-size: 11px; }

    /* Fila guardada SELECCIONADA (para borrar): resaltada en azul más fuerte. */
    .qk-table tbody tr.guardada.seleccionada td { background: #c9adec !important; }
    .qk-table tbody tr.guardada { cursor: pointer; }

    /* Fila de captura (inputs) */
    .qk-table tbody tr.captura td { background: #fff; }
    .qk-table tbody tr.captura.wb-row-activa td { background: var(--qk-activa); }
    .qk-cell {
        display: block;
        width: 100%;
        height: 42px;
        border: none;
        outline: none;
        background: transparent;
        font: inherit;
        color: var(--qk-texto);
        padding: 0 10px;
        box-sizing: border-box;
    }
    .qk-cell:focus { background: #fff; box-shadow: inset 0 0 0 2px #6B3FA0; }
    .qk-cell.r { text-align: right; }
    .qk-payee-wrap { display: flex; flex-direction: column; height: 42px; }
    .qk-payee-wrap .qk-cell { height: 21px; }
    .qk-payee-wrap .qk-cell:first-child { font-weight: 600; }
    .qk-payee-wrap .qk-cell:last-child { color: var(--qk-gris-cat); font-size: 11px; }
    .qk-cell[readonly] { color: #556; background: #f2f5fa; }

    /* Anchos de columna */
    .c-date { width: 95px; }
    .c-num { width: 70px; }
    .c-payee { width: 280px; }
    .c-payment { width: 115px; }
    .c-deposit { width: 115px; }
    .c-balance { width: 125px; }

    /* Pie: Ending Balance a la derecha */
    .qk-pie {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        background: #eef3fa;
        border-top: 1px solid var(--qk-azul-borde);
        padding: 7px 16px;
        font-size: 12px;
        color: #4a2078;
    }
    .qk-pie strong { font-size: 13px; }
</style>
@endpush
@section('content')
@php
    // Saldo final = el balance del último movimiento guardado (o el saldo de la cuenta).
    $endingBalance = $movimientos->isNotEmpty()
        ? (float) $movimientos->last()->balance
        : (float) optional($cuenta)->saldo_actual;
@endphp
<div class="qk-registro">
    {{-- Cabecera azul: nombre de cuenta + pestañas --}}
    <div class="qk-top">
        <span class="qk-cuenta">{{ $bancoNombre }}</span>
        <div class="qk-tabs">
            <span class="qk-tab activa">Register</span>
            {{-- Overview se quitó: dirección no la usa / no está definida. --}}
        </div>
    </div>

    {{-- Barra de acciones estilo Quicken --}}
    <div class="qk-acciones">
        <button type="button" class="qk-accion" id="qkDelete" disabled title="Selecciona un movimiento para borrar">Delete</button>
        <span class="qk-accion-sep"></span>
        <button type="button" class="qk-accion" id="qkFind">Find</button>
        <span class="qk-accion-sep"></span>
        <button type="button" class="qk-accion" disabled title="Próximamente">Transfer</button>
        <button type="button" class="qk-accion" disabled title="Próximamente">Reconcile</button>
        <button type="button" class="qk-accion" disabled title="Próximamente">Write Checks</button>
        <button type="button" class="qk-accion" disabled title="Próximamente">Set Up Online</button>
    </div>

    <div class="qk-sheet-wrap" id="wbSheet" aria-label="Registro bancario Wiese">
        <table class="qk-table" id="wbTable">
            <thead>
                <tr>
                    <th class="c-date">Date &#9650;</th>
                    <th class="c-num">Num</th>
                    <th class="c-payee">Payee / Category</th>
                    <th class="c-memo">Memo</th>
                    <th class="c-payment r">Payment</th>
                    <th class="c-deposit r">Deposit</th>
                    <th class="c-balance r">Balance</th>
                </tr>
            </thead>
            <tbody>
                {{-- Movimientos YA guardados (solo lectura, bandeados). --}}
                @foreach ($movimientos as $mov)
                <tr class="guardada" data-id="{{ $mov->id }}">
                    <td class="c-date"><div class="qk-static">{{ \Illuminate\Support\Carbon::parse($mov->fecha)->format('d/m/Y') }}</div></td>
                    <td class="c-num"><div class="qk-static">{{ $mov->num }}</div></td>
                    <td class="c-payee">
                        <div class="qk-static">
                            <div class="qk-payee-top">{{ $mov->payee }}</div>
                            <div class="qk-cat-bottom">{{ $mov->categoria }}</div>
                        </div>
                    </td>
                    <td class="c-memo"><div class="qk-static">{{ $mov->memo }}</div></td>
                    <td class="c-payment r"><div class="qk-static">{{ (float) $mov->payment != 0 ? number_format($mov->payment, 2) : '' }}</div></td>
                    <td class="c-deposit r"><div class="qk-static">{{ (float) $mov->deposit != 0 ? number_format($mov->deposit, 2) : '' }}</div></td>
                    <td class="c-balance r"><div class="qk-static">{{ number_format($mov->balance, 2) }}</div></td>
                </tr>
                @endforeach

                {{-- Una sola fila de captura al final (como Quicken). Al guardar aparece otra. --}}
                @include('admin.partials.wiese-banco-row')
            </tbody>
        </table>
        <template id="wbRowTpl">@include('admin.partials.wiese-banco-row')</template>
    </div>

    {{-- Pie con el saldo final --}}
    <div class="qk-pie">
        <span>Ending Balance:</span>
        <strong id="qkEndingBalance">{{ number_format($endingBalance, 2) }}</strong>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
    var table = document.getElementById('wbTable');
    var tpl = document.getElementById('wbRowTpl');
    if (!table || !tpl) return;
    var tbody = table.querySelector('tbody');

    var URL_GUARDAR = "{{ route('admin.wiese-banco.guardar', ['banco' => $bancoKey]) }}";
    var CSRF = "{{ csrf_token() }}";
    var endingEl = document.getElementById('qkEndingBalance');

    function cells() {
        return Array.prototype.slice.call(table.querySelectorAll('.qk-cell'));
    }
    function addRow() {
        tbody.appendChild(tpl.content.cloneNode(true));
    }
    function fechaISO(txt) {
        txt = (txt || '').trim();
        var m = txt.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (m) return m[3] + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[1]).slice(-2);
        return txt;
    }
    function leerFila(tr) {
        function val(col) {
            var el = tr.querySelector('.qk-cell[data-col="' + col + '"]');
            return el ? el.value.trim() : '';
        }
        return { tr: tr, date: val('date'), payee: val('payee'), category: val('category'),
                 memo: val('memo'), payment: val('payment'), deposit: val('deposit') };
    }
    function listaParaGuardar(d) {
        var tieneMonto = (parseFloat(d.payment) > 0) || (parseFloat(d.deposit) > 0);
        return d.date !== '' && tieneMonto;
    }

    function guardarFila(tr) {
        if (tr.dataset.guardada === '1' || tr.dataset.guardando === '1') return;
        var d = leerFila(tr);
        if (!listaParaGuardar(d)) return;
        tr.dataset.guardando = '1';

        fetch(URL_GUARDAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({
                fecha: fechaISO(d.date), payee: d.payee, categoria: d.category,
                memo: d.memo, payment: d.payment || 0, deposit: d.deposit || 0
            })
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
            tr.dataset.guardando = '';
            if (!res.ok || !res.j.ok) { tr.style.outline = '2px solid #c0392b'; return; }
            var numEl = tr.querySelector('.qk-cell[data-col="num"]');
            var balEl = tr.querySelector('.qk-cell[data-col="balance"]');
            if (numEl) numEl.value = res.j.num;
            if (balEl) balEl.value = res.j.balance;
            tr.dataset.guardada = '1';
            tr.classList.remove('wb-row-activa');
            tr.classList.add('guardada');          // ahora cuenta como fila guardada (seleccionable)
            tr.setAttribute('data-id', res.j.id);   // id para poder borrarla después
            tr.querySelectorAll('.qk-cell').forEach(function (el) { el.readOnly = true; });
            // Actualizar el Ending Balance del pie.
            if (endingEl) endingEl.textContent = Number(res.j.balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            // Agregar una fila vacía nueva si ya no quedan, con el SIGUIENTE num precargado.
            if (!tbody.querySelector('tr.captura:not([data-guardada="1"]) .qk-cell[data-col="date"]')) {
                addRow();
                var nuevaFila = tbody.querySelector('tr.captura:not([data-guardada="1"])');
                if (nuevaFila) {
                    var nEl = nuevaFila.querySelector('.qk-cell[data-col="num"]');
                    if (nEl) nEl.value = Number(res.j.num) + 1; // el siguiente consecutivo
                }
            }
        })
        .catch(function () { tr.dataset.guardando = ''; tr.style.outline = '2px solid #c0392b'; });
    }

    // ORDEN ESTRICTO de captura (izq -> der). Solo se avanza con Enter y en este orden.
    var ORDEN = ['payee', 'category', 'memo', 'payment', 'deposit'];

    // Devuelve la fila de captura activa (la que NO está guardada).
    function filaCaptura() {
        return tbody.querySelector('tr.captura:not([data-guardada="1"])');
    }

    // Enfoca una celda por su nombre de columna dentro de la fila de captura.
    function enfocar(col) {
        var tr = filaCaptura();
        if (!tr) return;
        var el = tr.querySelector('.qk-cell[data-col="' + col + '"]');
        if (el) { el.focus(); el.select && el.select(); }
    }

    table.addEventListener('focusin', function (e) {
        var tr = e.target.closest('tr');
        if (!tr) return;
        table.querySelectorAll('tr.wb-row-activa').forEach(function (el) { el.classList.remove('wb-row-activa'); });
        tr.classList.add('wb-row-activa');
    });

    // Al terminar deposit y guardar, el foco sale solo; también guardamos si pierde foco.
    table.addEventListener('focusout', function (e) {
        var tr = e.target.closest('tr');
        if (!tr) return;
        setTimeout(function () {
            var activo = document.activeElement;
            if (activo && tr.contains(activo)) return;
            guardarFila(tr);
        }, 150);
    });

    // ENTER (y Tab) avanza EN ORDEN ESTRICTO. Las flechas y otras teclas no cambian de celda.
    table.addEventListener('keydown', function (e) {
        var input = e.target.closest('.qk-cell');
        if (!input) return;
        if (e.key !== 'Enter' && e.key !== 'Tab') return;

        e.preventDefault();
        var col = input.getAttribute('data-col');

        // date confirma con Enter y pasa a payee.
        if (col === 'date') { enfocar('payee'); return; }

        var idx = ORDEN.indexOf(col);
        if (idx === -1) return;

        if (idx < ORDEN.length - 1) {
            // Avanzar a la siguiente columna del orden.
            enfocar(ORDEN[idx + 1]);
        } else {
            // Última (deposit): intentar guardar la fila.
            var tr = filaCaptura();
            if (tr) guardarFila(tr);
        }
    });

    // ===== Selección de fila guardada + botón Delete =====
    var btnDelete = document.getElementById('qkDelete');
    var filaSeleccionada = null;

    // URL base para borrar (le reemplazamos el ID al final). route() no acepta placeholder vacío,
    // así que usamos un id ficticio (0) y lo cambiamos por el real en JS.
    var URL_BORRAR_BASE = "{{ route('admin.wiese-banco.borrar', ['banco' => $bancoKey, 'movimiento' => 0]) }}";

    // Al hacer clic en una fila YA guardada, se selecciona y se activa Delete.
    tbody.addEventListener('click', function (e) {
        var tr = e.target.closest('tr.guardada');
        if (!tr || !tr.getAttribute('data-id')) return;
        if (filaSeleccionada) filaSeleccionada.classList.remove('seleccionada');
        if (filaSeleccionada === tr) {
            // Segundo clic en la misma: deseleccionar.
            filaSeleccionada = null;
            if (btnDelete) { btnDelete.disabled = true; }
            return;
        }
        filaSeleccionada = tr;
        tr.classList.add('seleccionada');
        if (btnDelete) { btnDelete.disabled = false; }
    });

    if (btnDelete) {
        btnDelete.addEventListener('click', function () {
            if (!filaSeleccionada) return;
            var id = filaSeleccionada.getAttribute('data-id');
            if (!id) return;
            if (!confirm('¿Borrar este movimiento? Se recalcularán los saldos siguientes.')) return;

            var url = URL_BORRAR_BASE.replace(/0$/, id);
            fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!res.ok || !res.j.ok) { alert('No se pudo borrar el movimiento.'); return; }
                // Quitar la fila de la pantalla.
                filaSeleccionada.parentNode.removeChild(filaSeleccionada);
                filaSeleccionada = null;
                btnDelete.disabled = true;
                // Actualizar el Ending Balance del pie.
                if (endingEl && res.j.ending_balance !== undefined) {
                    endingEl.textContent = Number(res.j.ending_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                // Recargar para que los balances recalculados de las otras filas se vean bien.
                location.reload();
            })
            .catch(function () { alert('Error al borrar.'); });
        });
    }

    // Al abrir la pantalla, poner el cursor en Payee de la fila de captura
    // (la fecha y el num ya vienen precargados; se empieza a capturar por Payee).
    var filaIni = filaCaptura();
    if (filaIni) {
        var payeeIni = filaIni.querySelector('.qk-cell[data-col="payee"]');
        if (payeeIni) payeeIni.focus();
    }
})();
</script>
@endpush
