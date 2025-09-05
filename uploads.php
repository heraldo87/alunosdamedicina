<?php
// Inicia a sessão explicitamente no início do arquivo
session_start();

// Define o título da página
$pageTitle = "Repositório de Arquivos - MedinFocus";

// Verificação de autenticação simplificada
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Redireciona para o login se não estiver autenticado
    header("Location: login.php?error=auth_required");
    exit();
}

// Obtém informações do usuário a partir da sessão com valores padrão de fallback
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Usuário';
$accessLevel = $_SESSION['access_level'] ?? 1;
$userTurma = $_SESSION['turma'] ?? '1º Ano';

// Configuração de caminhos para arquivos e metadados
$uploadsDir = 'uploads/';
$metadataFile = $uploadsDir . 'metadata.json';

// Cria o diretório de uploads se não existir
if (!file_exists($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        die("Erro: Não foi possível criar o diretório de uploads. Verifique as permissões.");
    }
}

// Cria o arquivo de metadados se não existir
if (!file_exists($metadataFile)) {
    $defaultMetadata = [
        'diretorios' => [],
        'arquivos' => []
    ];
    if (!file_put_contents($metadataFile, json_encode($defaultMetadata, JSON_PRETTY_PRINT))) {
        die("Erro: Não foi possível criar o arquivo de metadados. Verifique as permissões.");
    }
}

// Carrega os metadados
$metadataContent = file_get_contents($metadataFile);
if ($metadataContent === false) {
    die("Erro: Não foi possível ler o arquivo de metadados. Verifique as permissões.");
}

$metadata = json_decode($metadataContent, true);
if ($metadata === null) {
    // Se houver erro ao decodificar, cria um novo arquivo de metadados
    $metadata = ['diretorios' => [], 'arquivos' => []];
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
}

// Verificar se estamos visualizando o conteúdo de uma pasta específica
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '';
$dirName = "Repositório Principal";

// Se estamos em uma pasta específica, obtenha o nome dela
if (!empty($currentDir)) {
    foreach ($metadata['diretorios'] as $dir) {
        if ($dir['id'] === $currentDir) {
            $dirName = $dir['nome'];
            break;
        }
    }
}

