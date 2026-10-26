/* js/scorer.js — Bộ chấm điểm tương thích câu hỏi ↔ điều luật (thay TF-IDF cũ trong nlu.js)
 * Không phụ thuộc thư viện ngoài. Dùng được trong trình duyệt (window.Scorer) và Node (require).
 *
 * Điểm cuối = 0.60·KHÁI NIỆM + 0.30·TỪ KHÓA(BM25) + 0.10·GẦN ĐÚNG(lỗi gõ)
 *  - KHÁI NIỆM : cụm từ đời thường → điều luật (có trọng số, có thể âm). Cụm dài được "ăn" trước
 *                nên "đánh bạc" không bao giờ bị tính là "đánh".
 *  - TỪ KHÓA   : BM25 trên tiêu đề/từ khóa/tóm tắt/các khoản, token ghép (đánh_bạc) ở cả câu hỏi và điều luật.
 *  - GẦN ĐÚNG  : khoảng cách sửa (Levenshtein) cho từ còn lại bị gõ sai.
 */
(function (root) {
  'use strict';

  /* ---------- 1. TỪ ĐIỂN KHÁI NIỆM: phrases -> { số điều: trọng số } ---------- */
  // Chỉnh/thêm ở đây là cách "dạy" hệ thống. Số điều không có trong LAW_DATA sẽ tự bị bỏ qua.
  var CONCEPTS = [
    { id: 'danh_bac', laws: { 321: 1, 322: 0.4 },
      p: ['đánh bạc', 'đánh bài ăn tiền', 'đánh bài', 'cờ bạc', 'cá độ', 'cá cược', 'sới bạc', 'xóc đĩa',
          'lô đề', 'đánh đề', 'ghi đề', 'số đề', 'chơi bài ăn tiền', 'tá lả', 'bầu cua ăn tiền'] },
    { id: 'to_chuc_bac', laws: { 322: 1, 321: 0.3 },
      p: ['tổ chức đánh bạc', 'tổ chức cá độ', 'chứa bạc', 'mở sới bạc', 'cầm cái', 'chứa chấp đánh bạc'] },
    { id: 'bao_luc', laws: { 134: 1, 138: 0.25, 123: 0.2, 135: 0.25 },
      p: ['bị đánh', 'đánh nhau', 'đánh đập', 'đánh hội đồng', 'đánh người', 'hành hung', 'bị hành hung', 'đánh', 'đấm',
          'bị đấm', 'đá', 'đạp', 'tát', 'bị tát', 'xô xát', 'ẩu đả', 'đập', 'bạo hành', 'bạo lực', 'hành hạ',
          'bầm tím', 'thương tích', 'gây thương tích', 'bị thương', 'chấn thương', 'nhập viện', 'chảy máu',
          'gãy', 'chém', 'bị chém', 'đâm', 'bị đâm', 'hung khí', 'đánh trọng thương'] },
    { id: 'giet', laws: { 123: 1, 134: 0.3, 128: 0.3 },
      p: ['giết người', 'giết', 'bị giết', 'bị đánh chết', 'bị đâm chết', 'sát hại', 'bị sát hại', 'đâm chết', 'đánh chết', 'làm chết',
          'chết người', 'tử vong', 'thiệt mạng'] },
    { id: 'vo_y_chet', laws: { 128: 1 }, p: ['vô ý làm chết người', 'sơ suất làm chết người', 'sơ suất làm chết một người', 'cẩu thả làm chết người'] },
    { id: 'kich_dong_manh', laws: { 135: 1 }, p: ['trạng thái tinh thần bị kích động mạnh', 'trạng thái kích động mạnh', 'bị kích động mạnh', 'kích động mạnh gây thương tích', 'gây thương tích khi bị kích động mạnh'] },
    { id: 'vo_y', laws: { 138: 0.9, 134: -0.6, 128: 0.2 },
      p: ['vô ý', 'không cố ý', 'sơ ý', 'lỡ tay', 'vô tình'] },
    { id: 'vo_y_thuong_tich', laws: { 138: 1, 135: 0.2 }, p: ['vô ý gây thương tích', 'sơ suất làm người khác bị thương', 'sơ suất làm bị thương', 'tai nạn làm bị thương'] },
    { id: 'de_doa_giet', laws: { 133: 1, 134: 0.1 },
      p: ['đe dọa giết', 'dọa giết người', 'dọa giết', 'đe dọa'] },
    { id: 'lam_nhuc', laws: { 155: 1, 156: 0.45 },
      p: ['làm nhục', 'chửi bới', 'bị chửi', 'chửi', 'xúc phạm', 'bị xúc phạm', 'lăng mạ', 'bôi nhọ',
          'danh dự', 'nhân phẩm', 'tung ảnh nóng'] },
    { id: 'lam_nhuc_qua_mang', laws: { 155: 1 }, p: ['bị chửi trên facebook', 'bị xúc phạm trên mạng', 'đăng bài làm nhục'] },
    { id: 'vu_khong', laws: { 156: 1, 155: 0.3 },
      p: ['vu khống', 'bịa đặt', 'tố cáo sai', 'nói xấu', 'tung tin','bị vu khống','bị nói xấu'] },
    { id: 'trom', laws: { 173: 1, 171: 0.15 },
      p: ['trộm cắp', 'ăn trộm', 'bị trộm', 'trộm', 'mất trộm', 'lấy cắp', 'mất cắp', 'móc túi',
          'mất xe', 'mất điện thoại', 'lấy trộm'] },
    { id: 'mat_tai_san', laws: { 173: 0.55, 174: 0.45, 175: 0.3 },
      p: ['mất tiền', 'mất tài sản', 'mất đồ'] },
    { id: 'lua', laws: { 174: 1, 175: 0.3 },
      p: ['lừa đảo', 'bị lừa', 'lừa tiền', 'lừa chuyển tiền', 'lừa', 'giả danh', 'chiếm đoạt tiền', 'lừa đảo qua mạng'] },
    { id: 'lam_dung_tin_nhiem', laws: { 175: 1, 174: 0.4 },
      p: ['vay tiền không trả', 'vay tiền bị quỵt', 'vay tiền bị quịt', 'bị quỵt tiền vay', 'bị quịt tiền vay','bị vay không trả',
          'vay xong bùng nợ', 'vay rồi bùng', 'mượn tiền rồi bùng', 'mượn tiền không trả', 'mượn không trả',
          'mượn tài sản rồi bỏ trốn', 'mượn xe rồi đem bán', 'mượn xe rồi bán mất', 'mượn đồ rồi đem cầm',
          'giao xe cho mượn rồi bị đem bán', 'giao xe cho người quen mượn rồi đem bán', 'mượn xe của tôi rồi bán',
          'mượn rồi họ đem bán', 'mượn rồi đem bán',
          'nhờ giữ hộ rồi chiếm đoạt', 'nhận tài sản rồi không trả', 'không trả nợ',
          'quỵt tiền', 'quịt tiền', 'bùng nợ', 'lạm dụng tín nhiệm', 'vay'] },
    { id: 'cuop', laws: { 168: 1, 171: 0.2 },
      p: ['cướp tài sản', 'bị cướp', 'cướp', 'trấn lột', 'kề dao', 'dùng dao uy hiếp', 'đe dọa lấy tiền'] },
    { id: 'giat', laws: { 171: 1, 168: 0.6 },
      p: ['bị cướp giật', 'cướp giật', 'giật túi', 'giật túi xách', 'giật điện thoại', 'giật dây chuyền', 'giật đồ', 'bị giật', 'chạy xe giật đồ'] },
    { id: 'giat_tui_cu', laws: { 168: 1 }, p: ['bị giật túi xách'] },
    { id: 'giao_thong', laws: { 260: 1, 138: 0.3, 128: 0.3 },
      p: ['tai nạn giao thông', 'gây tai nạn', 'tai nạn', 'đâm xe', 'va chạm xe', 'tông xe', 'tông chết', 'vi phạm giao thông',
          'say rượu', 'nồng độ cồn', 'lái xe', 'không bằng lái', 'vượt đèn đỏ', 'chạy quá tốc độ'] },
    { id: 'ma_tuy', laws: { 249: 1 },
      p: ['ma túy', 'tàng trữ', 'heroin', 'thuốc lắc', 'cần sa', 'chất cấm', 'chất ma túy', 'hút chích'] },
    { id: 'tham_o', laws: { 353: 1 },
      p: ['tham ô', 'tham nhũng', 'biển thủ', 'công quỹ', 'ngân sách', 'thủ quỹ', 'lợi dụng chức vụ', 'quỹ nhà nước',
          'tiền nhà nước', 'tài sản nhà nước'] }
  ];
  // Từ đa nghĩa khi không dấu (đá→da, đập→dap, đạp→dap): chỉ nhận ở chế độ có dấu.
  var DIAC_ONLY = { 'đá': 1, 'đập': 1, 'đạp': 1 };

  var STOP_RAW = ['tôi', 'mình', 'em', 'anh', 'chị', 'bị', 'được', 'là', 'và', 'có', 'không', 'cho', 'của', 'thì', 'mà',
    'đã', 'đang', 'vừa', 'rồi', 'một', 'những', 'các', 'này', 'khi', 'ở', 'với', 'để', 'về', 'gì', 'nào', 'sao', 'thế',
    'nên', 'hay', 'ạ', 'nhé', 'ơi', 'xin', 'hỏi', 'cần', 'muốn', 'bởi', 'vì', 'do', 'nếu', 'như', 'bao', 'nhiêu', 'phải',
    'làm', 'ra', 'vào', 'lại', 'cũng', 'đó', 'người', 'ta', 'thì', 'bên', 'trong', 'tại', 'bằng', 'từ', 'đến', 'sẽ'];

  /* ---------- 2. Tiện ích văn bản ---------- */
  function fold(s, plain) {
    s = String(s == null ? '' : s).normalize('NFC').toLowerCase();
    if (plain) s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
    return s;
  }
  function split(s) { return (s.match(/[\p{L}\p{N}%]+/gu) || []); }
  function hasDiacritics(s) { return /[^\u0000-\u007f]/.test(String(s).normalize('NFC')); }
  function toText(x) { return Array.isArray(x) ? x.map(toText).join(' ') : (x == null ? '' : String(x)); }
  function lev(a, b, max) {
    if (Math.abs(a.length - b.length) > max) return max + 1;
    var prev = [], cur, i, j;
    for (j = 0; j <= b.length; j++) prev[j] = j;
    for (i = 1; i <= a.length; i++) {
      cur = [i];
      for (j = 1; j <= b.length; j++)
        cur[j] = Math.min(prev[j] + 1, cur[j - 1] + 1, prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
      prev = cur;
    }
    return prev[b.length];
  }

  /* ---------- 3. Từ điển theo chế độ (có dấu / không dấu) ---------- */
  var LEX = {}; // mode -> { map: phrase -> {cid, laws}, maxLen, stop:Set }
  function buildLex(plain) {
    var map = {}, maxLen = 1, stop = {};
    STOP_RAW.forEach(function (w) { stop[fold(w, plain)] = 1; });
    CONCEPTS.forEach(function (c) {
      c.p.forEach(function (ph) {
        if (plain && DIAC_ONLY[ph]) return;
        var k = split(fold(ph, plain)).join(' ');
        map[k] = { cid: c.id, laws: c.laws };
        maxLen = Math.max(maxLen, k.split(' ').length);
      });
    });
    return { map: map, maxLen: maxLen, stop: stop };
  }
  function lex(plain) { var k = plain ? 'p' : 'd'; return LEX[k] || (LEX[k] = buildLex(plain)); }

  // Phân đoạn "cụm dài ăn trước": trả về danh sách phần tử {t, phrase?, cid?, laws?}
  function segment(text, plain) {
    var L = lex(plain), toks = split(fold(text, plain)), out = [], i = 0, n, key, hit;
    while (i < toks.length) {
      hit = null;
      for (n = Math.min(L.maxLen, toks.length - i); n >= 1; n--) {
        key = toks.slice(i, i + n).join(' ');
        if (L.map[key]) { hit = { n: n, key: key, e: L.map[key] }; break; }
      }
      if (hit) { out.push({ t: hit.key.replace(/ /g, '_'), phrase: hit.key, cid: hit.e.cid, laws: hit.e.laws }); i += hit.n; }
      else { out.push({ t: toks[i] }); i++; }
    }
    return out;
  }

  /* ---------- 4. Chỉ mục BM25 ---------- */
  function articleKey(a) {
    var m = String(a.id == null ? '' : a.id).match(/\d+/) || String(a.tieu_de || '').match(/\d+/);
    return m ? m[0] : String(a.id);
  }
  function docText(a) {
    var khoan = (a.khoan || []).map(function (k) { return [k.dieu_kien, k.noi_dung, k.tu_khoa]; });
    return { head: toText([a.tieu_de, a.tieu_de, a.tieu_de, a.tu_khoa, a.tu_khoa]), body: toText([a.tom_tat, khoan]) };
  }
  function buildIndex(data, plain) {
    var L = lex(plain), docs = [], df = {}, total = 0;
    data.forEach(function (a) {
      var d = docText(a), tf = {}, len = 0;
      segment(d.head + ' ' + d.body, plain).forEach(function (s) {
        if (L.stop[s.t] || /^\d/.test(s.t)) return;
        tf[s.t] = (tf[s.t] || 0) + 1; len++;
      });
      // trọng số phần đầu (tiêu đề, từ khóa) đã được nhân bản trong head
      Object.keys(tf).forEach(function (t) { df[t] = (df[t] || 0) + 1; });
      docs.push({ a: a, key: articleKey(a), tf: tf, len: len, vocab: Object.keys(tf) });
      total += len;
    });
    return { docs: docs, df: df, avg: total / Math.max(1, docs.length), N: docs.length };
  }
  function bm25(idx, doc, qTerms) {
    var k1 = 1.2, b = 0.75, s = 0;
    qTerms.forEach(function (t) {
      var f = doc.tf[t]; if (!f) return;
      var idf = Math.log(1 + (idx.N - (idx.df[t] || 0) + 0.5) / ((idx.df[t] || 0) + 0.5));
      s += idf * (f * (k1 + 1)) / (f + k1 * (1 - b + b * doc.len / idx.avg));
    });
    return s;
  }

  var CACHE = { data: null, d: null, p: null };
  function getIndex(data, plain) {
    if (CACHE.data !== data) { CACHE = { data: data, d: null, p: null }; }
    var k = plain ? 'p' : 'd';
    return CACHE[k] || (CACHE[k] = buildIndex(data, plain));
  }

  /* ---------- 5. Vai trò người hỏi ---------- */
  function detectRole(q) {
    var s = fold(q, false);
    if (/bị\s+(bắt|phạt|khởi tố|truy tố|tạm giữ|triệu tập|công an|kiện)/.test(s)) return 'nguoi_vi_pham';
    if (/(tôi|mình|em|con tôi|chồng tôi|vợ tôi|bạn tôi)\s+(đã\s+|vừa\s+|lỡ\s+|có\s+)?(đánh|giết|trộm|lấy|chửi|lừa|cướp|đâm|tông|chém|đấm|tát)/.test(s)) return 'nguoi_vi_pham';
    if (/\bbị\b|nạn nhân|người nhà|gia đình tôi|con tôi bị|bố mẹ tôi/.test(s)) return 'nan_nhan';
    return 'chua_ro';
  }

  /* ---------- 6. Chấm điểm ---------- */
  var W = { concept: 0.60, lexical: 0.30, fuzzy: 0.10 };
  var MIN_SCORE = 0.20, MARGIN = 0.10;

  function rank(query, data, opts) {
    opts = opts || {};
    var plain = !hasDiacritics(query), L = lex(plain), idx = getIndex(data, plain);
    var seg = segment(query, plain);

    // gõ sai: từ đơn chưa nhận diện mà gần giống (≤1–2 ký tự) một từ đơn trong từ điển -> coi như trúng, giảm 30% trọng số
    var singles = Object.keys(L.map).filter(function (k) { return k.indexOf(' ') < 0 && k.length >= 4; });
    seg.forEach(function (s) {
      if (s.cid || L.stop[s.t] || s.t.length < 4 || /^\d/.test(s.t)) return;
      var max = s.t.length >= 7 ? 2 : 1, best = null, bd = 9;
      singles.forEach(function (k) { if (k[0] !== s.t[0]) return; var d = lev(s.t, k, max); if (d <= max && d < bd) { bd = d; best = k; } });
      if (best) { var e = L.map[best], sc = {}; Object.keys(e.laws).forEach(function (n) { sc[n] = e.laws[n] * 0.7; });
        s.cid = e.cid + '~'; s.laws = sc; s.phrase = s.t + '≈' + best; }
    });
    // khái niệm: gom số lần trúng theo concept
    var concepts = {};
    seg.forEach(function (s) {
      if (!s.cid) return;
      var c = concepts[s.cid] || (concepts[s.cid] = { laws: s.laws, hits: [] });
      c.hits.push(s.phrase);
    });
    // từ khóa
    var qTerms = [], leftovers = [];
    seg.forEach(function (s) {
      if (L.stop[s.t] || /^\d/.test(s.t)) return;
      if (qTerms.indexOf(s.t) < 0) qTerms.push(s.t);
      if (!s.cid && s.t.length >= 4) leftovers.push(s.t);
    });

    var results = idx.docs.map(function (doc) {
      var raw = 0, why = [];
      Object.keys(concepts).forEach(function (cid) {
        var c = concepts[cid], w = c.laws[doc.key];
        if (!w) return;
        var v = w * (1 + 0.25 * (c.hits.length - 1));
        raw += v; why.push(c.hits[0] + (c.hits.length > 1 ? ' (+' + (c.hits.length - 1) + ')' : '') + ' → ' + v.toFixed(2));
      });
      var C = raw > 0 ? 1 - Math.exp(-1.2 * raw) : 0;
      var s = bm25(idx, doc, qTerms), Lx = s / (s + 3);
      var fz = 0, vocab = doc.vocab;
      leftovers.forEach(function (t) {
        if (doc.tf[t]) return;
        var max = t.length >= 7 ? 2 : 1;
        for (var i = 0; i < vocab.length; i++) if (lev(t, vocab[i], max) <= max) { fz++; return; }
      });
      var F = leftovers.length ? Math.min(1, fz / leftovers.length) : 0;
      var score = W.concept * C + W.lexical * Lx + W.fuzzy * F;
      return { id: doc.a.id, key: doc.key, article: doc.a, score: score,
               parts: { concept: C, lexical: Lx, fuzzy: F }, why: why };
    }).sort(function (a, b) { return b.score - a.score; });

    var top = results[0], second = results[1], status = 'ok';
    // A specific concept must not fall back to a different, merely related
    // article when its strongest target has not been loaded yet.
    var missingTarget = null;
    Object.keys(concepts).some(function (cid) {
      var laws = concepts[cid].laws, targets = Object.keys(laws).sort(function (a, b) { return laws[b] - laws[a]; });
      if (!targets.length) return false;
      var target = targets[0], present = idx.docs.some(function (doc) { return doc.key === target; });
      // F04 covers the high-risk theft, embezzlement-of-trust, and defamation
      // concepts; preserve legacy mappings such as the existing robbery case.
      if (!present && laws[target] >= 0.8 && { 156: 1, 173: 1, 175: 1 }[target]) { missingTarget = target; return true; }
      return false;
    });
    if (!top || top.score < (opts.minScore || MIN_SCORE)) status = 'none';
    else if (second && second.score >= MIN_SCORE && top.score - second.score < (opts.margin || MARGIN)) status = 'ambiguous';
    if (missingTarget) status = 'none';
    return { query: query, mode: plain ? 'khong_dau' : 'co_dau', role: detectRole(query), status: status,
             results: results, concepts: Object.keys(concepts), missingTarget: missingTarget };
  }

  function explain(r, n) {
    return r.results.slice(0, n || 3).map(function (x) {
      return 'Điều ' + x.key + '  ' + x.score.toFixed(3) + '  [KN ' + x.parts.concept.toFixed(2) + ' | TK ' +
        x.parts.lexical.toFixed(2) + ' | GĐ ' + x.parts.fuzzy.toFixed(2) + ']  ' + x.why.join('; ');
    }).join('\n');
  }

  var Scorer = { rank: rank, explain: explain, detectRole: detectRole, CONCEPTS: CONCEPTS, WEIGHTS: W };
  if (typeof module !== 'undefined' && module.exports) module.exports = Scorer; else root.Scorer = Scorer;
})(typeof window !== 'undefined' ? window : this);
