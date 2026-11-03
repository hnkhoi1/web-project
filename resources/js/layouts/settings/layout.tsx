import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { edit as editPreferences } from '@/routes/preferences';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const tabs: NavItem[] = [
    { title: 'Hồ sơ', href: editProfile() },
    { title: 'Đổi mật khẩu', href: editSecurity() },
    { title: 'Tùy chỉnh', href: editPreferences() },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <div className="mx-auto max-w-2xl">
            <Heading
                title="Tài khoản"
                description="Quản lý hồ sơ, mật khẩu và giao diện của bạn"
            />

            <nav
                className="mb-8 flex gap-1 border-b"
                aria-label="Cài đặt tài khoản"
            >
                {tabs.map((item) => (
                    <Link
                        key={item.title}
                        href={item.href}
                        className={cn(
                            '-mb-px border-b-2 border-transparent px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground',
                            isCurrentUrl(item.href) &&
                                'border-primary text-foreground',
                        )}
                    >
                        {item.title}
                    </Link>
                ))}
            </nav>

            <section className="space-y-12">{children}</section>
        </div>
    );
}
