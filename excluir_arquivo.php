<?php
require_once '../php/config.php';
$id = $_POST['id'] ?? null;
if ($id) {
    // Soft Delete: Apenas muda status para inativo
    $stmt = $pdo->prepare("UPDATE arquivos SET status = 'inativo' WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
}
?>