<?php
/**
 * MEDINFOCUS - API de Disparo de Criação de Workspace
 * Delega a inteligência de banco de dados e integração para o n8n
 */

session_start();

// 1. SEGURANÇA E VALIDAÇÃO DE SESSÃO
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

// 2. VALIDAÇÃO DE ENTRADA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../repositorio.php?erro=metodo_nao_permitido');
    exit;
}

$nomeWS = filter_input(INPUT_POST, 'nome_ws', FILTER_SANITIZE_STRING);
$descWS = filter_input(INPUT_POST, 'desc_ws', FILTER_SANITIZE_STRING);
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;

if (!$nomeWS) {
    header('Location: ../repositorio.php?erro=nome_obrigatorio');
    exit;
}

// 3. COMUNICAÇÃO COM O n8n
// URL atualizada para o Webhook de produção conforme sua necessidade
$n8n_webhook_url = 'https://n8n.alunosdamedicina.com/webhook/a370fb4f-242a-4084-9358-45bab481fcb7'; 

$payload = [
    'nome_pasta'   => $nomeWS,
    'descricao'    => $descWS,
    'usuario_id'   => $userId,
    'usuario_nome' => $userName,
    'acao'         => "cria_ws" 
];

$ch = curl_init($n8n_webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 4. TRATAMENTO DE RETORNO E REDIRECIONAMENTO
if ($httpCode === 200) {
    // Redireciona de volta com uma flag de sucesso para o repositório
    header('Location: ../repositorio.php?sucesso=ws_criado');
    exit;
} else {
    // Redireciona de volta com uma flag de erro técnico
    header('Location: ../repositorio.php?erro=n8n_falha&code=' . $httpCode);
    exit;
}