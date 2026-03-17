@php
    $selected = $this->attrs;
@endphp
@foreach ($attributes as $item)
    @if(empty($item['hide_in_filter_search']))
        @php
            $translate = $item->translate();
        @endphp
        <div class="sidebar-item pag bc_icheck">
            <div class="item-title">
                <label>{{$translate->name}}</label>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <ul style="max-height: 180px" class="overflow-auto">
                    @foreach($item->terms as $key => $term)
                        @php $translate = $term->translate(); @endphp
                        <li class=" bc_icheck-item">
                            <label>
                                {!! $translate->name !!}
                                <input class="filter-tax" x-on:click="{{$scrollIntoViewJsSnippet}}"
                                           @if(in_array($term->slug,$selected[$item->id] ?? [])) checked
                                           @endif type="checkbox" wire:change="toggleTerm({{$item->id}},'{{$term->slug}}')"
                                           value="{{$term->slug}}">
                                <span class="checkmark fcheckbox"></span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endforeach
