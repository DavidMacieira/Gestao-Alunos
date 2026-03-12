<?php
session_start();

// Validação de Sessão
if (!isset($_SESSION['user_id']) || $_SESSION['perfil'] !== 'Aluno') {
    header("Location: ../auth/login.php");
    exit;
}

// Incluir a ligação à base de dados
require_once '../config/db.php'; 

$aluno_id = $_SESSION['user_id'];

try {
    // 1. Obter dados do Aluno e do Curso
    $stmtAluno = $pdo->prepare("
        SELECT a.nome, a.email, a.foto, c.nome AS curso_nome 
        FROM alunos a
        LEFT JOIN cursos c ON a.curso_id = c.id
        WHERE a.id = :id
    ");
    $stmtAluno->execute(['id' => $aluno_id]);
    $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);

    // 2. Obter Notas e Dados das Disciplinas
    $stmtNotas = $pdo->prepare("
        SELECT n.id as nota_id, n.nota, n.epoca, n.ano_letivo, d.nome_disciplina, d.sigla
        FROM notas n
        JOIN disciplinas d ON n.disciplina_id = d.id
        WHERE n.aluno_id = :id
        ORDER BY n.ano_letivo DESC, d.nome_disciplina ASC
    ");
    $stmtNotas->execute(['id' => $aluno_id]);
    $notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

    // 3. Processar Estatísticas Globais e por Ano
    $total_notas = count($notas);
    $soma_notas = 0;
    $aprovadas = 0;
    $reprovadas = 0;
    
    $melhor_nota_val = -1;
    $pior_nota_val = 21;
    
    $notas_por_ano = [];
    $stats_por_ano = [];
    $disciplinas_reprovadas = [];

    foreach ($notas as $n) {
        $nota = (float)$n['nota'];
        $ano = $n['ano_letivo'];
        
        // Globais
        $soma_notas += $nota;
        if ($nota >= 10) {
            $aprovadas++;
        } else {
            $reprovadas++;
            $disciplinas_reprovadas[] = $n;
        }
        
        // Melhor e Pior Nota
        if ($nota > $melhor_nota_val) $melhor_nota_val = $nota;
        if ($nota < $pior_nota_val) $pior_nota_val = $nota;

        // Por Ano Letivo
        if (!isset($notas_por_ano[$ano])) {
            $notas_por_ano[$ano] = [];
            $stats_por_ano[$ano] = ['soma' => 0, 'total' => 0, 'aprovadas' => 0, 'reprovadas' => 0];
        }
        $notas_por_ano[$ano][] = $n;
        $stats_por_ano[$ano]['soma'] += $nota;
        $stats_por_ano[$ano]['total']++;
        
        if ($nota >= 10) {
            $stats_por_ano[$ano]['aprovadas']++;
        } else {
            $stats_por_ano[$ano]['reprovadas']++;
        }
    }

    $media = $total_notas > 0 ? $soma_notas / $total_notas : 0;

    // Preparar dados para o Gráfico Chart.js
    $chart_anos = [];
    $chart_medias = [];
    $stats_ano_asc = $stats_por_ano;
    ksort($stats_ano_asc);
    
    foreach ($stats_ano_asc as $ano => &$stats) {
        $media_ano = $stats['total'] > 0 ? $stats['soma'] / $stats['total'] : 0;
        $stats['media'] = number_format($media_ano, 2);
        $chart_anos[] = $ano;
        $chart_medias[] = $media_ano;
    }
    
    krsort($notas_por_ano);

} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}

$fotoUrl = !empty($aluno['foto']) ? htmlspecialchars($aluno['foto']) : "https://ui-avatars.com/api/?name=" . urlencode($aluno['nome']) . "&background=007a33&color=fff";

