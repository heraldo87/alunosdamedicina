<?php
// teste_webhook.php

// 1. Configurações
// URL do Webhook do n8n (ambiente de teste)
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook-test/04d5dbcf-106b-416e-b8da-4f9b346290cc';

// Dados simulados (Payload)
// Aqui estamos enviando o ID do usuário e o nome da pasta desejada
$payloadData = [
    'id_usuario' => 5,                // ID fixo para teste
    'nome_pasta' => 'Biblioteca_Medicina_Teste', // Nome da pasta a ser criada/consultada no Drive
    'timestamp'  => date('Y-m-d H:i:s') // Útil para logs no n8n
];

// 2. Preparação da Requisição (cURL)
$ch = curl_init($webhookUrl);
$jsonData = json_encode($payloadData);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 3. Execução e Tratamento da Resposta
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if(curl_errno($ch)){
    $error_msg = curl_error($ch);
    echo "<h2>❌ Falha na conexão</h2>";
    echo "<p>Erro cURL: " . $error_msg . "</p>";
} else {
    echo "<h2>📡 Teste de Disparo para n8n</h2>";
    echo "<p><strong>URL Alvo:</strong> " . $webhookUrl . "</p>";
    echo "<p><strong>Dados Enviados:</strong> " . $jsonData . "</p>";
    echo "<hr>";
    echo "<p><strong>Código HTTP:</strong> " . $httpCode . "</p>";
    echo "<p><strong>Resposta do n8n:</strong> " . $response . "</p>";
    
    if ($httpCode == 200) {
        echo "<h3 style='color: green;'>✅ Sucesso! Verifique a execução no n8n.</h3>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ Atenção: O n8n recebeu, mas retornou um código diferente de 200.</h3>";
    }
}

curl_close($ch);
?>