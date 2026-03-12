<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

$id = $_GET['id'];

// eliminar disciplina
$stmt = $pdo->prepare("DELETE FROM disciplinas WHERE id=?");
$stmt->execute([$id]);

header("Location: listar.php");
exit();