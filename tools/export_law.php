<?php
declare(strict_types=1);

// This generator overwrites project data files, so it must never be exposed as
// a writable web endpoint. Use the XAMPP PHP CLI instead of opening this URL.
if (PHP_SAPI !== 'cli') {
    http_response_code(405);
    header('Allow: CLI');
    header('Content-Type: text/plain; charset=utf-8');
    echo "Exporter chỉ chạy bằng PHP CLI. Ví dụ: php tools/export_law.php\n";
    exit;
}

// Sinh các bản dữ liệu tiêu thụ từ data/dieu_luat_data.php. Không sửa tay output.
$root = dirname(__DIR__);
$data = require $root . '/data/dieu_luat_data.php';
if (!is_array($data)) {
    fwrite(STDERR, "Nguồn điều luật không phải mảng.\n");
    exit(1);
}

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
$js = "/* FILE SINH TỰ ĐỘNG — KHÔNG SỬA TAY. Nguồn: data/dieu_luat_data.php */\n" .
      "const LAW_DATA = " . $json . ";\n";
file_put_contents($root . '/js/data.js', $js);

function sqlValue($value): string
{
    if ($value === null) return 'NULL';
    if (is_bool($value)) return $value ? '1' : '0';
    if (is_int($value) || is_float($value)) return (string)$value;
    return "'" . str_replace("'", "''", (string)$value) . "'";
}

$lines = [
    '-- FILE SINH TỰ ĐỘNG — KHÔNG SỬA TAY. Nguồn: data/dieu_luat_data.php',
    'SET NAMES utf8mb4;',
    'START TRANSACTION;',
];
$chapters = [];
foreach ($data as $law) $chapters[$law['chuong']] = true;
foreach (array_keys($chapters) as $i => $chapter) {
    $lines[] = 'INSERT INTO `chuong` (`ten_chuong`, `so_thu_tu`) VALUES (' . sqlValue($chapter) . ', ' . ($i + 1) . ') ON DUPLICATE KEY UPDATE `ten_chuong`=VALUES(`ten_chuong`), `so_thu_tu`=VALUES(`so_thu_tu`);';
}
foreach ($data as $law) {
    $fields = ['id', 'chuong_id', 'tieu_de', 'tom_tat', 'tu_khoa', 'khung_hinh_phat', 'can_cu_boi_thuong', 'ghi_chu_quyen_loi'];
    $values = [sqlValue($law['id']), '(SELECT `id` FROM `chuong` WHERE `ten_chuong`=' . sqlValue($law['chuong']) . ')'];
    foreach (array_slice($fields, 2) as $field) $values[] = sqlValue($law[$field] ?? null);
    $update = [];
    foreach (array_slice($fields, 1) as $field) $update[] = '`' . $field . '`=VALUES(`' . $field . '`)';
    $lines[] = 'INSERT INTO `dieu_luat` (`' . implode('`,`', $fields) . '`) VALUES (' . implode(', ', $values) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $update) . ';';
    $clauseNumbers = [];
    foreach ($law['khoan'] as $k) {
        $columns = ['dieu_luat_id', 'so_khoan', 'dieu_kien', 'noi_dung', 'quyen_loi', 'tu_khoa', 'ti_le_ton_thuong_min', 'ti_le_ton_thuong_max', 'gia_tri_tai_san_min', 'gia_tri_tai_san_max', 'so_nguoi_chet_min'];
        $vals = [$law['id'], $k['so_khoan']];
        foreach (array_slice($columns, 2) as $field) $vals[] = $k[$field] ?? null;
        $updates = [];
        foreach (array_slice($columns, 2) as $column) $updates[] = '`' . $column . '`=VALUES(`' . $column . '`)';
        $lines[] = 'INSERT INTO `khoan` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(', ', array_map('sqlValue', $vals)) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates) . ';';
        $clauseNumbers[] = (int)$k['so_khoan'];
    }
    // Remove clauses deleted from the source while retaining upsert behavior
    // for existing clauses and their stable database IDs.
    if ($clauseNumbers) {
        $lines[] = 'DELETE FROM `khoan` WHERE `dieu_luat_id`=' . (int)$law['id'] . ' AND `so_khoan` NOT IN (' . implode(', ', $clauseNumbers) . ');';
    } else {
        $lines[] = 'DELETE FROM `khoan` WHERE `dieu_luat_id`=' . (int)$law['id'] . ';';
    }
}
$lines[] = 'COMMIT;';
file_put_contents($root . '/database/seed_law.sql', implode("\n", $lines) . "\n");
fwrite(STDOUT, "Đã sinh js/data.js và database/seed_law.sql từ " . count($data) . " điều luật.\n");
