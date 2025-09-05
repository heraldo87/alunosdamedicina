<?php
// 1) Autenticação obrigatória
require_once __DIR__ . '/php/auth_check.php';

// 2) Metadados da página (usados pelos partials/header & sidebar)
$pageTitle  = 'Quizzes - MedinFocus';
$activeMenu = 'quizzes';
$userId     = (int)($_SESSION['user_id'] ?? 0);

// Nome seguro do usuário para saudação
$nomeBase = $_SESSION['full_name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['email']
    ?? 'Aluno';
$safeNome = htmlspecialchars(is_string($nomeBase) ? trim($nomeBase) : 'Aluno', ENT_QUOTES, 'UTF-8');

// 3) Partials padrão (mesmo padrão do index)
// header.php deve abrir <!DOCTYPE html>, <html>, <head> (com Tailwind/Inter) e <body>
require_once __DIR__ . '/includes/header.php';

// Topbar (se existir)
if (file_exists(__DIR__ . '/includes/topbar.php')) {
    require_once __DIR__ . '/includes/topbar.php';
}

// Sidebar de navegação (o arquivo deve renderizar um elemento com id="sidebar" OU data-sidebar)
require_once __DIR__ . '/includes/sidebar_nav.php';
?>

<style>
  /* Estilos locais e leves (ideal mover para o CSS global do tema) */
  .custom-scrollbar::-webkit-scrollbar { width: 8px; }
  .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
  .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
  .quiz-card { transition: transform .2s ease, box-shadow .2s ease; }
  .quiz-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px rgba(0,0,0,.08); }
  .modal-enter { animation: fadeIn .25s ease; }
  @keyframes fadeIn { from {opacity:0; transform: translateY(-6px);} to {opacity:1; transform: translateY(0);} }
</style>

<div class="flex-1 flex flex-col min-h-0">
  <!-- Header interno da página (mantém layout do tema) -->
  <header class="bg-white shadow-sm p-4 flex items-center justify-between">
    <button id="openSidebarBtn" class="text-gray-500 hover:text-gray-700 lg:hidden" aria-label="Abrir menu lateral">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
    <span class="text-xl font-bold text-gray-800">Quizzes</span>
    <span class="text-gray-600">Olá, <?= $safeNome ?>!</span>
  </header>

  <!-- Conteúdo principal -->
  <main class="flex-1 p-4 bg-gray-50 overflow-y-auto custom-scrollbar">
    <div class="max-w-4xl mx-auto py-6">
      <!-- Painel de seleção do quiz -->
      <section id="quiz-selection-panel" class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-2">Escolha um Quiz para Começar</h2>
        <p class="text-gray-600 mb-4">Selecione as opções abaixo para iniciar um novo desafio.</p>

        <div class="flex flex-col md:flex-row md:items-center gap-4">
          <div class="flex-1">
            <label for="discipline-select" class="sr-only">Selecione a Disciplina</label>
            <select id="discipline-select" class="w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="" disabled selected>-- Selecione a Disciplina --</option>
            </select>
          </div>
          <div class="flex-1">
            <label for="partial-select" class="sr-only">Selecione a Parcial</label>
            <select id="partial-select" class="w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" disabled>
              <option value="" disabled selected>-- Selecione a Parcial --</option>
            </select>
          </div>
          <div class="flex-1">
            <label for="quiz-select" class="sr-only">Selecione o Quiz</label>
            <select id="quiz-select" class="w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" disabled>
              <option value="" disabled selected>-- Selecione o Quiz --</option>
            </select>
          </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-center gap-4 mt-6">
          <button id="start-quiz-btn" class="w-full md:w-auto px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50" disabled>
            Começar Quiz
          </button>
          <a href="#" id="create-quiz-btn" class="w-full md:w-auto px-6 py-2 border border-gray-300 text-gray-700 text-center font-medium rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300">
            Criar meu quiz
          </a>
        </div>
      </section>

      <!-- Container do quiz renderizado -->
      <div id="quiz-container" class="mt-8 hidden"></div>

      <!-- Toast/Notificação -->
      <div id="notification-modal" class="fixed inset-x-0 bottom-4 mx-auto p-4 bg-red-500 text-white rounded-lg shadow-lg w-fit transition-all duration-300 transform opacity-0 hidden z-50"></div>
    </div>
  </main>
