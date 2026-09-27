/**
 * The prescription screen (resources/views/patient/prescription-form.blade.php).
 *
 * Medicines are searched on demand and added / removed through the existing
 * endpoints; only the medicine list (JSON) is fetched again. Saving posts
 * the clinical notes form as before.
 *
 * Keyboard: "/" searches medicines, Enter in Days adds the line.
 */
import TomSelect from 'tom-select';
import { escape, getJson, post, remoteSelect } from './screen';

const root = document.getElementById('prescription-workspace');

if (root) {
    const data = JSON.parse(document.getElementById('prescription-data').textContent);
    const form = document.getElementById('medicine-form');
    const error = document.getElementById('medicine-error');
    const tbody = document.querySelector('#medicine-lines tbody');
    const days = document.getElementById('medicine-days');

    const showError = (message) => {
        error.textContent = message;
        error.hidden = !message;
    };

    function render(lines) {
        tbody.innerHTML = lines.length === 0
            ? `<tr class="invoice-empty"><td colspan="5" class="text-center text-body-secondary py-4">
                   <i class="fa fa-medkit fa-2x d-block mb-2 opacity-50"></i>No medicines yet: search one above.
               </td></tr>`
            : lines.map((line, index) => `
                <tr data-line="${line.id}">
                    <td class="text-center text-body-secondary">${index + 1}</td>
                    <td class="fw-semibold">${escape(line.product)}</td>
                    <td>${escape(line.instruction)}</td>
                    <td class="text-end">${escape(line.days ?? '')}</td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger line-delete" title="Remove"><i class="fa fa-trash"></i></button></td>
                </tr>`).join('');
        document.getElementById('medicine-count').textContent = lines.length;
    }

    const reload = async () => render(await getJson(data.urls.lines, { parent: data.parent }));

    const product = remoteSelect(document.getElementById('medicine-product'), data.urls.products, {
        render: {
            option: (item, esc) => `<div class="d-flex justify-content-between gap-2">
                <span>${esc(item.text)}</span>
                <span class="badge ${item.free > 0 ? 'text-bg-success' : 'text-bg-secondary'}">${item.free > 0 ? 'in stock' : 'out of stock'}</span>
            </div>`,
            no_results: () => '<div class="no-results">No medicine found</div>',
        },
        onChange: (value) => {
            if (value) {
                instruction.focus();
            }
        },
    });

    // Pick a standard instruction or type one
    const instruction = new TomSelect(document.getElementById('medicine-instruction'), {
        options: data.instructions.map((title) => ({ value: title, text: title })),
        create: true,
        createOnBlur: true,
        maxOptions: null,
        dropdownParent: 'body',
        onChange: (value) => {
            if (value) {
                days.focus();
            }
        },
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showError('');

        if (!product.getValue()) {
            showError('Please select a medicine.');
            product.focus();

            return;
        }

        const button = form.querySelector('[type="submit"]');
        button.disabled = true;

        try {
            await post(form.action, new FormData(form));
            await reload();
            product.clear(true);
            instruction.clear(true);
            days.value = '';
            product.focus();
        } catch (e) {
            showError(e.message);
        } finally {
            button.disabled = false;
        }
    });

    tbody.addEventListener('click', async (event) => {
        const button = event.target.closest('.line-delete');

        if (!button || !confirm('Remove this medicine?')) {
            return;
        }

        button.disabled = true;

        try {
            await post(data.urls.delete.replace('__ID__', button.closest('tr').dataset.line), new URLSearchParams({ ajax: 'prescription-medicine-grid' }));
        } catch (e) {
            alert(e.message);
        }

        await reload();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && !event.target.closest('input, textarea, select, [contenteditable]')) {
            event.preventDefault();
            product.focus();
        }
    });

    document.getElementById('prescription-form').addEventListener('submit', () => {
        document.getElementById('prescription-save').disabled = true;
    });

    render(data.lines);
}
