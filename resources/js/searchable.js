/**
 * Type-to-search dropdowns (the legacy app used select2 for these).
 * Applies to <select class="js-searchable">, including ones inside a grid
 * after it reloads.
 */
import TomSelect from 'tom-select';

export function initSearchable(root = document) {
    root.querySelectorAll('select.js-searchable:not(.tomselected)').forEach((select) => {
        new TomSelect(select, {
            allowEmptyOption: true,
            maxOptions: null,
            dropdownParent: 'body',
        });
    });
}

document.addEventListener('DOMContentLoaded', () => initSearchable());
document.addEventListener('grid:updated', (event) => initSearchable(event.target));
