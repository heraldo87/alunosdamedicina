<?php
// Inicia a sessão para acessar as variáveis do usuário
require_once 'auth_check.php';

// Pasta de destino (relativa a este script)
$target_dir = realpath(__DIR__ . "/../uploads/profile_pics");
if (!$target_dir) {
    die("Diretório de destino não encontrado.");
}

// Cria diretório se não existir
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$redirect_url = '../meu_perfil.php';

if (isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0) {
    $file = $_FILES["profile_pic"];
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    if ($user_id <= 0) {
        header("Location: $redirect_url?error=invalid_user");
        exit();
    }

    // --- Validações ---
    $max_file_size = 5 * 1024 * 1024; // 5 MB
    if ($file["size"] > $max_file_size) {
        header("Location: $redirect_url?error=file_too_large");
        exit();
    }

    $allowed_mime_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    $file_mime_type = function_exists("finfo_open")
        ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file["tmp_name"])
        : mime_content_type($file["tmp_name"]);

    if (!isset($allowed_mime_types[$file_mime_type])) {
        header("Location: $redirect_url?error=invalid_file_type");
        exit();
    }

    $ext = $allowed_mime_types[$file_mime_type];

    // --- Remove antigas fotos do usuário ---
    foreach (glob("$target_dir/{$user_id}.*") as $old_file) {
        @unlink($old_file);
    }

    // --- Salva a nova ---
    $final_name = $user_id . "." . $ext;
    $target_file = $target_dir . "/" . $final_name;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        chmod($target_file, 0644);
        header("Location: $redirect_url?success=upload_ok");
        exit();
    } else {
        header("Location: $redirect_url?error=move_file_failed");
        exit();
    }
} else {
    header("Location: $redirect_url?error=no_file_uploaded");
    exit();
}
