<?php
session_start();
require_once 'php/config.php';

// Verifica login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$nomeUsuario = $_SESSION['user_name'] ?? 'Acadêmico';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Texto - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0b0f1a', // Slate 900
                            primary: '#0ea5e9', // Sky 500
                            surface: '#1e293b' // Slate 800
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Scrollbar personalizada */
        .chat-scroll::-webkit-scrollbar { width: 6px; }
        .chat-scroll::-webkit-scrollbar-track { background: transparent; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 20px; }
        
        /* Animação de digitação (...) */
        .typing-dot { animation: typing 1.4s infinite ease-in-out both; }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }

        /* Estilização do Texto da IA (Markdown) */
        .prose p { margin-bottom: 0.8em; line-height: 1.6; }
        .prose ul { list-style-type: disc; margin-left: 1.5em; margin-bottom: 0.8em; }
        .prose ol { list-style-type: decimal; margin-left: 1.5em; margin-bottom: 0.8em; }
        .prose strong { color: #0ea5e9; font-weight: 700; } /* Destaque em azul */
        .prose h3 { font-size: 1.1em; font-weight: bold; color: white; margin-top: 1em; margin-bottom: 0.5em; }
    </style>
</head>
<body class="bg-[#0b0f1a] text-slate-200 font-sans antialiased h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative">
        
        <header class="h-20 border-b border-slate-800/50 bg-[#0b0f1a]/95 backdrop-blur flex items-center px-6 md:px-10 justify-between z-10">
            <div class="flex items-center gap-4">
                <a href="chat_ia.php" class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-brand-primary hover:text-white transition-all shadow-lg">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        Mentor <span class="text-brand-primary">Texto</span>
                    </h1>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Online • GPT-4o</span>
                    </div>
                </div>
            </div>
            
            <button onclick="limparChat()" class="text-xs font-bold text-slate-500 hover:text-rose-500 uppercase tracking-widest transition-colors flex items-center gap-2">
                <i class="fa-solid fa-trash-can"></i> <span class="hidden md:inline">Limpar Histórico</span>
            </button>
        </header>

        <div id="chatBox" class="flex-1 overflow-y-auto p-6 md:p-10 space-y-6 chat-scroll pb-32">
            
            <div class="flex gap-4 max-w-4xl mx-auto fade-in">
                <div class="w-10 h-10 rounded-2xl bg-brand-primary/10 flex-shrink-0 flex items-center justify-center mt-1 border border-brand-primary/20">
                    <i class="fa-solid fa-robot text-brand-primary text-sm"></i>
                </div>
                <div class="flex-1">
                    <div class="bg-slate-800/40 border border-slate-700/50 rounded-3xl rounded-tl-none p-6 text-slate-200 shadow-sm">
                        <p class="font-medium text-white mb-2">Olá, <?php echo htmlspecialchars($nomeUsuario); ?>!</p>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Sou sua IA especializada em medicina. Como posso ajudar nos seus estudos hoje?
                        </p>
                        <ul class="mt-3 space-y-1 text-sm text-slate-500">
                            <li>• Posso explicar fisiopatologias complexas</li>
                            <li>• Analisar casos clínicos descritos</li>
                            <li>• Sugerir diagnósticos diferenciais</li>
                        </ul>
                    </div>
                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider ml-2 mt-1 block">Agora</span>
                </div>
            </div>

        </div>

        <div class="absolute bottom-0 w-full bg-gradient-to-t from-[#0b0f1a] via-[#0b0f1a] to-transparent pt-12 pb-8 px-6 md:px-12">
            <div class="max-w-4xl mx-auto relative">
                <form id="formChat" onsubmit="enviarMensagem(event)" class="relative group">
                    
                    <input type="text" id="inputMensagem" autocomplete="off" placeholder="Descreva um caso clínico ou faça uma pergunta..." 
                           class="w-full bg-slate-800/90 border border-slate-700 text-white rounded-2xl pl-6 pr-16 py-4 shadow-2xl focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary placeholder-slate-500 transition-all">
                    
                    <button type="submit" id="btnEnviar" class="absolute right-2 top-2 bottom-2 w-12 bg-slate-700 hover:bg-brand-primary text-white rounded-xl flex items-center justify-center transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
                
                <p class="text-center text-[10px] text-slate-600 mt-3 font-medium">
                    <i class="fa-solid fa-shield-halved mr-1 text-slate-700"></i>
                    A IA pode cometer erros. Sempre verifique informações médicas cruciais.
                </p>
            </div>
        </div>

    </main>

    <script>
        const chatBox = document.getElementById('chatBox');
        const inputMensagem = document.getElementById('inputMensagem');
        const btnEnviar = document.getElementById('btnEnviar');
        let eventSource = null; 

        // Rolar para o fim automaticamente
        function scrollParaBaixo() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        async function enviarMensagem(e) {
            e.preventDefault();
            const texto = inputMensagem.value.trim();
            if (!texto) return;

            // 1. Mostrar pergunta do usuário
            adicionarMensagemUsuario(texto);
            
            // Bloquear input
            inputMensagem.value = '';
            inputMensagem.disabled = true;
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>'; // Loading icon

            // 2. Preparar balão da IA (Vazio com animação ...)
            const divRespostaIA = adicionarBalaoIA();
            const elementoTextoIA = divRespostaIA.querySelector('.conteudo-ia');
            let textoAcumulado = "";

            // 3. Conectar ao Streaming
            if (eventSource) eventSource.close();
            
            // Chama a API que criamos anteriormente
            eventSource = new EventSource(`api/chat_stream.php?msg=${encodeURIComponent(texto)}`);

            eventSource.onmessage = function(event) {
                // Fim da transmissão
                if(event.data === "[DONE]") {
                    encerrarTransmissao(elementoTextoIA, textoAcumulado);
                    return;
                }

                // Recebendo dados
                try {
                    const data = JSON.parse(event.data);
                    if(data.choices && data.choices[0].delta.content) {
                        textoAcumulado += data.choices[0].delta.content;
                        
                        // Atualização visual rápida (bruta) enquanto digita
                        // Trocamos quebras de linha por <br> para visualização básica
                        elementoTextoIA.innerHTML = textoAcumulado.replace(/\n/g, '<br>');
                        
                        scrollParaBaixo();
                    }
                } catch (err) {
                    console.error("Erro stream:", err);
                }
            };

            eventSource.onerror = function() {
                console.error("Erro de conexão");
                encerrarTransmissao(elementoTextoIA, textoAcumulado);
                if(!textoAcumulado) elementoTextoIA.innerHTML = "<span class='text-rose-500'>Erro ao conectar com o servidor. Tente novamente.</span>";
            };
        }

        function encerrarTransmissao(elemento, textoFinal) {
            if(eventSource) {
                eventSource.close();
                eventSource = null;
            }
            // Formatar Markdown final (Deixa negritos e listas bonitos)
            if(textoFinal) elemento.innerHTML = marked.parse(textoFinal);
            
            // Liberar input
            inputMensagem.disabled = false;
            inputMensagem.focus();
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
            scrollParaBaixo();
        }

        function adicionarMensagemUsuario(texto) {
            const html = `
                <div class="flex gap-4 max-w-4xl mx-auto flex-row-reverse group animate-in slide-in-from-bottom-2 duration-300">
                    <div class="w-10 h-10 rounded-2xl bg-slate-700 flex-shrink-0 flex items-center justify-center mt-1 border border-slate-600">
                        <i class="fa-solid fa-user text-slate-400"></i>
                    </div>
                    <div class="flex-1 text-right">
                        <div class="bg-brand-primary text-white rounded-3xl rounded-tr-none p-4 px-6 shadow-lg inline-block text-left text-base font-medium leading-relaxed">
                            ${texto.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                </div>
            `;
            chatBox.insertAdjacentHTML('beforeend', html);
            scrollParaBaixo();
        }

        function adicionarBalaoIA() {
            const container = document.createElement('div');
            container.className = "flex gap-4 max-w-4xl mx-auto animate-in fade-in duration-500";
            container.innerHTML = `
                <div class="w-10 h-10 rounded-2xl bg-brand-primary/10 flex-shrink-0 flex items-center justify-center mt-1 border border-brand-primary/20 shadow-[0_0_15px_rgba(14,165,233,0.15)]">
                    <i class="fa-solid fa-robot text-brand-primary text-sm"></i>
                </div>
                <div class="flex-1">
                    <div class="bg-slate-800/40 border border-slate-700/50 rounded-3xl rounded-tl-none p-6 text-slate-200 shadow-sm min-h-[60px]">
                        <div class="conteudo-ia prose prose-invert prose-sm max-w-none">
                            <div class="flex gap-1 h-5 items-center pl-2">
                                <div class="w-2 h-2 bg-slate-500 rounded-full typing-dot"></div>
                                <div class="w-2 h-2 bg-slate-500 rounded-full typing-dot"></div>
                                <div class="w-2 h-2 bg-slate-500 rounded-full typing-dot"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            chatBox.appendChild(container);
            scrollParaBaixo();
            return container;
        }

        function limparChat() {
            if(confirm('Deseja limpar o histórico visual desta conversa?')) {
                window.location.reload();
            }
        }
    </script>
</body>
</html>