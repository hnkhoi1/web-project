/* =========================================================
   DATABASE: Hỗ trợ 2 chế độ (Dual-Engine):
   1. MySQL qua Backend PHP (khi chạy trên XAMPP / Apache http://...)
   2. LocalStorage giả lập dự phòng (khi mở trực tiếp file:// hoặc offline)
   ========================================================= */
const DEMO_USER = Object.freeze({
  id: 'demo', username: 'demo', email: 'demo@demo.local', displayName: 'Tài khoản Demo',
  password: '12345', avatar: null, verified: true, builtIn: true, createdAt: 0,
});

const DB = (() => {
  const KEY = 'hs_demo_db_v1';
  const blank = () => ({ seq: 0, users: [], notes: [], contacts: [], prefs: {} });
  const DEFAULT_PREFS = { theme: 'system', font: 'md', noteColor: 'yellow' };

  // Kiểm tra môi trường HTTP (có thể kết nối backend PHP)
  const isHttp = typeof location !== 'undefined' && /^https?:/i.test(location.protocol);
  let backendOnline = false;

  function load() {
    try { const d = JSON.parse(localStorage.getItem(KEY)); return d && Array.isArray(d.users) ? { ...blank(), ...d } : blank(); }
    catch { return blank(); }
  }
  function save(db) {
    try { localStorage.setItem(KEY, JSON.stringify(db)); return true; } catch { return false; }
  }

  async function hashPw(pw, salt) {
    const s = salt + ':' + pw;
    if (typeof window !== 'undefined' && window.crypto && crypto.subtle) {
      const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(s));
      return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
    }
    let h1 = 0xdeadbeef, h2 = 0x41c6ce57;
    for (let i = 0; i < s.length; i++) { const c = s.charCodeAt(i); h1 = Math.imul(h1 ^ c, 2654435761); h2 = Math.imul(h2 ^ c, 1597334677); }
    h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
    h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);
    return 'f' + (4294967296 * (2097151 & h2) + (h1 >>> 0)).toString(16);
  }

  const guard = (u) => { if (!u || u.builtIn) throw new Error('Tài khoản demo là tài khoản cố định trong mã nguồn, không thể thay đổi hoặc xóa.'); };
  const rnd = () => Math.random().toString(36).slice(2) + Date.now().toString(36);

  // Helper gọi backend PHP API
  async function callApi(endpoint, data = null, method = 'POST') {
    if (!isHttp) return null;
    try {
      const basePath = location.pathname.includes('/html/') ? '../backend/api/' : 'backend/api/';
      const opts = { method, headers: { 'Content-Type': 'application/json' } };
      if (data) opts.body = JSON.stringify(data);
      const res = await fetch(basePath + endpoint, opts);
      if (!res.ok) return null;
      return await res.json();
    } catch {
      return null;
    }
  }

  // Khởi động kiểm tra MySQL
  if (isHttp) {
    callApi('auth.php?action=status', null, 'GET').then((res) => {
      if (res && res.success) {
        backendOnline = true;
        console.log('[DB Engine] Đã kết nối MySQL qua PHP Backend!');
      } else {
        console.log('[DB Engine] Đang hoạt động ở chế độ LocalStorage.');
      }
    });
  }

  const api = {
    DEFAULT_PREFS,
    getEngine: () => (backendOnline ? 'MySQL (phpMyAdmin)' : 'LocalStorage (Trình duyệt)'),
    isMySQL: () => backendOnline,

    users: () => [DEMO_USER, ...load().users],
    getUser: (id) => (id === DEMO_USER.id ? DEMO_USER : load().users.find((u) => u.id === id) || null),
    findByIdentifier(ident) {
      const v = String(ident || '').trim().toLowerCase();
      if (!v) return null;
      return v === DEMO_USER.username ? DEMO_USER : load().users.find((u) => u.email === v) || null;
    },

    async login(ident, pw) {
      // 1. Nếu MySQL Backend có sẵn, gọi xác thực từ MySQL
      if (backendOnline) {
        const res = await callApi('auth.php?action=login', { ident, password: pw });
        if (res && res.success) {
          // Đồng bộ user vào cache local
          const db = load();
          if (!db.users.some(u => u.id === res.user.id)) {
            db.users.push(res.user);
            save(db);
          }
          return res.user;
        }
      }

      // 2. Fallback sang LocalStorage
      const u = api.findByIdentifier(ident);
      if (!u) return null;
      if (u.builtIn) return pw === DEMO_USER.password ? u : null;
      return (await hashPw(pw, u.salt)) === u.passHash ? u : null;
    },

    async createUser({ email, displayName, password }) {
      email = email.trim().toLowerCase();
      const salt = rnd();
      const passHash = await hashPw(password, salt);

      // 1. Thử gửi lên MySQL
      let mySqlUser = null;
      if (backendOnline) {
        const res = await callApi('auth.php?action=register', { email, displayName, password });
        if (res && !res.success && res.error === 'EMAIL_EXISTS') {
          return { error: 'EMAIL_EXISTS' };
        }
        if (res && res.success) {
          mySqlUser = res.user;
        }
      }

      // 2. Lưu vào LocalStorage
      const db = load();
      if (db.users.some((u) => u.email === email) || email === DEMO_USER.email) return { error: 'EMAIL_EXISTS' };
      
      const user = mySqlUser || {
        id: 'u' + (++db.seq),
        email,
        displayName: displayName.trim(),
        salt,
        passHash,
        avatar: null,
        verified: false,
        builtIn: false,
        createdAt: Date.now()
      };

      db.users.push(user);
      return save(db) ? { user } : { error: 'STORAGE' };
    },

    updateUser(id, patch) {
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      for (const k of ['displayName', 'avatar', 'verified']) if (k in patch) u[k] = patch[k];
      if (!save(db)) throw new Error('Không đủ dung lượng lưu trữ trình duyệt.');

      if (backendOnline) {
        callApi('auth.php?action=update_profile', { id, ...patch });
      }
      return u;
    },

    async changePassword(id, current, next) {
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      if ((await hashPw(current, u.salt)) !== u.passHash) return false;

      if (backendOnline) {
        callApi('auth.php?action=change_password', { id, current, next });
      }
      return api.setPassword(id, next);
    },

    async setPassword(id, next) {
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      u.salt = rnd(); u.passHash = await hashPw(next, u.salt);
      return save(db);
    },

    async checkPassword(id, pw) {
      const u = load().users.find((x) => x.id === id);
      return !!u && (await hashPw(pw, u.salt)) === u.passHash;
    },

    deleteUser(id) {
      const db = load(); const u = db.users.find((x) => x.id === id); guard(u);
      db.users = db.users.filter((x) => x.id !== id);
      db.notes = db.notes.filter((n) => n.userId !== id); db.contacts = db.contacts.filter((c) => c.userId !== id);
      delete db.prefs[id];

      if (backendOnline) {
        callApi('auth.php?action=delete_account', { id });
      }
      return save(db);
    },

    resetAddedUsers() {
      const db = load(); const keep = (r) => r.userId === DEMO_USER.id;
      db.users = []; db.notes = db.notes.filter(keep); db.contacts = db.contacts.filter(keep);
      db.prefs = db.prefs.demo ? { demo: db.prefs.demo } : {};

      if (backendOnline) {
        callApi('auth.php?action=reset_users');
      }
      return save(db);
    },

    stats() {
      const db = load();
      return { users: db.users.length + 1, notes: db.notes.length, contacts: db.contacts.length };
    },

    getPrefs: (uid) => ({ ...DEFAULT_PREFS, ...((load().prefs || {})[uid] || {}) }),
    setPrefs(uid, p) { const db = load(); db.prefs[uid] = { ...DEFAULT_PREFS, ...p }; return save(db); },

    listNotes(uid, q = '') {
      q = q.trim().toLowerCase();
      return load().notes.filter((n) => String(n.userId) === String(uid) && (!q || (n.title + ' ' + n.content).toLowerCase().includes(q)))
        .sort((a, b) => (b.pinned - a.pinned) || (b.updatedAt - a.updatedAt));
    },

    saveNote(uid, n) {
      const db = load(); const now = Date.now();
      const title = (n.title || '').trim().slice(0, 120), content = (n.content || '').trim().slice(0, 5000);
      if (!title && !content) throw new Error('Hãy nhập tiêu đề hoặc nội dung.');
      const fields = { title: title || content.slice(0, 40), content, color: n.color || 'auto', pinned: !!n.pinned };
      let noteId = n.id;

      if (n.id) {
        const cur = db.notes.find((x) => x.id === n.id && String(x.userId) === String(uid));
        if (!cur) throw new Error('Không tìm thấy ghi chú.');
        Object.assign(cur, fields, { updatedAt: now });
      } else {
        if (db.notes.filter((x) => String(x.userId) === String(uid)).length >= 200) throw new Error('Bạn đã đạt tối đa 200 ghi chú.');
        noteId = ++db.seq;
        db.notes.push({ id: noteId, userId: uid, ...fields, source: n.source || null, createdAt: now, updatedAt: now });
      }
      if (!save(db)) throw new Error('Không đủ dung lượng lưu trữ trình duyệt.');

      if (backendOnline) {
        callApi('notes.php?action=save', { user_id: uid, id: n.id || 0, ...fields, source: n.source || null });
      }
    },

    pinNote(uid, id, pinned) {
      const db = load(); const n = db.notes.find((x) => x.id === id && String(x.userId) === String(uid));
      if (n) {
        n.pinned = pinned; save(db);
        if (backendOnline) callApi('notes.php?action=pin', { user_id: uid, id, pinned });
      }
    },

    deleteNote(uid, id) {
      const db = load(); db.notes = db.notes.filter((x) => !(x.id === id && String(x.userId) === String(uid)));
      save(db);
      if (backendOnline) callApi('notes.php?action=delete', { user_id: uid, id });
    },

    addContact(uid, c) {
      const db = load(); db.contacts.push({ id: ++db.seq, userId: uid, ...c, createdAt: Date.now() });
      if (backendOnline) {
        callApi('contacts.php', { user_id: uid, ...c });
      }
      return save(db);
    },
  };
  return api;
})();

const Session = {
  get: () => localStorage.getItem('hs_session'),
  set: (id) => localStorage.setItem('hs_session', id),
  clear: () => localStorage.removeItem('hs_session'),
};
