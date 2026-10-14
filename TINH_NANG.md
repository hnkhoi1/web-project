# TỔNG QUAN TÍNH NĂNG HỆ THỐNG — HÌNH SỰ TRA CỨU

**Hình Sự Tra Cứu** là ứng dụng web hỗ trợ tra cứu văn bản pháp luật, phân tích tình huống thực tế và tư vấn pháp luật hình sự Việt Nam bằng ngôn ngữ tự nhiên. Hệ thống giúp người dân, sinh viên luật và người quan tâm dễ dàng tiếp cận kiến thức pháp lý và nắm rõ quyền lợi bồi thường của người bị hại.

---

## 1. Hệ thống Xác thực & Quản lý Người dùng

- **Đăng nhập an toàn:**
  - Hỗ trợ đăng nhập bằng tên đăng nhập hoặc email.
  - Cơ chế tự động khóa tạm thời sau 5 lần nhập sai liên tiếp để chống brute-force.
  - Tích hợp sẵn tài khoản demo cố định (`demo` / `12345`) phục vụ trải nghiệm nhanh.
- **Đăng ký tài khoản:**
  - Kiểm tra tính hợp lệ của email, tên hiển thị và độ an toàn của mật khẩu.
  - Mã hóa mật khẩu một chiều an toàn bằng **SHA-256 + Salt ngẫu nhiên**.
  - Mô phỏng hộp thư gửi email kích hoạt tài khoản ngay trên giao diện web.
- **Khôi phục mật khẩu (Quên mật khẩu):**
  - Quy trình 3 bước bảo mật với mã xác thực OTP 6 chữ số giả lập.
  - Giới hạn thời gian hiệu lực của mã và khóa phiên nếu nhập sai quá 5 lần.
- **Bảo vệ quyền riêng tư & Phân quyền:**
  - Tự động bảo vệ tất cả các trang nội bộ (chưa đăng nhập sẽ tự động chuyển hướng về trang đăng nhập và ghi nhớ URL chuyển tiếp).
  - Tài khoản demo được đóng băng (`Object.freeze`), không thể chỉnh sửa thông tin hoặc xóa tài khoản.

---

## 2. Trang chủ Cá nhân & Quản lý Ghi chú (Smart Notes)

- **Bảng điều khiển cá nhân (Dashboard):**
  - Chào mừng theo tên người dùng, tích hợp ô tìm kiếm điều luật nhanh ngay tại Hero banner.
  - Danh thiếp điều hướng nhanh đến các khu vực: *Hỏi chatbot*, *Tra cứu điều luật*, *Văn bản mới*.
  - Hiển thị danh sách các điều luật phổ biến và khung hình phạt tương ứng.
- **Quản lý ghi chú cá nhân (CRUD & Ghim):**
  - Tạo mới, chỉnh sửa nội dung, ghim ghi chú quan trọng lên đầu danh sách, xóa ghi chú có hộp thoại xác nhận.
  - **Tùy chọn màu sắc ghi chú:** 7 tông màu trực quan (Vàng, Xanh lá, Xanh dương, Hồng, Tím, Xám, Mặc định).
  - **Tìm kiếm ghi chú theo thời gian thực:** Lọc ngay lập tức theo tiêu đề và nội dung khi người dùng gõ phím.
  - Thu gọn/mở rộng nội dung dài khi nhấp chuột.
  - Tự động liên kết nguồn gốc ghi chú (từ chatbot hoặc từ trang điều luật cụ thể).

---

## 3. Tra cứu Điều luật Thông minh (Search Engine)

- **Live Search thời gian thực:**
  - Tìm kiếm ngay khi gõ từ khóa với cơ chế debounce 300ms, không cần bấm submit.
  - Tự động đồng bộ từ khóa và bộ lọc lên URL query parameters (dễ dàng bookmark hoặc chia sẻ).
- **Bộ lọc đa chiều:**
  - Lọc theo số điều (ví dụ: `134`, `321`).
  - Lọc theo từng Chương của Bộ luật Hình sự (Xâm phạm tính mạng sức khỏe, Xâm phạm sở hữu, Tội phạm ma túy, Chức vụ...).
  - Xếp hạng kết quả theo độ tương thích phần trăm (`phù hợp ...%`).

---

## 4. Chatbot Tư vấn Pháp lý Bằng Ngôn ngữ Tự nhiên (Legal AI Advisor)

- **Hiểu ngôn ngữ đời thường:**
  - Sử dụng thuật toán kết hợp đa tầng: **Khái niệm ngữ nghĩa (60%) + Từ khóa BM25 (30%) + Khoảng cách sửa lỗi Levenshtein (10%)**.
  - Xử lý tốt tiếng Việt có dấu, không dấu, từ lóng đời thường (ví dụ: *"bị đánh gãy tay"*, *"bị lừa chuyển khoản"*, *"say rượu đâm xe chết người"*, *"thủ quỹ lấy tiền công quỹ"*).
  - Tự động phân biệt từ dễ nhầm lẫn (ví dụ: *"đánh bạc"* hoàn toàn tách biệt với *"đánh người"*).
- **Phân định hai chiều quyền lợi & mức phạt:**
  - Tự nhận diện vai trò người hỏi (người bị hại hay người vi phạm).
  - Nêu rõ **quyền lợi yêu cầu bồi thường thiệt hại** của nạn nhân dựa trên căn cứ Bộ luật Dân sự (chi phí viện phí, mai táng, cấp dưỡng, tổn thất tinh thần...).
  - Nêu rõ khung hình phạt và mức xử lý đối với người phạm tội.
