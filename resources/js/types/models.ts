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

export type ChatArticleRef = { id: number; tieu_de: string; chuong: string };

type ChatFound = {
    found: true;
    score: number;
    role: string | null;
    article: ChatArticleRef;
    alt: { id: number; tieu_de: string } | null;
    ghi_chu_quyen_loi: string | null;
    can_cu: string | null;
    note_title: string;
    note_text: string;
};

export type ChatAnswer =
    | { found: false }
    | (ChatFound & {
          mode: 'overview';
          khoan_all: {
              so_khoan: number;
              dieu_kien: string | null;
              noi_dung: string;
              cau_hoi_lam_ro: string | null;
          }[];
          ask: string;
      })
    | (ChatFound & {
          mode: 'specific';
          khoan: {
              so_khoan: number;
              dieu_kien: string | null;
              noi_dung: string;
          };
          quyen_loi: string | null;
      });

export type ArticleClause = {
    so_khoan: number;
    dieu_kien: string | null;
    noi_dung: string;
    quyen_loi: string | null;
};

/** An article as returned by the law search / detail pages. */
export type LawArticle = {
    id: number;
    chuong: string;
    tieu_de: string;
    tom_tat: string;
    khung_hinh_phat: string;
    can_cu_boi_thuong: string | null;
    ghi_chu_quyen_loi: string | null;
    score?: number;
};

export type LawArticleDetail = LawArticle & { khoan: ArticleClause[] };

export type RelatedArticle = { id: number; tieu_de: string };
