# BIÊN BẢN BÀN GIAO — Web tra cứu & tư vấn luật hình sự ("Hình Sự Tra Cứu")

Cập nhật lần cuối: 10/10/2026. Dành cho AI/người tiếp tục dự án. Đọc hết mục 1–3 trước khi sửa bất cứ thứ gì.

## 1. Bối cảnh & yêu cầu của người dùng (theo thứ tự thời gian)
Bài tập môn học: hoàn thành template gồm đăng ký đề tài, web FrontEnd tối thiểu 5 trang + BackEnd có menu chức năng, nộp file PDF (màn hình) và bộ web tĩnh (HTML, CSS, JavaScript), kèm thiết kế sơ bộ CSDL (PDF).

Yêu cầu cố định từ đầu:
- Chủ đề: web tra cứu & tư vấn luật hình sự Việt Nam, thiên hướng **chatbot**.
- Chỉ dùng **HTML, CSS, JavaScript, PHP**; không SQL/.sql; không Streamlit; "database dùng PHP".
- Hệ thống tự hiểu câu hỏi (ý định ban đầu là sentence-transformers hoặc mô hình nhẹ hơn → đang dùng TF-IDF + cosine).
- Chỉnh sửa đã yêu cầu: (1) chatbot nêu **quyền lợi/bồi thường** của người dùng theo mức độ/khoản, không chỉ nêu hình phạt khô khan; (2) trang giới thiệu viết như web thật hướng khách hàng, không mô tả kiến trúc/NLP; (3) dữ liệu điều luật cấu trúc **theo từng khoản**.
- Giai đoạn tài khoản: chưa đăng nhập → về login; đăng ký chỉ gồm email + tên hiển thị + mật khẩu nhập 2 lần, tự đăng nhập sau khi đăng ký và gửi email kích hoạt; chưa kích hoạt vẫn dùng đủ chức năng nhưng có thông báo nổi bật; hồ sơ (sửa tên + avatar có validate, avatar mặc định); đổi mật khẩu; tùy chỉnh cỡ chữ ghi chú / màu ghi chú / sáng-tối; quên mật khẩu qua link hoặc OTP rồi đăng nhập lại thủ công; trang chủ cá nhân xem ghi chú; UI/UX tốt, responsive (điện thoại, tablet, desktop).

**Yêu cầu MỚI NHẤT (quyết định hướng hiện tại):**
> Làm **bản demo giao diện tĩnh**. Tài khoản `demo` / `12345` nằm cứng trong code. Tạo hoặc xóa tài khoản chỉ thêm/bớt trong database, **không thay đổi tài khoản ban đầu**.

## 2. Hai bản đã giao — dùng bản nào?
| Bản | Gói | Trạng thái | Khi nào dùng |
|---|---|---|---|
| **Demo tĩnh (HIỆN HÀNH)** | `legal-demo-ui.zip` | Hoàn thành, **đã kiểm thử trên trình duyệt thật** (24/24 đạt) | Bản nộp/trình bày theo yêu cầu mới nhất |
| Bản PHP có máy chủ | `legal-demo.zip` (giao trước đó) | Đã viết đủ, **CHƯA CHẠY THỬ** vì môi trường AI không có PHP; chỉ kiểm cân bằng ngoặc và cú pháp JS | Chỉ khi người dùng muốn bản chạy thật trên XAMPP/PHP |

Lý do có hai bản: ban đầu AI chuyển sang PHP vì cần đăng nhập/bcrypt/email thật mà không hỏi lại người dùng; người dùng sau đó nêu đề bài cần "bộ web tĩnh" nên chọn demo giao diện. **Đừng tự ý quay lại PHP** nếu chưa hỏi.

## 3. Bản demo tĩnh — cấu trúc & hoạt động
Chạy: mở `login.html` (hoặc `python3 -m http.server`). Không build, không phụ thuộc thư viện ngoài (chỉ Google Fonts, có font dự phòng khi offline).

