<?php

// Instanciamento de URL para contato no n8n
$url = "https://n8n.alunosdamedicina.com/webhook/forum";
//$url = "http://181.215.135.63:5678/webhook/forum";


//Inicia sessão 
session_start(); 
$userId = $_SESSION['user_id'] ?? 0;

// Delcatação de variáveis que serão passadas pelo JSON via método POST
$data = [
    "msg"             => "Olá fórum!",
    "acao"            => 3,
    "Titulo"          => "Teste2",
    "Descriação_breve" => "Mais um teste 2",
    "timestamp"  => date("Y-m-d H:i:s"),
    "user_id" => $userId
];

// Configuração para envio de POST via CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));


// Confiruções de Repostas, neste caso, programada para receber um JSON como retorno do Respond
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
