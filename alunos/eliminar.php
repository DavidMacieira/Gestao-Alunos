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

// Buscar aluno e cursos
$aluno = $pdo->prepare("SELECT * FROM alunos WHERE id=?");
$aluno->execute([$id]);
$aluno = $aluno->fetch();

$cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome ASC")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $morada = $_POST['morada'];
    $data_nascimento = $_POST['data_nascimento'];
    $telefone = $_POST['telefone'];
    $curso_id = $_POST['curso_id'];

    // Atualizar password se fornecida
    if(!empty($_POST['password'])){
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE alunos SET nome=?, email=?, password=?, morada=?, data_nascimento=?, telefone=?, curso_id=? WHERE id=?");
        $stmt->execute([$nome,$email,$password,$morada,$data_nascimento,$telefone,$curso_id,$id]);
    } else {
        $stmt = $pdo->prepare("UPDATE alunos SET nome=?, email=?, morada=?, data_nascimento=?, telefone=?, curso_id=? WHERE id=?");
        $stmt->execute([$nome,$email,$morada,$data_nascimento,$telefone,$curso_id,$id]);
    }

    $sucesso = "Aluno atualizado com sucesso!";
    $aluno = $pdo->prepare("SELECT * FROM alunos WHERE id=?");
    $aluno->execute([$id]);
    $aluno = $aluno->fetch();
}
?>
<div class="navbar">
    <img src="../img/logo-ipca.png" alt="IPCA Logo">
    <h1>Painel de Administração - IPCA</h1>
    <div style="margin-left:auto;">
        <a href="../index.php">Home</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
</div>
<h2>Editar Aluno</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post">
    Nome: <input type="text" name="nome" value="<?= htmlspecialchars($aluno['nome']) ?>" required><br>
    Email: <input type="email" name="email" value="<?= htmlspecialchars($aluno['email']) ?>" required><br>
    Password: <input type="password" name="password" placeholder="Deixe em branco para não alterar"><br>
    Morada: <input type="text" name="morada" value="<?= htmlspecialchars($aluno['morada']) ?>"><br>
    Data de Nascimento: <input type="date" name="data_nascimento" value="<?= $aluno['data_nascimento'] ?>"><br>
    Telefone: <input type="text" name="telefone" value="<?= htmlspecialchars($aluno['telefone']) ?>"><br>
    Curso: 
    <select name="curso_id">
        <option value="">--Selecione--</option>
        <?php foreach($cursos as $curso): ?>
            <option value="<?= $curso['id'] ?>" <?= $aluno['curso_id']==$curso['id']?'selected':'' ?>><?= htmlspecialchars($curso['nome']) ?></option>
        <?php endforeach; ?>
    </select><br>
    <button type="submit">Atualizar</button>
</form>
<button type="button" onclick="window.location.href='listar.php'">Voltar</button>