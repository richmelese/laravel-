@extends('layouts.user')

@section('content')
    <h2 class="title-bar">
        {{ __('Chapa Split Payment') }}
    </h2>
    @include('admin.message')

    <div class="alert alert-info">
        {{ __('Your Chapa settlement account is created and managed by the site administrator when your vendor profile is saved in the admin area. You cannot change it from here.') }}
    </div>

    @if(empty($gateway))
        <div class="alert alert-warning">
            {{ __('The Chapa payment gateway is not enabled. Please contact the platform administrator.') }}
        </div>
    @else
        <p class="text-muted">
            {{ __('When configured, your share of booking payments is split automatically according to the settlement details on file.') }}
        </p>

        @if(!empty($subaccount_id))
            <div class="alert alert-success">
                <strong>{{ __('Active subaccount') }}:</strong>
                <code>{{ $subaccount_id }}</code>
            </div>
        @else
            <div class="alert alert-secondary">
                {{ __('No Chapa subaccount id is stored for your account yet. Ask your administrator to complete settlement details in the admin user screen.') }}
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">{{ __('Settlement details on file') }}</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-3">{{ __('Business / merchant name') }}</dt>
                    <dd class="col-sm-9">{{ $form['business_name'] ?: '—' }}</dd>
                    <dt class="col-sm-3">{{ __('Account holder') }}</dt>
                    <dd class="col-sm-9">{{ $form['account_name'] ?: '—' }}</dd>
                    <dt class="col-sm-3">{{ __('Bank code') }}</dt>
                    <dd class="col-sm-9">{{ $form['bank_code'] ?: '—' }}</dd>
                    <dt class="col-sm-3">{{ __('Account number') }}</dt>
                    <dd class="col-sm-9">{{ $form['account_number'] ? '••••' . substr((string) $form['account_number'], -4) : '—' }}</dd>
                    <dt class="col-sm-3">{{ __('Split') }}</dt>
                    <dd class="col-sm-9">{{ $form['split_type'] }} / {{ $form['split_value'] }}</dd>
                </dl>
            </div>
        </div>
    @endif
@endsection
