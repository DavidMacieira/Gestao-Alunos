<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Académico — IPCA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        /* ── Global Reset & Tokens ─────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:        #007a33;
            --green-mid:    #005a26;
            --green-pale:   #e8f5ed;
            --green-soft:   #c8e6d1;
            --white:        #ffffff;
            --off-white:    #F8F9FA;
            --gray-50:      #F4F5F6;
            --gray-100:     #EEEEEE;
            --gray-200:     #D8D8D8;
            --gray-400:     #9A9A9A;
            --gray-600:     #5A5A5A;
            --black:        #1A1A1A;
            --text-main:    #1A1A1A;
            --text-muted:   #6A6A6A;
            --text-faint:   #9A9A9A;
            --border:       1px solid #EEEEEE;
            --radius-sm:    6px;
            --radius-md:    10px;
            --radius-lg:    14px;
            --shadow-xs:    0 1px 3px rgba(0,0,0,0.04);
            --header-h:     56px;

            /* Legacy aliases — mantém compatibilidade com ficheiros antigos */
            --ipca-green:        #007a33;
            --ipca-green-light:  #00a34a;
            --ipca-green-hover:  #005f27;
            --ipca-blue:         #1A1A1A;
            --bg-light:          #F8F9FA;
            --card-bg:           #ffffff;
            --text-dark:         #1A1A1A;
            --text-muted-old:    #6A6A6A;
            --success-bg:        #e8f5ed;
            --success-text:      #005a26;
            --danger-bg:         #FEF0EF;
            --danger-text:       #9B2B20;
            --shadow-refined:    0 1px 3px rgba(0,0,0,0.04);
        }

        html { font-size: 14px; }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--off-white);
            color: var(--text-main);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        button { cursor: pointer; font-family: inherit; }
        img { max-width: 100%; display: block; }

        /* ── Top Navigation Bar ────────────────────────────────────── */
        .site-header {
            height: var(--header-h);
            background: var(--white);
            border-bottom: var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 200;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Logo mark */
        .header-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-mark {
            width: 30px;
            height: 30px;
            background: var(--black);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .logo-mark svg {
            width: 17px;
            height: 17px;
        }

        .logo-wordmark {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .logo-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--black);
            letter-spacing: 0.01em;
        }

        .logo-sub {
            font-size: 10px;
            color: var(--text-faint);
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        /* Divider pipe */
        .header-divider {
            width: 1px;
            height: 18px;
            background: var(--gray-200);
            flex-shrink: 0;
        }

        /* Section label (e.g. "Portal do Aluno") */
        .header-section-label {
            font-size: 12.5px;
            color: var(--text-faint);
            letter-spacing: 0.01em;
        }

        /* Nav links (shown on wider screens) */
        .header-nav {
            display: flex;
            align-items: center;
            gap: 2px;
            margin-left: 8px;
        }

        .header-nav-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text-muted);
            transition: all 0.15s;
            text-decoration: none;
            white-space: nowrap;
        }

        .header-nav-item:hover {
            background: var(--gray-50);
            color: var(--black);
        }

        .header-nav-item.active {
            background: var(--green-pale);
            color: var(--green-mid);
            font-weight: 500;
        }

        .header-nav-item svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            opacity: 0.7;
        }

        .header-nav-item.active svg,
        .header-nav-item:hover svg { opacity: 1; }

        /* Right side */
        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px 5px 6px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background 0.15s;
            border: var(--border);
            background: transparent;
            font-family: 'DM Sans', sans-serif;
            color: var(--text-main);
        }

        .header-user:hover { background: var(--gray-50); }

        .user-avatar-sm {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--black);
            color: white;
            font-size: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            letter-spacing: 0.03em;
        }

        .user-avatar-sm img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }

        .user-meta-name {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--black);
        }

        .user-meta-role {
            font-size: 10.5px;
            color: var(--text-faint);
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 12px;
            border-radius: var(--radius-sm);
            font-size: 12.5px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            border: var(--border);
            background: var(--white);
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.15s;
            cursor: pointer;
        }

        .btn-logout:hover {
            background: var(--gray-50);
            color: var(--black);
            border-color: var(--gray-200);
        }

        .btn-logout svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
        }

        /* ── Utility classes (usadas nos ficheiros PHP) ─────────────── */
        .container { max-width: 1140px; margin: 0 auto; padding: 0 24px; }

        /* Alerts */
        .alert { padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; border-left: 3px solid; font-size: 13px; line-height: 1.5; }
        .alert-success { background: var(--green-pale); color: var(--green-mid); border-color: var(--green); }
        .alert-danger   { background: #FEF0EF; color: #9B2B20; border-color: #E0604A; }
        .alert-warning  { background: #FFFAE6; color: #7A5A00; border-color: #D4A000; }
        .alert-info     { background: #E8F2FC; color: #0069B4; border-color: #5599D4; }
        .alert-secondary{ background: var(--gray-50); color: var(--text-muted); border-color: var(--gray-200); }

        /* Cards */
        .card { background: var(--white); border: var(--border); border-radius: var(--radius-lg); overflow: hidden; }
        .card-padding, .card-body { padding: 24px; }
        .card h3, .card-body h3 { font-size: 13.5px; font-weight: 500; color: var(--black); margin-bottom: 16px; padding-bottom: 12px; border-bottom: var(--border); }

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge-success, .badge-aprovada  { background: var(--green-pale); color: var(--green-mid); }
        .badge-danger,  .badge-rejeitada { background: #FEF0EF; color: #9B2B20; }
        .badge-warning, .badge-rascunho  { background: #FFFAE6; color: #7A5A00; }
        .badge-info,    .badge-submetida { background: #E8F2FC; color: #0069B4; }
        .badge-secondary, .badge-pendente{ background: var(--gray-50); color: var(--text-muted); border: var(--border); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            height: 38px; padding: 0 16px; border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
            border: var(--border); background: var(--white); color: var(--black);
            cursor: pointer; transition: all 0.15s; text-decoration: none; white-space: nowrap;
        }
        .btn:hover { background: var(--gray-50); border-color: var(--gray-200); }
        .btn:active { transform: scale(0.98); }
        .btn-primary  { background: var(--black); color: white; border-color: var(--black); }
        .btn-primary:hover  { background: #333; border-color: #333; }
        .btn-green    { background: var(--green); color: white; border-color: var(--green); }
        .btn-green:hover    { background: var(--green-mid); border-color: var(--green-mid); }
        .btn-danger   { background: #E0604A; color: white; border-color: #E0604A; }
        .btn-danger:hover   { background: #C0392B; border-color: #C0392B; }
        .btn-sm { height: 32px; padding: 0 12px; font-size: 12px; }
        .btn svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            padding: 10px 14px; text-align: left;
            font-size: 10.5px; font-weight: 500; text-transform: uppercase;
            letter-spacing: 0.07em; color: var(--text-faint);
            border-bottom: var(--border); white-space: nowrap;
        }
        .data-table td { padding: 13px 14px; border-bottom: var(--border); font-size: 13px; vertical-align: middle; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover td { background: var(--gray-50); }

        /* Form controls */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 7px; }
        .form-label .req { color: #E0604A; margin-left: 2px; }
        .form-control {
            width: 100%; height: 40px; padding: 0 12px;
            border: var(--border); border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif; font-size: 13.5px;
            color: var(--black); background: var(--white);
            transition: border-color 0.15s, box-shadow 0.15s; outline: none;
            appearance: none; -webkit-appearance: none;
        }
        .form-control:focus { border-color: var(--gray-400); box-shadow: 0 0 0 3px rgba(0,122,51,0.07); }
        .form-control:disabled { background: var(--gray-50); color: var(--gray-400); cursor: not-allowed; }
        textarea.form-control { height: auto; padding: 10px 12px; resize: vertical; min-height: 90px; }
        .form-hint { font-size: 11.5px; color: var(--gray-400); margin-top: 5px; }
        .select-wrap { position: relative; }
        .select-wrap::after { content: ''; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; border-top: 5px solid var(--gray-400); pointer-events: none; }

        /* Responsive hide */
        @media (max-width: 860px) {
            .header-nav { display: none; }
            .user-meta  { display: none; }
        }
        @media (max-width: 520px) {
            .site-header { padding: 0 16px; }
            .logo-wordmark .logo-sub { display: none; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-left">
        <!-- Logo -->
        <a href="#" class="header-logo">
            <div class="logo-mark">
                <svg viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 14V6.5L8.5 3L14.5 6.5V14" stroke="white" stroke-width="1.3" stroke-linejoin="round"/>
                    <path d="M6 14V9.5H11V14" stroke="white" stroke-width="1.3" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="logo-wordmark">
                <span class="logo-name">Portal Académico</span>
                <span class="logo-sub">IPCA</span>
            </div>
        </a>

        <div class="header-divider"></div>

        <!-- Section label + nav (injected by each page via JS or PHP) -->
        <?php
        // Determinar secção e links de nav com base no perfil
        $perfil  = $_SESSION['perfil_nome'] ?? '';
        $user_id = $_SESSION['user_id']     ?? null;

        $sectionLabel = match($perfil) {
            'Aluno'               => 'Portal do Aluno',
            'Gestor Pedagógico'   => 'Gestão Pedagógica',
            'Funcionário'         => 'Serviços Académicos',
            'Admin'               => 'Administração',
            default               => 'Portal Académico',
        };

        $navLinks = match($perfil) {
            'Aluno' => [
                ['href' => '../Home-Alunos/dashboard.php',       'label' => 'Dashboard',   'icon' => '<path d="M1 1h6v6H1zM9 1h6v6H9zM1 9h6v6H1zM9 9h6v6H9z"/>'],
                ['href' => '../Home-Alunos/submeter_matricula.php','label' => 'Ficha',      'icon' => '<rect x="2" y="1" width="12" height="14" rx="1.5"/><line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="11" y2="8"/>'],
                ['href' => '../Home-Alunos/pedido_matricula.php', 'label' => 'Matrícula',   'icon' => '<path d="M8 1L10 5.5H15L11 8.5L12.5 13L8 10L3.5 13L5 8.5L1 5.5H6Z"/>'],
            ],
            'Gestor Pedagógico' => [
                ['href' => '../gestor/dashboard.php',      'label' => 'Dashboard',   'icon' => '<path d="M1 1h6v6H1zM9 1h6v6H9zM1 9h6v6H1zM9 9h6v6H9z"/>'],
                ['href' => '../gestor/fichas.php',         'label' => 'Fichas',      'icon' => '<rect x="2" y="1" width="12" height="14" rx="1.5"/><line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="11" y2="8"/>'],
                ['href' => '../gestor/cursos.php',         'label' => 'Cursos',      'icon' => '<circle cx="8" cy="8" r="6.5"/><path d="M8 5v3l2 2"/>'],
                ['href' => '../gestor/disciplinas.php',    'label' => 'Disciplinas', 'icon' => '<path d="M2 3h12M2 7h8M2 11h10M2 15h6"/>'],
                ['href' => '../gestor/plano_estudos.php',  'label' => 'Plano',       'icon' => '<rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M5 3V1M11 3V1M1 7h14"/>'],
            ],
            'Funcionário' => [
                ['href' => '../servicos-academicos/dashboard.php', 'label' => 'Dashboard', 'icon' => '<path d="M1 1h6v6H1zM9 1h6v6H9zM1 9h6v6H1zM9 9h6v6H9z"/>'],
                ['href' => '../servicos-academicos/pedidos.php',   'label' => 'Pedidos',   'icon' => '<path d="M8 1L10 5.5H15L11 8.5L12.5 13L8 10L3.5 13L5 8.5L1 5.5H6Z"/>'],
                ['href' => '../servicos-academicos/pautas.php',    'label' => 'Pautas',    'icon' => '<rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M5 3V1M11 3V1M1 7h14"/>'],
            ],
            default => [],
        };

        // Avatar/iniciais
        $nome_sessao = $_SESSION['user_nome'] ?? 'Utilizador';
        $partes      = explode(' ', $nome_sessao);
        $iniciais    = strtoupper(($partes[0][0] ?? '') . ($partes[count($partes)-1][0] ?? ''));

        // Página atual para marcar nav item como active
        $pagina_atual = basename($_SERVER['PHP_SELF']);
        ?>

        <span class="header-section-label"><?= htmlspecialchars($sectionLabel) ?></span>

        <?php if (!empty($navLinks)): ?>
        <nav class="header-nav">
            <?php foreach ($navLinks as $link):
                $isActive = (basename($link['href']) === $pagina_atual) ? 'active' : '';
            ?>
            <a href="<?= htmlspecialchars($link['href']) ?>" class="header-nav-item <?= $isActive ?>">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                    <?= $link['icon'] ?>
                </svg>
                <?= htmlspecialchars($link['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>

    <div class="header-right">
        <!-- User chip -->
        <div class="header-user" title="<?= htmlspecialchars($nome_sessao) ?>">
            <div class="user-avatar-sm"><?= $iniciais ?></div>
            <div class="user-meta">
                <span class="user-meta-name"><?= htmlspecialchars(explode(' ', $nome_sessao)[0]) ?></span>
                <span class="user-meta-role"><?= htmlspecialchars($perfil ?: 'Portal') ?></span>
            </div>
        </div>

        <!-- Logout -->
        <a href="../auth/logout.php" class="btn-logout" title="Terminar sessão">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2H3a1 1 0 00-1 1v10a1 1 0 001 1h3M10 11l3-3-3-3M13 8H6"/>
            </svg>
            Sair
        </a>
    </div>
</header>