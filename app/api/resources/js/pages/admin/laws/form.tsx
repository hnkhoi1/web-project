import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/admin/laws';

type Num = number | string | null;

type ClauseData = {
    so_khoan: Num;
    dieu_kien: string | null;
    noi_dung: string;
    quyen_loi: string | null;
    tu_khoa: string | null;
    cau_hoi_lam_ro: string | null;
    ti_le_ton_thuong_min: Num;
    ti_le_ton_thuong_max: Num;
    gia_tri_tai_san_min: Num;
    gia_tri_tai_san_max: Num;
    so_nguoi_chet_min: Num;
    so_nguoi_chet_max: Num;
};

type ArticleData = {
    id: Num;
    chuong: string;
    tieu_de: string;
    tom_tat: string;
    tu_khoa: string;
    khung_hinh_phat: string;
    can_cu_boi_thuong: string | null;
    ghi_chu_quyen_loi: string | null;
    khoan: ClauseData[];
};

type Props = {
    article: ArticleData | null;
    chapters: string[];
};

const MAX_CLAUSES = 20;

const textareaClass =
    'min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';

const emptyClause = (so_khoan: number): ClauseData => ({
    so_khoan,
    dieu_kien: '',
    noi_dung: '',
    quyen_loi: '',
    tu_khoa: '',
    cau_hoi_lam_ro: '',
    ti_le_ton_thuong_min: '',
    ti_le_ton_thuong_max: '',
    gia_tri_tai_san_min: '',
    gia_tri_tai_san_max: '',
    so_nguoi_chet_min: '',
    so_nguoi_chet_max: '',
});

/** Inputs need strings; the server sends numbers and nulls. */
const forInput = (a: ArticleData): ArticleData => ({
    ...a,
    can_cu_boi_thuong: a.can_cu_boi_thuong ?? '',
    ghi_chu_quyen_loi: a.ghi_chu_quyen_loi ?? '',
    khoan: a.khoan.map((c) => {
        const out = { ...c };

        for (const key of Object.keys(out) as (keyof ClauseData)[]) {
            if (out[key] === null) {
                (out as Record<string, unknown>)[key] = '';
            }
        }

        return out;
    }),
});

const ranges: {
    key: 'ti_le_ton_thuong' | 'gia_tri_tai_san' | 'so_nguoi_chet';
    label: string;
    step: string;
}[] = [
    { key: 'ti_le_ton_thuong', label: 'Tỉ lệ tổn thương (%)', step: '0.01' },
    {
        key: 'gia_tri_tai_san',
        label: 'Giá trị tài sản (triệu đồng)',
        step: '0.001',
    },
    { key: 'so_nguoi_chet', label: 'Số người chết', step: '1' },
];

