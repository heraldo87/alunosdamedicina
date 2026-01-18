<?php
/**
 * MEDINFOCUS - API de Listagem de Workspaces via n8n
 * Recupera as pastas que o usuário tem acesso diretamente da automação
 */

// 1. Inicia a sessão se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Recupera os dados da sessão
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuário Desconhecido';

// URL do webhook do n8n (Ambiente de Teste)
$url = "https://n8n.alunosdamedicina.com/webhook/a370fb4f-242a-4084-9358-45bab481fcb7";

// 3. Dados para o n8n identificar o usuário e a intenção
$dados = [
    "acao"         => "lista_ws",
    "usuario_id"   => $userId,
    "usuario_nome" => $userName
];

$json = json_encode($dados);

// 4. Configuração do cURL para comunicação
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Content-Length: " . strlen($json)
]);

// 5. Execução e tratamento da resposta
$resposta = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// 6. Processamento do resultado para o Repositório
// Decodificamos o JSON vindo do n8n para um array PHP que será usado no foreach do repositorio.php
if ($error) {
    error_log("Erro cURL no MedInFocus: " . $error);
    $workspaces_remotos = [];
} else {
    $workspaces_remotos = json_decode($resposta, true);
    
    // Garantimos que $workspaces_remotos seja sempre um array, mesmo se o n8n retornar vazio ou erro
    if (!is_array($workspaces_remotos)) {
        $workspaces_remotos = [];
    }
}

// Nota: Não usamos 'echo' aqui porque este arquivo é incluído no topo do repositorio.php
// A variável $workspaces_remotos agora está disponível para o arquivo que der o 'require'