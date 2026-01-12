<?php
/**
 * MEDINFOCUS - Sistema de Cadastro Refatorado
 * Melhoria: Integração do campo Telefone/WhatsApp no processamento.
 */

session_start();

// 1. CONFIGURAÇÃO CENTRALIZADA
if (file_exists('php/config.php')) {
    require_once 'php/config.php';
} else {
    die("Erro Crítico: O arquivo 'php/config.php' não foi encontrado.");
}

// Variáveis de controle de interface
$erroCadastro = false;
$sucessoCadastro = false;
$msg = '';

// 2. BUSCA DE UNIVERSIDADES
$universidades = []; 
try {
    if (isset($pdo)) {
        $stmtUni = $pdo->query("SELECT id, nome, sigla FROM universidades ORDER BY nome ASC");
        if ($stmtUni) {
            $universidades = $stmtUni->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $universidades = []; 
}

// 3. LÓGICA DE PROCESSAMENTO DO CADASTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitização de entradas
    $nome = isset($_POST['nome']) ? strip_tags(trim($_POST['nome'])) : '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    // Limpa o telefone para garantir apenas números inteiros
    $telefone = isset($_POST['telefone']) ? preg_replace('/[^0-9]/', '', $_POST['telefone']) : '';
    $matricula = isset($_POST['matricula']) ? strip_tags(trim($_POST['matricula'])) : '';
    $faculdade_id = filter_input(INPUT_POST, 'faculdade', FILTER_VALIDATE_INT);
    $senha = $_POST['password'] ?? '';
    $confirmaSenha = $_POST['confirm_password'] ?? '';

    // Validações de Negócio (incluindo telefone)
    if (empty($nome) || empty($email) || empty($telefone) || empty($senha) || empty($matricula) || !$faculdade_id) {
        $erroCadastro = true;
        $msg = 'Por favor, preencha todos os campos obrigatórios, incluindo o WhatsApp.';
    } elseif ($senha !== $confirmaSenha) {
        $erroCadastro = true;
        $msg = 'As senhas digitadas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erroCadastro = true;
        $msg = 'A senha deve ter no mínimo 6 caracteres para sua segurança.';
    } else {
        try {
            // Verificar duplicidade de email
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->rowCount() > 0) {
                $erroCadastro = true;
                $msg = 'Este email já está registrado em nossa base.';
            } else {
                // Criptografia da senha
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserção com o novo campo 'telefone'
                $sql = "INSERT INTO usuarios (nome, email, telefone, senha_hash, matricula, faculdade_id, nivel_acesso, ativo, data_criacao) 
                        VALUES (:nome, :email, :telefone, :senha_hash, :matricula, :faculdade, 1, 0, NOW())";
                
                $stmtInsert = $pdo->prepare($sql);
                $foiInserido = $stmtInsert->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':telefone' => $telefone,
                    ':senha_hash' => $senhaHash,
                    ':matricula' => $matricula,
                    ':faculdade' => $faculdade_id
                ]);

                if ($foiInserido) {
                    $sucessoCadastro = true;
                    $msg = 'Cadastro realizado com sucesso! Aguarde a liberação do seu representante.';
                    header("refresh:4;url=login.php"); 
                } else {
                    $erroCadastro = true;
                    $msg = 'Ocorreu um erro técnico ao salvar seus dados. Tente novamente.';
                }
            }
        } catch (PDOException $e) {
            $erroCadastro = true;
            $msg = 'Erro de conexão: Certifique-se que o campo "telefone" foi criado na tabela "usuarios".';
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
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0f172a',
                            primary: '#0284c7',
                            secondary: '#0ea5e9',
                            accent: '#10b981',
                            surface: '#f8fafc',
                        }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
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
                <p class="text-sky-100 text-sm ml-1">Plataforma Acadêmica</p>
            </div>

            <div class="relative z-10 max-w-lg">
                <h2 class="text-4xl font-bold mb-6 leading-tight">
                    Excelência na sua <br>
                    <span class="text-sky-300">carreira médica.</span>
                </h2>
                <p class="text-sky-100 text-lg">Organize seus estudos, acesse materiais exclusivos e conecte-se com sua turma.</p>
            </div>

            <div class="relative z-10 text-xs text-sky-200/50">
                &copy; 2024 alunosdamedicina.com
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white overflow-y-auto">
            <div class="w-full max-w-md my-auto">
                
                <div class="text-center md:text-left mb-8">
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Criar nova conta</h2>
                    <p class="text-slate-500 text-sm">Preencha seus dados acadêmicos abaixo.</p>
                </div>

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
                <?php else: ?>

                <form method="POST" action="cadastro.php" class="space-y-4">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nome Completo</label>
                        <input type="text" name="nome" required 
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none transition-all" 
                            placeholder="Como no registro acadêmico"
                            value="<?php echo htmlspecialchars($nome ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email Acadêmico</label>
                        <input type="email" name="email" required 
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none transition-all" 
                            placeholder="exemplo@email.com"
                            value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp (DDD + Número)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-brands fa-whatsapp text-emerald-500 font-bold"></i>
                            </div>
                            <input type="tel" name="telefone" required 
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none transition-all" 
                                placeholder="Ex: 21999998888"
                                value="<?php echo htmlspecialchars($telefone ?? ''); ?>"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Apenas números. Campo obrigatório para validação do acesso.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Matrícula</label>
                            <input type="text" name="matricula" required 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none transition-all" 
                                placeholder="ID da Faculdade"
                                value="<?php echo htmlspecialchars($matricula ?? ''); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Universidade</label>
                            <select name="faculdade" required class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none bg-white">
                                <option value="">Selecione...</option>
                                <?php if(!empty($universidades)): ?>
                                    <?php foreach($universidades as $uni): ?>
                                        <option value="<?php echo $uni['id']; ?>" <?php echo (isset($faculdade_id) && $faculdade_id == $uni['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($uni['sigla']); ?> - <?php echo htmlspecialchars($uni['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                            <input type="password" name="password" required 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none" 
                                placeholder="Min. 6 dígitos">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar Senha</label>
                            <input type="password" name="confirm_password" required 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-primary outline-none" 
                                placeholder="Repita a senha">
                        </div>
                    </div>

                    <div class="flex items-start gap-2 pt-2">
                        <input id="terms" name="terms" type="checkbox" required class="mt-1 w-4 h-4 text-brand-primary border-slate-300 rounded focus:ring-brand-primary">
                        <label for="terms" class="text-xs text-slate-500 leading-tight">
                            Declaro que as informações são verdadeiras e estou ciente que meu acesso depende da aprovação do representante da turma.
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-brand-primary text-white font-bold py-3 rounded-lg shadow-lg hover:bg-sky-700 transition-all flex items-center justify-center gap-2 transform active:scale-[0.98] mt-2">
                        Solicitar Acesso <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>

                </form>
                <?php endif; ?>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-sm text-slate-500">
                        Já tem cadastro? 
                        <a href="login.php" class="text-brand-primary font-bold hover:underline">Fazer login</a>
                    </p>
                </div>

            </div>
        </div>

    </div>
</body>
</html>