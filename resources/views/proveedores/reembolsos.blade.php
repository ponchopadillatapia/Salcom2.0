@extends('layouts.proveedor')

@section('title', 'Reembolsos')

@section('hero')
<div class="hero-band">
    <h1>Reembolsos</h1>
    <p>Solicita y consulta el estatus de tus reembolsos</p>
</div>
@endsection

@push('styles')
<style>
    .reembolsos-wrap { max-width: 960px; }
    .reembolsos-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .reembolsos-header h2 { font-size: 18px; font-weight: 700; color: var(--gray-text); margin: 0; }
    .btn-nueva-solicitud { padding: 10px 20px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .2s; }
    .btn-nueva-solicitud:hover { background: var(--purple-dark); transform: translateY(-1px); }

    .reembolsos-empty { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 48px 24px; text-align: center; }
    .reembolsos-empty svg { margin-bottom: 16px; }
    .reembolsos-empty h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin: 0 0 8px; }
    .reembolsos-empty p { font-size: 13px; color: var(--gray-muted); margin: 0; }

    .reembolsos-table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; overflow: hidden; }
    .reembolsos-table th { background: var(--gray-soft); font-size: 11px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .4px; padding: 12px 16px; text-align: left; }
    .reembolsos-table td { padding: 14px 16px; font-size: 13px; color: var(--gray-text); border-top: 1px solid var(--border-light); }
    .reembolsos-table tr:hover td { background: var(--purple-subtle); }

    .badge-estatus { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .badge-pendiente { background: #fef3c7; color: #92400e; }
    .badge-aprobado { background: #dcfce7; color: #166534; }
    .badge-rechazado { background: #fee2e2; color: #991b1b; }
    .badge-pagado { background: #dbeafe; color: #1e40af; }
</style>
@endpush

@section('content')
<div class="reembolsos-wrap">
    <div class="reembolsos-header">
        <h2>Mis Reembolsos</h2>
    </div>

    <div class="reembolsos-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gray-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/><line x1="12" y1="7" x2="12" y2="12"/><line x1="12" y1="15" x2="12.01" y2="15"/></svg>
        <h3>No tienes reembolsos registrados</h3>
        <p>Cuando se genere un reembolso, aparecerá aquí con su estatus y detalle.</p>
    </div>
</div>
@endsection
