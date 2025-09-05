<?php
// Inicia a sessão explicitamente no início do arquivo
session_start();

// Define o título da página
$pageTitle = "Tire suas dúvidas com IA - MedinFocus";

// Verificação de autenticação simplificada
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Redireciona para o login se não estiver autenticado
    header("Location: login.php?error=auth_required");
    exit();
}

// Obtém informações do usuário a partir da sessão com valores padrão de fallback
$userId      = $_SESSION['user_id']      ?? 0;
$userName    = $_SESSION['user_name']    ?? 'Usuário';
$accessLevel = $_SESSION['access_level'] ?? 1;
$userTurma   = $_SESSION['turma']        ?? '1º Ano';

// Diretório para salvar históricos de chat e anotações
$chatDir   = 'chats/';
$notesFile = $chatDir . 'user_' . $userId . '_notes.json';

// Cria o diretório de chats se não existir
if (!file_exists($chatDir)) {Q
    if (!mkdir($chatDir, 0755, true)) {
        die("Erro: Não foi possível criar o diretório de chats. Verifique as permissões.");
    }
}

// Cria o arquivo de anotações se não existir
if (!file_exists($notesFile)) {
    $defaultNotes = [ 'notes' => [] ];
    if (!file_put_contents($notesFile, json_encode($defaultNotes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        // Não interrompe o uso do sistema, apenas avisa
        $notesError = "Não foi possível criar o arquivo de anotações. Algumas funcionalidades podem estar indisponíveis.";
    }
}

// Carrega as anotações salvas
$notes = [];
if (file_exists($notesFile)) {
    $notesContent = file_get_contents($notesFile);
    if ($notesContent !== false) {
        $notesData = json_decode($notesContent, true);
        if (is_array($notesData) && isset($notesData['notes'])) {
            $notes = $notesData['notes'];
        }
    }
}

// Processamento de formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processamento para salvar uma nova anotação
    if (isset($_POST['action']) && $_POST['action'] === 'save_note') {
        $noteText  = trim($_POST['note_text']  ?? '');
        $noteTitle = trim($_POST['note_title'] ?? '');
        
        if (!empty($noteText)) {
            // Adiciona a nova anotação
            $notes[] = [
                'id'    => time() . '_' . mt_rand(1000, 9999),
                'title' => !empty($noteTitle) ? $noteTitle : 'Anotação ' . (count($notes) + 1),
                'text'  => $noteText,
                'date'  => date('Y-m-d H:i:s')
            ];
            
            // Salva o arquivo atualizado
            $notesData = ['notes' => $notes];
            if (file_put_contents($notesFile, json_encode($notesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $noteMessage = ['type' => 'success', 'text' => 'Anotação salva com sucesso!'];
            } else {
                $noteMessage = ['type' => 'error', 'text' => 'Erro ao salvar a anotação.'];
            }
        } else {
            $noteMessage = ['type' => 'error', 'text' => 'O texto da anotação não pode estar vazio.'];
        }
    }
    
    // Processamento para excluir uma anotação
    if (isset($_POST['action']) && $_POST['action'] === 'delete_note') {
        $noteId = $_POST['note_id'] ?? '';
        
        // Encontra e remove a anotação
        foreach ($notes as $key => $note) {
            if (isset($note['id']) && $note['id'] === $noteId) {
                unset($notes[$key]);
                break;
            }
        }
        
        // Reindexação do array
        $notes = array_values($notes);
        
        // Salva o arquivo atualizado
        $notesData = ['notes' => $notes];
        if (file_put_contents($notesFile, json_encode($notesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $noteMessage = ['type' => 'success', 'text' => 'Anotação excluída com sucesso!'];
        } else {
            $noteMessage = ['type' => 'error', 'text' => 'Erro ao excluir a anotação.'];
        }
    }
}

// Inclui o cabeçalho HTML
include_once 'includes/header.php';

// Inclui a barra lateral de navegação
include_once 'includes/sidebar_nav.php';
?>

<div class="flex-1 flex flex-col">
    <!-- Barra superior com título e botão de menu -->
    <header class="bg-white shadow-md p-4 flex items-center justify-between">
        <button id="openSidebarBtn" class="text-gray-500 hover:text-gray-700 lg:hidden">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="text-xl font-bold text-gray-800">Tire suas dúvidas com IA</span>
    </header>

    <!-- Conteúdo principal -->
    <main class="flex-1 p-0 flex flex-col md:flex-row">
        <!-- Área do Chat - Ocupa toda a largura em mobile e 2/3 em desktop -->
        <div class="flex-1 flex flex-col h-full bg-gray-50 md:border-r border-gray-200">
            <!-- Histórico de mensagens do chat -->
            <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
                <!-- Mensagem de boas-vindas do sistema -->
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                            AI
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-md max-w-3xl">
                        <p class="text-gray-800">
                            Olá, <?php echo htmlspecialchars($userName); ?>! Sou o assistente de estudos do MedinFocus.
                            Como posso ajudar você hoje? Pode me fazer perguntas sobre medicina, anatomia, fisiologia,
                            ou qualquer assunto relacionado aos seus estudos.
                        </p>
                    </div>
                </div>

                <!-- As mensagens do chat serão adicionadas dinamicamente via JavaScript -->
            </div>

            <!-- Entrada de texto e envio de mensagem -->
            <div class="border-t border-gray-200 p-4 bg-white">
                <form id="chat-form" class="flex space-x-2">
                    <div class="flex-1 relative">
                        <textarea id="user-message" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                  placeholder="Digite sua pergunta aqui..." rows="2"></textarea>
                        <button type="button" id="clear-chat" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600" title="Limpar conversa">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors flex items-center" title="Enviar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Painel lateral de anotações - Oculto em mobile, 1/3 em desktop -->
        <div id="notes-panel" class="hidden md:flex md:w-80 lg:w-96 flex-col bg-white border-l border-gray-200">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Suas Anotações</h2>
                <button id="new-note-btn" class="text-blue-600 hover:text-blue-800" title="Nova anotação">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </button>
            </div>

            <?php if (isset($noteMessage)): ?>
                <div class="p-3 m-3 rounded-lg <?php echo $noteMessage['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $noteMessage['text']; ?>
                </div>
            <?php endif; ?>

            <!-- Lista de anotações -->
            <div class="flex-1 overflow-y-auto p-3 space-y-3">
                <?php if (count($notes) > 0): ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="bg-gray-50 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($note['title']); ?></h3>
                                <form method="post" class="inline">
                                    <input type="hidden" name="action" value="delete_note">
                                    <input type="hidden" name="note_id" value="<?php echo htmlspecialchars($note['id']); ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Excluir">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <p class="text-sm text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($note['text'])); ?></p>
                            <p class="text-xs text-gray-500 mt-2"><?php echo date("d/m/Y H:i", strtotime($note['date'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <p>Nenhuma anotação encontrada.</p>
                        <p class="text-sm mt-2">Clique no botão + para criar uma nova.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Botão flutuante para mostrar/ocultar anotações em telas pequenas -->
    <button id="toggle-notes" class="md:hidden fixed bottom-6 right-6 bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg" title="Anotações">
        <svg id="notes-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        <svg id="close-notes-icon" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

<!-- Modal para Criar Nova Anotação -->
<div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Nova Anotação</h3>
        
        <form action="" method="post">
            <input type="hidden" name="action" value="save_note">
            
            <div class="mb-4">
                <label for="note_title" class="block text-sm font-medium text-gray-700 mb-1">Título da Anotação</label>
                <input type="text" id="note_title" name="note_title"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Ex: Resumo de Cardiologia">
            </div>
            
            <div class="mb-4">
                <label for="note_text" class="block text-sm font-medium text-gray-700 mb-1">Conteúdo</label>
                <textarea id="note_text" name="note_text" required rows="5"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Digite aqui suas anotações..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancelNoteBtn"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ===== Config =====
    const N8N_ENDPOINT = 'http://localhost:5678/webhook-test/chat_ia'; // PRODUÇÃO (HTTPS)
    const SESSION_ID   = <?php echo json_encode('user-' . $userId); ?>;     // mantém conversa por usuário

    // Elementos DOM
    const chatForm           = document.getElementById('chat-form');
    const userMessageInput   = document.getElementById('user-message');
    const chatMessages       = document.getElementById('chat-messages');
    const clearChatButton    = document.getElementById('clear-chat');
    const toggleNotesButton  = document.getElementById('toggle-notes');
    const notesPanel         = document.getElementById('notes-panel');
    const newNoteBtn         = document.getElementById('new-note-btn');
    const noteModal          = document.getElementById('noteModal');
    const cancelNoteBtn      = document.getElementById('cancelNoteBtn');
    const notesIcon          = document.getElementById('notes-icon');
    const closeNotesIcon     = document.getElementById('close-notes-icon');

    // Array para armazenar mensagens da sessão atual (apenas no front)
    let chatHistory = [];

    // Função para enviar mensagem do usuário
    chatForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const userMessage = userMessageInput.value.trim();
        if (userMessage === '') return;

        addMessageToChat('user', userMessage);
        userMessageInput.value = '';
        showTypingIndicator();
        fetchAIResponse(userMessage);
    });

    // Adiciona mensagem ao chat
    function addMessageToChat(sender, message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex items-start';

        if (sender === 'user') {
            messageDiv.innerHTML = `
                <div class="flex-1"></div>
                <div class="bg-blue-100 rounded-lg p-4 shadow-md max-w-3xl">
                    <p class="text-gray-800">${escapeHTML(message)}</p>
                </div>
                <div class="flex-shrink-0 ml-3">
                    <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 font-bold">
                        ${getInitials(<?php echo json_encode($userName); ?>)}
                    </div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="flex-shrink-0 mr-3">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                        AI
                    </div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-md max-w-3xl relative group">
                    <p class="text-gray-800">${formatAIResponse(message)}</p>
                    <button class="save-to-note absolute top-2 right-2 opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 transition-opacity" title="Adicionar às anotações">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </button>
                </div>
            `;
            setTimeout(() => {
                const saveButton = messageDiv.querySelector('.save-to-note');
                if (saveButton) {
                    saveButton.addEventListener('click', function() {
                        openNoteModalWithText(message);
                    });
                }
            }, 10);
        }

        chatMessages.appendChild(messageDiv);

        chatHistory.push({
            sender: sender,
            message: message,
            timestamp: new Date().toISOString()
        });

        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Indicador de digitação
    function showTypingIndicator() {
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'flex items-start typing-indicator';
        typingIndicator.id = 'typing-indicator';
        typingIndicator.innerHTML = `
            <div class="flex-shrink-0 mr-3">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">AI</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-md max-w-3xl">
                <p class="text-gray-800">Escrevendo<span class="animate-typing">...</span></p>
            </div>
        `;
        chatMessages.appendChild(typingIndicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function removeTypingIndicator() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) typingIndicator.remove();
    }

    // ====== FETCH para o n8n (com timeout e parser robusto) ======
    async function fetchAIResponse(userMessage) {
        const payload = {
            sessionId: SESSION_ID,                 // mantém conversa por usuário (no n8n)
            user: <?php echo json_encode($userName); ?>,
            message: userMessage
        };

        const controller = new AbortController();
        const timeoutId  = setTimeout(() => controller.abort(), 25000); // 25s

        try {
            const resp = await fetch(N8N_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                mode: 'cors',
                signal: controller.signal
            });

            if (!resp.ok) {
                throw new Error('HTTP ' + resp.status);
            }

            // Tenta JSON; se falhar, lê texto puro
            let data, text;
            try {
                data = await resp.json();
            } catch (_) {
                text = await resp.text();
            }

            removeTypingIndicator();

            const aiResponse = parseAIResponse(data, text);
            addMessageToChat('ai', aiResponse);
        } catch (err) {
            console.error('Erro no fetch:', err);
            removeTypingIndicator();
            addMessageToChat('ai',
                'Desculpe, ocorreu um erro ao processar sua pergunta. ' +
                'Por favor, verifique sua conexão e tente novamente.'
            );
        } finally {
            clearTimeout(timeoutId);
        }
    }

    // Parser robusto para diferentes formatos do webhook
    function parseAIResponse(data, fallbackText='') {
        // 1) Se o n8n já retorna string simples
        if (typeof data === 'string') return data.trim();
        if (!data) return (fallbackText || 'Sem resposta.').toString();

        // 2) Estrutura que você mostrou no curl de produção:
        // {"index":0,"message":{"role":"assistant","content":"..."}}
        if (data?.message?.content) {
            if (typeof data.message.content === 'string') return data.message.content;
            if (typeof data.message.content?.resposta === 'string') return data.message.content.resposta;
        }

        // 3) Estrutura tipo OpenAI:
        // { choices: [ { message: { content: "..."} } ] }
        const ch0 = data?.choices?.[0]?.message?.content;
        if (typeof ch0 === 'string') return ch0;
        if (typeof ch0?.resposta === 'string') return ch0.resposta;

        // 4) Campo resposta direto
        if (typeof data?.resposta === 'string') return data.resposta;

        // 5) Fallback para texto do HTTP caso não seja JSON válido
        if (fallbackText) return fallbackText.toString();

        // 6) Último recurso: stringify alguns campos úteis
        try { return JSON.stringify(data); } catch (_) { return 'Resposta em formato inesperado.'; }
    }

    // Botão para limpar o chat (e opcionalmente resetar a memória no n8n)
    clearChatButton.addEventListener('click', async function() {
        if (chatHistory.length > 0) {
            if (!confirm('Tem certeza que deseja limpar a conversa atual?')) return;

            // Limpa UI
            chatMessages.innerHTML = '';
            const welcomeDiv = document.createElement('div');
            welcomeDiv.className = 'flex items-start';
            welcomeDiv.innerHTML = `
                <div class="flex-shrink-0 mr-3">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">AI</div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-md max-w-3xl">
                    <p class="text-gray-800">
                        Olá, <?php echo htmlspecialchars($userName); ?>! Sou o assistente de estudos do MedinFocus.
                        Como posso ajudar você hoje? Pode me fazer perguntas sobre medicina, anatomia, fisiologia,
                        ou qualquer assunto relacionado aos seus estudos.
                    </p>
                </div>
            `;
            chatMessages.appendChild(welcomeDiv);
            chatHistory = [];

            // (Opcional) Se o seu workflow aceita reset por sessionId:
            try {
                await fetch(N8N_ENDPOINT, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sessionId: SESSION_ID, reset: true }),
                    mode: 'cors'
                });
            } catch (e) {
                // Se não houver suporte a reset no n8n, ignore
                console.debug('Reset remoto opcional falhou/ignorado:', e);
            }
        }
    });

    // Toggle para mostrar/ocultar o painel de anotações em telas pequenas
    toggleNotesButton?.addEventListener('click', function() {
        if (notesPanel.classList.contains('hidden')) {
            notesPanel.classList.remove('hidden');
            notesPanel.classList.add('fixed', 'inset-0', 'z-40');
            notesIcon.classList.add('hidden');
            closeNotesIcon.classList.remove('hidden');
        } else {
            notesPanel.classList.add('hidden');
            notesPanel.classList.remove('fixed', 'inset-0', 'z-40');
            notesIcon.classList.remove('hidden');
            closeNotesIcon.classList.add('hidden');
        }
    });

    // Abrir modal para criar nova anotação
    newNoteBtn?.addEventListener('click', function() {
        document.getElementById('note_title').value = '';
        document.getElementById('note_text').value = '';
        noteModal.classList.remove('hidden');
    });

    // Fechar modal de anotação
    cancelNoteBtn?.addEventListener('click', function() {
        noteModal.classList.add('hidden');
    });

    // Fechar modal ao clicar fora dele
    noteModal?.addEventListener('click', function(e) {
        if (e.target === noteModal) {
            noteModal.classList.add('hidden');
        }
    });

    // Ajusta a altura do textarea conforme o conteúdo
    userMessageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        const newHeight = Math.min(this.scrollHeight, 150);
        this.style.height = newHeight + 'px';
    });

    // Animação "digitando..."
    const style = document.createElement('style');
    style.innerHTML = `
        .animate-typing { animation: typing 1s infinite; }
        @keyframes typing {
            0% { content: ""; }
            25% { content: "."; }
            50% { content: ".."; }
            75% { content: "..."; }
            100% { content: ""; }
        }
    `;
    document.head.appendChild(style);

    // Utils
    function openNoteModalWithText(text) {
        document.getElementById('note_text').value = text;
        noteModal.classList.remove('hidden');
    }

    function formatAIResponse(text) {
        if (!text) return '';
        // links clicáveis
        text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>');
        // quebras de linha
        text = text.replace(/\n/g, '<br>');
        return text;
    }

    function getInitials(name) {
        if (!name) return 'U';
        return name.split(' ').map(part => part.charAt(0)).join('').toUpperCase().substring(0, 2);
    }

    function escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php include_once 'includes/footer.php'; ?>
