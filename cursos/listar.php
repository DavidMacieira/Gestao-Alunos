<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Admin', 'Gestor Pedagógico'])){
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

require_once "../config/db.php";

$stmt = $pdo->query("SELECT * FROM cursos ORDER BY nome ASC");
$cursos = $stmt->fetchAll();
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
<h2>Lista de Cursos</h2>
<a href="adicionar.php">Adicionar Curso</a>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Sigla</th>
        <th>Horário PDF</th>
        <th>Ações</th>
    </tr>
    <?php foreach($cursos as $curso): ?>
    <tr>
        <td><?= $curso['id'] ?></td>
        <td><?= htmlspecialchars($curso['nome']) ?></td>
        <td><?= htmlspecialchars($curso['sigla']) ?></td>
<td>
    <?= $curso['horario_pdf'] ? '<a href="uploads/'.basename($curso['horario_pdf']).'" target="_blank">Ver PDF</a>' : '' ?>
</td>
        <td>
            <a href="editar.php?id=<?= $curso['id'] ?>">Editar</a> | 
            <a href="eliminar.php?id=<?= $curso['id'] ?>" onclick="return confirm('Tem certeza?')">Apagar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<button type="button" onclick="window.location.href='../admin/dashboard.php'">Voltar</button>