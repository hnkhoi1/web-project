<?php
/**
 * backend/api/news.php
 * API lấy danh sách văn bản pháp luật mới từ MySQL
 */

require_once __DIR__ . '/../config.php';

$pdo = getPDO();
if (!$pdo) {
    jsonResponse(['success' => false, 'error' => 'DATABASE_OFFLINE'], 500);
}

$rows = $pdo->query('SELECT * FROM news ORDER BY ngay_ban_hanh DESC')->fetchAll();

$out = array_map(function($r) {
    return [
        'id' => (int)$r['id'],
        'so_hieu' => $r['so_hieu'],
        'ten' => $r['ten'],
        'loai' => $r['loai'],
        'co_quan' => $r['co_quan'],
        'ngay_ban_hanh' => $r['ngay_ban_hanh'],
        'ngay_hieu_luc' => $r['ngay_hieu_luc'],
        'tom_tat' => $r['tom_tat'],
        'noi_bat' => $r['noi_bat'] ? json_decode($r['noi_bat'], true) : [],
        'dieu_luat_lien_quan' => $r['dieu_luat_lien_quan'] ? json_decode($r['dieu_luat_lien_quan'], true) : [],
        'tags' => $r['tags'] ? json_decode($r['tags'], true) : []
    ];
}, $rows);

jsonResponse(['success' => true, 'news' => $out]);
