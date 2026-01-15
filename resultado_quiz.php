<?php
/**
 * MEDINFOCUS - Processamento de Resultados
 * Calcula a nota, salva no histórico e exibe o feedback.
 */

session_start();
require_once 'php/quiz_logic.php';

// 1. Segurança e Validação
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quiz_id'])) {
    header('Location: quizzes.php');
    exit;
}

$quizId = intval($_POST['quiz_id']);
$respostasEnviadas = $_POST['respostas'] ?? []; // Array [pergunta_id => alternativa_id]
$usuarioId = $_SESSION['user_id'] ?? 0; // Assumindo que você tem user_id na sessão. Se não tiver, ajuste.

// 2. Buscar Gabarito
$perguntas = buscarPerguntasQuiz($pdo, $quizId);
$quizInfo = buscarInfoQuiz($pdo, $quizId);

// 3. Calcular Nota
$totalQuestoes = count($perguntas);
$acertos = 0;
$detalhesResultado = [];

foreach ($perguntas as $pergunta) {
    $pId = $pergunta['id'];
    $alternativaEscolhida = intval($respostasEnviadas[$pId] ?? 0);
    $corretaId = 0;
    
    // Descobrir qual era a correta
    foreach ($pergunta['alternativas'] as $alt) {
        if ($alt['e_correta'] == 1) {
            $corretaId = $alt['id'];
            break;
        }
    }

    $acertou = ($alternativaEscolhida === $corretaId);
    if ($acertou) {
        $acertos++;
    }

    // Preparar dados para exibição
    $detalhesResultado[] = [
        'pergunta' => $pergunta['texto_pergunta'],
        'explicacao' => $pergunta['explicacao_resposta'],
        'acertou' => $acertou,
        'escolha_usuario' => $alternativaEscolhida, // ID
        'alternativas' => $pergunta['alternativas'] // Array completo para mostrar textos
    ];
}

// Nota de 0 a 10
$notaFinal = ($totalQuestoes > 0) ? ($acertos / $totalQuestoes) * 10 : 0;

