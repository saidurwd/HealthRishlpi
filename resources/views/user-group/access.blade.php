@extends('layouts.app')

@section('title', 'Set user access')

@section('header')
    <x-page-header icon="fa fa-lock" title="Access Manager" subtitle="Manage" :breadcrumbs="['Groups' => route('userGroup.admin'), 'Set user access']" />
@endsection

@section('content')
    <p>
        <a href="{{ route('userGroup.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> User Group</a>
    </p>
    <x-card icon="fa fa-users" :title="$group->title">
        <x-slot:tools>
            <a href="#" class="btn btn-sm btn-link" data-acl-all="1" title="Deny all"><i class="fa fa-remove"></i> Deny all</a>
            <a href="#" class="btn btn-sm btn-link" data-acl-all="2" title="Access all"><i class="fa fa-check-square-o"></i> Access all</a>
        </x-slot:tools>
        <div class="accordion" id="acl-accordion">
            @foreach ($controllers as $controller)
                <div class="accordion-item">
                    <h4 class="accordion-header">
                        <button @class(['accordion-button', 'collapsed' => ! $loop->first]) type="button" data-bs-toggle="collapse" data-bs-target="#acl-{{ $controller->id }}">
                            {{ $controller->title }}
                        </button>
                    </h4>
                    <div id="acl-{{ $controller->id }}" @class(['accordion-collapse collapse', 'show' => $loop->first])>
                        <div class="accordion-body">
                            <div class="mb-2">
                                <a href="#" class="me-3" data-acl-all="2" data-acl-controller="{{ $controller->controller }}" title="Access all"><i class="fa fa-check-square-o"></i> Access all</a>
                                <a href="#" data-acl-all="1" data-acl-controller="{{ $controller->controller }}" title="Deny all"><i class="fa fa-remove"></i> Deny all</a>
                            </div>
                            @foreach ($acl[$controller->controller] ?? [] as $row)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                                    <span><i class="fa fa-check"></i> {{ $row->action_title }}</span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="autoopen{{ $row->id }}" data-acl-id="{{ $row->id }}" @checked((int) $row->access === 1)>
                                        <label class="form-check-label" for="autoopen{{ $row->id }}">{{ (int) $row->access === 1 ? 'ON' : 'OFF' }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
@endsection

@push('scripts')
    <script>
        (() => {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const post = async (url, data = {}) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams(data),
                });
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
            };
            const fail = () => alert('some error occured, please try again later');

            document.querySelectorAll('[data-acl-id]').forEach((toggle) => {
                toggle.addEventListener('change', () => {
                    const url = toggle.checked
                        ? @js(route('userGroup.turnon', '__ID__'))
                        : @js(route('userGroup.turnoff', '__ID__'));
                    toggle.disabled = true;
                    post(url.replace('__ID__', toggle.dataset.aclId))
                        .then(() => { toggle.nextElementSibling.textContent = toggle.checked ? 'ON' : 'OFF'; })
                        .catch(() => { toggle.checked = !toggle.checked; fail(); })
                        .finally(() => { toggle.disabled = false; });
                });
            });

            document.querySelectorAll('[data-acl-all]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const controller = link.dataset.aclController;
                    const data = { id: link.dataset.aclAll, group_id: @js($group->id) };
                    const url = controller === undefined ? @js(route('userGroup.accessall')) : @js(route('userGroup.accessallc'));
                    if (controller !== undefined) {
                        data.cntrl = controller;
                    }
                    post(url, data).then(() => window.location.reload()).catch(fail);
                });
            });
        })();
    </script>
@endpush
