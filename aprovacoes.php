<?php
// ==============================
// aprovacoes.php (com restrição por turma/turno p/ nível 2)
// ==============================

require_once __DIR__ . '/php/auth_check.php';

// Nível do usuário logado
$nivel = (int)($_SESSION['access_level'] ?? $_SESSION['nivel_acesso'] ?? 1);
if (!in_array($nivel, [2, 3], true)) {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/php/conn.php';

// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ==== Obtém turma/turno do moderador (nível 2) ====
$myTurma = $_SESSION['turma'] ?? null;
$myTurno = $_SESSION['turno'] ?? null;
if ($nivel === 2 && (empty($myTurma) || empty($myTurno))) {
    $stmtTU = $conn->prepare("SELECT turma, turno FROM usuarios WHERE id = ? LIMIT 1");
    $stmtTU->bind_param('i', $_SESSION['user_id']);
    $stmtTU->execute();
    $rTU = $stmtTU->get_result();
    if ($rTU && $rTU->num_rows === 1) {
        $row = $rTU->fetch_assoc();
        $myTurma = $row['turma'] ?? null;
        $myTurno = $row['turno'] ?? null;
        // cache em sessão
        $_SESSION['turma'] = $myTurma;
        $_SESSION['turno'] = $myTurno;
    }
    $stmtTU->close();
}

// ==== Processa POST (aprovar selecionados) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'aprovar') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Falha de segurança (CSRF). Tente novamente.';
        header('Location: aprovacoes.php');
        exit();
    }

    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || count($ids) === 0) {
        $_SESSION['flash_error'] = 'Nenhum aluno selecionado.';
        header('Location: aprovacoes.php');
        exit();
    }

    $ids = array_map('intval', $ids);
    $ids = array_values(array_filter($ids, fn($x)=>$x>0));

    if (count($ids) === 0) {
        $_SESSION['flash_error'] = 'Nenhum aluno válido selecionado.';
        header('Location: aprovacoes.php');
        exit();
    }

    // Monta UPDATE com restrição condicional
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE usuarios SET aprovacao = 1 WHERE aprovacao = 0 AND id IN ($placeholders)";
    $types = str_repeat('i', count($ids));
    $params = $ids;

    if ($nivel === 2) {
        // Segurança extra: limita à turma/turno do moderador
        if (empty($myTurma) || empty($myTurno)) {
            $_SESSION['flash_error'] = 'Seu perfil não possui turma/turno definidos. Peça ao admin para atualizar seu cadastro.';
            header('Location: aprovacoes.php');
            exit();
        }
        $sql .= " AND turma = ? AND turno = ?";
        $types .= 'ss';
        $params[] = $myTurma;
        $params[] = $myTurno;
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $ok = $stmt->affected_rows;
        $stmt->close();
        $_SESSION['flash_success'] = $ok > 0
            ? "Aprovação concluída para {$ok} aluno(s)."
            : "Nenhum registro alterado (já aprovados, fora do seu escopo ou inexistentes).";
    } else {
        $_SESSION['flash_error'] = 'Erro no servidor ao aprovar.';
    }

    header('Location: aprovacoes.php?p='.(int)($_GET['p'] ?? 1));
    exit();
}

// ==== Paginação e filtros ====
$por_pagina = 20;
$pagina_atual = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pagina_atual - 1) * $por_pagina;

// COUNT
if ($nivel === 3) {
    $stmtC = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE aprovacao = 0");
} else {
    if (empty($myTurma) || empty($myTurno)) {
        $total = 0; // Sem turma/turno, nada a listar
        $pendentes = [];
        $paginas = 1;
        goto RENDER;
    }
    $stmtC = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE aprovacao = 0 AND turma = ? AND turno = ?");
    $stmtC->bind_param('ss', $myTurma, $myTurno);
}
$stmtC->execute();
$rC = $stmtC->get_result();
$total = ($rC && ($row=$rC->fetch_assoc())) ? (int)$row['total'] : 0;
$stmtC->close();

$paginas = max(1, (int)ceil($total / $por_pagina));

