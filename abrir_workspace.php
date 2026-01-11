<?php
/**
 * MEDINFOCUS - Visualizador de Workspace (Modo Ícones Inteligentes)
 * Funcionalidade: Exibe arquivos do Drive/N8N e permite ações de CRUD
 */

session_start();

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. CAPTURA PARÂMETROS
$folderName = $_GET['name'] ?? 'Workspace'; 
$folderId   = $_GET['id'] ?? ''; 

// 3. COMUNICAÇÃO COM N8N (LISTAGEM)
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook/37c97a47-aef8-4689-976d-0c3f6acc5cc4';

$files = [];
$errorMsg = null;

$payload = [
    'acao' => 'listar_arquivos_pasta',
    'folder_id' => $folderId,
    'nome_pasta' => $folderName,
    'usuario' => $_SESSION['user_name'] ?? 'Aluno',
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    $ch = curl_init($webhookUrl);
    $jsonData = json_encode($payload);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20, 
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
            'X-Source: MedInFocus-System'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Conexão instável: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        
        if (isset($decoded['files'])) {
            $files = $decoded['files'];
        } elseif (isset($decoded[0]['files'])) {
            $files = $decoded[0]['files'];
        } elseif (isset($decoded['json']['files'])) {
            $files = $decoded['json']['files'];
        }
    } else {
        throw new Exception("O servidor de arquivos não respondeu corretamente (Cód: $httpCode).");
    }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}

/**
 * Função Inteligente de Ícones
 */
