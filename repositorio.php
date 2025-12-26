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

// 2. LÓGICA DO REPOSITÓRIO (SISTEMA DE ARQUIVOS)
$caminhoBase = 'repositorio/';

// Criar a pasta base caso não exista
if (!is_dir($caminhoBase)) {
    mkdir($caminhoBase, 0777, true);
}

// Escanear subpastas (cada pasta é um Workspace/Disciplina)
$workspaces = array_filter(glob($caminhoBase . '*'), 'is_dir');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repositório - MEDINFOCUS</title>
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
            border-color: #f59e0b; /* Cor Amber para combinar com o ícone de pasta */
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

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar">
        
        <header class="pt-12 pb-8 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-folder-tree text-amber-500"></i>
                    Repositório de <span class="text-amber-500">Arquivos</span>
                </h1>
                <p class="text-slate-500 mt-1 font-medium italic">Explore os materiais e disciplinas da sua turma.</p>
            </div>

            <?php if ($tipoUsuario === 'admin' || $tipoUsuario === 'representante'): ?>
                <button onclick="alert('Funcionalidade de criar pasta será implementada a seguir.')" class="bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-3 px-6 rounded-2xl flex items-center gap-2 transition-all shadow-lg shadow-amber-500/20">
                    <i class="fa-solid fa-plus-circle"></i>
                    Nova Disciplina
                </button>
            <?php endif; ?>
        </header>

        <div class="px-6 md:px-12 pb-20">
            <?php if (empty($workspaces)): ?>
                <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-800 rounded-[3rem]">
                    <i class="fa-solid fa-box-open text-6xl text-slate-700 mb-4"></i>
                    <p class="text-slate-500 font-medium">Nenhuma pasta ou disciplina encontrada no servidor.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php 
                    foreach ($workspaces as $workspace): 
                        $nomePasta = basename($workspace);
                        // Contar arquivos dentro da pasta (opcional)
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
                                <h3 class="text-white font-bold text-lg leading-tight group-hover:text-amber-500 transition-colors">
                                    <?php echo htmlspecialchars($nomePasta); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-1 font-medium">
                                    <i class="fa-solid fa-file-lines mr-1"></i> <?php echo $qtdArquivos; ?> arquivos disponíveis
                                </p>
                            </div>

                            <div class="mt-2 pt-4 border-t border-slate-800/50 flex items-center justify-between text-xs font-bold uppercase tracking-tighter">
                                <span class="text-slate-500">Acesso: <?php echo ($tipoUsuario === 'aluno' ? 'Leitura' : 'Total'); ?></span>
                                <i class="fa-solid fa-chevron-right text-slate-700 group-hover:text-amber-500 group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

</body>
</html>