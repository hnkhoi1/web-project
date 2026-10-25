-- ============================================================
-- DỮ LIỆU MẪU: HÌNH SỰ TRA CỨU (seed.sql)
-- Chứa tài khoản demo, tài khoản admin, tùy chọn và ghi chú mẫu.
-- Đảm bảo tính Idempotent: Import nhiều lần vẫn không phát sinh lỗi.
-- ============================================================

USE `hinh_su_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Xóa dữ liệu mẫu trước đó nếu có để import lại không bị trùng lặp khóa chính / UNIQUE
DELETE FROM `user_prefs` WHERE `user_id` IN (1, 2);
DELETE FROM `notes` WHERE `user_id` IN (1, 2);
DELETE FROM `users` WHERE `id` IN (1, 2) OR `email` IN ('demo@demo.local', 'admin@hinhsutracuu.vn') OR `username` IN ('demo', 'admin');
DELETE FROM `contacts` WHERE `id` IN (1, 2);

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. TÀI KHOẢN NGƯỜI DÙNG MẪU (users)
-- Mật khẩu được băm đúng chuẩn bcrypt bằng password_hash(PASSWORD_BCRYPT):
-- - demo:  '12345'     -> $2y$10$3HYQuMqH7QvvtPc0LcrVoumu.z.diuPf9i9KKOZJElNsFJTKKr8Ni
-- - admin: 'Admin@123' -> $2y$10$Xk4q2ryw0QcrmS1BQmJU3e3nuTX.PIBsAYuw84Y/8LeBf348o192u
-- ------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `display_name`, `password_hash`, `avatar`, `verified`, `is_builtin`, `role`, `created_at`)
VALUES
(
  1,
  'demo',
  'demo@demo.local',
  'Tài khoản Demo',
  '$2y$10$3HYQuMqH7QvvtPc0LcrVoumu.z.diuPf9i9KKOZJElNsFJTKKr8Ni',
  NULL,
  1,
  1,
  'user',
  NOW()
),
(
  2,
  'admin',
  'admin@hinhsutracuu.vn',
  'Quản trị viên Hệ thống',
  '$2y$10$Xk4q2ryw0QcrmS1BQmJU3e3nuTX.PIBsAYuw84Y/8LeBf348o192u',
  NULL,
  1,
  0,
  'admin',
  NOW()
);

-- ------------------------------------------------------------
-- 2. TÙY CHỌN GIAO DIỆN MẪU (user_prefs)
-- ------------------------------------------------------------
INSERT INTO `user_prefs` (`user_id`, `theme`, `font`, `note_color`)
VALUES
(1, 'system', 'md', 'yellow'),
(2, 'dark',   'md', 'blue');

-- ------------------------------------------------------------
-- 3. GHI CHÚ MẪU (notes)
-- ------------------------------------------------------------
INSERT INTO `notes` (`id`, `user_id`, `title`, `content`, `color`, `pinned`, `source_type`, `source_ref`, `created_at`)
VALUES
(
  1,
  1,
  'Lưu ý Điều 134 — Tội cố ý gây thương tích',
  'Tỉ lệ tổn thương cơ thể từ 11% đến 30% thì bị phạt cải tạo không giam giữ đến 3 năm hoặc phạt tù từ 6 tháng đến 3 năm. Người bị hại có quyền yêu cầu bồi thường thiệt hại theo Điều 590 Bộ luật Dân sự 2015.',
  'yellow',
  1,
  'article',
  '134',
  NOW()
),
(
  2,
  1,
  'Hướng dẫn xử lý khi bị lừa đảo trực tuyến',
  'Thu thập ngay các chứng cứ: biên lai chuyển khoản, ảnh chụp màn hình tin nhắn, thông tin tài khoản nhận tiền và gửi đơn tố giác đến Cơ quan Cảnh sát điều tra Công an cấp quận/huyện theo Điều 174 BLHS.',
  'blue',
  0,
  'chat',
  '174',
  NOW()
);

-- Ghi chú: dữ liệu chương / điều luật / khoản KHÔNG nằm ở file này.
-- Chúng được nạp bởi database/seed_law.sql (Khôi phụ trách, sinh tự động
-- từ data/dieu_luat_data.php bằng tools/export_law.php).

-- ------------------------------------------------------------
-- 4. TIN LIÊN HỆ GÓP Ý MẪU (contacts)
-- ------------------------------------------------------------
INSERT INTO `contacts` (`id`, `user_id`, `ho_ten`, `sdt`, `email`, `noi_dung`, `trang_thai`, `created_at`)
VALUES
(
  1,
  1,
  'Nguyễn Văn Demo',
  '0901234567',
  'demo@demo.local',
  'Hệ thống tra cứu rất trực quan và hữu ích cho việc tìm hiểu quy định pháp luật hình sự.',
  'moi',
  NOW()
);
