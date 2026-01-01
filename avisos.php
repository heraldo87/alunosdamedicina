<?php
/**
 * PÁGINA DE AVISOS E MURAL - MEDINFOCUS
 * Frontend para comunicação oficial e avisos de turmas.
 */
session_start();
require_once 'php/config.php';

// Verificação de Segurança
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$tipoUsuario = $_SESSION['user_type'] ?? 'aluno';
// Permissão: Apenas representantes e admins podem postar (pode ajustar conforme necessidade)
$podePostar = in_array($tipoUsuario, ['admin', 'representante']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mural de Avisos - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0f172a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        
        /* Animação suave para novos cards */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0b0f1a', primary: '#0284c7', surface: '#1e293b' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-dark text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <header class="pt-10 pb-6 px-6 md:px-12 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-bullhorn text-brand-primary"></i>
                    Mural de <span class="text-brand-primary">Avisos</span>
                </h1>
                <p class="text-slate-500 mt-1 font-medium italic text-sm">Fique por dentro de tudo que acontece na sua turma.</p>
            </div>

            <?php if ($podePostar): ?>
            <button onclick="abrirModal()" class="bg-brand-primary hover:bg-sky-600 text-white font-bold py-2.5 px-5 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-sky-500/20 text-sm">
                <i class="fa-solid fa-pen-to-square"></i> Novo Aviso
            </button>
            <?php endif; ?>
        </header>

        <div class="flex flex-col lg:flex-row px-6 md:px-12 pb-12 gap-8 h-full">
            
            <div class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-slate-800/40 rounded-2xl p-4 border border-slate-700/50 sticky top-0">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4 px-2">Filtrar por Canal</h3>
                    
                    <div id="lista-murais" class="space-y-1">
                        <button onclick="carregarAvisos('todos', this)" class="mural-btn w-full text-left px-4 py-3 rounded-xl bg-brand-primary text-white font-semibold text-sm transition-all flex items-center justify-between group active-mural shadow-lg shadow-sky-500/10">
                            <span>Todos os Avisos</span>
                            <i class="fa-solid fa-layer-group"></i>
                        </button>
                        
                        </div>
                </div>
            </div>

            <div class="flex-1 max-w-3xl">
                <div id="loading" class="hidden text-center py-10">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-brand-primary"></i>
                    <p class="text-slate-500 text-sm mt-2">Atualizando mural...</p>
                </div>

                <div id="empty-state" class="hidden flex flex-col items-center justify-center py-20 bg-slate-800/20 rounded-3xl border border-dashed border-slate-700">
                    <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4 text-slate-600">
                        <i class="fa-regular fa-paper-plane text-2xl"></i>
                    </div>
                    <p class="text-slate-400 font-medium">Nenhum aviso encontrado neste mural.</p>
                </div>

                <div id="feed-avisos" class="space-y-4">
                    </div>
            </div>
        </div>
        
        <?php include 'includes/footer.php'; ?>
    </main>

    <div id="modalAviso" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="fecharModal()"></div>
        
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-6 bg-brand-surface border border-slate-700 rounded-2xl shadow-2xl scale-100 transition-transform">
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Publicar Aviso</h3>
                <button onclick="fecharModal()" class="text-slate-400 hover:text-white transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <form id="formAviso" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Título do Aviso</label>
                    <input type="text" name="titulo" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:border-brand-primary outline-none focus:ring-1 focus:ring-brand-primary transition-all placeholder-slate-600" placeholder="Ex: Mudança de Sala - Anatomia">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Mural de Destino</label>
                        <select id="selectMuralModal" name="mural_id" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:border-brand-primary outline-none appearance-none">
                            </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Prioridade</label>
                        <select name="prioridade" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2.5 text-white focus:border-brand-primary outline-none appearance-none">
                            <option value="normal">Normal</option>
                            <option value="alta">Alta (Destaque)</option>
                            <option value="urgente">Urgente (Vermelho)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Conteúdo da Mensagem</label>
                    <textarea name="conteudo" required rows="4" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-brand-primary outline-none placeholder-slate-600 resize-none" placeholder="Digite aqui a mensagem completa..."></textarea>
                </div>

                <div class="flex items-center gap-2 py-2">
                    <input type="checkbox" id="fixado" name="fixado" value="true" class="w-4 h-4 rounded border-slate-600 bg-slate-900 text-brand-primary focus:ring-offset-slate-800">
                    <label for="fixado" class="text-sm text-slate-400 select-none">Fixar no topo da lista</label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-brand-primary hover:bg-sky-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-sky-500/20 transition-all flex justify-center items-center gap-2 transform active:scale-[0.98]">
                        <span>Publicar Agora</span>
                        <i class="fa-regular fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variável de controle global
        let muralAtual = 1; // Default: primeiro mural que encontrar ou lógica específica

        document.addEventListener('DOMContentLoaded', () => {
            carregarMurais();
        });

        // 1. Carrega a lista de Murais (Lateral e Select do Modal)
        function carregarMurais() {
            fetch('api/avisos.php?acao=listar_murais')
                .then(r => r.json())
                .then(resp => {
                    if(resp.sucesso) {
                        const lista = document.getElementById('lista-murais');
                        const select = document.getElementById('selectMuralModal');
                        
                        // Limpa mantendo o botão "Todos" se quiser, ou reconstrói
                        // Aqui vamos adicionar aos existentes
                        
                        resp.dados.forEach((mural, index) => {
                            // Renderiza na Sidebar
                            const btnHTML = `
                                <button onclick="carregarAvisos(${mural.id}, this)" class="mural-btn w-full text-left px-4 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 font-medium text-sm transition-all flex items-center justify-between group">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" style="background-color: ${mural.cor_tema}"></span>
                                        ${mural.nome}
                                    </span>
                                </button>
                            `;
                            lista.innerHTML += btnHTML;

                            // Renderiza no Select do Modal
                            select.innerHTML += `<option value="${mural.id}">${mural.nome}</option>`;

                            // Carrega o primeiro mural automaticamente (exceto se for "todos")
                            if(index === 0) carregarAvisos(mural.id, null); 
                        });
                    }
                });
        }

        // 2. Carrega os Avisos do Feed
        function carregarAvisos(muralId, elementoBtn) {
            muralAtual = muralId; // Atualiza contexto global
            
            // UI: Atualiza classe ativa no menu lateral
            if(elementoBtn) {
                document.querySelectorAll('.mural-btn').forEach(btn => {
                    btn.classList.remove('bg-brand-primary', 'text-white', 'shadow-lg');
                    btn.classList.add('text-slate-400', 'hover:bg-slate-800');
                });
                elementoBtn.classList.remove('text-slate-400', 'hover:bg-slate-800');
                elementoBtn.classList.add('bg-brand-primary', 'text-white', 'shadow-lg');
            }

            const feed = document.getElementById('feed-avisos');
            const loading = document.getElementById('loading');
            const empty = document.getElementById('empty-state');

            feed.innerHTML = '';
            loading.classList.remove('hidden');
            empty.classList.add('hidden');

            // Chamada à API
            fetch(`api/avisos.php?acao=listar_avisos&mural_id=${muralId}`)
                .then(r => r.json())
                .then(resp => {
                    loading.classList.add('hidden');
                    
                    if(resp.sucesso && resp.dados.length > 0) {
                        resp.dados.forEach(aviso => {
                            renderizarCardAviso(aviso);
                        });
                    } else {
                        empty.classList.remove('hidden');
                    }
                })
                .catch(() => {
                    loading.classList.add('hidden');
                    // Pode adicionar mensagem de erro de conexão aqui
                });
        }

        // 3. Renderiza um Card de Aviso
        function renderizarCardAviso(aviso) {
            const feed = document.getElementById('feed-avisos');
            
            // Configurações visuais baseadas na prioridade
            let borderClass = 'border-slate-700';
            let iconClass = 'text-slate-500 fa-regular fa-bell';
            let bgIcon = 'bg-slate-800';
            
            if(aviso.fixado == 1) {
                borderClass = 'border-amber-500/50 shadow-[0_0_15px_-3px_rgba(245,158,11,0.15)]';
                iconClass = 'text-amber-500 fa-solid fa-thumbtack';
                bgIcon = 'bg-amber-500/10';
            } else if (aviso.prioridade === 'urgente') {
                borderClass = 'border-red-500/50';
                iconClass = 'text-red-500 fa-solid fa-circle-exclamation';
                bgIcon = 'bg-red-500/10';
            }

            // Formata data
            const dataObj = new Date(aviso.data_criacao);
            const dataFormatada = dataObj.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });

            const html = `
                <div class="bg-brand-surface border ${borderClass} rounded-2xl p-6 relative group animate-fade-in hover:border-slate-600 transition-colors">
                    
                    ${aviso.fixado == 1 ? '<div class="absolute top-0 right-0 px-3 py-1 bg-amber-500 text-brand-dark text-[10px] font-bold uppercase tracking-wide rounded-bl-xl rounded-tr-xl"><i class="fa-solid fa-thumbtack mr-1"></i> Fixado</div>' : ''}
                    
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl ${bgIcon} flex items-center justify-center flex-shrink-0">
                            <i class="${iconClass} text-xl"></i>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="text-white font-bold text-lg leading-tight mb-1 pr-16">${aviso.titulo}</h4>
                            
                            <div class="flex items-center gap-3 text-xs text-slate-500 mb-3">
                                <span class="font-semibold text-brand-primary bg-brand-primary/10 px-2 py-0.5 rounded flex items-center gap-1">
                                    <i class="fa-solid fa-user-pen"></i> ${aviso.autor_nome || 'Sistema'}
                                </span>
                                <span><i class="fa-regular fa-clock"></i> ${dataFormatada}</span>
                            </div>
                            
                            <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap">${aviso.conteudo}</p>
                        </div>
                    </div>
                </div>
            `;
            feed.innerHTML += html;
        }

        // 4. Lógica do Modal e Form
        function abrirModal() { document.getElementById('modalAviso').classList.remove('hidden'); }
        function fecharModal() { 
            document.getElementById('modalAviso').classList.add('hidden'); 
            document.getElementById('formAviso').reset();
        }

        document.getElementById('formAviso').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Publicando...';

            const formData = new FormData(this);
            // Checkbox fixado precisa de tratamento manual se não vier checked
            // (Mas o FormData pega se estiver checked, o PHP trata o resto)

            fetch('api/avisos.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(r => r.json())
            .then(resp => {
                btn.disabled = false;
                btn.innerHTML = originalText;

                if(resp.sucesso) {
                    fecharModal();
                    // Recarrega o mural para onde o aviso foi enviado
                    const muralDestino = formData.get('mural_id');
                    carregarAvisos(muralDestino, null); 
                    // Se o usuário estiver vendo outro mural, talvez queira avisar, mas recarregar é suficiente por ora.
                } else {
                    alert('Erro: ' + resp.msg);
                }
            });
        });

    </script>
</body>
</html>