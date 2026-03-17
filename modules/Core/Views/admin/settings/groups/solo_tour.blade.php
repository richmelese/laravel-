<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title">{{__('Header & Footer Settings')}}</h3>
        <p class="form-group-desc">{{__('Change your options')}}</p>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-body">
                @if(is_default_lang())
                    <div class="form-group">
                        <label>{{__("Solo Logo")}}</label>
                        <div class="form-controls form-group-image">
                            {!! \Modules\Media\Helpers\FileHelper::fieldUpload('solotour_logo_id',setting_item('solotour_logo_id')) !!}
                        </div>
                    </div>
                @endif
                <div class="form-group">
                    <label>{{__("Topbar Text")}}</label>
                    <div class="form-controls">
                        <div id="solotour_topbar_text" class="ace-editor" style="height: 400px" data-theme="textmate" data-mod="html">{{setting_item_with_lang('solotour_topbar_text',request()->query('lang'))}}</div>
                        <textarea class="d-none" name="solotour_topbar_text" > {{ setting_item_with_lang('solotour_topbar_text',request()->query('lang')) }} </textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{__("Footer List Widget")}}</label>
                    <div class="form-controls">
                        <div class="form-group-item">
                            <div class="form-group-item">
                                <div class="g-items-header">
                                    <div class="row">
                                        <div class="col-md-3">{{__("Title")}}</div>
                                        <div class="col-md-2">{{__('Size')}}</div>
                                        <div class="col-md-6">{{__('Content')}}</div>
                                        <div class="col-md-1"></div>
                                    </div>
                                </div>
                                <div class="g-items">
                                    <?php
                                    $social_share = setting_item_with_lang('solotour_list_widget_footer',request()->query('lang'));
                                    if(!empty($social_share)) $social_share = json_decode($social_share,true);
                                    if(empty($social_share) or !is_array($social_share))
                                        $social_share = [];
                                    ?>
                                    @foreach($social_share as $key=>$item)
                                        <div class="item" data-number="{{$key}}">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <input type="text" name="solotour_list_widget_footer[{{$key}}][title]" class="form-control" value="{{$item['title'] ?? ''}}">
                                                </div>
                                                <div class="col-md-2">
                                                    <select class="form-control" name="solotour_list_widget_footer[{{$key}}][size]">
                                                        <option @if(!empty($item['size']) && $item['size']=='3') selected @endif value="3">1/4</option>
                                                        <option @if(!empty($item['size']) && $item['size']=='4') selected @endif value="4">1/3</option>
                                                        <option @if(!empty($item['size']) && $item['size']=='6') selected @endif value="6">1/2</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <textarea name="solotour_list_widget_footer[{{$key}}][content]" rows="5" class="form-control">{{$item['content']}}</textarea>
                                                </div>
                                                <div class="col-md-1">
                                                    <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-right">
                                    <span class="btn btn-info btn-sm btn-add-item"><i class="icon ion-ios-add-circle-outline"></i> {{__('Add item')}}</span>
                                </div>
                                <div class="g-more hide">
                                    <div class="item" data-number="__number__">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <input type="text" __name__="solotour_list_widget_footer[__number__][title]" class="form-control" value="">
                                            </div>
                                            <div class="col-md-2">
                                                <select class="form-control" __name__="solotour_list_widget_footer[__number__][size]">
                                                    <option value="3">1/4</option>
                                                    <option value="4">1/3</option>
                                                    <option value="6">1/2</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <textarea __name__="solotour_list_widget_footer[__number__][content]" class="form-control" rows="5"></textarea>
                                            </div>
                                            <div class="col-md-1">
                                                <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{__("Footer Text Left")}}</label>
                    <div class="form-controls">
                        <textarea name="solotour_footer_text_left" class="d-none has-ckeditor" cols="30" rows="10">{{setting_item_with_lang('solotour_footer_text_left',request()->query('lang')) }}</textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{__("Footer Text Right")}}</label>
                    <div class="form-controls">
                        <textarea name="solotour_footer_text_right" class="d-none has-ckeditor" cols="30" rows="10">{{setting_item_with_lang('solotour_footer_text_right',request()->query('lang')) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('js')
    <script src="{{asset('libs/ace/src-min-noconflict/ace.js')}}" type="text/javascript" charset="utf-8"></script>
    <script>
        (function ($) {
            $('.ace-editor').each(function () {
                var editor = ace.edit($(this).attr('id'));
                editor.setTheme("ace/theme/"+$(this).data('theme'));
                editor.session.setMode("ace/mode/"+$(this).data('mod'));
                var me = $(this);

                editor.session.on('change', function(delta) {
                    // delta.start, delta.end, delta.lines, delta.action
                    me.next('textarea').val(editor.getValue());
                });
            });
        })(jQuery)
    </script>
@endpush
