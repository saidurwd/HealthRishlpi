/**
 * The invoice screen (resources/views/invoice/workspace.blade.php).
 *
 * Patients, products and stock are searched on demand; lines are added,
 * changed and removed through the existing invoice endpoints, and only the
 * lines (JSON) are fetched again afterwards. Saving the invoice posts the
 * header form as before.
 *
 * Keyboard: "/" searches products, Enter in a quantity adds the line.
 */
import TomSelect from 'tom-select';
import { csrfToken, escape, getJson, post, remoteSelect } from './screen';

const root = document.getElementById('invoice-workspace');

if (root) {
    const data = JSON.parse(document.getElementById('invoice-data').textContent);
    const lineForm = document.getElementById('invoice-line-form');
    const lineError = document.getElementById('line-error');
    const preview = document.getElementById('line-preview');
    const tbody = document.querySelector('#invoice-lines tbody');
    const canAddLines = data.mode !== 'edit';

    // ---- helpers ------------------------------------------------------------

    const money = (value) => {
        const number = Number(value) || 0;
        const text = data.currency + Math.abs(number).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        return number < 0 ? `(${text})` : text;
    };

    const quantity = (value) => Number(value).toLocaleString('en-US', { maximumFractionDigits: 6 });

    function showError(message) {
        if (!lineError) {
            alert(message);

            return;
        }
        lineError.textContent = message;
        lineError.hidden = !message;
    }

    // ---- lines -------------------------------------------------------------

    function renderLines(payload) {
        const { lines, totals } = payload;

        tbody.innerHTML = lines.length === 0
            ? `<tr class="invoice-empty"><td colspan="7" class="text-center text-body-secondary py-4">
                   <i class="fa fa-shopping-basket fa-2x d-block mb-2 opacity-50"></i>
                   No items yet${canAddLines ? ': search a product or a service above.' : '.'}
               </td></tr>`
            : lines.map((line, index) => `
                <tr data-line="${line.id}">
                    <td class="text-center text-body-secondary">${index + 1}</td>
                    <td>
                        <div class="fw-semibold">${escape(line.title)}</div>
                        <div class="small text-body-secondary">${line.type === 'Medicine'
                            ? [line.store ?? 'N/A', line.batch ?? 'N/A', line.expiry ? 'exp ' + line.expiry : null].filter(Boolean).map((part) => `<span class="text-nowrap">${escape(part)}</span>`).join(' · ')
                            : 'Service' + (line.note ? ' · ' + escape(line.note) : '')}</div>
                    </td>
                    <td class="text-end">
                        <div class="input-group input-group-sm flex-nowrap justify-content-end">
                            <input type="number" class="form-control text-end line-quantity" min="0" step="any" value="${escape(line.quantity)}" aria-label="Quantity">
                            ${line.type === 'Medicine' ? `<span class="input-group-text">${escape(line.uom)}</span>` : ''}
                        </div>
                    </td>
                    <td class="text-end">${escape(line.rate)}</td>
                    <td class="text-end text-body-secondary">${escape(line.discount)}</td>
                    <td class="text-end fw-semibold">${escape(line.amount)}</td>
                    ${canAddLines ? `<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger line-delete" title="Remove"><i class="fa fa-trash"></i></button></td>` : ''}
                </tr>`).join('');

        document.querySelectorAll('[data-total]').forEach((cell) => {
            cell.textContent = totals[cell.dataset.total] ?? '';
        });

        const save = document.getElementById('invoice-save');
        save.disabled = totals.count === 0;
        save.title = totals.count === 0 ? 'Add one or more items first' : '';
    }

    async function reloadLines() {
        renderLines(await getJson(data.urls.lines, { parent: data.parent }));
    }

    tbody.addEventListener('change', async (event) => {
        const input = event.target.closest('.line-quantity');

        if (!input) {
            return;
        }

        const id = input.closest('tr').dataset.line;
        input.disabled = true;

        try {
            await post(data.urls.adjust, new URLSearchParams({ id, adjustment: input.value, type: 'quantity' }));
        } catch (error) {
            alert(error.message);
        }

        await reloadLines();
    });

    tbody.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.closest('.line-quantity')) {
            event.preventDefault();
            event.target.blur();
        }
    });

    tbody.addEventListener('click', async (event) => {
        const button = event.target.closest('.line-delete');

        if (!button || !confirm('Remove this item?')) {
            return;
        }

        button.disabled = true;

        try {
            await post(data.urls.delete.replace('__ID__', button.closest('tr').dataset.line), new URLSearchParams({ ajax: 'invoice-grid' }));
        } catch (error) {
            alert(error.message);
        }

        await reloadLines();
    });

    renderLines(data.lines);

    // ---- add a line --------------------------------------------------------

    if (lineForm) {
        const typeField = lineForm.elements.servicetype;
        const stockSelect = document.getElementById('line-stock');
        const quantityInput = document.getElementById('line-quantity');
        const serviceQuantity = document.getElementById('line-service-quantity');
        const rateInput = document.getElementById('line-rate');
        const discountType = document.getElementById('line-discounttype');
        const discountValue = document.getElementById('line-discountamount');
        const serviceById = new Map(data.services.flatMap((group) => group.options.map((option) => [String(option.id), option])));
        let batches = [];

        const itemSelect = remoteSelect(document.getElementById('line-item'), data.urls.items, {
            render: {
                option: (item, esc) => `<div class="d-flex justify-content-between gap-2">
                    <span>${esc(item.text)}</span>
                    <span class="badge ${item.free > 0 ? 'text-bg-success' : 'text-bg-secondary'}">${item.free > 0 ? esc(quantity(item.free)) + ' free' : 'out of stock'}</span>
                </div>`,
                no_results: () => '<div class="no-results">No product found</div>',
            },
            onChange: (value) => loadStock(value),
        });

        const serviceSelect = new TomSelect(document.getElementById('line-service'), {
            valueField: 'id',
            labelField: 'title',
            searchField: ['title'],
            optgroupField: 'group',
            optgroupLabelField: 'group',
            optgroupValueField: 'group',
            maxOptions: null,
            dropdownParent: 'body',
            options: data.services.flatMap((group) => group.options.map((option) => ({ ...option, group: group.group }))),
            optgroups: data.services.map((group) => ({ group: group.group })),
            render: {
                option: (item, esc) => `<div class="d-flex justify-content-between gap-2"><span>${esc(item.title)}</span><span class="text-body-secondary">${item.manual ? 'manual rate' : esc(money(item.rate))}</span></div>`,
            },
            onChange: () => {
                toggleManual();
                updatePreview();
                serviceQuantity.focus();
            },
        });

        function selectedService() {
            return serviceById.get(String(serviceSelect.getValue()));
        }

        function toggleType(type) {
            typeField.value = type;
            lineForm.querySelectorAll('[data-for="Medicine"], [data-for="Service"]').forEach((section) => {
                section.hidden = section.dataset.for !== type;
            });
            toggleManual();
            showError('');
            updatePreview();
            (type === 'Medicine' ? itemSelect : serviceSelect).focus();
        }

        function toggleManual() {
            const manual = typeField.value === 'Service' && Boolean(selectedService()?.manual);
            lineForm.querySelectorAll('[data-for="Manual"]').forEach((section) => {
                section.hidden = !manual;
            });
        }

        async function loadStock(item) {
            batches = [];
            stockSelect.innerHTML = '<option value="">Pick a product first</option>';
            stockSelect.disabled = true;
            updatePreview();

            if (!item) {
                return;
            }

            stockSelect.innerHTML = '<option value="">Loading…</option>';

            try {
                batches = await getJson(data.urls.stock, { item });
            } catch (error) {
                showError(error.message);
            }

            if (batches.length === 0) {
                stockSelect.innerHTML = '<option value="">Out of stock</option>';
                updatePreview();

                return;
            }

            stockSelect.innerHTML = batches.map((row, index) => {
                const label = `${row.store_name} · ${row.batch_title} · ${row.expiry ? 'exp ' + row.expiry : 'no expiry'} · ${quantity(row.free)} free${row.expired ? ' · EXPIRED' : ''}`;

                return `<option value="${index}" class="${row.expired ? 'text-danger' : ''}" ${row.free > 0 ? '' : 'disabled'}>${escape(label)}</option>`;
            }).join('');
            stockSelect.disabled = false;

            // Suggest the batch that expires first and is still in date
            const suggested = batches.findIndex((row) => row.free > 0 && !row.expired);
            stockSelect.value = String(suggested >= 0 ? suggested : batches.findIndex((row) => row.free > 0));
            updatePreview();
            quantityInput.focus();
        }

        function selectedBatch() {
            return batches[Number(stockSelect.value)] ?? null;
        }

        function updatePreview() {
            const parts = [];

            if (typeField.value === 'Medicine') {
                const batch = selectedBatch();

                if (batch) {
                    const qty = Number(quantityInput.value) || 0;
                    const gross = qty * batch.rate;
                    parts.push(`Rate <strong>${money(batch.rate)}</strong>`);
                    parts.push(`Free <strong>${quantity(batch.free)}</strong>`);
                    parts.push(`Less ${data.medicineDiscount}%`);

                    if (qty > 0) {
                        parts.push(`Line total <strong>${money(gross - gross * data.medicineDiscount / 100)}</strong>`);
                    }
                    if (qty > batch.free) {
                        parts.push('<span class="text-danger"><i class="fa fa-warning"></i> more than is free</span>');
                    }
                    if (batch.expired) {
                        parts.push('<span class="text-danger"><i class="fa fa-warning"></i> batch expired</span>');
                    }
                }
            } else {
                const service = selectedService();

                if (service) {
                    const rate = service.manual ? Number(rateInput.value) || 0 : service.rate;
                    const gross = (Number(serviceQuantity.value) || 0) * rate;
                    const off = discountType.value === 'Percentage' ? gross * (Number(discountValue.value) || 0) / 100 : Number(discountValue.value) || 0;
                    parts.push(`Rate <strong>${service.manual ? 'manual' : money(service.rate)}</strong>`);
                    parts.push(`Line total <strong>${money(gross - off)}</strong>`);
                }
            }

            preview.innerHTML = parts.map((part) => `<span>${part}</span>`).join('');
        }

        document.querySelectorAll('input[name="line-type"]').forEach((radio) => {
            radio.addEventListener('change', () => toggleType(radio.value));
        });
        stockSelect.addEventListener('change', () => {
            updatePreview();
            quantityInput.focus();
        });
        [quantityInput, serviceQuantity, rateInput, discountType, discountValue].forEach((field) => field.addEventListener('input', updatePreview));

        lineForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            showError('');

            const body = new FormData(lineForm);
            body.set('_token', csrfToken());

            if (typeField.value === 'Medicine') {
                const batch = selectedBatch();
                body.set('store', batch ? batch.store : '');
                body.set('batch', batch ? batch.batch : '');
            } else {
                body.set('quantity', serviceQuantity.value);
            }

            const buttons = lineForm.querySelectorAll('[type="submit"]');
            buttons.forEach((button) => { button.disabled = true; });

            try {
                await post(lineForm.action, body);
                await reloadLines();

                if (typeField.value === 'Medicine') {
                    itemSelect.clear(true);
                    quantityInput.value = '';
                    await loadStock('');
                    itemSelect.focus();
                } else {
                    serviceSelect.clear(true);
                    serviceQuantity.value = '1';
                    discountValue.value = '0';
                    rateInput.value = '';
                    lineForm.elements.note.value = '';
                    toggleManual();
                    updatePreview();
                    serviceSelect.focus();
                }
            } catch (error) {
                showError(error.message);
            } finally {
                buttons.forEach((button) => { button.disabled = false; });
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === '/' && !event.target.closest('input, textarea, select, [contenteditable]')) {
                event.preventDefault();
                document.getElementById('line-type-medicine').click();
                itemSelect.focus();
            }
        });

        updatePreview();
    }

    // ---- header ------------------------------------------------------------

    const prescriptionSelect = document.getElementById('invoice-prescription');
    const patientDetail = document.getElementById('invoice-patient-detail');

    function setPrescriptions(list, selected = '') {
        prescriptionSelect.innerHTML = '<option value="">No prescription</option>'
            + list.map((p) => `<option value="${p.id}" ${String(p.id) === String(selected) ? 'selected' : ''}>${escape(p.text)}</option>`).join('');
    }

    const patientSelect = remoteSelect(document.getElementById('invoice-patient'), data.urls.patients, {
        render: {
            option: (item, esc) => `<div><div>${esc(item.text)}</div><div class="small text-body-secondary">${esc(item.detail)}</div></div>`,
            no_results: () => '<div class="no-results">No patient found</div>',
        },
        onChange: async (value) => {
            patientDetail.textContent = patientSelect.options[value]?.detail ?? '';
            setPrescriptions([]);

            if (value) {
                setPrescriptions(await getJson(data.urls.prescriptions, { patient: value }).catch(() => []));
            }
        },
    });

    if (data.patient) {
        patientSelect.addOption(data.patient);
        patientSelect.setValue(String(data.patient.id), true);
        patientDetail.textContent = data.patient.detail;
    }
    setPrescriptions(data.prescriptions, data.prescription);

    document.getElementById('invoice-header').addEventListener('submit', (event) => {
        const category = event.target.querySelector('[name="patient_category"]');

        if (!patientSelect.getValue()) {
            event.preventDefault();
            alert('Please select a patient.');
            patientSelect.focus();

            return;
        }

        // The update page insists on a sub category, as it did in Yii
        if (data.requireCategory && category && category.value === '') {
            event.preventDefault();
            alert('Please select a patient category.');

            return;
        }

        document.getElementById('invoice-save').disabled = true;
    });

    if (data.mode === 'create' && !data.patient) {
        patientSelect.focus();
    }
}
