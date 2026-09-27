{{-- Header form of the requisition / issue / transfer pages: status (on update) and comments --}}
<form method="post" id="{{ $formId }}" class="mt-3 border-top pt-3">
    @csrf
    <x-form.errors />
    <div class="row g-2">
        @isset($statuses)
            <div class="col-md-2"><x-form.select name="status" :label="$parent::label('status')" :options="$statuses" :value="$parent->status" /></div>
        @endisset
        <div class="col-md-6"><x-form.input name="comments" :label="$parent::label('comments')" :value="$parent->comments" maxlength="1000" placeholder="Comments" /></div>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
    <button type="button" class="btn btn-default" onclick="window.history.back();">Back</button>
</form>
