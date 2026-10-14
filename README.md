# Hình Sự Tra Cứu — demo giao diện tĩnh (HTML + CSS + JavaScript)

Không cần PHP, không cần cài đặt: **mở `html/login.html` bằng trình duyệt** (hoặc `python3 -m http.server` rồi vào http://localhost:8000/html/login.html).

## Tài khoản demo (nằm cứng trong mã nguồn)
| Tên đăng nhập | Mật khẩu |
|---|---|
| `demo` | `12345` |

Khai báo ở đầu `js/db.js` (`DEMO_USER`). Quy tắc theo yêu cầu:
- Tài khoản demo **không nằm trong database**, được `Object.freeze`, và mọi thao tác sửa tên/ảnh, đổi mật khẩu, xóa tài khoản đều bị từ chối (giao diện cũng khóa các nút này).
- **Đăng ký / xóa tài khoản chỉ thêm / bớt bản ghi trong database** (localStorage của trình duyệt). Xóa mọi tài khoản đã thêm bằng nút ở trang *Dữ liệu demo* cũng không ảnh hưởng tài khoản demo.
- Ghi chú và tùy chỉnh giao diện được lưu riêng theo từng người dùng, kể cả demo (đây là dữ liệu, không phải bản ghi tài khoản).

## Cấu trúc thư mục

```
web-project/

├── html/                     # Tất cả trang HTML
│   ├── index.html            # Trang chủ cá nhân + ghi chú
│   ├── login.html            # Đăng nhập
│   ├── register.html         # Đăng ký
│   ├── forgot-password.html  # Quên mật khẩu (OTP giả lập)
│   ├── tra-cuu.html          # Tra cứu điều luật
│   ├── dieu-luat-chi-tiet.html # Chi tiết 1 điều luật
│   ├── tu-van.html           # Tư vấn / chatbot
│   ├── van-ban-moi.html      # Văn bản pháp luật mới ← MỚI
│   ├── gioi-thieu.html       # Giới thiệu hệ thống
│   ├── lien-he.html          # Liên hệ / gửi câu hỏi
│   ├── ho-so.html            # Hồ sơ cá nhân
│   ├── doi-mat-khau.html     # Đổi mật khẩu
│   ├── tuy-chinh.html        # Tùy chỉnh giao diện
│   └── database.html         # Xem dữ liệu demo
├── css/
│   └── app.css               # Toàn bộ style (responsive, dark mode)
└── js/
    ├── db.js                 # Database localStorage + tài khoản demo
    ├── app.js                # Kiểm tra đăng nhập, header/footer, tiện ích form
    ├── auth.js               # Đăng nhập/đăng ký/quên mật khẩu
    ├── data.js               # Dữ liệu điều luật hình sự (LAW_DATA)
    ├── news-data.js          # Dữ liệu văn bản pháp luật mới (LAW_NEWS) ← MỚI
    ├── nlu.js                # TF-IDF + cosine similarity (dùng cho chọn khoản)
    ├── scorer.js             # Bộ chấm điểm chính: Khái niệm + BM25 + Gần đúng
    ├── law.js                # Tra cứu & chatbot logic
    ├── search.js             # Trang tra cứu (live search)
    ├── detail.js             # Trang chi tiết điều luật
    ├── chatbot.js            # Trang tư vấn chatbot
    ├── van-ban-moi.js        # Trang văn bản pháp luật mới ← MỚI
    ├── notes.js              # Ghi chú (trang chủ)
    ├── contact.js            # Form liên hệ
    └── account.js            # Hồ sơ, đổi mật khẩu, tùy chỉnh, database demo
```

## Màn hình
| Trang | File |
|---|---|
| Đăng nhập / Đăng ký / Quên mật khẩu (email + OTP giả lập ngay trên màn hình) | `login.html`, `register.html`, `forgot-password.html` |
| Trang chủ cá nhân + ghi chú (thêm/sửa/xóa/ghim/tìm) | `index.html`, `js/notes.js` |
| Tra cứu, chi tiết điều luật, chatbot tư vấn | `tra-cuu.html`, `dieu-luat-chi-tiet.html`, `tu-van.html` |
| **Văn bản pháp luật mới** (lọc theo loại, năm, tìm kiếm live) | `van-ban-moi.html`, `js/van-ban-moi.js` |
| Giới thiệu, Liên hệ | `gioi-thieu.html`, `lien-he.html` |
| Hồ sơ (tên + avatar, xóa tài khoản), đổi mật khẩu, tùy chỉnh (sáng/tối, cỡ chữ, màu ghi chú) | `ho-so.html`, `doi-mat-khau.html`, `tuy-chinh.html` |
| Xem "database" giả lập | `database.html` |

Chưa đăng nhập mở bất kỳ trang nào ngoài 3 trang xác thực đều bị chuyển về `login.html`. Đăng ký xong tự đăng nhập; tài khoản mới chưa kích hoạt vẫn dùng đủ chức năng nhưng có thanh vàng nổi bật ("Mở email kích hoạt" → hộp thư giả lập).

## File JavaScript
`db.js` database + tài khoản demo · `app.js` kiểm tra đăng nhập, header/footer, tiện ích form · `auth.js` đăng nhập/đăng ký/quên mật khẩu · `law.js` + `data.js` + `scorer.js` (chấm điểm khái niệm + BM25 + gần đúng) + `nlu.js` (TF-IDF, chỉ còn dùng chọn khoản dự phòng) tra cứu & chatbot · `news-data.js` + `van-ban-moi.js` trang văn bản mới · `notes.js`, `search.js`, `detail.js`, `chatbot.js`, `contact.js`, `account.js` theo từng trang.

## Cách chạy
1. Mở file `html/login.html` trực tiếp bằng trình duyệt, **hoặc**
2. Chạy server tĩnh: `python3 -m http.server 8000` rồi mở http://localhost:8000/html/login.html

## Lưu ý trung thực về bảo mật
Đây là demo giao diện: kiểm tra đăng nhập chạy ở trình duyệt nên **không an toàn thật** (ai mở DevTools cũng đọc được mật khẩu demo và dữ liệu localStorage). Mật khẩu tài khoản thêm vào được băm SHA-256 + salt chỉ để minh họa. Phiên bản có máy chủ thật (bcrypt, CSRF, email thật) nằm ở bản PHP riêng.

## Dữ liệu
Toàn bộ nội dung điều luật và văn bản pháp luật trong bản demo là **dữ liệu biên soạn rút gọn cho mục đích học tập**, không phải trích dẫn nguyên văn từ văn bản pháp luật chính thức và không thay thế tư vấn pháp lý của luật sư. Khi triển khai thật, cần đối chiếu và cập nhật theo văn bản pháp luật hiện hành tại Công Báo hoặc cơ sở QPPL quốc gia.
