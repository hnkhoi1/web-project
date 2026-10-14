// tests/full-test.js — Kiểm tra toàn bộ logic và tính toàn vẹn của project
const fs = require('fs');
const path = require('path');

let totalTests = 0;
let passedTests = 0;

function assert(condition, message) {
  totalTests++;
  if (condition) {
    passedTests++;
    console.log(`  ✓ PASS: ${message}`);
  } else {
    console.error(`  ✗ FAIL: ${message}`);
  }
}

console.log('====================================================');
console.log('BẮT ĐẦU KIỂM THỬ TOÀN DIỆN PROJECT HÌNH SỰ TRA CỨU');
console.log('====================================================\n');

// ----------------------------------------------------
// PHẦN 1: KIỂM TRA TẤT CẢ FILE HTML VÀ TÀI NGUYÊN
// ----------------------------------------------------
console.log('--- PHẦN 1: KIỂM TRA CẤU TRÚC 14 FILE HTML ---');
const htmlDir = path.join(__dirname, '../html');
const expectedHtmlFiles = [
  'database.html',
  'dieu-luat-chi-tiet.html',
  'doi-mat-khau.html',
  'forgot-password.html',
  'gioi-thieu.html',
  'ho-so.html',
  'index.html',
  'lien-he.html',
  'login.html',
  'register.html',
  'tra-cuu.html',
  'tu-van.html',
  'tuy-chinh.html',
  'van-ban-moi.html'
];

const actualHtmlFiles = fs.readdirSync(htmlDir).filter(f => f.endsWith('.html')).sort();
assert(
  JSON.stringify(actualHtmlFiles) === JSON.stringify(expectedHtmlFiles.sort()),
  `Đủ 14 file HTML trong thư mục html/ (${actualHtmlFiles.length}/14)`
);

for (const file of actualHtmlFiles) {
  const content = fs.readFileSync(path.join(htmlDir, file), 'utf8');
  assert(content.includes('<!doctype html>') || content.includes('<!DOCTYPE html>'), `[${file}] Có <!doctype html>`);
  assert(content.includes('lang="vi"'), `[${file}] Có thuộc tính lang="vi"`);
  assert(content.includes('<meta charset="utf-8">'), `[${file}] Có charset utf-8`);
  assert(content.includes('href="../css/app.css"'), `[${file}] Liên kết đúng ../css/app.css`);
  assert(content.includes('src="../js/db.js"'), `[${file}] Nạp db.js`);
  assert(content.includes('src="../js/app.js"'), `[${file}] Nạp app.js`);
  
  // Kiểm tra thứ tự db.js trước app.js
  const dbIndex = content.indexOf('src="../js/db.js"');
  const appIndex = content.indexOf('src="../js/app.js"');
  assert(dbIndex !== -1 && appIndex !== -1 && dbIndex < appIndex, `[${file}] db.js đứng trước app.js`);
}

// ----------------------------------------------------
// PHẦN 2: KIỂM TRA FILE VĂN BẢN MỚI (PLAN.txt YÊU CẦU)
// ----------------------------------------------------
console.log('\n--- PHẦN 2: KIỂM TRA VĂN BẢN PHÁP LUẬT MỚI ---');
global.window = global;
const dataJs = fs.readFileSync(path.join(__dirname, '../js/data.js'), 'utf8');
eval(dataJs + ';global.LAW_DATA=LAW_DATA;');

const newsDataJs = fs.readFileSync(path.join(__dirname, '../js/news-data.js'), 'utf8');
eval(newsDataJs + ';global.LAW_NEWS=LAW_NEWS;');

assert(Array.isArray(global.LAW_NEWS) && global.LAW_NEWS.length >= 5, `Có danh sách LAW_NEWS (${global.LAW_NEWS.length} mục)`);

const lawIds = new Set(global.LAW_DATA.map(d => d.id));
let validDates = true;
let validRelations = true;

for (const item of global.LAW_NEWS) {
  if (!item.id || !item.so_hieu || !item.ten || !item.loai || !item.co_quan || !item.ngay_ban_hanh || !item.ngay_hieu_luc || !item.tom_tat) {
    validDates = false;
  }
  if (isNaN(new Date(item.ngay_ban_hanh).getTime()) || isNaN(new Date(item.ngay_hieu_luc).getTime())) {
    validDates = false;
  }
  if (Array.isArray(item.dieu_luat_lien_quan)) {
    for (const rid of item.dieu_luat_lien_quan) {
      if (!lawIds.has(rid)) {
        validRelations = false;
      }
    }
  }
}
assert(validDates, 'Tất cả văn bản mới có đầy đủ trường thông tin và ngày hợp lệ');
assert(validRelations, 'Tất cả điều luật liên quan trong văn bản mới đều tồn tại trong LAW_DATA');

// ----------------------------------------------------
// PHẦN 3: KIỂM TRA LAW_DATA VÀ SCORER / NLU / LAW
// ----------------------------------------------------
console.log('\n--- PHẦN 3: KIỂM TRA DỮ LIỆU ĐIỀU LUẬT & SEARCH / CHATBOT ---');
const nluJs = fs.readFileSync(path.join(__dirname, '../js/nlu.js'), 'utf8');
eval(nluJs + ';global.vnNormalize=vnNormalize;global.buildIndex=buildIndex;global.semanticSearch=semanticSearch;');