function getFileIconClass($file) {
    $mime = strtolower($file['mimeType'] ?? '');
    $name = strtolower($file['name'] ?? '');
    $ext  = pathinfo($name, PATHINFO_EXTENSION);

    if (strpos($mime, 'folder') !== false) return 'fa-solid fa-folder text-amber-400';
    if (strpos($mime, 'pdf') !== false || $ext === 'pdf') return 'fa-solid fa-file-pdf text-rose-500';
    if (strpos($mime, 'image') !== false || in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) return 'fa-solid fa-file-image text-purple-400';
    if (strpos($mime, 'word') !== false || strpos($mime, 'document') !== false || in_array($ext, ['doc','docx','txt','rtf'])) return 'fa-solid fa-file-word text-blue-500';
    if (strpos($mime, 'sheet') !== false || strpos($mime, 'excel') !== false || strpos($mime, 'spreadsheet') !== false || in_array($ext, ['xls','xlsx','csv'])) return 'fa-solid fa-file-excel text-emerald-500';
    if (strpos($mime, 'presentation') !== false || strpos($mime, 'powerpoint') !== false || in_array($ext, ['ppt','pptx'])) return 'fa-solid fa-file-powerpoint text-orange-500';
    if (strpos($mime, 'video') !== false || in_array($ext, ['mp4','mov','avi','mkv'])) return 'fa-solid fa-circle-play text-pink-500';
    if (strpos($mime, 'audio') !== false || in_array($ext, ['mp3','wav','ogg'])) return 'fa-solid fa-music text-yellow-400';
    if (strpos($mime, 'zip') !== false || strpos($mime, 'compressed') !== false || in_array($ext, ['zip','rar','7z','tar'])) return 'fa-solid fa-file-zipper text-slate-400';

    return 'fa-solid fa-file text-slate-600';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($folderName); ?> - Arquivos</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        
        .file-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .file-card:hover {
            transform: translateY(-5px);
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(2, 132, 199, 0.4);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-enter { animation: fadeIn 0.4s ease-out forwards; }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative bg-brand-dark">
        
        <header class="pt-10 pb-6 px-8 border-b border-slate-800/50 bg-brand-dark/95 sticky top-0 z-20 backdrop-blur-md">
            <div class="flex items-center gap-4 mb-3">
                <a href="repositorio.php" class="w-8 h-8 rounded-full bg-slate-800/50 border border-slate-700 flex items-center justify-center text-slate-400 hover:text-white hover:bg-brand-primary transition-all group" title="Voltar">
                    <i class="fa-solid fa-arrow-left text-xs group-hover:-translate-x-0.5 transition-transform"></i>
                </a>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Repositório / Workspace</span>
            </div>
            
            <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4">
                <h2 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-regular fa-folder-open text-amber-500"></i>
                    <?php echo htmlspecialchars($folderName); ?>
                </h2>
                
                <div class="flex items-center gap-3">
                    <div class="hidden md:block text-[10px] font-mono text-slate-600 px-2 py-1 rounded bg-slate-900 border border-slate-800">
                        SYNC: N8N DRIVE
                    </div>

                    <?php if (!empty($files)): ?>
                        <form action="api/upload_worspace.php" method="POST" target="_blank">
                            <input type="hidden" name="files_data" value="<?php echo htmlspecialchars(json_encode($files)); ?>">
                            <input type="hidden" name="folder_id" value="<?php echo htmlspecialchars($folderId); ?>">
                            <input type="hidden" name="folder_name" value="<?php echo htmlspecialchars($folderName); ?>">
                            
                            <button type="submit" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg transition-all active:scale-95 flex items-center gap-2 border border-slate-600">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                Upload
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="p-8">
            
            <?php if ($errorMsg): ?>
                <div class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center animate-enter">
                    <div class="w-12 h-12 bg-rose-500/20 rounded-full flex items-center justify-center mx-auto mb-3 text-rose-500">
                        <i class="fa-solid fa-plug-circle-xmark text-xl"></i>
                    </div>
                    <h3 class="text-rose-400 font-bold mb-1">Não foi possível carregar os arquivos</h3>
                    <p class="text-xs text-rose-300/70"><?php echo htmlspecialchars($errorMsg); ?></p>
                    <a href="abrir_workspace.php?name=<?php echo urlencode($folderName); ?>&id=<?php echo urlencode($folderId); ?>" class="inline-block mt-4 px-4 py-2 bg-rose-500 text-white text-xs font-bold rounded-lg hover:bg-rose-600 transition-colors">
                        Tentar Novamente
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($files) && !$errorMsg): ?>
                <div class="flex flex-col items-center justify-center py-24 text-slate-600 animate-enter">
                    <div class="w-24 h-24 bg-slate-800/30 rounded-3xl flex items-center justify-center mb-6 border border-slate-800 border-dashed">
                        <i class="fa-solid fa-folder-open text-4xl opacity-20"></i>
                    </div>
                    <p class="font-medium text-slate-500">Esta pasta está vazia.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($files)): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6" id="filesGrid">
                    <?php foreach ($files as $index => $file): ?>
                        
                        <div id="file-card-<?php echo $file['id'] ?? 'uniq_'.uniqid(); ?>" class="file-card group relative rounded-3xl p-4 flex flex-col items-center text-center animate-enter" 
                             style="animation-delay: <?php echo $index * 50; ?>ms;">
                            
                            <a href="<?php echo $file['webViewLink'] ?? $file['url'] ?? '#'; ?>" target="_blank" 
                               class="w-16 h-16 mb-3 flex items-center justify-center transition-transform group-hover:scale-110 duration-300 cursor-pointer">
                                <i class="<?php echo getFileIconClass($file); ?> text-5xl drop-shadow-2xl"></i>
                            </a>

                            <h4 class="text-sm font-medium text-slate-200 group-hover:text-brand-primary transition-colors line-clamp-2 w-full leading-snug mb-3 min-h-[2.5rem]" title="<?php echo htmlspecialchars($file['name'] ?? 'Sem Nome'); ?>">
                                <?php echo htmlspecialchars($file['name'] ?? 'Sem Nome'); ?>
                            </h4>

                            <div class="mt-auto pt-3 w-full border-t border-white/5 flex justify-between items-center px-1">
                                
                                <a href="api/baixar_ws.php?file_id=<?php echo $file['id'] ?? ''; ?>&name=<?php echo urlencode($file['name'] ?? 'arquivo'); ?>&mime=<?php echo urlencode($file['mimeType'] ?? ''); ?>" 
                                   target="_blank"
                                   class="text-slate-500 hover:text-emerald-400 hover:bg-emerald-400/10 p-2 rounded-lg transition-all"
                                   title="Baixar Arquivo">
                                    <i class="fa-solid fa-download"></i>
                                </a>

                                <span class="text-[9px] font-mono text-slate-600 uppercase">
                                    <?php echo strtoupper(pathinfo($file['name'] ?? 'file', PATHINFO_EXTENSION)); ?>
                                </span>

                                <button onclick="deletarArquivo('<?php echo $file['id'] ?? ''; ?>', '<?php echo addslashes($file['name'] ?? 'Arquivo'); ?>')" 
                                        class="text-slate-500 hover:text-rose-500 hover:bg-rose-500/10 p-2 rounded-lg transition-all"
                                        title="Deletar Arquivo">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-10 text-center border-t border-slate-800 pt-6">
                    <p class="text-[10px] text-slate-600 uppercase font-bold tracking-widest">
                        Exibindo <?php echo count($files); ?> itens
                    </p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script>
        function deletarArquivo(fileId, fileName) {
            if (!fileId) {
                Swal.fire('Erro', 'ID do arquivo inválido.', 'error');
                return;
            }

            Swal.fire({
                title: 'Tem certeza?',
                text: `Você está prestes a deletar "${fileName}". Esta ação não pode ser desfeita.`,
                icon: 'warning',
                background: '#1e293b',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#475569',
                confirmButtonText: 'Sim, deletar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    // Mostra loading
                    Swal.fire({
                        title: 'Deletando...',
                        text: 'Aguarde enquanto processamos no Drive.',
                        background: '#1e293b',
                        color: '#fff',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Chama API de Deleção
                    fetch('api/deletar_ws.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            file_id: fileId,
                            file_name: fileName
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove o card visualmente
                            const card = document.getElementById('file-card-' + fileId);
                            if (card) {
                                card.classList.add('opacity-0', 'scale-90');
                                setTimeout(() => card.remove(), 300);
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Deletado!',
                                text: 'O arquivo foi removido com sucesso.',
                                background: '#1e293b',
                                color: '#fff',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            throw new Error(data.message || 'Erro desconhecido');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Não foi possível deletar: ' + error.message,
                            background: '#1e293b',
                            color: '#fff'
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>