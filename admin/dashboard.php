<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Admin') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

try {
    $total_alunos      = $pdo->query("SELECT COUNT(*) FROM utilizadores WHERE perfil_id=1")->fetchColumn();
    $total_cursos      = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
    $total_disciplinas = $pdo->query("SELECT COUNT(*) FROM disciplinas")->fetchColumn();
    $total_notas       = $pdo->query("SELECT COUNT(*) FROM notas")->fetchColumn();
    $total_pautas      = $pdo->query("SELECT COUNT(*) FROM pautas")->fetchColumn();
    $fichas_pendentes  = $pdo->query("SELECT COUNT(*) FROM alunos WHERE estado='Submetida'")->fetchColumn();
    $pedidos_pendentes = $pdo->query("SELECT COUNT(*) FROM pedidos_matricula WHERE estado='Pendente'")->fetchColumn();

    // Últimos 5 utilizadores registados
    $ultimos = $pdo->query("
        SELECT u.nome, u.email, u.created_at, p.nome AS perfil
        FROM utilizadores u
        JOIN perfis p ON p.id = u.perfil_id
        ORDER BY u.created_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1100px; margin:0 auto; }
.metrics { display:grid; grid-template-columns:repeat(4,1fr); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; background:var(--white); margin-bottom:28px; }
.mc { padding:20px 24px; border-right:var(--border); transition:background .15s; cursor:default; }
.mc:last-child { border-right:none; }
.mc:hover { background:var(--gray-50); }
.mc-label { font-size:10px; font-weight:500; text-transform:uppercase; letter-spacing:.08em; color:var(--text-faint); margin-bottom:8px; }
.mc-val { font-size:28px; font-weight:300; font-family:'DM Serif Display',serif; letter-spacing:-.02em; line-height:1; color:var(--black); }
.mc-val.amber { color:#7A5A00; }
.mc-val.green { color:var(--green); }
.mc-sub { font-size:11.5px; color:var(--text-faint); margin-top:5px; }
.grid-2 { display:grid; grid-template-columns:2fr 1fr; gap:20px; }
.section-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; padding-bottom:12px; border-bottom:var(--border); }
.section-hd h3 { font-size:12px; font-weight:500; text-transform:uppercase; letter-spacing:.06em; color:var(--text-faint); margin:0; padding:0; border:none; }
.module-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.module-item { display:flex; align-items:center; gap:12px; padding:13px 16px; border:var(--border); border-radius:var(--radius-md); text-decoration:none; color:var(--text-main); transition:all .15s; }
.module-item:hover { background:var(--gray-50); border-color:var(--gray-200); }
.module-icon { width:34px; height:34px; border-radius:8px; background:var(--gray-50); border:var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.module-icon svg { width:15px; height:15px; color:var(--text-muted); }
.module-label { font-size:13px; font-weight:500; color:var(--black); }
.module-sub   { font-size:11.5px; color:var(--text-faint); }
.alert-item { display:flex; align-items:center; gap:10px; padding:10px 14px; border:var(--border); border-radius:var(--radius-md); margin-bottom:8px; text-decoration:none; transition:all .15s; }
.alert-item:hover { background:var(--gray-50); }
.alert-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
@media(max-width:760px){ .metrics{grid-template-columns:1fr 1fr;} .grid-2{grid-template-columns:1fr;} .module-grid{grid-template-columns:1fr;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?>
        <div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>

    <!-- Métricas -->
    <div class="metrics">
        <div class="mc">
            <div class="mc-label">Total de Alunos</div>
            <div class="mc-val"><?= (int)$total_alunos ?></div>
            <div class="mc-sub">Utilizadores registados</div>
        </div>
        <div class="mc">
            <div class="mc-label">Fichas Pendentes</div>
            <div class="mc-val amber"><?= (int)$fichas_pendentes ?></div>
            <div class="mc-sub">Aguardam validação</div>
        </div>
        <div class="mc">
            <div class="mc-label">Pedidos Pendentes</div>
            <div class="mc-val amber"><?= (int)$pedidos_pendentes ?></div>
            <div class="mc-sub">Matrículas por processar</div>
        </div>
        <div class="mc">
            <div class="mc-label">Notas Lançadas</div>
            <div class="mc-val green"><?= (int)$total_notas ?></div>
            <div class="mc-sub">Em <?= (int)$total_pautas ?> pautas</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Módulos de gestão -->
        <div class="card">
            <div class="card-body">
                <div class="section-hd"><h3>Módulos de Gestão</h3></div>
                <div class="module-grid">
                    <?php
                    $modulos = [
                        ['href'=>'../alunos/listar.php',       'label'=>'Alunos',          'sub'=>'CRUD completo',
                         'icon'=>'<circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6"/>'],
                        ['href'=>'../cursos/listar.php',       'label'=>'Cursos',           'sub'=>'CRUD completo',
                         'icon'=>'<circle cx="8" cy="8" r="6.5"/><path d="M8 5v3l2 2"/>'],
                        ['href'=>'../disciplinas/listar.php',  'label'=>'Disciplinas',      'sub'=>'CRUD completo',
                         'icon'=>'<path d="M2 3h12M2 7h8M2 11h10M2 15h6" stroke-linecap="round"/>'],
                        ['href'=>'../notas/listar.php',        'label'=>'Notas',            'sub'=>'CRUD completo',
                         'icon'=>'<rect x="2" y="1" width="12" height="14" rx="1.5"/><line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="11" y2="8"/>'],
                        ['href'=>'../planos-estudo/listar.php','label'=>'Planos de Estudo', 'sub'=>'Associar UCs a cursos',
                         'icon'=>'<rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M5 3V1M11 3V1M1 7h14"/>'],
                        ['href'=>'utilizadores.php',           'label'=>'Utilizadores',     'sub'=>'Gerir contas',
                         'icon'=>'<circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6"/><line x1="12" y1="8" x2="16" y2="8"/><line x1="14" y1="6" x2="14" y2="10"/>'],
                    ];
                    foreach ($modulos as $m): ?>
                    <a href="<?= $m['href'] ?>" class="module-item">
                        <div class="module-icon">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><?= $m['icon'] ?></svg>
                        </div>
                        <div>
                            <div class="module-label"><?= $m['label'] ?></div>
                            <div class="module-sub"><?= $m['sub'] ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Alertas -->
            <div class="card">
                <div class="card-body">
                    <div class="section-hd"><h3>Alertas do Sistema</h3></div>
                    <?php if ($fichas_pendentes > 0): ?>
                    <a href="../gestor/fichas.php" class="alert-item">
                        <span class="alert-dot" style="background:#D4A000;"></span>
                        <div>
                            <div style="font-size:13px;font-weight:500;"><?= (int)$fichas_pendentes ?> ficha(s) aguardam validação</div>
                            <div style="font-size:11.5px;color:var(--text-faint);">Gestor Pedagógico → Fichas</div>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if ($pedidos_pendentes > 0): ?>
                    <a href="../servicos_academicos/pedidos.php" class="alert-item">
                        <span class="alert-dot" style="background:#0069B4;"></span>
                        <div>
                            <div style="font-size:13px;font-weight:500;"><?= (int)$pedidos_pendentes ?> pedido(s) por processar</div>
                            <div style="font-size:11.5px;color:var(--text-faint);">Serviços Académicos → Pedidos</div>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if (!$fichas_pendentes && !$pedidos_pendentes): ?>
                        <div style="text-align:center;padding:20px;color:var(--text-faint);font-size:12.5px;">
                            Sem alertas pendentes.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resumo geral -->
            <div class="card">
                <div class="card-body">
                    <div class="section-hd"><h3>Resumo Geral</h3></div>
                    <?php
                    $items = [
                        ['label'=>'Cursos',       'val'=>$total_cursos],
                        ['label'=>'Disciplinas',  'val'=>$total_disciplinas],
                        ['label'=>'Pautas',       'val'=>$total_pautas],
                        ['label'=>'Notas',        'val'=>$total_notas],
                    ];
                    foreach ($items as $it): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:var(--border);">
                        <span style="font-size:13px;color:var(--text-muted);"><?= $it['label'] ?></span>
                        <strong style="font-size:13px;"><?= (int)$it['val'] ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Últimos registos -->
            <div class="card">
                <div class="card-body">
                    <div class="section-hd"><h3>Últimos Registos</h3></div>
                    <?php foreach ($ultimos as $u): ?>
                    <div style="padding:8px 0;border-bottom:var(--border);">
                        <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars($u['nome']) ?></div>
                        <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($u['perfil']) ?> · <?= date('d/m/Y', strtotime($u['created_at'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>