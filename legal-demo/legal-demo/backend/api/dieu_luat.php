<?php
require_once __DIR__ . '/../config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    json_out(['error' => 'Thiếu tham số id.'], 400);
}

try {
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        "SELECT d.*, c.ten_chuong AS chuong
         FROM dieu_luat d JOIN chuong c ON c.id = d.chuong_id
         WHERE d.id = :id"
    );
    $stmt->execute(['id' => $id]);
    $article = $stmt->fetch();
    if (!$article) {
        json_out(['error' => 'Không tìm thấy điều luật.'], 404);
    }

    $stmt = $pdo->prepare("SELECT so_khoan, noi_dung FROM khoan WHERE dieu_luat_id = :id ORDER BY so_khoan");
    $stmt->execute(['id' => $id]);
    $article['khoan'] = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT id, tieu_de FROM dieu_luat WHERE chuong_id = :cid AND id != :id LIMIT 4"
    );
    $stmt->execute(['cid' => $article['chuong_id'], 'id' => $id]);
    $article['lien_quan'] = $stmt->fetchAll();

    json_out(['article' => $article]);
} catch (Throwable $e) {
    json_out(['error' => 'Không kết nối được CSDL. Kiểm tra config.php và đã chạy database/schema.sql chưa.'], 500);
}
