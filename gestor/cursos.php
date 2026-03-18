<?php
/**
 * gestor/cursos.php
 * RF2.1 — CRUD de Cursos (criar, listar, editar, desativar)
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Gestor Pedagógico') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$mensagem = ""; $tipo_msg = "danger";
$modo     = $_GET['acao'] ?? 'listar'; // listar | criar | editar
$edit_id  = (int)($_GET['id'] ?? 0);

// ── Processar POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao  = $_POST['acao']  ?? '';
    $nome  = trim($_POST['nome']  ?? '');
    $sigla = trim($_POST['sigla'] ?? '');
    $id    = (int)($_POST['id'] ?? 0);

    switch ($acao) {
        case 'criar':
            $erros = [];
            if (empty($nome))  $erros[] = "O nome do curso é obrigatório.";
            if (empty($sigla)) $erros[] = "A sigla é obrigatória.";
            // Verificar duplicado
            $dup = $pdo->prepare("SELECT id FROM cursos WHERE nome = ? OR sigla = ?");
            $dup->execute([$nome, $sigla]);
            if ($dup->fetch()) $erros[] = "Já existe um curso com esse nome ou sigla.";
            if (empty($erros)) {
                $pdo->prepare("INSERT INTO cursos (nome, sigla) VALUES (?,?)")->execute([$nome, $sigla]);
                $_SESSION['flash_success'] = "Curso «{$nome}» criado com sucesso.";
                header("Location: cursos.php"); exit;
            }
            $mensagem = implode('<br>', $erros);
            $modo = 'criar';
            break;

        case 'editar':
            $erros = [];
            if (empty($nome))  $erros[] = "O nome do curso é obrigatório.";
            if (empty($sigla)) $erros[] = "A sigla é obrigatória.";
            $dup = $pdo->prepare("SELECT id FROM cursos WHERE (nome = ? OR sigla = ?) AND id != ?");
            $dup->execute([$nome, $sigla, $id]);
            if ($dup->fetch()) $erros[] = "Já existe outro curso com esse nome ou sigla.";
            if (empty($erros)) {
                $pdo->prepare("UPDATE cursos SET nome=?, sigla=? WHERE id=?")->execute([$nome, $sigla, $id]);
                $_SESSION['flash_success'] = "Curso atualizado com sucesso.";
                header("Location: cursos.php"); exit;
            }
            $mensagem = implode('<br>', $erros);
            $modo    = 'editar';
            $edit_id = $id;
            break;

        case 'desativar':
            // Verifica se tem alunos associados
            $check = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE curso_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                $_SESSION['flash_error'] = "Não é possível desativar — existem alunos matriculados neste curso.";
            } else {
                $pdo->prepare("DELETE FROM cursos WHERE id=?")->execute([$id]);
                $_SESSION['flash_success'] = "Curso removido com sucesso.";
            }
            header("Location: cursos.php"); exit;
    }
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Carregar cursos
$cursos = $pdo->query("
    SELECT c.*, COUNT(DISTINCT a.id) AS total_alunos, COUNT(DISTINCT pe.id) AS total_ucs
    FROM cursos c
    LEFT JOIN alunos a ON a.curso_id = c.id
    LEFT JOIN plano_estudos pe ON pe.curso_id = c.id
    GROUP BY c.id
    ORDER BY c.nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Curso a editar
$curso_edit = null;
if ($modo === 'editar' && $edit_id > 0) {
    $s = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
    $s->execute([$edit_id]);
    $curso_edit = $s->fetch(PDO::FETCH_ASSOC);
    if (!$curso_edit) $modo = 'listar';
}

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1000px; margin:0 auto; }
.page-hd { display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px; }
.page-hd h1 { font-size:22px;font-family:'DM Serif Display',serif;font-weight:400;color:var(--black);letter-spacing:-.01em; }
.page-hd p  { font-size:13px;color:var(--text-muted);margin-top:4px; }
.grid-2 { display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start; }
.form-card { background:var(--white);border:var(--border);border-radius:var(--radius-lg);overflow:hidden; }
.form-card-head { padding:18px 24px;border-bottom:var(--border); }
.form-card-head h3 { font-size:14px;font-weight:500;color:var(--black); }
.form-card-head p  { font-size:12px;color:var(--text-muted);margin-top:3px; }
.form-card-body { padding:24px; }
.btn-row { display:flex;gap:10px;margin-top:20px; }
.curso-card { background:var(--white);border:var(--border);border-radius:var(--radius-md);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;transition:border-color .15s; }
.curso-card:hover { border-color:var(--gray-200); }
.curso-initial { width:36px;height:36px;border-radius:8px;background:var(--black);color:white;font-size:12px;font-weight:500;display:flex;align-items:center;justify-content:center;flex-shrink:0;letter-spacing:.03em; }
.curso-info { flex:1;min-width:0; }
.curso-nome { font-size:13.5px;font-weight:500;color:var(--black); }
.curso-meta { font-size:12px;color:var(--text-faint);margin-top:2px; }
.curso-actions { display:flex;gap:6px;flex-shrink:0; }
.empty-state { text-align:center;padding:40px 20px;color:var(--text-faint);font-size:13px; }
@media(max-width:700px){ .grid-2{grid-template-columns:1fr;} .page-hd{flex-direction:column;align-items:flex-start;gap:12px;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Cursos</h1>
            <p>RF2.1 — Criar, editar e desativar cursos</p>
        </div>
        <?php if ($modo !== 'criar'): ?>
            <a href="cursos.php?acao=criar" class="btn btn-primary">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
                Novo Curso
            </a>
        <?php endif; ?>
    </div>

    <div class="grid-2">
        <!-- Lista de cursos -->
        <div>
            <?php if (empty($cursos)): ?>
                <div class="form-card">
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1" style="display:block;margin:0 auto 12px;opacity:.3"><circle cx="18" cy="18" r="15"/><path d="M18 11v7l4 4"/></svg>
                        Nenhum curso criado ainda.
                    </div>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($cursos as $c): ?>
                    <div class="curso-card">
                        <div class="curso-initial"><?= htmlspecialchars(strtoupper(substr($c['sigla'] ?? $c['nome'], 0, 3))) ?></div>
                        <div class="curso-info">
                            <div class="curso-nome"><?= htmlspecialchars($c['nome']) ?></div>
                            <div class="curso-meta">
                                <?= htmlspecialchars($c['sigla'] ?? '—') ?>
                                · <?= (int)$c['total_ucs'] ?> UC<?= $c['total_ucs'] != 1 ? 's' : '' ?>
                                · <?= (int)$c['total_alunos'] ?> aluno<?= $c['total_alunos'] != 1 ? 's' : '' ?>
                            </div>
                        </div>
                        <div class="curso-actions">
                            <a href="plano_estudos.php?curso_id=<?= (int)$c['id'] ?>" class="btn btn-sm" title="Plano de estudos">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M5 3V1M11 3V1M1 7h14"/></svg>
                            </a>
                            <a href="cursos.php?acao=editar&id=<?= (int)$c['id'] ?>" class="btn btn-sm" title="Editar">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remover o curso «<?= htmlspecialchars(addslashes($c['nome'])) ?>»?\nEsta ação não pode ser desfeita.')">
                                <input type="hidden" name="acao" value="desativar">
                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="color:#9B2B20;" title="Remover">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="3,6 4.5,15 11.5,15 13,6"/><line x1="1" y1="6" x2="15" y2="6"/><path d="M6 6V4a2 2 0 014 0v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulário criar / editar -->
        <div class="form-card">
            <div class="form-card-head">
                <h3><?= $modo === 'editar' ? 'Editar Curso' : 'Novo Curso' ?></h3>
                <p><?= $modo === 'editar' ? 'Altere os dados e guarde.' : 'Preencha os dados do novo curso.' ?></p>
            </div>
            <div class="form-card-body">
                <?php if ($mensagem): ?>
                    <div class="alert alert-danger" style="margin-bottom:16px;"><?= $mensagem ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="acao" value="<?= $modo === 'editar' ? 'editar' : 'criar' ?>">
                    <?php if ($modo === 'editar'): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit_id ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="nome">Nome do Curso <span class="req">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-control"
                               value="<?= htmlspecialchars($curso_edit['nome'] ?? ($_POST['nome'] ?? '')) ?>"
                               placeholder="Ex: Desenvolvimento Web e Multimédia" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="sigla">Sigla <span class="req">*</span></label>
                        <input type="text" id="sigla" name="sigla" class="form-control"
                               value="<?= htmlspecialchars($curso_edit['sigla'] ?? ($_POST['sigla'] ?? '')) ?>"
                               placeholder="Ex: DWM" maxlength="20" required>
                        <div class="form-hint">Abreviatura do curso (máx. 20 caracteres).</div>
                    </div>

                    <div class="btn-row">
                        <a href="cursos.php" class="btn" style="flex:0;white-space:nowrap;">Cancelar</a>
                        <button type="submit" class="btn btn-primary" style="flex:1;">
                            <?= $modo === 'editar' ? 'Guardar Alterações' : 'Criar Curso' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>