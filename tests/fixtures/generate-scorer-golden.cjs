// Generates tests/fixtures/scorer-golden.json and app/Search/data/concepts.json from the legacy JS scorer.
// Run: node tests/fixtures/generate-scorer-golden.cjs
const fs = require('fs'), path = require('path');
const root = path.join(__dirname, '../..');
global.window = global;
eval(fs.readFileSync(path.join(root, 'js/data.js'), 'utf8') + ';global.LAW_DATA=LAW_DATA');
// ArticleRepository::all() returns articles in id order; data.js is not id-ordered, which only changes tie order
// between equally scored articles. Sort in place so Scorer and Law (which hold the same array) see the repository order.
LAW_DATA.sort((a, b) => a.id - b.id);
eval(fs.readFileSync(path.join(root, 'js/nlu.js'), 'utf8') + ';global.buildIndex=buildIndex;global.semanticSearch=semanticSearch;global.vnNormalize=vnNormalize');
// package.json is "type": "module", so require() of the legacy CommonJS-style js/scorer.js would
// treat it as ESM; evaluate it with a CommonJS module shim instead.
const scorerModule = { exports: {} };
new Function('module', 'exports', fs.readFileSync(path.join(root, 'js/scorer.js'), 'utf8'))(scorerModule, scorerModule.exports);
global.Scorer = scorerModule.exports;
eval(fs.readFileSync(path.join(root, 'js/law.js'), 'utf8') + ';global.Law=Law');

// Question lists copied from tests/scorer-test.js
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
const availability = [['vay tiền không trả', 175], ['bị trộm xe máy', 173], ['bị vu khống', 156]];
const followUps = [
  [128, 'vô ý làm chết người', 'bao nhiêu người tử vong'], [133, 'đe dọa giết người', 'nội dung đe dọa cụ thể'],
  [135, 'bị kích động mạnh gây thương tích', 'bao nhiêu phần trăm'], [138, 'vô ý gây thương tích', 'bao nhiêu phần trăm'],
  [155, 'bị chửi trên facebook', 'nội dung xúc phạm'], [156, 'bị vu khống trên mạng', 'thông tin nào'],
  [171, 'bị cướp giật điện thoại', 'bao nhiêu triệu đồng'], [173, 'bị trộm xe máy', 'bao nhiêu triệu đồng'],
  [175, 'vay tiền không trả', 'bao nhiêu triệu đồng'], [322, 'tổ chức đánh bạc', 'bao nhiêu người'],
];
const conversations = [
  ['vay tiền bị quịt thì phải làm sao', '80', 175, 80],
  ['tôi bị đánh gây thương tích, giờ xử lý thế nào', '20', 134, 20],
];
const SUG = ['Tôi bị đánh gây thương tích 20% thì được bồi thường gì?', 'Bị lừa chuyển khoản mất 80 triệu đồng, tôi đòi lại bằng cách nào?',
  'Lái xe say rượu gây tai nạn làm chết 1 người thì bị phạt bao nhiêu năm tù?', 'Nhân viên nhà nước lấy 300 triệu tiền công quỹ thì phạm tội gì?', 'Tàng trữ ma túy trong người bị xử lý ra sao?'];

const questions = [];
const add = (q) => { if (!questions.includes(q)) questions.push(q); };
C.forEach(([q]) => add(q));
added.forEach(([q]) => add(q));
add('thời tiết hôm nay thế nào');
availability.forEach(([q]) => add(q));
followUps.forEach(([, q]) => add(q));
conversations.forEach(([initial, reply, id]) => { add(initial); add(`${initial} ${reply}${id === 134 ? '%' : ' triệu đồng'}`); });
SUG.forEach(add);
['tôi bị đánh 20%', 'chém 3 người chết', 'trộm 2 tỷ', 'điều 134'].forEach(add);
// Extra coverage: unaccented input, typos (fuzzy branch), accent-only words, out-of-scope text.
['toi bi lua mat 80 trieu', 'bi chui tren mang', 'toi danh nguoi', 'bi tat vao mat', 'xin chao', 'đá bóng cuối tuần',
  'bi da', 'tang tru thuoc lac', 'say ruou gay tai nan', 'tham o cong quy', 'bị hành hùng', 'ăn tróm xe máy',
  'bị lừa đảo qua mạng', 'cuop giat dien thoai', 'dieu 321', 'Điều 999'].forEach(add);

const r9 = (x) => Number(x.toFixed(9));
const golden = questions.map((question) => {
  const r = Scorer.rank(question, LAW_DATA);
  const rank = {
    mode: r.mode, status: r.status, concepts: r.concepts, missingTarget: r.missingTarget,
    top5: r.results.slice(0, 5).map((x) => ({ key: x.key, score: r10(x.score), parts: { concept: r10(x.parts.concept), lexical: r10(x.parts.lexical), fuzzy: r10(x.parts.fuzzy) } })),
  };
  const answer = Law.answer(question); delete answer.debug; delete answer.role;
  const search = Law.search(question).map((x) => ({ id: x.id, score: x.score === undefined ? null : x.score }));
  return { question, rank, answer, search };
});
function r10(x) { return r9(x); }

fs.writeFileSync(path.join(__dirname, 'scorer-golden.json'), JSON.stringify(golden, null, 2) + '\n');
fs.writeFileSync(path.join(root, 'app/Search/data/concepts.json'), JSON.stringify(Scorer.CONCEPTS, null, 2) + '\n');
console.log('golden entries: ' + golden.length);
