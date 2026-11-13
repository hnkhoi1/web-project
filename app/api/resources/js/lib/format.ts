/** "13/11/2026 14:05" in the user's locale, or an en dash when there is no date. */
export function formatDateTime(iso: string | null | undefined): string {
    if (!iso) {
        return '–';
    }

    return new Date(iso).toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatDate(iso: string | null | undefined): string {
    if (!iso) {
        return '–';
    }

    return new Date(iso).toLocaleDateString('vi-VN');
}
