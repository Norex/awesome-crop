(function($){
			
    $(document).ready(function() {

         $('#poststuff .field-image .add-image').live('click', function(e){
            // var field_key = $(this).parent().siblings('input.value').attr('name');
            var field_key = $(this).closest('.acf-image-uploader.clearfix').find('input').attr('name');
            var way = 'upload';
            setFieldKey(field_key, way);
        });

        $('a.edit-image.ir').remove(); /*.live('click', function(e){
            var field_key = $(this).closest('.acf-image-uploader.clearfix').find('input').attr('name');
            var way = 'edit';
            setFieldKey(field_key, way);
        });*/
        
    });

    function setFieldKey(field_key, way) {
        var start = field_key.indexOf('[') + 1;
        var end = field_key.indexOf(']');
        window.field_key = field_key.substring(start, end);
        window.post_type = $('input#post_type').val();
        window.openWay = way;
    }

})(jQuery);