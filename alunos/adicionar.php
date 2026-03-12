<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

$erro = "";
$sucesso = "";

// Buscar cursos para select
$cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome ASC")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $morada = $_POST['morada'];
    $data_nascimento = $_POST['data_nascimento'];
    $telefone = $_POST['telefone'];
    $curso_id = $_POST['curso_id'];

    // Upload da foto
    $foto = null;
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){
        $foto = "uploads/".basename($_FILES['foto']['name']);
        move_uploaded_file($_FILES['foto']['tmp_name'], "../../".$foto);
    }

    // Inserir no banco
    $stmt = $pdo->prepare("INSERT INTO alunos (nome,email,password,morada,data_nascimento,telefone,foto,curso_id) VALUES (?,?,?,?,?,?,?,?)");
    if($stmt->execute([$nome,$email,$password,$morada,$data_nascimento,$telefone,$foto,$curso_id])){
        $sucesso = "Aluno adicionado com sucesso!";
    } else {
        $erro = "Erro ao adicionar aluno!";
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

<div class="container">
<h2>Adicionar Aluno</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post" enctype="multipart/form-data">
    Nome: <input type="text" name="nome" required><br>
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    Morada: <input type="text" name="morada"><br>
    Data de Nascimento: <input type="date" name="data_nascimento"><br>
    Telefone: <input type="text" name="telefone"><br>
    Curso: 
    <select name="curso_id">
        <option value="">--Selecione--</option>
        <?php foreach($cursos as $curso): ?>
            <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
        <?php endforeach; ?>
    </select><br>
    Foto: <input type="file" name="foto"><br>
    <button type="submit">Adicionar</button>
</form>
<button type="button" onclick="window.location.href='listar.php'">Voltar</button>