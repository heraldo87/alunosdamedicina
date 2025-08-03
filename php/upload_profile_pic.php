<?php
// Inicia a sessão para acessar as variáveis do usuário, como o ID
require_once 'auth_check.php';
// Inclui o arquivo de conexão com o banco de dados
require_once 'conn.php';

// Define a pasta de destino para as imagens de perfil
$target_dir = "../uploads/profile_pics/";

// Define uma variável para redirecionamento e mensagens
$redirect_url = '../meu_perfil.php';

// Verifica se um arquivo foi realmente enviado
if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {

    $file = $_FILES["profile_pic"];
    $user_id = $_SESSION['user_id'];

    // --- VALIDAÇÕES DE SEGURANÇA ---

    // 1. Verificar o tamanho do arquivo (ex: máximo de 5MB)
    $max_file_size = 5 * 1024 * 1024; // 5 MB
    if ($file["size"] > $max_file_size) {
        // Você pode adicionar um sistema de mensagens de erro aqui
        header("Location: " . $redirect_url . "?error=file_too_large");
        exit();
    }

    // 2. Verificar o tipo de arquivo (permitir apenas formatos de imagem comuns)
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_mime_type = mime_content_type($file["tmp_name"]);
    if (!in_array($file_mime_type, $allowed_mime_types)) {
        header("Location: " . $redirect_url . "?error=invalid_file_type");
        exit();
    }

    // --- PROCESSAMENTO DO ARQUIVO ---

    // Pega a extensão do arquivo (jpg, png, etc.)
    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    
    // Cria um nome de arquivo único para evitar conflitos e apagar fotos de outros usuários.
    // Ex: user_1_timestamp.jpg
    $unique_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
    
    // O caminho completo onde o arquivo será salvo no servidor
    $target_file_path = $target_dir . $unique_filename;
    
    // O caminho que será salvo no banco de dados (relativo à raiz do site)
    $db_path = "uploads/profile_pics/" . $unique_filename;

    // Tenta mover o arquivo da pasta temporária para a pasta de destino
    if (move_uploaded_file($file["tmp_name"], $target_file_path)) {
        
        // --- ATUALIZAÇÃO DO BANCO DE DADOS ---

        // Prepara a query para atualizar o caminho da imagem do perfil
        $stmt = $conn->prepare("UPDATE usuarios SET profile_image_path = ? WHERE id = ?");
        $stmt->bind_param("si", $db_path, $user_id);
        
        // Executa a query e redireciona com sucesso
        if ($stmt->execute()) {
            header("Location: " . $redirect_url . "?success=upload_ok");
        } else {
            // Se falhar, pode registrar o erro
            header("Location: " . $redirect_url . "?error=db_update_failed");
        }
        $stmt->close();

    } else {
        // Se a função move_uploaded_file falhar (geralmente por permissões da pasta)
        header("Location: " . $redirect_url . "?error=move_file_failed");
    }

} else {
    // Se nenhum arquivo foi enviado ou houve um erro no upload
    header("Location: " . $redirect_url . "?error=no_file_uploaded");
}

$conn->close();
exit();
?>