<?php
/**
 * MEDINFOCUS - Repositório de Arquivos (Workspaces)
 * Interface para gestão de pastas integradas ao Google Drive via n8n
 */

session_start();

// 1. SEGURANÇA: Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'php/config.php';
// Importante: lista_ws.php agora define a variável $workspaces_remotos vinda do n8n
require_once 'api/lista_ws.php';

$nomeUsuario = $_SESSION['user_name'] ?? 'Usuário';
$userId = $_SESSION['user_id'] ?? 0;
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
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        .glass-card { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar">
        
        <header class="pt-12 pb-8 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Repositório de <span class="text-brand-primary">Workspaces</span></h1>
                <p class="text-slate-500 mt-1 font-medium italic uppercase text-xs tracking-widest">Sincronizado via MedInFocus Cloud</p>
            </div>
            
            <button onclick="document.getElementById('modalWS').classList.remove('hidden')" class="flex items-center justify-center gap-2 px-6 py-3 bg-brand-primary hover:bg-sky-600 text-white font-bold rounded-2xl transition-all shadow-lg shadow-brand-primary/20">
                <i class="fa-solid fa-folder-plus"></i>
                Criar Workspace
            </button>
        </header>

        <div class="px-6 md:px-12 pb-20">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                
                <?php
                // Verificamos se a API lista_ws.php retornou dados válidos do n8n
                if (!empty($workspaces_remotos) && is_array($workspaces_remotos)):
                    foreach ($workspaces_remotos as $ws): ?>
                        
                        <div class="glass-card p-6 rounded-[2rem] group hover:border-brand-primary/50 transition-all cursor-pointer" 
                             onclick="window.location.href='workspace_view.php?drive_id=<?php echo $ws['google_drive_id']; ?>'">
                            
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                    <i class="fa-solid fa-folder-open text-2xl"></i>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="px-2 py-1 rounded-lg bg-emerald-500/10 text-emerald-500 text-[10px] font-bold uppercase tracking-tighter">Sincronizado</span>
                                    <span class="text-[9px] text-slate-600 mt-1 font-bold"><?php echo strtoupper($ws['nivel'] ?? 'Membro'); ?></span>
                                </div>
                            </div>

                            <h3 class="text-white font-bold text-lg mb-1"><?php echo htmlspecialchars($ws['nome']); ?></h3>
                            <p class="text-slate-500 text-sm line-clamp-2"><?php echo htmlspecialchars($ws['descricao'] ?: 'Pasta de documentos acadêmicos.'); ?></p>
                            
                            <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-[10px] font-black uppercase tracking-tighter text-slate-500">
                                <span>Aceder Arquivos</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-brand-primary group-hover:mr-1 transition-all">Abrir</span>
                                    <i class="fa-solid fa-chevron-right text-brand-primary"></i>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; 
                else: ?>
                    <div class="col-span-full py-20 flex flex-col items-center justify-center text-center">
                        <div class="w-24 h-24 bg-slate-800/30 rounded-full flex items-center justify-center mb-6">
                            <i class="fa-solid fa-folder-tree text-4xl text-slate-700"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white mb-2">Nenhum Workspace encontrado</h2>
                        <p class="text-slate-500 max-w-xs mx-auto mb-8">Não localizamos pastas vinculadas ao seu perfil. Crie uma nova para começar.</p>
                        <button onclick="document.getElementById('modalWS').classList.remove('hidden')" class="px-8 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold transition-all">
                            Criar Primeiro Workspace
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <div id="modalWS" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-brand-surface w-full max-w-md rounded-[2.5rem] shadow-2xl border border-slate-700 overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Novo Workspace</h2>
                    <button onclick="document.getElementById('modalWS').classList.add('hidden')" class="text-slate-500 hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                
                <p class="text-slate-400 text-sm mb-8 leading-relaxed">Defina o nome da disciplina ou projeto. O MedInFocus criará a estrutura no Google Drive para você.</p>
                
                <form action="api/criar_ws.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nome do Workspace</label>
                        <input type="text" name="nome_ws" required placeholder="Ex: Patologia Especial" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all placeholder-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Descrição (Opcional)</label>
                        <textarea name="desc_ws" rows="3" placeholder="Resumos, provas e materiais..." 
                                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all placeholder-slate-600"></textarea>
                    </div>
                    
                    <div class="flex gap-4 pt-2">
                        <button type="submit" class="flex-1 py-4 bg-brand-primary text-white font-bold rounded-2xl shadow-lg shadow-brand-primary/20 hover:bg-sky-600 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i>
                            Confirmar Criação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>