@extends('admin.layouts.app')

@section('content')
    <form action="{{route('user.admin.store',['id'=>$row->id ?? -1])}}" method="post" class="needs-validation" novalidate>
        @csrf
        <div class="container">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar">{{$row->id ? 'Edit: '.$row->getDisplayName() : 'Add new user'}}</h1>
                </div>
            </div>
            @include('admin.message')
            <div class="row">
                <div class="col-md-9">
                    <div class="panel">
                        <div class="panel-title"><strong>{{ __('User Info')}}</strong></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__("Business name")}}</label>
                                        <input type="text" value="{{old('business_name',$row->business_name)}}" required name="business_name" placeholder="{{__("Business name")}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('E-mail')}}</label>
                                        <input type="email" required value="{{old('email',$row->email)}}" placeholder="{{ __('Email')}}" name="email" class="form-control"  >
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__("User name")}}</label>
                                        <input type="text" name="user_name" required value="{{old('user_name',$row->user_name)}}" placeholder="{{__("User name")}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            {{__("Password")}}
                                            @if(!$row->id)
                                                <span class="text-danger">*</span>
                                            @else
                                                <small class="text-muted">({{__("Leave blank to keep current password")}})</small>
                                            @endif
                                        </label>
                                        <input type="password" name="password" autocomplete="new-password"
                                               @if(!$row->id) required @endif
                                               minlength="6"
                                               placeholder="{{__("Password (min 6 characters)")}}"
                                               class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            {{__("Confirm Password")}}
                                            @if(!$row->id)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <input type="password" name="password_confirmation" autocomplete="new-password"
                                               @if(!$row->id) required @endif
                                               minlength="6"
                                               placeholder="{{__("Re-enter password")}}"
                                               class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__("First name")}}</label>
                                        <input type="text" required value="{{old('first_name',$row->first_name)}}" name="first_name" placeholder="{{__("First name")}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__("Last name")}}</label>
                                        <input type="text" required value="{{old('last_name',$row->last_name)}}" name="last_name" placeholder="{{__("Last name")}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Phone Number')}}</label>
                                        <input type="text" value="{{old('phone',$row->phone)}}" placeholder="{{ __('Phone')}}" name="phone" class="form-control" required   >
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Birthday')}}</label>
                                        <input type="text" value="{{ old('birthday',$row->birthday ? date("Y/m/d",strtotime($row->birthday)) :'') }}" placeholder="{{ __('Birthday')}}" name="birthday" class="form-control has-datepicker input-group date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Address Line 1')}}</label>
                                        <input type="text" value="{{old('address',$row->address)}}" placeholder="{{ __('Address')}}" name="address" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Address Line 2')}}</label>
                                        <input type="text" value="{{old('address2',$row->address2)}}" placeholder="{{ __('Address 2')}}" name="address2" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__("City")}}</label>
                                        <input type="text" value="{{old('city',$row->city)}}" name="city" placeholder="{{__("City")}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__("State")}}</label>
                                        <input type="text" value="{{old('state',$row->state)}}" name="state" placeholder="{{__("State")}}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="">{{__("Country")}}</label>
                                        <select name="country" class="form-control" id="country-sms-testing" required>
                                            <option value="">{{__('-- Select --')}}</option>
                                            @foreach(get_country_lists() as $id=>$name)
                                                <option @if($row->country==$id) selected @endif value="{{$id}}">{{$name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{__("Zip Code")}}</label>
                                        <input type="text" value="{{old('zip_code',$row->zip_code)}}" name="zip_code" placeholder="{{__("Zip Code")}}" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label">{{ __('Biographical')}}</label>
                                <div class="">
                                    <textarea name="bio" class="d-none has-ckeditor" cols="30" rows="10">{{old('bio',$row->bio)}}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel">
                        <div class="panel-title"><strong>{{ __('Publish')}}</strong></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label>{{__('Status')}}</label>
                                <select required class="custom-select" name="status">
                                    <option @if(old('status',$row->status) =='publish') selected @endif value="publish">{{ __('Publish')}}</option>
                                    <option @if(old('status',$row->status) =='blocked') selected @endif value="blocked">{{ __('Blocked')}}</option>
                                </select>
                            </div>
                            @if(is_admin())
                                @if(empty($user_type) or $user_type != 'vendor')
                                    <div class="form-group">
                                        <label>{{__('Role')}} <span class="text-danger">*</span></label>
                                        <select required class="form-control" name="role_id">
                                            <option value="">{{ __('-- Select --')}}</option>
                                            @foreach($roles as $role)
                                                <option value="{{$role->id}}" @if(old('role_id',$row->role_id) == $role->id) selected @elseif(old('role_id')  == $role->id ) selected @elseif(request()->input("user_type")  == strtolower($role->name) ) selected @endif >{{ucfirst($role->name)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label>{{__('Email Verified?')}}</label>
                                    <select  class="form-control" name="is_email_verified">
                                        <option value="">{{ __('No')}}</option>
                                        <option @if(old('is_email_verified',$row->email_verified_at ? 1 : 0)) selected @endif value="1">{{__('Yes')}}</option>
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-title"><strong>{{ __('Vendor')}}</strong></div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label>{{__('Vendor Commission Type')}}</label>
                                <div class="form-controls">
                                    <select name="vendor_commission_type" class="form-control">
                                        <option value="">{{__("Default")}}</option>
                                        <option value="percent" {{old("vendor_commission_type",($row->vendor_commission_type ?? '')) == 'percent' ? 'selected' : ''  }}>{{__('Percent')}}</option>
                                        <option value="amount" {{old("vendor_commission_type",($row->vendor_commission_type ?? '')) == 'amount' ? 'selected' : ''  }}>{{__('Amount')}}</option>
                                        <option value="disable" {{old("vendor_commission_type",($row->vendor_commission_type ?? '')) == 'disable' ? 'selected' : ''  }}>{{__('Disable Commission')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{__('Vendor commission value')}}</label>
                                <div class="form-controls">
                                    <input type="text" class="form-control" name="vendor_commission_amount" value="{{old("vendor_commission_amount",($row->vendor_commission_amount ?? '')) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-title"><strong>{{ __('Avatar')}}</strong></div>
                        <div class="panel-body">
                            <div class="form-group">
                                {!! \Modules\Media\Helpers\FileHelper::fieldUpload('avatar_id',old('avatar_id',$row->avatar_id)) !!}
                            </div>
                        </div>
                    </div>
                    @php
                        $chapa_banks = $chapa_banks ?? [];
                        $vendorRoleId = (int) ($vendor_role_id ?? 0);
                        $isChapaVendorUser = $vendorRoleId > 0 && (int) old('role_id', $row->role_id ?? 0) === $vendorRoleId;
                        $isChapaVendorUser = $isChapaVendorUser || (int) old('role_id', $row->role_id ?? 0) === 2 || strtolower((string) optional($row->role)->code) === 'vendor';
                        $isChapaVendorUser = $isChapaVendorUser || (request()->input('user_type') === 'vendor' && !$row->id);
                    @endphp
                    @if(is_admin())
                    <div class="panel" id="admin-chapa-settlement-panel" style="{{ $isChapaVendorUser ? '' : 'display:none' }}" data-vendor-role-id="{{ $vendorRoleId ?: 2 }}">
                        <div class="panel-title"><strong>{{ __('Chapa settlement (vendor only)')}}</strong></div>
                        <div class="panel-body">
                            <p class="text-muted small">{{ __('When you save a vendor with complete bank and split details and no subaccount exists yet, a Chapa subaccount is created once and the id is stored automatically.')}}</p>
                            @error('chapa_settlement')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                            @if(trim((string) $row->getMeta('chapa_subaccount_id')) !== '')
                                <div class="alert alert-success small">
                                    {{ __('Active Chapa subaccount id:') }}
                                    <code>{{ $row->getMeta('chapa_subaccount_id') }}</code>
                                    <br><span class="text-muted">{{ __('Bank and split fields below update local records only; the subaccount on Chapa is not recreated.') }}</span>
                                </div>
                            @endif
                            <div class="form-group">
                                <label>{{ __('Settlement / merchant name (Chapa)')}}</label>
                                <input type="text" class="form-control" name="chapa_business_name" maxlength="255"
                                       value="{{ old('chapa_business_name', $row->getMeta('chapa_business_name') ?: $row->business_name) }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('Account holder name')}} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="chapa_account_name" maxlength="255"
                                       value="{{ old('chapa_account_name', $row->getMeta('chapa_account_name') ?: trim(($row->first_name ?? '').' '.($row->last_name ?? ''))) }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('Bank')}} <span class="text-danger">*</span></label>
                                @if(!empty($chapa_banks))
                                    <select class="form-control" name="chapa_bank_code">
                                        <option value="">{{ __('-- Select --')}}</option>
                                        @foreach($chapa_banks as $bank)
                                            @php
                                                $bankId = data_get($bank, 'id');
                                                $bankName = data_get($bank, 'name');
                                                $bankCurrency = data_get($bank, 'currency');
                                            @endphp
                                            <option value="{{ $bankId }}" @selected((string) old('chapa_bank_code', $row->getMeta('chapa_bank_code')) === (string) $bankId)>
                                                {{ $bankName }}@if($bankCurrency) ({{ $bankCurrency }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control" name="chapa_bank_code"
                                           value="{{ old('chapa_bank_code', $row->getMeta('chapa_bank_code')) }}"
                                           placeholder="{{ __('Bank code (e.g. from Chapa banks list)')}}">
                                    <small class="text-muted">{{ __('Enable Chapa and configure keys to load banks here, or enter the bank code manually.')}}</small>
                                @endif
                            </div>
                            <div class="form-group">
                                <label>{{ __('Account number')}}</label>
                                <input type="text" class="form-control" name="chapa_account_number" maxlength="64"
                                       value="{{ old('chapa_account_number', $row->getMeta('chapa_account_number')) }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('Split type')}}</label>
                                <select class="form-control" name="chapa_split_type">
                                    <option value="percentage" @selected(old('chapa_split_type', $row->getMeta('chapa_split_type') ?: 'percentage') === 'percentage')>{{ __('Percentage (0–1, e.g. 0.05 = 5%)')}}</option>
                                    <option value="flat" @selected(old('chapa_split_type', $row->getMeta('chapa_split_type')) === 'flat')>{{ __('Flat (ETB)')}}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Split value')}}</label>
                                <input type="text" class="form-control" name="chapa_split_value"
                                       value="{{ old('chapa_split_value', $row->getMeta('chapa_split_value')) }}"
                                       placeholder="{{ __('e.g. 0.05')}}">
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <span></span>
                <button class="btn btn-primary" type="submit">{{ __('Save Change')}}</button>
            </div>
        </div>
    </form>
    @if(is_admin())
    <script>
        (function () {
            var panel = document.getElementById('admin-chapa-settlement-panel');
            if (!panel) return;
            var roleSelect = document.querySelector('select[name="role_id"]');
            if (!roleSelect) {
                panel.style.display = '';
                return;
            }
            var vendorRoleId = parseInt(panel.getAttribute('data-vendor-role-id'), 10) || 2;
            function sync() {
                var v = parseInt(roleSelect.value, 10);
                panel.style.display = (v === vendorRoleId || v === 2) ? '' : 'none';
            }
            roleSelect.addEventListener('change', sync);
            sync();
        })();
    </script>
    @endif
@endsection
