<?php
// api/deletar_ws.php
header('Content-Type: application/json');
session_start();

// Desabilita exibição de erros na tela para não quebrar o JSON de resposta
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

// Verifica método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

// Obtém dados
$idWorkspace = $_POST['id'] ?? null;
$userId = $_SESSION['user_id'] ?? 0; // Importante enviar quem está deletando

if (!$idWorkspace) {
    echo json_encode(['success' => false, 'message' => 'ID do workspace não fornecido.']);
    exit;
}

// 2. CONFIGURAÇÃO DO WEBHOOK N8N
// URL fornecida (Modo de Teste)
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook/deleta_ws';

// Monta o payload (dados que o N8N vai receber)
$payload = json_encode([
    'action'       => 'delete_workspace',
    'workspace_id' => $idWorkspace,
    'user_id'      => $userId,
    'request_time' => date('Y-m-d H:i:s')
]);

// 3. EXECUÇÃO DO CURL
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos para não travar o PHP

// Ignorar SSL (conforme padrão do projeto atual)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 4. PROCESSAR RESPOSTA DO N8N
if ($httpCode >= 200 && $httpCode < 300) {
    // Tenta ler o JSON de resposta do N8N, se houver
    $jsonResponse = json_decode($response, true);
    
    // Se o N8N retornou uma confirmação específica, usamos ela. 
    // Se não, assumimos sucesso pelo HTTP 200.
    $success = $jsonResponse['success'] ?? true;
    $message = $jsonResponse['message'] ?? 'Solicitação de exclusão enviada com sucesso.';

    echo json_encode([
        'success' => $success, 
        'message' => $message
    ]);
} else {
    // Erro na comunicação com N8N
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao processar exclusão no servidor.',
        'debug_info' => [
            'http_code' => $httpCode,
            'curl_error' => $curlError
        ]
    ]);
}
?>