export default function AdminLawForm({ article, chapters }: Props) {
    const editing = article !== null;
    const form = useForm<ArticleData>(
        article
            ? forInput(article)
            : {
                  id: '',
                  chuong: chapters[0] ?? '',
                  tieu_de: '',
                  tom_tat: '',
                  tu_khoa: '',
                  khung_hinh_phat: '',
                  can_cu_boi_thuong: '',
                  ghi_chu_quyen_loi: '',
                  khoan: [emptyClause(1)],
              },
    );
    const { data, setData, errors } = form;
    const err = errors as Record<string, string | undefined>;

    const setClause = (i: number, key: keyof ClauseData, value: string) =>
        setData(
            'khoan',
            data.khoan.map((c, j) => (j === i ? { ...c, [key]: value } : c)),
        );

    const addClause = () => {
        const next =
            Math.max(0, ...data.khoan.map((c) => Number(c.so_khoan) || 0)) + 1;
        setData('khoan', [...data.khoan, emptyClause(next)]);
    };

    const removeClause = (i: number) =>
        setData(
            'khoan',
            data.khoan.filter((_, j) => j !== i),
        );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            form.transform(({ id: _id, ...rest }) => rest);
            form.submit(update(article.id as number), { preserveScroll: true });
        } else {
            form.transform((d) => d);
            form.submit(store(), { preserveScroll: true });
        }
    };

    const field = (
        key: keyof Omit<ArticleData, 'khoan' | 'id'>,
        label: string,
        opts: { required?: boolean; multiline?: boolean; hint?: string } = {},
    ) => (
        <div className="grid gap-2">
            <Label htmlFor={`law-${key}`}>
                {label}
                {opts.required && <span className="text-destructive"> *</span>}
            </Label>
            {opts.multiline ? (
                <textarea
                    id={`law-${key}`}
                    value={data[key] ?? ''}
                    onChange={(e) => setData(key, e.target.value)}
                    rows={3}
                    maxLength={5000}
                    className={textareaClass}
                />
            ) : (
                <Input
                    id={`law-${key}`}
                    value={data[key] ?? ''}
                    onChange={(e) => setData(key, e.target.value)}
                    maxLength={255}
                    list={key === 'chuong' ? 'law-chapters' : undefined}
                />
            )}
            {opts.hint && (
                <p className="text-xs text-muted-foreground">{opts.hint}</p>
            )}
            <InputError message={err[key]} />
        </div>
    );

    return (
        <>
            <Head
                title={
                    editing
                        ? `Quản trị: Sửa Điều ${article.id}`
                        : 'Quản trị: Thêm điều luật'
                }
            />

            <div className="flex items-center gap-3">
                <Button asChild variant="ghost" size="sm">
                    <Link href={index()}>
                        <ArrowLeft /> Danh sách điều luật
                    </Link>
                </Button>
                <h2 className="text-lg font-semibold">
                    {editing ? `Sửa Điều ${article.id}` : 'Thêm điều luật'}
                </h2>
            </div>

            <form onSubmit={submit} className="space-y-8" noValidate>
                <fieldset className="grid gap-4 rounded-lg border p-4 md:grid-cols-2">
                    <legend className="px-1 text-sm font-semibold">
                        Thông tin điều luật
                    </legend>

                    <div className="grid gap-2">
                        <Label htmlFor="law-id">
                            Số điều<span className="text-destructive"> *</span>
                        </Label>
                        <Input
                            id="law-id"
                            type="number"
                            min={1}
                            max={999}
                            value={data.id ?? ''}
                            onChange={(e) => setData('id', e.target.value)}
                            disabled={editing}
                        />
                        <p className="text-xs text-muted-foreground">
                            {editing
                                ? 'Số điều không đổi được sau khi tạo.'
                                : 'Ví dụ 173 cho Điều 173.'}
                        </p>
                        <InputError message={err.id} />
                    </div>

                    {field('chuong', 'Chương', {
                        required: true,
                        hint: 'Chọn chương có sẵn hoặc gõ tên chương mới.',
                    })}
                    <datalist id="law-chapters">
                        {chapters.map((c) => (
                            <option key={c} value={c} />
                        ))}
                    </datalist>

                    <div className="md:col-span-2">
                        {field('tieu_de', 'Tên tội danh', { required: true })}
                    </div>
                    {field('tom_tat', 'Tóm tắt', {
                        required: true,
                        multiline: true,
                    })}
                    {field('khung_hinh_phat', 'Khung hình phạt', {
                        required: true,
                        multiline: true,
                    })}
                    {field('tu_khoa', 'Từ khóa', {
                        required: true,
                        multiline: true,
                        hint: 'Các từ người dùng hay gõ khi hỏi về tội này, cách nhau bằng dấu cách.',
                    })}
                    {field('can_cu_boi_thuong', 'Căn cứ bồi thường', {
                        multiline: true,
                        hint: 'Để trống nếu tội không có người bị hại trực tiếp.',
                    })}
                    <div className="md:col-span-2">
                        {field('ghi_chu_quyen_loi', 'Ghi chú quyền lợi', {
                            multiline: true,
                        })}
                    </div>
                </fieldset>

                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="font-semibold">
                            Các khoản ({data.khoan.length})
                        </h3>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addClause}
                            disabled={data.khoan.length >= MAX_CLAUSES}
                        >
                            <Plus /> Thêm khoản
                        </Button>
                    </div>
                    <InputError message={err.khoan} />

                    {data.khoan.map((c, i) => {
                        const e = (k: string) => err[`khoan.${i}.${k}`];

                        return (
                            <fieldset
                                key={i}
                                className="grid gap-4 rounded-lg border p-4 md:grid-cols-2"
                                data-test="clause"
                            >
                                <legend className="px-1 text-sm font-semibold">
                                    Khoản {c.so_khoan || '?'}
                                </legend>

                                <div className="flex items-end gap-2 md:col-span-2">
                                    <div className="grid w-32 gap-2">
                                        <Label htmlFor={`k${i}-so`}>
                                            Số khoản
                                            <span className="text-destructive">
                                                {' '}
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id={`k${i}-so`}
                                            type="number"
                                            min={1}
                                            max={99}
                                            value={c.so_khoan ?? ''}
                                            onChange={(ev) =>
                                                setClause(
                                                    i,
                                                    'so_khoan',
                                                    ev.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="flex-1" />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-destructive"
                                        onClick={() => removeClause(i)}
                                        disabled={data.khoan.length <= 1}
                                        aria-label={`Xóa khoản ${c.so_khoan}`}
                                    >
                                        <Trash2 /> Xóa khoản
                                    </Button>
                                </div>
                                <InputError
                                    className="md:col-span-2"
                                    message={e('so_khoan')}
                                />

                                {(
                                    [
                                        ['noi_dung', 'Nội dung', true],
                                        [
                                            'dieu_kien',
                                            'Điều kiện áp dụng',
                                            false,
                                        ],
                                        [
                                            'quyen_loi',
                                            'Quyền lợi người bị hại',
                                            false,
                                        ],
                                        [
                                            'cau_hoi_lam_ro',
                                            'Câu hỏi làm rõ (chatbot hỏi lại)',
                                            false,
                                        ],
                                    ] as const
                                ).map(([k, label, required]) => (
                                    <div key={k} className="grid gap-2">
                                        <Label htmlFor={`k${i}-${k}`}>
                                            {label}
                                            {required && (
                                                <span className="text-destructive">
                                                    {' '}
                                                    *
                                                </span>
                                            )}
                                        </Label>
                                        <textarea
                                            id={`k${i}-${k}`}
                                            value={c[k] ?? ''}
                                            onChange={(ev) =>
                                                setClause(i, k, ev.target.value)
                                            }
                                            rows={3}
                                            maxLength={5000}
                                            className={textareaClass}
                                        />
                                        <InputError message={e(k)} />
                                    </div>
                                ))}

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor={`k${i}-tu_khoa`}>
                                        Từ khóa của khoản
                                    </Label>
                                    <Input
                                        id={`k${i}-tu_khoa`}
                                        value={c.tu_khoa ?? ''}
                                        onChange={(ev) =>
                                            setClause(
                                                i,
                                                'tu_khoa',
                                                ev.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={e('tu_khoa')} />
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3 md:col-span-2">
                                    {ranges.map((r) => (
                                        <div key={r.key} className="grid gap-2">
                                            <span className="text-sm font-medium">
                                                {r.label}
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    step={r.step}
                                                    placeholder="từ"
                                                    aria-label={`${r.label} từ, khoản ${c.so_khoan}`}
                                                    value={
                                                        c[`${r.key}_min`] ?? ''
                                                    }
                                                    onChange={(ev) =>
                                                        setClause(
                                                            i,
                                                            `${r.key}_min`,
                                                            ev.target.value,
                                                        )
                                                    }
                                                />
                                                <span aria-hidden>–</span>
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    step={r.step}
                                                    placeholder="đến"
                                                    aria-label={`${r.label} đến, khoản ${c.so_khoan}`}
                                                    value={
                                                        c[`${r.key}_max`] ?? ''
                                                    }
                                                    onChange={(ev) =>
                                                        setClause(
                                                            i,
                                                            `${r.key}_max`,
                                                            ev.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <InputError
                                                message={
                                                    e(`${r.key}_min`) ??
                                                    e(`${r.key}_max`)
                                                }
                                            />
                                        </div>
                                    ))}
                                </div>
                                <p className="text-xs text-muted-foreground md:col-span-2">
                                    Chatbot dùng các mức này để chọn khoản theo
                                    con số trong câu hỏi. Để trống nếu khoản
                                    không phụ thuộc vào con số.
                                </p>
                            </fieldset>
                        );
                    })}
                </div>

                <div className="flex justify-end gap-2">
                    <Button asChild type="button" variant="outline">
                        <Link href={index()}>Hủy</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {editing ? 'Lưu thay đổi' : 'Thêm điều luật'}
                    </Button>
                </div>
            </form>
        </>
    );
}
