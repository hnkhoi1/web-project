import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

type Props = { status: 403 | 419 | 429 | 500 };

const COPY: Record<
    Props['status'],
    { title: string; text: string; reload?: boolean }
> = {
    403: {
        title: 'Không có quyền truy cập',
        text: 'Bạn không được phép xem trang này.',
    },
    419: {
        title: 'Phiên làm việc đã hết hạn',
        text: 'Trang đã mở quá lâu nên cần được tải lại. Dữ liệu bạn chưa lưu có thể phải nhập lại.',
        reload: true,
    },
    429: {
        title: 'Bạn thao tác quá nhanh',
        text: 'Vui lòng đợi một chút rồi thử lại.',
        reload: true,
    },
    500: {
        title: 'Đã có lỗi xảy ra',
        text: 'Hệ thống gặp sự cố ngoài ý muốn. Bạn thử lại sau ít phút, hoặc liên hệ với chúng tôi nếu lỗi vẫn còn.',
        reload: true,
    },
};

export default function ErrorPage({ status }: Props) {
    const { auth } = usePage().props;
    const copy = COPY[status];

    return (
        <>
            <Head title={copy.title} />

            <div
                className="mx-auto flex max-w-md flex-col items-center gap-4 py-16 text-center"
                data-test="error-page"
            >
                <p className="text-5xl font-semibold">{status}</p>
                <h1 className="text-xl font-semibold">{copy.title}</h1>
                <p className="text-muted-foreground">{copy.text}</p>
                <div className="flex flex-wrap justify-center gap-2">
                    {copy.reload && (
                        <Button onClick={() => window.location.reload()}>
                            Tải lại trang
                        </Button>
                    )}
                    <Button
                        variant={copy.reload ? 'outline' : 'default'}
                        asChild
                    >
                        <Link href={home()}>
                            {auth.user
                                ? 'Về trang ghi chú'
                                : 'Về trang đăng nhập'}
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
