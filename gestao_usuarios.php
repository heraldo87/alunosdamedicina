<?php
/**
 * MEDINFOCUS - Gestão de Liberações
 * Filtros: Admin (Ver todos) | Representante (Ver apenas sua faculdade)
 */

session_start();
require_once 'php/config.php';

// 1. PROTEÇÃO DE ACESSO (Apenas Nível 2 e 3)
if (!isset($_SESSION['loggedin']) || $_SESSION['user_level'] < 2) {
    header('Location: index.php');
    exit;
}

$nivelLogado = $_SESSION['user_level'];
$faculdadeLogada = $_SESSION['faculdade_id'] ?? 0;
$msgFeedback = '';

// 2. LÓGICA DE ATIVAÇÃO DE USUÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ativar_id'])) {
    $idParaAtivar = filter_input(INPUT_POST, 'ativar_id', FILTER_VALIDATE_INT);
    
    try {
        // Segurança extra: Se for representante, só pode ativar se o aluno for da mesma faculdade
        if ($nivelLogado == 2) {
            $stmtAtivar = $pdo->prepare("UPDATE usuarios SET ativo = 1 WHERE id = :id AND faculdade_id = :fac_id");
            $stmtAtivar->execute(['id' => $idParaAtivar, 'fac_id' => $faculdadeLogada]);
        } else {
            // Admin ativa qualquer um
            $stmtAtivar = $pdo->prepare("UPDATE usuarios SET ativo = 1 WHERE id = :id");
            $stmtAtivar->execute(['id' => $idParaAtivar]);
        }
        
        if ($stmtAtivar->rowCount() > 0) {
            $msgFeedback = "Usuário liberado com sucesso!";
        }
    } catch (PDOException $e) {
        $msgFeedback = "Erro ao processar liberação.";
    }
}

// 3. BUSCA DE USUÁRIOS PENDENTES (ativo = 0)
try {
    $sqlBase = "SELECT u.id, u.nome, u.email, u.matricula, u.data_criacao, f.sigla as faculdade_sigla 
                FROM usuarios u 
                JOIN universidades f ON u.faculdade_id = f.id 
                WHERE u.ativo = 0";

    if ($nivelLogado == 2) {
        // Representante: Filtra pela faculdade dele
        $sqlBase .= " AND u.faculdade_id = :fac_id";
        $stmtUsers = $pdo->prepare($sqlBase);
        $stmtUsers->execute(['fac_id' => $faculdadeLogada]);
    } else {
        // Admin: Vê todos
        $stmtUsers = $pdo->prepare($sqlBase);
        $stmtUsers->execute();
    }
    
    $pendentes = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendentes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liberações - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { base: '#0f172a', card: '#1e293b', border: '#334155', text: '#94a3b8', heading: '#f1f5f9' },
                        brand: { primary: '#0ea5e9' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-dark-base flex font-sans text-dark-text antialiased">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 h-screen overflow-y-auto bg-dark-base">
        
        <header class="sticky top-0 z-10 bg-dark-base/80 backdrop-blur-md border-b border-dark-border px-8 py-6">
            <div class="max-w-6xl mx-auto flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-dark-heading tracking-tight">Liberações de Acesso</h1>
                    <p class="text-dark-text text-sm mt-1">
                        <?php echo ($nivelLogado == 3) ? 'Gestão Global de Novos Alunos' : 'Alunos pendentes da sua faculdade'; ?>
                    </p>
                </div>
                <div class="bg-emerald-500/10 text-emerald-400 px-4 py-2 rounded-xl border border-emerald-500/20 text-xs font-bold">
                    <i class="fa-solid fa-users mr-2"></i> <?php echo count($pendentes); ?> Pendentes
                </div>
            </div>
        </header>

        <div class="p-8 max-w-6xl mx-auto">
            
            <?php if ($msgFeedback): ?>
            <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center gap-3 animate-bounce">
                <i class="fa-solid fa-check-circle"></i>
                <span class="text-sm font-medium"><?php echo $msgFeedback; ?></span>
            </div>
            <?php endif; ?>

            <div class="bg-dark-card rounded-3xl border border-dark-border overflow-hidden shadow-2xl">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-900/50 border-b border-dark-border">
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-dark-text/60">Acadêmico</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-dark-text/60">Matrícula</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-dark-text/60">Faculdade</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-dark-text/60">Data Cadastro</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-dark-text/60 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border/30">
                        <?php if (empty($pendentes)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center text-dark-text/40">
                                <i class="fa-solid fa-user-slash text-4xl mb-4 block"></i>
                                <span class="text-sm">Não há novas solicitações de acesso no momento.</span>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($pendentes as $aluno): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors group">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-slate-700 flex items-center justify-center text-xs font-bold text-white uppercase">
                                            <?php echo substr($aluno['nome'], 0, 1); ?>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-slate-200 font-semibold text-sm"><?php echo htmlspecialchars($aluno['nome']); ?></span>
                                            <span class="text-xs text-dark-text/60"><?php echo htmlspecialchars($aluno['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-sm font-mono text-slate-400"><?php echo htmlspecialchars($aluno['matricula']); ?></td>
                                <td class="px-6 py-5">
                                    <span class="px-2 py-1 rounded bg-brand-primary/10 text-brand-primary text-[10px] font-black uppercase">
                                        <?php echo htmlspecialchars($aluno['faculdade_sigla']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-xs text-dark-text/60">
                                    <?php echo date('d/m/Y H:i', strtotime($aluno['data_criacao'])); ?>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <form method="POST" onsubmit="return confirm('Liberar acesso para este acadêmico?');">
                                        <input type="hidden" name="ativar_id" value="<?php echo $aluno['id']; ?>">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black uppercase px-4 py-2 rounded-xl transition-all shadow-lg shadow-emerald-900/20 active:scale-95">
                                            <i class="fa-solid fa-user-plus mr-1"></i> Liberar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex gap-4 p-6 rounded-2xl bg-amber-500/5 border border-amber-500/10">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-1"></i>
                <div class="text-xs text-dark-text leading-relaxed">
                    <p class="font-bold text-slate-200 mb-1">Atenção Representante:</p>
                    Verifique se o nome e a matrícula coincidem com a lista oficial da sua turma antes de liberar o acesso. Uma vez liberado, o aluno terá acesso total aos conteúdos protegidos da plataforma.
                </div>
            </div>

        </div>
    </main>
</body>
</html>