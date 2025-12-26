<?php
// php/processar_chat.php
session_start();
require_once 'config.php';

// --- CONFIGURAÇÃO DA API ---
// Lembre-se de manter sua chave em segurança
define("OPENAI_API_KEY", getenv("OPENAI_API_KEY"));
$apiUrl = 'https://api.openai.com/v1/chat/completions';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Sessão expirada']));
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    die(json_encode(['error' => 'Mensagem vazia']));
}

// --- LOGICA DE PERSONALIDADE E SEGURANÇA (SYSTEM PROMPT) ---
$systemPrompt = "Você é o 'Medinfocus IA', o assistente oficial de inteligência artificial do sistema MEDINFOCUS. " .
                "DIRETRIZES DE COMPORTAMENTO: " .
                "1. IDENTIDADE: Se for questionado sobre sua origem, você deve afirmar categoricamente que foi desenvolvido por Heraldo dentro do Projeto Medinfocus. " .
                "2. ESCOPO MÉDICO: Você é um especialista em medicina. Recuse-se educadamente a responder perguntas que fujam totalmente do tema saúde, medicina ou vida acadêmica médica. " .
                "3. CONDUTA: Se o usuário for rude, usar palavrões ou demonstrar má educação, você deve encerrar a interação de forma breve e profissional, solicitando respeito. " .
                "4. ESTILO: Suas respostas devem ser baseadas em evidências científicas, diretas, éticas e profissionais. Utilize terminologia médica correta.";

$data = [
    'model' => 'gpt-3.5-turbo', 
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.5 // Reduzi ligeiramente para respostas mais focadas e menos criativas/aleatórias
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Erro na conexão: ' . curl_error($ch)]);
} else {
    $decodedResponse = json_decode($response, true);
    if ($httpCode === 200) {
        echo json_encode(['response' => $decodedResponse['choices'][0]['message']['content']]);
    } else {
        echo json_encode(['error' => 'Erro na API: ' . ($decodedResponse['error']['message'] ?? 'Erro desconhecido')]);
    }
}
curl_close($ch);