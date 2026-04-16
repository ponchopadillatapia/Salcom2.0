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
    <title>Página no encontrada — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --purple: #6B3FA0; --purple-dark: #4A2070; --purple-light: #EDE7F6;
            --purple-mid: #9C6DD0; --gray-text: #4A4A6A; --gray-soft: #F7F6FB;
            --border: #D8CFE8; --white: #FFFFFF;
        }
        body { font-family: 'Nunito', sans-serif; background: var(--gray-soft); min-height: 100vh; display: flex; flex-direction: column; }
        nav { background: var(--white); padding: 0 32px; height: 57px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple); font-weight: 600; }
        .nav-logo span { display: block; font-family: 'Nunito', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 3px; color: var(--purple-mid); text-transform: uppercase; margin-top: -4px; }
        .error-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px 24px; }
        .error-card { background: var(--white); border-radius: 20px; padding: 60px 48px; text-align: center; max-width: 520px; width: 100%; box-shadow: 0 8px 40px rgba(107,63,160,0.10); border: 1px solid rgba(107,63,160,0.08); }
        .error-code { font-family: 'Playfair Display', serif; font-size: 96px; font-weight: 600; color: var(--purple); line-height: 1; }
        .error-title { font-family: 'Playfair Display', serif; font-size: 24px; color: var(--purple-dark); font-weight: 600; margin-top: 12px; }
        .error-desc { font-size: 14px; color: var(--gray-text); margin-top: 12px; line-height: 1.6; }
        .btn-back { display: inline-block; margin-top: 28px; padding: 12px 32px; background: var(--purple); color: var(--white); border: none; border-radius: 12px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; text-decoration: none; transition: background .2s, transform .15s; box-shadow: 0 4px 16px rgba(107,63,160,0.25); }
        .btn-back:hover { background: var(--purple-dark); transform: translateY(-1px); }
        .error-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--purple-light); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
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
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    <line x1="8" y1="11" x2="14" y2="11"/>
                </svg>
            </div>
            <div class="error-code">404</div>
            <div class="error-title">Página no encontrada</div>
            <div class="error-desc">La página que buscas no existe o fue movida. Verifica la dirección o regresa al portal.</div>
            <a href="{{ $portalUrl }}" class="btn-back">Volver al portal</a>
        </div>
    </div>
    <footer>
        <div class="footer-logo">Industrias Salcom</div>
        <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
