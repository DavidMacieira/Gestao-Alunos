<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Admin', 'Gestor Pedagógico'])){
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

require_once "../config/db.php";

// Buscar alunos
$stmt = $pdo->query("SELECT alunos.id, alunos.nome, alunos.email, cursos.nome AS curso 
                     FROM alunos 
                     LEFT JOIN cursos ON alunos.curso_id = cursos.id 
                     ORDER BY alunos.nome ASC");
$alunos = $stmt->fetchAll();
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
<h2>Lista de Alunos</h2>

<a href="adicionar.php">Adicionar Aluno</a>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Email</th>
        <th>Curso</th>
        <th>Ações</th>
    </tr>
    <?php foreach($alunos as $aluno): ?>
    <tr>
        <td><?= $aluno['id'] ?></td>
        <td><?= htmlspecialchars($aluno['nome']) ?></td>
        <td><?= htmlspecialchars($aluno['email']) ?></td>
        <td><?= htmlspecialchars($aluno['curso']) ?></td>
        <td>
            <a href="editar.php?id=<?= $aluno['id'] ?>">Editar</a> | 
            <a href="eliminar.php?id=<?= $aluno['id'] ?>" onclick="return confirm('Tem certeza?')">Apagar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<button type="button" onclick="window.location.href='../admin/dashboard.php'">Voltar</button>