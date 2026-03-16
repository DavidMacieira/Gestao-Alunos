<?php
session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['foto'])) {
    $aluno_id = $_SESSION['user_id'];
    $arquivo = $_FILES['foto'];
    
    // Validações [cite: 81, 82]
    $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($extensao, $extensoes_permitidas)) {
        $erro = "Apenas JPG ou PNG são permitidos.";
    } elseif ($arquivo['size'] > $max_size) {
        $erro = "O ficheiro é demasiado grande (Máx 2MB).";
    } else {
        $nome_foto = "foto_" . $aluno_id . "." . $extensao;
        $caminho_final = "../uploads/fotos/" . $nome_foto;

        if (move_uploaded_file($arquivo['tmp_name'], $caminho_final)) {
            $sql = "UPDATE alunos SET foto = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$nome_foto, $aluno_id]);
            $sucesso = "Foto atualizada!";
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="foto" accept="image/png, image/jpeg" required>
    <button type="submit">Enviar Foto</button>
</form>