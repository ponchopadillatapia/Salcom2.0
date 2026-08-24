/* Estilos compartidos del menú (proveedor / admin / cliente) */
.nav-right { flex-shrink: 0; }
.wrapper { min-width: 0; }
.sb-nav { min-height: 0; }
.nav-menu-btn { width: 40px; height: 40px; touch-action: manipulation; }
.notif-dropdown,
.notif-drop {
    width: min(320px, calc(100vw - 24px));
    max-width: calc(100vw - 24px);
    max-height: min(70vh, 480px);
    overflow: auto;
}
.notif-drop.show { display: block; }

@media (max-width: 1024px) {
    .nav-menu-btn { display: inline-flex; }
    .nav-logo { gap: 8px; flex: 1; min-width: 0; }
    .nav-logo span.nav-title { max-width: min(240px, 34vw); }
    .sb-toggle { display: none !important; }
    .nav-pedidos-quick { display: flex; }
    .sb-submenu-items { padding-left: 12px; }

    .sidebar {
        position: fixed;
        top: 56px;
        left: 0;
        bottom: 0;
        z-index: 300;
        width: min(300px, 88vw);
        min-width: min(300px, 88vw);
        height: calc(100dvh - 56px);
        transform: translateX(-100%);
        box-shadow: none;
        overflow: hidden;
    }
    .sidebar .sb-nav {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        padding-bottom: env(safe-area-inset-bottom, 0px);
    }
    .sidebar.open {
        transform: translateX(0);
        box-shadow: 8px 0 32px rgba(0,0,0,.14);
    }
    .sidebar.collapsed,
    .sidebar.collapsed:hover {
        width: min(300px, 88vw);
        min-width: min(300px, 88vw);
        box-shadow: none;
    }
    .sidebar.collapsed .sb-text,
    .sidebar.collapsed:hover .sb-text { display: inline; }
    .sidebar.collapsed .sb-section,
    .sidebar.collapsed:hover .sb-section { display: block; }
    .sidebar.collapsed .sb-client-meta,
    .sidebar.collapsed:hover .sb-client-meta { display: flex; }
    .sidebar.collapsed .sb-badge,
    .sidebar.collapsed:hover .sb-badge { display: inline-flex; }
    .sidebar.collapsed .sb-link,
    .sidebar.collapsed:hover .sb-link {
        justify-content: flex-start;
        padding: 8px 16px;
        margin: 1px 8px;
    }
    .sidebar.collapsed .sb-client,
    .sidebar.collapsed:hover .sb-client {
        justify-content: flex-start;
        padding: 14px 12px 10px 16px;
        margin-bottom: 6px;
        gap: 12px;
    }
    .sidebar.collapsed .sb-submenu.open .sb-submenu-items,
    .sidebar.collapsed:hover .sb-submenu.open .sb-submenu-items {
        display: flex !important;
        flex-direction: column;
    }
    body.sb-open { overflow: hidden; }
    .main-content { padding: 20px 16px 48px; }
    nav.top-nav {
        padding: 0 max(12px, env(safe-area-inset-right)) 0 max(12px, env(safe-area-inset-left));
    }
    .hero-band { padding: 18px 16px 8px; }
    .hero-band h1 { font-size: 20px; }
    footer {
        flex-wrap: wrap;
        gap: 6px 16px;
        padding: 14px 16px;
        justify-content: center;
        text-align: center;
    }
}

@media (hover: none) {
    .nav-notif-wrap:hover .notif-drop { display: none; }
    .nav-notif-wrap.open .notif-drop { display: block; }
}

@media (max-width: 640px) {
    .nav-logo span.nav-title { display: none; }
    .nav-right { gap: 6px; }
    .btn-logout { padding: 6px 12px; }
    .nav-user { max-width: 140px; }
}

@media (max-width: 400px) {
    .btn-logout { font-size: 11px; padding: 6px 10px; }
    .nav-logo img { height: 22px !important; }
}
