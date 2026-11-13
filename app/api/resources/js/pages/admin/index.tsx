import { Head, Link } from '@inertiajs/react';
import { index as contacts } from '@/routes/admin/contacts';
import { index as questions } from '@/routes/admin/questions';
import { index as users } from '@/routes/admin/users';

type Stats = {
    users: number;
    newUsers: number;
    notes: number;
    questions: number;
    questionsToday: number;
    missed: number;
    missedRate: number;
    rated: number;
    ratedWrong: number;
    newContacts: number;
};

type ChartDay = { day: string; total: number; missed: number };
type TopArticle = { id: number; tieu_de: string | null; total: number };

type Props = { stats: Stats; chart: ChartDay[]; topArticles: TopArticle[] };

function StatCard({
    label,
    value,
    hint,
    href,
}: {
    label: string;
    value: number | string;
    hint?: string;
    href?: string;
}) {
    const body = (
        <>
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 font-serif text-3xl font-semibold">{value}</p>
            {hint && (
                <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
            )}
        </>
    );
    const cls = 'rounded-lg border bg-card p-4 text-card-foreground';

    return href ? (
        <Link href={href} className={`${cls} block hover:border-primary`}>
            {body}
        </Link>
    ) : (
        <div className={cls}>{body}</div>
    );
}

export default function AdminIndex({ stats, chart, topArticles }: Props) {
    const max = Math.max(1, ...chart.map((d) => d.total));

    return (
        <>
            <Head title="Quản trị" />

            <div
                className="grid grid-cols-2 gap-4 lg:grid-cols-4"
                data-test="admin-stats"
            >
                <StatCard
                    label="Người dùng"
                    value={stats.users}
                    hint={`${stats.newUsers} mới trong 7 ngày`}
                    href={users().url}
                />
                <StatCard label="Ghi chú" value={stats.notes} />
                <StatCard
                    label="Câu hỏi chatbot"
                    value={stats.questions}
                    hint={`${stats.questionsToday} hôm nay`}
                    href={questions().url}
                />
                <StatCard
                    label="Không khớp điều luật"
                    value={stats.missed}
                    hint={`${stats.missedRate}% số câu hỏi`}
                    href={questions({ query: { loai: 'khong-khop' } }).url}
                />
                <StatCard
                    label="Đánh giá sai"
                    value={stats.ratedWrong}
                    hint={`${stats.rated} câu đã được đánh giá`}
                    href={questions({ query: { loai: 'sai' } }).url}
                />
                <StatCard
                    label="Thư liên hệ mới"
                    value={stats.newContacts}
                    href={contacts({ query: { trang_thai: 'moi' } }).url}
                />
            </div>

            <section
                className="rounded-lg border bg-card p-5"
                aria-labelledby="chart-title"
            >
                <h3 id="chart-title" className="mb-1 text-base">
                    Câu hỏi chatbot 14 ngày gần nhất
                </h3>
                <p className="mb-4 flex items-center gap-4 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1.5">
                        <span className="size-2.5 rounded-sm bg-primary" /> Có
                        điều luật khớp
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="size-2.5 rounded-sm bg-destructive" />{' '}
                        Không khớp
                    </span>
                </p>
                <div
                    className="flex h-40 items-end gap-1.5"
                    role="img"
                    aria-label="Biểu đồ số câu hỏi theo ngày"
                >
                    {chart.map((d) => (
                        <div
                            key={d.day}
                            className="flex h-full flex-1 flex-col justify-end"
                            title={`${d.day}: ${d.total} câu hỏi, ${d.missed} không khớp`}
                        >
                            <div
                                className="flex w-full flex-col justify-end overflow-hidden rounded-t-sm"
                                style={{ height: `${(d.total / max) * 100}%` }}
                            >
                                <div
                                    className="bg-destructive"
                                    style={{
                                        height: `${d.total ? (d.missed / d.total) * 100 : 0}%`,
                                    }}
                                />
                                <div className="flex-1 bg-primary" />
                            </div>
                        </div>
                    ))}
                </div>
                <div className="mt-2 flex gap-1.5 text-[0.65rem] text-muted-foreground">
                    {chart.map((d, i) => (
                        <span key={d.day} className="flex-1 text-center">
                            {i % 2 === 0 ? d.day : ''}
                        </span>
                    ))}
                </div>
            </section>

            <section
                className="rounded-lg border bg-card p-5"
                aria-labelledby="top-title"
            >
                <h3 id="top-title" className="mb-3 text-base">
                    Điều luật được hỏi nhiều nhất
                </h3>
                {topArticles.length > 0 ? (
                    <ol className="space-y-2 text-sm">
                        {topArticles.map((a) => (
                            <li
                                key={a.id}
                                className="flex items-center justify-between gap-3"
                            >
                                <Link
                                    href={`/dieu-luat/${a.id}`}
                                    className="hover:underline"
                                >
                                    Điều {a.id}. {a.tieu_de}
                                </Link>
                                <span className="text-muted-foreground">
                                    {a.total} lượt
                                </span>
                            </li>
                        ))}
                    </ol>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Chưa có câu hỏi nào được khớp.
                    </p>
                )}
            </section>
        </>
    );
}