include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --ipca-green: #007a33;
        --ipca-green-light: #00a34a;
        --card-bg: #ffffff;
        --bg-light: #f4f6f9;
        --text-dark: #2c3e50;
        --text-muted: #6c757d;
        --success-bg: #d4edda;
        --success-text: #155724;
        --danger-bg: #f8d7da;
        --danger-text: #721c24;
        --shadow-refined: 0 2px 8px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.04);
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: var(--bg-light);
        color: var(--text-dark);
    }

    .container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .header-actions {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 1.5rem;
        gap: 10px;
    }

    .btn-print {
        background-color: var(--ipca-green);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        transition: background 0.3s;
    }
    .btn-print:hover { background-color: var(--ipca-green-light); }

    .btn-cert {
        background-color: transparent;
        color: var(--ipca-green);
        border: 2px solid var(--ipca-green);
        padding: 8px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-cert:hover {
        background-color: var(--ipca-green);
        color: white;
    }

    .grid-top { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-bottom: 2rem; }
    .grid-bottom { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem; }
    .card { background: var(--card-bg); border-radius: 12px; box-shadow: var(--shadow-refined); opacity: 0; animation: fadeInUp 0.6s ease forwards; overflow: hidden; }
    .card-padding { padding: 1.5rem; }
    .card h3 { color: var(--ipca-green); margin-top: 0; border-bottom: 2px solid var(--bg-light); padding-bottom: 0.8rem; }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .card:nth-child(1) { animation-delay: 0s; }
    .card:nth-child(2) { animation-delay: 0.1s; }
    .card:nth-child(3) { animation-delay: 0.2s; }
    .card:nth-child(4) { animation-delay: 0.3s; }

    /* Card Perfil */
    .card-perfil-header { background: linear-gradient(135deg, var(--ipca-green) 0%, var(--ipca-green-light) 100%); height: 100px; }
    .perfil-info { text-align: center; padding: 0 1.5rem 1.5rem; margin-top: -60px; position: relative; }
    .perfil-info img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--card-bg); margin-bottom: 0.5rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .perfil-info h4 { margin: 0; font-size: 1.3rem; font-weight: 700; }
    .perfil-info p { margin: 5px 0; color: var(--text-muted); font-size: 0.95rem; }
    .curso-badge { display: inline-block; background: var(--ipca-green); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; margin-top: 10px; font-weight: 600; }

    /* Estatísticas Globais */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; text-align: center; margin-top: 1rem; }
    .stat-box { padding: 1.5rem 1rem; border-radius: 10px; background: var(--bg-light); box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
    .stat-box h2 { margin: 0; font-size: 2.5rem; color: var(--text-dark); }
    .stat-box p { margin: 5px 0 0; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600; }
    .box-media { border-top: 4px solid var(--ipca-green); }
    .box-aprovadas { border-top: 4px solid #28a745; background-color: #f0fdf4; }
    .box-reprovadas { border-top: 4px solid #dc3545; background-color: #fef2f2; }

    /* Estatísticas por Ano */
    .year-stats-list { margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem; }
    .year-stat-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed #eee; font-size: 0.95rem; }

    /* Tabela & Filtros */
    .filters-container { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
    .filters-container input, .filters-container select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Outfit'; flex: 1; outline: none; }
    details.year-details { margin-bottom: 1rem; background: var(--bg-light); border-radius: 8px; overflow: hidden; }
    summary { padding: 1rem 1.5rem; font-weight: 700; cursor: pointer; background: #e9ecef; list-style: none; display: flex; justify-content: space-between; }
    summary::-webkit-details-marker { display: none; }
    summary::after { content: '▼'; font-size: 0.8rem; color: var(--text-muted); }
    details[open] summary::after { content: '▲'; }
    .table-container { max-height: 500px; overflow-y: auto; background: white; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 14px 15px; text-align: left; border-bottom: 1px solid #eee; }
    th { background-color: var(--card-bg); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; position: sticky; top: 0; z-index: 10; cursor: pointer; }
    tbody tr { transition: background 0.15s ease, box-shadow 0.15s ease; animation: fadeInUp 0.3s ease forwards; opacity: 0; }
    tbody tr:hover { background-color: #fcfcfc; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transform: translateY(-1px); }
    .badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; text-align: center; display: inline-block; min-width: 45px; }
    .badge-success { background-color: var(--success-bg); color: var(--success-text); }
    .badge-danger { background-color: var(--danger-bg); color: var(--danger-text); }
    .nota-wrapper { display: flex; align-items: center; gap: 10px; }
    .progress-bar { width: 80px; height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden; }
    .progress-fill { height: 100%; width: var(--pct); background: var(--ipca-green); transition: width 0.5s ease; }
    .progress-fill.danger { background: #dc3545; }

    /* Simulador */
    .simulador-box { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 1px solid #e9ecef; }
    .simulador-box select, .simulador-box input { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Outfit'; }
    .resultado-simulador { font-size: 1.2rem; font-weight: bold; text-align: center; margin-top: 10px; }

    /* =======================================================
       ESTILOS PARA IMPRESSÃO / GERAÇÃO DE PDF
       ======================================================= */
    
    #certificado-container { display: none; } /* Oculto na vista normal */

    @media print {
        body { background: white; color: black; }
        header, footer, nav, .header-actions { display: none !important; }

        /* --- MODO 1: Imprimir Notas (Normal) --- */
        body:not(.print-certificado) #certificado-container { display: none !important; }
        body:not(.print-certificado) .btn-print, 
        body:not(.print-certificado) .filters-container, 
        body:not(.print-certificado) .simulador-box, 
        body:not(.print-certificado) .grid-bottom { display: none !important; }
        body:not(.print-certificado) .container { margin: 0; padding: 0; width: 100%; max-width: 100%; }
        body:not(.print-certificado) .card { box-shadow: none; border: 1px solid #ddd; margin-bottom: 20px; page-break-inside: avoid; }
        body:not(.print-certificado) .card-perfil-header { background: #fff !important; border-bottom: 2px solid #000; height: auto; padding: 10px;}
        body:not(.print-certificado) .perfil-info { margin-top: 0; padding: 0; text-align: left; display: flex; align-items: center; gap: 20px;}
        body:not(.print-certificado) .perfil-info img { border: 2px solid #000; width: 80px; height: 80px; margin:0;}
        body:not(.print-certificado) .progress-bar { display: none; }
        body:not(.print-certificado) .badge { border: 1px solid #000; background: transparent !important; color: #000 !important; }
        body:not(.print-certificado) details.year-details { border: 1px solid #000; margin-bottom: 10px; }
        body:not(.print-certificado) details[open] summary, body:not(.print-certificado) summary { background: #eee; border-bottom: 1px solid #000; }
        body:not(.print-certificado) .table-container { max-height: none; overflow: visible;}

        /* --- MODO 2: Imprimir Certificado de Matrícula --- */
        body.print-certificado .container { display: none !important; } /* Esconde o dashboard */
        body.print-certificado #certificado-container { 
            display: block; 
            margin: 0 auto;
            padding: 2cm;
            font-family: 'Georgia', serif; 
            color: #000;
        }
        
        /* Design formal do certificado */
        .cert-border {
            border: 4px double var(--ipca-green);
            padding: 40px;
            text-align: center;
            height: 90vh; /* Para ocupar a página bem distribuído */
            box-sizing: border-box;
            position: relative;
        }
        .cert-header-title {
            font-size: 28px;
            font-weight: bold;
            color: var(--ipca-green);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 40px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        .cert-body {
            font-size: 18px;
            line-height: 2;
            text-align: justify;
            margin: 40px 20px;
        }
        .cert-body strong { font-size: 20px; }
        .cert-footer {
            position: absolute;
            bottom: 40px;
            left: 0;
            width: 100%;
            text-align: center;
        }
        .cert-signature {
            border-top: 1px solid #000;
            width: 300px;
            margin: 60px auto 10px;
            padding-top: 10px;
            font-style: italic;
        }
        .cert-logo {
            width: 180px;
            margin-top: 30px;
        }
    }

    @media (max-width: 768px) {
        .grid-top { grid-template-columns: 1fr; }
        .grid-bottom { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: 1fr; }
        .filters-container { flex-direction: column; }
    }
</style>

<div class="container">
    
    <div class="header-actions">
        <button class="btn-cert" onclick="imprimirCertificado()">🎓 Baixar Certificado</button>
        <button class="btn-print" id="btnEnviarEmail" onclick="enviarEmailNotas()">📧 Enviar por Email</button>
        <button class="btn-print" onclick="window.print()">🖨️ Exportar Notas</button>
    </div>

    <div class="grid-top">
        <div class="card">
            <div class="card-perfil-header"></div>
            <div class="perfil-info">
                <img src="<?= $fotoUrl ?>" alt="Foto de <?= htmlspecialchars($aluno['nome']) ?>">
                <h4><?= htmlspecialchars($aluno['nome']) ?></h4>
                <p><?= htmlspecialchars($aluno['email']) ?></p>
                <div class="curso-badge">
                    <?= htmlspecialchars($aluno['curso_nome'] ?? 'Curso não atribuído') ?>
                </div>
            </div>
        </div>

        <div class="card card-padding">
            <h3>Estatísticas Académicas</h3>
            <div class="stats-grid">
                <div class="stat-box box-media">
                    <h2 class="counter" data-target="<?= number_format($media, 2) ?>">0.00</h2>
                    <p>Média Global</p>
                </div>
                <div class="stat-box box-aprovadas">
                    <h2 class="counter" data-target="<?= $aprovadas ?>">0</h2>
                    <p>Aprovadas</p>
                </div>
                <div class="stat-box box-reprovadas">
                    <h2 class="counter" data-target="<?= $reprovadas ?>">0</h2>
                    <p>Reprovadas</p>
                </div>
            </div>
            <div class="year-stats-list">
                <h4>Evolução por Ano</h4>
                <?php foreach($stats_ano_asc as $ano => $st): ?>
                    <div class="year-stat-item">
                        <strong><?= htmlspecialchars($ano) ?></strong>
                        <span>Média: <?= $st['media'] ?> | ✅ <?= $st['aprovadas'] ?> | ❌ <?= $st['reprovadas'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card card-padding">
        <h3>Histórico de Notas</h3>
        <?php if ($total_notas > 0): ?>
            <div class="filters-container">
                <input type="text" id="searchDiscipline" placeholder="Pesquisar disciplina ou sigla...">
                <select id="filterYear">
                    <option value="all">Todos os Anos Letivos</option>
                    <?php foreach(array_keys($notas_por_ano) as $anoKey): ?>
                        <option value="<?= htmlspecialchars($anoKey) ?>"><?= htmlspecialchars($anoKey) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="table-container" id="mainTableContainer">
                <?php 
                $first = true;
                foreach ($notas_por_ano as $ano => $notas_ano): ?>
                    <details class="year-details" data-year="<?= htmlspecialchars($ano) ?>" <?= $first ? 'open' : '' ?>>
                        <summary>Ano Letivo: <?= htmlspecialchars($ano) ?> <span style="font-weight:normal; font-size:0.9rem">(<?= count($notas_ano) ?> disciplinas)</span></summary>
                        <table class="sortable-table">
                            <thead>
                                <tr>
                                    <th data-type="string">Disciplina <span class="sort-icon"></span></th>
                                    <th data-type="string">Sigla <span class="sort-icon"></span></th>
                                    <th data-type="number">Nota <span class="sort-icon"></span></th>
                                    <th data-type="string">Época <span class="sort-icon"></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notas_ano as $n): ?>
                                    <?php 
                                        $notaNum = (float)$n['nota'];
                                        $notaClasse = ($notaNum >= 10) ? 'badge-success' : 'badge-danger';
                                        $barClasse = ($notaNum >= 10) ? '' : 'danger';
                                        $pct = ($notaNum / 20) * 100;
                                        
                                        $icon = "";
                                        $title = "";
                                        if ($notaNum == $melhor_nota_val && $notaNum >= 10) {
                                            $icon = "🏆 ";
                                            $title = "Melhor nota";
                                        } elseif ($notaNum == $pior_nota_val) {
                                            $icon = "⚠️ ";
                                            $title = "Nota mais baixa";
                                        }
                                    ?>
                                    <tr class="data-row">
                                        <td class="col-disciplina" title="<?= $title ?>"><?= $icon ?><?= htmlspecialchars($n['nome_disciplina']) ?></td>
                                        <td class="col-sigla"><?= htmlspecialchars($n['sigla']) ?></td>
                                        <td data-value="<?= $notaNum ?>">
                                            <div class="nota-wrapper">
                                                <span class="badge <?= $notaClasse ?>"><?= htmlspecialchars($n['nota']) ?></span>
                                                <div class="progress-bar">
                                                    <div class="progress-fill <?= $barClasse ?>" style="--pct: <?= $pct ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($n['epoca']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </details>
                <?php 
                $first = false;
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid-bottom">
        <div class="card card-padding">
            <h3>Evolução da Média</h3>
            <canvas id="evolucaoChart" height="100"></canvas>
        </div>

        <div class="card card-padding">
            <h3>Simulador de Nota</h3>
            <div class="simulador-box">
                <label>Selecione a Disciplina (Reprovada):</label>
                <select id="simDisc">
                    <option value="">-- Escolher --</option>
                    <?php foreach ($disciplinas_reprovadas as $dr): ?>
                        <option value="<?= htmlspecialchars($dr['nota']) ?>">
                            <?= htmlspecialchars($dr['nome_disciplina']) ?> (Atual: <?= htmlspecialchars($dr['nota']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Nova Nota Hipotética (0-20):</label>
                <input type="number" id="simNota" min="10" max="20" placeholder="Ex: 15">

                <div class="resultado-simulador" id="simResult">
                    Nova Média: <span style="color:var(--text-muted)">-</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="certificado-container">
    <div class="cert-border">
        <div class="cert-header-title">Certificado de Matrícula</div>
        
        <div class="cert-body">
            Declaramos, para os devidos efeitos legais e académicos, que o(a) aluno(a)<br>
            <strong><?= htmlspecialchars($aluno['nome']) ?></strong>,<br>
            portador(a) do endereço de email institucional <em><?= htmlspecialchars($aluno['email']) ?></em>, 
            encontra-se regularmente matriculado(a) no curso de <br>
            <strong><?= htmlspecialchars($aluno['curso_nome'] ?? 'Curso não atribuído') ?></strong> <br>
            nesta Instituição de Ensino Superior durante o presente ano letivo.
        </div>

        <div class="cert-footer">
            <p>Emitido eletronicamente a <strong><?= date('d/m/Y') ?></strong>.</p>
            <div class="cert-signature">Serviços Académicos</div>
            <img src="../img/logo-ipca.png" alt="Logotipo da Instituição" class="cert-logo">
        </div>
    </div>
</div>
<script>
    const chartLabels = <?= json_encode($chart_anos) ?>;
    const chartData = <?= json_encode($chart_medias) ?>;
    const totalSomaNotas = <?= (float)$soma_notas ?>;
    const totalDisciplinas = <?= (int)$total_notas ?>;

    // Função para imprimir Apenas o Certificado
    function imprimirCertificado() {
        // Adiciona a classe que ativa as regras CSS específicas para o certificado
        document.body.classList.add('print-certificado');
        
        // Dispara a janela de impressão
        window.print();
        
        // Tira a classe para que, se voltar a imprimir normal (Exportar Notas), funcione bem
        setTimeout(() => {
            document.body.classList.remove('print-certificado');
        }, 1000); 
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Contadores Animados
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const inc = target / 50;
            if (count < target) {
                counter.innerText = (count + inc).toFixed(counter.innerText.includes('.') ? 2 : 0);
                setTimeout(updateCount, 20);
            } else {
                counter.innerText = target.toFixed(counter.innerText.includes('.') ? 2 : 0);
            }
        };
        updateCount();
    });

    // 2. Gráfico Chart.js
    if (document.getElementById('evolucaoChart') && chartLabels.length > 0) {
        new Chart(document.getElementById('evolucaoChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Média Anual',
                    data: chartData,
                    borderColor: '#007a33',
                    backgroundColor: 'rgba(0, 122, 51, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#00a34a',
                    pointRadius: 5, fill: true, tension: 0.3
                }]
            },
            options: { responsive: true, scales: { y: { min: 0, max: 20 } } }
        });
    }

    // 3. Filtros
    const searchInput = document.getElementById('searchDiscipline');
    const yearSelect = document.getElementById('filterYear');
    function filterTable() {
        if(!searchInput || !yearSelect) return;
        const searchTerm = searchInput.value.toLowerCase();
        const yearTerm = yearSelect.value;
        document.querySelectorAll('details.year-details').forEach(details => {
            let hasVisibleRow = false;
            details.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                if ((yearTerm === 'all' || details.dataset.year === yearTerm) && text.includes(searchTerm)) {
                    row.style.display = ''; hasVisibleRow = true;
                } else { row.style.display = 'none'; }
            });
            details.style.display = hasVisibleRow ? 'block' : 'none';
            if(searchTerm !== '' && hasVisibleRow) details.setAttribute('open', 'open');
        });
    }
    if(searchInput) searchInput.addEventListener('input', filterTable);
    if(yearSelect) yearSelect.addEventListener('change', filterTable);

    // 4. Ordenação
    document.querySelectorAll('.sortable-table th').forEach((th, i) => {
        th.addEventListener('click', () => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = th.classList.contains('asc');
            
            table.querySelectorAll('th').forEach(h => { h.classList.remove('asc', 'desc'); h.querySelector('.sort-icon').textContent = ''; });
            
            rows.sort((a, b) => {
                let vA = th.dataset.type === 'number' ? parseFloat(a.children[i].dataset.value) : a.children[i].textContent.trim().toLowerCase();
                let vB = th.dataset.type === 'number' ? parseFloat(b.children[i].dataset.value) : b.children[i].textContent.trim().toLowerCase();
                return vA < vB ? (isAsc ? 1 : -1) : (vA > vB ? (isAsc ? -1 : 1) : 0);
            });
            
            th.classList.add(isAsc ? 'desc' : 'asc');
            th.querySelector('.sort-icon').textContent = isAsc ? '↓' : '↑';
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // 5. Simulador
    const simDisc = document.getElementById('simDisc');
    const simNota = document.getElementById('simNota');
    const simResult = document.getElementById('simResult');
    function calcSim() {
        if (!simDisc || !simNota || totalDisciplinas === 0) return;
        const oldN = parseFloat(simDisc.value), newN = parseFloat(simNota.value);
        if (isNaN(oldN) || isNaN(newN)) return simResult.innerHTML = `Nova Média: <span style="color:var(--text-muted)">-</span>`;
        
        const media = ((totalSomaNotas - oldN) + newN) / totalDisciplinas;
        const color = newN < 10 ? '#dc3545' : '#007a33';
        simResult.innerHTML = `Nova Média: <span style="color:${color}">${media.toFixed(2)}</span><br><span style="font-size:0.9rem; color:${color}; font-weight:normal;">${newN < 10 ? 'Continua reprovado.' : 'Aprovado na disciplina!'}</span>`;
    }
    if(simDisc) simDisc.addEventListener('change', calcSim);
    if(simNota) simNota.addEventListener('input', calcSim);
});
function enviarEmailNotas() {
    const btn = document.getElementById('btnEnviarEmail');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = "⌛ A enviar...";

    fetch('enviar_notas_process.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert("✅ As suas notas foram enviadas para o seu email com sucesso!");
        } else {
            alert("❌ Erro: " + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert("❌ Erro ao comunicar com o servidor.");
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>

<?php include '../includes/footer.php'; ?>