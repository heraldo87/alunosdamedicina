<?php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA: Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$tipoUsuario = $_SESSION['user_type'] ?? 'aluno';
$nomeUsuario = $_SESSION['user_name'] ?? 'Usuário';

// 2. LÓGICA DO REPOSITÓRIO (WORKSPACES)
$caminhoBase = 'repositorio/';

// Criar a pasta base caso não exista
if (!is_dir($caminhoBase)) {
    mkdir($caminhoBase, 0777, true);
}

// Escanear subpastas (cada pasta é um Workspace)
$workspaces = array_filter(glob($caminhoBase . '*'), 'is_dir');
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
    <style>
        body { font-family: 'Inter', sans-serif; }
        .workspace-card { 
            transition: all 0.3s ease;
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(10px);
        }
        .workspace-card:hover { 
            transform: translateY(-5px);
            background: rgba(30, 41, 59, 0.8);
            border-color: #f59e0b; /* Cor Amber */
        }
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
        
        <header class="pt-12 pb-8 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-folder-tree text-amber-500"></i>
                    Workspaces <span class="text-amber-500">Colaborativos</span>
                </h1>
                <p class="text-slate-500 mt-1 font-medium italic">Explore materiais, grupos e disciplinas da turma.</p>
            </div>

            <button onclick="document.getElementById('modalNovaPasta').classList.remove('hidden')" class="bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-3 px-6 rounded-2xl flex items-center gap-2 transition-all shadow-lg shadow-amber-500/20">
                <i class="fa-solid fa-plus-circle"></i>
                Novo Workspace
            </button>
        </header>

        <div class="px-6 md:px-12 pb-20">
            <?php if (empty($workspaces)): ?>
                <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-800 rounded-[3rem]">
                    <i class="fa-solid fa-box-open text-6xl text-slate-700 mb-4"></i>
                    <p class="text-slate-500 font-medium">Nenhum workspace encontrado.</p>
                    <p class="text-slate-600 text-sm mt-2">Seja o primeiro a criar um grupo de estudos!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php 
                    foreach ($workspaces as $workspace): 
                        $nomePasta = basename($workspace);
                        // Conta arquivos ignorando . e ..
                        $qtdArquivos = count(array_diff(scandir($workspace), array('.', '..')));
                    ?>
                        <a href="abrir_pasta.php?folder=<?php echo urlencode($nomePasta); ?>" class="workspace-card group p-6 rounded-[2rem] border border-slate-800/50 flex flex-col gap-4">
                            <div class="flex items-start justify-between">
                                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300">
                                    <i class="fa-solid fa-folder text-2xl"></i>
                                </div>
                                <span class="text-[10px] font-black px-3 py-1 bg-slate-800 rounded-full text-slate-400 uppercase tracking-widest">Workspace</span>
                            </div>
                            
                            <div>
                                <h3 class="text-white font-bold text-lg leading-tight group-hover:text-amber-500 transition-colors truncate">
                                    <?php echo htmlspecialchars($nomePasta); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-1 font-medium">
                                    <i class="fa-solid fa-file-lines mr-1"></i> <?php echo $qtdArquivos; ?> arquivos
                                </p>
                            </div>

                            <div class="mt-2 pt-4 border-t border-slate-800/50 flex items-center justify-between text-xs font-bold uppercase tracking-tighter">
                                <span class="text-slate-500">Acesso: Total</span>
                                <i class="fa-solid fa-chevron-right text-slate-700 group-hover:text-amber-500 group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <div id="modalNovaPasta" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 p-8 rounded-3xl w-full max-w-md shadow-2xl relative animate-in fade-in zoom-in duration-300">
            <button onclick="document.getElementById('modalNovaPasta').classList.add('hidden')" class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
            
            <h2 class="text-2xl font-bold text-white mb-2">Novo Workspace</h2>
            <p class="text-slate-400 text-sm mb-6">Crie um espaço para compartilhar arquivos com sua turma.</p>
            
            <form action="criar_workspace.php" method="POST">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nome do Workspace</label>
                <input type="text" name="nome_pasta" required placeholder="Ex: Anatomia_GrupoA" 
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 mb-6 transition-all">
                
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('modalNovaPasta').classList.add('hidden')" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-3 bg-amber-500 hover:bg-amber-600 text-brand-dark rounded-xl font-bold transition shadow-lg shadow-amber-500/20">
                        Criar Pasta
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>