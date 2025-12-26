<?php
/**
 * MEDINFOCUS - Single File Login System
 * Refatorado para usar configuração centralizada e correção de rotas.
 */

session_start();

// 1. INCLUSÃO DA CONFIGURAÇÃO (Conexão com Banco de Dados)
// O arquivo php/config.php deve conter a criação da variável $pdo
if (file_exists('php/config.php')) {
    require_once 'php/config.php';
} else {
    // Para o script se o config não for achado, evitando erros em cascata
    die("Erro Crítico: Arquivo de configuração não encontrado em 'php/config.php'.");
}

// Variáveis de controle de interface
$erroLogin = false;
$msgErro = '';

// 2. LÓGICA DE LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Filtra e sanitiza inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['password'] ?? ''; // Operador de coalescência nula para evitar warning se vazio

    if (!empty($email) && !empty($senha)) {
        try {
            // Verifica se a conexão $pdo foi criada corretamente no config.php
            if (!isset($pdo)) {
                throw new Exception("A conexão com o banco de dados não foi estabelecida.");
            }

            // Busca usuário (Query Preparada)
            $stmt = $pdo->prepare("SELECT id, nome, senha_hash, tipo_usuario FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica Senha
            if ($user && password_verify($senha, $user['senha_hash'])) {
                // Sucesso: Regenera ID da sessão para segurança
                session_regenerate_id(true);
                
                // Define variáveis de sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo_usuario'];
                $_SESSION['loggedin'] = true;
                
                // CORREÇÃO: Redireciona para a Dashboard Principal (index.php)
                header('Location: index.php');
                exit;
            } else {
                $erroLogin = true;
                $msgErro = 'Credenciais inválidas. Verifique seu email e senha.';
            }

        } catch (Exception $e) {
            $erroLogin = true;
            // Em produção, evite mostrar $e->getMessage() para o usuário final por segurança
            $msgErro = 'Erro ao conectar ao sistema. Tente novamente mais tarde.'; 
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0f172a',    // Slate 900
                            primary: '#0284c7', // Sky 600
                            secondary: '#0ea5e9', // Sky 500
                            accent: '#10b981',  // Emerald 500
                            surface: '#f8fafc', // Slate 50
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Merriweather', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-surface font-sans antialiased h-screen w-full overflow-hidden">

    <div class="flex h-full w-full">
        
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-primary to-blue-900 p-12 text-white flex-col justify-between relative overflow-hidden">
            
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-full h-full opacity-10 pointer-events-none">
                 <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white" />
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center text-white border border-white/10">
                        <i class="fa-solid fa-heart-pulse text-xl"></i>
                    </div>
                    <span class="font-bold text-2xl tracking-tight">MED<span class="text-sky-300">INFOCUS</span></span>
                </div>
                <p class="text-sky-100 text-sm ml-1">Plataforma de Gestão Acadêmica</p>
            </div>

            <div class="relative z-10 max-w-lg">
                <h2 class="text-4xl font-bold mb-6 leading-tight">
                    O futuro da sua carreira médica <br>
                    <span class="text-sky-300">começa aqui.</span>
                </h2>
                <p class="text-sky-100 text-lg leading-relaxed mb-8">
                    Acesse materiais, acompanhe notas e gerencie seus estudos em um único lugar, projetado para sua excelência.
                </p>
            </div>

            <div class="relative z-10 text-xs text-sky-200/50">
                &copy; 2024 alunosdamedicina.com
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white overflow-y-auto">
            <div class="w-full max-w-md">
                
                <div class="lg:hidden text-center mb-10">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-brand-primary/10 text-brand-primary mb-3">
                        <i class="fa-solid fa-heart-pulse text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900">MED<span class="text-brand-primary">INFOCUS</span></h2>
                </div>

                <div class="text-center md:text-left mb-8">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Bem-vindo de volta</h2>
                    <p class="text-slate-500 text-sm">Por favor, insira seus dados para entrar.</p>
                </div>

                <?php if($erroLogin): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex items-center gap-3 animate-pulse">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $msgErro; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-5">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-regular fa-envelope text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            </div>
                            <input type="email" id="email" name="email" required 
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" 
                                placeholder="voce@alunosdamedicina.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-1">
                            <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                            <a href="esqueceu_senha.php" class="text-sm text-brand-primary hover:text-brand-secondary font-medium">Esqueceu a senha?</a>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-lock text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            </div>
                            <input type="password" id="password" name="password" required 
                                class="w-full pl-10 pr-10 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" 
                                placeholder="••••••••">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-slate-400 hover:text-slate-600" onclick="togglePassword()">
                                <i class="fa-regular fa-eye" id="eye-icon"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-brand-primary text-white font-semibold py-2.5 rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary transition-all duration-200 flex items-center justify-center gap-2 transform active:scale-[0.98]">
                        Entrar na Plataforma <i class="fa-solid fa-arrow-right text-sm"></i>
                    </button>

                </form>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-sm text-slate-500">
                        Ainda não tem conta? 
                        <a href="cadastro.php" class="text-brand-primary font-semibold hover:text-brand-secondary">Criar cadastro</a>
                    </p>
                </div>

            </div>
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>