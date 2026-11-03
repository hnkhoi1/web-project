import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { search } from '@/actions/App/Http/Controllers/LawController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { LawArticle } from '@/types';

type Props = {
    q: string;
    chuong: string;
    chapters: string[];
    results: LawArticle[];
};

// Radix Select cannot use '' as an item value.
const ALL = '__all__';

export default function LawSearch({ q, chuong, chapters, results }: Props) {
    const [query, setQuery] = useState(q);

    const go = (nextQuery: string, nextChuong: string) => {
        const data: Record<string, string> = {};

        if (nextQuery.trim()) {
            data.q = nextQuery.trim().slice(0, 200);
        }

        if (nextChuong) {
            data.chuong = nextChuong;
        }

        router.visit(search.url(), {
            data,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        go(query, chuong);
    };

    return (
        <>
            <Head title="Tra cứu điều luật" />

            <div className="mx-auto w-full max-w-4xl space-y-6 px-4 py-6">
                <h1 className="text-2xl font-semibold">Tra cứu điều luật</h1>

                <form
                    onSubmit={submit}
                    className="flex flex-col gap-2 sm:flex-row"
                    role="search"
                >
                    <div className="relative flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={query}
                            maxLength={200}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Nhập hành vi, tội danh hoặc số điều…"
                            aria-label="Từ khóa tra cứu"
                            className="pl-9"
                        />
                    </div>
                    <Select
                        value={chuong || ALL}
                        onValueChange={(v) => go(query, v === ALL ? '' : v)}
                    >
                        <SelectTrigger
                            aria-label="Chương"
                            className="w-full sm:w-72"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Tất cả chương</SelectItem>
                            {chapters.map((c) => (
                                <SelectItem key={c} value={c}>
                                    {c}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button type="submit">Tìm</Button>
                </form>

                <p
                    className="text-sm text-muted-foreground"
                    data-test="result-count"
                >
                    {results.length} kết quả{q ? ` cho "${q}"` : ''}
                </p>

                {results.length === 0 ? (
                    <div
                        className="rounded-lg border border-dashed p-8 text-center"
                        data-test="search-empty"
                    >
                        <p className="font-semibold">
                            Không tìm thấy điều luật phù hợp.
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Thử từ khóa khác, hoặc mô tả tình huống ở trang{' '}
                            <Link
                                href="/tu-van"
                                className="underline underline-offset-4"
                            >
                                Tư vấn
                            </Link>
                            .
                        </p>
                    </div>
                ) : (
                    <ul className="space-y-3">
                        {results.map((r) => (
                            <li
                                key={r.id}
                                className="flex items-start justify-between gap-4 rounded-lg border bg-card p-4 text-card-foreground"
                            >
                                <div className="space-y-2">
                                    <h2 className="font-semibold">
                                        <Link
                                            href={`/dieu-luat/${r.id}`}
                                            className="hover:underline"
                                        >
                                            Điều {r.id}. {r.tieu_de}
                                        </Link>
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {r.tom_tat}
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs">
                                            {r.chuong}
                                        </span>
                                        <span className="rounded-full border px-2.5 py-0.5 text-xs">
                                            {r.khung_hinh_phat}
                                        </span>
                                    </div>
                                </div>
                                {r.score !== undefined && (
                                    <span className="shrink-0 rounded-full bg-primary px-2.5 py-0.5 text-xs font-medium text-primary-foreground">
                                        Phù hợp {Math.round(r.score * 100)}%
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
