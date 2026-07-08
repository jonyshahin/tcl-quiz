function getCookie(name: string): string | null {
    const match = document.cookie.match(
        new RegExp('(^|;\\s*)' + name + '=([^;]*)'),
    );

    return match ? decodeURIComponent(match[2]) : null;
}

/**
 * POST JSON to a Laravel web route, forwarding the XSRF cookie as a header so
 * CSRF protection still applies. Used for the lightweight answer round-trip that
 * must not trigger a full Inertia page reload.
 */
export async function postJson<T>(url: string, body: unknown): Promise<T> {
    const token = getCookie('XSRF-TOKEN');

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    return (await response.json()) as T;
}
