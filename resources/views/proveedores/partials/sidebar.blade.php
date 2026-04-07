<div class="sidebar" id="appSidebar">
    <div class="sb-toggle" onclick="sbToggle()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </div>
    <nav class="sb-nav">
        <div class="sb-label">Principal</div>

        <a href="{{ route('proveedores.portal') }}" data-tip="Inicio"
           class="sb-link {{ request()->routeIs('proveedores.portal') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <span class="sb-text">Inicio</span>
        </a>

        <a href="{{ route('proveedores.dashboard') }}" data-tip="Dashboard"
           class="sb-link {{ request()->routeIs('proveedores.dashboard') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <span class="sb-text">Dashboard</span>
        </a>

        <div class="sb-hr"></div>
        <div class="sb-label">Operaciones</div>

        <a href="{{ route('proveedores.oc') }}" data-tip="Consultar OC"
           class="sb-link {{ request()->routeIs('proveedores.oc') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <span class="sb-text">Consultar OC</span>
        </a>

        <a href="{{ route('proveedores.dashboard') }}" data-tip="Facturas" class="sb-link">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <span class="sb-text">Facturas</span>
        </a>

        <a href="{{ route('proveedores.dashboard') }}" data-tip="Pagos" class="sb-link">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <span class="sb-text">Pagos</span>
        </a>

        <div class="sb-hr"></div>
        <div class="sb-label">Mi empresa</div>

        <a href="#" data-tip="Onboarding" class="sb-link">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <span class="sb-text">Onboarding</span>
        </a>

        <a href="#" data-tip="Business" class="sb-link">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
            <span class="sb-text">Business</span>
        </a>
    </nav>
</div>