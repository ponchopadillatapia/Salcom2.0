<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Área') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--gray-soft);
            min-height: 100vh;
            color: var(--gray-text);
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        .area-nav {
            background: var(--white);
            border-bottom: 1px solid var(--border-light);
            padding: 0 28px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .area-nav-left { display: flex; align-items: center; gap: 16px; }
        .area-nav-brand { font-size: 16px; font-weight: 700; color: var(--purple); }
        .area-nav-sep { width: 1px; height: 24px; background: var(--border-light); }
        .area-nav-title { font-size: 14px; font-weight: 600; color: var(--gray-text); }
        .area-nav-right { display: flex; align-items: center; gap: 14px; }
        .area-nav-user { font-size: 13px; color: var(--gray-muted); font-weight: 500; }
        .area-nav-user strong { color: var(--gray-text); }
        .btn-logout {
            font-size: 12px; color: var(--gray-muted); padding: 7px 16px;
            border: 1px solid var(--border-light); border-radius: 8px;
            background: var(--white); cursor: pointer; font-family: inherit; font-weight: 500;
            transition: all .15s;
        }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        .area-hero {
            background: linear-gradient(135deg, var(--white) 0%, var(--purple-subtle) 100%);
            padding: 28px 32px;
            border-bottom: 1px solid var(--border-light);
        }
        .area-hero h1 { font-size: 22px; font-weight: 700; color: var(--gray-text); }
        .area-hero p { font-size: 14px; color: var(--gray-muted); margin-top: 4px; font-weight: 500; }

        .area-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 32px 64px;
        }

        .area-footer {
            background: var(--white);
            border-top: 1px solid var(--border-light);
            padding: 16px 28px;
            text-align: center;
            font-size: 11px;
            color: var(--gray-muted);
        }

        @media(max-width:768px) {
            .area-nav { padding: 0 16px; }
            .area-content { padding: 20px 16px 48px; }
        }
    </style>
    @stack('styles')
</head>
<body>

<nav class="area-nav">
    <div class="area-nav-left">
        <span class="area-nav-brand">Salcom</span>
        <div class="area-nav-sep"></div>
        <span class="area-nav-title">@yield('area-title', 'Área')</span>
    </div>
    <div class="area-nav-right">
        <span class="area-nav-user"><strong>{{ session('admin_nombre', 'Usuario') }}</strong></span>
        <form method="POST" action="/logout-admin" style="margin:0;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

<div class="area-hero">
    @yield('hero')
</div>

<div class="area-content">
    @if(session('admin_rol') === 'gerente')
    <div style="margin-bottom:16px">
        <a href="{{ route('admin.dashboard') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none;padding:8px 16px;border:1.5px solid var(--border-light);border-radius:8px;background:var(--white);transition:all .15s">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Regresar al panel
        </a>
    </div>
    @endif
    @if(session('mensaje'))
        <div style="background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green);border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;font-weight:500">{{ session('mensaje') }}</div>
    @endif
    @if(session('error'))
        <div style="background:var(--red-bg);border:1px solid #fecaca;color:var(--red);border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;font-weight:500">{{ session('error') }}</div>
    @endif
    @yield('content')
</div>

<footer class="area-footer">
    &copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.
</footer>

@stack('scripts')
</body>
</html>
