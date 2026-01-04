<?php
/**
 * MEDINFOCUS - Dashboard Principal (v2.0)
 * Atualizado para integração com Banco de Dados e UI Dinâmica
 */

// 1. INICIALIZAÇÃO E SEGURANÇA
session_start();

// Verifica login antes de qualquer coisa
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Carrega configurações e conexão PDO
require_once 'php/config.php';

// 2. DADOS DO USUÁRIO
$nomeUsuario = $_SESSION['user_name'] ?? 'Doutor(a)';
$tipoUsuario = $_SESSION['user_type'] ?? 'aluno'; 
$primeiroNome = explode(' ', $nomeUsuario)[0];

// 3. BUSCA DE DADOS DINÂMICOS (WIDGETS)
$proximoEvento = "Nenhum evento";
$dataEvento = "";
$qtdAvisos = 0;

try {
    // A. Busca o próximo evento no calendário (tabelas novas)
    // Verifica se as tabelas existem para evitar erro fatal se ainda não rodou o SQL
    $checkTable = $pdo->query("SHOW TABLES LIKE 'calendario_datas'");
    if($checkTable->rowCount() > 0) {
        $stmtAgenda = $pdo->query("
            SELECT e.titulo, d.data_inicio 
            FROM calendario_datas d
            JOIN calendario_eventos e ON d.evento_id = e.id
            WHERE d.data_inicio >= NOW()
            ORDER BY d.data_inicio ASC
            LIMIT 1
        ");
        $evento = $stmtAgenda->fetch();
        if ($evento) {
            $proximoEvento = $evento['titulo'];
            // Formata data: 28/12 às 14:00
            $dataEvento = date('d/m \à\s H:i', strtotime($evento['data_inicio'])); 
        }
    }

    // B. Conta avisos ativos
    $checkAvisos = $pdo->query("SHOW TABLES LIKE 'avisos_mensagens'");
    if($checkAvisos->rowCount() > 0) {
        $stmtAvisos = $pdo->query("SELECT COUNT(*) FROM avisos_mensagens WHERE ativo = 1");
        $qtdAvisos = $stmtAvisos->fetchColumn();
    }

} catch (Exception $e) {
    // Silencia erros de query na dashboard para não quebrar a UI
    error_log("Erro Widget Dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #0b0f1a; 
        }
        
        /* Estilo dos Cards com Efeito Glassmorphism */
        .app-card { 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .app-card:hover { 
            transform: scale(1.03) translateY(-5px);
            background: rgba(30, 41, 59, 0.7);
            border-color: var(--hover-color, rgba(2, 132, 199, 0.4));
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar relative">
        
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-brand-primary/10 rounded-full blur-[100px] pointer-events-none"></div>

        <header class="pt-12 pb-10 px-6 md:px-12 z-10">
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight">
                        Olá, <span class="text-brand-primary"><?php echo htmlspecialchars($primeiroNome); ?></span>!
                    </h1>
                    <p class="text-slate-500 mt-2 font-medium text-lg">
                        Seu cockpit acadêmico está pronto.
                    </p>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-widest">Status do Sistema</p>
                    <div class="flex items-center justify-end gap-2 mt-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-emerald-500 text-sm font-bold">Operacional</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="px-6 md:px-12 pb-20 flex-1 z-10">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                
                <a href="chat_ia.php" class="app-card group relative rounded-[2.5rem] p-6 flex flex-col items-center text-center gap-4" style="--hover-color: rgba(2, 132, 199, 0.5)">
                    <div class="w-16 h-16 bg-sky-500/10 rounded-2xl flex items-center justify-center text-sky-500 group-hover:bg-sky-500 group-hover:text-white transition-all duration-500 shadow-lg shadow-sky-500/10">
                        <i class="fa-solid fa-brain text-3xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-wider">IA Mentor</span>
                        <span class="text-[10px] text-sky-400 font-bold mt-1 block">Tira-dúvidas 24h</span>
                    </div>
                    <div class="absolute top-5 right-5 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-sky-500"></span>
                    </div>
                </a>

                <a href="repositorio.php" class="app-card group rounded-[2.5rem] p-6 flex flex-col items-center text-center gap-4" style="--hover-color: rgba(245, 158, 11, 0.5)">
                    <div class="w-16 h-16 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all duration-500">
                        <i class="fa-solid fa-folder-tree text-3xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-wider">Arquivos</span>
                        <span class="text-[10px] text-slate-500 font-bold mt-1 block group-hover:text-amber-400 transition-colors">Materiais & Aulas</span>
                    </div>
                </a>

                <a href="agenda.php" class="app-card group rounded-[2.5rem] p-6 flex flex-col items-center text-center gap-4 relative overflow-hidden" style="--hover-color: rgba(244, 63, 94, 0.5)">
                    <div class="w-16 h-16 bg-rose-500/10 rounded-2xl flex items-center justify-center text-rose-500 group-hover:bg-rose-500 group-hover:text-white transition-all duration-500">
                        <i class="fa-solid fa-calendar-check text-3xl"></i>
                    </div>
                    <div class="relative z-10">
                        <span class="block text-sm font-black text-white uppercase tracking-wider">Agenda</span>
                        <?php if ($dataEvento): ?>
                            <span class="text-[10px] text-rose-400 font-bold mt-1 block truncate max-w-[120px]">
                                <?php echo htmlspecialchars($dataEvento); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-[10px] text-slate-500 font-bold mt-1 block">Sem eventos</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($dataEvento): ?>
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-rose-500/50"></div>
                    <?php endif; ?>
                </a>

                <a href="avisos.php" class="app-card group rounded-[2.5rem] p-6 flex flex-col items-center text-center gap-4" style="--hover-color: rgba(168, 85, 247, 0.5)">
                    <div class="w-16 h-16 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-all duration-500">
                        <i class="fa-solid fa-bullhorn text-3xl"></i>
                    </div>
                    <div class="relative">
                        <span class="block text-sm font-black text-white uppercase tracking-wider">Mural</span>
                        <span class="text-[10px] text-slate-500 font-bold mt-1 block group-hover:text-purple-400">
                            <?php echo $qtdAvisos > 0 ? "$qtdAvisos novos avisos" : "Mural atualizado"; ?>
                        </span>
                    </div>
                    <?php if ($qtdAvisos > 0): ?>
                    <div class="absolute top-4 right-4 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full border border-purple-400">
                        <?php echo $qtdAvisos; ?>
                    </div>
                    <?php endif; ?>
                </a>

                <a href="simulados.php" class="app-card group rounded-[2.5rem] p-6 flex flex-col items-center text-center gap-4" style="--hover-color: rgba(16, 185, 129, 0.5)">
                    <div class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-500">
                        <i class="fa-solid fa-notes-medical text-3xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-wider">Simulados</span>
                        <span class="text-[10px] text-slate-500 font-bold mt-1 block">Treino Clínico</span>
                    </div>
                </a>

                <?php if ($tipoUsuario === 'admin' || $tipoUsuario === 'representante'): ?>
                <a href="aprovacoes.php" class="app-card group rounded-[2.5rem] p-6 flex flex-col items-center text-center gap-4 border-dashed border-slate-700 hover:border-solid" style="--hover-color: rgba(99, 102, 241, 0.5)">
                    <div class="w-16 h-16 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-400 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-500">
                        <i class="fa-solid fa-user-gear text-3xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-wider">Gestão</span>
                        <span class="text-[10px] text-indigo-400 font-bold mt-1 block">Área Restrita</span>
                    </div>
                </a>
                <?php endif; ?>

            </div>
        </div>

        <?php include 'includes/footer.php'; ?>

    </main>
</body>
</html>