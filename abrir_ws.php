<?php
// abrir_ws.php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA E VALIDAÇÃO
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Captura segura dos parâmetros da URL
// Ex: abrir_ws.php?folder=Patologia&id=1A2b3C...
$folderName = isset($_GET['folder']) ? strip_tags(urldecode($_GET['folder'])) : 'Workspace';
$driveId = isset($_GET['id']) ? strip_tags($_GET['id']) : '';

// Se não tiver ID, volta para o repositório (segurança)
if (empty($driveId)) {
    header('Location: repositorio.php?erro=id_invalido');
    exit;
}

$tituloPagina = "Workspace: " . htmlspecialchars($folderName);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tituloPagina; ?> - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
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
                <p class="text-[10px] text-slate-600 font-mono mt-1">ID Drive: <?php echo htmlspecialchars($driveId); ?></p>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="toggleModal('uploadModal')" class="bg-brand-primary hover:bg-sky-600 text-white font-bold py-2.5 px-6 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-sky-500/20 hover:shadow-sky-500/40 active:scale-95">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Novo Arquivo</span>
                </button>
            </div>
        </header>

        <div class="px-6 md:px-12 py-8 flex-1" id="fileListContainer">
            
            <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-800 rounded-[2rem] bg-slate-900/20">
                <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-folder-open text-slate-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-400">Pasta Conectada</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-md text-center">
                    Os arquivos desta pasta no Google Drive aparecerão aqui. <br>
                    <span class="text-xs opacity-50">(Funcionalidade de listagem em desenvolvimento...)</span>
                </p>
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
                        <button type="button" id="btnEnviar" disabled onclick="uploadFile()" class="rounded-xl bg-brand-primary px-5 py-2 text-sm font-bold text-white hover:bg-sky-600 disabled:opacity-50 transition-all shadow-lg shadow-brand-primary/20">
                            Enviar Arquivo
                        </button>
                        <button type="button" onclick="toggleModal('uploadModal')" class="rounded-xl border border-slate-600 px-5 py-2 text-sm font-bold text-slate-300 hover:bg-slate-800 transition-all">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- CONFIGURAÇÃO E VARIÁVEIS ---
        // Aqui passamos os dados do PHP para o JS de forma segura
        const currentDriveId = <?php echo json_encode($driveId); ?>;
        const currentFolderName = <?php echo json_encode($folderName); ?>;
        let selectedFile = null;

        // --- FUNÇÕES DE INTERFACE ---
        function toggleModal(id) {
            const el = document.getElementById(id);
            el.classList.toggle('hidden');
            // Se fechou o modal, limpa o arquivo selecionado
            if(el.classList.contains('hidden')) clearFile();
        }

        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');

        // Eventos de Drag & Drop
        dropZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));
        
        dropZone.addEventListener('dragover', (e) => { 
            e.preventDefault(); 
            dropZone.classList.add('border-brand-primary', 'bg-slate-800'); 
        });
        
        dropZone.addEventListener('dragleave', () => { 
            dropZone.classList.remove('border-brand-primary', 'bg-slate-800'); 
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-brand-primary', 'bg-slate-800');
            if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
        });

        function handleFile(file) {
            if(!file) return;
            selectedFile = file;
            document.getElementById('fileName').textContent = file.name;
            
            // Troca visual: esconde dropzone, mostra preview
            document.getElementById('dropZone').classList.add('hidden');
            document.getElementById('filePreview').classList.remove('hidden');
            document.getElementById('btnEnviar').disabled = false;
        }

        function clearFile() {
            selectedFile = null;
            fileInput.value = '';
            document.getElementById('dropZone').classList.remove('hidden');
            document.getElementById('filePreview').classList.add('hidden');
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressText').textContent = '0%';
            document.getElementById('btnEnviar').disabled = true;
            document.getElementById('btnEnviar').innerHTML = 'Enviar Arquivo';
        }

        // --- LÓGICA DE UPLOAD (AJAX) ---
        function uploadFile() {
            if (!selectedFile) return;

            // Prepara o pacote para envio
            const formData = new FormData();
            formData.append('arquivo', selectedFile);
            
            // É AQUI QUE O MÁGICA ACONTECE:
            // Anexamos o ID do Drive para o PHP saber onde salvar
            formData.append('drive_id', currentDriveId); 
            formData.append('folder_name', currentFolderName);

            const xhr = new XMLHttpRequest();
            
            // Feedback Visual de Carregando
            const btn = document.getElementById('btnEnviar');
            const originalBtnText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Enviando...';
            btn.disabled = true;

            xhr.open('POST', 'api/upload_arquivo_ws.php', true);

            // Barra de Progresso
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progressBar').style.width = pct + '%';
                    document.getElementById('progressText').textContent = pct + '%';
                }
            };

            // Retorno do Servidor
            xhr.onload = () => {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;

                if (xhr.status === 200) {
                    try {
                        const resp = JSON.parse(xhr.responseText);
                        if (resp.success) {
                            Toastify({ 
                                text: "Sucesso! Arquivo enviado ao Drive.", 
                                duration: 3000,
                                gravity: "top", 
                                position: "right",
                                style: { background: "#10b981" } 
                            }).showToast();
                            toggleModal('uploadModal');
                        } else {
                            throw new Error(resp.message || 'Erro desconhecido');
                        }
                    } catch (e) {
                        Toastify({ 
                            text: "Erro: " + e.message, 
                            duration: 4000,
                            style: { background: "#f43f5e" } 
                        }).showToast();
                    }
                } else {
                    Toastify({ 
                        text: "Erro de conexão com o servidor.", 
                        style: { background: "#f43f5e" } 
                    }).showToast();
                }
            };

            xhr.onerror = () => {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
                Toastify({ text: "Erro de rede.", style: { background: "#f43f5e" } }).showToast();
            };

            xhr.send(formData);
        }
    </script>
</body>
</html>