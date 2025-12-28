<?php
// ARQUIVO: php/processar_chat.php

// 1. INÍCIO DA SESSÃO (Isso corrige o erro "Sessão expirada")
session_start();

// 2. Cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// 3. Verificação de Segurança
if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin'])) {
    echo json_encode(['error' => 'Sessão expirada. Recarregue a página.']);
    exit;
}

// 4. Inclusão da Configuração (Para pegar a AI_API_KEY)
require_once 'config.php'; // Certifique-se que o caminho está certo

// Recebe o JSON do Javascript
$input = json_decode(file_get_contents('php://input'), true);
$mensagemUsuario = $input['message'] ?? '';

if (empty($mensagemUsuario)) {
    echo json_encode(['error' => 'Mensagem vazia.']);
    exit;
}

// 5. Chamada à OpenAI
try {
    $apiKey = AI_API_KEY; // Constante definida no config.php

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    $payload = [
        "model" => "gpt-3.5-turbo", // Ou gpt-4-turbo
        "messages" => [
            ["role" => "system", "content" => "Você é um assistente médico."],
            ["role" => "user", "content" => $mensagemUsuario]
        ]
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $result = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($result, true);
    $aiText = $response['choices'][0]['message']['content'] ?? 'Erro ao processar resposta da IA.';

    echo json_encode(['response' => $aiText]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro interno do servidor.']);
}
?>