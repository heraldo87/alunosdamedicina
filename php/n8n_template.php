<?php
session_start(); // garante que a sessão está iniciada

// Instanciamento de URL para contato no n8n
$url = "https://n8n.alunosdamedicina.com/webhook/forum";

// Recupera ID do usuário logado da sessão
$userId = $_SESSION['user_id'] ?? 0; // se não houver sessão, usa 0

// Monta os dados que serão enviados no JSON
$data = [
    "msg"        => "Olá fórum!",
    "acao"       => 1,
    "user_id"    => $userId,              // <- enviado para o n8n
    "timestamp"  => date("Y-m-d H:i:s")   // <- timestamp atual
];

// Configuração para envio de POST via CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Resposta do n8n
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "❌ Erro cURL: " . curl_error($ch);
} else {
    echo "✅ Resposta bruta do n8n:\n";
    var_dump($response);

    echo "\n\n✅ Resposta decodificada:\n";
    $decoded = json_decode($response, true);
    print_r($decoded);
}

curl_close($ch);
