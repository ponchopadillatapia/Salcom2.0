@extends('layouts.proveedor-extranjero')
@section('title', 'Home')

@push('styles')
<style>
    .fp-wrap { max-width: 1000px; margin: 0 auto; }
    .fp-hello { font-size: 22px; font-weight: 700; color: var(--gray-text); margin-bottom: 4px; }
    .fp-sub { font-size: 14px; color: var(--gray-muted); margin-bottom: 22px; }
    .fp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
    .fp-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 20px; box-shadow: var(--shadow-sm); }
    .fp-card h4 { font-size: 12px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; }
    .fp-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .fp-badge.ok { background: #dcfce7; color: #166534; } .fp-badge.pend { background: #fef3c7; color: #92400e; }
    .fp-card p { font-size: 13px; color: var(--gray-muted); margin-top: 8px; line-height: 1.4; }
    .fp-link { display: inline-block; margin-top: 12px; color: var(--purple); font-size: 13px; font-weight: 600; text-decoration: none; }
    .fp-banner { background: #fff7ed; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
    .fp-banner strong { color: #92400e; } .fp-banner p { font-size: 13px; color: #92400e; margin-top: 4px; }
    .fp-banner.active { background: #ecfdf5; border-color: #86efac; } .fp-banner.active strong, .fp-banner.active p { color: #166534; }
    @media(max-width:768px){ .fp-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="fp-wrap">
    <div class="fp-hello">Welcome, {{ $proveedor->nombre }}</div>
    <div class="fp-sub">Foreign supplier portal — import / C-TPAT</div>

    @if($proveedor->activo)
        <div class="fp-banner active">
            <strong>Your account is active.</strong>
            <p>You can review your onboarding, invoices and purchase orders from the menu.</p>
        </div>
    @else
        <div class="fp-banner">
            <strong>Your account is under review.</strong>
            <p>Complete your onboarding (business profile, security questionnaire and the required documents). Our team will activate your account afterwards.</p>
        </div>
    @endif

    <div class="fp-grid">
        <div class="fp-card">
            <h4>Business Profile</h4>
            <span class="fp-badge {{ ($perfil && $perfil->profile_completed) ? 'ok' : 'pend' }}">{{ ($perfil && $perfil->profile_completed) ? 'Completed' : 'Pending' }}</span>
            <p>General company information for your import profile.</p>
            <a href="{{ route('proveedores.foreign-onboarding') }}" class="fp-link">Go to onboarding →</a>
        </div>
        <div class="fp-card">
            <h4>Security Questionnaire</h4>
            <span class="fp-badge {{ ($perfil && $perfil->security_completed) ? 'ok' : 'pend' }}">{{ ($perfil && $perfil->security_completed) ? 'Completed' : 'Pending' }}</span>
            <p>C-TPAT confidential security questionnaire.</p>
            <a href="{{ route('proveedores.foreign-onboarding', ['step' => 2]) }}" class="fp-link">Fill questionnaire →</a>
        </div>
        <div class="fp-card">
            <h4>Documents</h4>
            <span class="fp-badge {{ $docsOk ? 'ok' : 'pend' }}">{{ $docsSubidos }}/{{ $docsTotal }} uploaded</span>
            <p>Address proof, Tax ID proof, contact info and bank letter.</p>
            <a href="{{ route('proveedores.foreign-onboarding', ['step' => 3]) }}" class="fp-link">Upload documents →</a>
        </div>
    </div>
</div>
@endsection
