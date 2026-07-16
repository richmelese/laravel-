@if(!empty($customHtml))
    {!! $customHtml !!}
@endif

<div class="form-group mt-3">
    <label for="chapa_mobile">{{ __('Mobile wallet number') }} <span class="required">*</span></label>
    <input
        type="tel"
        class="form-control"
        id="chapa_mobile"
        name="chapa_mobile"
        value="{{ old('chapa_mobile', auth()->user()->phone ?? '') }}"
        placeholder="0911234567"
        inputmode="numeric"
        autocomplete="tel"
    >
    <small class="form-text text-muted">
        {{ __('A :method USSD authorization request will be sent to this number. You will remain on this website.', ['method' => $paymentMethod]) }}
    </small>
</div>
