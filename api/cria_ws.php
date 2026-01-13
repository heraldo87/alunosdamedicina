<?php
// api/cria_ws.php
header('Content-Type: application/json');
session_start();

// Carrega configurações (necessário para validações de sessão, se houver lógica lá)
require_once '../php/config.php';

// 1. SEGURANÇA E VALIDAÇÃO
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); 
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$nomeWS = trim($_POST['nome'] ?? '');

// DADOS DA SESSÃO (Usuario Logado)
$userId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION['user_email'] ?? 'usuario_sem_email@medinfocus.com'; 
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userType = $_SESSION['user_type'] ?? 'aluno';

if (empty($nomeWS)) {
    echo json_encode(['success' => false, 'message' => 'O nome do workspace é obrigatório.']);
    exit;
}

// 2. COMUNICAÇÃO COM O N8N (Webhook)
// O PHP não grava nada no banco. Ele delega tudo para o n8n.
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook/criar_workspace'; 

$payload = json_encode([
    'action' => 'create_workspace',
    'folder_name' => $nomeWS,
    'owner_id' => $userId,
    'owner_email' => $userEmail,
    'owner_name' => $userName,
    'owner_type' => $userType,
    'timestamp' => date('Y-m-d H:i:s')
]);

// Configuração do cURL
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Espera até 30s pela resposta do n8n

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 3. TRATAMENTO DA RESPOSTA
if ($httpCode >= 200 && $httpCode < 300 && $response) {
    $jsonResponse = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        // Sucesso! O n8n já criou no banco e no drive.
        echo json_encode([
            'success' => true, 
            'message' => 'Workspace criado com sucesso!',
            'n8n_data' => $jsonResponse
        ]);
    } else {
        // n8n respondeu, mas não foi JSON
        echo json_encode([
            'success' => true, // Consideramos sucesso pois o n8n processou
            'message' => 'Workspace solicitado, mas resposta do servidor foi atípica.',
            'debug' => $response
        ]);
    }
} else {
    // Falha na comunicação
    error_log("Erro n8n: " . $curlError);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro na integração: ' . ($curlError ?: 'Código HTTP ' . $httpCode)
    ]);
}
?>