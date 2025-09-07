<?php
// 1. Verificação de autenticação (SEMPRE PRIMEIRO)
include_once __DIR__ . '/php/auth_check.php';

// 2. Define o título da página
$pageTitle = "Painel do Aluno - MedinFocus";

// 2.1 Nome seguro para o "Bem-vindo"
$nome = $_SESSION['full_name']
    ?? $_SESSION['user_name']   // fallback p/ telas antigas
    ?? $_SESSION['email']
    ?? 'Aluno';
$nome = is_string($nome) ? trim($nome) : 'Aluno';
$safeNome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');

// 3. Inclui o cabeçalho HTML (abre <html> e <body>)
include_once __DIR__ . '/includes/header.php';

// 4. Inclui a barra lateral de navegação
include_once __DIR__ . '/includes/sidebar_nav.php';
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
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                Bem-vindo, <?= $safeNome ?>!
            </h1>
            <p class="text-gray-600">Explore as ferramentas para turbinar seus estudos.</p>
            <?php
              // Debug seguro em comentário HTML (opcional)
              $lvl = $_SESSION['access_level'] ?? ($_SESSION['nivel_acesso'] ?? null);
              $apv = $_SESSION['aprovacao'] ?? null;
              echo "<!-- Debug: access_level=" . var_export($lvl, true) . " aprovacao=" . var_export($apv, true) . " -->";
            ?>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Aplicativos MedinFocus</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4 md:gap-6 md:mt-8">
            
            <a href="uploads.php" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-blue-600 to-teal-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                    <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Repositório</h3>
                <p class="text-sm font-light">Acesse nosso repositório de materiais.</p>
            </a>

            <a href="chat_ia.php" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-purple-600 to-pink-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Tire suas dúvidas com IA</h3>
                <p class="text-sm font-light">Converse com nossa IA para tirar dúvidas.</p>
            </a>

            <a href="quizzes.php" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-green-600 to-cyan-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
                <div class="p-3 mb-4 inline-flex">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zM10 4h4v2h-4V4zM4 20v-2h16v2H4zm18-4H2v-6h20v6z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-1">Quiz</h3>
                <p class="text-sm font-light">Teste seus conhecimentos com um quiz interativo.</p>
            </a>

            <a href="forum.php" class="block transform transition-transform duration-300 hover:scale-105 app-card-gradient from-amber-500 to-orange-500 p-6 rounded-3xl shadow-xl text-white flex flex-col items-start justify-center">
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
    </main>
</div> <!-- FECHA o .flex-1 flex flex-col -->

<?php
// 5. Inclui o rodapé (fecha </body></html>)
include_once __DIR__ . '/includes/footer.php';