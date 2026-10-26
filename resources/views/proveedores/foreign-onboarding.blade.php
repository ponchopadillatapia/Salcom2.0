<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foreign Supplier Onboarding — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
    <style>
        .fo-wrap { max-width: 960px; margin: 0 auto; padding: 28px 18px 60px; }
        .fo-brand { text-align:center; margin-bottom: 18px; }
        .fo-brand p { color: rgba(255,255,255,.55); font-size: 12px; letter-spacing:.8px; margin-top:6px; }
        .fo-card { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 26px 28px; }
        .fo-title { font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 4px; }
        .fo-sub { font-size: 14px; color: rgba(255,255,255,.55); margin: 0 0 20px; }
        /* Steps bar */
        .fo-steps { display:flex; gap:10px; margin-bottom: 24px; flex-wrap:wrap; }
        .fo-step-pill { flex:1; min-width: 180px; display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:12px; border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.04); cursor:pointer; }
        .fo-step-pill.active { border-color: rgba(139,92,246,.6); background: rgba(139,92,246,.15); }
        .fo-step-pill.done .fo-step-num { background:#22c55e; }
        .fo-step-num { width:26px; height:26px; border-radius:50%; background: rgba(255,255,255,.15); color:#fff; font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .fo-step-txt { color:#fff; font-size:13px; font-weight:600; } .fo-step-txt small { display:block; color:rgba(255,255,255,.5); font-weight:400; }
        .fo-panel { display:none; } .fo-panel.active { display:block; }
        .fo-section-title { font-size:11px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:rgba(255,255,255,.42); margin:18px 0 10px; }
        .fo-row { display:grid; grid-template-columns:1fr 1fr; gap:14px 18px; }
        .fo-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px 18px; }
        .fo-field { display:flex; flex-direction:column; gap:6px; margin-bottom:4px; }
        .fo-field label { font-size:12px; font-weight:600; color:rgba(255,255,255,.78); }
        .fo-field input[type=text], .fo-field input[type=email], .fo-field input[type=tel], .fo-field input[type=date], .fo-field input[type=number], .fo-field select {
            width:100%; border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:11px 14px; font-size:14px; font-family:inherit; color:#fff; background:rgba(255,255,255,.05); outline:none;
        }
        .fo-field input:focus, .fo-field select:focus { border-color: rgba(139,92,246,.5); background: rgba(255,255,255,.08); }
        .fo-req { color:#c4b5fd; }
        .fo-yn { display:flex; gap:16px; align-items:center; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.07); }
        .fo-yn .q { flex:1; color:rgba(255,255,255,.85); font-size:13px; line-height:1.4; }
        .fo-yn label { color:#fff; font-size:13px; display:flex; align-items:center; gap:5px; cursor:pointer; }
        .fo-actions { display:flex; justify-content:space-between; gap:12px; margin-top:24px; }
        .fo-btn { padding:12px 22px; border-radius:12px; font-size:14px; font-weight:600; cursor:pointer; border:none; font-family:inherit; }
        .fo-btn-primary { background:#7c3aed; color:#fff; } .fo-btn-ghost { background:rgba(255,255,255,.08); color:#fff; }
        .fo-ok { background: rgba(34,197,94,.15); border:1px solid rgba(34,197,94,.4); color:#86efac; border-radius:10px; padding:12px 16px; margin-bottom:16px; font-size:13px; }
        .fo-err { background: rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.4); color:#fca5a5; border-radius:10px; padding:12px 16px; margin-bottom:16px; font-size:13px; }
        .fo-hint { font-size:11px; color:rgba(255,255,255,.45); }
        .fo-doc { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px; border:1px solid rgba(255,255,255,.1); border-radius:12px; margin-bottom:12px; flex-wrap:wrap; }
        .fo-doc-info { color:#fff; font-size:13px; font-weight:600; } .fo-doc-info small { display:block; color:rgba(255,255,255,.5); font-weight:400; }
        .fo-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .fo-badge.pend { background:#fef3c7; color:#92400e; } .fo-badge.appr { background:#dcfce7; color:#166534; } .fo-badge.rej { background:#fee2e2; color:#991b1b; }
        .fo-cond { display:none; } .fo-cond.show { display:block; }
        @media(max-width:760px){ .fo-row, .fo-row-3 { grid-template-columns:1fr; } }
    </style>
</head>
<body class="ios-login-bg">
<div class="orb-accent"></div>
<div class="fo-wrap">
    <div class="fo-brand">
        @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        <p>FOREIGN SUPPLIER ONBOARDING</p>
    </div>

    <div class="fo-card">
        <h1 class="fo-title">Welcome, {{ $proveedor->nombre }}</h1>
        <p class="fo-sub">Complete the following steps so our team can review and activate your account.</p>

        @if(session('mensaje'))<div class="fo-ok">{{ session('mensaje') }}</div>@endif
        @if($errors->any())
            <div class="fo-err"><ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        {{-- Steps nav --}}
        <div class="fo-steps">
            <div class="fo-step-pill {{ $perfil->profile_completed ? 'done' : '' }}" data-step="1" onclick="foGo(1)">
                <div class="fo-step-num">1</div><div class="fo-step-txt">Business Profile<small>Company information</small></div>
            </div>
            <div class="fo-step-pill {{ $perfil->security_completed ? 'done' : '' }}" data-step="2" onclick="foGo(2)">
                <div class="fo-step-num">2</div><div class="fo-step-txt">Security Questionnaire<small>C-TPAT</small></div>
            </div>
            <div class="fo-step-pill" data-step="3" onclick="foGo(3)">
                <div class="fo-step-num">3</div><div class="fo-step-txt">Documents<small>Required attachments</small></div>
            </div>
        </div>

        {{-- ───────────── STEP 1: BUSINESS PROFILE ───────────── --}}
        <div class="fo-panel" id="fo-panel-1">
            <form method="POST" action="{{ route('proveedores.foreign-onboarding.profile') }}">
                @csrf
                <div class="fo-section-title">Company information</div>
                <div class="fo-row">
                    <div class="fo-field"><label>Name of Company <span class="fo-req">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $perfil->company_name) }}" required></div>
                    <div class="fo-field"><label>Tax ID Number <span class="fo-req">*</span></label>
                        <input type="text" name="tax_id_number" value="{{ old('tax_id_number', $perfil->tax_id_number) }}" required></div>
                </div>
                <div class="fo-field"><label>Complete Address (including ZIP Code) <span class="fo-req">*</span></label>
                    <input type="text" name="complete_address" value="{{ old('complete_address', $perfil->complete_address) }}" required></div>
                <div class="fo-row-3">
                    <div class="fo-field"><label>ZIP Code</label>
                        <input type="text" name="zip_code" value="{{ old('zip_code', $perfil->zip_code) }}"></div>
                    <div class="fo-field"><label>Year Established</label>
                        <input type="text" name="year_established" value="{{ old('year_established', $perfil->year_established) }}"></div>
                    <div class="fo-field"><label>Number of Employees</label>
                        <input type="text" name="number_of_employees" value="{{ old('number_of_employees', $perfil->number_of_employees) }}"></div>
                </div>
                <div class="fo-row">
                    <div class="fo-field"><label>Type of Business</label>
                        <input type="text" name="type_of_business" value="{{ old('type_of_business', $perfil->type_of_business) }}"></div>
                    <div class="fo-field"><label>Business Address</label>
                        <input type="text" name="business_address" value="{{ old('business_address', $perfil->business_address) }}"></div>
                </div>
                <div class="fo-row">
                    <div class="fo-field"><label>Telephone Number</label>
                        <input type="tel" name="telephone_number" value="{{ old('telephone_number', $perfil->telephone_number) }}"></div>
                    <div class="fo-field"><label>President / Owner / CEO</label>
                        <input type="text" name="president_owner_ceo" value="{{ old('president_owner_ceo', $perfil->president_owner_ceo) }}"></div>
                </div>
                <div class="fo-field"><label>Website</label>
                    <input type="text" name="website" value="{{ old('website', $perfil->website) }}"></div>

                <div class="fo-section-title">C-TPAT</div>
                <div class="fo-row">
                    <div class="fo-field"><label>Are you C-TPAT Certified?</label>
                        <select name="ctpat_certified" id="fo_ctpat">
                            <option value="">— Select —</option>
                            <option value="1" {{ old('ctpat_certified', $perfil->ctpat_certified)==='1'||$perfil->ctpat_certified===true?'selected':'' }}>Yes</option>
                            <option value="0" {{ (old('ctpat_certified', $perfil->ctpat_certified)==='0'||$perfil->ctpat_certified===false) && $perfil->ctpat_certified!==null?'selected':'' }}>No</option>
                        </select></div>
                    <div class="fo-field fo-cond" id="fo_svi_wrap"><label>SVI #</label>
                        <input type="text" name="svi_number" value="{{ old('svi_number', $perfil->svi_number) }}"></div>
                    <div class="fo-field fo-cond" id="fo_plan_wrap"><label>If no, do you plan to sign up?</label>
                        <input type="text" name="plan_to_sign_up" value="{{ old('plan_to_sign_up', $perfil->plan_to_sign_up) }}"></div>
                </div>

                <div class="fo-section-title">Assets & associations</div>
                <div class="fo-field"><label>Professional Associations</label>
                    <input type="text" name="professional_associations" value="{{ old('professional_associations', $perfil->professional_associations) }}"></div>
                <div class="fo-row-3">
                    <div class="fo-field"><label>Do you own a warehouse?</label>
                        <select name="owns_warehouse"><option value="">—</option>
                            <option value="1" {{ $perfil->owns_warehouse===true?'selected':'' }}>Yes</option>
                            <option value="0" {{ $perfil->owns_warehouse===false?'selected':'' }}>No</option></select></div>
                    <div class="fo-field"><label>Do you own trailers? How many?</label>
                        <input type="number" min="0" name="trailers_count" placeholder="0" value="{{ old('trailers_count', $perfil->trailers_count) }}"></div>
                    <div class="fo-field"><label>Do you own tractors? How many?</label>
                        <input type="number" min="0" name="tractors_count" placeholder="0" value="{{ old('tractors_count', $perfil->tractors_count) }}"></div>
                </div>
                {{-- Flags derivados de los counts (si > 0, owns = yes). Se mandan ocultos. --}}
                <input type="hidden" name="owns_trailers" value="1">
                <input type="hidden" name="owns_tractors" value="1">

                <div class="fo-section-title">Two business references</div>
                <div class="fo-row">
                    <div class="fo-field"><label>Reference 1 — Name</label>
                        <input type="text" name="reference1_name" value="{{ old('reference1_name', $perfil->reference1_name) }}"></div>
                    <div class="fo-field"><label>Reference 1 — Phone</label>
                        <input type="tel" name="reference1_phone" value="{{ old('reference1_phone', $perfil->reference1_phone) }}"></div>
                    <div class="fo-field"><label>Reference 2 — Name</label>
                        <input type="text" name="reference2_name" value="{{ old('reference2_name', $perfil->reference2_name) }}"></div>
                    <div class="fo-field"><label>Reference 2 — Phone</label>
                        <input type="tel" name="reference2_phone" value="{{ old('reference2_phone', $perfil->reference2_phone) }}"></div>
                </div>

                <div class="fo-actions">
                    <span></span>
                    <button type="submit" class="fo-btn fo-btn-primary">Save &amp; continue →</button>
                </div>
            </form>
        </div>

        {{-- ───────────── STEP 2: SECURITY QUESTIONNAIRE ───────────── --}}
        <div class="fo-panel" id="fo-panel-2">
            <form method="POST" action="{{ route('proveedores.foreign-onboarding.security') }}">
                @csrf
                <div class="fo-section-title">Section I</div>
                <div class="fo-row">
                    <div class="fo-field"><label>Are you C-TPAT certified?</label>
                        <select name="sec_ctpat_certified"><option value="">—</option>
                            <option value="1" {{ $perfil->sec_ctpat_certified===true?'selected':'' }}>Yes</option>
                            <option value="0" {{ $perfil->sec_ctpat_certified===false?'selected':'' }}>No</option></select></div>
                    <div class="fo-field"><label>SVI #</label>
                        <input type="text" name="sec_svi_number" value="{{ $perfil->sec_svi_number }}"></div>
                    <div class="fo-field"><label>Member of a Mutual Recognition Program?</label>
                        <select name="mutual_recognition_member"><option value="">—</option>
                            <option value="1" {{ $perfil->mutual_recognition_member===true?'selected':'' }}>Yes</option>
                            <option value="0" {{ $perfil->mutual_recognition_member===false?'selected':'' }}>No</option></select></div>
                    <div class="fo-field"><label>If yes, which one?</label>
                        <input type="text" name="mutual_recognition_which" value="{{ $perfil->mutual_recognition_which }}"></div>
                </div>

                <div class="fo-section-title">Section II — Security profile (Yes / No)</div>
                @php
                    $preguntas = [
                        'physical_security' => 'Physical Security: buildings resist unlawful entry and protect against outside intrusion',
                        'fencing' => 'Fencing',
                        'gates_gatehouses' => 'Gates and Gate Houses',
                        'parking_restrictions' => 'Parking restrictions near cargo areas',
                        'locking_devices' => 'Locking devices and key controls',
                        'lighting' => 'Lighting adequate to deter',
                        'alarm_video' => 'Alarm systems & video surveillance cameras in use',
                        'it_security' => 'Information Technology security (password access, training)',
                        'access_controls' => 'Access controls to shipping, loading dock and cargo areas',
                        'visitor_photo_id' => 'Visitors and vendors present photo identification',
                        'challenge_unauthorized' => 'Policy to challenge and remove unauthorized persons',
                        'container_security' => 'Container security: controls to properly seal containers',
                        'container_inspection' => 'Container inspection prior to loading (7-point)',
                        'container_seals' => 'Written procedures to control and affix seals',
                        'seal_discrepancy_procedure' => 'Procedure for reporting seal/container discrepancies',
                        'seal_control' => 'Control over seals so they are not misused',
                        'container_storage' => 'Containers stored in a controlled area',
                        'procedural_security' => 'Procedural security for incoming/outgoing goods',
                        'documentation_processing' => 'Documentation processing controls (accuracy, tracking)',
                        'personnel_security' => 'Personnel security (screening, background checks)',
                        'education_training' => 'Education and training awareness program',
                        'threat_awareness' => 'Threat awareness program',
                        'documentation_processing2' => 'Shipment documentation complete, accurate and protected',
                        'manifest_procedures' => 'Manifest procedures: cargo integrity and accurate reporting',
                    ];
                @endphp
                @foreach($preguntas as $campo => $texto)
                    <div class="fo-yn">
                        <div class="q">{{ $texto }}</div>
                        <label><input type="radio" name="{{ $campo }}" value="1" {{ $perfil->$campo===true?'checked':'' }}> Yes</label>
                        <label><input type="radio" name="{{ $campo }}" value="0" {{ $perfil->$campo===false?'checked':'' }}> No</label>
                    </div>
                @endforeach

                <div class="fo-section-title">Section III — Completed by company official</div>
                <div class="fo-row">
                    <div class="fo-field"><label>Print Name</label>
                        <input type="text" name="signed_print_name" value="{{ $perfil->signed_print_name }}"></div>
                    <div class="fo-field"><label>Title</label>
                        <input type="text" name="signed_title" value="{{ $perfil->signed_title }}"></div>
                    <div class="fo-field"><label>Company Name</label>
                        <input type="text" name="signed_company_name" value="{{ $perfil->signed_company_name }}"></div>
                    <div class="fo-field"><label>Date</label>
                        <input type="date" name="signed_date" value="{{ $perfil->signed_date ? $perfil->signed_date->format('Y-m-d') : '' }}"></div>
                </div>

                <div class="fo-actions">
                    <button type="button" class="fo-btn fo-btn-ghost" onclick="foGo(1)">← Back</button>
                    <button type="submit" class="fo-btn fo-btn-primary">Save &amp; continue →</button>
                </div>
            </form>
        </div>

        {{-- ───────────── STEP 3: DOCUMENTS ───────────── --}}
        <div class="fo-panel" id="fo-panel-3">
            <div class="fo-section-title">Required documents</div>
            <p class="fo-hint" style="margin-bottom:16px;">Upload each document as PDF, JPG or PNG (max 10 MB). Our team will review them.</p>
            @foreach($docsRequeridos as $tipo => $label)
                @php $doc = $docsEstado[$tipo] ?? null; @endphp
                <div class="fo-doc">
                    <div class="fo-doc-info">{{ $label }}
                        @if($doc)
                            <small>Uploaded · <a href="{{ route('proveedores.documento.archivo', $doc) }}" target="_blank" style="color:#c4b5fd;">view file</a></small>
                        @else
                            <small>Not uploaded yet</small>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        @if($doc)
                            @php $cls = $doc->estatus==='aprobado'?'appr':($doc->estatus==='rechazado'?'rej':'pend'); @endphp
                            <span class="fo-badge {{ $cls }}">{{ ucfirst($doc->estatus) }}</span>
                        @endif
                        <form method="POST" action="{{ route('proveedores.foreign-onboarding.document') }}" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
                            @csrf
                            <input type="hidden" name="tipo_documento" value="{{ $tipo }}">
                            <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required style="color:#fff;font-size:12px;max-width:200px;">
                            <button type="submit" class="fo-btn fo-btn-primary" style="padding:8px 14px;font-size:12px;">Upload</button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="fo-actions">
                <button type="button" class="fo-btn fo-btn-ghost" onclick="foGo(2)">← Back</button>
                <a href="{{ route('proveedores.foreign-portal') }}" class="fo-btn fo-btn-ghost" style="text-decoration:none;">Go to portal</a>
            </div>
        </div>
    </div>
</div>

<script>
    function foGo(step) {
        for (var i = 1; i <= 3; i++) {
            var panel = document.getElementById('fo-panel-' + i);
            var pill = document.querySelector('.fo-step-pill[data-step="' + i + '"]');
            if (panel) panel.classList.toggle('active', i === step);
            if (pill) pill.classList.toggle('active', i === step);
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    // Mostrar/ocultar SVI# vs "plan to sign up" según C-TPAT certified.
    function foToggleCtpat() {
        var sel = document.getElementById('fo_ctpat');
        var svi = document.getElementById('fo_svi_wrap');
        var plan = document.getElementById('fo_plan_wrap');
        if (!sel) return;
        if (svi) svi.classList.toggle('show', sel.value === '1');
        if (plan) plan.classList.toggle('show', sel.value === '0');
    }
    document.addEventListener('DOMContentLoaded', function () {
        var ctpat = document.getElementById('fo_ctpat');
        if (ctpat) { ctpat.addEventListener('change', foToggleCtpat); foToggleCtpat(); }
        // Paso inicial: lee ?step= de la URL, por defecto 1.
        var params = new URLSearchParams(window.location.search);
        var step = parseInt(params.get('step') || '1', 10);
        foGo(step >= 1 && step <= 3 ? step : 1);
    });
</script>
</body>
</html>
