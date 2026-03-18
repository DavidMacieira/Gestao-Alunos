<?php
/**
 * servicos_academicos/criar_pauta.php
 * RF5.1 — Criar pauta para uma UC, ano letivo e época.
 * RF5.2 — Obter lista de alunos elegíveis por inscrição/matrícula aprovada.
 * Cenário de Demonstração 5 (parte 1).
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['perfil_nome'] !== 'Funcionário') {
    header("Location: ../auth/login.php"); exit;
}
require_once '../config/db.php';

$func_id  = $_SESSION['user_id'];
$mensagem = "";

// Dados para os selects
$disciplinas = $pdo->query("SELECT id, nome_disciplina, sigla FROM disciplinas ORDER BY nome_disciplina")->fetchAll(PDO::FETCH_ASSOC);

// Ano letivo atual
$mes = (int)date('n');
$ano = (int)date('Y');
$ano_letivo_atual = ($mes >= 9) ? "$ano/" . ($ano+1) : ($ano-1) . "/$ano";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disciplina_id = (int)($_POST['disciplina_id'] ?? 0);
    $ano_letivo    = trim($_POST['ano_letivo']     ?? '');
    $epoca         = trim($_POST['epoca']          ?? '');
    $alunos_sel    = $_POST['alunos'] ?? []; // array de aluno_ids

    $erros = [];
    if ($disciplina_id <= 0) $erros[] = "Selecione uma disciplina.";
    if (empty($ano_letivo))  $erros[] = "O ano letivo é obrigatório.";
    if (!preg_match('/^\d{4}\/\d{4}$/', $ano_letivo)) $erros[] = "Formato do ano letivo inválido (ex: 2024/2025).";
    if (!in_array($epoca, ['Normal','Recurso','Especial'])) $erros[] = "Época inválida.";
    if (empty($alunos_sel))  $erros[] = "Selecione pelo menos um aluno.";

    // Verificar pauta duplicada
    if (empty($erros)) {
        $dup = $pdo->prepare("SELECT id FROM pautas WHERE disciplina_id=? AND ano_letivo=? AND epoca=?");
        $dup->execute([$disciplina_id, $ano_letivo, $epoca]);
        if ($dup->fetch()) $erros[] = "Já existe uma pauta para esta disciplina, ano letivo e época.";
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            // Criar pauta
            $pdo->prepare("INSERT INTO pautas (disciplina_id, ano_letivo, epoca, criado_por) VALUES (?,?,?,?)")
                ->execute([$disciplina_id, $ano_letivo, $epoca, $func_id]);
            $pauta_id = $pdo->lastInsertId();

            // Criar registos de nota (a null) para cada aluno selecionado
            $stmtNota = $pdo->prepare("
                INSERT INTO notas (aluno_id, disciplina_id, nota, epoca, ano_letivo, pauta_id)
                VALUES (?, ?, NULL, ?, ?, ?)
            ");
            foreach ($alunos_sel as $aid) {
                $aid = (int)$aid;
                if ($aid > 0) {
                    // Evitar duplicado (mesmo aluno na mesma disciplina/epoca/ano)
                    $dupNota = $pdo->prepare("SELECT id FROM notas WHERE aluno_id=? AND disciplina_id=? AND epoca=? AND ano_letivo=?");
                    $dupNota->execute([$aid, $disciplina_id, $epoca, $ano_letivo]);
                    if (!$dupNota->fetch()) {
                        $stmtNota->execute([$aid, $disciplina_id, $epoca, $ano_letivo, $pauta_id]);
                    }
                }
            }

            $pdo->commit();
            $_SESSION['flash_success'] = "Pauta criada com sucesso! Lance as notas abaixo.";
            header("Location: lancar-notas.php?pauta_id={$pauta_id}"); exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $erros[] = "Erro na base de dados: " . $e->getMessage();
        }
    }

    if (!empty($erros)) $mensagem = implode('<br>', $erros);
}

// Alunos elegíveis: ficha Aprovada + pedido de matrícula Aprovado
// Se não houver pedidos aprovados, mostra todos com ficha aprovada
$alunos_eleg = $pdo->query("
    SELECT DISTINCT a.id, u.nome, u.email, c.nome AS curso_nome
    FROM alunos a
    JOIN utilizadores u ON u.id = a.id
    LEFT JOIN cursos c ON c.id = a.curso_id
    WHERE a.estado = 'Aprovada'
    ORDER BY u.nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>
<style>
.page-body { padding:32px 28px; max-width:900px; margin:0 auto; }
.back-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-muted); margin-bottom:24px; transition:color .15s; }
.back-link:hover { color:var(--black); }
.back-link svg { width:14px; height:14px; }
.page-hd { margin-bottom:24px; }
.page-hd h1 { font-size:22px; font-family:'DM Serif Display',serif; font-weight:400; color:var(--black); letter-spacing:-.01em; }
.page-hd p  { font-size:13px; color:var(--text-muted); margin-top:4px; }
.form-section { margin-bottom:24px; padding-bottom:24px; border-bottom:var(--border); }
.form-section-title { font-size:11px; font-weight:500; text-transform:uppercase; letter-spacing:.08em; color:var(--text-faint); margin-bottom:16px; }
.form-grid-3 { display:grid; grid-template-columns:2fr 1fr 1fr; gap:16px; }
/* Alunos checkboxes */
.alunos-list { border:var(--border); border-radius:var(--radius-md); overflow:hidden; max-height:340px; overflow-y:auto; }
.aluno-item { display:flex; align-items:center; gap:12px; padding:11px 16px; border-bottom:var(--border); cursor:pointer; transition:background .15s; }
.aluno-item:last-child { border-bottom:none; }
.aluno-item:hover { background:var(--gray-50); }
.aluno-item input[type=checkbox] { width:15px; height:15px; accent-color:var(--green); flex-shrink:0; cursor:pointer; }
.aluno-name { font-size:13px; font-weight:500; color:var(--black); }
.aluno-meta { font-size:11.5px; color:var(--text-faint); margin-top:1px; }
.select-all-bar { display:flex; align-items:center; gap:10px; padding:10px 16px; background:var(--gray-50); border-bottom:var(--border); font-size:12.5px; }
.btn-row { display:flex; gap:10px; margin-top:24px; }
@media(max-width:600px){ .form-grid-3{grid-template-columns:1fr;} }
</style>

