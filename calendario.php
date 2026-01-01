<?php
/**
 * PÁGINA DE CALENDÁRIO - MEDINFOCUS
 * Frontend integrado com FullCalendar e API JSON.
 */
session_start();
require_once 'php/config.php';

// Verificação de segurança
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$tipoUsuario = $_SESSION['user_type'] ?? 'aluno';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário Acadêmico - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Customização do FullCalendar para combinar com o tema Dark/Slate */
        :root {
            --fc-border-color: #334155; /* Slate 700 */
            --fc-page-bg-color: #1e293b; /* Slate 800 */
            --fc-neutral-bg-color: #0f172a;
            --fc-list-event-hover-bg-color: #334155;
            --fc-today-bg-color: rgba(2, 132, 199, 0.1); /* Sky 600 com opacidade */
        }
        
        .fc-theme-standard td, .fc-theme-standard th { border-color: var(--fc-border-color); }
        .fc .fc-toolbar-title { font-size: 1.5rem; font-weight: 700; color: #f8fafc; }
        .fc .fc-col-header-cell-cushion { color: #94a3b8; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; padding-top: 10px; padding-bottom: 10px; }
        .fc .fc-daygrid-day-number { color: #cbd5e1; font-weight: 500; }
        .fc-day-today .fc-daygrid-day-number { color: #38bdf8; font-weight: 800; }
        
        /* Botões do FullCalendar */
        .fc .fc-button-primary { background-color: #1e293b; border-color: #334155; color: #cbd5e1; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem; }
        .fc .fc-button-primary:hover { background-color: #0284c7; border-color: #0284c7; color: white; }
        .fc .fc-button-primary:not(:disabled).fc-button-active { background-color: #0284c7; border-color: #0284c7; }

        /* Estilização do Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0f172a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
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
        
        <header class="pt-10 pb-6 px-6 md:px-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-regular fa-calendar-check text-brand-primary"></i>
                    Calendário <span class="text-brand-primary">Acadêmico</span>
                </h1>
                <p class="text-slate-500 mt-1 font-medium italic text-sm">Organize suas provas, aulas e eventos importantes.</p>
            </div>
            
            <button onclick="abrirModal()" class="bg-brand-primary hover:bg-sky-600 text-white font-bold py-2.5 px-5 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-sky-500/20 text-sm">
                <i class="fa-solid fa-plus"></i> Novo Evento
            </button>
        </header>

        <div class="flex-1 px-6 md:px-10 pb-10 flex flex-col lg:flex-row gap-6">
            
            <div class="w-full lg:w-64 flex-shrink-0 space-y-6">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-700/50">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Meus Calendários</h3>
                    <div id="lista-calendarios" class="space-y-3">
                        <div class="animate-pulse flex items-center gap-3">
                            <div class="w-4 h-4 bg-slate-700 rounded"></div>
                            <div class="h-2 w-24 bg-slate-700 rounded"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-900/20 p-5 rounded-2xl border border-blue-500/10">
                    <h3 class="text-xs font-black text-blue-400 uppercase tracking-widest mb-2">Próxima Prova</h3>
                    <p class="text-white font-bold text-lg">Anatomia II</p>
                    <p class="text-slate-400 text-xs mt-1"><i class="fa-regular fa-clock mr-1"></i> 14/Out às 08:00</p>
                </div>
            </div>

            <div class="flex-1 bg-slate-800/30 backdrop-blur-sm rounded-3xl border border-slate-700/50 p-6 shadow-xl relative min-h-[600px]">
                <div id='calendar' class="h-full"></div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>

    </main>

    <div id="modalEvento" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="fecharModal()"></div>
        
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6 bg-brand-surface border border-slate-700 rounded-2xl shadow-2xl scale-100 transition-transform">
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Adicionar Evento</h3>
                <button onclick="fecharModal()" class="text-slate-400 hover:text-white transition-colors"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <form id="formEvento" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Título</label>
                    <input type="text" name="titulo" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-brand-primary outline-none focus:ring-1 focus:ring-brand-primary transition-all placeholder-slate-600" placeholder="Ex: Prova de Bioquímica">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Data Início</label>
                        <input type="datetime-local" name="data_inicio" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:border-brand-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Data Fim (Opcional)</label>
                        <input type="datetime-local" name="data_fim" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:border-brand-primary outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Calendário</label>
                    <select id="selectCalendario" name="calendario_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-brand-primary outline-none">
                        </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Local / Observação</label>
                    <textarea name="descricao" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-brand-primary outline-none placeholder-slate-600" placeholder="Sala 302, Bloco C..."></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-brand-primary hover:bg-sky-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-sky-500/20 transition-all flex justify-center items-center gap-2">
                        <span>Salvar no Calendário</span>
                        <i class="fa-regular fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let calendar; // Variável global para o calendário

        document.addEventListener('DOMContentLoaded', function() {
            carregarCalendarios(); // Carrega os filtros e o select do modal

            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    list: 'Lista'
                },
                height: '100%',
                navLinks: true, 
                editable: false, // Por enquanto, edição só via modal
                selectable: true,
                dayMaxEvents: true,
                
                // Clicar no dia abre modal para criar evento naquela data
                dateClick: function(info) {
                    abrirModal(info.dateStr); 
                },

                // Configuração da Fonte de Eventos (API)
                // O FullCalendar chama essa função sempre que muda o mês/semana
                events: function(info, successCallback, failureCallback) {
                    // Pegamos os IDs dos calendários marcados no filtro
                    const checkboxes = document.querySelectorAll('.calendario-filter:checked');
                    if (checkboxes.length === 0) {
                        successCallback([]); // Se nada marcado, retorna vazio
                        return;
                    }

                    // Por enquanto, vamos pegar o PRIMEIRO calendário para demonstrar.
                    // (Melhoria futura: modificar API para aceitar múltiplos IDs ou fazer loop aqui)
                    // Para simplificar agora: carregamos eventos do ID 1 (padrão)
                    
                    // fetch(`api/calendario.php?acao=listar_eventos&calendario_id=1&inicio=${info.startStr}&fim=${info.endStr}`)
                    //     .then(response => response.json())
                    //     .then(data => {
                    //         if(data.sucesso) successCallback(data.dados);
                    //         else failureCallback();
                    //     });
                    
                    // LÓGICA DE MULTIPLOS CALENDÁRIOS (Avançada)
                    // Vamos fazer requests para cada calendário checado
                    let promises = [];
                    checkboxes.forEach(chk => {
                        let url = `api/calendario.php?acao=listar_eventos&calendario_id=${chk.value}&inicio=${info.startStr}&fim=${info.endStr}`;
                        promises.push(fetch(url).then(r => r.json()));
                    });

                    Promise.all(promises).then(results => {
                        let todosEventos = [];
                        results.forEach((res, index) => {
                            if(res.sucesso) {
                                // Adiciona a cor correta baseado no dataset do checkbox
                                let cor = checkboxes[index].dataset.cor;
                                let eventosComCor = res.dados.map(evt => ({ ...evt, backgroundColor: cor, borderColor: cor }));
                                todosEventos = todosEventos.concat(eventosComCor);
                            }
                        });
                        successCallback(todosEventos);
                    });
                }
            });

            calendar.render();
        });

        // --- FUNÇÕES AUXILIARES ---

        // 1. Busca os tipos de calendário para preencher Sidebar e Modal
        function carregarCalendarios() {
            fetch('api/calendario.php?acao=listar_calendarios')
                .then(r => r.json())
                .then(resp => {
                    if(resp.sucesso) {
                        const lista = document.getElementById('lista-calendarios');
                        const select = document.getElementById('selectCalendario');
                        
                        lista.innerHTML = '';
                        select.innerHTML = '';

                        resp.dados.forEach(cal => {
                            // Preenche Filtro Lateral
                            lista.innerHTML += `
                                <div class="flex items-center gap-3 group cursor-pointer">
                                    <input type="checkbox" id="cal_${cal.id}" value="${cal.id}" data-cor="${cal.cor}" 
                                           class="calendario-filter w-4 h-4 rounded border-slate-600 bg-slate-800 text-brand-primary focus:ring-offset-slate-900 cursor-pointer" 
                                           checked onchange="calendar.refetchEvents()">
                                    <label for="cal_${cal.id}" class="text-sm text-slate-300 font-medium group-hover:text-white cursor-pointer select-none flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" style="background-color: ${cal.cor}"></span>
                                        ${cal.nome}
                                    </label>
                                </div>
                            `;

                            // Preenche Select do Modal
                            select.innerHTML += `<option value="${cal.id}">${cal.nome}</option>`;
                        });
                    }
                });
        }

        // 2. Controle do Modal
        function abrirModal(dataInicial = null) {
            const modal = document.getElementById('modalEvento');
            modal.classList.remove('hidden');
            
            // Se clicou num dia, já preenche o input de data
            if(dataInicial) {
                // Formato datetime-local requer YYYY-MM-DDTHH:MM
                const date = new Date(dataInicial);
                // Ajuste fuso simples para o exemplo (ideal usar biblioteca moment/luxon)
                date.setHours(8, 0, 0, 0); 
                const isoStr = date.toISOString().slice(0, 16); // Remove segundos e Z
                document.getElementsByName('data_inicio')[0].value = isoStr;
            }
        }

        function fecharModal() {
            document.getElementById('modalEvento').classList.add('hidden');
            document.getElementById('formEvento').reset();
        }

        // 3. Enviar Novo Evento
        document.getElementById('formEvento').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            // Transforma FormData em JSON
            const data = Object.fromEntries(formData.entries());

            fetch('api/calendario.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(resp => {
                if(resp.sucesso) {
                    fecharModal();
                    calendar.refetchEvents(); // Atualiza o calendário na hora
                    // Opcional: Mostrar toast de sucesso
                    alert('Evento criado com sucesso!');
                } else {
                    alert('Erro: ' + resp.msg);
                }
            });
        });
    </script>
</body>
</html>