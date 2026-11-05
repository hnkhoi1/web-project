import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { postJson } from '@/lib/api';
import type { ChatAnswer } from '@/types';

type Props = { answer: ChatAnswer };

function Para({ label, children }: { label?: string; children: ReactNode }) {
    return (
        <p>
            {label && <b>{label} </b>}
            {children}
        </p>
    );
}

export default function AnswerMessage({ answer }: Props) {
    const [saved, setSaved] = useState(false);
    const [saving, setSaving] = useState(false);

    if (!answer.found) {
        return (
            <div className="space-y-2">
                <p>
                    Mình chưa tìm được điều luật phù hợp với mô tả này. Bạn thử
                    nêu rõ hành vi (ví dụ: trộm xe máy, lừa đảo qua mạng, đánh
                    người gây thương tích) hoặc dùng trang Tra cứu nhé.
                </p>
                <Link href="/tra-cuu" className="underline underline-offset-4">
                    Mở trang Tra cứu
                </Link>
            </div>
        );
    }

    const { article } = answer;

    const save = async () => {
        setSaving(true);

        try {
            await postJson('/ghi-chu', {
                title: answer.note_title,
                content: answer.note_text,
                source_type: 'chat',
                source_ref: String(article.id),
            });
            setSaved(true);
            toast.success('Đã lưu vào ghi chú.');
        } catch (e) {
            toast.error(
                e instanceof Error ? e.message : 'Không thể lưu ghi chú.',
            );
        } finally {
            setSaving(false);
        }
    };

    const rights = () => {
        if (answer.mode === 'specific' && answer.quyen_loi) {
            return (
                <Para label="Quyền lợi của bạn (người bị hại):">
                    {answer.quyen_loi}
                    {answer.can_cu ? ` (${answer.can_cu})` : ''}
                </Para>
            );
        }

        if (answer.ghi_chu_quyen_loi) {
            return (
                <Para label="Về quyền lợi/bồi thường:">
                    {answer.ghi_chu_quyen_loi}
                </Para>
            );
        }

        return null;
    };

    return (
        <div className="space-y-2">
            {answer.mode === 'overview' ? (
                <>
                    <Para>
                        Tình huống của bạn có thể liên quan đến Điều{' '}
                        {article.id}. {article.tieu_de} ({article.chuong}). Mức
                        xử lý phụ thuộc mức độ hậu quả:
                    </Para>
                    {answer.khoan_all.map((k) => (
                        <Para
                            key={k.so_khoan}
                            label={`Khoản ${k.so_khoan} (${k.dieu_kien ?? ''}):`}
                        >
                            {k.noi_dung}
                        </Para>
                    ))}
                    <Para label="Để nêu đúng quyền lợi:">{answer.ask}</Para>
                    {rights()}
                </>
            ) : (
                <>
                    <Para>
                        Tình huống của bạn liên quan đến Điều {article.id}.{' '}
                        {article.tieu_de} ({article.chuong}), cụ thể Khoản{' '}
                        {answer.khoan.so_khoan} —{' '}
                        {answer.khoan.dieu_kien?.replace(/[.\s]+$/, '')}.
                    </Para>
                    {answer.role === 'nan_nhan' && rights()}
                    <Para label="Mức xử lý với người vi phạm:">
                        {answer.khoan.noi_dung}
                    </Para>
                    {answer.role !== 'nan_nhan' && rights()}
                </>
            )}

            {answer.alt && (
                <p>
                    Có thể bạn muốn hỏi:{' '}
                    <Link
                        href={`/dieu-luat/${answer.alt.id}`}
                        className="underline underline-offset-4"
                    >
                        Điều {answer.alt.id}. {answer.alt.tieu_de}
                    </Link>
                </p>
            )}

            <div className="flex flex-wrap items-center gap-3 pt-1">
                <Link
                    href={`/dieu-luat/${article.id}`}
                    className="text-sm underline underline-offset-4"
                >
                    Xem chi tiết Điều {article.id}
                </Link>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    disabled={saved || saving}
                    onClick={save}
                >
                    {saved ? '✓ Đã lưu' : '+ Lưu vào ghi chú'}
                </Button>
            </div>

            <p className="text-xs text-muted-foreground">
                (Tham khảo từ dữ liệu demo rút gọn, không thay thế tư vấn của
                luật sư.)
            </p>
        </div>
    );
}
