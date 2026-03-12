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

// Buscar curso
$stmt = $pdo->prepare("SELECT * FROM cursos WHERE id=?");
$stmt->execute([$id]);
$curso = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $sigla = $_POST['sigla'];

    // Upload do PDF
    $horario_pdf = $curso['horario_pdf']; // manter existente
    if(isset($_FILES['horario_pdf']) && $_FILES['horario_pdf']['error'] == 0){
        $horario_pdf = "uploads/".basename($_FILES['horario_pdf']['name']);
        move_uploaded_file($_FILES['horario_pdf']['tmp_name'], "../../".$horario_pdf);
    }

    $stmt = $pdo->prepare("UPDATE cursos SET nome=?, sigla=?, horario_pdf=? WHERE id=?");
    $stmt->execute([$nome,$sigla,$horario_pdf,$id]);

    $sucesso = "Curso atualizado com sucesso!";
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id=?");
    $stmt->execute([$id]);
    $curso = $stmt->fetch();
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
<h2>Editar Curso</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post" enctype="multipart/form-data">
    Nome: <input type="text" name="nome" value="<?= htmlspecialchars($curso['nome']) ?>" required><br>
    Sigla: <input type="text" name="sigla" value="<?= htmlspecialchars($curso['sigla']) ?>"><br>
    Horário PDF: <input type="file" name="horario_pdf"><br>
    <?php if($curso['horario_pdf']): ?>
        Arquivo atual: <a href="../../<?= $curso['horario_pdf'] ?>" target="_blank">Ver PDF</a><br>
    <?php endif; ?>
    <button type="submit">Atualizar</button>
</form>