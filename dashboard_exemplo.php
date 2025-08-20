<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard (demo) – EMOP style</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <style>
    :root { --brand:#0c4a6e; --brand-2:#075985; --brand-3:#1e293b; }
    /* barra de rolagem discreta no container principal */
    ::-webkit-scrollbar { height: 8px; width: 8px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
  </style>
</head>
<body class="bg-slate-50 text-slate-900">
  <!-- Shell -->
  <div class="min-h-screen grid grid-cols-12">
    <!-- Sidebar -->
    <aside class="col-span-12 md:col-span-2 xl:col-span-2 bg-[var(--brand)] text-white p-4 md:p-6 space-y-6">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-white/10 grid place-items-center font-bold">G</div>
        <div>
          <div class="text-lg font-semibold leading-tight">Gov-RJ (demo)</div>
          <div class="text-xs text-white/80">Dashboard fictício</div>
        </div>
      </div>
      <nav class="space-y-2">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-white/10 transition" href="#">
          <span class="inline-block w-5 h-5">🏠</span>
          <span>Início</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-white/10 transition" href="#empresas">
          <span class="inline-block w-5 h-5">🏗️</span>
          <span>Empresas</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-white/10 transition" href="#contratos">
          <span class="inline-block w-5 h-5">📄</span>
          <span>Contratos</span>
        </a>
      </nav>
      <div class="mt-8 space-y-3 text-sm">
        <div class="font-semibold">Filtros (demo)</div>
        <label class="block">Município
          <select class="mt-1 w-full bg-white/10 border border-white/20 rounded-lg px-2 py-1">
            <option>Todos</option>
            <option>Rio de Janeiro</option>
            <option>Niterói</option>
            <option>Volta Redonda</option>
          </select>
        </label>
        <label class="block">Secretaria
          <select class="mt-1 w-full bg-white/10 border border-white/20 rounded-lg px-2 py-1">
            <option>Todos</option>
            <option>Infraestrutura</option>
            <option>Educação</option>
            <option>Saúde</option>
          </select>
        </label>
      </div>
      <p class="text-xs text-white/70 pt-4">* Todos os números são <strong>fictícios</strong> e servem apenas para demonstração.</p>
    </aside>

    <!-- Main -->
    <main class="col-span-12 md:col-span-10 xl:col-span-10 p-4 md:p-6 lg:p-8 space-y-6 overflow-x-hidden">
      <!-- Top title -->
      <header class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl md:text-2xl font-bold text-[var(--brand-3)]">Acompanhamento de Obras (DEMO)</h1>
        <div class="text-sm text-slate-600">Atualizado agora • Dados simulados</div>
      </header>

      <!-- KPI cards -->
      <section class="grid grid-cols-12 gap-4">
        <!-- Valor total -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <div class="text-slate-600 font-semibold uppercase tracking-wide">Valor total da Obra</div>
          <div id="kpi-total" class="mt-4 text-2xl font-bold">—</div>
        </article>
        <!-- Realizado -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <div class="text-slate-600 font-semibold uppercase tracking-wide">Realizado</div>
          <div id="kpi-realizado" class="mt-4 text-2xl font-bold">—</div>
        </article>
        <!-- Saldo -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <div class="text-slate-600 font-semibold uppercase tracking-wide">Saldo Contratual</div>
          <div id="kpi-saldo" class="mt-4 text-2xl font-bold">—</div>
        </article>
        <!-- Evolução financeira (gauge) -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <div class="text-slate-600 font-semibold uppercase tracking-wide">Evolução Financeira</div>
          <div class="mt-2">
            <canvas id="gauge" height="160"></canvas>
          </div>
          <div class="mt-2 flex justify-between text-xs text-slate-500">
            <span>0,00%</span>
            <span>100,00%</span>
          </div>
        </article>
      </section>

      <!-- Middle cards -->
      <section class="grid grid-cols-12 gap-4">
        <!-- Previsão de desembolso -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <h3 class="font-semibold text-[var(--brand-3)]">Previsão de Desembolso</h3>
          <div class="mt-4 space-y-3 text-slate-700">
            <div>
              <div id="prev-2025" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">2025</div>
            </div>
            <div>
              <div id="prev-2026" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">2026</div>
            </div>
          </div>
        </article>

        <!-- Resolução conjunta -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <h3 class="font-semibold text-[var(--brand-3)]">Resolução Conjunta</h3>
          <div class="mt-4 space-y-3 text-slate-700">
            <div>
              <div id="rc-conjunta" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">CONJUNTA</div>
            </div>
            <div>
              <div id="rc-saldo" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">SALDO</div>
            </div>
          </div>
        </article>

        <!-- Descentralização -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <h3 class="font-semibold text-[var(--brand-3)]">Descentralização</h3>
          <div class="mt-4 space-y-3 text-slate-700">
            <div>
              <div id="desc-total" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">DESCENTRALIZAÇÃO</div>
            </div>
            <div>
              <div id="desc-saldo" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">SALDO</div>
            </div>
          </div>
        </article>

        <!-- Empenho -->
        <article class="col-span-12 md:col-span-6 lg:col-span-3 bg-white rounded-2xl shadow p-4">
          <h3 class="font-semibold text-[var(--brand-3)]">Empenho</h3>
          <div class="mt-4 space-y-3 text-slate-700">
            <div>
              <div id="emp-total" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">EMPENHO</div>
            </div>
            <div>
              <div id="emp-saldo" class="text-xl font-bold">—</div>
              <div class="text-sm text-slate-500">SALDO</div>
            </div>
          </div>
        </article>
      </section>

      <!-- Empresas + Tabela -->
      <section class="grid grid-cols-12 gap-4" id="empresas">
        <!-- Empresas -->
        <article class="col-span-12 lg:col-span-4 bg-white rounded-2xl shadow p-4">
          <h3 class="font-semibold text-[var(--brand-3)]">Empresa</h3>
          <input id="empresa-search" type="search" placeholder="Filtrar empresas..." class="mt-3 w-full border rounded-lg px-3 py-2 text-sm" />
          <ul id="empresa-list" class="mt-3 divide-y max-h-[360px] overflow-auto"></ul>
        </article>

        <!-- Contrato - Vigência -->
        <article class="col-span-12 lg:col-span-8 bg-white rounded-2xl shadow p-4" id="contratos">
          <h3 class="font-semibold text-[var(--brand-3)]">Contrato - Vigência</h3>
          <div class="mt-3 overflow-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-slate-600">
                  <th class="py-2 pr-4">OBRA</th>
                  <th class="py-2 pr-4">TCT INÍCIO</th>
                  <th class="py-2 pr-4">TCT TÉRMINO</th>
                  <th class="py-2 pr-4">INÍCIO</th>
                  <th class="py-2 pr-4">TÉRMINO</th>
                  <th class="py-2 pr-4">OBRA INÍCIO</th>
                  <th class="py-2 pr-4">OBRA TÉRMINO</th>
                  <th class="py-2 pr-4">GARANTIA</th>
                </tr>
              </thead>
              <tbody id="contratos-body" class="divide-y"></tbody>
            </table>
          </div>
        </article>
      </section>

      <footer class="pt-4 text-xs text-slate-500">Interface inspirada no layout público da EMOP-RJ, apenas para fins educacionais. © 2025</footer>
    </main>
  </div>

  <script>
    // =========================
    // DADOS FICTÍCIOS (pode editar)
    // =========================
    const dados = {
      totalObra: 206_980_881.34,
      realizado: 89_422_825.37,
      previsao: { 2025: 100_571_967.02, 2026: 36_064_121.13 },
      resolucaoConjunta: { conjunta: 54_273_221.32, saldo: -46_298_745.70 },
      descentralizacao: { total: 50_218_361.21, saldo: -50_353_605.81 },
      empenho: { total: 46_430_382.02, saldo: 22_657_065.92 },
      empresas: [
        'Irmãos Haddad Construtora EIRELI',
        'Kadima Construções Ltda.',
        'Midas Engenharia LTDA.',
        'MTF Construção e Manutenção Ltda',
        'RR Fênix Tecnologia em Serviços Ltda.',
        'Scalle Construções, Reformas e Instalações LTDA',
        'TN de Souza, Comércio, Serviços e Consultoria LTDA',
        'Cense Muro Ilha',
        'DEAM',
        'CRIAD Cabo Frio'
      ],
      contratos: [
        {
          obra: 'C.E. Maurício Medeiros de Alvarenga - Rio das Ostras',
          tctInicio: '14/03/2023', tctTermino: '20/12/2025',
          inicio: '10/06/2022', termino: '30/05/2026',
          obraInicio: '23/06/2022', obraTermino: '31/12/2025', garantia: '28/10/2026'
        },
        { obra: 'Cense São Gonçalo', tctInicio: '04/04/2024', tctTermino: '26/10/2025', inicio: '31/08/2024', termino: '25/02/2026', obraInicio: '20/09/2024', obraTermino: '18/10/2025', garantia: '31/08/2026' },
        { obra: 'Centro de Mídia (projetos)', tctInicio: '13/05/2024', tctTermino: '05/09/2025', inicio: '15/10/2024', termino: '29/10/2025', obraInicio: '15/10/2024', obraTermino: '31/07/2025', garantia: '12/06/2026' },
        { obra: 'Core Lagoa', tctInicio: '12/07/2024', tctTermino: '31/05/2026', inicio: '23/05/2024', termino: '31/03/2025', obraInicio: '31/12/2024', obraTermino: '31/12/2025', garantia: '06/11/2026' },
        { obra: 'CRIAD Cabo Frio', tctInicio: '19/08/2024', tctTermino: '23/10/2026', inicio: '20/09/2024', termino: '24/07/2026', obraInicio: '27/12/2024', obraTermino: '28/02/2026', garantia: '18/03/2027' },
        { obra: 'DEAM', tctInicio: '15/05/2024', tctTermino: '31/12/2025', inicio: '27/09/2023', termino: '28/02/2026', obraInicio: '12/03/2024', obraTermino: '12/08/2025', garantia: '11/10/2026' },
        { obra: 'Def. Campos', tctInicio: '18/11/2021', tctTermino: '10/06/2024', inicio: '02/09/2022', termino: '19/01/2025', obraInicio: '04/02/2022', obraTermino: '09/07/2024', garantia: '31/01/2027' }
      ]
    };

    // =========================
    // Helpers
    // =========================
    const brl = (n) => n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const pct = (n) => n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';

    // =========================
    // KPIs
    // =========================
    const saldo = dados.totalObra - dados.realizado;
    document.getElementById('kpi-total').textContent = brl(dados.totalObra);
    document.getElementById('kpi-realizado').textContent = brl(dados.realizado);
    document.getElementById('kpi-saldo').textContent = brl(saldo);

    // Previsão
    document.getElementById('prev-2025').textContent = brl(dados.previsao[2025]);
    document.getElementById('prev-2026').textContent = brl(dados.previsao[2026]);

    // RC
    document.getElementById('rc-conjunta').textContent = brl(dados.resolucaoConjunta.conjunta);
    document.getElementById('rc-saldo').textContent = brl(dados.resolucaoConjunta.saldo);

    // Descentralização
    document.getElementById('desc-total').textContent = brl(dados.descentralizacao.total);
    document.getElementById('desc-saldo').textContent = brl(dados.descentralizacao.saldo);

    // Empenho
    document.getElementById('emp-total').textContent = brl(dados.empenho.total);
    document.getElementById('emp-saldo').textContent = brl(dados.empenho.saldo);

    // =========================
    // Empresas (lista + filtro)
    // =========================
    const ul = document.getElementById('empresa-list');
    function renderEmpresas(q = '') {
      ul.innerHTML = '';
      dados.empresas.filter(e => e.toLowerCase().includes(q.toLowerCase())).forEach((nome) => {
        const li = document.createElement('li');
        li.className = 'py-2 flex items-center gap-3';
        li.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span><span>${nome}</span>`;
        ul.appendChild(li);
      });
    }
    renderEmpresas();
    document.getElementById('empresa-search').addEventListener('input', (ev) => renderEmpresas(ev.target.value));

    // =========================
    // Tabela de contratos
    // =========================
    const tbody = document.getElementById('contratos-body');
    dados.contratos.forEach((c) => {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-slate-50';
      tr.innerHTML = `
        <td class="py-2 pr-4">${c.obra}</td>
        <td class="py-2 pr-4">${c.tctInicio}</td>
        <td class="py-2 pr-4">${c.tctTermino}</td>
        <td class="py-2 pr-4">${c.inicio}</td>
        <td class="py-2 pr-4">${c.termino}</td>
        <td class="py-2 pr-4">${c.obraInicio}</td>
        <td class="py-2 pr-4">${c.obraTermino}</td>
        <td class="py-2 pr-4">${c.garantia}</td>
      `;
      tbody.appendChild(tr);
    });

    // =========================
    // Gauge (Chart.js semicírculo com texto central)
    // =========================
    const perc = (dados.realizado / dados.totalObra) * 100;

    const centerText = {
      id: 'centerText',
      afterDatasetsDraw(chart, args, pluginOptions) {
        const {ctx} = chart;
        ctx.save();
        const value = pct(perc);
        ctx.font = '600 20px Inter, system-ui, -apple-system, Segoe UI, Roboto';
        ctx.fillStyle = '#0f172a';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        const {x, y} = chart.getDatasetMeta(0).data[0].getProps(['x', 'y']);
        ctx.fillText(value, x, y);
        ctx.restore();
      }
    };

    const gctx = document.getElementById('gauge');
    new Chart(gctx, {
      type: 'doughnut',
      data: {
        labels: ['Realizado', 'Restante'],
        datasets: [{
          data: [perc, 100 - perc],
          borderWidth: 0,
          // Deixe o Chart.js escolher as cores padrão do tema do navegador
          cutout: '70%'
        }]
      },
      options: {
        animation: { animateRotate: true },
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        circumference: 180,
        rotation: 270
      },
      plugins: [centerText]
    });
  </script>
</body>
</html>
