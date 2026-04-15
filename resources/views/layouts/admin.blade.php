<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; margin: 0; padding: 0; }

        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#F3EEFA;
            --purple-mid:  #9C6DD0;
            --purple-subtle:#F8F5FC;
            --gray-text:   #1a1a2e;
            --gray-muted:  #6b7280;
            --gray-soft:   #f9fafb;
            --border:      #e5e7eb;
            --border-light:#f3f4f6;
            --white:       #FFFFFF;
            --green:       #059669;
            --green-bg:    #ECFDF5;
            --amber:       #D97706;
            --amber-bg:    #FFFBEB;
            --blue:        #2563EB;
            --blue-bg:     #EFF6FF;
            --red:         #DC2626;
            --red-bg:      #FEF2F2;
            --shadow-sm:   0 1px 2px rgba(74,32,112,0.04);
            --shadow-md:   0 2px 8px rgba(74,32,112,0.06), 0 1px 2px rgba(74,32,112,0.04);
            --shadow-lg:   0 4px 16px rgba(74,32,112,0.08), 0 2px 4px rgba(74,32,112,0.04);
            --radius:      10px;
            --radius-lg:   14px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-soft);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--gray-text);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        nav.top-nav {
            background: var(--white);
            padding: 0 28px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 200;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
        }
        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            color: var(--purple);
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.1;
        }
        .nav-logo span {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2.5px;
            color: var(--purple-mid);
            text-transform: uppercase;
            margin-top: 1px;
        }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 600; }
        .btn-back {
            font-size: 12px;
            color: var(--gray-muted);
            padding: 6px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--white);
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s ease;
        }
        .btn-back:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        .hero-band {
            background: var(--white);
            padding: 20px 28px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .hero-band h1 { font-size: 20px; color: var(--gray-text); font-weight: 700; letter-spacing: -0.3px; }
        .hero-band p { color: var(--gray-muted); font-size: 13px; margin-top: 2px; }

        .main-content {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
            padding: 28px 32px 64px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        footer p { font-size: 11px; color: var(--gray-muted); }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 15px; color: var(--purple); font-weight: 600; }

        @media (max-width: 768px) {
            .main-content { padding: 20px 16px 48px; }
            nav.top-nav { padding: 0 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>

<nav class="top-nav">
    <div class="nav-logo">
        Industrias Salcom
        <span>Panel Administrativo</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Administrador</span>
        <a href="/" class="btn-back">← Volver al inicio</a>
    </div>
</nav>

@yield('hero')

<div class="main-content">
    @yield('content')
</div>

<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

@stack('scripts')
</body>
</html>
