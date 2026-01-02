/**
 * js/voice_client.js
 * Cliente WebRTC para conexão direta com OpenAI Realtime API
 */

class VoiceClient {
    constructor() {
        this.peerConnection = null;
        this.dc = null; // Data Channel
        this.audioElement = document.createElement('audio');
        this.audioElement.autoplay = true;
        this.localStream = null;
        this.isSessionActive = false;
        
        // Callbacks para atualizar a UI (Interface)
        this.onStatusChange = (status) => console.log("Status:", status);
        this.onAudioLevel = (level) => {}; // Para animação de ondas
    }

    async start() {
        if (this.isSessionActive) return;

        try {
            this.onStatusChange("conectando");

            // 1. Obter Token Efêmero do nosso Backend
            const tokenResponse = await fetch("api/token_voz.php");
            if (!tokenResponse.ok) throw new Error("Falha ao obter token de voz");
            const data = await tokenResponse.json();
            const EPHEMERAL_KEY = data.client_secret.value;

            // 2. Inicializar conexão WebRTC (PeerConnection)
            this.peerConnection = new RTCPeerConnection();

            // Configurar áudio de retorno (o que a IA fala)
            this.peerConnection.ontrack = (event) => {
                this.audioElement.srcObject = event.streams[0];
            };

            // 3. Capturar Microfone do Usuário
            // echoCancellation: true é vital para a IA não ouvir a própria voz
            const ms = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true } });
            this.localStream = ms;
            
            // Adiciona o microfone à conexão
            this.peerConnection.addTrack(ms.getTracks()[0]);

            // 4. Canal de Dados (para eventos de texto/controle)
            this.dc = this.peerConnection.createDataChannel("oai-events");
            this.dc.onopen = () => this.onStatusChange("ouvindo");
            this.dc.onmessage = (e) => this.handleDataMessage(e);

            // 5. Criar Oferta SDP (Sinalização)
            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);

            // 6. Enviar Oferta para OpenAI e receber Resposta
            const baseUrl = "https://api.openai.com/v1/realtime";
            const model = "gpt-4o-realtime-preview-2024-10-01";
            
            const sdpResponse = await fetch(`${baseUrl}?model=${model}`, {
                method: "POST",
                body: offer.sdp,
                headers: {
                    "Authorization": `Bearer ${EPHEMERAL_KEY}`,
                    "Content-Type": "application/sdp"
                },
            });

            if (!sdpResponse.ok) throw new Error("Erro na conexão SDP com OpenAI");

            // 7. Definir a resposta remota (Conexão estabelecida!)
            const answerSdp = await sdpResponse.text();
            const answer = { type: "answer", sdp: answerSdp };
            await this.peerConnection.setRemoteDescription(answer);

            this.isSessionActive = true;
            this.monitorarAudio(); // Inicia visualizador de áudio (opcional)

        } catch (erro) {
            console.error("Erro VoiceClient:", erro);
            this.onStatusChange("erro");
            this.stop();
            alert("Erro ao iniciar voz: " + erro.message);
        }
    }

    stop() {
        // Encerra tudo
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }
        this.isSessionActive = false;
        this.onStatusChange("parado");
    }

    // Monitor simples de volume para animações
    monitorarAudio() {
        if (!this.localStream) return;
        
        const audioContext = new AudioContext();
        const source = audioContext.createMediaStreamSource(this.localStream);
        const analyzer = audioContext.createAnalyser();
        analyzer.fftSize = 256;
        source.connect(analyzer);
        
        const bufferLength = analyzer.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);
        
        const checkVolume = () => {
            if (!this.isSessionActive) {
                audioContext.close();
                return;
            }
            analyzer.getByteFrequencyData(dataArray);
            let sum = 0;
            for(let i = 0; i < bufferLength; i++) sum += dataArray[i];
            const average = sum / bufferLength;
            
            // Chama callback para atualizar UI
            if (this.onAudioLevel) this.onAudioLevel(average);
            
            requestAnimationFrame(checkVolume);
        };
        checkVolume();
    }

    handleDataMessage(e) {
        // Aqui podemos processar eventos JSON vindos da OpenAI (ex: transcrição em tempo real)
        try {
            const msg = JSON.parse(e.data);
            // console.log("Evento OpenAI:", msg.type);
        } catch(err) {}
    }
}