<?php
/**
 * API - Baixar Arquivo do Workspace (Proxy para n8n/Drive)
 */
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Acesso negado.");
}

$fileId = $_GET['file_id'] ?? null;
$fileName = $_GET['name'] ?? 'download';
$mimeType = $_GET['mime'] ?? '';

if (!$fileId) {
    die("ID do arquivo não fornecido.");
}

// URL do Webhook do n8n configurado para Download (Heraldo deve configurar)
// Este webhook deve receber o ID e retornar a URL de download ou o stream do arquivo
$n8nWebhookDownload = 'https://n8n.alunosdamedicina.com/webhook/DOWNLOAD_WEBHOOK_UUID_AQUI'; 

// Payload
$payload = [
    'acao' => 'baixar_arquivo',
    'file_id' => $fileId,
    'user' => $_SESSION['user_name']
];

// Opcional: Se já tivermos o link direto do Drive, poderíamos redirecionar direto.
// Mas como solicitado, vamos passar pela estrutura de API para controle.

// Log de auditoria (opcional)
// file_put_contents('../logs/downloads.log', date('Y-m-d H:i:s') . " - User {$_SESSION['user_name']} baixou $fileId\n", FILE_APPEND);

// Redirecionamento direto para a lógica do n8n
// Se o n8n for retornar o binário, precisaríamos usar cURL aqui e repassar os headers.
// Se o n8n for apenas retornar o link, podemos fazer um redirect.

// MODO SIMPLIFICADO (Assumindo que o n8n retorna um JSON com { "download_url": "..." })
$ch = curl_init($n8nWebhookDownload);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    if (isset($data['download_url'])) {
        header("Location: " . $data['download_url']);
        exit;
    }
}

// FALLBACK TEMPORÁRIO (Caso o Webhook ainda não esteja pronto):
// Tenta montar um link de exportação padrão do Google Drive se o ID for reconhecível
header("Location: https://drive.google.com/uc?export=download&id=" . $fileId);
exit;
?>