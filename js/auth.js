(() => {
  const { $, el, esc, fieldErr, clearErrs, flash, passwordError, nameError } = HS;
  const page = document.body.dataset.page;

  // ===================== ĐĂNG NHẬP =====================
  if (page === 'login') {
    const form = $('#login-form'), box = $('#form-error');
    const failKey = 'hs_fail';
    const fails = () => JSON.parse(sessionStorage.getItem(failKey) || '{}');
    form.addEventListener('submit', async (e) => {
      e.preventDefault(); clearErrs(form); box.hidden = true;
      const ident = form.ident.value.trim(), pw = form.password.value;
      let bad = false;
      if (!ident) { fieldErr(form.ident, 'Vui lòng nhập tên đăng nhập hoặc email.'); bad = true; }
      if (!pw) { fieldErr(form.password, 'Vui lòng nhập mật khẩu.'); bad = true; }
      if (bad) return;
      const key = ident.toLowerCase(), f = fails(), rec = f[key] || { n: 0, until: 0 };
      if (rec.until > Date.now()) {
        box.textContent = `Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ${Math.ceil((rec.until - Date.now()) / 1000)} giây.`; box.hidden = false; return;
      }
      const btn = form.querySelector('button[type="submit"]'); btn.disabled = true; btn.textContent = 'Đang xử lý...';
      const user = await DB.login(ident, pw);
      if (!user) {
        rec.n++; if (rec.n >= 5) { rec.until = Date.now() + 60000; rec.n = 0; }
        f[key] = rec; sessionStorage.setItem(failKey, JSON.stringify(f));
        box.textContent = 'Tên đăng nhập/email hoặc mật khẩu không đúng.'; box.hidden = false;
        btn.disabled = false; btn.textContent = 'Đăng nhập'; return;
      }
      Session.set(user.id);
      const next = sessionStorage.getItem('hs_next'); sessionStorage.removeItem('hs_next');
      location.href = next && /^[a-z0-9-]+\.html(\?[\w=&%.\-]*)?$/i.test(next) ? next : 'index.html';
    });
  }

  // ===================== ĐĂNG KÝ =====================
  if (page === 'register') {
    const form = $('#register-form');
    form.addEventListener('submit', async (e) => {
      e.preventDefault(); clearErrs(form);
      const email = form.email.value.trim().toLowerCase(), name = form.displayName.value.trim(), pw = form.password.value, pw2 = form.password2.value;
      let bad = false; const err = (i, m) => { fieldErr(i, m); bad = true; };
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || email.length > 254) err(form.email, 'Email không hợp lệ.');
      const ne = nameError(name); if (ne) err(form.displayName, ne);
      const pe = passwordError(pw); if (pe) err(form.password, pe);
      if (pw !== pw2) err(form.password2, 'Hai mật khẩu không khớp.');
      if (bad) return;
      const btn = form.querySelector('button[type="submit"]'); btn.disabled = true; btn.textContent = 'Đang xử lý...';
      const r = await DB.createUser({ email, displayName: name, password: pw });
      if (r.error) {
        btn.disabled = false; btn.textContent = 'Đăng ký';
        return err(form.email, r.error === 'EMAIL_EXISTS' ? 'Email này đã được đăng ký. Bạn có thể đăng nhập hoặc dùng "Quên mật khẩu".' : 'Không lưu được dữ liệu (bộ nhớ trình duyệt đầy hoặc bị chặn).');
      }
      Session.set(r.user.id);                                   // đăng ký xong tự đăng nhập
      flash('ok', `Chào mừng ${r.user.displayName}! Tài khoản đã được tạo. Email kích hoạt đã được gửi (giả lập) — bấm "Mở email kích hoạt" ở thanh vàng.`);
      location.href = 'index.html';
    });
  }

  // ===================== QUÊN MẬT KHẨU =====================
  if (page === 'forgot') {
    const KEY = 'hs_reset';
    const steps = { 1: $('#step-1'), 2: $('#step-2'), 3: $('#step-3') };
    const show = (n) => Object.entries(steps).forEach(([k, s]) => { s.hidden = +k !== n; });
    const state = () => JSON.parse(sessionStorage.getItem(KEY) || 'null');
    const setState = (s) => sessionStorage.setItem(KEY, JSON.stringify(s));
    const banner = (msg, type = 'alert-err') => { const b = $('#form-error'); b.className = 'alert ' + type; b.firstElementChild.textContent = msg; b.hidden = false; };

    $('#f1').addEventListener('submit', (e) => {
      e.preventDefault(); const f = e.target; clearErrs(f); $('#form-error').hidden = true;
      const ident = f.email.value.trim();
      if (!ident) return fieldErr(f.email, 'Vui lòng nhập email đã đăng ký.');
      const u = DB.findByIdentifier(ident);
      if (u && u.builtIn) return banner('Tài khoản demo có mật khẩu cố định trong mã nguồn nên không thể đặt lại. Hãy thử với tài khoản bạn đã đăng ký.', 'alert-info');
      const otp = String(Math.floor(Math.random() * 1e6)).padStart(6, '0');
      setState({ userId: u ? u.id : null, email: ident.toLowerCase(), otp, expires: Date.now() + 15 * 60000, attempts: 0, verified: false });
      // Phản hồi trung tính: luôn sang bước 2; chỉ khi email tồn tại mới có "email" thật trong hộp thư giả lập.
      $('#mail-sim').innerHTML = u
        ? `<div class="mail-box"><strong>Đặt lại mật khẩu Hình Sự Tra Cứu</strong><pre>Xin chào ${esc(u.displayName)},

Cách 1: bấm nút "Mở liên kết trong email" bên dưới (hiệu lực 15 phút).
Cách 2: nhập mã OTP: </pre><p class="otp">${otp}</p><p><button class="btn btn-ghost btn-sm" type="button" id="link-btn">Mở liên kết trong email</button></p></div>`
        : `<p class="muted">Không có email nào được gửi vì địa chỉ này chưa đăng ký (bản demo chỉ hiện để bạn thấy luồng hoạt động).</p>`;
      $('#link-btn')?.addEventListener('click', () => { const s = state(); s.verified = true; setState(s); show(3); $('#new-pw').focus(); });
      show(2); $('#otp').focus();
    });

    $('#f2').addEventListener('submit', (e) => {
      e.preventDefault(); const f = e.target; clearErrs(f); $('#form-error').hidden = true;
      const s = state(); const code = f.otp.value.replace(/\D/g, '');
      if (!s) return show(1);
      if (code.length !== 6) return fieldErr(f.otp, 'Mã OTP gồm 6 chữ số.');
      if (s.attempts >= 5) return banner('Bạn đã nhập sai quá 5 lần, mã này đã bị khóa. Hãy yêu cầu mã mới.');
      if (s.userId && code === s.otp && Date.now() < s.expires) { s.verified = true; setState(s); show(3); $('#new-pw').focus(); return; }
      s.attempts++; setState(s);
      banner(s.attempts >= 5 ? 'Bạn đã nhập sai quá 5 lần, mã này đã bị khóa. Hãy yêu cầu mã mới.' : 'Mã OTP không đúng hoặc đã hết hạn.');
    });

    $('#f3').addEventListener('submit', async (e) => {
      e.preventDefault(); const f = e.target; clearErrs(f);
      const s = state(); if (!s || !s.verified || !s.userId) return show(1);
      const pw = f.password.value, pw2 = f.password2.value; let bad = false;
      const pe = passwordError(pw); if (pe) { fieldErr(f.password, pe); bad = true; }
      if (pw !== pw2) { fieldErr(f.password2, 'Hai mật khẩu không khớp.'); bad = true; }
      if (bad) return;
      await DB.setPassword(s.userId, pw);
      sessionStorage.removeItem(KEY);
      flash('ok', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập bằng mật khẩu mới.');
      location.href = 'login.html';                             // không tự đăng nhập
    });
    show(1);
  }
})();