<div class="page-body">
    <a href="pautas.php" class="back-link">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
        Voltar às pautas
    </a>

    <div class="page-hd">
        <h1>Nova Pauta de Avaliação</h1>
        <p>Cenário 5 — Criar pauta para uma UC e selecionar alunos</p>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-danger" style="margin-bottom:20px;"><?= $mensagem ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="pautaForm">

                <!-- Secção 1: Identificação da pauta -->
                <div class="form-section">
                    <div class="form-section-title">Identificação da Pauta</div>
                    <div class="form-grid-3">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="disciplina_id">Disciplina (UC) <span class="req">*</span></label>
                            <div class="select-wrap">
                                <select name="disciplina_id" id="disciplina_id" class="form-control" required>
                                    <option value="">— Selecionar —</option>
                                    <?php foreach ($disciplinas as $d): ?>
                                        <option value="<?= (int)$d['id'] ?>" <?= (isset($_POST['disciplina_id']) && $_POST['disciplina_id']==$d['id'])?'selected':'' ?>>
                                            <?= htmlspecialchars($d['nome_disciplina']) ?> (<?= htmlspecialchars($d['sigla']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="ano_letivo">Ano Letivo <span class="req">*</span></label>
                            <div class="select-wrap">
                                <select name="ano_letivo" id="ano_letivo" class="form-control" required>
                                    <option value="">—</option>
                                    <?php
                                    $ano_b = (int)date('n') >= 9 ? (int)date('Y') : (int)date('Y')-1;
                                    for ($i = -1; $i <= 1; $i++) {
                                        $al = ($ano_b+$i) . '/' . ($ano_b+$i+1);
                                        $sel = $al === $ano_letivo_atual ? 'selected' : '';
                                        echo "<option value=\"{$al}\" {$sel}>{$al}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="epoca">Época <span class="req">*</span></label>
                            <div class="select-wrap">
                                <select name="epoca" id="epoca" class="form-control" required>
                                    <option value="">—</option>
                                    <option value="Normal"   <?= (($_POST['epoca']??'')==='Normal')  ?'selected':'' ?>>Normal</option>
                                    <option value="Recurso"  <?= (($_POST['epoca']??'')==='Recurso') ?'selected':'' ?>>Recurso</option>
                                    <option value="Especial" <?= (($_POST['epoca']??'')==='Especial')?'selected':'' ?>>Especial</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secção 2: Seleção de alunos -->
                <div class="form-section" style="border-bottom:none;margin-bottom:0;padding-bottom:0;">
                    <div class="form-section-title" style="display:flex;align-items:center;justify-content:space-between;">
                        <span>Alunos Elegíveis</span>
                        <span id="countSel" style="font-size:12px;color:var(--text-faint);text-transform:none;letter-spacing:0;">0 selecionados</span>
                    </div>

                    <?php if (empty($alunos_eleg)): ?>
                        <div class="alert alert-warning">
                            Não existem alunos com ficha aprovada. Os alunos precisam de ter a ficha aprovada pelo Gestor Pedagógico para aparecerem aqui.
                        </div>
                    <?php else: ?>
                        <div class="alunos-list">
                            <div class="select-all-bar">
                                <input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="width:15px;height:15px;accent-color:var(--green);cursor:pointer;">
                                <label for="selectAll" style="cursor:pointer;font-weight:500;">Selecionar todos (<?= count($alunos_eleg) ?> alunos)</label>
                            </div>
                            <?php foreach ($alunos_eleg as $al): ?>
                            <label class="aluno-item">
                                <input type="checkbox" name="alunos[]" value="<?= (int)$al['id'] ?>"
                                       class="aluno-check"
                                       onchange="updateCount()"
                                       <?= (isset($_POST['alunos']) && in_array($al['id'], $_POST['alunos'])) ? 'checked' : '' ?>>
                                <div>
                                    <div class="aluno-name"><?= htmlspecialchars($al['nome']) ?></div>
                                    <div class="aluno-meta"><?= htmlspecialchars($al['email']) ?> · <?= htmlspecialchars($al['curso_nome'] ?? '—') ?></div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-hint" style="margin-top:8px;">
                            Apenas alunos com ficha aprovada aparecem nesta lista (RF5.2).
                        </div>
                    <?php endif; ?>
                </div>

                <div class="btn-row">
                    <a href="pautas.php" class="btn" style="text-decoration:none;flex:0;white-space:nowrap;">Cancelar</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;" onclick="return validarPauta()">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M5 3V1M11 3V1M1 7h14"/></svg>
                        Criar Pauta e Lançar Notas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateCount() {
    const n = document.querySelectorAll('.aluno-check:checked').length;
    document.getElementById('countSel').textContent = n + ' selecionado' + (n !== 1 ? 's' : '');
    document.getElementById('selectAll').checked = (n === document.querySelectorAll('.aluno-check').length);
}

function toggleAll(cb) {
    document.querySelectorAll('.aluno-check').forEach(c => c.checked = cb.checked);
    updateCount();
}

function validarPauta() {
    const disc = document.getElementById('disciplina_id').value;
    const ano  = document.getElementById('ano_letivo').value;
    const ep   = document.getElementById('epoca').value;
    const sel  = document.querySelectorAll('.aluno-check:checked').length;
    const erros = [];
    if (!disc) erros.push('Selecione uma disciplina.');
    if (!ano)  erros.push('Selecione o ano letivo.');
    if (!ep)   erros.push('Selecione a época.');
    if (sel === 0) erros.push('Selecione pelo menos um aluno.');
    if (erros.length) { alert('Por favor corrija:\n\n• ' + erros.join('\n• ')); return false; }
    return true;
}

// Contar ao carregar (caso venha de POST com erros)
updateCount();
</script>
<?php include '../includes/footer.php'; ?>