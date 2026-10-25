# TESTING CHECKLIST — Hình Sự Tra Cứu

> Chạy §1 **trước mỗi lần push lên `main`** (nhóm commit thẳng lên `main`); chạy toàn bộ vào cuối mỗi sprint và trước khi nộp (T40).
> Ghi kết quả: ngày · người chạy · commit · PASS/FAIL · ghi chú. Tài khoản demo: xem `js/db.js` / seed.

## 0. Chuẩn bị môi trường
- [ ] Chế độ tĩnh: `python -m http.server 8000` tại gốc repo → `http://localhost:8000/html/login.html` (hoặc mở `file://`).
- [ ] Chế độ XAMPP (**chế độ chính**, D1 = PHP + MySQL): bật Apache + MySQL, import `database/schema.sql` → `seed.sql` → `seed_law.sql` vào CSDL trống `hinh_su_db`, tạo `backend/config.local.php`, vào `http://localhost/web-project/html/login.html`.
- [ ] Xóa dữ liệu cũ: DevTools → Application → Clear storage (hoặc nút ở trang Dữ liệu demo).
- [ ] Công cụ: Node LTS (test JS) **hoặc** `tests/run-in-browser.html`; Python 3 + `pip install playwright && playwright install chromium` (e2e).

## 1. Smoke test (≈5 phút, sau mỗi merge)
- [ ] Chưa đăng nhập mở `tu-van.html` → về `login.html`; đăng nhập demo → quay lại `tu-van.html`.
- [ ] 13 trang (+ trang mới) tải không lỗi console.
- [ ] Chatbot: "tôi bị đánh" → Điều 134, hỏi thêm tỉ lệ; "Tôi bị đánh gây thương tích 20%…" → Điều 134 khoản cụ thể + quyền lợi.
- [ ] Tra cứu "lừa đảo" có kết quả; "134" ra đúng 1 điều; chi tiết `?id=134` hiển thị các khoản.
- [ ] Tạo / sửa / ghim / xóa 1 ghi chú; "Lưu vào ghi chú" từ chatbot hiện ở trang chủ.
- [ ] Đăng xuất → về login, bấm Back không vào lại được trang nội bộ.

## 2. Test tự động
| Lệnh | Owner | Kỳ vọng hiện tại |
|---|---|---|
| `node tests/db-test.js` | TV-A | 13/13 PASS (chưa chạy được trên máy audit vì thiếu Node) |
| `node tests/scorer-test.js` | TV-B | 19/19 PASS (đã kiểm tương đương trong trình duyệt: 18/18 + ngoài phạm vi) |
| `python tests/e2e.py` → sau F02: `python tests/e2e/run_all.py` | caychay0512-dev | ❌ đang hỏng đường dẫn (B2) — sửa trong F02 |
| `php -l` mọi file `.php` | TV-A/B | Không lỗi cú pháp |
| `php tests/api_smoke.php` (sau T11) | TV-A | Toàn bộ PASS |

## 3. Chức năng chi tiết

### 3.1 Xác thực (TV-A)
- [ ] Đăng nhập: bỏ trống → lỗi từng ô; sai mật khẩu → lỗi chung (không nói rõ sai ô nào); 5 lần sai → khóa 60s.
- [ ] Đăng nhập bằng email (tài khoản mới) và username `demo`, không phân biệt hoa/thường, có khoảng trắng thừa.
- [ ] Đăng ký: email sai định dạng, tên <2 hoặc >40, mật khẩu <8 / không có số / không có chữ, 2 mật khẩu lệch, email trùng → đúng thông báo; thành công → tự đăng nhập + thanh vàng.
- [ ] Kích hoạt email giả lập → thanh vàng biến mất.
- [ ] Quên mật khẩu: email chưa đăng ký vẫn sang bước 2; demo bị báo không đặt lại được; OTP sai 5 lần → khóa; OTP đúng / bấm link → đặt mật khẩu mới → về login, **không** tự đăng nhập; đăng nhập bằng mật khẩu mới được, mật khẩu cũ bị từ chối.
- [ ] (api) Gọi API ghi khi chưa đăng nhập → 401; đổi `user_id` trong request không truy cập được dữ liệu người khác; thiếu CSRF token → 403.

