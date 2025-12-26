<?php
/**
 * MEDINFOCUS - Mentor por Voz (Versão Automática & Hands-free)
 */

// 1. CONFIGURAÇÃO INTEGRADA
$openai_api_key = getenv("OPENAI_API_KEY");

session_start();

// 2. LÓGICA DE BACKEND
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        // Ação de Boas-vindas Inicial
        if (isset($_POST['action']) && $_POST['action'] === 'welcome') {
            $welcomeText = "Olá! Eu sou o assistente do Projeto MedInFocus. Estou aqui para esclarecer suas dúvidas exclusivamente sobre medicina. Como posso ajudar em seus estudos hoje?";
            
            $ch = curl_init('https://api.openai.com/v1/audio/speech');
            $payload = ["model" => "tts-1", "voice" => "alloy", "input" => $welcomeText];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $openAIKey]);
            
            $audioBinary = curl_exec($ch);
            echo json_encode(['success' => true, 'audio_response' => base64_encode($audioBinary)]);
            exit;
        }

        // Processamento de Áudio (Whisper -> GPT -> TTS)
        if (isset($_FILES['audio'])) {
            $audioPath = $_FILES['audio']['tmp_name'];

            // 1. Transcrição
            $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($audioPath, 'audio/wav', 'audio.wav'), 'model' => 'whisper-1', 'language' => 'pt']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $openAIKey]);
            $transRes = json_decode(curl_exec($ch), true);
            $userText = $transRes['text'] ?? null;

            if (!$userText) throw new Exception("Não entendi o áudio.");

            // 2. Inteligência (Foco estrito em Medicina)
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            $payload = [
                "model" => "gpt-4o-mini",
                "messages" => [
                    ["role" => "system", "content" => "Você é o Mentor MedInFocus. Seu papel é esclarecer dúvidas APENAS sobre medicina. Se o usuário perguntar algo fora desse tema, responda que seu escopo é estritamente médico. Seja conciso."],
                    ["role" => "user", "content" => $userText]
                ]
            ];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $openAIKey]);
            $gptRes = json_decode(curl_exec($ch), true);
            $aiText = $gptRes['choices'][0]['message']['content'] ?? "Erro ao processar.";

            // 3. Voz
            $ch = curl_init('https://api.openai.com/v1/audio/speech');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["model" => "tts-1", "voice" => "alloy", "input" => $aiText]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $openAIKey]);
            $audioBinary = curl_exec($ch);

            echo json_encode(['success' => true, 'user_text' => $userText, 'ai_text' => $aiText, 'audio_response' => base64_encode($audioBinary)]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor MedInFocus - Hands Free</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pulse-ring { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .5; transform: scale(1.05); } }
        .recording-glow { box-shadow: 0 0 20px rgba(239, 68, 68, 0.5); border-color: #ef4444; }
    </style>
</head>
<body class="bg-[#0f172a] flex font-sans text-slate-300">

    <?php if (file_exists('includes/sidebar.php')) include 'includes/sidebar.php'; ?>

    <main class="flex-1 h-screen flex flex-col items-center justify-center p-8 relative overflow-hidden">
        
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-sky-500/10 rounded-full blur-[120px] pointer-events-none"></div>

        <div id="setup-screen" class="text-center z-10">
            <div class="mb-8 flex justify-center">
                <div class="w-20 h-20 bg-sky-500 rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">MedInFocus</h1>
            <p class="text-slate-400 mb-10 max-w-xs mx-auto">Assistente inteligente exclusivo para dúvidas de medicina.</p>
            <button id="start-btn" class="px-8 py-4 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-2xl shadow-xl transition-all hover:scale-105">
                <i class="fa-solid fa-play mr-2"></i> Iniciar Mentor
            </button>
        </div>

        <div id="chat-screen" class="hidden text-center z-10 w-full max-w-lg">
            <div id="status-badge" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-800 border border-slate-700 text-xs font-bold uppercase tracking-widest text-slate-400 mb-12">
                <span class="w-2 h-2 rounded-full bg-slate-500" id="status-dot"></span>
                <span id="status-text">Iniciando...</span>
            </div>

            <div class="relative flex justify-center mb-16">
                <div id="mic-visual" class="w-48 h-48 rounded-full border-4 border-slate-800 flex items-center justify-center transition-all duration-500">
                    <i class="fa-solid fa-brain text-6xl text-slate-700" id="main-icon"></i>
                </div>
            </div>

            <div class="space-y-6">
                <div id="box-user" class="hidden bg-slate-800/50 p-4 rounded-2xl border border-slate-700">
                    <p class="text-[10px] text-sky-400 font-black uppercase mb-1">Sua Pergunta</p>
                    <p id="txt-user" class="text-slate-300 italic"></p>
                </div>
                <div id="box-ai" class="hidden bg-sky-500/10 p-4 rounded-2xl border border-sky-500/20">
                    <p class="text-[10px] text-sky-400 font-black uppercase mb-1">Mentor MedInFocus</p>
                    <p id="txt-ai" class="text-white text-lg leading-relaxed"></p>
                </div>
            </div>
        </div>

        <audio id="player" class="hidden"></audio>
    </main>

    <script>
        const setup = document.getElementById('setup-screen');
        const chat = document.getElementById('chat-screen');
        const startBtn = document.getElementById('start-btn');
        const statusText = document.getElementById('status-text');
        const statusDot = document.getElementById('status-dot');
        const micVisual = document.getElementById('mic-visual');
        const mainIcon = document.getElementById('main-icon');
        const player = document.getElementById('player');

        let mediaRecorder;
        let chunks = [];

        // 1. Início da Sessão (Apresentação)
        startBtn.onclick = async () => {
            setup.classList.add('hidden');
            chat.classList.remove('hidden');
            
            try {
                // Solicita permissão do microfone logo de cara
                await navigator.mediaDevices.getUserMedia({ audio: true });
                
                // Busca a apresentação inicial
                const res = await fetch('chat_voz.php', { method: 'POST', body: new URLSearchParams({'action': 'welcome'}) });
                const data = await res.json();
                
                playResponse(data.audio_response);
            } catch (e) {
                alert("Erro: Necessário acesso ao microfone.");
            }
        };

        // 2. Tocar Áudio da IA
        function playResponse(base64) {
            statusText.innerText = "Mentor Falando...";
            statusDot.className = "w-2 h-2 rounded-full bg-sky-500 animate-ping";
            micVisual.className = "w-48 h-48 rounded-full border-4 border-sky-500 flex items-center justify-center pulse-ring";
            mainIcon.className = "fa-solid fa-volume-high text-6xl text-sky-500";

            player.src = 'data:audio/mp3;base64,' + base64;
            player.play();
            
            player.onended = () => {
                startAutoListen(); // Assim que ela para de falar, começa a ouvir
            };
        }

        // 3. Ouvir Automaticamente (Hands-free)
        async function startAutoListen() {
            statusText.innerText = "Pode Falar...";
            statusDot.className = "w-2 h-2 rounded-full bg-red-500 animate-pulse";
            micVisual.className = "w-48 h-48 rounded-full border-4 border-red-500 flex items-center justify-center recording-glow";
            mainIcon.className = "fa-solid fa-microphone text-6xl text-red-500";

            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            chunks = [];

            mediaRecorder.ondataavailable = e => chunks.push(e.data);
            mediaRecorder.onstop = async () => {
                processAudio();
            };

            mediaRecorder.start();

            // Silêncio Inteligente: Se não falar em 6 segundos, ele processa
            setTimeout(() => {
                if (mediaRecorder.state === "recording") mediaRecorder.stop();
            }, 6000);
        }

        // 4. Processar e Loop
        async function processAudio() {
            statusText.innerText = "Processando...";
            statusDot.className = "w-2 h-2 rounded-full bg-amber-500 animate-spin";
            micVisual.className = "w-48 h-48 rounded-full border-4 border-slate-700 flex items-center justify-center opacity-50";
            mainIcon.className = "fa-solid fa-rotate text-6xl text-slate-500 fa-spin";

            const blob = new Blob(chunks, { type: 'audio/wav' });
            const fd = new FormData();
            fd.append('audio', blob);

            const res = await fetch('chat_voz.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                document.getElementById('box-user').classList.remove('hidden');
                document.getElementById('box-ai').classList.remove('hidden');
                document.getElementById('txt-user').innerText = data.user_text;
                document.getElementById('txt-ai').innerText = data.ai_text;
                playResponse(data.audio_response);
            } else {
                statusText.innerText = "Erro. Tentando novamente...";
                setTimeout(startAutoListen, 2000);
            }
        }
    </script>
</body>
</html>