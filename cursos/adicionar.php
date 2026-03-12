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
    $nome = $_POST['nome'];
    $sigla = $_POST['sigla'];

    $horario_pdf = null;
    $uploadDir = "uploads/"; // pasta dentro de cursos

    // Cria a pasta uploads caso não exista
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if(isset($_FILES['horario_pdf']) && $_FILES['horario_pdf']['error'] == 0){
        // Lista de extensões permitidas
        $allowedExt = ['pdf','jpg','jpeg','png','doc','docx','ppt','pptx'];
        $ext = strtolower(pathinfo($_FILES['horario_pdf']['name'], PATHINFO_EXTENSION));

        if(in_array($ext, $allowedExt)){
            $horario_pdf = $uploadDir . basename($_FILES['horario_pdf']['name']);
            if(!move_uploaded_file($_FILES['horario_pdf']['tmp_name'], $horario_pdf)){
                $erro = "Erro ao mover o ficheiro!";
            }
        } else {
            $erro = "Formato inválido! Permitidos: PDF, JPG, PNG, DOC, DOCX, PPT, PPTX.";
        }
    }

    // Só insere no banco se não houver erro de upload
    if(empty($erro)){
        $stmt = $pdo->prepare("INSERT INTO cursos (nome, sigla, horario_pdf) VALUES (?,?,?)");
        if($stmt->execute([$nome,$sigla,$horario_pdf])){
            $sucesso = "Curso adicionado com sucesso!";
        } else {
            $erro = "Erro ao adicionar curso!";
        }
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
<h2>Adicionar Curso</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post" enctype="multipart/form-data">
    Nome: <input type="text" name="nome" required><br>
    Sigla: <input type="text" name="sigla"><br>
    Horário PDF: <input type="file" name="horario_pdf"><br>
    <button type="submit">Adicionar</button>
</form>