```
legal-demo-ui/
  login.html register.html forgot-password.html     (3 trang xác thực, bố cục chia đôi)
  index.html tra-cuu.html dieu-luat-chi-tiet.html tu-van.html
  gioi-thieu.html lien-he.html ho-so.html doi-mat-khau.html tuy-chinh.html database.html
  css/app.css        (một file; sáng/tối theo biến CSS; breakpoint 640 / 900 / 1024)
  js/db.js           database giả lập + DEMO_USER + Session
  js/app.js          chặn khi chưa đăng nhập, áp tùy chỉnh, dựng header/footer/banner, tiện ích form
  js/auth.js         đăng nhập, đăng ký, quên mật khẩu (OTP giả lập)
  js/law.js          tra cứu + chatbot (extractSoLieu, pickKhoan, answer)
  js/data.js nlu.js  dữ liệu điều luật theo khoản; TF-IDF/cosine
  js/notes.js search.js detail.js chatbot.js contact.js account.js   (mỗi trang một script)
  tests/e2e.py  tests/db-test.js
```

### 3.1 Tài khoản demo & database (phần quan trọng nhất)
- `DEMO_USER` ở đầu `js/db.js`: `username: 'demo'`, `password: '12345'`, `builtIn: true`, `Object.freeze`. **Không lưu trong database.**
- Database = `localStorage['hs_demo_db_v1']` = `{ seq, users[], notes[], contacts[], prefs{} }`. `users` chỉ chứa tài khoản được thêm.
- Mọi hàm ghi tài khoản (`updateUser`, `changePassword`, `setPassword`, `deleteUser`) gọi `guard()` → ném lỗi nếu là tài khoản demo hoặc không tồn tại. `resetAddedUsers()` xóa tài khoản đã thêm nhưng giữ demo.
- Giao diện khóa tương ứng: hồ sơ, đổi mật khẩu, nút xóa tài khoản bị `disabled` kèm thông báo (`#demo-lock`) cho demo.
- Phiên: `localStorage['hs_session']` = id người dùng. Khóa đăng nhập 60 giây sau 5 lần sai (đếm trong `sessionStorage['hs_fail']`).
- Mật khẩu tài khoản thêm vào: SHA-256 + salt (`crypto.subtle`; có hàm dự phòng khi mở bằng `file://` không hỗ trợ). **Chỉ để minh họa.**
- Đăng nhập bằng "tên đăng nhập hoặc email": `demo` khớp username; tài khoản mới khớp email (viết thường).

### 3.2 Luồng chính
- **Chặn truy cập:** `app.js` nạp đồng bộ trong `<head>`; chưa đăng nhập → lưu `hs_next` rồi `location.replace('login.html')`; đã đăng nhập mà vào trang xác thực → về `index.html`. Sau đăng nhập quay về đúng trang đang vào (regex kiểm tra đường dẫn).
- **Đăng ký:** validate từng ô (email, tên 2–40 ký tự, mật khẩu ≥8 gồm chữ+số, 2 lần khớp) → `createUser` → tự đăng nhập → thanh vàng "chưa xác thực" (`verified:false`). Nút "Mở email kích hoạt" mở hộp thư giả lập; bấm kích hoạt → `verified:true`.
- **Quên mật khẩu:** 3 bước trong một trang (email → OTP/link giả lập → mật khẩu mới); OTP 6 số, hiệu lực 15 phút, sai quá 5 lần bị khóa; xong thì về login, **không tự đăng nhập**. Email không tồn tại vẫn sang bước 2 (phản hồi trung tính); `demo` được báo riêng là không đặt lại được.
- **Ghi chú:** thêm/sửa/xóa/ghim/tìm; màu `auto` (theo tùy chỉnh người dùng) hoặc 6 màu; tối đa 200 ghi chú/người; nút "Lưu vào ghi chú" ở chatbot và trang điều luật. Dựng DOM bằng `textContent` (không `innerHTML` với dữ liệu người dùng; chỗ dùng `innerHTML` đều qua `esc()`).
- **Tùy chỉnh:** `theme` (system/light/dark), `font` (sm/md/lg/xl, áp cho nội dung ghi chú), `noteColor`; xem trước tức thì, lưu theo từng người (demo cũng lưu được).
- **Xóa tài khoản:** ở `ho-so.html`, yêu cầu nhập lại mật khẩu; xóa cả ghi chú/liên hệ/tùy chỉnh của người đó.
- **Chatbot (`law.js`):** TF-IDF chọn điều luật (ngưỡng 0.03) → `extractSoLieu` lấy % thương tích, giá trị tài sản (quy ra triệu đồng), số người chết → `pickKhoan` chọn khoản có khoảng số liệu hẹp nhất khớp, nếu không thì TF-IDF trên từng khoản → trả mức xử lý + **quyền lợi** + căn cứ bồi thường.

