<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$mensagem = "";
$cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = trim($_POST['password']  ?? '');
    $morada    = trim($_POST['morada']    ?? '');
    $telefone  = trim($_POST['telefone']  ?? '');
    $data_nasc = trim($_POST['data_nascimento'] ?? '');
    $curso_id  = (int)($_POST['curso_id'] ?? 0);
    $estado    = $_POST['estado'] ?? 'Rascunho';
    $perfil_id = 1; // Aluno

    $erros = [];
    if (empty($nome))     $erros[] = "O nome é obrigatório.";
    if (empty($email))    $erros[] = "O email é obrigatório.";
    if (empty($password)) $erros[] = "A password é obrigatória.";

    // Verificar email duplicado
    if (!empty($email)) {
        $dup = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $dup->execute([$email]);
        if ($dup->fetch()) $erros[] = "Já existe um utilizador com este email.";
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            // Inserir em utilizadores
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO utilizadores (nome, email, password, perfil_id) VALUES (?,?,?,?)")
                ->execute([$nome, $email, $hash, $perfil_id]);
            $novo_id = $pdo->lastInsertId();

            // Upload foto
            $foto = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png']) && $_FILES['foto']['size'] <= 2*1024*1024) {
                    $pasta = "../uploads/fotos/";
                    if (!is_dir($pasta)) mkdir($pasta, 0755, true);
                    $nome_foto = "foto_{$novo_id}_" . time() . ".{$ext}";
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $pasta . $nome_foto)) {
                        $foto = $pasta . $nome_foto;
                    }
                }
            }

            // Inserir em alunos
            $pdo->prepare("INSERT INTO alunos (id, morada, data_nascimento, telefone, foto, curso_id, estado) VALUES (?,?,?,?,?,?,?)")
                ->execute([$novo_id, $morada ?: null, $data_nasc ?: null, $telefone ?: null, $foto, $curso_id ?: null, $estado]);

            $pdo->commit();
            $_SESSION['flash_success'] = "Aluno «{$nome}» criado com sucesso.";
            header("Location: listar.php"); exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $erros[] = "Erro na base de dados: " . $e->getMessage();
        }
    }
    if (!empty($erros)) $mensagem = implode('<br>', $erros);
}

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:680px; margin:0 auto; }
.back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); margin-bottom:24px; transition:color .15s; }
.back-link:hover { color:var(--black); }
.back-link svg { width:14px; height:14px; }
.page-hd { margin-bottom:24px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.select-wrap { position:relative; }
.select-wrap::after { content:''; position:absolute; right:12px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--gray-400); pointer-events:none; }
.btn-row { display:flex; gap:10px; margin-top:24px; }
.file-label { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border:var(--border); border-radius:var(--radius-sm); font-size:13px; color:var(--text-muted); cursor:pointer; background:var(--white); transition:all .15s; width:100%; }
.file-label:hover { background:var(--off-white); }
.file-label svg { width:14px; height:14px; flex-shrink:0; }
#fotoInput { position:absolute; width:1px; height:1px; opacity:0; }
@media(max-width:520px){ .form-grid-2{grid-template-columns:1fr;} }
</style>

<div class="page-body">
    <a href="listar.php" class="back-link">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
        Voltar à lista
    </a>
    <div class="page-hd">
        <h1>Novo Aluno</h1>
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
                        <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="password">Password <span class="req">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="estado">Estado da Ficha</label>
                        <div class="select-wrap">
                            <select name="estado" id="estado" class="form-control">
                                <?php foreach (['Rascunho','Submetida','Aprovada','Rejeitada'] as $e): ?>
                                    <option value="<?= $e ?>" <?= (($_POST['estado']??'Rascunho')===$e)?'selected':'' ?>><?= $e ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="height:1px;background:var(--gray-100);margin:20px 0;"></div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--text-faint);margin-bottom:16px;">Dados Pessoais</div>

                <div class="form-group">
                    <label class="form-label" for="morada">Morada</label>
                    <input type="text" id="morada" name="morada" class="form-control" value="<?= htmlspecialchars($_POST['morada'] ?? '') ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="data_nascimento">Data de Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($_POST['data_nascimento'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="curso_id">Curso</label>
                        <div class="select-wrap">
                            <select name="curso_id" id="curso_id" class="form-control">
                                <option value="">— Sem curso —</option>
                                <?php foreach ($cursos as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto de Perfil</label>
                        <div style="position:relative;">
                            <label class="file-label" for="fotoInput">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 12V10a2 2 0 012-2h.5l1-2h5l1 2H12a2 2 0 012 2v2a1 1 0 01-1 1H3a1 1 0 01-1-1z"/><circle cx="8" cy="10" r="1.5"/></svg>
                                <span id="fotoLabelText">JPG / PNG — máx. 2MB</span>
                            </label>
                            <input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png"
                                   onchange="document.getElementById('fotoLabelText').textContent=this.files[0]?.name||'JPG / PNG — máx. 2MB'">
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <a href="listar.php" class="btn" style="text-decoration:none;flex:0;white-space:nowrap;">Cancelar</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Criar Aluno</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>