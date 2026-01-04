<?php
session_start();
require_once 'php/config.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$nomeUsuario = $_SESSION['user_name'] ?? 'Acadêmico';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor IA - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0b0f1a', // Slate 900
                            primary: '#0ea5e9', // Sky 500
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Efeito de Vidro Dark */
        .glass-card-dark {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-[#0b0f1a] text-slate-200 font-sans antialiased overflow-hidden">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-12 custom-scrollbar">
            
            <header class="mb-12">
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-3 py-1 bg-brand-primary/10 text-brand-primary text-[10px] font-black uppercase tracking-tighter rounded-full border border-brand-primary/20">
                        Inteligência Artificial
                    </span>
                </div>
                <h1 class="text-4xl font-black text-white tracking-tight">
                    Mentor <span class="text-brand-primary">IA</span>
                </h1>
                <p class="text-slate-400 mt-3 max-w-2xl text-lg font-medium">
                    Bem-vindo, <?php echo htmlspecialchars($nomeUsuario); ?>. Escolha como deseja interagir com a IA hoje.
                </p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-6xl">
                
                <a href="mentor_texto.php" class="group relative overflow-hidden glass-card-dark rounded-[2.5rem] p-10 hover:border-brand-primary/40 transition-all duration-500">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-brand-primary/5 rounded-full blur-3xl group-hover:bg-brand-primary/10 transition-colors"></div>
                    
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-brand-primary/10 rounded-2xl flex items-center justify-center text-brand-primary mb-8 group-hover:scale-110 group-hover:bg-brand-primary group-hover:text-white transition-all duration-500 shadow-xl shadow-brand-primary/5">
                            <i class="fa-solid fa-comments text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-4">Mentor por Chat</h3>
                        <p class="text-slate-400 text-base leading-relaxed mb-8">
                            Envie dúvidas escritas, cole casos clínicos e peça resumos detalhados. Ideal para revisões aprofundadas e listas.
                        </p>
                        <div class="flex items-center text-brand-primary font-black text-xs uppercase tracking-widest">
                            Iniciar Texto <i class="fa-solid fa-chevron-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                        </div>
                    </div>
                </a>

                <a href="mentor_voz.php" class="group relative overflow-hidden glass-card-dark rounded-[2.5rem] p-10 hover:border-rose-500/40 transition-all duration-500">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-rose-500/5 rounded-full blur-3xl group-hover:bg-rose-500/10 transition-colors"></div>
                    
                    <div class="absolute top-10 right-10">
                        <span class="bg-rose-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest animate-pulse shadow-lg shadow-rose-500/20">LIVE</span>
                    </div>

                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-rose-500/10 rounded-2xl flex items-center justify-center text-rose-500 mb-8 group-hover:scale-110 group-hover:bg-rose-500 group-hover:text-white transition-all duration-500 shadow-xl shadow-rose-500/5">
                            <i class="fa-solid fa-microphone-lines text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-4">Mentor por Voz</h3>
                        <p class="text-slate-400 text-base leading-relaxed mb-8">
                            Converse em tempo real. Discuta casos clínicos falando naturalmente, com latência zero, como em uma chamada.
                        </p>
                        <div class="flex items-center text-rose-500 font-black text-xs uppercase tracking-widest">
                            Falar Agora <i class="fa-solid fa-chevron-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                        </div>
                    </div>
                </a>

            </div>

            <div class="mt-12 flex items-center gap-4 px-6 py-4 rounded-2xl bg-slate-900/50 border border-slate-800/50 max-w-6xl">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-500">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    <strong class="text-slate-300">Aviso de Segurança:</strong> As respostas da IA são ferramentas de apoio e não substituem o julgamento clínico do profissional de saúde. Use como fonte de consulta e validação.
                </p>
            </div>

        </main>
    </div>

</body>
</html>