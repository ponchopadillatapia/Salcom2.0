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

        <a href="{{ route('proveedores.ia') }}" data-tip="Módulo IA"
           class="sb-link {{ request()->routeIs('proveedores.ia') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg>
            </div>
            <span class="sb-text">Módulo IA</span>
        </a>

        <a href="{{ route('proveedores.forecast') }}" data-tip="Forecast"
           class="sb-link {{ request()->routeIs('proveedores.forecast') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <span class="sb-text">Forecast</span>
        </a>

        <a href="{{ route('proveedores.otif') }}" data-tip="OTIF"
           class="sb-link {{ request()->routeIs('proveedores.otif') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <span class="sb-text">OTIF</span>
        </a>

        <a href="{{ route('proveedores.business') }}" data-tip="Inventario"
           class="sb-link {{ request()->routeIs('proveedores.inventario') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <span class="sb-text">Inventario</span>
        </a>

        <a href="{{ route('proveedores.perfil') }}" data-tip="Fiscal"
           class="sb-link {{ request()->routeIs('proveedores.fiscal') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <span class="sb-text">Fiscal</span>
        </a>

        <a href="{{ route('proveedores.oc') }}" data-tip="Consultar OC"
           class="sb-link {{ request()->routeIs('proveedores.oc') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <span class="sb-text">Consultar OC</span>
        </a>

        <a href="{{ route('proveedores.alta-producto') }}" data-tip="Alta de producto"
           class="sb-link {{ request()->routeIs('proveedores.alta-producto') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <span class="sb-text">Alta de producto</span>
        </a>

        <a href="{{ route('proveedores.dashboard') }}" data-tip="Facturas" class="sb-link">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <span class="sb-text">Facturas</span>
        </a>

        <a href="{{ route('proveedores.payment-history') }}" data-tip="Pagos"
           class="sb-link {{ request()->routeIs('proveedores.payment-history') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <span class="sb-text">Pagos</span>
        </a>

        <div class="sb-hr"></div>
        <div class="sb-label">Mi empresa</div>

        <a href="{{ route('proveedores.onboarding') }}" data-tip="Onboarding"
           class="sb-link {{ request()->routeIs('proveedores.onboarding') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <span class="sb-text">Onboarding</span>
        </a>

        <a href="{{ route('proveedores.payment-history') }}" data-tip="Historial de pagos"
           class="sb-link">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <span class="sb-text">Historial de pagos</span>
        </a>

        <a href="{{ route('proveedores.encuesta') }}" data-tip="Encuesta"
           class="sb-link {{ request()->routeIs('proveedores.encuesta') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <span class="sb-text">Encuesta</span>
        </a>

        <a href="{{ route('proveedores.business') }}" data-tip="Business"
           class="sb-link {{ request()->routeIs('proveedores.business') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
            <span class="sb-text">Business</span>
        </a>

        <div class="sb-hr"></div>
        <div class="sb-label">Cuenta</div>

        <a href="{{ route('proveedores.perfil') }}" data-tip="Mi Perfil"
           class="sb-link {{ request()->routeIs('proveedores.perfil') ? 'active' : '' }}">
            <div class="sb-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <span class="sb-text">Mi Perfil</span>
        </a>
    </nav>
</div>