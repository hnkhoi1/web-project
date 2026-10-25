# ARCHITECTURE — Hình Sự Tra Cứu

> Mô tả kiến trúc **hiện tại** (`main`) và kiến trúc **đích**. **D1 đã chốt: PHP + MySQL** (xem §4). D2–D5 còn mở.

## 1. Kiến trúc hiện tại (`main`) — SPA-less, nhiều trang tĩnh

```
Trình duyệt
 ├─ html/<trang>.html        chỉ chứa <main>; header/footer do app.js chèn vào #app-header/#app-footer
 ├─ js/db.js     (lớp dữ liệu)  DEMO_USER, DB.*, Session  → localStorage['hs_demo_db_v1'], ['hs_session']
 ├─ js/app.js    (khung)        chặn chưa đăng nhập, áp prefs, renderChrome(), window.HS = tiện ích
 ├─ js/<trang>.js (theo trang)  auth / notes / account / contact / search / detail / chatbot
 └─ js/data.js → nlu.js → scorer.js → law.js   (tri thức luật + NLU, chạy hoàn toàn client)
```

### Thứ tự nạp script (bắt buộc)
`db.js` → `app.js` (trong `<head>`, đồng bộ) → [`data.js` → `nlu.js` → `scorer.js` → `law.js`] → script của trang (cuối `<body>`).
Mọi script trang mở đầu bằng `const { user } = HS; if (!user) return;`.

### Module và trách nhiệm
| Module | File | Phụ thuộc | Trang dùng |
|---|---|---|---|
| Data access | `js/db.js` | localStorage, `crypto.subtle` | tất cả |
| Shell/khung | `js/app.js`, `css/app.css` | `DB`, `Session` | tất cả |
| Auth | `js/auth.js` | `HS`, `DB` | login, register, forgot-password |
| Notes | `js/notes.js` | `HS`, `DB`, `LAW_DATA` | index |
| Account | `js/account.js` | `HS`, `DB` | ho-so, doi-mat-khau, tuy-chinh, database |
| Contact | `js/contact.js` | `HS`, `DB` | lien-he |
| Law knowledge | `js/data.js` (LAW_DATA), `js/nlu.js` (TF-IDF, `vnNormalize`), `js/scorer.js` (Scorer), `js/law.js` (Law) | — | tra-cuu, tu-van, dieu-luat-chi-tiet, index |
| Search UI | `js/search.js` | `Law` | tra-cuu |
| Detail UI | `js/detail.js` | `Law`, `DB` | dieu-luat-chi-tiet |
| Chatbot UI | `js/chatbot.js` | `Law`, `DB` | tu-van |
| (mồ côi) | `data/DataStore.php`, `data/dieu_luat_data.php` | — | không trang nào |

### Mô hình dữ liệu hiện tại (localStorage `hs_demo_db_v1`)
```
{ seq: number,
  users:    [{ id:'u<n>', email, displayName, salt, passHash, avatar(dataURL|null), verified, builtIn:false, createdAt }],
  notes:    [{ id:number, userId, title(≤120), content(≤5000), color, pinned, source:{type:'article'|'chat', ref}|null, createdAt, updatedAt }],
  contacts: [{ id, userId, ho_ten, sdt, email, noi_dung, createdAt }],
  prefs:    { [userId]: { theme:'system'|'light'|'dark', font:'sm'|'md'|'lg'|'xl', noteColor } } }
DEMO_USER (hằng số, không lưu): { id:'demo', username:'demo', builtIn:true, ... }
LAW_DATA: [{ id, chuong, tieu_de, tom_tat, tu_khoa, khung_hinh_phat, can_cu_boi_thuong, ghi_chu_quyen_loi?,
             khoan:[{ so_khoan, dieu_kien, noi_dung, quyen_loi, tu_khoa, ti_le_ton_thuong_min/max, gia_tri_tai_san_min/max, so_nguoi_chet_min }] }]
```

