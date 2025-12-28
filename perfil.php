<?php
/**
 * MEDINFOCUS - Perfil do Usuário (Versão 2.0)
 * Atualizado para nova estrutura de Matrícula e Universidade.
 */

session_start();
require_once 'php/config.php';

// 1. PROTEÇÃO DA PÁGINA
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. BUSCA DE DADOS AVANÇADA (Com Join para pegar o nome da Faculdade)
try {
    $sql = "SELECT u.nome, u.email, u.matricula, u.nivel_acesso, u.data_criacao, f.nome as faculdade_nome, f.sigla as faculdade_sigla 
            FROM usuarios u
            INNER JOIN universidades f ON u.faculdade_id = f.id
            WHERE u.id = :id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Usuário não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao carregar perfil: " . $e->getMessage());
}

// 3. MAPEAMENTO DE BADGES (Baseado no nivel_acesso: 1, 2, 3)
$labels = [
    3 => ['txt' => 'Administrador', 'class' => 'bg-red-500/20 text-red-300 border border-red-500/30', 'icon' => 'fa-shield-halved'],
    2 => ['txt' => 'Representante', 'class' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30', 'icon' => 'fa-user-graduate'],
    1 => ['txt' => 'Acadêmico', 'class' => 'bg-sky-500/20 text-sky-300 border border-sky-500/30', 'icon' => 'fa-graduation-cap']
];

$badge = $labels[$user['nivel_acesso']] ?? $labels[1];
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
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        dark: {
                            base: '#0f172a',   
                            card: '#1e293b',   
                            border: '#334155', 
                            text: '#94a3b8',   
                            heading: '#f1f5f9' 
                        },
                        brand: { primary: '#0ea5e9' }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="bg-dark-base flex font-sans text-dark-text antialiased">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 h-screen overflow-y-auto bg-dark-base relative z-0">
        
        <header class="sticky top-0 z-10 bg-dark-base/80 backdrop-blur-md border-b border-dark-border px-8 py-6">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-2xl font-bold text-dark-heading tracking-tight">Meu Perfil</h1>
                <p class="text-dark-text text-sm mt-1">Dados acadêmicos sincronizados com o servidor.</p>
            </div>
        </header>

        <div class="p-8 max-w-4xl mx-auto">
            <div class="bg-dark-card rounded-3xl shadow-xl shadow-black/20 border border-dark-border overflow-hidden">
                
                <div class="h-32 bg-gradient-to-r from-slate-900 via-blue-900 to-dark-base relative">
                     <div class="absolute inset-0 bg-grid-white/[0.05] bg-[length:20px_20px]"></div> 
                </div>
                
                <div class="px-8 pb-8 relative">
                    <div class="flex flex-col md:flex-row items-start md:items-end -mt-12 mb-8 gap-6">
                        <div class="w-28 h-28 rounded-3xl bg-dark-card p-1.5 shadow-2xl ring-4 ring-dark-base">
                            <div class="w-full h-full rounded-2xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center text-white text-3xl font-bold">
                                <?php echo strtoupper(substr($user['nome'], 0, 2)); ?>
                            </div>
                        </div>

                        <div class="flex-1">
                            <h2 class="text-3xl font-bold text-white mb-2"><?php echo htmlspecialchars($user['nome']); ?></h2>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?php echo $badge['class']; ?> flex items-center gap-2">
                                    <i class="fa-solid <?php echo $badge['icon']; ?>"></i> <?php echo $badge['txt']; ?>
                                </span>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Verificado
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-8 border-t border-dark-border/50">
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-dark-text/40 uppercase tracking-[0.2em] mb-1">E-mail Institucional</label>
                                <p class="text-slate-200 font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-dark-text/40 uppercase tracking-[0.2em] mb-1">Universidade / Instituição</label>
                                <p class="text-slate-200 font-medium">
                                    <span class="text-brand-primary font-bold"><?php echo htmlspecialchars($user['faculdade_sigla']); ?></span> - <?php echo htmlspecialchars($user['faculdade_nome']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-dark-text/40 uppercase tracking-[0.2em] mb-1">Matrícula Acadêmica</label>
                                <p class="text-slate-200 font-medium font-mono"><?php echo htmlspecialchars($user['matricula']); ?></p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-dark-text/40 uppercase tracking-[0.2em] mb-1">Membro do MedInFocus desde</label>
                                <p class="text-slate-200 font-medium"><?php echo date('d/m/Y', strtotime($user['data_criacao'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-6 border-t border-dark-border/50 flex gap-4">
                        <button disabled class="px-5 py-2.5 rounded-xl bg-dark-base text-xs font-bold text-dark-text border border-dark-border opacity-50 cursor-not-allowed">
                            <i class="fa-solid fa-lock mr-2"></i> Alterar Senha
                        </button>
                        <button disabled class="px-5 py-2.5 rounded-xl bg-dark-base text-xs font-bold text-dark-text border border-dark-border opacity-50 cursor-not-allowed">
                            <i class="fa-solid fa-pen mr-2"></i> Editar Perfil
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-blue-500/5 border border-blue-500/10 rounded-2xl p-6 flex gap-4 items-start">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400 flex-shrink-0">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-200 mb-1">Segurança de Dados</h4>
                    <p class="text-xs text-dark-text leading-relaxed">
                        Para garantir a validade dos registros acadêmicos, alterações de nome, matrícula ou universidade devem ser solicitadas diretamente ao representante da sua turma ou através do suporte técnico.
                    </p>
                </div>
            </div>

        </div>
    </main>
</body>
</html>