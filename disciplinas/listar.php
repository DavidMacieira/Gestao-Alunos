<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$mensagem = "";
$modo     = $_GET['acao'] ?? 'listar';
$edit_id  = (int)($_GET['id'] ?? 0);

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao  = $_POST['acao']  ?? '';
    $nome  = trim($_POST['nome_disciplina'] ?? '');
    $sigla = trim($_POST['sigla'] ?? '');
    $id    = (int)($_POST['id'] ?? 0);

    switch ($acao) {
        case 'criar':
            $erros = [];
            if (empty($nome))  $erros[] = "O nome é obrigatório.";
            if (empty($sigla)) $erros[] = "A sigla é obrigatória.";
            $dup = $pdo->prepare("SELECT id FROM disciplinas WHERE nome_disciplina=? OR sigla=?");
            $dup->execute([$nome, $sigla]);
            if ($dup->fetch()) $erros[] = "Já existe uma disciplina com esse nome ou sigla.";
            if (empty($erros)) {
                $pdo->prepare("INSERT INTO disciplinas (nome_disciplina, sigla) VALUES (?,?)")->execute([$nome, $sigla]);
                $_SESSION['flash_success'] = "Disciplina «{$nome}» criada.";
                header("Location: listar.php"); exit;
            }
            $mensagem = implode('<br>', $erros); $modo = 'criar';
            break;

        case 'editar':
            $erros = [];
            if (empty($nome))  $erros[] = "O nome é obrigatório.";
            if (empty($sigla)) $erros[] = "A sigla é obrigatória.";
            $dup = $pdo->prepare("SELECT id FROM disciplinas WHERE (nome_disciplina=? OR sigla=?) AND id!=?");
            $dup->execute([$nome, $sigla, $id]);
            if ($dup->fetch()) $erros[] = "Já existe outra disciplina com esse nome ou sigla.";
            if (empty($erros)) {
                $pdo->prepare("UPDATE disciplinas SET nome_disciplina=?, sigla=? WHERE id=?")->execute([$nome, $sigla, $id]);
                $_SESSION['flash_success'] = "Disciplina atualizada.";
                header("Location: listar.php"); exit;
            }
            $mensagem = implode('<br>', $erros); $modo = 'editar'; $edit_id = $id;
            break;

        case 'remover':
            $temNotas = $pdo->prepare("SELECT COUNT(*) FROM notas WHERE disciplina_id=?");
            $temNotas->execute([$id]);
            if ($temNotas->fetchColumn() > 0) {
                $_SESSION['flash_error'] = "Não é possível remover — existem notas associadas a esta disciplina.";
            } else {
                $pdo->prepare("DELETE FROM plano_estudos WHERE disciplina_id=?")->execute([$id]);
                $pdo->prepare("DELETE FROM disciplinas WHERE id=?")->execute([$id]);
                $_SESSION['flash_success'] = "Disciplina removida.";
            }
            header("Location: listar.php"); exit;
    }
}

