<?php
/**
 * MEDINFOCUS - Chat IA (Produção)
 * Robusto: NOVA sessão por load/refresh/reabrir + limpeza de wrappers JSON (myField/response) + render seguro
 */

session_start();

// Anti-cache (reduz “restauração”/conteúdo antigo)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// 1. SEGURANÇA: Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. DADOS DO USUÁRIO
$nomeUsuario = $_SESSION['user_name'] ?? 'Doutor(a)';
$firstName = htmlspecialchars(explode(' ', $nomeUsuario)[0]);

// Para inserir string PHP dentro do JS com segurança
$nomeUsuarioJS = json_encode(
    $nomeUsuario,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IA Mentor - MEDINFOCUS</title>

    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0b0f1a',
                            primary: '#0284c7',
                            surface: '#1e293b',
                            chatUser: '#0284c7',
                            chatBot: '#1e293b'
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0b0f1a; }

        .chat-scrollbar::-webkit-scrollbar { width: 6px; }
        .chat-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .chat-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .glass-header {
            background: rgba(11, 15, 26, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(30, 41, 59, 0.5);
        }

        .glass-input {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(30, 41, 59, 0.5);
        }

        .typing-dot { animation: typing 1.4s infinite ease-in-out both; }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>
</head>

<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 relative">

        <header class="glass-header h-20 px-6 flex items-center justify-between absolute top-0 w-full z-10">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-brand-primary to-cyan-400 flex items-center justify-center shadow-lg shadow-brand-primary/20">
                    <i class="fa-solid fa-brain text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-white font-bold text-lg leading-tight">Mentor IA</h1>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs text-emerald-500 font-bold uppercase tracking-wider">Online & Conectado</span>
                    </div>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-3 px-3 py-1.5 rounded-full bg-slate-900/50 border border-slate-700/50">
                <i class="fa-solid fa-fingerprint text-xs text-slate-500"></i>
                <span id="session-badge" class="text-[10px] font-mono text-slate-400">ID: carregando...</span>
                <span id="webhook-status" class="text-[10px] font-mono text-slate-400 border-l border-slate-700 pl-3">Pronto</span>
            </div>
        </header>

        <div id="chat-container" class="flex-1 overflow-y-auto chat-scrollbar p-6 pt-24 pb-28 space-y-6 flex flex-col">

            <div class="flex justify-center">
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest py-1 px-3 bg-slate-800/50 rounded-full">
                    Sessão Segura Iniciada - <?php echo date('H:i'); ?>
                </span>
            </div>

            <div class="flex items-start gap-3 max-w-[85%]">
                <div class="w-8 h-8 rounded-full bg-brand-surface flex-shrink-0 flex items-center justify-center border border-slate-700">
                    <i class="fa-solid fa-robot text-brand-primary text-xs"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold text-slate-500 ml-1">MedInFocus AI</span>
                    <div class="bg-brand-surface p-4 rounded-2xl rounded-tl-none text-sm text-slate-300 shadow-md border border-slate-800">
                        <p>Olá, <strong><?php echo $firstName; ?></strong>! Sou seu assistente virtual focado em medicina. Como posso ajudar em seus estudos hoje?</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="glass-input absolute bottom-0 w-full p-6">
            <form id="chat-form" class="relative flex items-end gap-3 max-w-5xl mx-auto">

                <button type="button" class="p-4 text-slate-500 hover:text-brand-primary transition-colors" title="Anexar (em breve)">
                    <i class="fa-solid fa-paperclip text-lg"></i>
                </button>

                <div class="flex-1 bg-slate-900/50 border border-slate-700 rounded-2xl flex items-center p-2 focus-within:border-brand-primary focus-within:ring-1 focus-within:ring-brand-primary/50 transition-all shadow-inner">
                    <textarea
                        id="message-input"
                        rows="1"
                        class="w-full bg-transparent text-white placeholder-slate-500 text-sm px-4 py-2 focus:outline-none resize-none custom-scrollbar max-h-32"
                        placeholder="Digite sua dúvida clínica ou acadêmica..."
                        oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                </div>

                <button type="submit" id="send-btn" class="p-4 bg-brand-primary hover:bg-sky-500 text-white rounded-2xl shadow-lg shadow-brand-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group">
                    <i class="fa-solid fa-paper-plane text-lg group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                </button>
            </form>

            <p class="text-center text-[10px] text-slate-600 mt-3 font-medium">
                <i class="fa-solid fa-shield-halved mr-1"></i> Conteúdo restrito a fins educacionais médicos.
            </p>
        </div>

    </main>

    <script>
        // =========================
        // CONFIGURAÇÕES
        // =========================
        const N8N_WEBHOOK_URL = 'https://n8n.alunosdamedicina.com/webhook/8b49a02c-8597-4449-9e2f-4f37cbffa9b5';
        const userName = <?php echo $nomeUsuarioJS; ?>;

        // =========================
        // SESSÃO (robusta):
        // - Nova a cada load/refresh/reabrir
        // - Se página voltar de bfcache, força nova também
        // =========================
        function newSessionId() {
            return (crypto.randomUUID?.() ?? (Date.now() + '-' + Math.random().toString(16).slice(2)));
        }
        let chatSessionId = newSessionId();

        function updateSessionBadge() {
            const el = document.getElementById('session-badge');
            if (el) el.textContent = 'ID: ' + String(chatSessionId).slice(0, 8) + '...';
        }
        updateSessionBadge();

        window.addEventListener('pageshow', (e) => {
            if (e.persisted) {
                chatSessionId = newSessionId();
                updateSessionBadge();
            }
        });
        window.addEventListener('unload', () => {});

        // =========================
        // ELEMENTOS
        // =========================
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const chatContainer = document.getElementById('chat-container');
        const sendBtn = document.getElementById('send-btn');
        const statusBadge = document.getElementById('webhook-status');

        // =========================
        // UI / HELPERS
        // =========================
        function setStatus(text, tone) {
            statusBadge.innerText = text;
            statusBadge.classList.remove('text-slate-400', 'text-amber-400', 'text-emerald-400', 'text-rose-500');
            statusBadge.classList.add(tone);
        }

        function scrollToBottom() {
            chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[c]));
        }

        // Render seguro: escapa HTML e depois aplica apenas um “plus” leve (negrito ** **)
        function renderSafeRichText(plainText) {
            const escaped = escapeHtml(plainText);

            // negrito markdown: **texto**
            // seguro porque o texto já foi escapado (não permite inserir tags)
            return escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        }

        function appendMessage(text, sender) {
            const isUser = sender === 'user';

            let cleanText = (typeof text === 'string') ? text : JSON.stringify(text, null, 2);

            // remove aspas extras comuns
            if (cleanText.startsWith('"') && cleanText.endsWith('"')) {
                cleanText = cleanText.slice(1, -1);
            }

            const safeHtml = renderSafeRichText(cleanText);

            const html = `
                <div class="flex items-start gap-3 max-w-[85%] ${isUser ? 'ml-auto flex-row-reverse' : ''}">
                    <div class="w-8 h-8 rounded-full ${isUser ? 'bg-brand-chatUser' : 'bg-brand-surface'} flex-shrink-0 flex items-center justify-center border border-slate-700 shadow-md">
                        <i class="fa-solid ${isUser ? 'fa-user' : 'fa-robot'} ${isUser ? 'text-white' : 'text-brand-primary'} text-xs"></i>
                    </div>
                    <div class="flex flex-col gap-1 ${isUser ? 'items-end' : ''}">
                        <span class="text-[10px] font-bold text-slate-500 ${isUser ? 'mr-1' : 'ml-1'} uppercase">${isUser ? 'Você' : 'IA Mentor'}</span>
                        <div class="${isUser ? 'bg-brand-chatUser text-white' : 'bg-brand-surface text-slate-300'} p-4 rounded-2xl ${isUser ? 'rounded-tr-none' : 'rounded-tl-none'} text-sm shadow-md border ${isUser ? 'border-transparent' : 'border-slate-800'}">
                            <p class="whitespace-pre-wrap leading-relaxed">${safeHtml}</p>
                        </div>
                    </div>
                </div>
            `;

            chatContainer.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }

        function showTypingIndicator() {
            const id = 'typing-' + Date.now();
            const html = `
                <div id="${id}" class="flex items-start gap-3 max-w-[85%] typing-indicator-container">
                    <div class="w-8 h-8 rounded-full bg-brand-surface flex-shrink-0 flex items-center justify-center border border-slate-700">
                        <i class="fa-solid fa-robot text-brand-primary text-xs"></i>
                    </div>
                    <div class="bg-brand-surface p-4 rounded-2xl rounded-tl-none border border-slate-800 w-20">
                        <div class="flex items-center justify-center gap-1">
                            <div class="w-2 h-2 bg-slate-500 rounded-full typing-dot"></div>
                            <div class="w-2 h-2 bg-slate-500 rounded-full typing-dot"></div>
                            <div class="w-2 h-2 bg-slate-500 rounded-full typing-dot"></div>
                        </div>
                    </div>
                </div>
            `;
            chatContainer.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
            return id;
        }

        function removeTypingIndicator(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        // Remove ```json ...``` e ``` ... ```
        function stripCodeFences(s) {
            let t = String(s || '').trim();
            t = t.replace(/^```(?:json|JSON)?\s*/i, '');
            t = t.replace(/```$/i, '');
            return t.trim();
        }

        // Tenta extrair conteúdo de wrappers tipo:
        // { "myField": "...." } ou { "response": "..." }
        // Mesmo quando NÃO é JSON válido (por quebra de linha dentro de aspas)
        function unwrapLooseSingleFieldObjectLike(s, fieldName) {
            const src = String(s || '').trim();
            const re = new RegExp('^\\{\\s*"' + fieldName + '"\\s*:\\s*', 'i');
            if (!re.test(src)) return null;

            const colon = src.indexOf(':');
            if (colon < 0) return null;

            let i = colon + 1;
            while (i < src.length && /\s/.test(src[i])) i++;

            const quote = src[i];
            if (quote !== '"' && quote !== "'") return null;

            i++; // começa o conteúdo depois da aspa inicial

            const lastBrace = src.lastIndexOf('}');
            if (lastBrace < 0) return null;

            // acha a última aspa antes do último }
            const j = src.lastIndexOf(quote, lastBrace - 1);
            if (j <= i) return null;

            let content = src.slice(i, j);

            // des-escapa comuns (quando vierem como \n, \")
            content = content.replace(/\\n/g, '\n').replace(/\\"/g, '"').replace(/\\t/g, '\t');

            return content.trim();
        }

        function extractBotResponse(data) {
            // 1) Se veio texto puro, ainda assim pode estar “embrulhado”
            if (typeof data === 'string') {
                let t = stripCodeFences(data);

                // tenta JSON válido primeiro
                try {
                    const parsed = JSON.parse(t);
                    return extractBotResponse(parsed);
                } catch {}

                // tenta desembrulhar “JSON-like” inválido
                const fields = ['myField', 'response', 'text', 'output', 'answer', 'message'];
                for (const f of fields) {
                    const unwrapped = unwrapLooseSingleFieldObjectLike(t, f);
                    if (unwrapped) return unwrapped;
                }

                return t;
            }

            // 2) Se veio array (n8n às vezes retorna lista)
            if (Array.isArray(data)) {
                for (const item of data) {
                    const v = extractBotResponse(item);
                    if (typeof v === 'string' && v.trim()) return v;
                }
                return JSON.stringify(data);
            }

            // 3) Se veio objeto
            if (data && typeof data === 'object') {
                const bot =
                    data.response ||
                    data.text ||
                    data.output ||
                    data.answer ||
                    data.message ||
                    data.myField;

                if (typeof bot === 'string') {
                    return extractBotResponse(bot); // passa pela limpeza/unwrap também
                }
                if (bot != null) {
                    return String(bot);
                }

                // fallback: primeira string de qualquer campo
                const firstString = Object.values(data).find(v => typeof v === 'string' && v.trim());
                if (firstString) return extractBotResponse(firstString);

                return JSON.stringify(data);
            }

            return '';
        }

        async function fetchWithTimeout(url, options = {}, timeoutMs = 45000) {
            const controller = new AbortController();
            const t = setTimeout(() => controller.abort(), timeoutMs);
            try {
                return await fetch(url, { ...options, signal: controller.signal });
            } finally {
                clearTimeout(t);
            }
        }

        // =========================
        // SUBMIT
        // =========================
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message) return;

            appendMessage(message, 'user');
            messageInput.value = '';
            messageInput.style.height = 'auto';

            messageInput.disabled = true;
            sendBtn.disabled = true;
            setStatus('Enviando...', 'text-amber-400');

            const typingId = showTypingIndicator();

            try {
                const payload = {
                    message,
                    sessionId: chatSessionId,
                    user: userName
                };

                const response = await fetchWithTimeout(N8N_WEBHOOK_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                }, 45000);

                const raw = await response.text();

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status} - ${response.statusText}. Body: ${raw?.slice(0, 300)}`);
                }

                let data;
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch {
                    data = raw; // texto puro
                }

                removeTypingIndicator(typingId);

                const botResponse = extractBotResponse(data);
                appendMessage(botResponse, 'bot');

                setStatus('Online', 'text-emerald-400');

            } catch (error) {
                removeTypingIndicator(typingId);
                console.error(error);

                appendMessage("⚠️ Erro de conexão com o Mentor IA. Tente novamente em instantes.", 'bot');
                setStatus('Erro', 'text-rose-500');

            } finally {
                messageInput.disabled = false;
                sendBtn.disabled = false;
                messageInput.focus();
            }
        });

        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>
