<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Admin', 'Gestor Pedagógico'])) {
    header("Location: ../index.php?erro=acesso_restrito_gestor");
    exit;
}

$perfil = $_SESSION['perfil_nome'];
$nome_user = $_SESSION['user_nome'] ?? 'Utilizador';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel de Gestão - IPCA</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .admin-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid var(--ipca-blue);
            transition: transform 0.2s;
        }
        .admin-card:hover { transform: translateY(-5px); }
        .admin-card i { font-size: 2.5rem; color: var(--ipca-blue); margin-bottom: 15px; }
        .admin-card h3 { margin-bottom: 10px; color: #333; }
        .admin-card p { font-size: 0.9rem; color: #666; margin-bottom: 20px; }
        .btn-manage {
            background: var(--ipca-blue);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .admin-only { border-top-color: #d9534f; } /* Cor diferente para o que é só Admin */
    </style>
</head>
<body style="background-color: #f4f7f6;">

<div class="navbar" style="background: white; padding: 15px 50px; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <img src="../img/logo-ipca.png" alt="Logo IPCA" style="height: 40px; margin-right: 20px;">
    <h2 style="font-size: 1.2rem; color: var(--ipca-blue);">Portal de Gestão IPCA</h2>
    
    <div style="margin-left: auto; display: flex; align-items: center; gap: 20px;">
        <span><i class="fa-solid fa-user-circle"></i> <strong><?= htmlspecialchars($nome_user) ?></strong> (<?= $perfil ?>)</span>
        <a href="../auth/logout.php" class="btn-primary" style="padding: 8px 15px; font-size: 0.8rem; background: #666;">Sair</a>
    </div>
</div>

<div class="container" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    
    <header style="margin-bottom: 30px;">
        <h1>Bem-vindo ao Painel de Controlo</h1>
        <p>Utilize as opções abaixo para gerir os processos académicos de acordo com o seu perfil.</p>
    </header>

    <div class="dashboard-grid">

        <?php if($perfil == 'Admin' || $perfil == 'Gestor Pedagógico'): ?>
            <div class="admin-card">
                <i class="fa-solid fa-user-graduate"></i>
                <h3>Gestão de Alunos</h3>
                <p>Validar fichas de inscrição, fotos e estados de submissão.</p>
                <a href="../alunos/listar.php" class="btn-manage">Gerir Alunos</a>
            </div>

            <div class="admin-card">
                <i class="fa-solid fa-book"></i>
                <h3>Estrutura Curricular</h3>
                <p>Configurar cursos e disciplinas do instituto.</p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <a href="../cursos/listar.php" class="btn-manage" style="padding: 8px 12px;">Cursos</a>
                    <a href="../disciplinas/listar.php" class="btn-manage" style="padding: 8px 12px;">Disciplinas</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if($perfil == 'Admin'): ?>
            <div class="admin-card admin-only">
                <i class="fa-solid fa-users-gear"></i>
                <h3>Sistema</h3>
                <p>Gerir contas de utilizadores, perfis e permissões de acesso.</p>
                <a href="../utilizadores/listar.php" class="btn-manage" style="background: #d9534f;">Utilizadores</a>
            </div>
        <?php endif; ?>

        <?php if($perfil == 'Admin' || $perfil == 'Funcionário'): ?>
            <div class="admin-card">
                <i class="fa-solid fa-file-invoice"></i>
                <h3>Serviços Académicos</h3>
                <p>Lançamento de notas, faltas e geração de pautas oficiais.</p>
                <a href="../notas/listar.php" class="btn-manage">Notas e Pautas</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<p style="text-align: center; color: #999; margin-top: 50px; font-size: 0.8rem;">
    © <?= date('Y') ?> IPCA – Sistema Integrado de Gestão Académica
</p>

</body>
</html>