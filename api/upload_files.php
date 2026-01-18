<?php
/**
 * MEDINFOCUS - API de Upload de Ficheiros
 * Envia o ficheiro para o n8n para ser processado e guardado no Google Drive
 */

session_start();
header('Content-Type: application/json');

// 1. SEGURANÇA: Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida.']);
    exit;
}

// 2. VALIDAÇÃO DE ENTRADA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$parentId = $_POST['parent_id'] ?? null; // ID da pasta onde o ficheiro será guardado
if (!$parentId || !isset($_FILES['arquivo_upload'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para upload.']);
    exit;
}

$arquivo = $_FILES['arquivo_upload'];
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Usuário';

// 3. PREPARAÇÃO DO FICHEIRO PARA O n8n
// Lemos o conteúdo do ficheiro para enviar via cURL
$fileData = base64_encode(file_get_contents($arquivo['tmp_name']));
$fileName = $arquivo['name'];
$fileMime = $arquivo['type'];

// 4. COMUNICAÇÃO COM O n8n
$n8n_webhook_url = 'https://n8n.alunosdamedicina.com/webhook/a370fb4f-242a-4084-9358-45bab481fcb7'; 

$payload = [
    'acao'          => 'upload_arquivo',
    'parent_id'     => $parentId,
    'usuario_id'    => $userId,
    'usuario_nome'  => $userName,
    'nome_arquivo'  => $fileName,
    'mime_type'     => $fileMime,
    'conteudo_b64'  => $fileData
];

$ch = curl_init($n8n_webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 5. RESPOSTA PARA O FRONT-END
if ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'Upload realizado com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao processar upload no servidor de automação.', 'code' => $httpCode]);
}