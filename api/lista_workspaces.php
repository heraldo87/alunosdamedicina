<?php
// api/lista_workspaces.php

// 1. URL DO WEBHOOK
$webhookListagem = 'https://n8n.alunosdamedicina.com/webhook/lista_ws';

// 2. PREPARAR DADOS
$payload = json_encode([
    'action' => 'list_workspaces',
    'user_id' => $userId, 
    'user_type' => $tipoUsuario,
    'request_time' => date('Y-m-d H:i:s')
]);

// 3. CHAMADA CURL (Com correção para SSL)
$ch = curl_init($webhookListagem);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Aumentei para 10s para garantir

// IMPORTANTE: Desabilita verificação SSL temporariamente para evitar erro em VPS/Localhost
// Isso resolve problemas se o certificado do n8n não for reconhecido pelo PHP local
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 4. PROCESSAR A RESPOSTA
if ($httpCode >= 200 && $httpCode < 300 && $response) {
    $json = json_decode($response, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        
        // CENÁRIO 1: O JSON é um array direto (Seu caso atual: [ {...} ])
        if (is_array($json) && isset($json[0]['id'])) {
            $workspaces = $json;
        }
        // CENÁRIO 2: O JSON vem envelopado (Ex: { "workspaces": [...] })
        elseif (isset($json['workspaces']) && is_array($json['workspaces'])) {
            $workspaces = $json['workspaces'];
        }
        // CENÁRIO 3: O JSON vem envelopado pelo n8n padrão (Ex: { "data": [...] })
        elseif (isset($json['data']) && is_array($json['data'])) {
            $workspaces = $json['data'];
        }
        
    } else {
        // Erro de JSON (pode descomentar para debug)
        // echo "";
    }
} else {
    // Erro de conexão (pode descomentar para debug)
    // echo "";
}
?>