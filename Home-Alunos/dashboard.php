<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Aluno') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';

$aluno_id = $_SESSION['user_id'];

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if (isset($_GET['msg']) && $_GET['msg'] === 'submetido') $flash_success = "Ficha submetida com sucesso! Aguarda validação.";
if (isset($_GET['sucesso'])) $flash_success = "Operação realizada com sucesso!";

try {
    $stmtAluno = $pdo->prepare("
        SELECT u.nome, u.email, a.foto, a.estado, a.observacoes_validacao,
               a.data_validacao, a.data_nascimento, a.morada, a.telefone,
               u2.nome AS validado_por_nome,
               c.nome AS curso_nome, c.sigla AS curso_sigla
        FROM utilizadores u
        JOIN alunos a ON u.id = a.id
        LEFT JOIN cursos c ON a.curso_id = c.id
        LEFT JOIN utilizadores u2 ON a.validado_por = u2.id
        WHERE u.id = :id
    ");
    $stmtAluno->execute(['id' => $aluno_id]);
    $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
    if (!$aluno) die("Erro: Perfil de aluno não encontrado.");

    $stmtNotas = $pdo->prepare("
        SELECT n.id as nota_id, n.nota, n.epoca, n.ano_letivo, d.nome_disciplina, d.sigla
        FROM notas n
        JOIN disciplinas d ON n.disciplina_id = d.id
        WHERE n.aluno_id = :id
        ORDER BY n.ano_letivo DESC, d.nome_disciplina ASC
    ");
    $stmtNotas->execute(['id' => $aluno_id]);
    $notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

    $total_notas = count($notas);
    $soma_notas  = 0; $aprovadas = 0; $reprovadas = 0;
    $melhor_nota_val = -1; $pior_nota_val = 21;
    $notas_por_ano = []; $stats_por_ano = []; $disciplinas_reprovadas = [];

    foreach ($notas as $n) {
        $nota = (float)$n['nota']; $ano = $n['ano_letivo'];
        $soma_notas += $nota;
        if ($nota >= 10) { $aprovadas++; } else { $reprovadas++; $disciplinas_reprovadas[] = $n; }
        if ($nota > $melhor_nota_val) $melhor_nota_val = $nota;
        if ($nota < $pior_nota_val)   $pior_nota_val   = $nota;
        if (!isset($notas_por_ano[$ano])) {
            $notas_por_ano[$ano] = [];
            $stats_por_ano[$ano] = ['soma'=>0,'total'=>0,'aprovadas'=>0,'reprovadas'=>0];
        }
        $notas_por_ano[$ano][] = $n;
        $stats_por_ano[$ano]['soma']  += $nota;
        $stats_por_ano[$ano]['total'] ++;
        if ($nota >= 10) $stats_por_ano[$ano]['aprovadas']++; else $stats_por_ano[$ano]['reprovadas']++;
    }

    $media = $total_notas > 0 ? $soma_notas / $total_notas : 0;
    $chart_anos = []; $chart_medias = []; $stats_ano_asc = $stats_por_ano;
    ksort($stats_ano_asc);
    foreach ($stats_ano_asc as $ano => &$stats) {
        $m = $stats['total'] > 0 ? $stats['soma'] / $stats['total'] : 0;
        $stats['media'] = number_format($m, 1);
        $chart_anos[] = $ano; $chart_medias[] = round($m, 2);
    }
    unset($stats);
    krsort($notas_por_ano);

    $stmtPedidos = $pdo->prepare("
        SELECT pm.ano_letivo, pm.estado, pm.data_pedido, pm.observacoes, pm.data_decisao,
               c.nome AS curso_nome, u.nome AS funcionario_nome
        FROM pedidos_matricula pm
        LEFT JOIN cursos c ON pm.curso_id = c.id
        LEFT JOIN utilizadores u ON pm.funcionario_id = u.id
        WHERE pm.aluno_id = :id ORDER BY pm.data_pedido DESC
    ");
    $stmtPedidos->execute(['id' => $aluno_id]);
    $pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$estado         = $aluno['estado'];
$pode_editar    = in_array($estado, ['Rascunho', 'Rejeitada']);
$pode_submeter  = in_array($estado, ['Rascunho', 'Rejeitada']);
$pode_inscrever = $estado === 'Aprovada';

function classificacao(float $nota): string {
    if ($nota >= 18) return 'Excelente';
    if ($nota >= 16) return 'Muito Bom';
    if ($nota >= 14) return 'Bom';
    if ($nota >= 10) return 'Suficiente';
    return 'Reprovado';
}

$fotoUrl = !empty($aluno['foto'])
    ? htmlspecialchars($aluno['foto'])
    : "https://ui-avatars.com/api/?name=" . urlencode($aluno['nome']) . "&background=1A1A1A&color=fff&size=200&bold=true";

$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $aluno['nome']), 0, 2)));

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --green:       #007a33;
    --green-mid:   #005a26;
    --green-pale:  #e8f5ed;
    --green-soft:  #c8e6d1;
    --white:       #ffffff;
    --off-white:   #F8F9FA;
    --gray-50:     #F4F5F6;
    --gray-100:    #EEEEEE;
    --gray-200:    #D8D8D8;
    --gray-400:    #9A9A9A;
    --gray-600:    #5A5A5A;
    --black:       #1A1A1A;
    --text-main:   #1A1A1A;
    --text-muted:  #6A6A6A;
    --text-faint:  #9A9A9A;
    --sidebar-w:   240px;
    --header-h:    60px;
    --radius-sm:   6px;
    --radius-md:   10px;
    --radius-lg:   14px;
    --border:      1px solid #EEEEEE;
    --shadow-xs:   0 1px 3px rgba(0,0,0,0.04);
    --shadow-sm:   0 2px 8px rgba(0,0,0,0.05);
    --transition:  0.18s ease;
}

/* ─── Reset & Base ─────────────────────────────────────────────────── */
body {
    font-family: 'DM Sans', system-ui, sans-serif;
    background: var(--off-white);
    color: var(--text-main);
    font-size: 14px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    overflow-x: hidden;
}

a { color: inherit; text-decoration: none; }
button { cursor: pointer; font-family: inherit; }

/* ─── Layout Shell ─────────────────────────────────────────────────── */
.app-shell { display: flex; min-height: 100vh; }

/* ─── Sidebar ──────────────────────────────────────────────────────── */
.sidebar {
    width: var(--sidebar-w);
    min-height: 100vh;
    background: var(--white);
    border-right: var(--border);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    z-index: 100;
    transition: transform var(--transition);
}

