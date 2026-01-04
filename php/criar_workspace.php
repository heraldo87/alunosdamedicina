<?php
session_start();
// Garante que erros do PHP não vazem para o JSON e quebrem o frontend
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado.']);
    exit;
}

// Input
$input = json_decode(file_get_contents('php://input'), true);
$nomePastaRecebido = trim($input['nome_pasta'] ?? '');

if (empty($nomePastaRecebido)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Nome da pasta obrigatório.']);
    exit;
}

// --- URL DO N8N ---
// DICA: Quando finalizar os testes, troque "webhook-test" por "webhook" (Produção)
// E lembre-se: "webhook-test" só funciona com o editor do n8n ABERTO.
$webhookUrl = "https://n8n.alunosdamedicina.com/webhook-test/363a571f-154b-42fe-a004-fef0b9a83d49";

// Dados
$dados = [
    "origem"    => "medinfocus_web",
    "timestamp" => date('Y-m-d H:i:s'),
    "usuario" => [
        "id"          => $_SESSION['user_id'],
        "nome"        => $_SESSION['user_name'],
        "email"       => $_SESSION['user_email'] ?? 'sem_email',
        "tipo"        => $_SESSION['user_type'],
        "acao"        => "criarPasta",
        "nomeDaPasta" => $nomePastaRecebido
    ]
];

// cURL
$ch = curl_init($webhookUrl);
$payloadJson = json_encode($dados);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payloadJson,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    
    // --- CORREÇÃO PRINCIPAL AQUI ---
    // Permite que o servidor converse com ele mesmo ou subdomínios sem travar no SSL
    CURLOPT_SSL_VERIFYPEER => false, 
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_FOLLOWLOCATION => true
]);

$respostaRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch); // Captura erro detalhado antes de fechar
curl_close($ch);

// Verificação de Erro do cURL (Conexão falhou antes de chegar no n8n)
if ($respostaRaw === false) {
    echo json_encode([
        'sucesso' => false, 
        'erro' => 'O servidor não conseguiu contatar o n8n.',
        'detalhe_tecnico' => $curlError // Isso vai te mostrar o real motivo no console do navegador
    ]);
    exit;
}

// Decodifica o que o n8n respondeu
$respostaJson = json_decode($respostaRaw, true);

// Verifica se o HTTP Code é de sucesso (200 ou 201)
if ($httpCode >= 200 && $httpCode < 300) {
    if (isset($respostaJson['sucesso']) && $respostaJson['sucesso'] === true) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode([
            'sucesso' => false, 
            'erro' => $respostaJson['mensagem'] ?? 'O n8n processou, mas retornou erro.',
            'n8n_response' => $respostaJson
        ]);
    }
} 
// Erros HTTP (404, 500, 409) vindos do n8n
else {
    $msgErro = $respostaJson['mensagem'] ?? 'Erro no fluxo do n8n.';
    
    if ($httpCode === 409) {
        $msgErro = "Já existe uma pasta com este nome.";
    }
    
    // Se for 404, provavelmente é porque o webhook-test expirou
    if ($httpCode === 404) {
        $msgErro = "Webhook não encontrado. Se estiver em teste, verifique se o botão 'Execute' está ativo no n8n.";
    }

    echo json_encode([
        'sucesso' => false, 
        'erro' => $msgErro,
        'http_code' => $httpCode,
        'raw_response' => $respostaRaw
    ]);
}
?>