<?php
session_start();
require_once 'php/config.php';

// Segurança: Apenas logados
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 1. Validação de Segurança do Caminho
$pastaSolicitada = isset($_GET['folder']) ? basename($_GET['folder']) : '';
$caminhoBase = 'repositorio/';
$caminhoCompleto = $caminhoBase . $pastaSolicitada;

// Verifica se a pasta existe e se é realmente um diretório dentro de 'repositorio/'
if (empty($pastaSolicitada) || !is_dir($caminhoCompleto) || strpos($caminhoCompleto, '..') !== false) {
    // Redireciona para o repositório principal se houver erro
    header('Location: repositorio.php');
    exit;
}

// 2. Leitura do Conteúdo
// scandir pode falhar se não tiver permissão, o @ suprime o erro visual do PHP
$conteudo = @scandir($caminhoCompleto);
$arquivos = [];

if ($conteudo) {
    // Remove '.' e '..' da lista
    $arquivos = array_diff($conteudo, array('.', '..'));
}

// Lógica de Ícones (UX)
function getIcone($arquivo) {
    $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return '<i class="fa-solid fa-file-pdf text-rose-500"></i>';
        case 'jpg': case 'png': case 'jpeg': return '<i class="fa-solid fa-file-image text-purple-500"></i>';
        case 'doc': case 'docx': return '<i class="fa-solid fa-file-word text-blue-500"></i>';
        case 'xls': case 'xlsx': return '<i class="fa-solid fa-file-excel text-emerald-500"></i>';
        case 'zip': case 'rar': return '<i class="fa-solid fa-file-zipper text-amber-500"></i>';
        default: return '<i class="fa-solid fa-file text-slate-400"></i>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace: <?php echo htmlspecialchars($pastaSolicitada); ?></title>
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
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white"><?php echo htmlspecialchars($pastaSolicitada); ?></h1>
                </div>
            </div>

            <button onclick="alert('Upload será implementado na próxima fase!')" class="bg-slate-800 hover:bg-slate-700 text-white font-medium py-3 px-6 rounded-xl flex items-center gap-3 transition-all border border-slate-700">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                Upload de Arquivo
            </button>
        </header>

        <?php if (count($arquivos) === 0): ?>
            <div class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-slate-800 rounded-[2rem] bg-slate-900/20 p-10 text-center animate-pulse">
                <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center mb-6 shadow-2xl shadow-black/50">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-600"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-300">Este Workspace está vazio</h3>
                <p class="text-slate-500 mt-2 max-w-md">Seja o primeiro a colaborar! Faça upload de resumos, livros ou anotações para este grupo.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php foreach($arquivos as $arquivo): ?>
                    <a href="<?php echo $caminhoCompleto . '/' . $arquivo; ?>" download class="group bg-slate-800/40 hover:bg-slate-800 border border-slate-700/50 hover:border-brand-primary/50 p-4 rounded-xl flex items-center gap-4 transition-all hover:-translate-y-1">
                        <div class="text-2xl w-10 h-10 flex items-center justify-center bg-slate-900 rounded-lg group-hover:scale-110 transition-transform">
                            <?php echo getIcone($arquivo); ?>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-200 truncate group-hover:text-brand-primary transition-colors"><?php echo $arquivo; ?></p>
                            <p class="text-[10px] text-slate-500 uppercase font-bold mt-0.5">Clique para baixar</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>