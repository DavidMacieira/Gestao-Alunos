<?php
require_once "../config/db.php";
$erro = ""; 

// Procurar cursos para o select
try {
    $stmtCursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome ASC");
    $cursos = $stmtCursos->fetchAll();
} catch (PDOException $e) {
    $cursos = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome     = $_POST['nome'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $curso_id = !empty($_POST['curso_id']) ? $_POST['curso_id'] : null;

    try {
        $pdo->beginTransaction();

        // 1. Inserir na tabela UTILIZADORES
        $sqlUser = "INSERT INTO utilizadores (nome, email, password, perfil_id) VALUES (?, ?, ?, 1)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$nome, $email, $password]);
        
        // Obter o ID que o MySQL acabou de criar
        $novo_user_id = $pdo->lastInsertId();

        // 2. Inserir na tabela ALUNOS (apenas o ID e o curso)
        $sqlAluno = "INSERT INTO alunos (id, curso_id, estado) VALUES (?, ?, 'Rascunho')";
        $stmtAluno = $pdo->prepare($sqlAluno);
        $stmtAluno->execute([$novo_user_id, $curso_id]);

        $pdo->commit();
        header("Location: login.php?registered=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao registar: " . $e->getMessage();
    }
}
?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Registo – IPCA</title>
        <link rel="stylesheet" href="../style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
    <body>
    <div class="page-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-text-block">
                    <span class="logo-acronym">IPCA</span>
                    <span class="logo-full-name">Gestão Académica</span>
                </div>
            </div>

            <div class="auth-body">
                <h2 class="auth-title">Criar Conta</h2>
                <?php if($erro): ?>
                    <div class="alert-error" style="color:red; margin-bottom:15px;"><?= $erro ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nome Completo *</label>
                        <input class="form-control" type="text" name="nome" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Institucional *</label>
                        <input class="form-control" type="email" name="email" placeholder="utilizador@ipca.pt" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input class="form-control" type="password" name="password" minlength="8" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Curso *</label>
                        <select class="form-control" name="curso_id" required>
                            <option value="">Selecione um curso...</option>
                            <?php foreach($cursos as $curso): ?>
                                <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary">Criar Conta</button>
                </form>
                <div class="auth-footer">
                    Já tem conta? <a href="login.php">Iniciar sessão</a>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>