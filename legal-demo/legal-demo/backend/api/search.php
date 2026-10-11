<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../nlu/TfidfMatcher.php';

$q      = trim($_GET['q'] ?? '');
$chuong = trim($_GET['chuong'] ?? '');

try {
    $pdo = get_pdo();
    $sql = "SELECT d.id, d.tieu_de, d.tom_tat, d.tu_khoa, d.khung_hinh_phat, c.ten_chuong AS chuong
            FROM dieu_luat d JOIN chuong c ON c.id = d.chuong_id";
    $params = [];
    if ($chuong !== '') {
        $sql .= " WHERE c.ten_chuong = :chuong";
        $params['chuong'] = $chuong;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    json_out(['error' => 'Không kết nối được CSDL. Kiểm tra config.php và đã chạy database/schema.sql chưa.'], 500);
}

if ($q === '') {
    json_out(['results' => $rows]);
}

// Xây chỉ mục TF-IDF trực tiếp từ kết quả truy vấn (tập nhỏ, đủ nhanh cho demo).
// Với dữ liệu lớn hơn, nên tính & lưu vector trước (bảng phụ hoặc cache).
$docs = array_map(fn($r) => [
    'id'   => (int)$r['id'],
    'text' => $r['tieu_de'] . ' ' . $r['tom_tat'] . ' ' . $r['tu_khoa'] . ' ' . $r['chuong'],
], $rows);

$index   = TfidfMatcher::buildIndex($docs);
$ranked  = TfidfMatcher::search($q, $index, 15);
$byId    = [];
foreach ($rows as $r) $byId[(int)$r['id']] = $r;

$results = [];
foreach ($ranked as $r) {
    if ($r['score'] <= 0.03) continue;
    $row = $byId[$r['doc']['id']];
    $row['score'] = round($r['score'], 4);
    $results[] = $row;
}

// Không có điều nào vượt ngưỡng -> quay về so khớp chuỗi con đơn giản (fallback)
if (!$results) {
    $needle = TfidfMatcher::normalize($q);
    foreach ($rows as $row) {
        if (str_contains(TfidfMatcher::normalize($row['tieu_de'] . ' ' . $row['tu_khoa']), $needle)) {
            $results[] = $row;
        }
    }
}

json_out(['results' => $results]);
