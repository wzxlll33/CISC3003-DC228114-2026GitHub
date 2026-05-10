(() => {
    const parseJson = async (response) => {
        const text = await response.text();

        if (!text) {
            return null;
        }

        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error('Invalid JSON response from server.');
        }
    };

    const updateCsrfToken = (payload) => {
        if (!payload || typeof payload !== 'object' || !payload.csrf_token) {
            return;
        }

        const meta = document.querySelector('meta[name="csrf-token"]');

        if (meta) {
            meta.setAttribute('content', payload.csrf_token);
        }
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
            ...options,
        });

        const payload = await parseJson(response);
        updateCsrfToken(payload);

        if (!response.ok) {
            const message = payload && typeof payload === 'object' && payload.error
                ? payload.error
                : `Request failed with status ${response.status}.`;
            const error = new Error(message);
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload;
    };

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    window.api = {
        get(url) {
            return request(url, { method: 'GET' });
        },

        post(url, data = {}) {
            return request(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...data,
                    _token: getCsrfToken(),
                }),
            });
        },

        put(url, data = {}) {
            return request(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...data,
                    _token: getCsrfToken(),
                }),
            });
        },

        delete(url, data = {}) {
            return request(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...data,
                    _token: getCsrfToken(),
                }),
            });
        },
    };
})();
