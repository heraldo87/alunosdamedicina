<?php
/**
 * MEDINFOCUS - Definir Nova Senha
 */
session_start();

$dbHost = 'localhost';
$dbName = 'medinfocus';
$dbUser = 'medinfocus';
$dbPass = 'k78Gh6epARhPsMZP';

$token = $_GET['token'] ?? '';
$msg = '';
$tipoMsg = '';
$tokenValido = false;

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Verifica Token
    if (!empty($token)) {
        $stmt = $pdo->prepare("SELECT email, expiracao FROM recuperacao_senha WHERE token = :token AND expiracao > NOW() LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            $tokenValido = true;
            $emailRecuperacao = $dados['email'];
        } else {
            $tipoMsg = 'erro';
            $msg = 'Este link é inválido ou já expirou. Solicite um novo.';
        }
    } else {
        $tipoMsg = 'erro';
        $msg = 'Token não fornecido.';
    }

    // 2. Processa a Nova Senha
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
        $senha = $_POST['password'];
        $confirma = $_POST['confirm_password'];

        if ($senha === $confirma && strlen($senha) >= 6) {
            // Atualiza Senha do Usuário
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmtUpd = $pdo->prepare("UPDATE usuarios SET senha_hash = :senha WHERE email = :email");
            $stmtUpd->bindParam(':senha', $senhaHash);
            $stmtUpd->bindParam(':email', $emailRecuperacao);
            
            if ($stmtUpd->execute()) {
                // Remove o token usado (Segurança)
                $stmtDel = $pdo->prepare("DELETE FROM recuperacao_senha WHERE email = :email");
                $stmtDel->bindParam(':email', $emailRecuperacao);
                $stmtDel->execute();

                $tipoMsg = 'sucesso';
                $msg = 'Senha atualizada com sucesso! <a href="login.php" class="underline font-bold">Faça login agora.</a>';
                $tokenValido = false; // Esconde o formulário
            }
        } else {
            $tipoMsg = 'erro';
            $msg = 'As senhas não conferem ou são muito curtas.';
        }
    }

} catch (PDOException $e) {
    $msg = 'Erro de conexão.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - MEDINFOCUS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { dark: '#0f172a', primary: '#0284c7', surface: '#f8fafc' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 font-sans antialiased h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-xl shadow-lg max-w-md w-full border border-slate-200">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-slate-900">Definir Nova Senha</h2>
        </div>

        <?php if($msg): ?>
        <div class="mb-6 p-4 rounded-lg text-sm flex items-center gap-3 <?php echo $tipoMsg == 'sucesso' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'; ?>">
            <i class="fa-solid <?php echo $tipoMsg == 'sucesso' ? 'fa-check-circle' : 'fa-circle-exclamation'; ?>"></i>
            <span><?php echo $msg; ?></span>
        </div>
        <?php endif; ?>

        <?php if($tokenValido): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nova Senha</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-brand-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar Senha</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-brand-primary outline-none">
            </div>
            <button type="submit" class="w-full bg-brand-primary text-white font-bold py-2.5 rounded-lg hover:bg-sky-700 transition-all">
                Alterar Senha
            </button>
        </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="login.php" class="text-sm text-brand-primary hover:underline">Voltar ao Login</a>
        </div>
    </div>

</body>
</html>