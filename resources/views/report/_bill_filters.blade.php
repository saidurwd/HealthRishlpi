@include('report._category_filters')
<div class="col-md-2"><x-form.select name="service" :label="''" :options="$serviceTree" :value="$f['service']" empty="All Services" class="form-select-sm" searchable /></div>
<div class="col-md-2"><x-form.select name="status" :label="''" :options="\App\Models\InvoiceParent::PAYMENT_STATUSES" :value="$f['status']" empty="All Status" class="form-select-sm" /></div>
@include('report._dates')
