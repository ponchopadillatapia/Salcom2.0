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
    .adm-section {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 250px);
        min-height: 420px;
    }
    .adm-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 16px 22px;
        background: var(--gray-soft);
        border-bottom: 1px solid var(--border-light);
        flex-shrink: 0;
    }
    .adm-section-head h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-text);
        margin: 0;
    }
    .adm-section-meta {
        font-size: 12px;
        color: var(--gray-muted);
    }
    .wb-sheet-wrap {
        flex: 1;
        overflow: auto;
        overscroll-behavior: contain;
    }
    .wb-table {
        width: max-content;
        min-width: 1400px;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
        font-size: 13px;
    }
    .wb-table th,
    .wb-table td {
        border-bottom: 1px solid var(--border-light);
        border-right: 1px solid var(--border-light);
        vertical-align: top;
        background: var(--white);
    }
    .wb-table th:last-child,
    .wb-table td:last-child { border-right: none; }
    .wb-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 12px 16px;
        text-align: left;
        background: var(--white);
        border-bottom: 2px solid var(--purple-light);
        white-space: nowrap;
    }
    .wb-table thead .wb-sort {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .wb-table thead th.wb-center { text-align: center; }
    .wb-table thead th.wb-right { text-align: right; }
    .wb-th-stack {
        display: flex;
        flex-direction: column;
        gap: 2px;
        line-height: 1.2;
        text-transform: none;
        letter-spacing: 0;
    }
    .wb-th-stack span:first-child {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .wb-th-stack span:last-child {
        font-size: 10px;
        font-weight: 600;
        color: var(--gray-muted);
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .wb-sort-icon {
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-bottom: 5px solid var(--purple);
    }
    .wb-table tbody tr:hover td { background: var(--purple-subtle); }
    .wb-table tbody td {
        height: 48px;
        padding: 0;
        color: var(--gray-text);
    }
    .wb-table tbody td.is-focus { background: var(--purple-subtle); }
    .wb-cell {
        display: block;
        width: 100%;
        height: 48px;
        border: none;
        outline: none;
        background: transparent;
        font: inherit;
        color: var(--gray-text);
        padding: 0 16px;
        box-sizing: border-box;
    }
    .wb-cell:focus {
        background: #fff;
        box-shadow: inset 0 0 0 2px var(--purple);
    }
    .wb-cell.wb-right { text-align: right; font-variant-numeric: tabular-nums; }
    .wb-cell.wb-center { text-align: center; }
    .wb-payee {
        display: flex;
        flex-direction: column;
        height: 48px;
    }
    .wb-payee .wb-cell {
        height: 24px;
        padding: 0 16px;
        font-size: 12px;
    }
    .wb-payee .wb-cell:first-child {
        border-bottom: 1px solid var(--border-light);
        font-weight: 600;
        font-size: 13px;
    }
    .wb-col-date { width: 120px; }
    .wb-col-num { width: 96px; }
    .wb-col-payee { width: 240px; }
    .wb-col-memo { width: 280px; }
    .wb-col-payment { width: 130px; }
    .wb-col-clr { width: 64px; }
    .wb-col-deposit { width: 130px; }
    .wb-col-balance { width: 140px; }
    .wb-table thead th.wb-col-date,
    .wb-table tbody td.wb-col-date {
        position: sticky;
        left: 0;
        z-index: 1;
        box-shadow: 4px 0 8px -6px rgba(29, 29, 31, .18);
    }
    .wb-table thead th.wb-col-date { z-index: 3; }
    @media (max-width: 768px) {
        .adm-section { height: calc(100vh - 220px); min-height: 320px; }
        .wb-table tbody td,
        .wb-cell { height: 52px; }
        .wb-payee { height: 52px; }
        .wb-payee .wb-cell { height: 26px; }
    }
</style>
@endpush
@section('content')
<div class="adm-section">
    <div class="adm-section-head">
        <h4>Registro {{ $bancoNombre }}</h4>
        <span class="adm-section-meta">Haz clic en una celda para escribir</span>
    </div>
    <div class="wb-sheet-wrap" id="wbSheet" aria-label="Registro bancario Wiese">
        <table class="wb-table" id="wbTable">
            <thead>
                <tr>
                    <th class="wb-col-date">
                        <span class="wb-sort">Date <span class="wb-sort-icon" aria-hidden="true"></span></span>
                    </th>
                    <th class="wb-col-num">Num</th>
                    <th class="wb-col-payee">
                        <div class="wb-th-stack">
                            <span>Payee</span>
                            <span>Category</span>
                        </div>
                    </th>
                    <th class="wb-col-memo">Memo</th>
                    <th class="wb-col-payment wb-right">Payment</th>
                    <th class="wb-col-clr wb-center">Clr</th>
                    <th class="wb-col-deposit wb-right">Deposit</th>
                    <th class="wb-col-balance wb-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 20; $i++)
                @include('admin.partials.wiese-banco-row')
                @endfor
            </tbody>
        </table>
        <template id="wbRowTpl">@include('admin.partials.wiese-banco-row')</template>
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

    function cells() {
        return Array.prototype.slice.call(table.querySelectorAll('.wb-cell'));
    }

    function addRow() {
        tbody.appendChild(tpl.content.cloneNode(true));
    }

    table.addEventListener('focusin', function (e) {
        var td = e.target.closest('td');
        if (!td) return;
        table.querySelectorAll('td.is-focus').forEach(function (el) { el.classList.remove('is-focus'); });
        td.classList.add('is-focus');
    });

    table.addEventListener('keydown', function (e) {
        var input = e.target.closest('.wb-cell');
        if (!input) return;
        var list = cells();
        var i = list.indexOf(input);
        if (i < 0) return;

        var next = null;
        if (e.key === 'Enter') {
            e.preventDefault();
            var col = input.getAttribute('data-col');
            next = list.slice(i + 1).find(function (el) { return el.getAttribute('data-col') === col; });
            if (!next) {
                addRow();
                list = cells();
                next = list.slice(i + 1).find(function (el) { return el.getAttribute('data-col') === col; });
            }
        } else if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            next = list[i + 1];
            if (!next) {
                addRow();
                list = cells();
                next = list[i + 1];
            }
        } else if (e.key === 'Tab' && e.shiftKey) {
            e.preventDefault();
            next = list[i - 1];
        }

        if (next) {
            next.focus();
            next.select();
        }
    });
})();
</script>
@endpush
