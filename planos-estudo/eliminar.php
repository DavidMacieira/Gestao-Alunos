<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

$id = $_GET['id'];

// eliminar associação
$stmt = $pdo->prepare("DELETE FROM plano_estudos WHERE id=?");
$stmt->execute([$id]);

header("Location: listar.php");
exit();