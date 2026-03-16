<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Admin', 'Gestor Pedagógico'])){
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

require_once "../config/db.php";

$stmt = $pdo->query("SELECT * FROM disciplinas ORDER BY nome_disciplina ASC");
$disciplinas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Lista de Disciplinas - IPCA</title>
    <link rel="stylesheet" href="../global.css">
</head>
<body>
    <div class="navbar">
        <img src="../img/logo-ipca.png" alt="IPCA Logo" style="height:40px;">
        <h1>Gestão de Disciplinas - IPCA</h1>
        <div style="margin-left:auto;">
            <a href="../gestor/dashboard.php">Dashboard</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div style="padding: 20px;">
        <h2>Lista de Disciplinas</h2>
        
        <div style="margin-bottom: 20px;">
            <a href="adicionar.php" style="background:#28a745; color:white; padding:10px; text-decoration:none; border-radius:5px;">+ Adicionar Disciplina</a>
            <button type="button" onclick="window.location.href='../gestor/dashboard.php'" style="padding:10px; margin-left: 10px;">Voltar à Dashboard</button>
        </div>
        
        <table border="1" cellpadding="10" style="width:100%; border-collapse: collapse; text-align: left;">
            <tr style="background:#f4f4f4;">
                <th>ID</th>
                <th>Nome da Disciplina</th>
                <th>Sigla</th>
                <th>Ações</th>
            </tr>
            <?php foreach($disciplinas as $disciplina): ?>
            <tr>
                <td><?= $disciplina['id'] ?></td>
                <td><?= htmlspecialchars($disciplina['nome_disciplina']) ?></td>
                <td><?= htmlspecialchars($disciplina['sigla']) ?></td>
                <td>
                    <a href="editar.php?id=<?= $disciplina['id'] ?>" style="color: blue;">Editar</a> | 
                    <a href="eliminar.php?id=<?= $disciplina['id'] ?>" onclick="return confirm('Tem certeza absoluta que deseja eliminar esta disciplina?');" style="color: red;">Apagar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>