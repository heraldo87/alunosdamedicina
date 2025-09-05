<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Realizado com Sucesso!</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Animação de fundo para os blobs */
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <!-- Card de Sucesso -->
    <div class="relative w-full max-w-md mx-4">
        <!-- Formas decorativas de fundo -->
        <div class="absolute top-0 -left-4 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-teal-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative bg-white bg-opacity-80 backdrop-blur-md rounded-2xl shadow-2xl p-8 m-4 text-center">
            <!-- Icone de sucesso (check) -->
            <div class="flex justify-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            
            <!-- Mensagem de sucesso -->
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Cadastro Realizado com Sucesso!</h1>
            <p class="text-gray-600 mb-6">Sua conta foi criada. Você será redirecionado para a página de login em breve.</p>
            
            <!-- Link para redirecionamento manual -->
            <p class="text-sm text-gray-500">Se não for redirecionado automaticamente, <a href="login.php" class="text-blue-600 hover:underline">clique aqui</a>.</p>
        </div>
    </div>

    <script>
        // Redireciona o usuário para a página de login após 5 segundos
        setTimeout(function() {
            window.location.assign("login.php");
        }, 5000); // 5000 milissegundos = 5 segundos
    </script>

</body>
</html>
