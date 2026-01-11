<?php
/**
 * API - Deletar Arquivo do Workspace (Versão Segura)
 */
// 1. Evita que Warnings do PHP quebrem o JSON
error_reporting(0); 
ini_set('display_errors', 0);

// 2. Define cabeçalho JSON imediatamente
header('Content-Type: application/json; charset=utf-8');

session_start();

// Resposta padrão em caso de erro crítico
$response = ['success' => false, 'message' => 'Erro desconhecido'];

try {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        throw new Exception('Usuário não autenticado.');
    }

    // Recebe o JSON do fetch
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    $fileId = $input['file_id'] ?? null;
    $fileName = $input['file_name'] ?? 'Desconhecido';

    if (!$fileId) {
        throw new Exception('ID do arquivo inválido.');
    }

    // URL do Webhook do n8n para DELETAR
    // ATENÇÃO: Confirme se o UUID abaixo está correto ou se precisa atualizar
    $n8nWebhookDelete = 'https://n8n.alunosdamedicina.com/webhook/af6be96f-df69-4f77-b4ca-9ac230024e28'; 

    $payload = [
        'acao' => 'deletar_arquivo',
        'file_id' => $fileId,
        'file_name' => $fileName,
        'deleted_by' => $_SESSION['user_name'] ?? 'Usuario',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $ch = curl_init($n8nWebhookDelete);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $curlResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro Curl: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        // Tenta gravar log, mas usa @ para não gerar erro fatal se falhar permissão
        $logEntry = date('Y-m-d H:i:s') . " - DELETE - User: " . ($_SESSION['user_name'] ?? '?') . " - File: $fileName ($fileId)\n";
        @file_put_contents('../log_automacao.txt', $logEntry, FILE_APPEND);

        $response = ['success' => true];
    } else {
        $response = ['success' => false, 'message' => "O N8N retornou erro: $httpCode"];
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;
?>