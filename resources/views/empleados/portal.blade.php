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
        .sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .sec-head h2 { margin: 0; }
        .btn-nuevo { padding: 7px 14px; background: var(--purple); color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit; text-decoration: none; }
        .btn-nuevo:hover { background: var(--purple-dark); }
        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9999; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 40px 20px; }
        .modal-bg.show { display: flex; }
        .modal { background: #fff; border-radius: 16px; padding: 26px; width: 100%; max-width: 560px; }
        .modal h3 { font-size: 16px; font-weight: 700; margin-bottom: 18px; }
        .m-row { display: grid; gap: 14px; margin-bottom: 14px; }
        .m-row.c2 { grid-template-columns: 1fr 1fr; }
        .m-group { display: flex; flex-direction: column; gap: 6px; }
        .m-group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
        .m-group input, .m-group select, .m-group textarea { border: 1.5px solid var(--border-light); border-radius: 8px; padding: 10px 12px; font-size: 13px; font-family: inherit; width: 100%; box-sizing: border-box; outline: none; }
        .m-group input:focus, .m-group select:focus { border-color: var(--purple); }
        .m-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
        .btn-cancel { padding: 10px 18px; border: 1.5px solid var(--border-light); border-radius: 8px; background: #fff; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--gray-muted); }
        .btn-save { padding: 10px 20px; background: var(--purple); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
        .err-box { background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#991b1b;font-size:13px; }
        .ok-box { background:#ecfdf5;border:1px solid #059669;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#059669;font-size:13px;font-weight:600; }
        @media(max-width:768px){ .cards { grid-template-columns: 1fr; } .m-row.c2 { grid-template-columns: 1fr; } }
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
        <div class="hello-sub">Aquí puedes registrar y consultar tus reembolsos, viáticos y registros de gasolina.</div>

        @if(session('mensaje'))<div class="ok-box">{{ session('mensaje') }}</div>@endif
        @if($errors->any())<div class="err-box"><ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <div class="cards">
            <div class="kpi"><div class="num">{{ $reembolsos->count() }}</div><div class="lbl">Reembolsos</div></div>
            <div class="kpi"><div class="num">{{ $viajes->count() }}</div><div class="lbl">Reembolsos de Viaje</div></div>
            <div class="kpi"><div class="num">{{ $gasolina->count() }}</div><div class="lbl">Registros de Gasolina</div></div>
        </div>

        {{-- Reembolsos --}}
        <div class="section">
            <div class="sec-head">
                <h2>Mis Reembolsos</h2>
                <button type="button" class="btn-nuevo" onclick="document.getElementById('modalReembolso').classList.add('show')">+ Nuevo reembolso</button>
            </div>
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
            <div class="sec-head">
                <h2>Mis Reembolsos de Viaje</h2>
                <a href="{{ route('empleados.viaje.crear') }}" class="btn-nuevo">+ Nuevo viaje</a>
            </div>
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
            <div class="sec-head">
                <h2>Mi Bitácora de Gasolina</h2>
                <button type="button" class="btn-nuevo" onclick="document.getElementById('modalGasolina').classList.add('show')">+ Registrar gasolina</button>
            </div>
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

    {{-- Modal Reembolso --}}
    <div class="modal-bg" id="modalReembolso">
        <div class="modal">
            <h3>Nuevo Reembolso</h3>
            <form method="POST" action="{{ route('empleados.reembolso.guardar') }}" enctype="multipart/form-data">
                @csrf
                <div class="m-row c2">
                    <div class="m-group">
                        <label>Categoría *</label>
                        <select name="categoria" required>
                            <option value="gasto_general">Gasto general</option>
                            <option value="gasolina">Gasolina</option>
                            <option value="computo">Equipo de cómputo</option>
                            <option value="viaticos_nacional">Viáticos nacionales</option>
                        </select>
                    </div>
                    <div class="m-group">
                        <label>Razón social *</label>
                        <select name="razon_social" required>
                            <option value="Industrias Salcom S.A. de C.V.">Industrias Salcom S.A. de C.V.</option>
                            <option value="Franfoods S.A. de C.V.">Franfoods S.A. de C.V.</option>
                        </select>
                    </div>
                </div>
                <div class="m-row c2">
                    <div class="m-group">
                        <label>Método de pago empresa *</label>
                        <select name="metodo_pago_empresa" required>
                            <option value="bbva">BBVA (requiere factura y materialidad)</option>
                            <option value="inntec">Inntec (solo ticket)</option>
                        </select>
                    </div>
                    <div class="m-group">
                        <label>Monto *</label>
                        <input type="text" name="monto" placeholder="$0.00" required>
                    </div>
                </div>
                <div class="m-row">
                    <div class="m-group">
                        <label>Concepto *</label>
                        <input type="text" name="concepto" placeholder="Descripción del gasto" required maxlength="255">
                    </div>
                </div>
                <div class="m-row c2">
                    <div class="m-group">
                        <label>Factura (PDF o imagen) *</label>
                        <input type="file" name="archivo_factura" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="m-group">
                        <label>Materialidad (correo/foto)</label>
                        <input type="file" name="archivo_materialidad" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="m-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalReembolso').classList.remove('show')">Cancelar</button>
                    <button type="submit" class="btn-save">Enviar reembolso</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Gasolina --}}
    <div class="modal-bg" id="modalGasolina">
        <div class="modal">
            <h3>Registrar carga de gasolina</h3>
            <form method="POST" action="{{ route('empleados.gasolina.guardar') }}" enctype="multipart/form-data">
                @csrf
                <div class="m-row c2">
                    <div class="m-group">
                        <label>Monto ($) *</label>
                        <input type="text" name="monto" placeholder="$0.00" required>
                    </div>
                    <div class="m-group">
                        <label>Litros</label>
                        <input type="number" name="cantidad_litros" step="0.01" min="0" placeholder="Ej: 40.5">
                    </div>
                </div>
                <div class="m-row c2">
                    <div class="m-group">
                        <label>Rendimiento (km/l)</label>
                        <input type="number" name="rendimiento" step="0.01" min="0" placeholder="Ej: 12.5">
                    </div>
                    <div class="m-group">
                        <label>Kilometraje</label>
                        <input type="number" name="kilometraje" min="0" placeholder="Km del odómetro">
                    </div>
                </div>
                <div class="m-row c2">
                    <div class="m-group">
                        <label>Vehículo / Placa</label>
                        <input type="text" name="vehiculo" placeholder="Ej: Nissan NP300 - JHL-1234">
                    </div>
                    <div class="m-group">
                        <label>Notas</label>
                        <input type="text" name="notas" placeholder="Ruta, gasolinera, etc." maxlength="255">
                    </div>
                </div>
                <div class="m-row">
                    <div class="m-group">
                        <label>Factura (PDF o imagen)</label>
                        <input type="file" name="factura_gasolina" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="m-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('modalGasolina').classList.remove('show')">Cancelar</button>
                    <button type="submit" class="btn-save">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
