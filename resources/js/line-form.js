/**
 * "Add line" forms and inline quantity edits of the transaction screens
 * (prescriptions, invoices, requisitions, ...), replacing the legacy
 * jQuery $.ajax calls.
 *
 * <form data-line-form="grid-id" action="..."> posts itself with AJAX; on
 * success the grid reloads and the form is cleared, on a validation error
 * the messages are shown in an alert.
 *
 * <input|select data-adjust-url="..." data-adjust-id="5" data-adjust-type="rate">
 * posts {id, adjustment: value, type} (type defaults to "quantity") when
 * changed, then reloads the grid it sits in.
 */
import Grid from './grid';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body,
    });

    if (response.status === 422) {
        const data = await response.json();
        throw new Error(Object.values(data.errors ?? {}).flat().join('\n') || data.message);
    }

    if (!response.ok) {
        throw new Error('Error occured. Please try again');
    }

    return response.text();
}

function clear(form) {
    form.reset();
    form.querySelectorAll('select').forEach((select) => {
        select.tomselect?.clear(true);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-line-form]');

    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    button?.setAttribute('disabled', 'disabled');

    try {
        await post(form.action, new FormData(form));
        clear(form);
        Grid.update(form.dataset.lineForm);
    } catch (error) {
        alert(error.message);
    } finally {
        button?.removeAttribute('disabled');
    }
});

document.addEventListener('change', async (event) => {
    const input = event.target.closest('[data-adjust-url]');

    if (!input || input.value === '') {
        return;
    }

    const grid = input.closest('[data-grid]');

    try {
        await post(input.dataset.adjustUrl, new URLSearchParams({ id: input.dataset.adjustId, adjustment: input.value, type: input.dataset.adjustType ?? 'quantity' }));
    } catch (error) {
        alert(error.message);
    }

    Grid.update(grid.id);
});
