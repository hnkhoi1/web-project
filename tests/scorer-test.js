// Chạy trong Node.js hoặc được nạp bởi tests/run-in-browser.html.
const isNodeScorer = typeof module !== 'undefined' && !!module.exports;
if (isNodeScorer) {
  const fs = require('fs'), path = require('path'); global.window = global;
  eval(fs.readFileSync(path.join(__dirname, '../js/data.js'), 'utf8') + ';global.LAW_DATA=LAW_DATA');
  global.Scorer = require('../js/scorer.js');
  eval(fs.readFileSync(path.join(__dirname, '../js/law.js'), 'utf8') + ';global.Law=Law');
}

function runScorerTests() {
const scorer = isNodeScorer ? global.Scorer : window.Scorer;
const lawApi = isNodeScorer ? global.Law : window.Law;
const checkLog = (line) => { console.log(line); if (typeof window.HS_TEST_REPORT === 'function') window.HS_TEST_REPORT(line); };
const C = [
  ['tôi bị đánh', 134], ['bị đánh', 134], ['toi bi danh', 134], ['tôi bị người ta đánh nhập viện', 134],
  ['Tôi bị đánh gây thương tích 20% thì được bồi thường gì?', 134], ['bạn tôi bị hành hung', 134],
  ['tôi đánh bạc bị công an bắt', 321], ['toi danh bac an tien', 321], ['chơi cá độ bóng đá', 321],
  ['tôi bị đánh chết', 123], ['người thân bị giết', 123],
  ['Bị lừa chuyển khoản mất 80 triệu đồng, tôi đòi lại bằng cách nào?', 174], ['toi bi lua tien qua mang', 174],
  ['Lái xe say rượu gây tai nạn làm chết 1 người thì bị phạt bao nhiêu năm tù?', 260],
  ['Nhân viên nhà nước lấy 300 triệu tiền công quỹ thì phạm tội gì?', 353],
  ['Tàng trữ ma túy trong người bị xử lý ra sao?', 249], ['bị giật túi xách trên đường', 168], ['bị cướp dao kề cổ', 168],
  ['vay tiền bị quịt thì phải làm sao', 175], ['người quen vay tiền rồi bùng nợ', 175],
  ['mượn tiền xong chặn liên lạc để khỏi trả', 175], ['tôi giao xe cho người quen mượn rồi họ đem bán', 175],
  ['bị giật mất điện thoại khi đang đi bộ', 171], ['ai đó lén lấy trộm chiếc xe máy của tôi', 173],
  ['người ta dựng chuyện sai sự thật để hại danh tiếng tôi', 156], ['họ đăng lời lẽ xúc phạm tôi lên mạng xã hội', 155],
];
const added = [
  ['vô ý làm chết người do sơ suất', 128], ['sơ suất làm chết một người', 128],
  ['đe dọa giết tôi', 133], ['dọa giết người qua tin nhắn', 133],
  ['cố ý gây thương tích khi bị kích động mạnh', 135], ['đánh người trong trạng thái kích động mạnh', 135],
  ['vô ý gây thương tích do tai nạn', 138], ['sơ suất làm người khác bị thương', 138],
  ['bị chửi trên facebook', 155], ['đăng bài xúc phạm danh dự người khác', 155],
  ['bị vu khống bịa đặt trên mạng', 156], ['người khác loan tin sai sự thật về tôi', 156],
  ['bị cướp giật điện thoại', 171], ['giật dây chuyền rồi bỏ chạy', 171],
  ['bị trộm xe máy', 173], ['ăn trộm tài sản của người khác', 173],
  ['vay tiền không trả', 175], ['mượn tài sản rồi bỏ trốn', 175],
  ['tổ chức đánh bạc qua mạng', 322], ['mở sới bạc cho người khác chơi', 322],
];
for (const item of added) {
  if (LAW_DATA.some((law) => law.id === item[1])) C.push(item);
  else checkLog('SKIP ' + item[0] + ' → Điều ' + item[1] + ' (chưa export dữ liệu T21)');
}
let ok = 0;
for (const [q, want] of C) {
  const r = scorer.rank(q, LAW_DATA), t = r.results[0], pass = t.key == want && r.status !== 'none';
  ok += pass; checkLog((pass ? 'PASS ' : 'FAIL ') + q + ' → ' + t.key + ' (' + t.score.toFixed(2) + ',' + r.status + ')'); if (!pass) checkLog(scorer.explain(r));
}
const n = scorer.rank('thời tiết hôm nay thế nào', LAW_DATA); const np = n.status === 'none'; ok += np; checkLog((np ? 'PASS ' : 'FAIL ') + 'ngoài phạm vi → ' + n.status);
const availability = [['vay tiền không trả', 175], ['bị trộm xe máy', 173], ['bị vu khống', 156]];
for (const [q, id] of availability) {
  const r = scorer.rank(q, LAW_DATA), present = LAW_DATA.some((law) => law.id === id);
  const pass = present ? r.results[0].key == id && r.status !== 'none' : r.status === 'none';
  ok += pass; checkLog((pass ? 'PASS ' : 'FAIL ') + q + ' → ' + (present ? id : r.status));
}
const followUps = [
  [128, 'vô ý làm chết người', 'bao nhiêu người tử vong'], [133, 'đe dọa giết người', 'nội dung đe dọa cụ thể'],
  [135, 'bị kích động mạnh gây thương tích', 'bao nhiêu phần trăm'], [138, 'vô ý gây thương tích', 'bao nhiêu phần trăm'],
  [155, 'bị chửi trên facebook', 'nội dung xúc phạm'], [156, 'bị vu khống trên mạng', 'thông tin nào'],
  [171, 'bị cướp giật điện thoại', 'bao nhiêu triệu đồng'], [173, 'bị trộm xe máy', 'bao nhiêu triệu đồng'],
  [175, 'vay tiền không trả', 'bao nhiêu triệu đồng'], [322, 'tổ chức đánh bạc', 'bao nhiêu người'],
];
let followUpChecks = 0;
for (const [id, q, phrase] of followUps) {
  if (!LAW_DATA.some((law) => law.id === id)) continue;
  const article = LAW_DATA.find((law) => law.id === id), answer = lawApi.answer(q);
  const pass = article.khoan.length >= 2 && article.khoan.every((k) => k.cau_hoi_lam_ro) &&
    answer.found && answer.mode === 'overview' && answer.ask.toLowerCase().includes(phrase);
  followUpChecks++; ok += pass; checkLog((pass ? 'PASS ' : 'FAIL ') + 'câu hỏi làm rõ các khoản Điều ' + id + ' → ' + (answer.ask || answer.mode));
}
const conversations = [
  ['vay tiền bị quịt thì phải làm sao', '80', 175, 80],
  ['tôi bị đánh gây thương tích, giờ xử lý thế nào', '20', 134, 20],
];
let conversationChecks = 0;
for (const [initial, reply, id, amount] of conversations) {
  const first = lawApi.answer(initial);
  const unit = id === 134 ? '%' : ' triệu đồng';
  const second = lawApi.answer(`${initial} ${reply}${unit}`);
  const pass = first.found && first.mode === 'overview' && second.found &&
    second.article.id === id && second.mode === 'specific' &&
    (id === 134 ? second.khoan : second.khoan).so_khoan > 0;
  conversationChecks++; ok += pass;
  checkLog((pass ? 'PASS ' : 'FAIL ') + `tiếp nối hội thoại: "${initial}" → "${reply}" → Điều ${second.article?.id ?? 'none'}`);
}
const total = C.length + 4 + followUpChecks + conversationChecks;
checkLog(ok + '/' + total);
return { passed: ok, failed: total - ok };
}

if (isNodeScorer) { const result = runScorerTests(); if (result.failed) process.exitCode = 1; }
else { window.HS_TEST_SUITES = window.HS_TEST_SUITES || {}; window.HS_TEST_SUITES.scorer = runScorerTests; }
