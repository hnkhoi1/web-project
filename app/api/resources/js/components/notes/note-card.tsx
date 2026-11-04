import { Link } from '@inertiajs/react';
import { Pencil, Pin, PinOff, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Note } from '@/types';

type Props = {
    note: Note;
    onTogglePin: (note: Note) => void;
    onEdit: (note: Note) => void;
    onDelete: (note: Note) => void;
};

function formatTime(iso: string): string {
    const date = new Date(iso);

    return Number.isNaN(date.getTime())
        ? ''
        : date.toLocaleString('vi-VN', {
              dateStyle: 'short',
              timeStyle: 'short',
          });
}

export function NoteCard({ note, onTogglePin, onEdit, onDelete }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <article
            className="note-surface flex flex-col gap-2 rounded-lg border p-4 shadow-xs"
            data-color={note.color}
        >
            <h3 className="flex items-start gap-1.5 font-semibold break-words">
                {note.pinned && (
                    <Pin
                        className="mt-1 size-4 shrink-0"
                        aria-label="Đã ghim"
                    />
                )}
                {note.title}
            </h3>

            {note.content && (
                <button
                    type="button"
                    onClick={() => setOpen((v) => !v)}
                    aria-expanded={open}
                    className={cn(
                        'note-text text-left break-words whitespace-pre-wrap',
                        !open && 'line-clamp-4',
                    )}
                >
                    {note.content}
                </button>
            )}

            <div className="mt-auto flex flex-wrap items-center gap-x-3 pt-1 text-xs opacity-75">
                <span>{formatTime(note.updated_at)}</span>
                {note.source_type === 'article' && note.source_ref && (
                    <Link
                        href={`/dieu-luat/${note.source_ref}`}
                        className="underline underline-offset-2"
                    >
                        Điều {note.source_ref}
                    </Link>
                )}
                {note.source_type === 'chat' && <span>Từ chatbot</span>}
            </div>

            <div className="flex gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onTogglePin(note)}
                    aria-label={`${note.pinned ? 'Bỏ ghim' : 'Ghim'} ${note.title}`}
                >
                    {note.pinned ? <PinOff /> : <Pin />}
                    {note.pinned ? 'Bỏ ghim' : 'Ghim'}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onEdit(note)}
                    aria-label={`Sửa ghi chú ${note.title}`}
                >
                    <Pencil />
                    Sửa
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => onDelete(note)}
                    aria-label={`Xóa ghi chú ${note.title}`}
                >
                    <Trash2 />
                    Xóa
                </Button>
            </div>
        </article>
    );
}
