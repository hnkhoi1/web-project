# Chatbot: cách hoạt động và cách cài đặt

Chatbot ở `/tu-van` trả lời một tình huống bằng điều luật, mức xử lý và quyền lợi của người bị hại.
Nó chạy **hoàn toàn trong ứng dụng**: không cần khóa API, không gọi AI bên ngoài, không cần Internet.

## 1. Chế độ mặc định (từ khóa): không cần cài gì thêm

Sau khi chạy `docker compose up -d --build` và `docker compose exec app php artisan migrate --seed`, chatbot đã chạy.
Nó chấm điểm câu hỏi theo từ khóa, khái niệm, số tiền và tỉ lệ thương tật trên 18 điều luật trong CSDL.

Kiểm tra nhanh:

```bash
docker compose exec app php artisan search:status
```

Kết quả mong đợi: "Dữ liệu luật: 18 điều, 61 khoản" và "Chế độ từ khóa luôn sẵn sàng". Nếu báo chưa có dữ liệu thì chạy `php artisan migrate --seed`.

## 2. Chế độ trộn ngữ nghĩa (tùy chọn)

Thêm một mô hình ngôn ngữ nhỏ (`intfloat/multilingual-e5-small`, chạy trên CPU) để hiểu những câu diễn đạt khác từ khóa. Điểm từ khóa (60%) được trộn với độ giống về nghĩa (40%).

### Khi nào nên bật
Đo trên bộ 217 câu hỏi đánh giá của dự án (`php artisan search:eval`):

| Chế độ | Chọn đúng điều ở vị trí 1 | Có trong 3 kết quả đầu |
|---|---|---|
| Từ khóa (mặc định) | 42,9% | 59,4% |
| Chỉ ngữ nghĩa (không dùng trong web) | 29,5% | 51,2% |
| **Trộn** | **44,7%** | **64,5%** |

Chế độ trộn tốt hơn một chút, không phải vượt trội. Lợi ích rõ nhất là **bớt trả lời sai một cách tự tin**. Ví dụ:

- "Có người giật mất cái điện thoại của tôi giữa đường": từ khóa chắc chắn trả Điều 123 (giết người), trộn không chọn bừa.
- "Bị bạn bè mượn tiền rồi bặt tin không trả": từ khóa không tìm ra gì, trộn gợi ý Điều 175 (lạm dụng tín nhiệm).

(Các con số trên gồm cả nhóm câu hỏi "ngoài phạm vi" mà đáp án đúng là không có điều nào, nên cả ba chế độ đều thấp.)

Với demo thông thường, chế độ từ khóa là đủ. Chỉ bật khi cần.

### Chi phí
- Tải thêm vài trăm MB đến hơn 1 GB lúc build image (PyTorch bản CPU và model).
- Khoảng 1,5 GB RAM cho container `embedder`.
- Máy cần còn dung lượng trống (ổ đầy sẽ làm Docker lỗi).

### Các bước bật

1. Sửa `.env`:
   ```
   SEARCH_BLEND_ENABLED=true
   ```
2. Build và chạy dịch vụ `embedder` (profile `semantic`):
   ```bash
   docker compose --profile semantic up -d --build
   ```
   Ở bản chạy thật, thêm `-f compose.yaml`. Đợi `docker compose ps` thấy `embedder` ở trạng thái `healthy`.
3. Tạo vector cho các điều luật (một lần, và chạy lại sau khi sửa nội dung luật; chỉ tính lại dòng nào đổi):
   ```bash
   docker compose exec app php artisan search:embed
   ```
4. Kiểm tra:
   ```bash
   docker compose exec app php artisan search:status
   ```
   Cần thấy "Chế độ: BẬT", "Embedder: chạy", "Vector: 79/79" và "Chế độ trộn sẵn sàng".

Tắt lại: đặt `SEARCH_BLEND_ENABLED=false`. Có thể dừng container `embedder` (`docker compose stop embedder`).

### Nếu embedder gặp sự cố
Chatbot **không bị hỏng**. Khi embedder không trả lời kịp (giới hạn 1,5 giây) hoặc thiếu vector, hệ thống tự dùng chấm điểm từ khóa. `search:status` cho biết lý do.

## 3. Xử lý sự cố

| Hiện tượng | Nguyên nhân và cách xử lý |
|---|---|
| Chatbot nói chưa có dữ liệu | Chưa nạp luật: `docker compose exec app php artisan migrate --seed` |
| `search:status` báo "Embedder: không phản hồi" | Chưa chạy `--profile semantic`, hoặc đang khởi động (mất tới 1 phút), hoặc container bị dừng: `docker compose --profile semantic up -d` |
| "Thiếu vector" | Chạy `php artisan search:embed` |
| Container `embedder` bị tắt đột ngột | Thiếu RAM (giới hạn 1,5 GB) hoặc Docker hết dung lượng. Kiểm tra `docker compose logs embedder` |
| Bật `SEARCH_BLEND_ENABLED=true` mà kết quả không đổi | Chưa chạy lại ứng dụng đọc `.env` (dev đọc ngay, bản chạy thật cần `docker compose up -d`), hoặc thiếu vector (xem `search:status`) |
| Build embedder rất lâu hoặc lỗi mạng | Lần build đầu tải PyTorch và model; cần Internet lúc build, sau đó chạy offline |

## 4. Lệnh liên quan

| Lệnh | Việc |
|---|---|
| `php artisan search:status` | Kiểm tra chatbot đã sẵn sàng chưa |
| `php artisan search:embed` | Tạo hoặc cập nhật vector |
| `php artisan search:eval --engine=lexical\|semantic\|blend` | Chấm độ chính xác trên bộ câu hỏi đánh giá |
| `docker compose run --rm --build embedder-test` | Chạy test của dịch vụ embedder |

Chạy `php artisan` trong container `app`: `docker compose exec app php artisan ...`.

## 5. Nội dung luật

Chatbot chỉ biết 18 điều luật đã nạp từ `app/api/database/data/dieu_luat_data.php`. Câu hỏi ngoài phạm vi sẽ được trả lời là chưa có dữ liệu, và được ghi lại ở trang quản trị (Câu hỏi chatbot, bộ lọc "Không khớp điều luật") để biết cần bổ sung điều nào. Thêm điều luật mới là việc cần người có chuyên môn pháp lý duyệt nội dung, rồi chạy lại seeder và `search:embed`.

Dữ liệu chỉ để tham khảo, không thay thế tư vấn của luật sư.
