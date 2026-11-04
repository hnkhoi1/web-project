import { Link } from '@inertiajs/react';

const columns = [
    {
        title: 'Điều hướng',
        links: [
            { title: 'Tra cứu', href: '/tra-cuu' },
            { title: 'Tư vấn', href: '/tu-van' },
            { title: 'Giới thiệu', href: '/gioi-thieu' },
        ],
    },
    {
        title: 'Tài khoản',
        links: [
            { title: 'Hồ sơ', href: '/ho-so' },
            { title: 'Tùy chỉnh', href: '/tuy-chinh' },
            { title: 'Liên hệ', href: '/lien-he' },
        ],
    },
];

export function SiteFooter() {
    return (
        <footer className="mt-10 bg-ink py-8 text-[0.85rem] text-[#aeb6c2]">
            <div className="mx-auto flex max-w-6xl flex-col gap-6 px-5 sm:flex-row sm:justify-between">
                <div className="max-w-[340px]">
                    <strong className="text-white">Hình Sự Tra Cứu</strong>
                    <p className="mt-1">
                        Dữ liệu điều luật được biên soạn rút gọn cho mục đích
                        học tập, không thay thế văn bản pháp luật chính thức
                        hoặc tư vấn của luật sư.
                    </p>
                </div>
                <div className="flex gap-12">
                    {columns.map((col) => (
                        <div key={col.title}>
                            <h5 className="mb-2 font-sans text-[0.85rem] font-semibold text-white">
                                {col.title}
                            </h5>
                            <ul className="space-y-1.5">
                                {col.links.map((l) => (
                                    <li key={l.href}>
                                        <Link
                                            href={l.href}
                                            className="text-[#c7ccd6] hover:underline"
                                        >
                                            {l.title}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            </div>
        </footer>
    );
}
