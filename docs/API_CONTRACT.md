# API CONTRACT — Hình Sự Tra Cứu

> Hai lớp hợp đồng: (1) **JS facade `DB` / `Law`** mà mọi trang gọi; (2) **HTTP API PHP + MySQL** (D1 đã chốt, 25/10/2026).
> Trạng thái: **ĐỀ XUẤT v0.1 — chờ nhóm duyệt**. Khi đã duyệt, mọi thay đổi theo quy trình §4.

## 1. JS facade `DB` (owner: TV-A) — `js/db.js`

**Thay đổi so với hiện tại:** mọi hàm trả về `Promise` (hiện nay `listNotes`, `saveNote`, `getPrefs`… là đồng bộ). Adapter local vẫn giữ nguyên hành vi; chỉ bọc `async`. Lỗi nghiệp vụ trả qua `throw new DBError(code, message)`.

| Hàm | Vào | Ra | Mã lỗi |
|---|---|---|---|
| `DB.ready()` | — | `{ engine: 'local'\|'api' }` | — |
| `DB.currentUser()` | — | `User \| null` | — |
| `DB.login(ident, password)` | string, string | `User` | `INVALID_CREDENTIALS`, `LOCKED` |
| `DB.logout()` | — | `void` | — |
| `DB.register({email, displayName, password})` | | `User` (đã đăng nhập) | `EMAIL_EXISTS`, `VALIDATION`, `STORAGE` |
| `DB.verifyEmail()` | — | `User` | `DEMO_LOCKED` |
| `DB.requestPasswordReset(email)` | | `{ simulatedMail: {otp, link} \| null }` | `DEMO_LOCKED` |
| `DB.resetPassword({email, otp, password})` | | `void` | `OTP_INVALID`, `OTP_EXPIRED`, `OTP_LOCKED` |
| `DB.updateProfile({displayName?, avatar?})` | avatar = dataURL JPEG 256² hoặc `null` | `User` | `DEMO_LOCKED`, `VALIDATION`, `STORAGE` |
| `DB.changePassword(current, next)` | | `void` | `DEMO_LOCKED`, `WRONG_PASSWORD` |
| `DB.deleteAccount(password)` | | `void` | `DEMO_LOCKED`, `WRONG_PASSWORD` |
| `DB.getPrefs()` / `DB.setPrefs(p)` | `{theme, font, noteColor}` | `Prefs` | — |
| `DB.listNotes(q?)` | | `Note[]` (ghim trước, mới trước) | — |
| `DB.saveNote(note)` | `{id?, title, content, color, pinned, source?}` | `Note` (có `id` thật từ nguồn lưu) | `VALIDATION`, `LIMIT_200`, `NOT_FOUND` |
| `DB.pinNote(id, pinned)` / `DB.deleteNote(id)` | | `void` | `NOT_FOUND` |
| `DB.addContact({ho_ten, sdt?, email, noi_dung})` | | `{id}` | `VALIDATION` |
| `DB.admin.*` | xem §2.5 | | `FORBIDDEN` |

- **Không còn tham số `uid`** ở các hàm ghi chú/prefs: danh tính lấy từ phiên hiện tại (local: `hs_session`; api: cookie PHP session).
- Kiểu `User` trả cho UI: `{ id: string, username?: string, email, displayName, avatar, verified, builtIn, role: 'user'|'admin' }` — **không bao giờ** chứa `salt/passHash`.
- `Note`: `{ id: string, title, content, color, pinned, source: {type, ref}|null, createdAt, updatedAt }` (id là **string** ở mọi adapter để tránh lỗi số/chuỗi N4).

### Quy tắc validate dùng chung (cả client lẫn server phải giống nhau)
| Trường | Quy tắc |
|---|---|
| email | `^[^\s@]+@[^\s@]+\.[^\s@]+$`, ≤254, lưu chữ thường |
| displayName | 2–40 ký tự, không ký tự điều khiển |
| password | 8–72 ký tự, có chữ và số |
| note.title / content | ≤120 / ≤5000; không được cả hai rỗng; tối đa 200 ghi chú/người |
| contact | họ tên 2–60, email hợp lệ, sđt `^\+?[0-9 .\-]{8,15}$` (tùy chọn), nội dung 10–2000 |
| avatar | JPG/PNG/WebP, ≤2MB, ≥64×64, client chuẩn hóa về JPEG 256×256 |

