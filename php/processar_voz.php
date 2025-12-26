<?php
// php/processa_voz.php
session_start();

// Desativar exibição de erros HTML para não quebrar o JSON, mas logar em arquivo
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    echo json_encode(['success' => false, 'error' => 'Arquivo config.php não encontrado na pasta /php.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['audio']) || !isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado ou áudio não enviado.']);
    exit;
}

try {
    $audioPath = $_FILES['audio']['tmp_name'];
    if (!is_uploaded_file($audioPath)) {
        throw new Exception("Erro no upload do arquivo temporário.");
    }

    $audioData = base64_encode(file_get_contents($audioPath));

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $payload = [
        "model" => "gpt-4o-audio-preview",
        "modalities" => ["text", "audio"],
        "audio" => ["voice" => "alloy", "format" => "wav"],
        "messages" => [
            ["role" => "system", "content" => "Você é o Mentor MEDINFOCUS. Responda de forma curta."],
            ["role" => "user", "content" => [
                ["type" => "input_audio", "input_audio" => ["data" => $audioData, "format" => "wav"]]
            ]]
        ]
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    // IMPORTANTE: Se o seu VPS não tiver os certificados atualizados, isso resolve a falha de conexão:
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro cURL: ' . curl_error($ch));
    }
    
    curl_close($ch);
    $resData = json_decode($response, true);

    if (isset($resData['choices'][0]['message']['audio'])) {
        $msg = $resData['choices'][0]['message'];
        echo json_encode([
            'success' => true,
            'user_text' => $msg['audio']['transcript'] ?? 'Áudio detectado',
            'audio_response' => $msg['audio']['data']
        ]);
    } else {
        // Se a OpenAI retornar erro (ex: falta de saldo ou modelo não disponível)
        $errMsg = $resData['error']['message'] ?? 'Erro desconhecido na API da OpenAI.';
        echo json_encode(['success' => false, 'error' => $errMsg, 'debug' => $resData]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}