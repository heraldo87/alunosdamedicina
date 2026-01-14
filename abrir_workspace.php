<?php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$tipoUsuario = $_SESSION['user_type'] ?? 'aluno';
$workspaceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentFolderId = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : null;

// 2. BUSCAR DADOS DA WORKSPACE
$stmt = $pdo->prepare("SELECT * FROM workspaces WHERE id = ? AND status = 'ativo'");
$stmt->execute([$workspaceId]);
$workspace = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$workspace) {
    header('Location: repositorio.php');
    exit;
}

// 3. LOGICA DE BREADCRUMBS
$breadcrumbs = [];
$tempId = $currentFolderId;
while ($tempId != null) {
    $stmtFolder = $pdo->prepare("SELECT id, nome, parent_id FROM arquivos WHERE id = ?");
    $stmtFolder->execute([$tempId]);
    $folder = $stmtFolder->fetch(PDO::FETCH_ASSOC);
    if ($folder) {
        array_unshift($breadcrumbs, $folder);
        $tempId = $folder['parent_id'];
    } else {
        $tempId = null;
    }
}

// 4. BUSCAR CONTEÚDO DA PASTA ATUAL
$sql = "SELECT * FROM arquivos 
        WHERE workspace_id = :ws_id 
        AND parent_id " . ($currentFolderId ? "= :parent_id" : "IS NULL") . " 
        AND status = 'ativo' 
        ORDER BY tipo DESC, nome ASC";

