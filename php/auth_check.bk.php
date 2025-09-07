<?php
// Backend para o sistema de uploads do MedinFocus (versão minimal-robusta)
// Dep.: conn.php (MySQLi em $conn)

include_once 'conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================= Helpers JSON =================
function json_ok(array $data = [], int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}
function json_err(string $msg, int $status = 400, array $extra = []): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => false, 'message' => $msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// ============== Sanitização mínima =============
function sanitizeFileName($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    return trim($filename, '_');
}
// sanitiza cada segmento do caminho (evita ../)
function sanitizePath($path) {
    $parts = array_filter(explode('/', str_replace('\\', '/', (string)$path)), function ($p) {
        return $p !== '' && $p !== '.' && $p !== '..';
    });
    $clean = array_map('sanitizeFileName', $parts);
    return implode('/', $clean);
}

// ============== Sessão / Usuário ==============
/**
 * Busca dados essenciais do usuário.
 * Mantém compatibilidade com seu código (retorna 'access_level').
 */
function getUserData($userId) {
    global $conn;

    if (!is_numeric($userId) || (int)$userId <= 0) return null;

    $sql = "SELECT id,
                   COALESCE(full_name, email) AS display_name,
                   turma, turno,
                   nivel_acesso AS access_level,
                   COALESCE(aprovacao, 0) AS aprovacao
            FROM usuarios
            WHERE id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Erro na preparação do SELECT getUserData: " . $conn->error);
        return null;
    }
    $uid = (int)$userId;
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) return null;

    // Monta retorno compatível
    return [
        'id'           => (int)$row['id'],
        'full_name'    => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ($row['display_name'] ?? 'Usuário'),
        'turma'        => $row['turma'] ?? '',
        'turno'        => $row['turno'] ?? '',
        'access_level' => (int)$row['access_level'],
        'aprovacao'    => (int)$row['aprovacao'],
    ];
}

/**
 * Lógica antiga mantida (mas não é mais necessária p/ access_level).
 * Mantemos para compatibilidade — não é usada se getUserData achar nivel_acesso.
 */
function determineAccessLevel($userId) {
    if ($userId <= 10) return 3;
    return 2;
}

// ============== FS utils (iguais ao seu) ==============
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

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024; $unit++;
    }
    return round($size, 1) . ' ' . $units[$unit];
}

function formatDate($timestamp) {
    $now = time(); $diff = $now - $timestamp;
    if ($diff < 60) return 'Agora';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return floor($diff / 3600) . ' h atrás';
    if ($diff < 604800) return floor($diff / 86400) . ' dias atrás';
    if ($diff < 2592000) return floor($diff / 604800) . ' sem atrás';
    return date('d/m/Y', $timestamp);
}

function isAllowedFileType($filename) {
    $allowedExtensions = [
        'pdf','doc','docx','xls','xlsx','ppt','pptx',
        'jpg','jpeg','png','gif','svg',
        'txt','rtf','csv',
        'mp4','avi','mov','wmv',
        'mp3','wav','ogg',
        'zip','rar','7z'
    ];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions, true);
}

// ============ Aceitar JSON e normalizar action ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se vier application/json, popular $_POST:
    if (empty($_POST) && isset($_SERVER['CONTENT_TYPE']) &&
        stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $jsonBody = json_decode(file_get_contents('php://input'), true);
        if (is_array($jsonBody)) $_POST = $jsonBody;
    }
}

