<?php
session_start();
require_once 'php/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
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
        tailwind.config = { theme: { extend: { colors: { 'brand-dark': '#0f172a', 'brand-primary': '#0ea5e9' } } } }
    </script>
    <style>
        .chat-container { height: calc(100vh - 180px); }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="bg-[#0b0f1a] text-slate-200 font-sans antialiased overflow-hidden">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar.php'; ?>

        <main class="flex-1 flex flex-col p-4 md:p-8">
            <header class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="chat_ia.php" class="p-2 hover:bg-slate-800 rounded-xl transition-colors">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <h1 class="text-xl font-bold">Chat de Dúvida Médica</h1>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-emerald-500 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div> GPT-3.5 ONLINE
                </div>
            </header>

            <div id="chatWindow" class="flex-1 overflow-y-auto pr-4 mb-6 chat-container custom-scrollbar space-y-4">
                <div class="flex gap-4 max-w-3xl">
                    <div class="w-8 h-8 rounded-lg bg-brand-primary flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-robot text-xs text-white"></i>
                    </div>
                    <div class="glass-card p-4 rounded-2xl rounded-tl-none text-sm leading-relaxed">
                        Olá! Sou seu Mentor IA. Como posso auxiliar em sua conduta clínica ou dúvida acadêmica hoje?
                    </div>
                </div>
            </div>

            <div class="relative max-w-4xl mx-auto w-full">
                <form id="chatForm" class="flex gap-3 items-center bg-slate-900/50 p-2 rounded-2xl border border-slate-800 shadow-2xl">
                    <input type="text" id="userInput" placeholder="Digite sua dúvida médica aqui..." 
                           class="flex-1 bg-transparent border-none focus:ring-0 text-sm px-4 py-2 outline-none">
                    <button type="submit" id="sendBtn" class="bg-brand-primary hover:bg-brand-primary/80 text-white w-10 h-10 rounded-xl flex items-center justify-center transition-all">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        const chatForm = document.getElementById('chatForm');
        const userInput = document.getElementById('userInput');
        const chatWindow = document.getElementById('chatWindow');

        function appendMessage(role, text) {
            const div = document.createElement('div');
            div.className = `flex gap-4 max-w-3xl ${role === 'user' ? 'ml-auto flex-row-reverse' : ''}`;
            
            const iconBg = role === 'user' ? 'bg-slate-700' : 'bg-brand-primary';
            const icon = role === 'user' ? 'fa-user' : 'fa-robot';
            const cardStyle = role === 'user' ? 'bg-brand-primary/20 border-brand-primary/30 rounded-tr-none' : 'glass-card rounded-tl-none';

            div.innerHTML = `
                <div class="w-8 h-8 rounded-lg ${iconBg} flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid ${icon} text-xs text-white"></i>
                </div>
                <div class="${cardStyle} p-4 rounded-2xl text-sm leading-relaxed">
                    ${text}
                </div>
            `;
            chatWindow.appendChild(div);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }

        chatForm.onsubmit = async (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (!message) return;

            appendMessage('user', message);
            userInput.value = '';
            
            // Efeito de loading
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'text-[10px] text-slate-500 animate-pulse ml-12';
            loadingDiv.innerText = 'Mentor IA está analisando...';
            chatWindow.appendChild(loadingDiv);

            try {
                const response = await fetch('php/processar_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });
                const data = await response.json();
                chatWindow.removeChild(loadingDiv);

                if (data.error) {
                    appendMessage('system', 'Erro: ' + data.error);
                } else {
                    appendMessage('system', data.response);
                }
            } catch (err) {
                chatWindow.removeChild(loadingDiv);
                appendMessage('system', 'Erro crítico de conexão.');
            }
        };
    </script>
</body>
</html>