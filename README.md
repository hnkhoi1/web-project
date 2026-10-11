<<<<<<< HEAD
# Hình Sự Tra Cứu — demo giao diện tĩnh (HTML + CSS + JavaScript)

Không cần PHP, không cần cài đặt: **mở `login.html` bằng trình duyệt** (hoặc `python3 -m http.server` rồi vào http://localhost:8000/login.html).

## Tài khoản demo (nằm cứng trong mã nguồn)
| Tên đăng nhập | Mật khẩu |
|---|---|
| `demo` | `12345` |

Khai báo ở đầu `js/db.js` (`DEMO_USER`). Quy tắc theo yêu cầu:
- Tài khoản demo **không nằm trong database**, được `Object.freeze`, và mọi thao tác sửa tên/ảnh, đổi mật khẩu, xóa tài khoản đều bị từ chối (giao diện cũng khóa các nút này).
- **Đăng ký / xóa tài khoản chỉ thêm / bớt bản ghi trong database** (localStorage của trình duyệt). Xóa mọi tài khoản đã thêm bằng nút ở trang *Dữ liệu demo* cũng không ảnh hưởng tài khoản demo.
- Ghi chú và tùy chỉnh giao diện được lưu riêng theo từng người dùng, kể cả demo (đây là dữ liệu, không phải bản ghi tài khoản).

## Màn hình
| Trang | File |
|---|---|
| Đăng nhập / Đăng ký / Quên mật khẩu (email + OTP giả lập ngay trên màn hình) | `login.html`, `register.html`, `forgot-password.html` |
| Trang chủ cá nhân + ghi chú (thêm/sửa/xóa/ghim/tìm) | `index.html`, `js/notes.js` |
| Tra cứu, chi tiết điều luật, chatbot tư vấn | `tra-cuu.html`, `dieu-luat-chi-tiet.html`, `tu-van.html` |
| Giới thiệu, Liên hệ | `gioi-thieu.html`, `lien-he.html` |
| Hồ sơ (tên + avatar, xóa tài khoản), đổi mật khẩu, tùy chỉnh (sáng/tối, cỡ chữ, màu ghi chú) | `ho-so.html`, `doi-mat-khau.html`, `tuy-chinh.html` |
| Xem "database" giả lập | `database.html` |

Chưa đăng nhập mở bất kỳ trang nào ngoài 3 trang xác thực đều bị chuyển về `login.html`. Đăng ký xong tự đăng nhập; tài khoản mới chưa kích hoạt vẫn dùng đủ chức năng nhưng có thanh vàng nổi bật ("Mở email kích hoạt" → hộp thư giả lập).

## File JavaScript
`db.js` database + tài khoản demo · `app.js` kiểm tra đăng nhập, header/footer, tiện ích form · `auth.js` đăng nhập/đăng ký/quên mật khẩu · `law.js` + `data.js` + `scorer.js` (chấm điểm khái niệm + BM25 + gần đúng) + `nlu.js` (TF-IDF, chỉ còn dùng chọn khoản dự phòng) tra cứu & chatbot · `notes.js`, `search.js`, `detail.js`, `chatbot.js`, `contact.js`, `account.js` theo từng trang.

## Lưu ý trung thực về bảo mật
Đây là demo giao diện: kiểm tra đăng nhập chạy ở trình duyệt nên **không an toàn thật** (ai mở DevTools cũng đọc được mật khẩu demo và dữ liệu localStorage). Mật khẩu tài khoản thêm vào được băm SHA-256 + salt chỉ để minh họa. Phiên bản có máy chủ thật (bcrypt, CSRF, email thật) nằm ở bản PHP riêng.
=======
# Hình Sự Tra Cứu — Demo web tra cứu & tư vấn Luật Hình sự Việt Nam

Đồ án minh họa: hệ thống tra cứu điều luật hình sự theo hướng **chatbot**, hiểu câu hỏi
bằng ngôn ngữ tự nhiên (không cần gõ đúng số điều/thuật ngữ pháp lý), BackEnd &
cơ sở dữ liệu dùng **PHP thuần + MySQL** (không dùng Streamlit).

## Cấu trúc thư mục

```
legal-demo/
├── frontend/                 # FrontEnd tĩnh — mở trực tiếp được, không cần server
│   ├── index.html            # 1. Trang chủ
│   ├── tra-cuu.html          # 2. Tra cứu điều luật (tìm kiếm ngữ nghĩa)
│   ├── dieu-luat-chi-tiet.html # 3. Chi tiết 1 điều luật
│   ├── tu-van.html           # 4. Tư vấn / chatbot
│   ├── gioi-thieu.html       # 5. Giới thiệu hệ thống
│   ├── lien-he.html          # 6. Liên hệ / gửi câu hỏi
│   ├── css/style.css
│   └── js/
│       ├── data.js           # dữ liệu mẫu dùng khi chạy demo tĩnh (không có PHP)
│       ├── nlu.js            # TF-IDF + cosine similarity — bản JS (fallback)
│       ├── search.js / chatbot.js / main.js
├── backend/                  # BackEnd PHP — các API JSON + màn hình quản trị (menu chức năng)
│   ├── config.php            # kết nối MySQL (PDO)
│   ├── nlu/TfidfMatcher.php  # TF-IDF + cosine similarity — bản PHP (dùng thật khi có server)
│   └── api/
│       ├── search.php        # GET  ?q=&chuong=   -> tra cứu
│       ├── chatbot.php       # POST {question}     -> hỏi đáp chatbot
│       ├── dieu_luat.php     # GET  ?id=            -> chi tiết điều luật
│       └── lien_he.php       # POST {...}           -> lưu câu hỏi liên hệ
└── database/
    └── schema.sql            # thiết kế CSDL + dữ liệu mẫu (chạy để tạo DB luat_hinh_su)
```

## Cách chạy demo

**Cách 1 — chỉ xem giao diện (không cần PHP):** mở thẳng `frontend/index.html` bằng
trình duyệt. Trang Tra cứu và Tư vấn tự phát hiện không có server PHP và chuyển sang xử
lý NLU (TF-IDF + cosine similarity) ngay trên trình duyệt bằng `frontend/js/nlu.js` +
`frontend/js/data.js`, nên vẫn hoạt động đầy đủ.

**Cách 2 — chạy đủ FrontEnd + BackEnd PHP + MySQL (khuyến nghị để nộp bài):**
1. Cài XAMPP/LAMPP (PHP 8 + MySQL/MariaDB), copy cả thư mục `legal-demo` vào `htdocs/`.
2. Tạo CSDL: mở phpMyAdmin hoặc `mysql -u root -p < database/schema.sql`.
3. Sửa `backend/config.php` nếu tài khoản MySQL khác mặc định.
4. Truy cập `http://localhost/legal-demo/frontend/index.html` — lúc này Tra cứu và
   Tư vấn sẽ gọi thật `backend/api/search.php` và `backend/api/chatbot.php`.

## Thiết kế cơ sở dữ liệu (sơ bộ)

`chuong (1‑n) dieu_luat (1‑n) khoan`, cùng `lich_su_hoi_dap` (log câu hỏi chatbot +
điều luật khớp + điểm tương đồng, phục vụ đánh giá/cải thiện NLU sau này), `lien_he`
(câu hỏi gửi từ trang Liên hệ) và `quan_tri_vien` (tài khoản cho màn hình quản trị).
Chi tiết cột xem `database/schema.sql`.

## Menu chức năng BackEnd

| Chức năng | Endpoint | Mô tả |
|---|---|---|
| Tra cứu điều luật | `GET /backend/api/search.php` | Lọc theo chương + xếp hạng theo độ khớp ngữ nghĩa |
| Chi tiết điều luật | `GET /backend/api/dieu_luat.php` | Trả về 1 điều luật kèm các khoản |
| Tư vấn chatbot | `POST /backend/api/chatbot.php` | Nhận câu hỏi, trả lời tư vấn + trích dẫn điều luật, có ghi log |
| Liên hệ | `POST /backend/api/lien_he.php` | Lưu câu hỏi người dùng gửi vào CSDL |

Có thể mở rộng thêm màn hình quản trị CRUD điều luật (thêm/sửa/xoá `dieu_luat`,
`khoan`) dùng chung `config.php` — đã có bảng `quan_tri_vien` sẵn cho việc đăng nhập.

## Về phần "tự hiểu câu hỏi" (NLU)

Yêu cầu là hệ thống hiểu câu hỏi qua **sentence-transformers hoặc mô hình nhẹ hơn**.
Vì BackEnd chính dùng PHP (không có sentence-transformers gốc), bản demo cài **TF-IDF +
cosine similarity** — nhẹ, chạy thuần PHP/JS, không cần mô hình tải về hay GPU, và đã đủ
để so khớp câu hỏi tiếng Việt với điều luật gần nghĩa nhất trong tập dữ liệu nhỏ.

### Hướng nâng cấp lên sentence-transformers thật

Khi cần độ chính xác ngữ nghĩa cao hơn (đồng nghĩa xa, câu hỏi phức tạp), có thể thêm
một dịch vụ Python nhỏ chạy song song với PHP:

1. Dịch vụ Python (FastAPI/Flask) dùng mô hình embedding đa ngôn ngữ **nhẹ**, ví dụ
   `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2` (~470MB) hoặc các
   biến thể nhỏ hơn nếu cần tốc độ/tài nguyên thấp hơn nữa.
2. Precompute: tính sẵn vector embedding cho từng `dieu_luat` (offline), lưu vào cột
   `embedding` (JSON) trong bảng `dieu_luat` hoặc một bảng riêng.
3. `backend/api/chatbot.php` gọi `POST http://localhost:8000/embed` (dịch vụ Python)
   để lấy vector câu hỏi, sau đó vẫn dùng `TfidfMatcher::cosine()` (đổi tên thành
   `cosine()` dùng chung) để so khớp — chỉ thay bước *vectorize*, giữ nguyên phần còn lại.
4. Có thể cache embedding câu hỏi thường gặp trong `lich_su_hoi_dap` để giảm độ trễ.

Kiến trúc lai này giữ được yêu cầu "database dùng PHP" trong khi vẫn dùng được mô hình
ngữ nghĩa thật khi cần, và bản TF-IDF hiện tại đã là "mô hình tương đương nhưng nhẹ hơn"
để nộp trước trong giai đoạn demo.

## Lưu ý

Toàn bộ nội dung điều luật trong bản demo là **dữ liệu biên soạn rút gọn cho mục đích
học tập**, không phải trích dẫn nguyên văn Bộ luật Hình sự và không thay thế tư vấn
pháp lý chính thức. Khi triển khai thật, cần đối chiếu và cập nhật theo văn bản pháp
luật hiện hành.
>>>>>>> 43bf1fe6352d41f89ff06dcb2b9a0dbbed023e03
