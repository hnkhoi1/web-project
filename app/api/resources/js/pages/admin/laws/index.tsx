import { Form, Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/format';
import { show } from '@/routes/law';
import { create, destroy, edit, index } from '@/routes/admin/laws';

type ArticleRow = {
    id: number;
    tieu_de: string;
    chuong: string;
    clauses_count: number;
    answers_count: number;
    updated_at: string | null;
};

type Props = {
    articles: ArticleRow[];
    filters: { q: string };
};

export default function AdminLaws({ articles, filters }: Props) {
    const [deleting, setDeleting] = useState<ArticleRow | null>(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(destroy(deleting.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <>
            <Head title="Quản trị: Điều luật" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <Form {...index.form()} className="flex gap-2">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            name="q"
                            defaultValue={filters.q}
                            placeholder="Tìm theo số điều hoặc tên…"
                            aria-label="Tìm điều luật"
                            className="w-64 pl-9"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Tìm
                    </Button>
                </Form>

                <Button asChild>
                    <Link href={create()} data-test="law-create">
                        <Plus /> Thêm điều luật
                    </Link>
                </Button>
            </div>

            <p className="text-sm text-muted-foreground">
                Thay đổi có hiệu lực ngay với trang tra cứu và chatbot. Nếu bật
                tìm kiếm ngữ nghĩa, chạy lại{' '}
                <code className="rounded bg-muted px-1">search:embed</code> sau
                khi sửa. Nên thêm cụm từ thường gặp của tội mới vào từ điển khái
                niệm để chatbot nhận ra tốt hơn.
            </p>

            {articles.length === 0 ? (
                <p
                    className="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                    data-test="admin-empty"
                >
                    Không tìm thấy điều luật nào.
                </p>
            ) : (
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[44rem] text-left text-sm">
                        <thead className="bg-muted text-muted-foreground">
                            <tr>
                                <th className="px-3 py-2 font-medium">Điều</th>
                                <th className="px-3 py-2 font-medium">
                                    Tội danh
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Số khoản
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Lượt chatbot dùng
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Cập nhật
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    <span className="sr-only">Thao tác</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {articles.map((a) => (
                                <tr key={a.id} data-test="law-row">
                                    <td className="px-3 py-2 font-medium">
                                        {a.id}
                                    </td>
                                    <td className="px-3 py-2">
                                        <Link
                                            href={show(a.id)}
                                            className="underline-offset-4 hover:underline"
                                        >
                                            {a.tieu_de}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {a.chuong}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        {a.clauses_count}
                                    </td>
                                    <td className="px-3 py-2">
                                        {a.answers_count}
                                    </td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {formatDateTime(a.updated_at)}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="ghost"
                                            >
                                                <Link
                                                    href={edit(a.id)}
                                                    aria-label={`Sửa Điều ${a.id}`}
                                                >
                                                    <Pencil /> Sửa
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-destructive"
                                                aria-label={`Xóa Điều ${a.id}`}
                                                onClick={() => setDeleting(a)}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Xóa Điều {deleting?.id}?</DialogTitle>
                        <DialogDescription>
                            {deleting && deleting.answers_count > 0
                                ? `Chatbot đã dùng điều này trong ${deleting.answers_count} câu trả lời nên không xóa được. Hãy sửa nội dung thay vì xóa.`
                                : `“${deleting?.tieu_de}” và toàn bộ các khoản sẽ bị xóa vĩnh viễn khỏi trang tra cứu và chatbot.`}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleting(null)}
                        >
                            Hủy
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={
                                processing || (deleting?.answers_count ?? 0) > 0
                            }
                            onClick={confirmDelete}
                        >
                            Xóa
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
