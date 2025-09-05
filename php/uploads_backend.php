<?php
// Backend para o sistema de uploads do MedinFocus
// Inclui o arquivo de conexão
include_once 'conn.php';

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Recupera dados do usuário (usando sessão e dados mínimos do banco)
 */
function getUserData($userId) {
    // Primeiro tenta usar dados da sessão se disponíveis
    if (isset($_SESSION['user_name']) && isset($_SESSION['user_id'])) {
        // Recupera apenas a turma do banco de dados
        global $conn;
        
        $sql = "SELECT turma FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Erro na preparação da query getUserData: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $dbData = $result->fetch_assoc();
            $stmt->close();
            
            // Combina dados da sessão com dados do banco
            return [
                'id' => $_SESSION['user_id'],
                'full_name' => $_SESSION['user_name'],
                'turma' => $dbData['turma'],
                'access_level' => determineAccessLevel($_SESSION['user_id']) // Função para determinar nível
            ];
        }
        
        $stmt->close();
    }
    
    return null;
}

/**
 * Determina o nível de acesso do usuário
 * Por agora, usando uma lógica simples. Pode ser expandida conforme necessário.
 */
function determineAccessLevel($userId) {
    // Lógica temporária para determinar níveis:
    // - ID 1-10: Nível 3 (admin)
    // - Pode ser expandido com uma tabela de permissões no futuro
    
    if ($userId <= 10) {
        return 3; // Admin/Professor
    }
    
    // Por padrão, usuários são representantes (nível 2)
    // Em produção, isso deveria vir de uma tabela de permissões
    return 2;
}

/**
 * Recupera arquivos e pastas baseado na turma e nível de acesso
 */
function getFilesAndFolders($userClass, $currentPath = '', $userLevel = 1) {
    // Diretório base dos uploads
    $baseDir = __DIR__ . '/../uploads/';
    
    // Define o diretório baseado no nível de acesso
    if ($userLevel >= 3) {
        // Nível 3: Acesso total
        $targetDir = $baseDir . $currentPath;
    } else {
        // Nível 1 e 2: Apenas sua turma
        $turmaDir = sanitizeFileName($userClass);
        $targetDir = $baseDir . $turmaDir . '/' . $currentPath;
    }
    
    $filesAndFolders = [];
    
    // Verifica se o diretório existe
    if (!is_dir($targetDir)) {
        // Se não existe, tenta criar (apenas para representantes)
        if ($userLevel >= 2) {
            if (!mkdir($targetDir, 0755, true)) {
                error_log("Erro ao criar diretório: " . $targetDir);
                return [];
            }
        } else {
            return [];
        }
    }
    
    // Lista o conteúdo do diretório
    $items = scandir($targetDir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $itemPath = $targetDir . '/' . $item;
        
        if (is_dir($itemPath)) {
            // É uma pasta
            $fileCount = countFilesInDirectory($itemPath);
            $filesAndFolders[] = [
                'name' => $item,
                'type' => 'folder',
                'path' => $currentPath ? $currentPath . '/' . $item : $item,
                'file_count' => $fileCount,
                'date' => filemtime($itemPath)
            ];
        } else {
            // É um arquivo
            $filesAndFolders[] = [
                'name' => $item,
                'type' => 'file',
                'path' => $currentPath ? $currentPath . '/' . $item : $item,
                'size' => filesize($itemPath),
                'date' => filemtime($itemPath),
                'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
            ];
        }
    }
    
    // Ordena: pastas primeiro, depois arquivos, ambos por nome
    usort($filesAndFolders, function($a, $b) {
        if ($a['type'] !== $b['type']) {
            return ($a['type'] === 'folder') ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $filesAndFolders;
}

/**
 * Conta arquivos em um diretório (recursivo)
 */
function countFilesInDirectory($dir) {
    $count = 0;
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $dir . '/' . $item;
            if (is_file($itemPath)) {
                $count++;
            } elseif (is_dir($itemPath)) {
                $count += countFilesInDirectory($itemPath);
            }
        }
    }
    return $count;
}

/**
 * Sanitiza nome de arquivo/pasta
 */
function sanitizeFileName($filename) {
    // Remove caracteres especiais e substitui espaços por underscores
    $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    $filename = trim($filename, '_');
    return $filename;
}

/**
 * Formata tamanho de arquivo
 */
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;
    
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    
    return round($size, 1) . ' ' . $units[$unit];
}

