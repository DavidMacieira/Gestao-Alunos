<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

$id = $_GET['id'];
$erro = "";
$sucesso = "";

// Buscar disciplina
$stmt = $pdo->prepare("SELECT * FROM disciplinas WHERE id=?");
$stmt->execute([$id]);
$disciplina = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_disciplina = $_POST['nome_disciplina'];
    $sigla = $_POST['sigla'];

    $stmt = $pdo->prepare("UPDATE disciplinas SET nome_disciplina=?, sigla=? WHERE id=?");
    $stmt->execute([$nome_disciplina, $sigla, $id]);

    $sucesso = "Disciplina atualizada com sucesso!";

    $stmt = $pdo->prepare("SELECT * FROM disciplinas WHERE id=?");
    $stmt->execute([$id]);
    $disciplina = $stmt->fetch();
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
<h2>Editar Disciplina</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post">
    Nome da Disciplina: <input type="text" name="nome_disciplina" value="<?= htmlspecialchars($disciplina['nome_disciplina']) ?>" required><br>
    Sigla: <input type="text" name="sigla" value="<?= htmlspecialchars($disciplina['sigla']) ?>"><br>
    <button type="submit">Atualizar</button>
</form>