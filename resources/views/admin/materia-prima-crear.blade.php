@extends('layouts.area')
@section('title', 'Nuevo Producto — Materia Prima')
@section('area-title', 'Materia Prima')
@section('hero')
    <h1>Nuevo Producto</h1>
    <p>Alta de materia prima en el sistema</p>
@endsection
@push('styles')
<style>
    .form-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:28px;margin-bottom:24px}
    .form-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:14px;border-bottom:2px solid var(--border-light)}
    .form-header h2{font-size:16px;font-weight:700;color:var(--gray-text)}
    .form-header .date{font-size:12px;color:var(--gray-muted)}

    .toolbar-btns{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
    .tb-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;border:1.5px solid var(--border-light);border-radius:8px;background:var(--white);color:var(--gray-text);text-decoration:none;transition:all .15s;cursor:pointer;font-family:inherit}
    .tb-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-light)}
    .tb-btn.primary{background:var(--purple);color:#fff;border-color:var(--purple)}
    .tb-btn.primary:hover{background:var(--purple-dark)}

    .section-bar{background:var(--purple);color:#fff;font-size:12px;font-weight:700;padding:6px 14px;border-radius:6px;margin:20px 0 12px;text-transform:uppercase;letter-spacing:.5px}

    .field-grid{display:grid;grid-template-columns:160px 1fr;gap:8px 16px;align-items:center;margin-bottom:6px}
    .field-grid label{font-size:12px;font-weight:600;color:var(--gray-text)}
    .field-grid input,.field-grid select,.field-grid textarea{border:1px solid var(--border-light);border-radius:6px;padding:7px 10px;font-size:13px;font-family:inherit;color:var(--gray-text);width:100%}
    .field-grid input:focus,.field-grid select:focus,.field-grid textarea:focus{outline:none;border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.1)}
    .field-grid textarea{resize:vertical;min-height:60px}

    .row-fields{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:6px}
    .row-fields-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:6px}

    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px}

    .check-group{display:flex;flex-direction:column;gap:6px;margin-top:4px}
    .check-item{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--gray-text)}
    .check-item input[type="checkbox"]{width:14px;height:14px;accent-color:var(--purple)}

    .photo-box{width:140px;height:140px;border:2px dashed var(--border-light);border-radius:10px;display:flex;align-items:center;justify-content:center;text-align:center;font-size:11px;color:var(--gray-muted);cursor:pointer;transition:all .15s;position:relative;overflow:hidden}
    .photo-box:hover{border-color:var(--purple);background:var(--purple-light)}
    .photo-box input{position:absolute;inset:0;opacity:0;cursor:pointer}

    .impuestos-grid{display:grid;grid-template-columns:160px 80px;gap:6px 12px;align-items:center}
    .impuestos-grid label{font-size:12px;color:var(--gray-text)}
    .impuestos-grid input{border:1px solid var(--border-light);border-radius:6px;padding:5px 8px;font-size:12px;text-align:right;width:100%}

    .required-mark{color:#dc2626;font-weight:700}
    .hint{font-size:11px;color:var(--gray-muted);margin-top:2px}

    @media(max-width:768px){.field-grid{grid-template-columns:1fr}.two-col{grid-template-columns:1fr}.row-fields,.row-fields-3{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

<div style="margin-bottom:16px">
    <a href="{{ route('admin.materia-prima') }}" class="tb-btn">← Regresar a lista</a>
</div>

<div class="form-card">
    <div class="form-header">
        <h2>Productos — Materia Prima</h2>
        <div class="date">Fecha de Registro: {{ now()->format('d/m/Y') }}</div>
    </div>

    <form method="POST" action="{{ route('admin.materia-prima.guardar') }}" enctype="multipart/form-data">
        @csrf

        {{-- Código y Nombre --}}
        <div class="row-fields" style="margin-bottom:16px">
            <div>
                <div class="field-grid">
                    <label>Código: <span class="required-mark">*</span></label>
                    <input type="text" name="codigo" placeholder="Ej: MP053-2" value="{{ old('codigo') }}" required>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <label style="font-size:12px;font-weight:600;white-space:nowrap">Inactivo</label>
                <input type="checkbox" name="inactivo" style="width:16px;height:16px;accent-color:var(--purple)">
            </div>
        </div>

        <div class="field-grid" style="margin-bottom:16px">
            <label>Nombre: <span class="required-mark">*</span></label>
            <input type="text" name="nombre" placeholder="Ej: POLIPROPILENO NATURAL 35 RECICLADO CLARIFICADO" value="{{ old('nombre') }}" required>
        </div>

        {{-- Tabs --}}
        <div style="display:flex;gap:0;border-bottom:2px solid var(--border-light);margin-bottom:16px">
            <div style="padding:8px 16px;font-size:12px;font-weight:700;color:var(--purple);border-bottom:2px solid var(--purple);margin-bottom:-2px">1 Datos Generales</div>
            <div style="padding:8px 16px;font-size:12px;font-weight:600;color:var(--gray-muted)">2 Precios y Costos</div>
        </div>

        <div class="two-col">
            <div>
                {{-- Clasificación --}}
                <div class="section-bar">Clasificación:</div>

                <div class="field-grid">
                    <label>Producción</label>
                    <div style="display:flex;gap:8px"><input type="text" name="cod_produccion" value="MP" style="width:50px" readonly><input type="text" value="MATERIA PRIMA" readonly style="background:#f5f5f5"></div>
                </div>
                <div class="field-grid">
                    <label>Familia</label>
                    <div style="display:flex;gap:8px"><input type="text" name="cod_familia" value="MP" style="width:50px" readonly><input type="text" name="familia" placeholder="Materia Prima" value="{{ old('familia', 'Materia Prima') }}"></div>
                </div>
                <div class="field-grid">
                    <label>Familias de P. Terminado</label>
                    <input type="text" name="familia_terminado" value="{{ old('familia_terminado') }}">
                </div>
                <div class="field-grid">
                    <label>Segmento de Mercado</label>
                    <input type="text" name="segmento_mercado" value="{{ old('segmento_mercado') }}">
                </div>
                <div class="field-grid">
                    <label>Agente de Venta</label>
                    <input type="text" name="agente_venta" value="{{ old('agente_venta') }}">
                </div>
                <div class="field-grid">
                    <label>Tipos de Producto</label>
                    <div style="display:flex;gap:8px"><input type="text" value="MP" style="width:50px" readonly><input type="text" value="MP" style="width:50px" readonly></div>
                </div>
            </div>

            <div>
                {{-- Tipo --}}
                <div class="section-bar">Tipo:</div>
                <select name="tipo_producto" style="border:1px solid var(--border-light);border-radius:6px;padding:7px 10px;font-size:13px;width:100%;margin-bottom:16px">
                    <option value="general">General</option>
                    <option value="resina">Resina</option>
                    <option value="solvente">Solvente</option>
                    <option value="pigmento">Pigmento</option>
                    <option value="aditivo">Aditivo</option>
                    <option value="otro">Otro</option>
                </select>

                {{-- Foto --}}
                <div class="photo-box">
                    <input type="file" name="foto_producto" accept="image/*">
                    <span>Fotografía<br>del<br>Producto</span>
                </div>
            </div>
        </div>

        {{-- Descripciones --}}
        <div class="section-bar">Descripciones:</div>

        <div class="row-fields">
            <div class="field-grid"><label>Código alterno:</label><input type="text" name="codigo_alterno" value="{{ old('codigo_alterno') }}"></div>
            <div class="field-grid"><label>Nombre alterno:</label><input type="text" name="nombre_alterno" value="{{ old('nombre_alterno') }}"></div>
        </div>
        <div class="row-fields">
            <div class="field-grid"><label>Clave SAT:</label><input type="text" name="clave_sat" value="{{ old('clave_sat') }}"></div>
            <div class="field-grid"><label>No. identificación:</label><input type="text" name="no_identificacion" value="{{ old('no_identificacion') }}"></div>
        </div>
        <div class="field-grid"><label>Descripción corta:</label><input type="text" name="descripcion_corta" value="{{ old('descripcion_corta') }}"></div>
        <div class="field-grid" style="margin-top:8px"><label>Descripción:</label><textarea name="descripcion" placeholder="Descripción completa del producto">{{ old('descripcion') }}</textarea></div>

        {{-- Criterios e Impuestos --}}
        <div class="two-col" style="margin-top:16px">
            <div>
                <div class="section-bar">Criterios de Control:</div>
                <div class="field-grid" style="margin-bottom:8px">
                    <label>Unidad de Medida:</label>
                    <select name="unidad_venta">
                        <option value="kg">KG</option>
                        <option value="lt">LT</option>
                        <option value="pz">PZ</option>
                        <option value="rollo">Rollo</option>
                        <option value="m">M</option>
                    </select>
                </div>
                <div class="check-group">
                    <label class="check-item"><input type="checkbox" name="ctrl_caracteristicas"> Características</label>
                    <label class="check-item"><input type="checkbox" name="ctrl_series"> Series</label>
                    <label class="check-item"><input type="checkbox" name="ctrl_pedimentos"> Pedimentos</label>
                    <label class="check-item"><input type="checkbox" name="ctrl_lotes" checked> Lotes</label>
                </div>
            </div>
            <div>
                <div class="section-bar">Impuestos:</div>
                <div class="impuestos-grid">
                    <label>I.V.A.</label><input type="text" name="iva" value="0.16">
                    <label>IEPS</label><input type="text" name="ieps" value="0.00">
                    <label>Retención I.V.A.</label><input type="text" name="retencion_iva" value="0.00">
                    <label>Retención I.S.R.</label><input type="text" name="retencion_isr" value="0.00">
                </div>
            </div>
        </div>

        {{-- Precios --}}
        <div class="section-bar" style="margin-top:20px">Precios y Stock:</div>
        <div class="row-fields-3">
            <div class="field-grid"><label>Precio:</label><input type="number" name="precio" step="0.01" value="{{ old('precio', '0.00') }}"></div>
            <div class="field-grid"><label>Stock inicial:</label><input type="number" name="stock" value="{{ old('stock', '0') }}"></div>
            <div class="field-grid"><label>Unidad XML:</label><input type="text" name="unidad_xml" value="{{ old('unidad_xml', 'KGM') }}"></div>
        </div>

        @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:10px 14px;font-size:12px;margin-top:16px">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <div style="margin-top:20px;display:flex;gap:10px">
            <button type="submit" class="tb-btn primary">Guardar</button>
            <a href="{{ route('admin.materia-prima') }}" class="tb-btn">Cancelar</a>
        </div>
    </form>
</div>

@endsection
