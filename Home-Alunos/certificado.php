<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Aluno') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';
$aluno_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT u.nome, u.email, a.estado, a.data_nascimento,
               a.data_validacao, u2.nome AS validado_por_nome,
               c.nome AS curso_nome, c.sigla AS curso_sigla
        FROM utilizadores u
        JOIN alunos a ON u.id = a.id
        LEFT JOIN cursos c ON a.curso_id = c.id
        LEFT JOIN utilizadores u2 ON a.validado_por = u2.id
        WHERE u.id = :id
    ");
    $stmt->execute(['id' => $aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$aluno || $aluno['estado'] !== 'Aprovada') {
        die("Certificado não disponível. A ficha de aluno deve estar aprovada.");
    }
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$nome       = htmlspecialchars($aluno['nome']);
$email      = htmlspecialchars($aluno['email']);
$curso      = htmlspecialchars($aluno['curso_nome'] ?? 'Curso não atribuído');
$sigla      = htmlspecialchars($aluno['curso_sigla'] ?? '');
$validado   = htmlspecialchars($aluno['validado_por_nome'] ?? 'Serviços Académicos');
$data_val   = !empty($aluno['data_validacao']) ? date('d/m/Y', strtotime($aluno['data_validacao'])) : date('d/m/Y');
$hoje       = date('d \d\e F \d\e Y');
$ano_letivo = date('Y') . '/' . (date('Y') + 1);
$num_cert   = 'IPCA-' . date('Y') . '-' . str_pad($aluno_id, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificado de Matrícula — <?= $nome ?></title>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --green:      #006633;
    --green-dark: #004d26;
    --gold:       #B8963E;
    --gold-light: #D4AF6A;
    --cream:      #FDFAF4;
    --ink:        #1A1A1A;
    --muted:      #5A5A5A;
    --border:     #C8B87A;
}

html, body {
    font-family: 'Sora', sans-serif;
    background: #E8E4DC;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 40px 20px;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── Screen toolbar ── */
.toolbar {
    width: 210mm;
    max-width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.toolbar a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--muted);
    text-decoration: none;
    transition: color .15s;
}
.toolbar a:hover { color: var(--ink); }

.btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: var(--green);
    color: white;
    border: none;
    border-radius: 8px;
    font-family: 'Sora', sans-serif;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
    transition: background .2s, transform .15s;
}
.btn-print:hover { background: var(--green-dark); transform: translateY(-1px); }

/* ── Certificate Sheet ── */
.cert-sheet {
    width: 210mm;
    min-height: 297mm;
    max-width: 100%;
    background: var(--cream);
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 80px rgba(0,0,0,0.18);
}

/* ── Background decorative elements ── */
.bg-circle-tl {
    position: absolute;
    top: -80px; left: -80px;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0,102,51,0.05) 0%, transparent 70%);
    pointer-events: none;
}
.bg-circle-br {
    position: absolute;
    bottom: -100px; right: -100px;
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(184,150,62,0.07) 0%, transparent 70%);
    pointer-events: none;
}

/* Fine diagonal lines pattern */
.bg-lines {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image: repeating-linear-gradient(
        -45deg,
        rgba(0,102,51,0.012) 0px,
        rgba(0,102,51,0.012) 1px,
        transparent 1px,
        transparent 24px
    );
}

/* ── Outer border frame ── */
.cert-frame-outer {
    position: absolute;
    inset: 16px;
    border: 1px solid rgba(184,150,62,0.35);
    pointer-events: none;
}
.cert-frame-inner {
    position: absolute;
    inset: 20px;
    border: 0.5px solid rgba(184,150,62,0.2);
    pointer-events: none;
}

/* ── Corner ornaments ── */
.corner {
    position: absolute;
    width: 48px; height: 48px;
    pointer-events: none;
}
.corner svg { width: 100%; height: 100%; }
.corner-tl { top: 10px; left: 10px; }
.corner-tr { top: 10px; right: 10px; transform: scaleX(-1); }
.corner-bl { bottom: 10px; left: 10px; transform: scaleY(-1); }
.corner-br { bottom: 10px; right: 10px; transform: scale(-1,-1); }

/* ── Header band ── */
.cert-header {
    position: relative;
    z-index: 2;
    background: var(--green);
    padding: 28px 52px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.logo-circle {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.25);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.logo-circle img {
    width: 34px;
    filter: brightness(0) invert(1);
}

.header-text-block {}
.header-inst {
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.7);
    line-height: 1;
    margin-bottom: 5px;
}
.header-title {
    font-family: 'EB Garamond', serif;
    font-size: 22px;
    font-weight: 400;
    color: #ffffff;
    letter-spacing: 0.04em;
    line-height: 1;
}

.header-num {
    text-align: right;
    flex-shrink: 0;
}
.num-label { font-size: 9.5px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-bottom: 4px; }
.num-value { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.85); letter-spacing: 0.05em; font-family: monospace; }

