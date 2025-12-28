<?php
/**
 * MEDINFOCUS - Sistema de Login Refatorado
 * Coordenador: Projeto MedInFocus
 * * Ajustes realizados:
 * 1. Validação do campo 'ativo' (bloqueia usuários pendentes).
 * 2. Atualização das variáveis de sessão para suportar Nível de Acesso (1, 2, 3).
 * 3. Inclusão da faculdade_id na sessão para filtros de representantes.
 */

session_start();

// 1. INCLUSÃO DA CONFIGURAÇÃO CENTRALIZADA
if (file_exists('php/config.php')) {
    require_once 'php/config.php';
} else {
    die("Erro Crítico: Arquivo de configuração não encontrado.");
}

// Variáveis de interface
$erroLogin = false;
$msgErro = '';

// 2. LÓGICA DE LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['password'] ?? '';

    if (!empty($email) && !empty($senha)) {
        try {
            if (!isset($pdo)) {
                throw new Exception("Falha na conexão com o banco.");
            }

            // BUSCA ATUALIZADA: Agora pegamos nivel_acesso, faculdade_id e ativo
            $stmt = $pdo->prepare("SELECT id, nome, senha_hash, nivel_acesso, faculdade_id, ativo FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. VERIFICAÇÃO DE CREDENCIAIS E STATUS
            if ($user && password_verify($senha, $user['senha_hash'])) {
                
                // CHECAGEM DE ATIVAÇÃO: Se estiver 0, o acesso é negado
                if ($user['ativo'] == 0) {
                    $erroLogin = true;
                    $msgErro = 'Sua conta está aguardando liberação do representante da sua turma.';
                } else {
                    // SUCESSO: Configuração da Sessão
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nome'];
                    $_SESSION['user_level'] = (int)$user['nivel_acesso']; // 1: Aluno, 2: Rep, 3: Admin
                    $_SESSION['faculdade_id'] = $user['faculdade_id'];
                    $_SESSION['loggedin'] = true;
                    
                    // Redireciona para a Dashboard
                    header('Location: index.php');
                    exit;
                }
            } else {
                $erroLogin = true;
                $msgErro = 'E-mail ou senha incorretos.';
            }

        } catch (Exception $e) {
            $erroLogin = true;
            $msgErro = 'Ocorreu um erro no sistema. Tente novamente em alguns minutos.'; 
        }
    } else {
        $erroLogin = true;
        $msgErro = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0f172a', primary: '#0284c7', secondary: '#0ea5e9', accent: '#10b981', surface: '#f8fafc' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</head>
<body class="bg-brand-surface font-sans antialiased h-screen w-full overflow-hidden">

    <div class="flex h-full w-full">
        
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-primary to-blue-900 p-12 text-white flex-col justify-between relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/10">
                        <i class="fa-solid fa-heart-pulse text-xl"></i>
                    </div>
                    <span class="font-bold text-2xl tracking-tight">MED<span class="text-sky-300">INFOCUS</span></span>
                </div>
            </div>
            <div class="relative z-10 max-w-lg">
                <h2 class="text-4xl font-bold mb-6 leading-tight">O futuro da medicina <br><span class="text-sky-300">está aqui.</span></h2>
                <p class="text-sky-100 text-lg">Acesse sua plataforma acadêmica para gerenciar estudos, arquivos e sua jornada profissional.</p>
            </div>
            <div class="relative z-10 text-xs text-sky-200/50">&copy; 2024 alunosdamedicina.com</div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-md">
                
                <div class="text-center md:text-left mb-8">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Bem-vindo</h2>
                    <p class="text-slate-500">Entre com suas credenciais acadêmicas.</p>
                </div>

                <?php if($erroLogin): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex items-center gap-3 animate-pulse">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $msgErro; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
                        <div class="relative group">
                            <i class="fa-regular fa-envelope absolute left-3 top-3.5 text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            <input type="email" name="email" required 
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none transition-all" 
                                placeholder="voce@email.com"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-1">
                            <label class="block text-sm font-medium text-slate-700">Senha</label>
                            <a href="esqueceu_senha.php" class="text-xs text-brand-primary font-semibold hover:underline">Esqueceu a senha?</a>
                        </div>
                        <div class="relative group">
                            <i class="fa-solid fa-lock absolute left-3 top-3.5 text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            <input type="password" id="password" name="password" required 
                                class="w-full pl-10 pr-10 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none transition-all" 
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600">
                                <i class="fa-regular fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-brand-primary text-white font-bold py-3 rounded-lg shadow-lg hover:bg-sky-700 transition-all flex items-center justify-center gap-2 transform active:scale-[0.98]">
                        Acessar Sistema <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center text-sm text-slate-500">
                    Ainda não possui conta? <a href="cadastro.php" class="text-brand-primary font-bold hover:underline">Criar cadastro</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>