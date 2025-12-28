<?php
// ARQUIVO: chat_texto.php
session_start();
require_once 'php/config.php';

// Verificação de Segurança
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat IA - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0b0f1a', primary: '#0ea5e9' }
                    }
                }
            }
        }
    </script>
    <style>
        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body class="bg-brand-dark text-slate-200 font-sans h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col relative w-full">
        <header class="h-16 border-b border-slate-800 flex items-center justify-between px-6 bg-slate-900/50 backdrop-blur-md">
            <div class="flex items-center gap-4">
                <a href="chat_ia.php" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-400 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="font-bold text-white leading-tight">Mentor Clínico</h1>
                    <p class="text-[10px] text-emerald-500 font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-circle text-[8px] mr-1 animate-pulse"></i>Online
                    </p>
                </div>
            </div>
        </header>

        <div id="chatWindow" class="flex-1 overflow-y-auto p-6 space-y-6 chat-scroll pb-32">
            <div class="flex gap-4">
                <div class="w-8 h-8 rounded-lg bg-brand-primary flex items-center justify-center flex-shrink-0 mt-1">
                    <i class="fa-solid fa-robot text-xs text-white"></i>
                </div>
                <div class="bg-slate-800/50 border border-slate-700 p-4 rounded-2xl rounded-tl-none max-w-2xl">
                    <p class="text-sm text-slate-300 leading-relaxed">
                        Olá! Sou a IA do MedInFocus. Posso ajudar com dúvidas sobre farmacologia, fisiologia, análise de casos ou diretrizes médicas. Como posso ser útil?
                    </p>
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 w-full bg-brand-dark border-t border-slate-800 p-4 md:p-6">
            <form id="chatForm" class="relative max-w-4xl mx-auto flex gap-3">
                <input type="text" id="userInput" 
                    class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl px-4 py-3 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all placeholder-slate-500"
                    placeholder="Ex: Qual a conduta inicial para cetoacidose diabética?" autocomplete="off">
                
                <button type="submit" id="sendBtn" class="bg-brand-primary hover:bg-sky-600 text-white px-6 rounded-xl font-medium transition-colors flex items-center gap-2">
                    <i class="fa-regular fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </main>

    <script>
        const form = document.getElementById('chatForm');
        const input = document.getElementById('userInput');
        const chatWindow = document.getElementById('chatWindow');

        function addMessage(text, isUser) {
            const div = document.createElement('div');
            div.className = `flex gap-4 ${isUser ? 'flex-row-reverse' : ''}`;
            
            const icon = isUser 
                ? `<div class="w-8 h-8 rounded-lg bg-slate-700 flex items-center justify-center flex-shrink-0 mt-1"><i class="fa-solid fa-user text-xs"></i></div>`
                : `<div class="w-8 h-8 rounded-lg bg-brand-primary flex items-center justify-center flex-shrink-0 mt-1"><i class="fa-solid fa-robot text-xs"></i></div>`;

            const bubbleClass = isUser 
                ? 'bg-brand-primary/10 border border-brand-primary/20 text-sky-100 rounded-tr-none' 
                : 'bg-slate-800/50 border border-slate-700 text-slate-300 rounded-tl-none';

            div.innerHTML = `
                ${icon}
                <div class="${bubbleClass} p-4 rounded-2xl max-w-2xl text-sm leading-relaxed shadow-sm">
                    ${text}
                </div>
            `;
            
            chatWindow.appendChild(div);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const msg = input.value.trim();
            if(!msg) return;

            addMessage(msg, true);
            input.value = '';

            // Loading
            const loadingId = 'loading-' + Date.now();
            const loadingDiv = document.createElement('div');
            loadingDiv.id = loadingId;
            loadingDiv.className = 'flex gap-4';
            loadingDiv.innerHTML = `
                <div class="w-8 h-8 rounded-lg bg-brand-primary flex items-center justify-center flex-shrink-0 mt-1"><i class="fa-solid fa-robot text-xs text-white"></i></div>
                <div class="bg-slate-800/50 border border-slate-700 p-4 rounded-2xl rounded-tl-none">
                    <div class="flex gap-1">
                        <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            `;
            chatWindow.appendChild(loadingDiv);
            chatWindow.scrollTop = chatWindow.scrollHeight;

            try {
                const res = await fetch('php/processar_chat.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ message: msg })
                });
                const data = await res.json();
                
                document.getElementById(loadingId).remove();
                
                if(data.error) {
                    addMessage("Erro: " + data.error, false);
                } else {
                    addMessage(data.response, false);
                }
            } catch (err) {
                document.getElementById(loadingId).remove();
                addMessage("Erro de conexão. Tente novamente.", false);
            }
        };
    </script>
</body>
</html>