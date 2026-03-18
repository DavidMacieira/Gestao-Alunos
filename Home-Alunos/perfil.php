<?php
/**
 * perfil.php
 * Página de perfil do aluno — vista resumida pública/interna.
 * Para edição completa, redirecionar para submeter_matricula.php.
 */
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Aluno') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/db.php';

$aluno_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT u.nome, u.email, u.created_at,
               a.foto, a.estado, a.observacoes_validacao,
               a.morada, a.telefone, a.data_nascimento,
               c.nome AS curso_nome, c.sigla AS curso_sigla,
               u2.nome AS validado_por_nome, a.data_validacao
        FROM utilizadores u
        JOIN alunos a ON u.id = a.id
        LEFT JOIN cursos c ON a.curso_id = c.id
        LEFT JOIN utilizadores u2 ON a.validado_por = u2.id
        WHERE u.id = :id
    ");
    $stmt->execute(['id' => $aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) die("Erro: Perfil não encontrado.");

    // Contar notas
    $stmtStats = $pdo->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN nota >= 10 THEN 1 ELSE 0 END) AS aprovadas,
               AVG(nota) AS media
        FROM notas WHERE aluno_id = :id
    ");
    $stmtStats->execute(['id' => $aluno_id]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

$estado = $aluno['estado'];
$pode_editar = in_array($estado, ['Rascunho', 'Rejeitada']);

$fotoUrl = !empty($aluno['foto'])
    ? htmlspecialchars($aluno['foto'])
    : "https://ui-avatars.com/api/?name=" . urlencode($aluno['nome']) . "&background=007a33&color=fff&size=200";

$badgeMap = [
    'Rascunho'  => ['class' => 'badge-warning', 'icon' => '✏️'],
    'Submetida' => ['class' => 'badge-info',    'icon' => '📤'],
    'Aprovada'  => ['class' => 'badge-success', 'icon' => '✅'],
    'Rejeitada' => ['class' => 'badge-danger',  'icon' => '❌'],
];
$bm = $badgeMap[$estado] ?? ['class' => 'badge-secondary', 'icon' => 'ℹ️'];

include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --ipca-green: #007a33;
        --ipca-green-light: #00a34a;
        --bg-light: #f4f6f9;
        --card-bg: #ffffff;
        --text-dark: #2c3e50;
        --text-muted: #6c757d;
        --shadow: 0 2px 8px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.04);
    }
    body { font-family: 'Outfit', sans-serif; background: var(--bg-light); color: var(--text-dark); }
    .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }

    .card { background: var(--card-bg); border-radius: 14px; box-shadow: var(--shadow); overflow: hidden; margin-bottom: 1.5rem; }
    .card-padding { padding: 1.5rem 2rem; }
    .card h3 { color: var(--ipca-green); margin-top: 0; border-bottom: 2px solid var(--bg-light); padding-bottom: 0.8rem; }

    /* Capa */
    .perfil-capa { background: linear-gradient(135deg, var(--ipca-green) 0%, var(--ipca-green-light) 100%); height: 130px; position: relative; }
    .perfil-corpo { padding: 0 2rem 2rem; }
    .perfil-foto-wrap { margin-top: -65px; margin-bottom: 1rem; }
    .perfil-foto { width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 5px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    .perfil-nome { font-size: 1.6rem; font-weight: 700; margin: 0; }
    .perfil-email { color: var(--text-muted); margin: 4px 0 10px; }
    .perfil-meta { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }

    /* Badges */
    .badge { display: inline-block; padding: 5px 14px; border-radius: 20px; font-weight: 700; font-size: 0.83rem; }
    .badge-success  { background: #d4edda; color: #155724; }
    .badge-danger   { background: #f8d7da; color: #721c24; }
    .badge-warning  { background: #fff3cd; color: #856404; }
    .badge-info     { background: #d1ecf1; color: #0c5460; }
    .badge-secondary{ background: #e9ecef; color: #495057; }
    .badge-green    { background: var(--ipca-green); color: white; }

    /* Grelha de dados */
    .dados-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem 2rem; }
    .dado-item { padding: 10px 0; border-bottom: 1px dashed #eee; }
    .dado-label { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 3px; }
    .dado-value { font-size: 0.98rem; color: var(--text-dark); }

    /* Stats rápidas */
    .quick-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    .qs-box { text-align: center; padding: 1.2rem; background: var(--bg-light); border-radius: 10px; }
    .qs-box h3 { margin: 0; font-size: 2rem; color: var(--text-dark); }
    .qs-box p  { margin: 4px 0 0; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }
    .qs-media  { border-top: 4px solid var(--ipca-green); }
    .qs-apr    { border-top: 4px solid #28a745; }
    .qs-total  { border-top: 4px solid #17a2b8; }

    /* Ações */
    .perfil-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 1.5rem; }
    .btn { padding: 10px 20px; border-radius: 8px; font-family: 'Outfit'; font-size: 0.92rem; font-weight: 600; text-decoration: none; display: inline-block; cursor: pointer; border: none; transition: all 0.3s; }
    .btn-green  { background: var(--ipca-green); color: white; }
    .btn-green:hover { background: var(--ipca-green-light); }
    .btn-outline { background: transparent; color: var(--ipca-green); border: 2px solid var(--ipca-green); }
    .btn-outline:hover { background: var(--ipca-green); color: white; }
    .btn-grey   { background: #6c757d; color: white; opacity: 0.7; cursor: not-allowed; }

    .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 1rem; border-left: 5px solid; }
    .alert-danger  { background: #f8d7da; color: #721c24; border-color: #dc3545; }

    @media (max-width: 600px) {
        .dados-grid  { grid-template-columns: 1fr; }
        .quick-stats { grid-template-columns: 1fr; }
    }
</style>

<div class="container">
    <a href="dashboard.php" style="color:var(--text-muted); text-decoration:none; font-size:0.9rem;">← Voltar ao Dashboard</a>

    <!-- Card Perfil principal -->
    <div class="card" style="margin-top:1rem;">
        <div class="perfil-capa"></div>
        <div class="perfil-corpo">
            <div class="perfil-foto-wrap">
                <img src="<?= $fotoUrl ?>" alt="Foto" class="perfil-foto"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($aluno['nome']) ?>&background=007a33&color=fff&size=200'">
            </div>

            <h2 class="perfil-nome"><?= htmlspecialchars($aluno['nome']) ?></h2>
            <p class="perfil-email"><?= htmlspecialchars($aluno['email']) ?></p>

            <div class="perfil-meta">
                <span class="badge badge-green"><?= htmlspecialchars($aluno['curso_nome'] ?? 'Sem curso') ?></span>
                <span class="badge <?= $bm['class'] ?>"><?= $bm['icon'] ?> <?= htmlspecialchars($estado) ?></span>
                <?php if (!empty($aluno['created_at'])): ?>
                    <span style="font-size:0.85rem; color:var(--text-muted);">
                        Membro desde <?= date('M Y', strtotime($aluno['created_at'])) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($estado === 'Rejeitada' && !empty($aluno['observacoes_validacao'])): ?>
                <div class="alert alert-danger" style="margin-top:1rem;">
                    ❌ Motivo da rejeição: <?= htmlspecialchars($aluno['observacoes_validacao']) ?>
                </div>
            <?php endif; ?>

            <div class="perfil-actions">
                <a href="dashboard.php" class="btn btn-green">📊 Dashboard</a>
                <?php if ($pode_editar): ?>
                    <a href="submeter_matricula.php" class="btn btn-outline">✏️ Editar Ficha</a>
                <?php endif; ?>
                <a href="upload_foto.php" class="btn btn-outline">📷 Alterar Foto</a>
                <?php if ($estado === 'Aprovada'): ?>
                    <a href="inscricao.php" class="btn btn-green" style="background:#2c3e50;">📝 Inscrição</a>
                <?php else: ?>
                    <button class="btn btn-grey" disabled title="Disponível após aprovação">📝 Inscrição Bloqueada</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats rápidas -->
    <div class="card card-padding">
        <h3>📊 Resumo Académico</h3>
        <div class="quick-stats">
            <div class="qs-box qs-media">
                <h3><?= $stats['total'] > 0 ? number_format((float)$stats['media'], 2) : '—' ?></h3>
                <p>Média Global</p>
            </div>
            <div class="qs-box qs-apr">
                <h3><?= (int)($stats['aprovadas'] ?? 0) ?></h3>
                <p>Aprovadas</p>
            </div>
            <div class="qs-box qs-total">
                <h3><?= (int)($stats['total'] ?? 0) ?></h3>
                <p>Total de Notas</p>
            </div>
        </div>
    </div>

    <!-- Dados pessoais -->
    <div class="card card-padding">
        <h3>👤 Dados Pessoais
            <?php if ($pode_editar): ?>
                <a href="submeter_matricula.php" style="font-size:0.8rem; float:right; color:var(--ipca-green); text-decoration:none; font-weight:600;">✏️ Editar</a>
            <?php endif; ?>
        </h3>
        <div class="dados-grid">
            <div class="dado-item">
                <div class="dado-label">Nome Completo</div>
                <div class="dado-value"><?= htmlspecialchars($aluno['nome']) ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Email</div>
                <div class="dado-value"><?= htmlspecialchars($aluno['email']) ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Morada</div>
                <div class="dado-value"><?= htmlspecialchars($aluno['morada'] ?? '—') ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Telefone</div>
                <div class="dado-value"><?= htmlspecialchars($aluno['telefone'] ?? '—') ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Data de Nascimento</div>
                <div class="dado-value">
                    <?= !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '—' ?>
                </div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Curso</div>
                <div class="dado-value"><?= htmlspecialchars($aluno['curso_nome'] ?? '—') ?></div>
            </div>
        </div>

        <?php if (!empty($aluno['data_validacao'])): ?>
        <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid #eee;">
            <h4 style="margin:0 0 0.8rem; color:var(--text-muted); font-size:0.85rem; text-transform:uppercase;">Última Validação</h4>
            <div class="dados-grid">
                <div class="dado-item">
                    <div class="dado-label">Validado por</div>
                    <div class="dado-value"><?= htmlspecialchars($aluno['validado_por_nome'] ?? '—') ?></div>
                </div>
                <div class="dado-item">
                    <div class="dado-label">Data</div>
                    <div class="dado-value"><?= date('d/m/Y H:i', strtotime($aluno['data_validacao'])) ?></div>
                </div>
                <?php if (!empty($aluno['observacoes_validacao'])): ?>
                <div class="dado-item" style="grid-column: span 2;">
                    <div class="dado-label">Observações</div>
                    <div class="dado-value"><?= htmlspecialchars($aluno['observacoes_validacao']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>