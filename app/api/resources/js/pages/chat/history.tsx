import { Form, Head, Link, router } from '@inertiajs/react';
import { Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { clear, destroy, index, show } from '@/routes/history';
import type { ChatRating, Paginated } from '@/types';

type Row = {
    id: number;
    cau_hoi: string;
    dieu_luat_id: number | null;
    tieu_de: string | null;
    khoan_so: number | null;
    danh_gia: ChatRating;
    created_at: string | null;
};

type Props = {
    history: Paginated<Row>;
    filters: { q: string };
};

/** A destructive button that asks "are you sure?" on the first click. */
function ConfirmButton({
    label,
    confirmLabel,
    onConfirm,
    ariaLabel,
}: {
    label: string;
    confirmLabel: string;
    onConfirm: () => void;
    ariaLabel?: string;
}) {
    const [armed, setArmed] = useState(false);

    return armed ? (
        <span className="flex items-center gap-1">
            <Button
                type="button"
                size="sm"
                variant="destructive"
                onClick={onConfirm}
                autoFocus
            >
                {confirmLabel}
            </Button>
            <Button
                type="button"
                size="sm"
                variant="ghost"
                onClick={() => setArmed(false)}
            >
                Hủy
            </Button>
        </span>
    ) : (
        <Button
            type="button"
            size="sm"
            variant="ghost"
            aria-label={ariaLabel}
            onClick={() => setArmed(true)}
        >
            <Trash2 /> {label}
        </Button>
    );
}

export default function ChatHistory({ history, filters }: Props) {
    return (
        <>
            <Head title="Lịch sử câu hỏi" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Lịch sử câu hỏi
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Những câu bạn đã hỏi chatbot. Bấm vào một câu để xem
                            lại câu trả lời.
                        </p>
                    </div>
                    {history.total > 0 && (
                        <ConfirmButton
                            label="Xóa toàn bộ"
                            confirmLabel="Xóa hết, không khôi phục được"
                            onConfirm={() => router.delete(clear())}
                        />
                    )}
                </div>

                <Form {...index.form()} className="flex gap-2">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            name="q"
                            defaultValue={filters.q}
                            placeholder="Tìm trong câu hỏi…"
                            aria-label="Tìm trong lịch sử"
                            className="w-64 pl-9"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Tìm
                    </Button>
                </Form>

                {history.data.length === 0 ? (
                    <div
                        className="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                        data-test="history-empty"
                    >
                        <p>
                            {filters.q
                                ? `Không có câu hỏi nào khớp "${filters.q}".`
                                : 'Bạn chưa hỏi chatbot câu nào.'}
                        </p>
                        {!filters.q && (
                            <Link
                                href="/tu-van"
                                className="mt-2 inline-block underline underline-offset-4"
                            >
                                Bắt đầu hỏi
                            </Link>
                        )}
                    </div>
                ) : (
                    <ul className="space-y-3">
                        {history.data.map((h) => (
                            <li
                                key={h.id}
                                className="flex flex-wrap items-start justify-between gap-3 rounded-lg border bg-card p-4 text-card-foreground"
                                data-test="history-row"
                            >
                                <div className="min-w-0 flex-1 space-y-1">
                                    <Link
                                        href={show(h.id)}
                                        className="block font-medium hover:underline"
                                    >
                                        {h.cau_hoi}
                                    </Link>
                                    <p className="text-sm text-muted-foreground">
                                        {formatDateTime(h.created_at)}
                                        {' · '}
                                        {h.dieu_luat_id ? (
                                            <>
                                                Điều {h.dieu_luat_id}
                                                {h.tieu_de
                                                    ? `. ${h.tieu_de}`
                                                    : ''}
                                                {h.khoan_so
                                                    ? `, khoản ${h.khoan_so}`
                                                    : ''}
                                            </>
                                        ) : (
                                            'Chưa tìm được điều luật phù hợp'
                                        )}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {h.danh_gia && (
                                        <span
                                            className={cn(
                                                'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                h.danh_gia === 'dung'
                                                    ? 'bg-success-bg text-success'
                                                    : 'bg-danger-bg text-destructive-foreground',
                                            )}
                                        >
                                            {h.danh_gia === 'dung'
                                                ? 'Bạn đánh giá: đúng'
                                                : 'Bạn đánh giá: chưa đúng'}
                                        </span>
                                    )}
                                    <ConfirmButton
                                        label="Xóa"
                                        confirmLabel="Xóa câu này"
                                        ariaLabel={`Xóa câu hỏi: ${h.cau_hoi}`}
                                        onConfirm={() =>
                                            router.delete(destroy(h.id), {
                                                preserveScroll: true,
                                            })
                                        }
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination page={history} />
            </div>
        </>
    );
}
