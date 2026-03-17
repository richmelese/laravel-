<script>
    Alpine.data('editBooking', () =>{
        return {
            object_model: $wire.entangle('object_model'),
            object_id: $wire.entangle('object_id'),
            init(){
                console.log('init');

                this.initSelect2();
            },
            setObjectModel(model){
                this.object_id = '';
                this.object_model = model;
                this.initSelect2(true);

                // Set wire:model
                this.$wire.set('object_model', model, false);
                this.$wire.set('object_id', '', false);
            },
            initSelect2(reset = false){

                if(reset){
                    $('#select-object').val(null).trigger('change');
                }

                $('#select-object').select2({
                    placeholder: '{{__('Select')}}',
                    allowClear: true,
                    ajax: {
                        url: `/admin/module/${this.object_model}/getForSelect2`,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    }
                });
                console.log('initSelect2');
            }
        }
    })
</script>