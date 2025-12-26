<?php
session_start();
require_once 'php/config.php';

// Proteção da página
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Busca de dados (Usando a coluna corrigida 'data_criacao')
try {
    $stmt = $pdo->prepare("SELECT nome, email, tipo_usuario, data_criacao FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar perfil: " . $e->getMessage());
}

// Mapeamento de badges (Ajustado para cores mais vibrantes no dark mode)
$labels = [
    'admin' => ['txt' => 'Administrador', 'class' => 'bg-red-500/20 text-red-300 border border-red-500/30'],
    'representante' => ['txt' => 'Representante', 'class' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30'],
    'aluno' => ['txt' => 'Acadêmico', 'class' => 'bg-sky-500/20 text-sky-300 border border-sky-500/30']
];
$badge = $labels[$user['tipo_usuario']] ?? $labels['aluno'];
?>

<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        // Paleta Dark Mode refinada
                        dark: {
                            base: '#0f172a',   // Fundo principal (igual sidebar)
                            card: '#1e293b',   // Fundo dos cartões
                            border: '#334155', // Bordas sutis
                            text: '#94a3b8',   // Texto secundário
                            heading: '#f1f5f9' // Títulos
                        },
                        brand: { primary: '#0ea5e9' } // Um azul ligeiramente mais vibrante para dark
                    }
                }
            }
        }
    </script>
    <style>
        /* Scrollbar dark personalizada */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="bg-dark-base flex font-sans text-dark-text antialiased selection:bg-brand-primary/30 selection:text-white">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 h-screen overflow-y-auto bg-dark-base relative z-0">
        
        <header class="sticky top-0 z-10 bg-dark-base/80 backdrop-blur-md border-b border-dark-border px-8 py-6">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-2xl font-bold text-dark-heading tracking-tight">Meu Perfil</h1>
                <p class="text-dark-text text-sm mt-1">Visão geral da sua conta e informações acadêmicas.</p>
            </div>
        </header>

        <div class="p-8 max-w-4xl mx-auto">
            <div class="bg-dark-card rounded-3xl shadow-xl shadow-black/20 border border-dark-border overflow-hidden transition-all hover:border-dark-border/80">
                
                <div class="h-36 bg-[conic-gradient(at_top_right,_var(--tw-gradient-stops))] from-slate-900 via-blue-900 to-dark-base relative overflow-hidden">
                     <div class="absolute inset-0 bg-grid-white/[0.05] bg-[length:20px_20px]"></div> </div>
                
                <div class="px-8 pb-8 relative">
                    <div class="flex flex-col md:flex-row items-start md:items-end -mt-16 mb-8 gap-6">
                        <div class="relative group">
                            <div class="w-32 h-32 rounded-3xl bg-dark-card p-1.5 shadow-2xl shadow-black/50 ring-4 ring-dark-base group-hover:ring-brand-primary/50 transition-all">
                                <div class="w-full h-full rounded-2xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center text-slate-300 text-4xl font-bold tracking-wider">
                                    <?php echo strtoupper(substr($user['nome'], 0, 2)); ?>
                                </div>
                            </div>
                            <div class="absolute bottom-2 right-2 w-5 h-5 bg-emerald-500 rounded-full border-4 border-dark-card" title="Status: Ativo"></div>
                        </div>

                        <div class="flex-1 md:mb-2">
                            <h2 class="text-3xl font-bold text-white tracking-tight mb-3"><?php echo htmlspecialchars($user['nome']); ?></h2>
                            
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider inline-flex items-center gap-2 <?php echo $badge['class']; ?>">
                                    <?php if($user['tipo_usuario'] == 'admin'): ?><i class="fa-solid fa-shield-halved"></i><?php endif; ?>
                                    <?php if($user['tipo_usuario'] == 'aluno'): ?><i class="fa-solid fa-graduation-cap"></i><?php endif; ?>
                                    <?php echo $badge['txt']; ?>
                                </span>
                                <span class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check text-[0.6rem]"></i> Conta Ativa
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 py-8 border-t border-dark-border/50">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-dark-text/60 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-regular fa-user text-dark-text/40"></i> Nome Completo
                            </label>
                            <p class="text-lg text-slate-200 font-medium break-words"><?php echo htmlspecialchars($user['nome']); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-dark-text/60 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-regular fa-envelope text-dark-text/40"></i> E-mail Institucional
                            </label>
                            <p class="text-lg text-slate-200 font-medium break-all"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-dark-text/60 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-regular fa-calendar text-dark-text/40"></i> Membro Desde
                            </label>
                            <p class="text-lg text-slate-200 font-medium">
                                <?php echo date('d/m/Y', strtotime($user['data_criacao'] ?? 'now')); ?>
                            </p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-dark-text/60 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-fingerprint text-dark-text/40"></i> ID do Usuário
                            </label>
                            <p class="text-lg text-slate-200 font-medium font-mono">#<?php echo str_pad($_SESSION['user_id'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-4 pt-6 border-t border-dark-border/50">
                        <button disabled class="group flex items-center gap-3 px-6 py-3 bg-dark-base/50 text-dark-text font-semibold rounded-xl transition-all border border-dark-border opacity-60 cursor-not-allowed hover:bg-dark-base/70">
                            <i class="fa-solid fa-lock text-dark-text/50 group-hover:text-brand-primary/70 transition-colors"></i> 
                            <span>Alterar Senha</span>
                            <span class="text-xs bg-dark-card px-2 py-0.5 rounded ml-2">Em breve</span>
                        </button>
                         <button disabled class="group flex items-center gap-3 px-6 py-3 bg-dark-base/50 text-dark-text font-semibold rounded-xl transition-all border border-dark-border opacity-60 cursor-not-allowed hover:bg-dark-base/70">
                            <i class="fa-solid fa-user-pen text-dark-text/50 group-hover:text-brand-primary/70 transition-colors"></i> 
                             <span>Editar Dados</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-2xl p-6 flex gap-4 items-start bg-gradient-to-br from-blue-900/20 to-dark-card border border-blue-500/10 shadow-lg relative overflow-hidden">
                 <div class="absolute top-0 right-0 -mt-12 -mr-12 w-32 h-32 bg-brand-primary/20 rounded-full blur-3xl pointer-events-none"></div>
                 
                <div class="bg-brand-primary/20 p-3 rounded-xl flex-shrink-0">
                     <i class="fa-solid fa-shield-cat text-brand-primary text-xl"></i>
                </div>
                <div>
                    <h4 class="text-base font-bold text-slate-200 mb-1">Segurança e Privacidade</h4>
                    <p class="text-dark-text text-sm leading-relaxed">
                        Seus dados estão protegidos em nosso ambiente seguro. Para garantir a integridade das informações acadêmicas, a edição direta do perfil está temporariamente restrita e passa por validação da coordenação.
                    </p>
                </div>
            </div>

            <div class="h-12"></div>
        </div>
    </main>

</body>
</html>