- **Hỏi làm rõ tình huống:**
  - Nếu câu hỏi chưa có số liệu định lượng (tỉ lệ thương tích %, số tiền bị chiếm đoạt...), bot tự động gợi ý người dùng bổ sung số liệu để chỉ ra đúng Khoản áp dụng.
- **Lưu nhanh vào ghi chú:**
  - Nút *"+ Lưu vào ghi chú"* ngay tại câu trả lời giúp lưu lại toàn bộ trích dẫn pháp lý chỉ với một click.

---

## 5. Chi tiết Điều luật & Quyền lợi Bồi thường

- **Trình bày rõ ràng, mạch lạc:**
  - Hiển thị tóm tắt hành vi phạm tội, khung hình phạt tổng quan.
  - Phân tách chi tiết từng Khoản kèm điều kiện định khung và hình phạt tương ứng.
  - Hộp nổi bật **Quyền lợi của người bị hại** và căn cứ pháp lý bồi thường dân sự (Điều 589, 590, 591, 601 Bộ luật Dân sự).
- **Gợi ý điều luật liên quan:**
  - Tự động liên kết các điều luật khác cùng chương để người dùng đối chiếu.

---

## 6. Văn bản Pháp luật Mới (Cập nhật Pháp lý Mới Triển khai)

*(Tính năng theo yêu cầu nâng cấp của PLAN.txt)*

- **Kho văn bản pháp luật mới ban hành:**
  - Cập nhật các Luật sửa đổi, Nghị quyết của Hội đồng Thẩm phán TAND Tối cao, Nghị định, Thông tư liên tịch mới nhất liên quan đến lĩnh vực hình sự và an toàn công cộng.
- **Bộ lọc & Tìm kiếm văn bản:**
  - Lọc theo loại văn bản: *Luật, Nghị quyết HĐTP, Nghị định, Thông tư*.
  - Lọc theo năm ban hành.
  - Tìm kiếm nhanh theo số hiệu, cơ quan ban hành, nội dung quy định.
- **Trạng thái hiệu lực trực quan:**
  - Huy hiệu phân biệt tự động: `Đã có hiệu lực` / `Chưa có hiệu lực` theo ngày tháng thực tế.
  - Trích dẫn các điều luật liên quan trực tiếp đến văn bản mới để đối chiếu nhanh.
  - Danh sách điểm mới nổi bật của từng văn bản.

---

## 7. Cá nhân hóa & Tùy chỉnh Giao diện

- **Chế độ Sáng / Tối (Dark mode / Light mode):**
  - Hỗ trợ 3 chế độ: *Sáng*, *Tối*, hoặc *Tự động theo thiết bị hệ điều hành*.
  - Chuyển đổi mượt mà với bảng màu tương phản chuẩn WCAG.
- **Tùy chỉnh cỡ chữ ghi chú:** 4 cấp độ (*Nhỏ, Vừa, Lớn, Rất lớn*) phù hợp nhu cầu đọc của từng người dùng.
- **Xem trước trực tiếp (Live Preview):** Hiển thị ngay thay đổi màu sắc và kích thước chữ trước khi lưu.

---

## 8. Quản lý Hồ sơ Cá nhân & Bảo mật

- **Cập nhật thông tin:** Cho phép đổi tên hiển thị và cập nhật thông tin tài khoản.
- **Ảnh đại diện tùy chỉnh:** Tải ảnh lên và tự động cắt vuông kích thước 256×256 qua Canvas (hỗ trợ JPG, PNG, WebP tối đa 2MB).
- **Đổi mật khẩu:** Yêu cầu mật khẩu hiện tại, kiểm tra độ trùng khớp và thanh đo độ mạnh mật khẩu theo thời gian thực.
- **Xóa vĩnh viễn tài khoản:** Tính năng Danger Zone với hộp thoại xác nhận mật khẩu, dọn dẹp sạch sẽ toàn bộ ghi chú của tài khoản đó.

---

## 9. Quản trị & Kiểm tra Dữ liệu Demo (Database Viewer)

- **Bảng thống kê toàn diện:** Xem số lượng tài khoản đã tạo, số lượng ghi chú và câu hỏi liên hệ được lưu trong `localStorage`.
- **Phân tách nguồn gốc dữ liệu:** Phân biệt rõ tài khoản demo cố định trong mã nguồn và tài khoản thêm mới vào database.
- **Xóa nhanh dữ liệu thử nghiệm:** Nút Reset dữ liệu người dùng thêm mới mà không làm ảnh hưởng đến tài khoản demo.

---

## 10. Trải nghiệm & Tương thích Kỹ thuật

- **Hoạt động hoàn toàn trên trình duyệt (Client-side):** Không cần cài đặt máy chủ phức tạp, có thể mở trực tiếp hoặc chạy qua server tĩnh đơn giản.
- **Thiết kế Responsive 100%:**
  - Điện thoại (≤ 639px): Menu thanh trượt dạng hamburger, các nút bấm tối ưu cảm ứng (chiều cao ≥ 44px).
  - Máy tính bảng (640px – 1023px): Bố cục lưới 2 cột co giãn linh hoạt.
  - Máy tính để bàn (≥ 1024px): Bố cục tối ưu đa cột, thanh điều hướng ngang rộng rãi.
- **Khả năng tiếp cận (Accessibility):** Có nút "Bỏ qua điều hướng" (Skip to main), thông báo aria-live và quản lý tiêu điểm bàn phím cho người khiếm thị.
