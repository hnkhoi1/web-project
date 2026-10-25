# TIMELINE 14 NGÀY — Hình Sự Tra Cứu

> Từ **Thứ Hai 26/10/2026 (N1)** đến **Chủ Nhật 08/11/2026 (N14)**. Chi tiết từng task ở TASK_BOARD.md; file nào của ai xem FILE_OWNERSHIP.md.
> Mỗi ô ghi **việc chính của ngày** → *kết quả phải push lên `main` cuối ngày*. 🔗 = có người khác đang chờ kết quả này.
> Nếu trễ quá 1 ngày ở ô có 🔗, báo nhóm ngay để dời lịch của người đang chờ.

## Mốc chung
| Mốc | Ngày | Điều kiện đạt | Tag |
|---|---|---|---|
| M0 Khởi động | N1 | Đề bài nằm trong `docs/requirements/`, D2–D5 đã chốt (họp 30 phút) | — |
| M1 Hết lỗi `main` | N5 | F01–F05 xong; e2e chạy lại được | `dot-1` |
| M2 Lớp dữ liệu mới | N7 | T10 đã lên `main`, cả 3 người đã chuyển code của mình sang `DB` async | `dot-2` |
| M3 BackEnd chạy thật | N10 | Đăng nhập, ghi chú, liên hệ đi qua PHP + MySQL | `dot-3` |
| M4 Đóng băng tính năng | N12 | Đủ tính năng, kể cả trang quản trị; từ đây chỉ sửa lỗi | `dot-4` |
| M5 Nộp bài | N14 | Toàn bộ TESTING_CHECKLIST đạt, có PDF, tag `v1.0` | `v1.0` |

## Lịch theo ngày

| Ngày | kajima (BackEnd / CSDL) | Khôi (Luật / Chatbot) | caychay0512-dev (Giao diện / Test) |
|---|---|---|---|
| **N1** T2 26/10 | Họp chốt T00 (cả nhóm). Bắt đầu **T13**: viết `schema.sql` cho users, notes, contacts, prefs | **F03** `.gitignore` + `index.html` gốc. Gửi kajima danh sách cột cho bảng `chuong`/`dieu_luat`/`khoan` 🔗 | **F01** README: bỏ dấu xung đột, viết hướng dẫn chạy bản tĩnh |
| **N2** T3 27/10 | **T13**: các bảng luật, login_attempts, password_resets, lich_su_hoi_dap + `seed.sql` (demo/admin, bcrypt). *Push schema* 🔗 | **F04**: sửa scorer trả `none` khi điều luật chưa có dữ liệu | **F01** xong → **F02**: sửa BASE e2e, thêm `HS_BASE` |
| **N3** T4 28/10 | **T13**: xóa `DataStore.php` (F06), viết `docs/DATABASE.md` + ERD. Bắt đầu **T10** | **F04**: `tests/run-in-browser.html` chạy db-test và scorer-test | **F02**: tách test thành `tests/e2e/test_*.py` + `run_all.py` |
| **N4** T5 29/10 | **T10**: viết facade async theo API_CONTRACT §1 (adapter local) | **F05**: `tools/export_law.php` sinh `js/data.js` | **F02** xong: e2e chạy lại đạt 🔗 (T10 cần e2e để kiểm hồi quy) |
| **N5** T6 30/10 | **T10**: giữ API cũ (deprecated), thêm stub `DB.admin.*`, cập nhật `db-test`. *Push T10* 🔗 | **F05** xong. Chạy lại toàn bộ scorer-test | Đọc API_CONTRACT §1, chuẩn bị chuyển `notes.js`. Smoke test sau khi T10 lên → **M1** |
| **N6** T7 31/10 | **T11**: `backend/config.php`, `lib/db.php`, `lib/response.php`, `lib/validate.php` | **T24**: `search.js`, `detail.js`, `chatbot.js` chuyển sang `await DB.*` | **T30**: `notes.js` chuyển sang `DB` async |
| **N7** CN 01/11 | **T11**: `lib/auth.php` (session, CSRF, `require_login`) | **T24** xong: lọc qua `?q=&chuong=` trên URL | **T30** xong. Bắt đầu **T31** (`account.js`) → **M2** |
| **N8** T2 02/11 | **T11**: `api/auth.php` (login/register/me/logout, khóa 5 lần sai). *Push* 🔗 | **T21**: thêm điều 173, 175, 171 (+ CONCEPTS, test) | **T31**: `contact.js` + màn hình xác thực, map đúng mã lỗi |
| **N9** T3 03/11 | **T11**: quên mật khẩu/OTP, đổi mật khẩu, xóa tài khoản. `tests/api_smoke.php`. Bắt đầu **T12** (adapter api) | **T21**: thêm điều 155, 156, 128, 138 | **T31** xong. Bắt đầu **T32**: khung menu trang quản trị + `admin/index.html` (dùng stub) |
| **N10** T4 04/11 | **T12**: `notes.php`, `contacts.php`, `prefs.php`; adapter api thành mặc định. *Push* 🔗 → **M3** | **T21** xong (133, 135, 322). Chạy export sinh `seed_law.sql` (phần SQL của F05) | **T32**: trang người dùng, liên hệ, lịch sử (vẫn dùng stub). Từ hôm nay test qua XAMPP |
| **N11** T5 05/11 | **T14**: `admin.php` + `require_admin()`. *Push* 🔗 | **T23**: `law.php` (list/get/chuong đọc từ MySQL) | **T32**: thêm case vào `test_admin.py`; cập nhật README phần cài XAMPP/MySQL |
| **N12** T6 06/11 | Gỡ API deprecated trong `db.js`. Sửa lỗi T11–T14 | **T23**: `ask` ghi `lich_su_hoi_dap`, thêm case API. *Push* | **T32** chuyển sang API thật (T14) → **M4: đóng băng tính năng** |
| **N13** T7 07/11 | Chạy TESTING_CHECKLIST §3.1, §3.5 (phần API) + sửa lỗi. Xuất PDF thiết kế CSDL | Chạy §3.4 + sửa lỗi. Kiểm tra nội dung điều luật | Chạy §1, §3.2, §3.3, §4 + **T34** chụp màn hình (§5), ghép PDF |
| **N14** CN 08/11 | Sửa lỗi cuối. Kiểm thử cài mới hoàn toàn trên máy khác theo README | Sửa lỗi cuối. Rà lại README phần chatbot/dữ liệu | Hoàn tất PDF màn hình. **T40** cả nhóm: tag `v1.0`, zip bộ web, nộp → **M5** |

