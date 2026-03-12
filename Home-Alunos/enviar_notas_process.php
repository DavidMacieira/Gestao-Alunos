<?php
session_start();
require_once '../config/db.php';

// Verificação de segurança
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
    exit;
}

$aluno_id = $_SESSION['user_id'];

try {
    // 1. Ir buscar os mesmos dados que tens no dashboard
    $stmt = $pdo->prepare("
        SELECT n.nota, n.epoca, n.ano_letivo, d.nome_disciplina, a.nome, a.email 
        FROM notas n 
        JOIN disciplinas d ON n.disciplina_id = d.id 
        JOIN alunos a ON n.aluno_id = a.id
        WHERE n.aluno_id = :id
        ORDER BY n.ano_letivo DESC
    ");
    $stmt->execute(['id' => $aluno_id]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$notas) {
        echo json_encode(['success' => false, 'message' => 'Não foram encontradas notas.']);
        exit;
    }

    $nomeAluno = $notas[0]['nome'];
    $emailDestino = $notas[0]['email'];

    // 2. Construir o corpo do Email em HTML
    $mensagemHTML = "<h2>Olá, $nomeAluno</h2>";
    $mensagemHTML .= "<p>Aqui está o seu histórico de notas extraído do portal:</p>";
    $mensagemHTML .= "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    $mensagemHTML .= "<tr style='background: #007a33; color: white;'><th>Ano</th><th>Disciplina</th><th>Nota</th><th>Época</th></tr>";

    foreach ($notas as $n) {
        $corNota = ($n['nota'] >= 10) ? "black" : "red";
        $mensagemHTML .= "<tr>
            <td>{$n['ano_letivo']}</td>
            <td>{$n['nome_disciplina']}</td>
            <td style='color: $corNota; font-weight: bold;'>{$n['nota']}</td>
            <td>{$n['epoca']}</td>
        </tr>";
    }
    $mensagemHTML .= "</table><p>Gerado automaticamente em: " . date('d/m/Y H:i') . "</p>";

    // 3. Enviar o Email
    // Se usares PHPMailer (Recomendado), configurarias aqui. 
    // Exemplo simplificado com mail() (pode ir para SPAM ou não funcionar em localhost sem config):
    $to = $emailDestino;
    $subject = "Histórico de Notas - $nomeAluno";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Portal Académico <noreply@teudominio.com>" . "\r\n";

    if (mail($to, $subject, $mensagemHTML, $headers)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'O servidor não conseguiu enviar o email.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}