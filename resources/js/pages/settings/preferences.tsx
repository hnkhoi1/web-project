import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import ChoiceGroup from '@/components/choice-group';
import Heading from '@/components/heading';
import { usePrefs } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import type { FontSize, NoteColor } from '@/types';

const fonts: { value: FontSize; label: string }[] = [
    { value: 'sm', label: 'Nhỏ' },
    { value: 'md', label: 'Vừa' },
    { value: 'lg', label: 'Lớn' },
    { value: 'xl', label: 'Rất lớn' },
];

const colors: { value: NoteColor; label: string }[] = [
    { value: 'yellow', label: 'Vàng' },
    { value: 'green', label: 'Xanh lá' },
    { value: 'blue', label: 'Xanh dương' },
    { value: 'rose', label: 'Hồng' },
    { value: 'violet', label: 'Tím' },
    { value: 'gray', label: 'Xám' },
];

export default function Preferences() {
    const { prefs, updatePrefs } = usePrefs();

    return (
        <>
            <Head title="Tùy chỉnh" />

            <h1 className="sr-only">Tùy chỉnh</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Tùy chỉnh giao diện"
                    description="Thay đổi được lưu ngay và áp dụng trên mọi thiết bị của bạn"
                />

                <AppearanceTabs />

                <ChoiceGroup
                    label="Cỡ chữ ghi chú"
                    choices={fonts}
                    value={prefs.font}
                    onChange={(font) => updatePrefs({ font })}
                />

                <div
                    role="radiogroup"
                    aria-label="Màu ghi chú mặc định"
                    className="space-y-2"
                >
                    <p className="text-sm font-medium">Màu ghi chú mặc định</p>
                    <div className="flex flex-wrap gap-2">
                        {colors.map((color) => (
                            <button
                                key={color.value}
                                type="button"
                                role="radio"
                                aria-checked={prefs.noteColor === color.value}
                                onClick={() =>
                                    updatePrefs({ noteColor: color.value })
                                }
                                className={cn(
                                    'note-surface flex items-center rounded-full border px-4 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                    prefs.noteColor === color.value &&
                                        'ring-2 ring-primary',
                                )}
                                data-color={color.value}
                            >
                                {color.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div
                    className="note-surface rounded-lg border p-4"
                    data-color="auto"
                >
                    <h3 className="mb-1 font-semibold">Xem trước</h3>
                    <p className="note-text">
                        Ghi chú mẫu: Điều 134 — quyền yêu cầu bồi thường chi phí
                        cứu chữa và thu nhập bị mất.
                    </p>
                </div>
            </div>
        </>
    );
}
