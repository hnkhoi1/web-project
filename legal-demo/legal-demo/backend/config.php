<?php
/**
 * config.php — Kết nối cơ sở dữ liệu MySQL/MariaDB bằng PDO.
 * Chỉnh 4 hằng số bên dưới cho khớp môi trường của bạn (XAMPP/LAMPP...).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'luat_hinh_su');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/** Trả JSON và kết thúc request, luôn set CORS/header phù hợp cho demo cục bộ. */
function json_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