// Processamento de formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processamento de criação de pasta
    if (isset($_POST['action']) && $_POST['action'] === 'create_folder' && $accessLevel >= 2) {
        $folderName = trim($_POST['folder_name']);
        $folderTurma = $_POST['folder_turma'];
        $parentDir = $_POST['parent_dir'];
        
        // Verifica se o nome da pasta foi fornecido
        if (empty($folderName)) {
            $message = ['type' => 'error', 'text' => 'O nome da pasta é obrigatório.'];
        }
        // Verifica se o usuário pode criar pasta para a turma selecionada
        else if ($accessLevel < 3 && $folderTurma != $userTurma) {
            $message = ['type' => 'error', 'text' => 'Você só pode criar pastas para sua turma.'];
        } else {
            // Cria ID único para o diretório
            $folderId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $folderName)) . '_' . time();
            
            // Cria o diretório físico
            $folderPath = $uploadsDir . $folderId . '/';
            if (!file_exists($folderPath)) {
                if (mkdir($folderPath, 0755, true)) {
                    // Adiciona aos metadados
                    $metadata['diretorios'][] = [
                        'id' => $folderId,
                        'nome' => $folderName,
                        'turma' => $folderTurma,
                        'parent_dir' => $parentDir,
                        'usuario_id' => $userId,
                        'data_criacao' => date('Y-m-d H:i:s')
                    ];
                    
                    // Salva os metadados
                    if (file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT))) {
                        $message = ['type' => 'success', 'text' => 'Pasta criada com sucesso!'];
                    } else {
                        $message = ['type' => 'error', 'text' => 'Erro ao salvar os metadados.'];
                    }
                } else {
                    $message = ['type' => 'error', 'text' => 'Erro ao criar a pasta no servidor.'];
                }
            } else {
                $message = ['type' => 'error', 'text' => 'Já existe uma pasta com este nome.'];
            }
        }
    }
    
    // Processamento de upload de arquivo
    if (isset($_POST['action']) && $_POST['action'] === 'upload_file' && $accessLevel >= 2) {
        $fileTurma = $_POST['file_turma'];
        $diretorioId = $_POST['diretorio_id'];
        
        // Verifica se o usuário pode fazer upload para a turma selecionada
        if ($accessLevel < 3 && $fileTurma != $userTurma) {
            $message = ['type' => 'error', 'text' => 'Você só pode fazer upload para sua turma.'];
        } else if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
            $fileName = $_FILES['file_upload']['name'];
            $fileSize = $_FILES['file_upload']['size'];
            $fileTmpName = $_FILES['file_upload']['tmp_name'];
            $fileType = $_FILES['file_upload']['type'];
            
            // Gera um nome único para o arquivo
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $fileName);
            
            // Define o caminho de destino
            $destinationDir = $uploadsDir;
            if (!empty($diretorioId)) {
                $destinationDir .= $diretorioId . '/';
                
                // Verifica se o diretório existe, se não, cria
                if (!file_exists($destinationDir)) {
                    mkdir($destinationDir, 0755, true);
                }
            }
            
            $destination = $destinationDir . $uniqueName;
            
            // Obtém o nome de exibição do arquivo
            $displayName = !empty($_POST['file_name']) ? trim($_POST['file_name']) : $fileName;
            
            // Move o arquivo enviado para o destino
            if (move_uploaded_file($fileTmpName, $destination)) {
                // Adiciona aos metadados
                $metadata['arquivos'][] = [
                    'id' => $uniqueName,
                    'nome' => $displayName,
                    'arquivo' => $uniqueName,
                    'tamanho' => $fileSize,
                    'tipo' => $fileType,
                    'turma' => $fileTurma,
                    'diretorio_id' => $diretorioId,
                    'usuario_id' => $userId,
                    'data_upload' => date('Y-m-d H:i:s')
                ];
                
                // Salva os metadados
                if (file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT))) {
                    $message = ['type' => 'success', 'text' => 'Arquivo enviado com sucesso!'];
                } else {
                    $message = ['type' => 'error', 'text' => 'Arquivo enviado, mas erro ao atualizar metadados.'];
                }
            } else {
                $message = ['type' => 'error', 'text' => 'Erro ao fazer upload do arquivo.'];
            }
        } else {
            $message = ['type' => 'error', 'text' => 'Nenhum arquivo selecionado ou erro no upload.'];
        }
    }
    
    // Processamento de exclusão de arquivo
    if (isset($_POST['action']) && $_POST['action'] === 'delete_file') {
        $fileId = $_POST['file_id'];
        $fileFound = false;
        
        foreach ($metadata['arquivos'] as $key => $file) {
            if ($file['id'] === $fileId) {
                $fileFound = true;
                
                // Verifica se o usuário tem permissão para excluir
                if ($accessLevel >= 3 || ($accessLevel == 2 && $userId == $file['usuario_id'])) {
                    // Remove o arquivo físico
                    $filePath = $uploadsDir;
                    if (!empty($file['diretorio_id'])) {
                        $filePath .= $file['diretorio_id'] . '/';
                    }
                    $filePath .= $file['arquivo'];
                    
                    $fileDeleted = true;
                    if (file_exists($filePath)) {
                        $fileDeleted = unlink($filePath);
                    }
                    
                    if ($fileDeleted) {
                        // Remove dos metadados
                        unset($metadata['arquivos'][$key]);
                        $metadata['arquivos'] = array_values($metadata['arquivos']); // Reindexação
                        
                        // Salva os metadados
                        if (file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT))) {
                            $message = ['type' => 'success', 'text' => 'Arquivo excluído com sucesso.'];
                        } else {
                            $message = ['type' => 'error', 'text' => 'Arquivo excluído, mas erro ao atualizar metadados.'];
                        }
                    } else {
                        $message = ['type' => 'error', 'text' => 'Erro ao excluir o arquivo.'];
                    }
                } else {
                    $message = ['type' => 'error', 'text' => 'Você não tem permissão para excluir este arquivo.'];
                }
                
                break;
            }
        }
        
        if (!$fileFound) {
            $message = ['type' => 'error', 'text' => 'Arquivo não encontrado.'];
        }
    }
}

