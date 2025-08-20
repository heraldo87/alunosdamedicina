<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard de Obras da Prefeitura — Exemplo</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Acessibilidade e micro melhorias */
    :root { color-scheme: light dark; }
    html { scroll-behavior: smooth; }
    .card { border-radius: 1rem; box-shadow: 0 6px 18px rgba(0,0,0,.06); }
    .chip { padding: .2rem .5rem; border-radius: 999px; font-size: .75rem; }
  </style>
</head>
<body class="bg-gray-100 text-gray-800">
  <header class="bg-white sticky top-0 z-30 border-b">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold">Painel de Obras da Prefeitura</h1>
        <p class="text-sm text-gray-500">Acompanhamento executivo — dados de demonstração.</p>
      </div>
      <button id="btnExport" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-xl">
        Baixar CSV
      </button>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    <!-- Filtros -->
    <section class="card bg-white p-4 md:p-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-2">
          <label class="text-sm text-gray-600">Busca</label>
          <input id="f_busca" type="text" placeholder="Obra, empreiteira, fiscal..."
                 class="mt-1 w-full border rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
          <label class="text-sm text-gray-600">Status</label>
          <select id="f_status" class="mt-1 w-full border rounded-xl px-3 py-2">
            <option>Todos</option>
            <option>Concluída</option>
            <option>Em execução</option>
            <option>Atrasada</option>
            <option>Paralisada</option>
            <option>Licitação</option>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">Secretaria</label>
          <select id="f_secretaria" class="mt-1 w-full border rounded-xl px-3 py-2"></select>
        </div>
        <div>
          <label class="text-sm text-gray-600">Bairro</label>
          <select id="f_bairro" class="mt-1 w-full border rounded-xl px-3 py-2"></select>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Início (mín):</span>
          <input id="f_ini" type="date" class="border rounded-lg px-2 py-1">
          <span class="text-gray-400">—</span>
          <span class="text-sm text-gray-600">Fim (máx):</span>
          <input id="f_fim" type="date" class="border rounded-lg px-2 py-1">
        </div>
        <div class="flex items-center gap-2">
          <button id="btnLimpar" class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-xl">Limpar filtros</button>
        </div>
      </div>
    </section>

    <!-- KPIs -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="card bg-white p-4">
        <div class="text-gray-500 text-xs">Obras no filtro</div>
        <div id="kpi_total" class="text-2xl font-bold mt-1">0</div>
        <div class="text-xs text-gray-400">Total de contratos</div>
      </div>
      <div class="card bg-white p-4">
        <div class="text-gray-500 text-xs">Valor agregado</div>
        <div id="kpi_valor" class="text-2xl font-bold mt-1">R$ 0,00</div>
        <div class="text-xs text-gray-400">Soma do orçamento</div>
      </div>
      <div class="card bg-white p-4">
        <div class="text-gray-500 text-xs">Concluídas</div>
        <div id="kpi_conc" class="text-2xl font-bold mt-1">0</div>
        <div class="text-xs text-gray-400">No período selecionado</div>
      </div>
      <div class="card bg-white p-4">
        <div class="text-gray-500 text-xs">Atrasadas</div>
        <div id="kpi_atr" class="text-2xl font-bold mt-1">0</div>
        <div class="text-xs text-gray-400">Demandam atenção</div>
      </div>
    </section>

    <!-- Gráficos -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="card bg-white p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Status das obras</h3>
          <span class="text-xs text-gray-400">Passe o mouse</span>
        </div>
        <canvas id="chartStatus" height="230" aria-label="Gráfico de pizza por status"></canvas>
      </div>

      <div class="card bg-white p-4 lg:col-span-2">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Orçamento por secretaria</h3>
          <span class="text-xs text-gray-400">Valores em R$</span>
        </div>
        <canvas id="chartSecretaria" height="230" aria-label="Gráfico de barras por secretaria"></canvas>
      </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="card bg-white p-4 lg:col-span-2">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Progresso médio por mês</h3>
          <span class="text-xs text-gray-400">0 a 100%</span>
        </div>
        <canvas id="chartAndamento" height="230" aria-label="Gráfico de linha por mês"></canvas>
      </div>

      <!-- “Mapa” ilustrativo (sem libs) -->
      <div class="card bg-white p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Mapa ilustrativo</h3>
          <span class="text-xs text-gray-400">Distribuição simbólica</span>
        </div>
        <div id="miniMap" class="h-60 rounded-xl border bg-gradient-to-br from-sky-50 to-emerald-50 relative overflow-hidden"></div>
      </div>
    </section>

    <!-- Tabela -->
    <section class="card bg-white p-4 md:p-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Lista de obras</h3>
        <div class="text-sm text-gray-500"><span id="contaItens">0</span> itens</div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm" id="tabela">
          <thead class="bg-gray-50">
            <tr class="text-left text-gray-600">
              <th class="p-2 cursor-pointer" data-sort="id">ID</th>
              <th class="p-2 cursor-pointer" data-sort="obra">Obra</th>
              <th class="p-2 cursor-pointer" data-sort="secretaria">Secretaria</th>
              <th class="p-2 cursor-pointer" data-sort="bairro">Bairro</th>
              <th class="p-2 cursor-pointer" data-sort="custo">Custo</th>
              <th class="p-2 cursor-pointer" data-sort="inicio">Início</th>
              <th class="p-2 cursor-pointer" data-sort="fimPrevisto">Fim Prev.</th>
              <th class="p-2 cursor-pointer" data-sort="status">Status</th>
              <th class="p-2">%</th>
            </tr>
          </thead>
          <tbody id="tbody"></tbody>
        </table>
      </div>
    </section>

    <footer class="text-center text-xs text-gray-500 pb-10">
      * Painel demonstrativo. Para dados reais, basta trocar o array no script ou integrar com uma API.
    </footer>
  </main>

  <script>
    // ====== DADOS DE EXEMPLO ======
    const OBRAS = [
      { id:101, obra:"Requalificação da Av. Central", secretaria:"Infraestrutura", bairro:"Centro", custo:4200000, inicio:"2025-02-10", fimPrevisto:"2025-11-30", status:"Em execução", percentual:62, empreiteira:"Construtora Atlas", fiscal:"Eng. Paula Sousa", x:22, y:28 },
      { id:102, obra:"Construção da UBS Vila Verde", secretaria:"Saúde", bairro:"Vila Verde", custo:1800000, inicio:"2024-12-01", fimPrevisto:"2025-08-15", status:"Atrasada", percentual:48, empreiteira:"SaúdeMais Obras", fiscal:"Eng. Marcelo Lima", x:70, y:54 },
      { id:103, obra:"Reforma da EMEF Monte Azul", secretaria:"Educação", bairro:"Monte Azul", custo:950000, inicio:"2025-03-05", fimPrevisto:"2025-09-30", status:"Em execução", percentual:55, empreiteira:"Alfa Engenharia", fiscal:"Eng. Carla Nunes", x:62, y:36 },
      { id:104, obra:"Drenagem Rua das Flores", secretaria:"Infraestrutura", bairro:"Jardins", custo:720000, inicio:"2025-01-18", fimPrevisto:"2025-06-25", status:"Concluída", percentual:100, empreiteira:"Delta Obras", fiscal:"Eng. Ricardo Pires", x:40, y:40 },
      { id:105, obra:"Praça da Juventude", secretaria:"Esportes e Lazer", bairro:"Aurora", custo:1300000, inicio:"2025-04-12", fimPrevisto:"2025-12-20", status:"Em execução", percentual:37, empreiteira:"Vita Construções", fiscal:"Eng. Júlia Freitas", x:18, y:68 },
      { id:106, obra:"Recapeamento Bairro Sul", secretaria:"Infraestrutura", bairro:"Bairro Sul", custo:2600000, inicio:"2025-02-01", fimPrevisto:"2025-07-31", status:"Atrasada", percentual:44, empreiteira:"PavMix", fiscal:"Eng. Tiago Moraes", x:82, y:24 },
      { id:107, obra:"Centro de Referência ao Idoso", secretaria:"Assistência Social", bairro:"Boa Esperança", custo:2100000, inicio:"2025-01-25", fimPrevisto:"2025-10-10", status:"Em execução", percentual:29, empreiteira:"Solidus", fiscal:"Eng. Ana Barros", x:30, y:16 },
      { id:108, obra:"Quadra Coberta EMEI Alecrim", secretaria:"Educação", bairro:"Alecrim", custo:560000, inicio:"2025-05-05", fimPrevisto:"2025-09-15", status:"Licitação", percentual:0, empreiteira:"—", fiscal:"—", x:10, y:52 },
      { id:109, obra:"Ponte do Ribeirão", secretaria:"Infraestrutura", bairro:"Ribeirinho", custo:3800000, inicio:"2024-11-12", fimPrevisto:"2025-10-30", status:"Paralisada", percentual:23, empreiteira:"Contex", fiscal:"Eng. Roberto Dias", x:50, y:74 },
      { id:110, obra:"Ciclofaixa Zona Norte", secretaria:"Mobilidade", bairro:"Zona Norte", custo:1550000, inicio:"2025-03-20", fimPrevisto:"2025-10-01", status:"Em execução", percentual:41, empreiteira:"MobWay", fiscal:"Eng. Priscila Gama", x:86, y:68 },
    ];

    // ====== Estado / Filtros ======
    const $ = (s) => document.querySelector(s);
    const f = {
      busca: $('#f_busca'),
      status: $('#f_status'),
      secretaria: $('#f_secretaria'),
      bairro: $('#f_bairro'),
      ini: $('#f_ini'),
      fim: $('#f_fim'),
      limpar: $('#btnLimpar'),
    };

    // Preenche combos de secretaria e bairro
    const secretarias = ["Todas", ...Array.from(new Set(OBRAS.map(o=>o.secretaria)))];
    const bairros = ["Todos", ...Array.from(new Set(OBRAS.map(o=>o.bairro)))];

    f.secretaria.innerHTML = secretarias.map(s=>`<option>${s}</option>`).join('');
    f.bairro.innerHTML = bairros.map(s=>`<option>${s}</option>`).join('');

    // ====== Funções util ======
    const brl = (v) => v.toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
    const statusClass = (s) => ({
      'Concluída':'bg-emerald-100 text-emerald-800',
      'Em execução':'bg-blue-100 text-blue-800',
      'Atrasada':'bg-orange-100 text-orange-800',
      'Paralisada':'bg-red-100 text-red-800',
      'Licitação':'bg-slate-100 text-slate-800',
    }[s] || 'bg-gray-100 text-gray-700');

    function filtrar() {
      const t = (f.busca.value || '').toLowerCase().trim();
      const st = f.status.value;
      const sc = f.secretaria.value;
      const bb = f.bairro.value;
      const di = f.ini.value;
      const df = f.fim.value;

      return OBRAS.filter(o=>{
        const texto = !t || `${o.obra} ${o.secretaria} ${o.bairro} ${o.empreiteira} ${o.fiscal}`.toLowerCase().includes(t);
        const stOk = st==='Todos' || o.status===st;
        const scOk = sc==='Todas' || o.secretaria===sc;
        const bbOk = bb==='Todos' || o.bairro===bb;
        const diOk = !di || o.inicio >= di;
        const dfOk = !df || o.fimPrevisto <= df;
        return texto && stOk && scOk && bbOk && diOk && dfOk;
      });
    }

    // ====== KPIs ======
    function atualizarKPIs(data) {
      const total = data.length;
      const valor = data.reduce((s,o)=>s+o.custo,0);
      const conc = data.filter(o=>o.status==='Concluída').length;
      const atr = data.filter(o=>o.status==='Atrasada').length;

      $('#kpi_total').textContent = total;
      $('#kpi_valor').textContent = brl(valor);
      $('#kpi_conc').textContent = conc;
      $('#kpi_atr').textContent = atr;
      $('#contaItens').textContent = total;
    }

    // ====== Tabela ======
    let sortKey = 'id', sortAsc = false;
    function renderTabela(data) {
      const tbody = $('#tbody');
      const sorted = [...data].sort((a,b)=>{
        let va=a[sortKey], vb=b[sortKey];
        if (typeof va === 'string') { va = va.toLowerCase(); vb = vb.toLowerCase(); }
        if (va<vb) return sortAsc?-1:1;
        if (va>vb) return sortAsc?1:-1;
        return 0;
      });

      tbody.innerHTML = sorted.map(o=>`
        <tr class="border-b">
          <td class="p-2">${o.id}</td>
          <td class="p-2">
            <div class="font-medium">${o.obra}</div>
            <div class="text-xs text-gray-500">${o.empreiteira} • ${o.fiscal}</div>
          </td>
          <td class="p-2">${o.secretaria}</td>
          <td class="p-2">${o.bairro}</td>
          <td class="p-2 font-medium">${brl(o.custo)}</td>
          <td class="p-2">${o.inicio}</td>
          <td class="p-2">${o.fimPrevisto}</td>
          <td class="p-2"><span class="chip ${statusClass(o.status)}">${o.status}</span></td>
          <td class="p-2">
            <div class="w-24 bg-gray-100 rounded-full h-2">
              <div class="bg-blue-500 h-2 rounded-full" style="width:${o.percentual}%"></div>
            </div>
            <div class="text-xs text-gray-600 mt-1">${o.percentual}%</div>
          </td>
        </tr>
      `).join('');
    }

    // ====== Gráficos (Chart.js) ======
    let chartStatus, chartSecretaria, chartAndamento;

    function datasetStatus(data) {
      const grupos = {};
      data.forEach(o=> grupos[o.status] = (grupos[o.status]||0)+1);
      return { labels:Object.keys(grupos), values:Object.values(grupos) };
    }

    function datasetSecretaria(data) {
      const grupos = {};
      data.forEach(o=> grupos[o.secretaria] = (grupos[o.secretaria]||0)+o.custo);
      const labels = Object.keys(grupos);
      const values = labels.map(l=>grupos[l]);
      return { labels, values };
    }

    function datasetAndamento(data) {
      const buck = {};
      data.forEach(o=>{
        const ym = o.inicio.slice(0,7);
        if(!buck[ym]) buck[ym]=[];
        buck[ym].push(o.percentual);
      });
      const labels = Object.keys(buck).sort();
      const values = labels.map(k => Math.round(buck[k].reduce((s,v)=>s+v,0)/buck[k].length));
      return { labels, values };
    }

    function renderGraficos(data) {
      // Status
      const s = datasetStatus(data);
      if (chartStatus) chartStatus.destroy();
      chartStatus = new Chart(document.getElementById('chartStatus').getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: s.labels,
          datasets: [{ data: s.values }]
        },
        options: {
          plugins: { legend: { position:'bottom' } },
          cutout: '55%'
        }
      });

      // Secretaria
      const sec = datasetSecretaria(data);
      if (chartSecretaria) chartSecretaria.destroy();
      chartSecretaria = new Chart(document.getElementById('chartSecretaria').getContext('2d'), {
        type: 'bar',
        data: {
          labels: sec.labels,
          datasets: [{ label: 'Orçamento (R$)', data: sec.values }]
        },
        options: {
          scales: {
            y: { ticks: { callback: v => 'R$ ' + (v/1_000_000).toFixed(1) + ' mi' } }
          },
          plugins: { legend: { display:false }, tooltip: { callbacks: { label: ctx => brl(ctx.parsed.y) } } }
        }
      });

      // Andamento mensal
      const and = datasetAndamento(data);
      if (chartAndamento) chartAndamento.destroy();
      chartAndamento = new Chart(document.getElementById('chartAndamento').getContext('2d'), {
        type: 'line',
        data: {
          labels: and.labels,
          datasets: [{ label:'Progresso médio (%)', data: and.values, tension:.3 }]
        },
        options: {
          scales: { y: { min:0, max:100 } },
          plugins: { legend: { display:false } }
        }
      });
    }

    // ====== Mini “Mapa” (pontos relativos) ======
    function renderMiniMap(data) {
      const el = $('#miniMap');
      el.innerHTML = '';
      // grid leve
      for (let i=1;i<6;i++){
        const h = document.createElement('div');
        h.className='absolute left-0 right-0 border-t border-dashed border-gray-200';
        h.style.top = (i*16)+'%';
        el.appendChild(h);
        const v = document.createElement('div');
        v.className='absolute top-0 bottom-0 border-l border-dashed border-gray-200';
        v.style.left = (i*16)+'%';
        el.appendChild(v);
      }
      // pontos
      data.forEach(o=>{
        const p = document.createElement('div');
        p.className = 'absolute w-3 h-3 rounded-full';
        const color = o.status==='Concluída' ? 'bg-emerald-500' :
                      o.status==='Atrasada' ? 'bg-orange-500' :
                      o.status==='Paralisada' ? 'bg-red-500' : 'bg-blue-500';
        p.classList.add(color);
        p.style.left = `calc(${o.x}% - .375rem)`;
        p.style.top  = `calc(${o.y}% - .375rem)`;
        p.title = `${o.obra} — ${o.bairro}`;
        el.appendChild(p);
      });
    }

    // ====== Exportar CSV ======
    function exportCSV(data) {
      const headers = ["ID","Obra","Secretaria","Bairro","Custo","Início","Fim Previsto","Status","%","Empreiteira","Fiscal"];
      const rows = data.map(o=>[
        o.id,o.obra,o.secretaria,o.bairro,o.custo,o.inicio,o.fimPrevisto,o.status,o.percentual,o.empreiteira,o.fiscal
      ]);
      const csv = [headers, ...rows]
        .map(r => r.map(v => String(v).replaceAll('\n',' ').replaceAll('"','""')).map(v=>`"${v}"`).join(','))
        .join('\n');
      const blob = new Blob(["\uFEFF"+csv], { type:'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = `obras_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.csv`;
      document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
    }

    // ====== Integração geral ======
    let DATA = [...OBRAS];

    function apply() {
      const data = filtrar();
      atualizarKPIs(data);
      renderTabela(data);
      renderGraficos(data);
      renderMiniMap(data);
    }

    // eventos
    [f.busca, f.status, f.secretaria, f.bairro, f.ini, f.fim].forEach(el => el.addEventListener('input', apply));
    f.limpar.addEventListener('click', ()=>{
      f.busca.value=''; f.status.value='Todos'; f.secretaria.value='Todas'; f.bairro.value='Todos'; f.ini.value=''; f.fim.value='';
      apply();
    });

    // Ordenação
    document.querySelectorAll('#tabela thead th[data-sort]').forEach(th=>{
      th.addEventListener('click', ()=>{
        const key = th.getAttribute('data-sort');
        if (sortKey === key) sortAsc = !sortAsc; else { sortKey = key; sortAsc = true; }
        apply();
      });
    });

    // Export
    document.getElementById('btnExport').addEventListener('click', ()=> exportCSV(filtrar()));

    // primeira renderização
    apply();
  </script>
</body>
</html>
