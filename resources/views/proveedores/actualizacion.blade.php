<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Proveedor — Salcom Industries</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#EDE7F6;
            --purple-mid:  #9C6DD0;
            --gray-text:   #4A4A6A;
            --gray-soft:   #F7F6FB;
            --border:      #D8CFE8;
            --white:       #FFFFFF;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--gray-soft);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        nav {
            background: var(--white);
            padding: 14px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--purple);
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .nav-logo span {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 3px;
            color: var(--purple-mid);
            text-transform: uppercase;
            margin-top: -4px;
        }
        .nav-right { display: flex; align-items: center; gap: 24px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 500; }
        .nav-user span { color: var(--purple); font-weight: 600; }
        .btn-logout {
            font-size: 13px;
            color: var(--gray-text);
            padding: 6px 14px;
            border: 0.5px solid var(--border);
            border-radius: 8px;
            background: none;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
        }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        /* ── HERO BAND ── */
        .hero-band {
            background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 60%, var(--purple-mid) 100%);
            padding: 48px 48px 36px;
            position: relative;
            overflow: hidden;
        }
        .hero-band::before {
            content: '';
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -180px; right: -80px;
        }
        .hero-band::after {
            content: '';
            position: absolute;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            bottom: -120px; left: 60px;
        }
        .hero-band h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: var(--white);
            font-weight: 600;
            position: relative; z-index: 1;
        }
        .hero-band p {
            color: rgba(255,255,255,0.75);
            font-size: 14px;
            margin-top: 6px;
            position: relative; z-index: 1;
            font-weight: 300;
        }

        /* ── MAIN LAYOUT ── */
        .main {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 520px 1fr;
            gap: 0;
            padding: 48px 24px 64px;
            position: relative;
        }

        .deco-left {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            padding-right: 32px;
            padding-top: 20px;
        }

        /* ── CARD ── */
        .card {
            background: var(--white);
            border-radius: 20px;
            padding: 40px 40px 48px;
            box-shadow: 0 8px 40px rgba(107,63,160,0.10);
            border: 1px solid rgba(107,63,160,0.08);
            animation: fadeUp .5s ease both;
        }
        @keyframes fadeUp {
            from { opacity:0; transform: translateY(20px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .card-header .icon-wrap {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: var(--purple-light);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
        }
        .card-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--purple-dark);
            font-weight: 600;
        }
        .card-header p { font-size: 13px; color: #888; margin-top: 4px; }

        /* ── FORM ── */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .field { display: flex; flex-direction: column; margin-bottom: 18px; }
        .field label {
            font-size: 12px; font-weight: 600;
            color: var(--gray-text);
            margin-bottom: 6px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .field label .req { color: var(--purple-mid); margin-left: 2px; }
        .field input[type="text"],
        .field input[type="tel"],
        .field input[type="email"],
        .field input[type="password"] {
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            color: var(--gray-text);
            background: var(--white);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .field input::placeholder { color: #BDB8CC; }
        .field input:focus {
            border-color: var(--purple-mid);
            box-shadow: 0 0 0 3px rgba(156,109,208,0.12);
        }

        /* ── FILE UPLOAD ── */
        .file-upload-box {
            border: 1.5px dashed var(--border);
            border-radius: 10px;
            padding: 16px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative;
        }
        .file-upload-box:hover {
            border-color: var(--purple-mid);
            background: var(--purple-light);
        }
        .file-upload-box input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .file-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: var(--purple-light);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .file-info { flex: 1; }
        .file-info .file-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-text);
        }
        .file-info .file-hint {
            font-size: 11px;
            color: #AAA;
            margin-top: 2px;
        }
        .file-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            background: var(--purple-light);
            color: var(--purple);
            flex-shrink: 0;
        }

        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 4px 0 20px;
            color: #CCC; font-size: 12px;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1;
            border-top: 1px solid var(--border);
        }

        .btn-submit {
            width: 100%; padding: 14px;
            background: var(--purple);
            color: var(--white);
            border: none; border-radius: 12px;
            font-family: 'Nunito', sans-serif;
            font-size: 15px; font-weight: 600;
            cursor: pointer; letter-spacing: 0.3px;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(107,63,160,0.25);
        }
        .btn-submit:hover {
            background: var(--purple-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(107,63,160,0.35);
        }
        .btn-submit:active { transform: translateY(0); }

        .back-link { text-align: center; margin-top: 18px; font-size: 13px; color: #999; }
        .back-link a { color: var(--purple); text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }

        .alert-errors {
            background: #FEE2E2;
            border-left: 3px solid #C0392B;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px; color: #991B1B;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #D1FAE5;
            border-left: 3px solid #059669;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px; color: #065F46;
            margin-bottom: 20px;
        }
        .error-msg { font-size: 12px; color: #C0392B; margin-top: 4px; }

        /* ── FOOTER ── */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 24px 48px;
            display: flex; align-items: center; justify-content: space-between;
        }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple); }

        @media (max-width: 700px) {
            .main { grid-template-columns: 1fr; padding: 24px 16px 48px; }
            .deco-left, .deco-right { display: none; }
            .card { padding: 28px 22px 36px; }
            .form-row { grid-template-columns: 1fr; }
            nav { padding: 12px 20px; }
            footer { padding: 18px 20px; }
            .hero-band { padding: 32px 20px 28px; }
        }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<nav>
    <div class="nav-logo">
        Wiese
        <span>Salcom Industries</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Hola, <span>{{ session('proveedor_nombre', 'Proveedor') }}</span></span>
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

{{-- HERO --}}
<div class="hero-band">
    <h1>Actualizar Información</h1>
    <p>Mantén tus datos al día para seguir operando con Salcom Industries</p>
</div>

{{-- MAIN --}}
<div class="main">

    <div class="deco-left">
        <svg width="180" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" style="opacity:0.18">
            <path fill="#6B3FA0" d="M44.7,-62.3C56.6,-53.4,63.7,-38.2,68.1,-22.3C72.5,-6.4,74.2,10.2,69.1,24.6C64,39,52.2,51.2,38.4,59.2C24.6,67.2,8.8,71,-6.5,69.2C-21.8,67.4,-36.6,60,-47.3,49.1C-58,38.2,-64.6,23.8,-66.3,8.8C-68,-6.2,-64.8,-21.8,-57,-34.1C-49.2,-46.4,-36.8,-55.4,-23.4,-63.1C-10,-70.8,4.4,-77.2,18.3,-75.1C32.2,-73,45.6,-62.4,44.7,-62.3Z" transform="translate(100 100)" />
        </svg>
    </div>

    {{-- CARD --}}
    <div class="card">

        <div class="card-header">
            <div class="icon-wrap">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none"
                     stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
            <h2>Actualizar Datos</h2>
            <p>Modifica la información de tu cuenta</p>
        </div>

        @if ($errors->any())
            <div class="alert-errors">
                <ul style="padding-left:16px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('mensaje'))
            <div class="alert-success">{{ session('mensaje') }}</div>
        @endif

        <form method="POST" action="{{ route('proveedores.actualizacion.guardar') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Nombre --}}
            <div class="field">
                <label>Nombre completo <span class="req">*</span></label>
                <input type="text" name="nombre"
                       placeholder="Tu nombre completo"
                       value="{{ old('nombre') }}"
                       required>
                @error('nombre') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Persona física o moral --}}
            <div class="field">
                <label>Persona física o moral <span class="req">*</span></label>
                <input type="text" name="tipo_persona"
                        placeholder="Ej: Persona Física o Persona Moral"
                        value="{{ old('tipo_persona') }}"
                        required>
                @error('tipo_persona') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Teléfono y Correo --}}
            <div class="form-row">
                <div class="field" style="margin-bottom:0">
                    <label>Teléfono <span class="req">*</span></label>
                    <input type="tel" name="telefono"
                           placeholder="33 1234 5678"
                           value="{{ old('telefono') }}"
                           required>
                    @error('telefono') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
                <div class="field" style="margin-bottom:0">
                    <label>Correo electrónico <span class="req">*</span></label>
                    <input type="email" name="correo"
                           placeholder="tu@correo.com"
                           value="{{ old('correo') }}"
                           required>
                    @error('correo') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <div style="margin-bottom:18px"></div>

            <div class="divider">Cambiar contraseña (opcional)</div>

            <div class="form-row">
                <div class="field" style="margin-bottom:0">
                    <label>Nueva contraseña</label>
                    <input type="password" name="password"
                           placeholder="Dejar vacío para no cambiar">
                    @error('password') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
                <div class="field" style="margin-bottom:0">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="password_confirmation"
                           placeholder="Repite la nueva contraseña">
                </div>
            </div>

            <div style="margin-bottom:18px"></div>

            {{-- DOCUMENTOS FISCALES --}}
            <div class="divider">Documentos fiscales</div>

            {{-- CIF --}}
            <div class="field">
                <label>CIF — Constancia de Situación Fiscal <span class="req">*</span></label>
                <div class="file-upload-box">
                    <input type="file" name="cif" accept=".pdf">
                    <div class="file-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <div class="file-info">
                        <div class="file-label">Subir CIF</div>
                        <div class="file-hint">Formato PDF · Máx. 5MB · Mes actual</div>
                    </div>
                    <span class="file-badge">PDF</span>
                </div>
                @error('cif') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Opinión Positiva --}}
            <div class="field">
                <label>Opinión de Cumplimiento del SAT <span class="req">*</span></label>
                <div class="file-upload-box">
                    <input type="file" name="opinion_positiva" accept=".pdf">
                    <div class="file-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <polyline points="9 15 11 17 15 13"/>
                        </svg>
                    </div>
                    <div class="file-info">
                        <div class="file-label">Subir Opinión Positiva</div>
                        <div class="file-hint">Formato PDF · Máx. 5MB · Mes actual</div>
                    </div>
                    <span class="file-badge">PDF</span>
                </div>
                @error('opinion_positiva') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Acta Constitutiva --}}
            <div class="field">
                <label>Acta Constitutiva</label>
                <div class="file-upload-box">
                    <input type="file" name="acta_constitutiva" accept=".pdf">
                    <div class="file-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="8" y1="13" x2="16" y2="13"/>
                            <line x1="8" y1="17" x2="16" y2="17"/>
                        </svg>
                    </div>
                    <div class="file-info">
                        <div class="file-label">Subir Acta Constitutiva</div>
                        <div class="file-hint">Formato PDF · Máx. 5MB · Solo Persona Moral</div>
                    </div>
                    <span class="file-badge">PDF</span>
                </div>
                @error('acta_constitutiva') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div style="margin-bottom:8px"></div>

            <button type="submit" class="btn-submit">Guardar cambios</button>

        </form>

        <p class="back-link">
            <a href="{{ route('proveedores.portal') }}">← Volver al portal</a>
        </p>

    </div>

    <div style="padding-left:32px; padding-top:60px; opacity:0.12">
        <svg width="140" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
            <path fill="#9C6DD0" d="M39.5,-51.5C50.8,-42.6,59.2,-29.5,63.1,-14.8C67,0,66.3,16.4,59.7,29.7C53.1,43,40.5,53.2,26.4,59.3C12.3,65.4,-3.3,67.4,-17.8,63.1C-32.3,58.8,-45.7,48.2,-54.3,34.5C-62.9,20.8,-66.7,4,-63.9,-11.5C-61.1,-27,-51.7,-41.2,-39.4,-50.2C-27.1,-59.2,-11.9,-63,2,-65.4C15.9,-67.8,28.2,-60.4,39.5,-51.5Z" transform="translate(100 100)" />
        </svg>
    </div>

</div>

{{-- FOOTER --}}
<footer>
    <div class="footer-logo">Wiese <span style="font-family:'Nunito';font-size:11px;color:#AAA;font-weight:600;letter-spacing:2px;text-transform:uppercase">Salcom Industries</span></div>
    <p>© {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

</body>
</html>