# PROJECT AUDIT — Hình Sự Tra Cứu

> Ngày audit: 25/10/2026 · Phạm vi: `main` @ `a665b13` + 2 nhánh remote `origin/update-new-exten` (397c298), `origin/feature/grid-list` (15667f0).
> Chế độ: **chỉ đọc**. Không sửa code, không checkout/merge nhánh nào.
> Ký hiệu trạng thái: ✅ đã kiểm chứng chạy được · 🟡 đọc code thấy có nhưng chưa chạy thử · ❌ thiếu / lỗi · ❓ chưa xác minh được.

> **Cập nhật 25/10/2026:** nhóm quyết định coi hai nhánh `update-new-exten` / `feature/grid-list` như chưa đóng góp gì; `main` là gốc và cả nhóm commit thẳng lên `main`. Mục 6.2 chỉ còn giá trị tham khảo (lỗi cần tránh khi viết BackEnd mới).

## 1. Tóm tắt nhanh

- `main` hiện là **bản demo giao diện tĩnh** (HTML/CSS/JS thuần, dữ liệu trong `localStorage`). 13 trang đều tải được, không có lỗi console (đã kiểm tra trên trình duyệt qua `http://127.0.0.1`).
- **Chưa có BackEnd** trên `main`. `data/DataStore.php` + `data/dieu_luat_data.php` hợp lệ cú pháp nhưng **không file nào dùng tới** (code mồ côi).
- Nhánh `update-new-exten` / `feature/grid-list` thêm **PHP + MySQL** (`backend/`, `database/schema.sql`), trang "Văn bản mới", chế độ xem lưới/danh sách. Commit ghi rõ *"database (chưa fix)"*; đọc code thấy **nhiều lỗi tích hợp và bảo mật** (mục 6). Chưa merge vào `main`.
- **Không có tài liệu đề bài gốc trong repository.** Nguồn yêu cầu duy nhất là `HANDOVER.md` (đã bị xóa ở `1052e60`, đọc lại qua git history) — đây là ghi chú do AI viết, không phải đề bài của giảng viên. `TINH_NANG.md` (trên nhánh) nhắc tới `PLAN.txt` nhưng file này không có trong repo.
- **Có mâu thuẫn yêu cầu cần nhóm chốt trước** (mục 3): HANDOVER ghi "không SQL/.sql, bộ web tĩnh" nhưng nhánh mới lại dùng MySQL + `schema.sql`.

## 2. Cấu trúc & cách chạy

```
README.md            ⚠ còn nguyên dấu xung đột merge (<<<<<<< / ======= / >>>>>>>) — 2 nội dung README khác nhau
css/app.css          1 file CSS dùng chung (273 dòng, theme sáng/tối bằng biến CSS)
data/DataStore.php   kho dữ liệu PHP dạng file JSON — KHÔNG được dùng
data/dieu_luat_data.php  8 điều luật (bản PHP) — trùng lặp với js/data.js
html/*.html          13 trang (3 trang xác thực + 10 trang sau đăng nhập)
js/*.js              14 script (xem ARCHITECTURE.md)
tests/db-test.js     Node — test lớp DB
tests/scorer-test.js Node — test chatbot scorer
tests/e2e.py         Playwright — ⚠ BASE trỏ sai thư mục (xem 6.1)
```

- Tech stack: HTML5, CSS3, JavaScript ES2020 (không framework, không build, không package.json). Font Google (có fallback). PHP 8 chỉ có trên nhánh.
- Chạy `main`: mở `html/login.html` qua server tĩnh bất kỳ (`python -m http.server` tại thư mục gốc rồi vào `/html/login.html`) hoặc qua XAMPP `http://localhost/web-project/html/login.html`. Tài khoản demo: xem `js/db.js` (`DEMO_USER`).
- Không có `index.html` ở thư mục gốc → vào `http://localhost/web-project/` chỉ thấy danh sách thư mục.
- Môi trường máy audit: **không có Node.js** (không chạy được `tests/*.js` trực tiếp), **không có Playwright** (không chạy được `e2e.py`), có PHP 8.2 (XAMPP), MySQL/Apache **không chạy** lúc audit.

