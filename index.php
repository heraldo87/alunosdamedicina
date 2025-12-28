<?php
/**
 * MEDINFOCUS - Dashboard Principal (Refatorada v2.2)
 * Interface App-Style com foco em ferramentas acadêmicas.
 */

session_start();

// 1. SEGURANÇA: Verifica se o utilizador está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. DADOS DO UTILIZADOR (Sincronizados com o novo padrão numérico)
$nomeUsuario = $_SESSION['user_name'] ?? 'Doutor(a)';
$primeiroNome = explode(' ', $nomeUsuario)[0];
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
        
        /* Estilo dos Cards de Aplicação com Efeito de Vidro e Movimento */
        .app-card { 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(30, 41, 59, 0.3);
            backdrop-filter: blur(12px);
        }
        .app-card:hover { 
            transform: scale(1.05) translateY(-10px);
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(2, 132, 199, 0.4);
        }

        /* Scrollbar Personalizada */
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar">
        
        <header class="pt-16 pb-12 px-6 md:px-12">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight">
                Olá, <span class="text-brand-primary"><?php echo htmlspecialchars($primeiroNome); ?></span>! 👋
            </h1>
            <p class="text-slate-500 mt-3 font-medium text-lg italic uppercase tracking-wider">
                Selecione uma ferramenta acadêmica para iniciar.
            </p>
        </header>

        <div class="px-6 md:px-12 pb-20 flex-1">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 md:gap-10">
                
                <a href="chat_ia.php" class="app-card group relative border border-slate-800/50 rounded-[3rem] p-8 flex flex-col items-center text-center gap-5 shadow-2xl">
                    <div class="w-20 h-20 bg-brand-primary/10 rounded-3xl flex items-center justify-center text-brand-primary group-hover:bg-brand-primary group-hover:text-white transition-all duration-500 shadow-lg shadow-brand-primary/10">
                        <i class="fa-solid fa-brain text-4xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-[0.15em]">IA Mentor</span>
                        <span class="text-[10px] text-slate-500 font-bold mt-1 block uppercase">MedInFocus AI</span>
                    </div>
                    <div class="absolute top-6 right-8 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-brand-primary"></span>
                    </div>
                </a>

                <a href="repositorio.php" class="app-card group border border-slate-800/50 rounded-[3rem] p-8 flex flex-col items-center text-center gap-5 shadow-2xl hover:border-amber-500/50">
                    <div class="w-20 h-20 bg-amber-500/10 rounded-3xl flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all duration-500 shadow-lg shadow-amber-500/10">
                        <i class="fa-solid fa-folder-tree text-4xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-[0.15em]">Arquivos</span>
                        <span class="text-[10px] text-slate-500 font-bold mt-1 block uppercase">Repositório Turma</span>
                    </div>
                </a>

                <a href="simulados.php" class="app-card group border border-slate-800/50 rounded-[3rem] p-8 flex flex-col items-center text-center gap-5 shadow-2xl hover:border-emerald-500/50">
                    <div class="w-20 h-20 bg-emerald-500/10 rounded-3xl flex items-center justify-center text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-500 shadow-lg shadow-emerald-500/10">
                        <i class="fa-solid fa-notes-medical text-4xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-[0.15em]">Simulados</span>
                        <span class="text-[10px] text-slate-500 font-bold mt-1 block uppercase">Treino Clínico</span>
                    </div>
                </a>

                <a href="agenda.php" class="app-card group border border-slate-800/50 rounded-[3rem] p-8 flex flex-col items-center text-center gap-5 shadow-2xl hover:border-rose-500/50">
                    <div class="w-20 h-20 bg-rose-500/10 rounded-3xl flex items-center justify-center text-rose-500 group-hover:bg-rose-500 group-hover:text-white transition-all duration-500 shadow-lg shadow-rose-500/10">
                        <i class="fa-solid fa-calendar-check text-4xl"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-black text-white uppercase tracking-[0.15em]">Cronograma</span>
                        <span class="text-[10px] text-slate-500 font-bold mt-1 block uppercase">Datas e Provas</span>
                    </div>
                </a>

            </div>
        </div>

        <?php include 'includes/footer.php'; ?>

    </main>

</body>
</html>