<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: listar.php"); exit; }

$cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Carregar dados
$stmt = $pdo->prepare("
    SELECT a.*, u.nome, u.email, c.nome AS curso_nome
    FROM alunos a JOIN utilizadores u ON u.id=a.id LEFT JOIN cursos c ON c.id=a.curso_id
    WHERE a.id=?
");
$stmt->execute([$id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$aluno) { header("Location: listar.php"); exit; }

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $morada    = trim($_POST['morada']    ?? '');
    $telefone  = trim($_POST['telefone']  ?? '');
    $data_nasc = trim($_POST['data_nascimento'] ?? '');
    $curso_id  = (int)($_POST['curso_id'] ?? 0);
    $estado    = $_POST['estado'] ?? $aluno['estado'];

    $erros = [];
    if (empty($nome))  $erros[] = "O nome é obrigatório.";
    if (empty($email)) $erros[] = "O email é obrigatório.";

    // Email duplicado (excluindo o atual)
    $dup = $pdo->prepare("SELECT id FROM utilizadores WHERE email=? AND id!=?");
    $dup->execute([$email, $id]);
    if ($dup->fetch()) $erros[] = "Já existe outro utilizador com este email.";

    if (empty($erros)) {
        try {
            // Atualizar utilizadores
            if (!empty($_POST['password'])) {
                $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE utilizadores SET nome=?, email=?, password=? WHERE id=?")
                    ->execute([$nome, $email, $hash, $id]);
            } else {
                $pdo->prepare("UPDATE utilizadores SET nome=?, email=? WHERE id=?")
                    ->execute([$nome, $email, $id]);
            }

            // Upload foto
            $foto = $aluno['foto'];
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png']) && $_FILES['foto']['size'] <= 2*1024*1024) {
                    $pasta = "../uploads/fotos/";
                    if (!is_dir($pasta)) mkdir($pasta, 0755, true);
                    $nome_foto = "foto_{$id}_" . time() . ".{$ext}";
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $pasta . $nome_foto)) {
                        $foto = $pasta . $nome_foto;
                    }
                }
            }

            // Atualizar alunos
            $pdo->prepare("UPDATE alunos SET morada=?, telefone=?, data_nascimento=?, foto=?, curso_id=?, estado=? WHERE id=?")
                ->execute([$morada ?: null, $telefone ?: null, $data_nasc ?: null, $foto, $curso_id ?: null, $estado, $id]);

            $_SESSION['flash_success'] = "Aluno «{$nome}» atualizado com sucesso.";
            header("Location: listar.php"); exit;

        } catch (PDOException $e) {
            $erros[] = "Erro na base de dados: " . $e->getMessage();
        }
    }
    if (!empty($erros)) $mensagem = implode('<br>', $erros);
    // Atualizar dados locais para mostrar no form
    $aluno = array_merge($aluno, ['nome'=>$nome,'email'=>$email,'morada'=>$morada,'telefone'=>$telefone,'data_nascimento'=>$data_nasc,'curso_id'=>$curso_id,'estado'=>$estado]);
}

$fotoUrl = !empty($aluno['foto'])
    ? htmlspecialchars($aluno['foto'])
    : "https://ui-avatars.com/api/?name=".urlencode($aluno['nome'])."&background=1A1A1A&color=fff&size=120";

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:680px; margin:0 auto; }
.back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); margin-bottom:24px; transition:color .15s; }
.back-link:hover { color:var(--black); }
.back-link svg { width:14px; height:14px; }
.page-hd { margin-bottom:24px; display:flex; align-items:center; gap:16px; }
.page-hd-avatar { width:52px; height:52px; border-radius:50%; object-fit:cover; border:var(--border); flex-shrink:0; }
.page-hd h1 { font-size:20px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; margin-bottom:2px; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.select-wrap { position:relative; }
.select-wrap::after { content:''; position:absolute; right:12px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--gray-400); pointer-events:none; }
.btn-row { display:flex; gap:10px; margin-top:24px; }
.file-label { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border:var(--border); border-radius:var(--radius-sm); font-size:13px; color:var(--text-muted); cursor:pointer; background:var(--white); transition:all .15s; width:100%; }
.file-label:hover { background:var(--off-white); }
#fotoInput { position:absolute; width:1px; height:1px; opacity:0; }
@media(max-width:520px){ .form-grid-2{grid-template-columns:1fr;} }
</style>

<div class="page-body">
    <a href="listar.php" class="back-link">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
        Voltar à lista
    </a>
    <div class="page-hd">
        <img src="<?= $fotoUrl ?>" alt="" class="page-hd-avatar"
             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($aluno['nome']) ?>&background=1A1A1A&color=fff&size=120'">
        <div>
            <h1><?= htmlspecialchars($aluno['nome']) ?></h1>
            <div style="font-size:12.5px;color:var(--text-faint);"><?= htmlspecialchars($aluno['email']) ?></div>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-danger" style="margin-bottom:20px;"><?= $mensagem ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">

                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--text-faint);margin-bottom:16px;">Conta de Acesso</div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="nome">Nome Completo <span class="req">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($aluno['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($aluno['email']) ?>" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="password">Nova Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Deixar em branco para não alterar">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado da Ficha</label>
                        <div class="select-wrap">
                            <select name="estado" id="estado" class="form-control">
                                <?php foreach (['Rascunho','Submetida','Aprovada','Rejeitada'] as $e): ?>
                                    <option value="<?= $e ?>" <?= $aluno['estado']===$e?'selected':'' ?>><?= $e ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="height:1px;background:var(--gray-100);margin:20px 0;"></div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--text-faint);margin-bottom:16px;">Dados Pessoais</div>

                <div class="form-group">
                    <label class="form-label" for="morada">Morada</label>
                    <input type="text" id="morada" name="morada" class="form-control" value="<?= htmlspecialchars($aluno['morada'] ?? '') ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" value="<?= htmlspecialchars($aluno['telefone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="data_nascimento">Data de Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($aluno['data_nascimento'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="curso_id">Curso</label>
                        <div class="select-wrap">
                            <select name="curso_id" id="curso_id" class="form-control">
                                <option value="">— Sem curso —</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= $aluno['curso_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto de Perfil</label>
                        <div style="position:relative;">
                            <label class="file-label" for="fotoInput">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 12V10a2 2 0 012-2h.5l1-2h5l1 2H12a2 2 0 012 2v2a1 1 0 01-1 1H3a1 1 0 01-1-1z"/><circle cx="8" cy="10" r="1.5"/></svg>
                                <span id="fotoLabelText">Substituir foto atual</span>
                            </label>
                            <input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png"
                                   onchange="document.getElementById('fotoLabelText').textContent=this.files[0]?.name||'Substituir foto atual'">
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <a href="listar.php" class="btn" style="text-decoration:none;flex:0;white-space:nowrap;">Cancelar</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>