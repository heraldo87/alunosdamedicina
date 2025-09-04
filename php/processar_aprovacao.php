<?php
// php/login_processar.php — usando usuarios(nivel_acesso, aprovacao) sem alterar o banco
require_once __DIR__ . '/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ../login.php?error=empty');
    exit();
}

// Busca exatamente os campos da sua tabela
$sql = "
    SELECT
        id,
        full_name,
        phone,
        ru,
        turma,
        turno,
        email,
        password_hash,
        terms_accepted,
        nivel_acesso,
        aprovacao,
        data_insercao
    FROM usuarios
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Location: ../login.php?error=server');
    exit();
}
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    header('Location: ../login.php?error=invalid_credentials');
    exit();
}

$user = $res->fetch_assoc();

// Valida a senha
if (!password_verify($password, $user['password_hash'] ?? '')) {
    header('Location: ../login.php?error=invalid_credentials');
    exit();
}

// Se NÃO aprovado -> vai para a tela de aguardando
if ((int)$user['aprovacao'] !== 1) {
    // guarda info mínima para mensagem amigável
    $_SESSION['pending_email'] = $user['email'];
    $_SESSION['pending_name']  = $user['full_name'];

    // limpa qualquer sessão autenticada
    unset($_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['email'],
          $_SESSION['access_level'], $_SESSION['nivel_acesso'], $_SESSION['aprovacao']);

    header('Location: ../aguardando_aprovacao.php');
    exit();
}

// Aprovado -> autentica e segue
session_regenerate_id(true);

$_SESSION['user_id']       = (int)$user['id'];
$_SESSION['full_name']     = $user['full_name'];
$_SESSION['email']         = $user['email'];

// Compatibilidade: seu sidebar usa $_SESSION['access_level'].
// Mantemos também $_SESSION['nivel_acesso'] se alguma página antiga usar.
$_SESSION['nivel_acesso']  = (int)$user['nivel_acesso'];
$_SESSION['access_level']  = (int)$user['nivel_acesso']; // <- importantíssimo p/ sidebar

$_SESSION['aprovacao']     = (int)$user['aprovacao'];

// (Opcional) Lembrar-me
if (isset($_POST['remember-me'])) {
    setcookie('user_auth', (string)$user['id'], [
        'expires'  => time() + 60*60*24*30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // habilite se estiver sempre em https
    ]);
}

header('Location: ../index.php');
exit();