## Bàn giao giữa các thành viên (🔗)
| Ngày | Từ → Đến | Bàn giao | Nếu trễ thì |
|---|---|---|---|
| N1 | Khôi → kajima | Danh sách cột cho 3 bảng luật | kajima dùng cấu trúc `LAW_DATA` hiện có, Khôi chỉnh sau bằng migration |
| N2 | kajima → Khôi | `schema.sql` có bảng luật | F05 làm phần JS trước, phần SQL dời sang N10 (đã xếp sẵn) |
| N4 | caychay → kajima | e2e chạy lại được | kajima kiểm thủ công theo TESTING_CHECKLIST §1 trước khi push T10 |
| N5 | kajima → cả nhóm | Facade `DB` async (T10) | Khôi và caychay làm trước các phần không cần DB: T21 (Khôi), giao diện trang quản trị (caychay) |
| N8–N10 | kajima → cả nhóm | `auth.php` rồi notes/contacts API | Tiếp tục dùng adapter local; giao diện không phải đổi vì đã đi qua facade |
| N11 | kajima → caychay | `admin.php` | T32 giữ stub tới N12, chậm nhất N13 phải nối API thật |

## Thói quen hằng ngày
- **Đầu ngày (≤5 phút, nhắn nhóm):** hôm qua xong gì (kèm mã commit), hôm nay làm gì, có bị chặn không.
- **Cuối ngày:** push phần đã chạy được và cập nhật cột Trạng thái của mình trong TASK_BOARD.md. Không để code chưa push qua đêm.
- **Cuối mỗi mốc (N5, N7, N10, N12):** họp 15 phút, mỗi người chạy Smoke test (§1) trên `main` mới nhất rồi gắn tag.

## Khối lượng
| Người | Task | Điểm | Ngày dự phòng |
|---|---|---|---|
| kajima | T13, F06, T10, T11, T12, T14 | 12 | N13–N14 |
| Khôi | F03, F04, F05, T24, T21, T23 | 12 | N13–N14 |
| caychay0512-dev | F01, F02, T30, T31, T32, T34 | 12 | N14 (N13 làm T34) |

Điểm nghẽn của lịch là kajima ở N5 (T10) và N8–N11 (BackEnd). Nếu kajima trễ quá 1 ngày, Khôi (rảnh hơn từ N12) nhận `api/prefs.php` và `api/contacts.php` theo đúng contract.
