<?php
// cursos/listar.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao  = $_POST['acao']  ?? '';
    $nome  = trim($_POST['nome']  ?? '');
    $sigla = trim($_POST['sigla'] ?? '');
    $id    = (int)($_POST['id'] ?? 0);

    switch ($acao) {
        case 'criar':
            $erros = [];
            if (empty($nome))  $erros[] = "Nome obrigatório.";
            if (empty($sigla)) $erros[] = "Sigla obrigatória.";
            $dup = $pdo->prepare("SELECT id FROM cursos WHERE nome=? OR sigla=?");
            $dup->execute([$nome, $sigla]);
            if ($dup->fetch()) $erros[] = "Já existe um curso com esse nome ou sigla.";

            // Upload horário PDF
            $horario_pdf = null;
            if (isset($_FILES['horario_pdf']) && $_FILES['horario_pdf']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['horario_pdf']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf','jpg','png','doc','docx'])) {
                    $pasta = "../uploads/horarios/";
                    if (!is_dir($pasta)) mkdir($pasta, 0755, true);
                    $fn = "horario_" . time() . ".{$ext}";
                    if (move_uploaded_file($_FILES['horario_pdf']['tmp_name'], $pasta.$fn)) $horario_pdf = $pasta.$fn;
                } else { $erros[] = "Formato inválido para o horário."; }
            }

            if (empty($erros)) {
                $pdo->prepare("INSERT INTO cursos (nome, sigla, horario_pdf) VALUES (?,?,?)")->execute([$nome, $sigla, $horario_pdf]);
                $_SESSION['flash_success'] = "Curso «{$nome}» criado.";
                header("Location: listar.php"); exit;
            }
            $mensagem = implode('<br>', $erros); $modo = 'criar';
            break;

        case 'editar':
            $erros = [];
            if (empty($nome))  $erros[] = "Nome obrigatório.";
            if (empty($sigla)) $erros[] = "Sigla obrigatória.";
            $dup = $pdo->prepare("SELECT id FROM cursos WHERE (nome=? OR sigla=?) AND id!=?");
            $dup->execute([$nome, $sigla, $id]);
            if ($dup->fetch()) $erros[] = "Já existe outro curso com esse nome ou sigla.";

            $horario_pdf = $_POST['horario_pdf_atual'] ?? null;
            if (isset($_FILES['horario_pdf']) && $_FILES['horario_pdf']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['horario_pdf']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf','jpg','png','doc','docx'])) {
                    $pasta = "../uploads/horarios/";
                    if (!is_dir($pasta)) mkdir($pasta, 0755, true);
                    $fn = "horario_{$id}_" . time() . ".{$ext}";
                    if (move_uploaded_file($_FILES['horario_pdf']['tmp_name'], $pasta.$fn)) $horario_pdf = $pasta.$fn;
                }
            }

            if (empty($erros)) {
                $pdo->prepare("UPDATE cursos SET nome=?, sigla=?, horario_pdf=? WHERE id=?")->execute([$nome, $sigla, $horario_pdf, $id]);
                $_SESSION['flash_success'] = "Curso atualizado.";
                header("Location: listar.php"); exit;
            }
            $mensagem = implode('<br>', $erros); $modo = 'editar'; $edit_id = $id;
            break;

        case 'eliminar':
            $check = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE curso_id=?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                $_SESSION['flash_error'] = "Não é possível eliminar — existem alunos neste curso.";
            } else {
                $pdo->prepare("DELETE FROM plano_estudos WHERE curso_id=?")->execute([$id]);
                $pdo->prepare("DELETE FROM cursos WHERE id=?")->execute([$id]);
                $_SESSION['flash_success'] = "Curso eliminado.";
            }
            header("Location: listar.php"); exit;
    }
}

