<?php
// 1. Primeiramente, faz a verificação de autenticação. SEMPRE DEVE SER O PRIMEIRO CÓDIGO PHP.
include_once 'php/auth_check.php';

// 2. Define o título da página
$pageTitle = "Painel - MedinFocus";

// 3. Inclui o cabeçalho HTML
include_once 'includes/header.php';
?>

<!-- Abre a tag <body> e define o layout principal -->
<body class="bg-gray-100 flex h-screen overflow-hidden">
<?php
// 4. Inclui a barra lateral de navegação
include_once 'includes/sidebar_nav.php';
?>

    <!-- Conteúdo Principal do Painel -->
    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <!-- Botão de Notificações ou Ajuda -->
            <button class="bg-white p-3 rounded-full shadow-lg hover:shadow-xl transition-shadow duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 22a2.02 2.02 0 0 0 2-2h-4a2.02 2.02 0 0 0 2 2zM18 16v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.63 5.36 6 7.93 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
            </button>
        </header>

        <!-- Seção de Métricas e Progresso -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card de Progresso Geral -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-600">
                <p class="text-sm font-medium text-gray-500">Progresso Total</p>
                <div class="flex items-center mt-2">
                    <h2 class="text-3xl font-bold text-gray-800">75%</h2>
                    <div class="ml-4 w-full h-2 bg-gray-200 rounded-full">
                        <div class="h-2 bg-blue-600 rounded-full" style="width: 75%;"></div>
                    </div>
                </div>
            </div>
            <!-- Card de Flashcards Concluídos -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-teal-500">
                <p class="text-sm font-medium text-gray-500">Flashcards Concluídos</p>
                <h2 class="text-3xl font-bold text-gray-800 mt-2">120</h2>
            </div>
            <!-- Card de Simulados Realizados -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-orange-400">
                <p class="text-sm font-medium text-gray-500">Simulados Realizados</p>
                <h2 class="text-3xl font-bold text-gray-800 mt-2">15</h2>
            </div>
            <!-- Card de Próximo Tópico Sugerido -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-purple-500">
                <p class="text-sm font-medium text-gray-500">Próximo Tópico</p>
                <h2 class="text-xl font-bold text-gray-800 mt-2">Fisiologia Cardíaca</h2>
            </div>
        </section>

        <!-- Seção de Ações Rápidas -->
        <section class="mb-8">
            <h2 class="text-2xl font-bold mb-4">Ações Rápidas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card para iniciar Flashcards -->
                <a href="#" class="block bg-blue-600 p-6 rounded-xl text-white shadow-lg hover:bg-blue-700 transition-colors duration-200 transform hover:scale-105">
                    <h3 class="text-lg font-semibold mb-2">Revisar Flashcards</h3>
                    <p class="text-sm opacity-80">Comece uma sessão de revisão rápida com seus flashcards.</p>
                </a>
                <!-- Card para iniciar Simulados -->
                <a href="#" class="block bg-teal-500 p-6 rounded-xl text-white shadow-lg hover:bg-teal-600 transition-colors duration-200 transform hover:scale-105">
                    <h3 class="text-lg font-semibold mb-2">Fazer um Simulado</h3>
                    <p class="text-sm opacity-80">Teste seus conhecimentos com um novo simulado.</p>
                </a>
                <!-- Card para utilizar a IA -->
                <a href="#" class="block bg-indigo-500 p-6 rounded-xl text-white shadow-lg hover:bg-indigo-600 transition-colors duration-200 transform hover:scale-105">
                    <h3 class="text-lg font-semibold mb-2">Estudar com a IA</h3>
                    <p class="text-sm opacity-80">Receba um plano de estudos personalizado agora.</p>
                </a>
            </div>
        </section>
        
        <!-- Seção de Atividade Recente -->
        <section>
            <h2 class="text-2xl font-bold mb-4">Atividade Recente</h2>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <div class="h-2 w-2 rounded-full bg-blue-600 mt-2 mr-4 flex-shrink-0"></div>
                        <p class="text-gray-700"><span class="font-semibold">20 de Julho:</span> Você concluiu a revisão de Flashcards sobre Fisiologia Renal.</p>
                    </li>
                    <li class="flex items-start">
                        <div class="h-2 w-2 rounded-full bg-teal-500 mt-2 mr-4 flex-shrink-0"></div>
                        <p class="text-gray-700"><span class="font-semibold">19 de Julho:</span> Você realizou o Simulado de Cardiologia e obteve 85% de acertos.</p>
                    </li>
                    <li class="flex items-start">
                        <div class="h-2 w-2 rounded-full bg-indigo-500 mt-2 mr-4 flex-shrink-0"></div>
                        <p class="text-gray-700"><span class="font-semibold">18 de Julho:</span> A IA sugeriu um novo plano de estudos para Anatomia Humana.</p>
                    </li>
                </ul>
            </div>
        </section>
    </main>
