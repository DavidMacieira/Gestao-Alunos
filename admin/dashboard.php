<?php
session_start();
// Verifica se a sessão existe e se o perfil é EXATAMENTE 'Admin'
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../index.php?erro=acesso_restrito_admin");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="dashboard.css"> <!-- opcional -->
</head>
<body>
    <h1>Painel de Administração</h1>

    <h3>Gestão</h3>
    <ul>
        <li><a href="../alunos/listar.php">Alunos</a></li>
        <li><a href="../cursos/listar.php">Cursos</a></li>
        <li><a href="../disciplinas/listar.php">Disciplinas</a></li>
        <li><a href="../notas/listar.php">Notas </a></li>
        <li><a href="../planos-estudo/listar.php">Planos de Estudos</a></li>
    </ul>

    <br>
    <a href="../auth/logout.php">Logout</a>
</body>
</html>