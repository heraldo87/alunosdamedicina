<?php
session_start();

// Headers para evitar cache agressivo
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Verificação de sessão
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$nomeUsuario = $_SESSION['user_name'] ?? 'Doutor(a)';
$firstName = htmlspecialchars(explode(' ', $nomeUsuario)[0]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mentor Voz — MEDINFOCUS</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              dark: '#0b0f1a',
              primary: '#0284c7',
              surface: '#1e293b',
            }
          },
          animation: {
            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            'blob': 'blob 7s infinite',
          },
          keyframes: {
            blob: {
              '0%': { transform: 'translate(0px, 0px) scale(1)' },
              '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
              '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
              '100%': { transform: 'translate(0px, 0px) scale(1)' },
            }
          }
        }
      }
    }
  </script>

  <style>
    body { font-family: 'Inter', sans-serif; background-color: #0b0f1a; overflow: hidden; }
    
    /* Vidro fosco moderno */
    .glass-panel {
      background: rgba(255, 255, 255, 0.03);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Botão Central */
    .mic-button {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.7);
    }
    
    .mic-button:hover {
      transform: scale(1.05);
    }
    
    .mic-button.active {
      background-color: #ef4444; /* Red for stop */
      box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    
    .mic-button.listening {
      animation: pulse-ring 2s infinite;
    }

    @keyframes pulse-ring {
      0% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.7); }
      70% { box-shadow: 0 0 0 20px rgba(2, 132, 199, 0); }
      100% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0); }
    }

    /* Visualizer Canvas container */
    .viz-container {
      position: relative;
      width: 100%;
      height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    /* Background Glow do Canvas */
    .glow-bg {
      position: absolute;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(2,132,199,0.4) 0%, rgba(0,0,0,0) 70%);
      border-radius: 50%;
      z-index: 0;
      opacity: 0.5;
      transition: opacity 0.5s ease;
    }

    canvas { 
        z-index: 10; 
        width: 100%; 
        height: 100%;
    }
  </style>
</head>

<body class="text-slate-300 h-screen w-screen flex flex-col md:flex-row">
  
  <?php include 'includes/sidebar.php'; ?>

  <main class="flex-1 flex flex-col relative h-full">
    
    <header class="absolute top-0 w-full p-6 flex justify-between items-center z-20">
      <div class="flex items-center gap-3">
        <div class="w-2 h-2 rounded-full bg-slate-600 transition-colors duration-500" id="connDot"></div>
        <span class="text-sm font-medium text-slate-400 tracking-wide">MENTOR IA</span>
      </div>
      
      <div class="glass-panel px-4 py-2 rounded-full flex items-center gap-2">
        <i class="fa-solid fa-user-doctor text-xs text-brand-primary"></i>
        <span class="text-xs font-mono text-slate-300">Dr(a). <?php echo $firstName; ?></span>
      </div>
    </header>

    <section class="flex-1 flex flex-col items-center justify-center p-6 relative">
      
      <div class="mb-8 text-center z-20 h-16 flex flex-col items-center justify-center">
        <h2 id="mainStatus" class="text-2xl md:text-3xl font-bold text-white tracking-tight transition-all duration-300">
          Olá, <?php echo $firstName; ?>
        </h2>
        <p id="subStatus" class="text-sm text-slate-500 mt-2 font-mono transition-all duration-300">
          Toque no microfone para começar
        </p>
      </div>

      <div class="viz-container">
        <div id="glowBg" class="glow-bg"></div>
        <canvas id="viz"></canvas>
      </div>

      <div class="mt-12 z-20 flex flex-col items-center gap-6">
        
        <button id="btnAction" class="mic-button w-20 h-20 rounded-full bg-brand-primary text-white flex items-center justify-center text-2xl shadow-lg shadow-brand-primary/30 relative">
          <i id="btnIcon" class="fa-solid fa-microphone"></i>
        </button>

        <div id="hint" class="text-xs text-slate-600 text-center max-w-xs opacity-80">
            Use fones de ouvido para melhor experiência
        </div>
      </div>

    </section>

    <footer class="absolute bottom-4 w-full text-center p-4 z-10 opacity-40 hover:opacity-100 transition-opacity">
        <span class="text-[10px] text-slate-600 font-mono">POWERED BY MEDINFOCUS AI • OPENAI REALTIME</span>
    </footer>
  </main>

