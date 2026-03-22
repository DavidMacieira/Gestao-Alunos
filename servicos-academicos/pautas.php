<?php
/**
 * servicos_academicos/pautas.php
 * RF5 — Consultar pautas criadas e histórico por UC/época
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Funcionário') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filtros
$filtro_disc    = (int)($_GET['disciplina_id'] ?? 0);
$filtro_epoca   = $_GET['epoca'] ?? '';
$filtro_ano     = $_GET['ano_letivo'] ?? '';

$where  = ["1=1"];
$params = [];

if ($filtro_disc > 0)    { $where[] = "p.disciplina_id = ?"; $params[] = $filtro_disc; }
if ($filtro_epoca)       { $where[] = "p.epoca = ?";         $params[] = $filtro_epoca; }
if ($filtro_ano)         { $where[] = "p.ano_letivo = ?";    $params[] = $filtro_ano; }

$pautas = $pdo->prepare("
    SELECT p.id, p.ano_letivo, p.epoca, p.data_criacao,
           d.nome_disciplina, d.sigla,
           u.nome AS criado_por_nome,
           COUNT(n.id) AS total_notas,
           SUM(CASE WHEN n.nota >= 10 THEN 1 ELSE 0 END) AS aprovados,
           ROUND(AVG(n.nota),1) AS media
    FROM pautas p
    JOIN disciplinas d ON d.id = p.disciplina_id
    LEFT JOIN utilizadores u ON u.id = p.criado_por
    LEFT JOIN notas n ON n.pauta_id = p.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY p.id
    ORDER BY p.data_criacao DESC
");
$pautas->execute($params);
$pautas = $pautas->fetchAll(PDO::FETCH_ASSOC);

// Para os filtros select
$disciplinas  = $pdo->query("SELECT id, nome_disciplina, sigla FROM disciplinas ORDER BY nome_disciplina")->fetchAll(PDO::FETCH_ASSOC);
$anos_letivos = $pdo->query("SELECT DISTINCT ano_letivo FROM pautas ORDER BY ano_letivo DESC")->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1060px; margin:0 auto; }
.page-hd { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.page-hd p  { font-size:13px; color:var(--text-muted); margin-top:4px; }
.filter-row { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.filter-row .select-wrap { min-width:180px; }
.empty-state { text-align:center; padding:60px 20px; color:var(--text-faint); font-size:13px; }
.empty-state svg { display:block; margin:0 auto 12px; opacity:.3; }
.media-pill { display:inline-flex; align-items:center; justify-content:center; width:38px; height:22px; border-radius:4px; font-size:12px; font-weight:500; }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>

    <div class="page-hd">
        <div>
            <h1>Pautas de Avaliação</h1>
            <p>RF5 — Consultar pautas e histórico por UC / época</p>
        </div>
        <a href="criar-pauta.php" class="btn btn-primary">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
            Nova Pauta
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET">
        <div class="filter-row">
            <div class="select-wrap" style="flex:2;">
                <select name="disciplina_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas as disciplinas</option>
                    <?php foreach ($disciplinas as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= $filtro_disc===$d['id']?'selected':'' ?>>
                            <?= htmlspecialchars($d['nome_disciplina']) ?> (<?= htmlspecialchars($d['sigla']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="select-wrap">
                <select name="epoca" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas as épocas</option>
                    <?php foreach (['Normal','Recurso','Especial'] as $e): ?>
                        <option value="<?= $e ?>" <?= $filtro_epoca===$e?'selected':'' ?>><?= $e ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="select-wrap">
                <select name="ano_letivo" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos os anos</option>
                    <?php foreach ($anos_letivos as $al): ?>
                        <option value="<?= htmlspecialchars($al) ?>" <?= $filtro_ano===$al?'selected':'' ?>><?= htmlspecialchars($al) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($filtro_disc || $filtro_epoca || $filtro_ano): ?>
                <a href="pautas.php" class="btn btn-sm">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabela de pautas -->
    <div class="card">
        <?php if (empty($pautas)): ?>
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1"><rect x="4" y="6" width="32" height="28" rx="3"/><line x1="4" y1="14" x2="36" y2="14"/><line x1="13" y1="6" x2="13" y2="14"/><line x1="27" y1="6" x2="27" y2="14"/></svg>
                Nenhuma pauta encontrada.<br>
                <a href="criar-pauta.php" style="color:var(--green);font-weight:500;margin-top:8px;display:inline-block;">Criar a primeira pauta</a>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th>Ano Letivo</th>
                    <th>Época</th>
                    <th>Alunos</th>
                    <th>Aprovados</th>
                    <th>Média</th>
                    <th>Criada por</th>
                    <th>Data</th>
                    <th style="text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pautas as $p):
                    $aprovados   = (int)($p['aprovados'] ?? 0);
                    $total       = (int)$p['total_notas'];
                    $media       = $p['media'] !== null ? (float)$p['media'] : null;
                    $mediaClass  = $media !== null ? ($media >= 10 ? 'nota-pass' : 'nota-fail') : '';
                    $epocaBadge  = ['Normal'=>'badge-info','Recurso'=>'badge-warning','Especial'=>'badge-secondary'][$p['epoca']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($p['nome_disciplina']) ?></div>
                        <div style="font-size:11px;color:var(--text-faint);font-family:monospace;"><?= htmlspecialchars($p['sigla']) ?></div>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($p['ano_letivo']) ?></td>
                    <td><span class="badge <?= $epocaBadge ?>"><?= htmlspecialchars($p['epoca']) ?></span></td>
                    <td style="font-size:13px;"><?= $total ?></td>
                    <td style="font-size:13px;color:var(--green);"><?= $aprovados ?><?= $total > 0 ? '<span style="color:var(--text-faint);font-size:11px;"> / '.$total.'</span>' : '' ?></td>
                    <td>
                        <?php if ($media !== null): ?>
                            <span class="badge <?= $media >= 10 ? 'badge-success' : 'badge-danger' ?>"><?= number_format($media,1) ?></span>
                        <?php else: ?>
                            <span style="color:var(--text-faint);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($p['criado_por_nome'] ?? '—') ?></td>
                    <td style="font-size:12px;color:var(--text-faint);white-space:nowrap;"><?= date('d/m/Y', strtotime($p['data_criacao'])) ?></td>
                    <td style="text-align:right;">
                        <a href="lancar-notas.php?pauta_id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-green">Notas</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>