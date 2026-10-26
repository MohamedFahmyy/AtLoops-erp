<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;
use App\Services\AuditLogger;
use PDO;
use RuntimeException;

class DocumentController extends Controller
{
    private const ALLOWED_MIMES = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip' => 'application/zip',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    public function index(): void
    {
        $this->requireAuth();

        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT d.*, u.name AS uploader_name
            FROM `documents` d
            JOIN `users` u ON u.id = d.user_id
            ORDER BY d.id DESC
        ");
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('documents/index', [
            'pageTitle' => __('common.documents'),
            'documents' => $documents,
        ]);
    }

    public function upload(): void
    {
        $this->requireAuth();

        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->redirectBack(['error' => 'Please select a valid file to upload.']);
        }

        $file = $_FILES['document'];
        $title = trim((string)($_POST['title'] ?? $file['name']));
        $category = $_POST['category'] ?? 'general';
        $entityType = $_POST['entity_type'] ?? 'general';
        $entityId = !empty($_POST['entity_id']) ? (int)$_POST['entity_id'] : null;

        // 1. File Size Check
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $this->redirectBack(['error' => 'File exceeds maximum allowed size (10 MB).']);
        }

        // 2. Extension Check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($ext, self::ALLOWED_MIMES)) {
            $this->redirectBack(['error' => 'Unsupported or prohibited file extension.']);
        }

        // 3. MIME Inspection using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $expectedMime = self::ALLOWED_MIMES[$ext];
        if ($realMime !== $expectedMime && !str_starts_with($realMime, 'image/')) {
            $this->redirectBack(['error' => 'File content does not match its claimed file extension. Upload rejected.']);
        }

        // 4. Generate Safe Storage Name
        $storageDir = dirname(__DIR__, 2) . '/storage/private';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $storedName = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $targetPath = $storageDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->redirectBack(['error' => 'Failed to store document in secure vault.']);
        }

        // 5. Store record in database
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO `documents` (
                `entity_type`, `entity_id`, `user_id`, `title`,
                `original_name`, `stored_name`, `file_size`, `mime_type`, `category`
            ) VALUES (
                :entity_type, :entity_id, :user_id, :title,
                :original_name, :stored_name, :file_size, :mime_type, :category
            )
        ");

        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => Auth::id() ?? 1,
            'title' => $title,
            'original_name' => basename($file['name']),
            'stored_name' => $storedName,
            'file_size' => $file['size'],
            'mime_type' => $realMime,
            'category' => $category,
        ]);

        $docId = (int)$pdo->lastInsertId();

        AuditLogger::log('document.upload', 'document', $docId, null, [
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'size' => $file['size'],
        ]);

        $this->redirectBack(['success' => 'Document securely vaulted and logged.']);
    }

    public function download(string $id): void
    {
        $this->requireAuth();

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM `documents` WHERE `id` = :id LIMIT 1");
        $stmt->execute(['id' => (int)$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            http_response_code(404);
            die("Document not found.");
        }

        // Resource-Level Authorization Check via Policy
        $currentUser = Auth::user();
        if (!$currentUser || !\App\Policies\DocumentPolicy::canDownload($currentUser, $doc, $pdo)) {
            AuditLogger::log('security.unauthorized_download_attempt', 'document', (int)$id, null, [
                'attempted_by' => Auth::id(),
                'original_name' => $doc['original_name'],
            ]);
            $this->forbidden('Access denied: You do not possess authorization to download this private document.');
        }

        $storageDir = dirname(__DIR__, 2) . '/storage/private';
        // Prevent path traversal
        $filePath = $storageDir . '/' . basename($doc['stored_name']);

        if (!file_exists($filePath)) {
            http_response_code(404);
            die("Document file missing from vault.");
        }

        AuditLogger::log('document.download', 'document', (int)$id, null, [
            'original_name' => $doc['original_name']
        ]);

        Response::download($filePath, $doc['original_name'], $doc['mime_type']);
    }
}
