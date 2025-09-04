<?php
// ==============================
// noticias_criar.php (versão enxuta, tema do index)
// Requer login; somente níveis 2 (moderador) e 3 (admin)
// ==============================

require_once __DIR__ . '/php/auth_check.php';

// Permissão: apenas moderador(2) ou admin(3)
$nivel = (int)($_SESSION['access_level'] ?? $_SESSION['nivel_acesso'] ?? 1);
if (!in_array($nivel, [2, 3], true)) {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/php/conn.php';

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function slugify($str) {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    $s = preg_replace('/[^a-zA-Z0-9\s-]/', '', $s);
    $s = strtolower(trim($s));
    $s = preg_replace('/[\s-]+/', '-', $s);
    return $s ?: ('noticia-'.time());
}

function parseDateTimeLocal($dt) {
    if (!$dt) return null;
    $ts = strtotime($dt);
    if ($ts === false) return null;
    return date('Y-m-d H:i:s', $ts);
}

// Flash
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'criar') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Falha de segurança (CSRF). Tente novamente.';
        header('Location: noticias_criar.php');
        exit();
    }

    $titulo    = trim($_POST['titulo'] ?? '');
    $resumo    = trim($_POST['resumo'] ?? '');
    $conteudo  = trim($_POST['conteudo'] ?? '');
    $dt_publi  = parseDateTimeLocal($_POST['data_publicacao'] ?? '');
    $autor_id  = (int)($_SESSION['user_id'] ?? 0);
    $slug      = slugify($titulo);

    $erros = [];
    if ($titulo === '')   $erros[] = 'Informe o título.';
    if ($conteudo === '') $erros[] = 'Informe a notícia (conteúdo).';

    // Se sem data, usa agora
    if (!$dt_publi) {
        $dt_publi = date('Y-m-d H:i:s');
    }

    if (count($erros) > 0) {
        $_SESSION['flash_error'] = implode(' ', $erros);
        $_SESSION['form_old'] = [
            'titulo'=>$titulo,'resumo'=>$resumo,'conteudo'=>$conteudo,
            'data_publicacao'=>($_POST['data_publicacao'] ?? '')
        ];
        header('Location: noticias_criar.php');
        exit();
    }

    // Ajuste os nomes das colunas conforme sua tabela `noticias`
    // Exemplo mínimo: titulo, resumo, conteudo, data_publicacao, autor_id, slug, created_at
    $sql = "INSERT INTO noticias
            (titulo, resumo, conteudo, data_publicacao, autor_id, slug, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['flash_error'] = 'Erro no servidor ao preparar a inserção.';
        header('Location: noticias_criar.php'); exit();
    }
    $stmt->bind_param(
        'ssssss',
        $titulo, $resumo, $conteudo, $dt_publi, $autor_id, $slug
    );
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = 'Notícia criada com sucesso.';
        unset($_SESSION['form_old']);
        header('Location: noticias_criar.php'); exit();
    } else {
        $_SESSION['flash_error'] = 'Falha ao salvar a notícia no banco.';
        header('Location: noticias_criar.php'); exit();
    }
}

// ======= RENDER =======
$pageTitle = "Criar Notícias - MedinFocus";
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/includes/sidebar_nav.php';

$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

$oldTitulo   = $old['titulo'] ?? '';
$oldResumo   = $old['resumo'] ?? '';
$oldConteudo = $old['conteudo'] ?? '';
$oldDataPub  = $old['data_publicacao'] ?? '';
?>

