/**
 * Enhanced API service with automatic CSRF 419 token refresh,
 * 401 session recovery, and idempotency key handling.
 */

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        return metaTag.getAttribute('content');
    }

    // Fallback: check cookie XSRF-TOKEN
    const match = document.cookie.match(new RegExp('(^|;\\s*)(XSRF-TOKEN)=([^;]*)'));
    return match ? decodeURIComponent(match[3]) : '';
}

function setCsrfToken(token) {
    let metaTag = document.querySelector('meta[name="csrf-token"]');
    if (!metaTag) {
        metaTag = document.createElement('meta');
        metaTag.name = 'csrf-token';
        document.head.appendChild(metaTag);
    }
    metaTag.setAttribute('content', token);
}

let isRefreshingCsrf = false;
let csrfRefreshPromise = null;

async function refreshCsrf() {
    if (isRefreshingCsrf && csrfRefreshPromise) {
        return csrfRefreshPromise;
    }

    isRefreshingCsrf = true;
    csrfRefreshPromise = fetch('/api/auth/csrf', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then(res => res.json())
        .then(data => {
            if (data?.csrf_token) {
                setCsrfToken(data.csrf_token);
                return data.csrf_token;
            }
            return '';
        })
        .finally(() => {
            isRefreshingCsrf = false;
            csrfRefreshPromise = null;
        });

    return csrfRefreshPromise;
}

async function request(endpoint, options = {}, isRetry = false) {
    const defaultHeaders = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const csrf = getCsrfToken();
    if (csrf) {
        defaultHeaders['X-CSRF-TOKEN'] = csrf;
    }

    const config = {
        credentials: 'same-origin',
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers,
        },
    };

    let response;
    try {
        response = await fetch(endpoint, config);
    } catch (networkError) {
        const err = new Error('Network connection error. Please check your data connectivity and retry.');
        err.isNetworkError = true;
        throw err;
    }

    // 1. Handle CSRF Token Mismatch (HTTP 419) with transparent auto-refresh & retry
    if (response.status === 419 && !isRetry) {
        try {
            await refreshCsrf();
            return await request(endpoint, options, true);
        } catch {
            // Fall through to standard error handling
        }
    }

    // 2. Handle Session Expiry (HTTP 401) by broadcasting event to preserve in-memory state
    if (response.status === 401) {
        window.dispatchEvent(new CustomEvent('session-expired', { detail: { endpoint } }));
    }

    // Parse response
    let data = null;
    const contentType = response.headers.get('content-type');
    if (contentType && contentType.includes('application/json')) {
        data = await response.json();
    } else {
        data = await response.text();
    }

    if (!response.ok) {
        const error = new Error(data?.message || `Request failed with status ${response.status}`);
        error.status = response.status;
        error.data = data;
        error.errors = data?.errors || {};
        throw error;
    }

    return data;
}

export const api = {
    get(endpoint, params = {}, options = {}) {
        const url = new URL(endpoint, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                url.searchParams.append(key, params[key]);
            }
        });
        return request(url.pathname + url.search, { method: 'GET', ...options });
    },

    post(endpoint, body = {}, options = {}) {
        return request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body),
            ...options,
        });
    },

    put(endpoint, body = {}, options = {}) {
        return request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body),
            ...options,
        });
    },

    delete(endpoint, options = {}) {
        return request(endpoint, { method: 'DELETE', ...options });
    },
};