.sidebar-logo {
    height: var(--header-h);
    display: flex;
    align-items: center;
    padding: 0 20px;
    border-bottom: var(--border);
    gap: 10px;
}

.logo-mark {
    width: 28px; height: 28px;
    background: var(--black);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.logo-mark svg { width: 16px; height: 16px; }

.logo-text {
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.04em;
    color: var(--black);
}

.logo-text span {
    display: block;
    font-size: 10px;
    font-weight: 400;
    color: var(--text-faint);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-top: 1px;
}

.sidebar-section { padding: 20px 12px 8px; }

.sidebar-label {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-faint);
    padding: 0 8px;
    margin-bottom: 4px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: var(--radius-sm);
    color: var(--text-muted);
    font-size: 13.5px;
    font-weight: 400;
    transition: all var(--transition);
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}

.nav-item:hover { background: var(--gray-50); color: var(--black); }
.nav-item.active { background: var(--green-pale); color: var(--green-mid); font-weight: 500; }

.nav-icon {
    width: 16px; height: 16px;
    flex-shrink: 0;
    opacity: 0.6;
}
.nav-item.active .nav-icon,
.nav-item:hover .nav-icon { opacity: 1; }

.nav-item .nav-badge {
    margin-left: auto;
    background: var(--green);
    color: white;
    font-size: 10px;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 10px;
    line-height: 1.4;
}

.sidebar-divider { height: 1px; background: var(--gray-100); margin: 8px 12px; }

.sidebar-footer {
    margin-top: auto;
    padding: 16px 12px;
    border-top: var(--border);
}

.user-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background var(--transition);
}
.user-pill:hover { background: var(--gray-50); }

.user-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--black);
    color: white;
    font-size: 11px;
    font-weight: 500;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    letter-spacing: 0.03em;
    overflow: hidden;
}
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }

.user-info-text { overflow: hidden; }
.user-name  { font-size: 13px; font-weight: 500; color: var(--black); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role  { font-size: 11px; color: var(--text-faint); }

/* ─── Main Content ─────────────────────────────────────────────────── */
.main-content {
    margin-left: var(--sidebar-w);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ─── Top Bar ──────────────────────────────────────────────────────── */
.topbar {
    height: var(--header-h);
    background: var(--white);
    border-bottom: var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    position: sticky;
    top: 0;
    z-index: 50;
}

.topbar-left { display: flex; align-items: center; gap: 12px; }

.page-eyebrow {
    font-size: 11px;
    font-weight: 400;
    color: var(--text-faint);
    letter-spacing: 0.04em;
}

.page-title-small {
    font-size: 15px;
    font-weight: 500;
    color: var(--black);
}

.topbar-right { display: flex; align-items: center; gap: 8px; }

/* ─── Buttons ──────────────────────────────────────────────────────── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    font-family: inherit;
    border: var(--border);
    background: var(--white);
    color: var(--text-main);
    transition: all var(--transition);
    line-height: 1;
    white-space: nowrap;
}
.btn:hover { background: var(--gray-50); border-color: var(--gray-200); }
.btn:active { transform: scale(0.98); }

.btn-primary {
    background: var(--black);
    color: white;
    border-color: var(--black);
}
.btn-primary:hover { background: #333; border-color: #333; }

.btn-green {
    background: var(--green);
    color: white;
    border-color: var(--green);
}
.btn-green:hover { background: var(--green-mid); border-color: var(--green-mid); }

.btn-ghost {
    background: transparent;
    border-color: transparent;
    color: var(--text-muted);
}
.btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-100); color: var(--black); }

.btn-sm { padding: 6px 10px; font-size: 12px; }
.btn-disabled { opacity: 0.42; pointer-events: none; cursor: not-allowed; }

.btn-icon { width: 16px; height: 16px; flex-shrink: 0; }

/* ─── Page Body ────────────────────────────────────────────────────── */
.page-body { padding: 32px; flex: 1; max-width: 1100px; }

/* ─── Status Banner ────────────────────────────────────────────────── */
.status-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-radius: var(--radius-md);
    border: var(--border);
    background: var(--white);
    margin-bottom: 28px;
    gap: 16px;
    flex-wrap: wrap;
}

.status-left { display: flex; align-items: center; gap: 14px; }

.status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-meta h4 { font-size: 13.5px; font-weight: 500; margin-bottom: 2px; }
.status-meta p  { font-size: 12px; color: var(--text-faint); }

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 500;
    letter-spacing: 0.02em;
}

