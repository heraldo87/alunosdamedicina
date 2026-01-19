<?php
/**
 * MEDINFOCUS - API de Upload de Ficheiros
 * Envia o ficheiro para o n8n e redireciona de volta ao Workspace
 */

session_start();

// 1. SEGURANÇA: Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

// 2. VALIDAÇÃO DE ENTRADA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../repositorio.php?erro=metodo_nao_permitido');
    exit;
}

$parentId = $_POST['parent_id'] ?? null;     // ID da pasta no Google Drive
$workspaceId = $_POST['workspace_id'] ?? null; // ID interno no Banco de Dados
$arquivo = $_FILES['arquivo_upload'] ?? null;

if (!$parentId || !$workspaceId || !$arquivo) {
    header("Location: ../workspace_view.php?drive_id=$parentId&ws_id=$workspaceId&erro=dados_incompletos");
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Usuário';

// 3. PREPARAÇÃO DO FICHEIRO PARA O n8n
$fileData = base64_encode(file_get_contents($arquivo['tmp_name']));
$fileName = $arquivo['name'];
$fileMime = $arquivo['type'];

// 4. COMUNICAÇÃO COM O n8n
$n8n_webhook_url = 'https://n8n.alunosdamedicina.com/webhook-test/8b41262d-fb1c-4d56-ba03-24944bbd0c00'; 

$payload = [
    'acao'          => 'upload_arquivo',
    'parent_id'     => $parentId,     // ID da pasta para o Drive
    'workspace_id'  => $workspaceId,  // ID interno para o SQL do n8n
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

// 5. REDIRECIONAMENTO FINAL
if ($httpCode === 200) {
    // Retorna para o workspace com flag de sucesso
    header("Location: ../workspace_view.php?drive_id=$parentId&ws_id=$workspaceId&sucesso=upload");
} else {
    // Retorna com flag de erro
    header("Location: ../workspace_view.php?drive_id=$parentId&ws_id=$workspaceId&erro=upload_failed");
}
exit;