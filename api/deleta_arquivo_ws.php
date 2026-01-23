<?php
// api/deleta_arquivo_ws.php

// --- BLINDAGEM CONTRA ERROS HTML ---
// Isso impede que Warnings do PHP quebrem o JSON no frontend
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Define que a resposta será SEMPRE JSON
header('Content-Type: application/json; charset=utf-8');

try {
    session_start();

    // Verificação de caminho para debug
    if (!file_exists('../php/config.php')) {
        throw new Exception("Erro interno: Arquivo de configuração não encontrado.");
    }

    require_once '../php/config.php';

    // 1. Segurança: Verifica se está logado
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
        exit;
    }

    // 2. Recebe os dados JSON
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao ler dados enviados (JSON Inválido).");
    }

    $fileId = $input['file_id'] ?? '';
    $driveId = $input['drive_id'] ?? '';

    if (empty($fileId)) {
        echo json_encode(['success' => false, 'error' => 'ID do arquivo não fornecido.']);
        exit;
    }

    // 3. Configuração da API Externa
    $externalApiUrl = 'https://n8n.alunosdamedicina.com/webhook/74afaf43-71ef-4626-b268-08329b0ecc85'; 

    // 4. Payload
    $payload = [
        'file_id' => $fileId,
        'drive_id' => $driveId,
        'user_email' => $_SESSION['email'] ?? 'usuario_desconhecido',
        'action' => 'delete'
    ];

    // 5. Envia requisição cURL
    $ch = curl_init($externalApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-MedInFocus-Auth: ' . ($_SESSION['id'] ?? '0')
    ]);
    
    // Timeout para não travar o servidor
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 

    $responseRaw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 6. Resposta
    if ($curlError) {
        echo json_encode(['success' => false, 'error' => 'Erro conexão externa: ' . $curlError]);
    } else {
        // Se a API externa respondeu 200 (OK), consideramos sucesso
        if ($httpCode >= 200 && $httpCode < 300) {
            echo json_encode(['success' => true, 'message' => 'Arquivo processado com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha remota. Código: ' . $httpCode]);
        }
    }

} catch (Exception $e) {
    // Captura qualquer erro fatal e retorna JSON limpo
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>