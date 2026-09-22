-- =========================================================
-- CƠ SỞ DỮ LIỆU: luat_hinh_su
-- Thiết kế sơ bộ cho hệ thống Tra cứu & Tư vấn Luật Hình sự VN
-- =========================================================
CREATE DATABASE IF NOT EXISTS luat_hinh_su CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luat_hinh_su;

-- ---------------------------------------------------------
-- Chương của Bộ luật Hình sự
-- ---------------------------------------------------------
CREATE TABLE chuong (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    ten_chuong    VARCHAR(255) NOT NULL,
    mo_ta         TEXT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Điều luật (đơn vị tra cứu chính)
-- ---------------------------------------------------------
CREATE TABLE dieu_luat (
    id                INT PRIMARY KEY,           -- trùng với số điều, VD 168 = "Điều 168"
    chuong_id         INT NOT NULL,
    tieu_de           VARCHAR(255) NOT NULL,      -- tên tội danh
    tom_tat           TEXT NOT NULL,              -- mô tả ngắn dùng để hiển thị & làm ngữ liệu NLU
    tu_khoa           TEXT NULL,                  -- từ khóa/đồng nghĩa bổ sung, giúp TF-IDF khớp tốt hơn
    khung_hinh_phat   VARCHAR(255) NOT NULL,
    tao_luc           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chuong_id) REFERENCES chuong(id)
) ENGINE=InnoDB;

FULLTEXT INDEX ft_dieu_luat (tieu_de, tom_tat, tu_khoa);

-- ---------------------------------------------------------
-- Các khoản trong một điều luật
-- ---------------------------------------------------------
CREATE TABLE khoan (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    dieu_luat_id  INT NOT NULL,
    so_khoan      INT NOT NULL,
    noi_dung      TEXT NOT NULL,
    FOREIGN KEY (dieu_luat_id) REFERENCES dieu_luat(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Lịch sử hỏi đáp chatbot (phục vụ thống kê / cải thiện NLU)
-- ---------------------------------------------------------
CREATE TABLE lich_su_hoi_dap (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    cau_hoi           TEXT NOT NULL,
    dieu_luat_id      INT NULL,                  -- điều luật khớp nhất được trả về
    diem_tuong_dong   FLOAT NULL,                -- cosine similarity 0..1
    thoi_gian         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dieu_luat_id) REFERENCES dieu_luat(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Câu hỏi gửi từ trang Liên hệ
-- ---------------------------------------------------------
CREATE TABLE lien_he (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten      VARCHAR(150) NOT NULL,
    sdt         VARCHAR(20) NULL,
    email       VARCHAR(150) NOT NULL,
    noi_dung    TEXT NOT NULL,
    trang_thai  ENUM('moi','da_xu_ly') DEFAULT 'moi',
    tao_luc     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tài khoản quản trị (màn hình quản trị điều luật ở BackEnd)
-- ---------------------------------------------------------
CREATE TABLE quan_tri_vien (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    ten_dang_nhap VARCHAR(50) UNIQUE NOT NULL,
    mat_khau_hash VARCHAR(255) NOT NULL,
    ho_ten        VARCHAR(150) NULL
) ENGINE=InnoDB;

-- =========================================================
-- DỮ LIỆU MẪU
-- =========================================================
INSERT INTO chuong (id, ten_chuong) VALUES
 (1,'Các tội xâm phạm tính mạng, sức khỏe'),
 (2,'Các tội xâm phạm sở hữu'),
 (3,'Các tội xâm phạm an toàn công cộng, trật tự công cộng'),
 (4,'Các tội phạm về chức vụ'),
 (5,'Các tội phạm về ma túy'),
 (6,'Các tội xâm phạm trật tự quản lý kinh tế');

INSERT INTO dieu_luat (id, chuong_id, tieu_de, tom_tat, tu_khoa, khung_hinh_phat) VALUES
 (123,1,'Tội giết người','Hành vi cố ý tước đoạt tính mạng người khác một cách trái pháp luật.','giết người tước đoạt tính mạng sát hại','7–20 năm tù, tù chung thân hoặc tử hình'),
 (168,2,'Tội cướp tài sản','Dùng vũ lực, đe dọa dùng vũ lực hoặc thủ đoạn khác làm nạn nhân không thể chống cự nhằm chiếm đoạt tài sản.','cướp tài sản dùng vũ lực đe dọa chiếm đoạt','3–20 năm tù hoặc tù chung thân'),
 (174,2,'Tội lừa đảo chiếm đoạt tài sản','Dùng thủ đoạn gian dối để chiếm đoạt tài sản của người khác.','lừa đảo gian dối chiếm đoạt tài sản online chuyển khoản','Cải tạo không giam giữ đến phạt tù 20 năm'),
 (260,3,'Tội vi phạm quy định về tham gia giao thông đường bộ','Vi phạm quy định an toàn giao thông gây thiệt hại tính mạng, sức khỏe, tài sản.','tai nạn giao thông lái xe gây chết người','Phạt tiền, cải tạo không giam giữ, hoặc tù 1–15 năm'),
 (353,4,'Tội tham ô tài sản','Người có chức vụ, quyền hạn lợi dụng chức vụ chiếm đoạt tài sản mình quản lý.','tham ô tài sản chức vụ quyền hạn chiếm đoạt công quỹ','2–20 năm tù, tù chung thân hoặc tử hình'),
 (134,1,'Tội cố ý gây thương tích hoặc gây tổn hại cho sức khỏe của người khác','Hành vi cố ý gây thương tích hoặc tổn hại sức khỏe cho người khác.','cố ý gây thương tích đánh nhau bạo lực','Cải tạo không giam giữ đến phạt tù 20 năm'),
 (249,5,'Tội tàng trữ trái phép chất ma túy','Tàng trữ trái phép chất ma túy không nhằm mua bán, vận chuyển, sản xuất.','ma túy tàng trữ trái phép chất cấm','1–20 năm tù hoặc tù chung thân'),
 (321,6,'Tội đánh bạc','Đánh bạc trái phép được thua bằng tiền/hiện vật trị giá từ mức quy định.','đánh bạc cờ bạc tổ chức trò chơi ăn tiền','Phạt tiền, cải tạo không giam giữ, hoặc tù đến 7 năm');

-- Khoản mẫu cho Điều 168 (đầy đủ) — các điều khác làm tương tự
INSERT INTO khoan (dieu_luat_id, so_khoan, noi_dung) VALUES
 (168,1,'Người nào dùng vũ lực, đe dọa dùng vũ lực ngay tức khắc... nhằm chiếm đoạt tài sản thì bị phạt tù từ 3 đến 10 năm.'),
 (168,2,'Phạm tội có tổ chức, tính chất chuyên nghiệp, dùng vũ khí, gây thương tích... thì bị phạt tù từ 7 đến 15 năm.'),
 (168,3,'Chiếm đoạt tài sản trị giá 200–500 triệu đồng, hoặc gây thương tích 31–60%, thì bị phạt tù từ 12 đến 20 năm.'),
 (168,4,'Trường hợp đặc biệt nghiêm trọng (tài sản từ 500 triệu đồng, làm chết người) thì bị phạt tù 18–20 năm hoặc tù chung thân.');
