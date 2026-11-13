import { Check, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { patchJson } from '@/lib/api';
import { cn } from '@/lib/utils';
import type { ChatRating } from '@/types';

type Props = {
    historyId: number;
    initial?: ChatRating;
};

/** "Was this answer right?" Clicking the active choice again clears the rating. */
export function RatingButtons({ historyId, initial = null }: Props) {
    const [value, setValue] = useState<ChatRating>(initial);
    const [busy, setBusy] = useState(false);

    const choose = async (next: 'dung' | 'sai') => {
        if (busy) {
            return;
        }

        const target = value === next ? null : next;
        setBusy(true);

        try {
            await patchJson(`/lich-su/${historyId}/danh-gia`, {
                danh_gia: target,
            });
            setValue(target);
            toast.success(
                target === null
                    ? 'Đã bỏ đánh giá.'
                    : 'Cảm ơn bạn, đã ghi nhận đánh giá.',
            );
        } catch (e) {
            toast.error(
                e instanceof Error ? e.message : 'Không thể gửi đánh giá.',
            );
        } finally {
            setBusy(false);
        }
    };

    const base =
        'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium transition-colors disabled:opacity-60';

    return (
        <div
            className="flex flex-wrap items-center gap-2 pt-1 text-xs text-muted-foreground"
            data-test="rating"
        >
            <span id={`rating-label-${historyId}`}>
                Câu trả lời này có đúng không?
            </span>
            <div
                role="group"
                aria-labelledby={`rating-label-${historyId}`}
                className="flex gap-2"
            >
                <button
                    type="button"
                    className={cn(
                        base,
                        value === 'dung'
                            ? 'border-success bg-success-bg text-success'
                            : 'hover:bg-accent',
                    )}
                    aria-pressed={value === 'dung'}
                    disabled={busy}
                    onClick={() => void choose('dung')}
                >
                    <Check className="size-3.5" /> Đúng
                </button>
                <button
                    type="button"
                    className={cn(
                        base,
                        value === 'sai'
                            ? 'border-destructive bg-danger-bg text-destructive-foreground'
                            : 'hover:bg-accent',
                    )}
                    aria-pressed={value === 'sai'}
                    disabled={busy}
                    onClick={() => void choose('sai')}
                >
                    <X className="size-3.5" /> Chưa đúng
                </button>
            </div>
        </div>
    );
}
