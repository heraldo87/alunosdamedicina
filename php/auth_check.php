<?php
// Este script deve ser incluído no topo de todas as páginas restritas do painel.

// Inclui o arquivo de conexão com o banco de dados.
// O caminho é relativo à pasta "php" onde este arquivo deve estar.
include_once 'conn.php';

// Inicia a sessão. É crucial que isso seja a primeira coisa a acontecer.
session_start();

// Verifica se a variável de sessão 'user_id' não está definida.
// Isso significa que o usuário não está logado.
if (!isset($_SESSION['user_id'])) {
    // Se o usuário não está logado, verifica se há um cookie de autenticação.
    if (isset($_COOKIE['user_auth'])) {
        $user_id_from_cookie = $_COOKIE['user_auth'];
        
        // Usa o ID do cookie para buscar o usuário no banco de dados.
        $sql = "SELECT id, full_name, access_level FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // Se a query for preparada com sucesso, executa-a.
        if ($stmt) {
            $stmt->bind_param("i", $user_id_from_cookie);
            $stmt->execute();
            $result = $stmt->get_result();

            // Se um usuário com o ID do cookie for encontrado...
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // ...recria a sessão para o usuário.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['access_level'] = $user['access_level'];
                
            } else {
                // Se o cookie não corresponder a um usuário, ele é inválido.
                // Redireciona para a página de login.
                header("Location: ../login.php");
                exit();
            }
            $stmt->close();
            
        } else {
            // Se houver um erro na preparação da query, redireciona para o login.
            header("Location: ../login.php");
            exit();
        }
    } else {
        // Se não houver sessão nem cookie, redireciona o usuário para a página de login.
        header("Location: ../login.php");
        exit();
    }
}
?>