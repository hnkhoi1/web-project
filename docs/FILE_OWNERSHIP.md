# FILE OWNERSHIP — Hình Sự Tra Cứu

> Nhóm commit thẳng lên `main` (không PR). Owner là người duy nhất được sửa tự do. Người khác muốn sửa phải nhắn owner, owner xem diff rồi mới push.
> **TV-A = kajima · TV-B = Khôi · TV-C = caychay0512-dev.** Hai nhánh `update-new-exten` và `feature/grid-list` không dùng.

## 1. Bảng sở hữu
| Đường dẫn | Owner | Ghi chú |
|---|---|---|
| `js/db.js` | TV-A | Facade dữ liệu — mọi trang phụ thuộc |
| `js/auth.js` | TV-A | Logic đăng nhập/đăng ký/quên mật khẩu |
| `js/app.js` | TV-A | **Dùng chung** — xem §2 |
| `backend/**` (trừ `backend/api/law.php`) | TV-A | |
| `database/**`, `docs/DATABASE.md` | TV-A | Schema chỉ TV-A sửa |
| `tests/db-test.js`, `tests/api/**` | TV-A | |
| `docs/API_CONTRACT.md`, `docs/ARCHITECTURE.md` | TV-A | Đổi theo quy trình contract |
| `data/dieu_luat_data.php`, `tools/export_law.php`, `database/seed_law.sql` (file sinh) | TV-B | Nguồn dữ liệu luật duy nhất |
| `.gitignore`, `index.html` (gốc) | TV-B | Task F03 |
| `js/data.js` | TV-B | **Sinh tự động**, không sửa tay |
| `js/nlu.js`, `js/scorer.js`, `js/law.js` | TV-B | |
| `js/search.js`, `js/detail.js`, `js/chatbot.js` | TV-B | |
| `html/tra-cuu.html`, `html/tu-van.html`, `html/dieu-luat-chi-tiet.html` | TV-B | |
| `backend/api/law.php` | TV-B | |
| `tests/scorer-test.js`, `tests/run-in-browser.html`, `tests/e2e/test_law.py` | TV-B | |
| `js/notes.js`, `js/account.js`, `js/contact.js`, `js/admin.js` | TV-C | |
| `html/index.html`, `html/login.html`, `html/register.html`, `html/forgot-password.html`, `html/ho-so.html`, `html/doi-mat-khau.html`, `html/tuy-chinh.html`, `html/lien-he.html`, `html/gioi-thieu.html`, `html/database.html`, `html/admin/**` | TV-C | Markup; logic auth vẫn ở `auth.js` (TV-A) |
| `css/app.css` | TV-C | **Dùng chung** — xem §2 |
| `tests/e2e/**` (khung, `conftest`, `run_all`) | TV-C | Mỗi owner tự thêm case trong file module của mình |
| `README.md` | TV-C | Task F01 |
| `docs/TASK_BOARD.md`, `docs/TESTING_CHECKLIST.md`, `docs/PROJECT_AUDIT.md` | Cả nhóm | Ai cũng cập nhật trạng thái task của mình |

## 2. Quy tắc cho file dùng chung (rủi ro conflict cao)

### `js/app.js` (owner TV-A)
- Chỉ 3 vùng được người khác đề xuất sửa: mảng `nav` trong `renderChrome()`, mảng `AUTH_PAGES`, thêm tiện ích vào `window.HS`.
- Mỗi thay đổi = 1 commit riêng ≤ 20 dòng, message `shared(app.js): …`. Nhắn nhóm trước khi sửa và push trong vòng 1 giờ.
- Không đổi chữ ký hàm có sẵn trong `HS` (chỉ thêm mới).

### `css/app.css` (owner TV-C)
- Chia vùng bằng comment đánh dấu: `/* == base == */`, `/* == layout == */`, `/* == auth == */`, `/* == notes == */`, `/* == law == */`, `/* == account == */`, `/* == admin == */`.
- Mỗi người **chỉ thêm/sửa trong vùng module mình**; style mới đặt cuối vùng. Biến màu (`:root`, theme) chỉ TV-C sửa.
- Nếu vùng của module > ~100 dòng → tách `css/<module>.css` (cùng owner module), thêm `<link>` vào trang của mình.

### `js/db.js` (owner TV-A)
- Trang **không** truy cập `localStorage['hs_demo_db_v1']` trực tiếp — luôn qua `DB.*`.
- Cần hàm mới → nhắn TV-A, TV-A cập nhật API_CONTRACT rồi thêm hàm; trong lúc chờ có thể viết hàm tạm trong file trang của mình kèm `// TODO(T10): chuyển vào DB`.

### `html/index.html` (owner TV-C)
- Khối "Điều luật nổi bật" (`#popular`) lấy dữ liệu do TV-B cung cấp; TV-B đổi cấu trúc dữ liệu thì báo TV-C.

### `js/data.js` ↔ `js/scorer.js`
- Thêm điều luật = sửa nguồn PHP + chạy export + thêm CONCEPTS + thêm test trong **cùng 1 commit** (đều thuộc TV-B).

### Tài liệu
- `README.md`: người khác gửi đoạn nội dung qua nhóm chat, TV-C đưa vào.
- `docs/TASK_BOARD.md`: chỉ sửa dòng task của mình (cột Trạng thái) để giảm conflict.

## 3. Tránh triển khai trùng lặp
- Tiện ích UI (toast, flash, fieldErr, esc, el…) **đã có trong `HS`** — không viết lại trong file trang.
- Validate theo bảng chung API_CONTRACT §1 — hàm `HS.passwordError`, `HS.nameError` là nguồn duy nhất phía client; PHP có hàm tương ứng trong `backend/lib/validate.php` (TV-A).
- Logic chọn điều/khoản chỉ ở `Law`/`Scorer` (TV-B); UI không tự chấm điểm.
