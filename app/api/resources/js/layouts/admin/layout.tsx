import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { index as overview } from '@/routes/admin';
import { index as contacts } from '@/routes/admin/contacts';
import { index as laws } from '@/routes/admin/laws';
import { index as questions } from '@/routes/admin/questions';
import { index as users } from '@/routes/admin/users';

const tabs = [
    { title: 'Tổng quan', href: overview(), exact: true },
    { title: 'Liên hệ', href: contacts() },
    { title: 'Câu hỏi chatbot', href: questions() },
    { title: 'Điều luật', href: laws() },
    { title: 'Người dùng', href: users() },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const { currentUrl } = useCurrentUrl();

    return (
        <div>
            <Heading
                title="Quản trị"
                description="Theo dõi hoạt động, xử lý thư liên hệ và cập nhật điều luật. Dữ liệu người dùng chỉ để cải thiện dịch vụ."
            />

            <nav
                className="mb-8 flex flex-wrap gap-1 border-b"
                aria-label="Mục quản trị"
            >
                {tabs.map((item) => {
                    const path = item.href.url;
                    const active = item.exact
                        ? currentUrl.split('?')[0] === path
                        : currentUrl.startsWith(path);

                    return (
                        <Link
                            key={item.title}
                            href={item.href}
                            className={cn(
                                '-mb-px border-b-2 border-transparent px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground',
                                active && 'border-primary text-foreground',
                            )}
                            aria-current={active ? 'page' : undefined}
                        >
                            {item.title}
                        </Link>
                    );
                })}
            </nav>

            <section className="space-y-6">{children}</section>
        </div>
    );
}
