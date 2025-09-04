<?php
session_start();
$servername = "localhost";
$username   = "MedinFocus";
$password   = "Her@ldoAlves963#";
$dbname     = "medinfocus";

// Conecta ao banco
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Garante que só números sejam salvos
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);

    $sql = "SELECT * FROM colaboradores WHERE cpf = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $colaborador = $result->fetch_assoc();

        $_SESSION['cpf']       = $colaborador['cpf'];
        $_SESSION['nome']      = $colaborador['nome'];
        $_SESSION['diretoria'] = $colaborador['diretoria'];

        header("Location: emop.php");
        exit;
    } else {
        $msg = "⚠️ CPF não encontrado na base.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login - Acompanhamento de Atividades</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white p-8 rounded-lg w-full max-w-md">
        <div class="flex justify-center mb-6">
            <img src="logo-cohidro-emop-cabecalho-form.jpg" alt="COHIDRO / EMOP" class="h-20">
        </div>
  <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
    <h2 class="text-2xl font-bold text-center mb-6">Acompanhamento de Atividades</h2>

    <?php if ($msg): ?>
      <div class="mb-4 p-3 text-sm text-red-700 bg-red-100 rounded-lg">
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <label for="cpf" class="block text-gray-700 font-semibold mb-2">Digite seu CPF:</label>
      <input type="text" 
       name="cpf" 
       id="cpf" 
       maxlength="14" 
       placeholder="000.000.000-00"
       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-5" 
       required
       oninput="mascaraCPF(this)">
      
      <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700">
        Entrar
      </button>
    </form>
  </div>
</div>
<script>
function mascaraCPF(input) {
    let value = input.value.replace(/\D/g, ""); // só números
    if (value.length > 11) value = value.slice(0, 11); // limita em 11 dígitos

    // Aplica a máscara
    if (value.length > 9) {
        input.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, "$1.$2.$3-$4");
    } else if (value.length > 6) {
        input.value = value.replace(/(\d{3})(\d{3})(\d{0,3})/, "$1.$2.$3");
    } else if (value.length > 3) {
        input.value = value.replace(/(\d{3})(\d{0,3})/, "$1.$2");
    } else {
        input.value = value;
    }
}
</script>

</body>
</html>
