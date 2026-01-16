<?php
// php/DriveService.php
declare(strict_types=1);

// 1) Composer autoload: tenta caminhos comuns
$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php', // padrão: /php/DriveService.php -> /vendor/autoload.php
    __DIR__ . '/vendor/autoload.php',    // caso vendor esteja dentro de /php
];

$autoload = null;
foreach ($autoloadCandidates as $p) {
    if (file_exists($p)) { $autoload = $p; break; }
}

if (!$autoload) {
    throw new Exception(
        "Composer autoload.php não encontrado. Caminhos testados: " . implode(" | ", $autoloadCandidates)
    );
}

require_once $autoload;

class DriveService {
    private $client;
    private $service;

    // credentials.json deve ficar na mesma pasta deste arquivo
    private $credentialsPath;

    public function __construct() {
        $this->credentialsPath = __DIR__ . '/credentials.json';

        if (!file_exists($this->credentialsPath)) {
            throw new Exception("credentials.json não encontrado em: " . $this->credentialsPath);
        }

        $this->client = new Google\Client();

        try {
            $this->client->setAuthConfig($this->credentialsPath);
            $this->client->addScope(Google\Service\Drive::DRIVE);

            $this->service = new Google\Service\Drive($this->client);

        } catch (Throwable $e) {
            throw new Exception("Erro ao autenticar no Google Drive: " . $e->getMessage());
        }
    }

    public function createFolder(string $folderName, string $parentId): string {
        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId]
        ]);

        $file = $this->service->files->create($fileMetadata, ['fields' => 'id']);
        return (string) $file->id;
    }

    public function uploadFile(string $localFilePath, string $fileName, string $parentId): array {
        if (!file_exists($localFilePath)) {
            throw new Exception("Arquivo temporário (TMP) não encontrado: " . $localFilePath);
        }

        // 2) MIME (mapa por extensão - não depende de extensões do PHP)
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $map = [
            "pdf"  => "application/pdf",
            "png"  => "image/png",
            "jpg"  => "image/jpeg",
            "jpeg" => "image/jpeg",
            "gif"  => "image/gif",
            "webp" => "image/webp",
            "txt"  => "text/plain",
            "csv"  => "text/csv",
            "doc"  => "application/msword",
            "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "xls"  => "application/vnd.ms-excel",
            "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "ppt"  => "application/vnd.ms-powerpoint",
            "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "zip"  => "application/zip"
        ];
        $mimeType = $map[$ext] ?? "application/octet-stream";

        $fileMetadata = new Google\Service\Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$parentId]
        ]);

        $content = file_get_contents($localFilePath);
        if ($content === false) {
            throw new Exception("Falha ao ler arquivo temporário: " . $localFilePath);
        }

        try {
            $file = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink, size'
            ]);
        } catch (Throwable $e) {
            throw new Exception("Falha no upload para o Google Drive: " . $e->getMessage());
        }

        return [
            'id'   => (string) $file->id,
            'link' => (string) ($file->webViewLink ?? ''),
            'size' => (int) ($file->size ?? 0),
        ];
    }
}
