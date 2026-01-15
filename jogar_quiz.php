<?php
/**
 * MEDINFOCUS - Interface de Jogo (Quiz Player)
 * Carrega todas as questões e gerencia a navegação via JS.
 */

session_start();
require_once 'php/quiz_logic.php';

// 1. Verificações de Segurança
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: quizzes.php');
    exit;
}

$quizId = intval($_GET['id']);
$quizInfo = buscarInfoQuiz($pdo, $quizId);
$perguntas = buscarPerguntasQuiz($pdo, $quizId);

// Se quiz não existe ou não tem perguntas
if (!$quizInfo || empty($perguntas)) {
    echo "<script>alert('Quiz indisponível ou vazio.'); window.location.href='quizzes.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogando: <?php echo htmlspecialchars($quizInfo['titulo']); ?></title>
    
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
        .glass-panel {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .option-btn.selected {
            border-color: #06b6d4; /* Cyan */
            background-color: rgba(6, 182, 212, 0.1);
            color: white;
        }
    </style>
</head>
<body class="text-slate-300 h-screen flex flex-col overflow-hidden">

    <header class="h-16 border-b border-slate-800/50 flex items-center justify-between px-6 bg-slate-900/50">
        <div class="flex items-center gap-3">
            <a href="quizzes.php" class="text-slate-500 hover:text-white transition-colors"><i class="fa-solid fa-xmark text-xl"></i></a>
            <span class="font-bold text-white tracking-wide truncate max-w-[200px] md:max-w-md"><?php echo htmlspecialchars($quizInfo['titulo']); ?></span>
        </div>
        <div class="text-xs font-bold text-cyan-500 uppercase tracking-widest">Modo Prova</div>
    </header>

    <main class="flex-1 flex flex-col items-center justify-center p-4 md:p-8 relative">
        
        <div class="absolute top-0 left-0 w-full h-1 bg-slate-800">
            <div id="progressBar" class="h-full bg-cyan-500 transition-all duration-500" style="width: 0%"></div>
        </div>

        <div class="w-full max-w-3xl">
            <form id="quizForm" action="resultado_quiz.php" method="POST">
                <input type="hidden" name="quiz_id" value="<?php echo $quizId; ?>">
                
                <?php foreach ($perguntas as $index => $p): ?>
                    <div class="question-slide hidden" data-index="<?php echo $index; ?>">
                        
                        <div class="mb-6 text-sm font-bold text-slate-500 uppercase tracking-widest">
                            Questão <span class="text-white text-lg"><?php echo $index + 1; ?></span> / <?php echo count($perguntas); ?>
                        </div>

                        <h2 class="text-2xl md:text-3xl font-bold text-white mb-8 leading-relaxed">
                            <?php echo htmlspecialchars($p['texto_pergunta']); ?>
                        </h2>

                        <div class="space-y-3">
                            <?php foreach ($p['alternativas'] as $alt): ?>
                                <label class="option-btn block p-4 rounded-xl border border-slate-700 bg-slate-800/30 hover:bg-slate-700/50 cursor-pointer transition-all group">
                                    <div class="flex items-center">
                                        <input type="radio" name="respostas[<?php echo $p['id']; ?>]" value="<?php echo $alt['id']; ?>" class="hidden" onchange="selectOption(this)">
                                        <div class="w-6 h-6 rounded-full border-2 border-slate-600 mr-4 flex items-center justify-center group-hover:border-slate-400">
                                            <div class="w-2.5 h-2.5 rounded-full bg-cyan-500 opacity-0 transition-opacity check-dot"></div>
                                        </div>
                                        <span class="text-lg"><?php echo htmlspecialchars($alt['texto_alternativa']); ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="mt-10 flex justify-between items-center">
                    <button type="button" id="btnPrev" onclick="changeSlide(-1)" class="px-6 py-3 rounded-xl text-slate-400 font-bold hover:text-white hover:bg-slate-800/50 transition-colors disabled:opacity-0">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Anterior
                    </button>
                    
                    <button type="button" id="btnNext" onclick="changeSlide(1)" class="px-8 py-3 rounded-xl bg-cyan-600 text-white font-bold shadow-lg shadow-cyan-500/20 hover:bg-cyan-500 hover:shadow-cyan-500/40 transition-all">
                        Próxima <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                    
                    <button type="submit" id="btnFinish" class="hidden px-8 py-3 rounded-xl bg-emerald-600 text-white font-bold shadow-lg shadow-emerald-500/20 hover:bg-emerald-500 hover:shadow-emerald-500/40 transition-all">
                        Finalizar Prova <i class="fa-solid fa-check ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.question-slide');
        const totalSlides = slides.length;
        const btnNext = document.getElementById('btnNext');
        const btnPrev = document.getElementById('btnPrev');
        const btnFinish = document.getElementById('btnFinish');
        const progressBar = document.getElementById('progressBar');

        function updateUI() {
            // Mostrar/Ocultar Slides
            slides.forEach((slide, index) => {
                if (index === currentSlide) {
                    slide.classList.remove('hidden');
                    // Animação de entrada
                    slide.classList.add('animate-[fadeIn_0.5s_ease-out]');
                } else {
                    slide.classList.add('hidden');
                    slide.classList.remove('animate-[fadeIn_0.5s_ease-out]');
                }
            });

            // Atualizar Botões
            btnPrev.disabled = currentSlide === 0;
            
            if (currentSlide === totalSlides - 1) {
                btnNext.classList.add('hidden');
                btnFinish.classList.remove('hidden');
            } else {
                btnNext.classList.remove('hidden');
                btnFinish.classList.add('hidden');
            }

            // Atualizar Barra de Progresso
            const progress = ((currentSlide + 1) / totalSlides) * 100;
            progressBar.style.width = `${progress}%`;
        }

        function changeSlide(direction) {
            // Validação simples: exigir resposta antes de avançar (opcional, removida para fluidez)
            currentSlide += direction;
            updateUI();
        }

        function selectOption(input) {
            // Remove estilo de todos os botões do slide atual
            const currentSlideEl = slides[currentSlide];
            const options = currentSlideEl.querySelectorAll('.option-btn');
            options.forEach(btn => {
                btn.classList.remove('selected');
                btn.querySelector('.check-dot').classList.add('opacity-0');
                btn.querySelector('.w-6').classList.remove('border-cyan-500');
                btn.querySelector('.w-6').classList.add('border-slate-600');
            });

            // Adiciona estilo ao selecionado
            const parentLabel = input.closest('label');
            parentLabel.classList.add('selected');
            parentLabel.querySelector('.check-dot').classList.remove('opacity-0');
            parentLabel.querySelector('.w-6').classList.remove('border-slate-600');
            parentLabel.querySelector('.w-6').classList.add('border-cyan-500');
        }

        // Inicializar
        updateUI();
    </script>
</body>
</html>