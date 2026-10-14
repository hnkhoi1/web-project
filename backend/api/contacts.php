<?php
/**
 * backend/api/contacts.php
 * API lưu câu hỏi liên hệ vào MySQL
 */

require_once __DIR__ . '/../config.php';

$pdo = getPDO();
if (!$pdo) {
    jsonResponse(['success' => false, 'error' => 'DATABASE_OFFLINE'], 500);
}

$input = getJsonInput();
$userId = (int)($input['user_id'] ?? 0);
$hoTen = trim($input['ho_ten'] ?? '');
$sdt = trim($input['sdt'] ?? '');
$email = trim($input['email'] ?? '');
$noiDung = trim($input['noi_dung'] ?? '');

if ($hoTen === '' || $email === '' || $noiDung === '') {
    jsonResponse(['success' => false, 'error' => 'MISSING_FIELDS'], 400);
}

$stmt = $pdo->prepare('
    INSERT INTO contacts (user_id, ho_ten, sdt, email, noi_dung)
    VALUES (?, ?, ?, ?, ?)
');
$stmt->execute([$userId > 0 ? $userId : null, $hoTen, $sdt ?: null, $email, $noiDung]);

jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
