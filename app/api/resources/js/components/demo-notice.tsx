import { usePage } from '@inertiajs/react';
import { Info } from 'lucide-react';
import InputError from '@/components/input-error';

/** Explains why controls are disabled for the shared demo account. */
export function DemoNotice({ error }: { error?: string }) {
    const { auth } = usePage().props;

    if (!auth.user?.isDemo) {
        return error ? <InputError message={error} /> : null;
    }

    return (
        <p
            className="flex items-start gap-2 rounded-lg border bg-muted/50 p-3 text-sm text-muted-foreground"
            data-test="demo-notice"
        >
            <Info className="mt-0.5 size-4 shrink-0" />
            Đây là tài khoản demo dùng chung nên không thể thay đổi hồ sơ, ảnh
            đại diện hoặc mật khẩu. Bạn vẫn có thể lưu tùy chỉnh giao diện.
        </p>
    );
}
