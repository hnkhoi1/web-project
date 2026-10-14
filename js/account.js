(() => {
  const { user, prefs, $, $$, el, esc, avatarHTML, fieldErr, clearErrs, flash, passwordError, nameError, toast, confirmDialog } = HS; if (!user) return;
  const lockDemo = (form) => { if (!user.builtIn) return; $('#demo-lock').hidden = false; $$('input,button', form).forEach((n) => { n.disabled = true; }); };

  // ===================== HỒ SƠ =====================
  const pf = $('#profile-form');
  if (pf) {
    let pendingAvatar = null;
    $('#avatar-preview-box').innerHTML = avatarHTML(user, 'avatar-lg');
    pf.displayName.value = user.displayName; $('#email-ro').value = user.builtIn ? 'demo (tài khoản cố định trong mã nguồn)' : user.email;
    $('#verify-hint').textContent = user.verified ? 'Đã xác thực.' : 'Chưa xác thực — mở email kích hoạt ở thanh vàng phía trên.';
    $('#remove-wrap').hidden = !user.avatar;
    lockDemo(pf); if (user.builtIn) $('#delete-btn').disabled = true;

    const avatarInput = $('#avatar');
    avatarInput.addEventListener('change', () => {
      fieldErr(avatarInput, null); pendingAvatar = null; const f = avatarInput.files[0]; if (!f) return;
      const fail = (m) => { fieldErr(avatarInput, m); avatarInput.value = ''; };
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) return fail('Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.');
      if (f.size > 2 * 1024 * 1024) return fail('Ảnh vượt quá 2MB.');
      const img = new Image(), url = URL.createObjectURL(f);
      img.onload = () => {
        if (img.width < 64 || img.height < 64) { URL.revokeObjectURL(url); return fail('Ảnh quá nhỏ (tối thiểu 64×64 px).'); }
        const c = document.createElement('canvas'); c.width = c.height = 256; const side = Math.min(img.width, img.height);
        const ctx = c.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 256, 256);
        ctx.drawImage(img, (img.width - side) / 2, (img.height - side) / 2, side, side, 0, 0, 256, 256);
        pendingAvatar = c.toDataURL('image/jpeg', 0.85); URL.revokeObjectURL(url);
        $('#avatar-preview-box').innerHTML = `<img class="avatar avatar-lg" src="${pendingAvatar}" alt="Ảnh xem trước" width="96" height="96">`;
      };
      img.onerror = () => { URL.revokeObjectURL(url); fail('Tệp không phải ảnh hợp lệ.'); };
      img.src = url;
    });
    pf.addEventListener('submit', (e) => {
      e.preventDefault(); clearErrs(pf); const name = pf.displayName.value.trim(), ne = nameError(name);
      if (ne) return fieldErr(pf.displayName, ne);
      const patch = { displayName: name };
      if (pendingAvatar) patch.avatar = pendingAvatar; else if ($('#remove-avatar').checked) patch.avatar = null;
      try { DB.updateUser(user.id, patch); } catch (err) { return toast(err.message, 'err'); }
      flash('ok', 'Đã cập nhật hồ sơ.'); location.reload();
    });
    const dd = $('#delete-dialog'), df = $('#delete-form');
    $('#delete-btn').addEventListener('click', () => { df['del-pw'].value = ''; fieldErr(df['del-pw'], null); confirmDialog(dd); df['del-pw'].focus(); });
    $('#del-cancel').addEventListener('click', () => dd.close());
    df.addEventListener('submit', async (e) => {
      e.preventDefault(); const pw = df['del-pw'].value; fieldErr(df['del-pw'], null);
      if (!(await DB.checkPassword(user.id, pw))) return fieldErr(df['del-pw'], 'Mật khẩu không đúng.');
      try { DB.deleteUser(user.id); } catch (err) { return toast(err.message, 'err'); }
      Session.clear(); flash('ok', 'Tài khoản đã được xóa khỏi database demo.'); location.href = 'login.html';
    });
  }

  // ===================== ĐỔI MẬT KHẨU =====================
  const pwf = $('#pw-form');
  if (pwf) {
    lockDemo(pwf);
    pwf.addEventListener('submit', async (e) => {
      e.preventDefault(); clearErrs(pwf); let bad = false; const err = (i, m) => { fieldErr(i, m); bad = true; };
      const cur = pwf.current.value, pw = pwf.password.value, pw2 = pwf.password2.value;
      if (!cur) err(pwf.current, 'Vui lòng nhập mật khẩu hiện tại.');
      const pe = passwordError(pw); if (pe) err(pwf.password, pe); else if (pw === cur) err(pwf.password, 'Mật khẩu mới phải khác mật khẩu hiện tại.');
      if (pw !== pw2) err(pwf.password2, 'Hai mật khẩu không khớp.');
      if (bad) return;
      let ok = false; try { ok = await DB.changePassword(user.id, cur, pw); } catch (x) { return toast(x.message, 'err'); }
      if (!ok) return err(pwf.current, 'Mật khẩu hiện tại không đúng.');
      flash('ok', 'Đã đổi mật khẩu.'); location.reload();
    });
  }

  // ===================== TÙY CHỈNH =====================
  const prf = $('#pref-form');
  if (prf) {
    const set = (n, v) => { prf.querySelector(`input[name="${n}"][value="${v}"]`).checked = true; };
    set('theme', prefs.theme); set('font', prefs.font); set('noteColor', prefs.noteColor);
    const preview = $('.note-preview');
    const apply = () => {
      const r = document.documentElement.dataset, v = (n) => prf.querySelector(`input[name="${n}"]:checked`).value;
      r.theme = v('theme'); r.font = v('font'); r.noteColor = v('noteColor');
    };
    prf.addEventListener('change', apply); apply();
    prf.addEventListener('submit', (e) => {
      e.preventDefault(); const v = (n) => prf.querySelector(`input[name="${n}"]:checked`).value;
      DB.setPrefs(user.id, { theme: v('theme'), font: v('font'), noteColor: v('noteColor') }); toast('Đã lưu tùy chỉnh.');
    });
  }

  // ===================== DỮ LIỆU DEMO =====================
  const body = $('#users-body');
  if (body) {
    const draw = () => {
      const s = DB.stats();
      const engine = DB.getEngine();
      const stEl = $('#stats');
      if (stEl) {
        stEl.innerHTML = `<strong>Cơ sở dữ liệu đang dùng:</strong> <span class="badge ${DB.isMySQL() ? 'badge-ok' : ''}">${esc(engine)}</span><br>${s.users} tài khoản (gồm 1 tài khoản demo cố định) · ${s.notes} ghi chú · ${s.contacts} câu hỏi liên hệ.`;
      }
      body.replaceChildren(...DB.users().map((u) => {
        const tr = el('tr'); const td = (t) => { const c = el('td', '', t); tr.appendChild(c); return c; };
        td(u.id); td(u.builtIn ? u.username : u.email); td(u.displayName); td(u.verified ? 'Đã xác thực' : 'Chưa xác thực');
        td('').appendChild(el('span', 'badge' + (u.builtIn ? ' lock' : ''), u.builtIn ? 'Cố định trong code (không sửa/xóa)' : 'Thêm vào database'));
        return tr;
      }));
    };
    $('#reset-db').addEventListener('click', () => {
      if (!confirm('Xóa tất cả tài khoản đã thêm cùng ghi chú của họ? Tài khoản demo không bị ảnh hưởng.')) return;
      DB.resetAddedUsers();
      if (!user.builtIn) { Session.clear(); flash('ok', 'Đã xóa các tài khoản đã thêm.'); location.href = 'login.html'; return; }
      toast('Đã xóa các tài khoản đã thêm.'); draw();
    });
    draw();
  }
})();
