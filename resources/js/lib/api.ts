function xsrfToken(): string {
    const match = /(?:^|; )XSRF-TOKEN=([^;]*)/.exec(document.cookie);

    return match?.[1] ? decodeURIComponent(match[1]) : '';
}

export class ApiError extends Error {
    constructor(
        message: string,
        readonly status: number,
    ) {
        super(message);
    }
}

type ErrorBody = { message?: string; errors?: Record<string, string[]> };

/** POST JSON with the XSRF cookie token; resolves with the parsed JSON body. */
export async function postJson<T>(url: string, body: unknown): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        let message = 'Đã có lỗi xảy ra. Vui lòng thử lại.';

        try {
            const data = (await response.json()) as ErrorBody;
            message =
                Object.values(data.errors ?? {})[0]?.[0] ??
                data.message ??
                message;
        } catch {
            // Non-JSON error body: keep the generic message.
        }

        throw new ApiError(message, response.status);
    }

    return (await response.json()) as T;
}
