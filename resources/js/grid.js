/**
 * Client side of <x-grid>, the port of Yii's CGridView (no jQuery).
 *
 * Sorting, paging and filtering reload the grid in place: the page is fetched
 * again and the grid element with the same id is swapped in, as
 * jquery.yiigridview.js did. Legacy calls to
 * `$('#x-grid').yiiGridView('update')` become `Grid.update('x-grid')`.
 */

const GRID = '[data-grid]';
// Named fields only: searchable dropdowns add their own unnamed inputs
const FILTER_FIELDS = '.filters input[name], .filters select[name]';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function update(id, url) {
    const grid = document.getElementById(id);

    if (!grid) {
        return;
    }

    url ??= grid.dataset.gridUrl;
    grid.classList.add('grid-view-loading');

    try {
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

        if (!response.ok) {
            throw new Error((await response.text()) || response.statusText);
        }

        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const fresh = page.getElementById(id);

        // Session expired or access denied: follow the server's page
        if (!fresh) {
            window.location.href = response.url;

            return;
        }

        grid.replaceWith(fresh);
        fresh.dispatchEvent(new CustomEvent('grid:updated', { bubbles: true }));
    } catch (error) {
        alert(error.message);
        grid.classList.remove('grid-view-loading');
    }
}

function applyFilters(grid) {
    const url = new URL(grid.dataset.gridUrl, window.location.href);
    const prefix = grid.dataset.gridPrefix;

    [...url.searchParams.keys()]
        .filter((key) => key.startsWith(`${prefix}[`) || key === `${prefix}_page`)
        .forEach((key) => url.searchParams.delete(key));

    grid.querySelectorAll(FILTER_FIELDS).forEach((field) => {
        if (field.name && field.value !== '') {
            url.searchParams.set(field.name, field.value);
        }
    });

    update(grid.id, url.toString());
}

async function remove(grid, link) {
    if (!window.confirm(link.dataset.confirm || 'Are you sure you want to delete this item?')) {
        return;
    }

    const body = new URLSearchParams({ ajax: grid.id });
    const response = await fetch(link.href, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
        body,
    });

    if (!response.ok) {
        alert((await response.text()) || response.statusText);

        return;
    }

    update(grid.id);
}

document.addEventListener('click', (event) => {
    const link = event.target.closest(`${GRID} a[data-grid-link], ${GRID} a[data-grid-delete]`);

    if (!link) {
        return;
    }

    event.preventDefault();
    const grid = link.closest(GRID);

    if (link.hasAttribute('data-grid-delete')) {
        remove(grid, link);
    } else {
        update(grid.id, link.href);
    }
});

document.addEventListener('change', (event) => {
    if (event.target.matches(`${GRID} ${FILTER_FIELDS}`)) {
        applyFilters(event.target.closest(GRID));
    }
});

// Enter in a filter box applies it (blur fires "change" when the value changed)
document.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && event.target.matches(`${GRID} .filters input[name]`)) {
        event.preventDefault();
        event.target.blur();
    }
});

export default { update };
