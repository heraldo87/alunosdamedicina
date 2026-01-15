<?php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. CAPTURA PARÂMETROS DA URL
$workspaceId = $_GET['id'] ?? 0;
$workspaceName = urldecode($_GET['folder'] ?? 'Workspace');
$driveId = $_GET['drive_id'] ?? ''; 
$parentId = isset($_GET['parent_id']) && is_numeric($_GET['parent_id']) ? $_GET['parent_id'] : null;

// Validar ID do workspace
if (!$workspaceId) {
    die("ID do Workspace não fornecido.");
}

// 3. BUSCAR ARQUIVOS E PASTAS NO BANCO DE DADOS
$conteudo = [];
$caminhoPao = []; // Breadcrumbs

try {
    // 3.1. Se estivermos em uma subpasta, pegamos o nome dela para o Breadcrumb
    if ($parentId) {
        $stmtPath = $pdo->prepare("SELECT id, nome_arquivo, parent_id FROM arquivos WHERE id = ?");
        $stmtPath->execute([$parentId]);
        $pastaAtualInfo = $stmtPath->fetch(PDO::FETCH_ASSOC);
        // (Aqui poderia ser implementada uma lógica recursiva para breadcrumbs completos)
    }

    // 3.2. Consulta Principal: Pega pastas e arquivos do nível atual
    // Usamos 'IS NULL' para raiz e '= ?' para subpastas
    if ($parentId === null) {
        $sql = "SELECT * FROM arquivos 
                WHERE workspace_id = :ws_id 
                AND parent_id IS NULL 
                AND status = 'ativo' 
                ORDER BY tipo ASC, nome_arquivo ASC"; // Pastas primeiro
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ws_id', $workspaceId);
    } else {
        $sql = "SELECT * FROM arquivos 
                WHERE workspace_id = :ws_id 
                AND parent_id = :p_id 
                AND status = 'ativo' 
                ORDER BY tipo ASC, nome_arquivo ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ws_id', $workspaceId);
        $stmt->bindValue(':p_id', $parentId);
    }
    
    $stmt->execute();
    $conteudo = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao listar arquivos: " . $e->getMessage());
}

