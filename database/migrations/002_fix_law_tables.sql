-- ============================================================
-- Migration: 002_fix_law_tables.sql
-- Mô tả: Sửa 3 bảng luật sau review T13
--   1. khoan.gia_tri_tai_san_min/max: BIGINT (VNĐ) -> DECIMAL(12,2) đơn vị TRIỆU ĐỒNG,
--      khớp với data/dieu_luat_data.php và js/law.js.
--   2. dieu_luat.chuong_id: NULL + ON DELETE SET NULL -> NOT NULL + ON DELETE RESTRICT.
--   3. Xóa 3 chương mẫu do seed.sql cũ tạo (tên không khớp dữ liệu gốc).
--      Từ nay chương được nạp bởi seed_law.sql.
-- Áp dụng cho: CSDL đã import schema.sql + seed.sql của commit e4c45fd.
-- Cài mới từ schema.sql hiện tại thì KHÔNG cần chạy file này.
-- Yêu cầu: bảng dieu_luat chưa có dòng nào có chuong_id = NULL.
-- ============================================================

USE `hinh_su_db`;

-- 1. Đơn vị giá trị tài sản
ALTER TABLE `khoan`
  MODIFY `gia_tri_tai_san_min` DECIMAL(12,2) NULL COMMENT 'Giá trị tài sản tối thiểu, ĐƠN VỊ TRIỆU ĐỒNG (giống data/dieu_luat_data.php và js/law.js)',
  MODIFY `gia_tri_tai_san_max` DECIMAL(12,2) NULL COMMENT 'Giá trị tài sản tối đa, ĐƠN VỊ TRIỆU ĐỒNG';

-- 2. Mỗi điều luật bắt buộc thuộc 1 chương
ALTER TABLE `dieu_luat` DROP FOREIGN KEY `fk_dieu_luat_chuong`;
ALTER TABLE `dieu_luat`
  MODIFY `chuong_id` INT NOT NULL COMMENT 'Khóa ngoại liên kết tới bảng chuong (mỗi điều luật bắt buộc thuộc 1 chương)';
ALTER TABLE `dieu_luat`
  ADD CONSTRAINT `fk_dieu_luat_chuong` FOREIGN KEY (`chuong_id`) REFERENCES `chuong` (`id`) ON DELETE RESTRICT;

-- 3. Bỏ các chương mẫu không có điều luật nào tham chiếu
DELETE FROM `chuong` WHERE `id` NOT IN (SELECT DISTINCT `chuong_id` FROM `dieu_luat`);
