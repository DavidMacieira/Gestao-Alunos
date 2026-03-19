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

// Dados auxiliares
$alunos_list    = $pdo->query("SELECT a.id, u.nome FROM alunos a JOIN utilizadores u ON u.id=a.id ORDER BY u.nome")->fetchAll(PDO::FETCH_ASSOC);
$disc_list      = $pdo->query("SELECT id, nome_disciplina, sigla FROM disciplinas ORDER BY nome_disciplina")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao          = $_POST['acao']          ?? '';
    $aluno_id      = (int)($_POST['aluno_id']      ?? 0);
    $disciplina_id = (int)($_POST['disciplina_id'] ?? 0);
    $nota_val      = str_replace(',', '.', trim($_POST['nota'] ?? ''));
    $epoca         = trim($_POST['epoca']    ?? '');
    $ano_letivo    = trim($_POST['ano_letivo'] ?? '');
    $id            = (int)($_POST['id'] ?? 0);

    switch ($acao) {
        case 'criar':
        case 'editar':
            $erros = [];
            if ($aluno_id <= 0)      $erros[] = "Selecione um aluno.";
            if ($disciplina_id <= 0) $erros[] = "Selecione uma disciplina.";
            if (!is_numeric($nota_val) || (float)$nota_val < 0 || (float)$nota_val > 20) $erros[] = "Nota inválida (0–20).";
            if (empty($epoca))       $erros[] = "A época é obrigatória.";
            if (empty($ano_letivo))  $erros[] = "O ano letivo é obrigatório.";

            if (empty($erros)) {
                if ($acao === 'criar') {
                    $pdo->prepare("INSERT INTO notas (aluno_id, disciplina_id, nota, epoca, ano_letivo) VALUES (?,?,?,?,?)")
                        ->execute([$aluno_id, $disciplina_id, (float)$nota_val, $epoca, $ano_letivo]);
                    $_SESSION['flash_success'] = "Nota adicionada com sucesso.";
                } else {
                    $pdo->prepare("UPDATE notas SET aluno_id=?, disciplina_id=?, nota=?, epoca=?, ano_letivo=? WHERE id=?")
                        ->execute([$aluno_id, $disciplina_id, (float)$nota_val, $epoca, $ano_letivo, $id]);
                    $_SESSION['flash_success'] = "Nota atualizada com sucesso.";
                }
                header("Location: listar.php"); exit;
            }
            $mensagem = implode('<br>', $erros);
            $modo = $acao === 'criar' ? 'criar' : 'editar';
            if ($acao === 'editar') $edit_id = $id;
            break;

        case 'eliminar':
            $pdo->prepare("DELETE FROM notas WHERE id=?")->execute([$id]);
            $_SESSION['flash_success'] = "Nota eliminada.";
            header("Location: listar.php"); exit;
    }
}

// Filtros de listagem
$filtro_aluno = (int)($_GET['aluno'] ?? 0);
$filtro_disc  = (int)($_GET['disc']  ?? 0);
$filtro_ano   = trim($_GET['ano']    ?? '');

$where = ["1=1"]; $params = [];
if ($filtro_aluno > 0) { $where[] = "n.aluno_id=?";       $params[] = $filtro_aluno; }
if ($filtro_disc  > 0) { $where[] = "n.disciplina_id=?";  $params[] = $filtro_disc; }
if ($filtro_ano)       { $where[] = "n.ano_letivo=?";      $params[] = $filtro_ano; }

