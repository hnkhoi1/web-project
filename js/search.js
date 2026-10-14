(() => {
  const { user, $, el } = HS; if (!user) return;
  const form = $('#search-form'), q = $('#q'), sel = $('#chuong'), box = $('#results'), count = $('#count');
  sel.replaceChildren(new Option('Tất cả chương', ''), ...Law.chuongList().map((c) => new Option(c, c)));
  const params = new URLSearchParams(location.search); q.value = (params.get('q') || '').slice(0, 200);
  if (params.get('chuong')) sel.value = params.get('chuong');

  let debounceTimer;

  function run() {
    const rows = Law.search(q.value, sel.value);
    const qTrim = q.value.trim();
    count.textContent = `${rows.length} kết quả` + (qTrim ? ` cho "${qTrim}"` : '');
    if (!rows.length) {
      const d = el('div', 'empty-state'); d.appendChild(el('p')).appendChild(el('strong', '', 'Không tìm thấy điều luật phù hợp.'));
      const p = el('p', '', 'Thử từ khóa khác, hoặc mô tả tình huống ở trang '); const a = el('a', '', 'Tư vấn'); a.href = 'tu-van.html'; p.append(a, '.'); d.appendChild(p);
      box.replaceChildren(d); return;
    }
    box.replaceChildren(...rows.map((r) => {
      const art = el('article', 'result-row'), left = el('div'), h = el('h3'), a = el('a', '', `Điều ${r.id}. ${r.tieu_de}`);
      a.href = 'dieu-luat-chi-tiet.html?id=' + r.id; h.appendChild(a);
      left.append(h, el('p', '', r.tom_tat), el('span', 'pill', r.chuong), ' ', el('span', 'penalty', r.khung_hinh_phat));
      art.appendChild(left);
      if (r.score !== undefined) art.appendChild(el('div', 'score', `phù hợp ${Math.round(r.score * 100)}%`));
      return art;
    }));
  }

  function updateURL() {
    const p = new URLSearchParams();
    if (q.value.trim()) p.set('q', q.value.trim());
    if (sel.value) p.set('chuong', sel.value);
    const newUrl = location.pathname + (p.toString() ? '?' + p.toString() : '');
    history.replaceState(null, '', newUrl);
  }

  form.addEventListener('submit', (e) => { e.preventDefault(); updateURL(); run(); });
  sel.addEventListener('change', () => { updateURL(); run(); });
  q.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { updateURL(); run(); }, 300);
  });
  run();
})();