/* State variants */
.state-rascunho  { --dot: #D4A000; --badge-bg: #FFFAE6; --badge-text: #856404; --border-l: #F0D060; }
.state-submetida { --dot: #0069B4; --badge-bg: #E8F2FC; --badge-text: #0069B4; --border-l: #A0C8F0; }
.state-aprovada  { --dot: #007a33; --badge-bg: var(--green-pale); --badge-text: var(--green-mid); --border-l: var(--green-soft); }
.state-rejeitada { --dot: #C0392B; --badge-bg: #FEF0EF; --badge-text: #9B2B20; --border-l: #F5A49A; }

.status-dot        { background: var(--dot); }
.status-badge      { background: var(--badge-bg); color: var(--badge-text); }
.status-banner     { border-left: 3px solid var(--border-l); }

/* ─── Flash Messages ───────────────────────────────────────────────── */
.flash {
    padding: 12px 18px;
    border-radius: var(--radius-md);
    font-size: 13px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.flash-success { background: var(--green-pale); color: var(--green-mid); border: 1px solid var(--green-soft); }
.flash-error   { background: #FEF0EF; color: #9B2B20; border: 1px solid #F5A49A; }

/* ─── Section Header ───────────────────────────────────────────────── */
.section-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: var(--border);
}
.section-title {
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text-faint);
}
.section-action { font-size: 12px; color: var(--green); font-weight: 500; }
.section-action:hover { color: var(--green-mid); text-decoration: underline; }

/* ─── Cards ────────────────────────────────────────────────────────── */
.card {
    background: var(--white);
    border: var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.card-body { padding: 24px; }
.card-body + .card-body { border-top: var(--border); }

/* ─── Metric Strip ─────────────────────────────────────────────────── */
.metrics-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    border: var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    margin-bottom: 24px;
    background: var(--white);
}

.metric-cell {
    padding: 20px 24px;
    border-right: var(--border);
    transition: background var(--transition);
}
.metric-cell:last-child { border-right: none; }
.metric-cell:hover { background: var(--gray-50); }

.metric-label {
    font-size: 11px;
    font-weight: 400;
    color: var(--text-faint);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.metric-value {
    font-size: 26px;
    font-weight: 300;
    color: var(--black);
    line-height: 1;
    font-family: 'DM Serif Display', serif;
    letter-spacing: -0.02em;
}

.metric-value.green  { color: var(--green); }
.metric-value.muted  { color: var(--text-faint); }

.metric-sub {
    font-size: 11.5px;
    color: var(--text-faint);
    margin-top: 5px;
}

/* ─── Grid Layouts ─────────────────────────────────────────────────── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3-2 { display: grid; grid-template-columns: 3fr 2fr; gap: 20px; }
.grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

.mb-6  { margin-bottom: 6px; }
.mb-12 { margin-bottom: 12px; }
.mb-16 { margin-bottom: 16px; }
.mb-20 { margin-bottom: 20px; }
.mb-24 { margin-bottom: 24px; }
.mb-32 { margin-bottom: 32px; }

/* ─── Profile Card ─────────────────────────────────────────────────── */
.profile-card { display: flex; align-items: flex-start; gap: 20px; }
.profile-avatar-wrap { position: relative; flex-shrink: 0; }
.profile-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--gray-100);
    display: block;
}
.avatar-placeholder {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: var(--black);
    color: white;
    font-size: 20px;
    font-weight: 300;
    display: flex; align-items: center; justify-content: center;
    font-family: 'DM Serif Display', serif;
    letter-spacing: 0.02em;
    flex-shrink: 0;
}
.profile-name {
    font-size: 19px;
    font-weight: 400;
    font-family: 'DM Serif Display', serif;
    color: var(--black);
    letter-spacing: -0.01em;
    margin-bottom: 4px;
    line-height: 1.2;
}
.profile-email { font-size: 12.5px; color: var(--text-faint); margin-bottom: 10px; }
.profile-tags  { display: flex; flex-wrap: wrap; gap: 6px; }

.tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 400;
    border: var(--border);
    background: var(--gray-50);
    color: var(--text-muted);
}

.tag-green { background: var(--green-pale); color: var(--green-mid); border-color: var(--green-soft); }

/* ─── Data Grid ────────────────────────────────────────────────────── */
.data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }

.data-row {
    padding: 12px 0;
    border-bottom: var(--border);
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.data-row:nth-child(odd) { padding-right: 20px; }
.data-row:nth-child(even) { padding-left: 20px; border-left: var(--border); }
.data-row:nth-last-child(-n+2) { border-bottom: none; }

.data-label { font-size: 10.5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-faint); }
.data-value { font-size: 13.5px; color: var(--text-main); }

/* ─── Notes Table ──────────────────────────────────────────────────── */
.filter-row {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.filter-input, .filter-select {
    height: 34px;
    padding: 0 12px;
    border: var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 13px;
    color: var(--text-main);
    background: var(--white);
    outline: none;
    transition: border-color var(--transition);
    min-width: 160px;
}
.filter-input:focus, .filter-select:focus { border-color: var(--gray-400); }
.filter-input { flex: 1; }

.notes-table { width: 100%; border-collapse: collapse; }
.notes-table thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-faint);
    border-bottom: var(--border);
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
}
.notes-table thead th:hover { color: var(--black); }

.notes-table tbody td {
    padding: 13px 14px;
    border-bottom: var(--border);
    font-size: 13.5px;
    vertical-align: middle;
}

.notes-table tbody tr:last-child td { border-bottom: none; }

.notes-table tbody tr {
    transition: background var(--transition);
}
.notes-table tbody tr:hover td { background: var(--gray-50); }

.year-group {
    margin-bottom: 20px;
    border: var(--border);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.year-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 18px;
    background: var(--gray-50);
    cursor: pointer;
    border-bottom: var(--border);
    user-select: none;
}

.year-group-title {
    font-size: 13px;
    font-weight: 500;
    color: var(--black);
}

.year-group-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: var(--text-faint);
}

.year-arrow {
    width: 14px; height: 14px;
    color: var(--text-faint);
    transition: transform var(--transition);
}
.year-group.collapsed .year-arrow { transform: rotate(-90deg); }
.year-group-body { display: block; }
.year-group.collapsed .year-group-body { display: none; }

