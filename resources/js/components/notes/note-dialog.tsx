import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import {
    destroy,
    store,
    update,
} from '@/actions/App/Http/Controllers/NoteController';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { Note, NoteColor } from '@/types';

type ColorChoice = NoteColor | 'auto';

export const colorChoices: { value: ColorChoice; label: string }[] = [
    { value: 'auto', label: 'Mặc định' },
    { value: 'yellow', label: 'Vàng' },
    { value: 'green', label: 'Xanh lá' },
    { value: 'blue', label: 'Xanh dương' },
    { value: 'rose', label: 'Hồng' },
    { value: 'violet', label: 'Tím' },
    { value: 'gray', label: 'Xám' },
];

type FormData = {
    title: string;
    content: string;
    color: ColorChoice;
    pinned: boolean;
};

type EditProps = {
    /** The note being edited, or null for a new one. Dialog is closed when undefined. */
    note: Note | null | undefined;
    onClose: () => void;
};

export function NoteDialog({ note, onClose }: EditProps) {
    const form = useForm<FormData>({
        title: '',
        content: '',
        color: 'auto',
        pinned: false,
    });
    const { setData, clearErrors, errors } = form;
    const isOpen = note !== undefined;
    const noteId = note?.id;

    useEffect(() => {
        if (note === undefined) {
            return;
        }

        setData({
            title: note?.title ?? '',
            content: note?.content ?? '',
            color: note?.color ?? 'auto',
            pinned: note?.pinned ?? false,
        });
        clearErrors();
        // Reset only when a different note is opened.
    }, [noteId, isOpen]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = { preserveScroll: true, onSuccess: onClose };
        form.transform((data) => ({
            ...data,
            color: data.color === 'auto' ? null : data.color,
        }));

        if (note) {
            form.submit(update(note), options);
        } else {
            form.submit(store(), options);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {note ? 'Sửa ghi chú' : 'Ghi chú mới'}
                    </DialogTitle>
                    <DialogDescription>
                        Nhập tiêu đề hoặc nội dung (không cần cả hai).
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="note-title">Tiêu đề</Label>
                        <Input
                            id="note-title"
                            value={form.data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            maxLength={120}
                            autoFocus
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="note-content">Nội dung</Label>
                        <textarea
                            id="note-content"
                            value={form.data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            maxLength={5000}
                            rows={6}
                            className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError message={errors.content} />
                    </div>

                    <div
                        role="radiogroup"
                        aria-label="Màu ghi chú"
                        className="flex flex-wrap gap-2"
                    >
                        {colorChoices.map((c) => (
                            <button
                                key={c.value}
                                type="button"
                                role="radio"
                                aria-checked={form.data.color === c.value}
                                onClick={() => setData('color', c.value)}
                                data-color={c.value}
                                className={cn(
                                    'note-surface rounded-full border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                    form.data.color === c.value &&
                                        'ring-2 ring-primary',
                                )}
                            >
                                {c.label}
                            </button>
                        ))}
                    </div>
                    <InputError message={errors.color} />

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.pinned}
                            onChange={(e) =>
                                setData('pinned', e.target.checked)
                            }
                        />
                        Ghim ghi chú này lên đầu
                    </label>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Hủy
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Lưu
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

type DeleteProps = { note: Note | null; onClose: () => void };

export function DeleteNoteDialog({ note, onClose }: DeleteProps) {
    const form = useForm({});

    return (
        <Dialog
            open={note !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xóa ghi chú?</DialogTitle>
                    <DialogDescription>
                        Ghi chú “{note?.title}” sẽ bị xóa vĩnh viễn.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Hủy
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={form.processing}
                        onClick={() =>
                            note &&
                            form.submit(destroy(note), {
                                preserveScroll: true,
                                onSuccess: onClose,
                            })
                        }
                    >
                        Xóa
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
