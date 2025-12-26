<?php
// php/get_token.php
session_start();
if (!isset($_SESSION['user_id'])) { die("Acesso negado"); }

$api_key = getenv("OPENAI_API_KEY");

$ch = curl_init('https://api.openai.com/v1/realtime/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-4o-realtime-preview-2024-12-17',
    'voice' => 'onyx', // Voz profissional e séria
    'instructions' => "Você é o 'Medinfocus IA', desenvolvido por Heraldo. " .
                      "Responda apenas sobre medicina e vida acadêmica médica. " .
                      "Seja educado. Se o usuário for mal-educado, peça respeito e encerre a conversa. " .
                      "Sua primeira frase ao conectar deve ser: 'Olá! Sou o Medinfocus IA, desenvolvido por Heraldo. Estou disponível para tirar suas dúvidas médicas. Como posso ajudar?'"
]));

$response = curl_exec($ch);
echo $response; // Retorna o JSON com a ephemeral_key
curl_close($ch);