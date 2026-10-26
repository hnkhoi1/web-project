# Hình Sự Tra Cứu

Ứng dụng web tra cứu và tư vấn **Luật Hình sự Việt Nam** bằng ngôn ngữ đời thường: người dùng mô tả tình huống, hệ thống trả về điều luật, mức xử lý và quyền lợi bồi thường của người bị hại. Có thêm quản lý ghi chú cá nhân, tài khoản và tùy chỉnh giao diện.

> **Trạng thái hiện tại:** bản chạy được là **bản tĩnh** (HTML + CSS + JavaScript, dữ liệu lưu ở `localStorage` của trình duyệt). Nhóm đang chuyển sang **PHP + MySQL** (XAMPP). CSDL đã thiết kế xong (`database/`), phần BackEnd PHP đang được làm. Tiến độ xem [docs/TASK_BOARD.md](docs/TASK_BOARD.md).

## 1. Chạy bản tĩnh

Không cần cài PHP, MySQL hay Node. Dùng một trong ba cách:

| Cách | Làm thế nào |
|---|---|
| **Python** (khuyên dùng) | Mở terminal tại thư mục gốc của repo, chạy `python3 -m http.server 8000` (Windows: `python -m http.server 8000`), rồi vào http://localhost:8000/html/login.html |
| **VS Code** | Cài extension **Live Server**, chuột phải `html/login.html` → *Open with Live Server* |
| **Mở trực tiếp** | Mở file `html/login.html` bằng trình duyệt (đường dẫn dạng `file://`) |

> Thư mục gốc chưa có `index.html`, nên luôn bắt đầu từ `html/login.html`. (Task F03 sẽ thêm trang chuyển hướng.)

### Tài khoản demo
| Tên đăng nhập | Mật khẩu |
|---|---|
| `demo` | `12345` |

Tài khoản demo nằm cứng trong `js/db.js` (`DEMO_USER`), bị khóa: không sửa tên/ảnh, không đổi mật khẩu, không xóa được. Tài khoản tự đăng ký thì dùng đầy đủ chức năng; dữ liệu lưu trong `localStorage` của trình duyệt đó. Xóa dữ liệu đã tạo: trang **Dữ liệu demo** (`html/database.html`) hoặc DevTools → Application → Clear storage.

## 2. Các trang

Chưa đăng nhập mà mở bất kỳ trang nào ngoài ba trang xác thực thì bị chuyển về `login.html`.

| Nhóm | Trang (trong `html/`) | Chức năng |
|---|---|---|
| Xác thực | `login.html`, `register.html`, `forgot-password.html` | Đăng nhập (tên đăng nhập hoặc email, khóa tạm sau 5 lần sai), đăng ký (tự đăng nhập, có thanh nhắc kích hoạt email), quên mật khẩu bằng OTP giả lập trên màn hình |
| Trang chủ | `index.html` | Ghi chú cá nhân: thêm, sửa, xóa có xác nhận, ghim, tìm theo thời gian thực, chọn màu |
| Luật | `tra-cuu.html`, `dieu-luat-chi-tiet.html`, `tu-van.html` | Tra cứu điều luật, chi tiết từng điều (các khoản, quyền lợi người bị hại), chatbot tư vấn |
| Tài khoản | `ho-so.html`, `doi-mat-khau.html`, `tuy-chinh.html` | Hồ sơ và ảnh đại diện, đổi mật khẩu, sáng/tối, cỡ chữ, màu ghi chú |
| Khác | `gioi-thieu.html`, `lien-he.html`, `database.html` | Giới thiệu, gửi liên hệ, xem dữ liệu demo trong `localStorage` |

## 3. Cấu trúc thư mục