// ============== Ações (AJAX via POST) ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Sessão obrigatória
    if (!isset($_SESSION['user_id'])) {
        json_err('Não autorizado', 401);
    }

    $userData = getUserData($_SESSION['user_id']);
    if (!$userData) {
        json_err('Usuário não encontrado', 401);
    }
    if ((int)($userData['aprovacao'] ?? 0) !== 1) {
        json_err('Cadastro pendente de aprovação', 403);
    }

    $userLevel = (int)($userData['access_level'] ?? determineAccessLevel($_SESSION['user_id']));
    $userClass = (string)($userData['turma'] ?? '');

    $action = strtolower(trim((string)$_POST['action']));

    switch ($action) {

        case 'upload': {
            if ($userLevel < 2) json_err('Sem permissão para upload', 403);
            if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
                json_err('Nenhum arquivo selecionado');
            }

            $currentPath = sanitizePath($_POST['path'] ?? '');
            $baseDir = rtrim(__DIR__ . '/../uploads', '/');

            // Define diretório de destino
            if ($userLevel >= 3) {
                $targetDir = $baseDir . ($currentPath ? '/' . $currentPath : '');
            } else {
                $turmaDir  = sanitizeFileName($userClass);
                $targetDir = $baseDir . '/' . $turmaDir . ($currentPath ? '/' . $currentPath : '');
            }

            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                json_err('Erro ao criar diretório');
            }

            $uploadedFiles = [];
            $errors = [];

            for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                $fileName   = (string)$_FILES['files']['name'][$i];
                $fileTmp    = (string)$_FILES['files']['tmp_name'][$i];
                $fileError  = (int)$_FILES['files']['error'][$i];
                $fileSize   = (int)$_FILES['files']['size'][$i];

                if ($fileError !== UPLOAD_ERR_OK) { $errors[] = "Erro no upload de $fileName"; continue; }
                if (!isAllowedFileType($fileName)) { $errors[] = "Tipo não permitido: $fileName"; continue; }
                if ($fileSize > 50 * 1024 * 1024) { $errors[] = "Arquivo muito grande: $fileName"; continue; }

                $safeName  = sanitizeFileName($fileName);
                $fullPath  = $targetDir . '/' . $safeName;

                // evita overwrite
                $counter = 1;
                $base = pathinfo($safeName, PATHINFO_FILENAME);
                $ext  = pathinfo($safeName, PATHINFO_EXTENSION);
                while (file_exists($fullPath)) {
                    $cand = $base . '_' . $counter . ($ext ? '.' . $ext : '');
                    $fullPath = $targetDir . '/' . $cand;
                    $counter++;
                }

                if (move_uploaded_file($fileTmp, $fullPath)) {
                    $uploadedFiles[] = basename($fullPath);
                } else {
                    $errors[] = "Erro ao salvar $fileName";
                }
            }

            if ($errors) json_err(implode(', ', $errors), 207, ['files' => $uploadedFiles]);
            json_ok(['message' => count($uploadedFiles) . ' arquivo(s) enviado(s) com sucesso', 'files' => $uploadedFiles]);
        }

        case 'create_folder': {
            if ($userLevel < 2) json_err('Sem permissão para criar pastas', 403);

            $folderName = sanitizeFileName($_POST['folder_name'] ?? '');
            if ($folderName === '') json_err('Nome da pasta é obrigatório');

            $currentPath = sanitizePath($_POST['path'] ?? '');
            $baseDir = rtrim(__DIR__ . '/../uploads', '/');

            if ($userLevel >= 3) {
                $targetDir = $baseDir . ($currentPath ? '/' . $currentPath : '');
            } else {
                $turmaDir  = sanitizeFileName($userClass);
                $targetDir = $baseDir . '/' . $turmaDir . ($currentPath ? '/' . $currentPath : '');
            }

            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                json_err('Erro ao preparar caminho base');
            }

            $newFolderPath = $targetDir . '/' . $folderName;

            if (is_dir($newFolderPath)) json_err('Pasta já existe');
            if (!mkdir($newFolderPath, 0755, true)) json_err('Erro ao criar pasta');

            json_ok(['message' => 'Pasta criada com sucesso']);
        }

        case 'delete': {
            if ($userLevel < 2) json_err('Sem permissão para deletar', 403);

            $itemPath = sanitizePath($_POST['item_path'] ?? '');
            if ($itemPath === '') json_err('Caminho do item é obrigatório');

            $baseDir = rtrim(__DIR__ . '/../uploads', '/');
            if ($userLevel >= 3) {
                $fullPath = $baseDir . '/' . $itemPath;
            } else {
                $turmaDir = sanitizeFileName($userClass);
                $fullPath = $baseDir . '/' . $turmaDir . '/' . $itemPath;
            }

            if (!file_exists($fullPath)) json_err('Item não encontrado', 404);

            $ok = false;
            if (is_dir($fullPath)) {
                $ok = deleteDirectory($fullPath);
                if (!$ok) json_err('Erro ao deletar pasta', 500);
                json_ok(['message' => 'Pasta deletada com sucesso']);
            } else {
                $ok = @unlink($fullPath);
                if (!$ok) json_err('Erro ao deletar arquivo', 500);
                json_ok(['message' => 'Arquivo deletado com sucesso']);
            }
        }

        // (opcional) futura ação 'list' — se seu front precisar
        case 'list': {
            $currentPath = sanitizePath($_POST['path'] ?? '');
            $baseDir = rtrim(__DIR__ . '/../uploads', '/');

            if ($userLevel >= 3) {
                $targetDir = $baseDir . ($currentPath ? '/' . $currentPath : '');
            } else {
                $turmaDir  = sanitizeFileName($userClass);
                $targetDir = $baseDir . '/' . $turmaDir . ($currentPath ? '/' . $currentPath : '');
            }

            if (!is_dir($targetDir)) {
                // cria apenas para reps/admin
                if ($userLevel >= 2 && !mkdir($targetDir, 0755, true)) {
                    json_err('Erro ao criar diretório de listagem', 500);
                }
            }

            $items = [];
            if (is_dir($targetDir)) {
                $scan = scandir($targetDir) ?: [];
                foreach ($scan as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $p = $targetDir . '/' . $item;
                    $rel = ($currentPath ? $currentPath . '/' : '') . $item;
                    if (is_dir($p)) {
                        $items[] = [
                            'name' => $item, 'type' => 'folder', 'path' => $rel,
                            'file_count' => countFilesInDirectory($p),
                            'date' => @filemtime($p) ?: 0
                        ];
                    } else {
                        $items[] = [
                            'name' => $item, 'type' => 'file', 'path' => $rel,
                            'size' => @filesize($p) ?: 0,
                            'date' => @filemtime($p) ?: 0,
                            'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
                        ];
                    }
                }
                usort($items, function($a, $b) {
                    if ($a['type'] !== $b['type']) return $a['type'] === 'folder' ? -1 : 1;
                    return strcasecmp($a['name'], $b['name']);
                });
            }
            json_ok(['items' => $items]);
        }

        default:
            json_err('Ação não reconhecida', 400, ['received' => $action]);
    }
    // sai pelos json_ok / json_err
}

