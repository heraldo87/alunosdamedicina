<?php
/**
 * MEDINFOCUS - Visualização de Workspace
 * Interface para gestão de arquivos e pastas via n8n
 */

session_start();

// 1. SEGURANÇA: Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'php/config.php';

// 2. RECUPERAÇÃO DE PARÂMETROS
$drive_id = $_GET['drive_id'] ?? null;
if (!$drive_id) {
    header('Location: repositorio.php?erro=drive_id_ausente');
    exit;
}

// 3. CONSULTA AO n8n (Recuperando a lista de arquivos/pastas)
$n8n_url = "https://n8n.alunosdamedicina.com/webhook-test/a370fb4f-242a-4084-9358-45bab481fcb7";
$payload = [
    "acao" => "listar_arquivos_pasta",
    "google_drive_id" => $drive_id,
    "usuario_id" => $_SESSION['user_id']
];

$ch = curl_init($n8n_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$resposta = curl_exec($ch);
curl_close($ch);

$arquivos = json_decode($resposta, true) ?: [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Workspace - MEDINFOCUS</title>
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
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        .glass-card { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05); }
        .file-row:hover { background: rgba(2, 132, 199, 0.08); transition: all 0.2s; }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar">
        
        <header class="pt-12 pb-8 px-6 md:px-12">
            <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-4">
                <a href="repositorio.php" class="hover:text-brand-primary transition-colors">Repositório</a>
                <i class="fa-solid fa-chevron-right text-[8px] opacity-50"></i>
                <span class="text-brand-primary">Explorar Pasta</span>
            </nav>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                        <i class="fa-solid fa-folder-open text-amber-500"></i>
                        Arquivos do <span class="text-brand-primary">Workspace</span>
                    </h1>
                    <p class="text-slate-500 mt-2 font-mono text-[10px]">ID: <?php echo htmlspecialchars($drive_id); ?></p>
                </div>

                <div class="flex items-center gap-3">
                    <button onclick="document.getElementById('modalSubPasta').classList.remove('hidden')" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition-all border border-slate-700 flex items-center gap-2">
                        <i class="fa-solid fa-folder-plus text-amber-500"></i> Criar Pasta
                    </button>

                    <button onclick="document.getElementById('modalUpload').classList.remove('hidden')" class="px-5 py-2.5 bg-brand-primary hover:bg-sky-600 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-brand-primary/20 flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Novo Arquivo
                    </button>

                    <button onclick="location.reload()" class="w-10 h-10 bg-slate-900 text-slate-400 rounded-xl flex items-center justify-center hover:text-white border border-slate-800 transition-all">
                        <i class="fa-solid fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </header>

        <div class="px-6 md:px-12 pb-20">
            <div class="glass-card rounded-[2rem] overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-900/60 border-b border-slate-800">
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-500">Documento / Pasta</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-500 hidden sm:table-cell">Tipo</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-500 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php if (!empty($arquivos) && is_array($arquivos)): 
                            foreach ($arquivos as $file): 
                                $mime = $file['tipo_mime'] ?? '';
                                $isFolder = ($mime === 'application/vnd.google-apps.folder');
                                
                                $icon = $isFolder ? "fa-folder text-amber-500" : "fa-file-alt text-slate-400";
                                if (strpos($mime, 'pdf') !== false) $icon = "fa-file-pdf text-rose-500";
                                elseif (strpos($mime, 'image') !== false) $icon = "fa-file-image text-emerald-500";
                                elseif (strpos($mime, 'word') !== false) $icon = "fa-file-word text-blue-500";
                        ?>
                            <tr class="file-row group">
                                <td class="px-8 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-lg border border-slate-800 group-hover:border-brand-primary/30 transition-all">
                                            <i class="fa-solid <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-200 group-hover:text-brand-primary transition-colors">
                                                <?php echo htmlspecialchars($file['nome_arquivo']); ?>
                                            </span>
                                            <span class="text-[9px] font-black text-slate-600 uppercase tracking-tighter">ID: <?php echo substr($file['google_file_id'], 0, 10); ?>...</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-4 hidden sm:table-cell">
                                    <span class="px-2 py-1 bg-slate-800 rounded text-[9px] font-bold text-slate-500 uppercase">
                                        <?php echo $isFolder ? 'PASTA' : (explode('/', $mime)[1] ?? 'FILE'); ?>
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <?php if($isFolder): ?>
                                        <a href="workspace_view.php?drive_id=<?php echo $file['google_file_id']; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-amber-500 text-slate-400 hover:text-white rounded-lg text-[10px] font-black uppercase transition-all">
                                            <i class="fa-solid fa-folder-open"></i> Abrir
                                        </a>
                                    <?php else: ?>
                                        <a href="https://drive.google.com/uc?id=<?php echo $file['google_file_id']; ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-brand-primary text-slate-400 hover:text-white rounded-lg text-[10px] font-black uppercase transition-all">
                                            <i class="fa-solid fa-eye"></i> Visualizar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; 
                        else: ?>
                            <tr>
                                <td colspan="3" class="px-8 py-24 text-center text-slate-500 text-xs">
                                    <i class="fa-solid fa-folder-open text-3xl mb-3 block opacity-20"></i>
                                    Esta pasta está vazia. Comece criando uma subpasta ou subindo um arquivo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <div id="modalSubPasta" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-brand-surface w-full max-w-md rounded-[2.5rem] shadow-2xl border border-slate-700 overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Nova Pasta</h2>
                    <button onclick="document.getElementById('modalSubPasta').classList.add('hidden')" class="text-slate-500 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                <form action="api/criar_ws.php" method="POST" class="space-y-6">
                    <input type="hidden" name="parent_id" value="<?php echo $drive_id; ?>">
                    <input type="hidden" name="tipo_acao" value="subpasta">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nome da Subpasta</label>
                        <input type="text" name="nome_ws" required placeholder="Ex: Aulas Práticas" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-brand-primary outline-none transition-all placeholder-slate-600">
                    </div>
                    <button type="submit" class="w-full py-4 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-folder-plus"></i> Criar Subpasta
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="modalUpload" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-brand-surface w-full max-w-md rounded-[2.5rem] shadow-2xl border border-slate-700 overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Upload de Ficheiro</h2>
                    <button onclick="document.getElementById('modalUpload').classList.add('hidden')" class="text-slate-500 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                <form action="api/upload_files.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="parent_id" value="<?php echo $drive_id; ?>">
                    <div class="border-2 border-dashed border-slate-700 rounded-2xl p-8 text-center hover:border-brand-primary transition-all cursor-pointer group" onclick="document.getElementById('fileInput').click()">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-600 group-hover:text-brand-primary mb-3"></i>
                        <p class="text-sm text-slate-400">Clique para selecionar ou arraste o ficheiro</p>
                        <input type="file" id="fileInput" name="arquivo_upload" class="hidden" onchange="updateFileName(this)">
                        <span id="fileNameDisplay" class="block mt-2 text-xs text-brand-primary font-bold"></span>
                    </div>
                    <button type="submit" class="w-full py-4 bg-brand-primary text-white font-bold rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check"></i> Iniciar Upload
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const display = document.getElementById('fileNameDisplay');
            display.textContent = input.files[0] ? input.files[0].name : '';
        }
    </script>

</body>
</html>