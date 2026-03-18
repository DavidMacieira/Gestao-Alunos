<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Funcionário') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

try {
    // Pedidos por estado
    $pedidos_stats = $pdo->query("SELECT estado, COUNT(*) as n FROM pedidos_matricula GROUP BY estado")
                         ->fetchAll(PDO::FETCH_KEY_PAIR);

    // Últimos pedidos pendentes
    $stmtPendentes = $pdo->query("
        SELECT pm.id, pm.ano_letivo, pm.data_pedido,
               u.nome AS aluno_nome, u.email AS aluno_email,
               c.nome AS curso_nome
        FROM pedidos_matricula pm
        JOIN alunos a ON a.id = pm.aluno_id
        JOIN utilizadores u ON u.id = a.id
        LEFT JOIN cursos c ON c.id = pm.curso_id
        WHERE pm.estado = 'Pendente'
        ORDER BY pm.data_pedido ASC
        LIMIT 8
    ");
    $pedidos_pendentes = $stmtPendentes->fetchAll(PDO::FETCH_ASSOC);

    // Pautas recentes
    $stmtPautas = $pdo->query("
        SELECT p.id, p.ano_letivo, p.epoca, p.data_criacao,
               d.nome_disciplina, d.sigla,
               COUNT(n.id) AS total_notas
        FROM pautas p
        JOIN disciplinas d ON d.id = p.disciplina_id
        LEFT JOIN notas n ON n.pauta_id = p.id
        GROUP BY p.id
        ORDER BY p.data_criacao DESC
        LIMIT 5
    ");
    $pautas_recentes = $stmtPautas->fetchAll(PDO::FETCH_ASSOC);

    // Totais
    $total_alunos   = $pdo->query("SELECT COUNT(*) FROM alunos WHERE estado='Aprovada'")->fetchColumn();
    $total_pautas   = $pdo->query("SELECT COUNT(*) FROM pautas")->fetchColumn();
    $total_notas    = $pdo->query("SELECT COUNT(*) FROM notas")->fetchColumn();

} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1100px; margin:0 auto; }
.metrics { display:grid; grid-template-columns:repeat(4,1fr); border:var(--border); border-radius:var(--radius-lg); overflow:hidden; background:var(--white); margin-bottom:28px; }
.mc { padding:20px 24px; border-right:var(--border); transition:background .15s; }
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
.empty-state { text-align:center; padding:40px 20px; color:var(--text-faint); font-size:13px; }
.empty-state svg { display:block; margin:0 auto 12px; opacity:.3; }
.pauta-row { display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:var(--border); }
.pauta-row:last-child { border-bottom:none; }
.pauta-sigla { display:inline-flex; align-items:center; justify-content:center; padding:3px 9px; border-radius:4px; background:var(--gray-50); border:var(--border); font-size:11px; font-weight:500; color:var(--text-muted); font-family:monospace; flex-shrink:0; min-width:44px; }
.pauta-info { flex:1; min-width:0; }
.pauta-nome { font-size:13px; font-weight:500; color:var(--black); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pauta-meta { font-size:11.5px; color:var(--text-faint); margin-top:1px; }
@media(max-width:760px){ .metrics{grid-template-columns:1fr 1fr;} .grid-2{grid-template-columns:1fr;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <!-- Métricas -->
    <div class="metrics">
        <div class="mc">
            <div class="mc-label">Pedidos Pendentes</div>
            <div class="mc-val amber"><?= (int)($pedidos_stats['Pendente'] ?? 0) ?></div>
            <div class="mc-sub">Por processar</div>
        </div>
        <div class="mc">
            <div class="mc-label">Pedidos Aprovados</div>
            <div class="mc-val green"><?= (int)($pedidos_stats['Aprovado'] ?? 0) ?></div>
            <div class="mc-sub">Total aprovados</div>
        </div>
        <div class="mc">
            <div class="mc-label">Pautas Criadas</div>
            <div class="mc-val"><?= (int)$total_pautas ?></div>
            <div class="mc-sub">Total de pautas</div>
        </div>
        <div class="mc">
            <div class="mc-label">Notas Lançadas</div>
            <div class="mc-val"><?= (int)$total_notas ?></div>
            <div class="mc-sub">Em todas as pautas</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Pedidos pendentes -->
        <div class="card">
            <div class="card-body">
                <div class="section-hd">
                    <h3>Pedidos Pendentes</h3>
                    <a href="pedidos.php" class="btn btn-sm">Ver todos</a>
                </div>
                <?php if (empty($pedidos_pendentes)): ?>
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1"><path d="M18 4L32 32H4L18 4z"/><line x1="18" y1="14" x2="18" y2="22"/><circle cx="18" cy="27" r="1"/></svg>
                        Nenhum pedido pendente.
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Curso</th>
                                <th>Ano Letivo</th>
                                <th>Data</th>
                                <th style="text-align:right;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos_pendentes as $p): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($p['aluno_nome']) ?></div>
                                    <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($p['aluno_email']) ?></div>
                                </td>
                                <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($p['curso_nome'] ?? '—') ?></td>
                                <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($p['ano_letivo'] ?? '—') ?></td>
                                <td style="font-size:12px;color:var(--text-faint);"><?= date('d/m/Y', strtotime($p['data_pedido'])) ?></td>
                                <td style="text-align:right;">
                                    <a href="pedidos.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-green">Processar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Ações rápidas -->
            <div class="card">
                <div class="card-body">
                    <div class="section-hd"><h3>Acesso Rápido</h3></div>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <?php
                        $links = [
                            ['href'=>'pedidos.php',      'label'=>'Pedidos de Matrícula', 'sub'=>'Aprovar / Rejeitar',
                             'icon'=>'<path d="M8 1L10 5.5H15L11 8.5L12.5 13L8 10L3.5 13L5 8.5L1 5.5H6Z"/>'],
                            ['href'=>'pautas.php',       'label'=>'Pautas de Avaliação',  'sub'=>'Consultar pautas',
                             'icon'=>'<rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M5 3V1M11 3V1M1 7h14"/>'],
                            ['href'=>'criar-pauta.php',  'label'=>'Nova Pauta',            'sub'=>'Criar e lançar notas',
                             'icon'=>'<line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/>'],
                        ];
                        foreach ($links as $l): ?>
                        <a href="<?= $l['href'] ?>" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:var(--border);border-radius:var(--radius-md);text-decoration:none;color:var(--text-main);transition:all .15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                            <div style="width:32px;height:32px;border-radius:8px;background:var(--gray-50);border:var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><?= $l['icon'] ?></svg>
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:500;"><?= $l['label'] ?></div>
                                <div style="font-size:11.5px;color:var(--text-faint);"><?= $l['sub'] ?></div>
                            </div>
                            <div style="margin-left:auto;color:var(--text-faint);">
                                <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 2l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Pautas recentes -->
            <div class="card">
                <div class="card-body">
                    <div class="section-hd">
                        <h3>Pautas Recentes</h3>
                        <a href="pautas.php" class="btn btn-sm">Ver todas</a>
                    </div>
                    <?php if (empty($pautas_recentes)): ?>
                        <div style="text-align:center;padding:20px;color:var(--text-faint);font-size:12.5px;">Nenhuma pauta criada.</div>
                    <?php else: ?>
                        <?php foreach ($pautas_recentes as $p): ?>
                        <div class="pauta-row">
                            <span class="pauta-sigla"><?= htmlspecialchars($p['sigla']) ?></span>
                            <div class="pauta-info">
                                <div class="pauta-nome"><?= htmlspecialchars($p['nome_disciplina']) ?></div>
                                <div class="pauta-meta"><?= htmlspecialchars($p['ano_letivo']) ?> · <?= htmlspecialchars($p['epoca']) ?> · <?= (int)$p['total_notas'] ?> notas</div>
                            </div>
                            <a href="lancar-notas.php?pauta_id=<?= (int)$p['id'] ?>" class="btn btn-sm">Notas</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>