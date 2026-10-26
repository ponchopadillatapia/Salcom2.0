@extends('layouts.proveedor-extranjero')
@section('title', 'Invoices')

@section('content')
<div style="max-width:1000px;margin:0 auto;">
    <h1 style="font-size:22px;font-weight:700;color:var(--gray-text);margin-bottom:4px;">Invoices</h1>
    <p style="font-size:14px;color:var(--gray-muted);margin-bottom:22px;">Your invoices with Industrias Salcom.</p>

    <div style="background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px;box-shadow:var(--shadow-sm);">
        @if(isset($facturas) && $facturas->count())
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:10px;border-bottom:2px solid var(--border-light);font-size:11px;color:var(--gray-muted);text-transform:uppercase;">Date</th>
                    <th style="text-align:left;padding:10px;border-bottom:2px solid var(--border-light);font-size:11px;color:var(--gray-muted);text-transform:uppercase;">Folio</th>
                    <th style="text-align:right;padding:10px;border-bottom:2px solid var(--border-light);font-size:11px;color:var(--gray-muted);text-transform:uppercase;">Amount</th>
                    <th style="text-align:left;padding:10px;border-bottom:2px solid var(--border-light);font-size:11px;color:var(--gray-muted);text-transform:uppercase;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facturas as $f)
                <tr>
                    <td style="padding:10px;border-bottom:1px solid var(--border-light);">{{ $f->created_at?->format('d/m/Y') }}</td>
                    <td style="padding:10px;border-bottom:1px solid var(--border-light);">{{ $f->folio ?? '—' }}</td>
                    <td style="padding:10px;border-bottom:1px solid var(--border-light);text-align:right;">${{ number_format((float) $f->total, 2) }}</td>
                    <td style="padding:10px;border-bottom:1px solid var(--border-light);">{{ ucfirst($f->estatus ?? '—') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align:center;padding:40px;color:var(--gray-muted);font-size:14px;">
            No invoices yet.
        </div>
        @endif
    </div>
</div>
@endsection
