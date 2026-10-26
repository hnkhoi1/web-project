(() => {
  const { user, $, el } = HS; if (!user) return;
  const form = $('#search-form'), q = $('#q'), sel = $('#chuong'), box = $('#results'), count = $('#count');
  sel.replaceChildren(new Option('Tất cả chương', ''), ...Law.chuongList().map((c) => new Option(c, c)));
  function restoreFilters() {
    const params = new URLSearchParams(location.search);
    q.value = (params.get('q') || '').slice(0, 200);
    const chapter = params.get('chuong') || '';
    sel.value = [...sel.options].some((o) => o.value === chapter) ? chapter : '';
  }
  function syncFilters() {
    const params = new URLSearchParams(location.search), query = q.value.trim().slice(0, 200);
    query ? params.set('q', query) : params.delete('q');
    sel.value ? params.set('chuong', sel.value) : params.delete('chuong');
    const search = params.toString();
    history.replaceState(null, '', location.pathname + (search ? '?' + search : '') + location.hash);
  }

  function run() {
    const rows = Law.search(q.value, sel.value);
    count.textContent = `${rows.length} kết quả` + (q.value.trim() ? ` cho "${q.value.trim()}"` : '');
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
  form.addEventListener('submit', (e) => { e.preventDefault(); syncFilters(); run(); });
  sel.addEventListener('change', () => { syncFilters(); run(); });
  window.addEventListener('popstate', () => { restoreFilters(); run(); });
  restoreFilters();
  run();
})();
