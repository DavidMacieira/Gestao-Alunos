<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Gestor Pedagógico') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

try {
    // Fichas por estado
    $fichas_stats = $pdo->query("
        SELECT estado, COUNT(*) as total FROM alunos GROUP BY estado
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Fichas submetidas (pendentes de validação)
    $stmtPendentes = $pdo->query("
        SELECT a.id, u.nome, u.email, c.nome AS curso_nome, a.estado
        FROM alunos a
        JOIN utilizadores u ON u.id = a.id
        LEFT JOIN cursos c ON a.curso_id = c.id
        WHERE a.estado = 'Submetida'
        ORDER BY a.id DESC
        LIMIT 8
    ");
    $fichas_pendentes = $stmtPendentes->fetchAll(PDO::FETCH_ASSOC);

    // Totais gerais
    $total_cursos      = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
    $total_disciplinas = $pdo->query("SELECT COUNT(*) FROM disciplinas")->fetchColumn();
    $total_alunos      = $pdo->query("SELECT COUNT(*) FROM alunos")->fetchColumn();

} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

include '../includes/header.php';
?>
<style>
.page-body { padding: 32px 28px; max-width: 1100px; margin: 0 auto; }
.metrics { display: grid; grid-template-columns: repeat(4,1fr); border: var(--border); border-radius: var(--radius-lg); overflow: hidden; background: var(--white); margin-bottom: 28px; }
.mc { padding: 20px 24px; border-right: var(--border); }
.mc:last-child { border-right: none; }
.mc:hover { background: var(--gray-50); }
.mc-label { font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: .08em; color: var(--text-faint); margin-bottom: 8px; }
.mc-val { font-size: 28px; font-weight: 300; font-family: 'DM Serif Display', serif; letter-spacing: -.02em; line-height: 1; }
.mc-val.green { color: var(--green); }
.mc-val.amber { color: #7A5A00; }
.mc-sub { font-size: 11.5px; color: var(--text-faint); margin-top: 5px; }
.grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
.section-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: var(--border); }
.section-hd h3 { font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: .06em; color: var(--text-faint); margin: 0; padding: 0; border: none; }
.quick-links { display: flex; flex-direction: column; gap: 6px; }
.ql-item { display: flex; align-items: center; gap: 12px; padding: 13px 16px; border: var(--border); border-radius: var(--radius-md); background: var(--white); transition: all .15s; text-decoration: none; color: var(--text-main); }
.ql-item:hover { background: var(--gray-50); border-color: var(--gray-200); }
.ql-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--gray-50); border: var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ql-icon svg { width: 15px; height: 15px; color: var(--text-muted); }
.ql-label { font-size: 13px; font-weight: 500; }
.ql-sub   { font-size: 11.5px; color: var(--text-faint); }
.ql-arrow { margin-left: auto; color: var(--text-faint); }
.ql-arrow svg { width: 14px; height: 14px; }
.empty-state { text-align: center; padding: 40px 20px; color: var(--text-faint); font-size: 13px; }
.empty-state svg { display: block; margin: 0 auto 12px; opacity: .3; }
@media(max-width:760px){ .metrics{grid-template-columns:1fr 1fr;} .grid-2{grid-template-columns:1fr;} }
</style>

<div class="page-body">

    <?php if ($flash_success): ?>
        <div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>

    <!-- Metrics -->
    <div class="metrics">
        <div class="mc">
            <div class="mc-label">Fichas Submetidas</div>
            <div class="mc-val amber"><?= (int)($fichas_stats['Submetida'] ?? 0) ?></div>
            <div class="mc-sub">Aguardam validação</div>
        </div>
        <div class="mc">
            <div class="mc-label">Fichas Aprovadas</div>
            <div class="mc-val green"><?= (int)($fichas_stats['Aprovada'] ?? 0) ?></div>
            <div class="mc-sub">Total aprovadas</div>
        </div>
        <div class="mc">
            <div class="mc-label">Cursos Ativos</div>
            <div class="mc-val"><?= (int)$total_cursos ?></div>
            <div class="mc-sub">Cursos configurados</div>
        </div>
        <div class="mc">
            <div class="mc-label">Disciplinas</div>
            <div class="mc-val"><?= (int)$total_disciplinas ?></div>
            <div class="mc-sub">Unidades curriculares</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Fichas pendentes -->
        <div class="card">
            <div class="card-body">
                <div class="section-hd">
                    <h3>Fichas Submetidas — a aguardar validação</h3>
                    <a href="fichas.php" class="btn btn-sm">Ver todas</a>
                </div>
                <?php if (empty($fichas_pendentes)): ?>
                    <div class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1"><rect x="5" y="3" width="26" height="30" rx="3"/><line x1="11" y1="12" x2="25" y2="12"/><line x1="11" y1="18" x2="25" y2="18"/></svg>
                        Nenhuma ficha pendente de validação.
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Curso</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fichas_pendentes as $f): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($f['nome']) ?></div>
                                    <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($f['email']) ?></div>
                                </td>
                                <td style="color:var(--text-muted);font-size:12.5px;"><?= htmlspecialchars($f['curso_nome'] ?? '—') ?></td>
                                <td><span class="badge badge-submetida">Submetida</span></td>
                                <td style="text-align:right;">
                                    <a href="validar_ficha.php?id=<?= (int)$f['id'] ?>" class="btn btn-sm btn-green">Validar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick links -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <div class="card-body">
                    <div class="section-hd"><h3>Acesso Rápido</h3></div>
                    <div class="quick-links">
                        <a href="fichas.php" class="ql-item">
                            <div class="ql-icon"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="2" y="1" width="12" height="14" rx="1.5"/><line x1="5" y1="5" x2="11" y2="5"/><line x1="5" y1="8" x2="11" y2="8"/></svg></div>
                            <div><div class="ql-label">Fichas de Aluno</div><div class="ql-sub">Validar / Rejeitar</div></div>
                            <div class="ql-arrow"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 2l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        </a>
                        <a href="cursos.php" class="ql-item">
                            <div class="ql-icon"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="8" cy="8" r="6.5"/><path d="M8 5v3l2 2"/></svg></div>
                            <div><div class="ql-label">Cursos</div><div class="ql-sub">Criar, editar, desativar</div></div>
                            <div class="ql-arrow"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 2l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        </a>
                        <a href="disciplinas.php" class="ql-item">
                            <div class="ql-icon"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2 3h12M2 7h8M2 11h10M2 15h6" stroke-linecap="round"/></svg></div>
                            <div><div class="ql-label">Disciplinas (UCs)</div><div class="ql-sub">Criar e gerir UCs</div></div>
                            <div class="ql-arrow"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 2l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        </a>
                        <a href="plano_estudos.php" class="ql-item">
                            <div class="ql-icon"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M5 3V1M11 3V1M1 7h14"/></svg></div>
                            <div><div class="ql-label">Plano de Estudos</div><div class="ql-sub">Associar UCs a cursos</div></div>
                            <div class="ql-arrow"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 2l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Resumo de fichas -->
            <div class="card">
                <div class="card-body">
                    <div class="section-hd"><h3>Resumo de Fichas</h3></div>
                    <?php
                    $estados = ['Rascunho','Submetida','Aprovada','Rejeitada'];
                    $dots    = ['Rascunho'=>'#D4A000','Submetida'=>'#0069B4','Aprovada'=>'#007a33','Rejeitada'=>'#C0392B'];
                    foreach ($estados as $e):
                        $n = (int)($fichas_stats[$e] ?? 0);
                    ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:var(--border);">
                        <div style="display:flex;align-items:center;gap:8px;font-size:13px;">
                            <span style="width:7px;height:7px;border-radius:50%;background:<?= $dots[$e] ?>;display:inline-block;flex-shrink:0;"></span>
                            <?= $e ?>
                        </div>
                        <strong style="font-size:13px;"><?= $n ?></strong>
                    </div>
                    <?php endforeach; ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;font-size:12.5px;color:var(--text-faint);">
                        <span>Total de alunos</span>
                        <strong style="color:var(--black);"><?= (int)$total_alunos ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>