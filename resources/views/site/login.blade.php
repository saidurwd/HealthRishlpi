@extends('layouts.guest')

@section('title', 'Login - '.config('app.name'))

@section('content')
    <div class="row g-4 align-items-start">
        <div class="col-lg-7 col-xl-8 d-none d-lg-block">
            <x-flash />
            <h1 class="text-danger fw-light">{{ config('legacy.adminName') }}</h1>
            <div class="d-flex gap-4 align-items-start">
                <h5 class="fw-normal lh-base flex-grow-1">{!! config('legacy.tagLine') !!}</h5>
                <img src="{{ asset('images/forms_icon.png') }}" alt="" style="width: 340px">
            </div>
        </div>
        <div class="col-lg-5 col-xl-4">
            <div class="d-lg-none"><x-flash /></div>
            <div class="card">
                <div class="card-header"><strong>APPLICATION SIGN IN</strong></div>
                <form method="post" action="{{ route('site.login') }}" id="login-form">
                    @csrf
                    <div class="card-body">
                        <x-form.errors />
                        <x-form.input name="username" label="E-mail" placeholder="Please enter email address/username" autofocus />
                        <x-form.input name="password" type="password" label="Password" placeholder="Enter your password" />
                        <div class="form-check">
                            <input type="hidden" name="rememberMe" value="0">
                            <input class="form-check-input" type="checkbox" name="rememberMe" id="rememberMe" value="1" @checked(old('rememberMe', '1') === '1')>
                            <label class="form-check-label" for="rememberMe">Remember me next time</label>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
