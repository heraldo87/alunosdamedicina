<?php
/**
 * MEDINFOCUS - Processador de Upload de Workspace
 * Funcionalidade: Recebe a lista de arquivos e envia para o n8n processar/sincronizar.
 */

session_start();

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php'); // Ajuste o caminho se necessário
    exit;
}

// Verifica se veio via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se tentar acessar direto pela URL, manda de volta pro repositório
    header('Location: repositorio.php');
    exit;
}

// 2. CAPTURA DOS DADOS DO FORMULÁRIO
// Decodifica o JSON que veio do input hidden
$filesData  = json_decode($_POST['files_data'] ?? '[]', true);
$folderId   = $_POST['folder_id'] ?? '';
$folderName = $_POST['folder_name'] ?? 'Workspace';

// 3. CONFIGURAÇÃO DA API (O Webhook Correto)
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook-test/8b40c833-b574-441d-835b-33968d93cc97';

$apiResult = null;
$errorMsg = null;
$success = false;

// Prepara o Payload para o n8n
$payload = [
    'acao' => 'processar_upload_workspace',
    'folder' => [
        'id' => $folderId,
        'name' => $folderName
    ],
    'files_count' => count($filesData),
    'files_list' => $filesData, // A lista completa dos arquivos
    'triggered_by' => [
        'user_id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['user_name'] ?? 'Usuario',
        'email' => $_SESSION['user_email'] ?? ''
    ],
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    $ch = curl_init($webhookUrl);
    $jsonData = json_encode($payload);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30, // Timeout generoso para processamento
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
            'X-Source: MedInFocus-System'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro na comunicação com servidor: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $success = true;
        $apiResult = json_decode($response, true);
    } else {
        throw new Exception("O n8n retornou erro (HTTP $httpCode).");
    }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processando Upload - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0b0f1a',    
                            primary: '#0284c7', 
                            surface: '#1e293b', 
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0b0f1a; }
        
        /* Card de Status */
        .status-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 20px rgba(2, 132, 199, 0); }
            100% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(2, 132, 199, 0); }
        }
        .pulse-animation {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>
<body class="text-slate-300 h-screen flex items-center justify-center relative overflow-hidden">

    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-brand-primary/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="status-card p-10 rounded-3xl max-w-lg w-full text-center mx-4">
        
        <?php if ($success): ?>
            <div class="mb-6 relative inline-block">
                <div class="w-24 h-24 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400 mx-auto pulse-animation">
                    <i class="fa-solid fa-cloud-arrow-up text-4xl"></i>
                </div>
                <div class="absolute bottom-0 right-0 bg-emerald-500 text-white w-8 h-8 rounded-full flex items-center justify-center border-4 border-slate-900">
                    <i class="fa-solid fa-check text-xs"></i>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-white mb-2">Sincronização Iniciada!</h1>
            <p class="text-slate-400 text-sm mb-6">
                Enviamos <strong><?php echo count($filesData); ?> arquivos</strong> da pasta <span class="text-brand-primary font-mono"><?php echo htmlspecialchars($folderName); ?></span> para processamento.
            </p>

            <div class="bg-slate-900/50 rounded-xl p-4 mb-8 text-left border border-slate-800">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Status do Servidor:</p>
                <div class="flex items-center gap-3 text-xs text-emerald-400">
                    <i class="fa-solid fa-server"></i>
                    <span>Recebido com sucesso pelo n8n.</span>
                </div>
                <?php if(isset($apiResult['message'])): ?>
                    <p class="mt-2 text-xs text-slate-500 font-mono">Msg: <?php echo htmlspecialchars($apiResult['message']); ?></p>
                <?php endif; ?>
            </div>

            <a href="abrir_workspace.php?id=<?php echo urlencode($folderId); ?>&name=<?php echo urlencode($folderName); ?>" 
               class="inline-flex items-center justify-center px-6 py-3 bg-brand-primary hover:bg-sky-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-brand-primary/25 w-full">
                <i class="fa-solid fa-arrow-left mr-2"></i> Voltar para a Pasta
            </a>

        <?php else: ?>
            <div class="mb-6">
                <div class="w-24 h-24 bg-rose-500/10 rounded-full flex items-center justify-center text-rose-500 mx-auto border border-rose-500/20">
                    <i class="fa-solid fa-triangle-exclamation text-4xl"></i>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-white mb-2">Falha no Envio</h1>
            <p class="text-slate-400 text-sm mb-6">
                Não conseguimos conectar com o servidor de automação.
            </p>

            <div class="bg-rose-950/30 rounded-xl p-4 mb-8 text-left border border-rose-500/20">
                <p class="text-[10px] font-bold text-rose-400 uppercase tracking-widest mb-1">Detalhe do Erro:</p>
                <p class="text-xs text-rose-300 font-mono break-all"><?php echo htmlspecialchars($errorMsg); ?></p>
            </div>

            <div class="flex gap-3">
                <a href="abrir_workspace.php?id=<?php echo urlencode($folderId); ?>&name=<?php echo urlencode($folderName); ?>" 
                   class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl transition-colors text-sm">
                    Voltar
                </a>
                <button onclick="window.location.reload();" 
                   class="flex-1 px-4 py-3 bg-brand-primary hover:bg-sky-500 text-white font-bold rounded-xl transition-colors text-sm">
                    Tentar Novamente
                </button>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>