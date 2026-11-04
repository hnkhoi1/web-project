import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { postJson } from '@/lib/api';
import { articleNoteText } from '@/lib/law';
import type { LawArticleDetail, RelatedArticle } from '@/types';

type Props = { article: LawArticleDetail; related: RelatedArticle[] };

export default function LawShow({ article, related }: Props) {
    const [saved, setSaved] = useState(false);
    const [saving, setSaving] = useState(false);

    const save = async () => {
        setSaving(true);

        try {
            await postJson('/ghi-chu', {
                title: `Điều ${article.id}. ${article.tieu_de}`,
                content: articleNoteText(article).slice(0, 5000),
                source_type: 'article',
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

    return (
        <>
            <Head title={`Điều ${article.id}`} />

            <div className="mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
                <header className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        <Link
                            href="/tra-cuu"
                            className="underline-offset-4 hover:underline"
                        >
                            Tra cứu
                        </Link>{' '}
                        › {article.chuong}
                    </p>
                    <h1 className="text-2xl font-semibold">
                        Điều {article.id}. {article.tieu_de}
                    </h1>
                    <div className="flex flex-wrap gap-2">
                        <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs">
                            {article.chuong}
                        </span>
                        <span className="rounded-full border px-2.5 py-0.5 text-xs">
                            {article.khung_hinh_phat}
                        </span>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={saved || saving}
                        onClick={() => void save()}
                    >
                        {saved ? '✓ Đã lưu vào ghi chú' : '+ Lưu vào ghi chú'}
                    </Button>
                </header>

                <div className="grid gap-6 md:grid-cols-[1fr_18rem]">
                    <div className="space-y-4">
                        <p>{article.tom_tat}</p>

                        {article.khoan.map((k) => (
                            <section
                                key={k.so_khoan}
                                className="space-y-2 rounded-lg border bg-card p-4 text-card-foreground"
                            >
                                <p>
                                    <b>Khoản {k.so_khoan}.</b>{' '}
                                    <em>{k.dieu_kien}</em>
                                </p>
                                <p>
                                    <b>Mức xử lý:</b> {k.noi_dung}
                                </p>
                                {k.quyen_loi && (
                                    <p className="rounded-md bg-muted p-3 text-sm">
                                        <b>Quyền lợi của người bị hại:</b>{' '}
                                        {k.quyen_loi}
                                    </p>
                                )}
                            </section>
                        ))}

                        {article.ghi_chu_quyen_loi && (
                            <p className="rounded-md bg-muted p-3 text-sm">
                                <b>Về quyền lợi/bồi thường:</b>{' '}
                                {article.ghi_chu_quyen_loi}
                            </p>
                        )}
                    </div>

                    <aside className="space-y-4">
                        {article.can_cu_boi_thuong && (
                            <div className="rounded-lg border bg-card p-4 text-card-foreground">
                                <h2 className="mb-2 font-semibold">
                                    Căn cứ bồi thường
                                </h2>
                                <p className="text-sm">
                                    {article.can_cu_boi_thuong}
                                </p>
                            </div>
                        )}
                        {related.length > 0 && (
                            <div className="rounded-lg border bg-card p-4 text-card-foreground">
                                <h2 className="mb-2 font-semibold">
                                    Điều luật liên quan
                                </h2>
                                <ul className="space-y-1 text-sm">
                                    {related.map((r) => (
                                        <li key={r.id}>
                                            <Link
                                                href={`/dieu-luat/${r.id}`}
                                                className="hover:underline"
                                            >
                                                Điều {r.id}. {r.tieu_de}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}
