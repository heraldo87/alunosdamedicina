<?php
/**
 * MEDINFOCUS - Teste de Listagem de Arquivos (Visual)
 * Objetivo: Validar o retorno do n8n e exibir como lista formatada
 */

session_start();

// URL do Webhook (Ajustada conforme seu código)
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook/37c97a47-aef8-4689-976d-0c3f6acc5cc4';

$resultado = null;
$status = null;
$files = [];

// Função para ícones (Copiada do padrão do projeto)
function getFileIcon($mimeType) {
    if (strpos($mimeType, 'pdf') !== false) return 'fa-file-pdf text-red-500';
    if (strpos($mimeType, 'image') !== false) return 'fa-file-image text-purple-500';
    if (strpos($mimeType, 'word') !== false || strpos($mimeType, 'document') !== false) return 'fa-file-word text-blue-500';
    if (strpos($mimeType, 'sheet') !== false || strpos($mimeType, 'excel') !== false) return 'fa-file-excel text-emerald-500';
    if (strpos($mimeType, 'presentation') !== false) return 'fa-file-powerpoint text-orange-500';
    if (strpos($mimeType, 'folder') !== false) return 'fa-folder text-amber-500';
    return 'fa-file text-slate-400';
}

// Só executa se o botão for clicado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar_api'])) {
    
    // DADOS PARA ENVIAR
    $payload = [
        'mensagem' => 'Solicitando lista de arquivos',
        'acao' => 'listar_arquivos_pasta',
        'nome_pasta' => 'Teste2', // Pasta alvo
        'usuario' => $_SESSION['user_name'] ?? 'Visitante',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $ch = curl_init($webhookUrl);
    $jsonData = json_encode($payload);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20, // Timeout aumentado para listagem
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $resultado = 'Erro cURL: ' . curl_error($ch);
        $status = 'erro';
    } else {
        $resultado = $response;
        $status = ($httpCode >= 200 && $httpCode < 300) ? 'sucesso' : 'erro_http';
        
        // Tenta processar o JSON para extrair os arquivos
        $decoded = json_decode($response, true);
        
        // Verifica vários formatos possíveis que o n8n pode retornar
        if (isset($decoded['files'])) {
            $files = $decoded['files'];
        } elseif (isset($decoded[0]['files'])) {
            $files = $decoded[0]['files'];
        } elseif (isset($decoded['json']['files'])) {
            $files = $decoded['json']['files'];
        }
    }
    
    curl_close($ch);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizador de Arquivos - MedInFocus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0b0f1a', primary: '#0284c7', surface: '#1e293b' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0b0f1a; }
        .file-row { transition: all 0.2s ease; border-bottom: 1px solid rgba(30, 41, 59, 0.5); }
        .file-row:hover { background: rgba(30, 41, 59, 0.6); }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-300 h-screen flex flex-col items-center justify-start pt-10 overflow-y-auto custom-scrollbar">

    <div class="w-full max-w-4xl px-6">
        
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-white mb-2">Teste de Integração: Listagem</h1>
            <p class="text-slate-500 text-sm">Busca arquivos na pasta <span class="text-brand-primary font-mono bg-slate-800 px-2 py-0.5 rounded">Teste2</span> via n8n.</p>
            
            <form method="POST" class="mt-6">
                <button type="submit" name="testar_api" class="bg-brand-primary hover:bg-sky-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-brand-primary/20 transition-all active:scale-95 flex items-center gap-2 mx-auto">
                    <i class="fa-solid fa-sync <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'fa-spin' : ''; ?>"></i> 
                    Carregar Arquivos
                </button>
            </form>
        </div>

        <?php if ($resultado !== null): ?>
            
            <?php if ($status === 'erro' || $status === 'erro_http'): ?>
                <div class="p-6 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-center">
                    <i class="fa-solid fa-triangle-exclamation text-3xl text-rose-500 mb-3"></i>
                    <h3 class="text-rose-400 font-bold">Falha na Comunicação</h3>
                    <p class="text-xs text-rose-300/70 font-mono mt-2 break-all"><?php echo htmlspecialchars($resultado); ?></p>
                </div>

            <?php elseif (!empty($files)): ?>
                <div class="bg-slate-900/50 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl backdrop-blur-sm animate-fade-in">
                    
                    <div class="grid grid-cols-12 gap-4 p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-800 bg-slate-950/50">
                        <div class="col-span-8 md:col-span-7">Arquivo</div>
                        <div class="col-span-2 hidden md:block text-right">Tamanho</div>
                        <div class="col-span-4 md:col-span-3 text-right">Ação</div>
                    </div>

                    <?php foreach ($files as $file): ?>
                        <div class="file-row grid grid-cols-12 gap-4 p-4 items-center group cursor-default">
                            
                            <div class="col-span-8 md:col-span-7 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-slate-800/50 flex items-center justify-center text-lg shadow-inner">
                                    <i class="fa-solid <?php echo getFileIcon($file['mimeType'] ?? ''); ?>"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-medium text-slate-200 truncate group-hover:text-brand-primary transition-colors">
                                        <?php echo htmlspecialchars($file['name'] ?? 'Sem Nome'); ?>
                                    </h4>
                                    <p class="text-[10px] text-slate-500 truncate font-mono">
                                        <?php echo htmlspecialchars($file['mimeType'] ?? 'Desconhecido'); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-span-2 hidden md:block text-right text-xs text-slate-500 font-mono">
                                <?php 
                                    $size = $file['size'] ?? 0;
                                    echo ($size > 1024*1024) ? round($size / 1024 / 1024, 2) . ' MB' : round($size / 1024, 0) . ' KB'; 
                                ?>
                            </div>

                            <div class="col-span-4 md:col-span-3 flex justify-end">
                                <a href="<?php echo $file['webViewLink'] ?? $file['url'] ?? '#'; ?>" target="_blank"
                                   class="px-3 py-1.5 rounded-lg bg-brand-primary/10 border border-brand-primary/20 text-xs font-bold text-brand-primary hover:bg-brand-primary hover:text-white transition-all flex items-center gap-2">
                                    Abrir <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                    
                    <div class="p-3 bg-slate-950/50 text-center text-[10px] text-slate-600 border-t border-slate-800">
                        Total de <?php echo count($files); ?> arquivos encontrados.
                    </div>
                </div>

            <?php else: ?>
                <div class="p-8 bg-slate-900/50 border border-slate-800 rounded-2xl text-center">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-700 mb-4"></i>
                    <p class="text-slate-400">Nenhum arquivo encontrado ou formato de JSON inesperado.</p>
                    
                    <div class="mt-6 text-left">
                        <p class="text-[10px] uppercase font-bold text-slate-600 mb-2">Debug JSON Cru:</p>
                        <pre class="bg-black/50 p-4 rounded-lg text-xs text-green-400 font-mono overflow-x-auto border border-slate-800"><?php 
                            $json = json_decode($resultado);
                            echo $json ? json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $resultado; 
                        ?></pre>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

</body>
</html>