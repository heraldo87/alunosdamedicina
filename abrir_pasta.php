<?php
// abrir_pasta.php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA E VALIDAÇÃO
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$workspaceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$folderId    = isset($_GET['folder']) ? intval($_GET['folder']) : null; // NULL = Raiz
$usuarioId   = $_SESSION['user_id'] ?? 0;

// Verificar se o usuário tem acesso a este Workspace
$sqlPermissao = "SELECT p.role, w.name as workspace_name, w.drive_folder_id 
                 FROM workspaces w 
                 JOIN workspace_permissions p ON w.id = p.workspace_id 
                 WHERE w.id = :wid AND p.user_id = :uid AND w.status = 'active'";
$stmt = $pdo->prepare($sqlPermissao);
$stmt->execute([':wid' => $workspaceId, ':uid' => $usuarioId]);
$acesso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$acesso) {
    die("Acesso negado ou disciplina inexistente.");
}

$nomeWorkspace = $acesso['workspace_name'];
$permissaoUser = $acesso['role']; // 'viewer', 'editor', 'admin'

// 2. LOGICA DE BREADCRUMBS (Caminho de Pão)
$breadcrumbs = [];
$breadcrumbs[] = ['id' => null, 'name' => $nomeWorkspace]; // Raiz

$folderAtualNome = "Raiz";
if ($folderId) {
    // Buscar caminho recursivo (ou apenas o pai imediato para simplificar neste passo)
    // Para um breadcrumb completo recursivo, seria ideal uma função auxiliar, 
    // aqui faremos uma busca simples do nome da pasta atual.
    $stmtCaminho = $pdo->prepare("SELECT id, name, parent_folder_id FROM virtual_folders WHERE id = :fid");
    $stmtCaminho->execute([':fid' => $folderId]);
    $folderAtual = $stmtCaminho->fetch(PDO::FETCH_ASSOC);
    if($folderAtual){
        $folderAtualNome = $folderAtual['name'];
        $breadcrumbs[] = ['id' => $folderAtual['id'], 'name' => $folderAtual['name']];
    }
}

// 3. BUSCAR CONTEÚDO (Pastas e Arquivos)

// A) Pastas Virtuais (Subpastas)
$sqlPastas = "SELECT * FROM virtual_folders 
              WHERE workspace_id = :wid 
              AND " . ($folderId ? "parent_folder_id = :fid" : "parent_folder_id IS NULL") . "
              ORDER BY name ASC";
$stmtPastas = $pdo->prepare($sqlPastas);
$paramsPastas = [':wid' => $workspaceId];
if ($folderId) $paramsPastas[':fid'] = $folderId;
$stmtPastas->execute($paramsPastas);
$pastas = $stmtPastas->fetchAll(PDO::FETCH_ASSOC);

// B) Arquivos
$sqlArquivos = "SELECT * FROM files 
                WHERE workspace_id = :wid 
                AND " . ($folderId ? "folder_id = :fid" : "folder_id IS NULL") . "
                AND status = 'active'
                ORDER BY uploaded_at DESC";
$stmtArquivos = $pdo->prepare($sqlArquivos);
$stmtArquivos->execute($paramsPastas); // Mesmos parâmetros de filtro
$arquivos = $stmtArquivos->fetchAll(PDO::FETCH_ASSOC);

