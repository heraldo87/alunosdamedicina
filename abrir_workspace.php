<?php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA E VALIDAÇÃO
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$workspace_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$current_folder_id = filter_input(INPUT_GET, 'folder');

if (!$workspace_id) {
    header('Location: repositorio.php');
    exit;
}

// 2. BUSCAR INFO DO WORKSPACE
// Trazemos tudo (*), inclusive o google_drive_id
$stmt = $pdo->prepare("SELECT * FROM workspaces WHERE id = ? AND status = 'ativo'");
$stmt->execute([$workspace_id]);
$workspace = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$workspace) {
    die("Workspace não encontrado ou inativo.");
}

// 3. SISTEMA DE BREADCRUMBS
$breadcrumbs = [];
if ($current_folder_id) {
    $temp_id = $current_folder_id;
    while ($temp_id != null) {
        $stmtPath = $pdo->prepare("SELECT id, nome_arquivo, parent_id FROM arquivos WHERE id = ?");
        $stmtPath->execute([$temp_id]);
        $folder = $stmtPath->fetch(PDO::FETCH_ASSOC);
        if ($folder) {
            array_unshift($breadcrumbs, $folder);
            $temp_id = $folder['parent_id'];
        } else {
            break;
        }
    }
}

// 4. LISTAR ARQUIVOS
$sql = "SELECT * FROM arquivos 
        WHERE workspace_id = :ws_id 
        AND parent_id " . ($current_folder_id ? "= :parent" : "IS NULL") . " 
        AND status = 'ativo'
        ORDER BY tipo DESC, nome_arquivo ASC";

