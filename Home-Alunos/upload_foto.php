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

// ── Obter dados atuais do aluno ──
$stmt = $pdo->prepare("SELECT a.foto, u.nome FROM alunos a JOIN utilizadores u ON u.id = a.id WHERE a.id = ?");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
    die("Erro: Aluno não encontrado.");
}

// ── Verificar se pode alterar foto (bloqueado se Submetida ou Aprovada) ──
$stmtEstado = $pdo->prepare("SELECT estado FROM alunos WHERE id = ?");
$stmtEstado->execute([$aluno_id]);
$estado = $stmtEstado->fetchColumn();

$bloqueado = false; // Foto pode ser alterada em qualquer estado

// ── Processar upload ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {

    if ($bloqueado) {
        $mensagem = "Não é possível alterar a foto enquanto a ficha estiver no estado '{$estado}'.";
    } else {
        $arquivo   = $_FILES['foto'];
        $extensoes = ['jpg', 'jpeg', 'png'];
        $extensao  = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $max_size  = 2 * 1024 * 1024;

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $mensagem = "Erro no upload do ficheiro. Tente novamente.";
        } elseif (!in_array($extensao, $extensoes)) {
            $mensagem = "Formato inválido. Apenas JPG ou PNG são permitidos.";
        } elseif ($arquivo['size'] > $max_size) {
            $mensagem = "O ficheiro é demasiado grande. Máximo permitido: 2MB.";
        } else {
            $pasta = "../uploads/fotos/";
            if (!is_dir($pasta)) mkdir($pasta, 0755, true);

            $nome_foto    = "foto_" . $aluno_id . "_" . time() . "." . $extensao;
            $caminho_final = $pasta . $nome_foto;

            if (move_uploaded_file($arquivo['tmp_name'], $caminho_final)) {
                $sql = "UPDATE alunos SET foto = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$caminho_final, $aluno_id]);
                $_SESSION['flash_success'] = "Foto de perfil atualizada com sucesso!";
                header("Location: dashboard.php");
                exit;
            } else {
                $mensagem = "Não foi possível guardar a foto. Verifique as permissões da pasta.";
            }
        }
    }
}

$fotoAtual = !empty($aluno['foto'])
    ? htmlspecialchars($aluno['foto'])
    : "https://ui-avatars.com/api/?name=" . urlencode($aluno['nome']) . "&background=007a33&color=fff&size=200";

include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --ipca-green: #007a33;
        --ipca-green-light: #00a34a;
        --bg-light: #f4f6f9;
        --card-bg: #ffffff;
        --text-dark: #2c3e50;
        --text-muted: #6c757d;
        --shadow: 0 2px 8px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.04);
    }
    body { font-family: 'Outfit', sans-serif; background: var(--bg-light); color: var(--text-dark); }
    .container { max-width: 500px; margin: 2rem auto; padding: 0 1rem; }
    .card { background: var(--card-bg); border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
    .card-header { background: linear-gradient(135deg, var(--ipca-green), var(--ipca-green-light)); padding: 1.5rem 2rem; color: white; }
    .card-header h2 { margin: 0; font-size: 1.4rem; }
    .card-body { padding: 2rem; text-align: center; }

    .foto-atual { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid var(--ipca-green); box-shadow: 0 4px 15px rgba(0,122,51,0.2); margin-bottom: 1.5rem; }

    .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 1.2rem; border-left: 5px solid; text-align: left; }
    .alert-success { background: #d4edda; color: #155724; border-color: #28a745; }
    .alert-danger  { background: #f8d7da; color: #721c24; border-color: #dc3545; }
    .alert-warning { background: #fff3cd; color: #856404; border-color: #ffc107; }

    .form-group { margin-bottom: 1.2rem; text-align: left; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.92rem; }
    .form-control { width: 100%; padding: 11px 14px; border: 1.5px solid #ddd; border-radius: 8px; font-family: 'Outfit'; font-size: 0.95rem; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--ipca-green); }
    .form-hint { font-size: 0.82rem; color: var(--text-muted); margin-top: 4px; }

    .btn-submit { width: 100%; padding: 12px; background: var(--ipca-green); color: white; border: none; border-radius: 8px; font-family: 'Outfit'; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.3s; }
    .btn-submit:hover { background: var(--ipca-green-light); }
    .btn-back { display: inline-block; margin-top: 1rem; color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
    .btn-back:hover { color: var(--ipca-green); }

    .locked-box { background: #f0f0f0; border-radius: 8px; padding: 1.5rem; text-align: center; }
    .locked-box .icon { font-size: 3rem; margin-bottom: 0.5rem; }
</style>

<div class="container">
    <a href="dashboard.php" class="btn-back">← Voltar ao Dashboard</a>

    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <h2>📷 Atualizar Foto de Perfil</h2>
        </div>
        <div class="card-body">
            <img src="<?= $fotoAtual ?>" alt="Foto atual" class="foto-atual"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($aluno['nome']) ?>&background=007a33&color=fff&size=200'">

            <?php if ($mensagem): ?>
                <div class="alert alert-<?= $tipo_msg ?>"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <?php if ($bloqueado): ?>
                <div class="locked-box">
                    <div class="icon">🔒</div>
                    <p>Não é possível alterar a foto enquanto a ficha estiver no estado <strong><?= htmlspecialchars($estado) ?></strong>.</p>
                    <a href="dashboard.php" style="color: var(--ipca-green); font-weight:600;">Voltar ao Dashboard</a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Selecionar Nova Foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png" required>
                        <p class="form-hint">Formatos: JPG ou PNG · Tamanho máximo: 2MB</p>
                    </div>
                    <button type="submit" class="btn-submit">📤 Enviar Nova Foto</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>