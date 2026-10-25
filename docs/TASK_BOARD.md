# TASK BOARD — Hình Sự Tra Cứu

> Phiên bản v0.3 · cập nhật 25/10/2026. Các quyết định nhóm đã chốt:
> - **D1:** dùng PHP + MySQL.
> - **`main` là gốc duy nhất.** Hai nhánh `update-new-exten` và `feature/grid-list` coi như **không đóng góp gì**: không merge, không chép code từ đó. Mọi tính năng làm lại trên `main`.
> - **Cả nhóm commit thẳng lên `main`.** Không dùng nhánh tính năng hay Pull Request. Quy tắc làm việc ở cuối file.
> - **Sửa lỗi trước, phát triển sau** (Giai đoạn 1 xong rồi mới sang Giai đoạn 2).
>
> **Lịch 14 ngày (26/10 – 08/11/2026) cho từng người: xem [TIMELINE.md](TIMELINE.md).**
>
> Ước lượng khối lượng: S = 1 điểm (≤½ ngày), M = 2 điểm (1–2 ngày), L = 3 điểm (3–4 ngày).

## Phân công

| Người | Phụ trách | Task | Điểm |
|---|---|---|---|
| **kajima** | Lớp dữ liệu `DB`, BackEnd PHP, CSDL MySQL, bảo mật | F06, T10, T11, T12, T13, T14 | 12 |
| **Khôi** (hnkhoi1) | Dữ liệu điều luật, chatbot/NLU, tra cứu, chi tiết điều luật | F03, F04, F05, T21, T23, T24 | 12 |
| **caychay0512-dev** | Giao diện người dùng: ghi chú, tài khoản, trang quản trị; README; e2e; PDF nộp bài | F01, F02, T30, T31, T32, T34 | 12 |

Mỗi người chỉ sửa file thuộc phần mình (xem FILE_OWNERSHIP.md). Báo tiến độ bằng cách cập nhật cột **Trạng thái** ở dòng task của mình: ⬜ chưa làm · 🔄 đang làm · ✅ xong (ghi kèm mã commit).

---

## Giai đoạn 1 — Sửa lỗi trên `main` (làm trước)

| ID | Lỗi | Người làm | Cỡ | Trạng thái |
|---|---|---|---|---|
| F01 | README còn dấu xung đột merge và mô tả sai (B1) | caychay0512-dev | M | ⬜ |
| F02 | `tests/e2e.py` trỏ sai thư mục nên toàn bộ e2e hỏng (B2) | caychay0512-dev | M | ⬜ |
| F03 | Thiếu `.gitignore`; thư mục gốc không có trang vào (B6, B8) | Khôi | S | ⬜ |
| F04 | Chatbot trả sai điều luật với tình huống ngoài dữ liệu (B5) + chạy test không cần Node | Khôi | M | ⬜ |
| F05 | Dữ liệu điều luật nằm ở 2 nơi JS/PHP (B3) | Khôi | M | ⬜ |
| F06 | `data/DataStore.php` không được dùng (B4) | kajima | gộp vào T13 | ⬜ |
| — | Đăng nhập/khóa có thể vượt qua bằng DevTools (B7) | kajima | sửa trong T11 | ⬜ |

### F01 — README đúng thực tế (caychay0512-dev, M, P0)
- **Mục tiêu:** bỏ dấu `<<<<<<<` / `=======` / `>>>>>>>` và đoạn mô tả `legal-demo/` cũ. Ghi đúng cấu trúc hiện tại và cách chạy tĩnh. Phần cài XAMPP/MySQL viết sau, khi T13 có lệnh import (lúc đó cập nhật lại README).
- **Được sửa:** `README.md`.
- **Hoàn thành khi:** `git grep -n "<<<<<<<\|>>>>>>>" README.md` không ra gì. Một thành viên khác làm theo README và chạy được bản tĩnh.
- **Test:** không có. **Cần xong trước:** không.

### F02 — Sửa và tách e2e (caychay0512-dev, M, P0)
- **Mục tiêu:**
  - Sửa đường dẫn BASE sang thư mục `html/`.
  - Thêm biến môi trường `HS_BASE` để chọn chạy bằng `file://` hay `http://`.
  - Tách thành `tests/e2e/test_auth.py`, `test_notes.py`, `test_law.py`, `test_account.py`, `test_admin.py`, cùng `tests/e2e/run_all.py`.
