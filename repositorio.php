<?php
session_start();
// Configuração de erros (desative display_errors em produção)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once 'php/config.php';

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$drive_id = $_SESSION['drive_id'] ?? 0;
$tipoUsuario = $_SESSION['user_type'] ?? 'aluno';

// 2. BUSCAR DADOS NO MYSQL (Refatorado para performance local)
$workspaces = [];

try {
    // Busca apenas workspaces ativos (respeitando o soft delete)
    // Ordena pelo mais recente
    $sql = "SELECT * FROM workspaces 
            WHERE status != 'inativo' 
            ORDER BY created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $workspaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Em caso de erro no banco, loga no servidor e mantêm a lista vazia
    error_log("Erro ao buscar workspaces: " . $e->getMessage());
    $workspaces = [];
}
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
        .workspace-card { 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(10px);
        }
        .workspace-card:hover { 
            transform: translateY(-5px);
            background: rgba(30, 41, 59, 0.8);
            border-color: #0284c7; 
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.3);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: hidden !important; }
    </style>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden selection:bg-brand-primary selection:text-white">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <header class="pt-12 pb-8 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-layer-group text-brand-primary"></i>
                    Meus <span class="text-brand-primary">Workspaces</span>
                </h1>
                <p class="text-slate-500 mt-2 font-medium">Gerencie seus projetos e materiais de estudo.</p>
            </div>

            <button onclick="toggleModal('modal-create-ws')" class="group bg-brand-primary hover:bg-sky-600 text-white font-bold py-3 px-6 rounded-xl flex items-center gap-3 transition-all shadow-lg shadow-brand-primary/25 hover:shadow-brand-primary/40">
                <div class="bg-white/20 rounded-lg p-1 group-hover:rotate-90 transition-transform duration-300">
                    <i class="fa-solid fa-plus text-sm"></i>
                </div>
                <span>Novo Workspace</span>
            </button>
        </header>

        <div class="px-6 md:px-12 pb-20">
            
            <?php if (empty($workspaces)): ?>
                <div class="flex flex-col items-center justify-center py-24 border-2 border-dashed border-slate-800 rounded-[2rem] bg-slate-900/20">
                    <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center mb-6 animate-pulse">
                        <i class="fa-solid fa-folder-open text-3xl text-slate-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Nenhum Workspace encontrado</h3>
                    <p class="text-slate-500 mt-2 text-center max-w-md">
                        Não encontramos registros ativos.<br>
                        Utilize o botão acima para criar seu primeiro espaço.
                    </p>
                    <button onclick="toggleModal('modal-create-ws')" class="mt-4 bg-brand-primary px-4 py-2 rounded text-white font-bold hover:bg-sky-500 transition-colors">
                        Criar Agora
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($workspaces as $ws): ?>
                        
                        <div class="workspace-card group p-6 rounded-2xl border border-slate-800/50 flex flex-col justify-between h-48 relative overflow-hidden">
                            
                            <button onclick="deletarWorkspace(event, '<?php echo $ws['id']; ?>', '<?php echo htmlspecialchars($ws['nome']); ?>')" 
                                    class="absolute top-4 right-4 z-30 w-8 h-8 flex items-center justify-center rounded-full bg-slate-800/80 text-slate-400 hover:bg-red-500/20 hover:text-red-500 transition-all opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 duration-200 border border-transparent hover:border-red-500/30"
                                    title="Excluir Workspace">
                                <i class="fa-solid fa-trash-can text-sm"></i>
                            </button>

                            <a href="abrir_workspace.php?id=<?php echo $ws['id']; ?>" class="absolute inset-0 z-20 cursor-pointer"></a>

                            <div class="flex justify-between items-start z-10 relative pointer-events-none">
                                <div class="w-12 h-12 bg-gradient-to-br from-brand-primary to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-primary/20 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-box-archive"></i>
                                </div>
                                
                                <?php 
                                    $nivel = strtolower($ws['nivel_acesso'] ?? 'leitura');
                                    $badgeClass = match($nivel) {
                                        'admin' => 'bg-rose-500/20 text-rose-400 border-rose-500/30',
                                        'editor' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                        default => 'bg-slate-700/50 text-slate-400 border-slate-600/50'
                                    };
                                ?>
                                <span class="mr-6 px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border <?php echo $badgeClass; ?>">
                                    <?php echo ucfirst($nivel); ?>
                                </span>
                            </div>
                            
                            <div class="z-10 relative mt-4 pointer-events-none">
                                <h3 class="text-white font-bold text-lg leading-tight mb-1 truncate pr-2">
                                    <?php echo htmlspecialchars($ws['nome']); ?>
                                </h3>
                                
                                <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                                    <?php if(($ws['status'] ?? '') === 'pendente'): ?>
                                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>Criando...</span>
                                    <?php else: ?>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span>Ativo</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="pt-4 mt-auto border-t border-white/5 flex items-center justify-between text-xs text-slate-400 z-10 relative pointer-events-none">
                                <span>
                                    <i class="fa-regular fa-clock mr-1"></i> 
                                    <?php echo isset($ws['created_at']) ? date('d/m/Y', strtotime($ws['created_at'])) : '-'; ?>
                                </span>
                                <i class="fa-solid fa-arrow-right -rotate-45 group-hover:rotate-0 group-hover:text-brand-primary transition-all duration-300"></i>
                            </div>

                            <div class="absolute -bottom-4 -right-4 text-8xl text-white/5 rotate-12 group-hover:scale-110 transition-transform duration-500 pointer-events-none z-0">
                                <i class="fa-solid fa-folder"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <div id="modal-create-ws" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-slate-900/90 backdrop-blur-sm" onclick="toggleModal('modal-create-ws')"></div>
        <div class="modal-container bg-slate-900 w-11/12 md:max-w-md mx-auto rounded-[2rem] shadow-2xl z-50 border border-slate-700 overflow-hidden transform transition-all scale-95">
            <div class="bg-brand-surface px-8 py-6 border-b border-slate-800 flex justify-between items-center">
                <h3 class="font-bold text-xl text-white flex items-center gap-2">
                    <i class="fa-solid fa-folder-plus text-brand-primary"></i> Novo Workspace
                </h3>
                <button onclick="toggleModal('modal-create-ws')" class="text-slate-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="px-8 py-8">
                <form id="formCreateWS" class="space-y-5">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">Nome do Espaço</label>
                        <input type="text" id="wsName" name="wsName" required placeholder="Ex: Anatomia Clínica, Resumos..." class="w-full bg-slate-950 text-white border border-slate-800 rounded-xl px-4 py-3 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all placeholder-slate-700">
                    </div>
                    <div class="bg-blue-500/10 p-4 rounded-xl flex gap-3 items-start">
                        <i class="fa-solid fa-circle-info text-brand-primary mt-1"></i>
                        <p class="text-xs text-blue-200 leading-relaxed">Este workspace será sincronizado com o Banco de Dados e o sistema de arquivos virtuais.</p>
                    </div>
                    <button type="submit" id="btnSubmit" class="w-full bg-brand-primary hover:bg-sky-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-brand-primary/20 transition-all transform active:scale-95 flex justify-center items-center gap-2">
                        <span>Criar Workspace</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Lógica do Modal
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            const container = modal.querySelector('.modal-container');
            if (modal.classList.contains('opacity-0')) {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                container.classList.remove('scale-95'); container.classList.add('scale-100');
                document.body.classList.add('modal-active');
                setTimeout(() => document.getElementById('wsName').focus(), 100);
            } else {
                modal.classList.add('opacity-0', 'pointer-events-none');
                container.classList.remove('scale-100'); container.classList.add('scale-95');
                document.body.classList.remove('modal-active');
            }
        }
        document.onkeydown = function(evt) { if (evt.keyCode == 27) toggleModal('modal-create-ws'); };

        // Processo de Criação (Chamando API local em vez do N8N direto)
        document.getElementById('formCreateWS').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const originalText = btn.innerHTML;
            const nomeWS = document.getElementById('wsName').value;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Criando...';
            btn.disabled = true;

            // Importante: Certifique-se de que "api/cria_ws.php" existe
            fetch('api/cria_ws.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'nome=' + encodeURIComponent(nomeWS)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Workspace criado.', background: '#1e293b', color: '#fff', confirmButtonColor: '#0284c7' }).then(() => window.location.reload());
                } else { throw new Error(data.message); }
            })
            .catch(error => Swal.fire({ icon: 'error', title: 'Ops!', text: error.message, background: '#1e293b', color: '#fff' }))
            .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
        });

        // Função de Deletar (Soft Delete)
        function deletarWorkspace(event, id, nome) {
            event.stopPropagation();
            event.preventDefault();

            Swal.fire({
                title: 'Desativar Workspace?',
                text: `"${nome}" ficará inativo e não aparecerá na lista.`,
                icon: 'warning',
                background: '#1e293b',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Sim, desativar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Processando...', didOpen: () => Swal.showLoading(), background: '#1e293b', color: '#fff' });

                    // Importante: Certifique-se de que "api/deletar_ws.php" existe
                    fetch('api/deletar_ws.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + encodeURIComponent(id)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Feito!', text: 'Workspace desativado.', background: '#1e293b', color: '#fff' }).then(() => window.location.reload());
                        } else { throw new Error(data.message); }
                    })
                    .catch(error => Swal.fire({ icon: 'error', title: 'Erro', text: error.message, background: '#1e293b', color: '#fff' }));
                }
            });
        }
    </script>
</body>
</html>