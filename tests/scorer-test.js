// node tests/scorer-test.js — chấm điểm trên LAW_DATA thật (js/data.js)
const fs = require('fs'), path = require('path'); global.window = global;
eval(fs.readFileSync(path.join(__dirname, '../js/data.js'), 'utf8') + ';global.LAW_DATA=LAW_DATA');
const Scorer = require('../js/scorer.js');
const C = [
  ['tôi bị đánh', 134], ['bị đánh', 134], ['toi bi danh', 134], ['tôi bị người ta đánh nhập viện', 134],
  ['Tôi bị đánh gây thương tích 20% thì được bồi thường gì?', 134], ['bạn tôi bị hành hung', 134],
  ['tôi đánh bạc bị công an bắt', 321], ['toi danh bac an tien', 321], ['chơi cá độ bóng đá', 321],
  ['tôi bị đánh chết', 123], ['người thân bị giết', 123],
  ['Bị lừa chuyển khoản mất 80 triệu đồng, tôi đòi lại bằng cách nào?', 174], ['toi bi lua tien qua mang', 174],
  ['Lái xe say rượu gây tai nạn làm chết 1 người thì bị phạt bao nhiêu năm tù?', 260],
  ['Nhân viên nhà nước lấy 300 triệu tiền công quỹ thì phạm tội gì?', 353],
  ['Tàng trữ ma túy trong người bị xử lý ra sao?', 249], ['bị giật túi xách trên đường', 168], ['bị cướp dao kề cổ', 168],
];
let ok = 0;
for (const [q, want] of C) {
  const r = Scorer.rank(q, LAW_DATA), t = r.results[0], pass = t.key == want && r.status !== 'none';
  ok += pass; console.log((pass ? 'PASS ' : 'FAIL ') + q + ' → ' + t.key + ' (' + t.score.toFixed(2) + ',' + r.status + ')'); if (!pass) console.log(Scorer.explain(r));
}
const n = Scorer.rank('thời tiết hôm nay thế nào', LAW_DATA); const np = n.status === 'none'; ok += np; console.log((np ? 'PASS ' : 'FAIL ') + 'ngoài phạm vi → ' + n.status);
console.log(ok + '/' + (C.length + 1)); process.exit(ok === C.length + 1 ? 0 : 1);