- **Được sửa:** `tests/e2e.py` (xóa sau khi tách), `tests/e2e/**`.
- **Hoàn thành khi:** `python tests/e2e/run_all.py` chạy được, và đạt cùng số case như e2e cũ (HANDOVER báo 26/26 nhưng **chưa kiểm chứng**). README có hướng dẫn cài Playwright.
- **Test:** chính bộ e2e.
- **Conflict:** từ đây mỗi người tự thêm case vào file test của module mình.

### F03 — `.gitignore` và trang gốc (Khôi, S, P1)
- **Mục tiêu:** `.gitignore` gồm `*.zip`, `.claude/`, `backend/config.local.php`, `node_modules/`, `.vscode/`. Thêm `index.html` ở thư mục gốc, chuyển hướng tới `html/login.html`.
- **Được sửa:** `.gitignore`, `index.html` (gốc).
- **Hoàn thành khi:** mở `http://localhost/web-project/` thì vào trang đăng nhập.

### F04 — Độ chính xác chatbot và test không cần Node (Khôi, M, P0)
- **Mục tiêu:**
  - Khi khái niệm khớp nhất trỏ tới điều luật chưa có dữ liệu, trả `none` kèm gợi ý, không chọn bừa điều khác. Ví dụ hiện tại: "vay tiền không trả" đang ra Điều 174, phải hết.
  - Tạo `tests/run-in-browser.html` để chạy `db-test` và `scorer-test` ngay trong trình duyệt (máy không có Node vẫn chạy được).
- **Được sửa:** `js/scorer.js`, `js/law.js`, `tests/scorer-test.js`, `tests/run-in-browser.html`.
- **Hoàn thành khi:** 18 câu cũ vẫn đúng; "vay tiền không trả", "bị trộm xe máy", "bị vu khống" trả `none` (cho tới khi T21 bổ sung dữ liệu); trang chạy test hiện tổng PASS/FAIL.

### F05 — Một nguồn dữ liệu điều luật (Khôi, M, P1)
- **Mục tiêu:** `data/dieu_luat_data.php` là nguồn duy nhất. Script `tools/export_law.php` sinh `js/data.js`, và sinh `database/seed_law.sql` khi T13 đã có bảng luật. File sinh ra có dòng đầu "FILE SINH TỰ ĐỘNG — KHÔNG SỬA TAY".
- **Được sửa:** `data/dieu_luat_data.php`, `tools/export_law.php`, `js/data.js` (chỉ qua script), `database/seed_law.sql` (chỉ qua script).
- **Hoàn thành khi:** chạy `php tools/export_law.php` rồi `git diff --exit-code js/data.js` không thấy khác biệt về nội dung; scorer-test vẫn đạt.
- **Cần xong trước:** phần sinh `seed_law.sql` cần DDL bảng luật từ T13.

---

## Giai đoạn 2 — Phát triển

| ID | Tên | Người làm | Ưu tiên | Cỡ | Cần xong trước | Trạng thái |
|---|---|---|---|---|---|---|
| T00 | Đưa đề bài gốc vào repo, chốt D2–D5 | Cả nhóm (kajima ghi biên bản) | P0 | — | — | 🟡 D1 xong |
| T10 | Facade `DB` bất đồng bộ (adapter local) | kajima | P0 | L | F02 | ⬜ |
| T13 | CSDL MySQL: schema, seed, migrations, PDF thiết kế CSDL | kajima | P0 | M | — | ⬜ |
| T11 | BackEnd lõi: `lib/*`, session, CSRF, `auth.php` | kajima | P0 | L | T10, T13 | ⬜ |
| T12 | `notes.php`, `contacts.php`, `prefs.php` + adapter api | kajima | P1 | M | T11 | ⬜ |
| T14 | `admin.php` + vai trò admin | kajima | P1 | M | T11 | ⬜ |
| T21 | Mở rộng dữ liệu lên ~18 điều luật | Khôi | P1 | L | F04, F05 | ⬜ |
| T23 | `law.php` + ghi log lịch sử hỏi đáp | Khôi | P1 | M | T11, F05 | ⬜ |
| T24 | Tra cứu/chi tiết/chatbot chuyển sang `DB` async, đồng bộ bộ lọc lên URL | Khôi | P2 | M | T10 | ⬜ |
| T30 | Ghi chú chuyển sang `DB` async (+ xem lưới/danh sách nếu nhóm muốn) | caychay0512-dev | P1 | M | T10 | ⬜ |
| T31 | Tài khoản/liên hệ/màn hình xác thực chuyển sang `DB` async | caychay0512-dev | P1 | M | T10 | ⬜ |
| T32 | Trang quản trị (menu chức năng BackEnd) | caychay0512-dev | P1 | L | T10 (stub), T14 | ⬜ |
| T34 | Responsive/a11y, chụp màn hình làm PDF nộp | caychay0512-dev | P2 | S | các task khác | ⬜ |
| T40 | Đóng gói và nộp bài | Cả nhóm | cuối | — | tất cả | ⬜ |

