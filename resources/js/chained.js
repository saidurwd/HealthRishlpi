/**
 * Dependent dropdowns (the legacy app used jquery.chained).
 *
 * <select data-chained="#parent"> shows only the options whose data-chain
 * matches the parent's value; with several parents
 * (data-chained="#item, #store") the values are joined with "\". The empty
 * option is always kept, and the select is disabled when nothing else is
 * left. Works with type-to-search selects (tom-select).
 */
function parentsOf(select) {
    return select.dataset.chained.split(',').map((selector) => document.querySelector(selector.trim()));
}

function apply(select) {
    const key = parentsOf(select).map((parent) => parent?.value ?? '').join('\\');
    const current = select.value;

    select.replaceChildren(
        ...select.chainOptions
            .filter((option) => option.value === '' || (option.dataset.chain ?? '').split(' ').includes(key))
            .map((option) => option.cloneNode(true)),
    );
    select.value = [...select.options].some((option) => option.value === current) ? current : '';
    select.disabled = select.options.length === 1 && select.value === '';

    if (select.tomselect) {
        select.tomselect.sync();
        select.disabled ? select.tomselect.disable() : select.tomselect.enable();
    }

    select.dispatchEvent(new Event('change', { bubbles: true }));
}

export function initChained(root = document) {
    root.querySelectorAll('select[data-chained]').forEach((select) => {
        if (select.chainOptions) {
            return;
        }

        select.chainOptions = [...select.options].map((option) => option.cloneNode(true));
        parentsOf(select).forEach((parent) => parent?.addEventListener('change', () => apply(select)));
        apply(select);
    });
}

document.addEventListener('DOMContentLoaded', () => initChained());
