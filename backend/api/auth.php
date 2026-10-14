<?php
/**
 * backend/api/auth.php
 * API xác thực và quản lý tài khoản với MySQL
 */

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = getJsonInput();
if (empty($action) && isset($input['action'])) {
    $action = $input['action'];
}

$pdo = getPDO();

// Kiểm tra trạng thái MySQL
if ($action === 'status') {
    if ($pdo) {
        jsonResponse([
            'success' => true,
            'mode' => 'mysql',
            'message' => 'Đã kết nối cơ sở dữ liệu MySQL thành công (hinh_su_db).'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'mode' => 'local',
            'message' => 'Chưa kết nối được MySQL. Đang hoạt động ở chế độ LocalStorage.'
        ]);
    }
}

// Nếu không có kết nối DB
if (!$pdo) {
    jsonResponse([
        'success' => false,
        'error' => 'DATABASE_OFFLINE',
        'message' => 'Không thể kết nối đến máy chủ MySQL. Vui lòng bật MySQL trong XAMPP.'
    ], 500);
}

// 1. ĐĂNG NHẬP
if ($action === 'login') {
    $ident = trim($input['ident'] ?? '');
    $password = $input['password'] ?? '';

    if ($ident === '' || $password === '') {
        jsonResponse(['success' => false, 'error' => 'MISSING_FIELDS', 'message' => 'Vui lòng nhập đầy đủ thông tin.'], 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1');
    $stmt->execute([$ident, $ident]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'INVALID_CREDENTIALS', 'message' => 'Tài khoản không tồn tại.'], 401);
    }

    $passOk = false;
    // Kiểm tra bcrypt hoặc mật khẩu thuần '12345'
    if (password_verify($password, $user['password_hash']) || $password === '12345' && $user['username'] === 'demo') {
        $passOk = true;
    }

    if (!$passOk) {
        jsonResponse(['success' => false, 'error' => 'INVALID_CREDENTIALS', 'message' => 'Mật khẩu không chính xác.'], 401);
    }

    // Định dạng dữ liệu trả về cho Frontend
    $userData = [
        'id' => (string)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'displayName' => $user['display_name'],
        'avatar' => $user['avatar'],
        'verified' => (bool)$user['verified'],
        'builtIn' => (bool)$user['is_builtin'],
        'createdAt' => strtotime($user['created_at']) * 1000
    ];

    jsonResponse(['success' => true, 'user' => $userData]);
}

// 2. ĐĂNG KÝ
if ($action === 'register') {
    $email = strtolower(trim($input['email'] ?? ''));
    $displayName = trim($input['displayName'] ?? '');
    $password = $input['password'] ?? '';

    if ($email === '' || $displayName === '' || $password === '') {
        jsonResponse(['success' => false, 'error' => 'MISSING_FIELDS'], 400);
    }

    // Kiểm tra trùng email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'error' => 'EMAIL_EXISTS', 'message' => 'Email này đã được sử dụng.'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (email, display_name, password_hash, verified, is_builtin) VALUES (?, ?, ?, 0, 0)');
    $stmt->execute([$email, $displayName, $hash]);
    $newId = $pdo->lastInsertId();

    $userData = [
        'id' => (string)$newId,
        'email' => $email,
        'displayName' => $displayName,
        'avatar' => null,
        'verified' => false,
        'builtIn' => false,
        'createdAt' => time() * 1000
    ];

    jsonResponse(['success' => true, 'user' => $userData]);
}

// 3. CẬP NHẬT HỒ SƠ
if ($action === 'update_profile') {
    $id = (int)($input['id'] ?? 0);
    $displayName = trim($input['displayName'] ?? '');
    $avatar = $input['avatar'] ?? null;
    $verified = isset($input['verified']) ? (int)$input['verified'] : null;

    $stmt = $pdo->prepare('SELECT is_builtin FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) jsonResponse(['success' => false, 'error' => 'USER_NOT_FOUND'], 404);
    if ($u['is_builtin']) jsonResponse(['success' => false, 'error' => 'DEMO_LOCKED'], 403);

    $fields = [];
    $params = [];
    if ($displayName !== '') { $fields[] = 'display_name = ?'; $params[] = $displayName; }
    if (array_key_exists('avatar', $input)) { $fields[] = 'avatar = ?'; $params[] = $avatar; }
    if ($verified !== null) { $fields[] = 'verified = ?'; $params[] = $verified; }

    if (!empty($fields)) {
        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);
    }

    jsonResponse(['success' => true]);
}

// 4. ĐỔI MẬT KHẨU
if ($action === 'change_password') {
    $id = (int)($input['id'] ?? 0);
    $current = $input['current'] ?? '';
    $next = $input['next'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) jsonResponse(['success' => false, 'error' => 'USER_NOT_FOUND'], 404);
    if ($u['is_builtin']) jsonResponse(['success' => false, 'error' => 'DEMO_LOCKED'], 403);

    if (!password_verify($current, $u['password_hash'])) {
        jsonResponse(['success' => false, 'error' => 'WRONG_CURRENT_PASSWORD', 'message' => 'Mật khẩu hiện tại không đúng.'], 400);
    }

    $newHash = password_hash($next, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $id]);
    jsonResponse(['success' => true]);
}

// 5. XÓA TÀI KHOẢN
if ($action === 'delete_account') {
    $id = (int)($input['id'] ?? 0);
    $password = $input['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) jsonResponse(['success' => false, 'error' => 'USER_NOT_FOUND'], 404);
    if ($u['is_builtin']) jsonResponse(['success' => false, 'error' => 'DEMO_LOCKED'], 403);

    if (!password_verify($password, $u['password_hash'])) {
        jsonResponse(['success' => false, 'error' => 'WRONG_PASSWORD', 'message' => 'Mật khẩu không đúng.'], 400);
    }

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

// 6. DANH SÁCH USER (CHO DATABASE.HTML)
if ($action === 'list_users') {
    $users = $pdo->query('SELECT id, username, email, display_name, verified, is_builtin, created_at FROM users ORDER BY id ASC')->fetchAll();
    $out = array_map(function($u) {
        return [
            'id' => (string)$u['id'],
            'username' => $u['username'],
            'email' => $u['email'],
            'displayName' => $u['display_name'],
            'verified' => (bool)$u['verified'],
            'builtIn' => (bool)$u['is_builtin']
        ];
    }, $users);

    $notesCount = $pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn();
    $contactsCount = $pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();

    jsonResponse([
        'success' => true,
        'users' => $out,
        'stats' => [
            'users' => count($out),
            'notes' => (int)$notesCount,
            'contacts' => (int)$contactsCount
        ]
    ]);
}

// 7. RESET TÀI KHOẢN THÊM MỚI
if ($action === 'reset_users') {
    $pdo->exec('DELETE FROM users WHERE is_builtin = 0');
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'INVALID_ACTION'], 400);
