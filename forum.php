<?php
// 1) Autenticação (sempre primeiro)
include_once __DIR__ . '/php/auth_check.php';

// 2) Título da página (usado no <title> pelo header.php)
$pageTitle = 'Fóruns - MedinFocus';

// 3) Cabeçalho padrão (abre <html> e <body>)
include_once __DIR__ . '/includes/header.php';

// 4) Sidebar de navegação padrão
include_once __DIR__ . '/includes/sidebar_nav.php';
?>

<!-- CONTEÚDO PRINCIPAL (Tailwind-only, 1 por linha) -->
<div class="flex-1 flex flex-col">
  <!-- Header da página -->
  <header class="bg-white shadow-md p-4 flex items-center justify-between">
    <span class="text-xl font-bold text-gray-800">Fóruns</span>
    <div class="flex items-center gap-3">
      <div class="hidden md:flex items-center gap-2 bg-gray-100 rounded-xl px-3 py-2">
        <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.65 6.15z"/></svg>
        <input type="text" placeholder="Buscar fóruns..." class="bg-transparent placeholder-gray-500 text-sm focus:outline-none"/>
      </div>
      <a href="#" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Criar novo tópico
      </a>
    </div>
  </header>

  <main class="flex-1 p-4 md:p-8 overflow-y-auto">
    <!-- Estatísticas (chips simples) -->
    <section class="max-w-5xl mx-auto mb-6">
      <div class="flex flex-wrap gap-2">
        <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 border border-blue-100 px-3 py-1.5 rounded-full text-xs font-medium">📚 <strong class="font-semibold">23</strong> Fóruns</span>
        <span class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-100 px-3 py-1.5 rounded-full text-xs font-medium">🧵 <strong class="font-semibold">1.248</strong> Tópicos ativos</span>
        <span class="inline-flex items-center gap-2 bg-violet-50 text-violet-700 border border-violet-100 px-3 py-1.5 rounded-full text-xs font-medium">🟢 <strong class="font-semibold">27</strong> Usuários online</span>
      </div>
    </section>

    <!-- Lista 1 por linha, com "corezinhas" por categoria -->
    <section class="max-w-5xl mx-auto space-y-4">
      <!-- ITEM: Anatomia (verde) -->
      <a href="#" class="block rounded-2xl bg-white p-5 shadow-sm hover:shadow-md ring-1 ring-gray-100 hover:ring-gray-200 transition border-l-4 border-emerald-500">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Anatomia Humana</h3>
            <p class="mt-1 text-sm text-gray-600">Discussões sobre anatomia, atlas e recursos para estudo.</p>
            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
              <span class="inline-flex items-center gap-1">🧵 <span>32 tópicos</span></span>
              <span class="inline-flex items-center gap-1">⏱️ <span>2 h atrás</span></span>
            </div>
          </div>
          <span class="self-start text-[11px] px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-semibold">Novo</span>
        </div>
      </a>

      <!-- ITEM: Fisiologia (azul) -->
      <a href="#" class="block rounded-2xl bg-white p-5 shadow-sm hover:shadow-md ring-1 ring-gray-100 hover:ring-gray-200 transition border-l-4 border-blue-500">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Fisiologia</h3>
            <p class="mt-1 text-sm text-gray-600">Processos fisiológicos, integração com clínica e materiais de apoio.</p>
            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
              <span class="inline-flex items-center gap-1">🧵 <span>18 tópicos</span></span>
              <span class="inline-flex items-center gap-1">⏱️ <span>Ontem</span></span>
            </div>
          </div>
          <span class="self-start text-[11px] px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold">Atualizado</span>
        </div>
      </a>

      <!-- ITEM: Farmacologia (amber) -->
      <a href="#" class="block rounded-2xl bg-white p-5 shadow-sm hover:shadow-md ring-1 ring-gray-100 hover:ring-gray-200 transition border-l-4 border-amber-500">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Farmacologia</h3>
            <p class="mt-1 text-sm text-gray-600">Mecanismos de ação, RAM, interações e casos clínicos.</p>
            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
              <span class="inline-flex items-center gap-1">🧵 <span>45 tópicos</span></span>
              <span class="inline-flex items-center gap-1">⏱️ <span>3 dias atrás</span></span>
            </div>
          </div>
          <span class="self-start text-[11px] px-2 py-1 rounded-full bg-amber-100 text-amber-700 font-semibold">Em revisão</span>
        </div>
      </a>

      <!-- ITEM: Semiologia (violeta) -->
      <a href="#" class="block rounded-2xl bg-white p-5 shadow-sm hover:shadow-md ring-1 ring-gray-100 hover:ring-gray-200 transition border-l-4 border-violet-500">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Semiologia</h3>
            <p class="mt-1 text-sm text-gray-600">Exame físico, sinais, sintomas e correlação clínico‑patológica.</p>
            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
              <span class="inline-flex items-center gap-1">🧵 <span>29 tópicos</span></span>
              <span class="inline-flex items-center gap-1">⏱️ <span>1 h atrás</span></span>
            </div>
          </div>
          <span class="self-start text-[11px] px-2 py-1 rounded-full bg-violet-100 text-violet-700 font-semibold">Em alta</span>
        </div>
      </a>

      <!-- Paginação -->
      <div class="pt-2 flex justify-center items-center gap-2">
        <button class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm">Anterior</button>
        <a class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm">1</a>
        <a class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm" href="#">2</a>
        <a class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm" href="#">3</a>
        <button class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm">Próxima</button>
      </div>

      <!-- CTA -->
      <div class="pt-4 text-center">
        <a href="#" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-5 rounded-xl shadow transition">
          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Criar novo tópico
        </a>
      </div>
    </section>
  </main>
</div>

<?php
// 5) Rodapé padrão (fecha </body></html>)
include_once __DIR__ . '/includes/footer.php';
?>