<?php
session_start();
require_once 'php/config.php';

// Segurança básica
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Workspaces - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0f172a', primary: '#0284c7', surface: '#1e293b', accent: '#f59e0b' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Efeito Glassmorphism */
        .glass-panel {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto relative">
        
        <header class="pt-10 pb-6 px-8 md:px-12 border-b border-slate-800/50">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Meus <span class="text-brand-primary">Workspaces</span>
            </h1>
            <p class="text-slate-500 mt-2">Gerencie suas disciplinas e materiais de estudo.</p>
        </header>

        <div class="flex-1 flex flex-col items-center justify-center p-8">
            
            <div class="glass-panel max-w-2xl w-full rounded-[2rem] p-12 text-center border-dashed border-2 border-slate-700/50 hover:border-brand-primary/30 transition-colors duration-500">
                
                <div class="w-24 h-24 bg-brand-surface rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-black/50 ring-4 ring-brand-primary/10">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-600"></i>
                </div>

                <h2 class="text-2xl font-bold text-white mb-3">Nenhum workspace encontrado</h2>
                <p class="text-slate-400 mb-8 max-w-md mx-auto leading-relaxed">
                    Parece que você ainda não tem nenhuma disciplina criada ou vinculada à sua conta. Comece organizando seus estudos agora mesmo.
                </p>

                <button onclick="abrirModal()" class="group relative inline-flex items-center gap-3 bg-brand-primary hover:bg-sky-500 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 shadow-lg shadow-sky-900/20 hover:shadow-sky-500/30 hover:-translate-y-1">
                    <i class="fa-solid fa-plus text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    <span>Criar Novo Workspace</span>
                </button>

            </div>

        </div>

        <?php include 'includes/footer.php'; ?>
    </main>


    <div id="modalCriar" class="fixed inset-0 z-50 hidden">
        
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300" id="modalPanel">
                
                <div class="p-6 md:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-layer-group text-brand-primary"></i> Novo Workspace
                        </h3>
                        <button onclick="fecharModal()" class="text-slate-500 hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <form id="formCriar" onsubmit="enviarCriacao(event)">
                        <div class="mb-6">
                            <label class="block text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">Nome da Disciplina</label>
                            <input type="text" id="nomePastaInput" required
                                class="w-full bg-brand-surface border border-slate-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all placeholder-slate-600"
                                placeholder="Ex: Cardiologia 2024">
                            <p class="text-xs text-slate-500 mt-2">Isso criará uma pasta segura para seus arquivos.</p>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" onclick="fecharModal()" class="flex-1 py-3 rounded-xl border border-slate-700 text-slate-300 hover:bg-slate-800 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" id="btnSalvar" class="flex-1 py-3 rounded-xl bg-brand-primary hover:bg-sky-500 text-white font-bold shadow-lg shadow-sky-900/20 transition-all flex items-center justify-center gap-2">
                                <span>Criar</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalCriar');
        const backdrop = document.getElementById('modalBackdrop');
        const panel = document.getElementById('modalPanel');

        function abrirModal() {
            modal.classList.remove('hidden');
            // Timeout para permitir animação CSS entrar
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            }, 10);
            document.getElementById('nomePastaInput').focus();
        }

        function fecharModal() {
            backdrop.classList.add('opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('formCriar').reset();
                resetarBotao();
            }, 300);
        }

        async function enviarCriacao(e) {
            e.preventDefault();
            const inputNome = document.getElementById('nomePastaInput').value;
            const btn = document.getElementById('btnSalvar');
            const originalText = btn.innerHTML;

            // Estado de Loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processando...';
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                // Chama o PHP que ajustamos
                const response = await fetch('php/criar_workspace.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome_pasta: inputNome })
                });

                const data = await response.json();

                if (data.sucesso) {
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Criado!';
                    btn.classList.replace('bg-brand-primary', 'bg-emerald-500'); // Verde sucesso
                    
                    setTimeout(() => {
                        fecharModal();
                        alert('Sucesso! O sistema está criando seu workspace em segundo plano.');
                        // Aqui você poderia recarregar a página ou atualizar a lista via AJAX
                        // location.reload(); 
                    }, 1000);
                } else {
                    alert('Erro ao criar: ' + (data.erro || 'Falha desconhecida'));
                    resetarBotao();
                }
            } catch (error) {
                console.error(error);
                alert('Erro de conexão com o servidor.');
                resetarBotao();
            }
        }

        function resetarBotao() {
            const btn = document.getElementById('btnSalvar');
            btn.disabled = false;
            btn.innerHTML = '<span>Criar</span><i class="fa-solid fa-arrow-right"></i>';
            btn.classList.remove('opacity-75', 'cursor-not-allowed', 'bg-emerald-500');
            btn.classList.add('bg-brand-primary');
        }

        // Fechar ao clicar fora (no backdrop)
        backdrop.addEventListener('click', fecharModal);
    </script>
</body>
</html>