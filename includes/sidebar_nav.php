<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl flex flex-col justify-between p-6 transition-transform duration-300 transform -translate-x-full lg:translate-x-0 lg:static lg:flex lg:flex-shrink-0">

    <div class="flex justify-end lg:hidden mb-4">
        <button id="closeSidebarBtn" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div>
        <!-- Logo -->
        <div class="flex items-center mb-10">
            <svg class="h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
            <span class="text-2xl font-bold text-gray-800 ml-2">MedinFocus</span>
        </div>
        
        <nav class="space-y-2">
            <!-- Painel -->
            <a href="index.php" class="flex items-center px-4 py-3 rounded-xl text-blue-600 bg-blue-50 font-semibold transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20z"/>
                    <path d="M12 7.75a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0V8.5a.75.75 0 0 0-.75-.75zM12 15a.75.75 0 0 0-.75.75v.5a.75.75 0 0 0 1.5 0v-.5a.75.75 0 0 0-.75-.75z"/>
                </svg>
                Painel
            </a>

            <!-- Meu Perfil -->
            <a href="meu_perfil.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                Meu Perfil
            </a>

            <!-- Botões restritos -->
            <?php
            $nivel = intval($_SESSION['access_level'] ?? 0);
            if ($nivel === 2 || $nivel === 3) {
                echo '
                <a href="aprovacoes.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" clip-rule="evenodd" />
                    </svg>
                    Aprovações
                </a>

                <a href="noticias_criar.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5a2 2 0 0 0-2 2v14l4-4h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/>
                    </svg>
                    Notícias
                </a>

                <a href="uploads.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 16l4-5h-3V4h-2v7H8z"/>
                        <path d="M20 18H4v2h16v-2z"/>
                    </svg>
                    Uploads
                </a>
                ';
            }
            ?>
        </nav>
    </div>
    
    <!-- Botão sair -->
    <div class="mt-auto">
        <a href="logout.php" class="flex items-center px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 transition-colors duration-200 font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17 7l-1.41 1.41L18.17 11H9v2h9.17l-2.58 2.58L17 17l5-5-5-5zM4 5h14v2H4V5zm0 12h14v2H4v-2z"/>
            </svg>
            Sair
        </a>
    </div>
</aside>
