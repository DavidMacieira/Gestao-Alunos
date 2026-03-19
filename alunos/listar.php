<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filtros
$filtro_q      = trim($_GET['q']      ?? '');
$filtro_estado = $_GET['estado']      ?? 'all';
$filtro_curso  = (int)($_GET['curso'] ?? 0);

$where  = ["1=1"];
$params = [];

if ($filtro_q) {
    $where[]  = "(u.nome LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$filtro_q}%";
    $params[] = "%{$filtro_q}%";
}
if ($filtro_estado !== 'all') {
    $where[]  = "a.estado = ?";
    $params[] = $filtro_estado;
}
if ($filtro_curso > 0) {
    $where[]  = "a.curso_id = ?";
    $params[] = $filtro_curso;
}

$alunos = $pdo->prepare("
    SELECT a.id, a.estado, a.foto, a.curso_id,
           u.nome, u.email, u.created_at,
           c.nome AS curso_nome
    FROM alunos a
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON c.id = a.curso_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY u.nome ASC
");
$alunos->execute($params);
$alunos = $alunos->fetchAll(PDO::FETCH_ASSOC);

$cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$counts = $pdo->query("SELECT estado, COUNT(*) as n FROM alunos GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1100px; margin:0 auto; }
.page-hd { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.page-hd p  { font-size:13px; color:var(--text-muted); margin-top:4px; }
.tab-bar { display:flex; gap:2px; border-bottom:var(--border); margin-bottom:20px; }
.tab { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; font-size:13px; color:var(--text-muted); border-bottom:2px solid transparent; text-decoration:none; transition:all .15s; margin-bottom:-1px; }
.tab:hover { color:var(--black); }
.tab.active { color:var(--black); border-bottom-color:var(--black); font-weight:500; }
.tab-count { font-size:10.5px; padding:1px 6px; border-radius:10px; background:var(--gray-50); color:var(--text-muted); border:var(--border); }
.tab.active .tab-count { background:var(--black); color:white; border-color:var(--black); }
.filter-bar { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.filter-bar input, .filter-bar select { height:36px; padding:0 12px; border:var(--border); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:13px; outline:none; transition:border-color .15s; background:var(--white); color:var(--black); }
.filter-bar input { flex:1; min-width:180px; }
.filter-bar input:focus, .filter-bar select:focus { border-color:var(--gray-400); }
.empty-state { text-align:center; padding:50px; color:var(--text-faint); font-size:13px; }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Alunos</h1>
            <p>Gestão completa de alunos — Admin</p>
        </div>
        <a href="adicionar.php" class="btn btn-primary">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
            Novo Aluno
        </a>
    </div>

    <!-- Tabs por estado -->
    <div class="tab-bar">
        <?php
        $tabs = ['all'=>'Todos','Rascunho'=>'Rascunho','Submetida'=>'Submetidas','Aprovada'=>'Aprovadas','Rejeitada'=>'Rejeitadas'];
        foreach ($tabs as $val => $lbl):
            $cnt    = $val === 'all' ? array_sum($counts) : (int)($counts[$val] ?? 0);
            $active = $filtro_estado === $val ? 'active' : '';
            $url    = '?estado='.urlencode($val).($filtro_q?'&q='.urlencode($filtro_q):'').($filtro_curso?'&curso='.$filtro_curso:'');
        ?>
        <a href="<?= $url ?>" class="tab <?= $active ?>"><?= $lbl ?> <span class="tab-count"><?= $cnt ?></span></a>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <form method="GET">
        <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_estado) ?>">
        <div class="filter-bar">
            <input type="text" name="q" value="<?= htmlspecialchars($filtro_q) ?>" placeholder="Pesquisar por nome ou email...">
            <select name="curso" onchange="this.form.submit()">
                <option value="0">Todos os cursos</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $filtro_curso===$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm">Pesquisar</button>
            <?php if ($filtro_q || $filtro_curso): ?>
                <a href="?estado=<?= urlencode($filtro_estado) ?>" class="btn btn-sm">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabela -->
    <div class="card">
        <?php if (empty($alunos)): ?>
            <div class="empty-state">Nenhum aluno encontrado.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Curso</th>
                    <th>Estado da Ficha</th>
                    <th>Registado em</th>
                    <th style="text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $a):
                    $fotoUrl = !empty($a['foto'])
                        ? htmlspecialchars($a['foto'])
                        : "https://ui-avatars.com/api/?name=".urlencode($a['nome'])."&background=1A1A1A&color=fff&size=60&bold=true";
                    $bc = ['Rascunho'=>'badge-rascunho','Submetida'=>'badge-submetida','Aprovada'=>'badge-aprovada','Rejeitada'=>'badge-rejeitada'][$a['estado']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="<?= $fotoUrl ?>" alt="" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:var(--border);flex-shrink:0;"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($a['nome']) ?>&background=1A1A1A&color=fff&size=60'">
                            <div>
                                <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($a['nome']) ?></div>
                                <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($a['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($a['curso_nome'] ?? '—') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($a['estado']) ?></span></td>
                    <td style="font-size:12px;color:var(--text-faint);"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                    <td style="text-align:right;">
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="editar.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm" title="Editar">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                            </a>
                            <form method="POST" action="eliminar.php" style="display:inline;" onsubmit="return confirm('Eliminar o aluno <?= htmlspecialchars(addslashes($a['nome'])) ?> permanentemente?')">
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="color:#9B2B20;" title="Eliminar">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="3,6 4.5,15 11.5,15 13,6"/><line x1="1" y1="6" x2="15" y2="6"/><path d="M6 6V4a2 2 0 014 0v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>