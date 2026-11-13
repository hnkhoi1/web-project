# Hình Sự Tra Cứu

Ứng dụng web tra cứu và tư vấn **Luật Hình sự Việt Nam** bằng ngôn ngữ đời thường: người dùng mô tả tình huống, hệ thống trả về điều luật, mức xử lý và quyền lợi bồi thường của người bị hại. Có thêm ghi chú cá nhân, tài khoản và tùy chỉnh giao diện (sáng/tối, cỡ chữ, màu ghi chú).

Dự án là monorepo chạy hoàn toàn bằng **Docker Compose**, không cần cài PHP, Node hay MySQL trên máy.

## 1. Cấu trúc

```
.
├── app/
│   ├── api/        Laravel 13 + Inertia + React 19 (giao diện chính, API, CSDL)
│   └── embedder/   dịch vụ Python (FastAPI) tạo vector cho tìm kiếm ngữ nghĩa (tùy chọn)
├── docker/mysql/   script tạo CSDL test
├── docs/           tài liệu (CSDL, thiết kế, kế hoạch)
├── compose.yaml            cấu hình chạy thật (production)
└── compose.override.yaml   cấu hình dev, tự được nạp khi chạy từ thư mục gốc
```

Stack: PHP 8.5 (FPM) · MySQL 8.4 · nginx · Node 26 + pnpm 12 · Python 3.14. Tất cả image dựa trên Debian 13 (trixie).

## 2. Chạy lần đầu (dev)

Yêu cầu: Docker Desktop (hoặc Docker Engine + Compose), khoảng 10 GB trống.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Compose **không tự chạy migration**, nên lệnh `migrate --seed` ở trên là bắt buộc ở lần đầu. Lệnh nạp 18 điều luật và tạo tài khoản demo. `APP_KEY` được tự sinh và lưu trong volume `app_key`.

| Địa chỉ | Là gì |
|---|---|
| http://localhost:8080 | Ứng dụng chính (nginx → Laravel) |
| http://localhost:5173 | Vite dev server cho `app/api` (HMR, không có trang gốc) |
| http://localhost:8025 | Mailpit: hộp thư giả của chế độ dev, xem thư đặt lại mật khẩu ở đây |
| `127.0.0.1:3306` | MySQL (chỉ mở ở chế độ dev) |

Đổi cổng web bằng `WEB_PORT` trong `.env`.

### Tài khoản demo
Tài khoản `demo` do `DemoUserSeeder` tạo (mật khẩu xem trong file seeder). Đây là tài khoản dùng chung, bị khóa: không sửa hồ sơ, ảnh, mật khẩu. Tài khoản tự đăng ký dùng đầy đủ chức năng.

## 3. Chức năng

Chưa đăng nhập thì chỉ vào được đăng nhập, đăng ký, giới thiệu và liên hệ.

| Trang | Đường dẫn | Chức năng |
|---|---|---|
| Đăng nhập / đăng ký | `/login`, `/register` | Đăng nhập bằng tên đăng nhập hoặc email; khóa tạm sau 5 lần sai |
| Quên mật khẩu | `/quen-mat-khau`, `/dat-lai-mat-khau/{token}` | Gửi liên kết đặt lại qua email (hiệu lực 60 phút, dùng một lần); tài khoản demo không đặt lại được |
| Ghi chú (trang chủ) | `/ghi-chu` | Thêm, sửa, xóa có xác nhận, ghim, tìm tức thời, chọn màu; lọc theo ghim và màu; chuyển dạng lưới hoặc danh sách (được nhớ theo tài khoản); tải ghi chú ra `.txt`; in; điều luật nổi bật |
| Tra cứu | `/tra-cuu` | Tìm theo từ khóa, số điều, chương (`?q=&chuong=`) |
| Chi tiết điều luật | `/dieu-luat/{id}` | Các khoản, quyền lợi người bị hại, lưu vào ghi chú, in điều luật |
| Tư vấn | `/tu-van` | Chatbot chấm điểm tình huống; mỗi câu hỏi ghi vào `lich_su_hoi_dap`; có nút "Đúng / Chưa đúng" để đánh giá câu trả lời |
| Lịch sử câu hỏi | `/lich-su` | Xem lại, tìm, mở lại câu trả lời, đánh giá và xóa các câu mình đã hỏi (chỉ thấy của mình) |
| Tài khoản | `/ho-so`, `/doi-mat-khau`, `/tuy-chinh` | Hồ sơ và ảnh, đổi mật khẩu, sáng/tối, cỡ chữ, màu ghi chú |
| Khác | `/gioi-thieu`, `/lien-he` | Giới thiệu, gửi liên hệ |
| Quản trị (chỉ admin) | `/quan-tri` | Tổng quan và biểu đồ câu hỏi, xử lý thư liên hệ, câu hỏi chatbot (lọc câu không khớp và câu bị đánh giá sai), danh sách người dùng (chỉ xem) |

