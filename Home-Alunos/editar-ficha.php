<?php
/**
 * editar-ficha.php
 * Redireciona para submeter_matricula.php que é o formulário unificado de edição/submissão.
 * Mantido para compatibilidade com links antigos.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

header("Location: submeter_matricula.php");
exit;