// Função auxiliar para ícones
function getIcon($tipo, $mime) {
    if ($tipo === 'pasta') return '<i class="fa-solid fa-folder text-amber-500 text-3xl"></i>';
    if (strpos($mime, 'pdf') !== false) return '<i class="fa-solid fa-file-pdf text-red-500 text-3xl"></i>';
    if (strpos($mime, 'image') !== false) return '<i class="fa-solid fa-file-image text-purple-500 text-3xl"></i>';
    if (strpos($mime, 'word') !== false) return '<i class="fa-solid fa-file-word text-blue-500 text-3xl"></i>';
    return '<i class="fa-solid fa-file text-slate-400 text-3xl"></i>';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($workspaceName); ?> - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .file-row:hover { background-color: rgba(30, 41, 59, 0.5); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0b0f1a; }
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
        
        <header class="bg-brand-dark/95 backdrop-blur z-20 sticky top-0 border-b border-slate-800 px-8 py-5 flex items-center justify-between">
            <div>
                <nav class="flex items-center text-sm text-slate-500 mb-1">
                    <a href="repositorio.php" class="hover:text-brand-primary transition-colors"><i class="fa-solid fa-house"></i></a>
                    <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
                    <a href="abrir_workspace.php?id=<?php echo $workspaceId; ?>&folder=<?php echo urlencode($workspaceName); ?>&drive_id=<?php echo $driveId; ?>" class="hover:text-brand-primary transition-colors font-bold text-slate-300">
                        <?php echo htmlspecialchars($workspaceName); ?>
                    </a>
                    <?php if(isset($pastaAtualInfo)): ?>
                        <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
                        <span class="text-brand-primary font-bold"><?php echo htmlspecialchars($pastaAtualInfo['nome_arquivo']); ?></span>
                    <?php endif; ?>
                </nav>
                <h1 class="text-2xl font-bold text-white tracking-tight">
                    <?php echo isset($pastaAtualInfo) ? htmlspecialchars($pastaAtualInfo['nome_arquivo']) : 'Arquivos'; ?>
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="document.getElementById('inputUpload').click()" class="bg-brand-primary hover:bg-sky-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-brand-primary/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                </button>
            </div>
        </header>

        <form id="formUpload" action="SEU_ENDPOINT_OU_ARQUIVO_PHP" method="POST" enctype="multipart/form-data" class="hidden">
            <input type="file" id="inputUpload" name="arquivo" onchange="uploadArquivo()">
            <input type="hidden" name="workspace_id" value="<?php echo $workspaceId; ?>">
            <input type="hidden" name="drive_id" value="<?php echo htmlspecialchars($driveId); ?>">
            <input type="hidden" name="parent_id" value="<?php echo $parentId ?? ''; ?>">
        </form>

        <div class="p-8">
            <?php if (empty($conteudo)): ?>
                <div class="flex flex-col items-center justify-center py-20 border border-dashed border-slate-800 rounded-3xl bg-slate-900/30">
                    <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-regular fa-folder-open text-2xl text-slate-500"></i>
                    </div>
                    <p class="text-slate-400 font-medium">Pasta vazia</p>
                    <p class="text-xs text-slate-600 mt-1">Faça upload ou crie uma nova pasta.</p>
                </div>
            <?php else: ?>
                <div class="bg-brand-surface border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-800">
                                <th class="px-6 py-4 font-bold w-1/2">Nome</th>
                                <th class="px-6 py-4 font-bold text-center">Tipo</th>
                                <th class="px-6 py-4 font-bold text-center">Tamanho</th>
                                <th class="px-6 py-4 font-bold text-right">Data</th>
                                <th class="px-6 py-4 font-bold text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            <?php foreach ($conteudo as $item): 
                                $isFolder = ($item['tipo'] === 'pasta');
                                $nome = $item['nome_arquivo'];
                                $idItem = $item['id'];
                                
                                // Link: Se for pasta, recarrega a página com parent_id. Se arquivo, link de download/visualização
                                if ($isFolder) {
                                    $link = "abrir_workspace.php?id=$workspaceId&folder=" . urlencode($workspaceName) . "&drive_id=" . urlencode($driveId) . "&parent_id=$idItem";
                                } else {
                                    // Ajuste conforme sua lógica de visualização de arquivo (ex: link do drive ou local)
                                    $link = $item['caminho_virtual'] ?? '#'; 
                                }
                            ?>
                            <tr class="file-row transition-colors group">
                                <td class="px-6 py-4">
                                    <a href="<?php echo $link; ?>" class="flex items-center gap-4 group-hover:text-brand-primary transition-colors">
                                        <div class="shrink-0 transition-transform group-hover:scale-110">
                                            <?php echo getIcon($item['tipo'], $item['mime_type'] ?? ''); ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm text-slate-200"><?php echo htmlspecialchars($nome); ?></p>
                                        </div>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-400">
                                        <?php echo htmlspecialchars($isFolder ? 'Pasta' : explode('/', $item['mime_type'] ?? 'Arquivo')[1] ?? 'File'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-xs text-slate-500 font-mono">
                                    <?php 
                                        if($isFolder) echo '-';
                                        else {
                                            $bytes = $item['tamanho_bytes'] ?? 0;
                                            if ($bytes > 1048576) echo round($bytes / 1048576, 2) . ' MB';
                                            elseif ($bytes > 1024) echo round($bytes / 1024, 2) . ' KB';
                                            else echo $bytes . ' B';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-slate-500">
                                    <?php echo date('d/m/Y', strtotime($item['criado_em'])); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-slate-500 hover:text-white p-2 rounded-lg hover:bg-slate-700 transition-all">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Simulação de Upload - AQUI VOCÊ CONECTA AO SEU BACKEND/N8N
        function uploadArquivo() {
            const form = document.getElementById('formUpload');
            const formData = new FormData(form);

            Swal.fire({
                title: 'Enviando...',
                text: 'Aguarde enquanto processamos o arquivo.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading() },
                background: '#1e293b', color: '#fff'
            });

            // Exemplo de envio via Fetch (AJUSTE A URL 'api/upload_n8n.php')
            /*
            fetch('api/upload_arquivo.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Sucesso!', 'Arquivo enviado.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Erro!', 'Falha no envio.', 'error');
                }
            });
            */
            
            // Apenas para debug visual agora:
            console.log("Drive ID enviado:", formData.get('drive_id'));
            console.log("Workspace ID:", formData.get('workspace_id'));
            setTimeout(() => {
                Swal.fire({
                    icon: 'info', 
                    title: 'Configurar Backend', 
                    text: 'O front-end capturou o arquivo e IDs. Configure o fetch no script para enviar ao N8N.',
                    background: '#1e293b', color: '#fff'
                });
            }, 1000);
        }
    </script>
</body>
</html>