$stmtItems = $pdo->prepare($sql);
$params = ['ws_id' => $workspaceId];
if ($currentFolderId) {
    $params['parent_id'] = $currentFolderId;
}
$stmtItems->execute($params);
$itens = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Função auxiliar ícones
function getFileIcon($ext) {
    $icons = [
        'pdf' => 'fa-file-pdf text-red-500',
        'doc' => 'fa-file-word text-blue-500',
        'docx' => 'fa-file-word text-blue-500',
        'xls' => 'fa-file-excel text-green-500',
        'xlsx' => 'fa-file-excel text-green-500',
        'ppt' => 'fa-file-powerpoint text-orange-500',
        'pptx' => 'fa-file-powerpoint text-orange-500',
        'jpg' => 'fa-file-image text-purple-500',
        'png' => 'fa-file-image text-purple-500',
        'zip' => 'fa-file-zipper text-yellow-500',
        'rar' => 'fa-file-zipper text-yellow-500',
    ];
    return $icons[strtolower($ext)] ?? 'fa-file text-slate-400';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($workspace['nome']); ?> - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .item-card { 
            transition: all 0.2s ease;
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(5px);
        }
        .item-card:hover { 
            background: rgba(30, 41, 59, 0.9);
            transform: translateY(-2px);
            border-color: #64748b;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        
        /* Ações aparecem no hover */
        .item-actions { opacity: 0; transition: opacity 0.2s; }
        .item-card:hover .item-actions { opacity: 1; }
        
        /* Modal Animation */
        .modal { transition: opacity 0.3s ease, visibility 0.3s ease; opacity: 0; visibility: hidden; }
        .modal.active { opacity: 1; visibility: visible; }
        .modal-content { transform: scale(0.95); transition: transform 0.3s ease; }
        .modal.active .modal-content { transform: scale(1); }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { brand: { dark: '#0b0f1a', primary: '#0284c7', surface: '#1e293b' } } }
            }
        }

        function deletarItem(id, nome, tipo) {
            if(confirm(`Tem certeza que deseja mover ${tipo === 'pasta' ? 'a pasta' : 'o arquivo'} "${nome}" para a lixeira?`)) {
                window.location.href = `api/deletar_item.php?id=${id}&workspace_id=<?php echo $workspaceId; ?>&redirect_folder=<?php echo $currentFolderId ?? ''; ?>`;
            }
        }

        // Funções dos Modais
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.toggle('active');
        }
    </script>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <header class="pt-8 pb-6 px-6 md:px-12 bg-slate-900/50 border-b border-slate-800 sticky top-0 z-10 backdrop-blur-md">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="<?php echo $currentFolderId ? 'abrir_workspace.php?id='.$workspaceId : 'repositorio.php'; ?>" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-800 text-slate-400 hover:bg-amber-500 hover:text-white transition-colors">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <h1 class="text-2xl font-bold text-white tracking-tight">
                            <?php echo htmlspecialchars($workspace['nome']); ?>
                        </h1>
                    </div>
                    
                    <div class="flex gap-2">
                            <button onclick="toggleModal('modalUpload')" class="bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition-all">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                            </button>
                            <button onclick="toggleModal('modalPasta')" class="bg-amber-500 hover:bg-amber-600 text-brand-dark text-sm font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-all shadow-lg shadow-amber-500/20">
                                <i class="fa-solid fa-folder-plus"></i> Nova Pasta
                            </button>
                        </div>
                    </div>

                <nav class="flex text-sm font-medium text-slate-500 overflow-x-auto whitespace-nowrap pb-2">
                    <a href="abrir_workspace.php?id=<?php echo $workspaceId; ?>" class="hover:text-amber-500 transition-colors flex items-center">
                        <i class="fa-solid fa-house mr-1"></i> Raiz
                    </a>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <span class="mx-2 text-slate-700">/</span>
                        <a href="abrir_workspace.php?id=<?php echo $workspaceId; ?>&folder_id=<?php echo $crumb['id']; ?>" class="hover:text-amber-500 transition-colors">
                            <?php echo htmlspecialchars($crumb['nome']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </header>

        <div class="px-6 md:px-12 py-8 pb-20">
            <?php if (empty($itens)): ?>
                <div class="flex flex-col items-center justify-center py-16 border border-dashed border-slate-800 rounded-[2rem] bg-slate-900/10">
                    <i class="fa-regular fa-folder-open text-5xl text-slate-700 mb-3"></i>
                    <p class="text-slate-500">Esta pasta está vazia.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($itens as $item): ?>
                        <?php 
                        if ($item['tipo'] === 'pasta') {
                            $link = "abrir_workspace.php?id=$workspaceId&folder_id=" . $item['id'];
                            $icon = "fa-folder text-amber-500 text-2xl";
                            $bgIcon = "bg-amber-500/10";
                        } else {
                            $link = $item['caminho_fisico'] ? $item['caminho_fisico'] : "#"; 
                            $icon = getFileIcon($item['extensao']) . " text-xl";
                            $bgIcon = "bg-slate-800";
                        }
                        ?>
                        <div class="item-card group relative p-4 rounded-xl border border-slate-800/50 flex items-center gap-4">
                            <a href="<?php echo $link; ?>" class="<?php echo $bgIcon; ?> w-12 h-12 rounded-lg flex items-center justify-center shrink-0">
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            </a>
                            <a href="<?php echo $link; ?>" class="flex-1 min-w-0">
                                <h4 class="text-slate-200 font-medium truncate text-sm" title="<?php echo htmlspecialchars($item['nome']); ?>">
                                    <?php echo htmlspecialchars($item['nome']); ?>
                                </h4>
                                <p class="text-slate-500 text-xs mt-0.5 flex items-center gap-2">
                                    <?php if ($item['tipo'] === 'pasta'): ?>
                                        <span>Pasta</span>
                                    <?php else: ?>
                                        <span><?php echo strtoupper($item['extensao'] ?? 'FILE'); ?></span>
                                        <span class="w-1 h-1 rounded-full bg-slate-700"></span>
                                        <span><?php $size = $item['tamanho_bytes']; echo $size > 1048576 ? round($size/1048576, 1).' MB' : round($size/1024, 0).' KB'; ?></span>
                                    <?php endif; ?>
                                </p>
                            </a>
                            <div class="item-actions flex items-center gap-2 ml-2">
                                <?php if ($item['tipo'] === 'arquivo'): ?>
                                    <a href="<?php echo $link; ?>" download class="w-8 h-8 rounded-full bg-slate-800 hover:bg-blue-600 text-slate-400 hover:text-white flex items-center justify-center transition-colors">
                                        <i class="fa-solid fa-download text-xs"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <button onclick="deletarItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['nome']); ?>', '<?php echo $item['tipo']; ?>')" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-red-500 text-slate-400 hover:text-white flex items-center justify-center transition-colors">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <div id="modalPasta" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="modal-content bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-xl font-bold text-white mb-4">Criar Nova Pasta</h3>
            <form action="api/criar_pasta.php" method="POST">
                <input type="hidden" name="workspace_id" value="<?php echo $workspaceId; ?>">
                <input type="hidden" name="parent_id" value="<?php echo $currentFolderId ?? ''; ?>">
                
                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-medium mb-2">Nome da Pasta</label>
                    <input type="text" name="nome_pasta" required autofocus
                           class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition-colors"
                           placeholder="Ex: Anatomia Clínica">
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="toggleModal('modalPasta')" class="px-4 py-2 text-slate-400 hover:text-white font-medium transition-colors">Cancelar</button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-2 px-6 rounded-lg transition-colors">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalUpload" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="modal-content bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-xl font-bold text-white mb-4">Enviar Arquivo</h3>
            <form action="api/upload_arquivo.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="workspace_id" value="<?php echo $workspaceId; ?>">
                <input type="hidden" name="parent_id" value="<?php echo $currentFolderId ?? ''; ?>">
                
                <div class="mb-4">
                    <label class="block text-slate-400 text-sm font-medium mb-2">Selecione o arquivo</label>
                    <input type="file" name="arquivo" required
                           class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-brand-dark hover:file:bg-amber-600 transition-colors">
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="toggleModal('modalUpload')" class="px-4 py-2 text-slate-400 hover:text-white font-medium transition-colors">Cancelar</button>
                    <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">Enviar</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>