</div>

<?php
// Debug opcional (aparece apenas no HTML, útil em dev)
$lvl = $_SESSION['access_level'] ?? ($_SESSION['nivel_acesso'] ?? null);
$apv = $_SESSION['aprovacao'] ?? null;
echo "<!-- Debug: user_id=" . var_export($userId, true) . " access_level=" . var_export($lvl, true) . " aprovacao=" . var_export($apv, true) . " -->";
?>

<script>
(() => {
  // Expor userId se necessário para telemetria ou POSTs futuros
  window.MEDINFOCUS = Object.assign({}, window.MEDINFOCUS || {}, {
    userId: <?= json_encode($userId) ?>,
  });

  // ------- Elements
  const disciplineSelect   = document.getElementById('discipline-select');
  const partialSelect      = document.getElementById('partial-select');
  const quizSelect         = document.getElementById('quiz-select');
  const startQuizBtn       = document.getElementById('start-quiz-btn');
  const createQuizBtn      = document.getElementById('create-quiz-btn');
  const quizContainer      = document.getElementById('quiz-container');
  const quizSelectionPanel = document.getElementById('quiz-selection-panel');
  const notificationModal  = document.getElementById('notification-modal');
  const openSidebarBtn     = document.getElementById('openSidebarBtn');
  const sidebar            = document.getElementById('sidebar') || document.querySelector('[data-sidebar]');

  // ------- Dados mockados (trocar por fetch() ao integrar backend)
  const mockQuizData = {
    disciplines: [
      { id: 'anatomia',     name: 'Anatomia Humana' },
      { id: 'fisiologia',   name: 'Fisiologia' },
      { id: 'farmacologia', name: 'Farmacologia' },
      { id: 'patologia',    name: 'Patologia' },
    ],
    partials: [
      { id: 'p1', name: 'Primeiro Parcial' },
      { id: 'p2', name: 'Segundo Parcial' },
    ],
    quizzes: [
      { id: '1', title: 'Anatomia Básica',              disciplineId: 'anatomia',   partialId: 'p1' },
      { id: '2', title: 'Fisiologia Cardiovascular',    disciplineId: 'fisiologia', partialId: 'p2' },
      { id: '3', title: 'Farmacologia - Antibióticos',  disciplineId: 'farmacologia', partialId: 'p2' },
      { id: '4', title: 'Patologia - Inflamação',       disciplineId: 'patologia',  partialId: 'p1' },
    ],
  };

  const quizQuestionsData = {
    '1': {
      title: 'Anatomia Humana - Básica',
      questions: [
        { text: 'Qual é o maior osso do corpo humano?', answers: ['Fíbula', 'Fêmur', 'Úmero', 'Tíbia'], correct: 'Fêmur' },
        { text: 'Qual órgão é responsável por bombear o sangue para todo o corpo?', answers: ['Pulmão', 'Fígado', 'Coração', 'Cérebro'], correct: 'Coração' },
        { text: 'Quantos ossos tem o corpo humano adulto?', answers: ['206', '212', '270', '250'], correct: '206' },
      ],
    },
    '2': {
      title: 'Fisiologia do Sistema Cardiovascular',
      questions: [
        { text: 'Qual a principal função das artérias?', answers: ['Levar sangue para o coração', 'Levar sangue do coração para o corpo', 'Trocar gases no pulmão', 'Coagular o sangue'], correct: 'Levar sangue do coração para o corpo' },
        { text: 'O que a sístole representa?', answers: ['Relaxamento do coração', 'Contração do coração', 'Fluxo de sangue nos capilares', 'Batimento cardíaco em repouso'], correct: 'Contração do coração' },
      ],
    },
    '3': {
      title: 'Farmacologia - Antibióticos',
      questions: [
        { text: 'Qual classe de antibióticos age inibindo a síntese da parede celular bacteriana?', answers: ['Macrolídeos', 'Aminoglicosídeos', 'Beta-lactâmicos', 'Tetraciclinas'], correct: 'Beta-lactâmicos' },
      ],
    },
    '4': {
      title: 'Patologia - Inflamação',
      questions: [
        { text: 'Qual é o principal mediador químico da inflamação aguda, responsável pela vasodilatação e aumento da permeabilidade vascular?', answers: ['Interleucina-1', 'Histamina', 'Fator de necrose tumoral', 'Prostaglandinas'], correct: 'Histamina' },
      ],
    },
  };

  // ------- Helpers UI
  function showNotification(message, isError = true) {
    if (!notificationModal) return;
    notificationModal.textContent = message;
    notificationModal.classList.toggle('bg-red-500', !!isError);
    notificationModal.classList.toggle('bg-green-500', !isError);
    notificationModal.classList.remove('hidden', 'opacity-0');
    notificationModal.classList.add('opacity-100', 'modal-enter');
    setTimeout(() => {
      notificationModal.classList.remove('opacity-100');
      notificationModal.classList.add('opacity-0');
      setTimeout(() => notificationModal.classList.add('hidden'), 250);
    }, 2500);
  }

  function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function populateDropdown(selectEl, data, placeholder) {
    selectEl.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    (data || []).forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item.name || item.title;
      selectEl.appendChild(opt);
    });
    selectEl.disabled = false;
  }

  function filterAndPopulateQuizzes() {
    const d = disciplineSelect.value;
    const p = partialSelect.value;
    const placeholder = '-- Selecione o Quiz --';
    if (d && p) {
      const filtered = mockQuizData.quizzes.filter(q => q.disciplineId === d && q.partialId === p);
      populateDropdown(quizSelect, filtered, placeholder);
      startQuizBtn.disabled = true; // só habilita quando um quiz for escolhido
    } else {
      quizSelect.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
      quizSelect.disabled = true;
      startQuizBtn.disabled = true;
    }
  }

  // ------- Renderização do Quiz
  function startQuiz(quizId) {
    quizSelectionPanel.classList.add('hidden');
    renderQuiz(quizId);
  }

  function renderQuiz(quizId) {
    const quizData = quizQuestionsData[quizId];
    if (!quizData) {
      quizContainer.innerHTML = `<div class="p-4 bg-red-100 text-red-800 rounded-md">Quiz não encontrado!</div>`;
      quizContainer.classList.remove('hidden');
      return;
    }

    let html = `
      <div class="bg-white rounded-lg shadow-sm p-6 quiz-card">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">${escapeHTML(quizData.title)}</h2>
        <form id="quiz-form" class="space-y-4">
    `;

    quizData.questions.forEach((question, index) => {
      html += `
        <div class="border border-gray-200 rounded-lg p-4">
          <p class="text-lg font-medium text-gray-900 mb-3">${index + 1}. ${escapeHTML(question.text)}</p>
          <div class="space-y-2">
      `;
      question.answers.forEach(answer => {
        const id = `q${index}_${btoa(unescape(encodeURIComponent(answer))).replace(/=/g,'')}`;
        html += `
          <label for="${id}" class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-gray-50">
            <input id="${id}" type="radio" name="question-${index}" value="${escapeHTML(answer)}" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-700">${escapeHTML(answer)}</span>
          </label>
        `;
      });
      html += `</div></div>`;
    });

    html += `
        <div class="flex justify-between items-center pt-2">
          <button type="submit" class="px-6 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700">Finalizar Quiz</button>
          <button type="button" id="back-to-select" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Voltar</button>
        </div>
      </form>
    </div>
    <div id="quiz-results" class="mt-8 hidden"></div>`;

    quizContainer.innerHTML = html;
    quizContainer.classList.remove('hidden');

    document.getElementById('quiz-form').addEventListener('submit', (e) => {
      e.preventDefault();
      checkAnswers(quizData.questions);
    });

    document.getElementById('back-to-select').addEventListener('click', () => {
      quizSelectionPanel.classList.remove('hidden');
      quizContainer.innerHTML = '';
      quizContainer.classList.add('hidden');
    });
  }

  function checkAnswers(questions) {
    let score = 0;
    const resultsDiv = document.getElementById('quiz-results');
    let resultsHtml = `
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Seu Resultado</h3>
        <div class="space-y-4">
    `;

    questions.forEach((q, i) => {
      const selected = document.querySelector(`input[name="question-${i}"]:checked`);
      const isCorrect = selected && selected.value === q.correct;
      if (isCorrect) score++;

      resultsHtml += isCorrect ? `
        <div class="bg-green-50 p-4 rounded-lg border border-green-200 flex items-center gap-3">
          <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <div>
            <p class="font-medium text-green-800">Pergunta ${i + 1}: Resposta Correta!</p>
            <p class="text-sm text-gray-600">Sua resposta: "${escapeHTML(selected ? selected.value : '')}"</p>
          </div>
        </div>` : `
        <div class="bg-red-50 p-4 rounded-lg border border-red-200 flex items-center gap-3">
          <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          <div>
            <p class="font-medium text-red-800">Pergunta ${i + 1}: Resposta Incorreta.</p>
            <p class="text-sm text-gray-600">Sua resposta: "${escapeHTML(selected ? selected.value : 'Nenhuma resposta')}"</p>
            <p class="text-sm text-red-700">Resposta correta: "${escapeHTML(q.correct)}"</p>
          </div>
        </div>`;
    });

    resultsHtml += `
        </div>
        <div class="mt-8 text-center bg-gray-100 p-6 rounded-lg">
          <p class="text-2xl font-extrabold text-blue-600">Sua Pontuação</p>
          <p class="text-4xl font-extrabold mt-2">${score} / ${questions.length}</p>
        </div>
        <div class="mt-6 flex justify-center">
          <button id="restart-quiz-btn" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-full hover:bg-blue-700">Voltar à Seleção de Quizzes</button>
        </div>
      </div>
    `;

    resultsDiv.innerHTML = resultsHtml;
    resultsDiv.classList.remove('hidden');

    document.getElementById('restart-quiz-btn').addEventListener('click', () => {
      quizSelectionPanel.classList.remove('hidden');
      quizContainer.innerHTML = '';
      quizContainer.classList.add('hidden');
      resultsDiv.classList.add('hidden');
    });

    resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  // ------- Inicialização
  document.addEventListener('DOMContentLoaded', () => {
    populateDropdown(disciplineSelect, mockQuizData.disciplines, '-- Selecione a Disciplina --');
    populateDropdown(partialSelect,    mockQuizData.partials,    '-- Selecione a Parcial --');
  });

  // ------- Eventos
  disciplineSelect.addEventListener('change', () => {
    // Habilita parcial e reseta quiz
    partialSelect.disabled = !disciplineSelect.value;
    filterAndPopulateQuizzes();
  });

  partialSelect.addEventListener('change', filterAndPopulateQuizzes);

  quizSelect.addEventListener('change', () => {
    startQuizBtn.disabled = !quizSelect.value;
  });

  startQuizBtn.addEventListener('click', () => {
    const id = quizSelect.value;
    if (id) startQuiz(id); else showNotification('Por favor, selecione um quiz válido para começar.', true);
  });

  createQuizBtn.addEventListener('click', (e) => {
    e.preventDefault();
    showNotification('Funcionalidade de "Criar Quiz" em desenvolvimento!', false);
  });

  // ------- Sidebar mobile (fallback para #sidebar OU [data-sidebar])
  if (openSidebarBtn && sidebar) {
    openSidebarBtn.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
    });
    document.addEventListener('click', (event) => {
      const isInsideSidebar = sidebar.contains(event.target);
      const isOnBtn = openSidebarBtn.contains(event.target);
      if (!isInsideSidebar && !isOnBtn && !sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.add('-translate-x-full');
      }
    });
  }
})();
</script>

<?php
// 5) Footer global (fecha </body></html>)
require_once __DIR__ . '/includes/footer.php';
