import type { LawArticleDetail } from '@/types';

/** Note body for "+ Lưu vào ghi chú" (same format as the original detail page). */
export function articleNoteText(a: LawArticleDetail): string {
    let text = `${a.tom_tat}\nKhung hình phạt: ${a.khung_hinh_phat}`;

    for (const k of a.khoan) {
        text += `\n\nKhoản ${k.so_khoan} (${k.dieu_kien}): ${k.noi_dung}`;

        if (k.quyen_loi) {
            text += `\nQuyền lợi: ${k.quyen_loi}`;
        }
    }

    if (a.can_cu_boi_thuong) {
        text += `\n\nCăn cứ bồi thường: ${a.can_cu_boi_thuong}`;
    }

    return text;
}
