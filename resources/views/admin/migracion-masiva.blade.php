@extends('layouts.admin')
@section('title', 'Migración Masiva')
@section('hero')
<div class="hero-band">
    <h1>Migración Masiva</h1>
    <p>Sube el Excel del sistema viejo y la IA descompondrá cada producto en el formato nuevo (Tipo, Marca, Modelo, Medida, Especificación).</p>
</div>
@endsection
@push('styles')
<style>
    .migracion-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px}
    .migracion-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px}
    .migracion-card h3{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:16px}
    .upload-zone{border:2px dashed var(--border);border-radius:14px;padding:40px;text-align:center;transition:var(--transition);cursor:pointer}
    .upload-zone:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .upload-zone.dragover{border-color:var(--purple);background:var(--purple-light)}
    .btn-upload{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:var(--transition);margin-top:16px}
    .btn-upload:hover{background:var(--purple-dark)}
    .btn-upload:disabled{opacity:.5;cursor:not-allowed}
    .btn-download{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none;transition:var(--transition)}
    .btn-download:hover{background:#15803d}

    /* Progreso */
    .progreso-section{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px;margin-bottom:24px}
    .progreso-bar-container{background:var(--gray-soft);border-radius:10px;height:24px;overflow:hidden;margin:12px 0}
    .progreso-bar{height:100%;background:linear-gradient(90deg, var(--purple), #8b5cf6);border-radius:10px;transition:width .5s ease;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff}
    .progreso-info{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:var(--gray-muted)}
    .progreso-info strong{color:var(--gray-text)}

    /* Badges de estatus */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600}
    .badge-pendiente{background:#fef3c7;color:#92400e}
    .badge-procesando{background:#dbeafe;color:#1e40af}
    .badge-completado{background:#d1fae5;color:#065f46}
    .badge-error{background:#fee2e2;color:#991b1b}

    /* Tabla de migraciones */
    .tabla-migraciones{width:100%;border-collapse:collapse;font-size:13px}
    .tabla-migraciones th{text-align:left;padding:10px 12px;background:var(--gray-soft);font-weight:600;color:var(--gray-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-light)}
    .tabla-migraciones td{padding:10px 12px;border-bottom:1px solid var(--border-light);color:var(--gray-text)}

    .alert-success{background:var(--green-bg);border:1px solid var(--green);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--green);margin-bottom:16px}
    .alert-error{background:var(--red-bg);border:1px solid var(--red);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--red);margin-bottom:16px}

    @media(max-width:768px){.migracion-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
<div class="alert-success">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
<div class="alert-error">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert-error">
    @foreach($errors->all() as $error)
        {{ $error }}<br>
    @endforeach
</div>
@endif

{{-- ═══ PROGRESO DE MIGRACIÓN ACTIVA ═══ --}}
@if($migracionActiva)
<div class="progreso-section" id="progresoSection" data-migracion-id="{{ $migracionActiva->id }}">
    <h3 style="margin:0 0 8px 0;font-size:15px;font-weight:700;color:var(--gray-text);">
        <span class="badge badge-{{ $migracionActiva->estatus }}">{{ ucfirst($migracionActiva->estatus) }}</span>
        Migración en curso
    </h3>
    <div class="progreso-info">
        <span id="progresoTexto">Procesando lote <strong id="lotesCompletados">{{ $migracionActiva->lotes_completados }}</strong> de <strong>{{ $migracionActiva->lotes_total }}</strong>...</span>
        <span><strong id="productosConteo">{{ number_format($migracionActiva->productos_procesados) }}</strong>/{{ number_format($migracionActiva->total_productos) }} productos procesados</span>
    </div>
    <div class="progreso-bar-container">
        <div class="progreso-bar" id="progresoBar" style="width: {{ $migracionActiva->porcentaje }}%">
            {{ $migracionActiva->porcentaje }}%
        </div>
    </div>
    <div class="progreso-info" style="margin-top:8px;">
        <span>Productos con error: <strong id="productosError" style="color:var(--red);">{{ $migracionActiva->productos_error }}</strong></span>
        <span>Archivo: {{ basename($migracionActiva->archivo_path) }}</span>
    </div>
</div>
@endif

{{-- ═══ GRID: INSTRUCCIONES + UPLOAD ═══ --}}
<div class="migracion-grid">
    {{-- Instrucciones --}}
    <div class="migracion-card">
        <h3>¿Cómo funciona?</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:flex-start;gap:12px;font-size:13px;">
                <div style="width:28px;height:28px;border-radius:50%;background:var(--purple);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">1</div>
                <div style="padding-top:4px;color:var(--gray-text);"><strong style="display:block;margin-bottom:2px;">Sube el Excel del sistema viejo</strong>El archivo con los ~3,000 productos tal cual están hoy.</div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:12px;font-size:13px;">
                <div style="width:28px;height:28px;border-radius:50%;background:var(--purple);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">2</div>
                <div style="padding-top:4px;color:var(--gray-text);"><strong style="display:block;margin-bottom:2px;">La IA procesa en lotes de 50</strong>Claude analiza cada producto y lo descompone en: Tipo, Marca, Modelo, Medida, Especificación.</div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:12px;font-size:13px;">
                <div style="width:28px;height:28px;border-radius:50%;background:var(--purple);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">3</div>
                <div style="padding-top:4px;color:var(--gray-text);"><strong style="display:block;margin-bottom:2px;">Descarga el resultado</strong>Un Excel con todos los productos ya en el formato nuevo, listo para revisión.</div>
            </div>
        </div>

        <div style="background:var(--gray-soft);border-radius:10px;padding:16px;margin-top:20px;">
            <h4 style="font-size:12px;font-weight:700;color:var(--gray-text);margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;">Notas importantes</h4>
            <ul style="list-style:none;padding:0;margin:0;font-size:12px;color:var(--gray-muted);">
                <li style="padding:4px 0;display:flex;align-items:center;gap:6px;"><span style="width:6px;height:6px;border-radius:50%;background:var(--purple);flex-shrink:0;"></span> El proceso corre en segundo plano — puedes cerrar la pestaña</li>
                <li style="padding:4px 0;display:flex;align-items:center;gap:6px;"><span style="width:6px;height:6px;border-radius:50%;background:var(--purple);flex-shrink:0;"></span> Los lotes con error se pueden reprocesar</li>
                <li style="padding:4px 0;display:flex;align-items:center;gap:6px;"><span style="width:6px;height:6px;border-radius:50%;background:var(--purple);flex-shrink:0;"></span> El Excel de resultados estará disponible al terminar</li>
                <li style="padding:4px 0;display:flex;align-items:center;gap:6px;"><span style="width:6px;height:6px;border-radius:50%;background:var(--purple);flex-shrink:0;"></span> Formatos aceptados: .xlsx, .xls, .csv (máx 10MB)</li>
            </ul>
        </div>
    </div>

    {{-- Upload --}}
    <div class="migracion-card">
        <h3>Subir Excel del sistema viejo</h3>
        <form method="POST" action="{{ route('admin.migracion-masiva.subir') }}" enctype="multipart/form-data" id="formMigracion">
            @csrf
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                <div>
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div style="font-size:14px;font-weight:600;color:var(--gray-text);margin-top:8px;">Arrastra tu Excel aquí o haz clic</div>
                <div style="font-size:12px;color:var(--gray-muted);margin-top:4px;">Formatos: .xlsx, .xls, .csv · Máximo 10MB</div>
                <div id="fileName" style="margin-top:8px;font-size:12px;color:var(--purple);font-weight:600;display:none;"></div>
            </div>
            <input type="file" name="excel" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none;" onchange="showFileName(this)">
            <button type="submit" class="btn-upload" id="btnUpload" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Iniciar Migración
            </button>
        </form>
    </div>
</div>

{{-- ═══ TABLA: HISTORIAL DE MIGRACIONES ═══ --}}
<div class="migracion-card">
    <h3>Historial de migraciones</h3>
    @if($migraciones->isEmpty())
        <p style="font-size:13px;color:var(--gray-muted);text-align:center;padding:20px 0;">No hay migraciones registradas.</p>
    @else
        <table class="tabla-migraciones">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Total</th>
                    <th>Procesados</th>
                    <th>Errores</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($migraciones as $mig)
                <tr>
                    <td>{{ $mig->created_at->format('d/m/Y H:i') }}</td>
                    <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ basename($mig->archivo_path) }}</td>
                    <td>{{ number_format($mig->total_productos) }}</td>
                    <td>{{ number_format($mig->productos_procesados) }}</td>
                    <td style="color:{{ $mig->productos_error > 0 ? 'var(--red)' : 'inherit' }}">{{ number_format($mig->productos_error) }}</td>
                    <td><span class="badge badge-{{ $mig->estatus }}">{{ ucfirst($mig->estatus) }}</span></td>
                    <td>
                        @if($mig->resultado_path)
                            <a href="{{ asset('storage/' . $mig->resultado_path) }}" download class="btn-download">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Descargar
                            </a>
                        @else
                            <span style="font-size:11px;color:var(--gray-muted);">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
@push('scripts')
<script>
// ═══ Drag & Drop ═══
function showFileName(input) {
    const name = input.files[0]?.name;
    if (name) {
        document.getElementById('fileName').textContent = name;
        document.getElementById('fileName').style.display = 'block';
        document.getElementById('btnUpload').disabled = false;
    }
}

const zone = document.getElementById('uploadZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv'))) {
        const input = document.getElementById('fileInput');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showFileName(input);
    }
});

// ═══ AJAX Polling: actualizar progreso cada 3 segundos ═══
(function() {
    const progresoSection = document.getElementById('progresoSection');
    if (!progresoSection) return; // No hay migración activa

    const migracionId = progresoSection.dataset.migracionId;
    let polling = null;

    function actualizarProgreso() {
        fetch(`/admin/migracion-masiva/${migracionId}/estado`)
            .then(r => r.json())
            .then(data => {
                // Actualizar barra de progreso
                const bar = document.getElementById('progresoBar');
                bar.style.width = data.porcentaje + '%';
                bar.textContent = data.porcentaje + '%';

                // Actualizar conteos
                document.getElementById('lotesCompletados').textContent = data.lotes_completados;
                document.getElementById('productosConteo').textContent = data.productos_procesados.toLocaleString();
                document.getElementById('productosError').textContent = data.productos_error;

                // Si terminó, detener polling y recargar
                if (data.estatus === 'completado' || data.estatus === 'error') {
                    clearInterval(polling);
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(err => console.error('Error polling:', err));
    }

    // Polling cada 3 segundos
    polling = setInterval(actualizarProgreso, 3000);
})();
</script>
@endpush