// Filtra os diretórios conforme o nível de acesso
function getDirectories($metadata, $accessLevel, $userTurma, $parentDir = '') {
    $directories = [];
    
    foreach ($metadata['diretorios'] as $dir) {
        if ($dir['parent_dir'] === $parentDir) {
            if ($accessLevel == 3 || $dir['turma'] == $userTurma) {
                $directories[] = $dir;
            }
        }
    }
    
    // Ordena os diretórios por nome
    usort($directories, function($a, $b) {
        return strcasecmp($a['nome'], $b['nome']);
    });
    
    return $directories;
}

// Filtra os arquivos conforme o nível de acesso
function getFiles($metadata, $accessLevel, $userTurma, $diretorioId = '') {
    $files = [];
    
    foreach ($metadata['arquivos'] as $file) {
        if ($file['diretorio_id'] === $diretorioId) {
            if ($accessLevel == 3 || $file['turma'] == $userTurma) {
                $files[] = $file;
            }
        }
    }
    
    // Ordena os arquivos por data de upload (mais recentes primeiro)
    usort($files, function($a, $b) {
        return strtotime($b['data_upload']) - strtotime($a['data_upload']);
    });
    
    return $files;
}

// Obter diretórios para a visualização atual
$directories = getDirectories($metadata, $accessLevel, $userTurma, $currentDir);

// Obter arquivos para a visualização atual
$files = getFiles($metadata, $accessLevel, $userTurma, $currentDir);

// Inclui o cabeçalho HTML
include_once 'includes/header.php';

// Inclui a barra lateral de navegação
include_once 'includes/sidebar_nav.php';
?>