### 3.3 Dữ liệu điều luật (`js/data.js`)
Mảng `LAW_DATA`; mỗi điều có `id, chuong, tieu_de, tom_tat, tu_khoa, khung_hinh_phat, can_cu_boi_thuong, ghi_chu_quyen_loi, khoan[]`; mỗi khoản có `so_khoan, dieu_kien, noi_dung, quyen_loi, tu_khoa` và các trường định lượng `ti_le_ton_thuong_min/max, gia_tri_tai_san_min/max, so_nguoi_chet_min`. Nội dung biên soạn rút gọn cho học tập, **không phải trích nguyên văn luật**.

## 4. Kiểm thử đã thực hiện
- `tests/db-test.js` (Node): 13/13 — đăng nhập demo, trùng email, băm mật khẩu, mọi thao tác sửa/xóa demo bị từ chối, `DEMO_USER` đóng băng, xóa tài khoản kèm ghi chú, reset database chỉ còn demo.
- `tests/e2e.py` (Playwright + Chromium, mở bằng `file://`): 24/24 — chặn khi chưa đăng nhập; đăng nhập sai/đúng và quay về đúng trang; chatbot trả Điều 134 kèm quyền lợi; lưu ghi chú; hồ sơ/mật khẩu demo bị khóa; theme tối được lưu; đăng ký (lỗi khớp mật khẩu → thành công → thanh vàng → kích hoạt); `database.html` đủ 2 tài khoản; xóa tài khoản (sai/đúng mật khẩu), tài khoản đã xóa không đăng nhập được, demo vẫn bình thường; OTP sai/đúng → đặt lại → đăng nhập mật khẩu mới; 375px có hamburger, không tràn ngang; không lỗi JS.
  Chạy: `cd legal-demo-ui && python3 tests/e2e.py` (cần `pip install playwright` + chromium; test chặn Google Fonts nên chạy được offline).
- Chưa làm: kiểm thử Firefox/Safari, trình đọc màn hình, mở thật trên điện thoại.

## 5. Giả định chưa được người dùng xác nhận
1. Ghi chú và tùy chỉnh giao diện của tài khoản demo **vẫn sửa được** (coi là dữ liệu, không phải bản ghi tài khoản). Nếu người dùng muốn demo hoàn toàn chỉ-đọc, cần khóa thêm ở `notes.js`/`account.js`/`db.js`.
2. Đăng nhập bằng "username hoặc email" (đề bài chỉ nói username cho demo; đăng ký vẫn theo yêu cầu cũ: email + tên + mật khẩu×2).
3. Có thêm trang `database.html` (xem/xóa dữ liệu demo) và nút xóa tài khoản — người dùng nhắc "thêm/bớt database" nên AI bổ sung để trình bày được.
4. Chưa đối chiếu với **đề bài nộp chính xác** (PDF màn hình, thiết kế CSDL). Nên hỏi người dùng: cần bao nhiêu màn hình, có cần sơ đồ CSDL (gợi ý: users, notes, contacts, prefs — xem 3.1) và có cần phần "BackEnd có menu chức năng" dưới dạng trang quản trị giả lập không.

