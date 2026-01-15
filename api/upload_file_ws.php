<?php
// Arquivo: api/upload_file_ws.php
require_once '../php/config.php'; 
session_start();

// Define que a resposta será sempre JSON
header('Content-Type: application/json');

// 1. SEGURANÇA: Verifica Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
    exit;
}

// 2. VERIFICAÇÃO DO MÉTODO E ARQUIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    
    // Captura dados do formulário
    $workspace_id = $_POST['workspace_id'] ?? null;
    $parent_id    = $_POST['parent_id'] ?? null;
    $userId       = $_SESSION['user_id'] ?? 0;
    
    // Captura o DRIVE ID vindo do Frontend (Input Hidden)
    // Nota: O nome deve bater com o <input name="drive_id"> do formulário HTML
    $drive_id_destino = $_POST['drive_id'] ?? null;

    // Tratamento para parent_id vazio
    if (empty($parent_id) || $parent_id === 'root' || $parent_id === 'undefined') {
        $parent_id = null;
    }

    // Se não tivermos o ID do Drive de destino, paramos aqui
    if (!$drive_id_destino) {
        echo json_encode(['status' => 'error', 'message' => 'Identificador do Google Drive não fornecido.']);
        exit;
    }

    // Dados do Arquivo Físico
    $file = $_FILES['arquivo'];
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];
    $fileType = $file['type'];
    $fileSize = $file['size'];

    // --- CONFIGURAÇÃO N8N ---
    // URL do Webhook fornecida
    $n8n_webhook_url = "https://n8n.alunosdamedicina.com/webhook-test/a65ff3d0-e80a-4b88-ae37-abf8aa35b01b"; 

    // Prepara arquivo para envio via cURL
    $cfile = new CURLFile($fileTmpPath, $fileType, $fileName);
    
    $data = [
        'file' => $cfile,
        'workspace_id' => $workspace_id,
        'user_id' => $userId,
        'parent_id' => $parent_id,        // ID da pasta virtual (SQL)
        'drive_folder_id' => $drive_id_destino, // ID da pasta no Google Drive (Workspace ou Subpasta)
        'action' => 'upload_file'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $n8n_webhook_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Timeout aumentado para uploads maiores
    
    $response = curl_exec($ch);
    $httpError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 3. PROCESSAR RESPOSTA DO N8N
    if ($httpCode === 200) {
        $n8nData = json_decode($response, true);
        
        // O N8N precisa retornar o 'google_drive_id' do NOVO arquivo criado
        $newFileDriveId = $n8nData['google_drive_id'] ?? null;
        
        // Se o N8N retornou o ID, salvamos no banco
        if ($newFileDriveId) {
            try {
                // Atualize a query conforme suas colunas exatas
                $sql = "INSERT INTO arquivos 
                        (workspace_id, parent_id, nome_arquivo, tipo, google_drive_id, mime_type, tamanho_bytes, status, criado_por, criado_em) 
                        VALUES (:ws_id, :parent_id, :nome, 'arquivo', :drive_id, :mime, :tamanho, 'ativo', :user_id, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':ws_id' => $workspace_id,
                    ':parent_id' => $parent_id,
                    ':nome' => $fileName,
                    ':drive_id' => $newFileDriveId,
                    ':mime' => $fileType,
                    ':tamanho' => $fileSize,
                    ':user_id' => $userId
                ]);
                
                echo json_encode(['status' => 'success', 'message' => 'Arquivo enviado e sincronizado!']);
            } catch (PDOException $e) {
                // Log do erro no servidor (não mostra detalhes sensíveis ao usuário)
                error_log("Erro SQL Upload: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Arquivo subiu, mas falhou ao registrar no banco.']);
            }
        } else {
            error_log("Erro N8N: Resposta 200 mas sem google_drive_id. Resposta: " . $response);
            echo json_encode(['status' => 'error', 'message' => 'Erro n8n: ID do arquivo não retornado.']);
        }
    } else {
        error_log("Erro Conexão N8N: Código $httpCode. Erro: $httpError");
        echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o servidor de automação.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nenhum arquivo enviado.']);
}
?>