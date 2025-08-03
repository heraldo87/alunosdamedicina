<?php
// Inclui o arquivo de conexão
include 'conn.php';

// Verifica se os dados do formulário foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta os dados do formulário e sanitiza
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $ru = $conn->real_escape_string(trim($_POST['ru']));
    $turma = $conn->real_escape_string(trim($_POST['turma']));
    $turno = $conn->real_escape_string(trim($_POST['turno']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];

    // Validação básica no servidor
    if (empty($full_name) || empty($phone) || empty($ru) || empty($turma) || empty($turno) || empty($email) || empty($password)) {
        die("Todos os campos são obrigatórios.");
    }
    
    // Criptografa a senha antes de salvar
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Usa prepared statements para prevenir SQL Injection
    $sql = "INSERT INTO usuarios (full_name, phone, ru, turma, turno, email, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Erro na preparação da query: " . $conn->error);
    }

    // Associa os parâmetros e executa a query
    $stmt->bind_param("sssssss", $full_name, $phone, $ru, $turma, $turno, $email, $password_hash);

    if ($stmt->execute()) {
        // Redireciona o usuário para uma página de sucesso ou login
        header("Location: ../cadastro_sucesso.php");
        exit();
    } else {
        echo "Erro ao registrar o usuário: " . $stmt->error;
    }

    // Fecha o statement
    $stmt->close();
}

// Fecha a conexão no final do script
$conn->close();
?>
