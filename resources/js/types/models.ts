export type Theme = 'system' | 'light' | 'dark';
export type FontSize = 'sm' | 'md' | 'lg' | 'xl';
export type NoteColor =
    | 'yellow'
    | 'green'
    | 'blue'
    | 'rose'
    | 'violet'
    | 'gray';

/** The signed-in user as shared by Inertia (never includes the password). */
export type User = {
    id: number;
    name: string;
    email: string;
    username: string | null;
    avatar: string | null;
    isDemo: boolean;
};

export type Prefs = {
    theme: Theme;
    font: FontSize;
    noteColor: NoteColor;
};

export type Note = {
    id: number;
    title: string;
    content: string | null;
    color: NoteColor;
    pinned: boolean;
    source_type: string | null;
    source_ref: string | null;
    created_at: string;
    updated_at: string;
};

export type Clause = {
    id: number;
    dieu_luat_id: number;
    so_khoan: number;
    dieu_kien: string | null;
    noi_dung: string;
    quyen_loi: string | null;
    cau_hoi_lam_ro: string | null;
};

export type Article = {
    id: number;
    chuong_id: number;
    tieu_de: string;
    tom_tat: string;
    tu_khoa: string;
    khung_hinh_phat: string;
    can_cu_boi_thuong: string | null;
    ghi_chu_quyen_loi: string | null;
    khoan?: Clause[];
};

export type ChatAnswer =
    | { found: false }
    | {
          found: true;
          mode: 'overview' | 'specific';
          score: number;
          article: { id: number; tieu_de: string; chuong: string };
          alt: { id: number; tieu_de: string } | null;
          note_title: string;
          note_text: string;
      };
