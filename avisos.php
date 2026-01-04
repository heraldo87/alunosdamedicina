<?php
/**
 * PÁGINA DE AVISOS E MURAL - MEDINFOCUS
 * Versão 2.1: Controle de Acesso por Nível (2 e 3)
 */
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// DEFINIÇÃO DE PERMISSÕES
// Nível 1: Aluno (Leitura)
// Nível 2: Representante (Leitura + Postagem)
// Nível 3: Admin (Leitura + Postagem + Gestão Total)
$nivelAcesso = $_SESSION['user_level'] ?? 1; 
$podePostar = ($nivelAcesso >= 2); 

// 2. LÓGICA DE POSTAGEM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Trava de Segurança no Back-end
    if (!$podePostar) {
        die("Acesso negado: Seu perfil não tem permissão para criar avisos.");
    }

    $titulo = $_POST['titulo'] ?? '';
    $conteudo = $_POST['conteudo'] ?? '';
    $tipo = $_POST['tipo'] ?? 'info';
    $destinado = $_POST['destinado_a'] ?? 'Geral';
    
    if (!empty($titulo) && !empty($conteudo)) {
        try {
            $sqlInsert = "INSERT INTO avisos (titulo, conteudo, tipo, destinado_a, data_criacao) VALUES (:titulo, :conteudo, :tipo, :destinado, NOW())";
            $stmt = $pdo->prepare($sqlInsert);
            $stmt->execute([
                ':titulo' => $titulo,
                ':conteudo' => $conteudo,
                ':tipo' => $tipo,
                ':destinado' => $destinado
            ]);
            header("Location: avisos.php?status=sucesso");
            exit;
        } catch (PDOException $e) { 
            // Em produção, ideal gravar em log de erro
        }
    }
}

// 3. PAGINAÇÃO E BUSCA
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite = 5; // Máximo de avisos por página
$inicio = ($pagina > 1) ? ($pagina - 1) * $limite : 0;

try {
    // Contar total de avisos ativos
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM avisos WHERE ativo = 1");
    $total_avisos = $total_stmt->fetchColumn();
    $total_paginas = ceil($total_avisos / $limite);

    // Buscar avisos da página atual
    $sqlBusca = "SELECT * FROM avisos WHERE ativo = 1 ORDER BY fixado DESC, data_criacao DESC LIMIT :inicio, :limite";
    $stmt = $pdo->prepare($sqlBusca);
    $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    $listaAvisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $listaAvisos = [];
}

