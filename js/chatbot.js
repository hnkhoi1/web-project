(() => {
  const { user, $, el, toast } = HS; if (!user) return;
  const log = $('#chat-log'), form = $('#chat-form'), input = $('#chat-input');
  const SUG = ['Tôi bị đánh gây thương tích 20% thì được bồi thường gì?', 'Bị lừa chuyển khoản mất 80 triệu đồng, tôi đòi lại bằng cách nào?',
    'Lái xe say rượu gây tai nạn làm chết 1 người thì bị phạt bao nhiêu năm tù?', 'Nhân viên nhà nước lấy 300 triệu tiền công quỹ thì phạm tội gì?', 'Tàng trữ ma túy trong người bị xử lý ra sao?'];
  const scroll = () => { log.scrollTop = log.scrollHeight; };
  const addMsg = (cls, text) => { const m = el('div', 'msg ' + cls, text); log.appendChild(m); return m; };
  const para = (parent, label, text) => { const p = el('p'); if (label) p.appendChild(el('b', '', label + ' ')); p.append(text); parent.appendChild(p); };

  function showAnswer(a) {
    const m = addMsg('bot');
    if (!a.found) {
      para(m, '', 'Mình chưa tìm được điều luật phù hợp với mô tả này. Bạn thử nêu rõ hành vi (ví dụ: trộm xe máy, lừa đảo qua mạng, đánh người gây thương tích) hoặc dùng trang Tra cứu nhé.');
      const l = el('a', '', 'Mở trang Tra cứu'); l.href = 'tra-cuu.html'; m.appendChild(l); return;
    }
    if (a.mode === 'overview') {
      para(m, '', `Tình huống của bạn có thể liên quan đến Điều ${a.article.id}. ${a.article.tieu_de} (${a.article.chuong}). Mức xử lý phụ thuộc mức độ hậu quả:`);
      a.khoan_all.forEach((k) => para(m, `Khoản ${k.so_khoan} (${k.dieu_kien}):`, k.noi_dung));
      para(m, 'Để nêu đúng quyền lợi:', a.ask);
      if (a.ghi_chu_quyen_loi) para(m, 'Về quyền lợi/bồi thường:', a.ghi_chu_quyen_loi);
    } else {
      para(m, '', `Tình huống của bạn liên quan đến Điều ${a.article.id}. ${a.article.tieu_de} (${a.article.chuong}), cụ thể Khoản ${a.khoan.so_khoan} — ${a.khoan.dieu_kien}.`);
      const rights = () => { if (a.quyen_loi) para(m, 'Quyền lợi của bạn (người bị hại):', a.quyen_loi + (a.can_cu ? ` (${a.can_cu})` : ''));
        else if (a.ghi_chu_quyen_loi) para(m, 'Về quyền lợi/bồi thường:', a.ghi_chu_quyen_loi); };
      if (a.role === 'nan_nhan') rights();   // người bị hại: nêu quyền lợi trước
      para(m, 'Mức xử lý với người vi phạm:', a.khoan.noi_dung);
      if (a.role !== 'nan_nhan') rights();
    }
    if (a.alt) { const p = el('p'); p.append('Có thể bạn muốn hỏi: '); const l2 = el('a', '', `Điều ${a.alt.id}. ${a.alt.tieu_de}`); l2.href = 'dieu-luat-chi-tiet.html?id=' + a.alt.id; p.appendChild(l2); m.appendChild(p); }
    const cite = el('div', 'cite'), l = el('a', '', `Xem chi tiết Điều ${a.article.id}`); l.href = 'dieu-luat-chi-tiet.html?id=' + a.article.id; cite.appendChild(l);
    const save = el('button', 'btn btn-ghost btn-sm', '+ Lưu vào ghi chú'); save.type = 'button';
    save.addEventListener('click', () => {
      try { DB.saveNote(user.id, { title: a.note_title, content: a.note_text, source: { type: 'chat', ref: a.article.id } }); }
      catch (e) { return toast(e.message, 'err'); }
      save.disabled = true; save.textContent = '✓ Đã lưu'; toast('Đã lưu vào ghi chú.');
    });
    cite.appendChild(save); m.appendChild(cite);
  }

  function ask(q) {
    q = q.trim().slice(0, 500); if (!q) return;
    addMsg('user', q); input.value = ''; input.disabled = true;
    const t = el('div', 'typing', 'Đang phân tích tình huống...'); log.appendChild(t); scroll();
    setTimeout(() => { t.remove(); showAnswer(Law.answer(q)); input.disabled = false; scroll(); input.focus(); }, 450);
  }
  form.addEventListener('submit', (e) => { e.preventDefault(); ask(input.value); });
  $('#chips').replaceChildren(...SUG.map((s) => { const b = el('button', 'suggestion', s); b.type = 'button'; b.addEventListener('click', () => ask(s)); return b; }));
  addMsg('bot', 'Xin chào! Hãy mô tả tình huống của bạn, mình sẽ chỉ ra điều luật áp dụng, mức xử lý và quyền lợi của bạn.');
})();
