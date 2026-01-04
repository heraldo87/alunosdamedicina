<?php
/**
 * MEDINFOCUS - Script de Encerramento de Sessão Seguro
 * Localização: php/logout.php
 */

session_start();

// 1. Limpa todas as variáveis de sessão na memória do servidor
$_SESSION = array();

// 2. Destrói o cookie de sessão no navegador do usuário
// Isso garante que o identificador da sessão antiga não seja reutilizado
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destrói a sessão no servidor
session_destroy();

// 4. Redireciona para a tela de login
// Como o script está na pasta /php, usamos ../ para voltar à raiz
header("Location: ../login.php");
exit;
?>