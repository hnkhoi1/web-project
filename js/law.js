/* Tra cứu + chatbot phía trình duyệt (dùng LAW_DATA trong data.js và TF-IDF trong nlu.js). */
const Law = (() => {
  const all = LAW_DATA;
  const byId = (id) => all.find((a) => a.id === id) || null;
  const chuongList = () => [...new Set(all.map((a) => a.chuong))];
  const lienQuan = (a, n = 4) => all.filter((r) => r.chuong === a.chuong && r.id !== a.id).slice(0, n);

  function search(q, chuong = '') {
    let pool = chuong ? all.filter((a) => a.chuong === chuong) : all;
    q = (q || '').trim();
    if (!q) return pool;
    const m = q.match(/^(?:điều|dieu)?\s*(\d{1,3})$/i);
    if (m) { const hit = pool.find((a) => a.id === +m[1]); if (hit) return [{ ...hit, score: 1 }]; }
    const ranked = Scorer.rank(q, pool).results.filter((r) => r.score > 0.08).slice(0, 15).map((r) => ({ ...r.article, score: r.score }));
    if (ranked.length) return ranked;
    const needle = vnNormalize(q);
    return pool.filter((a) => vnNormalize(a.tieu_de + ' ' + a.tu_khoa).includes(needle));
  }

  function extractSoLieu(text) {
    const t = text.toLowerCase(); let ti_le = null, gia_tri = null, so_nguoi_chet = null, m;
    if ((m = t.match(/(\d{1,3})\s*%/))) ti_le = +m[1];
    if ((m = t.match(/(\d+(?:[.,]\d+)?)\s*t(?:ỷ|ỉ|y)(?!\p{L})/u))) gia_tri = parseFloat(m[1].replace(',', '.')) * 1000;
    else if ((m = t.match(/(\d+(?:[.,]\d+)?)\s*tri[eệ]u/))) gia_tri = parseFloat(m[1].replace(',', '.'));
    if ((m = t.match(/ch[eế]t\s*(\d+)\s*ng[uư][oơ]?i/) || t.match(/(\d+)\s*ng[uư][oơ]?i\s*ch[eế]t/))) so_nguoi_chet = +m[1];
    return { ti_le, gia_tri, so_nguoi_chet };
  }

  function pickKhoan(article, question, sl) {
    const inRange = (v, mn, mx) => (mn == null || v >= mn) && (mx == null || v <= mx);
    if (sl.ti_le != null || sl.gia_tri != null || sl.so_nguoi_chet != null) {
      const matches = article.khoan.filter((k) => {
        let any = false, ok = true;
        if (sl.ti_le != null && (k.ti_le_ton_thuong_min != null || k.ti_le_ton_thuong_max != null)) { any = true; if (!inRange(sl.ti_le, k.ti_le_ton_thuong_min, k.ti_le_ton_thuong_max)) ok = false; }
        if (sl.gia_tri != null && (k.gia_tri_tai_san_min != null || k.gia_tri_tai_san_max != null)) { any = true; if (!inRange(sl.gia_tri, k.gia_tri_tai_san_min, k.gia_tri_tai_san_max)) ok = false; }
        if (sl.so_nguoi_chet != null && (k.so_nguoi_chet_min != null || k.so_nguoi_chet_max != null)) {
          any = true; if (!inRange(sl.so_nguoi_chet, k.so_nguoi_chet_min, k.so_nguoi_chet_max)) ok = false;
        }
        return any && ok;
      });
      if (matches.length) {
        const span = (k) => {
          let s = 0; const add = (mn, mx) => { s += mn == null || mx == null ? 1e6 : mx - mn; };
          if (k.ti_le_ton_thuong_min != null || k.ti_le_ton_thuong_max != null) add(k.ti_le_ton_thuong_min, k.ti_le_ton_thuong_max);
          if (k.gia_tri_tai_san_min != null || k.gia_tri_tai_san_max != null) add(k.gia_tri_tai_san_min, k.gia_tri_tai_san_max);
          return s;
        };
        return matches.sort((a, b) => span(a) - span(b))[0];
      }
      return null; // Không đoán khoản khi số liệu người dùng nêu không khớp dữ liệu.
    }
    const idx = buildIndex(article.khoan.map((k, i) => ({ id: i, text: `${k.tu_khoa} ${k.dieu_kien} ${k.noi_dung}` })));
    const r = semanticSearch(question, idx, 1);
    return r.length ? article.khoan[r[0].doc.id] : article.khoan[0];
  }

  // Chatbot: chấm điểm bằng Scorer (khái niệm + BM25 + gần đúng) — xem js/scorer.js
  function answer(question) {
    const r = Scorer.rank(question, all);
    if (r.status === 'none') return { found: false };
    const top = r.results[0], a = top.article, sl = extractSoLieu(question);
    const alt = r.status === 'ambiguous' ? { id: r.results[1].article.id, tieu_de: r.results[1].article.tieu_de } : null;
    const quant = a.khoan.some((k) => k.ti_le_ton_thuong_min != null || k.gia_tri_tai_san_min != null || k.so_nguoi_chet_min != null);
    const noFig = sl.ti_le == null && sl.gia_tri == null && sl.so_nguoi_chet == null;
    const hasFollowUp = a.khoan.some((k) => k.cau_hoi_lam_ro);
    const picked = noFig ? null : pickKhoan(a, question, sl);
    const ask = a.khoan.find((k) => k.cau_hoi_lam_ro)?.cau_hoi_lam_ro ||
      (a.khoan.some((k) => k.ti_le_ton_thuong_min != null)
        ? 'Bạn cho mình biết tỉ lệ tổn thương cơ thể (%, theo giám định) hoặc mức độ thương tích để mình nêu đúng khoản và quyền lợi nhé.'
        : 'Bạn cho mình biết giá trị tài sản (triệu đồng) hoặc hậu quả cụ thể (số người chết...) để mình nêu đúng khoản nhé.');
    const base = { found: true, score: top.score, role: r.role, alt, debug: Scorer.explain(r, 3),
      article: { id: a.id, tieu_de: a.tieu_de, chuong: a.chuong } };
    // Chưa có số liệu để chọn khoản: nêu tổng quan các khoản + hỏi thêm, tránh chọn bừa khoản 1
    if ((quant && noFig && !/(vô ý|lỡ tay|say rượu|hung khí)/i.test(question) || hasFollowUp && noFig || !noFig && !picked) && a.khoan.length > 1) {
      const khoan_all = a.khoan.map((k) => ({ so_khoan: k.so_khoan, dieu_kien: k.dieu_kien, noi_dung: k.noi_dung, cau_hoi_lam_ro: k.cau_hoi_lam_ro || null }));
      let text = `Tình huống: ${question}\nĐiều ${a.id}. ${a.tieu_de}\n\n` + khoan_all.map((k) => `Khoản ${k.so_khoan} (${k.dieu_kien}): ${k.noi_dung}`).join('\n') + `\n\n${ask}\n\n(Tham khảo từ dữ liệu demo rút gọn, không thay thế tư vấn của luật sư.)`;
      return { ...base, mode: 'overview', khoan_all, ask, ghi_chu_quyen_loi: a.ghi_chu_quyen_loi || null, can_cu: a.can_cu_boi_thuong || null,
        note_title: `Điều ${a.id} — ${a.tieu_de}`.slice(0, 120), note_text: text.slice(0, 5000) };
    }
    const k = picked || pickKhoan(a, question, sl);
    const quyenLoi = k.quyen_loi || null, ghiChu = quyenLoi ? null : a.ghi_chu_quyen_loi || null, canCu = quyenLoi ? a.can_cu_boi_thuong || null : null;
    let text = `Tình huống: ${question}\nĐiều ${a.id}. ${a.tieu_de} — Khoản ${k.so_khoan} (${k.dieu_kien})\n\nMức xử lý với người vi phạm: ${k.noi_dung}`;
    if (quyenLoi) text += `\n\nQuyền lợi của người bị hại: ${quyenLoi}${canCu ? ` (${canCu})` : ''}`;
    else if (ghiChu) text += `\n\nVề quyền lợi/bồi thường: ${ghiChu}`;
    text += '\n\n(Tham khảo từ dữ liệu demo rút gọn, không thay thế tư vấn của luật sư.)';
    return { ...base, mode: 'specific',
      khoan: { so_khoan: k.so_khoan, dieu_kien: k.dieu_kien, noi_dung: k.noi_dung }, quyen_loi: quyenLoi, can_cu: canCu, ghi_chu_quyen_loi: ghiChu,
      note_title: `Điều ${a.id}, Khoản ${k.so_khoan} — ${a.tieu_de}`.slice(0, 120), note_text: text.slice(0, 5000) };
  }
  return { all, byId, chuongList, lienQuan, search, answer };
})();
if (typeof window !== 'undefined') window.Law = Law;
