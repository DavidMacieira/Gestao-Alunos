<?php
session_start();
if(!isset($_SESSION['perfil']) || $_SESSION['perfil'] != 'Admin'){
    header("Location: ../index.php");
    exit();
}

require_once "../config/db.php";

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM notas WHERE id=?");
$stmt->execute([$id]);

header("Location: listar.php");
exit();