// LISTA
if ($nivel === 3) {
    $stmtL = $conn->prepare("
        SELECT id, full_name, email, ru, turma, turno, data_insercao
        FROM usuarios
        WHERE aprovacao = 0
        ORDER BY data_insercao DESC, id DESC
        LIMIT ? OFFSET ?
    ");
    $stmtL->bind_param('ii', $por_pagina, $offset);
} else {
    $stmtL = $conn->prepare("
        SELECT id, full_name, email, ru, turma, turno, data_insercao
        FROM usuarios
        WHERE aprovacao = 0 AND turma = ? AND turno = ?
        ORDER BY data_insercao DESC, id DESC
        LIMIT ? OFFSET ?
    ");
    $stmtL->bind_param('ssii', $myTurma, $myTurno, $por_pagina, $offset);
}
$stmtL->execute();
$rL = $stmtL->get_result();
$pendentes = $rL ? $rL->fetch_all(MYSQLI_ASSOC) : [];
$stmtL->close();

RENDER:
$pageTitle = "Aprovações - MedinFocus";
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/includes/sidebar_nav.php';

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<div class="flex-1 flex flex-col">
    <header class="bg-white shadow-md p-4 flex items-center justify-between">
        <button id="openSidebarBtn" class="text-gray-500 hover:text-gray-700 lg:hidden">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-xl font-bold text-gray-800">Aprovações</span>
        <div class="text-sm text-gray-500">
            Pendentes: <?= number_format($total, 0, ',', '.') ?>
            <?php if ($nivel === 2): ?>
                <span class="ml-2 text-gray-400">| Filtro: Turma <?= h($myTurma ?? '-') ?> • Turno <?= h($myTurno ?? '-') ?></span>
            <?php endif; ?>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto">
        <div class="p-6 bg-white rounded-xl shadow-lg mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Gerenciar Aprovações</h1>
            <p class="text-gray-600">Selecione os alunos pendentes e confirme a aprovação. 🩺</p>

            <?php if ($flash_success): ?>
                <div class="mt-4 p-3 rounded-lg bg-green-50 text-green-700 border border-green-200 flex items-center gap-2">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span><?= h($flash_success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flash_error): ?>
                <div class="mt-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 flex items-center gap-2">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span><?= h($flash_error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($nivel === 2 && (empty($myTurma) || empty($myTurno))): ?>
                <div class="mt-4 p-3 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 flex items-center gap-2">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.511l-4.5 9A1.75 1.75 0 005.257 15h9.486a1.75 1.75 0 001.5-2.489l-4.5-9a1.75 1.75 0 00-2.986 0zM10 12a1 1 0 100-2 1 1 0 000 2zm-1 4a1 1 0 102 0 1 1 0 00-2 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Seu perfil de moderador não possui <strong>turma/turno</strong> definidos. Peça ao administrador para atualizar seu cadastro.</span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-xl shadow-md">
            <form method="POST" class="p-4 md:p-6">
                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="aprovar">

                <?php if ($total === 0): ?>
                    <div class="text-center py-12">
                        <div class="text-5xl mb-3">🎉</div>
                        <p class="text-gray-700 text-lg">Não há alunos pendentes de aprovação<?= $nivel===2 ? ' na sua turma/turno.' : '.' ?></p>
                        <p class="text-gray-500 mt-2 text-sm">Tudo certo por aqui!</p>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-4 gap-4">
                        <div class="flex items-center gap-3">
                            <input id="checkAll" type="checkbox" class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <label for="checkAll" class="text-sm text-gray-700">Selecionar todos desta página</label>
                        </div>
                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md transition-all duration-200 flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Aprovar selecionados</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto -mx-4 md:mx-0">
                        <table class="min-w-full text-sm border-collapse rounded-lg border border-gray-200">
                            <thead class="bg-blue-50 text-blue-800 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-left rounded-tl-lg"><span class="sr-only">Selecionar</span></th>
                                    <th class="px-4 py-3 text-left">Nome</th>
                                    <th class="px-4 py-3 text-left">E-mail</th>
                                    <th class="px-4 py-3 text-left">RU</th>
                                    <th class="px-4 py-3 text-left">Turma</th>
                                    <th class="px-4 py-3 text-left">Turno</th>
                                    <th class="px-4 py-3 text-left rounded-tr-lg">Solicitado em</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($pendentes as $index => $u): ?>
                                    <tr class="transition-colors duration-200 hover:bg-blue-50 <?= $index % 2 === 1 ? 'bg-blue-100' : '' ?>">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="ids[]" value="<?= (int)$u['id'] ?>"
                                                   class="rowCheck h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900"><?= h($u['full_name']) ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?= h($u['email']) ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?= h($u['ru']) ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?= h($u['turma']) ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?= h($u['turno']) ?></td>
                                        <td class="px-4 py-3 text-gray-600">
                                            <?= h(date('d/m/Y H:i', strtotime($u['data_insercao'] ?? 'now'))) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between mt-6">
                        <div class="text-sm text-gray-600 mb-2 sm:mb-0">
                            Página <?= $pagina_atual ?> de <?= $paginas ?> (Total: <?= number_format($total, 0, ',', '.') ?> alunos)
                        </div>
                        <div class="flex gap-2">
                            <?php $prev = max(1, $pagina_atual - 1); $next = min($paginas, $pagina_atual + 1); ?>
                            <a href="?p=<?= $prev ?>" class="px-4 py-2 rounded-lg border text-gray-700 bg-white hover:bg-gray-100 transition-colors duration-200 <?= $pagina_atual <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                                Anterior
                            </a>
                            <a href="?p=<?= $next ?>" class="px-4 py-2 rounded-lg border text-gray-700 bg-white hover:bg-gray-100 transition-colors duration-200 <?= $pagina_atual >= $paginas ? 'opacity-50 pointer-events-none' : '' ?>">
                                Próxima
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAll');
        const rows = document.querySelectorAll('.rowCheck');
        if (checkAll) {
            checkAll.addEventListener('change', () => {
                rows.forEach(cb => cb.checked = checkAll.checked);
            });
        }
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>