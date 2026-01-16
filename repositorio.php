<?php
// repositorio.php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA BÁSICA (Apenas login necessário)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$tipoUsuario = $_SESSION['user_type'] ?? 'aluno';
$usuarioId   = $_SESSION['user_id'] ?? 0;

// 2. BUSCAR WORKSPACES NO BANCO DE DADOS
// Traz apenas os workspaces onde o usuário tem permissão ou criou
try {
    $sql = "SELECT w.*, p.role 
            FROM workspaces w 
            JOIN workspace_permissions p ON w.id = p.workspace_id 
            WHERE p.user_id = :uid AND w.status = 'active'
            ORDER BY w.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $usuarioId]);
    $workspaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $workspaces = [];
    $erroDb = "Erro ao carregar disciplinas: " . $e->getMessage();
}
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
            border-color: #f59e0b;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        
        /* Modal Animation */
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
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
                    <i class="fa-solid fa-cloud text-amber-500"></i>
                    Repositório <span class="text-amber-500">Drive</span>
                </h1>
                <p class="text-slate-500 mt-1 font-medium italic">Materiais sincronizados via Google Cloud.</p>
            </div>

            <button onclick="toggleModal('modal-novo-workspace')" class="bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-3 px-6 rounded-2xl flex items-center gap-2 transition-all shadow-lg shadow-amber-500/20">
                <i class="fa-solid fa-plus-circle"></i>
                Nova Disciplina
            </button>
        </header>

        <div class="px-6 md:px-12 pb-20">
            <?php if (isset($erroDb)): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-4 rounded-xl mb-6">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?php echo $erroDb; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($workspaces)): ?>
                <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-800 rounded-[3rem]">
                    <div class="w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-folder-open text-4xl text-slate-600"></i>
                    </div>
                    <p class="text-slate-500 font-bold text-lg">Nenhuma disciplina encontrada.</p>
                    <p class="text-slate-600 text-sm mt-1">Crie um novo workspace para começar.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($workspaces as $ws): ?>
                        <a href="abrir_pasta.php?id=<?php echo $ws['id']; ?>" class="workspace-card group p-6 rounded-[2rem] border border-slate-800/50 flex flex-col gap-4 cursor-pointer">
                            <div class="flex items-start justify-between">
                                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-lg shadow-amber-500/5">
                                    <i class="fa-brands fa-google-drive text-2xl"></i>
                                </div>
                                <span class="text-[10px] font-black px-3 py-1 bg-slate-800 rounded-full text-slate-400 uppercase tracking-widest">
                                    <?php echo htmlspecialchars($ws['role']); ?>
                                </span>
                            </div>
                            
                            <div>
                                <h3 class="text-white font-bold text-lg leading-tight group-hover:text-amber-500 transition-colors truncate">
                                    <?php echo htmlspecialchars($ws['name']); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-1 font-medium line-clamp-2">
                                    <?php echo !empty($ws['description']) ? htmlspecialchars($ws['description']) : 'Sem descrição definida.'; ?>
                                </p>
                            </div>

                            <div class="mt-2 pt-4 border-t border-slate-800/50 flex items-center justify-between text-xs font-bold uppercase tracking-tighter">
                                <span class="text-slate-500">ID: #<?php echo $ws['id']; ?></span>
                                <i class="fa-solid fa-chevron-right text-slate-700 group-hover:text-amber-500 group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div id="modal-novo-workspace" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-black/80 backdrop-blur-sm" onclick="toggleModal('modal-novo-workspace')"></div>
        
        <div class="modal-container bg-brand-surface w-11/12 md:max-w-md mx-auto rounded-[2rem] shadow-2xl z-50 overflow-y-auto border border-slate-700 transform transition-all scale-95" id="modal-content">
            
            <div class="py-6 px-8 text-left px-6">
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold text-white">Nova Disciplina</p>
                    <div class="cursor-pointer z-50 text-slate-400 hover:text-white" onclick="toggleModal('modal-novo-workspace')">
                        <i class="fa-solid fa-times text-xl"></i>
                    </div>
                </div>

                <form id="form-criar-workspace" class="space-y-4 mt-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">Nome da Matéria/Área</label>
                        <input type="text" name="nome_workspace" placeholder="Ex: Anatomia Patológica" required
                               class="w-full bg-slate-900/50 text-white border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all placeholder-slate-600">
                    </div>
                    
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">Descrição (Opcional)</label>
                        <textarea name="descricao" rows="3" placeholder="Ex: Material do semestre 2024.1..."
                                  class="w-full bg-slate-900/50 text-white border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all placeholder-slate-600 resize-none"></textarea>
                    </div>

                    <div class="pt-4">
                        <button type="submit" id="btn-submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-amber-500/20 transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2">
                            <span id="btn-text">Criar e Sincronizar</span>
                            <i id="btn-icon" class="fa-solid fa-rocket"></i>
                            <i id="btn-loading" class="fa-solid fa-circle-notch fa-spin hidden"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(modalID){
            const modal = document.getElementById(modalID);
            const content = document.getElementById('modal-content');
            if(modal.classList.contains('opacity-0')){
                modal.classList.remove('opacity-0', 'pointer-events-none');
                document.body.classList.add('modal-active');
                setTimeout(() => { content.classList.remove('scale-95'); content.classList.add('scale-100'); }, 10);
            } else {
                content.classList.remove('scale-100'); content.classList.add('scale-95');
                setTimeout(() => { modal.classList.add('opacity-0', 'pointer-events-none'); document.body.classList.remove('modal-active'); }, 150);
            }
        }

        document.getElementById('form-criar-workspace').addEventListener('submit', function(e) {
            e.preventDefault();
            const btnText = document.getElementById('btn-text');
            const btnIcon = document.getElementById('btn-icon');
            const btnLoading = document.getElementById('btn-loading');
            const btn = document.getElementById('btn-submit');

            btnText.textContent = 'Contactando Google...';
            btnIcon.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            const formData = new FormData(this);

            fetch('php/action_criar_workspace.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    btnText.textContent = 'Sucesso!';
                    btnLoading.classList.add('hidden');
                    btnIcon.classList.remove('hidden');
                    btnIcon.className = 'fa-solid fa-check';
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            })
            .catch(error => {
                alert('Erro: ' + error.message);
                btnText.textContent = 'Tentar Novamente';
                btnIcon.classList.remove('hidden', 'fa-check');
                btnIcon.className = 'fa-solid fa-rocket';
                btnLoading.classList.add('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            });
        });
    </script>
</body>
</html>