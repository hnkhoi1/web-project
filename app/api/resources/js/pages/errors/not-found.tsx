import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

export default function NotFound() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Không tìm thấy trang" />

            <div className="mx-auto flex max-w-md flex-col items-center gap-4 py-16 text-center">
                <p className="text-5xl font-semibold">404</p>
                <h1 className="text-xl font-semibold">Không tìm thấy trang</h1>
                <p className="text-muted-foreground">
                    Trang bạn tìm không tồn tại hoặc đã được di chuyển.
                </p>
                <Button asChild>
                    <Link href={home()}>
                        {auth.user ? 'Về trang ghi chú' : 'Về trang đăng nhập'}
                    </Link>
                </Button>
            </div>
        </>
    );
}