/* ── Gold divider ── */
.gold-divider {
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--gold), var(--gold-light), var(--gold), transparent);
    position: relative;
    z-index: 2;
}

/* ── Body ── */
.cert-body {
    position: relative;
    z-index: 2;
    padding: 42px 64px 36px;
}

/* ── Intro line ── */
.intro-line {
    font-family: 'EB Garamond', serif;
    font-size: 14px;
    font-style: italic;
    color: var(--muted);
    letter-spacing: 0.04em;
    text-align: center;
    margin-bottom: 28px;
}

/* ── Central name block ── */
.name-block {
    text-align: center;
    padding: 28px 0 24px;
    border-top: 1px solid rgba(184,150,62,0.25);
    border-bottom: 1px solid rgba(184,150,62,0.25);
    margin-bottom: 28px;
}
.certify-label {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 12px;
}
.student-name {
    font-family: 'EB Garamond', serif;
    font-size: 36px;
    font-weight: 500;
    color: var(--ink);
    letter-spacing: 0.01em;
    line-height: 1.1;
    margin-bottom: 8px;
}
.student-email {
    font-size: 12.5px;
    color: var(--muted);
    letter-spacing: 0.02em;
}

/* ── Declaration text ── */
.declaration {
    font-family: 'EB Garamond', serif;
    font-size: 16.5px;
    line-height: 1.85;
    color: var(--ink);
    text-align: center;
    margin-bottom: 32px;
}
.declaration strong {
    font-weight: 600;
    color: var(--green-dark);
}
.declaration em {
    font-style: italic;
    color: var(--muted);
}

/* ── Info row ── */
.info-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0;
    border: 1px solid rgba(184,150,62,0.25);
    border-radius: 4px;
    margin-bottom: 36px;
    overflow: hidden;
}
.info-cell {
    padding: 14px 20px;
    border-right: 1px solid rgba(184,150,62,0.2);
    text-align: center;
}
.info-cell:last-child { border-right: none; }
.info-cell-label {
    font-size: 9.5px;
    font-weight: 500;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 5px;
}
.info-cell-value {
    font-family: 'EB Garamond', serif;
    font-size: 14.5px;
    font-weight: 500;
    color: var(--ink);
    line-height: 1.2;
}

/* ── Validity strip ── */
.validity-strip {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center;
    margin-bottom: 40px;
    font-size: 11.5px;
    color: var(--muted);
    letter-spacing: 0.04em;
}
.validity-dot {
    width: 5px; height: 5px;
    border-radius: 50%;
    background: var(--gold);
    flex-shrink: 0;
}

/* ── Signatures ── */
.sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 28px;
}
.sig-block { text-align: center; }
.sig-line {
    height: 1px;
    background: rgba(0,0,0,0.18);
    margin-bottom: 8px;
    position: relative;
}
.sig-line::before, .sig-line::after {
    content: '';
    position: absolute;
    bottom: 0;
    width: 5px; height: 5px;
    border-radius: 50%;
    background: rgba(184,150,62,0.5);
}
.sig-line::before { left: 0; }
.sig-line::after  { right: 0; }

.sig-name  { font-family: 'EB Garamond', serif; font-size: 14px; font-style: italic; color: var(--ink); margin-bottom: 3px; }
.sig-role  { font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); }

