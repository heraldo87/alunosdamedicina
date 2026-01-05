<?php
/**
 * MEDINFOCUS - Gestão de Workspaces (Drive Sync)
 */
session_start();

// VERIFICAÇÃO DE SEGURANÇA
// Garante que o usuário está logado antes de mostrar o conteúdo
if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Inclusão do Menu Lateral
include 'includes/sidebar.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspaces - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0b0f1a',    
                            primary: '#0284c7', 
                            surface: '#1e293b', 
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0b0f1a; color: #cbd5e1; }
        
        /* Efeito de Vidro (Glassmorphism) */
        .glass-panel {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto p-6 md:p-12 relative">
        
        <header class="mb-10">
            <h1 class="text-3xl font-bold text-white mb-2">Minhas Workspaces</h1>
            <p class="text-slate-400">Gerencie pastas sincronizadas no Google Drive para otimizar seus arquivos.</p>
        </header>

        <div class="glass-panel rounded-2xl p-8 max-w-2xl w-full mx-auto relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fa-brands fa-google-drive text-9xl text-brand-primary"></i>
            </div>

            <div class="relative z-10">
                <h2 class="text-xl font-semibold text-white mb-4">Criar Nova Área de Arquivos</h2>
                
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="nomePasta" class="block text-sm font-medium text-slate-300 mb-1">Nome da Workspace</label>
                        <input type="text" id="nomePasta" 
                            class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all placeholder-slate-600"
                            placeholder="Ex: Anatomia Patológica 2026">
                    </div>
                    
                    <button onclick="criarWorkspace()" id="btnCriar"
                        class="bg-brand-primary hover:bg-sky-600 text-white font-semibold py-3 px-6 rounded-lg transition-all flex items-center justify-center gap-2 shadow-lg shadow-brand-primary/20 hover:scale-[1.02] transform active:scale-95">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Sincronizar Nova Pasta</span>
                    </button>
                </div>
            </div>
            
            <div id="feedbackArea" class="mt-6 hidden transition-all duration-300"></div>
        </div>

    </main>

    <script>
        async function criarWorkspace() {
            const nomePastaInput = document.getElementById('nomePasta');
            const btn = document.getElementById('btnCriar');
            const feedback = document.getElementById('feedbackArea');
            const originalBtnText = btn.innerHTML; // Salva o texto original do botão

            const nomePasta = nomePastaInput.value.trim();

            // Validação simples no front
            if (!nomePasta) {
                alert("Por favor, digite um nome para a pasta.");
                nomePastaInput.focus();
                return;
            }

            // 1. UI: Bloqueia interface e mostra loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processando...';
            feedback.classList.add('hidden');
            feedback.innerHTML = '';

            try {
                // 2. Requisição para a API PHP
                const response = await fetch('api/criar_workspace.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome_pasta: nomePasta })
                });

                // Tenta fazer o parse do JSON
                // Se o servidor devolver HTML (erro 404/500), isso vai falhar e cair no catch
                const data = await response.json();

                feedback.classList.remove('hidden');
                
                // 3. Lógica de Validação da Resposta
                // Só consideramos sucesso se o HTTP for OK E o campo 'success' for true
                if (response.ok && data.success === true) {
                    
                    // --- SUCESSO ---
                    feedback.innerHTML = `
                        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm flex items-start gap-3 animate-fade-in">
                            <i class="fa-solid fa-check-circle text-lg mt-0.5"></i>
                            <div>
                                <span class="font-bold block mb-1">Sucesso!</span>
                                ${data.message}
                            </div>
                        </div>`;
                    
                    // Limpa o formulário
                    nomePastaInput.value = '';

                } else {
                    // --- ERRO LÓGICO (Ex: Duplicidade ou Erro do n8n) ---
                    // Lança erro para cair no bloco catch abaixo
                    throw new Error(data.message || 'Erro desconhecido ao processar solicitação.');
                }

            } catch (error) {
                // --- ERRO TÉCNICO ---
                feedback.classList.remove('hidden');
                feedback.innerHTML = `
                    <div class="p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg text-sm flex items-start gap-3 animate-pulse">
                        <i class="fa-solid fa-triangle-exclamation text-lg mt-0.5"></i>
                        <div>
                            <span class="font-bold block mb-1">Atenção</span>
                            ${error.message}
                        </div>
                    </div>`;
            } finally {
                // 4. UI: Restaura o botão
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            }
        }
    </script>
</body>
</html>