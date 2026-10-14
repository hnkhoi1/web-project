<?php
/**
 * backend/api/notes.php
 * API quản lý ghi chú người dùng với MySQL
 */

require_once __DIR__ . '/../config.php';

$pdo = getPDO();
if (!$pdo) {
    jsonResponse(['success' => false, 'error' => 'DATABASE_OFFLINE'], 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = getJsonInput();
if (empty($action) && isset($input['action'])) {
    $action = $input['action'];
}

// 1. LẤY DANH SÁCH GHI CHÚ
if ($method === 'GET' || $action === 'list') {
    $userId = (int)($_GET['user_id'] ?? $input['user_id'] ?? 0);
    $q = trim($_GET['q'] ?? $input['q'] ?? '');

    if ($userId <= 0) {
        jsonResponse(['success' => false, 'error' => 'INVALID_USER_ID'], 400);
    }

    if ($q !== '') {
        $stmt = $pdo->prepare('
            SELECT * FROM notes 
            WHERE user_id = ? AND (LOWER(title) LIKE ? OR LOWER(content) LIKE ?)
            ORDER BY pinned DESC, updated_at DESC
        ');
        $like = '%' . strtolower($q) . '%';
        $stmt->execute([$userId, $like, $like]);
    } else {
        $stmt = $pdo->prepare('
            SELECT * FROM notes 
            WHERE user_id = ? 
            ORDER BY pinned DESC, updated_at DESC
        ');
        $stmt->execute([$userId]);
    }

    $notes = $stmt->fetchAll();
    $out = array_map(function($n) {
        return [
            'id' => (int)$n['id'],
            'userId' => (string)$n['user_id'],
            'title' => $n['title'],
            'content' => $n['content'] ?? '',
            'color' => $n['color'] ?? 'auto',
            'pinned' => (bool)$n['pinned'],
            'source' => $n['source_type'] ? ['type' => $n['source_type'], 'ref' => $n['source_ref']] : null,
            'createdAt' => strtotime($n['created_at']) * 1000,
            'updatedAt' => strtotime($n['updated_at']) * 1000
        ];
    }, $notes);

    jsonResponse(['success' => true, 'notes' => $out]);
}

// 2. LƯU GHI CHÚ (THÊM HOẶC SỬA)
if ($action === 'save') {
    $userId = (int)($input['user_id'] ?? 0);
    $noteId = (int)($input['id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
    $color = $input['color'] ?? 'auto';
    $pinned = !empty($input['pinned']) ? 1 : 0;
    $source = $input['source'] ?? null;

    if ($userId <= 0) jsonResponse(['success' => false, 'error' => 'INVALID_USER_ID'], 400);
    if ($title === '' && $content === '') jsonResponse(['success' => false, 'error' => 'EMPTY_NOTE'], 400);

    if ($title === '') $title = mb_substr($content, 0, 40);

    $sourceType = $source['type'] ?? null;
    $sourceRef = $source['ref'] ?? null;

    if ($noteId > 0) {
        $stmt = $pdo->prepare('
            UPDATE notes 
            SET title = ?, content = ?, color = ?, pinned = ?, source_type = ?, source_ref = ?
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$title, $content, $color, $pinned, $sourceType, $sourceRef, $noteId, $userId]);
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO notes (user_id, title, content, color, pinned, source_type, source_ref)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $title, $content, $color, $pinned, $sourceType, $sourceRef]);
        $noteId = (int)$pdo->lastInsertId();
    }

    jsonResponse(['success' => true, 'id' => $noteId]);
}

// 3. GHIM / BỎ GHIM
if ($action === 'pin') {
    $userId = (int)($input['user_id'] ?? 0);
    $noteId = (int)($input['id'] ?? 0);
    $pinned = !empty($input['pinned']) ? 1 : 0;

    $stmt = $pdo->prepare('UPDATE notes SET pinned = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$pinned, $noteId, $userId]);
    jsonResponse(['success' => true]);
}

// 4. XÓA GHI CHÚ
if ($action === 'delete') {
    $userId = (int)($input['user_id'] ?? $_GET['user_id'] ?? 0);
    $noteId = (int)($input['id'] ?? $_GET['id'] ?? 0);

    $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
    $stmt->execute([$noteId, $userId]);
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'INVALID_ACTION'], 400);
