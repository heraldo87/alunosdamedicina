<?php
// ==============================================
// dashboard.php — Tudo em um arquivo (PHP + HTML + JS)
// ==============================================

/* -------- CONFIG DO BANCO -------- */
$DB_HOST = 'localhost';
$DB_USER = 'MedinFocus';
$DB_PASS = 'Her@ldoAlves963#';
$DB_NAME = 'medinfocus';
$DB_PORT = 3306;
$TIMEZONE = 'America/Rio_Branco';

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($TIMEZONE);
}

function ensure_utf8($v) {
    if (!is_string($v)) return $v;
    if (function_exists('mb_convert_encoding')) return mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    if (function_exists('iconv')) { $x = @iconv('UTF-8', 'UTF-8//IGNORE', $v); return $x === false ? $v : $x; }
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v);
}

/* -------- Conexão -------- */
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body style="font-family: sans-serif">';
    echo '<h2>Falha ao conectar no MySQL</h2>';
    echo '<pre>'.htmlspecialchars($mysqli->connect_error).'</pre>';
    echo '</body></html>'; exit;
}
$mysqli->set_charset('utf8mb4');

/* -------- Query principal -------- */
$sql = "SELECT `id`, `nome`, `cpf`, `diretoria`, `data_inicial`, `data_final`,
               `atividades_realizadas`, `atividades_previstas`, `pontos_relevantes`, `data_registro`,
               DATE(`data_registro`) AS `dia`, `nome`
        FROM `acompanhamento_atividades`
        ORDER BY `data_registro` ASC";
$result = $mysqli->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $k => $v) { $row[$k] = ensure_utf8($v); }
        $rows[] = $row;
    }
    $result->free();
}
$mysqli->close();