$notas = $pdo->prepare("
    SELECT n.id, n.nota, n.epoca, n.ano_letivo,
           u.nome AS aluno_nome, d.nome_disciplina, d.sigla
    FROM notas n
    JOIN alunos a ON a.id=n.aluno_id
    JOIN utilizadores u ON u.id=a.id
    JOIN disciplinas d ON d.id=n.disciplina_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY n.ano_letivo DESC, u.nome ASC
");
$notas->execute($params);
$notas = $notas->fetchAll(PDO::FETCH_ASSOC);

$anos = $pdo->query("SELECT DISTINCT ano_letivo FROM notas ORDER BY ano_letivo DESC")->fetchAll(PDO::FETCH_COLUMN);

$nota_edit = null;
if ($modo === 'editar' && $edit_id > 0) {
    $s = $pdo->prepare("SELECT * FROM notas WHERE id=?");
    $s->execute([$edit_id]);
    $nota_edit = $s->fetch(PDO::FETCH_ASSOC);
    if (!$nota_edit) $modo = 'listar';
}

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1100px; margin:0 auto; }
.page-hd { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); }
.grid-3-1 { display:grid; grid-template-columns:3fr 1.2fr; gap:20px; align-items:start; }
.filter-bar { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.filter-bar select { height:36px; padding:0 12px; border:var(--border); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13px; outline:none; background:var(--white); color:var(--black); transition:border-color .15s; }
.filter-bar select:focus { border-color:var(--gray-400); }
.form-card { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; position:sticky; top:72px; }
.form-card-head { padding:18px 24px; border-bottom:var(--border); }
.form-card-head h3 { font-size:14px; font-weight:500; color:var(--black); }
.form-card-body { padding:24px; }
.btn-row { display:flex; gap:10px; margin-top:20px; }
.select-wrap { position:relative; }
.select-wrap::after { content:''; position:absolute; right:12px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--gray-400); pointer-events:none; }
@media(max-width:800px){ .grid-3-1{grid-template-columns:1fr;} .form-card{position:static;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <div><h1>Notas</h1><p style="font-size:13px;color:var(--text-muted);margin-top:4px;">CRUD completo — Admin</p></div>
        <?php if ($modo !== 'criar'): ?>
            <a href="listar.php?acao=criar" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
                Nova Nota
            </a>
        <?php endif; ?>
    </div>

    <div class="grid-3-1">
        <!-- Lista -->
        <div>
            <form method="GET">
                <div class="filter-bar">
                    <select name="aluno" onchange="this.form.submit()">
                        <option value="0">Todos os alunos</option>
                        <?php foreach ($alunos_list as $al): ?>
                            <option value="<?= (int)$al['id'] ?>" <?= $filtro_aluno===$al['id']?'selected':'' ?>><?= htmlspecialchars($al['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="disc" onchange="this.form.submit()">
                        <option value="0">Todas as disciplinas</option>
                        <?php foreach ($disc_list as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= $filtro_disc===$d['id']?'selected':'' ?>><?= htmlspecialchars($d['nome_disciplina']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="ano" onchange="this.form.submit()">
                        <option value="">Todos os anos</option>
                        <?php foreach ($anos as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= $filtro_ano===$a?'selected':'' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($filtro_aluno || $filtro_disc || $filtro_ano): ?>
                        <a href="listar.php" class="btn btn-sm">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="card">
                <?php if (empty($notas)): ?>
                    <div style="text-align:center;padding:40px;color:var(--text-faint);font-size:13px;">Nenhuma nota encontrada.</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Disciplina</th>
                            <th>Nota</th>
                            <th>Época</th>
                            <th>Ano Letivo</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notas as $n):
                            $nv = (float)$n['nota'];
                            $bc = $nv >= 10 ? 'badge-aprovada' : 'badge-rejeitada';
                        ?>
                        <tr>
                            <td style="font-size:13px;font-weight:500;"><?= htmlspecialchars($n['aluno_nome']) ?></td>
                            <td>
                                <span style="font-size:11px;font-family:monospace;color:var(--text-faint);"><?= htmlspecialchars($n['sigla']) ?></span>
                                <span style="font-size:13px;margin-left:6px;"><?= htmlspecialchars($n['nome_disciplina']) ?></span>
                            </td>
                            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($n['nota']) ?></span></td>
                            <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($n['epoca']) ?></td>
                            <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($n['ano_letivo']) ?></td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="listar.php?acao=editar&id=<?= (int)$n['id'] ?>" class="btn btn-sm" title="Editar">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar esta nota?')">
                                        <input type="hidden" name="acao" value="eliminar">
                                        <input type="hidden" name="id"   value="<?= (int)$n['id'] ?>">
                                        <button type="submit" class="btn btn-sm" style="color:#9B2B20;">
                                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="3,6 4.5,15 11.5,15 13,6"/><line x1="1" y1="6" x2="15" y2="6"/><path d="M6 6V4a2 2 0 014 0v2"/></svg>
                                        </button>
                                    </form>
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
                <h3><?= $modo==='editar'?'Editar Nota':'Nova Nota' ?></h3>
            </div>
            <div class="form-card-body">
                <?php if ($mensagem): ?><div class="alert alert-danger" style="margin-bottom:16px;"><?= $mensagem ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="acao" value="<?= $modo==='editar'?'editar':'criar' ?>">
                    <?php if ($modo==='editar'): ?><input type="hidden" name="id" value="<?= (int)$edit_id ?>"><?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Aluno <span class="req">*</span></label>
                        <div class="select-wrap">
                            <select name="aluno_id" class="form-control" required>
                                <option value="">— Selecionar —</option>
                                <?php foreach ($alunos_list as $al): ?>
                                    <option value="<?= (int)$al['id'] ?>" <?= ($nota_edit['aluno_id']??0)==$al['id']?'selected':'' ?>><?= htmlspecialchars($al['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Disciplina <span class="req">*</span></label>
                        <div class="select-wrap">
                            <select name="disciplina_id" class="form-control" required>
                                <option value="">— Selecionar —</option>
                                <?php foreach ($disc_list as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>" <?= ($nota_edit['disciplina_id']??0)==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['nome_disciplina']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nota (0–20) <span class="req">*</span></label>
                        <input type="number" name="nota" class="form-control" step="0.25" min="0" max="20"
                               value="<?= htmlspecialchars($nota_edit['nota'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Época <span class="req">*</span></label>
                        <div class="select-wrap">
                            <select name="epoca" class="form-control" required>
                                <option value="">—</option>
                                <?php foreach (['Normal','Recurso','Especial'] as $e): ?>
                                    <option value="<?= $e ?>" <?= ($nota_edit['epoca']??'')===$e?'selected':'' ?>><?= $e ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ano Letivo <span class="req">*</span></label>
                        <input type="text" name="ano_letivo" class="form-control"
                               value="<?= htmlspecialchars($nota_edit['ano_letivo'] ?? '') ?>"
                               placeholder="Ex: 2024/2025" required>
                    </div>
                    <div class="btn-row">
                        <a href="listar.php" class="btn" style="text-decoration:none;flex:0;">Cancelar</a>
                        <button type="submit" class="btn btn-primary" style="flex:1;"><?= $modo==='editar'?'Guardar':'Adicionar' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>