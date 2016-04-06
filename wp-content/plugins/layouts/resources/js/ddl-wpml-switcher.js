var DDLayout = DDLayout || {};

DDLayout.WPMLSwitcher  = function($){
    var self = this,
        $lang_select = null,
        $dialog = null;

    self.default_language = DDLayout_LangSwitch_Settings.default_language;
    self.current_language = self.default_language;
    self.post_id = null;

    self.init = function(){
        wp.hooks.addAction('ddl-wpml-init', self.dialog_before_load, 10, 3);
        wp.hooks.addAction('ddl-wpml-refresh', self.ajax_response_callback, 10, 3);
        wp.hooks.addAction('ddl-wpml-cleanup', self.clean_up_events, 10);
        wp.hooks.addFilter('ddl-js-apply-language', self.get_current_language);
    };

    self.dialog_before_load = function( dialog, post_id, args ){
        $dialog = dialog;
        self.post_id = post_id;
        $lang_select = $('.js-ddl-single-assignments-lang-select', $dialog);
        self.init_selector( $lang_select );
    };

    self.ajax_response_callback = function( container, post_id, args ){
        $lang_select = $('.js-ddl-single-assignments-lang-select', $dialog);
        self.init_selector( $lang_select );
    };

    self.get_current_language = function(lang){
        return self.current_language;
    };

    self.init_selector = function( $lang_select ){
        function format( option, select2_element ) {

            if( $(option.element).data('languageIcon') === 'none' ) return option.text;
            if( select2_element.hasClass('select2-chosen') ){
                var icon = $(option.element).data('languageIcon') ? '<i class="ddl-wpml-flag" style="background:url('+$(option.element).data('languageIcon')+') no-repeat bottom left"></i>' : '';
                return  icon + option.text;
            } else {
                var icon = $(option.element).data('languageIcon') ? '<i class="ddl-wpml-flag" style="background:url('+$(option.element).data('languageIcon')+') no-repeat bottom left"></i>' : '';
                return icon + option.text;
            }
        }

        $lang_select.select2({
            minimumResultsForSearch: Infinity,
            formatResult: format,
            formatSelection: format,
            width: '150px',
            val:self.current_language,
            escapeMarkup: function(m) { return m; }
        });

        $lang_select.select2('val', self.current_language );
        self.change_handler( $lang_select );

    };

    self.clean_up_events = function( ){
        self.current_language = self.default_language;
        $lang_select.select2('close');
    };

    self.change_handler = function( $lang_select ){

        $lang_select.on('change', function( event ){
            self.current_language = $(this).val();
            wp.hooks.doAction('ddl-reload-post-list-by-language', event);
        });
    };

    self.init();
};

DDLayout.WPMLSwitcher.builder = function( $ ){
    DDLayout.wpmlSwitcher = new DDLayout.WPMLSwitcher($);
};
wp.hooks.addAction('ddl-wpml-language-switcher-build', DDLayout.WPMLSwitcher.builder, 10, 1);