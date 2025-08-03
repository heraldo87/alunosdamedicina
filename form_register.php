<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - MedinFocus</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Aplicando a fonte Inter ao corpo */
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

    <!-- Card de Cadastro -->
    <div class="relative w-full max-w-md mx-4">
        <!-- Formas decorativas de fundo -->
        <div class="absolute top-0 -left-4 w-72 h-72 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-teal-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative bg-white bg-opacity-80 backdrop-blur-md rounded-2xl shadow-2xl p-8 m-4">
            <!-- Cabeçalho -->
            <div class="text-center mb-8">
                <div class="flex justify-center items-center mb-4">
                    <!-- SVG Logo (Estetoscópio) -->
                    <svg class="h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <h1 class="text-3xl font-bold text-gray-800 ml-3">MedinFocus</h1>
                </div>
                <p class="text-gray-600">Crie sua conta para começar a estudar.</p>
            </div>

            <!-- Formulário de Cadastro -->
            <form id="registrationForm" onsubmit="return validateForm(event);" action="./php/cadastro_processar.php" method="POST">
                <!-- Nome Completo -->
                <div class="mb-4">
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input type="text" id="full_name" name="full_name"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300"
                           placeholder="Seu nome completo" required>
                </div>
                
                <!-- Telefone -->
                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="tel" id="phone" name="phone"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300"
                           placeholder="(XX) XXXXX-XXXX" required>
                </div>

                <!-- R.U. (Registro Único) -->
                <div class="mb-4">
                    <label for="ru" class="block text-sm font-medium text-gray-700 mb-1">Registro Único (R.U.)</label>
                    <input type="text" id="ru" name="ru"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300"
                           placeholder="Seu número de registro único" required>
                </div>

                <!-- Turma (Dropdown) -->
                <div class="mb-4">
                    <label for="turma" class="block text-sm font-medium text-gray-700 mb-1">Turma</label>
                    <select id="turma" name="turma" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300" required>
                        <option value="" disabled selected>Selecione sua turma</option>
                        <option value="1º Ano">1º Ano</option>
                        <option value="2º Ano">2º Ano</option>
                        <option value="3º Ano">3º Ano</option>
                        <option value="4º Ano">4º Ano</option>
                        <option value="5º Ano">5º Ano</option>
                        <option value="6º Ano">6º Ano</option>
                    </select>
                </div>

                <!-- Turno (Dropdown) -->
                <div class="mb-4">
                    <label for="turno" class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                    <select id="turno" name="turno" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300" required>
                        <option value="" disabled selected>Selecione seu turno</option>
                        <option value="Manhã">Manhã</option>
                        <option value="Noite">Noite</option>
                    </select>
                </div>

                <!-- Email Input -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300"
                           placeholder="seu.email@exemplo.com" required>
                </div>

                <!-- Senha Input -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" id="password" name="password"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300"
                           placeholder="********" required>
                </div>

                <!-- Confirmar Senha Input -->
                <div class="mb-6">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-300"
                           placeholder="********" required>
                </div>

                <!-- Checkbox de Termos -->
                <div class="flex items-start mb-6">
                    <div class="flex items-center h-5">
                        <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="font-medium text-gray-700">Eu concordo com os <a href="#" class="text-blue-600 hover:text-blue-500">Termos de Uso</a> e a <a href="#" class="text-blue-600 hover:text-blue-500">Política de Privacidade</a>.</label>
                    </div>
                </div>

                <!-- Botão de Cadastro -->
                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">
                        Criar Conta
                    </button>
                </div>
            </form>

            <!-- Link para Login -->
            <p class="mt-8 text-center text-sm text-gray-600">
                Já tem uma conta?
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    Faça login
                </a>
            </p>
        </div>
    </div>

    <script>
        // Função para exibir mensagens de erro personalizadas sem usar alert()
        function showMessage(message, type) {
            const container = document.getElementById('registrationForm').parentNode;
            let messageBox = document.querySelector('.form-message');
            if (!messageBox) {
                messageBox = document.createElement('div');
                messageBox.className = 'form-message mt-4 p-3 rounded-lg text-sm text-center font-medium';
                container.insertBefore(messageBox, container.querySelector('form').nextSibling);
            }
            messageBox.textContent = message;
            if (type === 'error') {
                messageBox.classList.remove('bg-green-100', 'text-green-800');
                messageBox.classList.add('bg-red-100', 'text-red-800');
            } else if (type === 'success') {
                messageBox.classList.remove('bg-red-100', 'text-red-800');
                messageBox.classList.add('bg-green-100', 'text-green-800');
            }
        }
        
        // Função de validação do formulário
        function validateForm(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const termsCheckbox = document.getElementById('terms');
            const phone = document.getElementById('phone').value;
            const ru = document.getElementById('ru').value;
            const turma = document.getElementById('turma').value;
            const turno = document.getElementById('turno').value;

            // Validação de senhas
            if (password !== confirmPassword) {
                showMessage('As senhas não coincidem.', 'error');
                event.preventDefault(); // Impede o envio do formulário
                return false;
            }

            // Validação do checkbox
            if (!termsCheckbox.checked) {
                showMessage('Você deve concordar com os Termos de Uso e a Política de Privacidade.', 'error');
                event.preventDefault();
                return false;
            }
            
            // Validação básica do telefone (pode ser mais robusta)
            const phoneRegex = /^\(?\d{2}\)?[\s-]?\d{4,5}-?\d{4}$/;
            if (!phoneRegex.test(phone)) {
                showMessage('Por favor, insira um número de telefone válido.', 'error');
                event.preventDefault();
                return false;
            }

            // Validação do R.U.
            const ruRegex = /^4\d{4}$/;
            if (!ruRegex.test(ru)) {
                showMessage('Número de R.U. inválido.', 'error');
                event.preventDefault();
                return false;
            }

            // Validação dos dropdowns
            if (turma === "" || turno === "") {
                showMessage('Por favor, selecione sua turma e turno.', 'error');
                event.preventDefault();
                return false;
            }

            // Se todas as validações passarem
            // showMessage('Formulário enviado com sucesso!', 'success');
            // Aqui você pode adicionar o código para enviar o formulário
            // event.preventDefault(); // Comentar esta linha quando o backend for integrado
            return true;
        }
    </script>

</body>
</html>
