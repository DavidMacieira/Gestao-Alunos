<?php
session_start();
// Verifica se está logado e se é Gestor ou Admin [cite: 27]
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Gestor Pedagógico', 'Admin'])) {
    header("Location: ../auth/login.php?erro=acesso_negado");
    exit();
}

echo "Bem-vindo, Gestor " . $_SESSION['user_nome'];
?>