Không có: xác thực email.

### Chatbot
Chatbot chạy ngay sau `migrate --seed`, không cần khóa API hay dịch vụ ngoài. Kiểm tra bằng:

```bash
docker compose exec app php artisan search:status
```

Có thêm chế độ trộn ngữ nghĩa (tùy chọn, tốn thêm RAM và dung lượng, cải thiện không lớn): xem [docs/CHATBOT.md](docs/CHATBOT.md) để biết khi nào nên bật, các bước cài và cách xử lý sự cố.

## 4. Kiểm thử

```bash
# API (Pest/PHPUnit)
docker compose exec app php artisan test

# Giao diện: kiểu và lint
docker compose exec vite pnpm types:check
docker compose exec vite pnpm check

# Dịch vụ embedder (chỉ khi bật chế độ ngữ nghĩa)
docker compose run --rm --build embedder-test
```

Test API dùng CSDL riêng (tạo bởi `docker/mysql/create-test-db.sql`), không đụng dữ liệu dev.

### Tạo tài khoản admin
Trang quản trị chỉ mở cho tài khoản có vai trò admin; người khác nhận trang 404. Không có trang nào cho người dùng tự thành admin. Cách làm:
1. Đăng ký một tài khoản bình thường ở `/register`.
2. Cấp quyền bằng lệnh (email hoặc tên đăng nhập):
   ```bash
   docker compose exec app php artisan admin:promote email@cua-ban.com
   ```
   Thu hồi: thêm `--revoke`. Tài khoản demo không thể làm admin.
3. Hoặc đặt `ADMIN_EMAIL=email@cua-ban.com` trong `.env` rồi chạy `docker compose exec app php artisan db:seed --class=AdminSeeder`.

Admin xem được câu hỏi người dùng đã gõ vào chatbot, nên hãy dùng dữ liệu này chỉ để cải thiện dịch vụ.

### Gửi email (quên mật khẩu)
- **Dev:** thư không gửi đi đâu cả, mọi thư nằm trong Mailpit (http://localhost:8025). Không cần cấu hình.
- **Chạy thật:** điền tài khoản SMTP vào `.env`. Ví dụ Gmail (bật xác minh 2 bước, tạo "Mật khẩu ứng dụng" 16 ký tự, **không** dùng mật khẩu đăng nhập Gmail):
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USERNAME=<địa chỉ gmail>
  MAIL_PASSWORD=<mật khẩu ứng dụng>
  MAIL_FROM_ADDRESS=<địa chỉ gmail>
  ```
  `.env` không được commit. Gmail giới hạn khoảng 500 thư mỗi ngày và thư có thể vào mục Spam.

## 5. Chạy thật (production)

```bash
cp .env.example .env     # sửa APP_ENV=production, APP_DEBUG=false, APP_URL, mật khẩu DB, MAIL_*
docker compose -f compose.yaml up -d --build
```

Dùng `-f compose.yaml` để bỏ qua file override dev. Bản chạy thật **tự migrate khi khởi động** và nạp dữ liệu luật cùng tài khoản demo một lần khi CSDL còn trống, nên không cần chạy `migrate` hay `seed` tay. Các container tự khởi động lại cùng Docker và có kiểm tra sức khỏe. nginx phục vụ HTTP thuần ở `WEB_PORT`; HTTPS đặt proxy phía trước và chỉnh `TRUSTED_PROXIES`, `APP_URL` trong `.env`.

Sao lưu CSDL, khôi phục, nâng cấp và các việc cần làm trước khi mở ra ngoài: xem [docs/OPERATIONS.md](docs/OPERATIONS.md).

## 6. Lưu ý khi phát triển

- Trên Windows, file bind-mount có thể không báo thay đổi cho Vite. Nếu giao diện không cập nhật: `docker compose restart vite`.
- Luôn chạy `docker compose` từ thư mục gốc. Dùng `-f` sẽ bỏ qua `compose.override.yaml`.
- Dữ liệu luật nằm ở `app/api/database/data/dieu_luat_data.php` và được nạp bằng `LawSeeder`.
- Thiết kế CSDL: [docs/DATABASE.md](docs/DATABASE.md).
