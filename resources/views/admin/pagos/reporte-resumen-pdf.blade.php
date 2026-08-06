<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Resumen de Pagos #{{ $pago->id }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .top { width: 100%; margin-bottom: 14px; }
        .top td { vertical-align: top; }
        .brand { font-size: 13px; font-weight: 700; letter-spacing: .2px; }
        .title { font-size: 15px; font-weight: 700; margin-top: 4px; }
        .range { font-size: 10px; margin-top: 4px; }
        .meta { text-align: right; font-size: 10px; line-height: 1.5; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.grid th {
            background: #4a4a4a;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 7px 5px;
            border: 1px solid #333;
            text-align: left;
        }
        table.grid td {
            border: 1px solid #999;
            padding: 7px 5px;
            font-size: 9px;
            vertical-align: top;
        }
        .num { text-align: right; white-space: nowrap; }
        .folios { font-size: 8.5px; line-height: 1.35; }
        .total-row { margin-top: 10px; text-align: right; font-size: 11px; font-weight: 700; }
        .firma {
            margin-top: 48px;
            width: 220px;
            margin-left: auto;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 6px;
            font-size: 9px;
        }
        .muted { color: #555; font-weight: 400; }
    </style>
</head>
<body>
@php
    $fechaDoc = $pago->fecha_pago?->format('d/m/Y')
        ?? $pago->confirmado_at?->format('d/m/Y')
        ?? now()->format('d/m/Y');
    $folioDoc = 'FCONA-'.str_pad((string) $pago->id, 4, '0', STR_PAD_LEFT);
@endphp

<table class="top">
    <tr>
        <td>
            <div class="brand">INDUSTRIAS SALCOM S.A. DE C.V.</div>
            <div class="title">REPORTE RESUMEN DE PAGOS</div>
            <div class="range">Del: {{ $fechaDoc }} &nbsp; Al: {{ $fechaDoc }}</div>
        </td>
        <td class="meta">
            <div>{{ $folioDoc }}</div>
            <div>Fecha: {{ $fechaDoc }}</div>
        </td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th style="width:12%;">Banco</th>
            <th style="width:10%;">Cuenta</th>
            <th style="width:14%;">CLABE</th>
            <th style="width:7%;">SWIFT</th>
            <th style="width:8%;">Importe</th>
            <th style="width:10%;">RFC</th>
            <th style="width:7%;">IVA</th>
            <th style="width:12%;">Proveedor</th>
            <th style="width:12%;">Folio general</th>
            <th style="width:8%;">Total del banco</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $banco !== '' ? $banco : '—' }}</td>
            <td>{{ $cuenta !== '' ? $cuenta : '—' }}</td>
            <td>{{ $clabe !== '' ? $clabe : '—' }}</td>
            <td>{{ $swift !== '' ? $swift : '' }}</td>
            <td class="num">
                {{ number_format($importe, 2) }}<br>
                <span class="muted">0.00</span>
            </td>
            <td>{{ $rfc !== '' ? $rfc : '—' }}</td>
            <td class="num">{{ number_format($iva, 2) }}</td>
            <td>{{ $nombreProveedor }}</td>
            <td class="folios">{{ $foliosGenerales !== '' ? $foliosGenerales : '—' }}</td>
            <td class="num">
                <span class="muted">0.00</span><br>
                {{ number_format($totalBanco, 2) }}
            </td>
        </tr>
    </tbody>
</table>

<div class="total-row">Total a pagar en MXN: {{ number_format($totalPagar, 2) }}</div>

<div class="firma">
    Autorización / Firma
</div>
</body>
</html>
