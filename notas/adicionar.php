<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

$erro = "";
$sucesso = "";

// Buscar alunos e disciplinas para selects
$alunos = $pdo->query("SELECT id, nome FROM alunos ORDER BY nome ASC")->fetchAll();
$disciplinas = $pdo->query("SELECT id, nome_disciplina FROM disciplinas ORDER BY nome_disciplina ASC")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aluno_id = $_POST['aluno_id'];
    $disciplina_id = $_POST['disciplina_id'];
    $nota = $_POST['nota'];
    $epoca = $_POST['epoca'];
    $ano_letivo = $_POST['ano_letivo'];

    $stmt = $pdo->prepare("INSERT INTO notas (aluno_id, disciplina_id, nota, epoca, ano_letivo) VALUES (?,?,?,?,?)");
    if($stmt->execute([$aluno_id, $disciplina_id, $nota, $epoca, $ano_letivo])){
        $sucesso = "Nota adicionada com sucesso!";
    } else {
        $erro = "Erro ao adicionar nota!";
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
<h2>Adicionar Nota</h2>
<?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
<?php if($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

<form method="post">
    Aluno: 
    <select name="aluno_id" required>
        <option value="">--Selecione--</option>
        <?php foreach($alunos as $aluno): ?>
            <option value="<?= $aluno['id'] ?>"><?= htmlspecialchars($aluno['nome']) ?></option>
        <?php endforeach; ?>
    </select><br>

    Disciplina:
    <select name="disciplina_id" required>
        <option value="">--Selecione--</option>
        <?php foreach($disciplinas as $disciplina): ?>
            <option value="<?= $disciplina['id'] ?>"><?= htmlspecialchars($disciplina['nome_disciplina']) ?></option>
        <?php endforeach; ?>
    </select><br>

    Nota: <input type="number" name="nota" step="0.01" min="0" max="20" required><br>
    Época: <input type="text" name="epoca" required><br>
    Ano Letivo: <input type="text" name="ano_letivo" required><br>

    <button type="submit">Adicionar</button>
</form>