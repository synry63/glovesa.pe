var DDLayout = DDLayout || {};

DDLayout.LayoutsSettingsScreen = function( $ ) {
	
	var self = this, amount_posts, $button_amount = $('.js-max-posts-num-save'), $input_amount = $('.js-ddl-max-posts-num');
	
	/**
	* --------------------
	* Toolset Admin Bar Menu
	* --------------------
	*/
	
	self.toolset_admin_bar_menu_state = ( $( '#js-wpv-toolset-admin-bar-menu' ).length > 0 ) ? $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' ) : false;

    self.handle_admin_bar_option_change = function(){
        $( '#js-wpv-toolset-admin-bar-menu' ).on( 'change', function() {
            var thiz = $( this ),
                thiz_container = thiz.closest( '.js-wpv-setting-container' ),
                thiz_save_button = thiz_container.find( '.js-wpv-toolset-admin-bar-menu-settings-save' );
            if ( thiz.prop( 'checked' ) == self.toolset_admin_bar_menu_state ) {
                thiz_save_button
                    .addClass( 'button-secondary' )
                    .removeClass( 'button-primary' )
                    .prop( 'disabled', true );
            } else {
                thiz_save_button
                    .addClass( 'button-primary' )
                    .removeClass( 'button-secondary' )
                    .prop( 'disabled', false );
            }
        });
    };

	self.handle_admin_bar_option_save = function(){
        $( '.js-wpv-toolset-admin-bar-menu-settings-save' ).on( 'click', function( e ) {
            e.preventDefault();
            var thiz = $( this ),
                spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
                thiz_container = thiz.closest( '.js-wpv-setting-container' ),
                thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
                data = {
                    action: 'ddl_update_toolset_admin_bar_menu_status',
                    status: $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' ),
                    wpnonce: $('#ddl_toolset_admin_bar_menu_nonce').val()
                };
            $.ajax({
                async: false,
                type: "POST",
                dataType: "json",
                url: ajaxurl,
                data: data,
                success: function( response ) {
                    if ( response.success ) {
                        self.toolset_admin_bar_menu_state = $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' );
                        thiz
                            .addClass( 'button-secondary' )
                            .removeClass( 'button-primary' )
                            .prop( 'disabled', true );
                        thiz_messages_container
                            .wpvToolsetMessage({
                                text: ddl_settings_texts.setting_saved,
                                type: 'success',
                                inline: true,
                                stay: false
                            });
                    }
                },
                error: function (ajaxContext) {
                    //console.log( "Error: ", ajaxContext.responseText );
                },
                complete: function() {
                    spinnerContainer.remove();
                }
            });
        });
    };

    /**
     * --------------------
     * WP_Query Limit
     * --------------------
     */

	self.init_post_amount = function(){
		amount_posts = +$input_amount.val();

        $input_amount.on('change', function(){
				if( +$(this).val() !== amount_posts ){
                    $button_amount.addClass( 'button-primary' )
						.removeClass( 'button-secondary' )
						.prop( 'disabled', false );
				} else {
                    $button_amount.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
				}
		});

        self.do_change_posts_amount();
	};

    self.do_change_posts_amount = function(){
        $button_amount.on('click', function(){
            var amount_nonce = $("#ddl_max-posts-num_nonce").val(),
                thiz = $( this ),
                spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
                thiz_container = thiz.closest( '.js-wpv-setting-container' ),
                thiz_messages_container = thiz_container.find( '.js-wpv-messages' );

            amount_posts = +$input_amount.val();

            var data = {
                'ddl_max-posts-num_nonce':amount_nonce,
                action:'ddl_set_max_posts_amount',
                amount_posts:amount_posts
            };

            WPV_Toolset.Utils.do_ajax_post(data, {
                success:function( response, params ){
                    var res = response.Data;
                    spinnerContainer.remove();
                    thiz
                        .addClass( 'button-secondary' )
                        .removeClass( 'button-primary' )
                        .prop( 'disabled', true );
                    if( res.message ){

                        thiz_messages_container
                            .wpvToolsetMessage({
                                text: res.message,
                                type: 'success',
                                inline: true,
                                stay: false
                            });
                    } else if( res.error){
                        thiz_messages_container
                            .wpvToolsetMessage({
                                text: res.error,
                                type: 'warning',
                                inline: true,
                                stay: false
                            });
                    }
                    amount_posts = res.amount;
                },
                error:function( response, params ){
                    thiz_messages_container
                        .wpvToolsetMessage({
                            text: response.error,
                            type: 'error',
                            inline: true,
                            stay: false
                        });
                }
            })
        })
    };

    self.handle_background_change = function(){
        if( /#toolset-admin-bar-settings$/.test( window.location.href ) ) {
            $( '#toolset-admin-bar-settings' ).parent().css( 'background-color', '#ffffca' );
        }
    };

		
	self.init = function() {
        self.handle_admin_bar_option_change();
        self.handle_admin_bar_option_save();
        self.init_post_amount();
        self.handle_background_change();
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    DDLayout.layouts_settings_screen = new DDLayout.LayoutsSettingsScreen( $ );
});