## 3. Yêu cầu đồ án (nguồn: HANDOVER.md đã xóa — CẦN NHÓM XÁC NHẬN LẠI VỚI ĐỀ BÀI GỐC)

| # | Yêu cầu (theo HANDOVER) | Ghi chú |
|---|---|---|
| R1 | Đăng ký đề tài theo template | Không có trong repo ❓ |
| R2 | FrontEnd tối thiểu 5 trang | |
| R3 | BackEnd có **menu chức năng** | |
| R4 | Nộp PDF chụp màn hình + bộ web tĩnh (HTML, CSS, JS) | |
| R5 | Thiết kế sơ bộ CSDL (PDF) | |
| R6 | Chủ đề: tra cứu & tư vấn luật hình sự VN, thiên hướng chatbot | |
| R7 | Chỉ dùng HTML, CSS, JS, PHP; "không SQL/.sql"; "database dùng PHP" | ⚠ mâu thuẫn với nhánh MySQL |
| R8 | Hệ thống tự hiểu câu hỏi (NLU nhẹ) | |
| R9 | Chatbot nêu quyền lợi/bồi thường theo khoản; dữ liệu theo từng khoản | |
| R10 | Trang giới thiệu viết như web thật, không mô tả kỹ thuật | |
| R11 | Tài khoản: chặn khi chưa đăng nhập; đăng ký (email + tên + mật khẩu ×2), tự đăng nhập, email kích hoạt, banner chưa kích hoạt; hồ sơ (tên + avatar có validate); đổi mật khẩu; tùy chỉnh (cỡ chữ ghi chú, màu ghi chú, sáng/tối); quên mật khẩu (link/OTP), đăng nhập lại thủ công; trang chủ cá nhân có ghi chú; responsive | |
| R12 | Tài khoản `demo` cố định trong code; tạo/xóa tài khoản chỉ thêm/bớt trong database | |

> **Cập nhật 25/10/2026:** nhóm đã chốt D1 = PHP + MySQL. Câu 1 bên dưới được coi là "có"; vẫn cần đưa đề bài vào repo để có bằng chứng.

**Câu hỏi bắt buộc phải trả lời trước khi chia việc phần BackEnd (quyết định D1):**
1. Đề bài có cho phép MySQL/SQL không? (HANDOVER nói "không SQL/.sql").
2. "BackEnd có menu chức năng" nghĩa là trang quản trị chạy PHP thật, hay chỉ cần mô phỏng giao diện?
3. Sản phẩm nộp là "bộ web tĩnh" mở bằng trình duyệt hay phải chạy trên XAMPP?

## 4. Đối chiếu yêu cầu ↔ code trên `main`

### 4.1 Đáp ứng (đã kiểm chứng)
| Yêu cầu | Bằng chứng |
|---|---|
| R2 ≥5 trang FrontEnd | 13 trang, tải được, không lỗi console ✅ |
| R6/R8 chatbot hiểu câu hỏi | `js/scorer.js` (khái niệm + BM25 + Levenshtein). Chạy lại 18 câu của `scorer-test.js` trong trình duyệt: **18/18 đúng**, câu ngoài phạm vi → `none` ✅ |
| R9 quyền lợi theo khoản | `Law.answer('… thương tích 20% …')` → Điều 134, khoản 1, chế độ `specific` ✅ |
| R11 chặn khi chưa đăng nhập | Mở `tu-van.html` khi chưa có phiên → chuyển `login.html` ✅ |
| R11/R12 đăng nhập demo, đăng ký, trùng email, xóa tài khoản kèm ghi chú, demo không sửa/xóa được | Chạy trực tiếp `DB.*` trong trình duyệt: tất cả đúng ✅ |

