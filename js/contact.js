(() => {
  const { user, $, fieldErr, clearErrs, flash } = HS; if (!user) return;
  const f = $('#contact-form'); f.ho_ten.value = user.displayName; f.email.value = user.builtIn ? '' : user.email;
  f.addEventListener('submit', (e) => {
    e.preventDefault(); clearErrs(f); let bad = false; const err = (i, m) => { fieldErr(i, m); bad = true; };
    const v = Object.fromEntries(['ho_ten', 'sdt', 'email', 'noi_dung'].map((k) => [k, f[k].value.trim()]));
    if (v.ho_ten.length < 2 || v.ho_ten.length > 60) err(f.ho_ten, 'Họ tên từ 2 đến 60 ký tự.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.email)) err(f.email, 'Email không hợp lệ.');
    if (v.sdt && !/^\+?[0-9 .\-]{8,15}$/.test(v.sdt)) err(f.sdt, 'Số điện thoại không hợp lệ.');
    if (v.noi_dung.length < 10 || v.noi_dung.length > 2000) err(f.noi_dung, 'Nội dung từ 10 đến 2000 ký tự.');
    if (bad) return;
    if (!DB.addContact(user.id, v)) return err(f.noi_dung, 'Không lưu được (bộ nhớ trình duyệt đầy).');
    flash('ok', 'Đã gửi câu hỏi. Chúng tôi sẽ phản hồi qua email sớm nhất có thể.'); location.reload();
  });
})();