## 2. Nhánh `update-new-exten` / `feature/grid-list` — KHÔNG DÙNG
Nhóm quyết định (25/10/2026) coi hai nhánh này như chưa đóng góp gì; mọi việc phát triển lại từ `main`. Phần mô tả dưới đây chỉ giữ lại như bài học về các lỗi cần tránh (PROJECT_AUDIT §6.2).
Thêm `backend/config.php` (PDO MySQL `hinh_su_db`), `backend/api/{auth,notes,contacts,news}.php`, `database/schema.sql` (users, notes, contacts, news), `js/db.js` "dual-engine" (ghi cả localStorage và MySQL, đọc localStorage). Không có phiên server.

## 3. Kiến trúc đích (D1 = PHP + MySQL)

Nguyên tắc: **HTML/CSS/JS + PHP 8 + MySQL/MariaDB (XAMPP)**, không thêm framework, không Composer. BackEnd **viết mới trên `main`**; không chép code từ hai nhánh cũ. Các lỗi N1–N8 (PROJECT_AUDIT §6.2) là danh sách lỗi cần tránh.

```
Trình duyệt
 html/*.html + js/<trang>.js ──gọi──► js/db.js  (FACADE DUY NHẤT, mọi hàm trả Promise)
                                          │  chọn adapter MỘT LẦN trong DB.ready()
                     ┌────────────────────┴─────────────────────┐
         Adapter "api" (MẶC ĐỊNH khi http://)          Adapter "local" (chỉ khi file:// hoặc API không phản hồi lúc khởi động)
         fetch + cookie session + X-CSRF-Token          localStorage — bản demo tĩnh để nộp "bộ web tĩnh"
                     │
Apache (XAMPP)       ▼
 backend/
   config.php            hằng số DB_*, mở session, header JSON (không CORS *)
   lib/db.php            getPDO() — PDO, ERRMODE_EXCEPTION, utf8mb4, prepared statements
   lib/auth.php          require_login(), require_admin(), current_user(), csrf_check()
   lib/validate.php      cùng quy tắc với HS.passwordError / nameError (API_CONTRACT §1)
   lib/response.php      ok($data) / fail($code, $msg, $http)
   api/auth.php  api/notes.php  api/contacts.php  api/prefs.php   (TV-A)
   api/law.php                                                     (TV-B)
   api/admin.php                                                   (TV-A; UI ở html/admin/ của TV-C)
 database/
   schema.sql            tạo CSDL hinh_su_db (không seed)
   seed.sql              tài khoản demo + admin (bcrypt đúng mật khẩu), ghi chú mẫu
   seed_law.sql          SINH TỰ ĐỘNG từ data/dieu_luat_data.php
   migrations/NNN_*.sql  mọi thay đổi schema sau v1
        │
        ▼
 MySQL hinh_su_db
```

### Mô hình CSDL đích (sẽ hoàn thiện trong T13)
| Bảng | Cột chính | Ghi chú |
|---|---|---|
| `users` | id, username NULL UNIQUE, email UNIQUE, display_name, password_hash (bcrypt), avatar MEDIUMTEXT, verified, is_builtin, **role ENUM('user','admin')**, created_at | demo `is_builtin=1` không sửa/xóa được (kiểm ở server) |
| `user_prefs` | user_id PK/FK, theme, font, note_color | **mới** — hiện prefs chỉ ở localStorage |
| `notes` | id, user_id FK CASCADE, title, content, color, pinned, source_type, source_ref, created_at, updated_at | |
| `contacts` | id, user_id FK NULL, ho_ten, sdt, email, noi_dung, trang_thai ENUM('moi','da_tra_loi'), created_at | |
| `login_attempts` | ident, fail_count, locked_until | **mới** — khóa 5 lần sai phía server |
| `password_resets` | user_id, otp_hash, expires_at, attempts, used | **mới** — OTP lưu server |
| `chuong` / `dieu_luat` / `khoan` | theo cấu trúc `LAW_DATA` (khoan có các cột định lượng, quyen_loi) | seed từ `seed_law.sql` |
| `lich_su_hoi_dap` | id, user_id NULL, cau_hoi, dieu_luat_id NULL, khoan_so NULL, diem, created_at | phục vụ trang quản trị |
| `news` | so_hieu, ten, loai, co_quan, ngay_ban_hanh, ngay_hieu_luc, tom_tat | chỉ khi D5 = làm |

