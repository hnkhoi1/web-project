import { Head, Link, router } from '@inertiajs/react';
import { Check, RotateCcw } from 'lucide-react';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index, update } from '@/routes/admin/contacts';
import type { Paginated } from '@/types';

type ContactRow = {
    id: number;
    ho_ten: string;
    sdt: string | null;
    email: string;
    noi_dung: string;
    trang_thai: 'moi' | 'da_tra_loi';
    created_at: string | null;
};

type Props = {
    contacts: Paginated<ContactRow>;
    filter: 'moi' | 'da_tra_loi' | null;
};

const filters: { label: string; value: Props['filter'] }[] = [
    { label: 'Tất cả', value: null },
    { label: 'Chưa trả lời', value: 'moi' },
    { label: 'Đã trả lời', value: 'da_tra_loi' },
];

export default function AdminContacts({ contacts, filter }: Props) {
    const setStatus = (row: ContactRow, trang_thai: ContactRow['trang_thai']) =>
        router.patch(update(row.id), { trang_thai }, { preserveScroll: true });

    return (
        <>
            <Head title="Quản trị: Liên hệ" />

            <div
                className="flex flex-wrap gap-2"
                role="group"
                aria-label="Lọc theo trạng thái"
            >
                {filters.map((f) => (
                    <Link
                        key={f.label}
                        href={
                            f.value
                                ? index({ query: { trang_thai: f.value } })
                                : index()
                        }
                        className={cn(
                            'rounded-full border px-3 py-1 text-sm',
                            filter === f.value
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        )}
                        aria-current={filter === f.value ? 'true' : undefined}
                    >
                        {f.label}
                    </Link>
                ))}
            </div>

            {contacts.data.length === 0 ? (
                <p
                    className="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                    data-test="admin-empty"
                >
                    Chưa có thư liên hệ nào.
                </p>
            ) : (
                <ul className="space-y-3">
                    {contacts.data.map((c) => (
                        <li
                            key={c.id}
                            className="rounded-lg border bg-card p-4 text-card-foreground"
                            data-test="contact-row"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-medium">{c.ho_ten}</p>
                                    <p className="text-sm text-muted-foreground">
                                        <a
                                            href={`mailto:${c.email}`}
                                            className="underline underline-offset-4"
                                        >
                                            {c.email}
                                        </a>
                                        {c.sdt && <> · {c.sdt}</>}
                                        {' · '}
                                        {formatDateTime(c.created_at)}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span
                                        className={cn(
                                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            c.trang_thai === 'moi'
                                                ? 'bg-warn-bg text-warn'
                                                : 'bg-success-bg text-success',
                                        )}
                                    >
                                        {c.trang_thai === 'moi'
                                            ? 'Chưa trả lời'
                                            : 'Đã trả lời'}
                                    </span>
                                    {c.trang_thai === 'moi' ? (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                setStatus(c, 'da_tra_loi')
                                            }
                                        >
                                            <Check /> Đánh dấu đã trả lời
                                        </Button>
                                    ) : (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => setStatus(c, 'moi')}
                                        >
                                            <RotateCcw /> Mở lại
                                        </Button>
                                    )}
                                </div>
                            </div>
                            <p className="mt-3 text-sm whitespace-pre-wrap">
                                {c.noi_dung}
                            </p>
                        </li>
                    ))}
                </ul>
            )}

            <Pagination page={contacts} />
        </>
    );
}
