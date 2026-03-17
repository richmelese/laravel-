<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar">{{__('Edit Booking #:index',['index'=>$booking->id])}}</h1>
    </div>
    <form wire:submit.prevent="submit">
    <div x-data="editBooking">
        <div class="row">
            <div class="col-md-9">
                <div class="panel">
                    <div class="panel-title">
                        <strong>{{__('Item Detail #:index',['index'=>$booking->id])}}</strong>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('Select service')}}</label>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <select x-on:change="setObjectModel($event.target.value)" x-bind:value="object_model" class="form-control">
                                                <option value="">{{__('-- Select Type --')}}</option>
                                                @if(!empty(get_bookable_services()))
                                                    @foreach(get_bookable_services() as $id=>$service)
                                                        <option value="{{$id}}">{{ucfirst($id)}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-md-6" wire:ignore>
                                            <select x-on:change="setObjectId($event.target.value)" x-bind:value="object_id"  id="select-object" class="form-control">
                                                @if(!empty($this->service))
                                                    <option value="{{$this->service->id}}">{{$this->service->title}}</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                @include('Booking::admin.booking.parts.sidebar')
            </div>
        </div>
    </div>
    </form>
</div>

@script
    @include('Booking::admin.booking.parts.script')
@endscript