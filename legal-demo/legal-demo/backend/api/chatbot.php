<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../nlu/TfidfMatcher.php';

$body     = read_json_body();
$question = trim($body['question'] ?? '');

if ($question === '') {
    json_out(['error' => 'Thiếu nội dung câu hỏi.'], 400);
}

try {
    $pdo  = get_pdo();
    $rows = $pdo->query(
        "SELECT d.id, d.tieu_de, d.tom_tat, d.tu_khoa, d.khung_hinh_phat, c.ten_chuong AS chuong
         FROM dieu_luat d JOIN chuong c ON c.id = d.chuong_id"
    )->fetchAll();
} catch (Throwable $e) {
    json_out(['error' => 'Không kết nối được CSDL. Kiểm tra config.php và đã chạy database/schema.sql chưa.'], 500);
}

$docs = array_map(fn($r) => [
    'id'   => (int)$r['id'],
    'text' => $r['tieu_de'] . ' ' . $r['tom_tat'] . ' ' . $r['tu_khoa'] . ' ' . $r['chuong'],
], $rows);

$index  = TfidfMatcher::buildIndex($docs);
$ranked = TfidfMatcher::search($question, $index, 3);

$byId = [];
foreach ($rows as $r) $byId[(int)$r['id']] = $r;

if (!$ranked || $ranked[0]['score'] < 0.03) {
    $answer = 'Xin lỗi, hệ thống chưa tìm thấy điều luật phù hợp rõ ràng với câu hỏi này. '
            . 'Bạn có thể mô tả cụ thể hơn hành vi, tình huống, hoặc thử tra cứu trực tiếp ở trang Tra cứu.';
    log_hoi_dap($pdo, $question, null, null);
    json_out(['answer_html' => $answer, 'matches' => []]);
}

$best  = $byId[$ranked[0]['doc']['id']];
$score = $ranked[0]['score'];

$others = array_slice($ranked, 1, 2);
$othersText = '';
if ($others) {
    $names = array_map(fn($o) => "Điều {$o['doc']['id']} ({$byId[$o['doc']['id']]['tieu_de']})", $others);
    $othersText = '<br><br>Ngoài ra bạn có thể tham khảo thêm: ' . implode(', ', $names) . '.';
}

$answer = sprintf(
    'Dựa trên câu hỏi của bạn, tình huống này có thể liên quan đến <b>Điều %d. %s</b> (%s).<br><br>%s<br><br>'
    . '<b>Khung hình phạt tham khảo:</b> %s%s'
    . '<span class="cite">Nguồn tham khảo: <a href="dieu-luat-chi-tiet.html?id=%d">Điều %d — xem chi tiết &amp; các khoản</a></span>',
    $best['id'], htmlspecialchars($best['tieu_de']), htmlspecialchars($best['chuong']),
    htmlspecialchars($best['tom_tat']), htmlspecialchars($best['khung_hinh_phat']),
    $othersText, $best['id'], $best['id']
);

log_hoi_dap($pdo, $question, (int)$best['id'], $score);

json_out([
    'answer_html' => $answer,
    'matches'     => array_map(fn($r) => $byId[$r['doc']['id']]['id'], $ranked),
]);

function log_hoi_dap(PDO $pdo, string $question, ?int $dieuLuatId, ?float $score): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO lich_su_hoi_dap (cau_hoi, dieu_luat_id, diem_tuong_dong) VALUES (:q, :d, :s)'
        );
        $stmt->execute(['q' => $question, 'd' => $dieuLuatId, 's' => $score]);
    } catch (Throwable $e) {
        // Ghi log là phụ — không làm hỏng response nếu lỗi.
    }
}
