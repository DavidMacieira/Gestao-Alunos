<?php
session_start();
// Proteção: Apenas Admin ou Gestor Pedagógico
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Admin', 'Gestor Pedagógico'])){
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/db.php";

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_disciplina = $_POST['nome_disciplina'];
    $sigla = $_POST['sigla'];

    $stmt = $pdo->prepare("INSERT INTO disciplinas (nome_disciplina, sigla) VALUES (?, ?)");
    if($stmt->execute([$nome_disciplina, $sigla])){
        $sucesso = "Disciplina adicionada com sucesso!";
    } else {
        $erro = "Erro ao adicionar disciplina!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Disciplina - IPCA</title>
    <link rel="stylesheet" href="../global.css">
</head>
<body>
    <div class="navbar">
        <img src="../img/logo-ipca.png" alt="IPCA Logo" style="height:40px;">
        <h1>Gestão de Disciplinas - IPCA</h1>
        <div style="margin-left:auto;">
            <a href="../gestor/dashboard.php">Dashboard</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div style="padding: 20px;">
        <h2>Adicionar Disciplina</h2>
        
        <?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
        <?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

        <form method="post" style="max-width: 400px;">
            <p>Nome da Disciplina: <input type="text" name="nome_disciplina" required style="width: 100%;"></p>
            <p>Sigla: <input type="text" name="sigla" style="width: 100%;"></p>
            <br>
            <button type="submit" style="padding: 10px 20px;">Adicionar</button>
            <br><br>
            <a href="listar.php">Voltar à Lista de Disciplinas</a>
        </form>
    </div>
</body>
</html>