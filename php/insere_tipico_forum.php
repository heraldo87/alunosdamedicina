<?php
// insere_tipico_forum.php — Gateway PHP → n8n (com coerção de tipos p/ inteiros)

// 1) Sessão (para capturar user_id)
if (session_status() === PHP_SESSION_NONE) session_start();

// 2) Endpoints do n8n
$N8N_ENDPOINTS = [
  ['label' => 'PROD', 'url' => 'https://n8n.alunosdamedicina.com/webhook/forum'],
  ['label' => 'TEST', 'url' => 'https://n8n.alunosdamedicina.com/webhook-test/forum'],
  ['label' => 'IP',   'url' => 'http://181.215.135.63:5678/webhook/forum'],
];

// 3) Log simples (opcional)
function _log_n8n($msg) {
  $dir = __DIR__ . '/logs';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  @file_put_contents($dir.'/insere_tipico_forum.log', '['.date('c')."] $msg\n", FILE_APPEND);
}

// 4) HTTP POST JSON helper
function post_json($url, array $data) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 2,
    CURLOPT_USERAGENT      => 'MedinFocus/GatewayForum',
  ]);
  $body = curl_exec($ch);
  $info = curl_getinfo($ch);
  $err  = curl_error($ch);
  curl_close($ch);
  return ['body'=>$body, 'info'=>$info, 'err'=>$err, 'http'=>(int)($info['http_code'] ?? 0)];
}

// 5) Entrada unificada (aceita form e JSON); converte tipos p/ inteiros onde preciso
$input = $_GET + $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
  $raw = file_get_contents('php://input');
  $json = json_decode($raw, true);
  if (is_array($json)) {
    // JSON tem preferência sobre form em caso de conflito
    $input = $json + $input;
  }
}

// Coerções mínimas (aqui está a “mágica”):
$acao     = isset($input['acao'])      ? (int)$input['acao']      : 3;
$userId   = isset($input['user_id'])   ? (int)$input['user_id']   : (int)($_SESSION['user_id'] ?? 0);
$topicoId = isset($input['topico_id']) ? (int)$input['topico_id'] : null;

// Campos textuais (mantém se vierem)
$titulo          = $input['titulo']           ?? $input['Titulo']           ?? null;
$breveDesc       = $input['breve_descricao']  ?? $input['descricao_breve']  ?? $input['Descrição_breve'] ?? null;
$mensagem        = $input['mensagem']         ?? null;

// Monta payload final (com tipos já corretos)
$payload = [
  'acao'      => $acao,                         // <- number
  'user_id'   => $userId,                       // <- number
  'timestamp' => date('Y-m-d H:i:s'),
];
if ($topicoId !== null)          $payload['topico_id']       = $topicoId;     // <- number
if (!empty($titulo))             $payload['titulo']          = (string)$titulo;
if (!empty($breveDesc))          $payload['breve_descricao'] = (string)$breveDesc;
if (!empty($mensagem))           $payload['mensagem']        = (string)$mensagem;

_log_n8n('Payload → ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

// 6) Tenta PROD → TEST → IP e aceita só 2xx (com corpo podendo ser vazio ou não, a critério do seu fluxo)
$response = null;
foreach ($N8N_ENDPOINTS as $ep) {
  $res = post_json($ep['url'], $payload);
  _log_n8n("TRY {$ep['label']} {$ep['url']} HTTP={$res['http']} ERR={$res['err']}");
  if ($res['http'] >= 200 && $res['http'] < 300) { $response = $res; break; }
}

// 7) Resposta ao cliente
header('Content-Type: application/json; charset=utf-8');
if (!$response) {
  http_response_code(502);
  echo json_encode(['success'=>false,'message'=>'Falha ao contatar o n8n'], JSON_UNESCAPED_UNICODE);
  exit;
}
echo is_string($response['body']) ? $response['body'] : json_encode($response['body'], JSON_UNESCAPED_UNICODE);
