<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
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
<link rel="stylesheet" href="../global.css">
<div class="navbar">
    <img src="../img/logo-ipca.png" alt="IPCA Logo">
    <h1>Painel de Administração - IPCA</h1>
    <div style="margin-left:auto;">
        <a href="../index.php">Home</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
</div>
<h2>Adicionar Disciplina</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post">
    Nome da Disciplina: <input type="text" name="nome_disciplina" required><br>
    Sigla: <input type="text" name="sigla"><br>
    <button type="submit">Adicionar</button>
</form>