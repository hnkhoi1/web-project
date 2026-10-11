/* =========================================================
   Khung dùng chung: chặn truy cập khi chưa đăng nhập, áp tùy chỉnh giao diện,
   dựng header/footer, tiện ích form. Nạp đồng bộ trong <head> (sau db.js).
   ========================================================= */
(() => {
  const AUTH_PAGES = ['login.html', 'register.html', 'forgot-password.html'];
  const page = location.pathname.split('/').pop() || 'index.html';
  const isAuth = AUTH_PAGES.includes(page);

  let user = Session.get() ? DB.getUser(Session.get()) : null;
  if (Session.get() && !user) Session.clear();
  if (!isAuth && !user) {                                   // chưa đăng nhập => về trang login ngay, trước khi vẽ trang
    sessionStorage.setItem('hs_next', page + location.search);
    location.replace('login.html');
  } else if (isAuth && user) {
    location.replace('index.html');
  }

  const prefs = DB.getPrefs(user ? user.id : '_');
  const root = document.documentElement;
  root.dataset.theme = prefs.theme; root.dataset.font = prefs.font; root.dataset.noteColor = prefs.noteColor;

  // ---------- tiện ích ----------
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };
  const fmtTime = (ms) => new Date(ms).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' });

  function toast(msg, type = '') {
    let box = $('#toasts');
    if (!box) { box = el('div', 'toasts'); box.id = 'toasts'; box.setAttribute('aria-live', 'polite'); document.body.appendChild(box); }
    const t = el('div', 'toast ' + type, msg); box.appendChild(t); setTimeout(() => t.remove(), 4000);
  }
  const flash = (type, msg) => sessionStorage.setItem('hs_flash', JSON.stringify([type, msg]));
  function takeFlash() { const f = sessionStorage.getItem('hs_flash'); sessionStorage.removeItem('hs_flash'); return f ? JSON.parse(f) : null; }
  function flashEl([type, msg]) {
    const d = el('div', 'alert ' + (type === 'ok' ? 'alert-ok' : type === 'err' ? 'alert-err' : 'alert-info'));
    d.setAttribute('role', type === 'err' ? 'alert' : 'status'); d.appendChild(el('span', '', msg));
    const x = el('button', 'alert-x', '×'); x.type = 'button'; x.setAttribute('aria-label', 'Đóng thông báo'); x.addEventListener('click', () => d.remove());
    d.appendChild(x); return d;
  }

  function avatarHTML(u, cls = '') {
    if (u.avatar) return `<img class="avatar ${cls}" src="${esc(u.avatar)}" alt="" width="40" height="40">`;
    const ch = (u.displayName.trim()[0] || '?').toUpperCase();
    let h = 0; for (const c of u.id + (u.email || '')) h = (h * 31 + c.charCodeAt(0)) >>> 0;
    return `<span class="avatar av-${h % 8} ${cls}" aria-hidden="true">${esc(ch)}</span>`;
  }
  const brandSvg = (s = 34) => `<svg width="${s}" height="${s}" viewBox="0 0 48 48" fill="none" aria-hidden="true" focusable="false">
    <path d="M24 6V42" stroke="#C9AA76" stroke-width="2"/><path d="M8 12H40" stroke="#C9AA76" stroke-width="2"/>
    <path d="M8 12L3 22C3 26 7 28 10.5 28C14 28 18 26 18 22L13 12" stroke="#C9AA76" stroke-width="2" stroke-linejoin="round"/>
    <path d="M40 12L35 22C35 26 39 28 42.5 28C46 28 50 26 50 22" stroke="#C9AA76" stroke-width="2" stroke-linejoin="round"/>
    <path d="M15 42H33" stroke="#C9AA76" stroke-width="2"/><rect x="18" y="4" width="12" height="4" rx="1" fill="#C9AA76"/></svg>`;

  function fieldErr(input, msg) {
    const f = input.closest('.field'); if (!f) return;
    let p = f.querySelector('.field-err');
    if (!msg) { f.classList.remove('has-err'); p?.remove(); input.removeAttribute('aria-invalid'); return; }
    if (!p) { p = el('p', 'field-err'); p.id = input.id + '-err'; f.appendChild(p); }
    p.textContent = msg; f.classList.add('has-err'); input.setAttribute('aria-invalid', 'true'); input.setAttribute('aria-describedby', p.id);
  }
  const clearErrs = (form) => $$('input,textarea', form).forEach((i) => fieldErr(i, null));
  function passwordError(pw) {
    if (pw.length < 8) return 'Mật khẩu tối thiểu 8 ký tự.';
    if (pw.length > 72) return 'Mật khẩu tối đa 72 ký tự.';
    if (!/\p{L}/u.test(pw) || !/\d/.test(pw)) return 'Mật khẩu cần có cả chữ và số.';
    return null;
  }
  const nameError = (n) => (n.length < 2 || n.length > 40 ? 'Tên hiển thị từ 2 đến 40 ký tự.' : /[\x00-\x1F\x7F]/.test(n) ? 'Tên hiển thị chứa ký tự không hợp lệ.' : null);
  const confirmDialog = (d) => (d.showModal ? d.showModal() : d.setAttribute('open', ''));

  // ---------- header / banner / footer ----------
  function renderChrome() {
    const nav = [['home', 'index.html', 'Trang chủ'], ['search', 'tra-cuu.html', 'Tra cứu điều luật'], ['chat', 'tu-van.html', 'Tư vấn (Chatbot)'],
                 ['about', 'gioi-thieu.html', 'Giới thiệu'], ['contact', 'lien-he.html', 'Liên hệ']];
    const cur = document.body.dataset.page;
    const hdr = $('#app-header');
    if (hdr) {
      hdr.innerHTML = `<a class="skip" href="#main">Bỏ qua điều hướng</a>
      ${user.verified ? '' : `<div class="verify-banner" role="alert"><div class="wrap verify-in">
        <p><strong>Tài khoản chưa được xác thực.</strong> Hãy mở email kích hoạt gửi tới <b>${esc(user.email)}</b> (giả lập trong bản demo).</p>
        <button class="btn btn-light btn-sm" type="button" id="activate-btn">Mở email kích hoạt</button></div></div>`}
      <header class="site-header"><div class="wrap bar">
        <a class="brand" href="index.html">${brandSvg()}<span class="brand-text"><strong>Hình Sự Tra Cứu</strong><span>Tra cứu &amp; tư vấn Luật Hình sự VN</span></span></a>
        <nav id="site-nav" class="main-nav" aria-label="Điều hướng chính"><ul>
          ${nav.map(([k, href, l]) => `<li><a href="${href}"${k === cur ? ' aria-current="page"' : ''}>${l}</a></li>`).join('')}
        </ul></nav>
        <div class="bar-right">
          <div class="user-menu">
            <button class="user-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="user-pop">${avatarHTML(user, 'avatar-sm')}<span class="user-name">${esc(user.displayName)}</span></button>
            <div id="user-pop" class="user-pop" hidden>
              <div class="user-pop-head"><strong>${esc(user.displayName)}</strong><small>${user.builtIn ? 'demo (tài khoản cố định)' : esc(user.email)}</small></div>
              <a href="ho-so.html">Hồ sơ cá nhân</a><a href="doi-mat-khau.html">Đổi mật khẩu</a><a href="tuy-chinh.html">Tùy chỉnh giao diện</a>
              <a href="database.html">Dữ liệu demo</a><button type="button" id="logout-btn">Đăng xuất</button>
            </div>
          </div>
          <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Mở menu"><span></span><span></span><span></span></button>
        </div></div></header>`;
    }
    const ft = $('#app-footer');
    if (ft) ft.innerHTML = `<footer class="site-footer"><div class="wrap foot">
      <div class="legal-note"><strong>Hình Sự Tra Cứu</strong><p>Bản demo giao diện tĩnh. Dữ liệu điều luật được biên soạn rút gọn cho mục đích học tập, không thay thế văn bản pháp luật chính thức hoặc tư vấn của luật sư.</p></div>
      <div class="foot-cols"><div><h5>Điều hướng</h5><ul><li><a href="tra-cuu.html">Tra cứu</a></li><li><a href="tu-van.html">Tư vấn</a></li><li><a href="gioi-thieu.html">Giới thiệu</a></li></ul></div>
      <div><h5>Tài khoản</h5><ul><li><a href="ho-so.html">Hồ sơ</a></li><li><a href="tuy-chinh.html">Tùy chỉnh</a></li><li><a href="lien-he.html">Liên hệ</a></li></ul></div></div></div></footer>`;

    const toggle = $('.nav-toggle'), navEl = $('#site-nav');
    toggle?.addEventListener('click', () => {
      const open = navEl.classList.toggle('open'); toggle.setAttribute('aria-expanded', open); toggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
    });
    const ub = $('.user-btn'), pop = $('#user-pop');
    const closePop = () => { if (pop && !pop.hidden) { pop.hidden = true; ub.setAttribute('aria-expanded', 'false'); } };
    ub?.addEventListener('click', (e) => { e.stopPropagation(); pop.hidden = !pop.hidden; ub.setAttribute('aria-expanded', String(!pop.hidden)); });
    document.addEventListener('click', (e) => { if (pop && !pop.contains(e.target)) closePop(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closePop(); ub?.focus(); } });
    $('#logout-btn')?.addEventListener('click', () => { Session.clear(); flash('ok', 'Bạn đã đăng xuất.'); location.href = 'login.html'; });
    $('#activate-btn')?.addEventListener('click', showActivationMail);
  }

  function showActivationMail() {
    const d = el('dialog', 'dialog'); d.setAttribute('aria-labelledby', 'act-t');
    d.innerHTML = `<div class="dialog-in"><h3 id="act-t">Hộp thư giả lập</h3>
      <div class="mail-box"><strong>Kích hoạt tài khoản Hình Sự Tra Cứu</strong><pre>Xin chào ${esc(user.displayName)},

Bấm nút bên dưới để kích hoạt tài khoản.</pre></div>
      <div class="dialog-actions"><button class="btn btn-ghost" type="button" id="act-close">Đóng</button><button class="btn btn-primary" type="button" id="act-go">Kích hoạt tài khoản</button></div></div>`;
    document.body.appendChild(d); confirmDialog(d);
    $('#act-close', d).addEventListener('click', () => d.close());
    $('#act-go', d).addEventListener('click', () => { DB.updateUser(user.id, { verified: true }); flash('ok', 'Tài khoản đã được kích hoạt.'); location.reload(); });
  }

  // ---------- gắn hành vi form dùng chung ----------
  function bindForms() {
    $$('.pw-toggle').forEach((b) => b.addEventListener('click', () => {
      const inp = b.parentElement.querySelector('input'); const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password'; b.textContent = show ? 'Ẩn' : 'Hiện'; b.setAttribute('aria-pressed', String(show));
      b.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    }));
    $$('input[data-strength]').forEach((inp) => {
      const meter = $(`.meter[data-for="${inp.id}"]`), label = $(`.meter-label[data-for="${inp.id}"]`);
      inp.addEventListener('input', () => {
        const v = inp.value;
        const pts = (v.length >= 8) + (v.length >= 12) + (/[a-z]/.test(v) && /[A-Z]/.test(v)) + /\d/.test(v) + /[^A-Za-z0-9]/.test(v);
        const lvl = !v ? 0 : pts <= 1 ? 1 : pts === 2 ? 2 : pts === 3 ? 3 : 4;
        meter.className = 'meter' + (lvl ? ' m' + lvl : '');
        label.textContent = ['', 'Độ mạnh: yếu', 'Độ mạnh: trung bình', 'Độ mạnh: khá', 'Độ mạnh: mạnh'][lvl];
      });
    });
    $$('form[data-match]').forEach((form) => {
      const [a, b] = form.dataset.match.split(',').map((n) => form.elements[n]);
      const check = () => fieldErr(b, b.value && a.value !== b.value ? 'Hai mật khẩu chưa khớp.' : null);
      a.addEventListener('input', check); b.addEventListener('input', check);
    });
    $$('input[data-otp]').forEach((i) => i.addEventListener('input', () => { i.value = i.value.replace(/\D/g, '').slice(0, 6); }));
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (!isAuth && !user) return;
    $$('[data-brand-svg]').forEach((n) => { n.innerHTML = brandSvg(+n.dataset.brandSvg || 34); });
    if (!isAuth) {
      renderChrome();
      const f = takeFlash(); const main = $('main');
      if (f && main) { const w = el('div', 'wrap flash-wrap'); w.appendChild(flashEl(f)); main.prepend(w); }
    }
    else { const f = takeFlash(); const box = $('#flash'); if (f && box) box.appendChild(flashEl(f)); }
    bindForms();
  });

  window.HS = { user, page, prefs, $, $$, esc, el, fmtTime, toast, flash, takeFlash, flashEl, avatarHTML, fieldErr, clearErrs,
                passwordError, nameError, confirmDialog };
})();
