<?php
require_once __DIR__ . '/../config.php';

$body = read_json_body();
$ho_ten   = trim($body['ho_ten'] ?? '');
$sdt      = trim($body['sdt'] ?? '');
$email    = trim($body['email'] ?? '');
$noi_dung = trim($body['noi_dung'] ?? '');

if ($ho_ten === '' || $email === '' || $noi_dung === '') {
    json_out(['error' => 'Vui lòng nhập đầy đủ họ tên, email và nội dung.'], 400);
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO lien_he (ho_ten, sdt, email, noi_dung) VALUES (:ho_ten, :sdt, :email, :noi_dung)'
    );
    $stmt->execute(compact('ho_ten', 'sdt', 'email', 'noi_dung'));
    json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    json_out(['error' => 'Không kết nối được CSDL. Kiểm tra config.php và đã chạy database/schema.sql chưa.'], 500);
}
