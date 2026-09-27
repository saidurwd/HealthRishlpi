@extends('layouts.app')

@section('title', 'Set user access')

@section('header')
    <x-page-header icon="fa fa-lock" title="Access Manager" subtitle="Manage" :breadcrumbs="['Groups' => route('userGroup.admin'), 'Set user access']" />
@endsection

@section('content')
    <p>
        <a href="{{ route('userGroup.admin') }}" class="btn btn-primary"><i class="fa fa-home"></i> User Group</a>
    </p>
    <x-card icon="fa fa-users" :title="$group->name">
        <x-slot:tools>
            <a href="#" class="btn btn-sm btn-link" data-acl-all="1" title="Deny all"><i class="fa fa-remove"></i> Deny all</a>
            <a href="#" class="btn btn-sm btn-link" data-acl-all="2" title="Access all"><i class="fa fa-check-square-o"></i> Access all</a>
        </x-slot:tools>
        @if ((int) $group->id === \App\Models\User::SUPER_GROUP)
            <div class="alert alert-info">Super Users pass every access check, whatever is switched on here.</div>
        @endif
        <div class="accordion" id="acl-accordion">
            @foreach ($sections as $section => $permissions)
                <div class="accordion-item">
                    <h4 class="accordion-header">
                        <button @class(['accordion-button', 'collapsed' => ! $loop->first]) type="button" data-bs-toggle="collapse" data-bs-target="#acl-{{ $loop->index }}">
                            {{ $section }}
                        </button>
                    </h4>
                    <div id="acl-{{ $loop->index }}" @class(['accordion-collapse collapse', 'show' => $loop->first])>
                        <div class="accordion-body">
                            <div class="mb-2">
                                <a href="#" class="me-3" data-acl-all="2" data-acl-section="{{ $section }}" title="Access all"><i class="fa fa-check-square-o"></i> Access all</a>
                                <a href="#" data-acl-all="1" data-acl-section="{{ $section }}" title="Deny all"><i class="fa fa-remove"></i> Deny all</a>
                            </div>
                            @foreach ($permissions as $permission)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                                    <span><i class="fa fa-check"></i> {{ $permission->title }} <small class="text-body-secondary">{{ $permission->name }}</small></span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="autoopen{{ $permission->id }}" data-acl-permission="{{ $permission->name }}" @checked($granted->has($permission->id))>
                                        <label class="form-check-label" for="autoopen{{ $permission->id }}">{{ $granted->has($permission->id) ? 'ON' : 'OFF' }}</label>
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

            document.querySelectorAll('[data-acl-permission]').forEach((toggle) => {
                toggle.addEventListener('change', () => {
                    const url = toggle.checked
                        ? @js(route('userGroup.turnon', $group->id))
                        : @js(route('userGroup.turnoff', $group->id));
                    toggle.disabled = true;
                    post(url, { permission: toggle.dataset.aclPermission })
                        .then(() => { toggle.nextElementSibling.textContent = toggle.checked ? 'ON' : 'OFF'; })
                        .catch(() => { toggle.checked = !toggle.checked; fail(); })
                        .finally(() => { toggle.disabled = false; });
                });
            });

            document.querySelectorAll('[data-acl-all]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const section = link.dataset.aclSection;
                    const data = { id: link.dataset.aclAll, group_id: @js($group->id) };
                    const url = section === undefined ? @js(route('userGroup.accessall')) : @js(route('userGroup.accessallc'));
                    if (section !== undefined) {
                        data.section = section;
                    }
                    post(url, data).then(() => window.location.reload()).catch(fail);
                });
            });
        })();
    </script>
@endpush
