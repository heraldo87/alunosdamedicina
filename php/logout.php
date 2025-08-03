<?php
// Este script deve ser salvo em php/logout.php.

// Inicia a sessão para poder acessá-la e destruí-la.
session_start();

// Destrói todas as variáveis de sessão.
$_SESSION = array();

// Se o cookie de sessão for usado, destrói-o também.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão.
session_destroy();

// Se houver um cookie de autenticação, remove-o também.
if (isset($_COOKIE['user_auth'])) {
    setcookie('user_auth', '', time() - 3600, "/");
}

// Redireciona o usuário para a página de login.
header("Location: ../login.php");
exit();
?>
