<?php
/**
 * MEDINFOCUS - Repositório de Workspaces
 * Funcionalidade: Listar (via n8n) e Criar Workspaces (Localmente)
 */

session_start();
// Se o arquivo config.php não existir, comente a linha abaixo ou ajuste o caminho
if (file_exists('php/config.php')) {
    require_once 'php/config.php';
}

// 1. SEGURANÇA: Apenas usuários logados podem acessar
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// =================================================================================
// PARTE 1: LÓGICA DE CRIAÇÃO
// =================================================================================
$msgFeedback = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] === 'criar_pasta') {
    
    $nomePasta = trim($_POST['nome_pasta']);
    
    // HIGIENIZAÇÃO
    $nomePasta = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $nomePasta);
    $nomePasta = str_replace(' ', '_', $nomePasta);

    if (!empty($nomePasta)) {
        $caminho = 'repositorio/' . $nomePasta;
        
        if (!file_exists($caminho)) {
            if (mkdir($caminho, 0777, true)) {
                file_put_contents($caminho . '/index.html', ''); 
                header("Location: repositorio.php?msg=sucesso");
                exit;
            } else {
                $msgFeedback = "Erro ao criar diretório. Verifique permissões.";
            }
        } else {
            $msgFeedback = "Já existe uma pasta com esse nome.";
        }
    } else {
        $msgFeedback = "Nome inválido.";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso') {
    $msgFeedback = "Workspace criada com sucesso!";
}

// =================================================================================
// PARTE 2: LÓGICA DE LISTAGEM (Conecta no n8n)
// =================================================================================
$userData = [
    'user_id'   => $_SESSION['user_id'] ?? 0,
    'user_name' => $_SESSION['user_name'] ?? 'Usuario',
    'user_email'=> $_SESSION['user_email'] ?? 'email@exemplo.com',
    'user_type' => $_SESSION['user_type'] ?? 'aluno',
    'request_time' => date('Y-m-d H:i:s')
];

// URL do n8n que retorna a LISTA DE WORKSPACES (Pastas Raiz)
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
        $errorMsg = 'Erro de conexão API: ' . curl_error($ch);
    }
    curl_close($ch);

    if ($httpCode === 200) {
        $response = json_decode($result, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // Lógica para encontrar o array de workspaces
            if (isset($response[0]['workspaces']) && is_array($response[0]['workspaces'])) {
                $workspaces = $response[0]['workspaces'];
                $apiStatus = true;
            } elseif (isset($response['workspaces']) && is_array($response['workspaces'])) {
                $workspaces = $response['workspaces'];
                $apiStatus = true;
            } elseif (is_array($response) && isset($response[0]['name'])) {
                $workspaces = $response;
                $apiStatus = true;
            }
        }
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
        .modal-bg {
            background-color: rgba(11, 15, 26, 0.85);
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <header class="pt-10 pb-6 px-8 border-b border-slate-800/50 bg-brand-dark/95 sticky top-0 z-10 backdrop-blur-md">
            <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4">
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
                
                <div class="flex items-center gap-4">
                    <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full border text-[10px] font-bold uppercase tracking-wider <?php echo $apiStatus ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400' : 'border-rose-500/20 bg-rose-500/5 text-rose-400'; ?>">
                        <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo $apiStatus ? 'bg-emerald-400' : 'bg-rose-400'; ?> opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 <?php echo $apiStatus ? 'bg-emerald-500' : 'bg-rose-500'; ?>"></span>
                        </span>
                        <?php echo $apiStatus ? 'Sincronizado' : 'Offline'; ?>
                    </div>

                    <button onclick="document.getElementById('modalCriar').classList.remove('hidden')" 
                            class="bg-brand-primary hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-brand-primary/20 transition-all active:scale-95 flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        Nova Workspace
                    </button>
                </div>
            </div>
        </header>

        <div class="p-8">
            
            <?php if (!empty($msgFeedback)): ?>
                <div class="mb-8 p-4 rounded-xl border <?php echo strpos($msgFeedback, 'Erro') !== false ? 'bg-rose-500/10 border-rose-500/20 text-rose-400' : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400'; ?> flex items-center gap-4">
                    <i class="fa-solid <?php echo strpos($msgFeedback, 'Erro') !== false ? 'fa-circle-exclamation' : 'fa-check-circle'; ?>"></i>
                    <span class="font-bold text-sm"><?php echo htmlspecialchars($msgFeedback); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="mb-4 text-xs text-rose-500 font-mono">Debug: <?php echo htmlspecialchars($errorMsg); ?></div>
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
                        
                        <a href="abrir_workspace.php?id=<?php echo urlencode($ws['id'] ?? ''); ?>&name=<?php echo urlencode($ws['name'] ?? 'Workspace'); ?>" 
                           class="folder-card p-6 rounded-3xl group relative flex flex-col h-full cursor-pointer">
                            
                            <div class="flex justify-between items-start mb-5">
                                <div class="w-14 h-14 rounded-2xl bg-slate-700/30 flex items-center justify-center text-amber-500 border border-white/5 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-lg shadow-black/20">
                                    <i class="fa-solid <?php echo htmlspecialchars($ws['icon'] ?? 'fa-folder'); ?> text-2xl"></i>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-slate-400 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
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
                                    ABRIR
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="modalCriar" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-bg px-4">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden relative animate-bounce-in">
            <div class="bg-slate-800/50 p-6 border-b border-slate-700 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg">Nova Workspace Local</h3>
                <button onclick="document.getElementById('modalCriar').classList.add('hidden')" class="text-slate-500 hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form method="POST" action="repositorio.php" class="p-6">
                <input type="hidden" name="acao" value="criar_pasta">
                <div class="mb-5">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nome da Pasta</label>
                    <div class="relative">
                        <i class="fa-solid fa-folder absolute left-4 top-3.5 text-slate-500"></i>
                        <input type="text" name="nome_pasta" required placeholder="Ex: Patologia_2024" 
                               class="w-full bg-slate-950 border border-slate-700 text-white text-sm rounded-xl py-3 pl-10 pr-4 focus:ring-2 focus:ring-brand-primary outline-none transition-all placeholder-slate-600">
                    </div>
                    <p class="text-[10px] text-slate-500 mt-2">Permitido: Letras, números e underline (_).</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modalCriar').classList.add('hidden')" 
                            class="px-4 py-2 rounded-xl text-sm font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Cancelar</button>
                    <button type="submit" 
                            class="px-6 py-2 rounded-xl text-sm font-bold bg-brand-primary text-white hover:bg-sky-500 shadow-lg shadow-brand-primary/20 transition-all active:scale-95">Criar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>