### 3.2 Tài khoản (TV-C)
- [ ] Hồ sơ: đổi tên (validate), tải avatar JPG/PNG/WebP; từ chối GIF, >2MB, <64×64; gỡ avatar.
- [ ] Đổi mật khẩu: sai mật khẩu hiện tại, mới trùng cũ, không khớp → lỗi; thành công đăng nhập lại được.
- [ ] Xóa tài khoản: sai mật khẩu bị chặn; đúng → về login, tài khoản không đăng nhập được, ghi chú bị xóa.
- [ ] Demo: hồ sơ / đổi mật khẩu / xóa bị khóa và có thông báo.
- [ ] Tùy chỉnh: sáng/tối/hệ thống, 4 cỡ chữ, màu ghi chú — xem trước tức thì, lưu theo từng người.
- [ ] Liên hệ: validate họ tên/email/sđt/nội dung; gửi thành công có thông báo.

### 3.3 Ghi chú (TV-C)
- [ ] Không cho lưu khi cả tiêu đề và nội dung rỗng; tiêu đề >120 / nội dung >5000 bị cắt.
- [ ] Ghim lên đầu; tìm theo tiêu đề/nội dung; trạng thái rỗng khi tìm không thấy.
- [ ] Lưới/danh sách được nhớ theo người dùng.
- [ ] Ghi chú của người A không hiện cho người B.
- [ ] Nội dung chứa `<script>alert(1)</script>` hiển thị dạng chữ, không chạy.

### 3.4 Tra cứu & chatbot (TV-B)
- [ ] Mọi câu trong `scorer-test` đúng điều.
- [ ] Câu ngoài phạm vi ("thời tiết hôm nay") → "chưa tìm được điều luật phù hợp".
- [ ] Sau T21: "bị trộm xe máy"→173, "vay tiền không trả"→175, "bị vu khống"→156, "bị chửi trên facebook"→155.
- [ ] Có số liệu (%, triệu, số người chết) → chọn đúng khoản; không có → chế độ tổng quan + câu hỏi thêm.
- [ ] Người bị hại thấy quyền lợi trước mức xử lý.
- [ ] Lọc theo chương; `?q=` và `?chuong=` trên URL khôi phục bộ lọc.
- [ ] `?id=999` → "Không tìm thấy điều luật".

### 3.5 Quản trị (TV-C UI / TV-A API)
- [ ] User thường không vào được `admin/*` (UI và API đều chặn).
- [ ] Admin: thống kê đúng số liệu; đổi trạng thái liên hệ; xem lịch sử hỏi đáp; danh sách người dùng.
- [ ] Không xóa được tài khoản demo / admin của chính mình.

## 4. Không chức năng
- [ ] Responsive 375 / 768 / 1280px: không tràn ngang, có hamburger ở 375px, nút ≥44px.
- [ ] Bàn phím: Tab tới mọi điều khiển, "Bỏ qua điều hướng", Esc đóng menu/dialog.
- [ ] Theme tối đủ tương phản.
- [ ] Chrome + Firefox (ít nhất); Edge nếu có.
- [ ] Mở offline (chặn Google Fonts) vẫn hiển thị.

## 5. Danh sách màn hình cần chụp cho PDF nộp (R4)
Đăng nhập · Đăng ký · Quên mật khẩu (3 bước) · Trang chủ + ghi chú · Tra cứu · Chi tiết điều luật · Chatbot (1 câu tổng quan + 1 câu cụ thể) · Giới thiệu · Liên hệ · Hồ sơ · Đổi mật khẩu · Tùy chỉnh (sáng & tối) · Văn bản mới (nếu giữ) · Quản trị (từng mục menu) · 1 ảnh mobile 375px.

## 6. Nhật ký chạy
| Ngày | Người | Commit | Phạm vi | Kết quả | Ghi chú |
|---|---|---|---|---|---|
| 25/10/2026 | coordinator (audit) | a665b13 | Tải 13 trang, DB.* cơ bản, 18 câu scorer trong trình duyệt | PASS | Không có Node/Playwright; e2e chưa chạy; nhánh PHP chưa chạy (MySQL tắt) |
