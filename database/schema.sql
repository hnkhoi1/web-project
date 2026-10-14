-- ============================================================
-- CƠ SỞ DỮ LIỆU: HÌNH SỰ TRA CỨU (hinh_su_db)
-- Hệ quản trị CSDL: MySQL / MariaDB (phpMyAdmin / XAMPP)
-- Hướng dẫn: Mở phpMyAdmin -> Nhấp tab 'Import' -> Chọn file này
-- ============================================================

CREATE DATABASE IF NOT EXISTS `hinh_su_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `hinh_su_db`;

-- ------------------------------------------------------------
-- 1. BẢNG NGƯỜI DÙNG (users)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `display_name` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar` LONGTEXT NULL,
  `verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_builtin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nạp tài khoản demo mặc định (mật khẩu: 12345)
-- Sử dụng hash SHA-256 + salt hoặc password_hash BCRYPT
INSERT INTO `users` (`id`, `username`, `email`, `display_name`, `password_hash`, `avatar`, `verified`, `is_builtin`, `created_at`) 
VALUES (
  1, 
  'demo', 
  'demo@demo.local', 
  'Tài khoản Demo', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt của '12345'
  NULL, 
  1, 
  1, 
  NOW()
);

-- ------------------------------------------------------------
-- 2. BẢNG GHI CHÚ CÁ NHÂN (notes)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  `color` VARCHAR(20) NOT NULL DEFAULT 'auto',
  `pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `source_type` VARCHAR(50) NULL,
  `source_ref` VARCHAR(50) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ghi chú mẫu cho tài khoản demo
INSERT INTO `notes` (`user_id`, `title`, `content`, `color`, `pinned`, `source_type`, `source_ref`) VALUES
(1, 'Lưu ý Điều 134 — Thương tích', 'Tỉ lệ tổn thương từ 11% đến 30% thì phạt cải tạo không giam giữ đến 3 năm hoặc phạt tù 6 tháng đến 3 năm. Có quyền yêu cầu bồi thường theo Điều 590 BLDS.', 'yellow', 1, 'article', '134'),
(1, 'Cách xử lý khi bị lừa đảo chuyển khoản', 'Thu thập biên lai chuyển tiền, ảnh chụp tin nhắn, số tài khoản nhận tiền và gửi đơn tố giác đến cơ quan công an quận/huyện.', 'blue', 0, 'chat', '174');

-- ------------------------------------------------------------
-- 3. BẢNG LIÊN HỆ & CÂU HỎI (contacts)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `ho_ten` VARCHAR(100) NOT NULL,
  `sdt` VARCHAR(25) NULL,
  `email` VARCHAR(255) NOT NULL,
  `noi_dung` TEXT NOT NULL,
  `trang_thai` VARCHAR(50) NOT NULL DEFAULT 'moi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. BẢNG VĂN BẢN PHÁP LUẬT MỚI (news)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `so_hieu` VARCHAR(100) NOT NULL,
  `ten` TEXT NOT NULL,
  `loai` VARCHAR(50) NOT NULL,
  `co_quan` VARCHAR(150) NOT NULL,
  `ngay_ban_hanh` DATE NOT NULL,
  `ngay_hieu_luc` DATE NOT NULL,
  `tom_tat` TEXT NOT NULL,
  `noi_bat` JSON NULL,
  `dieu_luat_lien_quan` JSON NULL,
  `tags` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nạp sẵn 8 văn bản pháp luật mới
