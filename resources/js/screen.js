/**
 * Helpers shared by the invoice and prescription screens: JSON requests with
 * the CSRF token, HTML escaping and a tom-select that searches the server.
 */
import TomSelect from 'tom-select';

export const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

export async function getJson(url, params = {}) {
    const query = new URLSearchParams(params).toString();
    const response = await fetch(query ? `${url}?${query}` : url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });

    if (!response.ok) {
        throw new Error(response.status === 403 || response.redirected ? 'You are not allowed to do this.' : 'Could not load data. Please try again.');
    }

    return response.json();
}

/**
 * POST form data; a validation error (422) becomes an Error with the messages.
 */
export async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body,
    });

    if (response.status === 422) {
        const json = await response.json();
        throw new Error(Object.values(json.errors ?? {}).flat().join(' ') || json.message);
    }

    if (!response.ok) {
        throw new Error('Could not save. Please try again.');
    }

    return response.text();
}

/**
 * Searchable <select> fed by `url?q=...` (JSON [{id, text, ...}]); every
 * result of the latest search is shown.
 */
export function remoteSelect(select, url, options = {}) {
    return new TomSelect(select, {
        valueField: 'id',
        labelField: 'text',
        searchField: [],
        maxOptions: 50,
        loadThrottle: 250,
        preload: 'focus',
        dropdownParent: 'body',
        score: () => () => 1,
        load(query, callback) {
            this.clearOptions();
            getJson(url, { q: query }).then(callback).catch(() => callback());
        },
        ...options,
    });
}
