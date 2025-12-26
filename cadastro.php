<?php
/**
 * MEDINFOCUS - Sistema de Cadastro
 * Design idêntico ao Login, com lógica de registro atualizada para PHP 8.1+
 */

session_start();

// 1. CONFIGURAÇÃO
$dbHost = '181.215.135.63';
$dbName = 'medinfocus';
$dbUser = 'medinfocus'; // Corrigido
$dbPass = 'k78Gh6epARhPsMZP';

// Variáveis de controle
$erroCadastro = false;
$sucessoCadastro = false;
$msg = '';

// 2. LÓGICA DE CADASTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitização Atualizada (PHP 8.1+)
    // Remove tags HTML e espaços em branco do início/fim
    $nome = isset($_POST['nome']) ? strip_tags(trim($_POST['nome'])) : '';
    
    // O filtro de email continua válido e seguro
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $senha = $_POST['password'] ?? '';
    $confirmaSenha = $_POST['confirm_password'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        $erroCadastro = true;
        $msg = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($senha !== $confirmaSenha) {
        $erroCadastro = true;
        $msg = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erroCadastro = true;
        $msg = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $erroCadastro = true;
                $msg = 'Este email já está cadastrado. Tente fazer login.';
            } else {
                // Criar Hash da senha
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserir novo usuário (Padrão: tipo 'aluno', nível 1)
                // O banco define 'ativo' como 0 (pendente) por padrão
                $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, nivel_acesso, data_criacao) VALUES (:nome, :email, :senha, 1, NOW())");
                $stmtInsert->bindParam(':nome', $nome);
                $stmtInsert->bindParam(':email', $email);
                $stmtInsert->bindParam(':senha', $senhaHash);
                
                if ($stmtInsert->execute()) {
                    $sucessoCadastro = true;
                    $msg = 'Conta criada com sucesso! Redirecionando...';
                    // Redireciona após 2 segundos
                    header("refresh:2;url=login.php"); 
                } else {
                    $erroCadastro = true;
                    $msg = 'Erro ao criar conta. Tente novamente.';
                }
            }
        } catch (PDOException $e) {
            $erroCadastro = true;
            // Em produção, a mensagem deve ser genérica por segurança
            $msg = 'Erro de conexão com o sistema. Verifique as configurações.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - MEDINFOCUS</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Configuração de Branding -->
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
        
        <!-- LADO ESQUERDO: BRANDING -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-primary to-blue-900 p-12 text-white flex-col justify-between relative overflow-hidden">
            
            <!-- Elementos Decorativos -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-full h-full opacity-10 pointer-events-none">
                 <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white" />
                </svg>
            </div>

            <!-- Header -->
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center text-white border border-white/10">
                        <i class="fa-solid fa-heart-pulse text-xl"></i>
                    </div>
                    <span class="font-bold text-2xl tracking-tight">MED<span class="text-sky-300">INFOCUS</span></span>
                </div>
                <p class="text-sky-100 text-sm ml-1">Plataforma de Gestão Acadêmica</p>
            </div>

            <!-- Conteúdo Central -->
            <div class="relative z-10 max-w-lg">
                <h2 class="text-4xl font-bold mb-6 leading-tight">
                    Junte-se à nova geração da <br>
                    <span class="text-sky-300">medicina.</span>
                </h2>
                <p class="text-sky-100 text-lg leading-relaxed mb-8">
                    Crie sua conta agora para acessar conteúdos exclusivos, simulados e organizar sua rotina de estudos de forma profissional.
                </p>
            </div>

            <!-- Footer -->
            <div class="relative z-10 text-xs text-sky-200/50">
                &copy; 2024 alunosdamedicina.com
            </div>
        </div>

        <!-- LADO DIREITO: FORMULÁRIO -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white overflow-y-auto">
            <div class="w-full max-w-md my-auto">
                
                <!-- Logo Mobile -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-brand-primary/10 text-brand-primary mb-3">
                        <i class="fa-solid fa-heart-pulse text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900">MED<span class="text-brand-primary">INFOCUS</span></h2>
                </div>

                <!-- Cabeçalho Form -->
                <div class="text-center md:text-left mb-6">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Criar nova conta</h2>
                    <p class="text-slate-500 text-sm">Preencha os dados abaixo para começar.</p>
                </div>

                <!-- Alertas -->
                <?php if($erroCadastro): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex items-center gap-3 animate-pulse">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $msg; ?></span>
                </div>
                <?php endif; ?>

                <?php if($sucessoCadastro): ?>
                <div class="mb-6 p-4 bg-green-50 border border-brand-accent/30 text-brand-accent rounded-lg text-sm flex items-center gap-3">
                    <i class="fa-solid fa-check-circle"></i>
                    <span><?php echo $msg; ?></span>
                </div>
                <?php endif; ?>

                <!-- Formulário -->
                <?php if(!$sucessoCadastro): ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                    
                    <!-- Nome -->
                    <div>
                        <label for="nome" class="block text-sm font-medium text-slate-700 mb-1">Nome Completo</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-regular fa-user text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            </div>
                            <input type="text" id="nome" name="nome" required 
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" 
                                placeholder="Seu nome"
                                value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Acadêmico</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-regular fa-envelope text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            </div>
                            <input type="email" id="email" name="email" required 
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" 
                                placeholder="aluno@medicina.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-lock text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            </div>
                            <input type="password" id="password" name="password" required 
                                class="w-full pl-10 pr-10 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" 
                                placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>

                    <!-- Confirmar Senha -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1">Confirmar Senha</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-lock text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                class="w-full pl-10 pr-10 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" 
                                placeholder="Repita a senha">
                        </div>
                    </div>

                    <!-- Termos -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required class="w-4 h-4 border border-slate-300 rounded bg-gray-50 focus:ring-3 focus:ring-brand-primary/30 text-brand-primary">
                        </div>
                        <label for="terms" class="ml-2 text-sm font-medium text-slate-500">
                            Eu concordo com os <a href="termo.php" class="text-brand-primary hover:underline">Termos de Uso</a> e <a href="termo.php" class="text-brand-primary hover:underline">Privacidade</a>.
                        </label>
                    </div>

                    <!-- Botão Submit -->
                    <button type="submit" class="w-full bg-brand-primary text-white font-semibold py-2.5 rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary transition-all duration-200 flex items-center justify-center gap-2 transform active:scale-[0.98] mt-2">
                        Criar Conta <i class="fa-solid fa-user-plus text-sm"></i>
                    </button>

                </form>
                <?php endif; ?>

                <!-- Footer Form -->
                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-sm text-slate-500">
                        Já tem uma conta? 
                        <a href="login.php" class="text-brand-primary font-semibold hover:text-brand-secondary">Fazer login</a>
                    </p>
                </div>

            </div>
        </div>

    </div>
</body>
</html>