@php
    if (session('admin_id')) {
        $portalNombre = 'Panel Administrativo';
        $portalUrl = '/admin/ia';
    } elseif (session('cliente_id')) {
        $portalNombre = 'Portal de Clientes';
        $portalUrl = '/portal-cliente';
    } elseif (session('proveedor_id')) {
        $portalNombre = 'Portal de Proveedores';
        $portalUrl = '/portal-proveedor';
    } else {
        $portalNombre = 'Industrias Salcom';
        $portalUrl = '/';
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del servidor — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --purple: #6B3FA0; --purple-dark: #4A2070; --purple-light: #EDE7F6;
            --purple-mid: #9C6DD0; --gray-text: #4A4A6A; --gray-soft: #F7F6FB;
            --border: #D8CFE8; --white: #FFFFFF; --red: #DC2626; --red-bg: #FEE2E2;
        }
        body { font-family: 'Nunito', sans-serif; background: var(--gray-soft); min-height: 100vh; display: flex; flex-direction: column; }
        nav { background: var(--white); padding: 0 32px; height: 57px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple); font-weight: 600; }
        .nav-logo span { display: block; font-family: 'Nunito', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 3px; color: var(--purple-mid); text-transform: uppercase; margin-top: -4px; }
        .error-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 24px; }
        .error-card { background: var(--white); border-radius: 20px; padding: 60px 48px; text-align: center; max-width: 520px; width: 100%; box-shadow: 0 8px 40px rgba(107,63,160,0.10); border: 1px solid rgba(107,63,160,0.08); }
        .error-code { font-family: 'Playfair Display', serif; font-size: 96px; font-weight: 600; color: var(--red); line-height: 1; }
        .error-title { font-family: 'Playfair Display', serif; font-size: 24px; color: var(--purple-dark); font-weight: 600; margin-top: 12px; }
        .error-desc { font-size: 14px; color: var(--gray-text); margin-top: 12px; line-height: 1.6; }
        .btn-back { display: inline-block; margin-top: 28px; padding: 12px 32px; background: var(--purple); color: var(--white); border: none; border-radius: 12px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; text-decoration: none; transition: background .2s, transform .15s; box-shadow: 0 4px 16px rgba(107,63,160,0.25); }
        .btn-back:hover { background: var(--purple-dark); transform: translateY(-1px); }
        .error-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--red-bg); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        footer { background: var(--white); border-top: 1px solid var(--border); padding: 18px 32px; display: flex; align-items: center; justify-content: space-between; }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple); }
        @media (max-width: 600px) { .error-card { padding: 40px 24px; } .error-code { font-size: 64px; } }
    </style>
</head>
<body>
    <nav>
        <div class="nav-logo">Industrias Salcom<span>{{ $portalNombre }}</span></div>
    </nav>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="error-code">500</div>
            <div class="error-title">Error del servidor</div>
            <div class="error-desc">Algo salió mal de nuestro lado. Estamos trabajando para resolverlo. Intenta de nuevo en unos minutos.</div>
            <a href="{{ $portalUrl }}" class="btn-back">Volver al portal</a>
        </div>
    </div>
    <footer>
        <div class="footer-logo">Industrias Salcom</div>
        <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
