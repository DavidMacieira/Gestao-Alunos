<?php
header('Content-Type: application/json');
session_start();

// 1. Ligar à Base de Dados
require_once '../config/db.php'; 

// 2. Importar PHPMailer (Caminho corrigido para a pasta 'phpmailer')
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$aluno_id = $_SESSION['user_id'];

try {
    // 3. Obter dados do Aluno e Notas
    $stmt = $pdo->prepare("
        SELECT a.nome, a.email, n.nota, n.ano_letivo, d.nome_disciplina 
        FROM alunos a
        JOIN notas n ON a.id = n.aluno_id
        JOIN disciplinas d ON n.disciplina_id = d.id
        WHERE a.id = :id
    ");
    $stmt->execute(['id' => $aluno_id]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$dados) {
        throw new Exception("Não foram encontradas notas para enviar.");
    }

    $nomeAluno = $dados[0]['nome'];
    $emailDestino = $dados[0]['email'];

    // 4. Configurar o Email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'davidfloresmacieira@gmail.com';       // <--- TEU GMAIL AQUI
    $mail->Password   = 'dfmu rbaa uwmo hhrd';         // <--- TUA SENHA DE APP (16 LETRAS)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Remetente e Destinatário
    $mail->setFrom('davidfloresmacieira@gmail.com', 'Portal IPCA');
    $mail->addAddress($emailDestino, $nomeAluno);

    // Conteúdo (Tabela de Notas)
    $mail->isHTML(true);
    $mail->Subject = "As tuas Notas Académicas - $nomeAluno";

    $html = "<h2>Olá, $nomeAluno</h2><p>Aqui estão as tuas notas:</p>";
    $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    $html .= "<tr style='background: #007a33; color: white;'><th>Disciplina</th><th>Nota</th><th>Ano</th></tr>";
    
    foreach ($dados as $linha) {
        $html .= "<tr><td>{$linha['nome_disciplina']}</td><td>{$linha['nota']}</td><td>{$linha['ano_letivo']}</td></tr>";
    }
    $html .= "</table>";

    $mail->Body = $html;

    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Erro: " . $e->getMessage()]);
}