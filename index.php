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
    <title>Portal Académico | IPCA</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --ipca-green: #006633;
            --ipca-light-green: #008040;
            --ipca-pale: #e8f5ee;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f0f4f1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-image: radial-gradient(circle at 20% 20%, rgba(0,102,51,0.07) 0%, transparent 50%),
                              radial-gradient(circle at 80% 80%, rgba(0,128,64,0.07) 0%, transparent 50%);
        }
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .main-container {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,102,51,0.12), 0 4px 16px rgba(0,0,0,0.06);
            max-width: 980px;
            width: 100%;
            overflow: hidden;
        }
        .left-panel {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(160deg, #f7fbf8 0%, #eaf4ee 100%);
        }
        .brand-logo {
            display: flex;
            align-items: center;
            font-family: 'Nunito', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--ipca-green);
            margin-bottom: 2rem;
            letter-spacing: 3px;
            align-self: flex-start;
        }
        .brand-logo i { margin-right: 10px; font-size: 1.8rem; }
        .quote-text {
            font-family: 'Nunito', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: #555;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        /* ─── CHARACTER ─── */
        .character-wrapper {
            position: relative;
            width: 220px;
            height: 270px;
        }
        #student-svg { width: 100%; height: 100%; overflow: visible; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-10px); }
        }
        .character-body-group { animation: float 3s ease-in-out infinite; transform-origin: center bottom; }

        @keyframes wave {
            0%   { transform: rotate(0deg); }
            20%  { transform: rotate(-35deg); }
            45%  { transform: rotate(10deg); }
            65%  { transform: rotate(-28deg); }
            85%  { transform: rotate(5deg); }
            100% { transform: rotate(0deg); }
        }
        .arm-right { transform-origin: 4px 2px; }
        .arm-right.waving { animation: wave 0.85s ease-in-out forwards; }

        .speech-bubble {
            position: absolute;
            top: 0px;
            right: -90px;
            background: white;
            border: 2.5px solid var(--ipca-green);
            border-radius: 14px 14px 14px 4px;
            padding: 8px 14px;
            font-family: 'Nunito', sans-serif;
            font-size: 0.82rem;
            font-weight: 800;
            color: var(--ipca-green);
            white-space: nowrap;
            opacity: 0;
            transform: scale(0.8) translateY(5px);
            transition: opacity 0.25s, transform 0.25s;
            pointer-events: none;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,102,51,0.15);
        }
        .speech-bubble.visible { opacity: 1; transform: scale(1) translateY(0); }

        /* ─── RIGHT PANEL ─── */
        .right-panel {
            background: #fff;
            padding: 50px 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-left: 1px solid rgba(0,102,51,0.08);
        }
        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,102,51,0.10);
            width: 100%;
            max-width: 340px;
            overflow: hidden;
        }
        .card-header-curved {
            background: linear-gradient(135deg, var(--ipca-green) 0%, var(--ipca-light-green) 100%);
            height: 100px;
            border-radius: 20px 20px 0 0;
            position: relative;
        }
        .card-header-curved::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 30px;
            background: white;
            border-radius: 50% 50% 0 0 / 30px 30px 0 0;
        }
        .avatar-circle {
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            width: 76px; height: 76px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0,102,51,0.2);
            padding: 10px;
            z-index: 2;
        }
        .avatar-circle img { max-width: 100%; border-radius: 50%; }
        .card-body-auth { padding: 55px 30px 30px; text-align: center; }
        .card-body-auth h5 {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .btn-auth {
            padding: 13px 20px;
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 14px;
            font-size: 0.95rem;
            display: block;
            text-decoration: none;
            text-align: center;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--ipca-green), var(--ipca-light-green));
            color: white; border: none;
            box-shadow: 0 4px 14px rgba(0,102,51,0.3);
        }
        .btn-login:hover { color: white; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,102,51,0.35); }
        .btn-register {
            background: transparent;
            border: 2.5px solid var(--ipca-green);
            color: var(--ipca-green);
        }
        .btn-register:hover { background: var(--ipca-pale); color: var(--ipca-green); transform: translateY(-2px); }

        /* ─── FOOTER ─── */
        footer { background-color: #1a1a1a; color: white; padding: 30px 0 10px; font-size: 0.82rem; }
        .footer-logo { max-height: 48px; }
        .location-item { margin-bottom: 15px; line-height: 1.5; }
        .location-item i { color: #66bb88; margin-bottom: 4px; display: block; }
        .location-item strong { font-size: 0.78rem; }
        .social-icons a { color: white; font-size: 1.4rem; margin-left: 14px; text-decoration: none; transition: color 0.2s; }
        .social-icons a:hover { color: #66bb88; }
        .bottom-bar { border-top: 1px solid rgba(255,255,255,0.12); margin-top: 24px; padding-top: 18px; }
        @media (max-width: 768px) {
            .left-panel { padding: 30px 20px; }
            .brand-logo { align-self: center; }
            .speech-bubble { right: -70px; }
        }
    </style>
</head>
<body>

<main class="hero-section">
    <div class="main-container">
        <div class="row g-0">

            <div class="col-md-6 left-panel">
                <div class="brand-logo">
                    <i class="fas fa-layer-group"></i> IPCA
                </div>
                <p class="quote-text">Instituto Politécnico do Cávado e do Ave</p>

                <div class="character-wrapper">
                    <div class="speech-bubble" id="bubble">Olá! 👋</div>

                    <svg id="student-svg" viewBox="0 0 200 265" xmlns="http://www.w3.org/2000/svg">
                        <!-- Shadow -->
                        <ellipse cx="100" cy="252" rx="40" ry="7" fill="rgba(0,0,0,0.09)"/>

                        <g class="character-body-group" id="bodyGroup">

                            <!-- LEGS -->
                            <rect x="80" y="196" width="16" height="40" rx="8" fill="#2c2c54"/>
                            <ellipse cx="88" cy="236" rx="13" ry="7" fill="#1a1a2e"/>
                            <rect x="104" y="196" width="16" height="40" rx="8" fill="#2c2c54"/>
                            <ellipse cx="112" cy="236" rx="13" ry="7" fill="#1a1a2e"/>

                            <!-- BODY -->
                            <rect x="70" y="128" width="60" height="72" rx="14" fill="#006633"/>
                            <!-- Collar -->
                            <polygon points="100,137 91,148 100,144 109,148" fill="#f5c892"/>
                            <!-- IPCA badge -->
                            <rect x="82" y="158" width="36" height="18" rx="4" fill="rgba(255,255,255,0.15)"/>
                            <text x="100" y="171" text-anchor="middle" font-family="Nunito,sans-serif" font-size="10" font-weight="900" fill="white" letter-spacing="1">IPCA</text>

                            <!-- LEFT ARM (static) -->
                            <g transform="translate(70,142)">
                                <rect x="-20" y="0" width="15" height="38" rx="7.5" fill="#006633"/>
                                <ellipse cx="-12" cy="43" rx="9" ry="9" fill="#f5c892"/>
                                <line x1="-8"  y1="49" x2="-8"  y2="53" stroke="#e0a870" stroke-width="2" stroke-linecap="round"/>
                                <line x1="-12" y1="50" x2="-12" y2="54" stroke="#e0a870" stroke-width="2" stroke-linecap="round"/>
                                <line x1="-16" y1="49" x2="-16" y2="53" stroke="#e0a870" stroke-width="2" stroke-linecap="round"/>
                            </g>

                            <!-- RIGHT ARM (waves) -->
                            <g id="arm-right" class="arm-right" transform="translate(130,142)">
                                <rect x="5" y="0" width="15" height="38" rx="7.5" fill="#006633"/>
                                <ellipse cx="12" cy="43" rx="9" ry="9" fill="#f5c892"/>
                                <line x1="8"  y1="49" x2="8"  y2="53" stroke="#e0a870" stroke-width="2" stroke-linecap="round"/>
                                <line x1="12" y1="50" x2="12" y2="54" stroke="#e0a870" stroke-width="2" stroke-linecap="round"/>
                                <line x1="16" y1="49" x2="16" y2="53" stroke="#e0a870" stroke-width="2" stroke-linecap="round"/>
                            </g>

                            <!-- NECK -->
                            <rect x="93" y="117" width="14" height="15" rx="6" fill="#f5c892"/>

                            <!-- HEAD GROUP -->
                            <g id="headGroup">
                                <!-- Head -->
                                <ellipse cx="100" cy="96" rx="36" ry="37" fill="#f5c892"/>
                                <!-- Ears -->
                                <ellipse cx="64" cy="98" rx="7" ry="10" fill="#f5c892"/>
                                <ellipse cx="64" cy="98" rx="4" ry="6"  fill="#e8a878"/>
                                <ellipse cx="136" cy="98" rx="7" ry="10" fill="#f5c892"/>
                                <ellipse cx="136" cy="98" rx="4" ry="6"  fill="#e8a878"/>

                                <!-- Hair -->
                                <ellipse cx="100" cy="64" rx="36" ry="16" fill="#2c1810"/>
                                <rect x="64" y="63" width="72" height="16" fill="#2c1810"/>
                                <path d="M64,70 Q59,80 62,92" stroke="#2c1810" stroke-width="8" fill="none" stroke-linecap="round"/>
                                <path d="M136,70 Q141,80 138,92" stroke="#2c1810" stroke-width="8" fill="none" stroke-linecap="round"/>

                                <!-- Graduation cap -->
                                <rect x="70" y="55" width="60" height="11" rx="3" fill="#1a1a2e"/>
                                <polygon points="100,42 132,57 100,66 68,57" fill="#1a1a2e"/>
                                <line x1="132" y1="57" x2="138" y2="76" stroke="#f0c040" stroke-width="2.5" stroke-linecap="round"/>
                                <circle cx="138" cy="78" r="4.5" fill="#f0c040"/>

                                <!-- EYEBROWS -->
                                <g id="eyebrows">
                                    <path class="eyebrow-left"  d="M81,86 Q88,82 95,85" stroke="#2c1810" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                                    <path class="eyebrow-right" d="M105,85 Q112,82 119,86" stroke="#2c1810" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                                </g>

                                <!-- EYES -->
                                <g id="eyesGroup">
                                    <ellipse cx="89" cy="98" rx="9.5" ry="9.5" fill="white"/>
                                    <ellipse id="iris-left" cx="89" cy="99" rx="5.5" ry="6" fill="#3a2010"/>
                                    <ellipse cx="90.5" cy="98" rx="2.5" ry="3" fill="black"/>
                                    <ellipse cx="91.5" cy="96.5" rx="1.5" ry="1.5" fill="white"/>

                                    <ellipse cx="111" cy="98" rx="9.5" ry="9.5" fill="white"/>
                                    <ellipse id="iris-right" cx="111" cy="99" rx="5.5" ry="6" fill="#3a2010"/>
                                    <ellipse cx="112.5" cy="98" rx="2.5" ry="3" fill="black"/>
                                    <ellipse cx="113.5" cy="96.5" rx="1.5" ry="1.5" fill="white"/>
                                </g>

                                <!-- NOSE -->
                                <ellipse cx="100" cy="109" rx="4" ry="2.5" fill="#e0966a"/>

                                <!-- MOUTH -->
                                <path id="mouth" d="M91,117 Q100,124 109,117" stroke="#c0785a" stroke-width="2.5" fill="none" stroke-linecap="round"/>

                                <!-- Cheeks -->
                                <ellipse cx="77" cy="108" rx="9" ry="5.5" fill="rgba(255,100,100,0.13)"/>
                                <ellipse cx="123" cy="108" rx="9" ry="5.5" fill="rgba(255,100,100,0.13)"/>
                            </g>

                        </g>
                    </svg>
                </div>
            </div>

            <!-- RIGHT -->
            <div class="col-md-6 right-panel">
                <div class="auth-card">
                    <div class="card-header-curved">
                        <div class="avatar-circle">
                            <img src="img/logo-ipca.png" alt="IPCA"
                                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                        </div>
                    </div>
                    <div class="card-body-auth">
                        <h5>Bem-vindo ao Portal</h5>
                        <a href="auth/login.php"   id="btn-login"    class="btn btn-auth btn-login">Iniciar Sessão</a>
                        <a href="auth/registo.php" id="btn-register" class="btn btn-auth btn-register">Criar Conta</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="row align-items-start">
            <div class="col-md-4 mb-4">
                <img src="img/logo-rodape" alt="IPCA" class="footer-logo mb-3" onerror="this.style.display='none'">
            </div>
            <div class="col-md-8 text-md-end mb-4">
                <img src="img/logo-rodape" alt="Parceiros" style="max-height:60px;" onerror="this.style.display='none'">
            </div>
        </div>
        <div class="row text-center mt-4">
            <div class="col-md-2 location-item"><i class="fas fa-map-marker-alt"></i><strong>CAMPUS DO IPCA | BARCELOS</strong><br>Vila Frescaínha S. Martinho<br>4750-810 Barcelos</div>
            <div class="col-md-2 location-item"><i class="fas fa-map-marker-alt"></i><strong>POLO DE BRAGA</strong><br>Av. Dr. Francisco Pires Gonçalves<br>4715-558 Braga</div>
            <div class="col-md-2 location-item"><i class="fas fa-map-marker-alt"></i><strong>POLO DE FAMALICÃO</strong><br>Avenida de Tibães, nº1199<br>Vale S.Cosme<br>4770-568 V. N. Famalicão</div>
            <div class="col-md-2 location-item"><i class="fas fa-map-marker-alt"></i><strong>POLO DE GUIMARÃES</strong><br>Avepark - Zona Industrial da Gandra<br>4806-909 Caldas das Taipas</div>
            <div class="col-md-2 location-item"><i class="fas fa-map-marker-alt"></i><strong>POLO DE ESPOSENDE</strong><br>Rua Sra. da Saúde<br>4740-289 Esposende</div>
            <div class="col-md-2 location-item"><i class="fas fa-map-marker-alt"></i><strong>POLO DE VILA VERDE</strong><br>Rua do Conhecimento<br>4730-575 Soutelo</div>
        </div>
        <div class="row bottom-bar align-items-center">
            <div class="col-md-6"><span>(+351) 253 802 190 | geral@ipca.pt</span></div>
            <div class="col-md-6 text-md-end">
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center mt-4"><small>Copyright © IPCA - Instituto Politécnico do Cávado e do Ave</small></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    const arm      = document.getElementById('arm-right');
    const irisL    = document.getElementById('iris-left');
    const irisR    = document.getElementById('iris-right');
    const eyebrows = document.getElementById('eyebrows');
    const mouth    = document.getElementById('mouth');
    const bubble   = document.getElementById('bubble');
    const btnLogin = document.getElementById('btn-login');
    const btnReg   = document.getElementById('btn-register');

    let idleTimer;

    function setLook(dx, dy) {
        irisL.setAttribute('cx', 89  + dx);  irisL.setAttribute('cy', 99  + dy);
        irisR.setAttribute('cx', 111 + dx);  irisR.setAttribute('cy', 99  + dy);
    }
    function resetLook() { setLook(0, 0); }

    function wave() {
        arm.classList.remove('waving');
        void arm.offsetWidth;
        arm.classList.add('waving');
    }

    function setMouth(d) { mouth.setAttribute('d', d); }
    const MOUTH_NORMAL = 'M91,117 Q100,124 109,117';
    const MOUTH_BIG    = 'M87,115 Q100,129 113,115';
    const MOUTH_GENTLE = 'M89,117 Q100,125 111,117';

    function showBubble(text) { bubble.textContent = text; bubble.classList.add('visible'); }
    function hideBubble()     { bubble.classList.remove('visible'); }

    function raiseBrows(y) {
        eyebrows.querySelectorAll('path').forEach(p => { p.style.transform = `translateY(${y}px)`; });
    }

    // ── Hover: Iniciar Sessão ──
    btnLogin.addEventListener('mouseenter', () => {
        clearInterval(idleTimer);
        setLook(5, 0);
        raiseBrows(-4);
        setMouth(MOUTH_BIG);
        wave();
        showBubble('Bem-vindo! 😊');
    });
    btnLogin.addEventListener('mouseleave', () => {
        resetLook();
        raiseBrows(0);
        setMouth(MOUTH_NORMAL);
        hideBubble();
        startIdle();
    });

    // ── Hover: Criar Conta ──
    btnReg.addEventListener('mouseenter', () => {
        clearInterval(idleTimer);
        setLook(5, 2);
        raiseBrows(-2);
        setMouth(MOUTH_GENTLE);
        wave();
        showBubble('Junta-te a nós! 🎓');
    });
    btnReg.addEventListener('mouseleave', () => {
        resetLook();
        raiseBrows(0);
        setMouth(MOUTH_NORMAL);
        hideBubble();
        startIdle();
    });

    // ── Idle: occasional look-around ──
    function startIdle() {
        idleTimer = setInterval(() => {
            if (document.querySelector('.btn-auth:hover')) return;
            const dirs = [{dx:-3,dy:-1},{dx:3,dy:0},{dx:0,dy:2},{dx:0,dy:0},{dx:-2,dy:1}];
            const d = dirs[Math.floor(Math.random() * dirs.length)];
            setLook(d.dx, d.dy);
            setTimeout(resetLook, 900);
        }, 2800);
    }

    // CSS blink
    const style = document.createElement('style');
    style.textContent = `
        @keyframes blink {
            0%,88%,100% { transform: scaleY(1); }
            93%          { transform: scaleY(0.08); }
        }
        #eyesGroup { animation: blink 4.5s ease-in-out infinite; transform-origin: center 98px; }
    `;
    document.head.appendChild(style);

    startIdle();
})();
</script>
</body>
</html>