# Hình Sự Tra Cứu

Ứng dụng web tra cứu và tư vấn **Luật Hình sự Việt Nam** bằng ngôn ngữ đời thường: người dùng mô tả tình huống, hệ thống trả về điều luật, mức xử lý và quyền lợi bồi thường của người bị hại. Có thêm ghi chú cá nhân, tài khoản và tùy chỉnh giao diện (sáng/tối, cỡ chữ, màu ghi chú).

Dự án là monorepo chạy hoàn toàn bằng **Docker Compose**, không cần cài PHP, Node hay MySQL trên máy.

## 1. Cấu trúc

```
.
├── app/
│   ├── api/        Laravel 13 + Inertia + React 19 (giao diện chính, API, CSDL)
│   ├── embedder/   dịch vụ Python (FastAPI) tạo vector cho tìm kiếm ngữ nghĩa (tùy chọn)
│   └── web/        khung Vite + React + TypeScript strict (chưa gọi API)
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
| http://localhost:3000 | `app/web` (khung React riêng) |
| `127.0.0.1:3306` | MySQL (chỉ mở ở chế độ dev) |

Đổi cổng bằng `WEB_PORT`, `FRONTEND_PORT` trong `.env`.

### Tài khoản demo
Tài khoản `demo` do `DemoUserSeeder` tạo (mật khẩu xem trong file seeder). Đây là tài khoản dùng chung, bị khóa: không sửa hồ sơ, ảnh, mật khẩu. Tài khoản tự đăng ký dùng đầy đủ chức năng.

## 3. Chức năng

Chưa đăng nhập thì chỉ vào được đăng nhập, đăng ký, giới thiệu và liên hệ.

| Trang | Đường dẫn | Chức năng |
|---|---|---|
| Đăng nhập / đăng ký | `/login`, `/register` | Đăng nhập bằng tên đăng nhập hoặc email; khóa tạm sau 5 lần sai |
| Ghi chú (trang chủ) | `/ghi-chu` | Thêm, sửa, xóa có xác nhận, ghim, tìm tức thời, chọn màu; điều luật nổi bật |
| Tra cứu | `/tra-cuu` | Tìm theo từ khóa, số điều, chương (`?q=&chuong=`) |
| Chi tiết điều luật | `/dieu-luat/{id}` | Các khoản, quyền lợi người bị hại, lưu vào ghi chú |
| Tư vấn | `/tu-van` | Chatbot chấm điểm tình huống; mỗi câu hỏi ghi vào `lich_su_hoi_dap` |
| Tài khoản | `/ho-so`, `/doi-mat-khau`, `/tuy-chinh` | Hồ sơ và ảnh, đổi mật khẩu, sáng/tối, cỡ chữ, màu ghi chú |
| Khác | `/gioi-thieu`, `/lien-he` | Giới thiệu, gửi liên hệ |

Không có: quên mật khẩu, xác thực email, trang quản trị (đã loại khỏi phạm vi, xem `docs/superpowers/specs/`).

### Tìm kiếm ngữ nghĩa (tùy chọn)
Mặc định tìm kiếm dùng thuật toán từ khóa. Để trộn thêm vector ngữ nghĩa, đặt `SEARCH_BLEND_ENABLED=true` trong `.env` rồi chạy:

```bash
docker compose --profile semantic up -d --build
```

Dịch vụ `embedder` cần khoảng 1,5 GB RAM và tải model lúc build image.

## 4. Kiểm thử

```bash
# API (Pest/PHPUnit)
docker compose exec app php artisan test

# Giao diện: kiểu và lint
docker compose exec vite pnpm types:check
docker compose exec vite pnpm check

# Dịch vụ embedder
docker compose run --rm --build embedder-test
```

Test API dùng CSDL riêng (tạo bởi `docker/mysql/create-test-db.sql`), không đụng dữ liệu dev.

## 5. Chạy thật (production)

```bash
cp .env.example .env     # sửa APP_ENV=production, APP_DEBUG=false, APP_URL, mật khẩu DB
docker compose -f compose.yaml up -d --build
docker compose -f compose.yaml exec app php artisan migrate --force --seed
```

Dùng `-f compose.yaml` để bỏ qua file override dev. nginx phục vụ HTTP thuần ở `WEB_PORT`; HTTPS đặt proxy phía trước và chỉnh `TRUSTED_PROXIES`, `APP_URL` trong `.env`.

## 6. Lưu ý khi phát triển

- Trên Windows, file bind-mount có thể không báo thay đổi cho Vite. Nếu giao diện không cập nhật: `docker compose restart vite`.
- Luôn chạy `docker compose` từ thư mục gốc. Dùng `-f` sẽ bỏ qua `compose.override.yaml`.
- Dữ liệu luật nằm ở `app/api/database/data/dieu_luat_data.php` và được nạp bằng `LawSeeder`.
- Thiết kế CSDL: [docs/DATABASE.md](docs/DATABASE.md).
