<?php
/**
 * MEDINFOCUS — Realtime Voice Token (PHP) [ROBUSTO]
 * Gera client secret efêmero (ek_...) para o navegador conectar no Realtime via WebRTC
 *
 * Endpoint: POST https://api.openai.com/v1/realtime/client_secrets
 * Body: { expires_after: {...}, session: {...} }
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

session_start();
require_once __DIR__ . '/../php/config.php'; // deve definir AI_API_KEY

function json_out(int $status, array $data): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function is_https_request(): bool {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return true;
  return false;
}

/**
 * Segurança básica contra chamadas cross-site.
 * - Se vier Origin, exigimos que o host seja o mesmo do HTTP_HOST.
 * - Se não vier Origin (alguns fetch/clients), não bloqueia.
 */
function enforce_same_origin_if_present(): void {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin === '') return;

  $originHost = parse_url($origin, PHP_URL_HOST);
  $host = $_SERVER['HTTP_HOST'] ?? '';

  if (!$originHost || !$host) return;

  // remove porta do host se existir
  $hostOnly = explode(':', $host)[0];

  if (strcasecmp($originHost, $hostOnly) !== 0) {
    json_out(403, [
      'ok' => false,
      'error' => 'Origin não permitido.',
    ]);
  }
}

// 1) Exige HTTPS (microfone + segurança)
if (!is_https_request()) {
  json_out(400, [
    'ok' => false,
    'error' => 'HTTPS é obrigatório para o Mentor por Voz.'
  ]);
}

// 2) Exige login
if (empty($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  json_out(401, ['ok' => false, 'error' => 'Não autenticado.']);
}

// 3) Origin (se presente)
enforce_same_origin_if_present();

// 4) Rate-limit simples (evita spam de token)
$now = time();
$last = (int)($_SESSION['rt_token_last'] ?? 0);
if ($now - $last < 1) {
  json_out(429, [
    'ok' => false,
    'error' => 'Muitas requisições. Tente novamente em 1s.'
  ]);
}

if (!defined('AI_API_KEY') || !AI_API_KEY) {
  json_out(500, ['ok' => false, 'error' => 'AI_API_KEY não configurada.']);
}

/**
 * Ajustes recomendados
 * - TTL curto para segurança (ex.: 120–600s). :contentReference[oaicite:2]{index=2}
 * - Model/voice: pode manter gpt-realtime/alloy
 */
$TTL_SECONDS = 600;
$MODEL = 'gpt-realtime';
$VOICE = 'alloy';
$SPEED = 1.0;

// Instruções “padrão MEDINFOCUS”
$INSTRUCTIONS =
  "Você é um mentor didático para estudante de medicina (1º ano). " .
  "Responda em português do Brasil, com linguagem clara e passo a passo. " .
  "Quando o usuário interromper falando, pare imediatamente e escute. " .
  "Se for tema clínico, seja cauteloso e evite prescrição; oriente a procurar um profissional quando necessário.";

// Corpo correto do endpoint client_secrets: expires_after + session :contentReference[oaicite:3]{index=3}
$payload = [
  'expires_after' => [
    'anchor' => 'created_at',
    'seconds' => $TTL_SECONDS,
  ],
  'session' => [
    'type' => 'realtime',
    'model' => $MODEL,
    'output_modalities' => ['audio'],
    'instructions' => $INSTRUCTIONS,
    'audio' => [
      'input' => [
        // server_vad + create_response + interrupt_response = “estilo ChatGPT voz” :contentReference[oaicite:4]{index=4}
        'turn_detection' => [
          'type' => 'server_vad',
          'threshold' => 0.5,
          'prefix_padding_ms' => 300,
          'silence_duration_ms' => 600,
          'idle_timeout_ms' => 30000,
          'create_response' => true,
          'interrupt_response' => true,
        ],
        // (opcional) melhora UX se você quer bolhas com transcrição do usuário
        // se você preferir setar só no session.update do frontend, pode remover.
        'transcription' => [
          'model' => 'gpt-4o-mini-transcribe',
          'language' => 'pt',
        ],
        'noise_reduction' => [
          'type' => 'near_field',
        ],
      ],
      'output' => [
        'voice' => $VOICE,
        'speed' => $SPEED,
      ],
    ],
  ],
];

$url = "https://api.openai.com/v1/realtime/client_secrets";

$ch = curl_init($url);

$headers = [
  "Authorization: Bearer " . AI_API_KEY,
  "Content-Type: application/json",
];

curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => $headers,
  CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
  CURLOPT_TIMEOUT => 20,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_SSL_VERIFYHOST => 2,
]);

$raw = curl_exec($ch);
$err = curl_error($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false) {
  json_out(500, ['ok' => false, 'error' => 'Falha cURL', 'detail' => $err]);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  error_log("Realtime token: resposta não-JSON. HTTP {$http}. Raw: " . substr($raw, 0, 400));
  json_out(500, ['ok' => false, 'error' => 'Resposta inválida da OpenAI.']);
}

if ($http < 200 || $http >= 300) {
  // log interno detalhado, retorno externo limpo
  error_log("Realtime token error HTTP {$http}: " . substr($raw, 0, 800));
  json_out(500, ['ok' => false, 'error' => 'Falha ao gerar token efêmero.']);
}

$value = $data['value'] ?? null;
$expiresAt = $data['expires_at'] ?? null;

if (!$value || !$expiresAt) {
  error_log("Realtime token: faltando value/expires_at. Raw: " . substr($raw, 0, 800));
  json_out(500, ['ok' => false, 'error' => 'Resposta incompleta ao gerar token.']);
}

// marca rate-limit somente após sucesso
$_SESSION['rt_token_last'] = $now;

// Resposta enxuta para o frontend
json_out(200, [
  'ok' => true,
  'value' => $value,
  'expires_at' => $expiresAt,
  // opcional: devolve também info mínima da sessão (ajuda debug)
  'session' => [
    'model' => $data['session']['model'] ?? $MODEL,
    'output_modalities' => $data['session']['output_modalities'] ?? ['audio'],
    'voice' => $data['session']['audio']['output']['voice'] ?? $VOICE,
  ],
]);