$disciplinas = $pdo->query("
    SELECT d.*, COUNT(DISTINCT pe.id) AS em_cursos, COUNT(DISTINCT n.id) AS total_notas
    FROM disciplinas d
    LEFT JOIN plano_estudos pe ON pe.disciplina_id = d.id
    LEFT JOIN notas n ON n.disciplina_id = d.id
    GROUP BY d.id ORDER BY d.nome_disciplina ASC
")->fetchAll(PDO::FETCH_ASSOC);

$disc_edit = null;
if ($modo === 'editar' && $edit_id > 0) {
    $s = $pdo->prepare("SELECT * FROM disciplinas WHERE id=?");
    $s->execute([$edit_id]);
    $disc_edit = $s->fetch(PDO::FETCH_ASSOC);
    if (!$disc_edit) $modo = 'listar';
}

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1000px; margin:0 auto; }
.page-hd { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.page-hd p  { font-size:13px; color:var(--text-muted); margin-top:4px; }
.grid-2 { display:grid; grid-template-columns:2fr 1fr; gap:20px; align-items:start; }
.form-card { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.form-card-head { padding:18px 24px; border-bottom:var(--border); }
.form-card-head h3 { font-size:14px; font-weight:500; color:var(--black); margin:0; }
.form-card-head p  { font-size:12px; color:var(--text-muted); margin-top:3px; }
.form-card-body { padding:24px; }
.btn-row { display:flex; gap:10px; margin-top:20px; }
.disc-row { display:flex; align-items:center; gap:12px; padding:11px 16px; border:var(--border); border-radius:var(--radius-md); background:var(--white); margin-bottom:6px; transition:border-color .15s; }
.disc-row:hover { border-color:var(--gray-200); }
.disc-pill { display:inline-flex; align-items:center; justify-content:center; padding:3px 10px; border-radius:4px; background:var(--gray-50); border:var(--border); font-size:11px; font-weight:500; color:var(--text-muted); font-family:monospace; flex-shrink:0; min-width:50px; }
.search-bar { display:flex; gap:8px; margin-bottom:14px; }
.search-bar input { height:36px; padding:0 12px; border:var(--border); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13px; outline:none; flex:1; transition:border-color .15s; background:var(--white); color:var(--black); }
.search-bar input:focus { border-color:var(--gray-400); }
@media(max-width:700px){ .grid-2{grid-template-columns:1fr;} .page-hd{flex-direction:column;align-items:flex-start;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Disciplinas</h1>
            <p>CRUD de Unidades Curriculares — Admin</p>
        </div>
        <?php if ($modo !== 'criar'): ?>
            <a href="listar.php?acao=criar" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
                Nova Disciplina
            </a>
        <?php endif; ?>
    </div>

    <div class="grid-2">
        <!-- Lista -->
        <div>
            <div class="search-bar">
                <input type="text" id="searchDisc" placeholder="Filtrar por nome ou sigla..." oninput="filtrar(this.value)">
            </div>
            <div id="discList">
                <?php if (empty($disciplinas)): ?>
                    <div style="text-align:center;padding:40px;color:var(--text-faint);font-size:13px;">Nenhuma disciplina criada.</div>
                <?php else: ?>
                    <?php foreach ($disciplinas as $d): ?>
                    <div class="disc-row" data-search="<?= strtolower(htmlspecialchars($d['nome_disciplina'].' '.$d['sigla'])) ?>">
                        <span class="disc-pill"><?= htmlspecialchars($d['sigla']) ?></span>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13.5px;font-weight:500;color:var(--black);"><?= htmlspecialchars($d['nome_disciplina']) ?></div>
                            <div style="font-size:12px;color:var(--text-faint);">Em <?= (int)$d['em_cursos'] ?> curso<?= $d['em_cursos']!=1?'s':'' ?> · <?= (int)$d['total_notas'] ?> nota<?= $d['total_notas']!=1?'s':'' ?></div>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <a href="listar.php?acao=editar&id=<?= (int)$d['id'] ?>" class="btn btn-sm" title="Editar">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remover «<?= htmlspecialchars(addslashes($d['nome_disciplina'])) ?>»?')">
                                <input type="hidden" name="acao" value="remover">
                                <input type="hidden" name="id"   value="<?= (int)$d['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="color:#9B2B20;" <?= $d['total_notas']>0?'disabled title="Tem notas associadas"':'' ?>>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="3,6 4.5,15 11.5,15 13,6"/><line x1="1" y1="6" x2="15" y2="6"/><path d="M6 6V4a2 2 0 014 0v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulário -->
        <div class="form-card">
            <div class="form-card-head">
                <h3><?= $modo==='editar'?'Editar Disciplina':'Nova Disciplina' ?></h3>
                <p><?= $modo==='editar'?'Altere os dados e guarde.':'Preencha os dados da UC.' ?></p>
            </div>
            <div class="form-card-body">
                <?php if ($mensagem): ?><div class="alert alert-danger" style="margin-bottom:16px;"><?= $mensagem ?></div><?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="acao" value="<?= $modo==='editar'?'editar':'criar' ?>">
                    <?php if ($modo==='editar'): ?><input type="hidden" name="id" value="<?= (int)$edit_id ?>"><?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Nome da Disciplina <span class="req">*</span></label>
                        <input type="text" name="nome_disciplina" class="form-control"
                               value="<?= htmlspecialchars($disc_edit['nome_disciplina'] ?? '') ?>"
                               placeholder="Ex: Programação Web" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sigla <span class="req">*</span></label>
                        <input type="text" name="sigla" class="form-control"
                               value="<?= htmlspecialchars($disc_edit['sigla'] ?? '') ?>"
                               placeholder="Ex: PW" maxlength="20" required>
                    </div>

                    <div class="btn-row">
                        <a href="listar.php" class="btn" style="text-decoration:none;flex:0;white-space:nowrap;">Cancelar</a>
                        <button type="submit" class="btn btn-primary" style="flex:1;">
                            <?= $modo==='editar'?'Guardar':'Criar Disciplina' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function filtrar(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#discList .disc-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
}
</script>
<?php include '../includes/footer.php'; ?>