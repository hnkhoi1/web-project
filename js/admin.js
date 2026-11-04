/* =========================================================
   Trang quản trị (T32): Tổng quan · Người dùng · Liên hệ · Lịch sử hỏi đáp.
   Gọi DB.admin.* (API_CONTRACT §2.5). Khi db.js chưa có DB.admin (T10/T14 chưa xong)
   thì dùng AdminStub bên dưới, chạy trên dữ liệu localStorage của bản tĩnh.
   Kiểu dữ liệu UI cần:
     stats()          → { users, notes, contacts, contactsNew, questions }
     users.list()     → [{ id, ident, displayName, role, verified, builtIn, createdAt }]
     contacts.list()  → [{ id, ho_ten, email, sdt, noi_dung, trang_thai: 'moi'|'da_tra_loi', createdAt }]
     history.list()   → [{ id, userName, cau_hoi, dieu_luat_id, khoan_so, diem, createdAt }]
   createdAt là số ms hoặc chuỗi ngày giờ MySQL.
   ========================================================= */
(() => {
  const { user, $, el, fmtTime, toast, confirmDialog } = HS; if (!user) return;

  // TODO(T14): xóa stub khi DB.admin gọi backend/api/admin.php.
  const AdminStub = (() => {
    const STATUS_KEY = 'hs_admin_stub_status';
    // DB chưa có hàm liệt kê liên hệ nên stub đọc tạm (chỉ đọc) kho của bản tĩnh. TODO(T10): chuyển vào DB.
    const raw = () => { try { return JSON.parse(localStorage.getItem('hs_demo_db_v1')) || {}; } catch { return {}; } };
    const statuses = () => { try { return JSON.parse(localStorage.getItem(STATUS_KEY)) || {}; } catch { return {}; } };
    const contacts = () => (raw().contacts || []).map((c) => ({ ...c, trang_thai: statuses()[c.id] || 'moi' }))
      .sort((a, b) => b.createdAt - a.createdAt);
    // Bản tĩnh không ghi lịch sử chatbot (chỉ law.php ghi vào MySQL), nên dùng vài dòng mẫu để dựng giao diện.
    const now = Date.now(), H = 3600e3;
    const HISTORY = [
      { id: 3, userName: 'Tài khoản Demo', cau_hoi: 'Tôi bị đánh gây thương tích 20% thì được bồi thường gì?', dieu_luat_id: 134, khoan_so: 1, diem: 0.82, createdAt: now - 2 * H },
      { id: 2, userName: null, cau_hoi: 'Bị lừa chuyển khoản mất 80 triệu đồng', dieu_luat_id: 174, khoan_so: 2, diem: 0.74, createdAt: now - 26 * H },
      { id: 1, userName: 'Tài khoản Demo', cau_hoi: 'tôi đánh bạc bị công an bắt', dieu_luat_id: 321, khoan_so: null, diem: 0.61, createdAt: now - 50 * H },
    ];
    return {
      stub: true,
      async stats() {
        const s = DB.stats(), c = contacts();
        return { users: s.users, notes: s.notes, contacts: c.length, contactsNew: c.filter((x) => x.trang_thai === 'moi').length, questions: HISTORY.length };
      },
      users: {
        list: async () => DB.users().map((u) => ({ id: u.id, ident: u.username || u.email, displayName: u.displayName,
          role: u.builtIn ? 'admin' : 'user', verified: u.verified, builtIn: u.builtIn, createdAt: u.createdAt })),
        async delete(id) { if (id === user.id) throw new Error('Không thể tự xóa tài khoản đang đăng nhập.'); DB.deleteUser(id); },
      },
      contacts: {
        list: async () => contacts(),
        async setStatus(id, trang_thai) { const s = statuses(); s[id] = trang_thai; localStorage.setItem(STATUS_KEY, JSON.stringify(s)); },
      },
      history: { list: async () => HISTORY },
    };
  })();

  const Admin = DB.admin || AdminStub;
  // Bản tĩnh chưa có vai trò: trong lúc dùng stub, tài khoản demo đóng vai admin để xem được menu.
  const isAdmin = user.role === 'admin' || (Admin.stub && user.builtIn);
  const ms = (v) => (typeof v === 'number' ? v : Date.parse(String(v).replace(' ', 'T')));
  const when = (v) => (ms(v) ? fmtTime(ms(v)) : '—');
  const page = document.body.dataset.admin;
  const shell = $('.admin-shell'), main = $('#admin-main');

  if (!isAdmin) {
    const d = el('div', 'empty-state'); d.id = 'admin-denied'; d.setAttribute('role', 'alert');
    d.appendChild(el('p')).appendChild(el('strong', '', 'Bạn không có quyền truy cập trang quản trị.'));
    const p = el('p'), a = el('a', '', '← Về trang chủ'); a.href = 'index.html'; p.appendChild(a); d.appendChild(p);
    shell.replaceChildren(d); return;
  }

  // ---------- menu ----------
  const MENU = [['overview', 'admin.html', 'Tổng quan'], ['users', 'admin-nguoi-dung.html', 'Người dùng'],
                ['contacts', 'admin-lien-he.html', 'Liên hệ'], ['history', 'admin-lich-su.html', 'Lịch sử hỏi đáp']];
  const nav = $('#admin-nav'), ul = el('ul');
  nav.appendChild(el('h2', '', 'Quản trị'));
  MENU.forEach(([k, href, label]) => {
    const li = el('li'), a = el('a'); a.href = href; a.appendChild(el('span', '', label));
    if (k === page) a.setAttribute('aria-current', 'page');
    if (k === 'contacts') { a.appendChild(el('span', 'badge new', '')).hidden = true; a.id = 'nav-contacts'; }
    li.appendChild(a); ul.appendChild(li);
  });
  nav.appendChild(ul);
  if (Admin.stub) {
    const n = el('div', 'alert alert-info'); n.id = 'stub-note'; n.setAttribute('role', 'status');
    n.appendChild(el('span', '', 'Đang dùng dữ liệu của bản tĩnh (localStorage). Lịch sử hỏi đáp là dữ liệu mẫu. Trang sẽ đọc MySQL khi API quản trị (T14) xong.'));
    main.prepend(n);
  }

  const fail = (box, err) => {
    toast(err.message || 'Không tải được dữ liệu.', 'err');
    if (box) box.replaceChildren(el('p', 'muted', 'Không tải được dữ liệu: ' + (err.message || 'lỗi không xác định') + '.'));
  };
  const td = (tr, text, cls) => { const c = el('td', cls || '', text == null ? '' : String(text)); tr.appendChild(c); return c; };
  const statusBadge = (s) => el('span', 'badge ' + (s === 'moi' ? 'new' : 'done'), s === 'moi' ? 'Chưa trả lời' : 'Đã trả lời');
  const lawLink = (id, khoan) => {
    if (!id) return el('span', 'muted', 'Không khớp');
    const a = el('a', '', `Điều ${id}` + (khoan ? `, khoản ${khoan}` : '')); a.href = 'dieu-luat-chi-tiet.html?id=' + id; return a;
  };
  const emptyRow = (body, cols, text) => { const tr = el('tr'); const c = td(tr, text, 'muted'); c.colSpan = cols; body.replaceChildren(tr); };

  async function refreshNavBadge() {
    try {
      const s = await Admin.stats(), b = $('#nav-contacts .badge');
      b.textContent = s.contactsNew; b.hidden = !s.contactsNew; b.setAttribute('aria-label', `${s.contactsNew} liên hệ chưa trả lời`);
    } catch { /* lỗi đã được báo ở phần nội dung trang */ }
  }

  // ---------- Tổng quan ----------
  async function overview() {
    const grid = $('#stat-grid');
    try {
      const [s, contacts, history] = await Promise.all([Admin.stats(), Admin.contacts.list(), Admin.history.list()]);
      const tile = (n, label, href) => { const t = el(href ? 'a' : 'div', 'stat'); if (href) t.href = href; t.append(el('b', '', String(n)), el('span', '', label)); return t; };
      grid.replaceChildren(tile(s.users, 'Người dùng', 'admin-nguoi-dung.html'), tile(s.notes, 'Ghi chú'),
        tile(s.contactsNew, `Liên hệ chưa trả lời / ${s.contacts}`, 'admin-lien-he.html'), tile(s.questions, 'Câu hỏi chatbot', 'admin-lich-su.html'));

      const recent = $('#recent-contacts');
      recent.replaceChildren(...(contacts.length ? contacts.slice(0, 5).map((c) => {
        const li = el('li'), top = el('div', 'panel-row'); top.append(el('strong', '', c.ho_ten), statusBadge(c.trang_thai));
        li.append(top, el('p', 'panel-text', c.noi_dung), el('small', 'muted', when(c.createdAt))); return li;
      }) : [el('li', 'muted', 'Chưa có liên hệ nào.')]));

      const count = {}; history.forEach((h) => { if (h.dieu_luat_id) count[h.dieu_luat_id] = (count[h.dieu_luat_id] || 0) + 1; });
      const top = Object.entries(count).sort((a, b) => b[1] - a[1]).slice(0, 5);
      $('#top-laws').replaceChildren(...(top.length ? top.map(([id, n]) => {
        const li = el('li', 'panel-row'); li.append(lawLink(id), el('span', 'muted', `${n} lượt hỏi`)); return li;
      }) : [el('li', 'muted', 'Chưa có câu hỏi nào.')]));
    } catch (err) { fail(grid, err); }
  }

  // ---------- Người dùng ----------
  async function users() {
    const body = $('#users-body'), q = $('#user-q'), count = $('#user-count');
    const dlg = $('#user-delete-dialog'); let all = [], pending = null;
    const draw = () => {
      const v = q.value.trim().toLowerCase();
      const rows = all.filter((u) => !v || (u.ident + ' ' + u.displayName).toLowerCase().includes(v));
      count.textContent = `${rows.length} / ${all.length} tài khoản`;
      if (!rows.length) return emptyRow(body, 7, 'Không có tài khoản nào khớp.');
      body.replaceChildren(...rows.map((u) => {
        const tr = el('tr'); td(tr, u.id); td(tr, u.ident); td(tr, u.displayName);
        td(tr, '').appendChild(el('span', 'badge' + (u.role === 'admin' ? ' admin' : ''), u.role === 'admin' ? 'Quản trị' : 'Người dùng'));
        td(tr, u.verified ? 'Đã xác thực' : 'Chưa xác thực'); td(tr, u.createdAt ? when(u.createdAt) : '—');
        const b = el('button', 'btn btn-ghost btn-sm', 'Xóa'); b.type = 'button';
        const locked = u.builtIn || u.role === 'admin' || u.id === user.id;
        b.disabled = locked; b.title = locked ? 'Không xóa được tài khoản cố định, tài khoản quản trị hoặc chính bạn.' : '';
        b.setAttribute('aria-label', 'Xóa tài khoản ' + u.ident);
        b.addEventListener('click', () => { pending = u; $('#user-delete-name').textContent = `${u.displayName} (${u.ident})`; confirmDialog(dlg); });
        td(tr, '').appendChild(b); return tr;
      }));
    };
    const load = async () => { try { all = await Admin.users.list(); draw(); } catch (err) { fail(null, err); emptyRow(body, 7, 'Không tải được danh sách người dùng.'); } };
    q.addEventListener('input', draw);
    $('#user-delete-no').addEventListener('click', () => dlg.close());
    $('#user-delete-yes').addEventListener('click', async () => {
      dlg.close();
      try { await Admin.users.delete(pending.id); } catch (err) { return toast(err.message, 'err'); }
      toast('Đã xóa tài khoản ' + pending.ident + '.'); await load(); refreshNavBadge();
    });
    await load();
  }

  // ---------- Liên hệ ----------
  async function contacts() {
    const list = $('#contact-list'), filter = $('#contact-filter'), count = $('#contact-count'); let all = [];
    const draw = () => {
      const rows = all.filter((c) => !filter.value || c.trang_thai === filter.value);
      count.textContent = `${rows.length} / ${all.length} liên hệ`;
      if (!rows.length) {
        const d = el('div', 'empty-state'); d.appendChild(el('p', '', all.length ? 'Không có liên hệ nào ở trạng thái này.' : 'Chưa có liên hệ nào.'));
        return list.replaceChildren(d);
      }
      list.replaceChildren(...rows.map((c) => {
        const art = el('article', 'contact-item' + (c.trang_thai === 'moi' ? ' is-new' : '')), head = el('div', 'contact-head');
        const who = el('div'); who.appendChild(el('strong', '', c.ho_ten));
        const mail = el('a', '', c.email); mail.href = 'mailto:' + c.email;
        const info = el('small'); info.append(' · ', mail); if (c.sdt) info.append(' · ' + c.sdt); info.append(' · ' + when(c.createdAt)); who.appendChild(info);
        head.append(who, statusBadge(c.trang_thai));
        const next = c.trang_thai === 'moi' ? 'da_tra_loi' : 'moi';
        const b = el('button', 'btn btn-ghost btn-sm', next === 'da_tra_loi' ? 'Đánh dấu đã trả lời' : 'Đánh dấu chưa trả lời'); b.type = 'button';
        b.addEventListener('click', async () => {
          b.disabled = true;
          try { await Admin.contacts.setStatus(c.id, next); } catch (err) { b.disabled = false; return toast(err.message, 'err'); }
          c.trang_thai = next; draw(); refreshNavBadge();
        });
        art.append(head, el('p', 'contact-body', c.noi_dung), b); return art;
      }));
    };
    filter.addEventListener('change', draw);
    try { all = await Admin.contacts.list(); draw(); } catch (err) { fail(list, err); }
  }

  // ---------- Lịch sử hỏi đáp ----------
  async function history() {
    const body = $('#history-body'), q = $('#history-q'), count = $('#history-count'); let all = [];
    const draw = () => {
      const v = q.value.trim().toLowerCase();
      const rows = all.filter((h) => !v || h.cau_hoi.toLowerCase().includes(v) || String(h.dieu_luat_id) === v);
      count.textContent = `${rows.length} / ${all.length} câu hỏi`;
      if (!rows.length) return emptyRow(body, 5, all.length ? 'Không có câu hỏi nào khớp.' : 'Chưa có câu hỏi nào.');
      body.replaceChildren(...rows.map((h) => {
        const tr = el('tr'); td(tr, when(h.createdAt)); td(tr, h.userName || 'Khách');
        td(tr, h.cau_hoi, 'wrap-cell'); td(tr, '').appendChild(lawLink(h.dieu_luat_id, h.khoan_so));
        td(tr, h.diem == null ? '—' : Number(h.diem).toFixed(2), 'cell-num'); return tr;
      }));
    };
    q.addEventListener('input', draw);
    try { all = await Admin.history.list(); draw(); } catch (err) { fail(null, err); emptyRow(body, 5, 'Không tải được lịch sử.'); }
  }

  ({ overview, users, contacts, history })[page]?.();
  refreshNavBadge();
})();
