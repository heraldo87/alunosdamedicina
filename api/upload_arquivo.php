<?php
session_start();
require_once '../php/config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    
    $workspaceId = intval($_POST['workspace_id']);
    $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $file = $_FILES['arquivo'];
    
    // Configurações
    $uploadDir = '../repositorio/';
    
    // Cria diretório físico se não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Gerar nome único para evitar sobreescrita
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nomeOriginal = pathinfo($file['name'], PATHINFO_FILENAME);
    $novoNomeArquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $nomeOriginal) . '.' . $extensao;
    $caminhoCompleto = $uploadDir . $novoNomeArquivo;
    $caminhoRelativo = 'repositorio/' . $novoNomeArquivo; // Salvo no banco
    
    if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
        
        try {
            // Salvar metadados no banco
            $stmt = $pdo->prepare("INSERT INTO arquivos 
                (workspace_id, parent_id, nome, tipo, caminho_fisico, extensao, tamanho_bytes, status, criado_por) 
                VALUES (?, ?, ?, 'arquivo', ?, ?, ?, 'ativo', ?)");
            
            $stmt->execute([
                $workspaceId,
                $parentId,
                $file['name'], // Nome legível para o usuário
                $caminhoRelativo,
                $extensao,
                $file['size'],
                $_SESSION['user_id'] ?? 0
            ]);
            
            // Redireciona
            $redirect = "../abrir_workspace.php?id=$workspaceId";
            if ($parentId) {
                $redirect .= "&folder_id=$parentId";
            }
            header("Location: $redirect");
            exit;
            
        } catch (PDOException $e) {
            die("Erro ao salvar no banco: " . $e->getMessage());
        }
        
    } else {
        die("Erro ao fazer upload do arquivo.");
    }
}
?>