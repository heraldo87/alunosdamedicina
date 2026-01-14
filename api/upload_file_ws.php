<?php
// Arquivo: api/upload_file_ws.php
require_once '../php/config.php'; 
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    
    $workspace_id = $_POST['workspace_id'] ?? 1; 
    $parent_id = $_POST['parent_id'] ?? null;
    // RECEBE O ID DO DRIVE DIRETO DO FRONT-END
    $workspaceDriveId = $_POST['workspace_drive_id'] ?? null;

    if ($parent_id === '' || $parent_id === 'root' || $parent_id === 'undefined') {
        $parent_id = null;
    }

    $file = $_FILES['arquivo'];
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];
    $userId = $_SESSION['user_id'] ?? 0; 

    // --- ENVIO PARA N8N ---
    $n8n_webhook_url = "https://n8n.alunosdamedicina.com/webhook-test/a65ff3d0-e80a-4b88-ae37-abf8aa35b01b"; 

    $cfile = new CURLFile($fileTmpPath, $fileType, $fileName);
    
    $data = [
        'file' => $cfile,
        'workspace_id' => $workspace_id,
        'user_id' => $userId,
        'folder_context' => $parent_id,
        // ENVIANDO PARA O N8N
        'workspace_drive_id' => $workspaceDriveId 
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $n8n_webhook_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $n8nData = json_decode($response, true);
        $driveId = $n8nData['google_drive_id'] ?? null;

        if ($driveId) {
            try {
                $sql = "INSERT INTO arquivos 
                        (workspace_id, parent_id, nome_arquivo, tipo, google_drive_id, mime_type, tamanho_bytes, status, criado_por) 
                        VALUES (:ws_id, :parent_id, :nome, 'arquivo', :drive_id, :mime, :tamanho, 'ativo', :user_id)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':ws_id' => $workspace_id,
                    ':parent_id' => $parent_id,
                    ':nome' => $fileName,
                    ':drive_id' => $driveId,
                    ':mime' => $fileType,
                    ':tamanho' => $fileSize,
                    ':user_id' => $userId
                ]);
                
                echo json_encode(['status' => 'success', 'message' => 'Arquivo enviado!']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Erro DB.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro n8n: ID não retornado.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Falha conexão n8n.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Sem arquivo.']);
}
?>