<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Interativo - MedinFocus</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .sidebar-overlay-hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .app-card-gradient {
            background-image: linear-gradient(135deg, var(--tw-gradient-stops));
        }
        
        .slide-in {
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .answer-option {
            transition: all 0.2s ease;
        }
        
        .answer-option:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .answer-selected {
            background: linear-gradient(135deg, #3B82F6, #1D4ED8);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .progress-bar {
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen lg:flex-row">
    <!-- Overlay do sidebar mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity duration-300 sidebar-overlay-hidden lg:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl flex flex-col justify-between p-6 transition-transform duration-300 transform -translate-x-full lg:translate-x-0 lg:static lg:flex lg:flex-shrink-0">
        
        <div class="flex justify-end lg:hidden mb-4">
            <button id="closeSidebarBtn" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div>
            <div class="flex items-center mb-10">
                <svg class="h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <span class="text-2xl font-bold text-gray-800 ml-2">MedinFocus</span>
            </div>
            
            <nav class="space-y-2">
                <a href="#" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20z"/><path d="M12 7.75a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0V8.5a.75.75 0 0 0-.75-.75zM12 15a.75.75 0 0 0-.75.75v.5a.75.75 0 0 0 1.5 0v-.5a.75.75 0 0 0-.75-.75z"/></svg>
                    Painel
                </a>
                <a href="#" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM8 20a1 1 0 0 1-1-1v-2h2v2a1 1 0 0 1-1 1zm8 0a1 1 0 0 1-1-1v-2h2v2a1 1 0 0 1-1 1zm-4-8H7v-2h5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1z"/></svg>
                    Flashcards
                </a>
                <a href="#" class="flex items-center px-4 py-3 rounded-xl text-blue-600 bg-blue-50 font-semibold transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4zM4 20v-2h16v2H4zm18-4H2v-6h20v6z"/></svg>
                    Quiz
                </a>
                <a href="#" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    Meu Perfil
                </a>
            </nav>
        </div>
        
        <div class="mt-auto">
            <a href="#" class="flex items-center px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 transition-colors duration-200 font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H9v2h9.17l-2.58 2.58L17 17l5-5-5-5zM4 5h14v2H4V5zm0 12h14v2H4v-2z"/></svg>
                Sair
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-md p-4 flex items-center justify-between">
            <button id="openSidebarBtn" class="text-gray-500 hover:text-gray-700 lg:hidden">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="text-xl font-bold text-gray-800">Quiz Interativo</span>
            <span class="text-gray-600">Olá, Estudante!</span>
        </header>

        <main class="flex-1 p-4 md:p-8 overflow-y-auto bg-gray-50">
            <!-- Tela de Configuração -->
            <div id="config-screen" class="max-w-2xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-8 text-center slide-in">
                    <div class="mb-8">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Configure seu Quiz</h1>
                        <p class="text-gray-600">Escolha suas preferências para começar</p>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Disciplina</label>
                            <select id="subject-select" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Selecione uma disciplina</option>
                                <option value="anatomia">Anatomia Humana</option>
                                <option value="fisiologia">Fisiologia</option>
                                <option value="farmacologia">Farmacologia</option>
                                <option value="patologia">Patologia</option>
                                <option value="clinica">Clínica Médica</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Número de Questões</label>
                            <div class="flex items-center space-x-4">
                                <input type="range" id="questions-slider" min="5" max="20" value="10" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                <span id="questions-count" class="text-2xl font-bold text-blue-600 w-12">10</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Recomendamos 10-15 questões para um bom treino</p>
                        </div>

                        <button id="start-quiz" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-4 px-8 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200">
                            Começar Quiz
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tela do Quiz -->
            <div id="quiz-screen" class="max-w-4xl mx-auto hidden">
                <!-- Progress Bar -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 slide-in">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">Progresso</span>
                        <span id="progress-text" class="text-sm font-medium text-gray-600">1 de 10</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div id="progress-bar" class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full progress-bar" style="width: 10%"></div>
                    </div>
                </div>

                <!-- Question Card -->
                <div id="question-card" class="bg-white rounded-xl shadow-lg p-8 slide-in">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <span id="question-number" class="text-sm font-medium text-blue-600 bg-blue-100 px-3 py-1 rounded-full">Questão 1</span>
                            <span id="timer" class="text-sm font-medium text-gray-500">Tempo restante: 00:30</span>
                        </div>
                        <h2 id="question-text" class="text-xl font-bold text-gray-800 leading-relaxed">
                            Carregando questão...
                        </h2>
                    </div>

                    <div id="answers-container" class="space-y-3 mb-8">
                        <!-- Answers will be inserted here -->
                    </div>

                    <div class="flex justify-between items-center">
                        <button id="prev-question" class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors" disabled>
                            Anterior
                        </button>
                        <div class="flex space-x-3">
                            <button id="skip-question" class="px-4 py-2 text-yellow-600 border border-yellow-300 rounded-lg hover:bg-yellow-50 transition-colors">
                                Pular
                            </button>
                            <button id="next-question" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" disabled>
                                Próxima
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tela de Resultados -->
            <div id="results-screen" class="max-w-3xl mx-auto hidden">
                <div class="bg-white rounded-xl shadow-lg p-8 text-center slide-in">
                    <div class="mb-8">
                        <div id="result-icon" class="w-24 h-24 mx-auto mb-4 rounded-full flex items-center justify-center bg-blue-100">
                            <svg class="w-12 h-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h1 id="result-title" class="text-3xl font-bold text-gray-800 mb-2">Parabéns!</h1>
                        <p id="result-subtitle" class="text-gray-600">Você completou o quiz</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-green-50 p-6 rounded-xl">
                            <div class="text-3xl font-bold text-green-600" id="correct-count">0</div>
                            <div class="text-sm text-green-700">Corretas</div>
                        </div>
                        <div class="bg-red-50 p-6 rounded-xl">
                            <div class="text-3xl font-bold text-red-600" id="incorrect-count">0</div>
                            <div class="text-sm text-red-700">Incorretas</div>
                        </div>
                        <div class="bg-blue-50 p-6 rounded-xl">
                            <div class="text-3xl font-bold text-blue-600" id="final-score">0%</div>
                            <div class="text-sm text-blue-700">Pontuação</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Dados do Quiz
        const quizDatabase = {
            anatomia: [
                { q: "Qual é o maior osso do corpo humano?", a: ["Fíbula", "Fêmur", "Úmero", "Tíbia"], c: 1 },
                { q: "Quantos ossos tem o corpo humano adulto?", a: ["206", "212", "270", "250"], c: 0 },
                { q: "Qual músculo é responsável pela respiração?", a: ["Intercostal", "Diafragma", "Trapézio", "Deltóide"], c: 1 },
                { q: "Em que região se localiza o osso occipital?", a: ["Braço", "Perna", "Crânio", "Coluna"], c: 2 },
                { q: "Quantos pares de costelas existem?", a: ["10", "12", "14", "16"], c: 1 },
                { q: "Qual osso forma a testa?", a: ["Parietal", "Temporal", "Frontal", "Occipital"], c: 2 },
                { q: "Quantas vértebras cervicais existem?", a: ["5", "7", "9", "12"], c: 1 },
                { q: "Qual músculo flexiona o braço?", a: ["Tríceps", "Bíceps", "Deltóide", "Trapézio"], c: 1 },
                { q: "Onde se localiza o osso esfenóide?", a: ["Braço", "Base do crânio", "Perna", "Coluna"], c: 1 },
                { q: "Qual é o menor osso do corpo humano?", a: ["Estribo", "Martelo", "Bigorna", "Falange"], c: 0 },
                { q: "Quantos músculos existem no corpo humano?", a: ["Cerca de 400", "Cerca de 600", "Cerca de 800", "Cerca de 1000"], c: 1 },
                { q: "Qual articulação permite maior mobilidade?", a: ["Ombro", "Quadril", "Joelho", "Cotovelo"], c: 0 },
                { q: "O Atlas é qual vértebra?", a: ["C1", "C2", "C7", "T1"], c: 0 },
                { q: "Quantas falanges tem cada dedo da mão?", a: ["2", "3", "4", "5"], c: 1 },
                { q: "A clavícula articula-se com:", a: ["Úmero", "Escápula e esterno", "Costelas", "Vértebras"], c: 1 },
                { q: "Quantos ossos formam o crânio?", a: ["6", "8", "10", "14"], c: 1 },
                { q: "Qual músculo é conhecido como 'músculo da mastigação'?", a: ["Temporal", "Masseter", "Pterigóideo", "Todos"], c: 3 },
                { q: "O fêmur articula-se superiormente com:", a: ["Tíbia", "Acetábulo", "Ílio", "Sacro"], c: 1 },
                { q: "Quantas vértebras lombares existem?", a: ["3", "5", "7", "12"], c: 1 },
                { q: "Qual é o maior músculo do corpo humano?", a: ["Bíceps", "Quadríceps", "Glúteo máximo", "Trapézio"], c: 2 }
            ],
            fisiologia: [
                { q: "Qual órgão produz insulina?", a: ["Fígado", "Pâncreas", "Rim", "Baço"], c: 1 },
                { q: "Qual a função principal dos glóbulos vermelhos?", a: ["Defesa", "Coagulação", "Transporte de O2", "Digestão"], c: 2 },
                { q: "Em que fase do ciclo cardíaco o coração se contrai?", a: ["Diástole", "Sístole", "Pausa", "Relaxamento"], c: 1 },
                { q: "Qual hormônio regula o açúcar no sangue?", a: ["Adrenalina", "Insulina", "Cortisol", "Tiroxina"], c: 1 },
                { q: "Onde ocorre a troca gasosa nos pulmões?", a: ["Bronquíolos", "Alvéolos", "Brônquios", "Traqueia"], c: 1 },
                { q: "Qual a pressão arterial normal?", a: ["100/60", "120/80", "140/90", "160/100"], c: 1 },
                { q: "Qual órgão filtra o sangue?", a: ["Fígado", "Pulmão", "Rim", "Coração"], c: 2 },
                { q: "O que significa taquicardia?", a: ["Batimento lento", "Batimento rápido", "Batimento irregular", "Parada cardíaca"], c: 1 },
                { q: "Onde são produzidos os glóbulos vermelhos?", a: ["Fígado", "Baço", "Medula óssea", "Rim"], c: 2 },
                { q: "Qual a frequência cardíaca normal em repouso?", a: ["40-60 bpm", "60-100 bpm", "100-120 bpm", "120-150 bpm"], c: 1 },
                { q: "Qual o pH normal do sangue?", a: ["6,8-7,0", "7,35-7,45", "7,8-8,0", "8,2-8,4"], c: 1 },
                { q: "A hemoglobina transporta principalmente:", a: ["CO2", "O2", "Glicose", "Água"], c: 1 },
                { q: "Qual hormônio estimula a contração uterina?", a: ["Progesterona", "Estrogênio", "Ocitocina", "Prolactina"], c: 2 },
                { q: "Qual enzima digere proteínas no estômago?", a: ["Amilase", "Pepsina", "Lipase", "Tripsina"], c: 1 },
                { q: "Onde ocorre a fertilização normalmente?", a: ["Ovário", "Útero", "Tuba uterina", "Vagina"], c: 2 },
                { q: "Qual célula produz anticorpos?", a: ["Linfócito T", "Linfócito B", "Neutrófilo", "Macrófago"], c: 1 },
                { q: "O que é homeostase?", a: ["Doença", "Equilíbrio interno", "Crescimento", "Reprodução"], c: 1 },
                { q: "Qual hormônio é produzido pela tireoide?", a: ["Insulina", "Tiroxina", "Cortisol", "Adrenalina"], c: 1 },
                { q: "A digestão das gorduras ocorre principalmente onde?", a: ["Estômago", "Intestino delgado", "Intestino grosso", "Fígado"], c: 1 },
                { q: "Qual é a função dos rins?", a: ["Digestão", "Respiração", "Filtração", "Circulação"], c: 2 }
            ],
            farmacologia: [
                { q: "O que é um antagonista?", a: ["Ativa receptor", "Bloqueia receptor", "Destrói receptor", "Cria receptor"], c: 1 },
                { q: "Qual via de administração é mais rápida?", a: ["Oral", "Intramuscular", "Intravenosa", "Subcutânea"], c: 2 },
                { q: "Paracetamol é um:", a: ["Antibiótico", "Analgésico", "Anti-inflamatório", "Antiviral"], c: 1 },
                { q: "Qual órgão metaboliza a maioria dos medicamentos?", a: ["Rim", "Fígado", "Pulmão", "Intestino"], c: 1 },
                { q: "O que é meia-vida de um fármaco?", a: ["Tempo para agir", "Tempo para sair 50%", "Tempo máximo", "Tempo mínimo"], c: 1 },
                { q: "Aspirina pertence à classe:", a: ["AINES", "Antibióticos", "Antifúngicos", "Antivirais"], c: 0 },
                { q: "Qual é o antídoto para warfarina?", a: ["Vitamina C", "Vitamina K", "Vitamina D", "Vitamina E"], c: 1 },
                { q: "Omeprazol é usado para:", a: ["Dor", "Ácido estomacal", "Infecção", "Alergia"], c: 1 },
                { q: "Morfina é um:", a: ["Estimulante", "Depressor", "Analgésico opióide", "Anti-inflamatório"], c: 2 },
                { q: "Beta-bloqueadores agem em:", a: ["Receptores alfa", "Receptores beta", "Canais de cálcio", "Canais de sódio"], c: 1 },
                { q: "Digoxina é usada para:", a: ["Hipertensão", "Insuficiência cardíaca", "Diabetes", "Epilepsia"], c: 1 },
                { q: "Qual droga é antídoto para morfina?", a: ["Naloxona", "Atropina", "Adrenalina", "Dopamina"], c: 0 },
                { q: "Captopril é um:", a: ["Diurético", "Betabloqueador", "IECA", "Bloqueador de canal"], c: 2 },
                { q: "Furosemida é um:", a: ["IECA", "Diurético", "Betabloqueador", "Vasodilatador"], c: 1 },
                { q: "Varfarina interfere na:", a: ["Agregação plaquetária", "Coagulação", "Fibrinólise", "Eritropoiese"], c: 1 },
                { q: "Qual droga trata convulsões?", a: ["Diazepam", "Propranolol", "Digoxina", "Captopril"], c: 0 },
                { q: "Nitroglicerina é usada para:", a: ["Hipertensão", "Angina", "Arritmia", "Insuficiência cardíaca"], c: 1 },
                { q: "O que significa biodisponibilidade?", a: ["Toxicidade do fármaco", "Fração absorvida", "Tempo de ação", "Local de ação"], c: 1 },
                { q: "Penicilina é um:", a: ["AINE", "Antibiótico", "Antifúngico", "Antiviral"], c: 1 },
                { q: "Qual é a via mais lenta de absorção?", a: ["Intravenosa", "Intramuscular", "Subcutânea", "Oral"], c: 3 }
            ],
            patologia: [
                { q: "O que caracteriza inflamação aguda?", a: ["Fibrose", "Exsudato", "Necrose", "Calcificação"], c: 1 },
                { q: "Qual célula predomina na inflamação crônica?", a: ["Neutrófilo", "Linfócito", "Eosinófilo", "Basófilo"], c: 1 },
                { q: "O que é metaplasia?", a: ["Morte celular", "Mudança de tipo celular", "Diminuição de tamanho", "Aumento de número"], c: 1 },
                { q: "Trombose é:", a: ["Coágulo em vaso", "Ruptura de vaso", "Dilatação de vaso", "Inflamação de vaso"], c: 0 },
                { q: "Aterosclerose afeta principalmente:", a: ["Veias", "Artérias", "Capilares", "Linfáticos"], c: 1 },
                { q: "Edema é causado por:", a: ["Aumento de pressão", "Diminuição de proteínas", "Aumento de permeabilidade", "Todas as anteriores"], c: 3 },
                { q: "Hiperplasia é:", a: ["Aumento de tamanho", "Aumento de número", "Diminuição de tamanho", "Mudança de tipo"], c: 1 },
                { q: "Isquemia resulta em:", a: ["Excesso de O2", "Falta de O2", "Excesso de CO2", "Falta de CO2"], c: 1 },
                { q: "Apoptose é:", a: ["Morte acidental", "Morte programada", "Morte por trauma", "Morte por isquemia"], c: 1 },
                { q: "Hipertrofia é:", a: ["Aumento de número", "Aumento de tamanho", "Diminuição de tamanho", "Mudança de função"], c: 1 },
                { q: "Necrose coagulativa ocorre em:", a: ["Cérebro", "Coração", "Pâncreas", "Intestino"], c: 1 },
                { q: "Qual mediador causa vasodilatação?", a: ["Histamina", "Prostaglandinas", "Óxido nítrico", "Todos os anteriores"], c: 3 },
                { q: "Embolia é:", a: ["Coágulo fixo", "Coágulo móvel", "Ruptura vascular", "Inflamação"], c: 1 },
                { q: "Granuloma é característico de:", a: ["Inflamação aguda", "Inflamação crônica", "Necrose", "Fibrose"], c: 1 },
                { q: "Anasarca é:", a: ["Edema localizado", "Edema generalizado", "Ascite", "Derrame pleural"], c: 1 },
                { q: "Qual tipo de necrose afeta gordura?", a: ["Coagulativa", "Liquefativa", "Caseosa", "Gordurosa"], c: 3 },
                { q: "Cicatrização de primeira intenção ocorre em:", a: ["Feridas limpas", "Feridas infectadas", "Feridas grandes", "Feridas crônicas"], c: 0 },
                { q: "Choque distributivo inclui:", a: ["Cardiogênico", "Hipovolêmico", "Séptico", "Obstrutivo"], c: 2 },
                { q: "Regeneração completa ocorre em:", a: ["Fígado", "Coração", "Cérebro", "Rim"], c: 0 },
                { q: "Os sinais cardinais da inflamação são:", a: ["3", "4", "5", "6"], c: 2 }
            ],
            clinica: [
                { q: "Hipertensão é diagnosticada com pressão:", a: ["≥120/80", "≥130/85", "≥140/90", "≥160/100"], c: 2 },
                { q: "Diabetes tipo 1 é caracterizada por:", a: ["Resistência à insulina", "Deficiência de insulina", "Excesso de insulina", "Hipoglicemia"], c: 1 },
                { q: "Angina é dor causada por:", a: ["Infarto", "Isquemia", "Embolia", "Trombose"], c: 1 },
                { q: "Qual exame confirma infarto?", a: ["ECG", "Troponina", "CK-MB", "Todos"], c: 3 },
                { q: "DPOC inclui:", a: ["Asma", "Enfisema", "Bronquite crônica", "B e C"], c: 3 },
                { q: "Pneumonia típica é causada por:", a: ["Vírus", "Bactéria", "Fungo", "Parasita"], c: 1 },
                { q: "Cirrose é caracterizada por:", a: ["Fibrose hepática", "Insuficiência hepática", "Hipertensão portal", "Todas"], c: 3 },
                { q: "IRC é definida por TFG:", a: ["<90", "<60", "<30", "<15"], c: 1 },
                { q: "Anemia ferropriva tem:", a: ["VCM baixo", "VCM alto", "VCM normal", "Variável"], c: 0 },
                { q: "Hipotireoidismo causa:", a: ["Taquicardia", "Bradicardia", "Hipertermia", "Insônia"], c: 1 },
                { q: "AVC isquêmico representa:", a: ["70%", "80%", "85%", "90%"], c: 2 },
                { q: "Epilepsia é diagnosticada por:", a: ["TC", "RM", "EEG", "Todas"], c: 2 },
                { q: "Artrite reumatoide afeta:", a: ["Articulações", "Pele", "Pulmões", "Todas"], c: 3 },
                { q: "Osteoporose é diagnosticada por:", a: ["Raio-X", "Densitometria", "TC", "RM"], c: 1 },
                { q: "Úlcera péptica é causada por:", a: ["H. pylori", "AINES", "Estresse", "Todas"], c: 3 },
                { q: "Glaucoma afeta:", a: ["Córnea", "Cristalino", "Nervo óptico", "Retina"], c: 2 },
                { q: "Catarata é:", a: ["Opacificação do cristalino", "Aumento da pressão", "Lesão da retina", "Inflamação"], c: 0 },
                { q: "Síndrome metabólica inclui:", a: ["Obesidade", "Hipertensão", "Diabetes", "Todas"], c: 3 },
                { q: "Fibrilação atrial causa:", a: ["Bradicardia", "Taquicardia", "Ritmo irregular", "Parada cardíaca"], c: 2 },
                { q: "Insuficiência cardíaca causa:", a: ["Dispneia", "Edema", "Fadiga", "Todas"], c: 3 }
            ]
        };

        // Estado do Quiz
        let quiz = {
            currentSubject: '',
            questions: [],
            currentIndex: 0,
            userAnswers: [],
            startTime: null,
            timer: null,
            timeLeft: 30
        };

        // Elementos DOM
        let elements = {};

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            initElements();
            setupEvents();
            updateQuestionCount();
        });

        function initElements() {
            elements = {
                configScreen: document.getElementById('config-screen'),
                quizScreen: document.getElementById('quiz-screen'),
                resultsScreen: document.getElementById('results-screen'),
                subjectSelect: document.getElementById('subject-select'),
                questionsSlider: document.getElementById('questions-slider'),
                questionsCount: document.getElementById('questions-count'),
                startBtn: document.getElementById('start-quiz'),
                progressBar: document.getElementById('progress-bar'),
                progressText: document.getElementById('progress-text'),
                questionNumber: document.getElementById('question-number'),
                questionText: document.getElementById('question-text'),
                answersContainer: document.getElementById('answers-container'),
                prevBtn: document.getElementById('prev-question'),
                nextBtn: document.getElementById('next-question'),
                skipBtn: document.getElementById('skip-question'),
                timer: document.getElementById('timer'),
                correctCount: document.getElementById('correct-count'),
                incorrectCount: document.getElementById('incorrect-count'),
                finalScore: document.getElementById('final-score'),
                resultIcon: document.getElementById('result-icon'),
                resultTitle: document.getElementById('result-title'),
                resultSubtitle: document.getElementById('result-subtitle'),
                restartBtn: document.getElementById('restart-quiz'),
                reviewBtn: document.getElementById('review-answers'),
                openSidebarBtn: document.getElementById('openSidebarBtn'),
                closeSidebarBtn: document.getElementById('closeSidebarBtn'),
                sidebar: document.getElementById('sidebar'),
                overlay: document.getElementById('sidebar-overlay')
            };
        }

        function setupEvents() {
            // Config
            if (elements.questionsSlider) {
                elements.questionsSlider.addEventListener('input', updateQuestionCount);
            }
            if (elements.startBtn) {
                elements.startBtn.addEventListener('click', startQuiz);
            }

            // Quiz navigation
            if (elements.prevBtn) elements.prevBtn.addEventListener('click', prevQuestion);
            if (elements.nextBtn) elements.nextBtn.addEventListener('click', nextQuestion);
            if (elements.skipBtn) elements.skipBtn.addEventListener('click', skipQuestion);

            // Results
            if (elements.restartBtn) elements.restartBtn.addEventListener('click', restartQuiz);
            if (elements.reviewBtn) elements.reviewBtn.addEventListener('click', () => 
                alert('Funcionalidade de revisão em desenvolvimento!'));

            // Sidebar
            if (elements.openSidebarBtn) {
                elements.openSidebarBtn.addEventListener('click', () => {
                    elements.sidebar.classList.remove('-translate-x-full');
                    elements.overlay.classList.remove('sidebar-overlay-hidden');
                });
            }

            if (elements.closeSidebarBtn) {
                elements.closeSidebarBtn.addEventListener('click', () => {
                    elements.sidebar.classList.add('-translate-x-full');
                    elements.overlay.classList.add('sidebar-overlay-hidden');
                });
            }

            if (elements.overlay) {
                elements.overlay.addEventListener('click', () => {
                    elements.sidebar.classList.add('-translate-x-full');
                    elements.overlay.classList.add('sidebar-overlay-hidden');
                });
            }
        }

        function updateQuestionCount() {
            if (elements.questionsCount && elements.questionsSlider) {
                elements.questionsCount.textContent = elements.questionsSlider.value;
            }
        }

        function startQuiz() {
            const subject = elements.subjectSelect?.value;
            const numQuestions = parseInt(elements.questionsSlider?.value || 10);

            if (!subject) {
                alert('Por favor, selecione uma disciplina!');
                return;
            }

            if (!quizDatabase[subject]) {
                alert('Disciplina não encontrada!');
                return;
            }

            // Setup
            quiz.currentSubject = subject;
            quiz.questions = shuffleArray(quizDatabase[subject]).slice(0, numQuestions);
            quiz.currentIndex = 0;
            quiz.userAnswers = new Array(numQuestions).fill(null);
            quiz.startTime = Date.now();

            // Show quiz
            showScreen('quiz');
            showQuestion();
        }

        function showScreen(screen) {
            elements.configScreen?.classList.add('hidden');
            elements.quizScreen?.classList.add('hidden');
            elements.resultsScreen?.classList.add('hidden');

            if (screen === 'config') elements.configScreen?.classList.remove('hidden');
            else if (screen === 'quiz') elements.quizScreen?.classList.remove('hidden');
            else if (screen === 'results') elements.resultsScreen?.classList.remove('hidden');
        }

        function showQuestion() {
            if (!quiz.questions.length) return;

            const question = quiz.questions[quiz.currentIndex];
            const num = quiz.currentIndex + 1;
            const total = quiz.questions.length;

            // Update progress
            const progress = (num / total) * 100;
            if (elements.progressBar) elements.progressBar.style.width = progress + '%';
            if (elements.progressText) elements.progressText.textContent = `${num} de ${total}`;

            // Update question
            if (elements.questionNumber) elements.questionNumber.textContent = `Questão ${num}`;
            if (elements.questionText) elements.questionText.textContent = question.q;

            // Create answers
            if (elements.answersContainer) {
                elements.answersContainer.innerHTML = '';
                question.a.forEach((answer, index) => {
                    const div = document.createElement('div');
                    div.className = 'answer-option p-4 border-2 border-gray-200 rounded-lg cursor-pointer';
                    div.innerHTML = `
                        <div class="flex items-center space-x-3">
                            <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center">
                                <div class="w-3 h-3 rounded-full bg-transparent"></div>
                            </div>
                            <span class="font-medium">${answer}</span>
                        </div>
                    `;
                    div.addEventListener('click', () => selectAnswer(index));
                    elements.answersContainer.appendChild(div);
                });
            }

            // Update buttons
            if (elements.prevBtn) elements.prevBtn.disabled = quiz.currentIndex === 0;
            if (elements.nextBtn) elements.nextBtn.disabled = quiz.userAnswers[quiz.currentIndex] === null;

            // Select previous answer
            if (quiz.userAnswers[quiz.currentIndex] !== null && quiz.userAnswers[quiz.currentIndex] !== -1) {
                selectAnswer(quiz.userAnswers[quiz.currentIndex]);
            }

            startTimer();
        }

        function selectAnswer(index) {
            // Clear selection
            document.querySelectorAll('.answer-option').forEach(opt => {
                opt.classList.remove('answer-selected');
                const circle = opt.querySelector('.w-3');
                if (circle) {
                    circle.classList.remove('bg-white');
                    circle.classList.add('bg-transparent');
                }
            });

            // Set selection
            const selected = elements.answersContainer?.children[index];
            if (selected) {
                selected.classList.add('answer-selected');
                const circle = selected.querySelector('.w-3');
                if (circle) {
                    circle.classList.remove('bg-transparent');
                    circle.classList.add('bg-white');
                }
            }

            quiz.userAnswers[quiz.currentIndex] = index;
            if (elements.nextBtn) elements.nextBtn.disabled = false;
        }

        function startTimer() {
            if (quiz.timer) clearInterval(quiz.timer);
            quiz.timeLeft = 30;

            quiz.timer = setInterval(() => {
                const minutes = Math.floor(quiz.timeLeft / 60);
                const seconds = quiz.timeLeft % 60;
                if (elements.timer) {
                    elements.timer.textContent = `Tempo restante: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }

                if (quiz.timeLeft <= 0) {
                    clearInterval(quiz.timer);
                    skipQuestion();
                    return;
                }
                quiz.timeLeft--;
            }, 1000);
        }

        function nextQuestion() {
            if (quiz.timer) clearInterval(quiz.timer);
            
            if (quiz.currentIndex < quiz.questions.length - 1) {
                quiz.currentIndex++;
                showQuestion();
            } else {
                finishQuiz();
            }
        }

        function prevQuestion() {
            if (quiz.timer) clearInterval(quiz.timer);
            
            if (quiz.currentIndex > 0) {
                quiz.currentIndex--;
                showQuestion();
            }
        }

        function skipQuestion() {
            quiz.userAnswers[quiz.currentIndex] = -1;
            nextQuestion();
        }

        function finishQuiz() {
            if (quiz.timer) clearInterval(quiz.timer);

            // Calculate results
            let correct = 0, incorrect = 0;
            
            quiz.userAnswers.forEach((answer, index) => {
                if (answer === quiz.questions[index].c) correct++;
                else if (answer !== -1) incorrect++;
            });

            const percentage = Math.round((correct / quiz.questions.length) * 100);

            // Update display
            if (elements.correctCount) elements.correctCount.textContent = correct;
            if (elements.incorrectCount) elements.incorrectCount.textContent = incorrect;
            if (elements.finalScore) elements.finalScore.textContent = percentage + '%';

            // Update result message
            if (percentage >= 80) {
                if (elements.resultTitle) elements.resultTitle.textContent = 'Excelente!';
                if (elements.resultSubtitle) elements.resultSubtitle.textContent = 'Você domina bem este conteúdo.';
            } else if (percentage >= 60) {
                if (elements.resultTitle) elements.resultTitle.textContent = 'Bom trabalho!';
                if (elements.resultSubtitle) elements.resultSubtitle.textContent = 'Continue estudando para melhorar.';
            } else {
                if (elements.resultTitle) elements.resultTitle.textContent = 'Continue estudando!';
                if (elements.resultSubtitle) elements.resultSubtitle.textContent = 'A prática leva à perfeição.';
            }

            showScreen('results');
        }

        function restartQuiz() {
            if (quiz.timer) clearInterval(quiz.timer);
            
            quiz = {
                currentSubject: '',
                questions: [],
                currentIndex: 0,
                userAnswers: [],
                startTime: null,
                timer: null,
                timeLeft: 30
            };

            showScreen('config');
        }

        function shuffleArray(array) {
            const shuffled = [...array];
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
            return shuffled;
        }
    </script>
</body>
</html>