/* ─── Nota Badge ───────────────────────────────────────────────────── */
.nota-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px; height: 24px;
    border-radius: 4px;
    font-size: 12.5px;
    font-weight: 500;
    letter-spacing: 0.01em;
}
.nota-pass { background: var(--green-pale); color: var(--green-mid); }
.nota-fail { background: #FEF0EF; color: #9B2B20; }

.progress-thin {
    width: 56px; height: 3px;
    background: var(--gray-100);
    border-radius: 2px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
}
.progress-fill-thin {
    height: 100%;
    border-radius: 2px;
    background: var(--green);
    width: var(--w);
}
.progress-fill-thin.fail { background: #E0604A; }

.class-text { font-size: 12px; color: var(--text-faint); font-style: italic; }

/* ─── Validation History ───────────────────────────────────────────── */
.history-table { width: 100%; border-collapse: collapse; }
.history-table td {
    padding: 12px 0;
    font-size: 13px;
    border-bottom: var(--border);
    vertical-align: top;
}
.history-table tr:last-child td { border-bottom: none; }
.history-table .col-label { width: 130px; color: var(--text-faint); font-size: 12px; }

/* ─── Pedidos ──────────────────────────────────────────────────────── */
.pedidos-table { width: 100%; border-collapse: collapse; }
.pedidos-table th {
    padding: 10px 14px;
    text-align: left;
    font-size: 10.5px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-faint);
    border-bottom: var(--border);
}
.pedidos-table td {
    padding: 12px 14px;
    font-size: 13px;
    border-bottom: var(--border);
}
.pedidos-table tbody tr:last-child td { border-bottom: none; }
.pedidos-table tbody tr:hover td { background: var(--gray-50); }

/* ─── Simulador ────────────────────────────────────────────────────── */
.sim-select, .sim-input {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border: var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 13px;
    color: var(--text-main);
    background: var(--white);
    outline: none;
    margin-bottom: 14px;
    transition: border-color var(--transition);
}
.sim-select:focus, .sim-input:focus { border-color: var(--gray-400); }
.sim-result {
    text-align: center;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    margin-top: 4px;
}
.sim-result .sim-val {
    font-size: 28px;
    font-family: 'DM Serif Display', serif;
    font-weight: 400;
    letter-spacing: -0.02em;
    color: var(--text-faint);
}
.sim-result .sim-label { font-size: 11.5px; color: var(--text-faint); margin-top: 2px; }

/* ─── Chart ────────────────────────────────────────────────────────── */
.chart-wrap { position: relative; }

/* ─── Cert container (print only) ─────────────────────────────────── */
#cert-container { display: none; }

/* ─── Scrollable table container ──────────────────────────────────── */
.table-scroll { max-height: 420px; overflow-y: auto; }

/* ─── Animations ───────────────────────────────────────────────────── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp 0.4s ease both; }
.delay-1 { animation-delay: 0.06s; }
.delay-2 { animation-delay: 0.12s; }
.delay-3 { animation-delay: 0.18s; }
.delay-4 { animation-delay: 0.24s; }
.delay-5 { animation-delay: 0.30s; }

/* ─── Responsive ───────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main-content { margin-left: 0; }
    .grid-2, .grid-3-2, .grid-2-1 { grid-template-columns: 1fr; }
    .metrics-strip { grid-template-columns: 1fr 1fr; }
    .metrics-strip .metric-cell:nth-child(2) { border-right: none; }
    .metrics-strip .metric-cell:nth-child(3) { border-top: var(--border); }
    .page-body { padding: 20px 16px; }
    .data-grid { grid-template-columns: 1fr; }
    .data-row:nth-child(even) { padding-left: 0; border-left: none; }
}

/* ─── Print ────────────────────────────────────────────────────────── */
@media print {
    .sidebar, .topbar, .page-body .btn, .filter-row, .status-banner { display: none !important; }
    .main-content { margin-left: 0; }
    body:not(.print-cert) #cert-container { display: none !important; }
    body.print-cert .main-content { display: none !important; }
    body.print-cert #cert-container { display: block; padding: 3cm; font-family: 'DM Serif Display', serif; color: #000; }
    .cert-inner { border: 2px solid #007a33; padding: 50px; min-height: 80vh; text-align: center; position: relative; }
    .cert-title { font-size: 32px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 50px; color: #007a33; }
    .cert-body  { font-size: 18px; line-height: 2.2; }
    .cert-footer { position: absolute; bottom: 50px; left: 0; width: 100%; text-align: center; font-size: 14px; color: #555; }
    .cert-sig   { border-top: 1px solid #000; width: 240px; margin: 50px auto 8px; padding-top: 10px; font-style: italic; }
}
</style>

<div class="app-shell">

    <!-- ══ SIDEBAR ══════════════════════════════════════════════════════ -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-mark">
                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 13V6L8 3L13 6V13" stroke="white" stroke-width="1.2" stroke-linejoin="round"/>
                    <path d="M6 13V9H10V13" stroke="white" stroke-width="1.2" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="logo-text">
                Portal Académico
                <span>IPCA</span>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-label">Principal</div>

            <button class="nav-item active" onclick="showSection('dashboard')">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/>
                    <rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/>
                </svg>
                Dashboard
            </button>

            <button class="nav-item" onclick="showSection('notas')">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <rect x="2" y="1" width="12" height="14" rx="1.5"/>
                    <line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="11" y2="8"/><line x1="5" y1="11" x2="9" y2="11"/>
                </svg>
                Notas
                <?php if ($total_notas > 0): ?><span class="nav-badge"><?= $total_notas ?></span><?php endif; ?>
            </button>

            <button class="nav-item" onclick="showSection('ficha')">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6"/>
                </svg>
                Ficha de Aluno
            </button>

            <button class="nav-item" onclick="showSection('matriculas')">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <path d="M8 1L10 5.5H15L11 8.5L12.5 13L8 10L3.5 13L5 8.5L1 5.5H6Z"/>
                </svg>
                Matrículas
            </button>
        </div>

        <div class="sidebar-divider"></div>

        <div class="sidebar-section">
            <div class="sidebar-label">Serviços</div>

            <?php if ($pode_submeter): ?>
            <a href="submeter_matricula.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <path d="M8 10V2M4 6l4-4 4 4"/><path d="M2 12v1a1 1 0 001 1h10a1 1 0 001-1v-1"/>
                </svg>
                Submeter Ficha
            </a>
            <?php endif; ?>

            <?php if ($pode_inscrever): ?>
            <a href="pedido_matricula.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <circle cx="8" cy="8" r="6"/><line x1="8" y1="5" x2="8" y2="11"/><line x1="5" y1="8" x2="11" y2="8"/>
                </svg>
                Inscrição em Disciplinas
            </a>
            <?php else: ?>
            <div class="nav-item btn-disabled" title="Disponível após aprovação da ficha">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <rect x="4" y="7" width="8" height="7" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/>
                </svg>
                Inscrição Bloqueada
            </div>
            <?php endif; ?>

            <a href="upload_foto.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3">
                    <circle cx="8" cy="8" r="3"/><circle cx="8" cy="8" r="6.5"/>
                    <path d="M6 2.5L7 1h2l1 1.5" stroke-linecap="round"/>
                </svg>
                Alterar Foto
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-pill">
                <div class="user-avatar">
                    <?php if (!empty($aluno['foto'])): ?>
                        <img src="<?= $fotoUrl ?>" alt="foto"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <span style="display:none; width:100%; height:100%; align-items:center; justify-content:center;"><?= $initials ?></span>
                    <?php else: ?>
                        <?= $initials ?>
                    <?php endif; ?>
                </div>
                <div class="user-info-text">
                    <div class="user-name"><?= htmlspecialchars(explode(' ', $aluno['nome'])[0]) ?></div>
                    <div class="user-role">Aluno · <?= htmlspecialchars($aluno['curso_sigla'] ?? 'N/D') ?></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ══ MAIN ═════════════════════════════════════════════════════════ -->
    <div class="main-content">

        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <div>
                    <div class="page-eyebrow">Portal Académico &nbsp;/&nbsp; Aluno</div>
                    <div class="page-title-small"><?= htmlspecialchars($aluno['nome']) ?></div>
                </div>
            </div>
            <div class="topbar-right">
                <?php if ($estado === 'Aprovada'): ?>
                    <button class="btn btn-sm" onclick="window.open('certificado.php', '_blank')">
                        <svg class="btn-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="1" width="10" height="12" rx="1.5"/><line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="9" y2="8"/></svg>
                        Certificado
                    </button>
                <?php endif; ?>
                <button class="btn btn-sm" id="btnEmail" onclick="sendEmail()">
                    <svg class="btn-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M1 4l7 5 7-5"/></svg>
                    Enviar Notas
                </button>
                <button class="btn btn-sm btn-primary" onclick="window.print()">
                    <svg class="btn-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M4 5V2h8v3M3 5h10a1 1 0 011 1v5H2V6a1 1 0 011-1zM4 11v3h8v-3"/></svg>
                    Imprimir
                </button>
            </div>
        </header>

        <!-- Page Body -->
        <div class="page-body">

            <!-- Flash messages -->
            <?php if ($flash_success): ?>
                <div class="flash flash-success fade-up">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="flex-shrink:0;margin-top:1px"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/><path d="M5 8l2 2 4-4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?= htmlspecialchars($flash_success) ?>
                </div>
            <?php endif; ?>
            <?php if ($flash_error): ?>
                <div class="flash flash-error fade-up">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="flex-shrink:0;margin-top:1px"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/><line x1="8" y1="5" x2="8" y2="9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="8" cy="11" r="0.8" fill="currentColor"/></svg>
                    <?= htmlspecialchars($flash_error) ?>
                </div>
            <?php endif; ?>

            <!-- Status Banner -->
            <?php
            $stateClass = 'state-' . strtolower($estado);
            $stateLabel = [
                'Rascunho'  => 'Rascunho — ficha incompleta',
                'Submetida' => 'Submetida — a aguardar validação',
                'Aprovada'  => 'Ficha aprovada',
                'Rejeitada' => 'Ficha rejeitada',
            ][$estado] ?? $estado;
            $stateDesc = [
                'Rascunho'  => 'Complete os seus dados e submeta a ficha para validação pelos serviços académicos.',
                'Submetida' => 'A ficha encontra-se em análise. A edição está temporariamente bloqueada.',
                'Aprovada'  => 'A sua matrícula está confirmada. Pode inscrever-se em disciplinas.',
                'Rejeitada' => !empty($aluno['observacoes_validacao']) ? 'Motivo: ' . htmlspecialchars($aluno['observacoes_validacao']) : 'Por favor corrija os dados e volte a submeter.',
            ][$estado] ?? '';
            ?>
            <div class="status-banner <?= $stateClass ?> fade-up">
                <div class="status-left">
                    <div class="status-dot"></div>
                    <div class="status-meta">
                        <h4><?= $stateLabel ?></h4>
                        <p><?= $stateDesc ?></p>
                    </div>
                </div>
                <div style="display:flex; gap:8px; flex-shrink:0;">
                    <span class="status-badge"><?= htmlspecialchars($estado) ?></span>
                    <?php if ($pode_submeter): ?>
                        <a href="submeter_matricula.php" class="btn btn-sm btn-green"
                           onclick="return confirm('Submeter a ficha para validação?')">
                            Submeter Ficha
                        </a>
                    <?php endif; ?>
                    <?php if ($pode_editar): ?>
                        <a href="submeter_matricula.php" class="btn btn-sm">Editar</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ══ SECTION: DASHBOARD ════════════════════════════════════ -->
            <div id="section-dashboard">

                <!-- Metrics Strip -->
                <div class="metrics-strip fade-up delay-1">
                    <div class="metric-cell">
                        <div class="metric-label">Média Global</div>
                        <div class="metric-value green counter" data-target="<?= number_format($media, 2) ?>" data-float="1">
                            <?= $total_notas > 0 ? '—' : '—' ?>
                        </div>
                        <div class="metric-sub">Em 20 valores</div>
                    </div>
                    <div class="metric-cell">
                        <div class="metric-label">Aprovadas</div>
                        <div class="metric-value counter" data-target="<?= $aprovadas ?>">0</div>
                        <div class="metric-sub">disciplinas</div>
                    </div>
                    <div class="metric-cell">
                        <div class="metric-label">Reprovadas</div>
                        <div class="metric-value muted counter" data-target="<?= $reprovadas ?>">0</div>
                        <div class="metric-sub">disciplinas</div>
                    </div>
                    <div class="metric-cell">
                        <div class="metric-label">Curso</div>
                        <div class="metric-value" style="font-size:16px; font-family:'DM Sans',sans-serif; font-weight:500; padding-top:4px;">
                            <?= htmlspecialchars($aluno['curso_sigla'] ?? '—') ?>
                        </div>
                        <div class="metric-sub"><?= htmlspecialchars($aluno['curso_nome'] ?? 'Sem curso atribuído') ?></div>
                    </div>
                </div>

                <!-- Profile + Evolution Chart -->
                <div class="grid-3-2 mb-24">
                    <div class="card fade-up delay-2">
                        <div class="card-body">
                            <div class="section-header mb-16">
                                <span class="section-title">Perfil</span>
                                <?php if ($pode_editar): ?>
                                    <a href="submeter_matricula.php" class="section-action">Editar dados</a>
                                <?php endif; ?>
                            </div>
                            <div class="profile-card mb-20">
                                <div class="profile-avatar-wrap">
                                    <?php if (!empty($aluno['foto'])): ?>
                                        <img src="<?= $fotoUrl ?>" alt="Foto" class="profile-avatar"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="avatar-placeholder" style="display:none;"><?= $initials ?></div>
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?= $initials ?></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="profile-name"><?= htmlspecialchars($aluno['nome']) ?></div>
                                    <div class="profile-email"><?= htmlspecialchars($aluno['email']) ?></div>
                                    <div class="profile-tags">
                                        <span class="tag tag-green"><?= htmlspecialchars($aluno['curso_nome'] ?? 'Sem curso') ?></span>
                                        <span class="tag">Aluno</span>
                                    </div>
                                </div>
                            </div>
                            <div class="data-grid">
                                <div class="data-row">
                                    <span class="data-label">Morada</span>
                                    <span class="data-value"><?= htmlspecialchars($aluno['morada'] ?? '—') ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Telefone</span>
                                    <span class="data-value"><?= htmlspecialchars($aluno['telefone'] ?? '—') ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Data de Nascimento</span>
                                    <span class="data-value">
                                        <?= !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '—' ?>
                                    </span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Estado da Ficha</span>
                                    <span class="data-value"><span class="status-badge <?= $stateClass ?>"><?= htmlspecialchars($estado) ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card fade-up delay-3">
                        <div class="card-body">
                            <div class="section-header mb-16">
                                <span class="section-title">Evolução Anual</span>
                            </div>
                            <?php if (count($chart_anos) > 0): ?>
                                <div class="chart-wrap">
                                    <canvas id="evolucaoChart" height="180"></canvas>
                                </div>
                            <?php else: ?>
                                <div style="display:flex; align-items:center; justify-content:center; height:160px; color:var(--text-faint); font-size:13px; flex-direction:column; gap:8px;">
                                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1" opacity="0.4"><path d="M4 24L12 16L18 20L28 10"/><circle cx="12" cy="16" r="2"/><circle cx="18" cy="20" r="2"/><circle cx="28" cy="10" r="2"/></svg>
                                    Sem dados para gráfico
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($stats_ano_asc)): ?>
                        <div class="card-body" style="padding-top:16px; padding-bottom:16px;">
                            <?php foreach ($stats_ano_asc as $ano => $st): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:var(--border);">
                                <span style="font-size:12.5px; color:var(--text-muted);"><?= htmlspecialchars($ano) ?></span>
                                <span style="font-size:12px; color:var(--text-faint);">
                                    Média <strong style="color:var(--black);"><?= $st['media'] ?></strong>
                                    &nbsp;·&nbsp; ✓ <?= $st['aprovadas'] ?>
                                    &nbsp;·&nbsp; ✗ <?= $st['reprovadas'] ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Validation history (if relevant) -->
                <?php if (!empty($aluno['data_validacao']) || in_array($estado, ['Submetida','Aprovada','Rejeitada'])): ?>
                <div class="card fade-up delay-4 mb-24">
                    <div class="card-body">
                        <div class="section-header mb-16">
                            <span class="section-title">Histórico de Validação</span>
                        </div>
                        <table class="history-table">
                            <tr>
                                <td class="col-label">Estado</td>
                                <td><span class="status-badge <?= $stateClass ?>"><?= htmlspecialchars($estado) ?></span></td>
                            </tr>
                            <tr>
                                <td class="col-label">Validado por</td>
                                <td style="font-size:13px;"><?= htmlspecialchars($aluno['validado_por_nome'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <td class="col-label">Data de Decisão</td>
                                <td style="font-size:13px;">
                                    <?= !empty($aluno['data_validacao']) ? date('d/m/Y \à\s H:i', strtotime($aluno['data_validacao'])) : '—' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-label">Observações</td>
                                <td style="font-size:13px; <?= $estado === 'Rejeitada' ? 'color:#9B2B20;' : '' ?>">
                                    <?= htmlspecialchars($aluno['observacoes_validacao'] ?? '—') ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /section-dashboard -->

            <!-- ══ SECTION: NOTAS ════════════════════════════════════════ -->
            <div id="section-notas" style="display:none;">

                <div class="section-header mb-20">
                    <span class="section-title">Histórico de Notas</span>
                    <span style="font-size:12px; color:var(--text-faint);"><?= $total_notas ?> registos</span>
                </div>

                <?php if ($total_notas > 0): ?>
                <div class="filter-row">
                    <input type="text" class="filter-input" id="searchDiscipline" placeholder="Pesquisar disciplina...">
                    <select class="filter-select" id="filterYear">
                        <option value="all">Todos os anos</option>
                        <?php foreach (array_keys($notas_por_ano) as $anoKey): ?>
                            <option value="<?= htmlspecialchars($anoKey) ?>"><?= htmlspecialchars($anoKey) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="filterResult" style="min-width:140px;">
                        <option value="all">Todos</option>
                        <option value="pass">Aprovadas</option>
                        <option value="fail">Reprovadas</option>
                    </select>
                </div>

                <div id="notasContainer">
                    <?php $first = true; foreach ($notas_por_ano as $ano => $notas_ano):
                        $s = $stats_por_ano[$ano];
                    ?>
                    <div class="year-group" data-year="<?= htmlspecialchars($ano) ?>">
                        <div class="year-group-header" onclick="toggleYear(this.closest('.year-group'))">
                            <span class="year-group-title"><?= htmlspecialchars($ano) ?></span>
                            <div class="year-group-meta">
                                <span>Média <?= $s['total'] > 0 ? number_format($s['soma'] / $s['total'], 1) : '—' ?></span>
                                <span style="color:#007a33;">✓ <?= $s['aprovadas'] ?></span>
                                <span style="color:#9B2B20;">✗ <?= $s['reprovadas'] ?></span>
                                <svg class="year-arrow" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 5l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>
                        <div class="year-group-body">
                            <table class="notes-table sortable-table">
                                <thead>
                                    <tr>
                                        <th data-type="string">Disciplina</th>
                                        <th data-type="string">Sigla</th>
                                        <th data-type="number" style="width:120px;">Nota</th>
                                        <th data-type="string">Classificação</th>
                                        <th data-type="string">Época</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notas_ano as $n):
                                        $nv = (float)$n['nota'];
                                        $pass = $nv >= 10;
                                        $pct  = ($nv / 20) * 100;
                                        $cls  = classificacao($nv);
                                        $res  = $pass ? 'pass' : 'fail';
                                        $icon = '';
                                        if ($nv == $melhor_nota_val && $pass) $icon = '<span title="Melhor nota" style="color:var(--green);margin-right:4px;">★</span>';
                                        elseif ($nv == $pior_nota_val && !$pass) $icon = '<span title="Nota mais baixa" style="color:#C0392B;margin-right:4px;">▲</span>';
                                    ?>
                                    <tr data-result="<?= $res ?>" data-disc="<?= strtolower(htmlspecialchars($n['nome_disciplina'])) ?> <?= strtolower(htmlspecialchars($n['sigla'])) ?>">
                                        <td><?= $icon ?><?= htmlspecialchars($n['nome_disciplina']) ?></td>
                                        <td style="color:var(--text-faint); font-size:12px;"><?= htmlspecialchars($n['sigla']) ?></td>
                                        <td data-value="<?= $nv ?>">
                                            <span class="nota-pill <?= $pass ? 'nota-pass' : 'nota-fail' ?>"><?= $nv ?></span>
                                            &nbsp;
                                            <span class="progress-thin" title="<?= $pct ?>% do máximo">
                                                <span class="progress-fill-thin <?= $pass ? '' : 'fail' ?>" style="--w:<?= $pct ?>%;"></span>
                                            </span>
                                        </td>
                                        <td class="class-text"><?= $cls ?></td>
                                        <td style="color:var(--text-faint); font-size:12.5px;"><?= htmlspecialchars($n['epoca']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>

                <!-- Simulador -->
                <div class="card mt-20" style="margin-top:20px;">
                    <div class="card-body">
                        <div class="section-header mb-16">
                            <span class="section-title">Simulador de Nota</span>
                        </div>
                        <?php if (empty($disciplinas_reprovadas)): ?>
                            <p style="font-size:13px; color:var(--green); text-align:center; padding:20px 0;">
                                Sem disciplinas reprovadas. Excelente!
                            </p>
                        <?php else: ?>
                        <div style="max-width:360px;">
                            <label style="font-size:11.5px; text-transform:uppercase; letter-spacing:.07em; color:var(--text-faint); display:block; margin-bottom:6px;">Disciplina Reprovada</label>
                            <select class="sim-select" id="simDisc">
                                <option value="">— Selecionar —</option>
                                <?php foreach ($disciplinas_reprovadas as $dr): ?>
                                    <option value="<?= $dr['nota'] ?>"><?= htmlspecialchars($dr['nome_disciplina']) ?> (<?= $dr['nota'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <label style="font-size:11.5px; text-transform:uppercase; letter-spacing:.07em; color:var(--text-faint); display:block; margin-bottom:6px;">Nova Nota Hipotética</label>
                            <input type="number" class="sim-input" id="simNota" min="0" max="20" placeholder="0 – 20">
                            <div class="sim-result">
                                <div class="sim-val" id="simVal">—</div>
                                <div class="sim-label" id="simLbl">Nova média global</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php else: ?>
                <div style="text-align:center; padding:60px 20px; color:var(--text-faint);">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1" style="display:block;margin:0 auto 12px;opacity:.3"><rect x="6" y="3" width="28" height="34" rx="3"/><line x1="13" y1="13" x2="27" y2="13"/><line x1="13" y1="20" x2="27" y2="20"/><line x1="13" y1="27" x2="21" y2="27"/></svg>
                    Sem notas registadas.
                </div>
                <?php endif; ?>

            </div><!-- /section-notas -->

            <!-- ══ SECTION: FICHA ════════════════════════════════════════ -->
            <div id="section-ficha" style="display:none;">
                <div class="section-header mb-20">
                    <span class="section-title">Ficha de Aluno</span>
                    <?php if ($pode_editar): ?>
                        <a href="submeter_matricula.php" class="section-action">Editar ficha</a>
                    <?php endif; ?>
                </div>

                <div class="card mb-24">
                    <div class="card-body">
                        <div class="data-grid">
                            <div class="data-row">
                                <span class="data-label">Nome Completo</span>
                                <span class="data-value"><?= htmlspecialchars($aluno['nome']) ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Email Institucional</span>
                                <span class="data-value"><?= htmlspecialchars($aluno['email']) ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Morada</span>
                                <span class="data-value"><?= htmlspecialchars($aluno['morada'] ?? '—') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Telefone</span>
                                <span class="data-value"><?= htmlspecialchars($aluno['telefone'] ?? '—') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Data de Nascimento</span>
                                <span class="data-value">
                                    <?= !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '—' ?>
                                </span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Curso</span>
                                <span class="data-value"><?= htmlspecialchars($aluno['curso_nome'] ?? '—') ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($estado === 'Submetida' || $estado === 'Aprovada'): ?>
                    <div class="card-body" style="background:var(--gray-50);">
                        <p style="font-size:12.5px; color:var(--text-faint);">
                            A edição está bloqueada porque a ficha se encontra no estado <strong style="color:var(--text-muted);"><?= $estado ?></strong>.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($aluno['data_validacao']) || in_array($estado, ['Submetida','Aprovada','Rejeitada'])): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="section-header mb-16">
                            <span class="section-title">Validação</span>
                        </div>
                        <table class="history-table">
                            <tr><td class="col-label">Estado atual</td>
                                <td><span class="status-badge <?= $stateClass ?>"><?= htmlspecialchars($estado) ?></span></td></tr>
                            <tr><td class="col-label">Gestor</td>
                                <td style="font-size:13px;"><?= htmlspecialchars($aluno['validado_por_nome'] ?? '—') ?></td></tr>
                            <tr><td class="col-label">Data</td>
                                <td style="font-size:13px;"><?= !empty($aluno['data_validacao']) ? date('d/m/Y H:i', strtotime($aluno['data_validacao'])) : '—' ?></td></tr>
                            <tr><td class="col-label">Observações</td>
                                <td style="font-size:13px; <?= $estado==='Rejeitada'?'color:#9B2B20;':'' ?>"><?= htmlspecialchars($aluno['observacoes_validacao'] ?? '—') ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div><!-- /section-ficha -->

            <!-- ══ SECTION: MATRÍCULAS ═══════════════════════════════════ -->
            <div id="section-matriculas" style="display:none;">
                <div class="section-header mb-20">
                    <span class="section-title">Pedidos de Matrícula</span>
                </div>

                <?php if (!empty($pedidos)): ?>
                <div class="card">
                    <div style="overflow-x:auto;">
                        <table class="pedidos-table">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Ano Letivo</th>
                                    <th>Estado</th>
                                    <th>Data do Pedido</th>
                                    <th>Data da Decisão</th>
                                    <th>Funcionário</th>
                                    <th>Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $p):
                                    $pState = match($p['estado']) {
                                        'Aprovado'  => 'state-aprovada',
                                        'Rejeitado' => 'state-rejeitada',
                                        default     => 'state-rascunho',
                                    };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['curso_nome'] ?? '—') ?></td>
                                    <td style="font-size:12.5px; color:var(--text-muted);"><?= htmlspecialchars($p['ano_letivo'] ?? '—') ?></td>
                                    <td><span class="status-badge <?= $pState ?>"><?= htmlspecialchars($p['estado']) ?></span></td>
                                    <td style="font-size:12.5px; color:var(--text-faint);"><?= date('d/m/Y', strtotime($p['data_pedido'])) ?></td>
                                    <td style="font-size:12.5px; color:var(--text-faint);"><?= !empty($p['data_decisao']) ? date('d/m/Y', strtotime($p['data_decisao'])) : '—' ?></td>
                                    <td style="font-size:12.5px;"><?= htmlspecialchars($p['funcionario_nome'] ?? '—') ?></td>
                                    <td style="font-size:12.5px; color:var(--text-faint);"><?= htmlspecialchars($p['observacoes'] ?? '—') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:60px 20px; color:var(--text-faint); font-size:13px;">
                    Sem pedidos de matrícula registados.
                </div>
                <?php endif; ?>
            </div><!-- /section-matriculas -->

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div><!-- /app-shell -->

<!-- ══ CERTIFICADO (só em print) ════════════════════════════════════════ -->
<div id="cert-container">
    <div class="cert-inner">
        <div class="cert-title">Certificado de Matrícula</div>
        <div class="cert-body">
            Declaram os Serviços Académicos que<br>
            <strong><?= htmlspecialchars($aluno['nome']) ?></strong><br>
            com o endereço de email <em><?= htmlspecialchars($aluno['email']) ?></em><br>
            se encontra regularmente matriculado(a) no curso de<br>
            <strong><?= htmlspecialchars($aluno['curso_nome'] ?? 'Curso não atribuído') ?></strong><br>
            durante o presente ano letivo.
        </div>
        <div class="cert-footer">
            <p>Emitido eletronicamente em <?= date('d/m/Y') ?></p>
            <div class="cert-sig">Serviços Académicos — IPCA</div>
        </div>
    </div>
</div>

<script>
const chartLabels  = <?= json_encode($chart_anos) ?>;
const chartData    = <?= json_encode($chart_medias) ?>;
const totalSoma    = <?= (float)$soma_notas ?>;
const totalDiscs   = <?= (int)$total_notas ?>;

/* ── Navigation ── */
function showSection(id) {
    ['dashboard','notas','ficha','matriculas'].forEach(s => {
        document.getElementById('section-' + s).style.display = s === id ? '' : 'none';
    });
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.toggle('active', el.getAttribute('onclick') === `showSection('${id}')`);
    });
}

/* ── Year Group Toggle ── */
function toggleYear(el) { el.classList.toggle('collapsed'); }

/* ── Print Certificate ── */
function printCert() {
    document.body.classList.add('print-cert');
    window.print();
    setTimeout(() => document.body.classList.remove('print-cert'), 1200);
}

/* ── Send Email ── */
function sendEmail() {
    const btn = document.getElementById('btnEmail');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = 'A enviar…';
    fetch('enviar_notas_process.php', { method: 'POST' })
        .then(r => r.json())
        .then(d => alert(d.success ? '✓ Notas enviadas para o seu email!' : '✗ Erro: ' + d.message))
        .catch(() => alert('✗ Erro de comunicação.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
}

document.addEventListener('DOMContentLoaded', () => {

    /* ── Animated counters ── */
    document.querySelectorAll('.counter').forEach(el => {
        const target = parseFloat(el.dataset.target);
        const isFloat = el.dataset.float === '1';
        const dur = 800, fps = 60, steps = dur / (1000 / fps);
        let cur = 0, frame = 0;
        const ease = t => 1 - Math.pow(1 - t, 3);
        const tick = () => {
            frame++;
            cur = target * ease(Math.min(frame / steps, 1));
            el.textContent = isFloat ? cur.toFixed(2) : Math.round(cur);
            if (frame < steps) requestAnimationFrame(tick);
            else el.textContent = isFloat ? target.toFixed(2) : target;
        };
        setTimeout(tick, 100);
    });

    /* ── Chart.js ── */
    if (document.getElementById('evolucaoChart') && chartLabels.length > 0) {
        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
        const tickColor = isDark ? '#888' : '#9A9A9A';

        new Chart(document.getElementById('evolucaoChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Média',
                    data: chartData,
                    borderColor: '#007a33',
                    backgroundColor: 'rgba(0,122,51,0.06)',
                    borderWidth: 1.5,
                    pointBackgroundColor: '#007a33',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    fill: true, tension: 0.35
                }]
            },
options: {
    responsive: true,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1A1A1A',
            titleColor: '#EEEEEE',
            bodyColor: '#9A9A9A',
            padding: 10, cornerRadius: 6,
            callbacks: { label: ctx => ' Média: ' + ctx.parsed.y.toFixed(2) }
        }
    },
    scales: {
        y: {
            min: 0, max: 20,
            ticks: { stepSize: 5, color: '#9A9A9A', font: { size: 11 } },
            grid: { color: 'rgba(0,0,0,0.05)' }
        },
        x: {
            ticks: { color: '#9A9A9A', font: { size: 11 } },
            grid: { display: false }
        }
    }
}
        });
    }

    /* ── Filters ── */
    const searchInput  = document.getElementById('searchDiscipline');
    const yearSelect   = document.getElementById('filterYear');
    const resultSelect = document.getElementById('filterResult');

    function applyFilters() {
        const q   = searchInput  ? searchInput.value.toLowerCase()  : '';
        const yr  = yearSelect   ? yearSelect.value                  : 'all';
        const res = resultSelect ? resultSelect.value                : 'all';

        document.querySelectorAll('.year-group').forEach(group => {
            if (yr !== 'all' && group.dataset.year !== yr) {
                group.style.display = 'none'; return;
            }
            let anyVisible = false;
            group.querySelectorAll('tbody tr').forEach(row => {
                const disc   = row.dataset.disc || '';
                const result = row.dataset.result || 'all';
                const show   = disc.includes(q) && (res === 'all' || result === res);
                row.style.display = show ? '' : 'none';
                if (show) anyVisible = true;
            });
            group.style.display = anyVisible ? '' : 'none';
            if (q && anyVisible) group.classList.remove('collapsed');
        });
    }

    if (searchInput)  searchInput.addEventListener('input',  applyFilters);
    if (yearSelect)   yearSelect.addEventListener('change',  applyFilters);
    if (resultSelect) resultSelect.addEventListener('change', applyFilters);

    /* ── Sortable table headers ── */
    document.querySelectorAll('.sortable-table thead th').forEach((th, i) => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const tbody = th.closest('table').querySelector('tbody');
            const rows  = [...tbody.querySelectorAll('tr')];
            const asc   = th.dataset.dir !== 'asc';
            th.closest('thead').querySelectorAll('th').forEach(h => delete h.dataset.dir);
            th.dataset.dir = asc ? 'asc' : 'desc';

            rows.sort((a, b) => {
                const ca = a.cells[i], cb = b.cells[i];
                const va = th.dataset.type === 'number' ? parseFloat(ca.dataset.value ?? ca.textContent) : ca.textContent.trim().toLowerCase();
                const vb = th.dataset.type === 'number' ? parseFloat(cb.dataset.value ?? cb.textContent) : cb.textContent.trim().toLowerCase();
                return va < vb ? (asc ? -1 : 1) : va > vb ? (asc ? 1 : -1) : 0;
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });

    /* ── Simulador ── */
    const simDisc = document.getElementById('simDisc');
    const simNota = document.getElementById('simNota');
    const simVal  = document.getElementById('simVal');
    const simLbl  = document.getElementById('simLbl');

    function calcSim() {
        if (!simDisc || !simNota) return;
        const oldN = parseFloat(simDisc.value), newN = parseFloat(simNota.value);
        if (isNaN(oldN) || isNaN(newN) || totalDiscs === 0) {
            if (simVal) simVal.textContent = '—';
            if (simLbl) simLbl.textContent = 'Nova média global';
            return;
        }
        const nova = ((totalSoma - oldN) + newN) / totalDiscs;
        if (simVal) {
            simVal.textContent = nova.toFixed(2);
            simVal.style.color = newN >= 10 ? '#007a33' : '#C0392B';
        }
        if (simLbl) simLbl.textContent = newN >= 10 ? 'Aprovado na disciplina!' : 'Continua reprovado.';
    }

    if (simDisc) simDisc.addEventListener('change', calcSim);
    if (simNota) simNota.addEventListener('input',  calcSim);
});
</script>

<?php include '../includes/footer.php'; ?>