<?php
/**
 * MEDINFOCUS - Sidebar Refatorada (Versão 2.2)
 * Coordenador: Projeto MedInFocus
 * * Atualizações:
 * 1. Adicionado botão para Página Principal.
 * 2. Removida opção de Configurar Pastas.
 * 3. Mantida lógica de níveis (1: Aluno, 2: Rep, 3: Admin).
 */

// 1. Configurações de Perfil e Lógica de Sessão
$nivelAcesso = $_SESSION['user_level'] ?? 1; 
$nomeUsuario = $_SESSION['user_name'] ?? 'Usuário';

// Mapeamento de Cores e Rótulos por Nível
$perfisConfig = [
    3 => [
        'label' => 'Administrador',
        'color' => 'bg-red-600',
        'icon' => 'fa-shield-medical'
    ],
    2 => [
        'label' => 'Representante',
        'color' => 'bg-amber-600',
        'icon' => 'fa-user-graduate'
    ],
    1 => [
        'label' => 'Acadêmico',
        'color' => 'bg-brand-primary',
        'icon' => 'fa-user-md'
    ]
];

$config = $perfisConfig[$nivelAcesso] ?? $perfisConfig[1];

// Simulação de Contadores
$avisosNaoLidos = 2;
$liberacoesPendentes = ($nivelAcesso >= 2) ? 5 : 0; 
?>

<aside class="hidden md:flex flex-col w-72 bg-brand-dark text-white border-r border-slate-800 h-screen sticky top-0 overflow-hidden">
    
    <div class="h-20 flex items-center px-6 border-b border-slate-800/50 bg-slate-900/20">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-brand-primary rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-primary/20">
                <i class="fa-solid fa-staff-snake text-lg"></i>
            </div>
            <div class="flex flex-col">
                <span class="font-bold text-lg leading-none tracking-tight">MED<span class="text-brand-primary">INFOCUS</span></span>
                <span class="text-[9px] text-slate-500 font-black uppercase tracking-[0.2em] mt-1">Core System</span>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 overflow-y-auto space-y-8 custom-scrollbar">

        <div>
            <p class="px-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Minha Conta</p>
            <div class="space-y-1">
                <a href="index.php" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all group">
                    <i class="fa-solid fa-house w-8 text-center mr-2 text-lg group-hover:text-brand-primary"></i>
                    <span>Página Principal</span>
                </a>

                <a href="perfil.php" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all group">
                    <div class="w-8 h-8 rounded-full <?php echo $config['color']; ?> flex items-center justify-center mr-3 text-[10px] font-bold border border-white/10 group-hover:scale-110 transition-transform">
                        <?php echo strtoupper(substr(htmlspecialchars($nomeUsuario), 0, 2)); ?>
                    </div>
                    <span>Meu Perfil</span>
                    <i class="fa-solid fa-circle-user ml-auto opacity-0 group-hover:opacity-100 transition-opacity text-brand-primary"></i>
                </a>

                <a href="calendario.php" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all group">
                    <i class="fa-solid fa-calendar-day w-8 text-center mr-2 text-lg group-hover:text-brand-primary"></i>
                    <span>Calendário</span>
                </a>

                <a href="avisos.php" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all group">
                    <i class="fa-solid fa-bullhorn w-8 text-center mr-2 text-lg group-hover:text-brand-primary"></i>
                    <span>Avisos</span>
                    <?php if($avisosNaoLidos > 0): ?>
                        <span class="ml-auto w-5 h-5 flex items-center justify-center bg-blue-600 text-[10px] font-bold rounded-full text-white ring-4 ring-brand-dark"><?php echo $avisosNaoLidos; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <div>
            <p class="px-3 text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Workspace</p>
            <div class="space-y-1">
                <a href="chat_ia.php" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all group">
                    <i class="fa-solid fa-brain w-8 text-center mr-2 text-lg group-hover:text-brand-primary"></i>
                    <span>Mentor IA</span>
                </a>
                <a href="repositorio.php" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all group">
                    <i class="fa-solid fa-folder-tree w-8 text-center mr-2 text-lg group-hover:text-amber-500"></i>
                    <span>Arquivos</span>
                </a>
            </div>
        </div>

        <?php if ($nivelAcesso >= 2): ?>
        <div>
            <p class="px-3 text-[10px] font-black text-amber-500 uppercase tracking-widest mb-4 italic">Administração de Sistema</p>
            <div class="space-y-1">
                <a href="gestao_usuarios.php" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-all group">
                    <i class="fa-solid fa-user-check w-8 text-center mr-2 text-lg group-hover:text-emerald-500"></i>
                    <span>Liberações</span>
                    <?php if($liberacoesPendentes > 0): ?>
                        <span class="ml-auto px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-[10px] font-bold rounded-lg border border-emerald-500/20">
                            <?php echo $liberacoesPendentes; ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                </div>
        </div>
        <?php endif; ?>

    </nav>

    <div class="p-4 mt-auto border-t border-slate-800/50 bg-slate-900/30">
        <div class="flex items-center gap-3 px-2 mb-4">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Sincronizado com Servidor</span>
        </div>
        
        <a href="php/logout.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl text-xs font-bold text-slate-500 hover:text-rose-500 hover:bg-rose-500/10 transition-all group">
            <i class="fa-solid fa-power-off group-hover:rotate-90 transition-transform"></i>
            Encerrar Sessão
        </a>
    </div>
</aside>