## 2. HTTP API PHP (owner: TV-A, phần luật: TV-B)

Chung: `Content-Type: application/json; charset=utf-8`. Phản hồi luôn `{ "ok": true, "data": … }` hoặc `{ "ok": false, "error": "CODE", "message": "…" }`. Xác thực bằng cookie session PHP (`HttpOnly`, `SameSite=Lax`). Không bật CORS `*`. Thao tác ghi yêu cầu header `X-CSRF-Token` (lấy từ `GET /auth.php?action=me`).

### 2.1 `backend/api/auth.php`
| Action | Method | Body | Ghi chú |
|---|---|---|---|
| `me` | GET | — | `{user, csrf}` hoặc `user:null` |
| `login` | POST | `{ident, password}` | khóa 60s sau 5 lần sai (lưu server) |
| `logout` | POST | — | |
| `register` | POST | `{email, displayName, password}` | tự đăng nhập |
| `verify_email` | POST | — | giả lập |
| `request_reset` / `reset_password` | POST | `{email}` / `{email, otp, password}` | OTP lưu server, 15 phút, 5 lần |
| `update_profile` | POST | `{displayName?, avatar?}` | |
| `change_password` | POST | `{current, next}` | |
| `delete_account` | POST | `{password}` | |

### 2.2 `backend/api/notes.php` — `list` (GET `?q=`), `save`, `pin`, `delete` (POST). **Không nhận `user_id`.**
### 2.3 `backend/api/contacts.php` — `create` (POST).
### 2.4 `backend/api/law.php` (owner TV-B)
| Action | Method | Vào | Ra |
|---|---|---|---|
| `list` | GET | `?q=&chuong=` | `[{id, chuong, tieu_de, tom_tat, khung_hinh_phat, score?}]` |
| `get` | GET | `?id=` | 1 điều luật đầy đủ + `lien_quan` |
| `chuong` | GET | — | `string[]` |
| `ask` | POST | `{question}` | cùng cấu trúc `Law.answer()` §3; ghi `lich_su_hoi_dap` |

### 2.5 `backend/api/admin.php` (role = admin; owner API: TV-A, UI: TV-C)
`stats`, `users.list`, `users.delete`, `contacts.list`, `contacts.set_status {id, trang_thai: moi|da_tra_loi}`, `history.list`, `laws.save`, `laws.delete` (nếu D2 cho phép sửa luật qua giao diện).

## 3. JS `Law` (owner: TV-B) — giữ nguyên chữ ký hiện tại
- `Law.search(q, chuong) → Article[]` (có `score` khi tìm theo nội dung)
- `Law.byId(id) → Article|null`, `Law.chuongList() → string[]`, `Law.lienQuan(article, n=4)`
- `Law.answer(question) → { found:false } | { found:true, mode:'overview'|'specific', score, role:'nan_nhan'|…, alt, article:{id,tieu_de,chuong}, khoan?, khoan_all?, ask?, quyen_loi, can_cu, ghi_chu_quyen_loi, note_title, note_text }`
- Bất kỳ thay đổi trường nào trong kết quả `answer` → phải báo TV-C (chatbot.js do TV-B giữ nhưng note_* dùng cho ghi chú).

## 4. Quy trình thay đổi contract / schema
1. Báo nhóm, rồi sửa **file này trước** trong một commit riêng `contract: …` trên `main` (ghi mục "Changelog" bên dưới) và push trước khi push code.
2. Owner contract và ít nhất 1 thành viên đang gọi contract đó phải xác nhận trong nhóm chat trước khi push.
3. Thêm mới = tương thích ngược, push được ngay. Đổi tên/xóa trường = giữ alias cũ ít nhất 1 đợt, ghi `@deprecated`.
4. Thay đổi `database/schema.sql`: chỉ owner (TV-A); kèm file `database/migrations/NNN_mo_ta.sql` và cập nhật seed; báo cả nhóm import lại.

## Changelog
- v0.1 (25/10/2026): bản đề xuất đầu tiên từ audit.
- v0.2 (25/10/2026): D1 = PHP + MySQL; làm việc thẳng trên `main`, bỏ quy trình PR.
