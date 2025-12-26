<?php
// php/logout.php
session_start();

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Destrói a sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redireciona para o login (ajuste o caminho se necessário)
header("Location: ../login.php");
exit;
?>