### Quy tắc bắt buộc
- **Server quyết định danh tính** từ `$_SESSION['uid']`; API không đọc `user_id`/`id người dùng` từ body (sửa N1).
- **Không ghi song song** hai nơi (sửa N3/N4): khi adapter `api` đang dùng thì `localStorage` chỉ giữ prefs giao diện cục bộ (tùy chọn), không giữ users/notes.
- Mọi truy vấn dùng prepared statement; mật khẩu `password_hash(PASSWORD_BCRYPT)`; không so sánh mật khẩu dạng chữ thường trong code (sửa N2).
- Thao tác ghi: POST + `X-CSRF-Token`; session cookie `HttpOnly`, `SameSite=Lax`; `session_regenerate_id()` sau khi đăng nhập.
- Lỗi API không được nuốt im lặng: facade ném `DBError(code)` để UI báo đúng thông báo (sửa N6).
- Cấu hình kết nối: `backend/config.local.php` (bị `.gitignore`) ghi đè `config.php` để mỗi máy dùng mật khẩu MySQL riêng.
- **Trang quản trị (BackEnd menu chức năng)**: `html/admin/*.html` + `js/admin.js` gọi `backend/api/admin.php`; menu: Tổng quan · Người dùng · Liên hệ · Lịch sử hỏi đáp · (Điều luật nếu D2 cho sửa).

## 4. Quyết định cần nhóm chốt (ADR mở)

| ID | Câu hỏi | Lựa chọn | Đề xuất của coordinator |
|---|---|---|---|
| **D1** | Lưu trữ BackEnd | ~~(a) demo tĩnh~~ · ~~(b) PHP + JSON~~ · **(c) PHP + MySQL** | ✅ **Đã chốt: PHP + MySQL** (nhóm, 25/10/2026). Hệ quả: `data/DataStore.php` (kho JSON) bị loại bỏ; ràng buộc "không SQL/.sql" trong HANDOVER coi như **không áp dụng** — nhóm cần giữ bằng chứng đề bài cho phép (đưa đề bài vào `docs/requirements/`). |
| D2 | Nguồn gốc dữ liệu điều luật | `data/dieu_luat_data.php` là gốc → sinh `seed_law.sql` + `js/data.js` / Bảng MySQL là gốc, sửa qua trang quản trị | Đề xuất: **file PHP là gốc** (dễ review trong Git); bảng MySQL và `js/data.js` đều sinh từ nó; trang quản trị chỉ xem điều luật, không sửa. |
| D3 | Giữ chế độ offline (`file://`) bằng adapter local? | Có / Không | Có — để nộp "bộ web tĩnh" (R4). Nếu bỏ, xóa T10-phần-local và giảm việc cho TV-A. |
| D4 | Mở rộng dữ liệu luật tới bao nhiêu điều? | 8 hiện tại / ~18 (khớp CONCEPTS) | ~18 điều đã có khái niệm trong `scorer.js`. |
| D5 | Có làm trang "Văn bản mới" không? | Làm / Không | Trang này không có trên `main` (chỉ có ở nhánh đã bỏ). **Chỉ làm nếu đề bài yêu cầu**; mặc định là không. |

## 5. Thông tin còn thiếu
- Đề bài gốc của giảng viên (file PDF/Word) và `PLAN.txt` — cần để xác nhận MySQL được phép.
- Yêu cầu cụ thể về "menu chức năng BackEnd" và hình thức nộp (giảng viên chấm trên XAMPP hay chỉ mở file tĩnh?).
- Phiên bản XAMPP/PHP/MySQL của từng thành viên (máy audit: PHP 8.2.12) — thống nhất tối thiểu PHP 8.1, MariaDB 10.4+.
- Danh sách điều luật tối thiểu phải có.
