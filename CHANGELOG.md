# Nhật ký thay đổi

## Chưa phát hành

### Tính năng mới
- **Quản lý điều luật** trong trang quản trị (`/quan-tri/dieu-luat`): thêm, sửa, xóa điều luật cùng các khoản (điều kiện, mức % thương tật, giá trị tài sản, số người chết, quyền lợi, câu hỏi làm rõ). Thay đổi có hiệu lực ngay với trang tra cứu và chatbot. Gõ tên chương mới thì chương được tạo; chương không còn điều nào thì tự xóa.
- Số điều không đổi được sau khi tạo. Không xóa được điều mà chatbot đã dùng để trả lời (để lịch sử và thống kê không bị sai); hãy sửa nội dung thay vì xóa.

### Lưu ý
- Từ bản này, **CSDL là nguồn dữ liệu luật chính**. Chạy lại `db:seed --class=LawSeeder` sẽ đưa các điều có trong `dieu_luat_data.php` về đúng nội dung của file, ghi đè chỉnh sửa trên web. Điều thêm trên web (không có trong file) thì giữ nguyên.
- Nếu bật tìm kiếm ngữ nghĩa, chạy `php artisan search:embed` sau khi sửa để cập nhật vector.

## v1.1 (2026-11-14)

### Tính năng mới
- **Quên mật khẩu** bằng liên kết gửi qua email (hiệu lực 60 phút, dùng một lần). Không lộ email nào có tài khoản; tài khoản demo không đặt lại được. Dev đọc thư ở Mailpit (http://localhost:8025).
- **Trang quản trị** `/quan-tri` (chỉ admin): tổng quan kèm biểu đồ, thư liên hệ, câu hỏi chatbot (lọc câu không khớp và câu bị đánh giá sai), danh sách người dùng. Cấp quyền bằng `php artisan admin:promote`.
- **Lịch sử câu hỏi** `/lich-su`: xem lại, tìm, mở lại câu trả lời, xóa từng câu hoặc toàn bộ.
- **Đánh giá câu trả lời** của chatbot (Đúng / Chưa đúng), hiện ở trang quản trị để biết chatbot sai ở đâu.
- **Ghi chú**: chuyển dạng lưới hoặc danh sách (nhớ theo tài khoản), lọc theo ghim và màu, tải ra `.txt`, in. Cũng in được điều luật và câu trả lời đã lưu.
- **Giao diện đăng nhập** có hiệu ứng vào nhẹ nhàng (tắt khi người dùng bật "giảm chuyển động").
- **Chatbot**: lệnh `php artisan search:status` để kiểm tra, tài liệu `docs/CHATBOT.md`, chế độ trộn ngữ nghĩa là tùy chọn.

### Vận hành và ổn định
- Bản chạy thật **tự migrate khi khởi động** và nạp dữ liệu một lần khi CSDL trống.
- Kiểm tra sức khỏe cho `web` và `app`; các container tự khởi động lại.
- Chatbot giới hạn 20 câu hỏi mỗi phút cho mỗi người dùng.
- Trang lỗi thân thiện cho 403, 419, 429 và 500.
- Hộp thoại sửa ghi chú không còn nháy tiêu đề khi đóng.
- Tài liệu vận hành, sao lưu và khôi phục: `docs/OPERATIONS.md`.

### Nâng cấp từ v1.0
Chạy `git pull` rồi `docker compose -f compose.yaml up -d --build`; migration mới tự chạy và chỉ **thêm** bảng hoặc cột, không xóa dữ liệu cũ. Nên sao lưu CSDL trước (xem `docs/OPERATIONS.md`). Muốn gửi thư thật, điền `MAIL_*` trong `.env`.

## v1.0 (2026-11-05)
Bản Laravel + React chạy trên Docker: tra cứu điều luật, chatbot tư vấn, ghi chú, tài khoản, giao diện sáng/tối. Thay thế bản HTML/JS tĩnh (còn ở tag `pre-laravel`).
