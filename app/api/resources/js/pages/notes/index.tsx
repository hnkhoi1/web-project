import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useDeferredValue, useMemo, useState } from 'react';
import { pin } from '@/actions/App/Http/Controllers/NoteController';
import { DeleteNoteDialog, NoteDialog } from '@/components/notes/note-dialog';
import { NoteCard } from '@/components/notes/note-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { filterNotes } from '@/lib/notes';
import type { Note } from '@/types';

type PopularArticle = {
    id: number;
    tieu_de: string;
    tom_tat: string;
    khung_hinh_phat: string;
};

type Props = { notes: Note[]; popular: PopularArticle[] };

export default function NotesIndex({ notes, popular }: Props) {
    const { auth } = usePage().props;
    const [query, setQuery] = useState('');
    const deferred = useDeferredValue(query);
    // undefined = closed, null = new note, Note = editing.
    const [editing, setEditing] = useState<Note | null | undefined>(undefined);
    const [deleting, setDeleting] = useState<Note | null>(null);

    const visible = useMemo(
        () => filterNotes(notes, deferred),
        [notes, deferred],
    );

    const togglePin = (note: Note) =>
        router.visit(pin(note), {
            data: { pinned: !note.pinned },
            preserveScroll: true,
        });

    return (
        <>
            <Head title="Ghi chú" />

            <section className="full-bleed bg-ink [background-image:radial-gradient(900px_300px_at_85%_-10%,rgb(169_121_58/0.18),transparent_60%)] text-on-ink">
                <div className="mx-auto grid max-w-6xl gap-7 px-5 py-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center lg:gap-12 lg:py-[60px]">
                    <div>
                        <p className="mb-2 font-serif text-[#c9aa76] italic">
                            Trang chủ của bạn
                        </p>
                        <h1 className="max-w-[20ch] text-[clamp(1.5rem,4.5vw,2rem)] text-white">
                            Xin chào, {auth.user?.name}
                        </h1>
                        <p className="mt-2 max-w-[56ch] text-on-ink-soft">
                            Mô tả tình huống bằng lời thường ngày để nhận điều
                            luật, mức xử lý và quyền lợi bồi thường tương ứng.
                            Những gì bạn muốn giữ lại sẽ nằm ở mục ghi chú bên
                            dưới.
                        </p>
                        <form
                            action="/tra-cuu"
                            method="get"
                            role="search"
                            className="mt-5 flex max-w-[560px] gap-2 rounded-lg bg-white p-1.5 shadow-card"
                        >
                            <label className="sr-only" htmlFor="home-q">
                                Tra cứu điều luật
                            </label>
                            <input
                                id="home-q"
                                type="text"
                                name="q"
                                placeholder="VD: trộm cắp, tai nạn giao thông, lừa đảo..."
                                className="min-h-11 min-w-0 flex-1 bg-transparent px-2.5 text-base text-[#16233a] outline-none"
                            />
                            <button
                                type="submit"
                                className="min-h-11 rounded-md bg-primary px-5 font-semibold text-white hover:opacity-90"
                            >
                                Tra cứu
                            </button>
                        </form>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        {[
                            {
                                href: '/tu-van',
                                title: 'Hỏi chatbot',
                                text: 'Nhận tư vấn từ tình huống của bạn',
                            },
                            {
                                href: '/tra-cuu',
                                title: 'Tra cứu điều luật',
                                text: 'Theo từ khóa, số điều hoặc chương',
                            },
                        ].map((c) => (
                            <Link
                                key={c.href}
                                href={c.href}
                                className="flex flex-col gap-1 rounded-lg border border-[#33415a] bg-white/[0.07] px-[18px] py-4 text-white hover:border-primary hover:no-underline"
                            >
                                <strong>{c.title}</strong>
                                <span className="text-[0.88rem] text-on-ink-soft">
                                    {c.text}
                                </span>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <div className="space-y-8 py-6">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                    <h2 className="text-[clamp(1.25rem,3.5vw,1.6rem)]">
                        Ghi chú của bạn
                    </h2>
                    <Button type="button" onClick={() => setEditing(null)}>
                        <Plus />
                        Ghi chú mới
                    </Button>
                </div>

                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Tìm trong ghi chú…"
                        aria-label="Tìm trong ghi chú"
                        className="pl-9"
                    />
                </div>

                {visible.length > 0 ? (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {visible.map((note) => (
                            <NoteCard
                                key={note.id}
                                note={note}
                                onTogglePin={togglePin}
                                onEdit={setEditing}
                                onDelete={setDeleting}
                            />
                        ))}
                    </div>
                ) : (
                    <p
                        className="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                        data-test="notes-empty"
                    >
                        {notes.length > 0
                            ? `Không có ghi chú nào khớp "${deferred.trim()}".`
                            : 'Chưa có ghi chú nào.'}
                    </p>
                )}

                <section className="space-y-3" aria-labelledby="popular">
                    <h2
                        id="popular"
                        className="text-[clamp(1.25rem,3.5vw,1.6rem)]"
                    >
                        Điều luật nổi bật
                    </h2>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {popular.map((a) => (
                            <div
                                key={a.id}
                                className="flex flex-col gap-2 rounded-lg border bg-card p-5 text-card-foreground"
                            >
                                <span className="font-serif text-[0.85rem] text-link">
                                    Điều {a.id}
                                </span>
                                <h3 className="font-semibold">
                                    <Link
                                        href={`/dieu-luat/${a.id}`}
                                        className="hover:underline"
                                    >
                                        {a.tieu_de}
                                    </Link>
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    {a.tom_tat}
                                </p>
                                <span className="mt-auto w-fit rounded-full bg-danger-bg px-2.5 py-0.5 text-[0.8rem] font-semibold text-destructive-foreground">
                                    {a.khung_hinh_phat}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>
            </div>

            <NoteDialog note={editing} onClose={() => setEditing(undefined)} />
            <DeleteNoteDialog
                note={deleting}
                onClose={() => setDeleting(null)}
            />
        </>
    );
}