$DATA_JSON = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard de Análise de Dados</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .chart-box { position: relative; height: 340px; }
        .chart-box canvas { width: 100% !important; height: 100% !important; display: block; }
        .input, .select { border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 0.5rem 0.75rem; background: white; }
        .btn { background:#4f46e5; color:white; padding:0.5rem 1rem; border-radius:0.75rem; font-weight:600; }
        .btn-outline { background:white; color:#4f46e5; border:1px solid #4f46e5; padding:0.5rem 1rem; border-radius:0.75rem; font-weight:600; }
        .table-wrap { overflow:auto; }
        /* Adicionado CSS para limitar a largura e estilizar o "ver mais" */
        .limit-text {
            width: 250px; /* Largura fixa para as colunas com muito texto */
            max-height: 60px; /* Limita a altura para mostrar apenas algumas linhas */
            overflow: hidden; /* Esconde o texto que ultrapassa o limite */
            position: relative;
        }
        .limit-text.expanded {
            max-height: none; /* Altura ilimitada ao expandir */
            overflow: visible;
        }
        .more-btn {
            color: #4f46e5;
            font-weight: 500;
            cursor: pointer;
            text-decoration: underline;
            white-space: nowrap; /* Impede a quebra de linha do botão */
        }
        th, td { white-space: nowrap; }
        #err { display:none; }
    </style>
</head>
<body class="p-6 md:p-10 text-gray-800">
    <div class="max-w-7xl mx-auto">
        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-2">Dashboard de Análise de Dados</h1>
            <p class="text-lg text-gray-600">Visão geral e insights sobre as atividades dos colaboradores.</p>
        </header>

        <div id="err" class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 p-3 text-sm"></div>

        <section class="mb-8 bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Filtros</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Data inicial</label>
                    <input id="filter-start" type="date" class="input w-full">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Data final</label>
                    <input id="filter-end" type="date" class="input w-full">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Colaborador</label>
                    <select id="filter-collab" class="select w-full">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button id="apply-filters" type="button" class="btn w-full">Aplicar</button>
                    <button id="clear-filters" type="button" class="btn-outline w-full">Limpar</button>
                </div>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b-2 border-gray-200 pb-2">Resumo Geral</h2>
            <div id="summary-cards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm text-gray-500 font-semibold uppercase">Relatórios (filtrados)</p>
                    <p id="total-reports" class="text-4xl font-bold mt-2 text-indigo-600">0</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm text-gray-500 font-semibold uppercase">Colaboradores Únicos</p>
                    <p id="total-collaborators" class="text-4xl font-bold mt-2 text-indigo-600">0</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm text-gray-500 font-semibold uppercase">Média de Relatórios</p>
                    <p id="avg-reports" class="text-4xl font-bold mt-2 text-indigo-600">0</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <p class="text-sm text-gray-500 font-semibold uppercase">Tempo Médio de Atividade</p>
                    <p id="avg-duration" class="text-3xl font-bold mt-2 text-indigo-600">0 dias</p>
                </div>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b-2 border-gray-200 pb-2">Produtividade</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-semibold mb-4">Relatórios por Colaborador</h3>
                    <div class="chart-box"><canvas id="reports-by-collaborator"></canvas></div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-semibold mb-4">Relatórios por Dia</h3>
                    <div class="chart-box"><canvas id="reports-per-day"></canvas></div>
                </div>
            </div>
        </section>

        <section class="mb-16">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b-2 border-gray-200 pb-2">Relatórios (Detalhes)</h2>
            <div class="bg-white rounded-xl shadow-lg p-6 table-wrap">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Nome</th>
                            <th class="px-4 py-2 text-left">CPF</th>
                            <th class="px-4 py-2 text-left">Diretoria</th>
                            <th class="px-4 py-2 text-left">Data Inicial</th>
                            <th class="px-4 py-2 text-left">Data Final</th>
                            <th class="px-4 py-2 text-left">Atividades Realizadas</th>
                            <th class="px-4 py-2 text-left">Atividades Previstas</th>
                            <th class="px-4 py-2 text-left">Pontos Relevantes</th>
                            <th class="px-4 py-2 text-left">Data Registro</th>
                        </tr>
                    </thead>
                    <tbody id="reports-table-body"></tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const rows = <?php echo $DATA_JSON ?: '[]'; ?>;

        function dateDiffInDays(a, b) {
            const _MS_PER_DAY = 1000 * 60 * 60 * 24;
            const d1 = new Date(a), d2 = new Date(b);
            const utc1 = Date.UTC(d1.getFullYear(), d1.getMonth(), d1.getDate());
            const utc2 = Date.UTC(d2.getFullYear(), d2.getMonth(), d2.getDate());
            return Math.floor(Math.abs(utc2 - utc1) / _MS_PER_DAY);
        }

        function showErr(msg){
            const box=document.getElementById('err');
            if (!box) return;
            box.textContent = msg;
            box.style.display = msg ? 'block' : 'none';
        }

        // ===================== Estado =====================
        let chartCollab = null;
        let chartPerDay = null;

        // ===================== Filtro =====================
        function applyFilters(data) {
            const startStr = document.getElementById('filter-start').value; // YYYY-MM-DD
            const endStr   = document.getElementById('filter-end').value;   // YYYY-MM-DD
            const coll     = document.getElementById('filter-collab').value;

            // Inversão automática se start > end (comparação por string)
            let s = startStr || '';
            let e = endStr || '';
            if (s && e && s > e) { const tmp = s; s = e; e = tmp; }

            return data.filter(r => {
                const day = r.dia; // 'YYYY-MM-DD'
                if (!day) return false;
                if (s && day < s) return false;
                if (e && day > e) return false;
                if (coll && String(r.nome || '') !== coll) return false;
                return true;
            });
        }

        // ===================== Summary =====================
        function updateSummary(filtered) {
            const totalReports = filtered.length;
            document.getElementById('total-reports').textContent = totalReports;
            const uniqueCollaborators = new Set(filtered.map(i => i.nome)).size;
            document.getElementById('total-collaborators').textContent = uniqueCollaborators;
            document.getElementById('avg-reports').textContent = uniqueCollaborators ? (totalReports / uniqueCollaborators).toFixed(2) : '0';
            const totalDuration = filtered.reduce((s, i) => s + dateDiffInDays(i.data_inicial, i.data_final), 0);
            document.getElementById('avg-duration').textContent = `${(totalReports ? (totalDuration / totalReports) : 0).toFixed(1)} dias`;
        }

        // ===================== Gráficos =====================
        function renderCharts(filtered) {
            const baseOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } };

            // 1) por colaborador
            const aggColab = {};
            filtered.forEach(i => { const k = i.nome || '—'; aggColab[k] = (aggColab[k]||0)+1; });
            const labelsColab = Object.keys(aggColab).map(e => e);
            const valuesColab = Object.values(aggColab);

            if (chartCollab) chartCollab.destroy();
            chartCollab = new Chart(document.getElementById('reports-by-collaborator').getContext('2d'), {
                type: 'bar', data: { labels: labelsColab, datasets: [{ data: valuesColab, backgroundColor: '#4f46e5', borderRadius: 8 }] }, options: baseOpts
            });

            // 2) por dia
            const perDay = {};
            filtered.forEach(i => { const d = i.dia; if (!d) return; perDay[d] = (perDay[d]||0)+1; });
            const dayKeys = Object.keys(perDay).sort((a,b)=>a.localeCompare(b));
            const dayLabels = dayKeys.map(k => { const [y,m,d]=k.split('-').map(Number); return new Date(y,m-1,d).toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'}); });
            const dayValues = dayKeys.map(k => perDay[k]);

            if (chartPerDay) chartPerDay.destroy();
            chartPerDay = new Chart(document.getElementById('reports-per-day').getContext('2d'), {
                type: 'line',
                data: { labels: dayLabels, datasets: [{ data: dayValues, borderColor: 'rgb(75, 192, 192)', borderWidth: 3, tension: 0.2, pointRadius: 2, fill: false }] },
                options: baseOpts
            });
        }

        // ===================== Word Cloud =====================
        function renderWordCloud(filtered) {
            // Removido para simplificar, pois não há elemento #word-cloud
        }

        // ===================== Tabela =====================
        function renderTable(filtered) {
            const tbody = document.getElementById('reports-table-body'); tbody.innerHTML = '';
            if (!filtered.length) {
                const tr=document.createElement('tr'); const td=document.createElement('td'); td.colSpan=10; td.className='px-4 py-3 text-center text-gray-500'; td.textContent='Nenhum registro para os filtros selecionados.'; tr.appendChild(td); tbody.appendChild(tr); return;
            }
            filtered.forEach(r => {
                const tr=document.createElement('tr'); tr.className='border-b hover:bg-gray-50';
                const c=v=>{ const td=document.createElement('td'); td.className='px-4 py-2'; td.textContent=v??''; return td; };
                
                const createExpandableCell = (text) => {
                    const td = document.createElement('td');
                    td.className = 'px-4 py-2';
                    
                    const wrapper = document.createElement('div');
                    wrapper.className = 'limit-text';
                    wrapper.textContent = text ?? '';

                    const content = text ?? '';
                    
                    if (content.length > 80) { // Ajustei o valor para um limite mais sensível
                        const moreBtn = document.createElement('span');
                        moreBtn.className = 'more-btn ml-2';
                        moreBtn.textContent = 'ver mais';

                        moreBtn.onclick = () => {
                            wrapper.classList.toggle('expanded');
                            if (wrapper.classList.contains('expanded')) {
                                moreBtn.textContent = 'ver menos';
                            } else {
                                moreBtn.textContent = 'ver mais';
                            }
                        };
                        td.appendChild(wrapper);
                        td.appendChild(moreBtn);
                    } else {
                        td.appendChild(wrapper);
                    }
                    return td;
                };

                tr.appendChild(c(r.id));
                tr.appendChild(c(r.nome));
                tr.appendChild(c(r.cpf));
                tr.appendChild(c(r.diretoria));
                tr.appendChild(c(r.data_inicial));
                tr.appendChild(c(r.data_final));
                tr.appendChild(createExpandableCell(r.atividades_realizadas));
                tr.appendChild(createExpandableCell(r.atividades_previstas));
                tr.appendChild(createExpandableCell(r.pontos_relevantes));
                tr.appendChild(c(r.data_registro));
                tbody.appendChild(tr);
            });
        }

        // ===================== Inicialização =====================
        document.addEventListener('DOMContentLoaded', () => {
            try {
                // Preenche colaborador
                const sel = document.getElementById('filter-collab');
                const nomes = Array.from(new Set(rows.map(r => r.nome).filter(Boolean))).sort();
                nomes.forEach(e => { const o=document.createElement('option'); o.value=e; o.textContent=e; sel.appendChild(o); });

                // Datas padrão: min(dia) .. max(dia)
                const dias = rows.map(r => r.dia).filter(Boolean).sort((a,b)=>a.localeCompare(b));
                const minDia = dias[0];
                const maxDia = dias[dias.length-1];
                if (minDia) document.getElementById('filter-start').value = minDia;
                if (maxDia) document.getElementById('filter-end').value   = maxDia;

                function recompute(){
                    const f = applyFilters(rows);
                    updateSummary(f);
                    renderCharts(f);
                    renderTable(f);
                    showErr('');
                }

                // Primeira render
                recompute();

                // Botões
                document.getElementById('apply-filters').addEventListener('click', recompute);
                document.getElementById('clear-filters').addEventListener('click', ()=>{
                    document.getElementById('filter-start').value = minDia || '';
                    document.getElementById('filter-end').value   = maxDia || '';
                    document.getElementById('filter-collab').value = '';
                    recompute();
                });
            } catch (e) {
                console.error(e); showErr('Erro ao inicializar o dashboard: '+ (e && e.message ? e.message : e));
            }
        });
    </script>
</body>
</html>