// ============= DELETE recursivo (igual ao seu) =============
function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $p = $dir . '/' . $item;
        if (is_dir($p)) {
            if (!deleteDirectory($p)) return false;
        } else {
            if (!@unlink($p)) return false;
        }
    }
    return @rmdir($dir);
}

// ============== Download (GET) ==============
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download') {

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); exit('Não autorizado');
    }

    $userData = getUserData($_SESSION['user_id']);
    if (!$userData) {
        http_response_code(401); exit('Usuário não encontrado');
    }
    if ((int)($userData['aprovacao'] ?? 0) !== 1) {
        http_response_code(403); exit('Cadastro pendente de aprovação');
    }

    $userLevel = (int)($userData['access_level'] ?? 1);
    $userClass = (string)($userData['turma'] ?? '');
    $filePath  = sanitizePath($_GET['file'] ?? '');

    if ($filePath === '') { http_response_code(400); exit('Arquivo não especificado'); }

    $baseDir = rtrim(__DIR__ . '/../uploads', '/');

    if ($userLevel >= 3) {
        $fullPath = $baseDir . '/' . $filePath;
    } else {
        $turmaDir = sanitizeFileName($userClass);
        $fullPath = $baseDir . '/' . $turmaDir . '/' . $filePath;
    }

    if (!file_exists($fullPath) || !is_file($fullPath)) { http_response_code(404); exit('Arquivo não encontrado'); }

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
