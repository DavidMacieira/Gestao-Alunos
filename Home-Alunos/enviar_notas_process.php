<?php
/**
 * enviar_notas_process.php
 * Endpoint AJAX — envia o boletim de notas do aluno para o seu email via PHPMailer.
 * Responde sempre em JSON.
 */

header('Content-Type: application/json; charset=UTF-8');
session_start();

// ── Segurança ──
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Aluno') {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada ou sem permissão.']);
    exit;
}

// ── Só aceita POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

require_once '../config/db.php';

// ── PHPMailer ──
// Ajusta o caminho conforme a tua estrutura de pastas
if (file_exists(__DIR__ . '/phpmailer/PHPMailer.php')) {
    require __DIR__ . '/phpmailer/Exception.php';
    require __DIR__ . '/phpmailer/PHPMailer.php';
    require __DIR__ . '/phpmailer/SMTP.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    echo json_encode(['success' => false, 'message' => 'PHPMailer não encontrado. Verifique a instalação.']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$aluno_id = $_SESSION['user_id'];

try {
    // ── Dados do aluno e notas ──
    $stmtAluno = $pdo->prepare("
        SELECT u.nome, u.email, c.nome AS curso_nome
        FROM utilizadores u
        JOIN alunos a ON u.id = a.id
        LEFT JOIN cursos c ON a.curso_id = c.id
        WHERE u.id = :id
    ");
    $stmtAluno->execute(['id' => $aluno_id]);
    $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) throw new Exception("Dados do aluno não encontrados.");

    $stmtNotas = $pdo->prepare("
        SELECT n.nota, n.ano_letivo, n.epoca, d.nome_disciplina, d.sigla
        FROM notas n
        JOIN disciplinas d ON n.disciplina_id = d.id
        WHERE n.aluno_id = :id
        ORDER BY n.ano_letivo DESC, d.nome_disciplina ASC
    ");
    $stmtNotas->execute(['id' => $aluno_id]);
    $notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

    if (!$notas) throw new Exception("Não existem notas para enviar.");

    // ── Construir HTML do email ──
    $nomeAluno = htmlspecialchars($aluno['nome']);
    $cursoNome = htmlspecialchars($aluno['curso_nome'] ?? 'N/D');
    $dataEmissao = date('d/m/Y');

    $tabelaNotas = "";
    $aprovadas = 0; $reprovadas = 0; $soma = 0;

    foreach ($notas as $n) {
        $nota    = (float)$n['nota'];
        $cor     = $nota >= 10 ? '#155724' : '#721c24';
        $fundo   = $nota >= 10 ? '#d4edda' : '#f8d7da';
        $soma   += $nota;
        if ($nota >= 10) $aprovadas++; else $reprovadas++;

        $tabelaNotas .= "
        <tr>
            <td style='padding:10px 14px; border-bottom:1px solid #eee;'>" . htmlspecialchars($n['nome_disciplina']) . "</td>
            <td style='padding:10px 14px; border-bottom:1px solid #eee; text-align:center;'>" . htmlspecialchars($n['sigla']) . "</td>
            <td style='padding:10px 14px; border-bottom:1px solid #eee; text-align:center;'>
                <span style='background:{$fundo}; color:{$cor}; padding:4px 10px; border-radius:20px; font-weight:700;'>{$nota}</span>
            </td>
            <td style='padding:10px 14px; border-bottom:1px solid #eee; text-align:center; color:{$cor}; font-weight:600;'>" .
                ($nota >= 10 ? 'Aprovado' : 'Reprovado') .
            "</td>
            <td style='padding:10px 14px; border-bottom:1px solid #eee; text-align:center; color:#6c757d;'>" . htmlspecialchars($n['ano_letivo']) . "</td>
        </tr>";
    }

    $total  = count($notas);
    $media  = $total > 0 ? number_format($soma / $total, 2) : '—';

    $htmlEmail = "
    <!DOCTYPE html>
    <html lang='pt'>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:20px;'>
        <div style='max-width:680px; margin:0 auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08);'>

            <!-- Cabeçalho -->
            <div style='background: linear-gradient(135deg,#007a33,#00a34a); padding: 30px 40px; text-align:center;'>
                <h1 style='color:white; margin:0; font-size:22px; letter-spacing:1px;'>Portal Académico IPCA</h1>
                <p style='color:rgba(255,255,255,0.85); margin:8px 0 0; font-size:14px;'>Boletim de Notas — Emitido a {$dataEmissao}</p>
            </div>

            <!-- Saudação -->
            <div style='padding: 30px 40px 10px;'>
                <p style='font-size:16px;'>Olá, <strong>{$nomeAluno}</strong>!</p>
                <p style='color:#6c757d; font-size:14px;'>
                    Curso: <strong>{$cursoNome}</strong><br>
                    Segue em anexo o seu boletim de notas atualizado.
                </p>
            </div>

            <!-- Resumo -->
            <div style='padding: 0 40px 20px; display:flex; gap:20px;'>
                <div style='flex:1; background:#f8f9fa; border-radius:8px; padding:16px; text-align:center; border-top:4px solid #007a33;'>
                    <div style='font-size:28px; font-weight:700; color:#2c3e50;'>{$media}</div>
                    <div style='font-size:12px; color:#6c757d; text-transform:uppercase; font-weight:600; margin-top:4px;'>Média Global</div>
                </div>
                <div style='flex:1; background:#f0fdf4; border-radius:8px; padding:16px; text-align:center; border-top:4px solid #28a745;'>
                    <div style='font-size:28px; font-weight:700; color:#155724;'>{$aprovadas}</div>
                    <div style='font-size:12px; color:#6c757d; text-transform:uppercase; font-weight:600; margin-top:4px;'>Aprovadas</div>
                </div>
                <div style='flex:1; background:#fef2f2; border-radius:8px; padding:16px; text-align:center; border-top:4px solid #dc3545;'>
                    <div style='font-size:28px; font-weight:700; color:#721c24;'>{$reprovadas}</div>
                    <div style='font-size:12px; color:#6c757d; text-transform:uppercase; font-weight:600; margin-top:4px;'>Reprovadas</div>
                </div>
            </div>

            <!-- Tabela de notas -->
            <div style='padding: 0 40px 30px;'>
                <table style='width:100%; border-collapse:collapse; font-size:14px;'>
                    <thead>
                        <tr style='background:#007a33; color:white;'>
                            <th style='padding:12px 14px; text-align:left;'>Disciplina</th>
                            <th style='padding:12px 14px; text-align:center;'>Sigla</th>
                            <th style='padding:12px 14px; text-align:center;'>Nota</th>
                            <th style='padding:12px 14px; text-align:center;'>Resultado</th>
                            <th style='padding:12px 14px; text-align:center;'>Ano Letivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$tabelaNotas}
                    </tbody>
                </table>
            </div>

            <!-- Rodapé -->
            <div style='background:#f8f9fa; padding:20px 40px; text-align:center; color:#6c757d; font-size:12px; border-top:1px solid #eee;'>
                <p style='margin:0;'>Este email foi enviado automaticamente pelo Portal Académico do IPCA.</p>
                <p style='margin:4px 0 0;'>Para questões, contacte os Serviços Académicos.</p>
            </div>
        </div>
    </body>
    </html>";

    // ── Configurar e enviar com PHPMailer ──
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'davidfloresmacieira@gmail.com'; // ← O teu Gmail
    $mail->Password   = 'dfmu rbaa uwmo hhrd';            // ← Senha de aplicação
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('davidfloresmacieira@gmail.com', 'Portal Académico IPCA');
    $mail->addAddress($aluno['email'], $aluno['nome']);

    $mail->isHTML(true);
    $mail->Subject = "📊 Boletim de Notas — {$nomeAluno}";
    $mail->Body    = $htmlEmail;
    $mail->AltBody = "Olá {$nomeAluno}, o seu boletim de notas foi emitido em {$dataEmissao}. Média global: {$media}.";

    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}