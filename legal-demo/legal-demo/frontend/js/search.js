let LOCAL_INDEX = null;

function getLocalIndex(){
  if (!LOCAL_INDEX){
    LOCAL_INDEX = buildIndex(LAW_DATA.map(a => ({
      id: a.id,
      text: `${a.tieu_de} ${a.tom_tat} ${a.tu_khoa} ${a.chuong}`
    })));
  }
  return LOCAL_INDEX;
}

function renderResults(items){
  const box = document.getElementById("results");
  if (!items.length){
    box.innerHTML = `<div class="empty-state">
      <p><strong>Không tìm thấy điều luật phù hợp.</strong></p>
      <p>Thử dùng từ khóa khác, hoặc mô tả tình huống bằng câu hỏi thường ngày ở trang <a href="tu-van.html">Tư vấn</a>.</p>
    </div>`;
    return;
  }
  box.innerHTML = items.map(it => `
    <div class="result-row">
      <div>
        <h3><a href="dieu-luat-chi-tiet.html?id=${it.id}">Điều ${it.id}. ${it.tieu_de}</a></h3>
        <p>${it.tom_tat}</p>
        <span class="pill">${it.chuong}</span>
        <span class="penalty" style="margin-left:6px;">${it.khung_hinh_phat}</span>
      </div>
      ${it.score !== undefined ? `<div class="score">phù hợp ${(it.score*100).toFixed(0)}%</div>` : ""}
    </div>`).join("");
}

async function runSearch(query, chuong){
  // 1) Thử gọi backend PHP thật (search.php dùng TfidfMatcher.php + MySQL)
  try {
    const data = await callApi(`../backend/api/search.php?q=${encodeURIComponent(query)}&chuong=${encodeURIComponent(chuong||"")}`);
    renderResults(data.results);
    return;
  } catch (e) {
    // 2) Không có server PHP đang chạy (demo tĩnh) -> xử lý ngay trên trình duyệt
  }
  let pool = LAW_DATA;
  if (chuong) pool = pool.filter(a => a.chuong === chuong);
  if (!query.trim()){
    renderResults(pool);
    return;
  }
  const idx = buildIndex(pool.map(a => ({ id: a.id, text: `${a.tieu_de} ${a.tom_tat} ${a.tu_khoa} ${a.chuong}` })));
  const ranked = semanticSearch(query, idx, 10)
    .filter(r => r.score > 0.03)
    .map(r => ({ ...pool.find(a => a.id === r.doc.id), score: r.score }));
  renderResults(ranked.length ? ranked : pool.filter(a =>
    vnNormalize(a.tieu_de + a.tu_khoa).includes(vnNormalize(query))
  ));
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("search-form");
  const input = document.getElementById("q");
  const chuongSel = document.getElementById("chuong");

  const params = new URLSearchParams(location.search);
  if (params.get("q")) input.value = params.get("q");

  chuongSel.innerHTML = `<option value="">Tất cả chương</option>` +
    [...new Set(LAW_DATA.map(a => a.chuong))].map(c => `<option>${c}</option>`).join("");

  form.addEventListener("submit", e => {
    e.preventDefault();
    runSearch(input.value, chuongSel.value);
  });
  chuongSel.addEventListener("change", () => runSearch(input.value, chuongSel.value));

  runSearch(input.value, "");
});
