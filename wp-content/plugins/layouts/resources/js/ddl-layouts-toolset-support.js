var DDLayout = DDLayout || {};

DDLayout.LayoutsToolsetSupport = function($){

};

DDLayout.LayoutsToolsetSupport.prototype.operate_extra_controls = function( $root, $append_to ){

    var data = this.fetch_extra_controls( $root );
    var controls = data.controls;

    jQuery( $append_to ).append(controls);
    //jQuery(controls).insertAfter('.wpv-title-section .wpv-settings-title-and-desc');

    jQuery('.ddl-setting-for-layouts-container-in-iframe .js-select2').select2({
        width:'555px'
    });
    window.parent.DDLayout.Dialogs.Prototype.setUpAdditionalClassInput( jQuery('.ddl-setting-for-layouts-container-in-iframe .js-select2-tokenizer') );

    jQuery('#'+$root+' .js-ddl-tag-name').val(data.tag).trigger('change');
    jQuery('#'+$root+' .js-edit-css-id').css('width', '555px');

    /* trigger click to prevent height to bother */
    jQuery('#'+$root+' .js-edit-css-class').css({
        height:'auto',
    }).trigger('click');

    jQuery('#'+$root+' #ddl-default-edit-cell-name').val(data.name);

    return data;
};

DDLayout.LayoutsToolsetSupport.prototype.fetch_extra_controls = function(who){
        return null;
};