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

            <div className="mx-auto w-full max-w-6xl space-y-8 px-4 py-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        Xin chào, {auth.user?.name}
                    </h1>
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
                    <h2 id="popular" className="text-lg font-semibold">
                        Điều luật nổi bật
                    </h2>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {popular.map((a) => (
                            <div
                                key={a.id}
                                className="flex flex-col gap-2 rounded-lg border bg-card p-4 text-card-foreground"
                            >
                                <span className="text-xs font-medium text-muted-foreground">
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
                                <span className="mt-auto w-fit rounded-full bg-muted px-2.5 py-0.5 text-xs">
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
