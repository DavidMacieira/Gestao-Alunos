<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

$erro = "";
$sucesso = "";

// Buscar cursos e disciplinas
$cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome ASC")->fetchAll();
$disciplinas = $pdo->query("SELECT id, nome_disciplina FROM disciplinas ORDER BY nome_disciplina ASC")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $curso_id = $_POST['curso_id'];
    $disciplina_id = $_POST['disciplina_id'];

    // Verificar se já existe
    $stmtCheck = $pdo->prepare("SELECT * FROM plano_estudos WHERE curso_id=? AND disciplina_id=?");
    $stmtCheck->execute([$curso_id, $disciplina_id]);
    if($stmtCheck->rowCount() > 0){
        $erro = "Essa disciplina já está associada a este curso!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO plano_estudos (curso_id, disciplina_id) VALUES (?, ?)");
        if($stmt->execute([$curso_id, $disciplina_id])){
            $sucesso = "Disciplina associada ao curso com sucesso!";
        } else {
            $erro = "Erro ao associar disciplina!";
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
<h2>Adicionar Disciplina a Curso</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post">
    Curso: 
    <select name="curso_id" required>
        <option value="">--Selecione--</option>
        <?php foreach($cursos as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?>
    </select><br>
    
    Disciplina: 
    <select name="disciplina_id" required>
        <option value="">--Selecione--</option>
        <?php foreach($disciplinas as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome_disciplina']) ?></option>
        <?php endforeach; ?>
    </select><br>
    
    <button type="submit">Associar</button>
</form>