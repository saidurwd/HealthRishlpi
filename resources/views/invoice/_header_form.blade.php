{{--
    Invoice header. $fields picks the extra fields: status (update/rollback)
    and patient categories (update/rollback/special edit).
--}}
@php $fields ??= []; @endphp
<form method="post" id="invoice-parent-form" class="mt-3 border-top pt-3">
    @csrf
    <x-form.errors />
    <div class="row g-2">
        <div class="col-md-3">
            <x-form.select name="patient" :label="$parent::label('patient')" :options="$patients" :value="$parent->patient" empty="Select a Patient" searchable />
        </div>
        <div class="col-md-2">
            <x-form.select name="prescription" :label="$parent::label('prescription')" :options="$prescriptions" :value="$parent->prescription" empty="Select a Prescription" data-chained="#patient" />
        </div>
        @if (in_array('status', $fields, true))
            <div class="col-md-1">
                <x-form.select name="status" :label="$parent::label('status')" :options="$statuses" :value="$parent->status" />
            </div>
        @endif
        <div class="col-md-1">
            <x-form.select name="payment_status" :label="$parent::label('payment_status')" :options="$parent::PAYMENT_STATUSES" :value="$parent->payment_status" />
        </div>
        @if (in_array('categories', $fields, true))
            <div class="col-md-2">
                <x-form.select name="patient_category_new" :label="$parent::label('patient_category_new')" :options="$categoriesNew" :value="$parent->patient_category_new" empty="Select a Category" searchable />
            </div>
            <div class="col-md-2">
                <x-form.select name="patient_category" :label="$parent::label('patient_category')" :options="$categories" :value="$parent->patient_category" empty="Select a Sub Category" searchable />
            </div>
        @endif
        <div class="col-md-3">
            <x-form.input name="comments" :label="$parent::label('comments')" :value="$parent->comments" maxlength="1000" placeholder="Comments" />
        </div>
    </div>
    <button type="submit" class="btn btn-primary" id="btnSubmit">{{ $submit }}</button>
    <button type="button" class="btn btn-default" onclick="window.history.back();">Back</button>
</form>

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('invoice-parent-form');
            form.addEventListener('submit', (event) => {
                const category = form.querySelector('[name="patient_category"]');
                // The update page insisted on a sub category before saving
                if (@js(($checkCategory ?? false)) && category && category.value === '') {
                    event.preventDefault();
                    alert('Please select a patient category.');

                    return;
                }
                document.getElementById('btnSubmit').disabled = true;
            });
        })();
    </script>
@endpush
