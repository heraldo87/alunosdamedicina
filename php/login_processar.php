<?php
// Inclui o arquivo de conexão com o banco de dados
include 'conn.php';

// Inicia a sessão
session_start();

// Verifica se os dados do formulário foram enviados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];

    // Validação básica
    if (empty($email) || empty($password)) {
        // Redireciona de volta para a página de login com uma mensagem de erro
        header("Location: ../login.html?error=empty");
        exit();
    }

    // Consulta SQL para buscar o usuário pelo email
    $sql = "SELECT id, full_name, password_hash FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erro na preparação da query: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Verifica a senha
        if (password_verify($password, $user['password_hash'])) {
            // A senha está correta, cria a sessão e o cookie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            // Se o usuário marcou "Lembrar-me", cria o cookie
            if (isset($_POST['remember-me'])) {
                $cookie_name = "user_auth";
                $cookie_value = $user['id'];
                // O cookie dura 30 dias
                setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); 
            }

            // Redireciona para a página principal (painel)
            header("Location: ../index.php");
            exit();
        } else {
            // Senha incorreta
            header("Location: ../login.php?error=invalid_credentials");
            exit();
        }
    } else {
        // Email não encontrado
        header("Location: ../login.php?error=invalid_credentials");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
