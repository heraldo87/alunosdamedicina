<?php
/**
 * quizzes.php — Seleciona Disciplina/Parcial/Quiz e REDIRECIONA para form_quiz.php
 * - Usa php/conn.php (PDO $pdo OU MySQLi $conn/$mysqli)
 * - SSR: carrega Disciplinas e Parciais
 * - AJAX interno: ?ajax=quizzes_list (retorna quizzes para os filtros)
 * - Ao clicar em "Começar", faz window.location para form_quiz.php passando parâmetros via GET
 */

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/* ====== CONEXÃO ====== */
require_once __DIR__ . '/php/conn.php';

$DB_MODE = null; // 'pdo' | 'mysqli'
if (isset($pdo) && $pdo instanceof PDO) {
  $DB_MODE = 'pdo';
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} elseif (isset($conn) && $conn instanceof mysqli) {
  $DB_MODE = 'mysqli';
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
  $DB_MODE = 'mysqli';
  $conn = $mysqli;
} else {
  http_response_code(500);
  echo 'Erro de conexão: php/conn.php não expôs $pdo (PDO) nem $conn/$mysqli (MySQLi).';
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ====== DB helpers ====== */
function db_query_all($sql, $params = []) {
  global $DB_MODE, $pdo, $conn;
  if ($DB_MODE === 'pdo') {
    if (!$params) return $pdo->query($sql)->fetchAll();
    $st = $pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
  }
  // mysqli
  if (!$params) { $res = $conn->query($sql); if(!$res) throw new Exception($conn->error); return $res->fetch_all(MYSQLI_ASSOC);} 
  $st = $conn->prepare($sql); if(!$st) throw new Exception($conn->error);
  $types=''; $vals=[]; foreach($params as $p){ $types .= is_int($p)?'i':(is_float($p)?'d':'s'); $vals[]=$p; }
  $st->bind_param($types, ...$vals); if(!$st->execute()) throw new Exception($st->error);
  $res=$st->get_result(); $rows=$res? $res->fetch_all(MYSQLI_ASSOC):[]; $st->close(); return $rows;
}

/* ===================== ENDPOINT AJAX ===================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'quizzes_list') {
  header('Content-Type: application/json; charset=utf-8');
  $id_disc = (int)($_GET['id_disciplina'] ?? 0);
  $id_par  = (int)($_GET['id_parcial'] ?? 0);
  if ($id_disc <= 0 || $id_par <= 0) { http_response_code(400); echo json_encode(['error'=>'Parâmetros inválidos']); exit; }
  try {
    // ajuste os nomes de colunas conforme seu schema real
    $rows = db_query_all(
      "SELECT id_quiz, titulo
         FROM quizzes
        WHERE id_disciplina = ?
          AND id_parcial    = ?
        ORDER BY COALESCE(data_criacao, '1970-01-01') DESC, id_quiz DESC",
      [$id_disc, $id_par]
    );
    echo json_encode(array_map(fn($r)=>['id_quiz'=>(int)$r['id_quiz'],'titulo'=>(string)($r['titulo']??'')], $rows));
  } catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error'=>'Erro ao consultar quizzes']);
  }
  exit;
}

/* ===================== SSR: DISCIPLINAS/PARCIAIS ===================== */
try {
  $disciplinas = db_query_all('SELECT id_disciplina, nome FROM disciplinas ORDER BY nome ASC');
  $parciais    = db_query_all('SELECT id_parcial, nome FROM parciais ORDER BY id_parcial ASC');
} catch(Throwable $e) { $disciplinas = $parciais = []; }

/* ===================== LAYOUT (partials) ===================== */
require_once __DIR__ . '/includes/header.php';
if (file_exists(__DIR__ . '/includes/topbar.php')) require_once __DIR__ . '/includes/topbar.php';
require_once __DIR__ . '/includes/sidebar_nav.php';
?>

<main class="flex-1 min-h-0 bg-gray-50">
  <div class="max-w-5xl mx-auto p-4">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
      <h2 class="text-2xl font-bold text-gray-800 mb-1">Escolha um Quiz</h2>
      <p class="text-gray-500 mb-5">Selecione Disciplina e Parcial para listar os quizzes disponíveis. Ao prosseguir, você será redirecionado para <code>form_quiz.php</code>.</p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label for="discipline" class="block text-sm text-gray-600 mb-1">Disciplina</label>
          <select id="discipline" class="w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Selecione a Disciplina --</option>
            <?php foreach($disciplinas as $d): ?>
              <option value="<?= (int)$d['id_disciplina'] ?>"><?= h($d['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="partial" class="block text-sm text-gray-600 mb-1">Parcial</label>
          <select id="partial" class="w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Selecione a Parcial --</option>
            <?php foreach($parciais as $p): ?>
              <option value="<?= (int)$p['id_parcial'] ?>"><?= h($p['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="quiz" class="block text-sm text-gray-600 mb-1">Quiz</label>
          <select id="quiz" class="w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" disabled>
            <option value="">-- Selecione o Quiz --</option>
          </select>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button id="btnGo" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-semibold disabled:opacity-50" disabled>Ir para form_quiz.php</button>
        <span class="text-sm text-gray-500">Dica: preencha os 3 campos acima</span>
      </div>
    </div>
  </div>
</main>

<script>
  const selDisc  = document.getElementById('discipline');
  const selPar   = document.getElementById('partial');
  const selQuiz  = document.getElementById('quiz');
  const btnGo    = document.getElementById('btnGo');

  async function loadQuizzes(){
    selQuiz.innerHTML = '<option value="">Carregando...</option>';
    selQuiz.disabled = true; btnGo.disabled = true;
    const d = selDisc.value, p = selPar.value;
    if(!d || !p){ selQuiz.innerHTML = '<option value="">-- Selecione o Quiz --</option>'; return; }
    try {
      const r = await fetch(`?ajax=quizzes_list&id_disciplina=${encodeURIComponent(d)}&id_parcial=${encodeURIComponent(p)}`);
      const data = await r.json();
      selQuiz.innerHTML = '<option value="">-- Selecione o Quiz --</option>';
      if(Array.isArray(data) && data.length){
        data.forEach(q=>{
          const o = document.createElement('option');
          o.value = q.id_quiz; o.textContent = q.titulo; selQuiz.appendChild(o);
        });
        selQuiz.disabled = false;
      } else {
        const o = document.createElement('option'); o.value=''; o.textContent='Nenhum quiz encontrado'; selQuiz.appendChild(o);
      }
    } catch(e){ selQuiz.innerHTML = '<option value="">Erro ao carregar</option>'; }
  }

  selDisc.addEventListener('change', loadQuizzes);
  selPar.addEventListener('change', loadQuizzes);
  selQuiz.addEventListener('change', ()=>{ btnGo.disabled = !selQuiz.value; });

  // Redireciona para form_quiz.php com os parâmetros selecionados
  btnGo.addEventListener('click', ()=>{
    const id_disc = selDisc.value; const id_par = selPar.value; const id_quiz = selQuiz.value;
    if(!id_disc || !id_par || !id_quiz) return;
    const url = new URL('form_quiz.php', window.location.origin);
    url.searchParams.set('id_disciplina', id_disc);
    url.searchParams.set('id_parcial', id_par);
    url.searchParams.set('id_quiz', id_quiz);
    window.location.href = url.toString();
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