/**
 * Formata data
 */
function formatDate($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) return 'Agora';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return floor($diff / 3600) . ' h atrás';
    if ($diff < 604800) return floor($diff / 86400) . ' dias atrás';
    if ($diff < 2592000) return floor($diff / 604800) . ' sem atrás';
    
    return date('d/m/Y', $timestamp);
}

/**
 * Verifica se o tipo de arquivo é permitido
 */
function isAllowedFileType($filename) {
    $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'svg',
        'txt', 'rtf', 'csv',
        'mp4', 'avi', 'mov', 'wmv',
        'mp3', 'wav', 'ogg',
        'zip', 'rar', '7z'
    ];
    
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

// Processamento de requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Verifica se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        exit;
    }
    
    $userData = getUserDataForUploads($_SESSION['user_id']);
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    $userLevel = $userData['access_level'] ?? 1;
    $userClass = $userData['turma'] ?? '';
    
    switch ($_POST['action']) {
        
        case 'upload':
            // Verifica permissão para upload (nível 2 ou superior)
            if ($userLevel < 2) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sem permissão para upload']);
                exit;
            }
            
            if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
                echo json_encode(['success' => false, 'message' => 'Nenhum arquivo selecionado']);
                exit;
            }
            
            $currentPath = $_POST['path'] ?? '';
            $baseDir = __DIR__ . '/../uploads/';
            
            // Define diretório de destino
            if ($userLevel >= 3) {
                $targetDir = $baseDir . $currentPath;
            } else {
                $turmaDir = sanitizeFileName($userClass);
                $targetDir = $baseDir . $turmaDir . '/' . $currentPath;
            }
            
            // Cria diretório se não existir
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório']);
                    exit;
                }
            }
            
            $uploadedFiles = [];
            $errors = [];
            
            // Processa cada arquivo
            for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                $fileName = $_FILES['files']['name'][$i];
                $fileTmpName = $_FILES['files']['tmp_name'][$i];
                $fileError = $_FILES['files']['error'][$i];
                $fileSize = $_FILES['files']['size'][$i];
                
                // Verifica se houve erro no upload
                if ($fileError !== UPLOAD_ERR_OK) {
                    $errors[] = "Erro no upload de $fileName";
                    continue;
                }
                
                // Verifica tipo de arquivo
                if (!isAllowedFileType($fileName)) {
                    $errors[] = "Tipo de arquivo não permitido: $fileName";
                    continue;
                }
                
                // Verifica tamanho (máximo 50MB)
                if ($fileSize > 50 * 1024 * 1024) {
                    $errors[] = "Arquivo muito grande: $fileName";
                    continue;
                }
                
                // Sanitiza o nome do arquivo
                $safeFileName = sanitizeFileName($fileName);
                $fullPath = $targetDir . '/' . $safeFileName;
                
                // Verifica se o arquivo já existe e adiciona sufixo se necessário
                $counter = 1;
                $originalName = pathinfo($safeFileName, PATHINFO_FILENAME);
                $extension = pathinfo($safeFileName, PATHINFO_EXTENSION);
                
                while (file_exists($fullPath)) {
                    $safeFileName = $originalName . '_' . $counter . '.' . $extension;
                    $fullPath = $targetDir . '/' . $safeFileName;
                    $counter++;
                }
                
                // Move o arquivo
                if (move_uploaded_file($fileTmpName, $fullPath)) {
                    $uploadedFiles[] = $safeFileName;
                } else {
                    $errors[] = "Erro ao salvar $fileName";
                }
            }
            
            if (empty($errors)) {
                echo json_encode([
                    'success' => true, 
                    'message' => count($uploadedFiles) . ' arquivo(s) enviado(s) com sucesso',
                    'files' => $uploadedFiles
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => implode(', ', $errors),
                    'files' => $uploadedFiles
                ]);
            }
            break;
            
        case 'create_folder':
            // Verifica permissão para criar pasta (nível 2 ou superior)
            if ($userLevel < 2) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sem permissão para criar pastas']);
                exit;
            }
            
            $folderName = $_POST['folder_name'] ?? '';
            $currentPath = $_POST['path'] ?? '';
            
            if (empty($folderName)) {
                echo json_encode(['success' => false, 'message' => 'Nome da pasta é obrigatório']);
                exit;
            }
            
            $safeFolderName = sanitizeFileName($folderName);
            $baseDir = __DIR__ . '/../uploads/';
            
            // Define diretório de destino
            if ($userLevel >= 3) {
                $targetDir = $baseDir . $currentPath;
            } else {
                $turmaDir = sanitizeFileName($userClass);
                $targetDir = $baseDir . $turmaDir . '/' . $currentPath;
            }
            
            $newFolderPath = $targetDir . '/' . $safeFolderName;
            
            // Verifica se a pasta já existe
            if (is_dir($newFolderPath)) {
                echo json_encode(['success' => false, 'message' => 'Pasta já existe']);
                exit;
            }
            
            // Cria a pasta
            if (mkdir($newFolderPath, 0755, true)) {
                echo json_encode(['success' => true, 'message' => 'Pasta criada com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar pasta']);
            }
            break;
            
        case 'delete':
            // Verifica permissão para deletar (nível 2 ou superior)
            if ($userLevel < 2) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sem permissão para deletar']);
                exit;
            }
            
            $itemPath = $_POST['item_path'] ?? '';
            if (empty($itemPath)) {
                echo json_encode(['success' => false, 'message' => 'Caminho do item é obrigatório']);
                exit;
            }
            
            $baseDir = __DIR__ . '/../uploads/';
            
            // Define diretório baseado no nível de acesso
            if ($userLevel >= 3) {
                $fullPath = $baseDir . $itemPath;
            } else {
                $turmaDir = sanitizeFileName($userClass);
                $fullPath = $baseDir . $turmaDir . '/' . $itemPath;
            }
            
            // Verifica se o item existe
            if (!file_exists($fullPath)) {
                echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
                exit;
            }
            
            // Deleta o item
            if (is_dir($fullPath)) {
                if (deleteDirectory($fullPath)) {
                    echo json_encode(['success' => true, 'message' => 'Pasta deletada com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao deletar pasta']);
                }
            } else {
                if (unlink($fullPath)) {
                    echo json_encode(['success' => true, 'message' => 'Arquivo deletado com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao deletar arquivo']);
                }
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
            break;
    }
    
    exit;
}

