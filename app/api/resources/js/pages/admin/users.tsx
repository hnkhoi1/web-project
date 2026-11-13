import { Form, Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import { index } from '@/routes/admin/users';
import type { Paginated } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    username: string | null;
    role: 'user' | 'admin';
    is_demo: boolean;
    notes_count: number;
    created_at: string | null;
};

type Props = {
    users: Paginated<UserRow>;
    filters: { q: string };
};

export default function AdminUsers({ users, filters }: Props) {
    return (
        <>
            <Head title="Quản trị: Người dùng" />

            <Form {...index.form()} className="flex gap-2">
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        name="q"
                        defaultValue={filters.q}
                        placeholder="Tìm theo tên hoặc email…"
                        aria-label="Tìm người dùng"
                        className="w-64 pl-9"
                    />
                </div>
                <Button type="submit" variant="secondary">
                    Tìm
                </Button>
            </Form>

            {users.data.length === 0 ? (
                <p
                    className="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                    data-test="admin-empty"
                >
                    Không tìm thấy người dùng nào.
                </p>
            ) : (
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[40rem] text-left text-sm">
                        <thead className="bg-muted text-muted-foreground">
                            <tr>
                                <th className="px-3 py-2 font-medium">Tên</th>
                                <th className="px-3 py-2 font-medium">Email</th>
                                <th className="px-3 py-2 font-medium">
                                    Vai trò
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Ghi chú
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Ngày tạo
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {users.data.map((u) => (
                                <tr key={u.id} data-test="user-row">
                                    <td className="px-3 py-2">
                                        {u.name}
                                        {u.username && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                @{u.username}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {u.email}
                                    </td>
                                    <td className="px-3 py-2">
                                        {u.role === 'admin' && (
                                            <span className="rounded-full bg-info-bg px-2.5 py-0.5 text-xs font-semibold text-info">
                                                Admin
                                            </span>
                                        )}
                                        {u.is_demo && (
                                            <span className="rounded-full bg-warn-bg px-2.5 py-0.5 text-xs font-semibold text-warn">
                                                Demo
                                            </span>
                                        )}
                                        {u.role !== 'admin' && !u.is_demo && (
                                            <span className="text-muted-foreground">
                                                Người dùng
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {u.notes_count}
                                    </td>
                                    <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                        {formatDate(u.created_at)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <Pagination page={users} />
        </>
    );
}
