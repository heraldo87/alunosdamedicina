<?php
// abrir_ws.php
session_start();
require_once 'php/config.php';

// --- 1. SEGURANÇA E SESSÃO ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// --- 2. CAPTURA ROBUSTA DO GET ---
// Obtém os dados da URL (ex: ?folder=X&id=Y)
$folderNameRaw = $_GET['folder'] ?? 'Workspace';
$driveIdRaw = $_GET['id'] ?? '';

// Limpeza de segurança
$folderName = strip_tags(urldecode($folderNameRaw));
$driveId = strip_tags($driveIdRaw);

// TRAVA DE SEGURANÇA: Se o ID estiver vazio, nem carrega o resto.
if (empty($driveId)) {
    // Redireciona ou mostra erro fatal
    die("ERRO CRÍTICO: ID da pasta não fornecido na URL.");
}

// --- 3. PREPARAÇÃO DA CONFIGURAÇÃO (PHP -> JS) ---
$appConfig = [
    'folderId' => $driveId,       // Variável direta e simples
    'folderName' => $folderName,
    'user' => [
        'id' => $_SESSION['id'] ?? 0,
        'name' => $_SESSION['username'] ?? 'Usuario',
        'email' => $_SESSION['email'] ?? ''
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace: <?php echo htmlspecialchars($folderName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    </style>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand: { dark: '#0b0f1a', primary: '#0284c7', surface: '#1e293b' } } } }
        }
    </script>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <header class="pt-10 pb-6 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/50 bg-brand-dark/95 sticky top-0 z-20 backdrop-blur-sm">
            <div>
                <a href="repositorio.php" class="text-xs font-bold text-slate-500 hover:text-brand-primary uppercase tracking-wider mb-2 inline-flex items-center gap-1 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i> Voltar ao Repositório
                </a>
                <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-brands fa-google-drive text-emerald-500"></i>
                    <?php echo htmlspecialchars($folderName); ?>
                </h1>
                <p class="text-[10px] text-slate-600 font-mono mt-1">
                    ID Verificado: <span class="text-emerald-500 font-bold"><?php echo htmlspecialchars($driveId); ?></span>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="fetchFiles()" class="text-slate-400 hover:text-white p-2 transition-colors" title="Atualizar Lista">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button onclick="toggleModal('uploadModal')" class="bg-brand-primary hover:bg-sky-600 text-white font-bold py-2.5 px-6 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-sky-500/20 hover:shadow-sky-500/40 active:scale-95">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Novo Arquivo</span>
                </button>
            </div>
        </header>

        <div class="px-6 md:px-12 py-8 flex-1">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 px-4">
                <div class="w-1/2">Nome do Arquivo</div>
                <div class="hidden md:block w-1/4">Tipo</div>
                <div class="w-1/4 text-right">Ações</div>
            </div>

            <div id="fileListContainer" class="space-y-2">
                </div>

            <div id="emptyState" class="hidden flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-800 rounded-[2rem] bg-slate-900/20 mt-4">
                <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-folder-open text-slate-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Pasta Vazia</h3>
                <p class="text-slate-500 text-sm mt-1">Nenhum arquivo encontrado.</p>
            </div>
        </div>
    </main>

    <div id="uploadModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" onclick="toggleModal('uploadModal')"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto pointer-events-none">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-brand-surface border border-slate-700 text-left shadow-2xl transition-all sm:w-full sm:max-w-lg pointer-events-auto">
                    <div class="bg-brand-surface px-4 pb-4 pt-5 sm:p-6">
                        <h3 class="text-xl font-bold text-white mb-4">Upload para Drive</h3>
                        <div id="dropZone" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-slate-600 rounded-xl bg-slate-800/50 hover:bg-slate-800 cursor-pointer transition-all">
                            <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-500 mb-3"></i>
                            <p class="text-sm text-slate-400">Clique ou arraste arquivos aqui</p>
                            <input id="fileInput" type="file" class="hidden" />
                        </div>
                        <div id="filePreview" class="hidden mt-4 bg-slate-800 rounded-lg p-3 border border-slate-700">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-file-lines text-brand-primary text-xl"></i>
                                <p id="fileName" class="text-sm font-bold text-white truncate flex-1">arquivo.pdf</p>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-1.5 mt-3">
                                <div id="progressBar" class="bg-brand-primary h-1.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p id="progressText" class="text-[10px] text-right text-slate-400 mt-1">0%</p>
                        </div>
                    </div>
                    <div class="bg-slate-900/50 px-4 py-3 flex flex-row-reverse gap-2">
                        <button type="button" id="btnEnviar" disabled onclick="uploadFile()" class="rounded-xl bg-brand-primary px-5 py-2 text-sm font-bold text-white hover:bg-sky-600 disabled:opacity-50 transition-all shadow-lg shadow-brand-primary/20">Enviar Arquivo</button>
                        <button type="button" onclick="toggleModal('uploadModal')" class="rounded-xl border border-slate-600 px-5 py-2 text-sm font-bold text-slate-300 hover:bg-slate-800 transition-all">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- 1. INSTANCIAÇÃO DA CONFIGURAÇÃO (PHP -> JS) ---
        // Aqui garantimos que a variável foi criada
        const APP_CONFIG = <?php echo json_encode($appConfig); ?>;

        // DEBUG VISUAL NO CONSOLE
        console.group("MEDINFOCUS DEBUG SYSTEM");
        console.log("1. Configuração recebida do PHP:", APP_CONFIG);
        console.log("2. ID da Pasta:", APP_CONFIG.folderId);
        console.groupEnd();

        let selectedFile = null;

        document.addEventListener('DOMContentLoaded', () => {
            fetchFiles();
        });

        // --- 2. CHAMADA AO PROXY ---
       // --- FETCH FILES (ADAPTADO PARA O NOVO RETORNO DO WEBHOOK) ---
        async function fetchFiles() {
            const container = document.getElementById('fileListContainer');
            const emptyState = document.getElementById('emptyState');
            
            // Verificação de segurança
            if (!APP_CONFIG.folderId) {
                console.error("ERRO: ID da pasta não identificado.");
                return;
            }

            // Skeleton Loading (Mantém a animação enquanto carrega)
            container.innerHTML = `
                ${Array(3).fill(0).map(() => `
                    <div class="flex items-center justify-between p-4 bg-slate-800/30 border border-slate-800 rounded-xl animate-pulse">
                        <div class="flex items-center gap-4 w-1/2"><div class="w-10 h-10 bg-slate-700 rounded-lg"></div><div class="h-4 bg-slate-700 rounded w-3/4"></div></div>
                        <div class="hidden md:block w-1/4 h-4 bg-slate-700 rounded"></div>
                        <div class="w-1/4 flex justify-end gap-2"><div class="w-8 h-8 bg-slate-700 rounded-lg"></div></div>
                    </div>`).join('')}
            `;
            emptyState.classList.add('hidden');

            try {
                // Payload para o Proxy
                const payload = {
                    folderId: APP_CONFIG.folderId,      
                    folderName: APP_CONFIG.folderName,  
                    userData: APP_CONFIG.user           
                };

                const response = await fetch('api/proxy_listar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
                
                let files = await response.json();
                console.log("Resposta do Webhook:", files);

                // TRATAMENTO: Garante que é um array (mesmo que venha um objeto único)
                if (!Array.isArray(files)) {
                    // Se o n8n devolver um objeto único em vez de lista, transformamos em lista
                    files = files ? [files] : [];
                }

                container.innerHTML = '';

                if (files.length === 0) {
                    emptyState.classList.remove('hidden');
                    return;
                }

                files.forEach(file => {
                    // --- MAPEAMENTO DOS CAMPOS DO SEU WEBHOOK ---
                    const fileName = file.nome_arquivo || 'Sem Nome';
                    const fileMime = file.tipo_mime || 'application/octet-stream';
                    const dbId = file.id; // ID do banco de dados (ex: 10)
                    const driveId = file.google_file_id; // ID do Google (ex: 32 ou string longa)

                    // Ícone baseado no tipo
                    const iconClass = getIconForMimeType(fileMime);
                    
                    // Link: Como o webhook não traz link, tentamos montar ou deixamos vazio
                    // Se o google_file_id for o ID real do Drive, o link seria:
                    // const downloadLink = `https://drive.google.com/uc?id=${driveId}&export=download`;
                    const downloadLink = '#'; 
                    
                    const fileRow = document.createElement('div');
                    fileRow.className = "group flex items-center justify-between p-4 bg-brand-surface border border-slate-800 rounded-xl hover:border-brand-primary/50 hover:bg-slate-800 transition-all";
                    
                    fileRow.innerHTML = `
                        <div class="flex items-center gap-4 w-1/2 overflow-hidden">
                            <div class="w-10 h-10 flex-shrink-0 bg-slate-900 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-brand-primary transition-colors">
                                <i class="${iconClass} text-xl"></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-semibold text-slate-200 truncate group-hover:text-white" title="${fileName}">${fileName}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] bg-slate-800 text-slate-400 px-1.5 py-0.5 rounded border border-slate-700">ID: ${dbId}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="hidden md:block w-1/4 text-xs text-slate-500 truncate">
                            ${formatMimeType(fileMime)}
                        </div>

                        <div class="w-1/4 flex justify-end items-center gap-2">
                            <a href="${downloadLink}" ${downloadLink === '#' ? 'onclick="alert(\'Link indisponível neste momento.\'); return false;"' : 'target="_blank"'} class="p-2 text-slate-400 hover:text-emerald-400 hover:bg-emerald-400/10 rounded-lg transition-colors" title="Baixar">
                                <i class="fa-solid fa-download"></i>
                            </a>
                            
                            <button onclick="deleteFile('${dbId}')" class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-colors" title="Deletar">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(fileRow);
                });

            } catch (error) {
                console.error(error);
                container.innerHTML = '';
                emptyState.querySelector('h3').textContent = 'Erro ao carregar';
                emptyState.querySelector('p').textContent = 'Verifique o console para detalhes.';
                emptyState.classList.remove('hidden');
            }
        }

        // --- UTILITÁRIOS ---
        function deleteFile(id) { 
            if(confirm('Deseja deletar?')) Toastify({ text: "Em breve.", style: { background: "#3b82f6" } }).showToast(); 
        }
        function getIconForMimeType(mime) {
            if (!mime) return 'fa-solid fa-file';
            if (mime.includes('pdf')) return 'fa-solid fa-file-pdf';
            if (mime.includes('image')) return 'fa-solid fa-file-image';
            if (mime.includes('word') || mime.includes('document')) return 'fa-solid fa-file-word';
            return 'fa-solid fa-file';
        }
        function formatMimeType(mime) { return mime ? mime.split('/').pop().toUpperCase() : 'ARQUIVO'; }
        function formatFileSize(bytes) {
            if (!bytes) return '';
            const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + ['B', 'KB', 'MB', 'GB'][i];
        }

        // --- UPLOAD ---
        function toggleModal(id) {
            const el = document.getElementById(id);
            el.classList.toggle('hidden');
            if(el.classList.contains('hidden')) clearFile();
        }
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');

        dropZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-brand-primary', 'bg-slate-800'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-brand-primary', 'bg-slate-800'));
        dropZone.addEventListener('drop', (e) => { e.preventDefault(); if(e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]); });

        function handleFile(file) {
            if(!file) return;
            selectedFile = file;
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('dropZone').classList.add('hidden');
            document.getElementById('filePreview').classList.remove('hidden');
            document.getElementById('btnEnviar').disabled = false;
        }

        function clearFile() {
            selectedFile = null;
            fileInput.value = '';
            document.getElementById('dropZone').classList.remove('hidden');
            document.getElementById('filePreview').classList.add('hidden');
            document.getElementById('btnEnviar').disabled = true;
            document.getElementById('btnEnviar').innerHTML = 'Enviar Arquivo';
        }

        function uploadFile() {
            if (!selectedFile) return;
            const formData = new FormData();
            formData.append('arquivo', selectedFile);
            formData.append('drive_id', APP_CONFIG.folderId); 
            formData.append('folder_name', APP_CONFIG.folderName);

            const xhr = new XMLHttpRequest();
            const btn = document.getElementById('btnEnviar');
            const txt = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Enviando...';
            btn.disabled = true;

            xhr.open('POST', 'api/upload_arquivo_ws.php', true);
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progressBar').style.width = pct + '%';
                    document.getElementById('progressText').textContent = pct + '%';
                }
            };
            xhr.onload = () => {
                btn.innerHTML = txt;
                btn.disabled = false;
                if (xhr.status === 200) {
                    const resp = JSON.parse(xhr.responseText);
                    if(resp.success) {
                        Toastify({ text: "Sucesso!", style: { background: "#10b981" } }).showToast();
                        toggleModal('uploadModal');
                        fetchFiles();
                    } else {
                        Toastify({ text: "Erro: " + resp.message, style: { background: "#f43f5e" } }).showToast();
                    }
                }
            };
            xhr.send(formData);
        }
    </script>
</body>
</html>