INSERT INTO `news` (`so_hieu`, `ten`, `loai`, `co_quan`, `ngay_ban_hanh`, `ngay_hieu_luc`, `tom_tat`, `noi_bat`, `dieu_luat_lien_quan`, `tags`) VALUES
(
  'Luật số 12/2017/QH14',
  'Luật sửa đổi, bổ sung một số điều của Bộ luật Hình sự số 100/2015/QH13',
  'Luật',
  'Quốc hội',
  '2017-06-20',
  '2018-01-01',
  'Sửa đổi, bổ sung một số tội danh và mức hình phạt trong Bộ luật Hình sự 2015. Đặc biệt sửa đổi các quy định về tội phạm kinh tế, tội phạm tham nhũng và tội phạm về an toàn giao thông.',
  '["Tăng mức hình phạt tội tham ô tài sản", "Bổ sung hành vi phạm tội mới trong lĩnh vực kinh tế số", "Điều chỉnh mức định lượng một số tội phạm ma túy"]',
  '[353, 249, 260]',
  '["hình sự", "tham nhũng", "ma túy", "giao thông"]'
),
(
  'Nghị quyết số 02/2018/NQ-HĐTP',
  'Hướng dẫn áp dụng Điều 65 của Bộ luật Hình sự về án treo',
  'Nghị quyết HĐTP',
  'Hội đồng Thẩm phán TAND Tối cao',
  '2018-05-15',
  '2018-07-01',
  'Hướng dẫn chi tiết điều kiện cho hưởng án treo, thời gian thử thách, nghĩa vụ của người được hưởng án treo và hậu quả pháp lý khi vi phạm điều kiện án treo.',
  '["Làm rõ tiêu chí \\"nhân thân tốt\\"", "Quy định cụ thể về thời gian thử thách", "Hướng dẫn xử lý khi vi phạm trong thời gian thử thách"]',
  '[]',
  '["án treo", "hình phạt", "thử thách"]'
),
(
  'Thông tư số 09/2020/TT-BCA',
  'Quy định về trình tự, thủ tục tiếp nhận, giải quyết tố giác, tin báo về tội phạm và kiến nghị khởi tố của Cơ quan điều tra trong Công an nhân dân',
  'Thông tư',
  'Bộ Công an',
  '2020-09-01',
  '2020-10-22',
  'Quy định rõ trình tự, thủ tục tiếp nhận và giải quyết tố giác, tin báo tội phạm từ công dân. Người tố giác được bảo vệ thông tin cá nhân, được thông báo kết quả giải quyết trong thời hạn quy định.',
  '["Thống nhất quy trình tiếp nhận tố giác", "Bảo vệ người tố giác khỏi bị trả thù", "Thời hạn 20 ngày phải thông báo kết quả"]',
  '[]',
  '["tố giác", "điều tra", "công an"]'
),
(
  'Nghị định số 100/2019/NĐ-CP',
  'Quy định xử phạt vi phạm hành chính trong lĩnh vực giao thông đường bộ và đường sắt',
  'Nghị định',
  'Chính phủ',
  '2019-12-30',
  '2020-01-01',
  'Quy định mức xử phạt vi phạm hành chính về giao thông đường bộ, đặc biệt tăng mạnh mức phạt nồng độ cồn và vi phạm tốc độ. Lái xe có nồng độ cồn ≥ 80mg/100ml máu bị phạt đến 40 triệu đồng và tước giấy phép lái xe 22-24 tháng.',
  '["Phạt nồng độ cồn từ 2 triệu đến 40 triệu đồng", "Tước GPLX đến 24 tháng khi vi phạm nghiêm trọng", "Phạt ma túy khi tham gia giao thông"]',
  '[260]',
  '["giao thông", "nồng độ cồn", "phạt tiền", "GPLX"]'
),
(
  'Luật số 15/2023/QH15',
  'Luật Phòng, chống rửa tiền (sửa đổi)',
  'Luật',
  'Quốc hội',
  '2023-06-16',
  '2024-07-01',
  'Sửa đổi, bổ sung quy định về phòng, chống rửa tiền nhằm đáp ứng yêu cầu của FATF. Mở rộng đối tượng báo cáo, tăng cường kiểm soát giao dịch đáng ngờ và xử lý tài sản có nguồn gốc từ tội phạm.',
  '["Mở rộng đối tượng báo cáo giao dịch đáng ngờ", "Tăng cường kiểm soát tài sản ảo (crypto)", "Xử lý nghiêm hành vi che giấu nguồn gốc tài sản"]',
  '[]',
  '["rửa tiền", "tài sản", "FATF", "tài sản ảo"]'
),
(
  'Nghị quyết số 03/2019/NQ-HĐTP',
  'Hướng dẫn áp dụng Điều 324 của Bộ luật Hình sự về tội rửa tiền',
  'Nghị quyết HĐTP',
  'Hội đồng Thẩm phán TAND Tối cao',
  '2019-09-24',
  '2019-11-07',
  'Hướng dẫn xác định các hành vi cấu thành tội rửa tiền, phân biệt tội rửa tiền với một số tội phạm liên quan, và hướng dẫn tịch thu tài sản do phạm tội mà có.',
  '["Xác định rõ \\"tài sản do phạm tội mà có\\"", "Phân biệt rửa tiền với chứa chấp/tiêu thụ tài sản", "Hướng dẫn tịch thu toàn bộ tài sản liên quan"]',
  '[]',
  '["rửa tiền", "tịch thu tài sản"]'
),
(
  'Luật số 26/2022/QH15',
  'Luật Phòng, chống bạo lực gia đình (sửa đổi)',
  'Luật',
  'Quốc hội',
  '2022-11-14',
  '2023-07-01',
  'Mở rộng khái niệm bạo lực gia đình, bổ sung các biện pháp bảo vệ nạn nhân và chế tài xử lý người có hành vi bạo lực. Bổ sung biện pháp cấm tiếp xúc, yêu cầu giữ khoảng cách tối thiểu.',
  '["Bổ sung bạo lực kinh tế và kiểm soát tài chính vào định nghĩa", "Lệnh cấm tiếp xúc có thể áp dụng ngay", "Tăng trách nhiệm của chính quyền địa phương"]',
  '[134]',
  '["bạo lực gia đình", "bảo vệ nạn nhân", "cấm tiếp xúc"]'
),
(
  'Thông tư liên tịch số 01/2017/TTLT-BCA-BQP-BTC-BNN&PTNT-VKSNDTC',
  'Hướng dẫn về quan hệ phối hợp giữa các cơ quan trong tiếp nhận, giải quyết tố giác, tin báo về tội phạm và kiến nghị khởi tố',
  'Thông tư liên tịch',
  'Liên bộ (BCA, BQP, BTC, BNN, VKSNDTC)',
  '2017-12-29',
  '2018-02-15',
  'Quy định cơ chế phối hợp giữa cơ quan điều tra, viện kiểm sát và các bộ ngành trong tiếp nhận và xử lý tố giác tội phạm, đảm bảo không bỏ lọt tội phạm và không oan sai.',
  '["Phân định thẩm quyền tiếp nhận tố giác", "Quy trình chuyển giao hồ sơ giữa các cơ quan", "Thời hạn giải quyết và báo cáo"]',
  '[]',
  '["điều tra", "phối hợp", "tố giác", "kiến nghị khởi tố"]'
);
