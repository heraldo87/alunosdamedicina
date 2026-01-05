<?php
// Arquivo: api/criar_workspace.php
ob_start(); // Inicia o buffer para prevenir vazamento de HTML
session_start();
header('Content-Type: application/json');

// Desativa erros na tela (vão para o log)
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // --- VALIDAÇÕES BÁSICAS ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método inválido.", 405);
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Sessão expirada. Faça login novamente.", 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $nomePasta = $input['nome_pasta'] ?? '';

    if (empty($nomePasta)) {
        throw new Exception("O nome da pasta é obrigatório.", 400);
    }

    // --- CONFIGURAÇÃO cURL ---
    // URL do seu Webhook n8n
    $n8nWebhookUrl = 'https://n8n.alunosdamedicina.com/webhook-test/04d5dbcf-106b-416e-b8da-4f9b346290cc'; 
    
    $payload = [
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? 'Usuario',
        'folder_name' => $nomePasta,
        'action' => 'create_drive_folder'
    ];

    $ch = curl_init($n8nWebhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout de 15s
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // --- VALIDAÇÃO RIGOROSA (AQUI ESTÁ A CORREÇÃO) ---

    // 1. Falha de Conexão (DNS, Timeout, Servidor Offline)
    if ($response === false || !empty($curlError)) {
        throw new Exception("Falha1");
    }

    // 2. Servidor respondeu, mas com erro (500, 502, 404)
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Falha2");
    }

    // 3. Resposta não é JSON (Ex: Cloudflare ou Nginx devolvendo HTML de erro)
    $jsonResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Resposta inválida do servidor. Tente novamente mais tarde.");
    }

    // 4. Verificação Lógica (O n8n disse que falhou?)
    // Aqui repassamos exatamente o que o n8n mandou (sucesso ou duplicidade)
    ob_clean();
    echo json_encode($jsonResponse);

} catch (Exception $e) {
    ob_clean();
    // Força um erro para o Javascript
    http_response_code(500); 
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>