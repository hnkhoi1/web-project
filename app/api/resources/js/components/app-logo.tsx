import { usePage } from '@inertiajs/react';

/** Scales of justice in bronze, the mark of the original site. */
function BrandMark({ size = 34 }: { size?: number }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 48 48"
            fill="none"
            aria-hidden="true"
            focusable="false"
            className="flex-none"
        >
            <g stroke="#C9AA76" strokeWidth="2">
                <path d="M24 6V42" />
                <path d="M8 12H40" />
                <path
                    d="M8 12L3 22C3 26 7 28 10.5 28C14 28 18 26 18 22L13 12"
                    strokeLinejoin="round"
                />
                <path
                    d="M40 12L35 22C35 26 39 28 42.5 28C46 28 50 26 50 22"
                    strokeLinejoin="round"
                />
                <path d="M15 42H33" />
            </g>
            <rect x="18" y="4" width="12" height="4" rx="1" fill="#C9AA76" />
        </svg>
    );
}

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <BrandMark />
            <span className="flex flex-col leading-tight">
                <strong className="truncate font-serif text-[1.1rem] font-semibold">
                    {name}
                </strong>
                <span className="hidden text-[0.72rem] text-[#b9c0cc] sm:inline">
                    Tra cứu &amp; tư vấn Luật Hình sự VN
                </span>
            </span>
        </>
    );
}