<script>
  // Elementos UI
  const elMainStatus = document.getElementById('mainStatus');
  const elSubStatus = document.getElementById('subStatus');
  const elConnDot = document.getElementById('connDot');
  const elGlow = document.getElementById('glowBg');
  const btnAction = document.getElementById('btnAction');
  const btnIcon = document.getElementById('btnIcon');
  const canvas = document.getElementById('viz');
  const ctx = canvas.getContext('2d');

  // Variáveis de Estado Lógico
  let isRunning = false;
  let pc = null;
  let dc = null;
  let localStream = null;
  let remoteAudio = null;

  // WebAudio Analysers
  let audioCtx = null;
  let analyserMic = null;
  let analyserRemote = null;
  let dataMic = null;
  let dataRemote = null;
  let raf = 0;

  let userSpeaking = false;
  let assistantSpeaking = false;

  // --- UI Helpers ---

  function resizeCanvas(){
    const rect = canvas.parentElement.getBoundingClientRect();
    const dpr = Math.max(1, window.devicePixelRatio || 1);
    canvas.width = Math.floor(rect.width * dpr);
    canvas.height = Math.floor(rect.height * dpr);
  }
  window.addEventListener('resize', resizeCanvas);
  // Call once on init
  setTimeout(resizeCanvas, 100);

  function updateUIState(state, message = "") {
    // Reset classes básicas
    btnAction.classList.remove('active', 'listening');
    elGlow.style.opacity = '0.3';
    elGlow.style.transform = 'scale(1)';

    switch(state) {
      case 'idle':
        elMainStatus.textContent = "Toque para falar";
        elSubStatus.textContent = "Sessão encerrada";
        btnAction.classList.remove('bg-rose-500');
        btnAction.classList.add('bg-brand-primary');
        btnIcon.className = "fa-solid fa-microphone";
        elConnDot.classList.replace('bg-emerald-500', 'bg-slate-600');
        elConnDot.classList.replace('bg-amber-400', 'bg-slate-600');
        break;

      case 'connecting':
        elMainStatus.textContent = "Conectando...";
        elSubStatus.textContent = "Estabelecendo conexão segura";
        btnIcon.className = "fa-solid fa-spinner fa-spin";
        elConnDot.classList.add('bg-amber-400');
        break;

      case 'connected': // Estado neutro conectado
        elMainStatus.textContent = "Estou ouvindo";
        elSubStatus.textContent = "Pode falar agora";
        btnAction.classList.remove('bg-brand-primary');
        btnAction.classList.add('active', 'bg-rose-500'); // Fica vermelho para indicar "Parar/Desligar"
        btnIcon.className = "fa-solid fa-stop"; // Ícone de Stop
        elConnDot.classList.remove('bg-amber-400');
        elConnDot.classList.add('bg-emerald-500');
        break;
      
      case 'listening': // Usuário falando
        elMainStatus.textContent = "Ouvindo você...";
        elSubStatus.textContent = "Fale normalmente";
        btnAction.classList.add('listening'); // Animação de pulso
        elGlow.style.opacity = '0.8';
        elGlow.style.transform = 'scale(1.2)';
        break;

      case 'speaking': // IA falando
        elMainStatus.textContent = "Explicando...";
        elSubStatus.textContent = "Clique no botão para encerrar";
        elGlow.style.opacity = '0.6';
        elGlow.style.backgroundColor = 'rgba(34,211,238,0.4)'; // Cyan glow
        break;

      case 'thinking': // Processando
        elMainStatus.textContent = "Pensando...";
        elSubStatus.textContent = "Processando resposta";
        break;

      case 'error':
        elMainStatus.textContent = "Erro";
        elSubStatus.textContent = message;
        btnAction.classList.remove('bg-rose-500');
        btnAction.classList.add('bg-brand-primary');
        btnIcon.className = "fa-solid fa-rotate-right"; // Tentar novamente
        break;
    }
  }

  // --- Lógica de Visualização (Mantida e Adaptada) ---

  function ensureAudioContext(){
    if (audioCtx) return;
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  }

  function createAnalyserForStream(stream){
    ensureAudioContext();
    const source = audioCtx.createMediaStreamSource(stream);
    const analyser = audioCtx.createAnalyser();
    analyser.fftSize = 1024; // Menor para visualização mais suave
    analyser.smoothingTimeConstant = 0.8;
    source.connect(analyser);
    return analyser;
  }

  function startVisualizer(){
    cancelAnimationFrame(raf);
    resizeCanvas();

    const draw = () => {
      raf = requestAnimationFrame(draw);
      const w = canvas.width;
      const h = canvas.height;
      const cy = h / 2;

      ctx.clearRect(0,0,w,h);

      let analyser = null;
      let data = null;

      // Prioridade visual: Usuário > IA
      if (userSpeaking && analyserMic) {
        analyser = analyserMic; data = dataMic;
      } else if (assistantSpeaking && analyserRemote) {
        analyser = analyserRemote; data = dataRemote;
      }

      if (!analyser || !data) {
        // Linha reta suave (pulsação leve) se conectado
        if(isRunning) {
            ctx.beginPath();
            ctx.moveTo(0, cy);
            ctx.lineTo(w, cy);
            ctx.strokeStyle = 'rgba(255,255,255,0.1)';
            ctx.lineWidth = 2;
            ctx.stroke();
        }
        return;
      }

      analyser.getByteTimeDomainData(data);

      ctx.lineWidth = 4;
      ctx.lineCap = 'round';
      
      // Gradiente da onda
      const grad = ctx.createLinearGradient(0, 0, w, 0);
      if(userSpeaking) {
          grad.addColorStop(0, 'rgba(2,132,199,0.2)');
          grad.addColorStop(0.5, 'rgba(2,132,199,1)');
          grad.addColorStop(1, 'rgba(2,132,199,0.2)');
      } else {
          grad.addColorStop(0, 'rgba(34,211,238,0.2)');
          grad.addColorStop(0.5, 'rgba(34,211,238,1)');
          grad.addColorStop(1, 'rgba(34,211,238,0.2)');
      }
      
      ctx.strokeStyle = grad;
      ctx.beginPath();

      const sliceWidth = w / data.length;
      let x = 0;

      for (let i = 0; i < data.length; i++){
        const v = data[i] / 128.0; 
        const y = (v * h) / 2; // Centralizado

        if (i === 0) ctx.moveTo(x, y);
        else {
            // Suavização simples (Bezier curve poderia ser usada aqui para mais fluidez)
            ctx.lineTo(x, y);
        }
        x += sliceWidth;
      }
      ctx.lineTo(w, h/2);
      ctx.stroke();
    };

    draw();
  }

  function stopVisualizer(){
    cancelAnimationFrame(raf);
    raf = 0;
    ctx.clearRect(0,0,canvas.width,canvas.height);
  }

  // --- Lógica WebRTC (Realtime API) ---

  async function fetchEphemeralToken(){
    const r = await fetch('/api/realtime_token.php', { cache: 'no-store' });
    const j = await r.json();
    if (!r.ok || !j.ok || !j.value) throw new Error(j.error || 'Falha ao obter token.');
    return j.value;
  }

  function safeSend(obj){
    try{
      if (dc && dc.readyState === "open") dc.send(JSON.stringify(obj));
    }catch(e){}
  }

  function handleServerEvent(evt){
    const t = evt?.type || "";

    if (t === "input_audio_buffer.speech_started") {
      userSpeaking = true;
      if (assistantSpeaking) safeSend({ type: "response.cancel" });
      if (remoteAudio) remoteAudio.muted = true;
      updateUIState('listening');
    }
    else if (t === "input_audio_buffer.speech_stopped" || t === "input_audio_buffer.timeout_triggered") {
      userSpeaking = false;
      if (remoteAudio) remoteAudio.muted = false;
      updateUIState('thinking');
    }
    else if (t === "output_audio_buffer.started") {
      assistantSpeaking = true;
      if (!userSpeaking && remoteAudio) remoteAudio.muted = false;
      updateUIState('speaking');
    }
    else if (t === "output_audio_buffer.stopped" || t === "output_audio_buffer.cleared") {
      assistantSpeaking = false;
      if (remoteAudio) remoteAudio.muted = false;
      updateUIState('connected');
    }
    else if (t === "error") {
      updateUIState('error', evt?.error?.message || "Erro na sessão");
    }
  }

  async function waitIceGatheringComplete(pc, timeoutMs = 2500){
    if (pc.iceGatheringState === "complete") return;
    await new Promise((resolve) => {
      const to = setTimeout(resolve, timeoutMs);
      pc.addEventListener("icegatheringstatechange", () => {
        if (pc.iceGatheringState === "complete") {
          clearTimeout(to);
          resolve();
        }
      });
    });
  }

  async function startRealtime(){
    if(isRunning) return; // Prevent double click
    
    updateUIState('connecting');

    try{
      const token = await fetchEphemeralToken();

      pc = new RTCPeerConnection({
        iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
      });

      remoteAudio = new Audio();
      remoteAudio.autoplay = true;
      remoteAudio.playsInline = true;

      pc.ontrack = (e) => {
        remoteAudio.srcObject = e.streams[0];
        try{
          analyserRemote = createAnalyserForStream(e.streams[0]);
          dataRemote = new Uint8Array(analyserRemote.fftSize);
        }catch{}
      };

      // Microfone
      localStream = await navigator.mediaDevices.getUserMedia({
        audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
      });

      // Analyzer local
      try{
        analyserMic = createAnalyserForStream(localStream);
        dataMic = new Uint8Array(analyserMic.fftSize);
      }catch{}

      try{ await audioCtx.resume(); }catch{}

      for (const track of localStream.getTracks()) pc.addTrack(track, localStream);

      dc = pc.createDataChannel("oai-events");

      dc.onopen = () => {
        isRunning = true;
        
        // Configuração da Sessão (Prompt do Sistema)
        safeSend({
          type: "session.update",
          session: {
            output_modalities: ["audio"],
            instructions:
              "Você é um mentor didático para estudante de medicina (1º ano). " +
              "Responda em português do Brasil, passo a passo, com exemplos simples. " +
              "Seja conciso. Se o usuário interromper, pare imediatamente.",
            audio: {
              input: {
                turn_detection: {
                  type: "server_vad",
                  threshold: 0.5,
                  prefix_padding_ms: 300,
                  silence_duration_ms: 600,
                  create_response: true,
                  interrupt_response: true
                }
              },
              output: { voice: "alloy", speed: 1.0 }
            }
          }
        });

        // Saudação Inicial
        safeSend({
           type: "response.create",
           response: { instructions: "Diga apenas: 'Olá, doutor. Sobre o que vamos falar hoje?'" }
        });

        updateUIState('connected');
        startVisualizer();
      };

      dc.onmessage = (e) => {
        try{
          const evt = JSON.parse(e.data);
          handleServerEvent(evt);
        }catch{}
      };

      const offer = await pc.createOffer();
      await pc.setLocalDescription(offer);
      await waitIceGatheringComplete(pc);

      const sdpResp = await fetch("https://api.openai.com/v1/realtime/calls", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/sdp"
        },
        body: pc.localDescription.sdp
      });

      if (!sdpResp.ok) throw new Error(`Erro API: ${sdpResp.status}`);

      const answerSdp = await sdpResp.text();
      await pc.setRemoteDescription({ type: "answer", sdp: answerSdp });

    }catch(err){
      console.error(err);
      isRunning = false;
      updateUIState('error', "Falha na conexão");
      await stopRealtime(true);
    }
  }

  async function stopRealtime(silent = false){
    try{
      stopVisualizer();
      if (dc) { try{ dc.close(); }catch{} dc = null; }
      if (pc) { try{ pc.close(); }catch{} pc = null; }
      if (localStream) {
        for (const t of localStream.getTracks()) t.stop();
        localStream = null;
      }
      if (remoteAudio) {
        try{ remoteAudio.srcObject = null; }catch{}
        remoteAudio = null;
      }
      if (audioCtx) { try{ await audioCtx.suspend(); }catch{} }
      
      analyserMic = analyserRemote = null;

    } finally {
      isRunning = false;
      userSpeaking = false;
      assistantSpeaking = false;
      
      if (!silent) {
        updateUIState('idle');
      }
    }
  }

  // --- Toggle Action ---
  btnAction.addEventListener('click', () => {
    if (isRunning) {
        stopRealtime(false);
    } else {
        startRealtime();
    }
  });

  window.addEventListener('beforeunload', () => { stopRealtime(true); });
  
  // Estado inicial
  updateUIState('idle');

</script>
</body>
</html>