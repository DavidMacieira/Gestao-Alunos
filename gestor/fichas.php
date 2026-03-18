<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Gestor Pedagógico') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filtros
$filtro_estado = $_GET['estado'] ?? 'Submetida';
$filtro_search = trim($_GET['q'] ?? '');

$where  = ["1=1"];
$params = [];

if ($filtro_estado && $filtro_estado !== 'all') {
    $where[]  = "a.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_search) {
    $where[]  = "(u.nome LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$filtro_search}%";
    $params[] = "%{$filtro_search}%";
}

$sql = "
    SELECT a.id, a.estado, a.data_validacao, a.observacoes_validacao,
           u.nome, u.email,
           c.nome AS curso_nome,
           u2.nome AS validado_por_nome
    FROM alunos a
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON a.curso_id = c.id
    LEFT JOIN utilizadores u2 ON a.validado_por = u2.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.estado = 'Submetida' DESC, a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contagens por estado
$counts = $pdo->query("SELECT estado, COUNT(*) as n FROM alunos GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);

include '../includes/header.php';
?>
<style>
.page-body { padding: 32px 28px; max-width: 1100px; margin: 0 auto; }
.page-hd { margin-bottom: 24px; }
.page-hd h1 { font-size: 22px; font-family: 'DM Serif Display', serif; font-weight: 400; color: var(--black); letter-spacing: -.01em; }
.page-hd p  { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-bar input { height: 36px; padding: 0 12px; border: var(--border); border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--black); background: var(--white); outline: none; min-width: 220px; flex: 1; transition: border-color .15s; }
.filter-bar input:focus { border-color: var(--gray-400); }
.tab-bar { display: flex; gap: 2px; border-bottom: var(--border); margin-bottom: 20px; }
.tab { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; font-size: 13px; color: var(--text-muted); border-bottom: 2px solid transparent; cursor: pointer; text-decoration: none; transition: all .15s; margin-bottom: -1px; }
.tab:hover { color: var(--black); }
.tab.active { color: var(--black); border-bottom-color: var(--black); font-weight: 500; }
.tab-count { font-size: 10.5px; padding: 1px 6px; border-radius: 10px; background: var(--gray-50); color: var(--text-muted); border: var(--border); }
.tab.active .tab-count { background: var(--black); color: white; border-color: var(--black); }
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-faint); font-size: 13px; }
.empty-state svg { display: block; margin: 0 auto 12px; opacity: .3; }
</style>

<div class="page-body">
    <?php if ($flash_success): ?>
        <div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>

    <div class="page-hd">
        <h1>Fichas de Aluno</h1>
        <p>Validar, aprovar ou rejeitar fichas submetidas pelos alunos.</p>
    </div>

    <!-- Tabs por estado -->
    <div class="tab-bar">
        <?php
        $tabEstados = ['all' => 'Todas', 'Submetida' => 'Submetidas', 'Aprovada' => 'Aprovadas', 'Rejeitada' => 'Rejeitadas', 'Rascunho' => 'Rascunho'];
        foreach ($tabEstados as $val => $lbl):
            $cnt = $val === 'all' ? array_sum($counts) : (int)($counts[$val] ?? 0);
            $active = ($filtro_estado === $val || ($val === 'all' && !$filtro_estado)) ? 'active' : '';
            $url = '?estado=' . urlencode($val) . ($filtro_search ? '&q=' . urlencode($filtro_search) : '');
        ?>
        <a href="<?= $url ?>" class="tab <?= $active ?>">
            <?= $lbl ?>
            <span class="tab-count"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Barra de pesquisa -->
    <div class="filter-bar">
        <form method="GET" style="display:contents;">
            <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_estado) ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($filtro_search) ?>" placeholder="Pesquisar por nome ou email...">
            <button type="submit" class="btn btn-sm">Pesquisar</button>
            <?php if ($filtro_search): ?>
                <a href="?estado=<?= urlencode($filtro_estado) ?>" class="btn btn-sm">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela -->
    <div class="card">
        <?php if (empty($fichas)): ?>
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1"><rect x="6" y="3" width="28" height="34" rx="3"/><line x1="13" y1="13" x2="27" y2="13"/><line x1="13" y1="20" x2="27" y2="20"/><line x1="13" y1="27" x2="21" y2="27"/></svg>
                Nenhuma ficha encontrada com os filtros aplicados.
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Curso</th>
                    <th>Estado</th>
                    <th>Validado por</th>
                    <th>Data Validação</th>
                    <th style="text-align:right;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fichas as $f):
                    $bc = ['Submetida'=>'badge-submetida','Aprovada'=>'badge-aprovada','Rejeitada'=>'badge-rejeitada','Rascunho'=>'badge-rascunho'][$f['estado']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($f['nome']) ?></div>
                        <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($f['email']) ?></div>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($f['curso_nome'] ?? '—') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($f['estado']) ?></span></td>
                    <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($f['validado_por_nome'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text-faint);">
                        <?= !empty($f['data_validacao']) ? date('d/m/Y H:i', strtotime($f['data_validacao'])) : '—' ?>
                    </td>
                    <td style="text-align:right;">
                        <a href="validar_ficha.php?id=<?= (int)$f['id'] ?>" class="btn btn-sm <?= $f['estado']==='Submetida' ? 'btn-green' : '' ?>">
                            <?= $f['estado'] === 'Submetida' ? 'Validar' : 'Ver' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>