<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Aluno') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';

$aluno_id = $_SESSION['user_id'];
$mensagem = "";
$tipo_msg = "danger";

// ── Dados do aluno ──
$stmt = $pdo->prepare("
    SELECT a.*, u.nome, u.email, c.nome AS curso_nome
    FROM alunos a
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON a.curso_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$aluno) die("Erro: Perfil de aluno não encontrado.");

// ── Lista de cursos disponíveis (RF3.1 — selecionar curso pretendido) ──
$stmtCursos = $pdo->query("SELECT id, nome, sigla FROM cursos ORDER BY nome ASC");
$cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

$estado    = $aluno['estado'];
$bloqueado = in_array($estado, ['Submetida', 'Aprovada']);

// ── Processar POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($bloqueado) {
        $_SESSION['flash_error'] = "Não é possível editar a ficha no estado '{$estado}'.";
        header("Location: dashboard.php");
        exit;
    }

    $morada    = trim($_POST['morada']         ?? '');
    $telefone  = trim($_POST['telefone']        ?? '');
    $data_nasc = trim($_POST['data_nascimento'] ?? '');
    $curso_id  = (int)($_POST['curso_id']       ?? 0);
    $acao      = $_POST['acao'] ?? 'rascunho';

    // Validação server-side
    $erros = [];
    if (empty($morada))    $erros[] = "A morada é obrigatória.";
    if (empty($telefone))  $erros[] = "O telefone é obrigatório.";
    if (empty($data_nasc)) $erros[] = "A data de nascimento é obrigatória.";
    if ($curso_id <= 0)    $erros[] = "Deve selecionar um curso.";

    if ($curso_id > 0) {
        $chk = $pdo->prepare("SELECT id FROM cursos WHERE id = ?");
        $chk->execute([$curso_id]);
        if (!$chk->fetch()) $erros[] = "Curso selecionado inválido.";
    }

    if (empty($erros)) {
        $foto_nome = $aluno['foto'];

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext_ok   = ['jpg', 'jpeg', 'png'];
            $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($extensao, $ext_ok)) {
                $erros[] = "Formato de foto inválido. Use JPG ou PNG.";
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $erros[] = "A foto excede o tamanho máximo de 2MB.";
            } else {
                $pasta = "../uploads/fotos/";
                if (!is_dir($pasta)) mkdir($pasta, 0755, true);
                $novo_nome = "foto_{$aluno_id}_" . time() . ".{$extensao}";
                $destino   = $pasta . $novo_nome;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $foto_nome = $destino;
                } else {
                    $erros[] = "Erro ao guardar a foto. Tente novamente.";
                }
            }
        }
    }

    if (empty($erros)) {
        try {
            $novo_estado = ($acao === 'submeter') ? 'Submetida' : 'Rascunho';
            $pdo->prepare("UPDATE alunos SET morada=?, telefone=?, data_nascimento=?, foto=?, curso_id=?, estado=? WHERE id=?")
                ->execute([$morada, $telefone, $data_nasc, $foto_nome, $curso_id, $novo_estado, $aluno_id]);

            if ($novo_estado === 'Submetida') {
                $_SESSION['flash_success'] = "Ficha submetida com sucesso! Aguarda validação pelo Gestor Pedagógico.";
                header("Location: dashboard.php");
                exit;
            }
            $_SESSION['flash_success'] = "Dados guardados como rascunho!";
            header("Location: submeter_matricula.php");
            exit;
        } catch (PDOException $e) {
            $erros[] = "Erro na base de dados: " . $e->getMessage();
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
    --white:#fff; --off-white:#F8F9FA; --gray-100:#EEEEEE; --gray-400:#9A9A9A;
    --black:#1A1A1A; --text-muted:#6A6A6A; --border:1px solid #EEEEEE;
    --radius-sm:6px; --radius-md:10px; --radius-lg:14px;
}
body { font-family:'DM Sans',system-ui,sans-serif; background:var(--off-white); color:var(--black); font-size:14px; line-height:1.6; -webkit-font-smoothing:antialiased; }
a { color:inherit; text-decoration:none; }
.page-wrap { max-width:640px; margin:0 auto; padding:32px 16px 60px; }
.back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); margin-bottom:24px; transition:color .15s; }
.back-link:hover { color:var(--black); }
.back-link svg { width:14px; height:14px; }
.card { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.card-top { padding:28px 32px 24px; border-bottom:var(--border); }
.card-top-eyebrow { font-size:10.5px; font-weight:500; letter-spacing:.1em; text-transform:uppercase; color:var(--gray-400); margin-bottom:6px; }
.card-top-title { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.card-top-sub { font-size:13px; color:var(--text-muted); margin-top:5px; }
.card-body { padding:28px 32px; }
.estado-row { display:flex; align-items:center; gap:10px; margin-bottom:24px; padding-bottom:20px; border-bottom:var(--border); }
.estado-label { font-size:12px; color:var(--text-muted); }
.badge { display:inline-flex; align-items:center; padding:4px 11px; border-radius:20px; font-size:11.5px; font-weight:500; }
.badge-rascunho  { background:#FFFAE6; color:#856404; }
.badge-submetida { background:#E8F2FC; color:#0069B4; }
.badge-aprovada  { background:var(--green-pale); color:var(--green-mid); }
.badge-rejeitada { background:#FEF0EF; color:#9B2B20; }
.alert { padding:13px 16px; border-radius:var(--radius-md); margin-bottom:20px; border-left:3px solid; font-size:13px; }
.alert-success { background:var(--green-pale); color:var(--green-mid); border-color:var(--green); }
.alert-danger   { background:#FEF0EF; color:#9B2B20; border-color:#E0604A; }
.form-group { margin-bottom:18px; }
.form-label { display:block; font-size:11.5px; font-weight:500; color:var(--text-muted); letter-spacing:.05em; text-transform:uppercase; margin-bottom:7px; }
.form-label .req { color:#E0604A; margin-left:2px; }
.form-control {
    width:100%; height:42px; padding:0 14px;
    border:var(--border); border-radius:var(--radius-sm);
    font-family:'DM Sans',sans-serif; font-size:14px; color:var(--black); background:var(--white);
    transition:border-color .15s, box-shadow .15s; outline:none;
    appearance:none; -webkit-appearance:none;
}
.form-control:focus { border-color:var(--gray-400); box-shadow:0 0 0 3px rgba(0,122,51,.07); }
.form-control:disabled { background:var(--off-white); color:var(--gray-400); cursor:not-allowed; }
.form-hint { font-size:11.5px; color:var(--gray-400); margin-top:5px; }
.select-wrap { position:relative; }
.select-wrap::after { content:''; position:absolute; right:14px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--gray-400); pointer-events:none; }
.form-divider { display:flex; align-items:center; gap:12px; margin:22px 0 20px; }
.form-divider::before, .form-divider::after { content:''; flex:1; height:1px; background:var(--gray-100); }
.form-divider span { font-size:10.5px; color:var(--gray-400); letter-spacing:.07em; text-transform:uppercase; white-space:nowrap; }
.foto-preview-wrap { display:flex; align-items:center; gap:14px; margin-bottom:10px; }
.foto-preview { width:52px; height:52px; border-radius:50%; object-fit:cover; border:var(--border); }
.file-label {
    display:inline-flex; align-items:center; gap:8px;
    padding:9px 16px; border:var(--border); border-radius:var(--radius-sm);
    font-size:13px; color:var(--text-muted); cursor:pointer; background:var(--white);
    transition:all .15s; width:100%;
}
.file-label:hover { background:var(--off-white); border-color:var(--gray-400); color:var(--black); }
.file-label svg { width:15px; height:15px; flex-shrink:0; }
#fotoInput { position:absolute; width:1px; height:1px; opacity:0; }
.locked-box { text-align:center; padding:32px 20px; }
.lock-icon-wrap { width:52px; height:52px; border-radius:50%; background:var(--off-white); border:var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
.lock-icon-wrap svg { width:22px; height:22px; color:var(--gray-400); }
.locked-title { font-size:18px; font-family:'DM Serif Display',serif; color:var(--black); margin-bottom:8px; }
.locked-desc { font-size:13px; color:var(--text-muted); max-width:320px; margin:0 auto 20px; }
.ro-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; margin-top:24px; border-top:var(--border); }
.ro-item { padding:14px 0; border-bottom:var(--border); }
.ro-item:nth-child(odd)  { padding-right:20px; }
.ro-item:nth-child(even) { padding-left:20px; border-left:var(--border); }
.ro-item:nth-last-child(-n+2) { border-bottom:none; }
.ro-label { font-size:10.5px; font-weight:500; text-transform:uppercase; letter-spacing:.07em; color:var(--gray-400); margin-bottom:3px; }
.ro-value { font-size:13.5px; color:var(--black); }
.btn-row { display:flex; gap:10px; margin-top:24px; }
.btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:42px; padding:0 20px; border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13.5px; font-weight:500; border:var(--border); background:var(--white); color:var(--black); cursor:pointer; transition:all .15s; flex:1; }
.btn:hover { background:var(--off-white); }
.btn:active { transform:scale(.98); }
.btn-primary { background:var(--black); color:white; border-color:var(--black); }
.btn-primary:hover { background:#333; }
.btn svg { width:15px; height:15px; flex-shrink:0; }
@media (max-width:520px) {
    .card-top, .card-body { padding:20px; }
    .btn-row { flex-direction:column; }
    .ro-grid { grid-template-columns:1fr; }
    .ro-item:nth-child(even) { padding-left:0; border-left:none; }
}
</style>

<div class="page-wrap">
    <a href="dashboard.php" class="back-link">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
        Voltar ao Dashboard
    </a>

    <div class="card">
        <div class="card-top">
            <div class="card-top-eyebrow">RF3 — Ficha de Aluno</div>
            <div class="card-top-title">Dados Pessoais</div>
            <div class="card-top-sub">Preencha os seus dados e selecione o curso pretendido para validação pelos serviços académicos.</div>
        </div>

        <div class="card-body">

            <?php if ($flash_success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
            <?php endif; ?>
            <?php if ($mensagem): ?>
                <div class="alert alert-danger"><?= $mensagem ?></div>
            <?php endif; ?>

            <div class="estado-row">
                <span class="estado-label">Estado da ficha:</span>
                <?php
                $bc = ['Rascunho'=>'badge-rascunho','Submetida'=>'badge-submetida','Aprovada'=>'badge-aprovada','Rejeitada'=>'badge-rejeitada'][$estado] ?? 'badge-rascunho';
                ?>
                <span class="badge <?= $bc ?>"><?= htmlspecialchars($estado) ?></span>
            </div>

            <?php if ($bloqueado): ?>
            <div class="locked-box">
                <div class="lock-icon-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="11" width="14" height="10" rx="2"/>
                        <path d="M8 11V7a4 4 0 018 0v4"/>
                    </svg>
                </div>
                <div class="locked-title">Edição Bloqueada</div>
                <div class="locked-desc">
                    <?= $estado === 'Submetida'
                        ? 'A ficha foi submetida e aguarda validação pelo Gestor Pedagógico.'
                        : 'A ficha está <strong>Aprovada</strong> e não pode ser alterada.' ?>
                </div>
                <a href="dashboard.php" class="btn btn-primary" style="display:inline-flex;max-width:220px;">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>
                    Voltar ao Dashboard
                </a>
            </div>
            <div class="ro-grid">
                <div class="ro-item"><div class="ro-label">Morada</div><div class="ro-value"><?= htmlspecialchars($aluno['morada'] ?? '—') ?></div></div>
                <div class="ro-item"><div class="ro-label">Telefone</div><div class="ro-value"><?= htmlspecialchars($aluno['telefone'] ?? '—') ?></div></div>
                <div class="ro-item"><div class="ro-label">Data de Nascimento</div><div class="ro-value"><?= !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '—' ?></div></div>
                <div class="ro-item"><div class="ro-label">Curso</div><div class="ro-value"><?= htmlspecialchars($aluno['curso_nome'] ?? '—') ?></div></div>
            </div>

            <?php else: ?>

            <?php if ($estado === 'Rejeitada' && !empty($aluno['observacoes_validacao'])): ?>
                <div class="alert alert-danger">
                    <strong>Ficha rejeitada.</strong> Motivo: <?= htmlspecialchars($aluno['observacoes_validacao']) ?><br>
                    <span style="font-size:12.5px;margin-top:4px;display:block;">Corrija os dados abaixo e volte a submeter.</span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="fichaForm" novalidate>

                <!-- Curso (RF3.1) -->
                <div class="form-group">
                    <label class="form-label" for="cursoSelect">Curso Pretendido <span class="req">*</span></label>
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
                    <div class="form-hint">Curso em que pretende matricular-se.</div>
                </div>

                <div class="form-divider"><span>Dados Pessoais</span></div>

                <div class="form-group">
                    <label class="form-label" for="morada">Morada Completa <span class="req">*</span></label>
                    <input type="text" id="morada" name="morada" class="form-control"
                           value="<?= htmlspecialchars($aluno['morada'] ?? '') ?>"
                           placeholder="Ex: Rua das Flores, 123, 4750-000 Barcelos" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="telefone">Telemóvel / Telefone <span class="req">*</span></label>
                    <input type="tel" id="telefone" name="telefone" class="form-control"
                           value="<?= htmlspecialchars($aluno['telefone'] ?? '') ?>"
                           placeholder="Ex: 912 345 678" pattern="[0-9\s\+\-]{9,15}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="dataNasc">Data de Nascimento <span class="req">*</span></label>
                    <input type="date" id="dataNasc" name="data_nascimento" class="form-control"
                           value="<?= htmlspecialchars($aluno['data_nascimento'] ?? '') ?>"
                           max="<?= date('Y-m-d', strtotime('-16 years')) ?>" required>
                </div>

                <div class="form-divider"><span>Fotografia</span></div>

                <div class="form-group">
                    <label class="form-label">Foto de Perfil</label>
                    <?php if (!empty($aluno['foto'])): ?>
                        <div class="foto-preview-wrap">
                            <img src="<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto atual" class="foto-preview"
                                 onerror="this.style.display='none'">
                            <span style="font-size:12.5px;color:var(--text-muted);">Foto atual. Envie uma nova para substituir.</span>
                        </div>
                    <?php endif; ?>
                    <div style="position:relative;">
                        <label class="file-label" for="fotoInput">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 12V10a2 2 0 012-2h.5l1-2h5l1 2H12a2 2 0 012 2v2a1 1 0 01-1 1H3a1 1 0 01-1-1z"/><circle cx="8" cy="10" r="1.5"/></svg>
                            <span id="fotoLabelText">Escolher ficheiro (JPG ou PNG, máx. 2MB)</span>
                        </label>
                        <input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png"
                               onchange="document.getElementById('fotoLabelText').textContent = this.files[0]?.name || 'Escolher ficheiro'">
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" name="acao" value="rascunho" class="btn">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M13 9v4H3V9M8 2v8M5 5l3-3 3 3"/></svg>
                        Guardar Rascunho
                    </button>
                    <button type="submit" name="acao" value="submeter" class="btn btn-primary"
                            onclick="return validarEConfirmar()">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M8 10V2M4 6l4-4 4 4"/><path d="M2 12v1a1 1 0 001 1h10a1 1 0 001-1v-1"/></svg>
                        Finalizar e Submeter
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function validarEConfirmar() {
    const erros = [];
    if (!document.getElementById('cursoSelect').value) erros.push('Selecione um curso.');
    if (!document.getElementById('morada').value.trim()) erros.push('A morada é obrigatória.');
    if (!document.getElementById('telefone').value.trim()) erros.push('O telefone é obrigatório.');
    if (!document.getElementById('dataNasc').value) erros.push('A data de nascimento é obrigatória.');
    if (erros.length) { alert('Por favor corrija:\n\n• ' + erros.join('\n• ')); return false; }
    return confirm('Submeter a ficha para validação?\n\nApós a submissão não poderá editar até ser validada pelo Gestor Pedagógico.');
}
</script>
<?php include '../includes/footer.php'; ?>