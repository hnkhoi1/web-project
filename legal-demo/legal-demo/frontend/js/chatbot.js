let CHAT_INDEX = null;
function getChatIndex(){
  if (!CHAT_INDEX){
    CHAT_INDEX = buildIndex(LAW_DATA.map(a => ({
      id: a.id,
      text: `${a.tieu_de} ${a.tom_tat} ${a.tu_khoa} ${a.chuong} ${a.khoan.join(" ")}`
    })));
  }
  return CHAT_INDEX;
}

function appendMessage(role, html){
  const log = document.getElementById("chat-log");
  const div = document.createElement("div");
  div.className = `msg ${role}`;
  div.innerHTML = html;
  log.appendChild(div);
  log.scrollTop = log.scrollHeight;
  return div;
}

function showTyping(){
  const log = document.getElementById("chat-log");
  const div = document.createElement("div");
  div.className = "typing";
  div.id = "typing-indicator";
  div.textContent = "Hệ thống đang phân tích câu hỏi…";
  log.appendChild(div);
  log.scrollTop = log.scrollHeight;
}
function hideTyping(){
  document.getElementById("typing-indicator")?.remove();
}

/** Soạn câu trả lời kiểu tư vấn từ điều luật khớp nhất, kèm trích dẫn. */
function composeAnswer(query, top){
  if (!top.length || top[0].score < 0.03){
    return {
      html: `Xin lỗi, hệ thống chưa tìm thấy điều luật phù hợp rõ ràng với câu hỏi này.
        Bạn có thể mô tả cụ thể hơn hành vi, tình huống, hoặc thử tra cứu trực tiếp tại
        <a href="tra-cuu.html">trang Tra cứu</a>.`,
      matches: []
    };
  }
  const best = top[0];
  const others = top.slice(1, 3).filter(r => r.score > 0.05);
  let html = `Dựa trên câu hỏi của bạn, tình huống này có thể liên quan đến
    <b>Điều ${best.doc.article.id}. ${best.doc.article.tieu_de}</b> (${best.doc.article.chuong}).<br><br>
    ${best.doc.article.tom_tat}<br><br>
    <b>Khung hình phạt tham khảo:</b> ${best.doc.article.khung_hinh_phat}`;
  if (others.length){
    html += `<br><br>Ngoài ra bạn có thể tham khảo thêm: ` +
      others.map(o => `Điều ${o.doc.article.id} (${o.doc.article.tieu_de})`).join(", ") + ".";
  }
  html += `<span class="cite">Nguồn tham khảo: <a href="dieu-luat-chi-tiet.html?id=${best.doc.article.id}">Điều ${best.doc.article.id} — xem chi tiết &amp; các khoản</a></span>`;
  return { html, matches: [best.doc.article, ...others.map(o=>o.doc.article)] };
}

async function askBackend(question){
  const data = await callApi(`../backend/api/chatbot.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ question })
  });
  return data; // { answer_html, matches: [...] }
}

async function handleAsk(question){
  if (!question.trim()) return;
  appendMessage("user", question.replace(/</g, "&lt;"));
  showTyping();

  try {
    const data = await askBackend(question);
    hideTyping();
    appendMessage("bot", data.answer_html);
    return;
  } catch (e) {
    // Không có backend PHP đang chạy -> xử lý NLU ngay trên trình duyệt
  }

  const idx = getChatIndex();
  const ranked = semanticSearch(question, idx, 3).map(r => ({
    score: r.score,
    doc: { article: LAW_DATA.find(a => a.id === r.doc.id) }
  }));
  const { html } = composeAnswer(question, ranked);
  hideTyping();
  appendMessage("bot", html);
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("chat-form");
  const input = document.getElementById("chat-input");

  form.addEventListener("submit", e => {
    e.preventDefault();
    const q = input.value;
    input.value = "";
    handleAsk(q);
  });

  document.querySelectorAll(".suggestion").forEach(btn => {
    btn.addEventListener("click", () => handleAsk(btn.textContent.trim()));
  });

  appendMessage("bot", `Xin chào 👋 Tôi là trợ lý tra cứu &amp; tư vấn <b>Luật Hình sự Việt Nam</b>.
    Bạn có thể mô tả tình huống hoặc đặt câu hỏi bằng ngôn ngữ tự nhiên, ví dụ:
    <i>"Đánh nhau gây thương tích thì bị xử lý thế nào?"</i>. Tôi sẽ tìm điều luật liên quan
    và tóm tắt khung hình phạt tham khảo.
    <span class="cite">Đây là hệ thống hỗ trợ tra cứu, không thay thế tư vấn của luật sư.</span>`);
});