### T00 — Đề bài và các quyết định còn mở (cả nhóm)
- **Mục tiêu:** đưa đề bài gốc vào `docs/requirements/` và chốt D2–D5 (ARCHITECTURE §4).
- **Ghi chú:** D5 (trang "Văn bản mới") chỉ làm nếu đề bài yêu cầu; nó không có trên `main`.
- **Hoàn thành khi:** ARCHITECTURE §4 ghi "Đã chốt" cho từng quyết định.

### T10 — Facade `DB` bất đồng bộ (kajima, L, P0)
- **Mục tiêu:** hiện thực API_CONTRACT §1 bằng adapter localStorage, giữ nguyên hành vi. API cũ (đồng bộ, có tham số `uid`) giữ lại, đánh dấu `@deprecated`, để từng người chuyển dần phần của mình. Thêm stub `DB.admin.*` để caychay0512-dev làm T32 trước khi có API thật.
- **Được sửa:** `js/db.js`, `js/auth.js`. Trong `js/app.js` chỉ sửa khối khởi tạo `user` và thêm `HS.ready`. Thêm `tests/db-test.js`.
- **Hoàn thành khi:**
  - `db-test` đạt 100% (Node hoặc `run-in-browser.html`).
  - e2e vẫn đạt.
  - Object trả về cho UI không có `salt`/`passHash`.
  - Id ghi chú là chuỗi.
- **Conflict:** `app.js` dùng chung. Báo nhóm trước khi sửa và push trong ngày. Đây là commit lớn đầu tiên của Giai đoạn 2.
- **Dọn dẹp:** gỡ API deprecated sau khi T24, T30, T31 xong.

### T13 — CSDL MySQL (kajima, M, P0)
- **Mục tiêu:**
  - Viết mới `database/schema.sql` gồm các bảng: users (có `role`), user_prefs, notes, contacts, login_attempts, password_resets, chuong, dieu_luat, khoan, lich_su_hoi_dap. Cấu trúc theo ARCHITECTURE §3.
  - Viết `database/seed.sql` có tài khoản demo và admin, mật khẩu băm bcrypt đúng.
  - Tạo thư mục `database/migrations/`.
  - Viết `docs/DATABASE.md` (ERD + mô tả cột) và xuất PDF nộp bài.
  - Xóa `data/DataStore.php` (F06).
- **Hoàn thành khi:**
  - Import `schema → seed → seed_law` vào MySQL trống không lỗi; chạy lại lần 2 cũng không lỗi.
  - Xóa một user thì ghi chú và prefs của người đó tự xóa theo.
  - `password_verify` đúng với mật khẩu demo.
- **Phối hợp:** Khôi đề xuất cột cho 3 bảng luật, kajima viết DDL.

### T11 — BackEnd lõi (kajima, L, P0)
- **Mục tiêu:**
  - `backend/config.php` + `config.local.example.php`, không bật CORS `*`.
  - `backend/lib/db.php`, `auth.php`, `validate.php`, `response.php`.
  - `backend/api/auth.php` đúng API_CONTRACT §2.1.
  - Server xác định người dùng qua session, **không đọc `user_id` từ client**.
  - CSRF, `session_regenerate_id` sau đăng nhập, khóa 5 lần sai và OTP đều lưu ở server.
  - Mật khẩu chỉ kiểm bằng `password_verify`, không so sánh chuỗi cứng.
- **Hoàn thành khi:**
  - Gọi API khi chưa đăng nhập → 401.
  - Thiếu CSRF token → 403.
  - Đổi trình duyệt vẫn bị khóa sau 5 lần sai.
  - `php -l` sạch.
