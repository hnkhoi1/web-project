<?php
declare(strict_types=1);

// API luật T23. Nguồn nội dung luật duy nhất là data/dieu_luat_data.php;
// MySQL chỉ nhận lịch sử hỏi đáp. Không nhận user_id từ trình duyệt.
$root = dirname(__DIR__, 2);
$laws = require $root . '/data/dieu_luat_data.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function ok(mixed $data): never { respond(200, ['ok' => true, 'data' => $data]); }
function fail(string $code, string $message, int $status): never {
    respond($status, ['ok' => false, 'error' => $code, 'message' => $message]);
}
function lawPdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $localFile = dirname(__DIR__) . '/config.local.php';
    $config = is_file($localFile) ? require $localFile : [];
    if (!is_array($config)) $config = [];
    $host = (string)($config['host'] ?? getenv('HS_DB_HOST') ?: '127.0.0.1');
    $name = (string)($config['database'] ?? getenv('HS_DB_NAME') ?: 'hinh_su_db');
    $user = (string)($config['username'] ?? getenv('HS_DB_USER') ?: 'root');
    $pass = (string)($config['password'] ?? getenv('HS_DB_PASSWORD') ?: '');
    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}
$action = (string)($_GET['action'] ?? 'list');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($action === 'csrf' && $method === 'GET') {
    $_SESSION['law_csrf'] ??= bin2hex(random_bytes(32));
    ok(['csrf' => $_SESSION['law_csrf']]);
}

if ($action === 'ask' && $method === 'POST') {
    $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['law_csrf']) || !hash_equals((string)$_SESSION['law_csrf'], $token)) {
        fail('CSRF', 'Mã bảo vệ không hợp lệ. Tải lại trang và thử lại.', 403);
    }
    $body = json_decode(file_get_contents('php://input') ?: '', true);
    $question = trim((string)($body['question'] ?? ''));
    if ($question === '' || mb_strlen($question) > 1000) fail('VALIDATION', 'Câu hỏi phải có từ 1 đến 1000 ký tự.', 422);
    $answer = is_array($body['answer'] ?? null) ? $body['answer'] : [];
    $articleId = filter_var($answer['article_id'] ?? null, FILTER_VALIDATE_INT);
    $knownIds = array_column($laws, 'id');
    if (!$articleId || !in_array($articleId, $knownIds, true)) $articleId = null;
    $clause = filter_var($answer['khoan_so'] ?? null, FILTER_VALIDATE_INT);
    $score = is_numeric($answer['score'] ?? null) ? (float)$answer['score'] : null;
    if ($score !== null) $score = max(0, min(1, $score));
    try {
        $pdo = lawPdo();
        // Tài khoản demo hiện chạy localStorage nên không có users.id phía server.
        // Khi T11 cung cấp session uid dạng số, lịch sử sẽ tự liên kết tài khoản đó.
        $userId = filter_var($_SESSION['uid'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $stmt = $pdo->prepare('INSERT INTO lich_su_hoi_dap (user_id, cau_hoi, dieu_luat_id, khoan_so, diem) VALUES (:uid, :question, :law, :clause, :score)');
        $stmt->execute(['uid' => $userId, 'question' => $question, 'law' => $articleId, 'clause' => $clause ?: null, 'score' => $score]);
        ok(['id' => (int)$pdo->lastInsertId(), 'saved' => true]);
    } catch (Throwable $e) {
        error_log('law.php ask logging failed: ' . $e->getMessage());
        fail('STORAGE', 'Không lưu được lịch sử. Kiểm tra MySQL, cấu hình kết nối và import schema.sql.', 503);
    }
}

if ($method !== 'GET') fail('METHOD', 'Phương thức không được hỗ trợ.', 405);
if ($action === 'chuong') {
    ok(array_values(array_unique(array_column($laws, 'chuong'))));
}
if ($action === 'get') {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    foreach ($laws as $law) if ((int)$law['id'] === $id) {
        $law['lien_quan'] = array_values(array_slice(array_filter($laws, fn($x) => $x['chuong'] === $law['chuong'] && $x['id'] !== $law['id']), 0, 4));
        ok($law);
    }
    fail('NOT_FOUND', 'Không tìm thấy điều luật.', 404);
}
if ($action === 'list') {
    $query = mb_strtolower(trim((string)($_GET['q'] ?? '')));
    $chapter = trim((string)($_GET['chuong'] ?? ''));
    $result = array_values(array_filter($laws, static function ($law) use ($query, $chapter) {
        if ($chapter !== '' && $law['chuong'] !== $chapter) return false;
        if ($query === '') return true;
        $haystack = mb_strtolower($law['id'] . ' ' . $law['tieu_de'] . ' ' . $law['tom_tat'] . ' ' . $law['tu_khoa']);
        return mb_strpos($haystack, $query) !== false;
    }));
    ok($result);
}
fail('ACTION', 'Action không hợp lệ.', 400);
