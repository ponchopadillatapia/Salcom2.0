<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Empleados — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --purple:#6d28d9; --purple-dark:#5b21b6; --gray-soft:#f4f4f7; --gray-text:#1d1d1f; --gray-muted:#86868b; --border-light:#e5e5ea; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-soft); color: var(--gray-text); font-size: 14px; }
        .topbar { background: #fff; border-bottom: 1px solid var(--border-light); padding: 14px 28px; display: flex; align-items: center; justify-content: space-between; }
        .topbar .brand { font-size: 13px; font-weight: 700; letter-spacing: 2px; color: var(--purple); text-transform: uppercase; }
        .topbar .user { display: flex; align-items: center; gap: 14px; font-size: 13px; color: var(--gray-muted); }
        .btn-logout { padding: 6px 14px; border: 1px solid var(--border-light); border-radius: 20px; background: none; font-size: 12px; cursor: pointer; font-family: inherit; color: var(--gray-muted); }
        .btn-logout:hover { background: var(--gray-soft); color: var(--purple); }
        .wrap { max-width: 960px; margin: 0 auto; padding: 28px 20px 60px; }
        .hello { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .hello-sub { color: var(--gray-muted); font-size: 13px; margin-bottom: 24px; }
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .kpi { background: #fff; border: 1px solid var(--border-light); border-radius: 14px; padding: 20px; }
        .kpi .num { font-size: 26px; font-weight: 700; color: var(--purple); }
        .kpi .lbl { font-size: 12px; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }
        .section { background: #fff; border: 1px solid var(--border-light); border-radius: 14px; padding: 22px; margin-bottom: 20px; }
        .section h2 { font-size: 15px; font-weight: 700; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { text-align: left; font-size: 10px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; padding: 8px 10px; border-bottom: 2px solid var(--border-light); }
        td { padding: 10px; border-bottom: 1px solid var(--border-light); }
        .badge { padding: 3px 9px; border-radius: 12px; font-size: 10px; font-weight: 700; }
        .b-borrador { background:#f3f4f6;color:#6b7280; } .b-enviado { background:#fef3c7;color:#92400e; }
        .b-aprobado { background:#dcfce7;color:#166534; } .b-rechazado { background:#fee2e2;color:#991b1b; }
        .b-pagado { background:#dbeafe;color:#1e40af; } .b-pendiente { background:#fef3c7;color:#92400e; }
        .empty { text-align: center; padding: 20px; color: var(--gray-muted); font-size: 13px; }
        @media(max-width:768px){ .cards { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">Industrias Salcom · Portal Empleados</div>
        <div class="user">
            <span>{{ session('empleado_nombre') }} · {{ session('empleado_numero') }}</span>
            <form method="POST" action="{{ route('empleados.logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn-logout">Salir</button>
            </form>
        </div>
    </div>

    <div class="wrap">
        <div class="hello">Hola, {{ session('empleado_nombre') }}</div>
        <div class="hello-sub">Aquí puedes consultar tus reembolsos, viáticos y registros de gasolina.</div>

        <div class="cards">
            <div class="kpi"><div class="num">{{ $reembolsos->count() }}</div><div class="lbl">Reembolsos</div></div>
            <div class="kpi"><div class="num">{{ $viajes->count() }}</div><div class="lbl">Reembolsos de Viaje</div></div>
            <div class="kpi"><div class="num">{{ $gasolina->count() }}</div><div class="lbl">Registros de Gasolina</div></div>
        </div>

        {{-- Reembolsos --}}
        <div class="section">
            <h2>Mis Reembolsos</h2>
            @if($reembolsos->count())
            <table>
                <thead><tr><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Institución</th><th>Autorización</th></tr></thead>
                <tbody>
                    @foreach($reembolsos as $r)
                    @php $d = $r->datos ?? []; @endphp
                    <tr>
                        <td>{{ $r->created_at->format('d/m/Y') }}</td>
                        <td>{{ $d['concepto'] ?? '—' }}</td>
                        <td><strong>${{ $d['monto'] ?? '—' }}</strong></td>
                        <td>{{ strtoupper($d['metodo_pago_empresa'] ?? '—') }}</td>
                        <td>@if(!empty($d['autorizado_sandra']))<span class="badge b-aprobado">Autorizado</span>@else<span class="badge b-pendiente">Pendiente</span>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty">No tienes reembolsos registrados.</div>
            @endif
        </div>

        {{-- Reembolsos de Viaje --}}
        <div class="section">
            <h2>Mis Reembolsos de Viaje</h2>
            @if($viajes->count())
            <table>
                <thead><tr><th>Fecha</th><th>Destino</th><th>Total MXN</th><th>Estatus</th></tr></thead>
                <tbody>
                    @foreach($viajes as $v)
                    <tr>
                        <td>{{ $v->created_at->format('d/m/Y') }}</td>
                        <td>{{ $v->pais_destino }}</td>
                        <td><strong>${{ number_format($v->total_moneda_base, 2) }}</strong></td>
                        <td><span class="badge {{ $v->badgeClass() }}">{{ $v->estatusLabel() }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty">No tienes reembolsos de viaje.</div>
            @endif
        </div>

        {{-- Gasolina --}}
        <div class="section">
            <h2>Mi Bitácora de Gasolina</h2>
            @if($gasolina->count())
            <table>
                <thead><tr><th>Fecha</th><th>Litros</th><th>Monto</th><th>Vehículo</th><th>Km</th></tr></thead>
                <tbody>
                    @foreach($gasolina as $g)
                    @php $d = $g->datos ?? []; @endphp
                    <tr>
                        <td>{{ $d['fecha'] ?? $g->created_at->format('d/m/Y') }}</td>
                        <td>{{ $d['cantidad_litros'] ?? '—' }}</td>
                        <td><strong>${{ $d['monto'] ?? '—' }}</strong></td>
                        <td>{{ $d['vehiculo'] ?? '—' }}</td>
                        <td>{{ $d['kilometraje'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty">No tienes registros de gasolina.</div>
            @endif
        </div>
    </div>
</body>
</html>
