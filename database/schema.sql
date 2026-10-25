-- ============================================================
-- CƠ SỞ DỮ LIỆU: HÌNH SỰ TRA CỨU (hinh_su_db)
-- Hệ quản trị CSDL: MySQL 8.0+ / MariaDB 10.4+ (XAMPP / phpMyAdmin)
-- Bảng mã: utf8mb4 / Đối chiếu: utf8mb4_unicode_ci
-- Engine: InnoDB (Hỗ trợ khóa ngoại và giao dịch ACID)
--
-- ⚠ CHỈ CHẠY FILE NÀY KHI CÀI MỚI: file bắt đầu bằng DROP TABLE nên chạy lại
--   sẽ XÓA TOÀN BỘ DỮ LIỆU. CSDL đã có dữ liệu thì chỉ chạy các file mới
--   trong database/migrations/.
-- Thứ tự import khi cài mới: schema.sql -> seed.sql -> seed_law.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `hinh_su_db`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `hinh_su_db`;

-- Vô hiệu hóa kiểm tra khóa ngoại tạm thời để cho phép drop/create theo thứ tự tùy ý
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `lich_su_hoi_dap`;
DROP TABLE IF EXISTS `khoan`;
DROP TABLE IF EXISTS `dieu_luat`;
DROP TABLE IF EXISTS `chuong`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `notes`;
DROP TABLE IF EXISTS `user_prefs`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. BẢNG NGƯỜI DÙNG (users)
-- Quản lý tài khoản người dùng và quản trị viên
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NULL COMMENT 'Tên đăng nhập (dành cho demo/admin hoặc tùy chọn)',
  `email` VARCHAR(255) UNIQUE NOT NULL COMMENT 'Email đăng nhập chính thức',
  `display_name` VARCHAR(100) NOT NULL COMMENT 'Họ tên hiển thị',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Mật khẩu băm chuẩn bcrypt',
  `avatar` MEDIUMTEXT NULL COMMENT 'Ảnh đại diện Base64 (data:image/...) hoặc URL',
  `verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Chưa xác thực, 1: Đã xác thực email',
  `is_builtin` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Tài khoản hệ thống (demo), không thể sửa/xóa',
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user' COMMENT 'Vai trò: user hoặc admin',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. BẢNG TÙY CHỌN GIAO DIỆN (user_prefs)
-- Lưu cấu hình giao diện cá nhân cho từng người dùng
-- Khóa ngoại user_id ON DELETE CASCADE
-- ------------------------------------------------------------
CREATE TABLE `user_prefs` (
  `user_id` INT PRIMARY KEY,
  `theme` VARCHAR(20) NOT NULL DEFAULT 'system' COMMENT 'Chủ đề: system | light | dark',
  `font` VARCHAR(10) NOT NULL DEFAULT 'md' COMMENT 'Cỡ chữ: sm | md | lg | xl',
  `note_color` VARCHAR(20) NOT NULL DEFAULT 'yellow' COMMENT 'Màu ghi chú mặc định: yellow | blue | green | red | purple',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_user_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. BẢNG GHI CHÚ CÁ NHÂN (notes)
-- Lưu ghi chú của người dùng khi tra cứu luật và tư vấn
-- Khóa ngoại user_id ON DELETE CASCADE
-- ------------------------------------------------------------
CREATE TABLE `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL COMMENT 'ID người sở hữu ghi chú',
  `title` VARCHAR(255) NOT NULL COMMENT 'Tiêu đề ghi chú (tối đa 120 ký tự theo validate)',
  `content` TEXT NULL COMMENT 'Nội dung ghi chú (tối đa 5000 ký tự)',
  `color` VARCHAR(20) NOT NULL DEFAULT 'yellow' COMMENT 'Màu thẻ ghi chú',
  `pinned` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Ghim lên đầu danh sách',
  `source_type` VARCHAR(50) NULL COMMENT 'Nguồn tạo: article (từ điều luật) | chat (từ tư vấn) | manual',
  `source_ref` VARCHAR(50) NULL COMMENT 'Mã tham chiếu (ví dụ id điều luật)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notes_user_pinned` (`user_id`, `pinned`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. BẢNG LIÊN HỆ & GÓP Ý (contacts)
