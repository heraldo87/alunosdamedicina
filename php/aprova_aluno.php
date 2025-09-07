<?php
declare(strict_types=1);

/**
 * php/aprova_aluno.php — Endpoint para aprovar alunos selecionados
 * - Requer sessão e auth_check.php
 * - Verifica nível (2/3), CSRF, ids[]
 * - Nível 2: restringe por turma/turno
 * - Usa transação e bind dinâmico
 * - Define mensagens flash e redireciona de volta para /aprovacoes.php?p=...
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conn.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* Helper */
function back_to_list(int $p = 1): void {
    header('Location: /aprovacoes.php?p=' . max(1, $p));
    exit();
}
function set_flash(string $key, string $msg): void {
    $_SESSION[$key] = $msg;
}
function bind_params_dynamic(mysqli_stmt $stmt, string $types, array $values): void {
    $refs = [];
    foreach ($values as $k => $v) { $refs[$k] = &$values[$k]; }
    $stmt->bind_param($types, ...$refs);
}

/* Valida nível */
$userId = (int)($_SESSION['user_id'] ?? 0);
$nivel  = (int)($_SESSION['access_level'] ?? $_SESSION['nivel_acesso'] ?? 1);
if ($userId <= 0 || !in_array($nivel, [2,3], true)) {
    set_flash('flash_error', 'Acesso negado.');
    back_to_list((int)($_GET['p'] ?? 1));
}

/* Verifica método e CSRF */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'aprovar') {
    set_flash('flash_error', 'Requisição inválida.');
    back_to_list((int)($_GET['p'] ?? 1));
}
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    set_flash('flash_error', 'Falha de segurança (CSRF). Atualize a página e tente novamente.');
    back_to_list((int)($_GET['p'] ?? 1));
}

/* IDs */
$ids = $_POST['ids'] ?? [];
if (!is_array($ids) || empty($ids)) {
    set_flash('flash_error', 'Nenhum aluno selecionado.');
    back_to_list((int)($_GET['p'] ?? 1));
}
$ids = array_values(array_unique(array_map('intval', $ids)));
$ids = array_filter($ids, fn($x) => $x > 0);
if (empty($ids)) {
    set_flash('flash_error', 'Nenhum aluno válido selecionado.');
    back_to_list((int)($_GET['p'] ?? 1));
}

/* Se nível 2, garantir turma/turno */
$myTurma = $_SESSION['turma'] ?? null;
$myTurno = $_SESSION['turno'] ?? null;

if ($nivel === 2 && (empty($myTurma) || empty($myTurno))) {
    $stmtTU = $conn->prepare("SELECT turma, turno FROM usuarios WHERE id = ? LIMIT 1");
    $stmtTU->bind_param('i', $userId);
    $stmtTU->execute();
    $rTU = $stmtTU->get_result();
    if ($rTU && $rTU->num_rows === 1) {
        $row = $rTU->fetch_assoc();
        $myTurma = $row['turma'] ?? null;
        $myTurno = $row['turno'] ?? null;
        $_SESSION['turma'] = $myTurma;
        $_SESSION['turno'] = $myTurno;
    }
    $stmtTU->close();
    if (empty($myTurma) || empty($myTurno)) {
        set_flash('flash_error', 'Seu perfil não possui turma/turno definidos. Solicite ao administrador.');
        back_to_list((int)($_GET['p'] ?? 1));
    }
}

/* Monta UPDATE dinâmico */
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql   = "UPDATE usuarios SET aprovacao = 1 WHERE aprovacao = 0 AND id IN ($placeholders)";
$types = str_repeat('i', count($ids));
$params = $ids;

if ($nivel === 2) {
    $sql   .= " AND turma = ? AND turno = ?";
    $types .= 'ss';
    $params[] = $myTurma;
    $params[] = $myTurno;
}

/* Executa com transação */
$p = (int)($_GET['p'] ?? 1);
$txStarted = false;

try {
    $conn->begin_transaction();
    $txStarted = true;

    $stmt = $conn->prepare($sql);
    bind_params_dynamic($stmt, $types, $params);
    $stmt->execute();
    $ok = $stmt->affected_rows;
    $stmt->close();

    $conn->commit();
    $txStarted = false;

    if ($ok > 0) {
        set_flash('flash_success', "Aprovação concluída para {$ok} aluno(s).");
    } else {
        set_flash('flash_success', 'Nenhum registro alterado (já aprovados, fora do seu escopo ou inexistentes).');
    }
} catch (Throwable $e) {
    if ($txStarted) $conn->rollback();
    set_flash('flash_error', 'Erro ao aprovar: ' . $e->getMessage());
}

back_to_list($p);
