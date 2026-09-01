@extends('layouts.admin')
@section('title', 'Bitácora de Gasolina')

@push('styles')
<style>
    .bg-wrap { max-width: 900px; }
    .bg-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow-sm); }
    .bg-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .bg-form-row { display: grid; gap: 16px; margin-bottom: 16px; }
    .bg-form-row.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .bg-form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
    .bg-group { display: flex; flex-direction: column; gap: 6px; }
    .bg-group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
    .bg-group input, .bg-group select {
        border: 1.5px solid var(--border); border-radius: 8px; padding: 10px 14px;
        font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none;
        background: var(--white); width: 100%; box-sizing: border-box;
    }
    .bg-group input:focus, .bg-group select:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(107,63,160,.1); }
    .btn-registrar { padding: 10px 22px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-registrar:hover { background: var(--purple-dark); }
    .bg-table { width: 100%; border-collapse: collapse; }
    .bg-table th { font-size: 10px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .4px; padding: 10px 12px; text-align: left; border-bottom: 2px solid var(--border-light); }
    .bg-table td { padding: 11px 12px; font-size: 13px; border-bottom: 1px solid var(--border-light); }
    .bg-table tr:hover td { background: var(--purple-subtle); }
    .bg-total { font-weight: 700; background: var(--gray-soft); }
    .bg-empty { text-align: center; padding: 32px; color: var(--gray-muted); font-size: 13px; }
    .bg-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    @media(max-width:768px) { .bg-form-row.cols-4, .bg-form-row.cols-3 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="bg-wrap">
    @if(session('mensaje'))
    <div style="background:#ecfdf5;border:1px solid #059669;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#059669;font-size:13px;font-weight:600;">{{ session('mensaje') }}</div>
    @endif
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
        <ul style="margin:0;padding:0 0 0 16px;color:#991b1b;font-size:12px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Formulario de registro --}}
    <div class="bg-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><polyline points="18.7 8 12 14.7 9 11.7 3 17.7"/></svg>
            Registrar carga de gasolina
        </h3>
        <form method="POST" action="{{ route('admin.bitacora-gasolina.guardar') }}" enctype="multipart/form-data">
            @csrf
            <div class="bg-form-row cols-4">
                <div class="bg-group">
                    <label for="fecha">Fecha <span style="color:#DC2626">*</span></label>
                    <input type="date" id="fecha" name="fecha" required value="{{ old('fecha', date('Y-m-d')) }}">
                </div>
                <div class="bg-group">
                    <label for="numero_empleado">Número de Empleado <span style="color:#DC2626">*</span></label>
                    <input type="text" id="numero_empleado" name="numero_empleado" required placeholder="Ej: EMP-001" value="{{ old('numero_empleado') }}">
                </div>
                <div class="bg-group">
                    <label for="empleado">Empleado <span style="color:#DC2626">*</span></label>
                    <input type="text" id="empleado" name="empleado" required placeholder="Nombre del empleado" value="{{ old('empleado') }}">
                </div>
                <div class="bg-group">
                    <label for="monto">Monto ($) <span style="color:#DC2626">*</span></label>
                    <input type="text" id="monto" name="monto" required placeholder="$0.00" inputmode="decimal" value="{{ old('monto') }}">
                </div>
            </div>
            <div class="bg-form-row cols-4">
                <div class="bg-group">
                    <label for="cantidad_litros">Litros</label>
                    <input type="number" id="cantidad_litros" name="cantidad_litros" step="0.01" min="0" placeholder="Ej: 40.5" value="{{ old('cantidad_litros') }}">
                </div>
                <div class="bg-group">
                    <label for="rendimiento">Rendimiento (km/l)</label>
                    <input type="number" id="rendimiento" name="rendimiento" step="0.01" min="0" placeholder="Ej: 12.5" value="{{ old('rendimiento') }}">
                </div>
                <div class="bg-group">
                    <label for="vehiculo">Vehículo / Placa</label>
                    <input type="text" id="vehiculo" name="vehiculo" placeholder="Ej: Nissan NP300 - JHL-1234" value="{{ old('vehiculo') }}">
                </div>
                <div class="bg-group">
                    <label for="kilometraje">Kilometraje</label>
                    <input type="number" id="kilometraje" name="kilometraje" min="0" placeholder="Km del odómetro" value="{{ old('kilometraje') }}">
                </div>
            </div>
            <div class="bg-form-row cols-3">
                <div class="bg-group">
                    <label for="notas">Notas</label>
                    <input type="text" id="notas" name="notas" placeholder="Ruta, gasolinera, etc." value="{{ old('notas') }}" maxlength="255">
                </div>
                <div class="bg-group">
                    <label for="factura_gasolina">Factura (PDF o imagen)</label>
                    <input type="file" id="factura_gasolina" name="factura_gasolina" accept=".pdf,.jpg,.jpeg,.png" style="padding:7px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;">
                </div>
                <div class="bg-group" style="justify-content:flex-end;">
                    <button type="submit" class="btn-registrar">Registrar</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Historial / Bitácora --}}
    <div class="bg-card">
        <div class="bg-header">
            <h3 style="margin:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Bitácora
            </h3>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <form method="GET" action="{{ route('admin.bitacora-gasolina') }}" style="display:flex;gap:8px;align-items:center;">
                    <input type="text" name="filtro_empleado" value="{{ request('filtro_empleado') }}" placeholder="Buscar por Nº Empleado" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;width:180px;">
                    <button type="submit" style="padding:7px 14px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Filtrar</button>
                    @if(request('filtro_empleado'))
                    <a href="{{ route('admin.bitacora-gasolina') }}" style="font-size:11px;color:var(--gray-muted);text-decoration:none;">✕ Limpiar</a>
                    @endif
                </form>
                @if(isset($registros) && $registros->count())
                <a href="{{ route('admin.bitacora-gasolina.excel', ['filtro_empleado' => request('filtro_empleado')]) }}" style="padding:7px 14px;background:#dcfce7;border:1px solid #86efac;border-radius:8px;font-size:12px;font-weight:600;color:#166534;text-decoration:none;">Exportar Excel</a>
                @endif
            </div>
        </div>

        @if(request('filtro_empleado'))
        <p style="font-size:12px;color:var(--purple);margin-bottom:12px;font-weight:600;">Mostrando registros del empleado: {{ request('filtro_empleado') }}</p>
        @endif

        @if(isset($registros) && $registros->count())
        <div style="overflow-x:auto;">
            <table class="bg-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nº Empleado</th>
                        <th>Empleado</th>
                        <th>Litros</th>
                        <th>Rendimiento</th>
                        <th>Monto</th>
                        <th>Vehículo</th>
                        <th>Km</th>
                        <th>Factura</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registros as $r)
                    @php $d = $r->datos ?? []; @endphp
                    <tr>
                        <td>{{ $d['fecha'] ?? $r->created_at->format('d/m/Y') }}</td>
                        <td style="font-weight:600;">{{ $d['numero_empleado'] ?? '—' }}</td>
                        <td style="font-weight:600;">{{ $d['empleado'] ?? '—' }}</td>
                        <td>{{ $d['cantidad_litros'] ?? '—' }}</td>
                        <td>{{ $d['rendimiento'] ? $d['rendimiento'] . ' km/l' : '—' }}</td>
                        <td style="font-weight:600;">${{ $d['monto'] ?? '—' }}</td>
                        <td>{{ $d['vehiculo'] ?? '—' }}</td>
                        <td>{{ $d['kilometraje'] ?? '—' }}</td>
                        <td>@if(!empty($d['factura']))<a href="{{ asset('storage/' . $d['factura']) }}" target="_blank" style="color:var(--purple);font-size:11px;">Ver</a>@else — @endif</td>
                        <td style="font-size:11px;color:var(--gray-muted);">{{ $d['notas'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-empty">
            <p>No hay registros de gasolina.</p>
        </div>
        @endif
    </div>
</div>
@endsection
