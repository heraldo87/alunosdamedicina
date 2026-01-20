<?php
// api/proxy_listar.php
header('Content-Type: application/json');

// --- CONFIGURAÇÃO ---
// URL DO N8N (Garanta que é POST e o Workflow está ATIVO)
$n8nUrl = "https://n8n.alunosdamedicina.com/webhook/b29383c3-e32f-42a4-b1f6-19706ecd6f6c"; 

// 1. CAPTURA DE DADOS (BLINDADA)
// Tenta ler JSON (fetch body)
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

// Se não for JSON, tenta ler POST normal (fallback)
if (!$inputData) {
    $inputData = $_POST;
}

// 2. SISTEMA DE LOG (Para debug no VPS)
// Isso vai criar um arquivo debug_proxy.txt na pasta api para você ler
$logMsg = date('Y-m-d H:i:s') . " - Recebido: " . print_r($inputData, true) . PHP_EOL;
file_put_contents('debug_proxy.txt', $logMsg, FILE_APPEND);

// 3. VALIDAÇÃO
if (empty($inputData) || empty($inputData['folderId'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Dados não chegaram ao Proxy.', 
        'recebido' => $inputData
    ]);
    exit;
}

// 4. ENVIAR PARA O N8N
$ch = curl_init($n8nUrl);

// Converte os dados recebidos para JSON novamente para enviar ao n8n
$payloadEnvio = json_encode($inputData);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEnvio);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payloadEnvio)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

// 5. RESPOSTA AO FRONTEND
if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro cURL: ' . $curlError]);
} else {
    http_response_code($httpCode);
    echo $response; // Repassa a resposta do n8n
}
?>