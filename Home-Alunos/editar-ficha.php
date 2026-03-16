<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

$aluno_id = $_SESSION['user_id'];
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $morada = $_POST['morada'];
    $telefone = $_POST['telefone'];
    $submeter = isset($_POST['submeter']) ? 'Submetida' : 'Rascunho';

    try {
        $sql = "UPDATE alunos SET morada = ?, telefone = ?, estado = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$morada, $telefone, $submeter, $aluno_id]);
        
        if ($submeter == 'Submetida') {
            header("Location: dashboard.php?sucesso=1");
            exit;
        }
        $mensagem = "Dados guardados como rascunho!";
    } catch (PDOException $e) {
        $mensagem = "Erro: " . $e->getMessage();
    }
}

// Busca dados atuais
$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Ficha - IPCA</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="auth-card">
        <h2>Completar Ficha de Aluno</h2>
        <?php if($mensagem) echo "<p>$mensagem</p>"; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Morada</label>
                <input type="text" name="morada" class="form-control" value="<?= $aluno['morada'] ?>" required>
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" name="telefone" class="form-control" value="<?= $aluno['telefone'] ?>" required>
            </div>
            
            <button type="submit" name="guardar" class="btn-secondary">Guardar Rascunho</button>
            <button type="submit" name="submeter" class="btn-primary" style="background: #28a745;">Finalizar e Submeter</button>
        </form>
        
        <hr>
        <a href="upload_foto.php" class="btn-link">Atualizar Fotografia</a>
    </div>
</body>
</html>