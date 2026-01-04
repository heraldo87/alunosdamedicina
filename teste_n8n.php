<?php
// Configuração
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook-test/driver';
$mensagem = '';
$respostaN8n = '';

// Processa o clique no botão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disparar_teste'])) {
    
    // Dados de teste para enviar
    $dados = [
        'origem' => 'Teste Manual PHP',
        'data' => date('Y-m-d H:i:s'),
        'mensagem' => 'Olá n8n! Testando conexão.'
    ];

    $jsonDados = json_encode($dados);

    // Configuração do cURL
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDados);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonDados)
    ]);
    
    // Executa
    $resultado = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $mensagem = '<div class="bg-red-100 text-red-700 p-4 rounded mb-4">Erro cURL: ' . curl_error($ch) . '</div>';
    } else {
        $classeCor = ($httpCode >= 200 && $httpCode < 300) ? 'green' : 'yellow';
        $mensagem = '<div class="bg-'.$classeCor.'-100 text-'.$classeCor.'-800 p-4 rounded mb-4">
                        <strong>Sucesso!</strong> Código HTTP: ' . $httpCode . '
                     </div>';
        $respostaN8n = $resultado;
    }
    
    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Webhook n8n</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Teste de Conexão n8n</h1>
            <p class="text-slate-500 text-sm mt-1">Dispara para: .../webhook-test/driver</p>
        </div>

        <?php echo $mensagem; ?>

        <form method="POST">
            <button type="submit" name="disparar_teste" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded transition duration-200 flex items-center justify-center gap-2">
                🚀 Disparar Webhook Agora
            </button>
        </form>

        <?php if ($respostaN8n): ?>
            <div class="mt-6">
                <p class="text-xs font-bold text-slate-500 uppercase mb-2">Resposta do n8n:</p>
                <pre class="bg-slate-900 text-green-400 p-4 rounded text-xs overflow-auto"><?php echo htmlspecialchars($respostaN8n ?: 'Nenhuma resposta de texto (vazio)'); ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="text-sm text-blue-500 hover:underline">Voltar para Home</a>
        </div>
    </div>

</body>
</html>