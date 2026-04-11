<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Muestras — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --purple: #6B3FA0; --purple-dark: #4A2070; --purple-light: #EDE7F6;
            --purple-mid: #9C6DD0; --gray-text: #4A4A6A; --gray-soft: #F7F6FB;
            --border: #D8CFE8; --white: #FFFFFF; --green: #059669; --red: #DC2626;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: var(--gray-soft); color: var(--gray-text); }
        .navbar-salcom {
            background: linear-gradient(135deg, var(--purple-dark), var(--purple));
            padding: 0 2rem; height: 64px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 12px rgba(74,32,112,0.18);
        }
        .navbar-salcom .brand { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--white); }
        .navbar-salcom .brand span { color: #C9A8FF; }
        .nav-links { display: flex; gap: 1rem; align-items: center; }
        .nav-links a {
            color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.85rem; font-weight: 600;
            padding: 4px 12px; border-radius: 8px; transition: all 0.2s;
        }
        .nav-links a:hover { background: rgba(255,255,255,0.15); color: var(--white); }
        .page-wrapper { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .section-header h1 { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: var(--purple-dark); margin: 0; }
        .btn-nuevo {
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: var(--white); font-weight: 700; border: none; border-radius: 10px;
            padding: 0.6rem 1.2rem; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;
        }
        .btn-nuevo:hover { opacity: 0.9; color: var(--white); }
        .alert-exito {
            background: #D1FAE5; border: 1px solid var(--green); color: #065F46;
            border-radius: 10px; padding: 0.8rem 1rem; margin-bottom: 1rem; font-weight: 600;
        }
        /* Tarjeta de muestra */
        .muestra-card {
            background: var(--white); border: 1px solid var(--border); border-radius: 14px;
            padding: 1.25rem; margin-bottom: 1rem; box-shadow: 0 2px 12px rgba(107,63,160,0.06);
        }
        .muestra-card.aprobado { border-left: 5px solid var(--green); }
        .muestra-card.rechazado { border-left: 5px solid var(--red); }
        .muestra-card.en-proceso { border-left: 5px solid var(--purple); }
        .muestra-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem; }
        .muestra-lote { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--purple-dark); font-weight: 700; }
        .muestra-producto { font-size: 0.85rem; color: var(--gray-text); }
        .muestra-proveedor { font-size: 0.8rem; color: var(--purple-mid); font-weight: 600; }
        .etapa-badge {
            font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 0.3rem;
        }
        .etapa-badge.registro { background: #E0E7FF; color: #3730A3; }
        .etapa-badge.recepcion { background: #FEF3C7; color: #92400E; }
        .etapa-badge.validacion { background: #FDE68A; color: #78350F; }
        .etapa-badge.laboratorio { background: #DBEAFE; color: #1E40AF; }
        .etapa-badge.piso { background: #E0E7FF; color: #4338CA; }
        .etapa-badge.estabilidad { background: #FCE7F3; color: #9D174D; }
        .etapa-badge.aprobado { background: #D1FAE5; color: #065F46; }
        .etapa-badge.rechazado { background: #FEE2E2; color: #991B1B; }

        /* Timeline */
        .timeline { display: flex; align-items: center; gap: 0; margin: 0.75rem 0; overflow-x: auto; }
        .timeline-step {
            display: flex; flex-direction: column; align-items: center; min-width: 70px; position: relative;
        }
        .timeline-dot {
            width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; color: var(--white); font-weight: 700; z-index: 1;
        }
        .timeline-dot.done { background: var(--green); }
        .timeline-dot.active { background: var(--purple); animation: pulse 1.5s infinite; }
        .timeline-dot.pending { background: #D1D5DB; }
        .timeline-dot.rejected { background: var(--red); }
        @keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(107,63,160,0.4); } 50% { box-shadow: 0 0 0 8px rgba(107,63,160,0); } }
        .timeline-label { font-size: 0.62rem; font-weight: 600; color: var(--gray-text); margin-top: 4px; text-align: center; max-width: 70px; }
        .timeline-line { flex: 1; height: 3px; min-width: 20px; }
        .timeline-line.done { background: var(--green); }
        .timeline-line.pending { background: #E5E7EB; }

        /* Barra de progreso */
        .progress-bar-custom { height: 6px; border-radius: 3px; background: #E5E7EB; margin: 0.5rem 0; }
        .progress-bar-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--purple), var(--green)); transition: width 0.5s; }

        /* Acciones */
        .acciones { display: flex; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap; }
        .btn-sm-custom {
            font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 8px;
            border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;
        }
        .btn-aprobar { background: #D1FAE5; color: #065F46; }
        .btn-aprobar:hover { background: #A7F3D0; }
        .btn-rechazar { background: #FEE2E2; color: #991B1B; }
        .btn-rechazar:hover { background: #FECACA; }
        .btn-reiniciar { background: #E0E7FF; color: #3730A3; }
        .btn-reiniciar:hover { background: #C7D2FE; }
        .rechazo-input { font-size: 0.8rem; border-radius: 8px; border: 1px solid var(--border); padding: 4px 8px; flex: 1; min-width: 150px; }
        .motivo-rechazo { font-size: 0.78rem; color: #991B1B; background: #FEF2F2; padding: 4px 8px; border-radius: 6px; margin-top: 0.5rem; }
        .empty-state { text-align: center; padding: 3rem; color: var(--gray-text); opacity: 0.6; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 1rem; }
        .page-footer { text-align: center; margin-top: 2rem; font-size: 0.78rem; color: var(--gray-text); opacity: 0.5; }
    </style>
</head>
<body>

<nav class="navbar-salcom">
    <span class="brand">Industrias <span>Salcom</span></span>
    <div class="nav-links">
        <a href="{{ route('muestras.crear') }}"><i class="bi bi-plus-circle"></i> Nueva Muestra</a>
        <a href="{{ route('muestras.admin') }}"><i class="bi bi-list-check"></i> Admin</a>
    </div>
</nav>

<div class="page-wrapper">
    <div class="section-header">
        <h1><i class="bi bi-clipboard2-data"></i> Seguimiento de Muestras</h1>
        <a href="{{ route('muestras.crear') }}" class="btn-nuevo"><i class="bi bi-plus-circle"></i> Nueva Muestra</a>
    </div>

    @if(session('exito'))
        <div class="alert-exito"><i class="bi bi-check-circle"></i> {{ session('exito') }}</div>
    @endif

    @forelse($muestras as $m)
        @php
            $orden = ['registro','recepcion','validacion','laboratorio','piso','estabilidad','aprobado'];
            $etapaActual = $m->etapa;
            $indiceActual = array_search($etapaActual, $orden);
            $esRechazado = $etapaActual === 'rechazado';
            $esAprobado = $etapaActual === 'aprobado';
            $cardClass = $esAprobado ? 'aprobado' : ($esRechazado ? 'rechazado' : 'en-proceso');
        @endphp
        <div class="muestra-card {{ $cardClass }}">
            <div class="muestra-header">
                <div>
                    <div class="muestra-lote">Lote: {{ $m->lote }}</div>
                    <div class="muestra-producto">{{ $m->producto }} — {{ $m->cantidad }} {{ $m->unidad }}</div>
                    <div class="muestra-proveedor"><i class="bi bi-building"></i> {{ $m->proveedor }}</div>
                </div>
                <span class="etapa-badge {{ $etapaActual }}">
                    <i class="bi {{ $m->etapa_icono }}"></i> {{ $m->etapa_label }}
                </span>
            </div>

            {{-- Timeline visual --}}
            <div class="timeline">
                @foreach($orden as $i => $etapa)
                    @php
                        $etapaInfo = \App\Models\Muestra::ETAPAS[$etapa];
                        if ($esRechazado) {
                            $estado = 'pending';
                        } elseif ($indiceActual !== false && $i < $indiceActual) {
                            $estado = 'done';
                        } elseif ($indiceActual !== false && $i == $indiceActual) {
                            $estado = 'active';
                        } else {
                            $estado = 'pending';
                        }
                    @endphp
                    <div class="timeline-step">
                        <div class="timeline-dot {{ $estado }}">
                            <i class="bi {{ $etapaInfo['icono'] }}"></i>
                        </div>
                        <div class="timeline-label">{{ $etapaInfo['label'] }}</div>
                    </div>
                    @if(!$loop->last)
                        <div class="timeline-line {{ $estado === 'done' || $estado === 'active' ? 'done' : 'pending' }}"></div>
                    @endif
                @endforeach
            </div>

            @if($esRechazado)
                <div class="timeline-step" style="margin-top:0.5rem;">
                    <span class="etapa-badge rechazado"><i class="bi bi-x-circle"></i> RECHAZADO</span>
                </div>
            @endif

            {{-- Barra de progreso --}}
            <div class="progress-bar-custom">
                <div class="progress-bar-fill" style="width: {{ $m->progreso }}%"></div>
            </div>

            {{-- Info de fechas --}}
            <div style="font-size:0.75rem; color:var(--gray-text); opacity:0.7; margin-top:0.25rem;">
                Registrado: {{ $m->fecha_registro?->format('d/m/Y H:i') ?? '—' }}
                @if($m->fecha_resolucion)
                    · Resuelto: {{ $m->fecha_resolucion->format('d/m/Y H:i') }}
                @endif
            </div>

            @if($m->motivo_rechazo)
                <div class="motivo-rechazo"><i class="bi bi-exclamation-triangle"></i> {{ $m->motivo_rechazo }}</div>
            @endif

            {{-- Acciones --}}
            <div class="acciones">
                @if(!$esAprobado && !$esRechazado)
                    {{-- Aprobar --}}
                    <form method="POST" action="{{ route('muestras.aprobar', $m) }}" onsubmit="return confirm('¿Aprobar esta muestra?')">
                        @csrf @method('PATCH')
                        <button class="btn-sm-custom btn-aprobar"><i class="bi bi-check-circle"></i> Aprobar</button>
                    </form>
                    {{-- Rechazar --}}
                    <form method="POST" action="{{ route('muestras.rechazar', $m) }}" style="display:flex; gap:0.4rem; align-items:center;" onsubmit="return this.motivo_rechazo.value.trim() ? true : (alert('Escribe el motivo de rechazo'), false)">
                        @csrf @method('PATCH')
                        <input type="text" name="motivo_rechazo" class="rechazo-input" placeholder="Motivo de rechazo...">
                        <button class="btn-sm-custom btn-rechazar"><i class="bi bi-x-circle"></i> Rechazar</button>
                    </form>
                @endif

                @if($esRechazado)
                    <form method="POST" action="{{ route('muestras.reiniciar', $m) }}" onsubmit="return confirm('¿Reiniciar el proceso de esta muestra?')">
                        @csrf @method('PATCH')
                        <button class="btn-sm-custom btn-reiniciar"><i class="bi bi-arrow-repeat"></i> Reiniciar Proceso</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            No hay muestras registradas aún.<br>
            <a href="{{ route('muestras.crear') }}" style="color:var(--purple); font-weight:700;">Registrar primera muestra</a>
        </div>
    @endforelse
</div>

<p class="page-footer">Industrias Salcom · Sistema de control de muestras</p>
</body>
</html>
