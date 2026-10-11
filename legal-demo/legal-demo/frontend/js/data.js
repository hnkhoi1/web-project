/* =========================================================
   DỮ LIỆU MINH HỌA
   Đây là dữ liệu rút gọn, biên soạn lại cho mục đích DEMO môn học.
   Không phải trích dẫn nguyên văn Bộ luật Hình sự — khi triển khai
   thật, dữ liệu phải được nạp từ bảng `dieu_luat` (xem database/schema.sql)
   và đối chiếu với văn bản chính thức.
   ========================================================= */
const LAW_DATA = [
  {
    id: 123,
    chuong: "Các tội xâm phạm tính mạng, sức khỏe",
    tieu_de: "Tội giết người",
    tu_khoa: "giết người tước đoạt tính mạng sát hại cố ý",
    tom_tat: "Hành vi cố ý tước đoạt tính mạng người khác một cách trái pháp luật.",
    khung_hinh_phat: "7 – 20 năm tù, tù chung thân hoặc tử hình (có tình tiết định khung tăng nặng)",
    khoan: [
      "Người nào giết người thuộc một trong các trường hợp định khung tăng nặng (giết từ 2 người trở lên, giết người dưới 16 tuổi, có tính chất côn đồ, vì động cơ đê hèn...) thì bị phạt tù 12–20 năm, tù chung thân hoặc tử hình.",
      "Phạm tội không thuộc các trường hợp trên thì bị phạt tù từ 7 đến 15 năm.",
      "Người chuẩn bị phạm tội có thể bị phạt tù từ 1 đến 5 năm."
    ]
  },
  {
    id: 168,
    chuong: "Các tội xâm phạm sở hữu",
    tieu_de: "Tội cướp tài sản",
    tu_khoa: "cướp tài sản dùng vũ lực đe dọa chiếm đoạt",
    tom_tat: "Dùng vũ lực, đe dọa dùng vũ lực ngay tức khắc hoặc thủ đoạn khác làm người bị tấn công lâm vào tình trạng không thể chống cự nhằm chiếm đoạt tài sản.",
    khung_hinh_phat: "3 – 20 năm tù hoặc tù chung thân, tùy tình tiết và giá trị tài sản",
    khoan: [
      "Người nào dùng vũ lực, đe dọa dùng vũ lực ngay tức khắc hoặc có hành vi khác làm cho người bị tấn công lâm vào tình trạng không thể chống cự được nhằm chiếm đoạt tài sản thì bị phạt tù từ 3 đến 10 năm.",
      "Phạm tội có tổ chức, có tính chất chuyên nghiệp, dùng vũ khí, gây thương tích... thì bị phạt tù từ 7 đến 15 năm.",
      "Phạm tội chiếm đoạt tài sản trị giá từ 200 triệu đến dưới 500 triệu đồng, hoặc gây thương tích tỉ lệ tổn thương cơ thể từ 31–60% thì bị phạt tù từ 12 đến 20 năm.",
      "Phạm tội trong trường hợp đặc biệt nghiêm trọng (chiếm đoạt tài sản 500 triệu đồng trở lên, làm chết người) thì bị phạt tù 18–20 năm hoặc tù chung thân."
    ]
  },
  {
    id: 174,
    chuong: "Các tội xâm phạm sở hữu",
    tieu_de: "Tội lừa đảo chiếm đoạt tài sản",
    tu_khoa: "lừa đảo gian dối chiếm đoạt tài sản online chuyển khoản",
    tom_tat: "Dùng thủ đoạn gian dối để chiếm đoạt tài sản của người khác.",
    khung_hinh_phat: "Cảnh cáo, cải tạo không giam giữ đến 3 năm, hoặc phạt tù 6 tháng – 20 năm tùy giá trị tài sản",
    khoan: [
      "Người nào bằng thủ đoạn gian dối chiếm đoạt tài sản của người khác trị giá từ 2 triệu đến dưới 50 triệu đồng (hoặc dưới 2 triệu nhưng đã bị xử phạt hành chính / có tiền án chưa xóa) thì bị phạt cải tạo không giam giữ đến 3 năm hoặc phạt tù từ 6 tháng đến 3 năm.",
      "Phạm tội có tổ chức, tính chất chuyên nghiệp, chiếm đoạt tài sản 50–200 triệu đồng... thì bị phạt tù từ 2 đến 7 năm.",
      "Chiếm đoạt tài sản trị giá 200 triệu đến dưới 500 triệu đồng thì bị phạt tù từ 7 đến 15 năm.",
      "Chiếm đoạt tài sản trị giá 500 triệu đồng trở lên thì bị phạt tù từ 12 đến 20 năm hoặc tù chung thân."
    ]
  },
  {
    id: 260,
    chuong: "Các tội xâm phạm an toàn công cộng, trật tự công cộng",
    tieu_de: "Tội vi phạm quy định về tham gia giao thông đường bộ",
    tu_khoa: "tai nạn giao thông lái xe gây chết người vi phạm luật giao thông",
    tom_tat: "Người tham gia giao thông đường bộ vi phạm quy định về an toàn giao thông gây thiệt hại cho tính mạng, sức khỏe, tài sản người khác.",
    khung_hinh_phat: "Phạt tiền, cải tạo không giam giữ đến 3 năm, hoặc phạt tù 1 – 15 năm tùy hậu quả",
    khoan: [
      "Vi phạm quy định về an toàn giao thông đường bộ làm chết 1 người, hoặc gây thương tích tỉ lệ tổn thương cơ thể 61% trở lên, hoặc gây thiệt hại tài sản từ 100 triệu đến dưới 500 triệu đồng thì bị phạt tiền 30–100 triệu đồng, cải tạo không giam giữ đến 3 năm hoặc phạt tù từ 1 đến 5 năm.",
      "Không có giấy phép lái xe, trong tình trạng có sử dụng rượu bia/ma túy, bỏ chạy để trốn tránh trách nhiệm, làm chết 2 người... thì bị phạt tù từ 3 đến 10 năm.",
      "Làm chết 3 người trở lên hoặc gây thiệt hại tài sản 1,5 tỷ đồng trở lên thì bị phạt tù từ 7 đến 15 năm."
    ]
  },
  {
    id: 353,
    chuong: "Các tội phạm về chức vụ",
    tieu_de: "Tội tham ô tài sản",
    tu_khoa: "tham ô tài sản chức vụ quyền hạn chiếm đoạt công quỹ",
    tom_tat: "Người có chức vụ, quyền hạn lợi dụng chức vụ chiếm đoạt tài sản mà mình có trách nhiệm quản lý.",
    khung_hinh_phat: "2 – 20 năm tù, tù chung thân hoặc tử hình tùy giá trị tài sản",
    khoan: [
      "Người nào lợi dụng chức vụ, quyền hạn chiếm đoạt tài sản mà mình có trách nhiệm quản lý trị giá từ 2 triệu đến dưới 100 triệu đồng (hoặc dưới 2 triệu nhưng có tình tiết tăng nặng) thì bị phạt tù từ 2 đến 7 năm.",
      "Chiếm đoạt tài sản trị giá 100 triệu đến dưới 500 triệu đồng, hoặc có tổ chức... thì bị phạt tù từ 7 đến 15 năm.",
      "Chiếm đoạt tài sản trị giá 500 triệu đến dưới 1 tỷ đồng thì bị phạt tù từ 15 đến 20 năm.",
      "Chiếm đoạt tài sản trị giá 1 tỷ đồng trở lên thì bị phạt tù 20 năm, tù chung thân hoặc tử hình."
    ]
  },
  {
    id: 134,
    chuong: "Các tội xâm phạm tính mạng, sức khỏe",
    tieu_de: "Tội cố ý gây thương tích hoặc gây tổn hại cho sức khỏe của người khác",
    tu_khoa: "cố ý gây thương tích đánh nhau bạo lực tổn hại sức khỏe",
    tom_tat: "Hành vi cố ý gây thương tích hoặc tổn hại sức khỏe cho người khác.",
    khung_hinh_phat: "Cải tạo không giam giữ đến 3 năm, hoặc phạt tù 6 tháng – 20 năm tùy tỉ lệ tổn thương",
    khoan: [
      "Gây thương tích tỉ lệ tổn thương cơ thể từ 11% đến 30%, hoặc dưới 11% nhưng có tình tiết tăng nặng (dùng hung khí nguy hiểm, đối với người dưới 16 tuổi...) thì bị phạt cải tạo không giam giữ đến 3 năm hoặc phạt tù từ 6 tháng đến 3 năm.",
      "Tỉ lệ tổn thương từ 31% đến 60%, hoặc từ 11–30% nhưng có tình tiết tăng nặng thì bị phạt tù từ 2 đến 6 năm.",
      "Tỉ lệ tổn thương 61% trở lên, hoặc dẫn đến chết người, thì bị phạt tù từ 5 đến 10 năm hoặc cao hơn tùy tình tiết."
    ]
  },
  {
    id: 249,
    chuong: "Các tội phạm về ma túy",
    tieu_de: "Tội tàng trữ trái phép chất ma túy",
    tu_khoa: "ma túy tàng trữ trái phép chất cấm sử dụng",
    tom_tat: "Tàng trữ trái phép chất ma túy mà không nhằm mục đích mua bán, vận chuyển, sản xuất.",
    khung_hinh_phat: "1 – 20 năm tù hoặc tù chung thân tùy khối lượng",
    khoan: [
      "Tàng trữ trái phép chất ma túy với khối lượng ở mức khởi điểm theo quy định thì bị phạt tù từ 1 đến 5 năm.",
      "Có tổ chức, tái phạm nguy hiểm, khối lượng lớn hơn mức khởi điểm... thì bị phạt tù từ 5 đến 15 năm.",
      "Khối lượng đặc biệt lớn thì bị phạt tù 15–20 năm hoặc tù chung thân."
    ]
  },
  {
    id: 321,
    chuong: "Các tội xâm phạm trật tự quản lý kinh tế",
    tieu_de: "Tội đánh bạc",
    tu_khoa: "đánh bạc cờ bạc tổ chức trò chơi ăn tiền",
    tom_tat: "Đánh bạc trái phép dưới bất kỳ hình thức nào được thua bằng tiền hay hiện vật trị giá từ mức quy định trở lên.",
    khung_hinh_phat: "Phạt tiền, cải tạo không giam giữ đến 3 năm, hoặc phạt tù đến 7 năm",
    khoan: [
      "Đánh bạc trái phép được thua bằng tiền hay hiện vật trị giá từ 5 triệu đến dưới 50 triệu đồng (hoặc dưới 5 triệu nhưng đã bị xử phạt/kết án về hành vi này) thì bị phạt tiền 20–100 triệu đồng, cải tạo không giam giữ đến 3 năm hoặc phạt tù từ 6 tháng đến 3 năm.",
      "Có tính chất chuyên nghiệp, tiền/hiện vật trị giá 50 triệu đồng trở lên, sử dụng mạng internet/viễn thông... thì bị phạt tù từ 3 đến 7 năm."
    ]
  }
];
