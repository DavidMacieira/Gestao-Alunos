<?php
session_start();
// Proteção: Apenas Admin ou Gestor Pedagógico
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil_nome'], ['Admin', 'Gestor Pedagógico'])){
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/db.php";

// Verifica se o ID foi passado
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Eliminar disciplina
    $stmt = $pdo->prepare("DELETE FROM disciplinas WHERE id=?");
    $stmt->execute([$id]);
}

// Redireciona sempre de volta para a lista
header("Location: listar.php");
exit();
?>