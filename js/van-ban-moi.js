(() => {
  const { user, $, $$, el, esc } = HS; if (!user) return;
  const grid = $('#news-grid'), empty = $('#news-empty'), countEl = $('#news-count');
  const searchEl = $('#news-q'), typeEl = $('#news-type'), yearEl = $('#news-year');

  // Điền danh sách loại văn bản
  const types = [...new Set(LAW_NEWS.map(n => n.loai))];
  typeEl.replaceChildren(new Option('Tất cả loại', ''), ...types.map(t => new Option(t, t)));

  // Điền danh sách năm từ dữ liệu
  const years = [...new Set(LAW_NEWS.map(n => n.ngay_ban_hanh.slice(0, 4)))].sort((a, b) => b - a);
  yearEl.replaceChildren(new Option('Tất cả năm', ''), ...years.map(y => new Option(y, y)));

  const fmtDate = (s) => {
    if (!s) return '';
    const [y, m, d] = s.split('-');
    return `${d}/${m}/${y}`;
  };

  const statusBadge = (item) => {
    const today = new Date().toISOString().slice(0, 10);
    if (item.ngay_hieu_luc <= today) return { text: 'Đã có hiệu lực', cls: 'badge-ok' };
    return { text: 'Chưa có hiệu lực', cls: 'badge-warn' };
  };

  function makeCard(item) {
    const badge = statusBadge(item);
    const card = el('article', 'news-card');

    // Header
    const hd = el('div', 'news-card-head');
    hd.appendChild(el('span', 'news-type-label', item.loai));
    const st = el('span', 'badge ' + badge.cls, badge.text);
    hd.appendChild(st);
    card.appendChild(hd);

    // Số hiệu + tên
    card.appendChild(el('p', 'news-so-hieu', item.so_hieu));
    const h = el('h3'); h.textContent = item.ten; card.appendChild(h);

    // Cơ quan và ngày
    const meta = el('div', 'news-meta');
    meta.appendChild(el('span', '', '🏛 ' + item.co_quan));
    meta.appendChild(el('span', '', '📅 Ban hành: ' + fmtDate(item.ngay_ban_hanh)));
    meta.appendChild(el('span', '', '⚡ Hiệu lực: ' + fmtDate(item.ngay_hieu_luc)));
    card.appendChild(meta);

    // Tóm tắt
    card.appendChild(el('p', 'news-summary', item.tom_tat));

    // Nội dung nổi bật
    if (item.noi_bat && item.noi_bat.length) {
      const ul = el('ul', 'news-highlights');
      item.noi_bat.forEach(pt => { const li = el('li', '', pt); ul.appendChild(li); });
      card.appendChild(ul);
    }

    // Điều luật liên quan
    if (item.dieu_luat_lien_quan && item.dieu_luat_lien_quan.length) {
      const relBox = el('div', 'news-related');
      relBox.appendChild(el('span', 'news-related-label', 'Điều luật liên quan:'));
      item.dieu_luat_lien_quan.forEach(id => {
        const law = typeof LAW_DATA !== 'undefined' ? LAW_DATA.find(a => a.id === id) : null;
        const a = el('a', 'pill-link', law ? `Điều ${id}. ${law.tieu_de}` : `Điều ${id}`);
        a.href = 'dieu-luat-chi-tiet.html?id=' + id;
        relBox.appendChild(a);
      });
      card.appendChild(relBox);
    }

    // Tags
    if (item.tags && item.tags.length) {
      const tagBox = el('div', 'news-tags');
      item.tags.forEach(t => tagBox.appendChild(el('span', 'tag', '#' + t)));
      card.appendChild(tagBox);
    }

    return card;
  }

  function filter() {
    const q = (searchEl.value || '').trim().toLowerCase();
    const type = typeEl.value;
    const year = yearEl.value;

    const results = LAW_NEWS.filter(item => {
      if (type && item.loai !== type) return false;
      if (year && !item.ngay_ban_hanh.startsWith(year)) return false;
      if (q) {
        const haystack = [item.so_hieu, item.ten, item.tom_tat, item.co_quan, ...(item.tags || []), ...(item.noi_bat || [])].join(' ').toLowerCase();
        if (!haystack.includes(q)) return false;
      }
      return true;
    });

    // Sắp xếp mới nhất lên đầu
    results.sort((a, b) => b.ngay_ban_hanh.localeCompare(a.ngay_ban_hanh));

    countEl.textContent = `${results.length} văn bản` + (q ? ` phù hợp "${searchEl.value.trim()}"` : '');
    empty.hidden = results.length > 0;
    grid.replaceChildren(...results.map(makeCard));
  }

  let debounceTimer;
  searchEl.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(filter, 250); });
  typeEl.addEventListener('change', filter);
  yearEl.addEventListener('change', filter);

  filter();
})();
