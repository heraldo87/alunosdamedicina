<?php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA: Bloqueia não logados
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Verifica se o ID do usuário está na sessão (Correção crítica)
if (!isset($_SESSION['user_id'])) {
    // Tenta recuperar o ID pelo nome de usuário caso a sessão tenha falhado
    // Isso é um fallback de segurança
    if (isset($_SESSION['user_name'])) {
        $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE nome = ? LIMIT 1");
        $stmtUser->execute([$_SESSION['user_name']]);
        $userDados = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($userDados) {
            $_SESSION['user_id'] = $userDados['id'];
        } else {
            die("Erro crítico: Usuário não identificado. Faça login novamente.");
        }
    } else {
        header('Location: login.php');
        exit;
    }
}

$userId = $_SESSION['user_id'];
// Aceita tanto ?id= (novo padrão) quanto ?folder= (legado, para compatibilidade)
$workspaceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$folderSlugLegacy = isset($_GET['folder']) ? $_GET['folder'] : '';

$caminhoBase = 'repositorio/';
$workspace = null;

try {
    // 2. BUSCA INTELIGENTE NO BANCO
    // Se veio pelo ID (Padrão novo)
    if ($workspaceId > 0) {
        $sql = "SELECT * FROM workspaces WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $workspaceId]);
        $workspace = $stmt->fetch(PDO::FETCH_ASSOC);
    } 
    // Se veio pelo nome da pasta (Legado ou link antigo)
    elseif (!empty($folderSlugLegacy)) {
        $sql = "SELECT * FROM workspaces WHERE folder_slug = :slug";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['slug' => $folderSlugLegacy]);
        $workspace = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. VERIFICAÇÃO DE PERMISSÃO (A Regra de Ouro)
    if ($workspace) {
        $isCreator = ($workspace['creator_id'] == $userId);
        $isPublic = ($workspace['visibility'] === 'public');
        
        // Verifica se tem permissão explicita na tabela auxiliar (para workspaces restritos)
        $hasPermission = false;
        if (!$isCreator && !$isPublic) {
            $stmtPerm = $pdo->prepare("SELECT 1 FROM workspace_permissions WHERE workspace_id = ? AND user_id = ?");
            $stmtPerm->execute([$workspace['id'], $userId]);
            $hasPermission = $stmtPerm->fetchColumn();
        }

        // BLOQUEIO: Se não for criador, nem público, nem tiver permissão -> TCHAU!
        if (!$isCreator && !$isPublic && !$hasPermission) {
            // Redireciona para o repositório com erro silencioso ou mostra página de acesso negado
            echo "<script>alert('Acesso Negado: Este workspace é privado.'); window.location.href='repositorio.php';</script>";
            exit;
        }

    } else {
        // Se não achou no banco, mas a pasta existe fisicamente (Legado puro), permite se for admin?
        // Por segurança, melhor redirecionar.
        header('Location: repositorio.php');
        exit;
    }

    // 4. PREPARAÇÃO DO SISTEMA DE ARQUIVOS
    $nomePastaFisica = $workspace['folder_slug'];
    $caminhoCompleto = $caminhoBase . $nomePastaFisica;

    // Autocorreção: Se a pasta não existe no disco, cria ela agora (O dono tem acesso garantido)
    if (!is_dir($caminhoCompleto)) {
        if (!mkdir($caminhoCompleto, 0777, true)) {
             // Se falhar o mkdir, provavelmente é permissão da pasta 'repositorio' (chown www:www)
             die("Erro de servidor: Não foi possível inicializar a pasta física. Contate o suporte.");
        }
    }

    // Leitura dos arquivos
    $conteudo = @scandir($caminhoCompleto);
    $arquivos = ($conteudo !== false) ? array_diff($conteudo, array('.', '..')) : [];

} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Função de Ícones (UX)
function getIcone($arquivo) {
    $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'fa-file-pdf text-rose-500',
        'jpg' => 'fa-file-image text-purple-500', 'jpeg' => 'fa-file-image text-purple-500', 'png' => 'fa-file-image text-purple-500',
        'doc' => 'fa-file-word text-blue-500', 'docx' => 'fa-file-word text-blue-500',
        'xls' => 'fa-file-excel text-emerald-500', 'xlsx' => 'fa-file-excel text-emerald-500',
        'zip' => 'fa-file-zipper text-amber-500', 'rar' => 'fa-file-zipper text-amber-500',
        'ppt' => 'fa-file-powerpoint text-orange-500', 'pptx' => 'fa-file-powerpoint text-orange-500'
    ];
    $classe = $icons[$ext] ?? 'fa-file text-slate-400';
    return "<i class='fa-solid $classe'></i>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace: <?php echo htmlspecialchars($workspace['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { dark: '#0b0f1a', primary: '#0284c7', surface: '#1e293b' } } } } }
    </script>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto p-6 md:p-12">
        
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div class="flex items-center gap-4">
                <a href="repositorio.php" class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-brand-primary hover:text-white transition-all shadow-lg">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-brand-primary bg-brand-primary/10 px-2 py-0.5 rounded">Workspace</span>
                        <?php if($workspace['visibility'] === 'private'): ?>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20"><i class="fa-solid fa-lock mr-1"></i> Privado</span>
                        <?php else: ?>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20"><i class="fa-solid fa-globe mr-1"></i> Público</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                        <?php echo htmlspecialchars($workspace['name']); ?>
                    </h1>
                </div>
            </div>

            <button onclick="alert('Funcionalidade de upload em desenvolvimento.')" class="bg-slate-800 hover:bg-slate-700 text-white font-medium py-3 px-6 rounded-xl flex items-center gap-3 transition-all border border-slate-700 cursor-pointer shadow-lg">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span class="hidden sm:inline">Upload de Arquivo</span>
            </button>
        </header>

        <?php if (empty($arquivos)): ?>
            <div class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-slate-800 rounded-[2rem] bg-slate-900/20 p-10 text-center min-h-[300px]">
                <div class="w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center mb-6 shadow-xl ring-4 ring-slate-800/30">
                    <i class="fa-solid fa-folder-open text-3xl text-slate-600"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Workspace Vazio</h3>
                <p class="text-slate-500 mt-2 max-w-sm mx-auto text-sm">Este espaço está pronto para uso. Adicione arquivos para começar a colaborar.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-20">
                <?php foreach($arquivos as $arquivo): ?>
                    <a href="<?php echo $caminhoCompleto . '/' . $arquivo; ?>" download class="group bg-slate-800/40 hover:bg-slate-800 border border-slate-700/50 hover:border-brand-primary/50 p-4 rounded-xl flex items-center gap-4 transition-all hover:-translate-y-1 hover:shadow-lg hover:shadow-brand-primary/5">
                        <div class="text-2xl w-12 h-12 flex items-center justify-center bg-slate-900 rounded-lg group-hover:scale-110 transition-transform shadow-inner">
                            <?php echo getIcone($arquivo); ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-200 truncate group-hover:text-brand-primary transition-colors"><?php echo $arquivo; ?></p>
                            <p class="text-[10px] text-slate-500 uppercase font-bold mt-1 flex items-center gap-1">
                                <i class="fa-solid fa-download"></i> Baixar
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>