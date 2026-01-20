<?php
/**
 * MEDINFOCUS - Proxy de Listagem de Arquivos
 * Conecta o Frontend ao n8n, injetando contexto de segurança e usuário.
 */

// 1. INICIA SESSÃO (CRÍTICO: Deve ser a primeira linha)
session_start();

header('Content-Type: application/json');

// --- CONFIGURAÇÃO ---
// URL do Webhook do n8n
$n8nUrl = "https://n8n.alunosdamedicina.com/webhook-test/b29383c3-e32f-42a4-b1f6-19706ecd6f6c"; 

// 2. VERIFICAÇÃO DE SEGURANÇA (Baseado no login.php)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Sessão expirada ou inválida. Por favor, faça login novamente.']);
    exit;
}

// 3. CAPTURA DE DADOS DO FRONTEND
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

// Fallback para POST tradicional se JSON falhar
if (!$inputData) {
    $inputData = $_POST;
}

// Validação básica do Input
$folderId = $inputData['folderId'] ?? '';

// 4. MONTAGEM DO PAYLOAD (DADOS + CONTEXTO DO USUÁRIO)
// Aqui mesclamos o que o usuário quer ver (folderId) com QUEM ele é (Sessão)
$payload = [
    'action'            => 'listar_arquivos',
    'folderId'          => $folderId,
    'request_timestamp' => date('Y-m-d H:i:s'),
    'user_context'      => [
        'id'   => $_SESSION['user_id'] ?? 0,    //
        'nome' => $_SESSION['user_name'] ?? 'Desconhecido',
        'tipo' => $_SESSION['user_type'] ?? 'aluno'
    ]
];

// 5. SISTEMA DE LOG (Para debug no VPS)
// Grava o payload completo para verificar se os dados do usuário estão indo
$logMsg = date('Y-m-d H:i:s') . " - Enviando para n8n: " . json_encode($payload) . PHP_EOL;
file_put_contents('debug_proxy.txt', $logMsg, FILE_APPEND);

// 6. ENVIAR PARA O N8N VIA CURL
$ch = curl_init($n8nUrl);
$payloadJson = json_encode($payload);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payloadJson),
    'X-Source: MedInFocus-Proxy' // Cabeçalho extra para identificar a origem no n8n
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Aumentei um pouco para listagens grandes
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Cuidado em produção, mas ok para teste interno

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

// 7. RESPOSTA AO FRONTEND
if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno de comunicação (cURL): ' . $curlError]);
} else {
    // Repassa o código HTTP do n8n (ex: 200, 404, 500)
    http_response_code($httpCode);
    
    // Tenta decodificar para garantir que é JSON válido, senão envia como texto em um JSON
    $decodedResponse = json_decode($response);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo $response;
    } else {
        // Se o n8n retornar algo que não é JSON (ex: erro HTML), encapsula
        echo json_encode(['n8n_response' => $response]);
    }
}
?>