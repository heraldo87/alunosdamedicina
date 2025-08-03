<?php
// 1. Verificação de autenticação (SEMPRE PRIMEIRO)
include_once 'php/auth_check.php';

// 2. Define o título da página
$pageTitle = "Painel do Aluno - MedinFocus";

// 3. Inclui o cabeçalho HTML
include_once 'includes/header.php';

// 4. Inclui a barra lateral de navegação
include_once 'includes/sidebar_nav.php';
?>

<div class="flex-1 flex flex-col">
    <header class="bg-white shadow-md p-4 flex items-center justify-between">
        <button id="openSidebarBtn" class="text-gray-500 hover:text-gray-700 lg:hidden">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="text-xl font-bold text-gray-800">Painel do Aluno</span>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto">
        <div class="p-6 bg-white rounded-xl shadow-lg mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="text-gray-600">Explore as ferramentas para turbinar seus estudos.</p>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Aplicativos MedinFocus</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4 md:gap-6 md:mt-8">
            
            <a href="#" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-blue-600 to-teal-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                    <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Repositório</h3>
                <p class="text-sm font-light">Acesse nosso repositório de materiais.</p>
            </a>

            <a href="#" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-purple-600 to-pink-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Tire suas dúvidas com IA</h3>
                <p class="text-sm font-light">Converse com nossa IA para tirar dúvidas.</p>
            </a>

            <a href="#" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-green-600 to-cyan-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4zM4 20v-2h16v2H4zm18-4H2v-6h20v6z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Quiz</h3>
                <p class="text-sm font-light">Teste seus conhecimentos com um quiz interativo.</p>
            </a>

            <a href="#" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-amber-500 to-orange-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                    <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Fóruns</h3>
                <p class="text-sm font-light">Participe das discussões e tire dúvidas.</p>
            </a>

            <a href="#" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-red-500 to-rose-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                     <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962a3.75 3.75 0 015.25 0m-5.25 0a3.75 3.75 0 00-5.25 0M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Listas e Grupos</h3>
                <p class="text-sm font-light">Crie e participe de grupos de estudo.</p>
            </a>
        </div>

        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Lembretes e Avisos</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-md">
                    <div class="bg-blue-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                        <h3 class="text-lg font-bold">Lembretes</h3>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <ul class="p-4 space-y-3">
                        <li class="flex items-start">
                            <span class="icon-card bg-blue-500 text-white rounded-full flex items-center justify-center p-2 mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M12 21V9" /></svg></span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Simulado de Anatomia</p>
                                <p class="text-xs text-gray-500">25/08 às 14:00 - Fique preparado!</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white rounded-xl shadow-md">
                     <div class="bg-purple-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                        <h3 class="text-lg font-bold">Notícias e Avisos</h3>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <ul class="p-4 space-y-3">
                        <li class="flex items-start">
                             <span class="icon-card bg-purple-500 text-white rounded-full flex items-center justify-center p-2 mr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Novos Flashcards!</p>
                                <p class="text-xs text-gray-500">Módulo de Farmacologia disponível.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

<?php
// 5. Inclui o rodapé
include_once 'includes/footer.php';
?>