// Helper para ícones
function getFileIcon($mime) {
    if (strpos($mime, 'pdf') !== false) return 'fa-file-pdf text-rose-500';
    if (strpos($mime, 'image') !== false) return 'fa-file-image text-purple-500';
    if (strpos($mime, 'word') !== false || strpos($mime, 'document') !== false) return 'fa-file-word text-blue-500';
    if (strpos($mime, 'sheet') !== false || strpos($mime, 'excel') !== false) return 'fa-file-excel text-emerald-500';
    if (strpos($mime, 'zip') !== false || strpos($mime, 'compressed') !== false) return 'fa-file-zipper text-amber-600';
    return 'fa-file text-slate-400';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($folderAtualNome); ?> - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { brand: { dark: '#0b0f1a', surface: '#1e293b' } } }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .item-row:hover { background-color: rgba(30, 41, 59, 0.5); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        
        <header class="pt-8 pb-4 px-6 md:px-12 bg-brand-dark/95 backdrop-blur z-10 border-b border-slate-800">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                
                <nav class="flex items-center text-sm font-medium text-slate-500 overflow-x-auto whitespace-nowrap pb-2 md:pb-0">
                    <a href="repositorio.php" class="hover:text-amber-500 transition-colors"><i class="fa-solid fa-cloud"></i></a>
                    <span class="mx-2 text-slate-700">/</span>
                    <a href="abrir_pasta.php?id=<?php echo $workspaceId; ?>" class="hover:text-amber-500 transition-colors <?php echo !$folderId ? 'text-white font-bold' : ''; ?>">
                        <?php echo htmlspecialchars($nomeWorkspace); ?>
                    </a>
                    
                    <?php if ($folderId): ?>
                        <span class="mx-2 text-slate-700">/</span>
                        <span class="text-white font-bold flex items-center gap-2">
                            <i class="fa-solid fa-folder-open text-amber-500"></i>
                            <?php echo htmlspecialchars($folderAtualNome); ?>
                        </span>
                    <?php endif; ?>
                </nav>

                <div class="flex items-center gap-3">
                    <?php if ($permissaoUser !== 'viewer'): ?>
                        <button onclick="alert('Funcionalidade de Nova Pasta no próximo passo!')" class="p-2.5 rounded-xl border border-slate-700 text-slate-400 hover:text-white hover:bg-slate-800 transition-all" title="Nova Pasta">
                            <i class="fa-solid fa-folder-plus"></i>
                        </button>
                        <button onclick="toggleModal('modal-upload')" class="bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-2.5 px-6 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-amber-500/20 text-sm">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            Upload
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scrollbar px-6 md:px-12 py-6">
            
            <?php if (empty($pastas) && empty($arquivos)): ?>
                <div class="flex flex-col items-center justify-center h-64 border-2 border-dashed border-slate-800 rounded-3xl opacity-50">
                    <i class="fa-regular fa-folder-open text-4xl text-slate-600 mb-2"></i>
                    <span class="text-slate-500 font-medium">Pasta vazia</span>
                </div>
            <?php else: ?>
                
                <div class="grid grid-cols-12 gap-4 px-4 py-2 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-800 mb-2">
                    <div class="col-span-6 md:col-span-5">Nome</div>
                    <div class="col-span-3 hidden md:block">Adicionado por</div>
                    <div class="col-span-3 md:col-span-2 text-right">Tamanho</div>
                    <div class="col-span-3 md:col-span-2 text-center">Ações</div>
                </div>

                <?php foreach ($pastas as $pasta): ?>
                    <div class="item-row grid grid-cols-12 gap-4 px-4 py-3 rounded-xl items-center border border-transparent transition-all mb-1 cursor-pointer group"
                         onclick="window.location.href='abrir_pasta.php?id=<?php echo $workspaceId; ?>&folder=<?php echo $pasta['id']; ?>'">
                        <div class="col-span-6 md:col-span-5 flex items-center gap-3 overflow-hidden">
                            <i class="fa-solid fa-folder text-amber-500 text-xl group-hover:scale-110 transition-transform"></i>
                            <span class="text-slate-200 font-medium truncate"><?php echo htmlspecialchars($pasta['name']); ?></span>
                        </div>
                        <div class="col-span-3 hidden md:block text-slate-500 text-xs">-</div>
                        <div class="col-span-3 md:col-span-2 text-right text-slate-500 text-xs">-</div>
                        <div class="col-span-3 md:col-span-2 flex justify-center">
                            <button class="text-slate-600 hover:text-white transition-colors"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($arquivos as $arq): ?>
                    <div class="item-row grid grid-cols-12 gap-4 px-4 py-3 rounded-xl items-center border border-slate-800/30 bg-slate-900/20 mb-1 transition-all">
                        <div class="col-span-6 md:col-span-5 flex items-center gap-3 overflow-hidden">
                            <i class="fa-solid <?php echo getFileIcon($arq['mime_type']); ?> text-xl"></i>
                            <div class="flex flex-col truncate">
                                <span class="text-slate-300 font-medium truncate text-sm"><?php echo htmlspecialchars($arq['name']); ?></span>
                                <span class="text-[10px] text-slate-500 md:hidden">
                                    <?php echo date('d/m/y', strtotime($arq['uploaded_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-span-3 hidden md:block text-slate-500 text-xs truncate">
                            Usuario #<?php echo $arq['uploaded_by']; ?> <br>
                            <span class="text-[10px] opacity-70"><?php echo date('d/m/Y H:i', strtotime($arq['uploaded_at'])); ?></span>
                        </div>
                        <div class="col-span-3 md:col-span-2 text-right text-slate-500 text-xs font-mono">
                            <?php echo round($arq['size'] / 1024 / 1024, 2); ?> MB
                        </div>
                        <div class="col-span-3 md:col-span-2 flex justify-center gap-3">
                            <a href="#" onclick="alert('Download em breve!')" class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 hover:text-amber-500 hover:bg-slate-700 transition-all" title="Baixar">
                                <i class="fa-solid fa-download"></i>
                            </a>
                            <?php if ($permissaoUser !== 'viewer'): ?>
                                <button onclick="alert('Excluir em breve!')" class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-all" title="Excluir">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </main>

    <div id="modal-upload" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-black/80 backdrop-blur-sm" onclick="toggleModal('modal-upload')"></div>
        <div class="modal-container bg-brand-surface w-11/12 md:max-w-md mx-auto rounded-2xl shadow-2xl z-50 p-6 border border-slate-700 transform transition-all scale-95" id="modal-upload-content">
            <h3 class="text-xl font-bold text-white mb-4"><i class="fa-solid fa-cloud-arrow-up mr-2 text-amber-500"></i>Enviar Arquivo</h3>
            
            <form id="form-upload" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="workspace_id" value="<?php echo $workspaceId; ?>">
                <input type="hidden" name="folder_id" value="<?php echo $folderId ?? ''; ?>">
                
                <div class="border-2 border-dashed border-slate-700 rounded-xl p-8 text-center hover:border-amber-500 transition-colors cursor-pointer relative">
                    <input type="file" name="arquivo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                    <i class="fa-solid fa-file-arrow-up text-3xl text-slate-500 mb-2"></i>
                    <p class="text-sm text-slate-400">Clique ou arraste um arquivo</p>
                </div>

                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-3 rounded-xl transition-all">
                    Enviar para Drive
                </button>
            </form>
        </div>
    </div>

<script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            const content = document.getElementById(id + '-content');
            if (modal.classList.contains('opacity-0')) {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                setTimeout(() => { content.classList.remove('scale-95'); content.classList.add('scale-100'); }, 10);
            } else {
                content.classList.remove('scale-100'); content.classList.add('scale-95');
                setTimeout(() => { modal.classList.add('opacity-0', 'pointer-events-none'); }, 150);
            }
        }

        // --- LÓGICA DE UPLOAD ---
        const formUpload = document.getElementById('form-upload');
        if(formUpload) {
            const fileInput = formUpload.querySelector('input[type="file"]');
            const submitBtn = formUpload.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerText;

            // Atualizar texto ao selecionar arquivo
            fileInput.addEventListener('change', function() {
                if(this.files && this.files.length > 0) {
                    const fileName = this.files[0].name;
                    this.parentElement.querySelector('p').innerText = fileName;
                    this.parentElement.classList.add('border-amber-500', 'bg-amber-500/10');
                }
            });

            formUpload.addEventListener('submit', function(e) {
                e.preventDefault();

                // Bloquear UI
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Enviando para o Drive...';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                const formData = new FormData(this);

                fetch('php/action_upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        submitBtn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Sucesso!';
                        submitBtn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
                        submitBtn.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                        
                        // Recarregar para mostrar o arquivo
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        throw new Error(data.error || 'Erro desconhecido');
                    }
                })
                .catch(error => {
                    alert('Erro no upload: ' + error.message);
                    // Reset UI
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                });
            });
        }
    </script>
</body>
</html>

