<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento de Atividades</title>
    <!-- Inclui o Tailwind CSS para um design responsivo e moderno -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Define a fonte "Inter" para um visual limpo e profissional -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <!-- Container principal do formulário com estilo moderno -->
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl">
        
        <div class="bg-white p-8 rounded-lg w-full max-w-2xl">
        <div class="flex justify-center mb-6">
            <img src="logo-cohidro-emop-cabecalho-form.jpg" alt="COHIDRO / EMOP" class="h-20">
        </div>
        
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Acompanhamento de Atividades</h2>

        </div>

        <!-- Área para exibir mensagens de validação -->
        <div id="message-box" class="hidden p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <span id="message-text"></span>
        </div>

        <form action="emop_insert.php" method="POST" onsubmit="return validarFormulario();">
            
            <!-- Campo de E-mail -->
            <div class="mb-5">
                <label for="enviar_por_email" class="block text-gray-700 text-sm font-semibold mb-2">E-mail:</label>
                <input type="email" name="enviar_por_email" id="enviar_por_email" placeholder="Insira seu e-mail" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" required>
            </div>
            
            <!-- Campo de CPF -->
            <div class="mb-5">
                <label for="cpf" class="block text-gray-700 text-sm font-semibold mb-2">CPF:</label>
                <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" required>
            </div>
            
            <!-- Campos de Data (dispostos lado a lado em telas maiores) -->
            <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="data_inicial" class="block text-gray-700 text-sm font-semibold mb-2">Data Inicial:</label>
                    <input type="date" name="data_inicial" id="data_inicial" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" required>
                </div>
                <div>
                    <label for="data_final" class="block text-gray-700 text-sm font-semibold mb-2">Data Final:</label>
                    <input type="date" name="data_final" id="data_final" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" required>
                </div>
            </div>
            
            <!-- Campos de Texto (Atividades Realizadas, Previstas, Pontos Relevantes) -->
            <div class="mb-5">
                <label for="atividades_realizadas" class="block text-gray-700 text-sm font-semibold mb-2">Atividades Realizadas:</label>
                <textarea name="atividades_realizadas" id="atividades_realizadas" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" placeholder="Descreva as atividades realizadas neste período."></textarea>
            </div>
            
            <div class="mb-5">
                <label for="atividades_previstas" class="block text-gray-700 text-sm font-semibold mb-2">Atividades Previstas:</label>
                <textarea name="atividades_previstas" id="atividades_previstas" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" placeholder="Liste as atividades planejadas para o próximo período."></textarea>
            </div>
            
            <div class="mb-5">
                <label for="pontos_relevantes" class="block text-gray-700 text-sm font-semibold mb-2">Pontos Relevantes:</label>
                <textarea name="pontos_relevantes" id="pontos_relevantes" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" placeholder="Compartilhe pontos importantes, desafios ou conquistas."></textarea>
            </div>

            <!-- Botão de Envio com estilo e hover effect -->
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition duration-300 shadow-lg">
                Enviar
            </button>
        </form>
    </div>

    <script>
        // Abre o modal e preenche os campos
        function openEditModal(data) {
            document.getElementById('edit-id').value = data.id ?? '';
            document.getElementById('edit-email').value = data.enviar_por_email ?? '';
            document.getElementById('edit-cpf').value = data.cpf ?? '';
            document.getElementById('edit-id-colaborador').value = data.id_colaborador ?? '';
            document.getElementById('edit-data-inicial').value = data.data_inicial ?? '';
            document.getElementById('edit-data-final').value = data.data_final ?? '';
            document.getElementById('edit-atividades-realizadas').value = data.atividades_realizadas ?? '';
            document.getElementById('edit-atividades-previstas').value = data.atividades_previstas ?? '';
            document.getElementById('edit-pontos-relevantes').value = data.pontos_relevantes ?? '';

            document.getElementById('edit-modal').classList.remove('hidden');
            document.getElementById('edit-modal').classList.add('flex');
        }

        // Fecha o modal
        function closeEditModal() {
            const modal = document.getElementById('edit-modal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Confirmação de exclusão
        function confirmDelete() {
            return window.confirm("Você tem certeza que deseja deletar este registro?");
        }

        // Esconde mensagem de feedback após 5s
        setTimeout(() => {
            const messageBox = document.getElementById('message-box');
            if (messageBox) { messageBox.classList.add('hidden'); }
        }, 5000);
    </script>

</body>
</html>
