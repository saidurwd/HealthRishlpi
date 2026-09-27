{{-- Files of one receive line (loaded into the files modal) --}}
<h4 class="mb-3"><i class="fa fa-upload"></i> UPLOAD FILES FOR <span class="text-success">{{ $line->item0?->title }}</span></h4>
<form method="post" action="{{ route('purchaseReceive.docupload') }}" enctype="multipart/form-data" data-line-form="purchase-receive-document-grid" class="row g-2 mb-3">
    @csrf
    <input type="hidden" name="receive_number" value="{{ $line->id }}">
    <div class="col-md-5"><input type="text" name="doc_title" class="form-control form-control-sm" placeholder="Document Title" maxlength="255"></div>
    <div class="col-md-5"><input type="file" name="doc_file" class="form-control form-control-sm"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa fa-upload"></i> Upload</button></div>
</form>
<x-grid id="purchase-receive-document-grid" :grid="$documents" :columns="[
    ['header' => 'Title', 'value' => fn ($row) => $row->doc_title],
    ['header' => 'Created By', 'value' => fn ($row) => $row->createdBy?->full_name],
    ['header' => 'Created On', 'value' => fn ($row) => \App\Support\YiiFormat::date($row->created_on)],
    ['header' => 'Download', 'buttons' => [
        fn ($row) => '<a href=\''.e(route('purchaseReceive.downloadfile', $row->id)).'\' class=\'btn btn-sm btn-warning\' title=\'Download\'><i class=\'fa fa-download\'></i></a>',
        fn ($row) => '<a href=\''.e(route('purchaseReceive.deletefile', $row->id)).'\' class=\'btn btn-sm btn-danger\' title=\'Delete\' data-grid-delete><i class=\'fa fa-trash\'></i></a>',
    ]],
]" />