- **Test:** `php tests/api_smoke.php` chạy luồng đăng ký → đăng nhập → me → đổi mật khẩu → quên mật khẩu → xóa tài khoản.

### T12 — API ghi chú, liên hệ, tùy chỉnh + adapter api (kajima, M, P1)
- **Mục tiêu:** `notes.php`, `contacts.php`, `prefs.php`. Adapter `api` trong `db.js` là mặc định khi chạy `http://`, chỉ chọn adapter một lần trong `DB.ready()`, không ghi song song vào hai nơi. Lỗi API phải hiện lên UI.
- **Hoàn thành khi:** ghi chú tạo ở trình duyệt A thấy được ở trình duyệt B; e2e đạt với `HS_BASE=http`.

### T14 — API quản trị (kajima, M, P1)
- **Mục tiêu:** `backend/api/admin.php` theo API_CONTRACT §2.5, dùng `require_admin()`.
- **Hoàn thành khi:** user thường gọi → 403; admin xem được thống kê, người dùng, liên hệ (đổi được trạng thái), lịch sử hỏi đáp; không xóa được tài khoản demo hay admin.

### T21 — Mở rộng dữ liệu điều luật (Khôi, L, P1)
- **Mục tiêu:** bổ sung các điều 128, 133, 135, 138, 155, 156, 171, 173, 175, 322. Cấu trúc theo từng khoản, có `quyen_loi` với tội có người bị hại. Thêm/điều chỉnh CONCEPTS tương ứng.
- **Được sửa:** `data/dieu_luat_data.php`, `js/scorer.js`, `tests/scorer-test.js`; sau đó chạy lại script export.
- **Hoàn thành khi:**
  - "bị trộm xe máy" → 173, "vay tiền không trả" → 175, "bị vu khống" → 156, "bị chửi trên facebook" → 155.
  - Mỗi điều mới có ít nhất 2 câu test.
  - 18 câu cũ vẫn đúng.

### T23 — API điều luật (Khôi, M, P1)
- **Mục tiêu:** `backend/api/law.php` với các action `list`, `get`, `chuong` (đọc MySQL) và `ask`. Với `ask`, chatbot vẫn chấm điểm bằng `scorer.js` ở trình duyệt; server chỉ ghi `lich_su_hoi_dap`.
- **Hoàn thành khi:** khi chạy qua `http://`, trang Tra cứu và Chi tiết lấy dữ liệu qua API; mỗi câu hỏi chatbot tạo một dòng log.
- **Lưu ý:** dùng `backend/lib/*` của kajima, không sửa các file đó.

### T24 — Trang luật dùng `DB` async (Khôi, M, P2)
- **Được sửa:** `js/search.js`, `js/detail.js`, `js/chatbot.js`, `html/tra-cuu.html`, `html/tu-van.html`, `html/dieu-luat-chi-tiet.html`.
- **Hoàn thành khi:** nút "Lưu vào ghi chú" dùng `await DB.saveNote(...)`; các tham số `?q=&chuong=` trên URL khôi phục đúng bộ lọc.

### T30 — Ghi chú (caychay0512-dev, M, P1)
- **Được sửa:** `js/notes.js`, `html/index.html`, vùng `/* == notes == */` trong `css/app.css`.
- **Hoàn thành khi:** mọi thao tác ghi chú dùng API async; hiển thị đúng trạng thái rỗng và trạng thái tìm không thấy. Chế độ lưới/danh sách chỉ thêm nếu nhóm đồng ý (không có trong yêu cầu).

### T31 — Tài khoản và xác thực (caychay0512-dev, M, P1)
- **Được sửa:** `js/account.js`, `js/contact.js`, markup của các trang login, register, forgot-password, ho-so, doi-mat-khau, tuy-chinh, lien-he, database.
- **Lưu ý:** logic xác thực trong `js/auth.js` thuộc kajima.
- **Hoàn thành khi:** thông báo lỗi khớp đúng mã lỗi trong contract; tài khoản demo vẫn bị khóa sửa/xóa.

### T32 — Trang quản trị (caychay0512-dev, L, P1)
- **Mục tiêu:** đáp ứng yêu cầu "BackEnd có menu chức năng". Gồm `html/admin/index.html` (Tổng quan), `nguoi-dung.html`, `lien-he.html`, `lich-su.html` và `js/admin.js`.
- **Cách làm:** làm trước với stub `DB.admin.*`, chuyển sang API thật khi T14 xong.
- **Hoàn thành khi:** user thường bị chặn ở cả UI và API; admin dùng được đủ mục trong menu.

