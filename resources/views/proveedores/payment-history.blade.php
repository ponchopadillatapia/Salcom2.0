@extends('layouts.proveedor')

@section('title', 'Historial de Pagos')

@section('hero')
<div class="hero-band">
    <h1>Historial de Pagos</h1>
    <p>Consulta tus pagos realizados y facturas detalladas</p>
</div>
@endsection

@push('styles')
<style>
    .ph-info { background: var(--purple-light); border-radius: 10px; padding: 12px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 24px; font-size: 13px; color: var(--gray-text); flex-wrap: wrap; }
    .ph-info strong { color: var(--purple-dark); }
    .ph-info .change-link { color: var(--purple); font-weight: 600; text-decoration: none; margin-left: 4px; }

    .ph-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
    .ph-select { border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 13px; font-family: inherit; color: var(--gray-text); background: var(--white); outline: none; }
    .ph-search { flex: 1; min-width: 200px; border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 14px; font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none; }
    .ph-search:focus { border-color: var(--purple-mid); }
    .btn-download { display: flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px solid var(--border); border-radius: 8px; background: var(--white); font-size: 13px; font-family: inherit; color: var(--gray-text); cursor: pointer; font-weight: 500; }
    .btn-download:hover { border-color: var(--purple-mid); color: var(--purple); }

    .card { background: var(--white); border-radius: 14px; border: 1px solid var(--border); overflow: hidden; }
    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th { font-size: 12px; font-weight: 600; color: var(--gray-text); padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--border); background: var(--white); }
    .tabla th.sortable { cursor: pointer; }
    .tabla th.sortable::after { content: ' ↑'; color: var(--purple-mid); }
    .tabla td { padding: 14px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 1px solid var(--border); }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--purple-light); }
    .tabla .link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .tabla .link:hover { text-decoration: underline; }

    .badge-api { font-size: 11px; color: var(--amber); font-weight: 600; background: var(--amber-bg); padding: 3px 10px; border-radius: 999px; display: inline-block; margin-bottom: 16px; }
</style>
@endpush

@section('content')
    <span class="badge-api">⚠ Datos de prueba — Pendiente de API</span>

    <div class="ph-info">
        <span>Proveedor: <strong>{{ session('proveedor_codigo', '—') }}</strong> · {{ session('proveedor_nombre', 'Proveedor') }}</span>
        <span id="periodoTexto">Período: <strong id="periodoLabel">01/03/2026 - 31/03/2026</strong></span>
        <a href="#" class="change-link" onclick="document.getElementById('periodoForm').style.display='flex'; this.style.display='none'; return false;">Cambiar</a>
        <form id="periodoForm" style="display:none;align-items:center;gap:8px;margin-left:8px;">
            <input type="date" id="fechaDesde" value="2026-03-01" style="border:1.5px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;font-family:inherit;">
            <span style="color:var(--gray-muted);">—</span>
            <input type="date" id="fechaHasta" value="2026-03-31" style="border:1.5px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;font-family:inherit;">
            <button type="button" onclick="aplicarPeriodo()" style="padding:6px 14px;background:var(--purple);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">Aplicar</button>
            <button type="button" onclick="cancelarPeriodo()" style="padding:6px 14px;background:var(--gray-soft);color:var(--gray-text);border:1px solid var(--border);border-radius:6px;font-size:12px;cursor:pointer;font-family:inherit;">Cancelar</button>
        </form>
    </div>

    <div class="ph-toolbar">
        <select class="ph-select" id="filtroCampo"><option value="cheque">No. cheque</option><option value="monto">Monto pagado</option><option value="facturas">Facturas</option></select>
        <input type="text" class="ph-search" placeholder="Buscar...">
        <button class="btn-download" onclick="exportarCSV()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Descargar
        </button>
    </div>

    <div class="card">
        <table class="tabla" id="tablaPagos">
            <thead>
                <tr>
                    <th class="sortable">Fecha de pago</th>
                    <th>No. cheque</th>
                    <th>Monto pagado</th>
                    <th>Facturas</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>02/03/2026</td><td><a href="#" class="link">3511633</a></td><td>$81,151.02</td><td>5</td></tr>
                <tr><td>03/03/2026</td><td><a href="#" class="link">3547953</a></td><td>$59,453.16</td><td>6</td></tr>
                <tr><td>05/03/2026</td><td><a href="#" class="link">3558936</a></td><td>$78,732.85</td><td>6</td></tr>
                <tr><td>06/03/2026</td><td><a href="#" class="link">3565058</a></td><td>$88,787.13</td><td>4</td></tr>
                <tr><td>09/03/2026</td><td><a href="#" class="link">3571823</a></td><td>$247,854.21</td><td>6</td></tr>
                <tr><td>10/03/2026</td><td><a href="#" class="link">3577672</a></td><td>$16,503.79</td><td>2</td></tr>
                <tr><td>11/03/2026</td><td><a href="#" class="link">3582552</a></td><td>$17,869.15</td><td>1</td></tr>
                <tr><td>12/03/2026</td><td><a href="#" class="link">3588049</a></td><td>$36,547.96</td><td>2</td></tr>
                <tr><td>13/03/2026</td><td><a href="#" class="link">3595077</a></td><td>$130,325.33</td><td>2</td></tr>
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script>
function exportarCSV() {
    const tabla = document.getElementById('tablaPagos');
    let csv = '';
    tabla.querySelectorAll('tr').forEach(fila => {
        if (fila.style.display === 'none') return;
        const data = Array.from(fila.querySelectorAll('th,td')).map(c => '"' + c.textContent.trim().replace(/"/g,'""') + '"');
        csv += data.join(',') + '\n';
    });
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'historial-pagos-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

function aplicarPeriodo() {
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    if (!desde || !hasta) return;

    const desdeDate = new Date(desde);
    const hastaDate = new Date(hasta);
    const desdeStr = desdeDate.toLocaleDateString('es-MX', {day:'2-digit',month:'2-digit',year:'numeric'});
    const hastaStr = hastaDate.toLocaleDateString('es-MX', {day:'2-digit',month:'2-digit',year:'numeric'});

    document.getElementById('periodoLabel').textContent = desdeStr + ' - ' + hastaStr;
    document.getElementById('periodoForm').style.display = 'none';
    document.querySelector('.change-link').style.display = '';

    // Filtrar filas por fecha
    const filas = document.querySelectorAll('#tablaPagos tbody tr');
    filas.forEach(fila => {
        const fechaTexto = fila.cells[0].textContent.trim();
        const partes = fechaTexto.split('/');
        if (partes.length === 3) {
            const fechaFila = new Date(partes[2], partes[1]-1, partes[0]);
            fila.style.display = (fechaFila >= desdeDate && fechaFila <= hastaDate) ? '' : 'none';
        }
    });
}

function cancelarPeriodo() {
    document.getElementById('periodoForm').style.display = 'none';
    document.querySelector('.change-link').style.display = '';
}

// Buscar en tabla por columna seleccionada
document.querySelector('.ph-search').addEventListener('input', function() {
    const filtro = this.value.toLowerCase();
    const campo = document.getElementById('filtroCampo').value;
    const colIndex = campo === 'cheque' ? 1 : campo === 'monto' ? 2 : 3;
    const filas = document.querySelectorAll('#tablaPagos tbody tr');
    filas.forEach(fila => {
        const texto = fila.cells[colIndex].textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
});
</script>
@endpush
