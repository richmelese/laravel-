<div class="search-form-top">
    <div class="search-form-wrapper auto-height-form-search  bc_search-form-tour">
        <div class="container tour-search-form-home style2">
            <div class="search-form">
                <form wire:submit.prevent="submit" class="form bc_form" method="get" wire:ignore>
                    @php $car_search_fields = setting_item_array('car_search_fields');
                    $car_search_fields = array_values(
                        \Illuminate\Support\Arr::sort($car_search_fields, function ($value) {
                            return $value['position'] ?? 0;
                        }),
                    );
                    @endphp
                    @if (!empty($car_search_fields))
                        @foreach ($car_search_fields as $field)
                            @php $field['title'] = $field['title_'.app()->getLocale()] ?? $field['title'] ?? "" @endphp
                            <div class="col-md-{{ $field['size'] ?? '6' }}">
                                @switch($field['field'])
                                    @case ('service_name')
                                        @include('Car::frontend.layouts.search.fields.service_name')
                                        @break

                                    @case ('location')
                                        @include('Car::frontend.layouts.search.fields.location')
                                        @break

                                    @case ('date')
                                        @include('Car::frontend.layouts.search.fields.date')
                                        @break

                                    @case ('attr')
                                        @include('Car::frontend.layouts.search.fields.attr')
                                        @break
                                @endswitch
                            </div>
                        @endforeach
                    @endif
                    <div class="form-button-new ">
                        <button class="btn btn-primary btn-search" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@script
    <script>
        $('.form-date-search',$wire.$el).each(function () {
            var parent = $(this),
                check_in_input = $('.check-in-input', parent),
                check_out_input = $('.check-out-input', parent);

            check_in_input.on('change', function () {
                $wire.set('start', $(this).val(), false);
            });
            check_out_input.on('change', function () {
                $wire.set('end', $(this).val(), false);
            });
        });
        $('[name="location_id"]',$wire.$el).on('change', function () {
            $wire.set('location_id', $(this).val(), false);
        });
    </script>
@endscript