### T34 — Hoàn thiện giao diện và PDF (caychay0512-dev, S, P2)
- Kiểm tra mọi trang ở 375/768/1280px và khi dùng bàn phím.
- Chụp màn hình theo danh sách ở TESTING_CHECKLIST §5 rồi ghép thành PDF.

### T40 — Nộp bài (cả nhóm)
- Tag `v1.0`.
- Đóng gói zip (không kèm `.git` và `.claude`), PDF màn hình, PDF CSDL.
- Chạy toàn bộ TESTING_CHECKLIST trên bản đã tag.

---

## Thứ tự làm

```
Giai đoạn 1 (sửa lỗi):   F01, F02 (caychay) ‖ F03, F04, F05-phần-JS (Khôi) ‖ T13 (kajima, làm song song được vì toàn file mới)
Giai đoạn 2, đợt 1:       T10 (kajima, push trước) → T30, T31 (caychay) ‖ T24 (Khôi) ‖ T11 (kajima)
Giai đoạn 2, đợt 2:       T12 → T14 (kajima) ‖ T21, T23, F05-phần-SQL (Khôi) ‖ T32 (caychay)
Giai đoạn 2, đợt 3:       gỡ API deprecated (kajima) ‖ T34 (caychay) → T40
```

Từ khi T12 lên `main`, mọi người test qua XAMPP (`http://localhost/web-project/...`), không chỉ mở `file://`.

---

## Quy tắc làm việc trực tiếp trên `main`

### Trước khi bắt đầu và trước khi push
1. `git pull --rebase origin main`.
2. Chỉ stage file thuộc task của mình (`git add <file>`; không dùng `git add .`).
3. Chạy Smoke test (TESTING_CHECKLIST §1), test của module mình, và `php -l` với các file PHP đã sửa.
4. `git push origin main`. Nếu bị từ chối vì người khác vừa push thì quay lại bước 1.

### Commit
- Nhỏ, push thường xuyên (ít nhất 1 lần mỗi ngày làm việc). Không để thay đổi nằm ở máy nhiều ngày.
- Mẫu message: `fix(e2e): F02 sửa BASE sang html/`, `feat(api): T11 thêm require_login`.
- **Cấm:** `git push --force`, `git reset --hard` trên commit đã push, `git rebase` lại lịch sử đã push, commit file `.zip`, commit `config.local.php`.

### Khi rebase bị conflict
- Conflict trong file của mình: tự xử lý.
- Conflict trong file **của người khác**: dừng lại (`git rebase --abort`), nhắn chủ file. Không tự chọn "theirs/ours" trên file người khác.

### Sửa file dùng chung (`js/app.js`, `css/app.css`, `js/db.js`, `html/index.html`)
- Nhắn nhóm trước: "Đang sửa app.js – phần nav", rồi push trong vòng 1 giờ, sau đó báo "xong".
- Mỗi lần sửa ≤ 20 dòng, commit riêng với tiền tố `shared(...)`.

### Review (thay cho Pull Request)
- Thay đổi về schema, contract hoặc bảo mật: cho chủ module liên quan xem diff (`git diff --staged`) **trước khi push**.
- Cuối mỗi đợt: mỗi người đọc commit của người khác bằng `git log --since="7 days ago" --stat` và ghi nhận xét vào nhóm chat.
- Gắn tag cuối mỗi đợt (`dot-1`, `dot-2`, …) để có mốc quay lại.

### Khi `main` bị hỏng
- Người làm hỏng chạy `git revert <commit>` rồi push ngay, sau đó mới sửa.
- Không xóa lịch sử.

### Đổi contract hoặc schema
- Contract: sửa `docs/API_CONTRACT.md` trong một commit `contract:` riêng, đẩy lên **trước** code, và báo nhóm. Thêm trường mới thì được; đổi tên hoặc xóa trường phải giữ tên cũ qua ít nhất một đợt.
- Schema: chỉ kajima sửa. Mỗi thay đổi gồm `database/migrations/NNN_mo_ta.sql`, cập nhật `schema.sql`/`seed.sql` và `docs/DATABASE.md` trong **cùng commit**. Message ghi "⚠ cần chạy migration NNN".
