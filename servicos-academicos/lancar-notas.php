<?php
/**
 * servicos_academicos/lancar_notas.php
 * RF5.3 — Registar/editar nota final e consultar pautas por UC/época.
 * Cenário de Demonstração 5 (parte 2).
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Funcionário') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$func_id  = $_SESSION['user_id'];
$pauta_id = (int)($_GET['pauta_id'] ?? 0);

if ($pauta_id <= 0) { header("Location: pautas.php"); exit; }

// Carregar pauta
$stmtP = $pdo->prepare("
    SELECT p.*, d.nome_disciplina, d.sigla, u.nome AS criado_por_nome
    FROM pautas p
    JOIN disciplinas d ON d.id = p.disciplina_id
    LEFT JOIN utilizadores u ON u.id = p.criado_por
    WHERE p.id = ?
");
$stmtP->execute([$pauta_id]);
$pauta = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$pauta) { header("Location: pautas.php"); exit; }

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$mensagem = "";

// ── Processar lançamento de notas ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notas_input = $_POST['notas'] ?? []; // [nota_id => valor]
    $erros       = [];
    $atualizadas = 0;

    foreach ($notas_input as $nota_id => $valor) {
        $nota_id = (int)$nota_id;
        $valor   = trim($valor);

        if ($valor === '' || $valor === null) {
            // Limpar nota (NULL)
            $pdo->prepare("UPDATE notas SET nota=NULL WHERE id=? AND pauta_id=?")
                ->execute([$nota_id, $pauta_id]);
            $atualizadas++;
            continue;
        }

        $valor_num = str_replace(',', '.', $valor);
        if (!is_numeric($valor_num)) {
            $erros[] = "Nota inválida: '{$valor}'. Use valores entre 0 e 20.";
            continue;
        }
        $valor_num = (float)$valor_num;
        if ($valor_num < 0 || $valor_num > 20) {
            $erros[] = "Nota {$valor_num} fora do intervalo permitido (0–20).";
            continue;
        }

        $pdo->prepare("UPDATE notas SET nota=? WHERE id=? AND pauta_id=?")
            ->execute([round($valor_num, 2), $nota_id, $pauta_id]);
        $atualizadas++;
    }

    if (!empty($erros)) {
        $mensagem = implode('<br>', $erros);
    } else {
        $_SESSION['flash_success'] = "{$atualizadas} nota(s) guardadas com sucesso.";
        header("Location: lancar-notas.php?pauta_id={$pauta_id}"); exit;
    }
}

// Carregar notas da pauta
$notas = $pdo->prepare("
    SELECT n.id AS nota_id, n.nota, n.aluno_id,
           u.nome AS aluno_nome, u.email AS aluno_email,
           c.nome AS curso_nome
    FROM notas n
    JOIN alunos a ON a.id = n.aluno_id
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON c.id = a.curso_id
    WHERE n.pauta_id = ?
    ORDER BY u.nome ASC
");
$notas->execute([$pauta_id]);
$notas = $notas->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$total     = count($notas);
$lancadas  = count(array_filter($notas, fn($n) => $n['nota'] !== null));
$aprovados = count(array_filter($notas, fn($n) => $n['nota'] !== null && (float)$n['nota'] >= 10));
$media     = $lancadas > 0
    ? array_sum(array_map(fn($n) => (float)$n['nota'], array_filter($notas, fn($n) => $n['nota'] !== null))) / $lancadas
    : null;

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:1000px; margin:0 auto; }
.back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); margin-bottom:24px; transition:color .15s; }
.back-link:hover { color:var(--black); }
.back-link svg { width:14px; height:14px; }
.pauta-info { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:28px; }
.pi-item { flex:1; min-width:120px; padding:16px 20px; background:var(--white); border:var(--border); border-radius:var(--radius-md); }
.pi-label { font-size:10px; font-weight:500; text-transform:uppercase; letter-spacing:.08em; color:var(--text-faint); margin-bottom:6px; }
.pi-val { font-size:20px; font-weight:300; font-family:'DM Serif Display',serif; color:var(--black); }
.pi-val.green { color:var(--green); }
.nota-input {
    width:70px; height:34px; padding:0 10px; text-align:center;
    border:var(--border); border-radius:var(--radius-sm);
    font-family:'DM Sans',sans-serif; font-size:13.5px; font-weight:500;
    color:var(--black); background:var(--white);
    transition:border-color .15s, box-shadow .15s; outline:none;
}
.nota-input:focus { border-color:var(--gray-400); box-shadow:0 0 0 3px rgba(0,122,51,.07); }
.nota-input.pass { background:var(--green-pale); border-color:var(--green-soft); color:var(--green-mid); }
.nota-input.fail { background:#FEF0EF; border-color:#F5A49A; color:#9B2B20; }
.nota-input.empty { background:var(--gray-50); color:var(--gray-400); }
.progress-bar-thin { width:60px; height:3px; background:var(--gray-100); border-radius:2px; overflow:hidden; display:inline-block; vertical-align:middle; margin-left:8px; }
.progress-fill { height:100%; background:var(--green); border-radius:2px; }
.progress-fill.fail { background:#E0604A; }
.save-bar { position:sticky; bottom:0; background:var(--white); border-top:var(--border); padding:14px 28px; display:flex; align-items:center; justify-content:space-between; margin:0 -28px; }
.save-bar-info { font-size:12.5px; color:var(--text-muted); }
</style>

<div class="page-body">
    <a href="pautas.php" class="back-link">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
        Voltar às pautas
    </a>

    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($mensagem): ?><div class="alert alert-danger" style="margin-bottom:20px;"><?= $mensagem ?></div><?php endif; ?>

    <!-- Info da pauta -->
    <div class="pauta-info">
        <div class="pi-item" style="flex:2;">
            <div class="pi-label">Disciplina</div>
            <div class="pi-val" style="font-size:17px;font-family:'DM Sans',sans-serif;font-weight:500;">
                <?= htmlspecialchars($pauta['nome_disciplina']) ?>
                <span style="font-size:12px;font-family:monospace;color:var(--text-faint);font-weight:400;margin-left:6px;"><?= htmlspecialchars($pauta['sigla']) ?></span>
            </div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Ano Letivo</div>
            <div class="pi-val" style="font-size:15px;"><?= htmlspecialchars($pauta['ano_letivo']) ?></div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Época</div>
            <div class="pi-val" style="font-size:15px;"><?= htmlspecialchars($pauta['epoca']) ?></div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Notas Lançadas</div>
            <div class="pi-val green"><?= $lancadas ?> <span style="font-size:14px;color:var(--text-faint);">/ <?= $total ?></span></div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Aprovados</div>
            <div class="pi-val green"><?= $aprovados ?></div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Média</div>
            <div class="pi-val <?= $media !== null && $media >= 10 ? 'green' : '' ?>">
                <?= $media !== null ? number_format($media, 1) : '—' ?>
            </div>
        </div>
    </div>

    <?php if (empty($notas)): ?>
        <div class="card">
            <div style="text-align:center;padding:50px;color:var(--text-faint);font-size:13px;">
                Nenhum aluno nesta pauta.
                <br><a href="criar_pauta.php" style="color:var(--green);font-weight:500;margin-top:8px;display:inline-block;">Criar nova pauta com alunos</a>
            </div>
        </div>
    <?php else: ?>

    <form method="POST" id="notasForm">
        <div class="card" style="margin-bottom:80px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Curso</th>
                        <th style="width:140px;">Nota (0–20)</th>
                        <th>Resultado</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notas as $n):
                        $notaVal  = $n['nota'];
                        $notaNum  = $notaVal !== null ? (float)$notaVal : null;
                        $inputCls = $notaNum !== null ? ($notaNum >= 10 ? 'pass' : 'fail') : 'empty';
                        $pct      = $notaNum !== null ? ($notaNum / 20 * 100) : 0;
                    ?>
                    <tr id="row-<?= (int)$n['nota_id'] ?>">
                        <td>
                            <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($n['aluno_nome']) ?></div>
                            <div style="font-size:11.5px;color:var(--text-faint);"><?= htmlspecialchars($n['aluno_email']) ?></div>
                        </td>
                        <td style="font-size:12.5px;color:var(--text-muted);"><?= htmlspecialchars($n['curso_nome'] ?? '—') ?></td>
                        <td>
                            <input type="number"
                                   name="notas[<?= (int)$n['nota_id'] ?>]"
                                   id="nota-<?= (int)$n['nota_id'] ?>"
                                   class="nota-input <?= $inputCls ?>"
                                   value="<?= $notaVal !== null ? htmlspecialchars($notaVal) : '' ?>"
                                   min="0" max="20" step="0.25"
                                   placeholder="—"
                                   oninput="updateRow(<?= (int)$n['nota_id'] ?>, this.value)">
                        </td>
                        <td id="res-<?= (int)$n['nota_id'] ?>">
                            <?php if ($notaNum !== null): ?>
                                <span class="badge <?= $notaNum >= 10 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $notaNum >= 10 ? 'Aprovado' : 'Reprovado' ?>
                                </span>
                                <span class="progress-bar-thin">
                                    <span class="progress-fill <?= $notaNum < 10 ? 'fail' : '' ?>" style="width:<?= $pct ?>%;"></span>
                                </span>
                            <?php else: ?>
                                <span style="font-size:12px;color:var(--text-faint);">Por lançar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($notaNum !== null): ?>
                            <button type="button" class="btn btn-sm" style="color:var(--text-faint);" title="Limpar nota"
                                    onclick="clearNota(<?= (int)$n['nota_id'] ?>)">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><line x1="4" y1="4" x2="12" y2="12"/><line x1="12" y1="4" x2="4" y2="12"/></svg>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Barra de guardar sticky -->
        <div class="save-bar">
            <div class="save-bar-info">
                <span id="statsBar">
                    <?= $lancadas ?> / <?= $total ?> notas lançadas
                    <?= $lancadas > 0 ? '· ' . $aprovados . ' aprovados · Média ' . ($media !== null ? number_format($media,1) : '—') : '' ?>
                </span>
            </div>
            <div style="display:flex;gap:10px;">
                <a href="pautas.php" class="btn">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><path d="M13 9v4H3V9M8 2v8M5 5l3-3 3 3"/></svg>
                    Guardar Notas
                </button>
            </div>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
function updateRow(id, val) {
    const input  = document.getElementById('nota-' + id);
    const resCell= document.getElementById('res-' + id);
    const num    = parseFloat(val);

    // Atualizar classe do input
    input.classList.remove('pass','fail','empty');
    if (val === '' || isNaN(num)) {
        input.classList.add('empty');
        resCell.innerHTML = '<span style="font-size:12px;color:var(--text-faint);">Por lançar</span>';
    } else if (num >= 10) {
        input.classList.add('pass');
        const pct = (num/20*100).toFixed(0);
        resCell.innerHTML = `<span class="badge badge-success">Aprovado</span>
            <span class="progress-bar-thin"><span class="progress-fill" style="width:${pct}%"></span></span>`;
    } else {
        input.classList.add('fail');
        const pct = (num/20*100).toFixed(0);
        resCell.innerHTML = `<span class="badge badge-danger">Reprovado</span>
            <span class="progress-bar-thin"><span class="progress-fill fail" style="width:${pct}%"></span></span>`;
    }
    updateStats();
}

function clearNota(id) {
    const input = document.getElementById('nota-' + id);
    input.value = '';
    updateRow(id, '');
}

function updateStats() {
    const inputs  = document.querySelectorAll('.nota-input');
    let lancadas  = 0, aprovados = 0, soma = 0;
    inputs.forEach(inp => {
        const v = parseFloat(inp.value);
        if (!isNaN(v) && inp.value.trim() !== '') {
            lancadas++; soma += v;
            if (v >= 10) aprovados++;
        }
    });
    const total  = inputs.length;
    const media  = lancadas > 0 ? (soma/lancadas).toFixed(1) : '—';
    document.getElementById('statsBar').textContent =
        `${lancadas} / ${total} notas lançadas` +
        (lancadas > 0 ? ` · ${aprovados} aprovados · Média ${media}` : '');
}
</script>
<?php include '../includes/footer.php'; ?>