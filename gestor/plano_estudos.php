<?php
/**
 * gestor/plano_estudos.php
 * RF2.3 — Configuração do Plano de Estudos
 *  - Associar UCs a um curso
 *  - Indicar ano e semestre
 *  - Impedir duplicações incoerentes (mesma UC no mesmo curso/semestre)
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Gestor Pedagógico') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filtro de curso (URL param)
$cursos = $pdo->query("SELECT id, nome, sigla FROM cursos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$curso_sel = (int)($_GET['curso_id'] ?? ($cursos[0]['id'] ?? 0));

$mensagem = "";

// ── Processar POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao          = $_POST['acao']          ?? '';
    $curso_id      = (int)($_POST['curso_id']      ?? 0);
    $disciplina_id = (int)($_POST['disciplina_id'] ?? 0);
    $ano           = (int)($_POST['ano']           ?? 0);
    $semestre      = (int)($_POST['semestre']      ?? 0);
    $pe_id         = (int)($_POST['pe_id']         ?? 0);

    if ($acao === 'adicionar') {
        $erros = [];
        if ($curso_id     <= 0) $erros[] = "Selecione um curso.";
        if ($disciplina_id<= 0) $erros[] = "Selecione uma disciplina.";
        if ($ano          <= 0 || $ano > 5) $erros[] = "Ano inválido (1–5).";
        if ($semestre     <= 0 || $semestre > 2) $erros[] = "Semestre inválido (1–2).";

        if (empty($erros)) {
            // RF2.3 — Impedir duplicações: mesma UC no mesmo curso/semestre
            $dup = $pdo->prepare("
                SELECT id FROM plano_estudos
                WHERE curso_id=? AND disciplina_id=? AND ano=? AND semestre=?
            ");
            $dup->execute([$curso_id, $disciplina_id, $ano, $semestre]);
            if ($dup->fetch()) {
                $erros[] = "Esta disciplina já existe no plano deste curso (Ano {$ano}, Semestre {$semestre}).";
            }
        }

        if (empty($erros)) {
            $pdo->prepare("INSERT INTO plano_estudos (curso_id, disciplina_id, ano, semestre) VALUES (?,?,?,?)")
                ->execute([$curso_id, $disciplina_id, $ano, $semestre]);
            $_SESSION['flash_success'] = "Disciplina adicionada ao plano de estudos.";
            header("Location: plano_estudos.php?curso_id={$curso_id}"); exit;
        }
        $mensagem = implode('<br>', $erros);
        $curso_sel = $curso_id;

    } elseif ($acao === 'remover' && $pe_id > 0) {
        $pdo->prepare("DELETE FROM plano_estudos WHERE id=?")->execute([$pe_id]);
        $_SESSION['flash_success'] = "Disciplina removida do plano.";
        header("Location: plano_estudos.php?curso_id={$curso_id}"); exit;
    }
}

// Carregar plano do curso selecionado
$plano = [];
if ($curso_sel > 0) {
    $stmtPlano = $pdo->prepare("
        SELECT pe.id AS pe_id, pe.ano, pe.semestre,
               d.nome_disciplina, d.sigla
        FROM plano_estudos pe
        JOIN disciplinas d ON d.id = pe.disciplina_id
        WHERE pe.curso_id = ?
        ORDER BY pe.ano ASC, pe.semestre ASC, d.nome_disciplina ASC
    ");
    $stmtPlano->execute([$curso_sel]);
    $plano = $stmtPlano->fetchAll(PDO::FETCH_ASSOC);
}

// Agrupar plano por ano/semestre
$plano_agrupado = [];
foreach ($plano as $p) {
    $key = "Ano {$p['ano']} — Semestre {$p['semestre']}";
    $plano_agrupado[$key][] = $p;
}

// Disciplinas disponíveis (todas — o utilizador escolhe)
$disciplinas = $pdo->query("SELECT id, nome_disciplina, sigla FROM disciplinas ORDER BY nome_disciplina ASC")
                   ->fetchAll(PDO::FETCH_ASSOC);

// Curso selecionado info
$curso_info = null;
foreach ($cursos as $c) { if ((int)$c['id'] === $curso_sel) { $curso_info = $c; break; } }

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px;max-width:1060px;margin:0 auto; }
.page-hd { margin-bottom:24px; }
.page-hd h1 { font-size:22px;font-family:'DM Serif Display',serif;font-weight:400;color:var(--black);letter-spacing:-.01em; }
.page-hd p  { font-size:13px;color:var(--text-muted);margin-top:4px; }
.curso-tabs { display:flex;gap:4px;flex-wrap:wrap;margin-bottom:24px;padding-bottom:16px;border-bottom:var(--border); }
.ctab { display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;font-size:12.5px;border:var(--border);background:var(--white);color:var(--text-muted);text-decoration:none;transition:all .15s; }
.ctab:hover { background:var(--gray-50);color:var(--black); }
.ctab.active { background:var(--black);color:white;border-color:var(--black); }
.grid-2 { display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start; }
.semester-block { margin-bottom:20px; }
.semester-hd { font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--text-faint);margin-bottom:8px;padding:8px 0;border-bottom:var(--border); }
.uc-row { display:flex;align-items:center;gap:10px;padding:10px 14px;border:var(--border);border-radius:var(--radius-md);background:var(--white);margin-bottom:6px;transition:border-color .15s; }
.uc-row:hover { border-color:var(--gray-200); }
.uc-sigla { display:inline-flex;align-items:center;justify-content:center;padding:2px 9px;border-radius:4px;background:var(--gray-50);border:var(--border);font-size:11px;font-weight:500;color:var(--text-muted);font-family:monospace;flex-shrink:0;min-width:48px; }
.uc-nome { flex:1;font-size:13px;font-weight:500;color:var(--black); }
.form-card { background:var(--white);border:var(--border);border-radius:var(--radius-lg);overflow:hidden;position:sticky;top:80px; }
.form-card-head { padding:18px 24px;border-bottom:var(--border); }
.form-card-head h3 { font-size:14px;font-weight:500;color:var(--black);margin:0; }
.form-card-head p  { font-size:12px;color:var(--text-muted);margin-top:3px; }
.form-card-body { padding:24px; }
.btn-row { display:flex;gap:10px;margin-top:20px; }
.select-wrap { position:relative; }
.select-wrap::after { content:'';position:absolute;right:12px;top:50%;transform:translateY(-50%);width:0;height:0;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid var(--gray-400);pointer-events:none; }
.empty-plan { text-align:center;padding:50px 20px;color:var(--text-faint);font-size:13px; }
.empty-plan svg { display:block;margin:0 auto 12px;opacity:.3; }
.stats-row { display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap; }
.stat-mini { flex:1;min-width:100px;padding:14px 16px;background:var(--white);border:var(--border);border-radius:var(--radius-md);text-align:center; }
.stat-mini .val { font-size:22px;font-weight:300;font-family:'DM Serif Display',serif;color:var(--black); }
.stat-mini .lbl { font-size:10.5px;color:var(--text-faint);text-transform:uppercase;letter-spacing:.06em;margin-top:2px; }
@media(max-width:700px){ .grid-2{grid-template-columns:1fr;} .form-card{position:static;} }
</style>

<div class="page-body">
    <?php if ($flash_success): ?><div class="alert alert-success" style="margin-bottom:20px;"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if ($flash_error):   ?><div class="alert alert-danger"  style="margin-bottom:20px;"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <div class="page-hd">
        <h1>Plano de Estudos</h1>
        <p>RF2.3 — Associar unidades curriculares a cursos com ano e semestre</p>
    </div>

    <!-- Seletor de curso por tabs -->
    <div class="curso-tabs">
        <?php foreach ($cursos as $c): ?>
            <a href="plano_estudos.php?curso_id=<?= (int)$c['id'] ?>"
               class="ctab <?= $c['id'] == $curso_sel ? 'active' : '' ?>">
                <?= htmlspecialchars($c['sigla'] ?? $c['nome']) ?>
            </a>
        <?php endforeach; ?>
        <?php if (empty($cursos)): ?>
            <span style="font-size:13px;color:var(--text-faint);">Nenhum curso criado. <a href="cursos.php" style="color:var(--green);">Criar curso</a></span>
        <?php endif; ?>
    </div>

    <?php if ($curso_sel > 0 && $curso_info): ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="val"><?= count($plano) ?></div>
            <div class="lbl">UCs no plano</div>
        </div>
        <div class="stat-mini">
            <div class="val"><?= count($plano_agrupado) ?></div>
            <div class="lbl">Semestres</div>
        </div>
        <div class="stat-mini">
            <div class="val"><?= max(array_column($plano, 'ano') ?: [0]) ?></div>
            <div class="lbl">Anos</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Plano atual -->
        <div>
            <?php if (empty($plano_agrupado)): ?>
                <div class="form-card">
                    <div class="empty-plan">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1"><rect x="4" y="6" width="32" height="28" rx="3"/><line x1="4" y1="14" x2="36" y2="14"/><line x1="13" y1="6" x2="13" y2="14"/><line x1="27" y1="6" x2="27" y2="14"/></svg>
                        Nenhuma UC adicionada ao plano de <strong><?= htmlspecialchars($curso_info['nome']) ?></strong>.<br>
                        Use o formulário para começar.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($plano_agrupado as $grupo => $ucs): ?>
                <div class="semester-block">
                    <div class="semester-hd"><?= htmlspecialchars($grupo) ?></div>
                    <?php foreach ($ucs as $uc): ?>
                    <div class="uc-row">
                        <span class="uc-sigla"><?= htmlspecialchars($uc['sigla']) ?></span>
                        <span class="uc-nome"><?= htmlspecialchars($uc['nome_disciplina']) ?></span>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remover esta UC do plano?')">
                            <input type="hidden" name="acao"     value="remover">
                            <input type="hidden" name="pe_id"    value="<?= (int)$uc['pe_id'] ?>">
                            <input type="hidden" name="curso_id" value="<?= (int)$curso_sel ?>">
                            <button type="submit" class="btn btn-sm" style="color:#9B2B20;" title="Remover do plano">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="3,6 4.5,15 11.5,15 13,6"/><line x1="1" y1="6" x2="15" y2="6"/><path d="M6 6V4a2 2 0 014 0v2"/></svg>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Formulário adicionar UC -->
        <div class="form-card">
            <div class="form-card-head">
                <h3>Adicionar UC ao Plano</h3>
                <p><?= htmlspecialchars($curso_info['nome']) ?></p>
            </div>
            <div class="form-card-body">
                <?php if ($mensagem): ?><div class="alert alert-danger" style="margin-bottom:16px;"><?= $mensagem ?></div><?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="acao"     value="adicionar">
                    <input type="hidden" name="curso_id" value="<?= (int)$curso_sel ?>">

                    <div class="form-group">
                        <label class="form-label" for="disciplina_id">Disciplina (UC) <span class="req">*</span></label>
                        <div class="select-wrap">
                            <select name="disciplina_id" id="disciplina_id" class="form-control" required>
                                <option value="">— Selecionar —</option>
                                <?php foreach ($disciplinas as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>">
                                        <?= htmlspecialchars($d['nome_disciplina']) ?> (<?= htmlspecialchars($d['sigla']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label class="form-label" for="ano">Ano <span class="req">*</span></label>
                            <div class="select-wrap">
                                <select name="ano" id="ano" class="form-control" required>
                                    <option value="">—</option>
                                    <?php for ($a = 1; $a <= 4; $a++): ?>
                                        <option value="<?= $a ?>">Ano <?= $a ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="semestre">Semestre <span class="req">*</span></label>
                            <div class="select-wrap">
                                <select name="semestre" id="semestre" class="form-control" required>
                                    <option value="">—</option>
                                    <option value="1">Semestre 1</option>
                                    <option value="2">Semestre 2</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-hint" style="margin-top:-8px;margin-bottom:16px;">
                        Uma UC não pode ser repetida no mesmo curso/semestre (RF2.3).
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
                        Adicionar ao Plano
                    </button>
                </form>

                <div style="margin-top:16px;padding-top:16px;border-top:var(--border);">
                    <a href="disciplinas.php?acao=criar" style="font-size:12.5px;color:var(--green);font-weight:500;">
                        + Criar nova disciplina
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php elseif (empty($cursos)): ?>
        <div class="alert alert-warning">
            Não existem cursos criados. <a href="cursos.php" style="font-weight:500;">Criar um curso primeiro.</a>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>