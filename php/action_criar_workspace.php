<?php
// php/action_criar_workspace.php
session_start();
require_once 'config.php';
require_once 'DriveService.php';

// Configuração: ID da Pasta Raiz "MEDINFOCUS_ARQUIVOS" (que você criou no Drive)
// O sistema criará as disciplinas DENTRO desta pasta.
define('DRIVE_ROOT_FOLDER_ID', '1JfQe5FZeTIgp4yuWJIt0CUVRVB5OoVRY');

// 1. Verificação de Segurança
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Faça login.']);
    exit;
}

// Apenas POST é permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método inválido.']);
    exit;
}

// 2. Receber e Validar Dados
$nomeWorkspace = trim($_POST['nome_workspace'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$usuarioId = $_SESSION['user_id'] ?? 0; // Certifique-se que sua sessão tem user_id

if (empty($nomeWorkspace)) {
    echo json_encode(['success' => false, 'error' => 'O nome da disciplina é obrigatório.']);
    exit;
}

// Iniciar Transação (Para garantir que se falhar no BD, não cria lixo no Google e vice-versa)
// Nota: O Google Drive não tem rollback, então criamos lá primeiro.
try {
    // 3. Conectar ao Google Drive
    $driveService = new DriveService();
    
    // 4. Criar a Pasta Física no Google Drive
    // Isso retorna o ID "estranho" do Google (ex: 1A2b3C...)
    $googleFolderId = $driveService->createFolder($nomeWorkspace, DRIVE_ROOT_FOLDER_ID);

    if (!$googleFolderId) {
        throw new Exception("Falha ao criar pasta no Google Drive.");
    }

    // 5. Salvar no MySQL
    $pdo->beginTransaction();

    // Inserir Workspace
    $sqlWorkspace = "INSERT INTO workspaces (name, description, drive_folder_id, created_by) VALUES (:name, :desc, :drive_id, :user)";
    $stmt = $pdo->prepare($sqlWorkspace);
    $stmt->execute([
        ':name' => $nomeWorkspace,
        ':desc' => $descricao,
        ':drive_id' => $googleFolderId,
        ':user' => $usuarioId
    ]);
    
    $novoWorkspaceId = $pdo->lastInsertId();

    // Inserir Permissão de Admin para quem criou
    $sqlPermissao = "INSERT INTO workspace_permissions (workspace_id, user_id, role) VALUES (:wid, :uid, 'admin')";
    $stmtPerm = $pdo->prepare($sqlPermissao);
    $stmtPerm->execute([
        ':wid' => $novoWorkspaceId,
        ':uid' => $usuarioId
    ]);

    $pdo->commit();

    // 6. Retorno de Sucesso
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Workspace criado com sucesso!',
        'id' => $novoWorkspaceId,
        'google_id' => $googleFolderId
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log do erro real no servidor
    error_log("Erro ao criar workspace: " . $e->getMessage());
    
    // Resposta amigável para o frontend
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno ao processar solicitação: ' . $e->getMessage()]);
}
?>