$stmt = $pdo->prepare($sql);
$params = [':ws_id' => $workspace_id];
if ($current_folder_id) {
    $params[':parent'] = $current_folder_id;
}
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getFileIcon($mime, $tipo) {
    if ($tipo === 'pasta') return '<i class="fa-solid fa-folder text-amber-400 text-3xl"></i>';
    if (strpos($mime, 'pdf') !== false) return '<i class="fa-solid fa-file-pdf text-rose-500 text-3xl"></i>';
    if (strpos($mime, 'image') !== false) return '<i class="fa-solid fa-file-image text-purple-500 text-3xl"></i>';
    return '<i class="fa-solid fa-file text-slate-400 text-3xl"></i>';
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        body { font-family: 'Inter', sans-serif; }
        .file-row:hover { background-color: rgba(30, 41, 59, 0.5); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0b0f1a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">
        <header class="bg-brand-dark/95 backdrop-blur z-20 border-b border-slate-800 px-8 py-5 flex justify-between items-center">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                    <a href="repositorio.php" class="hover:text-brand-primary transition-colors"><i class="fa-solid fa-house"></i></a>
                    <span>/</span>
                    <a href="abrir_workspace.php?id=<?php echo $workspace_id; ?>" class="hover:text-brand-primary font-bold text-slate-400">
                        <?php echo htmlspecialchars($workspace['nome']); ?>
                    </a>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <span>/</span>
                        <a href="abrir_workspace.php?id=<?php echo $workspace_id; ?>&folder=<?php echo $crumb['id']; ?>" class="hover:text-brand-primary text-slate-400">
                            <?php echo htmlspecialchars($crumb['nome_arquivo']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <?php echo empty($breadcrumbs) ? '<i class="fa-solid fa-box-archive text-brand-primary"></i>' : '<i class="fa-regular fa-folder-open text-amber-400"></i>'; ?>
                    <?php echo empty($breadcrumbs) ? 'Raiz do Workspace' : htmlspecialchars(end($breadcrumbs)['nome_arquivo']); ?>
                </h1>
            </div>

            <div class="flex gap-3">
                <button onclick="toggleModal('modal-create-folder')" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-medium transition-all flex items-center gap-2 border border-slate-700">
                    <i class="fa-solid fa-folder-plus"></i> <span class="hidden sm:inline">Nova Pasta</span>
                </button>
                <button onclick="document.getElementById('fileUploadInput').click()" class="px-4 py-2 rounded-lg bg-brand-primary hover:bg-sky-600 text-white font-bold transition-all flex items-center gap-2 shadow-lg shadow-brand-primary/20">
                    <i class="fa-solid fa-cloud-arrow-up"></i> <span class="hidden sm:inline">Upload Arquivo</span>
                </button>
                <input type="file" id="fileUploadInput" class="hidden" onchange="handleFileUpload(this)">
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
            <?php if (empty($items)): ?>
                <div class="h-full flex flex-col items-center justify-center text-slate-500 opacity-60">
                    <i class="fa-regular fa-folder-open text-6xl mb-4"></i>
                    <p class="text-lg">Esta pasta está vazia</p>
                    <p class="text-sm">Faça upload ou crie uma pasta para começar.</p>
                </div>
            <?php else: ?>
                <div class="bg-brand-surface rounded-2xl border border-slate-800 overflow-hidden">
                    <div class="grid grid-cols-12 gap-4 p-4 border-b border-slate-800 bg-slate-900/50 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <div class="col-span-6 sm:col-span-5">Nome</div>
                        <div class="col-span-3 hidden sm:block">Data</div>
                        <div class="col-span-2 hidden sm:block">Tamanho</div>
                        <div class="col-span-6 sm:col-span-2 text-right">Ações</div>
                    </div>
                    <?php foreach ($items as $item): ?>
                        <div class="file-row grid grid-cols-12 gap-4 p-4 border-b border-slate-800/50 items-center transition-colors group">
                            <div class="col-span-6 sm:col-span-5 flex items-center gap-4 overflow-hidden">
                                <div class="shrink-0 w-8 text-center"><?php echo getFileIcon($item['mime_type'], $item['tipo']); ?></div>
                                <div class="truncate">
                                    <?php if ($item['tipo'] === 'pasta'): ?>
                                        <a href="abrir_workspace.php?id=<?php echo $workspace_id; ?>&folder=<?php echo $item['id']; ?>" class="text-white font-medium hover:text-brand-primary truncate block"><?php echo htmlspecialchars($item['nome_arquivo']); ?></a>
                                    <?php else: ?>
                                        <span class="text-slate-200 font-medium truncate block"><?php echo htmlspecialchars($item['nome_arquivo']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-span-3 hidden sm:block text-sm text-slate-500"><?php echo date('d/m/Y H:i', strtotime($item['criado_em'])); ?></div>
                            <div class="col-span-2 hidden sm:block text-sm text-slate-500 font-mono">
                                <?php echo ($item['tipo'] === 'pasta') ? '-' : ($item['tamanho_bytes'] < 1024 ? $item['tamanho_bytes'] . ' B' : round($item['tamanho_bytes'] / 1024) . ' KB'); ?>
                            </div>
                            <div class="col-span-6 sm:col-span-2 flex justify-end gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                <button onclick="deletarItem('<?php echo $item['id']; ?>', '<?php echo htmlspecialchars($item['nome_arquivo']); ?>')" class="p-2 text-slate-400 hover:text-red-400 rounded-lg hover:bg-red-900/20"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="modal-create-folder" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="absolute w-full h-full bg-slate-900/90 backdrop-blur-sm" onclick="toggleModal('modal-create-folder')"></div>
        <div class="modal-container bg-slate-900 w-11/12 max-w-sm rounded-2xl shadow-2xl z-50 border border-slate-700 p-6 transform transition-all scale-95">
            <h3 class="text-lg font-bold text-white mb-4">Nova Pasta</h3>
            <input type="text" id="newFolderName" placeholder="Nome da pasta" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:border-brand-primary focus:outline-none mb-4">
            <div class="flex justify-end gap-2">
                <button onclick="toggleModal('modal-create-folder')" class="px-4 py-2 text-slate-400 hover:text-white">Cancelar</button>
                <button onclick="criarPasta()" class="px-4 py-2 bg-brand-primary rounded-lg text-white font-bold hover:bg-sky-500">Criar</button>
            </div>
        </div>
    </div>

    <script>
        const WORKSPACE_ID = <?php echo $workspace_id; ?>;
        // --- 1. CAPTURA DO DRIVE ID DO PHP ---
        const WORKSPACE_DRIVE_ID = "<?php echo $workspace['google_drive_id'] ?? ''; ?>"; 
        const PARENT_ID = <?php echo $current_folder_id ? $current_folder_id : 'null'; ?>;

        function toggleModal(id) {
            const modal = document.getElementById(id);
            const container = modal.querySelector('.modal-container');
            if (modal.classList.contains('opacity-0')) {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                container.classList.remove('scale-95'); container.classList.add('scale-100');
            } else {
                modal.classList.add('opacity-0', 'pointer-events-none');
                container.classList.remove('scale-100'); container.classList.add('scale-95');
            }
        }

        function handleFileUpload(input) {
            if (input.files.length === 0) return;
            
            const file = input.files[0];
            const formData = new FormData();
            formData.append('arquivo', file);
            formData.append('workspace_id', WORKSPACE_ID);
            // --- 2. ENVIO DO DRIVE ID PARA A API ---
            formData.append('workspace_drive_id', WORKSPACE_DRIVE_ID);
            if (PARENT_ID) formData.append('parent_id', PARENT_ID);

            const toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false });
            toast.fire({ icon: 'info', title: 'Enviando arquivo...', text: 'Aguarde o processamento.' });

            fetch('api/upload_file_ws.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success', title: 'Sucesso!', text: 'Arquivo enviado.',
                        background: '#1e293b', color: '#fff', confirmButtonColor: '#0284c7'
                    }).then(() => window.location.reload());
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Erro no Upload', text: err.message, background: '#1e293b', color: '#fff' });
            })
            .finally(() => {
                input.value = ''; 
            });
        }

        function criarPasta() {
            const nome = document.getElementById('newFolderName').value;
            if (!nome) return;
            fetch('api/criar_pasta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `workspace_id=${WORKSPACE_ID}&parent_id=${PARENT_ID || ''}&nome=${encodeURIComponent(nome)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) window.location.reload();
                else Swal.fire('Erro', data.message, 'error');
            });
        }

        function deletarItem(id, nome) {
            Swal.fire({
                title: 'Excluir?', text: `Deseja remover "${nome}"?`, icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não',
                background: '#1e293b', color: '#fff', confirmButtonColor: '#ef4444'
            }).then((res) => {
                if (res.isConfirmed) {
                    fetch('api/excluir_arquivo.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}`
                    }).then(() => window.location.reload());
                }
            });
        }
    </script>
</body>
</html>