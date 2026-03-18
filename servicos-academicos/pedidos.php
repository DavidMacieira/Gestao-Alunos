<?php
/**
 * servicos_academicos/pedidos.php
 * RF4 — Cenário 4: Funcionário aprova/rejeita pedidos de matrícula.
 * Regista: funcionario_id e data_decisao.
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Funcionário') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$func_id = $_SESSION['user_id'];

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ── Processar decisão via POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = (int)($_POST['pedido_id'] ?? 0);
    $acao      = $_POST['acao'] ?? '';
    $obs       = trim($_POST['observacoes'] ?? '');

    if ($pedido_id > 0 && in_array($acao, ['aprovar', 'rejeitar'])) {

        if ($acao === 'rejeitar' && empty($obs)) {
            $_SESSION['flash_error'] = "É obrigatório indicar o motivo da rejeição.";
            header("Location: pedidos.php?id={$pedido_id}"); exit;
        }

        // Verificar se pedido ainda está Pendente
        $chk = $pdo->prepare("SELECT estado, aluno_id FROM pedidos_matricula WHERE id=?");
        $chk->execute([$pedido_id]);
        $pedido = $chk->fetch(PDO::FETCH_ASSOC);

        if ($pedido && $pedido['estado'] === 'Pendente') {
            $novo_estado = $acao === 'aprovar' ? 'Aprovado' : 'Rejeitado';
            $pdo->prepare("
                UPDATE pedidos_matricula
                SET estado=?, observacoes=?, funcionario_id=?, data_decisao=NOW()
                WHERE id=?
            ")->execute([$novo_estado, $obs ?: null, $func_id, $pedido_id]);

            $label = $acao === 'aprovar' ? 'aprovado' : 'rejeitado';
            $_SESSION['flash_success'] = "Pedido #{$pedido_id} {$label} com sucesso.";
        } else {
            $_SESSION['flash_error'] = "Este pedido já foi processado anteriormente.";
        }
    }
    header("Location: pedidos.php"); exit;
}

// ── Filtros ──
$filtro_estado = $_GET['estado'] ?? 'Pendente';
$filtro_q      = trim($_GET['q'] ?? '');
$detalhe_id    = (int)($_GET['id'] ?? 0);

$where  = ["1=1"];
$params = [];

if ($filtro_estado && $filtro_estado !== 'all') {
    $where[]  = "pm.estado = ?";
    $params[] = $filtro_estado;
}
if ($filtro_q) {
    $where[]  = "(u.nome LIKE ? OR u.email LIKE ? OR c.nome LIKE ?)";
    $params[] = "%{$filtro_q}%";
    $params[] = "%{$filtro_q}%";
    $params[] = "%{$filtro_q}%";
}

$pedidos = $pdo->prepare("
    SELECT pm.id, pm.ano_letivo, pm.estado, pm.data_pedido, pm.observacoes, pm.data_decisao,
           u.nome AS aluno_nome, u.email AS aluno_email,
           c.nome AS curso_nome,
           uf.nome AS funcionario_nome
    FROM pedidos_matricula pm
    JOIN alunos a ON a.id = pm.aluno_id
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON c.id = pm.curso_id
    LEFT JOIN utilizadores uf ON uf.id = pm.funcionario_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY pm.estado='Pendente' DESC, pm.data_pedido ASC
");
$pedidos->execute($params);
$pedidos = $pedidos->fetchAll(PDO::FETCH_ASSOC);

// Contagens
$counts = $pdo->query("SELECT estado, COUNT(*) as n FROM pedidos_matricula GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);

// Detalhe para modal inline
$pedido_detalhe = null;
if ($detalhe_id > 0) {
    $sd = $pdo->prepare("
        SELECT pm.*, u.nome AS aluno_nome, u.email AS aluno_email,
               c.nome AS curso_nome, a.morada, a.telefone, a.data_nascimento, a.foto
        FROM pedidos_matricula pm
        JOIN alunos a ON a.id = pm.aluno_id
        JOIN utilizadores u ON u.id = a.id
        LEFT JOIN cursos c ON c.id = pm.curso_id
        WHERE pm.id = ?
    ");
    $sd->execute([$detalhe_id]);
    $pedido_detalhe = $sd->fetch(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1100px; margin:0 auto; }
.page-hd { margin-bottom:24px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.page-hd p  { font-size:13px; color:var(--text-muted); margin-top:4px; }
.tab-bar { display:flex; gap:2px; border-bottom:var(--border); margin-bottom:20px; }
.tab { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; font-size:13px; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; text-decoration:none; transition:all .15s; margin-bottom:-1px; }
.tab:hover { color:var(--black); }
.tab.active { color:var(--black); border-bottom-color:var(--black); font-weight:500; }
.tab-count { font-size:10.5px; padding:1px 6px; border-radius:10px; background:var(--gray-50); color:var(--text-muted); border:var(--border); }
.tab.active .tab-count { background:var(--black); color:white; border-color:var(--black); }
.filter-bar { display:flex; gap:8px; margin-bottom:16px; }
.filter-bar input { height:36px; padding:0 12px; border:var(--border); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13px; outline:none; flex:1; min-width:200px; transition:border-color .15s; }
.filter-bar input:focus { border-color:var(--gray-400); }
.empty-state { text-align:center; padding:50px 20px; color:var(--text-faint); font-size:13px; }
/* Painel de detalhe/decisão */
.detail-panel { background:var(--white); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; margin-bottom:24px; }
.dp-head { padding:18px 24px; border-bottom:var(--border); display:flex; align-items:center; justify-content:space-between; }
.dp-head h3 { font-size:14px; font-weight:500; color:var(--black); }
.dp-body { padding:24px; display:grid; grid-template-columns:1fr 1.2fr; gap:24px; }
.dl-row { display:flex; justify-content:space-between; align-items:flex-start; padding:9px 0; border-bottom:var(--border); gap:12px; }
.dl-row:last-child { border-bottom:none; }
.dl-label { font-size:11px; font-weight:500; text-transform:uppercase; letter-spacing:.07em; color:var(--text-faint); flex-shrink:0; }
.dl-value { font-size:13px; color:var(--black); text-align:right; }
.btn-row { display:flex; gap:10px; margin-top:20px; }
@media(max-width:700px){ .dp-body{grid-template-columns:1fr;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <h1>Pedidos de Matrícula</h1>
        <p>Cenário 4 — Aprovar ou rejeitar pedidos dos alunos</p>
    </div>

    <!-- Painel de decisão (se vier ?id=X) -->
    <?php if ($pedido_detalhe): ?>
    <div class="detail-panel">
        <div class="dp-head">
            <h3>Processar Pedido #<?= (int)$pedido_detalhe['id'] ?></h3>
            <a href="pedidos.php?estado=<?= urlencode($filtro_estado) ?>" class="btn btn-sm">Fechar</a>
        </div>
        <div class="dp-body">
            <!-- Dados do pedido/aluno -->
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.07em;color:var(--text-faint);margin-bottom:12px;">Dados do Aluno</div>
                <div class="dl-row"><span class="dl-label">Nome</span><span class="dl-value"><?= htmlspecialchars($pedido_detalhe['aluno_nome']) ?></span></div>
                <div class="dl-row"><span class="dl-label">Email</span><span class="dl-value"><?= htmlspecialchars($pedido_detalhe['aluno_email']) ?></span></div>
                <div class="dl-row"><span class="dl-label">Curso</span><span class="dl-value"><?= htmlspecialchars($pedido_detalhe['curso_nome'] ?? '—') ?></span></div>
                <div class="dl-row"><span class="dl-label">Ano Letivo</span><span class="dl-value"><?= htmlspecialchars($pedido_detalhe['ano_letivo'] ?? '—') ?></span></div>
                <div class="dl-row"><span class="dl-label">Data do Pedido</span><span class="dl-value"><?= date('d/m/Y H:i', strtotime($pedido_detalhe['data_pedido'])) ?></span></div>
                <div class="dl-row"><span class="dl-label">Morada</span><span class="dl-value" style="max-width:180px;text-align:right;"><?= htmlspecialchars($pedido_detalhe['morada'] ?? '—') ?></span></div>
                <div class="dl-row"><span class="dl-label">Telefone</span><span class="dl-value"><?= htmlspecialchars($pedido_detalhe['telefone'] ?? '—') ?></span></div>
                <?php if (!empty($pedido_detalhe['observacoes'])): ?>
                <div class="dl-row"><span class="dl-label">Obs. Aluno</span><span class="dl-value" style="max-width:180px;text-align:right;color:var(--text-muted);"><?= htmlspecialchars($pedido_detalhe['observacoes']) ?></span></div>
                <?php endif; ?>
            </div>

            <!-- Formulário de decisão -->
            <div>
                <div style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.07em;color:var(--text-faint);margin-bottom:12px;">Decisão</div>

                <?php if ($pedido_detalhe['estado'] !== 'Pendente'): ?>
                    <div class="alert alert-info">
                        Este pedido já foi processado com o estado
                        <strong><?= htmlspecialchars($pedido_detalhe['estado']) ?></strong>.
                        Pode alterar a decisão abaixo.
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="pedido_id" value="<?= (int)$pedido_detalhe['id'] ?>">

                    <div class="form-group">
                        <label class="form-label" for="observacoes">Observações
                            <span style="font-size:10px;color:var(--text-faint);text-transform:none;letter-spacing:0;margin-left:4px;">(obrigatório ao rejeitar)</span>
                        </label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="4"
                                  placeholder="Justificação da decisão..."><?= htmlspecialchars($pedido_detalhe['observacoes'] ?? '') ?></textarea>
                    </div>

                    <div class="btn-row">
                        <button type="submit" name="acao" value="aprovar" class="btn btn-green"
                                onclick="return confirm('Aprovar este pedido de matrícula?')">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6.5"/><path d="M5 8l2 2 4-4"/></svg>
                            Aprovar
                        </button>
                        <button type="submit" name="acao" value="rejeitar" class="btn btn-danger"
                                onclick="return validarRejeicao()">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6.5"/><line x1="5.5" y1="5.5" x2="10.5" y2="10.5"/><line x1="10.5" y1="5.5" x2="5.5" y2="10.5"/></svg>
                            Rejeitar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tab-bar">
        <?php
        $tabs = ['Pendente'=>'Pendentes','Aprovado'=>'Aprovados','Rejeitado'=>'Rejeitados','all'=>'Todos'];
        foreach ($tabs as $val => $lbl):
            $cnt    = $val === 'all' ? array_sum($counts) : (int)($counts[$val] ?? 0);
            $active = $filtro_estado === $val ? 'active' : '';
            $url    = '?estado=' . urlencode($val) . ($filtro_q ? '&q='.urlencode($filtro_q) : '');
        ?>
        <a href="<?= $url ?>" class="tab <?= $active ?>">
            <?= $lbl ?> <span class="tab-count"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Pesquisa -->
    <div class="filter-bar">
        <form method="GET" style="display:contents;">
            <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_estado) ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($filtro_q) ?>" placeholder="Pesquisar por aluno, email ou curso...">
            <button type="submit" class="btn btn-sm">Pesquisar</button>
            <?php if ($filtro_q): ?><a href="?estado=<?= urlencode($filtro_estado) ?>" class="btn btn-sm">Limpar</a><?php endif; ?>
        </form>
    </div>

    <!-- Tabela -->
    <div class="card">
        <?php if (empty($pedidos)): ?>
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1" style="display:block;margin:0 auto 12px;opacity:.3"><path d="M20 4L34 32H6L20 4z"/><line x1="20" y1="16" x2="20" y2="24"/><circle cx="20" cy="29" r="1"/></svg>
                Nenhum pedido encontrado.
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Aluno</th>
                    <th>Curso</th>
                    <th>Ano Letivo</th>
                    <th>Estado</th>
                    <th>Data Pedido</th>
                    <th>Decisão</th>
                    <th style="text-align:right;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $p):
                    $bc = ['Pendente'=>'badge-pendente','Aprovado'=>'badge-aprovada','Rejeitado'=>'badge-rejeitada'][$p['estado']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td style="color:var(--text-faint);font-size:12px;">#<?= (int)$p['id'] ?></td>
                    <td>
                        <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($p['aluno_nome']) ?></div>
                        <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($p['aluno_email']) ?></div>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($p['curso_nome'] ?? '—') ?></td>
                    <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($p['ano_letivo'] ?? '—') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($p['estado']) ?></span></td>
                    <td style="font-size:12px;color:var(--text-faint);white-space:nowrap;"><?= date('d/m/Y', strtotime($p['data_pedido'])) ?></td>
                    <td style="font-size:12px;color:var(--text-faint);">
                        <?php if (!empty($p['data_decisao'])): ?>
                            <?= date('d/m/Y', strtotime($p['data_decisao'])) ?><br>
                            <span style="font-size:11px;"><?= htmlspecialchars($p['funcionario_nome'] ?? '') ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <a href="pedidos.php?id=<?= (int)$p['id'] ?>&estado=<?= urlencode($filtro_estado) ?>"
                           class="btn btn-sm <?= $p['estado']==='Pendente' ? 'btn-green' : '' ?>">
                            <?= $p['estado']==='Pendente' ? 'Processar' : 'Ver' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
function validarRejeicao() {
    const obs = document.getElementById('observacoes').value.trim();
    if (!obs) {
        alert('É obrigatório indicar o motivo da rejeição.');
        document.getElementById('observacoes').focus();
        return false;
    }
    return confirm('Rejeitar este pedido de matrícula?\n\nMotivo: ' + obs);
}
</script>
<?php include '../includes/footer.php'; ?>