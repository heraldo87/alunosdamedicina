<?php
/**
 * MEDINFOCUS - Listagem de Quizzes
 * Exibe os exames disponíveis e o desempenho do aluno.
 */

session_start();

// 1. Segurança
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. Importar Lógica
require_once 'php/quiz_logic.php';

// 3. Buscar Dados
$quizzes = listarQuizzesAtivos($pdo);
$usuarioId = $_SESSION['user_id'] ?? 0; // Garante que temos o ID
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes e Testes - MEDINFOCUS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0b0f1a; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        
        .quiz-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .quiz-card:hover {
            transform: translateY(-5px);
            border-color: rgba(6, 182, 212, 0.5);
            background: rgba(30, 41, 59, 0.7);
        }
    </style>
</head>
<body class="text-slate-300 h-screen flex overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto custom-scrollbar">
        
        <header class="pt-10 pb-8 px-6 md:px-12 border-b border-slate-800/50">
            <div class="flex items-center gap-4 mb-4">
                <a href="index.php" class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight">Central de Testes</h1>
                    <p class="text-slate-500 text-sm">Pratique e avalie seu conhecimento.</p>
                </div>
            </div>
        </header>

        <div class="p-6 md:p-12">
            <?php if (empty($quizzes)): ?>
                <div class="flex flex-col items-center justify-center py-20 text-center opacity-50">
                    <i class="fa-regular fa-folder-open text-6xl mb-4 text-slate-600"></i>
                    <p class="text-xl font-medium">Nenhum quiz disponível no momento.</p>
                </div>
            <?php else: ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($quizzes as $quiz): ?>
                        <?php 
                            // Cores de Dificuldade
                            $badgeColor = match($quiz['nivel']) {
                                'facil' => 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20',
                                'dificil' => 'text-rose-400 bg-rose-400/10 border-rose-400/20',
                                default => 'text-amber-400 bg-amber-400/10 border-amber-400/20'
                            };
                            
                            // Buscar Desempenho (NOVIDADE DO MICRO PASSO 8)
                            $stats = buscarMelhorDesempenho($pdo, $quiz['id'], $usuarioId);
                            $jaFez = ($stats && $stats['tentativas'] > 0);
                            $nota = $jaFez ? floatval($stats['melhor_nota']) : 0;
                            
                            // Cor da Nota
                            $notaColor = 'text-slate-400';
                            if($jaFez) {
                                if($nota >= 7) $notaColor = 'text-emerald-400';
                                elseif($nota >= 5) $notaColor = 'text-amber-400';
                                else $notaColor = 'text-rose-400';
                            }
                        ?>
                        
                        <div class="quiz-card rounded-3xl p-6 flex flex-col h-full relative group">
                            
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 bg-cyan-500/10 text-cyan-500 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform duration-300">
                                    <i class="fa-solid fa-microscope"></i>
                                </div>
                                <div class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $badgeColor; ?>">
                                    <?php echo ucfirst($quiz['nivel']); ?>
                                </div>
                            </div>

                            <h3 class="text-xl font-bold text-white mb-2 line-clamp-2">
                                <?php echo htmlspecialchars($quiz['titulo']); ?>
                            </h3>
                            
                            <?php if ($jaFez): ?>
                                <div class="mb-6 p-3 rounded-xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between">
                                    <span class="text-xs text-slate-500 font-bold uppercase">Sua Melhor Nota</span>
                                    <span class="text-lg font-black <?php echo $notaColor; ?>"><?php echo number_format($nota, 1); ?></span>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-slate-400 mb-6 flex-1 line-clamp-3">
                                    <?php echo htmlspecialchars($quiz['descricao']); ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-auto pt-4 border-t border-slate-700/50 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                                    <?php if($jaFez): ?>
                                        <i class="fa-solid fa-rotate-right"></i>
                                        <span><?php echo $stats['tentativas']; ?> tentativa(s)</span>
                                    <?php else: ?>
                                        <i class="fa-regular fa-clock"></i>
                                        <span>~5 min</span>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="jogar_quiz.php?id=<?php echo $quiz['id']; ?>" class="bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold py-2.5 px-5 rounded-xl transition-all shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/40 uppercase tracking-wide">
                                    <?php echo $jaFez ? 'Refazer' : 'Iniciar'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

    </main>
</body>
</html>