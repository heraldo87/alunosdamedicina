<?php
session_start();
require_once 'php/config.php'; //

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id']; // Certifique-se que o ID está na sessão ao logar
$tipoUsuario = $_SESSION['user_type'] ?? 'aluno';
$caminhoBase = 'repositorio/';

// 2. PROCESSAMENTO: CRIAR NOVO WORKSPACE
$msgErro = '';
$msgSucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_workspace') {
    $nomeWorkspace = trim($_POST['workspace_name']);
    $visibilidade = $_POST['visibility']; // public, private, restricted
    
    if (!empty($nomeWorkspace)) {
        // Gera um nome de pasta seguro (slug)
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $nomeWorkspace)) . '-' . uniqid();
        
        try {
            // 1. Cria no Banco
            $stmt = $pdo->prepare("INSERT INTO workspaces (name, folder_slug, creator_id, visibility) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nomeWorkspace, $slug, $userId, $visibilidade]);
            $workspaceId = $pdo->lastInsertId();

            // 2. Cria a Pasta Física
            if (!is_dir($caminhoBase . $slug)) {
                mkdir($caminhoBase . $slug, 0777, true);
            }

            // 3. (Opcional) Se for restrito, adicionar lógica para inserir na tabela workspace_permissions aqui
            
            $msgSucesso = "Workspace criado com sucesso!";
        } catch (PDOException $e) {
            $msgErro = "Erro ao criar workspace: " . $e->getMessage();
        }
    } else {
        $msgErro = "O nome do workspace não pode ser vazio.";
    }
}

// 3. BUSCAR WORKSPACES PERMITIDOS
// Lógica: Mostrar se for Público OU se eu for o Criador OU se eu tiver permissão na tabela auxiliar
$sql = "
    SELECT w.*, u.nome as criador_nome 
    FROM workspaces w
    LEFT JOIN usuarios u ON w.creator_id = u.id
    WHERE w.visibility = 'public' 
    OR w.creator_id = :userId
    OR w.id IN (SELECT workspace_id FROM workspace_permissions WHERE user_id = :userId)
    ORDER BY w.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['userId' => $userId]);
$workspaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden font-inter">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <?php if($msgErro): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-500 px-6 py-3 m-6 rounded-xl text-sm font-bold">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?php echo $msgErro; ?>
            </div>
        <?php endif; ?>
        <?php if($msgSucesso): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-500 px-6 py-3 m-6 rounded-xl text-sm font-bold">
                <i class="fa-solid fa-check-circle mr-2"></i> <?php echo $msgSucesso; ?>
            </div>
        <?php endif; ?>

        <header class="pt-12 pb-8 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-layer-group text-amber-500"></i>
                    Workspaces
                </h1>
                <p class="text-slate-500 mt-1 font-medium italic">Gerencie e colabore em arquivos.</p>
            </div>

            <button onclick="document.getElementById('modalCreate').classList.remove('hidden')" class="bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-3 px-6 rounded-2xl flex items-center gap-2 transition-all shadow-lg shadow-amber-500/20 cursor-pointer">
                <i class="fa-solid fa-plus-circle"></i>
                Novo Workspace
            </button>
        </header>

        <div class="px-6 md:px-12 pb-20">
            <?php if (empty($workspaces)): ?>
                <div class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-slate-800 rounded-[3rem]">
                    <i class="fa-solid fa-box-open text-6xl text-slate-700 mb-4"></i>
                    <p class="text-slate-500 font-medium">Nenhum workspace encontrado. Crie o primeiro!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php 
                    foreach ($workspaces as $ws): 
                        // Verifica quantos arquivos existem fisicamente
                        $path = $caminhoBase . $ws['folder_slug'];
                        $qtdArquivos = 0;
                        if(is_dir($path)) {
                            // Conta arquivos ignorando . e ..
                            $arquivos = array_diff(scandir($path), array('.', '..'));
                            $qtdArquivos = count($arquivos);
                        } else {
                            // Se a pasta não existe (erro de sync), cria ela agora para evitar erros futuros
                            mkdir($path, 0777, true); 
                        }
                    ?>
                        <a href="abrir_pasta.php?id=<?php echo $ws['id']; ?>" class="workspace-card group p-6 rounded-[2rem] border border-slate-800/50 flex flex-col gap-4 bg-slate-900/40 hover:bg-slate-800/60 transition-all hover:-translate-y-1 hover:border-amber-500/50">
                            <div class="flex items-start justify-between">
                                <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300">
                                    <i class="fa-solid fa-folder text-2xl"></i>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-[10px] font-black px-3 py-1 bg-slate-800 rounded-full text-slate-400 uppercase tracking-widest">
                                        <?php echo ($ws['visibility'] === 'public') ? 'PÚBLICO' : 'PRIVADO'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-white font-bold text-lg leading-tight group-hover:text-amber-500 transition-colors">
                                    <?php echo htmlspecialchars($ws['name']); ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-1 font-medium flex items-center gap-2">
                                    <span title="Arquivos"><i class="fa-solid fa-file-lines"></i> <?php echo $qtdArquivos; ?></span>
                                    <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                                    <span title="Criador"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($ws['criador_nome'] ?? 'Sistema'); ?></span>
                                </p>
                            </div>

                            <div class="mt-2 pt-4 border-t border-slate-800/50 flex items-center justify-between text-xs font-bold uppercase tracking-tighter">
                                <span class="text-slate-500">Acessar</span>
                                <i class="fa-solid fa-chevron-right text-slate-700 group-hover:text-amber-500 group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <div id="modalCreate" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
            <div class="bg-slate-800/50 p-6 border-b border-slate-700 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white">Criar Novo Workspace</h3>
                <button onclick="document.getElementById('modalCreate').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-times"></i></button>
            </div>
            <form method="POST" action="" class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" value="create_workspace">
                
                <div>
                    <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Nome do Workspace</label>
                    <input type="text" name="workspace_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition-colors" placeholder="Ex: Anatomia Clínica">
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Visibilidade</label>
                    <select name="visibility" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition-colors">
                        <option value="public">Público (Todos vêem)</option>
                        <option value="private">Privado (Só eu vejo)</option>
                        </select>
                </div>

                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-brand-dark font-bold py-3 rounded-xl mt-2 transition-all">
                    Criar Workspace
                </button>
            </form>
        </div>
    </div>

</body>
</html>