### 4.2 Có code nhưng chưa kiểm chứng đầy đủ bằng thao tác UI (🟡)
Quên mật khẩu 3 bước (OTP/link giả lập), hồ sơ + avatar (validate loại/size/kích thước), đổi mật khẩu, tùy chỉnh theme/cỡ chữ/màu, ghi chú (thêm/sửa/xóa/ghim/tìm), liên hệ, responsive 375px. HANDOVER báo e2e 26/26 nhưng **e2e hiện không chạy được trên `main`** (đường dẫn sai) nên coi là chưa xác minh.

### 4.3 Đáp ứng một phần
| Yêu cầu | Thiếu gì |
|---|---|
| R3 BackEnd có menu chức năng | `main` chỉ có `database.html` (xem danh sách user localStorage). Không có quản trị điều luật / liên hệ / lịch sử hỏi đáp. Nhánh có API PHP nhưng không có trang menu quản trị. |
| R7 "database dùng PHP" | `main`: chỉ localStorage; `DataStore.php` không được gọi. Nhánh: MySQL (có thể vi phạm "không SQL"). |
| R8 phạm vi hiểu câu hỏi | Chỉ **8 điều luật** (123, 134, 168, 174, 249, 260, 321, 353). `scorer.js` có khái niệm cho 173, 175, 155, 156, 138, 135, 128, 133, 171, 322 nhưng **không có dữ liệu** → "bị trộm xe máy", "bị chửi trên facebook", "bị vu khống" trả `none`; "vay tiền không trả" bị xếp vào **Điều 174 (lừa đảo)** — sai về pháp lý (đúng ra 175). |
| R11 email kích hoạt / OTP | Giả lập trên màn hình (đúng với định hướng demo, cần ghi rõ trong báo cáo). |

### 4.4 Thiếu
- R1, R4 (PDF màn hình), R5 (PDF thiết kế CSDL): **không có trong repo** ❌.
- Trang quản trị BackEnd thật (R3) ❌.
- Tài liệu đề bài gốc trong repo ❌.

## 5. Luồng xác thực, kiểm tra dữ liệu, phân quyền (`main`)

- Xác thực hoàn toàn phía trình duyệt; phiên = `localStorage.hs_session` (chỉ là id người dùng). Ai mở DevTools cũng đặt được `hs_session='demo'` hoặc id bất kỳ → **không có bảo mật thật** (đã được README thừa nhận; chấp nhận được nếu đề bài chỉ là demo tĩnh).
- Khóa đăng nhập 5 lần sai lưu ở `sessionStorage` → đóng tab là hết khóa.
- Mật khẩu tài khoản thêm: SHA-256 + salt (minh họa); demo so sánh plaintext.
- Validate phía client khá đầy đủ (email, tên 2–40, mật khẩu ≥8 có chữ + số, avatar loại/size/kích thước, liên hệ). Dựng DOM bằng `textContent`/`esc()` — không thấy XSS rõ ràng.
- Phân quyền: chỉ 1 vai trò (người dùng). Không có vai trò quản trị.

## 6. Lỗi và rủi ro

### 6.1 Trên `main`
| ID | Mức | Vấn đề | Vị trí |
|---|---|---|---|
| B1 | Cao | README.md chứa dấu xung đột merge, 2 nội dung mâu thuẫn (một bản nói `legal-demo/frontend/backend` không còn tồn tại) | `README.md` |
| B2 | Cao | `tests/e2e.py` dùng `BASE = file://<cwd>/` nhưng trang đã chuyển vào `html/` → toàn bộ e2e hỏng (nhánh đã sửa 1 dòng này) | `tests/e2e.py:3` |
| B3 | TB | Dữ liệu điều luật trùng lặp ở `js/data.js` và `data/dieu_luat_data.php` — dễ lệch nhau | `js/data.js`, `data/dieu_luat_data.php` |
| B4 | TB | `DataStore.php` không được dùng; comment trỏ tới đường dẫn `backend/data/…` không tồn tại | `data/DataStore.php` |
| B5 | TB | Chatbot trả lời sai điều luật khi tình huống nằm ngoài 8 điều có dữ liệu nhưng khớp khái niệm khác (vd. 175→174) | `js/scorer.js` CONCEPTS |
| B6 | Thấp | Không có trang gốc `index.html`/redirect; truy cập thư mục gốc thấy directory listing | gốc repo |
| B7 | Thấp | Khóa brute-force và phiên có thể bỏ qua qua DevTools (giới hạn bản chất của demo tĩnh) | `js/auth.js`, `js/db.js` |
| B8 | Thấp | Không có `.gitignore`; lịch sử từng commit file `.zip` | gốc repo |