-- Lưu tin nhắn liên hệ gửi từ trang Liên hệ
-- ------------------------------------------------------------
CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL COMMENT 'Người gửi nếu đã đăng nhập - NULL nếu là khách',
  `ho_ten` VARCHAR(100) NOT NULL COMMENT 'Họ và tên người gửi',
  `sdt` VARCHAR(25) NULL COMMENT 'Số điện thoại',
  `email` VARCHAR(255) NOT NULL COMMENT 'Địa chỉ email phản hồi',
  `noi_dung` TEXT NOT NULL COMMENT 'Nội dung câu hỏi hoặc góp ý',
  `trang_thai` ENUM('moi', 'da_tra_loi') NOT NULL DEFAULT 'moi' COMMENT 'Trạng thái xử lý',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_contacts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_contacts_status` (`trang_thai`),
  INDEX `idx_contacts_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. BẢNG THEO DÕI ĐĂNG NHẬP THẤT BẠI (login_attempts)
-- Khóa tài khoản tạm thời (rate limiting) sau 5 lần nhập sai
-- ------------------------------------------------------------
CREATE TABLE `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ident` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Tên đăng nhập, email hoặc địa chỉ IP',
  `fail_count` INT NOT NULL DEFAULT 0 COMMENT 'Số lần đăng nhập sai liên tiếp',
  `locked_until` TIMESTAMP NULL DEFAULT NULL COMMENT 'Thời điểm hết hạn khóa',
  `last_attempt_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_attempts_ident` (`ident`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. BẢNG YÊU CẦU ĐẶT LẠI MẬT KHẨU (password_resets)
-- Lưu mã OTP đặt lại mật khẩu phía server (hết hạn 15 phút)
-- Khóa ngoại user_id ON DELETE CASCADE
-- ------------------------------------------------------------
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL COMMENT 'Mã OTP băm bảo mật',
  `attempts` INT NOT NULL DEFAULT 0 COMMENT 'Số lần thử nhập sai OTP (tối đa 5)',
  `used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 nếu mã OTP đã được sử dụng thành công',
  `expires_at` TIMESTAMP NOT NULL COMMENT 'Thời điểm hết hạn OTP',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_resets_user_valid` (`user_id`, `used`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. BẢNG CHƯƠNG BỘ LUẬT HÌNH SỰ (chuong)
-- Danh mục các chương luật trong Bộ luật Hình sự
-- Dữ liệu do database/seed_law.sql (sinh từ data/dieu_luat_data.php) nạp vào;
-- ten_chuong phải trùng khớp chính xác với trường `chuong` trong dữ liệu gốc.
-- ------------------------------------------------------------
CREATE TABLE `chuong` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ten_chuong` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Tên chương luật đầy đủ',
  `so_thu_tu` INT NOT NULL DEFAULT 1 COMMENT 'Số thứ tự chương trong bộ luật',
  `mo_ta` TEXT NULL COMMENT 'Mô tả phạm vi điều chỉnh của chương',
  INDEX `idx_chuong_stt` (`so_thu_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. BẢNG ĐIỀU LUẬT (dieu_luat)
-- Lưu chi tiết từng điều luật trong Bộ luật Hình sự
-- Khóa chính id chính là số hiệu điều luật (VD: 123, 134, 174...)
-- ------------------------------------------------------------
CREATE TABLE `dieu_luat` (
  `id` INT PRIMARY KEY COMMENT 'Số điều luật (ví dụ: 123, 134, 174...)',
  `chuong_id` INT NOT NULL COMMENT 'Khóa ngoại liên kết tới bảng chuong (mỗi điều luật bắt buộc thuộc 1 chương)',
  `tieu_de` VARCHAR(255) NOT NULL COMMENT 'Tên tội danh / điều luật',
  `tom_tat` TEXT NOT NULL COMMENT 'Tóm tắt nội dung hành vi phạm tội',
  `tu_khoa` TEXT NOT NULL COMMENT 'Từ khóa phục vụ tìm kiếm và NLU',
  `khung_hinh_phat` TEXT NOT NULL COMMENT 'Khung hình phạt tổng quan',
  `can_cu_boi_thuong` TEXT NULL COMMENT 'Căn cứ bồi thường dân sự (thường là Điều Bộ luật Dân sự)',
  `ghi_chu_quyen_loi` TEXT NULL COMMENT 'Quyền lợi chung của người bị hại',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_dieu_luat_chuong` FOREIGN KEY (`chuong_id`) REFERENCES `chuong` (`id`) ON DELETE RESTRICT,
  INDEX `idx_dieu_luat_chuong` (`chuong_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. BẢNG KHOẢN CỦA ĐIỀU LUẬT (khoan)
-- Lưu từng khoản định khung hình phạt kèm các tiêu chí định lượng
-- Khóa ngoại dieu_luat_id ON DELETE CASCADE
-- ------------------------------------------------------------
CREATE TABLE `khoan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dieu_luat_id` INT NOT NULL COMMENT 'Mã điều luật sở hữu khoản này',
  `so_khoan` INT NOT NULL COMMENT 'Số thứ tự khoản (1, 2, 3...)',
  `dieu_kien` TEXT NULL COMMENT 'Điều kiện áp dụng định khung của khoản',
  `noi_dung` TEXT NOT NULL COMMENT 'Nội dung quy định của khoản',
  `quyen_loi` TEXT NULL COMMENT 'Quyền lợi/bồi thường cụ thể của nạn nhân ở mức độ này',
  `tu_khoa` TEXT NULL COMMENT 'Từ khóa đặc thù nhận diện khoản trong chatbot NLU',
  `ti_le_ton_thuong_min` DECIMAL(5,2) NULL COMMENT 'Tỉ lệ tổn thương cơ thể tối thiểu (%)',
  `ti_le_ton_thuong_max` DECIMAL(5,2) NULL COMMENT 'Tỉ lệ tổn thương cơ thể tối đa (%)',
  `gia_tri_tai_san_min` DECIMAL(12,2) NULL COMMENT 'Giá trị tài sản tối thiểu, ĐƠN VỊ TRIỆU ĐỒNG (giống data/dieu_luat_data.php và js/law.js)',
  `gia_tri_tai_san_max` DECIMAL(12,2) NULL COMMENT 'Giá trị tài sản tối đa, ĐƠN VỊ TRIỆU ĐỒNG',
  `so_nguoi_chet_min` INT NULL COMMENT 'Số người chết tối thiểu',
  CONSTRAINT `fk_khoan_dieu_luat` FOREIGN KEY (`dieu_luat_id`) REFERENCES `dieu_luat` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_dieu_khoan` (`dieu_luat_id`, `so_khoan`),
  INDEX `idx_khoan_dieu` (`dieu_luat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. BẢNG LỊCH SỬ HỎI ĐÁP CHATBOT (lich_su_hoi_dap)
-- Lưu vết các câu hỏi tư vấn để thống kê và cải thiện mô hình NLU
-- ------------------------------------------------------------
CREATE TABLE `lich_su_hoi_dap` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL COMMENT 'ID người dùng nếu đăng nhập - NULL nếu là khách ẩn danh',
  `cau_hoi` TEXT NOT NULL COMMENT 'Câu hỏi tình huống người dùng nhập vào',
  `dieu_luat_id` INT NULL COMMENT 'Điều luật mà hệ thống khớp được',
  `khoan_so` INT NULL COMMENT 'Khoản cụ thể mà hệ thống gợi ý',
  `diem` DECIMAL(5,4) NULL COMMENT 'Điểm tương đồng tính toán bởi Scorer (0.0000 - 1.0000)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_lich_su_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lich_su_dieu_luat` FOREIGN KEY (`dieu_luat_id`) REFERENCES `dieu_luat` (`id`) ON DELETE SET NULL,
  INDEX `idx_lich_su_dieu` (`dieu_luat_id`),
  INDEX `idx_lich_su_user` (`user_id`),
  INDEX `idx_lich_su_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
