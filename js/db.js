/* =========================================================
   "DATABASE" GIẢ LẬP bằng localStorage (demo giao diện tĩnh).
   - Tài khoản demo nằm CỨNG trong mã nguồn (DEMO_USER), không lưu vào database,
     bị đóng băng (Object.freeze); mọi thao tác sửa/xóa nó đều bị từ chối.
   - Đăng ký/xóa tài khoản chỉ thêm/bớt bản ghi trong localStorage.
   - Băm mật khẩu ở đây (SHA-256 + salt) chỉ để minh họa; web thật phải băm bcrypt ở máy chủ.
   ========================================================= */
const DEMO_USER = Object.freeze({
  id: 'demo', username: 'demo', email: 'demo@demo.local', displayName: 'Tài khoản Demo',
  password: '12345', avatar: null, verified: true, builtIn: true, createdAt: 0,
});

const DB = (() => {
  const KEY = 'hs_demo_db_v1';
  const blank = () => ({ seq: 0, users: [], notes: [], contacts: [], prefs: {} });
  const DEFAULT_PREFS = { theme: 'system', font: 'md', noteColor: 'yellow' };

  function load() {
    try { const d = JSON.parse(localStorage.getItem(KEY)); return d && Array.isArray(d.users) ? { ...blank(), ...d } : blank(); }
    catch { return blank(); }
  }
  function save(db) {
    try { localStorage.setItem(KEY, JSON.stringify(db)); return true; } catch { return false; }
  }
  async function hashPw(pw, salt) {
    const s = salt + ':' + pw;
    if (window.crypto && crypto.subtle) {
      const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(s));
      return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
    }
    let h1 = 0xdeadbeef, h2 = 0x41c6ce57;           // dự phòng khi trình duyệt không có crypto.subtle (vd. mở file://)
    for (let i = 0; i < s.length; i++) { const c = s.charCodeAt(i); h1 = Math.imul(h1 ^ c, 2654435761); h2 = Math.imul(h2 ^ c, 1597334677); }
    h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
    h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);
    return 'f' + (4294967296 * (2097151 & h2) + (h1 >>> 0)).toString(16);
  }
  const guard = (u) => { if (!u || u.builtIn) throw new Error('Tài khoản demo là tài khoản cố định trong mã nguồn, không thể thay đổi hoặc xóa.'); };
  const rnd = () => Math.random().toString(36).slice(2) + Date.now().toString(36);

  const api = {
    DEFAULT_PREFS,
    users: () => [DEMO_USER, ...load().users],
    getUser: (id) => (id === DEMO_USER.id ? DEMO_USER : load().users.find((u) => u.id === id) || null),
    findByIdentifier(ident) {
      const v = String(ident || '').trim().toLowerCase();
      if (!v) return null;
      return v === DEMO_USER.username ? DEMO_USER : load().users.find((u) => u.email === v) || null;
    },
    async login(ident, pw) {
      const u = api.findByIdentifier(ident);
      if (!u) return null;
      if (u.builtIn) return pw === DEMO_USER.password ? u : null;
      return (await hashPw(pw, u.salt)) === u.passHash ? u : null;
    },
    async createUser({ email, displayName, password }) {
      const db = load(); email = email.trim().toLowerCase();
      if (db.users.some((u) => u.email === email) || email === DEMO_USER.email) return { error: 'EMAIL_EXISTS' };
      const salt = rnd();
      const user = { id: 'u' + (++db.seq), email, displayName: displayName.trim(), salt, passHash: await hashPw(password, salt),
                     avatar: null, verified: false, builtIn: false, createdAt: Date.now() };
      db.users.push(user);
      return save(db) ? { user } : { error: 'STORAGE' };
    },
    updateUser(id, patch) {               // chỉ sửa tên/avatar/verified của tài khoản thêm vào; tài khoản demo bị từ chối
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      for (const k of ['displayName', 'avatar', 'verified']) if (k in patch) u[k] = patch[k];
      if (!save(db)) throw new Error('Không đủ dung lượng lưu trữ trình duyệt.');
      return u;
    },
    async changePassword(id, current, next) {
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      if ((await hashPw(current, u.salt)) !== u.passHash) return false;
      return api.setPassword(id, next);
    },
    async setPassword(id, next) {
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      u.salt = rnd(); u.passHash = await hashPw(next, u.salt);
      return save(db);
    },
    async checkPassword(id, pw) { const u = load().users.find((x) => x.id === id); return !!u && (await hashPw(pw, u.salt)) === u.passHash; },
    deleteUser(id) {
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      db.users = db.users.filter((x) => x.id !== id);
      db.notes = db.notes.filter((n) => n.userId !== id); db.contacts = db.contacts.filter((c) => c.userId !== id);
      delete db.prefs[id];
      return save(db);
    },
    resetAddedUsers() {                   // xóa mọi tài khoản đã thêm; tài khoản demo không bị ảnh hưởng
      const db = load(); const keep = (r) => r.userId === DEMO_USER.id;
      db.users = []; db.notes = db.notes.filter(keep); db.contacts = db.contacts.filter(keep);
      db.prefs = db.prefs.demo ? { demo: db.prefs.demo } : {};
      return save(db);
    },
    stats() { const db = load(); return { users: db.users.length + 1, notes: db.notes.length, contacts: db.contacts.length }; },

    getPrefs: (uid) => ({ ...DEFAULT_PREFS, ...((load().prefs || {})[uid] || {}) }),
    setPrefs(uid, p) { const db = load(); db.prefs[uid] = { ...DEFAULT_PREFS, ...p }; return save(db); },

    listNotes(uid, q = '') {
      q = q.trim().toLowerCase();
      return load().notes.filter((n) => n.userId === uid && (!q || (n.title + ' ' + n.content).toLowerCase().includes(q)))
        .sort((a, b) => (b.pinned - a.pinned) || (b.updatedAt - a.updatedAt));
    },
    saveNote(uid, n) {
      const db = load(); const now = Date.now();
      const title = (n.title || '').trim().slice(0, 120), content = (n.content || '').trim().slice(0, 5000);
      if (!title && !content) throw new Error('Hãy nhập tiêu đề hoặc nội dung.');
      const fields = { title: title || content.slice(0, 40), content, color: n.color || 'auto', pinned: !!n.pinned };
      if (n.id) {
        const cur = db.notes.find((x) => x.id === n.id && x.userId === uid);
        if (!cur) throw new Error('Không tìm thấy ghi chú.');
        Object.assign(cur, fields, { updatedAt: now });
      } else {
        if (db.notes.filter((x) => x.userId === uid).length >= 200) throw new Error('Bạn đã đạt tối đa 200 ghi chú.');
        db.notes.push({ id: ++db.seq, userId: uid, ...fields, source: n.source || null, createdAt: now, updatedAt: now });
      }
      if (!save(db)) throw new Error('Không đủ dung lượng lưu trữ trình duyệt.');
    },
    pinNote(uid, id, pinned) { const db = load(); const n = db.notes.find((x) => x.id === id && x.userId === uid); if (n) { n.pinned = pinned; save(db); } },
    deleteNote(uid, id) { const db = load(); db.notes = db.notes.filter((x) => !(x.id === id && x.userId === uid)); save(db); },
    addContact(uid, c) { const db = load(); db.contacts.push({ id: ++db.seq, userId: uid, ...c, createdAt: Date.now() }); return save(db); },
  };
  return api;
})();

const Session = {
  get: () => localStorage.getItem('hs_session'),
  set: (id) => localStorage.setItem('hs_session', id),
  clear: () => localStorage.removeItem('hs_session'),
};
