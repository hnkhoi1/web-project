import { describe, expect, it } from 'vite-plus/test';
import { articleNoteText } from './law';
import type { LawArticleDetail } from '@/types';

const base: LawArticleDetail = {
    id: 1,
    chuong: 'C',
    tieu_de: 'T',
    tom_tat: 'Tóm tắt',
    khung_hinh_phat: '1-2 năm',
    can_cu_boi_thuong: null,
    ghi_chu_quyen_loi: null,
    khoan: [
        { so_khoan: 1, dieu_kien: 'cơ bản', noi_dung: 'ND1', quyen_loi: 'QL1' },
        { so_khoan: 2, dieu_kien: 'nặng', noi_dung: 'ND2', quyen_loi: null },
    ],
};

describe('articleNoteText', () => {
    it('formats clauses like the original site', () => {
        expect(articleNoteText(base)).toBe(
            'Tóm tắt\nKhung hình phạt: 1-2 năm\n\nKhoản 1 (cơ bản): ND1\nQuyền lợi: QL1\n\nKhoản 2 (nặng): ND2',
        );
    });

    it('appends the compensation basis', () => {
        expect(
            articleNoteText({ ...base, khoan: [], can_cu_boi_thuong: 'BLDS' }),
        ).toBe('Tóm tắt\nKhung hình phạt: 1-2 năm\n\nCăn cứ bồi thường: BLDS');
    });
});
