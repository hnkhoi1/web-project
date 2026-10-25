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
DELETE FROM `chuong` WHERE `id` IN (1, 2, 3);
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

-- ------------------------------------------------------------
-- 4. DANH MỤC CHƯƠNG LUẬT MẪU (chuong)
-- ------------------------------------------------------------
INSERT INTO `chuong` (`id`, `ten_chuong`, `so_thu_tu`, `mo_ta`)
VALUES
(
  1,
  'Các tội xâm phạm tính mạng, sức khỏe, nhân phẩm, danh dự của con người',
  14,
  'Quy định về tội giết người, cố ý gây thương tích, đe dọa giết người, làm nhục người khác...'
),
(
  2,
  'Các tội xâm phạm quyền tự do của con người, quyền tự do, dân chủ của công dân',
  15,
  'Quy định về tội bắt giữ người trái pháp luật, xâm phạm chỗ ở, quyền khiếu nại tố cáo...'
),
(
  3,
  'Các tội xâm phạm sở hữu',
  16,
  'Quy định về tội cướp tài sản, trộm cắp tài sản, lừa đảo chiếm đoạt tài sản, lạm dụng tín nhiệm...'
);

-- ------------------------------------------------------------
-- 5. TIN LIÊN HỆ GÓP Ý MẪU (contacts)
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
