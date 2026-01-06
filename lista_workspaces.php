<?php
/**
 * MEDINFOCUS - Listagem de Workspaces (Integração n8n/Google Drive)
 * Recupera as pastas do Google Drive permitidas para o usuário via API n8n.
 */

session_start();

// 1. SEGURANÇA: Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. PREPARAÇÃO DOS DADOS
$userData = [
    'user_id'   => $_SESSION['user_id'] ?? 0,
    'user_name' => $_SESSION['user_name'] ?? 'Usuario',
    'user_email'=> $_SESSION['user_email'] ?? 'email@exemplo.com',
    'user_type' => $_SESSION['user_type'] ?? 'aluno',
    'request_time' => date('Y-m-d H:i:s')
];

// 3. COMUNICAÇÃO COM A API (n8n)
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook/c942248a-0c1d-49e8-98d3-8ec72d4f5b7c';

$workspaces = [];
$errorMsg = null;
$apiStatus = false;

try {
    $ch = curl_init($webhookUrl);
    $jsonData = json_encode($userData);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
            'X-Source: MedInFocus-System'
        ]
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro de conexão (cURL): ' . curl_error($ch));
    }
    curl_close($ch);

    // 4. PROCESSAMENTO DA RESPOSTA (Lógica Corrigida pelo Debug)
    if ($httpCode === 200) {
        $response = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('A API retornou dados inválidos.');
        }

        // VERIFICAÇÃO HIERÁRQUICA (A Correção Principal)
        // 1. Tenta pegar dentro do array wrapper [0]['workspaces'] (Formato atual do seu Debug)
        if (isset($response[0]['workspaces']) && is_array($response[0]['workspaces'])) {
            $workspaces = $response[0]['workspaces'];
            $apiStatus = true;
        } 
        // 2. Tenta pegar direto na raiz ['workspaces'] (Caso você mude o n8n no futuro)
        elseif (isset($response['workspaces']) && is_array($response['workspaces'])) {
            $workspaces = $response['workspaces'];
            $apiStatus = true;
        }
        // 3. Fallback: Tenta pegar a resposta inteira se for uma lista de objetos
        elseif (is_array($response) && isset($response[0]['name'])) {
            $workspaces = $response;
            $apiStatus = true;
        } else {
            // Recebeu JSON válido, mas sem workspaces (lista vazia)
            $workspaces = [];
            // Não vamos tratar como erro, apenas como "sem pastas"
        }

    } else {
        throw new Exception("Falha na API. Código HTTP: $httpCode");
    }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    $workspaces = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspaces - MEDINFOCUS</title>
    
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
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        
        .folder-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .folder-card:hover {
            transform: translateY(-5px) scale(1.02);
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(2, 132, 199, 0.5);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <header class="pt-10 pb-6 px-8 border-b border-slate-800/50 bg-brand-dark/95 sticky top-0 z-10 backdrop-blur-md">
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                        <span class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500 border border-emerald-500/20">
                            <i class="fa-brands fa-google-drive text-xl"></i>
                        </span>
                        Drive Workspaces
                    </h2>
                    <p class="text-slate-500 text-sm mt-2 ml-1">
                        Acesso direto aos repositórios de estudo sincronizados.
                    </p>
                </div>
                
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full border text-[11px] font-bold uppercase tracking-wider <?php echo $apiStatus ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400' : 'border-rose-500/20 bg-rose-500/5 text-rose-400'; ?>">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo $apiStatus ? 'bg-emerald-400' : 'bg-rose-400'; ?> opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 <?php echo $apiStatus ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                    </span>
                    <?php echo $apiStatus ? 'Sincronizado' : 'Offline'; ?>
                </div>
            </div>
        </header>

        <div class="p-8">
            
            <?php if ($errorMsg): ?>
                <div class="mb-8 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center gap-4">
                    <div class="p-2 bg-rose-500/20 rounded-full text-rose-500">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-400">Falha na Conexão</h3>
                        <p class="text-xs text-rose-300/70 mt-0.5"><?php echo htmlspecialchars($errorMsg); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($workspaces)): ?>
                <div class="flex flex-col items-center justify-center py-24 text-slate-600">
                    <div class="w-24 h-24 bg-slate-800/50 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-folder-open text-4xl opacity-30"></i>
                    </div>
                    <p class="text-lg font-medium text-slate-500">Nenhuma workspace disponível.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($workspaces as $ws): ?>
                        <a href="<?php echo htmlspecialchars($ws['url'] ?? '#'); ?>" target="_blank" class="folder-card p-6 rounded-3xl group relative flex flex-col h-full cursor-pointer">
                            
                            <div class="flex justify-between items-start mb-5">
                                <div class="w-14 h-14 rounded-2xl bg-slate-700/30 flex items-center justify-center text-amber-500 border border-white/5 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-lg shadow-black/20">
                                    <i class="fa-solid <?php echo htmlspecialchars($ws['icon'] ?? 'fa-folder'); ?> text-2xl"></i>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-slate-400 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                </div>
                            </div>
                            
                            <h3 class="text-white font-bold text-lg mb-2 group-hover:text-brand-primary transition-colors line-clamp-1">
                                <?php echo htmlspecialchars($ws['name'] ?? 'Pasta Sem Nome'); ?>
                            </h3>
                            
                            <p class="text-slate-500 text-xs leading-relaxed mb-6 line-clamp-2 h-8">
                                <?php echo htmlspecialchars($ws['description'] ?? 'Workspace Acadêmica'); ?>
                            </p>

                            <div class="mt-auto pt-4 border-t border-white/5 flex justify-between items-center">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-800/50 px-2 py-1 rounded">
                                    <?php echo $ws['files_count'] ?? '0'; ?> Arquivos
                                </span>
                                <span class="text-[10px] font-bold text-brand-primary opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                                    ACESSAR
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>