/* ── Footer ── */
.cert-footer {
    position: relative;
    z-index: 2;
    background: var(--green);
    padding: 14px 52px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.footer-left {
    font-size: 10px;
    color: rgba(255,255,255,0.55);
    letter-spacing: 0.05em;
    line-height: 1.6;
}
.footer-right {
    display: flex;
    align-items: center;
    gap: 20px;
}
.footer-contact {
    font-size: 10px;
    color: rgba(255,255,255,0.5);
    letter-spacing: 0.03em;
    text-align: right;
    line-height: 1.6;
}

/* ── PRINT STYLES ── */
@media print {
    @page {
        size: A4;
        margin: 0;
    }

    html, body {
        background: white;
        padding: 0;
        min-height: auto;
    }

    .toolbar { display: none !important; }

    .cert-sheet {
        width: 210mm;
        min-height: 297mm;
        max-width: none;
        box-shadow: none;
        page-break-after: avoid;
    }
}
</style>
</head>
<body>

<!-- Screen toolbar -->
<div class="toolbar">
    <a href="home.php">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 3L5 8l5 5"/>
        </svg>
        Voltar ao Portal
    </a>
    <button class="btn-print" onclick="window.print()">
        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 5V2h8v3M3 5h10a1 1 0 011 1v5H2V6a1 1 0 011-1zM4 11v3h8v-3"/>
        </svg>
        Guardar / Imprimir PDF
    </button>
</div>

<!-- Certificate Sheet -->
<div class="cert-sheet">

    <!-- Background decorative -->
    <div class="bg-circle-tl"></div>
    <div class="bg-circle-br"></div>
    <div class="bg-lines"></div>
    <div class="cert-frame-outer"></div>
    <div class="cert-frame-inner"></div>

    <!-- Corner ornaments -->
    <div class="corner corner-tl">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 4 L44 4" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L4 44" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L18 4 Q22 4 22 8 L22 22" stroke="#B8963E" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <circle cx="4" cy="4" r="2.5" fill="#B8963E" opacity="0.7"/>
            <circle cx="4" cy="4" r="1" fill="#D4AF6A"/>
        </svg>
    </div>
    <div class="corner corner-tr">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 4 L44 4" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L4 44" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L18 4 Q22 4 22 8 L22 22" stroke="#B8963E" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <circle cx="4" cy="4" r="2.5" fill="#B8963E" opacity="0.7"/>
            <circle cx="4" cy="4" r="1" fill="#D4AF6A"/>
        </svg>
    </div>
    <div class="corner corner-bl">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 4 L44 4" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L4 44" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L18 4 Q22 4 22 8 L22 22" stroke="#B8963E" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <circle cx="4" cy="4" r="2.5" fill="#B8963E" opacity="0.7"/>
            <circle cx="4" cy="4" r="1" fill="#D4AF6A"/>
        </svg>
    </div>
    <div class="corner corner-br">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 4 L44 4" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L4 44" stroke="#B8963E" stroke-width="0.8" opacity="0.6"/>
            <path d="M4 4 L18 4 Q22 4 22 8 L22 22" stroke="#B8963E" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <circle cx="4" cy="4" r="2.5" fill="#B8963E" opacity="0.7"/>
            <circle cx="4" cy="4" r="1" fill="#D4AF6A"/>
        </svg>
    </div>

    <!-- Header -->
    <div class="cert-header">
        <div class="header-left">
            <div class="logo-circle">
                <img src="../img/logo-ipca.png"
                     onerror="this.style.display='none'"
                     alt="IPCA">
            </div>
            <div class="header-text-block">
                <div class="header-inst">Instituto Politécnico do Cávado e do Ave</div>
                <div class="header-title">Certificado de Matrícula</div>
            </div>
        </div>
        <div class="header-num">
            <div class="num-label">Nº de Documento</div>
            <div class="num-value"><?= $num_cert ?></div>
        </div>
    </div>

    <!-- Gold divider -->
    <div class="gold-divider"></div>

    <!-- Body -->
    <div class="cert-body">

        <p class="intro-line">Os Serviços Académicos do IPCA declaram para os devidos efeitos que:</p>

        <!-- Name block -->
        <div class="name-block">
            <div class="certify-label">Aluno(a) Identificado(a)</div>
            <div class="student-name"><?= $nome ?></div>
            <div class="student-email"><?= $email ?></div>
        </div>

        <!-- Declaration -->
        <p class="declaration">
            se encontra regularmente matriculado(a) e inscrito(a) no<br>
            <strong>Instituto Politécnico do Cávado e do Ave</strong>,<br>
            no curso de <strong><?= $curso ?></strong><?= $sigla ? " (<em>$sigla</em>)" : '' ?>,<br>
            durante o ano letivo de <strong><?= $ano_letivo ?></strong>,<br>
            com a ficha académica devidamente <em>validada e aprovada</em>.
        </p>

        <!-- Info row -->
        <div class="info-row">
            <div class="info-cell">
                <div class="info-cell-label">Ano Letivo</div>
                <div class="info-cell-value"><?= $ano_letivo ?></div>
            </div>
            <div class="info-cell">
                <div class="info-cell-label">Data de Validação</div>
                <div class="info-cell-value"><?= $data_val ?></div>
            </div>
            <div class="info-cell">
                <div class="info-cell-label">Data de Emissão</div>
                <div class="info-cell-value"><?= $hoje ?></div>
            </div>
        </div>

        <!-- Validity notice -->
        <div class="validity-strip">
            <div class="validity-dot"></div>
            <span>Este documento é válido para todos os efeitos legais e institucionais</span>
            <div class="validity-dot"></div>
        </div>

        <!-- Signatures -->
        <div class="sig-row">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-name"><?= $validado ?></div>
                <div class="sig-role">Serviços Académicos</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-name">Direção do IPCA</div>
                <div class="sig-role">Aprovação Institucional</div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="cert-footer">
        <div class="footer-left">
            IPCA — Instituto Politécnico do Cávado e do Ave<br>
            Vila Frescaínha S. Martinho, 4750-810 Barcelos
        </div>
        <div class="footer-right">
            <div class="footer-contact">
                (+351) 253 802 190<br>
                geral@ipca.pt · www.ipca.pt
            </div>
        </div>
    </div>

</div>

<script>
// Auto-trigger print dialog if ?print=1 in URL
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.onload = () => window.print();
}
</script>
</body>
</html>