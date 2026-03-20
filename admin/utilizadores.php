<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$modo    = $_GET['acao'] ?? 'listar';
$edit_id = (int)($_GET['id'] ?? 0);
$mensagem = "";

$perfis = $pdo->query("SELECT id, nome FROM perfis ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// ── Processar POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao      = $_POST['acao']      ?? '';
    $nome      = trim($_POST['nome']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $perfil_id = (int)($_POST['perfil_id'] ?? 0);
    $id        = (int)($_POST['id'] ?? 0);

    switch ($acao) {
        case 'criar':
            $password = trim($_POST['password'] ?? '');
            $erros = [];
            if (empty($nome))     $erros[] = "O nome é obrigatório.";
            if (empty($email))    $erros[] = "O email é obrigatório.";
            if (empty($password)) $erros[] = "A password é obrigatória.";
            if ($perfil_id <= 0)  $erros[] = "Selecione um perfil.";

            $dup = $pdo->prepare("SELECT id FROM utilizadores WHERE email=?");
            $dup->execute([$email]);
            if ($dup->fetch()) $erros[] = "Já existe um utilizador com este email.";

            if (empty($erros)) {
                try {
                    $pdo->beginTransaction();
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT INTO utilizadores (nome, email, password, perfil_id) VALUES (?,?,?,?)")
                        ->execute([$nome, $email, $hash, $perfil_id]);
                    $novo_id = $pdo->lastInsertId();

                    // Se for Aluno, criar registo em alunos também
                    if ($perfil_id == 1) {
                        $pdo->prepare("INSERT INTO alunos (id, estado) VALUES (?, 'Rascunho')")
                            ->execute([$novo_id]);
                    }

                    $pdo->commit();
                    $_SESSION['flash_success'] = "Utilizador «{$nome}» criado com sucesso.";
                    header("Location: utilizadores.php"); exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $erros[] = "Erro: " . $e->getMessage();
                }
            }
            $mensagem = implode('<br>', $erros);
            $modo = 'criar';
            break;

        case 'editar':
            $erros = [];
            if (empty($nome))    $erros[] = "O nome é obrigatório.";
            if (empty($email))   $erros[] = "O email é obrigatório.";
            if ($perfil_id <= 0) $erros[] = "Selecione um perfil.";

            $dup = $pdo->prepare("SELECT id FROM utilizadores WHERE email=? AND id!=?");
            $dup->execute([$email, $id]);
            if ($dup->fetch()) $erros[] = "Já existe outro utilizador com este email.";

            if (empty($erros)) {
                if (!empty($_POST['password'])) {
                    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE utilizadores SET nome=?, email=?, password=?, perfil_id=? WHERE id=?")
                        ->execute([$nome, $email, $hash, $perfil_id, $id]);
                } else {
                    $pdo->prepare("UPDATE utilizadores SET nome=?, email=?, perfil_id=? WHERE id=?")
                        ->execute([$nome, $email, $perfil_id, $id]);
                }
                $_SESSION['flash_success'] = "Utilizador atualizado.";
                header("Location: utilizadores.php"); exit;
            }
            $mensagem = implode('<br>', $erros);
            $modo = 'editar'; $edit_id = $id;
            break;

        case 'eliminar':
            // Não pode eliminar a si próprio
            if ($id === (int)$_SESSION['user_id']) {
                $_SESSION['flash_error'] = "Não pode eliminar a sua própria conta.";
                header("Location: utilizadores.php"); exit;
            }
            $pdo->prepare("DELETE FROM utilizadores WHERE id=?")->execute([$id]);
            $_SESSION['flash_success'] = "Utilizador eliminado.";
            header("Location: utilizadores.php"); exit;
    }
}

// ── Filtros ──
$filtro_q      = trim($_GET['q']      ?? '');
$filtro_perfil = (int)($_GET['perfil'] ?? 0);

$where  = ["1=1"]; $params = [];
if ($filtro_q) {
    $where[]  = "(u.nome LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$filtro_q}%";
    $params[] = "%{$filtro_q}%";
}
if ($filtro_perfil > 0) {
    $where[]  = "u.perfil_id = ?";
    $params[] = $filtro_perfil;
}

$utilizadores = $pdo->prepare("
    SELECT u.id, u.nome, u.email, u.created_at, p.nome AS perfil_nome, p.id AS perfil_id
    FROM utilizadores u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY u.created_at DESC
");
$utilizadores->execute($params);
$utilizadores = $utilizadores->fetchAll(PDO::FETCH_ASSOC);

$counts = $pdo->query("SELECT p.nome, COUNT(u.id) as n FROM perfis p LEFT JOIN utilizadores u ON u.perfil_id=p.id GROUP BY p.id")->fetchAll(PDO::FETCH_ASSOC);

// Utilizador a editar
$user_edit = null;
if ($modo === 'editar' && $edit_id > 0) {
    $s = $pdo->prepare("SELECT * FROM utilizadores WHERE id=?");
    $s->execute([$edit_id]);
    $user_edit = $s->fetch(PDO::FETCH_ASSOC);
    if (!$user_edit) $modo = 'listar';
}

// Cores por perfil
$perfilCores = [
    'Aluno'             => ['bg'=>'#e8f5ed','color'=>'#005a26'],
    'Admin'             => ['bg'=>'#1A1A1A','color'=>'#ffffff'],
    'Funcionário'       => ['bg'=>'#E8F2FC','color'=>'#0069B4'],
    'Gestor Pedagógico' => ['bg'=>'#FFFAE6','color'=>'#7A5A00'],
];

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1100px; margin:0 auto; }
.page-hd { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.page-hd p  { font-size:13px; color:var(--text-muted); margin-top:4px; }
.grid-3-1 { display:grid; grid-template-columns:3fr 1.2fr; gap:20px; align-items:start; }
.stats-strip { display:flex; gap:0; border:var(--border); border-radius:var(--radius-lg); overflow:hidden; background:var(--white); margin-bottom:20px; }
.ss-item { flex:1; padding:14px 20px; border-right:var(--border); text-align:center; }
.ss-item:last-child { border-right:none; }
.ss-val { font-size:22px; font-weight:300; font-family:'DM Serif Display',serif; color:var(--black); }
.ss-lbl { font-size:11px; color:var(--text-faint); text-transform:uppercase; letter-spacing:.06em; margin-top:2px; }
.filter-bar { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.filter-bar input, .filter-bar select { height:36px; padding:0 12px; border:var(--border); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13px; outline:none; transition:border-color .15s; background:var(--white); color:var(--black); }
.filter-bar input { flex:1; min-width:180px; }
.filter-bar input:focus, .filter-bar select:focus { border-color:var(--gray-400); }
.form-card { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; position:sticky; top:72px; }
.form-card-head { padding:18px 24px; border-bottom:var(--border); }
.form-card-head h3 { font-size:14px; font-weight:500; color:var(--black); margin:0; }
.form-card-head p  { font-size:12px; color:var(--text-muted); margin-top:3px; }
.form-card-body { padding:24px; }
.btn-row { display:flex; gap:10px; margin-top:20px; }
.select-wrap { position:relative; }
.select-wrap::after { content:''; position:absolute; right:12px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--gray-400); pointer-events:none; }
.perfil-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
.user-initials { width:32px; height:32px; border-radius:50%; background:var(--black); color:white; font-size:11px; font-weight:500; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.empty-state { text-align:center; padding:50px; color:var(--text-faint); font-size:13px; }
@media(max-width:800px){ .grid-3-1{grid-template-columns:1fr;} .form-card{position:static;} .stats-strip{flex-wrap:wrap;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Utilizadores</h1>
            <p>Gestão de todas as contas do sistema — Admin</p>
        </div>
        <?php if ($modo !== 'criar'): ?>
            <a href="utilizadores.php?acao=criar" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
                Novo Utilizador
            </a>
        <?php endif; ?>
    </div>

    <!-- Stats por perfil -->
    <div class="stats-strip">
        <?php foreach ($counts as $c): ?>
        <div class="ss-item">
            <div class="ss-val"><?= (int)$c['n'] ?></div>
            <div class="ss-lbl"><?= htmlspecialchars($c['nome']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid-3-1">
        <!-- Lista -->
        <div>
            <form method="GET">
                <div class="filter-bar">
                    <input type="text" name="q" value="<?= htmlspecialchars($filtro_q) ?>" placeholder="Pesquisar por nome ou email...">
                    <select name="perfil" onchange="this.form.submit()">
                        <option value="0">Todos os perfis</option>
                        <?php foreach ($perfis as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $filtro_perfil===$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm">Pesquisar</button>
                    <?php if ($filtro_q || $filtro_perfil): ?>
                        <a href="utilizadores.php" class="btn btn-sm">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="card">
                <?php if (empty($utilizadores)): ?>
                    <div class="empty-state">Nenhum utilizador encontrado.</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Utilizador</th>
                            <th>Perfil</th>
                            <th>Registado em</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilizadores as $u):
                            $partes   = explode(' ', $u['nome']);
                            $iniciais = strtoupper(($partes[0][0]??'') . ($partes[count($partes)-1][0]??''));
                            $cor      = $perfilCores[$u['perfil_nome']] ?? ['bg'=>'var(--gray-50)','color'=>'var(--text-muted)'];
                            $isMe     = (int)$u['id'] === (int)$_SESSION['user_id'];
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="user-initials"><?= $iniciais ?></div>
                                    <div>
                                        <div style="font-weight:500;font-size:13px;">
                                            <?= htmlspecialchars($u['nome']) ?>
                                            <?php if ($isMe): ?>
                                                <span style="font-size:10.5px;color:var(--text-faint);font-weight:400;margin-left:4px;">(você)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="perfil-badge" style="background:<?= $cor['bg'] ?>;color:<?= $cor['color'] ?>;">
                                    <?= htmlspecialchars($u['perfil_nome']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-faint);"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="utilizadores.php?acao=editar&id=<?= (int)$u['id'] ?>" class="btn btn-sm" title="Editar">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                                    </a>
                                    <?php if (!$isMe): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar «<?= htmlspecialchars(addslashes($u['nome'])) ?>»? Esta ação não pode ser desfeita.')">
                                        <input type="hidden" name="acao" value="eliminar">
                                        <input type="hidden" name="id"   value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn btn-sm" style="color:#9B2B20;" title="Eliminar">
                                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="3,6 4.5,15 11.5,15 13,6"/><line x1="1" y1="6" x2="15" y2="6"/><path d="M6 6V4a2 2 0 014 0v2"/></svg>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm" disabled style="opacity:.3;" title="Não pode eliminar a sua própria conta">
                                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="4" y="7" width="8" height="7" rx="1"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulário criar/editar -->
        <div class="form-card">
            <div class="form-card-head">
                <h3><?= $modo==='editar'?'Editar Utilizador':'Novo Utilizador' ?></h3>
                <p><?= $modo==='editar'?'Altere os dados e guarde.':'Preencha os dados da nova conta.' ?></p>
            </div>
            <div class="form-card-body">
                <?php if ($mensagem): ?><div class="alert alert-danger" style="margin-bottom:16px;"><?= $mensagem ?></div><?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="acao" value="<?= $modo==='editar'?'editar':'criar' ?>">
                    <?php if ($modo==='editar'): ?><input type="hidden" name="id" value="<?= (int)$edit_id ?>"><?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Nome Completo <span class="req">*</span></label>
                        <input type="text" name="nome" class="form-control"
                               value="<?= htmlspecialchars($user_edit['nome'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($user_edit['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            Password <?= $modo==='editar'?'':'<span class="req">*</span>' ?>
                        </label>
                        <input type="password" name="password" class="form-control"
                               placeholder="<?= $modo==='editar'?'Deixar em branco para não alterar':'' ?>"
                               <?= $modo==='criar'?'required':'' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Perfil <span class="req">*</span></label>
                        <div class="select-wrap">
                            <select name="perfil_id" class="form-control" required>
                                <option value="">— Selecionar —</option>
                                <?php foreach ($perfis as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"
                                        <?= ($user_edit['perfil_id']??0)==$p['id']?'selected':'' ?>>
                                        <?= htmlspecialchars($p['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($modo==='criar'): ?>
                            <div class="form-hint">Se selecionar "Aluno", o registo na tabela de alunos é criado automaticamente.</div>
                        <?php endif; ?>
                    </div>

                    <div class="btn-row">
                        <a href="utilizadores.php" class="btn" style="text-decoration:none;flex:0;white-space:nowrap;">Cancelar</a>
                        <button type="submit" class="btn btn-primary" style="flex:1;">
                            <?= $modo==='editar'?'Guardar':'Criar Conta' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>