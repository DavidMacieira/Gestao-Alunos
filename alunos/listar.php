<?php
session_start();

// Proteção de Acesso
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Admin', 'Gestor Pedagógico'])){
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

require_once "../config/db.php";

// LÓGICA DE APROVAÇÃO/REJEIÇÃO (Processamento rápido)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $nova_status = ($_GET['action'] == 'aprovar') ? 'Aprovada' : 'Rejeitada';
    
    $update = $pdo->prepare("UPDATE alunos SET estado = ? WHERE id = ?");
    $update->execute([$nova_status, $id]);
    header("Location: listar.php?msg=status_atualizado");
    exit();
}

// Buscar alunos (incluindo o campo foto e estado)
$stmt = $pdo->query("SELECT alunos.*, cursos.nome AS curso 
                     FROM alunos 
                     LEFT JOIN cursos ON alunos.curso_id = cursos.id 
                     ORDER BY alunos.nome ASC");
$alunos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Alunos - IPCA</title>
    <link rel="stylesheet" href="../global.css">
    <style>
        .status-badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; }
        .submetida { background: #fefcbf; color: #b7791f; } /* Amarelo */
        .aprovada { background: #c6f6d5; color: #2f855a; }  /* Verde */
        .rejeitada { background: #fed7d7; color: #c53030; }  /* Vermelho */
        .btn-valida { text-decoration: none; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; margin: 0 2px; }
    </style>
</head>
<body>

<div class="navbar">
    <img src="../img/logo-ipca.png" alt="IPCA Logo" style="height:40px;">
    <h1>Validação Pedagógica de Alunos</h1>
    <div style="margin-left:auto;">
        <a href="../gestor/dashboard.php">Dashboard</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
</div>

<div style="padding: 20px;">
    <h2>Lista de Alunos e Estados de Ficha</h2>
    
    <?php if(isset($_GET['msg'])) echo "<p style='color:green;'>Estado atualizado com sucesso!</p>"; ?>

    <a href="adicionar.php" class="btn-primary" style="display:inline-block; margin-bottom:15px; text-decoration:none; background:#2c7a7b; color:white; padding:10px;">+ Adicionar Aluno Manualmente</a>

    <table border="1" cellpadding="10" style="width:100%; border-collapse: collapse; background: white;">
        <tr style="background:#f4f4f4;">
            <th>Foto</th>
            <th>Nome</th>
            <th>Curso</th>
            <th>Estado</th>
            <th>Validação (Gestor)</th>
            <th>Ações CRUD</th>
        </tr>
        
        <?php foreach($alunos as $aluno): ?>
        <tr>
            <td style="text-align:center;">
                <?php if(!empty($aluno['foto'])): ?>
                    <img src="../<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" style="width:50px; height:50px; object-fit:cover; border-radius:50%;">
                <?php else: ?>
                    <small>Sem foto</small>
                <?php endif; ?>
            </td>
            <td>
                <strong><?= htmlspecialchars($aluno['nome']) ?></strong><br>
                <small><?= htmlspecialchars($aluno['email']) ?></small>
            </td>
            <td><?= htmlspecialchars($aluno['curso'] ?? 'Não definido') ?></td>
            <td>
                <?php 
                    $classe = strtolower($aluno['estado'] ?? 'submetida');
                    echo "<span class='status-badge $classe'>" . ($aluno['estado'] ?? 'Submetida') . "</span>";
                ?>
            </td>
            <td>
                <?php if(($aluno['estado'] ?? 'Submetida') == 'Submetida'): ?>
                    <a href="listar.php?action=aprovar&id=<?= $aluno['id'] ?>" class="btn-valida" style="background:#48bb78; color:white;" onclick="return confirm('Aprovar esta ficha?')">✓ Aprovar</a>
                    <a href="listar.php?action=rejeitar&id=<?= $aluno['id'] ?>" class="btn-valida" style="background:#f56565; color:white;" onclick="return confirm('Rejeitar esta ficha?')">✕ Rejeitar</a>
                <?php else: ?>
                    <small style="color:#999;">Validado</small>
                <?php endif; ?>
            </td>
            <td>
                <a href="editar.php?id=<?= $aluno['id'] ?>">Editar</a> | 
                <a href="eliminar.php?id=<?= $aluno['id'] ?>" style="color:red;" onclick="return confirm('Apagar permanentemente?')">Apagar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <br>
    <button type="button" onclick="window.location.href='../gestor/dashboard.php'" style="padding:10px 20px; cursor:pointer;">Voltar ao Painel</button>
</div>

</body>
</html>