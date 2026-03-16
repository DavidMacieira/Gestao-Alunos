<?php
session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // 1. Procurar o utilizador e o seu perfil
    $sql = "SELECT utilizadores.*, perfis.nome AS perfil_nome 
            FROM utilizadores 
            JOIN perfis ON utilizadores.perfil_id = perfis.id 
            WHERE utilizadores.email = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verificar se o utilizador existe e a password coincide
    if ($user && password_verify($password, $user['password'])) {
        
        // Definir variáveis de sessão
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_nome']   = $user['nome'];
        $_SESSION['perfil_nome'] = $user['perfil_nome'];
        $_SESSION['perfil_id']   = $user['perfil_id'];

        // 3. Redirecionamento baseado no perfil (Lógica corrigida)
        switch ($user['perfil_nome']) {
            case 'Aluno':
                header("Location: ../Home-Alunos/dashboard.php");
                break;
            case 'Admin':
                header("Location: ../admin/dashboard.php");
                break;
            case 'Gestor Pedagógico':
                header("Location: ../gestor/dashboard.php");
                break;
            default:
                // Caso seja Serviços Académicos ou outro perfil não listado
                header("Location: ../servicos_academicos/dashboard.php");
                break;
        }
        exit();
    } else {
        // Se falhar a password ou o email não existir
        $erro = "Email ou password inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – IPCA</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="page-wrapper">
<div class="auth-card">

<div class="auth-header">
<div class="logo-wrapper">

<img src="../img/logo-ipca.png" alt="Logo IPCA" class="logo-img" onerror="this.style.display='none'">

<div class="logo-text-block">
<span class="logo-acronym">IPCA</span>
<span class="logo-full-name">Instituto Politécnico<br>do Cávado e do Ave</span>
</div>

</div>

<p class="auth-header-subtitle">Sistema de Gestão Académica</p>
</div>

<?php if(isset($erro)): ?>

<div class="alert-error" style="grid-column: span 2; margin-bottom: 20px;">
<i class="fa-solid fa-circle-exclamation"></i>
<?= htmlspecialchars($erro) ?>
</div>

<?php endif; ?>
<?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
    <div class="alert-success-aesthetic">
        <div class="icon-circle">
            <i class="fa-solid fa-check"></i>
        </div>
        <div class="message-text">
            <strong>Bem-vindo à comunidade IPCA!</strong>
            <span>A tua conta foi criada. Já podes iniciar sessão.</span>
        </div>
    </div>
<?php endif; ?>
<div class="auth-body">

<div>

<h3 style="color: var(--ipca-blue); margin-bottom:15px; font-size: 1.1rem;">
Iniciar sessão com palavra-passe
</h3>

<form method="POST" autocomplete="off">

<div class="form-group">
<label class="form-label" for="email">Email institucional</label>

<div class="input-wrapper">
<input class="form-control"
type="email"
id="email"
name="email"
placeholder="utilizador@ipca.pt"
required
value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">

<i class="fa-solid fa-envelope input-icon"></i>
</div>
</div>

<div class="form-group">
<label class="form-label" for="password">Password</label>

<div class="input-wrapper">
<input class="form-control"
type="password"
id="password"
name="password"
placeholder="••••••••"
required>

<i class="fa-solid fa-lock input-icon"></i>
</div>

</div>

<button type="submit" class="btn-primary">
Entrar
</button>

</form>

<div class="auth-footer" style="margin-top: 20px; text-align: left;">
Ainda não tem conta?
<a href="registo.php">Registar-se aqui</a>
</div>

</div>

<div style="border-left: 1px solid #ddd; padding-left: 30px;">

<h3 style="color: var(--ipca-blue); margin-bottom:15px; font-size: 1.1rem;">
Chave Móvel Digital | Cartão de Cidadão
</h3>

<p style="font-size: 0.9rem; color: #666; margin-bottom: 20px;">
Utilize a Chave Móvel Digital (CMD) ou Cartão de Cidadão (CC) para iniciar a sessão neste serviço.
</p>

<div style="background: #f8f9fa; border: 1px solid #eee; padding: 15px; text-align: center; border-radius: 4px;">

<i class="fa-solid fa-id-card" style="font-size: 2rem; color: var(--ipca-blue);"></i>

<p style="font-size: 0.8rem; margin-top: 10px;">
Autenticação Oficial Gov.pt
</p>

</div>

</div>

</div>
</div>
</div>

<p class="page-footer">
© <?= date('Y') ?> IPCA – Instituto Politécnico do Cávado e do Ave
</p>

</body>
</html>