const Scorer = require('../js/scorer.js');
global.Scorer = Scorer;

const lawJs = fs.readFileSync(path.join(__dirname, '../js/law.js'), 'utf8');
eval(lawJs + ';global.Law=Law;');

assert(typeof global.Law.search === 'function', 'Hàm Law.search tồn tại');
assert(typeof global.Law.answer === 'function', 'Hàm Law.answer tồn tại');
assert(typeof global.Law.byId === 'function', 'Hàm Law.byId tồn tại');

// Kiểm tra tìm kiếm điều luật
const searchExact = global.Law.search('134');
assert(searchExact.length > 0 && searchExact[0].id === 134, 'Tìm kiếm số điều "134" trả về Điều 134');

const searchByText = global.Law.search('trộm cắp tài sản');
assert(searchByText.length > 0, 'Tìm kiếm từ khóa "trộm cắp tài sản" trả về kết quả');

const searchFilterChuong = global.Law.search('', 'Các tội phạm về ma túy');
assert(searchFilterChuong.length > 0 && searchFilterChuong.every(r => r.chuong === 'Các tội phạm về ma túy'), 'Lọc theo chương hoạt động chính xác');

// Kiểm tra chatbot answer
const ans1 = global.Law.answer('tôi bị đánh gãy tay');
assert(ans1.found && ans1.article.id === 134, 'Chatbot: "tôi bị đánh gãy tay" -> Điều 134');

const ans2 = global.Law.answer('lừa đảo qua mạng 50 triệu');
assert(ans2.found && ans2.article.id === 174, 'Chatbot: "lừa đảo qua mạng 50 triệu" -> Điều 174');

const ansUnknown = global.Law.answer('cách nấu chè đậu xanh');
assert(!ansUnknown.found, 'Chatbot: câu hỏi không liên quan trả về found: false');

// ----------------------------------------------------
// PHẦN 4: KIỂM TRA DATABASE & AUTHENTICATION
// ----------------------------------------------------
console.log('\n--- PHẦN 4: KIỂM TRA LOCALSTORAGE DB & QUYỀN HẠN ---');
const store = {};
global.localStorage = {
  getItem: k => (k in store ? store[k] : null),
  setItem: (k, v) => { store[k] = String(v); },
  removeItem: k => { delete store[k]; },
  clear: () => { for (const k in store) delete store[k]; }
};

const dbJs = fs.readFileSync(path.join(__dirname, '../js/db.js'), 'utf8');
eval(dbJs + ';global.DB=DB;global.DEMO_USER=DEMO_USER;global.Session=Session;');

(async () => {
  // Test đăng nhập demo
  const demoLogin = await global.DB.login('demo', '12345');
  assert(demoLogin && demoLogin.id === 'demo', 'Đăng nhập tài khoản demo thành công');

  const demoWrongPass = await global.DB.login('demo', 'wrongpass');
  assert(demoWrongPass === null, 'Đăng nhập demo sai mật khẩu bị từ chối');

  // Test tài khoản demo bất khả xâm phạm
  let demoProtected = true;
  try { global.DB.updateUser('demo', { displayName: 'Hack' }); demoProtected = false; } catch (e) {}
  try { global.DB.deleteUser('demo'); demoProtected = false; } catch (e) {}
  try { await global.DB.changePassword('demo', '12345', 'newpass123'); demoProtected = false; } catch (e) {}
  assert(demoProtected, 'Mọi hành vi can thiệp sửa/xóa/đổi mật khẩu demo đều bị từ chối');

  // Test đăng ký tài khoản mới
  const regUser = await global.DB.createUser({ email: 'test@kajima.local', displayName: 'Người dùng thử', password: 'password123' });
  assert(regUser.user && regUser.user.id && !regUser.user.verified, 'Đăng ký tài khoản người dùng mới thành công');

  const duplicateReg = await global.DB.createUser({ email: 'test@kajima.local', displayName: 'Trùng', password: 'password123' });
  assert(duplicateReg.error === 'EMAIL_EXISTS', 'Ngăn chặn đăng ký trùng email');

  // Test ghi chú theo người dùng
  global.DB.saveNote(regUser.user.id, { title: 'Ghi chú 1', content: 'Nội dung ghi chú của user' });
  global.DB.saveNote('demo', { title: 'Ghi chú demo', content: 'Nội dung demo' });

  const userNotes = global.DB.listNotes(regUser.user.id);
  const demoNotes = global.DB.listNotes('demo');
  assert(userNotes.length === 1 && demoNotes.length === 1, 'Ghi chú được phân tách độc lập theo từng người dùng');

  // Xóa user thử nghiệm
  global.DB.deleteUser(regUser.user.id);
  assert(!global.DB.getUser(regUser.user.id) && global.DB.listNotes(regUser.user.id).length === 0, 'Xóa người dùng tự động dọn dẹp ghi chú liên quan');
  assert(global.DB.listNotes('demo').length === 1, 'Xóa người dùng không ảnh hưởng ghi chú của demo');

  // ----------------------------------------------------
  // TỔNG KẾT
  // ----------------------------------------------------
  console.log('\n====================================================');
  console.log(`KẾT QUẢ KIỂM THỬ: ${passedTests}/${totalTests} BÀI PASS`);
  console.log('====================================================');
  process.exit(totalTests === passedTests ? 0 : 1);
})();
