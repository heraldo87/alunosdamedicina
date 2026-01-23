<?php
// api/download_arquivo_ws.php

// 1. Configurações para lidar com arquivos grandes e binários
ini_set('display_errors', 0); // Oculta erros para não corromper o binário
set_time_limit(0);            // Sem limite de tempo (para arquivos grandes)
ini_set('memory_limit', '512M'); 

session_start();

// 2. Segurança
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    die("Acesso negado.");
}

// 3. Captura dos dados
$fileId = $_GET['file_id'] ?? '';
$fileName = $_GET['name'] ?? 'download_medinfocus.bin'; // Nome padrão caso venha vazio

if (empty($fileId)) {
    die("ID inválido.");
}

// 4. Configuração da Conexão com n8n
$externalApiUrl = 'https://n8n.alunosdamedicina.com/webhook/24ea734d-fdfc-4d22-b29e-b5f0eabeef7f'; 

$payload = json_encode([
    'file_id' => $fileId,
    'user_email' => $_SESSION['email'] ?? 'email_desconhecido',
    'action' => 'get_download_content' // Apenas semântico, o importante é o que o n8n faz
]);

// 5. Preparação dos Headers do Navegador (O PHP já avisa: "Vem arquivo aí")
// Forçamos o nome do arquivo aqui, pois o binário bruto do n8n pode não ter nome
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// 6. Início do Proxy (Streaming)
$ch = curl_init($externalApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutos de timeout
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

// --- MÁGICA DO STREAMING ---

// A) Callback de Headers: Lê os headers que vêm do n8n
// Se o n8n disser "Content-Type: application/pdf", nós repassamos isso pro navegador
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
    $len = strlen($header);
    $headerLine = trim($header);
    
    if (stripos($headerLine, 'Content-Type:') === 0) {
        header($headerLine);
    }
    if (stripos($headerLine, 'Content-Length:') === 0) {
        header($headerLine);
    }
    return $len;
});

// B) Callback de Write: Pega cada pedaço do arquivo do n8n e joga direto pro usuário
// Isso evita carregar o arquivo inteiro na memória RAM do servidor
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
    echo $data;
    return strlen($data);
});

// Executa
curl_exec($ch);

// Se houver erro de conexão, loga no servidor (não podemos exibir na tela pois os headers de download já foram enviados)
if (curl_errno($ch)) {
    error_log("Erro no download Proxy n8n: " . curl_error($ch));
}

curl_close($ch);
exit;
?>