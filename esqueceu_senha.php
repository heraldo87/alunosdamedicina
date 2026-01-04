<?php
/**
 * MEDINFOCUS - Recuperação de Senha (Solicitação)
 */
session_start();

// CONFIGURAÇÃO
$dbHost = 'localhost';
$dbName = 'medinfocus';
$dbUser = 'medinfocus';
$dbPass = 'k78Gh6epARhPsMZP';

$msg = '';
$tipoMsg = ''; // 'erro' ou 'sucesso'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (!empty($email)) {
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1. Verifica se o usuário existe
            $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 2. Gera Token Único e Seguro
                $token = bin2hex(random_bytes(32));
                // Validade de 1 hora
                $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // 3. Salva no Banco
                $stmtInsert = $pdo->prepare("INSERT INTO recuperacao_senha (email, token, expiracao) VALUES (:email, :token, :expiracao)");
                $stmtInsert->bindParam(':email', $email);
                $stmtInsert->bindParam(':token', $token);
                $stmtInsert->bindParam(':expiracao', $expiracao);
                $stmtInsert->execute();

                // 4. Cria o Link (Ajuste o domínio conforme necessário)
                $link = "https://alunosdamedicina.com/nova_senha.php?token=" . $token;

                // --- ENVIO DE EMAIL (Simulação/Real) ---
                $assunto = "Redefinir Senha - MEDINFOCUS";
                $mensagem = "Olá, " . $user['nome'] . ".\n\nClique no link para redefinir sua senha:\n" . $link;
                $headers = "From: no-reply@alunosdamedicina.com";

                // Tenta enviar (pode falhar sem SMTP configurado no servidor)
                // mail($email, $assunto, $mensagem, $headers);

                // MENSAGEM DE SUCESSO
                $tipoMsg = 'sucesso';
                $msg = 'Se o email estiver cadastrado, enviamos um link para você.';
                
                // --- DEBUG PARA DESENVOLVIMENTO (REMOVA EM PRODUÇÃO) ---
                $msg .= "<br><br><strong>[MODO DEV] Link gerado:</strong> <a href='$link' class='underline'>Clique aqui para testar</a>";

            } else {
                // Por segurança, mostramos a mesma mensagem para não revelar quais emails existem
                $tipoMsg = 'sucesso';
                $msg = 'Se o email estiver cadastrado, enviamos um link para você.';
            }

        } catch (PDOException $e) {
            $tipoMsg = 'erro';
            $msg = 'Erro no sistema. Tente novamente mais tarde.';
        }
    } else {
        $tipoMsg = 'erro';
        $msg = 'Por favor, informe seu email.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0f172a', primary: '#0284c7', secondary: '#0ea5e9', surface: '#f8fafc' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'], serif: ['Merriweather', 'serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-surface font-sans antialiased h-screen w-full overflow-hidden">
    <div class="flex h-full w-full">
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-slate-800 to-brand-dark p-12 text-white flex-col justify-between relative overflow-hidden">
             <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-primary opacity-20 rounded-full blur-3xl"></div>
             <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center text-white border border-white/10">
                        <i class="fa-solid fa-heart-pulse text-xl"></i>
                    </div>
                    <span class="font-bold text-2xl tracking-tight">MED<span class="text-brand-primary">INFOCUS</span></span>
                </div>
            </div>
            <div class="relative z-10 max-w-lg">
                <h2 class="text-3xl font-bold mb-4">Esqueceu sua senha?</h2>
                <p class="text-slate-300 text-lg leading-relaxed">Não se preocupe. Acontece com os melhores médicos. Vamos ajudar você a recuperar seu acesso rapidamente.</p>
            </div>
            <div class="relative z-10 text-xs text-slate-500">&copy; 2024 alunosdamedicina.com</div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white overflow-y-auto">
            <div class="w-full max-w-md">
                <div class="text-center md:text-left mb-8">
                    <a href="login.php" class="inline-flex items-center text-sm text-slate-400 hover:text-brand-primary mb-6 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar para Login
                    </a>
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Recuperar Acesso</h2>
                    <p class="text-slate-500 text-sm">Informe o email associado à sua conta.</p>
                </div>

                <?php if($msg): ?>
                <div class="mb-6 p-4 rounded-lg text-sm flex items-start gap-3 <?php echo $tipoMsg == 'sucesso' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200'; ?>">
                    <i class="fa-solid <?php echo $tipoMsg == 'sucesso' ? 'fa-check-circle' : 'fa-circle-exclamation'; ?> mt-0.5"></i>
                    <div class="break-words w-full"><?php echo $msg; ?></div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Acadêmico</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-regular fa-envelope text-slate-400 group-focus-within:text-brand-primary transition-colors"></i>
                            </div>
                            <input type="email" id="email" name="email" required 
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-brand-primary outline-none transition-all text-slate-900 placeholder-slate-400" 
                                placeholder="voce@alunosdamedicina.com">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-brand-primary text-white font-semibold py-2.5 rounded-lg shadow-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary transition-all duration-200">
                        Enviar Link de Recuperação
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>