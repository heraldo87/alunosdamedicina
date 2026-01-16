<?php
// php/action_upload.php
declare(strict_types=1);

session_start();

/**
 * 1) Garantir que NUNCA saia HTML acidentalmente (warnings/notices/fatal)
 *    e que a resposta seja sempre JSON.
 */
ini_set('display_errors', '0'); // não imprimir erros na tela (evita <br><b>...)
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Buffer para capturar qualquer saída antes do JSON
ob_start();

// Header padrão
header('Content-Type: application/json; charset=utf-8');

/**
 * Função helper para responder em JSON e encerrar.
 */
function respond(int $status, array $payload): void
{
    if (ob_get_length()) {
        ob_clean(); // remove qualquer lixo/HTML antes do JSON
    }
    http_response_code($status);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Captura erros fatais e devolve JSON (em vez de HTML).
 */
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        respond(500, [
            'success' => false,
            'error'   => 'Erro interno no servidor. Verifique o error_log do PHP.',
            // Em produção, você pode remover o debug abaixo:
            'debug'   => $err['message'] ?? null,
        ]);
    }
});

/**
 * 2) Carregar dependências com caminho absoluto (evita falha de require)
 */
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/DriveService.php';
} catch (Throwable $t) {
    respond(500, [
        'success' => false,
        'error'   => 'Falha ao carregar dependências do upload.',
        'debug'   => $t->getMessage(),
    ]);
}

/**
 * 3) Ajustes de upload (servidor)
 */
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

/**
 * 4) Verificações de sessão / autenticação
 */
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    respond(403, ['success' => false, 'error' => 'Acesso negado.']);
}

if (empty($_SESSION['user_id'])) {
    respond(403, ['success' => false, 'error' => 'Sessão inválida: user_id ausente.']);
}

$usuarioId = (int) $_SESSION['user_id'];

/**
 * 5) Verificações do request
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'error' => 'Método inválido. Use POST.']);
}

if (!isset($_FILES['arquivo'])) {
    respond(400, ['success' => false, 'error' => 'Nenhum arquivo recebido.']);
}

/**
 * 6) Validar erro nativo de upload (UPLOAD_ERR_*)
 */
$arquivo = $_FILES['arquivo'];

if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
    $map = [
        UPLOAD_ERR_INI_SIZE   => 'Arquivo excede o upload_max_filesize do servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo excede o limite definido no formulário.',
        UPLOAD_ERR_PARTIAL    => 'Upload parcial. Tente novamente.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo recebido.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente no servidor.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo no disco.',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão PHP.',
    ];
    $msg = $map[$arquivo['error']] ?? 'Erro desconhecido no upload.';
    respond(400, ['success' => false, 'error' => $msg]);
}

if (empty($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
    respond(400, ['success' => false, 'error' => 'Arquivo temporário inválido (upload não confiável).']);
}

if (empty($arquivo['name'])) {
    respond(400, ['success' => false, 'error' => 'Nome do arquivo inválido.']);
}

/**
 * 7) Validar parâmetros
 */
$workspaceId = (int) ($_POST['workspace_id'] ?? 0);
if ($workspaceId <= 0) {
    respond(400, ['success' => false, 'error' => 'workspace_id inválido.']);
}

// folder_id opcional: vazio / "0" vira NULL
$folderIdRaw = $_POST['folder_id'] ?? '';
$folderId = (!empty($folderIdRaw) && (int)$folderIdRaw > 0) ? (int)$folderIdRaw : null;

/**
 * 8) Validar $pdo vindo do config.php
 */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    respond(500, [
        'success' => false,
        'error'   => 'Falha de configuração: conexão PDO não disponível em config.php.',
    ]);
}

/**
 * 9) Fluxo principal: permissão -> upload drive -> insert DB
 */
try {
    // 9.1) Verificar permissão e obter drive_folder_id
    $sql = "SELECT w.drive_folder_id, p.role
            FROM workspaces w
            JOIN workspace_permissions p ON w.id = p.workspace_id
            WHERE w.id = :wid
              AND p.user_id = :uid
              AND w.status = 'active'
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':wid' => $workspaceId, ':uid' => $usuarioId]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        respond(403, ['success' => false, 'error' => 'Workspace não encontrado ou sem permissão.']);
    }

    if (($dados['role'] ?? '') === 'viewer') {
        respond(403, ['success' => false, 'error' => 'Você não tem permissão para fazer upload aqui.']);
    }

    $driveFolderId = $dados['drive_folder_id'] ?? '';
    if (empty($driveFolderId)) {
        respond(500, ['success' => false, 'error' => 'drive_folder_id não configurado para este workspace.']);
    }

    // 9.2) Descobrir MIME real (melhor do que confiar no navegador)
    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = finfo_file($finfo, $arquivo['tmp_name']);
            finfo_close($finfo);
            if (!empty($detected)) $mime = $detected;
        }
    }

    // 9.3) Upload para o Google Drive
    $driveService = new DriveService();

    $driveFile = $driveService->uploadFile(
        $arquivo['tmp_name'],
        $arquivo['name'],
        $driveFolderId
    );

    if (empty($driveFile['id'])) {
        respond(500, ['success' => false, 'error' => 'Falha ao receber confirmação do Google Drive.']);
    }

    // 9.4) Registrar no banco (folder_id virtual pode ser NULL)
    $sqlInsert = "INSERT INTO files
                  (workspace_id, folder_id, google_drive_file_id, name, mime_type, size, uploaded_by)
                  VALUES
                  (:wid, :fid, :gid, :nome, :mime, :size, :uid)";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ':wid'  => $workspaceId,
        ':fid'  => $folderId,
        ':gid'  => $driveFile['id'],
        ':nome' => $arquivo['name'],
        ':mime' => $mime,
        ':size' => (int) ($arquivo['size'] ?? 0),
        ':uid'  => $usuarioId
    ]);

    respond(200, [
        'success' => true,
        'message' => 'Arquivo enviado com sucesso!',
        'file_id' => (int) $pdo->lastInsertId(),
        // opcional:
        // 'drive_id' => $driveFile['id'],
        // 'drive_link' => $driveFile['link'] ?? null,
    ]);

} catch (Throwable $e) {
    // Se der exceção, devolve JSON limpo
    respond(500, [
        'success' => false,
        'error'   => 'Erro ao processar upload.',
        // Em produção, você pode ocultar:
        'debug'   => $e->getMessage(),
    ]);
}
