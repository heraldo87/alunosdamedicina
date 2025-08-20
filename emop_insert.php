<?php
// Inicia a sessão para armazenar e passar mensagens entre as páginas
session_start();

// Define as credenciais do banco de dados
$servername = "localhost";
$username = "MedinFocus";
$password = "Her@ldoAlves963#";
$dbname = "medinfocus";

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão e, se falhar, armazena a mensagem de erro e redireciona
if ($conn->connect_error) {
    $_SESSION['message'] = "Erro: A conexão com o banco de dados falhou.";
    $_SESSION['is_error'] = true;
    header("Location: emop.php"); // Redireciona para o seu formulário
    exit();
}

// Verifica se o formulário foi enviado através do método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário
    $email = $_POST['enviar_por_email'];
    $cpf = $_POST['cpf'];
    $id_colaborador = $_POST['id_colaborador'];
    $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    $atividades_realizadas = $_POST['atividades_realizadas'];
    $atividades_previstas = $_POST['atividades_previstas'];
    $pontos_relevantes = $_POST['pontos_relevantes'];
    
    // Prepara a query SQL com 'prepared statements' para evitar injeção de SQL
    $sql = "INSERT INTO acompanhamento_atividades 
            (`enviar_por_email`, `cpf`, `id_colaborador`, `data_inicial`, `data_final`, `atividades_realizadas`, `atividades_previstas`, `pontos_relevantes`, `data_registro`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['message'] = "Erro ao preparar a declaração SQL.";
        $_SESSION['is_error'] = true;
    } else {
        // Binda os parâmetros à query
        // 's' para string, 'i' para integer
        $stmt->bind_param("ssisssss", $email, $cpf, $id_colaborador, $data_inicial, $data_final, $atividades_realizadas, $atividades_previstas, $pontos_relevantes);
        
        // Executa a declaração e verifica o sucesso
        if ($stmt->execute()) {
            $_SESSION['message'] = "Dados inseridos com sucesso!";
            $_SESSION['is_error'] = false;
        } else {
            $_SESSION['message'] = "Erro ao inserir dados: " . $stmt->error;
            $_SESSION['is_error'] = true;
        }
        $stmt->close();
    }
} else {
    $_SESSION['message'] = "Método de requisição inválido.";
    $_SESSION['is_error'] = true;
}

// Fecha a conexão com o banco de dados
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status da Inserção</title>
    <!-- Redireciona para a página do formulário após 5 segundos -->
    <meta http-equiv="refresh" content="5;url=emop.php">
    <!-- Inclui o Tailwind CSS para um design responsivo e moderno -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <!-- Container do aviso -->
    <div class="p-8 rounded-lg shadow-xl w-full max-w-md text-center
        <?php echo $_SESSION['is_error'] ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
        <h2 class="text-2xl font-bold mb-4">
            <?php echo $_SESSION['is_error'] ? 'Ops! Algo deu errado.' : 'Sucesso!'; ?>
        </h2>
        <p class="text-lg mb-4">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </p>
        <p class="text-sm text-gray-600">
    Você será redirecionado em <span id="countdown">5</span> segundos.
        </p>
        <a href="emop.php" class="text-blue-600 hover:underline font-semibold mt-2 block">
            Clique aqui se não for redirecionado.
        </a>
    </div>
    
<script>
    let seconds = 5; // Tempo inicial em segundos
    const countdownElement = document.getElementById('countdown');

    function updateCountdown() {
        countdownElement.textContent = seconds;
        seconds--;

        if (seconds < 0) {
            // Redireciona para a página após a contagem
            window.location.href = "emop.php";
        } else {
            // Chama a função novamente após 1 segundo
            setTimeout(updateCountdown, 1000);
        }
    }

    // Inicia a contagem regressiva
    updateCountdown();
</script>

</body>
</html>