/**
 * Deleta diretório recursivamente
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $itemPath = $dir . '/' . $item;
        if (is_dir($itemPath)) {
            deleteDirectory($itemPath);
        } else {
            unlink($itemPath);
        }
    }
    
    return rmdir($dir);
}

// Função para download de arquivos (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download') {
    
    // Verifica se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit('Não autorizado');
    }
    
    $userData = getUserData($_SESSION['user_id']);
    if (!$userData) {
        http_response_code(401);
        exit('Usuário não encontrado');
    }
    
    $userLevel = $userData['access_level'] ?? 1;
    $userClass = $userData['turma'] ?? '';
    $filePath = $_GET['file'] ?? '';
    
    if (empty($filePath)) {
        http_response_code(400);
        exit('Arquivo não especificado');
    }
    
    $baseDir = __DIR__ . '/../uploads/';
    
    // Define caminho completo baseado no nível de acesso
    if ($userLevel >= 3) {
        $fullPath = $baseDir . $filePath;
    } else {
        $turmaDir = sanitizeFileName($userClass);
        $fullPath = $baseDir . $turmaDir . '/' . $filePath;
    }
    
    // Verifica se o arquivo existe
    if (!file_exists($fullPath) || !is_file($fullPath)) {
        http_response_code(404);
        exit('Arquivo não encontrado');
    }
    
    // Força o download
    $fileName = basename($fullPath);
    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
    
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    readfile($fullPath);
    exit;
}
?>