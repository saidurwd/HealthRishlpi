{{-- Add a medicine or service line (AJAX); $parentId is set on the update page --}}
<form method="post" action="{{ route('invoice.add') }}" id="invoice-form" data-line-form="invoice-grid" class="row g-2 align-items-start mt-3 invoice-line-form" data-manual-services="{{ json_encode($manualServices) }}">
    @csrf
    @isset($parentId)
        <input type="hidden" name="parent" value="{{ $parentId }}">
    @endisset
    <div class="col-md-1">
        <x-form.select name="servicetype" :label="''" :options="['Medicine' => 'Medicine', 'Service' => 'Service']" />
    </div>
    <div class="col-md-2" data-line-for="Service">
        <x-form.select name="service" :label="''" :options="$services" empty="Select a Service" searchable />
    </div>
    <div class="col-md-2" data-line-for="Medicine">
        <x-form.select name="item" :label="''" :options="$items" empty="Select a Product" searchable />
    </div>
    <div class="col-md-2" data-line-for="Medicine">
        <x-form.select name="store" :label="''" :options="$stores" empty="Select a Store" data-chained="#item" />
    </div>
    <div class="col-md-2" data-line-for="Medicine">
        <x-form.select name="batch" :label="''" :options="$batches" empty="Select a Batch" data-chained="#item, #store" />
    </div>
    <div class="col-md-2" data-line-for="Service">
        <x-form.select name="discounttype" :label="''" :options="['Percentage' => 'Percentage', 'Cash' => 'Cash']" />
    </div>
    <div class="col-md-2" data-line-for="Service">
        <x-form.input name="discountamount" :label="''" maxlength="4" placeholder="Percentage/Cash" />
    </div>
    <div class="col-md-1">
        <x-form.input name="quantity" :label="''" maxlength="20" placeholder="Quantity" />
    </div>
    <div class="col-md-1" data-line-for="Manual">
        <x-form.input name="rate" :label="''" maxlength="20" placeholder="Rate" />
    </div>
    <div class="col-md-2" data-line-for="Manual">
        <x-form.input name="note" :label="''" maxlength="400" placeholder="Note" />
    </div>
    <div class="col-md-1">
        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-plus"></i> Add</button>
    </div>
</form>

@push('scripts')
    <script>
        // Show the medicine or the service fields; rate and note only for manually priced services
        (() => {
            const form = document.getElementById('invoice-form');
            const manual = JSON.parse(form.dataset.manualServices).map(String);
            const type = form.querySelector('[name="servicetype"]');
            const service = form.querySelector('[name="service"]');
            const toggle = () => {
                form.querySelectorAll('[data-line-for]').forEach((section) => {
                    const forWhat = section.dataset.lineFor;
                    section.hidden = forWhat === 'Manual'
                        ? !(type.value === 'Service' && manual.includes(service.value))
                        : forWhat !== type.value;
                });
            };
            type.addEventListener('change', toggle);
            service.addEventListener('change', toggle);
            toggle();
        })();
    </script>
@endpush
