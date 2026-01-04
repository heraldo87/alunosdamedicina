<?php
/**
 * PÁGINA DE CALENDÁRIO - MEDINFOCUS
 * Versão 2.1: Controle de Acesso por Nível (2 e 3)
 */
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Lógica de Permissão
// Nível 1: Aluno (Leitura) | Nível 2+: Representante/Admin (Edição)
$nivelAcesso = $_SESSION['user_level'] ?? 1;
$podeEditar = ($nivelAcesso >= 2); 

// =================================================================================
// 2. CAMADA DE API (Backend Integrado)
// =================================================================================

// A) Endpoint GET Eventos (Público para usuários logados)
if (isset($_GET['get_events'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("SELECT id, titulo, descricao, data_inicio, data_fim, cor_css, tipo FROM eventos");
        $eventosDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $eventosFormatados = [];
        foreach($eventosDb as $evt) {
            $eventosFormatados[] = [
                'id' => $evt['id'],
                'title' => $evt['titulo'],
                'start' => $evt['data_inicio'],
                'end' => $evt['data_fim'],
                'classNames' => [$evt['cor_css'], 'border-0', 'text-white', 'shadow-md'],
                'extendedProps' => [
                    'descricao' => $evt['descricao'],
                    'tipo' => $evt['tipo']
                ]
            ];
        }
        echo json_encode($eventosFormatados);
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
}

// B) Endpoint POST Salvar (Protegido - Apenas Nível 2+)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'salvar_evento') {
    header('Content-Type: application/json');
    
    // Trava de Segurança no Backend
    if (!$podeEditar) {
        echo json_encode(['sucesso' => false, 'msg' => 'Acesso negado: Sem permissão para editar.']);
        exit;
    }

    try {
        $sql = "INSERT INTO eventos (titulo, data_inicio, data_fim, descricao, tipo, cor_css, localizacao) 
                VALUES (:titulo, :inicio, :fim, :desc, :tipo, :cor, :local)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $_POST['titulo'],
            ':inicio' => $_POST['data_inicio'],
            ':fim'    => !empty($_POST['data_fim']) ? $_POST['data_fim'] : null,
            ':desc'   => $_POST['descricao'] ?? '',
            ':tipo'   => $_POST['tipo'] ?? 'evento',
            ':cor'    => $_POST['cor_css'] ?? 'bg-sky-500',
            ':local'  => $_POST['localizacao'] ?? ''
        ]);
        
        echo json_encode(['sucesso' => true]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// =================================================================================
// 3. FRONTEND (HTML)
// =================================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário Acadêmico - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/pt-br.global.min.js'></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Tema Dark para FullCalendar */
        :root {
            --fc-border-color: #334155;
            --fc-page-bg-color: #1e293b;
            --fc-list-event-hover-bg-color: #334155;
            --fc-today-bg-color: rgba(2, 132, 199, 0.1);
        }
        .fc .fc-toolbar-title { font-size: 1.5rem; font-weight: 700; color: #f8fafc; }
        .fc .fc-col-header-cell-cushion { color: #94a3b8; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; padding: 10px 0; }
        .fc .fc-daygrid-day-number { color: #cbd5e1; font-weight: 500; text-decoration: none; }
        .fc-day-today .fc-daygrid-day-number { color: #38bdf8; font-weight: 800; }
        .fc .fc-button-primary { background-color: #0f172a; border-color: #334155; color: #cbd5e1; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; font-weight: 700; }
        .fc .fc-button-primary:hover { background-color: #0284c7; border-color: #0284c7; color: white; }
        .fc .fc-button-primary:not(:disabled).fc-button-active { background-color: #0284c7; border-color: #0284c7; color: white; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0f172a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
    
    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand: { dark: '#0b0f1a', primary: '#0284c7', surface: '#1e293b' } } } } }
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
                <p class="text-slate-500 mt-1 font-medium italic text-sm">Organize suas provas, aulas e eventos.</p>
            </div>
            
            <?php if ($podeEditar): ?>
            <button onclick="abrirModal()" class="bg-brand-primary hover:bg-sky-600 text-white font-bold py-2.5 px-5 rounded-xl flex items-center gap-2 transition-all shadow-lg shadow-sky-500/20 text-sm">
                <i class="fa-solid fa-plus"></i> Novo Evento
            </button>
            <?php endif; ?>
        </header>

        <div class="flex-1 px-6 md:px-10 pb-10 flex flex-col lg:flex-row gap-6">
            
            <div class="w-full lg:w-64 flex-shrink-0 space-y-6">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-700/50">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Legenda</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.5)]"></span>
                            <span class="text-sm font-medium text-slate-300">Provas</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                            <span class="text-sm font-medium text-slate-300">Estágios</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-indigo-500 shadow-[0_0_10px_rgba(99,102,241,0.5)]"></span>
                            <span class="text-sm font-medium text-slate-300">Eventos</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-sky-500 shadow-[0_0_10px_rgba(14,165,233,0.5)]"></span>
                            <span class="text-sm font-medium text-slate-300">Aulas</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-900/50 to-slate-900 p-5 rounded-2xl border border-indigo-500/20">
                    <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest mb-2">Dica do Sistema</h3>
                    <p class="text-slate-300 text-sm leading-relaxed">Clique em qualquer dia vazio no calendário para adicionar rapidamente um novo evento.</p>
                </div>
            </div>

            <div class="flex-1 bg-slate-800/30 backdrop-blur-sm rounded-3xl border border-slate-700/50 p-6 shadow-xl relative min-h-[600px]">
                <div id='calendar' class="h-full"></div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>

    </main>

    <?php if ($podeEditar): ?>
    <div id="modalEvento" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="fecharModal()"></div>
        
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6 bg-brand-surface border border-slate-700 rounded-2xl shadow-2xl">
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Novo Compromisso</h3>
                <button onclick="fecharModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>

            <form id="formEvento" class="space-y-4">
                <input type="hidden" name="ajax_action" value="salvar_evento">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">O que vai acontecer?</label>
                    <input type="text" name="titulo" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-brand-primary outline-none placeholder-slate-600" placeholder="Ex: Prova de Anatomia">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Início</label>
                        <input type="datetime-local" name="data_inicio" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:border-brand-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fim (Opcional)</label>
                        <input type="datetime-local" name="data_fim" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:border-brand-primary outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo de Evento</label>
                    <select name="cor_css" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-brand-primary outline-none appearance-none">
                        <option value="bg-sky-500 border-sky-600">Aula Comum (Azul)</option>
                        <option value="bg-rose-600 border-rose-700">Prova / Importante (Vermelho)</option>
                        <option value="bg-emerald-600 border-emerald-700">Estágio / Prática (Verde)</option>
                        <option value="bg-indigo-600 border-indigo-700">Evento / Congresso (Roxo)</option>
                        <option value="bg-amber-500 border-amber-600">Festa / Social (Amarelo)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Detalhes</label>
                    <textarea name="descricao" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-white focus:border-brand-primary outline-none placeholder-slate-600" placeholder="Local, observações..."></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnSalvar" class="w-full bg-brand-primary hover:bg-sky-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-sky-500/20 transition-all flex justify-center items-center gap-2">
                        <span>Salvar no Calendário</span>
                        <i class="fa-regular fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        let calendar;

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
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
                editable: false,
                selectable: true,
                dayMaxEvents: true,
                
                // Fonte de Eventos: Chama a própria página com ?get_events=true
                events: window.location.pathname + '?get_events=true',

                // Só permite clicar na data se tiver permissão (Injetado via PHP)
                <?php if ($podeEditar): ?>
                dateClick: function(info) {
                    abrirModal(info.dateStr);
                },
                <?php endif; ?>

                eventContent: function(arg) {
                    return {
                        html: `<div class="fc-content p-1 truncate">
                                <span class="font-bold text-xs">${arg.timeText}</span>
                                <span class="text-xs">${arg.event.title}</span>
                               </div>`
                    }
                }
            });

            calendar.render();
        });

        // Funções de Modal apenas se o usuário tiver permissão
        <?php if ($podeEditar): ?>
        function abrirModal(dataInicial = null) {
            document.getElementById('modalEvento').classList.remove('hidden');
            if(dataInicial) {
                let date = new Date(dataInicial);
                if(dataInicial.indexOf('T') === -1) date.setHours(8, 0, 0, 0); 
                const offset = date.getTimezoneOffset() * 60000;
                const localISOTime = (new Date(date - offset)).toISOString().slice(0, 16);
                document.getElementsByName('data_inicio')[0].value = localISOTime;
            }
        }

        function fecharModal() {
            document.getElementById('modalEvento').classList.add('hidden');
            document.getElementById('formEvento').reset();
        }

        document.getElementById('formEvento').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSalvar');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('calendario.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(resp => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if(resp.sucesso) {
                    fecharModal();
                    calendar.refetchEvents();
                } else {
                    alert('Erro: ' + resp.msg);
                }
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Erro de conexão.');
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>