<?php
// Inclui o arquivo de conexão com o banco de dados
include 'conn.php';

// Inicia a sessão
session_start();

// Verifica se os dados do formulário foram enviados
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Coleta e sanitiza os dados do formulário
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validação básica
    if ($email === '' || $password === '') {
        header("Location: ../login.php?error=empty");
        exit();
    }

    // Validação de formato do e-mail (opcional, mas recomendado)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../login.php?error=invalid_email");
        exit();
    }

    // Consulta SQL para buscar o usuário pelo email
    // Usamos alias para manter "access_level" como no seu código original
    $sql = "SELECT id, full_name, password_hash, nivel_acesso AS access_level
            FROM usuarios
            WHERE email = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Evita vazar detalhes técnicos para o usuário
        error_log("Erro na preparação da query de login: " . $conn->error);
        header("Location: ../login.php?error=server");
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verifica se encontrou o usuário
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifica a senha
        if (password_verify($password, $user['password_hash'])) {
            // Regenera o ID de sessão para prevenir fixation
            session_regenerate_id(true);

            // Cria variáveis de sessão
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['full_name'];
            $_SESSION['access_level'] = $user['access_level']; // Mantido como no seu código

            // Lembrar-me (opcional)
            if (isset($_POST['remember-me'])) {
                $cookie_name  = "user_auth";
                $cookie_value = (string)$user['id'];
                $expires      = time() + (86400 * 30); // 30 dias

                // Define flags seguras quando possível
                $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                $httponly = true;
                $path     = "/";

                // PHP 7.3+ suporta array de opções
                setcookie($cookie_name, $cookie_value, [
                    'expires'  => $expires,
                    'path'     => $path,
                    'secure'   => $secure,
                    'httponly' => $httponly,
                    'samesite' => 'Lax'
                ]);
            }

            // Redireciona para o painel
            header("Location: ../index.php");
            exit();
        } else {
            // Senha incorreta
            header("Location: ../login.php?error=invalid_credentials");
            exit();
        }
    } else {
        // E-mail não encontrado
        header("Location: ../login.php?error=invalid_credentials");
        exit();
    }

    // Fecha statement
    $stmt->close();
}

// Fecha a conexão
$conn->close();
