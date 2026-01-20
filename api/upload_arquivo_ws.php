<?php
// api/upload_arquivo_ws.php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Função de Log
function debugLog($msg) {
    file_put_contents('debug_upload_log.txt', "[" . date('Y-m-d H:i:s') . "] $msg" . PHP_EOL, FILE_APPEND);
}

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

// 2. VALIDAÇÃO DO ARQUIVO
if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    debugLog("Erro no upload PHP: " . ($_FILES['arquivo']['error'] ?? 'Sem arquivo'));
    echo json_encode(['success' => false, 'message' => 'Erro ao receber o arquivo no servidor.']);
    exit;
}

// Captura dados do formulário
$driveId = $_POST['drive_id'] ?? '';
$folderName = $_POST['folder_name'] ?? 'Indefinido';

if (empty($driveId)) {
    echo json_encode(['success' => false, 'message' => 'ID da pasta não informado.']);
    exit;
}

// 3. CONVERSÃO PARA BASE64 (A MUDANÇA MÁGICA)
$file = $_FILES['arquivo'];
$filePath = $file['tmp_name'];
$fileName = $file['name'];
$fileType = $file['type'];

// Lê o conteúdo binário do arquivo
$fileContent = file_get_contents($filePath);
if ($fileContent === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao ler o arquivo temporário.']);
    exit;
}

// Converte para Base64 (vira uma string de texto)
$base64Data = base64_encode($fileContent);

// 4. MONTAGEM DO JSON
// Agora enviamos apenas TEXTO, o que é muito mais fácil de passar por firewalls/configs
$payload = json_encode([
    'action' => 'upload_base64',
    'file_name' => $fileName,
    'file_type' => $fileType,
    'file_content_base64' => $base64Data, // O arquivo vai aqui dentro como texto
    'drive_id' => $driveId,
    'folder_name' => $folderName,
    'user_name' => $_SESSION['user_name'] ?? 'Anonimo',
    'user_id' => $_SESSION['user_id'] ?? 0
]);

// 5. ENVIO PARA O N8N
$webhookUrl = 'https://n8n.alunosdamedicina.com/webhook/8b41262d-fb1c-4d56-ba03-24944bbd0c00';
# $webhookUrl = 'https://n8n.alunosdamedicina.com/webhook-test/8b41262d-fb1c-4d56-ba03-24944bbd0c00';

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

// Ignorar SSL para garantir que chegue (em produção, tente remover isso depois)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 6. RETORNO
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true, 'message' => 'Enviado com sucesso!']);
} else {
    debugLog("Erro N8N ($httpCode): $curlError | Resp: $response");
    echo json_encode(['success' => false, 'message' => "Erro no servidor remoto: $httpCode"]);
}
?>