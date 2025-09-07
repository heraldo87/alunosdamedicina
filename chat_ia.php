<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedinFocus - AI Chat</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="flex flex-col h-full antialiased bg-gray-100">
    <div class="flex flex-1 overflow-hidden">
        <!-- Área Principal de Chat -->
        <main class="flex-1 flex flex-col p-6 max-w-4xl mx-auto w-full">
            <h1 class="text-4xl font-extrabold text-blue-700 mb-6 text-center">MedinFocus IA</h1>
            
            <!-- Caixa de conversa -->
            <div id="chat-box" class="flex-1 bg-white p-6 rounded-lg shadow-lg overflow-y-auto mb-4 border border-gray-200">
                <!-- Mensagens do chat serão exibidas aqui -->
            </div>

            <!-- Formulário de entrada de mensagem -->
            <div class="flex items-center space-x-4">
                <input type="text" id="user-input" class="flex-1 p-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" placeholder="Pergunte sobre qualquer tema de medicina...">
                <button id="send-btn" class="p-4 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    Enviar
                </button>
            </div>
        </main>
        
        <!-- Sidebar para Anotações -->
        <div id="notes-sidebar" class="w-1/4 bg-white p-6 shadow-md overflow-y-auto hidden md:block">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Minhas Anotações</h2>
            
            <button id="new-note-btn" class="w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700 transition-colors mb-4">
                Nova Anotação
            </button>

            <!-- Formulário para Nova Anotação (inicialmente escondido) -->
            <div id="new-note-form" class="hidden">
                <input type="text" id="note-title-input" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors mb-2" placeholder="Título da anotação">
                <textarea id="note-content-input" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" rows="4" placeholder="Adicione o conteúdo da anotação..."></textarea>
                <button id="save-note-btn" class="mt-2 w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700 transition-colors">Salvar Anotação</button>
                <div class="border-b my-4"></div>
            </div>

            <div id="notes-list" class="space-y-4">
                <!-- Títulos das anotações serão carregados aqui -->
            </div>
        </div>
    </div>

    <!-- Modal para mensagens de aviso -->
    <div id="modal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full">
            <p id="modal-message" class="text-center font-semibold text-gray-800"></p>
            <button id="modal-close-btn" class="mt-4 w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700">OK</button>
        </div>
    </div>
    
    <!-- Firebase SDKs -->
    <script src="https://www.gstatic.com/firebasejs/11.6.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.6.1/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore-compat.js"></script>
    
    <script type="module">
        // Variáveis globais de ambiente (definidas no Canvas)
        const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
        const firebaseConfig = typeof __firebase_config !== 'undefined' ? JSON.parse(__firebase_config) : {};
        const initialAuthToken = typeof __initial_auth_token !== 'undefined' ? __initial_auth_token : null;

        let db, auth, userId;

        // Inicialização do Firebase
        const app = firebase.initializeApp(firebaseConfig);
        db = firebase.firestore(app);
        auth = firebase.auth(app);

        // Referências para os elementos do DOM
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const notesList = document.getElementById('notes-list');
        const newNoteBtn = document.getElementById('new-note-btn');
        const newNoteForm = document.getElementById('new-note-form');
        const noteTitleInput = document.getElementById('note-title-input');
        const noteContentInput = document.getElementById('note-content-input');
        const saveNoteBtn = document.getElementById('save-note-btn');
        const modal = document.getElementById('modal');
        const modalMessage = document.getElementById('modal-message');
        const modalCloseBtn = document.getElementById('modal-close-btn');

        // Função para mostrar o modal de aviso
        function showModal(message) {
            modalMessage.textContent = message;
            modal.classList.remove('hidden');
        }

        // Evento para fechar o modal
        modalCloseBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Simulação de resposta da IA
        function getAiResponse(text) {
            return new Promise(resolve => {
                setTimeout(() => {
                    const response = `Com base na sua pergunta sobre "${text}", aqui está uma explicação detalhada...`;
                    resolve(response);
                }, 1500); // Simula um delay de 1.5s
            });
        }

        // Função para adicionar uma mensagem ao chat
        function addMessage(sender, message) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('p-3', 'rounded-lg', 'shadow-sm', 'max-w-md', 'mb-2');
            
            if (sender === 'user') {
                messageDiv.classList.add('bg-blue-500', 'text-white', 'self-end', 'ml-auto');
            } else {
                messageDiv.classList.add('bg-gray-200', 'text-gray-800', 'self-start');
            }
            
            messageDiv.textContent = message;
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Função para carregar as anotações do Firestore
        async function loadNotes() {
            if (!userId) {
                console.log("Usuário não autenticado, não é possível carregar anotações.");
                return;
            }

            const notesCollectionRef = db.collection(`artifacts/${appId}/users/${userId}/notes`);
            
            notesCollectionRef.onSnapshot(snapshot => {
                notesList.innerHTML = ''; // Limpa a lista para evitar duplicatas
                snapshot.forEach(doc => {
                    const note = doc.data();
                    const noteId = doc.id;
                    const noteDiv = document.createElement('div');
                    noteDiv.classList.add('note-item', 'p-4', 'bg-gray-100', 'rounded-md', 'border', 'border-gray-200', 'text-sm', 'relative', 'cursor-pointer');
                    noteDiv.innerHTML = `
                        <h4 class="font-semibold text-gray-800 truncate">${note.title}</h4>
                        <div class="note-content hidden mt-2 text-gray-700">${note.content}</div>
                        <button class="delete-note-btn absolute top-2 right-2 text-red-500 hover:text-red-700 font-bold" data-id="${noteId}">&times;</button>
                    `;
                    notesList.appendChild(noteDiv);
                });
            }, error => {
                console.error("Erro ao carregar anotações: ", error);
                showModal("Erro ao carregar anotações. Por favor, tente novamente.");
            });
        }

        // Função para salvar uma anotação no Firestore
        async function saveNote(noteTitle, noteContent) {
            if (!userId) {
                showModal("Não é possível salvar a anotação. Por favor, tente novamente mais tarde.");
                return;
            }
            
            const notesCollectionRef = db.collection(`artifacts/${appId}/users/${userId}/notes`);
            
            try {
                await notesCollectionRef.add({
                    title: noteTitle,
                    content: noteContent,
                    timestamp: firebase.firestore.FieldValue.serverTimestamp()
                });
                noteTitleInput.value = '';
                noteContentInput.value = '';
                newNoteForm.classList.add('hidden'); // Esconde o formulário após salvar
            } catch (error) {
                console.error("Erro ao salvar a anotação: ", error);
                showModal("Erro ao salvar a anotação. Por favor, tente novamente.");
            }
        }
        
        // Função para deletar uma anotação do Firestore
        async function deleteNote(noteId) {
            if (!userId) {
                showModal("Não é possível deletar a anotação. Por favor, tente novamente mais tarde.");
                return;
            }

            const noteDocRef = db.doc(`artifacts/${appId}/users/${userId}/notes/${noteId}`);
            try {
                await noteDocRef.delete();
            } catch (error) {
                console.error("Erro ao deletar a anotação: ", error);
                showModal("Erro ao deletar a anotação. Por favor, tente novamente.");
            }
        }
        
        // Evento para envio de mensagem
        sendBtn.addEventListener('click', async () => {
            const userText = userInput.value.trim();
            if (userText === '') return;

            addMessage('user', userText);
            userInput.value = '';
            
            // Adiciona uma mensagem de "digitando..."
            const typingIndicator = document.createElement('div');
            typingIndicator.id = 'typing-indicator';
            typingIndicator.classList.add('p-3', 'text-gray-500', 'italic');
            typingIndicator.textContent = 'IA está digitando...';
            chatBox.appendChild(typingIndicator);
            chatBox.scrollTop = chatBox.scrollHeight;

            const aiResponse = await getAiResponse(userText);
            
            // Remove a mensagem de "digitando..." e adiciona a resposta da IA
            typingIndicator.remove();
            addMessage('ai', aiResponse);
        });

        // Evento para mostrar/esconder o formulário de anotação
        newNoteBtn.addEventListener('click', () => {
            newNoteForm.classList.toggle('hidden');
        });

        // Evento para salvar anotação
        saveNoteBtn.addEventListener('click', () => {
            const noteTitle = noteTitleInput.value.trim();
            const noteContent = noteContentInput.value.trim();

            if (noteTitle === '' || noteContent === '') {
                showModal("Por favor, preencha o título e o conteúdo da anotação.");
                return;
            }
            saveNote(noteTitle, noteContent);
        });
        
        // Evento para deletar e expandir/esconder anotação
        notesList.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-note-btn')) {
                const noteId = e.target.dataset.id;
                deleteNote(noteId);
            } else if (e.target.closest('.note-item')) {
                const noteItem = e.target.closest('.note-item');
                const content = noteItem.querySelector('.note-content');
                content.classList.toggle('hidden');
            }
        });

        // Ativa o envio com a tecla Enter no input de texto
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendBtn.click();
            }
        });

        // Monitora o estado de autenticação para carregar dados
        auth.onAuthStateChanged(async (user) => {
            if (user) {
                userId = user.uid;
                await loadNotes();
            } else {
                try {
                    if (initialAuthToken) {
                        await auth.signInWithCustomToken(initialAuthToken);
                    } else {
                        await auth.signInAnonymously();
                    }
                } catch (error) {
                    console.error("Erro na autenticação: ", error);
                    showModal("Erro na autenticação. Algumas funcionalidades podem não funcionar.");
                }
            }
        });
    </script>
</body>
</html>