// 4. Salvar no Histórico (Opcional por enquanto, se tiver a tabela e user_id)
// Se sua sessão não tem user_id, comente o bloco try/catch abaixo para não dar erro.
if ($usuarioId > 0) {
    try {
        $stmt = $pdo->prepare("INSERT INTO quiz_historico (quiz_id, usuario_id, acertos, total_questoes, nota_final) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$quizId, $usuarioId, $acertos, $totalQuestoes, $notaFinal]);
    } catch (Exception $e) {
        // Ignora erro de log por enquanto
    }
}

// 5. Definições Visuais Baseadas na Nota
if ($notaFinal >= 7) {
    $corTitulo = 'text-emerald-500';
    $msgTitulo = 'Excelente!';
    $icone = 'fa-trophy';
    $corBg = 'bg-emerald-500/10';
} elseif ($notaFinal >= 5) {
    $corTitulo = 'text-amber-500';
    $msgTitulo = 'Bom trabalho!';
    $icone = 'fa-star-half-stroke';
    $corBg = 'bg-amber-500/10';
} else {
    $corTitulo = 'text-rose-500';
    $msgTitulo = 'Vamos revisar?';
    $icone = 'fa-book-medical';
    $corBg = 'bg-rose-500/10';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - <?php echo htmlspecialchars($quizInfo['titulo']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { dark: '#0b0f1a' } } } } }
    </script>
    <style>body { font-family: 'Inter', sans-serif; background-color: #0b0f1a; }</style>
</head>
<body class="text-slate-300 min-h-screen flex flex-col items-center py-10 px-4">

    <div class="w-full max-w-2xl bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 md:p-12 text-center shadow-2xl relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 <?php echo str_replace('/10', '', $corBg); ?>"></div>

        <div class="w-24 h-24 mx-auto <?php echo $corBg; ?> rounded-full flex items-center justify-center mb-6">
            <i class="fa-solid <?php echo $icone; ?> text-4xl <?php echo $corTitulo; ?>"></i>
        </div>

        <h1 class="text-4xl font-black text-white mb-2"><?php echo $msgTitulo; ?></h1>
        <p class="text-slate-500 mb-8 uppercase tracking-widest text-sm font-bold">Você concluiu o teste</p>

        <div class="flex justify-center gap-8 mb-10">
            <div class="text-center">
                <span class="block text-3xl font-bold text-white"><?php echo $acertos; ?></span>
                <span class="text-xs text-slate-500 uppercase">Acertos</span>
            </div>
            <div class="text-center">
                <span class="block text-3xl font-bold <?php echo $corTitulo; ?>"><?php echo number_format($notaFinal, 1); ?></span>
                <span class="text-xs text-slate-500 uppercase">Nota Final</span>
            </div>
            <div class="text-center">
                <span class="block text-3xl font-bold text-white"><?php echo $totalQuestoes; ?></span>
                <span class="text-xs text-slate-500 uppercase">Questões</span>
            </div>
        </div>

        <a href="quizzes.php" class="inline-block bg-slate-800 hover:bg-slate-700 text-white font-bold py-3 px-8 rounded-xl transition-all">
            Voltar para Lista
        </a>
    </div>

    <div class="w-full max-w-2xl mt-8 space-y-4">
        <h3 class="text-lg font-bold text-slate-400 px-2 uppercase tracking-wider">Gabarito Comentado</h3>

        <?php foreach ($detalhesResultado as $idx => $item): ?>
            <div class="bg-slate-900/40 border border-slate-800 rounded-2xl p-6 <?php echo $item['acertou'] ? 'border-l-4 border-l-emerald-500' : 'border-l-4 border-l-rose-500'; ?>">
                
                <div class="flex items-start gap-4 mb-4">
                    <span class="w-8 h-8 flex-shrink-0 rounded-lg bg-slate-800 flex items-center justify-center font-bold text-sm text-slate-400">
                        <?php echo $idx + 1; ?>
                    </span>
                    <h4 class="text-white font-medium text-lg leading-snug">
                        <?php echo htmlspecialchars($item['pergunta']); ?>
                    </h4>
                </div>

                <div class="pl-12 space-y-2 mb-4">
                    <?php 
                    foreach ($item['alternativas'] as $alt) {
                        $isUserChoice = ($alt['id'] == $item['escolha_usuario']);
                        $isCorrect = ($alt['e_correta'] == 1);
                        
                        // Lógica de exibição das classes CSS
                        $class = "text-slate-500";
                        $icon = "";

                        if ($isCorrect) {
                            $class = "text-emerald-400 font-bold";
                            $icon = "<i class='fa-solid fa-check ml-2'></i>";
                        } elseif ($isUserChoice && !$isCorrect) {
                            $class = "text-rose-400 font-bold line-through decoration-2";
                            $icon = "<i class='fa-solid fa-xmark ml-2'></i>";
                        } elseif ($isUserChoice && $isCorrect) {
                            // Já tratado no if($isCorrect), mas reforça
                            $class = "text-emerald-400 font-bold";
                        }

                        // Só mostra se for a correta ou a escolhida (para não poluir)
                        if ($isUserChoice || $isCorrect) {
                            echo "<div class='$class text-sm'>".htmlspecialchars($alt['texto_alternativa'])." $icon</div>";
                        }
                    }
                    ?>
                </div>

                <?php if (!empty($item['explicacao'])): ?>
                    <div class="pl-12 mt-4 pt-4 border-t border-slate-800/50">
                        <p class="text-sm text-slate-400 italic">
                            <i class="fa-solid fa-quote-left mr-2 text-slate-600"></i>
                            <?php echo htmlspecialchars($item['explicacao']); ?>
                        </p>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>