## 6. Giới hạn đã biết
- **Không bảo mật thật**: đăng nhập chạy ở trình duyệt, ai mở DevTools cũng thấy mật khẩu demo và localStorage. Chỉ phù hợp trình bày giao diện. Đừng mô tả nó là bảo mật thật trong báo cáo.
- localStorage ~5MB; avatar nén JPEG 256×256 (~20KB) nên ổn, nhưng nhiều người dùng + nhiều ghi chú có thể đầy → code đã báo lỗi thân thiện.
- Tiếng Việt bỏ dấu khi so khớp nên "tội" và "tôi" trùng dạng; từ "tôi" nằm trong danh sách bỏ qua.
- Dữ liệu điều luật chỉ vài điều; câu hỏi ngoài phạm vi trả "chưa tìm được điều luật phù hợp".
- Kích hoạt email/OTP chỉ là mô phỏng trên màn hình.

## 7. Lưu ý kỹ thuật khi làm việc tiếp
- Môi trường AI này **không có PHP**, `/bin/sh` là dash: `mkdir -p a/{b,c}` **không** mở rộng ngoặc nhọn (đã gây lỗi hai lần) — dùng `bash -c` hoặc liệt kê từng thư mục.
- Khi sửa HTML: các trang đã đăng nhập chỉ chứa nội dung trong `<main>`; header/footer/banner do `app.js` chèn vào `#app-header`/`#app-footer`. Trang mới cần `data-page` trên `<body>` (để đánh dấu menu) và thêm vào menu ở `renderChrome()`.
- Trang mới thuộc nhóm xác thực phải được thêm vào mảng `AUTH_PAGES` trong `app.js`.
- Mọi script trang đều bắt đầu bằng `if (!user) return;` để không lỗi khi đang bị chuyển hướng.
- Giữ nguyên bất biến: không thêm đường ghi nào vào `DEMO_USER`; mọi hàm ghi tài khoản mới phải đi qua `guard()`.

## 8. Việc có thể làm tiếp (theo thứ tự đề xuất)
1. Hỏi người dùng đề bài chính xác rồi chốt danh sách màn hình cần chụp cho PDF.
2. Vẽ sơ đồ CSDL (4 bảng ở mục 3.1 + `LAW_DATA` điều/khoản) cho phần "thiết kế sơ bộ CSDL".
3. Nếu cần "BackEnd có menu chức năng": thêm trang quản trị giả lập (quản lý điều luật/liên hệ) — hiện chỉ có `database.html`.
4. Xuất/nhập ghi chú, thêm điều luật mới, kiểm tra truy cập bằng bàn phím và trình đọc màn hình.
5. Chỉ khi người dùng đồng ý: chạy thử và hoàn thiện bản PHP (`legal-demo.zip`).


## 9. Cập nhật 11/10/2026 — sửa chatbot hiểu câu hỏi
- Lỗi: "tôi bị đánh" luôn ra Điều 321 (đánh bạc) vì TF-IDF bỏ dấu ("đánh"="danh") và chỉ khớp từ đơn.
- Đã thêm `js/scorer.js` (`Scorer.rank`, `Scorer.explain`): điểm = 0.6·khái niệm + 0.3·BM25 + 0.1·gần đúng; cụm dài được tách trước ("đánh bạc"/"đánh chết" ≠ "đánh"); có dấu thì khớp có dấu, không dấu mới khớp không dấu; ngưỡng 0.20 → "không tìm thấy", 2 điều chênh <0.10 → gợi ý "Có thể bạn muốn hỏi".
- `Law.answer` và `Law.search` dùng Scorer; khi chưa có số liệu (%, triệu đồng) thì trả tổng quan các khoản + hỏi thêm thay vì chọn bừa khoản 1; người bị hại (`role=nan_nhan`) thấy quyền lợi trước.
- Muốn dạy thêm cách nói đời thường → thêm cụm vào `CONCEPTS` đầu `scorer.js` (khóa là số điều). Thêm điều luật mới vào `data.js` thì phải thêm khái niệm tương ứng.
- Test: `node tests/scorer-test.js` 19/19; `tests/e2e.py` 26/26 (có hồi quy "tôi bị đánh").
