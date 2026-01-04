<?php
session_start();
require_once 'php/config.php';

// 1. SEGURANÇA: Apenas usuários logados podem criar
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Verifica se recebeu o envio do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pega o nome digitado e remove espaços extras no início/fim
    $nomePasta = trim($_POST['nome_pasta']);
    
    // 2. HIGIENIZAÇÃO (SANITIZAÇÃO) DO NOME
    // Permite apenas letras, números, hífen e underline. Remove todo o resto.
    // Isso evita que alguém tente criar uma pasta chamada "../../hack"
    $nomePasta = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $nomePasta);
    
    // Substitui espaços por underline (melhor para URLs)
    // Ex: "Minha Pasta" vira "Minha_Pasta"
    $nomePasta = str_replace(' ', '_', $nomePasta);

    // Se sobrou algum nome válido após a limpeza
    if (!empty($nomePasta)) {
        $caminho = 'repositorio/' . $nomePasta;
        
        // Verifica se a pasta já existe
        if (!file_exists($caminho)) {
            // 3. CRIAÇÃO DA PASTA
            // 0777 = Permissão total (necessário em alguns servidores compartilhados para upload funcionar depois)
            if (mkdir($caminho, 0777, true)) {
                
                // 4. SEGURANÇA ADICIONAL
                // Cria um arquivo index.html vazio dentro da pasta.
                // Isso impede que curiosos vejam a lista de arquivos se digitarem o endereço direto no navegador.
                file_put_contents($caminho . '/index.html', ''); 
            }
        }
    }
}

// Redireciona de volta para a lista, tendo criado ou não (para não travar a tela)
header("Location: repositorio.php");
exit;
?>