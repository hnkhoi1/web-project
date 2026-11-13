import { Form, Head, Link } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index } from '@/routes/admin/questions';
import type { Paginated } from '@/types';

type QuestionRow = {
    id: number;
    cau_hoi: string;
    user: string | null;
    dieu_luat_id: number | null;
    tieu_de: string | null;
    khoan_so: number | null;
    diem: number | null;
    danh_gia: 'dung' | 'sai' | null;
    created_at: string | null;
};

type Props = {
    questions: Paginated<QuestionRow>;
    filters: { q: string; loai: 'tat-ca' | 'khong-khop' | 'sai' };
};

export default function AdminQuestions({ questions, filters }: Props) {
    const tabs = [
        { label: 'Tất cả', loai: 'tat-ca' as const },
        { label: 'Không khớp điều luật', loai: 'khong-khop' as const },
        { label: 'Đánh giá sai', loai: 'sai' as const },
    ];

    return (
        <>
            <Head title="Quản trị: Câu hỏi chatbot" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <div
                    className="flex flex-wrap gap-2"
                    role="group"
                    aria-label="Lọc câu hỏi"
                >
                    {tabs.map((t) => (
                        <Link
                            key={t.loai}
                            href={index({
                                query: {
                                    ...(filters.q ? { q: filters.q } : {}),
                                    ...(t.loai !== 'tat-ca'
                                        ? { loai: t.loai }
                                        : {}),
                                },
                            })}
                            className={cn(
                                'rounded-full border px-3 py-1 text-sm',
                                filters.loai === t.loai
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-accent',
                            )}
                            aria-current={
                                filters.loai === t.loai ? 'true' : undefined
                            }
                        >
                            {t.label}
                        </Link>
                    ))}
                </div>

                <Form
                    {...index.form()}
                    className="flex gap-2"
                    key={filters.loai}
                >
                    {filters.loai !== 'tat-ca' && (
                        <input type="hidden" name="loai" value={filters.loai} />
                    )}
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            name="q"
                            defaultValue={filters.q}
                            placeholder="Tìm trong câu hỏi…"
                            aria-label="Tìm trong câu hỏi"
                            className="w-56 pl-9"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Tìm
                    </Button>
                </Form>
            </div>

            {questions.data.length === 0 ? (
                <p
                    className="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                    data-test="admin-empty"
                >
                    Không có câu hỏi nào.
                </p>
            ) : (
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[40rem] text-left text-sm">
                        <thead className="bg-muted text-muted-foreground">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    Thời gian
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Câu hỏi
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Người hỏi
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Kết quả
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Đánh giá
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {questions.data.map((q) => (
                                <tr key={q.id} data-test="question-row">
                                    <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                        {formatDateTime(q.created_at)}
                                    </td>
                                    <td className="px-3 py-2">{q.cau_hoi}</td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {q.user ?? '–'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {q.dieu_luat_id ? (
                                            <Link
                                                href={`/dieu-luat/${q.dieu_luat_id}`}
                                                className="hover:underline"
                                            >
                                                Điều {q.dieu_luat_id}
                                                {q.khoan_so
                                                    ? `, khoản ${q.khoan_so}`
                                                    : ''}
                                                {q.diem !== null && (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        (
                                                        {Math.round(
                                                            q.diem * 100,
                                                        )}
                                                        %)
                                                    </span>
                                                )}
                                            </Link>
                                        ) : (
                                            <span className="rounded-full bg-danger-bg px-2.5 py-0.5 text-xs font-semibold text-destructive-foreground">
                                                Không khớp
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {q.danh_gia === 'sai' && (
                                            <span className="rounded-full bg-danger-bg px-2.5 py-0.5 text-xs font-semibold text-destructive-foreground">
                                                Sai
                                            </span>
                                        )}
                                        {q.danh_gia === 'dung' && (
                                            <span className="rounded-full bg-success-bg px-2.5 py-0.5 text-xs font-semibold text-success">
                                                Đúng
                                            </span>
                                        )}
                                        {q.danh_gia === null && (
                                            <span className="text-muted-foreground">
                                                –
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <Pagination page={questions} />
        </>
    );
}
