@extends('layouts.proveedor')

@section('title', 'Business')

@section('hero')
<div class="hero-band">
    <h1>Business</h1>
    <p>Tus tareas pendientes y alertas importantes — {{ now()->format('d/m/Y') }}</p>
</div>
@endsection

@push('styles')
<style>
    .resumen-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    .resumen-card { background: var(--white); border-radius: 12px; padding: 18px 20px; border: 0.5px solid var(--border); position: relative; overflow: hidden; }
    .resumen-card .accent { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 12px 0 0 12px; }
    .resumen-label { font-size: 12px; color: var(--gray-text); font-weight: 500; margin-bottom: 6px; padding-left: 8px; }
    .resumen-value { font-size: 28px; font-weight: 700; padding-left: 8px; line-height: 1; }
    .resumen-sub { font-size: 11px; color: #AAA; padding-left: 8px; margin-top: 4px; }
    .val-red   { color: var(--red); }
    .val-amber { color: var(--amber); }
    .val-green { color: var(--green); }
    .val-blue  { color: var(--blue); }

    .seccion { margin-bottom: 28px; }
    .seccion-titulo { display: flex; align-items: center; gap: 10px; font-family: 'Playfair Display', serif; font-size: 17px; color: var(--purple-dark); font-weight: 600; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1.5px solid var(--border); }
    .seccion-titulo .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .seccion-sub-label { font-size: 11px; color: #AAA; margin-left: auto; font-family: 'Nunito', sans-serif; font-weight: 500; }

    .item360-wrap { background: var(--white); border-radius: 16px; border: 0.5px solid var(--border); padding: 28px; }
    .action-items { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px; }
    .action-card { background: var(--gray-soft); border-radius: 12px; padding: 16px 20px; border: 0.5px solid var(--border); }
    .action-card-label { font-size: 12px; color: var(--gray-text); margin-bottom: 6px; }
    .action-card-value { font-size: 28px; font-weight: 700; color: var(--purple-dark); line-height: 1; }
    .action-card-sub { font-size: 12px; color: #AAA; margin-top: 4px; }
    .action-card.alerta .action-card-value { color: var(--amber); }
    .action-card.ok .action-card-value { color: var(--green); }

    .item360-body { display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center; }
    .dona-wrap { display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .dona-container { position: relative; width: 200px; height: 200px; }
    .dona-container canvas { position: absolute; top: 0; left: 0; }
    .dona-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
    .dona-score { font-size: 36px; font-weight: 700; color: var(--purple-dark); line-height: 1; }
    .dona-label { font-size: 11px; color: #999; margin-top: 2px; }
    .dona-legend { width: 100%; }
    .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-text); margin-bottom: 6px; }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .legend-pct { font-weight: 700; margin-left: auto; }

    .prod-right { display: flex; flex-direction: column; gap: 12px; }
    .prod-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--gray-soft); border-radius: 10px; }
    .prod-name { font-size: 13px; font-weight: 700; color: var(--purple-dark); width: 70px; flex-shrink: 0; }
    .prod-bar-wrap { flex: 1; }
    .prod-bar { height: 8px; background: var(--border); border-radius: 999px; overflow: hidden; margin-bottom: 3px; }
    .prod-bar-fill { height: 100%; border-radius: 999px; }
    .prod-bar-label { font-size: 11px; color: #999; }
    .prod-monto { font-size: 13px; font-weight: 600; color: var(--gray-text); white-space: nowrap; }
    .prod-trend { font-size: 12px; font-weight: 700; white-space: nowrap; }
    .up   { color: var(--green); }
    .flat { color: #AAA; }
    .down { color: var(--red); }
    .api-note { font-size: 11px; color: #AAA; text-align: center; margin-top: 20px; }

    .tarea-card { background: var(--white); border-radius: 12px; border: 0.5px solid var(--border); padding: 16px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 10px; transition: box-shadow .15s; }
    .tarea-card:hover { box-shadow: 0 4px 16px rgba(107,63,160,0.08); }
    .tarea-card.urgente     { border-left: 4px solid var(--red); }
    .tarea-card.advertencia { border-left: 4px solid var(--amber); }
    .tarea-card.info        { border-left: 4px solid var(--blue); }
    .tarea-card.ok          { border-left: 4px solid var(--green); }
    .tarea-icono { font-size: 22px; flex-shrink: 0; width: 40px; text-align: center; }
    .tarea-info { flex: 1; min-width: 0; }
    .tarea-titulo { font-size: 14px; font-weight: 700; color: var(--purple-dark); margin-bottom: 2px; }
    .tarea-desc { font-size: 12px; color: #999; line-height: 1.5; }
    .tarea-fecha { font-size: 11px; color: #BBB; white-space: nowrap; flex-shrink: 0; }
    .badge-urgente     { background: var(--red-bg);   color: var(--red);   font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
    .badge-advertencia { background: var(--amber-bg); color: var(--amber); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
    .badge-info        { background: var(--blue-bg);  color: var(--blue);  font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
    .badge-ok          { background: var(--green-bg); color: var(--green); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
    .btn-accion { padding: 6px 16px; background: var(--purple); color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; text-decoration: none; white-space: nowrap; flex-shrink: 0; transition: background .15s; }
    .btn-accion:hover { background: var(--purple-dark); }

    @media (max-width: 900px) {
        .resumen-grid, .action-items { grid-template-columns: 1fr 1fr; }
        .item360-body { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

    <div class="resumen-grid">
        <div class="resumen-card"><div class="accent" style="background:var(--red)"></div><div class="resumen-label">Documentos por vencer</div><div class="resumen-value val-red">2</div><div class="resumen-sub">⚠ Renovar CIF y Opinión SAT</div></div>
        <div class="resumen-card"><div class="accent" style="background:var(--amber)"></div><div class="resumen-label">Facturas pendientes</div><div class="resumen-value val-amber">3</div><div class="resumen-sub">⚠ Subir factura a OC #10045, #10046, #10049</div></div>
        <div class="resumen-card"><div class="accent" style="background:var(--blue)"></div><div class="resumen-label">Pagos próximos</div><div class="resumen-value val-blue">1</div><div class="resumen-sub">Verificar datos bancarios actualizados</div></div>
        <div class="resumen-card"><div class="accent" style="background:var(--green)"></div><div class="resumen-label">Notificaciones</div><div class="resumen-value val-green">4</div><div class="resumen-sub">Revisar mensajes de Salcom</div></div>
    </div>

    {{-- TOP PRODUCTOS --}}
    <div class="seccion">
        <div class="seccion-titulo">
            <div class="dot" style="background:var(--purple)"></div>
            Rendimiento de productos
            <span class="seccion-sub-label">⚠ Datos de prueba — Pendiente de API</span>
        </div>
        <div class="item360-wrap">
            <div class="action-items">
                <div class="action-card alerta"><div class="action-card-label">OC sin factura</div><div class="action-card-value">3</div><div class="action-card-sub">Requieren atención</div></div>
                <div class="action-card ok"><div class="action-card-label">OC completadas este mes</div><div class="action-card-value">8</div><div class="action-card-sub">97.6% cumplimiento</div></div>
                <div class="action-card"><div class="action-card-label">Recomendaciones de mejora</div><div class="action-card-value">2</div><div class="action-card-sub">Ver detalles →</div></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                {{-- TOP 5 MEJORES --}}
                <div>
                    <h4 style="font-size:14px;font-weight:700;color:var(--green);margin-bottom:12px;display:flex;align-items:center;gap:6px;">📈 Top 5 — Mejor rendimiento</h4>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Resina epóxica industrial</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:92%;background:#059669"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 92</div><div class="prod-trend up">↑ +12%</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Solvente grado técnico</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:88%;background:#059669"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 88</div><div class="prod-trend up">↑ +8%</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Pigmento base agua</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:81%;background:#059669"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 81</div><div class="prod-trend flat">→ Estable</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Fibra de refuerzo</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:79%;background:#059669"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 79</div><div class="prod-trend up">↑ +3%</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Adhesivo estructural</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:76%;background:#059669"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 76</div><div class="prod-trend flat">→ Estable</div></div>
                </div>

                {{-- TOP 5 PEORES --}}
                <div>
                    <h4 style="font-size:14px;font-weight:700;color:var(--red);margin-bottom:12px;display:flex;align-items:center;gap:6px;">📉 Top 5 — Necesitan atención</h4>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Aditivo antioxidante</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:58%;background:#DC2626"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 58</div><div class="prod-trend down">↓ -15%</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Catalizador rápido</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:62%;background:#D97706"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 62</div><div class="prod-trend down">↓ -5%</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Sellador industrial</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:65%;background:#D97706"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 65</div><div class="prod-trend down">↓ -3%</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Disolvente especial</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:68%;background:#D97706"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 68</div><div class="prod-trend flat">→ Estable</div></div>
                    <div class="prod-row"><div class="prod-name" style="width:auto;flex:1">Recubrimiento base</div><div class="prod-bar-wrap" style="flex:0 0 100px"><div class="prod-bar"><div class="prod-bar-fill" style="width:70%;background:#D97706"></div></div></div><div class="prod-monto" style="font-size:12px">Score: 70</div><div class="prod-trend down">↓ -2%</div></div>
                </div>
            </div>

            <p class="api-note">⚠ Datos de prueba — se reemplazarán con la API de Alan</p>
        </div>
    </div>

    {{-- DOCUMENTOS POR VENCER --}}
    <div class="seccion">
        <div class="seccion-titulo"><div class="dot" style="background:var(--red)"></div>Documentos por vencer</div>
        <div class="tarea-card urgente"><div class="tarea-icono">📄</div><div class="tarea-info"><div class="tarea-titulo">CIF — Constancia de Situación Fiscal</div><div class="tarea-desc">Tu Constancia de Situación Fiscal vence en 5 días. Actualízala para continuar operando sin interrupciones.</div></div><span class="badge-urgente">Urgente</span><div class="tarea-fecha">Vence: 11/04/2026</div><a href="/empresa" class="btn-accion">Actualizar</a></div>
        <div class="tarea-card advertencia"><div class="tarea-icono">✅</div><div class="tarea-info"><div class="tarea-titulo">Opinión de Cumplimiento del SAT</div><div class="tarea-desc">Tu Opinión Positiva vence en 18 días. Te recomendamos renovarla pronto para evitar retrasos en tus pagos.</div></div><span class="badge-advertencia">Próximo</span><div class="tarea-fecha">Vence: 24/04/2026</div><a href="/empresa" class="btn-accion">Actualizar</a></div>
    </div>

    {{-- FACTURAS PENDIENTES --}}
    <div class="seccion">
        <div class="seccion-titulo"><div class="dot" style="background:var(--amber)"></div>Facturas pendientes de subir</div>
        <div class="tarea-card advertencia"><div class="tarea-icono">🧾</div><div class="tarea-info"><div class="tarea-titulo">OC #10045 — $12,500.00</div><div class="tarea-desc">Esta orden de compra no tiene factura asociada. Súbela para iniciar el proceso de pago.</div></div><span class="badge-advertencia">Sin factura</span><div class="tarea-fecha">OC: 01/03/2026</div><a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a></div>
        <div class="tarea-card advertencia"><div class="tarea-icono">🧾</div><div class="tarea-info"><div class="tarea-titulo">OC #10046 — $8,200.00</div><div class="tarea-desc">Esta orden de compra no tiene factura asociada. Súbela para iniciar el proceso de pago.</div></div><span class="badge-advertencia">Sin factura</span><div class="tarea-fecha">OC: 05/03/2026</div><a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a></div>
        <div class="tarea-card advertencia"><div class="tarea-icono">🧾</div><div class="tarea-info"><div class="tarea-titulo">OC #10049 — $15,100.00</div><div class="tarea-desc">Esta orden de compra no tiene factura asociada. Súbela para iniciar el proceso de pago.</div></div><span class="badge-advertencia">Sin factura</span><div class="tarea-fecha">OC: 20/03/2026</div><a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a></div>
    </div>

    {{-- PAGOS PROXIMOS --}}
    <div class="seccion">
        <div class="seccion-titulo"><div class="dot" style="background:var(--blue)"></div>Pagos próximos</div>
        <div class="tarea-card info"><div class="tarea-icono">💳</div><div class="tarea-info"><div class="tarea-titulo">Pago programado — $27,300.00</div><div class="tarea-desc">Pago correspondiente a la OC #10047 programado para esta semana. Verifica que tus datos bancarios estén actualizados.</div></div><span class="badge-info">Esta semana</span><div class="tarea-fecha">09/04/2026</div><a href="{{ route('proveedores.dashboard') }}" class="btn-accion">Ver detalle</a></div>
    </div>

    {{-- NOTIFICACIONES --}}
    <div class="seccion">
        <div class="seccion-titulo"><div class="dot" style="background:var(--purple)"></div>Notificaciones de Industrias Salcom</div>
        <div class="tarea-card ok"><div class="tarea-icono">🎉</div><div class="tarea-info"><div class="tarea-titulo">¡Bienvenido al portal de proveedores!</div><div class="tarea-desc">Tu cuenta ha sido creada exitosamente. Completa tu onboarding para activar tu cuenta al 100%.</div></div><span class="badge-ok">Nuevo</span><div class="tarea-fecha">06/04/2026</div></div>
        <div class="tarea-card info"><div class="tarea-icono">📢</div><div class="tarea-info"><div class="tarea-titulo">Nueva orden de compra generada</div><div class="tarea-desc">Industrias Salcom ha generado una nueva OC #10049 por $15,100.00. Revísala en el módulo de consultar OC.</div></div><span class="badge-info">OC Nueva</span><div class="tarea-fecha">20/03/2026</div><a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a></div>
        <div class="tarea-card info"><div class="tarea-icono">📋</div><div class="tarea-info"><div class="tarea-titulo">Documentos en revisión</div><div class="tarea-desc">Tu CIF y Opinión Positiva están siendo revisados por el equipo de Salcom. Te notificaremos cuando estén aprobados.</div></div><span class="badge-info">En revisión</span><div class="tarea-fecha">15/03/2026</div></div>
        <div class="tarea-card ok"><div class="tarea-icono">✅</div><div class="tarea-info"><div class="tarea-titulo">Registro completado</div><div class="tarea-desc">Tu registro como proveedor fue completado exitosamente. Ya puedes acceder al portal.</div></div><span class="badge-ok">Completado</span><div class="tarea-fecha">01/03/2026</div></div>
    </div>

@endsection

@push('scripts')
<script>
// No donut chart needed — using list view
</script>
@endpush