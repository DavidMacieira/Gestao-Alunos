<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

// Buscar todas as associações curso → disciplina
$stmt = $pdo->query("
    SELECT pe.id, c.nome AS curso, d.nome_disciplina
    FROM plano_estudos pe
    JOIN cursos c ON pe.curso_id = c.id
    JOIN disciplinas d ON pe.disciplina_id = d.id
    ORDER BY c.nome, d.nome_disciplina
");
$planos = $stmt->fetchAll();
?>
<div class="navbar">
    <img src="../img/logo-ipca.png" alt="IPCA Logo">
    <h1>Painel de Administração - IPCA</h1>
    <div style="margin-left:auto;">
        <a href="../index.php">Home</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
</div>
<link rel="stylesheet" href="../global.css">
<h2>Plano de Estudos</h2>
<a href="adicionar.php">Adicionar Disciplina a Curso</a>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Curso</th>
        <th>Disciplina</th>
        <th>Ações</th>
    </tr>
    <?php foreach($planos as $p): ?>
    <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['curso']) ?></td>
        <td><?= htmlspecialchars($p['nome_disciplina']) ?></td>
        <td>
            <a href="eliminar.php?id=<?= $p['id'] ?>" onclick="return confirm('Tem certeza?')">Remover</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<button type="button" onclick="window.location.href='../admin/dashboard.php'">Voltar</button>