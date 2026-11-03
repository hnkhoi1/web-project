import { Link, router } from '@inertiajs/react';
import { KeyRound, LogOut, SlidersHorizontal, UserRound } from 'lucide-react';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit as editPreferences } from '@/routes/preferences';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { User } from '@/types';

export function UserMenuContent({ user }: { user: User }) {
    const cleanup = useMobileNavigation();

    const items = [
        { title: 'Hồ sơ', href: editProfile(), icon: UserRound },
        { title: 'Đổi mật khẩu', href: editSecurity(), icon: KeyRound },
        {
            title: 'Tùy chỉnh',
            href: editPreferences(),
            icon: SlidersHorizontal,
        },
    ];

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                {items.map((item) => (
                    <DropdownMenuItem key={item.title} asChild>
                        <Link
                            className="block w-full cursor-pointer"
                            href={item.href}
                            prefetch
                            onClick={cleanup}
                        >
                            <item.icon className="mr-2" />
                            {item.title}
                        </Link>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logout()}
                    as="button"
                    onClick={() => {
                        cleanup();
                        router.flushAll();
                    }}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Đăng xuất
                </Link>
            </DropdownMenuItem>
        </>
    );
}
