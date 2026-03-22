<?php
session_start();

if(isset($_SESSION['tipo'])){
    if($_SESSION['tipo'] == 'admin'){
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: aluno/home.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Académico — IPCA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:      #006633;
            --green-mid:  #00813f;
            --green-light:#00a84f;
            --gold:       #c8a84b;
            --gold-light: #f0d080;
            --cream:      #faf8f3;
            --dark:       #0e1a12;
            --ink:        #1c2b1e;
            --muted:      #6b7c6e;
            --card-bg:    #ffffff;
            --border:     rgba(0,102,51,0.12);
        }

        html, body { height: 100%; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--cream);
            color: var(--ink);
            overflow-x: hidden;
        }

        /* ── NOISE TEXTURE OVERLAY ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
            background-size: 200px;
            opacity: 0.6;
        }

        /* ── LAYOUT ── */
        .page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
            z-index: 1;
        }

        /* ── LEFT PANEL ── */
        .left {
            background: var(--green);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 3.5rem;
            overflow: hidden;
            min-height: 100vh;
        }

        /* Geometric background shapes */
        .left::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(200,168,75,0.18) 0%, transparent 70%);
        }
        .left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,168,79,0.25) 0%, transparent 70%);
        }

        /* Diagonal stripe accent */
        .left .stripe {
            position: absolute;
            bottom: 0; right: 0;
            width: 45%;
            height: 100%;
            background: repeating-linear-gradient(
                -55deg,
                rgba(255,255,255,0.015) 0px,
                rgba(255,255,255,0.015) 1px,
                transparent 1px,
                transparent 18px
            );
            pointer-events: none;
        }

        /* ── LOGO AREA ── */
        .logo-block {
            position: relative;
            z-index: 2;
        }
        .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 0.55rem 1.1rem;
            backdrop-filter: blur(8px);
            margin-bottom: 3rem;
        }
        .logo-badge img {
            height: 28px;
            filter: brightness(0) invert(1);
        }
        .logo-badge span {
            color: rgba(255,255,255,0.9);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        /* ── HERO TEXT ── */
        .hero-text {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .eyebrow {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .eyebrow::before {
            content: '';
            display: inline-block;
            width: 28px; height: 2px;
            background: var(--gold);
            border-radius: 2px;
        }
        .hero-heading {
            font-family: 'DM Serif Display', serif;
            color: #ffffff;
            font-size: clamp(2.6rem, 4vw, 3.8rem);
            line-height: 1.08;
            letter-spacing: -0.01em;
        }
        .hero-heading em {
            font-style: italic;
            color: var(--gold-light);
        }
        .hero-sub {
            margin-top: 1.6rem;
            color: rgba(255,255,255,0.6);
            font-size: 0.95rem;
            font-weight: 300;
            line-height: 1.7;
            max-width: 380px;
        }

        /* ── STATS ROW ── */
        .stats-row {
            display: flex;
            gap: 2.5rem;
            margin-top: 3.5rem;
            position: relative;
            z-index: 2;
        }
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        .stat-num {
            font-family: 'DM Serif Display', serif;
            font-size: 2.1rem;
            color: #fff;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.5);
            font-weight: 500;
            letter-spacing: 0.04em;
            margin-top: 0.25rem;
        }
        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,0.15);
            margin: 0;
        }

        /* ── CAMPUS TAGS ── */
        .campus-strip {
            position: relative;
            z-index: 2;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .campus-tag {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: rgba(255,255,255,0.55);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 4px;
            padding: 0.25rem 0.6rem;
            text-transform: uppercase;
            transition: all 0.2s;
        }
        .campus-tag:hover { color: var(--gold-light); border-color: var(--gold); }

        /* ── RIGHT PANEL ── */
        .right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            background: var(--cream);
            position: relative;
        }

        /* Subtle radial gradient background */
        .right::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 60%; height: 50%;
            background: radial-gradient(ellipse at top right, rgba(0,102,51,0.06) 0%, transparent 65%);
            pointer-events: none;
        }

        /* ── AUTH CARD ── */
        .auth-card {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        .card-top {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .ipca-mark {
            width: 72px; height: 72px;
            border-radius: 20px;
            background: var(--green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(0,102,51,0.25);
            overflow: hidden;
        }
        .ipca-mark img {
            width: 54px;
            filter: brightness(0) invert(1);
        }

        .card-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.9rem;
            color: var(--dark);
            letter-spacing: -0.02em;
            line-height: 1.15;
        }
        .card-title em {
            font-style: italic;
            color: var(--green);
        }
        .card-desc {
            margin-top: 0.6rem;
            font-size: 0.88rem;
            color: var(--muted);
            font-weight: 400;
            line-height: 1.6;
        }

        /* ── DIVIDER ── */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 2rem 0;
        }
        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .section-divider span {
            font-size: 0.72rem;
            color: var(--muted);
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* ── BUTTONS ── */
        .btn-primary-ipca {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            padding: 1rem 1.5rem;
            background: var(--green);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-family: 'Sora', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.22s, transform 0.18s, box-shadow 0.22s;
            box-shadow: 0 4px 20px rgba(0,102,51,0.22);
            position: relative;
            overflow: hidden;
        }
        .btn-primary-ipca::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.07) 0%, transparent 60%);
        }
        .btn-primary-ipca:hover {
            background: var(--green-mid);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,102,51,0.3);
            color: #fff;
        }
        .btn-primary-ipca:active { transform: translateY(0); }

        .btn-secondary-ipca {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            padding: 1rem 1.5rem;
            background: transparent;
            color: var(--ink);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            font-family: 'Sora', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: border-color 0.22s, background 0.22s, transform 0.18s;
            margin-top: 1rem;
        }
        .btn-secondary-ipca:hover {
            border-color: var(--green);
            background: rgba(0,102,51,0.04);
            color: var(--green);
            transform: translateY(-2px);
        }
        .btn-secondary-ipca:active { transform: translateY(0); }

        /* Icon inside buttons */
        .btn-icon {
            width: 20px; height: 20px;
            opacity: 0.8;
        }

        /* ── FOOTER NOTE ── */
        .footer-note {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            text-align: center;
        }
        .footer-note p {
            font-size: 0.76rem;
            color: var(--muted);
            line-height: 1.6;
        }
        .footer-note a {
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
        }
        .footer-note a:hover { text-decoration: underline; }

        /* ── FEATURE PILLS ── */
        .feature-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 1.2rem;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--muted);
            background: rgba(0,102,51,0.06);
            border-radius: 50px;
            padding: 0.3rem 0.75rem;
            border: 1px solid rgba(0,102,51,0.1);
        }
        .pill svg { flex-shrink: 0; }

        /* ── ENTER ANIMATIONS ── */
        .fade-up {
            opacity: 0;
            transform: translateY(24px);
            animation: fadeUp 0.65s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.22s; }
        .delay-3 { animation-delay: 0.34s; }
        .delay-4 { animation-delay: 0.46s; }
        .delay-5 { animation-delay: 0.58s; }
        .delay-6 { animation-delay: 0.70s; }
        .delay-7 { animation-delay: 0.82s; }

        /* Left panel slides in from left */
        .left > * {
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.7s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        .left .logo-block  { animation-delay: 0.05s; }
        .left .hero-text   { animation-delay: 0.15s; }
        .left .stats-row   { animation-delay: 0.28s; }
        .left .campus-strip{ animation-delay: 0.4s; }
        @keyframes slideIn {
            to { opacity: 1; transform: translateX(0); }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .page { grid-template-columns: 1fr; }
            .left {
                min-height: auto;
                padding: 2.5rem 2rem;
            }
            .stats-row { gap: 1.5rem; }
            .right { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- ═══ LEFT ═══ -->
    <div class="left">
        <div class="stripe"></div>

        <div class="logo-block">
            <div class="logo-badge">
                <img src="../img/logo-ipca.png"
                     onerror="this.replaceWith(document.createTextNode('IPCA'))"
                     alt="IPCA">
                <span>Portal Académico</span>
            </div>
        </div>

        <div class="hero-text">
            <p class="eyebrow">Instituto Politécnico do Cávado e do Ave</p>
            <h1 class="hero-heading">
                O teu futuro<br>começa <em>aqui.</em>
            </h1>
            <p class="hero-sub">
                Acede aos teus horários, notas, inscrições e muito mais — tudo num só lugar, pensado para ti.
            </p>
        </div>

        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-num">6+</span>
                <span class="stat-label">Polos</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-num">40+</span>
                <span class="stat-label">Cursos</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-num">7k+</span>
                <span class="stat-label">Estudantes</span>
            </div>
        </div>

        <div class="campus-strip">
            <span class="campus-tag">Barcelos</span>
            <span class="campus-tag">Braga</span>
            <span class="campus-tag">Famalicão</span>
            <span class="campus-tag">Guimarães</span>
            <span class="campus-tag">Esposende</span>
            <span class="campus-tag">Vila Verde</span>
        </div>
    </div>

    <!-- ═══ RIGHT ═══ -->
    <div class="right">
        <div class="auth-card">

            <div class="card-top fade-up delay-1">
                <div class="ipca-mark">
                    <img src="img/logo-ipca.png"
                         onerror="this.style.display='none'"
                         alt="IPCA">
                </div>
                <h2 class="card-title">Bem&#8209;vindo<br>de <em>volta.</em></h2>
                <p class="card-desc">Entra na tua conta ou cria uma nova para começares.</p>
            </div>

            <div class="fade-up delay-2">
                <a href="auth/login.php" class="btn-primary-ipca">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Iniciar Sessão
                </a>
            </div>

            <div class="fade-up delay-3">
                <a href="auth/registo.php" class="btn-secondary-ipca">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                    Criar Conta
                </a>
            </div>

            <div class="section-divider fade-up delay-4">
                <span>O que podes fazer</span>
            </div>

            <div class="feature-pills fade-up delay-5">
                <span class="pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Notas
                </span>
                <span class="pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Horários
                </span>
                <span class="pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Inscrições
                </span>
                <span class="pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Mensagens
                </span>
                <span class="pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Presenças
                </span>
                <span class="pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.65 3.4a2 2 0 0 1 1.99-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 8.91A16 16 0 0 0 14 14.82l.84-.84a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21.92 16z"/></svg>
                    Suporte
                </span>
            </div>

            <div class="footer-note fade-up delay-6">
                <p>
                    Problemas de acesso? Contacta os <a href="mailto:geral@ipca.pt">Serviços Académicos</a>.<br>
                    <span style="font-size:0.7rem; color:#aaa;">© IPCA — Instituto Politécnico do Cávado e do Ave</span>
                </p>
            </div>

        </div>
    </div>

</div>
</body>
</html>