<div class="flex-1 flex flex-col">
    <!-- Barra superior com título e botão de menu -->
    <header class="bg-white shadow-md p-4 flex items-center justify-between">
        <button id="openSidebarBtn" class="text-gray-500 hover:text-gray-700 lg:hidden">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-xl font-bold text-gray-800">Repositório de Arquivos</span>
    </header>

    <!-- Conteúdo principal -->
    <main class="flex-1 p-6 overflow-y-auto bg-gray-50">
        
        <?php if (isset($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>
        
        <!-- Cabeçalho com breadcrumbs e ações -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <div class="flex items-center space-x-2 mb-4 md:mb-0">
                <a href="?dir=" class="text-blue-600 hover:text-blue-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </a>
                <?php if (!empty($currentDir)): ?>
                    <span class="text-gray-400">/</span>
                    <span class="font-medium text-gray-700"><?php echo htmlspecialchars($dirName); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                <?php if ($accessLevel >= 2): ?>
                    <!-- Botão para criar pasta (nível 2+) -->
                    <button id="newFolderBtn" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        Nova Pasta
                    </button>
                    
                    <!-- Botão para upload (nível 2+) -->
                    <button id="uploadBtn" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Upload de Arquivo
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Listagem de pastas -->
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Pastas</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
            <?php if (count($directories) > 0): ?>
                <?php foreach ($directories as $dir): ?>
                    <a href="?dir=<?php echo $dir['id']; ?>" 
                       class="flex items-center p-4 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow group">
                        <div class="p-2 rounded-lg bg-blue-100 text-blue-600 mr-3 group-hover:bg-blue-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </div>
                        <div class="flex-grow">
                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($dir['nome']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($dir['turma']); ?></p>
                        </div>
                        <div class="text-gray-400 group-hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full p-8 text-center bg-white rounded-lg border border-gray-200">
                    <p class="text-gray-600">Nenhuma pasta encontrada.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Listagem de arquivos -->
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
            <?php echo !empty($currentDir) ? "Arquivos em " . htmlspecialchars($dirName) : "Arquivos Recentes"; ?>
        </h2>
        
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamanho</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($files) > 0): ?>
                            <?php foreach ($files as $file): ?>
                                <?php
                                $fileExt = strtolower(pathinfo($file['nome'], PATHINFO_EXTENSION));
                                
                                $fileIcon = '';
                                if (in_array($fileExt, ['pdf'])) {
                                    $fileIcon = 'text-red-600';
                                } elseif (in_array($fileExt, ['doc', 'docx'])) {
                                    $fileIcon = 'text-blue-600';
                                } elseif (in_array($fileExt, ['xls', 'xlsx'])) {
                                    $fileIcon = 'text-green-600';
                                } elseif (in_array($fileExt, ['ppt', 'pptx'])) {
                                    $fileIcon = 'text-orange-600';
                                } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                                    $fileIcon = 'text-purple-600';
                                } else {
                                    $fileIcon = 'text-gray-600';
                                }
                                
                                $fileSizeFormatted = '';
                                $fileSize = $file['tamanho'];
                                if ($fileSize < 1024) {
                                    $fileSizeFormatted = $fileSize . " B";
                                } elseif ($fileSize < 1048576) {
                                    $fileSizeFormatted = round($fileSize / 1024, 2) . " KB";
                                } else {
                                    $fileSizeFormatted = round($fileSize / 1048576, 2) . " MB";
                                }
                                
                                $uploadDate = date("d/m/Y", strtotime($file['data_upload']));
                                
                                // Determina o caminho de download
                                $downloadPath = $uploadsDir;
                                if (!empty($file['diretorio_id'])) {
                                    $downloadPath .= $file['diretorio_id'] . '/';
                                }
                                $downloadPath .= $file['arquivo'];
                                ?>
                                
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="<?php echo $fileIcon; ?> flex-shrink-0 mr-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($file['nome']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo strtoupper($fileExt); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($file['turma']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $uploadDate; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $fileSizeFormatted; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?php echo $downloadPath; ?>" download class="text-blue-600 hover:text-blue-900 mr-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                        
                                        <?php if ($accessLevel >= 3 || ($accessLevel == 2 && $userId == $file['usuario_id'])): ?>
                                            <button onclick="confirmDelete('<?php echo $file['id']; ?>')" class="text-red-600 hover:text-red-900">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Nenhum arquivo encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal para Criar Pasta -->
<div id="folderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Nova Pasta</h3>
        
        <form action="" method="post">
            <input type="hidden" name="action" value="create_folder">
            <input type="hidden" name="parent_dir" value="<?php echo $currentDir; ?>">
            
            <div class="mb-4">
                <label for="folder_name" class="block text-sm font-medium text-gray-700 mb-1">Nome da Pasta</label>
                <input type="text" id="folder_name" name="folder_name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Ex: Anatomia, Fisiologia">
            </div>
            
            <div class="mb-4">
                <label for="folder_turma" class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                <select id="folder_turma" name="folder_turma" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <?php if ($accessLevel == 3): ?>
                        <option value="">Selecione a turma</option>
                        <option value="1º Ano">1º Ano</option>
                        <option value="2º Ano">2º Ano</option>
                        <option value="3º Ano">3º Ano</option>
                        <option value="4º Ano">4º Ano</option>
                        <option value="5º Ano">5º Ano</option>
                        <option value="6º Ano">6º Ano</option>
                    <?php else: ?>
                        <option value="<?php echo htmlspecialchars($userTurma); ?>" selected><?php echo htmlspecialchars($userTurma); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelFolderBtn"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Criar Pasta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Upload de Arquivo -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload de Arquivo</h3>
        
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_file">
            <input type="hidden" name="diretorio_id" value="<?php echo $currentDir; ?>">
            
            <div class="mb-4">
                <label for="file_name" class="block text-sm font-medium text-gray-700 mb-1">Nome do Arquivo (opcional)</label>
                <input type="text" id="file_name" name="file_name"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Deixe em branco para usar o nome original">
            </div>
            
            <div class="mb-4">
                <label for="file_upload" class="block text-sm font-medium text-gray-700 mb-1">Arquivo</label>
                <div class="flex items-center justify-center w-full">
                    <label class="flex flex-col w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-7">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 group-hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p id="file_label" class="pt-1 text-sm text-gray-600 group-hover:text-gray-600">Arraste ou clique para selecionar</p>
                        </div>
                        <input id="file_upload" name="file_upload" type="file" class="hidden" required onchange="updateFileLabel()" />
                    </label>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="file_turma" class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                <select id="file_turma" name="file_turma" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <?php if ($accessLevel == 3): ?>
                        <option value="">Selecione a turma</option>
                        <option value="1º Ano">1º Ano</option>
                        <option value="2º Ano">2º Ano</option>
                        <option value="3º Ano">3º Ano</option>
                        <option value="4º Ano">4º Ano</option>
                        <option value="5º Ano">5º Ano</option>
                        <option value="6º Ano">6º Ano</option>
                    <?php else: ?>
                        <option value="<?php echo htmlspecialchars($userTurma); ?>" selected><?php echo htmlspecialchars($userTurma); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelUploadBtn"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Enviar Arquivo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Confirmação para Deletar Arquivo -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmar Exclusão</h3>
        <p class="text-gray-600 mb-6">Tem certeza que deseja excluir este arquivo? Esta ação não pode ser desfeita.</p>
        
        <form action="" method="post" id="deleteForm">
            <input type="hidden" name="action" value="delete_file">
            <input type="hidden" name="file_id" id="file_id_to_delete">
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelDeleteBtn"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Excluir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Funções para os modais
    const folderModal = document.getElementById('folderModal');
    const uploadModal = document.getElementById('uploadModal');
    const deleteModal = document.getElementById('deleteModal');
    const newFolderBtn = document.getElementById('newFolderBtn');
    const uploadBtn = document.getElementById('uploadBtn');
    const cancelFolderBtn = document.getElementById('cancelFolderBtn');
    const cancelUploadBtn = document.getElementById('cancelUploadBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const fileIdToDelete = document.getElementById('file_id_to_delete');
    
    // Abrir modal de pasta
    if (newFolderBtn) {
        newFolderBtn.addEventListener('click', () => {
            folderModal.classList.remove('hidden');
        });
    }
    
    // Abrir modal de upload
    if (uploadBtn) {
        uploadBtn.addEventListener('click', () => {
            uploadModal.classList.remove('hidden');
        });
    }
    
    // Fechar modal de pasta
    if (cancelFolderBtn) {
        cancelFolderBtn.addEventListener('click', () => {
            folderModal.classList.add('hidden');
        });
    }
    
    // Fechar modal de upload
    if (cancelUploadBtn) {
        cancelUploadBtn.addEventListener('click', () => {
            uploadModal.classList.add('hidden');
        });
    }
    
    // Fechar modal de exclusão
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            deleteModal.classList.add('hidden');
        });
    }
    
    // Fechar modais ao clicar fora deles
    window.addEventListener('click', (e) => {
        if (e.target === folderModal) folderModal.classList.add('hidden');
        if (e.target === uploadModal) uploadModal.classList.add('hidden');
        if (e.target === deleteModal) deleteModal.classList.add('hidden');
    });
    
    // Função para atualizar o label do arquivo
    function updateFileLabel() {
        const fileUpload = document.getElementById('file_upload');
        const fileLabel = document.getElementById('file_label');
        
        if (fileUpload.files.length > 0) {
            fileLabel.textContent = fileUpload.files[0].name;
        } else {
            fileLabel.textContent = 'Arraste ou clique para selecionar';
        }
    }
    
    // Função para confirmar a exclusão
    function confirmDelete(fileId) {
        fileIdToDelete.value = fileId;
        deleteModal.classList.remove('hidden');
    }
</script>

<?php include_once 'includes/footer.php'; ?>