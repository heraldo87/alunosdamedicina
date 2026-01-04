<?php
// api/criar_workspace.php
session_start();

// Importante: Retornar sempre JSON
header('Content-Type: application/json');

// 1. Verificação de Segurança (Sessão)
if (!isset($_SESSION['id'])) {
    http_response_code(401); // Não autorizado
    echo json_encode(['status' => 'error', 'message' => 'Usuário não logado.']);
    exit;
}

// 2. Receber dados do Front-end (JSON)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['nome_pasta']) || empty(trim($input['nome_pasta']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'O nome da pasta é obrigatório.']);
    exit;
}

// 3. Configuração do Webhook n8n
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook-test/04d5dbcf-106b-416e-b8da-4f9b346290cc';

// Prepara os dados combinando Sessão + Input
$payloadData = [
    'id_usuario' => $_SESSION['id'],         // ID pego da sessão segura
    'email_usuario' => $_SESSION['email'] ?? 'nao_informado', // Opcional: útil para o n8n
    'nome_pasta' => trim($input['nome_pasta']),
    'origem'     => 'Painel Web',
    'timestamp'  => date('Y-m-d H:i:s')
];

// 4. Disparo via cURL
$ch = curl_init($webhookUrl);
$jsonData = json_encode($payloadData);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10s para não travar o front

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 5. Retorno para o Front-end
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        'status' => 'success', 
        'message' => 'Solicitação enviada com sucesso!',
        'n8n_response' => json_decode($response) // Repassa resposta do n8n se for JSON
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erro ao comunicar com o servidor de arquivos.',
        'debug' => $curlError
    ]);
}
?>