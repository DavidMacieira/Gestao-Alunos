<?php
/**
 * gestor/validar_ficha.php
 * Cenário de Demonstração 2 — Gestor Pedagógico valida/rejeita ficha com observações.
 * RF3.2 — aprovar ou rejeitar a ficha + registar observações/justificação.
 * Regista: quem validou (validado_por) e quando (data_validacao).
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Gestor Pedagógico') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$gestor_id = $_SESSION['user_id'];
$aluno_id  = (int)($_GET['id'] ?? 0);

if ($aluno_id <= 0) { header("Location: fichas.php"); exit; }

// Carregar dados do aluno
$stmt = $pdo->prepare("
    SELECT a.*, u.nome, u.email, c.nome AS curso_nome
    FROM alunos a
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON a.curso_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) { header("Location: fichas.php"); exit; }

$mensagem = "";
$tipo_msg = "danger";

// ── Processar decisão ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao     = $_POST['acao']          ?? '';
    $obs      = trim($_POST['observacoes'] ?? '');

    if (!in_array($acao, ['aprovar', 'rejeitar'])) {
        $mensagem = "Ação inválida.";
    } elseif ($acao === 'rejeitar' && empty($obs)) {
        $mensagem = "É obrigatório indicar o motivo da rejeição.";
    } elseif (!in_array($aluno['estado'], ['Submetida', 'Aprovada', 'Rejeitada'])) {
        $mensagem = "Esta ficha não pode ser validada no estado atual ({$aluno['estado']}).";
    } else {
        $novo_estado = $acao === 'aprovar' ? 'Aprovada' : 'Rejeitada';

        try {
            $pdo->prepare("
                UPDATE alunos
                SET estado = ?,
                    observacoes_validacao = ?,
                    validado_por = ?,
                    data_validacao = NOW()
                WHERE id = ?
            ")->execute([$novo_estado, $obs ?: null, $gestor_id, $aluno_id]);

            $label = $acao === 'aprovar' ? 'aprovada' : 'rejeitada';
            $_SESSION['flash_success'] = "Ficha de {$aluno['nome']} {$label} com sucesso.";
            header("Location: fichas.php");
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro na base de dados: " . $e->getMessage();
        }
    }
}

// Foto
$fotoUrl = !empty($aluno['foto'])
    ? htmlspecialchars($aluno['foto'])
    : "https://ui-avatars.com/api/?name=" . urlencode($aluno['nome']) . "&background=1A1A1A&color=fff&size=120&bold=true";

include '../includes/header.php';
?>
<style>
.page-body { padding: 32px 28px; max-width: 860px; margin: 0 auto; }
.back-link { display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text-muted);margin-bottom:24px;transition:color .15s; }
.back-link:hover { color:var(--black); }
.back-link svg { width:14px;height:14px; }
.page-hd { margin-bottom:24px; }
.page-hd h1 { font-size:22px;font-family:'DM Serif Display',serif;font-weight:400;color:var(--black);letter-spacing:-.01em; }
.page-hd p  { font-size:13px;color:var(--text-muted);margin-top:4px; }
.grid-2 { display:grid;grid-template-columns:1fr 1.4fr;gap:20px; }
.profile-block { display:flex;align-items:flex-start;gap:16px;margin-bottom:20px; }
.profile-avatar { width:60px;height:60px;border-radius:50%;object-fit:cover;border:var(--border);flex-shrink:0; }
.profile-name { font-size:17px;font-family:'DM Serif Display',serif;font-weight:400;color:var(--black);letter-spacing:-.01em;margin-bottom:3px; }
.profile-email { font-size:12px;color:var(--text-faint); }
.data-list { display:flex;flex-direction:column;gap:0; }
.dl-row { display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:var(--border);gap:12px; }
.dl-row:last-child { border-bottom:none; }
.dl-label { font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.07em;color:var(--text-faint);flex-shrink:0; }
.dl-value { font-size:13px;color:var(--black);text-align:right; }
.decision-card { background:var(--white);border:var(--border);border-radius:var(--radius-lg);overflow:hidden; }
.dc-head { padding:20px 24px;border-bottom:var(--border); }
.dc-head h3 { font-size:13px;font-weight:500;color:var(--black);margin:0; }
.dc-head p  { font-size:12px;color:var(--text-muted);margin-top:3px; }
.dc-body { padding:24px; }
.btn-row { display:flex;gap:10px;margin-top:20px; }
.already-validated { background:var(--gray-50);border-radius:var(--radius-md);padding:20px;text-align:center;font-size:13px;color:var(--text-muted); }
.already-validated strong { color:var(--black); }
@media(max-width:700px){ .grid-2{grid-template-columns:1fr;} }
</style>

<div class="page-body">
    <a href="fichas.php" class="back-link">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
        Voltar à lista de fichas
    </a>

    <div class="page-hd">
        <h1>Validação de Ficha</h1>
        <p>Cenário 2 — Aprovar ou rejeitar ficha com observações</p>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-danger" style="margin-bottom:20px;"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Dados do aluno -->
        <div class="card">
            <div class="card-body">
                <h3 style="margin-bottom:16px;">Dados do Aluno</h3>

                <div class="profile-block">
                    <img src="<?= $fotoUrl ?>" alt="Foto" class="profile-avatar"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($aluno['nome']) ?>&background=1A1A1A&color=fff&size=120'">
                    <div>
                        <div class="profile-name"><?= htmlspecialchars($aluno['nome']) ?></div>
                        <div class="profile-email"><?= htmlspecialchars($aluno['email']) ?></div>
                    </div>
                </div>

                <div class="data-list">
                    <div class="dl-row">
                        <span class="dl-label">Estado</span>
                        <?php
                        $bc = ['Submetida'=>'badge-submetida','Aprovada'=>'badge-aprovada','Rejeitada'=>'badge-rejeitada','Rascunho'=>'badge-rascunho'][$aluno['estado']] ?? 'badge-secondary';
                        ?>
                        <span class="dl-value"><span class="badge <?= $bc ?>"><?= htmlspecialchars($aluno['estado']) ?></span></span>
                    </div>
                    <div class="dl-row">
                        <span class="dl-label">Curso</span>
                        <span class="dl-value"><?= htmlspecialchars($aluno['curso_nome'] ?? '—') ?></span>
                    </div>
                    <div class="dl-row">
                        <span class="dl-label">Morada</span>
                        <span class="dl-value" style="max-width:200px;text-align:right;"><?= htmlspecialchars($aluno['morada'] ?? '—') ?></span>
                    </div>
                    <div class="dl-row">
                        <span class="dl-label">Telefone</span>
                        <span class="dl-value"><?= htmlspecialchars($aluno['telefone'] ?? '—') ?></span>
                    </div>
                    <div class="dl-row">
                        <span class="dl-label">Data Nasc.</span>
                        <span class="dl-value">
                            <?= !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '—' ?>
                        </span>
                    </div>
                    <?php if (!empty($aluno['data_validacao'])): ?>
                    <div class="dl-row">
                        <span class="dl-label">Última validação</span>
                        <span class="dl-value"><?= date('d/m/Y H:i', strtotime($aluno['data_validacao'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($aluno['observacoes_validacao'])): ?>
                    <div class="dl-row">
                        <span class="dl-label">Obs. anteriores</span>
                        <span class="dl-value" style="max-width:200px;text-align:right;color:var(--text-muted);"><?= htmlspecialchars($aluno['observacoes_validacao']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Decisão -->
        <div class="decision-card">
            <div class="dc-head">
                <h3>Decisão do Gestor Pedagógico</h3>
                <p>A decisão fica registada com a sua identificação e data/hora.</p>
            </div>
            <div class="dc-body">

                <?php if ($aluno['estado'] === 'Rascunho'): ?>
                    <div class="already-validated">
                        A ficha está em <strong>Rascunho</strong> e ainda não foi submetida pelo aluno.
                        Não é possível validar.
                    </div>

                <?php else: ?>

                <?php if ($aluno['estado'] !== 'Submetida'): ?>
                    <div class="alert alert-info" style="margin-bottom:20px;">
                        Esta ficha já foi processada anteriormente (estado: <strong><?= htmlspecialchars($aluno['estado']) ?></strong>).
                        Pode alterar a decisão abaixo.
                    </div>
                <?php endif; ?>

                <form method="POST" id="validacaoForm">

                    <div class="form-group">
                        <label class="form-label" for="observacoes">
                            Observações / Justificação
                            <?php if (true): ?><span style="font-size:10px;color:var(--text-faint);text-transform:none;letter-spacing:0;margin-left:4px;">(obrigatório ao rejeitar)</span><?php endif; ?>
                        </label>
                        <textarea name="observacoes" id="observacoes" class="form-control"
                                  rows="4"
                                  placeholder="Indique observações para o aluno (obrigatório se rejeitar)..."><?= htmlspecialchars($aluno['observacoes_validacao'] ?? '') ?></textarea>
                    </div>

                    <div class="btn-row">
                        <button type="submit" name="acao" value="aprovar"
                                class="btn btn-green"
                                onclick="return confirm('Aprovar a ficha de <?= htmlspecialchars(addslashes($aluno['nome'])) ?>?')">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="8" cy="8" r="6.5"/><path d="M5 8l2 2 4-4"/>
                            </svg>
                            Aprovar Ficha
                        </button>
                        <button type="submit" name="acao" value="rejeitar"
                                class="btn btn-danger"
                                onclick="return validarRejeicao()">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="8" cy="8" r="6.5"/><line x1="5.5" y1="5.5" x2="10.5" y2="10.5"/><line x1="10.5" y1="5.5" x2="5.5" y2="10.5"/>
                            </svg>
                            Rejeitar Ficha
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function validarRejeicao() {
    const obs = document.getElementById('observacoes').value.trim();
    if (!obs) {
        alert('É obrigatório indicar o motivo da rejeição no campo de observações.');
        document.getElementById('observacoes').focus();
        return false;
    }
    return confirm('Rejeitar a ficha de <?= htmlspecialchars(addslashes($aluno['nome'])) ?>?\n\nMotivo: ' + obs);
}
</script>
<?php include '../includes/footer.php'; ?>