<?php
/**
 * pedido_matricula.php
 * RF4 — Pedido de Matrícula/Inscrição (Aluno)
 * Cenário de Demonstração 3: Aluno submete pedido de matrícula/inscrição.
 *
 * Regras:
 *  - Só pode criar pedido se a ficha estiver 'Aprovada' (RF3 obrigatório antes de RF4)
 *  - Não pode ter um pedido 'Pendente' já existente para o mesmo curso/ano letivo
 *  - Estado inicial: Pendente → Aprovado | Rejeitado (pelo Funcionário)
 */
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Aluno') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';

$aluno_id = $_SESSION['user_id'];

// ── Verificar estado da ficha ──
$stmtFicha = $pdo->prepare("
    SELECT a.estado, a.curso_id, u.nome, c.nome AS curso_nome, c.sigla AS curso_sigla
    FROM alunos a
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON a.curso_id = c.id
    WHERE a.id = ?
");
$stmtFicha->execute([$aluno_id]);
$aluno = $stmtFicha->fetch(PDO::FETCH_ASSOC);

if (!$aluno) die("Erro: Perfil de aluno não encontrado.");

// ── Carregar cursos disponíveis ──
$stmtCursos = $pdo->query("SELECT id, nome, sigla FROM cursos ORDER BY nome ASC");
$cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

// ── Carregar pedidos anteriores ──
$stmtPedidos = $pdo->prepare("
    SELECT pm.id, pm.ano_letivo, pm.estado, pm.data_pedido,
           pm.observacoes, pm.data_decisao,
           c.nome AS curso_nome,
           u.nome AS funcionario_nome
    FROM pedidos_matricula pm
    LEFT JOIN cursos c ON pm.curso_id = c.id
    LEFT JOIN utilizadores u ON pm.funcionario_id = u.id
    WHERE pm.aluno_id = ?
    ORDER BY pm.data_pedido DESC
");
$stmtPedidos->execute([$aluno_id]);
$pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

// Ano letivo atual (calculado automaticamente)
$mes = (int)date('n');
$ano = (int)date('Y');
$ano_letivo_atual = $mes >= 9
    ? $ano . '/' . ($ano + 1)
    : ($ano - 1) . '/' . $ano;

$mensagem  = "";
$tipo_msg  = "danger";
$ficha_ok  = $aluno['estado'] === 'Aprovada';

// ── Processar POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ficha_ok) {

    $curso_id   = (int)($_POST['curso_id']   ?? 0);
    $ano_letivo = trim($_POST['ano_letivo']   ?? '');
    $obs        = trim($_POST['observacoes']  ?? '');

    $erros = [];
    if ($curso_id <= 0)      $erros[] = "Deve selecionar um curso.";
    if (empty($ano_letivo))  $erros[] = "O ano letivo é obrigatório.";

    // Validar formato do ano letivo (YYYY/YYYY)
    if (!empty($ano_letivo) && !preg_match('/^\d{4}\/\d{4}$/', $ano_letivo)) {
        $erros[] = "Formato de ano letivo inválido. Use o formato: 2024/2025";
    }

    // Verificar se o curso existe
    if ($curso_id > 0) {
        $chk = $pdo->prepare("SELECT id FROM cursos WHERE id = ?");
        $chk->execute([$curso_id]);
        if (!$chk->fetch()) $erros[] = "Curso inválido.";
    }

    // Verificar pedido duplicado (mesmo aluno, curso e ano letivo, estado Pendente)
    if (empty($erros)) {
        $dupChk = $pdo->prepare("
            SELECT id FROM pedidos_matricula
            WHERE aluno_id = ? AND curso_id = ? AND ano_letivo = ? AND estado = 'Pendente'
        ");
        $dupChk->execute([$aluno_id, $curso_id, $ano_letivo]);
        if ($dupChk->fetch()) {
            $erros[] = "Já existe um pedido pendente para este curso e ano letivo.";
        }
    }

    if (empty($erros)) {
        try {
            $pdo->prepare("
                INSERT INTO pedidos_matricula (aluno_id, curso_id, ano_letivo, estado, observacoes)
                VALUES (?, ?, ?, 'Pendente', ?)
            ")->execute([$aluno_id, $curso_id, $ano_letivo, $obs ?: null]);

            $_SESSION['flash_success'] = "Pedido de matrícula submetido com sucesso! Aguarda aprovação dos Serviços Académicos.";
            header("Location: pedido_matricula.php");
            exit;
        } catch (PDOException $e) {
            $erros[] = "Erro ao registar o pedido: " . $e->getMessage();
        }
    }

    if (!empty($erros)) $mensagem = implode('<br>', $erros);
}

$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

include '../includes/header.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --green:#007a33; --green-mid:#005a26; --green-pale:#e8f5ed; --green-soft:#c8e6d1;
    --white:#fff; --off-white:#F8F9FA; --gray-50:#F4F5F6; --gray-100:#EEEEEE;
    --gray-200:#D8D8D8; --gray-400:#9A9A9A; --black:#1A1A1A; --text-muted:#6A6A6A;
    --border:1px solid #EEEEEE; --radius-sm:6px; --radius-md:10px; --radius-lg:14px;
}
body { font-family:'DM Sans',system-ui,sans-serif; background:var(--off-white); color:var(--black); font-size:14px; line-height:1.6; -webkit-font-smoothing:antialiased; }
a { color:inherit; text-decoration:none; }
.page-wrap { max-width:760px; margin:0 auto; padding:32px 16px 60px; }
.back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); margin-bottom:24px; transition:color .15s; }
.back-link:hover { color:var(--black); }
.back-link svg { width:14px; height:14px; }
.page-heading { margin-bottom:28px; }
.page-eyebrow { font-size:10.5px; font-weight:500; letter-spacing:.1em; text-transform:uppercase; color:var(--gray-400); margin-bottom:6px; }
.page-title { font-size:26px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; line-height:1.2; }
.page-sub { font-size:13px; color:var(--text-muted); margin-top:6px; }
.grid-2 { display:grid; grid-template-columns:2fr 1.2fr; gap:20px; align-items:start; }
.card { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.card-head { padding:20px 24px 18px; border-bottom:var(--border); }
.card-head h3 { font-size:14px; font-weight:500; color:var(--black); }
.card-head p  { font-size:12px; color:var(--text-muted); margin-top:3px; }
.card-body { padding:24px; }
.alert { padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; border-left:3px solid; font-size:13px; line-height:1.5; }
.alert-success { background:var(--green-pale); color:var(--green-mid); border-color:var(--green); }
.alert-danger   { background:#FEF0EF; color:#9B2B20; border-color:#E0604A; }
.alert-warning  { background:#FFFAE6; color:#7A5A00; border-color:#D4A000; }
.alert-info     { background:#E8F2FC; color:#0069B4; border-color:#5599D4; }
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:11px; font-weight:500; color:var(--text-muted); letter-spacing:.06em; text-transform:uppercase; margin-bottom:7px; }
.form-label .req { color:#E0604A; margin-left:2px; }
.form-control {
    width:100%; height:42px; padding:0 14px;
    border:var(--border); border-radius:var(--radius-sm);
    font-family:'DM Sans',sans-serif; font-size:14px; color:var(--black); background:var(--white);
    transition:border-color .15s, box-shadow .15s; outline:none; appearance:none;
}
.form-control:focus { border-color:var(--gray-400); box-shadow:0 0 0 3px rgba(0,122,51,.07); }
.form-control:disabled { background:var(--gray-50); color:var(--gray-400); cursor:not-allowed; }
textarea.form-control { height:auto; padding:12px 14px; resize:vertical; min-height:80px; }
.form-hint { font-size:11.5px; color:var(--gray-400); margin-top:5px; }
.select-wrap { position:relative; }
.select-wrap::after { content:''; position:absolute; right:14px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--gray-400); pointer-events:none; }
.btn-row { display:flex; gap:10px; margin-top:22px; }
.btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:42px; padding:0 20px; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13.5px; font-weight:500; border:var(--border); background:var(--white); color:var(--black); cursor:pointer; transition:all .15s; }
.btn:hover { background:var(--gray-50); }
.btn:active { transform:scale(.98); }
.btn-primary { background:var(--black); color:white; border-color:var(--black); flex:1; }
.btn-primary:hover { background:#333; }
.btn-disabled { opacity:.4; pointer-events:none; cursor:not-allowed; }
.btn svg { width:15px; height:15px; flex-shrink:0; }
/* Bloqueio ficha não aprovada */
.blocked-notice { text-align:center; padding:36px 24px; }
.blocked-icon { width:56px; height:56px; border-radius:50%; background:var(--gray-50); border:var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.blocked-icon svg { width:24px; height:24px; color:var(--gray-400); }
.blocked-title { font-size:18px; font-family:'DM Serif Display',serif; color:var(--black); margin-bottom:8px; }
.blocked-desc { font-size:13px; color:var(--text-muted); max-width:340px; margin:0 auto 20px; line-height:1.6; }
/* Tabela de pedidos */
.pedidos-table { width:100%; border-collapse:collapse; }
.pedidos-table th { padding:10px 14px; text-align:left; font-size:10.5px; font-weight:500; text-transform:uppercase; letter-spacing:.07em; color:var(--gray-400); border-bottom:var(--border); }
.pedidos-table td { padding:13px 14px; border-bottom:var(--border); font-size:13px; vertical-align:middle; }
.pedidos-table tbody tr:last-child td { border-bottom:none; }
.pedidos-table tbody tr:hover td { background:var(--gray-50); }
/* Estado badges */
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
.badge-pendente  { background:#FFFAE6; color:#7A5A00; }
.badge-aprovado  { background:var(--green-pale); color:var(--green-mid); }
.badge-rejeitado { background:#FEF0EF; color:#9B2B20; }
/* Resumo da ficha na sidebar */
.ficha-status-card { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.fscard-head { padding:16px 20px; border-bottom:var(--border); }
.fscard-head h4 { font-size:11px; font-weight:500; letter-spacing:.07em; text-transform:uppercase; color:var(--gray-400); }
.fscard-body { padding:16px 20px; }
.fscard-row { display:flex; justify-content:space-between; align-items:flex-start; padding:9px 0; border-bottom:var(--border); gap:10px; }
.fscard-row:last-child { border-bottom:none; }
.fscard-label { font-size:11.5px; color:var(--text-muted); flex-shrink:0; }
.fscard-value { font-size:12.5px; color:var(--black); text-align:right; font-weight:500; }
.status-dot { display:inline-block; width:7px; height:7px; border-radius:50%; margin-right:5px; }
.dot-aprovada  { background:var(--green); }
.dot-rascunho  { background:#D4A000; }
.dot-submetida { background:#0069B4; }
.dot-rejeitada { background:#C0392B; }
@media (max-width:700px) {
    .grid-2 { grid-template-columns:1fr; }
    .card-body, .card-head { padding:18px 18px; }
}
</style>

<div class="page-wrap">
    <a href="dashboard.php" class="back-link">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
        Voltar ao Dashboard
    </a>

    <div class="page-heading">
        <div class="page-eyebrow">RF4 — Pedido de Matrícula</div>
        <div class="page-title">Inscrição num Curso</div>
        <div class="page-sub">Submeta o seu pedido de matrícula para aprovação pelos Serviços Académicos.</div>
    </div>

    <?php if ($flash_success): ?>
        <div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>

    <div class="grid-2">

        <!-- Coluna principal -->
        <div>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-head">
                    <h3>Novo Pedido</h3>
                    <p>Cenário 3 — Aluno submete pedido de matrícula/inscrição</p>
                </div>
                <div class="card-body">

                    <?php if (!$ficha_ok): ?>
                    <!-- Ficha não está aprovada -->
                    <div class="blocked-notice">
                        <div class="blocked-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r=".5" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="blocked-title">Ficha não aprovada</div>
                        <div class="blocked-desc">
                            Para submeter um pedido de matrícula, a sua ficha de aluno tem de estar
                            <strong>Aprovada</strong> pelo Gestor Pedagógico.
                            <?php if ($aluno['estado'] === 'Rascunho'): ?>
                                <br><br>A sua ficha está em <strong>Rascunho</strong>. Complete e submeta os seus dados primeiro.
                            <?php elseif ($aluno['estado'] === 'Submetida'): ?>
                                <br><br>A sua ficha foi <strong>Submetida</strong> e aguarda validação. Aguarde a aprovação.
                            <?php elseif ($aluno['estado'] === 'Rejeitada'): ?>
                                <br><br>A sua ficha foi <strong>Rejeitada</strong>. Corrija os dados e volte a submeter.
                            <?php endif; ?>
                        </div>
                        <a href="submeter_matricula.php" class="btn btn-primary" style="max-width:240px; text-decoration:none;">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M8 10V2M4 6l4-4 4 4"/><path d="M2 12v1a1 1 0 001 1h10a1 1 0 001-1v-1"/></svg>
                            Ir para a Ficha de Aluno
                        </a>
                    </div>

                    <?php else: ?>
                    <!-- Formulário de pedido -->
                    <?php if ($mensagem): ?>
                        <div class="alert alert-danger"><?= $mensagem ?></div>
                    <?php endif; ?>

                    <form method="POST" id="pedidoForm" novalidate>

                        <!-- Curso -->
                        <div class="form-group">
                            <label class="form-label" for="cursoSelect">Curso <span class="req">*</span></label>
                            <div class="select-wrap">
                                <select name="curso_id" id="cursoSelect" class="form-control" required>
                                    <option value="">— Selecionar curso —</option>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>"
                                            <?= ((int)$aluno['curso_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nome']) ?>
                                            <?= !empty($c['sigla']) ? ' (' . htmlspecialchars($c['sigla']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Ano letivo -->
                        <div class="form-group">
                            <label class="form-label" for="anoLetivo">Ano Letivo <span class="req">*</span></label>
                            <div class="select-wrap">
                                <select name="ano_letivo" id="anoLetivo" class="form-control" required>
                                    <option value="">— Selecionar —</option>
                                    <?php
                                    // Gerar opções dos próximos 3 anos letivos
                                    $ano_base = (int)date('Y');
                                    if ((int)date('n') < 9) $ano_base--;
                                    for ($i = 0; $i <= 2; $i++) {
                                        $a = ($ano_base + $i) . '/' . ($ano_base + $i + 1);
                                        $sel = ($a === $ano_letivo_atual) ? 'selected' : '';
                                        echo "<option value=\"{$a}\" {$sel}>{$a}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-hint">Ano letivo para o qual solicita a matrícula.</div>
                        </div>

                        <!-- Observações (opcional) -->
                        <div class="form-group">
                            <label class="form-label" for="observacoes">Observações</label>
                            <textarea name="observacoes" id="observacoes" class="form-control"
                                      placeholder="Informações adicionais (opcional)..."
                                      maxlength="500"></textarea>
                            <div class="form-hint">Máximo 500 caracteres.</div>
                        </div>

                        <div class="btn-row">
                            <a href="dashboard.php" class="btn" style="text-decoration:none; flex:0; white-space:nowrap;">Cancelar</a>
                            <button type="submit" class="btn btn-primary" onclick="return validarPedido()">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M8 10V2M4 6l4-4 4 4"/><path d="M2 12v1a1 1 0 001 1h10a1 1 0 001-1v-1"/></svg>
                                Submeter Pedido
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Histórico de pedidos -->
            <?php if (!empty($pedidos)): ?>
            <div class="card">
                <div class="card-head">
                    <h3>Histórico de Pedidos</h3>
                    <p>Todos os pedidos submetidos e o seu estado atual</p>
                </div>
                <div style="overflow-x:auto;">
                    <table class="pedidos-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Ano Letivo</th>
                                <th>Estado</th>
                                <th>Data Pedido</th>
                                <th>Decisão</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p):
                                $bc = [
                                    'Pendente'  => 'badge-pendente',
                                    'Aprovado'  => 'badge-aprovado',
                                    'Rejeitado' => 'badge-rejeitado',
                                ][$p['estado']] ?? 'badge-pendente';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($p['curso_nome'] ?? '—') ?></td>
                                <td style="color:var(--text-muted); font-size:12.5px;"><?= htmlspecialchars($p['ano_letivo'] ?? '—') ?></td>
                                <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($p['estado']) ?></span></td>
                                <td style="color:var(--text-muted); font-size:12.5px; white-space:nowrap;">
                                    <?= date('d/m/Y', strtotime($p['data_pedido'])) ?>
                                </td>
                                <td style="color:var(--text-muted); font-size:12.5px; white-space:nowrap;">
                                    <?= !empty($p['data_decisao']) ? date('d/m/Y', strtotime($p['data_decisao'])) : '—' ?>
                                    <?php if (!empty($p['funcionario_nome'])): ?>
                                        <br><span style="font-size:11px; color:var(--gray-400);"><?= htmlspecialchars($p['funcionario_nome']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12.5px; color:<?= $p['estado']==='Rejeitado'?'#9B2B20':'var(--text-muted)' ?>; max-width:180px;">
                                    <?= htmlspecialchars($p['observacoes'] ?? '—') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align:center; padding:40px; color:var(--gray-400); font-size:13px;">
                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1" style="display:block;margin:0 auto 12px;opacity:.35"><rect x="5" y="3" width="26" height="30" rx="3"/><line x1="11" y1="12" x2="25" y2="12"/><line x1="11" y1="18" x2="25" y2="18"/><line x1="11" y1="24" x2="19" y2="24"/></svg>
                    Ainda não submeteu nenhum pedido de matrícula.
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar — Estado da ficha -->
        <div>
            <div class="ficha-status-card">
                <div class="fscard-head">
                    <h4>Estado da sua Ficha</h4>
                </div>
                <div class="fscard-body">
                    <?php
                    $dotClass = [
                        'Aprovada'  => 'dot-aprovada',
                        'Rascunho'  => 'dot-rascunho',
                        'Submetida' => 'dot-submetida',
                        'Rejeitada' => 'dot-rejeitada',
                    ][$aluno['estado']] ?? '';
                    ?>
                    <div class="fscard-row">
                        <span class="fscard-label">Estado</span>
                        <span class="fscard-value">
                            <span class="status-dot <?= $dotClass ?>"></span>
                            <?= htmlspecialchars($aluno['estado']) ?>
                        </span>
                    </div>
                    <div class="fscard-row">
                        <span class="fscard-label">Nome</span>
                        <span class="fscard-value"><?= htmlspecialchars($aluno['nome']) ?></span>
                    </div>
                    <div class="fscard-row">
                        <span class="fscard-label">Curso atual</span>
                        <span class="fscard-value"><?= htmlspecialchars($aluno['curso_nome'] ?? '—') ?></span>
                    </div>
                    <div class="fscard-row">
                        <span class="fscard-label">Pedidos criados</span>
                        <span class="fscard-value"><?= count($pedidos) ?></span>
                    </div>
                    <?php
                    $pendentes  = count(array_filter($pedidos, fn($p) => $p['estado'] === 'Pendente'));
                    $aprovados  = count(array_filter($pedidos, fn($p) => $p['estado'] === 'Aprovado'));
                    ?>
                    <?php if ($pendentes > 0): ?>
                    <div class="fscard-row">
                        <span class="fscard-label">Pendentes</span>
                        <span class="fscard-value" style="color:#7A5A00;"><?= $pendentes ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($aprovados > 0): ?>
                    <div class="fscard-row">
                        <span class="fscard-label">Aprovados</span>
                        <span class="fscard-value" style="color:var(--green-mid);"><?= $aprovados ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$ficha_ok): ?>
            <div style="margin-top:14px; padding:14px 16px; background:var(--white); border:var(--border); border-radius:var(--radius-md); border-left:3px solid #D4A000;">
                <div style="font-size:11.5px; font-weight:500; color:#7A5A00; margin-bottom:5px;">Passos necessários</div>
                <ol style="padding-left:18px; font-size:12.5px; color:var(--text-muted); line-height:2;">
                    <li <?= in_array($aluno['estado'],['Submetida','Aprovada','Rejeitada']) ? 'style="text-decoration:line-through;opacity:.5"' : '' ?>>Preencher ficha de aluno</li>
                    <li <?= in_array($aluno['estado'],['Submetida','Aprovada','Rejeitada']) ? 'style="text-decoration:line-through;opacity:.5"' : '' ?>>Submeter para validação</li>
                    <li <?= $aluno['estado'] === 'Aprovada' ? 'style="text-decoration:line-through;opacity:.5"' : '' ?>>Aguardar aprovação do Gestor</li>
                    <li>Criar pedido de matrícula ← <em>aqui</em></li>
                </ol>
                <a href="submeter_matricula.php" style="display:block; margin-top:12px; font-size:12.5px; color:var(--green); font-weight:500;">Ir para a ficha →</a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function validarPedido() {
    const curso = document.getElementById('cursoSelect').value;
    const ano   = document.getElementById('anoLetivo').value;
    const erros = [];
    if (!curso) erros.push('Selecione um curso.');
    if (!ano)   erros.push('Selecione o ano letivo.');
    if (erros.length) { alert('Por favor corrija:\n\n• ' + erros.join('\n• ')); return false; }
    return confirm('Submeter pedido de matrícula?\n\nO pedido ficará pendente até ser processado pelos Serviços Académicos.');
}
</script>

<?php include '../includes/footer.php'; ?>