### 6.2 Trên nhánh `update-new-exten` / `feature/grid-list` (đọc code, CHƯA chạy vì MySQL không bật)
| ID | Mức | Vấn đề |
|---|---|---|
| N1 | **Nghiêm trọng** | API không có phiên phía server: `notes.php`, `auth.php?action=update_profile/delete_account/reset_users`, `contacts.php` **tin `user_id`/`id` do client gửi** → ai cũng đọc/sửa/xóa ghi chú người khác; `reset_users` xóa toàn bộ tài khoản **không cần đăng nhập**. |
| N2 | Cao | Hash bcrypt của demo trong `schema.sql` thực chất là hash của chuỗi `"password"`, không phải `12345` (đã kiểm bằng `password_verify`). `auth.php` bù bằng điều kiện cứng `password === '12345' && username === 'demo'`. |
| N3 | Cao | `db.js` "dual-engine" ghi song song localStorage + MySQL nhưng **chỉ đọc từ localStorage** → MySQL chỉ là bản sao ghi-một-chiều; dữ liệu 2 nơi lệch nhau. |
| N4 | Cao | Id ghi chú: localStorage dùng `seq`, MySQL dùng `AUTO_INCREMENT` → sửa/xóa ghi chú gửi sai id lên server. Demo có id `'demo'` (chuỗi) → `(int)'demo' = 0` → ghi chú demo không bao giờ vào MySQL. |
| N5 | Cao | User đăng nhập qua MySQL được cache vào localStorage **không có `salt/passHash`** → đổi mật khẩu / xóa tài khoản / `checkPassword` phía client luôn sai. `deleteUser` không gửi mật khẩu nhưng `delete_account` yêu cầu → xóa trên server luôn thất bại (lỗi bị nuốt). |
| N6 | TB | `backendOnline` được xác định bất đồng bộ; thao tác trước khi `status` trả về sẽ chỉ ghi local. Mọi lỗi API bị nuốt (`catch { return null }`). |
| N7 | TB | `Access-Control-Allow-Origin: *` cho API có thao tác ghi. |
| N8 | TB | `feature/grid-list` chứa toàn bộ commit của `update-new-exten` → hai nhánh không độc lập; merge cả hai sẽ trùng. |

## 7. File dùng chung có nguy cơ conflict cao
| File | Lý do |
|---|---|
| `js/app.js` | Menu điều hướng (`renderChrome`), `AUTH_PAGES`, tiện ích `HS.*` dùng ở mọi trang |
| `js/db.js` | Mọi trang gọi `DB.*`; nhánh đã viết lại 178 dòng |
| `css/app.css` | 1 file CSS cho toàn site; cả 2 nhánh đều sửa |
| `html/index.html` | Trang chủ = hero + ghi chú + điều luật nổi bật (3 module chung 1 file) |
| `README.md` | Đang xung đột sẵn |
| `tests/e2e.py` | 1 file test cho mọi luồng |
| `js/data.js` + `js/scorer.js` | Thêm điều luật phải sửa cả hai (CONCEPTS ↔ LAW_DATA) |

## 8. Vấn đề ảnh hưởng cài đặt / chạy thử / tích hợp
1. README sai và xung đột → người mới không biết chạy thế nào.
2. Không có Node trên ít nhất 1 máy → test JS không chạy; cần thống nhất công cụ (Node LTS) hoặc chuyển test sang chạy trong trình duyệt.
3. e2e hỏng đường dẫn.
4. Nhánh MySQL cần XAMPP + import `schema.sql`; chưa có hướng dẫn kiểm chứng; chưa rõ có được phép dùng SQL.
5. Hai nguồn dữ liệu điều luật (JS + PHP).
