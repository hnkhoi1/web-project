# Nhật ký thay đổi

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
