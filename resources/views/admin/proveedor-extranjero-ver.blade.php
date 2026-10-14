@extends('layouts.admin')
@section('title', 'Proveedor extranjero')
@section('hero')
<div class="hero-band">
    <h1>{{ $proveedor->nombre ?? $proveedor->usuario }}</h1>
    <p>Revisión de proveedor extranjero (importación / C-TPAT)</p>
</div>
@endsection
@push('styles')
<style>
    .pe-wrap{max-width:1000px;margin:0 auto;display:flex;flex-direction:column;gap:16px}
    .pe-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px 24px;box-shadow:var(--shadow-sm)}
    .pe-card h3{font-size:14px;font-weight:700;color:var(--gray-text);margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid var(--border-light)}
    .pe-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;font-size:13px}
    .pe-grid.c3{grid-template-columns:1fr 1fr 1fr}
    .pe-label{font-weight:600;color:var(--gray-muted);font-size:11px;text-transform:uppercase;letter-spacing:.4px}
    .pe-value{color:var(--gray-text)}
    .pe-yn-row{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .pe-yn-row .q{color:var(--gray-text)}
    .pe-yn{font-weight:700;font-size:12px;padding:2px 10px;border-radius:20px}
    .pe-yn.yes{background:var(--green-bg);color:var(--green)} .pe-yn.no{background:var(--red-bg);color:var(--red)} .pe-yn.na{background:var(--border-light);color:var(--gray-muted)}
    .pe-doc{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .pe-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
    .pe-badge.pend{background:var(--amber-bg);color:var(--amber)} .pe-badge.appr{background:var(--green-bg);color:var(--green)} .pe-badge.rej{background:var(--red-bg);color:var(--red)} .pe-badge.none{background:var(--border-light);color:var(--gray-muted)}
    .pe-actions{display:flex;gap:10px;justify-content:flex-end}
    .btn-aprobar{padding:9px 20px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-rechazar{padding:9px 20px;background:var(--red);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-back{font-size:12px;color:var(--purple);text-decoration:none}
    @media(max-width:768px){.pe-grid,.pe-grid.c3{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@php
    // Closure (no función global) para evitar "cannot redeclare" si la vista se renderiza más de una vez.
    $peYn = function ($v) {
        if ($v === true) return ['yes', 'Yes'];
        if ($v === false) return ['no', 'No'];
        return ['na', '—'];
    };
@endphp
<div class="pe-wrap">
    <a href="{{ route('admin.solicitudes-alta') }}" class="btn-back">← Volver a solicitudes</a>

    @if(session('error'))<div style="background:var(--red-bg);border:1px solid var(--red);border-radius:10px;padding:12px 16px;color:var(--red);font-size:13px;font-weight:600;">{{ session('error') }}</div>@endif

    {{-- Business Profile --}}
    <div class="pe-card">
        <h3>Business Profile</h3>
        @if($perfil)
        <div class="pe-grid c3">
            <div><div class="pe-label">Company name</div><div class="pe-value">{{ $perfil->company_name ?: '—' }}</div></div>
            <div><div class="pe-label">Tax ID number</div><div class="pe-value">{{ $perfil->tax_id_number ?: '—' }}</div></div>
            <div><div class="pe-label">Year established</div><div class="pe-value">{{ $perfil->year_established ?: '—' }}</div></div>
            <div style="grid-column:1/-1;"><div class="pe-label">Complete address</div><div class="pe-value">{{ $perfil->complete_address ?: '—' }} {{ $perfil->zip_code ? '· ZIP '.$perfil->zip_code : '' }}</div></div>
            <div><div class="pe-label">Type of business</div><div class="pe-value">{{ $perfil->type_of_business ?: '—' }}</div></div>
            <div><div class="pe-label">Telephone</div><div class="pe-value">{{ $perfil->telephone_number ?: '—' }}</div></div>
            <div><div class="pe-label">Number of employees</div><div class="pe-value">{{ $perfil->number_of_employees ?: '—' }}</div></div>
            <div><div class="pe-label">President / Owner / CEO</div><div class="pe-value">{{ $perfil->president_owner_ceo ?: '—' }}</div></div>
            <div><div class="pe-label">Website</div><div class="pe-value">{{ $perfil->website ?: '—' }}</div></div>
            <div><div class="pe-label">C-TPAT certified</div><div class="pe-value">{{ $perfil->ctpat_certified===true ? 'Yes · SVI '.$perfil->svi_number : ($perfil->ctpat_certified===false ? 'No · plan: '.$perfil->plan_to_sign_up : '—') }}</div></div>
            <div><div class="pe-label">Warehouse</div><div class="pe-value">{{ $perfil->owns_warehouse===true?'Yes':($perfil->owns_warehouse===false?'No':'—') }}</div></div>
            <div><div class="pe-label">Trailers / Tractors</div><div class="pe-value">{{ $perfil->trailers_count ?? '—' }} / {{ $perfil->tractors_count ?? '—' }}</div></div>
            <div style="grid-column:1/-1;"><div class="pe-label">Professional associations</div><div class="pe-value">{{ $perfil->professional_associations ?: '—' }}</div></div>
            <div><div class="pe-label">Reference 1</div><div class="pe-value">{{ $perfil->reference1_name ?: '—' }} · {{ $perfil->reference1_phone ?: '—' }}</div></div>
            <div><div class="pe-label">Reference 2</div><div class="pe-value">{{ $perfil->reference2_name ?: '—' }} · {{ $perfil->reference2_phone ?: '—' }}</div></div>
        </div>
        @else
        <p style="color:var(--gray-muted);font-size:13px;">El proveedor aún no ha completado el Business Profile.</p>
        @endif
    </div>

    {{-- Security Questionnaire --}}
    <div class="pe-card">
        <h3>Security Confidential Questionnaire (C-TPAT)</h3>
        @if($perfil)
        <div class="pe-grid" style="margin-bottom:14px;">
            @php [$c,$t] = $peYn($perfil->sec_ctpat_certified); @endphp
            <div><div class="pe-label">C-TPAT certified</div><div class="pe-value">{{ $t }} {{ $perfil->sec_svi_number ? '· SVI '.$perfil->sec_svi_number : '' }}</div></div>
            @php [$c2,$t2] = $peYn($perfil->mutual_recognition_member); @endphp
            <div><div class="pe-label">Mutual recognition member</div><div class="pe-value">{{ $t2 }} {{ $perfil->mutual_recognition_which ? '· '.$perfil->mutual_recognition_which : '' }}</div></div>
        </div>
        @php
            $preguntas = [
                'physical_security'=>'Physical Security','fencing'=>'Fencing','gates_gatehouses'=>'Gates and Gate Houses',
                'parking_restrictions'=>'Parking restrictions','locking_devices'=>'Locking devices & key controls',
                'lighting'=>'Lighting','alarm_video'=>'Alarm & video surveillance','it_security'=>'IT security',
                'access_controls'=>'Access controls','visitor_photo_id'=>'Visitor photo ID','challenge_unauthorized'=>'Challenge unauthorized persons',
                'container_security'=>'Container security','container_inspection'=>'Container inspection (7-point)','container_seals'=>'Container seals procedures',
                'seal_discrepancy_procedure'=>'Seal discrepancy procedure','seal_control'=>'Seal control','container_storage'=>'Container storage',
                'procedural_security'=>'Procedural security','documentation_processing'=>'Documentation processing','personnel_security'=>'Personnel security',
                'education_training'=>'Education & training','threat_awareness'=>'Threat awareness','documentation_processing2'=>'Shipment documentation protected',
                'manifest_procedures'=>'Manifest procedures',
            ];
        @endphp
        @foreach($preguntas as $campo => $texto)
            @php [$cls,$lbl] = $peYn($perfil->$campo); @endphp
            <div class="pe-yn-row"><span class="q">{{ $texto }}</span><span class="pe-yn {{ $cls }}">{{ $lbl }}</span></div>
        @endforeach
        <div class="pe-grid c3" style="margin-top:14px;">
            <div><div class="pe-label">Signed — Print name</div><div class="pe-value">{{ $perfil->signed_print_name ?: '—' }}</div></div>
            <div><div class="pe-label">Title</div><div class="pe-value">{{ $perfil->signed_title ?: '—' }}</div></div>
            <div><div class="pe-label">Date</div><div class="pe-value">{{ $perfil->signed_date ? $perfil->signed_date->format('d/m/Y') : '—' }}</div></div>
        </div>
        @else
        <p style="color:var(--gray-muted);font-size:13px;">El proveedor aún no ha completado el cuestionario de seguridad.</p>
        @endif
    </div>

    {{-- Documentos --}}
    <div class="pe-card">
        <h3>Required documents</h3>
        @foreach($docsRequeridos as $tipo => $label)
            @php
                $doc = $proveedor->documentos->firstWhere('tipo', $tipo);
                $cls = ! $doc ? 'none' : ($doc->estatus==='aprobado'?'appr':($doc->estatus==='rechazado'?'rej':'pend'));
                $txt = ! $doc ? 'Not uploaded' : ucfirst($doc->estatus);
            @endphp
            <div class="pe-doc">
                <div>{{ $label }}
                    @if($doc)<br><a href="{{ asset('storage/'.$doc->archivo) }}" target="_blank" style="color:var(--purple);font-size:12px;">Ver archivo</a>@endif
                </div>
                <span class="pe-badge {{ $cls }}">{{ $txt }}</span>
            </div>
        @endforeach
    </div>

    {{-- Acciones --}}
    <div class="pe-card">
        <div class="pe-actions">
            <form method="POST" action="{{ route('admin.solicitudes-alta.extranjero.rechazar') }}" onsubmit="return confirm('¿Rechazar a este proveedor extranjero?');">
                @csrf
                <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
                <button type="submit" class="btn-rechazar">✕ Rechazar</button>
            </form>
            <form method="POST" action="{{ route('admin.solicitudes-alta.extranjero.aprobar') }}" onsubmit="return confirm('¿Aprobar y activar a este proveedor extranjero?');">
                @csrf
                <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
                <button type="submit" class="btn-aprobar">✓ Aprobar y activar</button>
            </form>
        </div>
    </div>
</div>
@endsection
