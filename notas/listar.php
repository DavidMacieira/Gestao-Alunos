<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

// Buscar notas com join para mostrar nome do aluno e disciplina
$stmt = $pdo->query("
    SELECT notas.id, alunos.nome AS aluno, disciplinas.nome_disciplina AS disciplina, notas.nota, notas.epoca, notas.ano_letivo
    FROM notas
    INNER JOIN alunos ON notas.aluno_id = alunos.id
    INNER JOIN disciplinas ON notas.disciplina_id = disciplinas.id
    ORDER BY notas.id ASC
");
$notas = $stmt->fetchAll();
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
<h2>Lista de Notas</h2>
<a href="adicionar.php">Adicionar Nota</a>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Aluno</th>
        <th>Disciplina</th>
        <th>Nota</th>
        <th>Época</th>
        <th>Ano Letivo</th>
        <th>Ações</th>
    </tr>
    <?php foreach($notas as $nota): ?>
    <tr>
        <td><?= $nota['id'] ?></td>
        <td><?= htmlspecialchars($nota['aluno']) ?></td>
        <td><?= htmlspecialchars($nota['disciplina']) ?></td>
        <td><?= htmlspecialchars($nota['nota']) ?></td>
        <td><?= htmlspecialchars($nota['epoca']) ?></td>
        <td><?= htmlspecialchars($nota['ano_letivo']) ?></td>
        <td>
            <a href="editar.php?id=<?= $nota['id'] ?>">Editar</a> | 
            <a href="eliminar.php?id=<?= $nota['id'] ?>" onclick="return confirm('Tem certeza?')">Apagar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<button type="button" onclick="window.location.href='../admin/dashboard.php'">Voltar</button>