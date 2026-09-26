{{-- CActiveForm::errorSummary() --}}
@if ($errors->any())
    <div class="text-danger mb-3">
        <i class="fa fa-bell"></i> Please fix the following input errors:
        <ul class="mb-0">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