function formatarData($dataSql) {
    return date('d/m \à\s H:i', strtotime($dataSql));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mural de Avisos - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0f172a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
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
        
        <header class="pt-10 pb-6 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-4 flex-shrink-0">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-bullhorn text-brand-primary"></i>
                    Mural de <span class="text-brand-primary">Avisos</span>
                </h1>
                <p class="text-slate-500 mt-1 font-medium italic text-sm">Atualizações da turma (Pág. <?php echo $pagina; ?>)</p>
            </div>

            <?php if ($podePostar): ?>
            <button onclick="abrirModal()" class="bg-brand-primary hover:bg-sky-600 text-white font-bold py-2.5 px-5 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-sky-500/20 text-sm">
                <i class="fa-solid fa-pen-to-square"></i> Novo Aviso
            </button>
            <?php endif; ?>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sucesso'): ?>
        <div class="px-6 md:px-12 mb-4 flex-shrink-0">
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 px-4 py-3 rounded-xl flex items-center gap-3">
                <i class="fa-solid fa-check-circle"></i>
                <span class="font-bold text-sm">Aviso publicado com sucesso!</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row px-6 md:px-12 pb-12 gap-8 flex-1">
            
            <div class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-slate-800/40 rounded-2xl p-4 border border-slate-700/50 sticky top-0">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4 px-2">Estatísticas</h3>
                    <div class="space-y-1">
                        <div class="px-4 py-3 rounded-xl bg-brand-primary/10 border border-brand-primary/20 flex justify-between items-center">
                            <span class="text-brand-primary font-bold text-sm">Total Avisos</span>
                            <span class="bg-brand-primary text-white px-2 py-0.5 rounded text-[10px] font-bold"><?php echo $total_avisos; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 max-w-3xl flex flex-col">
                
                <?php if (empty($listaAvisos)): ?>
                    <div class="flex flex-col items-center justify-center py-20 bg-slate-800/20 rounded-3xl border border-dashed border-slate-700">
                        <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-600">
                            <i class="fa-regular fa-folder-open text-2xl"></i>
                        </div>
                        <p class="text-slate-400 font-medium">Nenhum aviso nesta página.</p>
                    </div>
                <?php else: ?>
                    
                    <div class="space-y-4">
                        <?php foreach ($listaAvisos as $aviso): 
                            $corCss = match($aviso['tipo']) {
                                'urgente' => ['border' => 'border-rose-500/50', 'icon' => 'text-rose-500 fa-circle-exclamation', 'bg_icon' => 'bg-rose-500/10'],
                                'sucesso' => ['border' => 'border-emerald-500/50', 'icon' => 'text-emerald-500 fa-check-circle', 'bg_icon' => 'bg-emerald-500/10'],
                                'destaque' => ['border' => 'border-amber-500/50', 'icon' => 'text-amber-500 fa-star', 'bg_icon' => 'bg-amber-500/10'],
                                default   => ['border' => 'border-slate-700', 'icon' => 'text-sky-500 fa-bell', 'bg_icon' => 'bg-sky-500/10']
                            };
                        ?>
                        <div class="bg-brand-surface border <?php echo $corCss['border']; ?> rounded-2xl p-6 relative group animate-fade-in hover:border-slate-500 transition-all shadow-lg">
                            
                            <div class="absolute top-4 right-4 flex gap-2">
                                <span class="px-2 py-1 rounded bg-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider border border-slate-700">
                                    <?php echo htmlspecialchars($aviso['destinado_a']); ?>
                                </span>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl <?php echo $corCss['bg_icon']; ?> flex items-center justify-center flex-shrink-0 mt-1">
                                    <i class="fa-solid <?php echo $corCss['icon']; ?> text-lg"></i>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-white font-bold text-base leading-tight mb-1 pr-20">
                                        <?php echo htmlspecialchars($aviso['titulo']); ?>
                                    </h4>
                                    
                                    <div class="flex items-center gap-3 text-xs text-slate-500 mb-2">
                                        <span><i class="fa-regular fa-clock"></i> <?php echo formatarData($aviso['data_criacao']); ?></span>
                                    </div>
                                    
                                    <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap line-clamp-3 hover:line-clamp-none transition-all cursor-default" title="Passe o mouse para ler tudo">
                                        <?php echo nl2br(htmlspecialchars($aviso['conteudo'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_paginas > 1): ?>
                    <div class="mt-8 flex justify-center items-center gap-2">
                        <?php if($pagina > 1): ?>
                            <a href="?page=<?php echo $pagina - 1; ?>" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium transition-colors border border-slate-700">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Anterior
                            </a>
                        <?php endif; ?>

                        <span class="px-4 text-sm text-slate-500 font-bold">
                            Pág. <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                        </span>

                        <?php if($pagina < $total_paginas): ?>
                            <a href="?page=<?php echo $pagina + 1; ?>" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium transition-colors border border-slate-700">
                                Próxima <i class="fa-solid fa-chevron-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-auto">
            <?php include 'includes/footer.php'; ?>
        </div>
    </main>

    <?php if ($podePostar): ?>
    <div id="modalAviso" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="fecharModal()"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-6 bg-brand-surface border border-slate-700 rounded-2xl shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Publicar Aviso</h3>
                <button onclick="fecharModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form method="POST" action="avisos.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Título</label>
                    <input type="text" name="titulo" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:border-brand-primary outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo</label>
                        <select name="tipo" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white outline-none">
                            <option value="info">Informação</option>
                            <option value="urgente">Urgente</option>
                            <option value="sucesso">Sucesso</option>
                            <option value="destaque">Destaque</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Destinado A</label>
                        <select name="destinado_a" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white outline-none">
                            <option value="Geral">Geral</option>
                            <option value="Turma A">Turma A</option>
                            <option value="Turma B">Turma B</option>
                            <option value="Representantes">Representantes</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Mensagem</label>
                    <textarea name="conteudo" required rows="4" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white outline-none resize-none"></textarea>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-brand-primary hover:bg-sky-600 text-white font-bold py-3 rounded-xl transition-all">Publicar Agora</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        <?php if ($podePostar): ?>
        function abrirModal() { document.getElementById('modalAviso').classList.remove('hidden'); }
        function fecharModal() { document.getElementById('modalAviso').classList.add('hidden'); }
        <?php endif; ?>
    </script>
</body>
</html>