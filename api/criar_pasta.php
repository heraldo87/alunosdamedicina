<?php
require_once '../php/config.php';
session_start();

$workspace_id = $_POST['workspace_id'] ?? null;
$parent_id = $_POST['parent_id'] ?? null;
if ($parent_id === 'null' || $parent_id === '') $parent_id = null;
$nome = $_POST['nome'] ?? 'Nova Pasta';

if ($workspace_id) {
    $stmt = $pdo->prepare("INSERT INTO arquivos (workspace_id, parent_id, nome_arquivo, tipo, status, criado_por) VALUES (?, ?, ?, 'pasta', 'ativo', ?)");
    if ($stmt->execute([$workspace_id, $parent_id, $nome, $_SESSION['user_id'] ?? 0])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro BD']);
    }
}
?>