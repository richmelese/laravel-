<div class="panel">
    <div class="panel-title">
        <strong>{{__('Summary')}}</strong>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{__('Booking ID')}}</label>
                    <input type="text" class="form-control" value="{{$booking->id}}" disabled>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{__('Booking Status')}}</label>
                    <select wire:model="status" class="form-control">
                        <option value="">{{__('-- Select Status --')}}</option>
                        @if(!empty($statues))
                            @foreach($statues as $status)
                                <option value="{{$status}}">{{booking_status_to_text($status)}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">{{__('Save Changes')}}</button>
    </div>
</div>