<div class="flex-1 flex flex-col">
  <!-- Header padrão do layout (branco, igual ao index) -->
  <header class="bg-white shadow-md p-4 flex items-center justify-between">
    <button id="openSidebarBtn" class="text-gray-500 hover:text-gray-700 lg:hidden">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
    <span class="text-xl font-bold text-gray-800">Criar Notícia</span>
  </header>

  <main class="flex-1 p-4 md:p-8 overflow-y-auto bg-gray-50">
    <!-- Toasts -->
    <?php if ($flash_success): ?>
      <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 shadow-sm">
        ✅ <?= h($flash_success) ?>
      </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
      <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 shadow-sm">
        ⚠️ <?= h($flash_error) ?>
      </div>
    <?php endif; ?>

    <form class="grid grid-cols-1 lg:grid-cols-3 gap-6" method="POST" enctype="multipart/form-data" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="criar">

      <!-- Coluna principal -->
      <section class="lg:col-span-2 space-y-6">
        <!-- Card: Título + Resumo -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200 rounded-t-2xl">
            <div class="flex items-center gap-2">
              <span class="inline-block w-1.5 h-5 rounded bg-gray-900"></span>
              <h2 class="font-semibold text-gray-900 tracking-tight">Informações principais</h2>
            </div>
          </div>
          <div class="p-6 space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-800 mb-1">Título *</label>
              <input id="titulo" type="text" name="titulo" required
                     value="<?= h($oldTitulo) ?>"
                     class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
              <div class="mt-2 inline-flex items-center gap-2 text-xs">
                <span class="px-2 py-0.5 rounded-full bg-gray-900 text-white">Slug</span>
                <span id="slugPreview" class="text-gray-700">—</span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-800 mb-1">Resumo (máx. 200)</label>
              <div class="relative">
                <input id="resumo" type="text" name="resumo" maxlength="200"
                       value="<?= h($oldResumo) ?>"
                       class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900 pr-16">
                <span id="resumoCount" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500">0/200</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">Opcional. Exibido em listagens e cartões.</p>
            </div>
          </div>
        </div>

        <!-- Card: Conteúdo -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200 rounded-t-2xl">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="inline-block w-1.5 h-5 rounded bg-gray-900"></span>
                <h2 class="font-semibold text-gray-900 tracking-tight">Notícia *</h2>
              </div>
              <button type="button" id="btnPreview"
                      class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50">
                Pré-visualizar
              </button>
            </div>
          </div>
          <div class="p-6 space-y-4">
            <textarea id="conteudo" name="conteudo" rows="12" required
                      class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"><?= h($oldConteudo) ?></textarea>
            <div id="conteudoPreview" class="hidden p-4 bg-gray-50 rounded-xl border text-sm text-gray-800"></div>
            <p class="text-xs text-gray-500">Texto simples. Linhas em branco são respeitadas na pré-visualização.</p>
          </div>
        </div>
      </section>

      <!-- Coluna lateral (configurações mínimas) -->
      <aside class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200 rounded-t-2xl">
            <div class="flex items-center gap-2">
              <span class="inline-block w-1.5 h-5 rounded bg-gray-900"></span>
              <h2 class="font-semibold text-gray-900 tracking-tight">Publicação</h2>
            </div>
          </div>
          <div class="p-6 space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-800 mb-1">Data de publicação</label>
              <input type="datetime-local" name="data_publicacao"
                     value="<?= h($oldDataPub) ?>"
                     class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
              <p class="mt-1 text-xs text-gray-500">Se vazio, será usada a data/hora atual.</p>
            </div>

            <div class="pt-2 flex flex-col gap-3">
              <button type="submit"
                      class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white bg-gray-900 hover:bg-black shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M5 20h14v-2H5v2zm7-18L3.5 9h5v6h6V9h5L12 2z"/></svg>
                Salvar notícia
              </button>
              <a href="noticias_listar.php"
                 class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 text-gray-800 hover:bg-gray-50">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h18v-2H3v2z"/></svg>
                Ver listagem
              </a>
            </div>
          </div>
        </div>
      </aside>
    </form>
  </main>
</div>

<script>
// Slug preview
const titulo = document.getElementById('titulo');
const slugPreview = document.getElementById('slugPreview');
function makeSlug(str){
  return (str || '')
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/[^a-zA-Z0-9\s-]/g,'')
    .trim().toLowerCase()
    .replace(/[\s-]+/g,'-') || 'noticia';
}
function updateSlug(){ if(slugPreview) slugPreview.textContent = makeSlug(titulo.value); }
if (titulo){ titulo.addEventListener('input', updateSlug); updateSlug(); }

// Resumo counter
const resumo = document.getElementById('resumo');
const resumoCount = document.getElementById('resumoCount');
function updateResumoCount(){
  if (!resumo || !resumoCount) return;
  const len = resumo.value.length;
  resumoCount.textContent = `${len}/200`;
}
if (resumo){ resumo.addEventListener('input', updateResumoCount); updateResumoCount(); }

// Pré-visualização do conteúdo (quebra de linha -> <br>)
const btnPreview = document.getElementById('btnPreview');
const conteudo = document.getElementById('conteudo');
const conteudoPreview = document.getElementById('conteudoPreview');
if (btnPreview && conteudo && conteudoPreview){
  btnPreview.addEventListener('click', ()=>{
    const safe = (conteudo.value || '')
      .replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))
      .replace(/\n/g,'<br>');
    conteudoPreview.innerHTML = safe || '<em class="text-gray-500">Sem conteúdo</em>';
    conteudoPreview.classList.toggle('hidden');
  });
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
