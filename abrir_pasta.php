<?php
session_start();
// Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Recupera dados da URL
$workspaceId = $_GET['id'] ?? 0;
$workspaceNome = $_GET['name'] ?? 'Pasta Sem Nome';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($workspaceNome); ?> - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0f172a', primary: '#0284c7', surface: '#1e293b', accent: '#f59e0b' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        /* Zona de Drop */
        .drop-zone {
            transition: all 0.3s ease;
            border: 2px dashed #334155;
        }
        .drop-zone.dragover {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            transform: scale(1.01);
        }
    </style>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <header class="pt-8 pb-4 px-8 border-b border-slate-800/50 bg-brand-dark/95 z-20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="repositorio.php" class="p-2 rounded-xl hover:bg-slate-800 text-slate-400 hover:text-white transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                        <span>Workspace</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <span class="text-brand-primary">Arquivos</span>
                    </div>
                    <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
                        <i class="fa-regular fa-folder-open text-brand-accent"></i>
                        <?php echo htmlspecialchars($workspaceNome); ?>
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="atualizarLista()" class="p-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors" title="Atualizar">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <label for="fileInput" class="cursor-pointer bg-brand-primary hover:bg-sky-500 text-white font-bold py-2.5 px-5 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-sky-900/20">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Upload</span>
                </label>
                <input type="file" id="fileInput" class="hidden" multiple onchange="handleFiles(this.files)">
            </div>
        </header>

        <div id="dropZone" class="flex-1 overflow-y-auto p-6 md:p-8 drop-zone relative">
            
            <div id="dragOverlay" class="absolute inset-0 bg-brand-dark/90 z-50 hidden flex-col items-center justify-center pointer-events-none">
                <i class="fa-solid fa-cloud-arrow-up text-6xl text-brand-accent animate-bounce mb-4"></i>
                <h3 class="text-2xl font-bold text-white">Solte os arquivos aqui</h3>
            </div>

            <div id="loading" class="hidden flex justify-center py-20">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-brand-primary"></i>
            </div>

            <div id="fileListContainer" class="hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs font-bold text-slate-500 uppercase border-b border-slate-800/50">
                            <th class="py-3 px-4 w-1/2">Nome</th>
                            <th class="py-3 px-4">Tamanho</th>
                            <th class="py-3 px-4">Modificado</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="fileListBody" class="text-sm font-medium text-slate-300">
                        </tbody>
                </table>
            </div>

            <div id="emptyState" class="hidden flex flex-col items-center justify-center py-20 opacity-60">
                <div class="w-20 h-20 bg-brand-surface rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-file-circle-plus text-3xl text-slate-600"></i>
                </div>
                <p class="text-slate-500 mb-4">Esta pasta está vazia.</p>
                <button onclick="document.getElementById('fileInput').click()" class="text-brand-accent font-bold hover:underline">Faça upload de um arquivo</button>
            </div>

        </div>
    </main>

    <script>
        const WORKSPACE_ID = <?php echo json_encode($workspaceId); ?>;
        
        // --- DRAG AND DROP ---
        const dropZone = document.getElementById('dropZone');
        const dragOverlay = document.getElementById('dragOverlay');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

        dropZone.addEventListener('dragenter', () => {
            dropZone.classList.add('dragover');
            dragOverlay.classList.remove('hidden');
            dragOverlay.classList.add('flex');
        });
        
        dropZone.addEventListener('dragleave', (e) => {
            // Só remove se sair do elemento pai
            if(e.target === dragOverlay || e.relatedTarget === null) {
                dropZone.classList.remove('dragover');
                dragOverlay.classList.add('hidden');
                dragOverlay.classList.remove('flex');
            }
        });

        dropZone.addEventListener('drop', (e) => {
            dropZone.classList.remove('dragover');
            dragOverlay.classList.add('hidden');
            dragOverlay.classList.remove('flex');
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        function handleFiles(files) {
            if(files.length > 0) {
                alert(`Prearando upload de ${files.length} arquivos... (API de Upload será implementada na próxima etapa)`);
                console.log(files);
            }
        }

        // --- LISTAGEM ---
        document.addEventListener('DOMContentLoaded', atualizarLista);

        async function atualizarLista() {
            const loading = document.getElementById('loading');
            const listContainer = document.getElementById('fileListContainer');
            const listBody = document.getElementById('fileListBody');
            const emptyState = document.getElementById('emptyState');

            loading.classList.remove('hidden');
            listContainer.classList.add('hidden');
            emptyState.classList.add('hidden');

            try {
                const response = await fetch(`php/api_listar_arquivos.php?workspace_id=${WORKSPACE_ID}`);
                const result = await response.json();

                loading.classList.add('hidden');

                if(!result.success) {
                    alert(result.message);
                    return;
                }

                if(result.data.length === 0) {
                    emptyState.classList.remove('hidden');
                    return;
                }

                listBody.innerHTML = '';
                result.data.forEach(arquivo => {
                    listBody.innerHTML += criarLinhaArquivo(arquivo);
                });
                listContainer.classList.remove('hidden');

            } catch (error) {
                console.error(error);
                loading.classList.add('hidden');
                alert('Erro ao carregar arquivos.');
            }
        }

        function criarLinhaArquivo(file) {
            // Ícones baseados na extensão (simples)
            let icon = 'fa-file';
            let color = 'text-slate-400';
            
            if(file.mimeType && file.mimeType.includes('pdf')) { icon = 'fa-file-pdf'; color = 'text-rose-500'; }
            else if(file.mimeType && file.mimeType.includes('image')) { icon = 'fa-file-image'; color = 'text-purple-500'; }
            else if(file.mimeType && file.mimeType.includes('folder')) { icon = 'fa-folder'; color = 'text-amber-500'; }

            return `
                <tr class="border-b border-slate-800/30 hover:bg-slate-800/40 transition-colors group">
                    <td class="py-3 px-4 flex items-center gap-3">
                        <i class="fa-solid ${icon} ${color} text-lg"></i>
                        <span class="text-white group-hover:text-brand-primary transition-colors">${file.name}</span>
                    </td>
                    <td class="py-3 px-4 text-slate-500 text-xs">${formatBytes(file.size)}</td>
                    <td class="py-3 px-4 text-slate-500 text-xs">-</td>
                    <td class="py-3 px-4 text-right">
                        <button class="text-slate-500 hover:text-white p-2"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                    </td>
                </tr>
            `;
        }

        function formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 B';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
        }
    </script>
</body>
</html>