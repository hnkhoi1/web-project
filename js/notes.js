(() => {
  const { user, $, el, fmtTime, toast } = HS; if (!user) return;
  $('#greet').textContent = 'Xin chào, ' + user.displayName;
  const grid = $('#notes-grid'), empty = $('#notes-empty'), search = $('#notes-q');
  const dlg = $('#note-dialog'), form = $('#note-form'), cdlg = $('#confirm-dialog');
  let pendingDelete = 0, timer;
  const open = (d) => (d.showModal ? d.showModal() : d.setAttribute('open', ''));
  const close = (d) => (d.close ? d.close() : d.removeAttribute('open'));

  // Điều luật nổi bật (dữ liệu từ data.js)
  $('#popular').replaceChildren(...LAW_DATA.slice(0, 6).map((a) => {
    const c = el('div', 'card'); c.appendChild(el('div', 'num', 'Điều ' + a.id));
    const h = el('h3'); const l = el('a', '', a.tieu_de); l.href = 'dieu-luat-chi-tiet.html?id=' + a.id; h.appendChild(l); c.appendChild(h);
    c.appendChild(el('p', '', a.tom_tat)); c.appendChild(el('span', 'penalty', a.khung_hinh_phat)); return c;
  }));

  function card(n) {
    const c = el('article', 'note'); c.dataset.color = n.color;
    c.appendChild(el('h3', '', (n.pinned ? '📌 ' : '') + n.title));
    if (n.content) { const b = el('p', 'note-body', n.content); b.addEventListener('click', () => b.classList.toggle('open')); c.appendChild(b); }
    const meta = el('div', 'note-meta'); meta.appendChild(el('span', '', fmtTime(n.updatedAt)));
    if (n.source?.type === 'article') { const a = el('a', '', 'Điều ' + n.source.ref); a.href = 'dieu-luat-chi-tiet.html?id=' + n.source.ref; meta.appendChild(a); }
    else if (n.source?.type === 'chat') meta.appendChild(el('span', '', 'Từ chatbot'));
    c.appendChild(meta);
    const act = el('div', 'note-actions');
    const mk = (label, fn, aria) => { const b = el('button', '', label); b.type = 'button'; b.setAttribute('aria-label', aria); b.addEventListener('click', fn); act.appendChild(b); };
    mk(n.pinned ? 'Bỏ ghim' : 'Ghim', () => { DB.pinNote(user.id, n.id, !n.pinned); render(); }, (n.pinned ? 'Bỏ ghim ' : 'Ghim ') + n.title);
    mk('Sửa', () => edit(n), 'Sửa ghi chú ' + n.title);
    mk('Xóa', () => { pendingDelete = n.id; open(cdlg); }, 'Xóa ghi chú ' + n.title);
    c.appendChild(act); return c;
  }
  function render() {
    const notes = DB.listNotes(user.id, search.value);
    grid.replaceChildren(...notes.map(card));
    empty.hidden = notes.length > 0;
    const isSearching = !!search.value.trim();
    empty.querySelector('p:first-child').textContent = isSearching
      ? `Không có ghi chú nào khớp "${search.value}".`
      : 'Chưa có ghi chú nào.';
    const hint = empty.querySelector('p:last-child');
    if (hint && hint !== empty.querySelector('p:first-child')) hint.hidden = isSearching;
  }
  function edit(n) {
    $('#note-dialog-title').textContent = n ? 'Sửa ghi chú' : 'Ghi chú mới';
    $('#note-id').value = n ? n.id : ''; $('#note-title').value = n ? n.title : ''; $('#note-content').value = n ? n.content : '';
    $('#note-pinned').checked = !!n && n.pinned;
    form.querySelector(`input[name="note-color"][value="${n ? n.color : 'auto'}"]`).checked = true;
    $('#note-err').hidden = true; open(dlg); $('#note-title').focus();
  }
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    try {
      DB.saveNote(user.id, { id: Number($('#note-id').value) || 0, title: $('#note-title').value, content: $('#note-content').value,
        color: form.querySelector('input[name="note-color"]:checked').value, pinned: $('#note-pinned').checked });
    } catch (err) { $('#note-err').textContent = err.message; $('#note-err').hidden = false; return; }
    close(dlg); toast('Đã lưu ghi chú.'); render();
  });
  $('#note-cancel').addEventListener('click', () => close(dlg));
  $('#note-new').addEventListener('click', () => edit(null));
  $('#confirm-no').addEventListener('click', () => close(cdlg));
  $('#confirm-yes').addEventListener('click', () => { close(cdlg); DB.deleteNote(user.id, pendingDelete); toast('Đã xóa ghi chú.'); render(); });
  search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(render, 200); });

  // Chuyển đổi hiển thị lưới (mặc định) / danh sách, nhớ lựa chọn riêng cho từng người dùng
  const VIEW_KEY = 'hs-notes-view-' + user.id;
  const viewBtns = document.querySelectorAll('.view-btn');
  function readView() {
    try { return localStorage.getItem(VIEW_KEY) === 'list' ? 'list' : 'grid'; } catch { return 'grid'; }
  }
  function applyView(view, save) {
    grid.classList.toggle('is-list', view === 'list');
    viewBtns.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.view === view)));
    if (save) { try { localStorage.setItem(VIEW_KEY, view); } catch { /* chế độ riêng tư: bỏ qua */ } }
  }
  viewBtns.forEach((b) => b.addEventListener('click', () => {
    if (b.getAttribute('aria-pressed') === 'true') return;
    applyView(b.dataset.view, true);
    toast(b.dataset.view === 'list' ? 'Đã chuyển sang dạng danh sách.' : 'Đã chuyển sang dạng lưới.');
  }));
  applyView(readView(), false);

  render();
})();
