{{-- Port of User::get_alert_message(). Flash texts are written by controllers and may contain markup. --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-fw fa-check"></i>
        {!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa fa-fw fa-times"></i>
        {!! session('error') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
