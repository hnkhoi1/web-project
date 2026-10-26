(() => {
  const { user, $, el, toast } = HS; if (!user) return;
  const box = $('#detail'), a = Law.byId(+new URLSearchParams(location.search).get('id'));
  if (!a) {
    const d = el('div', 'empty-state'); d.appendChild(el('p')).appendChild(el('strong', '', 'Không tìm thấy điều luật.'));
    const p = el('p'); const l = el('a', '', '← Quay lại tra cứu'); l.href = 'tra-cuu.html'; p.appendChild(l); d.appendChild(p); box.appendChild(d); return;
  }
  document.title = `Điều ${a.id} — Hình Sự Tra Cứu`;
  let noteText = `${a.tom_tat}\nKhung hình phạt: ${a.khung_hinh_phat}`;
  a.khoan.forEach((k) => { noteText += `\n\nKhoản ${k.so_khoan} (${k.dieu_kien}): ${k.noi_dung}`; if (k.quyen_loi) noteText += `\nQuyền lợi: ${k.quyen_loi}`; });
  if (a.can_cu_boi_thuong) noteText += `\n\nCăn cứ bồi thường: ${a.can_cu_boi_thuong}`;

  async function saveLawNote(note) {
    if (typeof DB.ready === 'function') { await DB.ready(); return DB.saveNote(note); }
    return DB.saveNote(user.id, note); // TODO(T10): compatibility with the current local adapter
  }

  const head = el('div', 'article-head'), bc = el('p', 'breadcrumb'), back = el('a', '', 'Tra cứu'); back.href = 'tra-cuu.html';
  bc.append(back, ' › ' + a.chuong); head.appendChild(bc);
  head.appendChild(el('h1', '', `Điều ${a.id}. ${a.tieu_de}`));
  const meta = el('div', 'article-meta'); meta.append(el('span', 'pill', a.chuong), el('span', 'penalty', a.khung_hinh_phat)); head.appendChild(meta);
  const save = el('button', 'btn btn-ghost btn-sm', '+ Lưu vào ghi chú'); save.type = 'button';
  save.addEventListener('click', async () => {
    try { await saveLawNote({ title: `Điều ${a.id}. ${a.tieu_de}`, content: noteText.slice(0, 5000), source: { type: 'article', ref: a.id } }); }
    catch (e) { return toast(e.message, 'err'); }
    save.disabled = true; save.textContent = '✓ Đã lưu vào ghi chú'; toast('Đã lưu vào ghi chú.');
  });
  head.appendChild(el('p')).appendChild(save);

  const body = el('div', 'article-body'), main = el('div'); main.appendChild(el('p', '', a.tom_tat));
  const bold = (label, text) => { const p = el('p'); p.append(el('b', '', label + ' '), text); return p; };
  a.khoan.forEach((k) => {
    const d = el('div', 'khoan'); const p1 = el('p'); p1.append(el('b', '', `Khoản ${k.so_khoan}.`), ' ', el('em', '', k.dieu_kien));
    d.append(p1, bold('Mức xử lý:', k.noi_dung));
    if (k.quyen_loi) { const r = el('div', 'rights'); r.append(el('b', '', 'Quyền lợi của người bị hại: '), k.quyen_loi); d.appendChild(r); }
    main.appendChild(d);
  });
  if (a.ghi_chu_quyen_loi) { const r = el('div', 'rights'); r.append(el('b', '', 'Về quyền lợi/bồi thường: '), a.ghi_chu_quyen_loi); main.appendChild(r); }
  const side = el('aside');
  if (a.can_cu_boi_thuong) { const s = el('div', 'sidebar-box'); s.append(el('h4', '', 'Căn cứ bồi thường'), el('p', '', a.can_cu_boi_thuong)); side.appendChild(s); }
  const lq = Law.lienQuan(a);
  if (lq.length) {
    const s = el('div', 'sidebar-box'), ul = el('ul'); s.appendChild(el('h4', '', 'Điều luật liên quan'));
    lq.forEach((l) => { const li = el('li'), x = el('a', '', `Điều ${l.id}. ${l.tieu_de}`); x.href = 'dieu-luat-chi-tiet.html?id=' + l.id; li.appendChild(x); ul.appendChild(li); });
    s.appendChild(ul); side.appendChild(s);
  }
  body.append(main, side); box.append(head, body);
})();