$cursos = $pdo->query("
    SELECT c.*, COUNT(DISTINCT a.id) AS total_alunos, COUNT(DISTINCT pe.id) AS total_ucs
    FROM cursos c
    LEFT JOIN alunos a ON a.curso_id=c.id
    LEFT JOIN plano_estudos pe ON pe.curso_id=c.id
    GROUP BY c.id ORDER BY c.nome
")->fetchAll(PDO::FETCH_ASSOC);

$curso_edit = null;
if ($modo === 'editar' && $edit_id > 0) {
    $s = $pdo->prepare("SELECT * FROM cursos WHERE id=?");
    $s->execute([$edit_id]);
    $curso_edit = $s->fetch(PDO::FETCH_ASSOC);
    if (!$curso_edit) $modo = 'listar';
}

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1000px; margin:0 auto; }
.page-hd { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); }
.grid-2 { display:grid; grid-template-columns:2fr 1fr; gap:20px; align-items:start; }
.form-card { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.form-card-head { padding:18px 24px; border-bottom:var(--border); }
.form-card-head h3 { font-size:14px; font-weight:500; color:var(--black); }
.form-card-head p  { font-size:12px; color:var(--text-muted); margin-top:3px; }
.form-card-body { padding:24px; }
.curso-row { display:flex; align-items:center; gap:12px; padding:12px 16px; border:var(--border); border-radius:var(--radius-md); background:var(--white); margin-bottom:6px; transition:border-color .15s; }
.curso-row:hover { border-color:var(--gray-200); }
.curso-init { width:36px; height:36px; border-radius:8px; background:var(--black); color:white; font-size:11px; font-weight:500; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.btn-row { display:flex; gap:10px; margin-top:20px; }
.select-wrap { position:relative; }
.select-wrap::after { content:''; position:absolute; right:12px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--gray-400); pointer-events:none; }
.file-label { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border:var(--border); border-radius:var(--radius-sm); font-size:13px; color:var(--text-muted); cursor:pointer; background:var(--white); transition:all .15s; width:100%; }
#horarioInput { position:absolute; width:1px; height:1px; opacity:0; }
@media(max-width:700px){ .grid-2{grid-template-columns:1fr;} .page-hd{flex-direction:column;align-items:flex-start;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <div><h1>Cursos</h1><p style="font-size:13px;color:var(--text-muted);margin-top:4px;">CRUD — Admin</p></div>
        <?php if ($modo !== 'criar'): ?>
            <a href="listar.php?acao=criar" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
                Novo Curso
            </a>
        <?php endif; ?>
    </div>

    <div class="grid-2">
        <div>
            <?php if (empty($cursos)): ?>
                <div style="text-align:center;padding:40px;color:var(--text-faint);font-size:13px;">Nenhum curso.</div>
            <?php else: ?>
                <?php foreach ($cursos as $c): ?>
                <div class="curso-row">
                    <div class="curso-init"><?= htmlspecialchars(strtoupper(substr($c['sigla']??$c['nome'],0,3))) ?></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:500;color:var(--black);"><?= htmlspecialchars($c['nome']) ?></div>
                        <div style="font-size:12px;color:var(--text-faint);"><?= htmlspecialchars($c['sigla']??'—') ?> · <?= (int)$c['total_ucs'] ?> UCs · <?= (int)$c['total_alunos'] ?> alunos</div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <?php if (!empty($c['horario_pdf'])): ?>
                            <a href="<?= htmlspecialchars($c['horario_pdf']) ?>" target="_blank" class="btn btn-sm" title="Ver horário">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="1" width="12" height="14" rx="1.5"/><line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="11" y2="8"/></svg>
                            </a>
                        <?php endif; ?>
                        <a href="listar.php?acao=editar&id=<?= (int)$c['id'] ?>" class="btn btn-sm" title="Editar">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                        </a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar «<?= htmlspecialchars(addslashes($c['nome'])) ?>»?')">
                            <input type="hidden" name="acao" value="eliminar">
                            <input type="hidden" name="id"   value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="color:#9B2B20;" <?= $c['total_alunos']>0?'disabled title="Tem alunos associados"':'' ?>>
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="3,6 4.5,15 11.5,15 13,6"/><line x1="1" y1="6" x2="15" y2="6"/><path d="M6 6V4a2 2 0 014 0v2"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="form-card">
            <div class="form-card-head">
                <h3><?= $modo==='editar'?'Editar Curso':'Novo Curso' ?></h3>
            </div>
            <div class="form-card-body">
                <?php if ($mensagem): ?><div class="alert alert-danger" style="margin-bottom:16px;"><?= $mensagem ?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="<?= $modo==='editar'?'editar':'criar' ?>">
                    <?php if ($modo==='editar'): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit_id ?>">
                        <input type="hidden" name="horario_pdf_atual" value="<?= htmlspecialchars($curso_edit['horario_pdf']??'') ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Nome <span class="req">*</span></label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($curso_edit['nome']??'') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sigla <span class="req">*</span></label>
                        <input type="text" name="sigla" class="form-control" value="<?= htmlspecialchars($curso_edit['sigla']??'') ?>" maxlength="20" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Horário (PDF/imagem)</label>
                        <div style="position:relative;">
                            <label class="file-label" for="horarioInput">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="1" width="12" height="14" rx="1.5"/><line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="11" y2="8"/></svg>
                                <span id="horarioLbl"><?= ($modo==='editar' && !empty($curso_edit['horario_pdf'])) ? 'Substituir ficheiro atual' : 'Escolher ficheiro' ?></span>
                            </label>
                            <input type="file" id="horarioInput" name="horario_pdf" accept=".pdf,.jpg,.png,.doc,.docx"
                                   onchange="document.getElementById('horarioLbl').textContent=this.files[0]?.name||'Escolher ficheiro'">
                        </div>
                        <?php if ($modo==='editar' && !empty($curso_edit['horario_pdf'])): ?>
                            <div class="form-hint">Atual: <a href="<?= htmlspecialchars($curso_edit['horario_pdf']) ?>" target="_blank" style="color:var(--green);">Ver ficheiro</a></div>
                        <?php endif; ?>
                    </div>

                    <div class="btn-row">
                        <a href="listar.php" class="btn" style="text-decoration:none;flex:0;">Cancelar</a>
                        <button type="submit" class="btn btn-primary" style="flex:1;"><?= $modo==='editar'?'Guardar':'Criar Curso' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>