```
web-project/
├── html/        13 trang. Header/footer do js/app.js chèn vào
├── css/app.css  giao diện dùng chung (có chế độ sáng/tối, responsive)
├── js/
│   ├── db.js        lớp dữ liệu, tài khoản demo, localStorage
│   ├── app.js       khung chung: chặn chưa đăng nhập, header/footer, tiện ích HS.*
│   ├── auth.js      đăng nhập / đăng ký / quên mật khẩu
│   ├── notes.js, account.js, contact.js      logic từng trang
│   ├── search.js, detail.js, chatbot.js      tra cứu, chi tiết, chatbot
│   └── data.js, nlu.js, scorer.js, law.js    dữ liệu điều luật + chấm điểm câu hỏi
├── data/        dieu_luat_data.php  (dữ liệu điều luật phía PHP)
├── database/    schema.sql, seed.sql, migrations/   (MySQL, xem mục 5)
├── tests/       db-test.js, scorer-test.js, e2e.py
└── docs/        tài liệu thiết kế và kế hoạch (mục 6)
```

**Thứ tự nạp script trong mỗi trang:** `db.js` → `app.js` (trong `<head>`) → `data.js` → `nlu.js` → `scorer.js` → `law.js` (chỉ trang luật) → script của trang (cuối `<body>`).

## 4. Chạy test

| Lệnh | Cần | Kết quả hiện tại |
|---|---|---|
| `node tests/db-test.js` | Node.js | 13 mục đạt |
| `node tests/scorer-test.js` | Node.js | 19/19 đạt |
| `python tests/e2e.py` | Python 3 + Playwright | **Đang hỏng** vì trỏ sai thư mục (lỗi đã biết, sửa trong F02) |

Cài Playwright cho e2e: `pip install playwright` rồi `playwright install chromium`.

Danh sách kiểm tay trước mỗi lần push: [docs/TESTING_CHECKLIST.md](docs/TESTING_CHECKLIST.md) (mục 1, "Smoke test").

## 5. Cơ sở dữ liệu MySQL

CSDL `hinh_su_db` gồm 10 bảng (người dùng, ghi chú, liên hệ, điều luật…). Mô tả cột và sơ đồ ERD: [docs/DATABASE.md](docs/DATABASE.md).

**Cài mới** (cần XAMPP hoặc MySQL 8 / MariaDB 10.4 trở lên), chạy tại thư mục gốc của repo:

```
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

Hoặc import hai file trên theo thứ tự trong phpMyAdmin. Sau này sẽ có thêm `database/seed_law.sql` (sinh tự động từ dữ liệu điều luật), import sau `seed.sql`.

> ⚠ `schema.sql` bắt đầu bằng `DROP TABLE`, chạy lại sẽ **xóa toàn bộ dữ liệu**. CSDL đã có dữ liệu thì chỉ chạy các file mới trong `database/migrations/`.

Hiện giao diện **chưa** đọc ghi MySQL; việc nối BackEnd PHP nằm ở các task T10–T14.

## 6. Tài liệu trong `docs/`

| File | Nội dung |
|---|---|
| `TASK_BOARD.md` | Phân công, trạng thái và tiêu chí hoàn thành từng task, quy tắc làm việc trên `main` |
| `TIMELINE.md` | Lịch 14 ngày (26/10 – 08/11/2026) cho từng người |
| `FILE_OWNERSHIP.md` | File nào của ai, cách sửa file dùng chung |
| `ARCHITECTURE.md`, `API_CONTRACT.md` | Kiến trúc hiện tại và kiến trúc đích, hợp đồng API |
| `DATABASE.md`, `DATABASE.pdf` | Thiết kế CSDL |
| `TESTING_CHECKLIST.md`, `PROJECT_AUDIT.md` | Danh sách kiểm thử, báo cáo rà soát lỗi |

## 7. Lưu ý

**Bảo mật của bản tĩnh:** việc kiểm tra đăng nhập chạy ngay trong trình duyệt nên **không an toàn thật**. Ai mở DevTools cũng đọc được dữ liệu `localStorage`, và mật khẩu của tài khoản tự đăng ký chỉ được băm SHA-256 + salt để minh họa. Bản PHP sẽ băm bcrypt, có phiên đăng nhập và CSRF ở máy chủ.

**Nội dung pháp luật:** điều luật trong ứng dụng là dữ liệu biên soạn rút gọn cho mục đích học tập, không phải trích dẫn nguyên văn Bộ luật Hình sự và không thay thế tư vấn pháp lý chính thức.
