# Vận hành: chạy thật, sao lưu, nâng cấp

Tài liệu cho người triển khai bản chạy thật (`compose.yaml`, không có file override dev). Mọi lệnh chạy từ thư mục gốc của repo.
Trong ví dụ dưới, viết tắt `dc` = `docker compose -f compose.yaml`.

## 1. Cài lần đầu

```bash
git clone https://github.com/hnkhoi1/web-project.git && cd web-project
cp .env.example .env        # rồi sửa các giá trị ở mục 2
docker compose -f compose.yaml up -d --build
```

Không cần chạy `migrate` hay `seed`: container `app` **tự migrate mỗi lần khởi động** và nạp dữ liệu luật cùng tài khoản demo **một lần** khi CSDL còn trống. Đợi `docker compose -f compose.yaml ps` thấy `web`, `app`, `db` đều `healthy` (lần đầu build có thể mất vài phút đến vài chục phút).

Muốn tự kiểm soát việc nạp dữ liệu, đặt trong `.env`:
- `AUTO_MIGRATE=false`: không tự migrate (tự chạy `dc exec app php artisan migrate --force`).
- `AUTO_SEED=false`: không tự nạp dữ liệu (tự chạy `dc exec app php artisan db:seed --force`).

## 2. Cấu hình bắt buộc sửa trong `.env`

| Biến | Giá trị cho bản chạy thật |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (nếu để `true` người dùng sẽ thấy chi tiết lỗi) |
| `APP_URL` | địa chỉ người dùng truy cập, ví dụ `http://may-chu:8080` |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | mật khẩu mạnh, **đặt trước lần chạy đầu tiên** (đổi sau thì xem mục 6) |
| `WEB_PORT` | cổng mở ra ngoài, mặc định 8080 |
| `MAIL_*` | tài khoản SMTP để gửi thư quên mật khẩu (xem README) |
| `ADMIN_EMAIL` | tùy chọn: email tài khoản sẽ được cấp quyền admin bởi `AdminSeeder` |

`.env` không được commit. Giữ một bản sao an toàn ở nơi khác.

## 3. Tài khoản quản trị đầu tiên

1. Mở web, đăng ký một tài khoản bình thường.
2. Cấp quyền: `dc exec app php artisan admin:promote email@cua-ban.com`.

Thu hồi: thêm `--revoke`. Tài khoản `demo` luôn bị từ chối.

## 4. Tự khởi động lại và kiểm tra sức khỏe

- `web`, `app`, `db` có `restart: unless-stopped`: chúng tự chạy lại khi Docker hoặc máy khởi động lại, trừ khi bạn chủ động `docker compose stop`.
- `web` kiểm tra sức khỏe qua `/up` (đi qua nginx, PHP và Laravel); `app` kiểm tra cổng PHP-FPM; `db` kiểm tra bằng `mysqladmin ping`.
- Xem trạng thái: `dc ps`. Xem log: `dc logs -f app` (hoặc `web`, `db`).
- Lỗi ứng dụng nằm trong `dc logs app` và file `storage/logs/laravel.log` trong container `app`.

## 5. Sao lưu và khôi phục CSDL

Mọi dữ liệu (tài khoản, ghi chú, lịch sử, thư liên hệ) nằm trong volume `db_data`. **Hãy sao lưu định kỳ.**

### Sao lưu
```bash
dc exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines "$MYSQL_DATABASE" > /tmp/backup.sql'
dc cp db:/tmp/backup.sql ./backup-$(date +%Y%m%d).sql
dc exec -T db sh -c 'rm /tmp/backup.sql'
```
(Trên PowerShell thay `$(date +%Y%m%d)` bằng tên file cụ thể, ví dụ `backup-20261114.sql`.) Cách này dump ngay trong container rồi copy ra, tránh việc PowerShell đổi mã hóa khi dùng `>`, và dùng `sh -c '...'` để Git Bash trên Windows không đổi đường dẫn `/tmp`. Các lệnh sao lưu và khôi phục này đã được thử thật: xóa một ghi chú, khôi phục, ghi chú trở lại.

Lưu file `.sql` ra ngoài máy chủ. Có thể đặt lệnh trên vào cron (Linux) hoặc Task Scheduler (Windows) chạy mỗi đêm.

### Khôi phục
**Cảnh báo:** việc này ghi đè dữ liệu hiện tại.
```bash
dc cp ./backup-20261114.sql db:/tmp/restore.sql
dc exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /tmp/restore.sql'
dc exec -T db sh -c 'rm /tmp/restore.sql'
```
Nên thử khôi phục trên một máy khác trước khi cần dùng thật.

## 6. Nâng cấp lên phiên bản mới

```bash
# 1. Sao lưu CSDL (mục 5)
git pull
docker compose -f compose.yaml up -d --build
```
Container `app` tự chạy migration mới khi khởi động. Các migration của dự án chỉ **thêm** cột hoặc bảng, không xóa dữ liệu cũ. Nếu có sự cố, quay về phiên bản trước bằng `git checkout <tag cũ>` rồi `up -d --build` lại, và khôi phục bản sao lưu nếu cần.

Đổi mật khẩu CSDL sau khi đã chạy: MySQL chỉ đọc mật khẩu lần khởi tạo đầu tiên. Cách dễ nhất là sao lưu, `dc down -v` (xóa volume), đổi `.env`, `up -d --build`, rồi khôi phục bản sao lưu.

## 7. Bảo mật tối thiểu

- Chạy sau một reverse proxy có HTTPS; đặt `TRUSTED_PROXIES` và `APP_URL=https://...`.
- Không mở cổng 3306 ra ngoài (bản `compose.yaml` không mở; chỉ file override dev mới mở và chỉ ở `127.0.0.1`).
- Đổi hoặc vô hiệu tài khoản `demo` nếu máy chủ mở công khai: mật khẩu của nó nằm trong mã nguồn.
- Admin xem được câu hỏi người dùng đã gõ vào chatbot. Chỉ dùng để cải thiện dịch vụ.
- Chatbot giới hạn 20 câu hỏi mỗi phút cho mỗi người dùng.
