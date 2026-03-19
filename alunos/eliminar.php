<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { header("Location: listar.php"); exit; }

// Verificar se tem notas associadas antes de apagar
$temNotas = $pdo->prepare("SELECT COUNT(*) FROM notas WHERE aluno_id=?");
$temNotas->execute([$id]);
if ($temNotas->fetchColumn() > 0) {
    $_SESSION['flash_error'] = "Não é possível eliminar — o aluno tem notas associadas. Elimine as notas primeiro.";
    header("Location: listar.php"); exit;
}

// Apaga o utilizador (CASCADE elimina o registo em alunos também)
$pdo->prepare("DELETE FROM utilizadores WHERE id=?")->execute([$id]);
$_SESSION['flash_success'] = "Aluno eliminado com sucesso.";
header("Location: listar.php"); exit;