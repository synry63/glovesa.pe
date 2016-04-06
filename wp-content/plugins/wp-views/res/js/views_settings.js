var WPViews = WPViews || {};

WPViews.ViewsSettingsScreen = function( $ ) {
	
	var self = this;
	
	/**
	* --------------------
	* Hidden custom fields
	* --------------------
	*/
	
	self.hidden_cf_state = ( $( '.js-wpv-all-hidden-cf-list' ).length > 0 ) ? $( '.js-wpv-all-hidden-cf-list input[type="checkbox"]' ).serialize() : false;
	
	$( '.js-wpv-show-hidden-cf-list' ).on( 'click', function( e ) {
        e.preventDefault();
        $( '.js-wpv-hidden-cf-toggle' ).fadeIn( 'fast' );
        $( '.js-wpv-hidden-cf-summary' ).hide();
        return false;
    });

    $( '.js-wpv-hide-hidden-cf-list' ).on( 'click', function( e ) {
        e.preventDefault();
        $( '.js-wpv-hidden-cf-toggle' ).hide();
        $( '.js-wpv-hidden-cf-summary' ).fadeIn( 'fast' );
        return false;
    });
	
	$( document ).on( 'change', '.js-wpv-all-hidden-cf-list input', function() {
		if ( self.hidden_cf_state == $( '.js-wpv-all-hidden-cf-list input[type="checkbox"]' ).serialize() ) {
			$( '.js-wpv-save-hidden-cf-list' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		} else {
			$( '.js-wpv-save-hidden-cf-list' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		}
	});
	
	 $( '.js-wpv-save-hidden-cf-list' ).on( 'click', function( e ) {
        e.preventDefault();
        var thiz = $( this ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show(),
        thiz_selected_list = $( '.js-wpv-selected-hidden-cf-list' ),
        thiz_checked = $('.js-wpv-all-hidden-cf-list :checked'),
        thiz_exists_message = $('.js-wpv-hidden-cf-exists-message'),
        thiz_no_exists_message = $('.js-wpv-no-hidden-cf-message'),
        data;
		
		thiz
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' )
			.prop( 'disabled', true );

        data = $( '.js-wpv-all-hidden-cf-list input[type="checkbox"]' ).serialize();
        data += '&action=wpv_get_show_hidden_custom_fields';
        data += '&wpnonce=' + $( '#wpv_show_hidden_custom_fields_nonce' ).val();

        $.ajax({
            async: false,
			dataType: "json",
            type: "POST",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
                    thiz_selected_list.empty();
                    if ( thiz_checked.length !== 0 ) {
                        thiz_exists_message.fadeIn('fast');
                        thiz_no_exists_message.hide();
                        thiz_selected_list.fadeIn('fast');
                        $.each( thiz_checked, function() {
                            thiz_selected_list.append('<li>' + $(this).next('label').text() + '</li>');
                        });
                    } else {
                        thiz_exists_message.hide();
                        thiz_no_exists_message.fadeIn('fast');
                        thiz_selected_list.hide();
                    }
					self.hidden_cf_state = $( '.js-wpv-all-hidden-cf-list input[type="checkbox"]' ).serialize();
                    $( '.js-wpv-hidden-cf-summary' ).fadeIn('fast');
                    $( '.js-wpv-hidden-cf-toggle' ).hide();
                    $( '.js-wpv-hidden-cf-update-message' )
						.show()
						.fadeOut( 'slow' );
                } else {
                    console.log( "Error: AJAX returned ", response );
                }
            },
            error: function (ajaxContext) {
                console.log( "Error: ", ajaxContext.responseText );
            },
            complete: function() {
				spinnerContainer.remove();
            }
        });
        return false;
    });
	
	/**
	* --------------------
	* Custom inner shortcodes and conditional functions
	* --------------------
	*/
	
	// Save custom inner shortcodes and conditional functions options

	$( document ).on( 'input cut paste', '.js-wpv-add-item-settings-form-newname', function( e ) {
		var thiz = $( this ),
		thiz_form = thiz.closest( '.js-wpv-add-item-settings-form' );
		$( '.js-wpv-cs-error, .js-wpv-cs-dup, .js-wpv-cs-ajaxfail', thiz_form ).hide();
		if ( thiz.val() != '' ) {
			$( '.js-wpv-add-item-settings-form-button', thiz_form )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		} else {
			$( '.js-wpv-add-item-settings-form-button', thiz_form )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		}
	});

	$( '.js-wpv-add-item-settings-form' ).submit( function( e ) {
		e.preventDefault();
		var thiz = $( this );
		$( '.js-wpv-add-item-settings-form-button', thiz ).click();
		return false;
	});
	
	// Add additional inner shortcodes
	
	$( '.js-wpv-custom-inner-shortcodes-add' ).on( 'click', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		shortcode_pattern = /^[a-z0-9\-\_]+$/,
		parent_form = thiz.closest( '.js-wpv-add-item-settings-form' ),
		parent_container = thiz.closest( '.js-wpv-add-item-settings-wrapper' ),
		newshortcode = $( '.js-wpv-add-item-settings-form-newname', parent_form ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">');
		$( '.js-wpv-cs-error, .js-wpv-cs-dup, .js-wpv-cs-ajaxfail', parent_form ).hide();
		if ( shortcode_pattern.test( newshortcode.val() ) == false ) {
			$( '.js-wpv-cs-error', parent_form ).show();
		} else if ( $( '.js-' + newshortcode.val() + '-item', parent_container ).length > 0 ) {
			$( '.js-wpv-cs-dup', parent_form ).show();
		} else {
			spinnerContainer.insertAfter( thiz ).show();
			thiz
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
			var data = {
				action: 'wpv_update_custom_inner_shortcodes',
				csaction: 'add',
				cstarget: newshortcode.val(),
				wpnonce: $( '#wpv_custom_inner_shortcodes_nonce' ).val()
			};

			$.ajax({
				async: false,
				dataType: "json",
				type: "POST",
				url: ajaxurl,
				data: data,
				success: function( response ) {
					if ( response.success ) {
						$( '.js-wpv-add-item-settings-list', parent_container )
							.append('<li class="js-' + newshortcode.val() + '-item"><span class="">[' + newshortcode.val() + ']</span> <i class="icon-remove-sign fa fa-times-circle js-wpv-custom-shortcode-delete" data-target="' + newshortcode.val() + '"></i></li>');
						newshortcode.val('');
					} else {
						$( '.js-wpv-cs-ajaxfail', parent_form ).show();
						console.log( "Error: AJAX returned ", response );
					}
				},
				error: function (ajaxContext) {
					$( '.js-wpv-cs-ajaxfail', parent_form ).show();
					console.log( "Error: ", ajaxContext.responseText );
				},
				complete: function() {
					spinnerContainer.remove();
				}
			});
		}
		return false;
	});
	
	// Delete additional inner shortcodes

	$(document).on('click', '.js-wpv-custom-shortcode-delete', function(e){
		e.preventDefault();
		var thiz = $( this ),
		thiz_target = thiz.data( 'target' ),
		parent_container = thiz.closest( '.js-wpv-add-item-settings-wrapper' ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( $( '.js-wpv-custom-inner-shortcodes-add' ) ).show();
		var data = {
			action: 'wpv_update_custom_inner_shortcodes',
			csaction: 'delete',
			cstarget: thiz_target,
			wpnonce: $( '#wpv_custom_inner_shortcodes_nonce' ).val()
		};

		$.ajax({
			async: false,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					$( 'li.js-' + thiz_target + '-item', parent_container )
						.addClass( 'remove' )
						.fadeOut( 'fast', function() { 
							$( this ).remove(); 
						});
				} else {
					$( '.js-wpv-cs-ajaxfail', parent_container ).show();
					console.log( "Error: AJAX returned ", response );
				}
			},
			error: function (ajaxContext) {
				$( '.js-wpv-cs-ajaxfail', parent_container ).show();
				console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});

		return false;
	});
	
	// Add custom conditional functions
	
	$( '.js-wpv-custom-conditional-function-add' ).on( 'click', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		shortcode_pattern = /^[a-zA-Z0-9\:\-\_]+$/,
		parent_form = thiz.closest( '.js-wpv-add-item-settings-form' ),
		parent_container = thiz.closest( '.js-wpv-add-item-settings-wrapper' ),
		newshortcode = $( '.js-wpv-add-item-settings-form-newname', parent_form ),
		sanitized_val = newshortcode.val().replace( '::', '-_paamayim_-' ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">');
		$( '.js-wpv-cs-error, .js-wpv-cs-dup, .js-wpv-cs-ajaxfail', parent_form ).hide();
		if ( shortcode_pattern.test( newshortcode.val() ) == false ) {
			$( '.js-wpv-cs-error', parent_form ).show();
		} else if ( $( '.js-' + sanitized_val + '-item', parent_container ).length > 0 ) {
			$( '.js-wpv-cs-dup', parent_form ).show();
		} else {
			spinnerContainer.insertAfter( thiz ).show();
			thiz
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
			var data = {
				action: 'wpv_update_custom_conditional_functions',
				csaction: 'add',
				cstarget: newshortcode.val(),
				wpnonce: $( '#wpv_custom_conditional_functions_nonce' ).val()
			};

			$.ajax({
				async: false,
				dataType: "json",
				type: "POST",
				url: ajaxurl,
				data: data,
				success:function( response ) {
					if ( response.success ) {
						$( '.js-wpv-add-item-settings-list', parent_container )
							.append('<li class="js-' + sanitized_val + '-item"><span class="">' + newshortcode.val() + '</span> <i class="icon-remove-sign fa fa-times-circle js-wpv-custom-function-delete" data-target="' + sanitized_val + '"></i></li>');
						newshortcode.val('');
					} else {
						$('.js-wpv-cs-ajaxfail', parent_form).show();
						console.log( "Error: AJAX returned ", response );
					}
				},
				error: function (ajaxContext) {
					$('.js-wpv-cs-ajaxfail', parent_form).show();
					console.log( "Error: ", ajaxContext.responseText );
				},
				complete: function() {
					spinnerContainer.remove();
				}
			});
		}
		return false;
	});
	
	// Delete custom conditional functions
	
	$( document ).on( 'click', '.js-wpv-custom-function-delete', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		thiz_target = thiz.data( 'target' ),
		parent_container = thiz.closest( '.js-wpv-add-item-settings-wrapper' ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( $( '.js-wpv-custom-conditional-function-add' ) ).show();
		var data = {
			action: 'wpv_update_custom_conditional_functions',
			csaction: 'delete',
			cstarget: thiz_target,
			wpnonce: $( '#wpv_custom_conditional_functions_nonce' ).val()
		};

		$.ajax({
			async: false,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					$( 'li.js-' + thiz_target + '-item', parent_container )
						.addClass( 'remove' )
						.fadeOut( 'fast', function() { 
							$( this ).remove(); 
						});
				} else {
					$('.js-wpv-cs-ajaxfail', parent_container).show();
					console.log( "Error: AJAX returned ", response );
				}
			},
			error: function (ajaxContext) {
				$('.js-wpv-cs-ajaxfail', parent_container).show();
				console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});

		return false;
	});
	
	/**
	* --------------------
	* Map plugin
	* --------------------
	*/
	
	self.map_plugin_state = ( $( '.js-wpv-map-plugin' ).length > 0 ) ? $( '.js-wpv-map-plugin' ).prop( 'checked' ) : false;
	
	$( '.js-wpv-map-plugin' ).on( 'change', function( e ){
		if ( self.map_plugin_state == $('.js-wpv-map-plugin').prop('checked') ) {
			$( '.js-wpv-map-plugin-settings-save' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		} else {
			$( '.js-wpv-map-plugin-settings-save' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		}
	});
	
	//Save Map plugin status
	$( '.js-wpv-map-plugin-settings-save' ).on( 'click', function( e ){
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_update_map_plugin_status',
			status: $( '.js-wpv-map-plugin' ).prop( 'checked' ),
			wpnonce: $('#wpv_map_plugin_nonce').val()
		};

		$.ajax({
			async: false,
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					self.map_plugin_state = $( '.js-wpv-map-plugin' ).prop( 'checked' );
					thiz
						.removeClass( 'button-primary' )
						.addClass( 'button-secondary' )
						.prop( 'disabled', true );
					thiz_messages_container
						.wpvToolsetMessage({
							text: wpv_settings_texts.setting_saved,
							type: 'success',
							inline: true,
							stay: false
						});
				}
				else {
					//console.log( "Error: AJAX returned ", response );
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
	
	/**
	* --------------------
	* Toolset Admin Bar Menu
	* --------------------
	*/
	
	self.toolset_admin_bar_menu_state = ( $( '#js-wpv-toolset-admin-bar-menu' ).length > 0 ) ? $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' ) : false;
	
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
	
	$( '.js-wpv-toolset-admin-bar-menu-settings-save' ).on( 'click', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_update_toolset_admin_bar_menu_status',
			status: $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' ),
			wpnonce: $('#wpv_toolset_admin_bar_menu_nonce').val()
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
							text: wpv_settings_texts.setting_saved,
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
	
	/**
	* --------------------
	* CodeMirror
	* --------------------
	*/
	
	self.codemirror_autoresize_state = ( $( '#js-wpv-codemirror-autoresize' ).length > 0 ) ? $( '#js-wpv-codemirror-autoresize' ).prop( 'checked' ) : false;
	
	$( '#js-wpv-codemirror-autoresize' ).on( 'change', function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_save_button = thiz_container.find( '.js-wpv-codemirror-settings-save' );
		if ( thiz.prop( 'checked' ) == self.codemirror_autoresize_state ) {
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
	
	$( '.js-wpv-codemirror-settings-save' ).on( 'click', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_update_codemirror_status',
			autoresize: $( '#js-wpv-codemirror-autoresize' ).prop( 'checked' ),
			wpnonce: $('#wpv_codemirror_options_nonce').val()
		};
		$.ajax({
            async: false,
            type: "POST",
			dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
				if ( response.success ) {
					self.codemirror_autoresize_state = $( '#js-wpv-codemirror-autoresize' ).prop( 'checked' );
					thiz
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
					thiz_messages_container
						.wpvToolsetMessage({
							text: wpv_settings_texts.setting_saved,
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
	
	/**
	* --------------------
	* Bootstrap
	* --------------------
	*/
	
	self.bootstrap_version_state = ( $('.js-wpv-bootstrap-version:checked').length > 0 ) ? $('.js-wpv-bootstrap-version:checked').val() : false;
	
	$( '.js-wpv-bootstrap-version' ).on( 'change', function( e ) {
		if ( self.bootstrap_version_state == $( '.js-wpv-bootstrap-version:checked' ).val() ) {
			$( '.js-wpv-bootstrap-version-settings-save' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		} else {
			$( '.js-wpv-bootstrap-version-settings-save' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		}
	});
	
	$( '.js-wpv-bootstrap-version-settings-save' ).on( 'click', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_update_bootstrap_version_status',
			status: $('.js-wpv-bootstrap-version:checked').val(),
			wpnonce: $('#wpv_bootstrap_version_nonce').val()
		};

		$.ajax({
			async: false,
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					self.bootstrap_version_state = $('.js-wpv-bootstrap-version:checked').val();
					thiz
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
					thiz_messages_container
						.wpvToolsetMessage({
							text: wpv_settings_texts.setting_saved,
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
	
	/**
	* --------------------
	* WPML
	* --------------------
	*/
	
	self.wpml_translation_settings = ( $( '.js-wpv-content-template-translation:checked' ).length > 0 ) ? $( '.js-wpv-content-template-translation:checked' ).val() : 0;
	
	$( document ).on( 'change', '.js-wpv-content-template-translation', function() {
		if ( self.wpml_translation_settings == $( '.js-wpv-content-template-translation:checked' ).val() ) {
			$( '.js-wpv-save-wpml-settings' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		} else {
			$( '.js-wpv-save-wpml-settings' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		}
	});

    $( '.js-wpv-save-wpml-settings' ).on( 'click', function( e ) {
        e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_update_wpml_settings',
			status: $( '.js-wpv-content-template-translation:checked' ).val(),
			wpnonce: $('#wpv_wpml_settings_nonce').val()
		};

        $.ajax({
            async: false,
            type: "POST",
			dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
					self.wpml_translation_settings = $( '.js-wpv-content-template-translation:checked' ).val();
					thiz
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
					thiz_messages_container
						.wpvToolsetMessage({
							text: wpv_settings_texts.setting_saved,
							type: 'success',
							inline: true,
							stay: false
						});
                }
            },
            error: function ( ajaxContext ) {
             //   console.log( "Error: ", ajaxContext.responseText );
            },
            complete: function() {
                spinnerContainer.hide();
            }
        });

        return false;
    });
	
	/**
	* --------------------
	* Frontend edit links
	* --------------------
	*/
	
	self.show_edit_view_link_state = ( $( '.js-wpv-show-edit-view-link' ).length > 0 ) ? $( '.js-wpv-show-edit-view-link' ).prop( 'checked' ) : false;
	
	$( '.js-wpv-show-edit-view-link' ).on( 'change', function( e ) {
		if ( self.show_edit_view_link_state == $( '.js-wpv-show-edit-view-link' ).prop( 'checked' ) ) {
			$( '.js-wpv-show-edit-view-link-settings-save' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		} else {
			$( '.js-wpv-show-edit-view-link-settings-save' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		}
	});
	
	$( '.js-wpv-show-edit-view-link-settings-save' ).on( 'click', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show();
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_update_show_edit_view_link_status',
			status: $( '.js-wpv-show-edit-view-link' ).prop( 'checked' ),
			wpnonce: $('#wpv_show_edit_view_link_nonce').val()
		};

		$.ajax({
			async: false,
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					self.show_edit_view_link_state = $( '.js-wpv-show-edit-view-link' ).prop( 'checked' );
					thiz
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
					thiz_messages_container
						.wpvToolsetMessage({
							text: wpv_settings_texts.setting_saved,
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
		return false;
	});
	
	/**
	* --------------------
	* Theme debug
	* --------------------
	*/
	
	self.theme_debug_settings = $( '.js-debug-settings-form :input' ).serialize();
	
	$( document ).on( 'change cut click paste keyup', '.js-debug-settings-form :input', function() {
		if ( self.theme_debug_settings == $( '.js-debug-settings-form :input' ).serialize() ) {
			$( '.js-wpv-save-theme-debug-settings' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		} else {
			$( '.js-wpv-save-theme-debug-settings' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		}
	});
	
    $( '.js-wpv-save-theme-debug-settings' ).on( 'click', function( e ) {
        e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_save_theme_debug_settings',
			wpv_theme_function: $( '.js-wpv-debug-theme-function' ).val(),
			wpv_theme_function_debug: $( '.js-wpv-debug-theme-function-enable-debug' ).prop( 'checked' ),
			wpnonce: $('#wpv_view_templates_theme_support').val()
		};

        $.ajax({
            async: false,
            type: "POST",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
                    self.theme_debug_settings = $('.js-debug-settings-form :input').serialize();
					thiz
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
					thiz_messages_container
						.wpvToolsetMessage({
							text: wpv_settings_texts.setting_saved,
							type: 'success',
							inline: true,
							stay: false
						});
                }
            },
            error: function ( ajaxContext ) {
				//console.log( "Error: ", ajaxContext.responseText );
            },
            complete: function() {
				spinnerContainer.remove();
            }
        });

        return false;
    });
	
	/**
	* --------------------
	* Debug
	* --------------------
	*/
	
	self.debug_mode_state = $('.js-debug-mode-form input').serialize();
	
	$( '.js-wpv-debug-mode, .js-wpv-debug-mode-type' ).on( 'change', function( e ) {
		if ( $( '.js-wpv-debug-mode' ).prop( 'checked' ) ) {
			$( '.js-wpv-debug-additional-options' ).fadeIn( 'fast' );
		} else {
			$( '.js-wpv-debug-additional-options' ).hide();
		}
		if ( self.debug_mode_state == $('.js-debug-mode-form input').serialize() ) {
			$( '.js-wpv-save-debug-mode-settings' )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
			$( '.js-wpv-debug-checker' ).show();
		} else {
			$( '.js-wpv-save-debug-mode-settings' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
			$( '.js-wpv-debug-checker' ).hide();
		}
	});
	
	$( '.js-wpv-save-debug-mode-settings' ).on( 'click', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		thiz_container = thiz.closest( '.js-wpv-setting-container' ),
		thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
		data = {
			action: 'wpv_update_debug_mode_status',
			debug_status: ( $( '.js-wpv-debug-mode' ).prop( 'checked' ) ) ? 1 : 0,
			debug_mode_type: $( 'input[name=wpv_debug_mode_type]:radio:checked' ).val(),
			wpnonce: $('#wpv_debug_tool_nonce').val()
		};
		$( '.js-debug-mode-update-message' ).hide();

		$.ajax({
			async: false,
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					self.debug_mode_state = $('.js-debug-mode-form input').serialize();
					thiz
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
					thiz_messages_container
						.wpvToolsetMessage({
							text: wpv_settings_texts.setting_saved,
							type: 'success',
							inline: true,
							stay: false
						});
					if ( $( '.js-wpv-debug-mode' ).prop( 'checked' ) ) {
						$( '.js-wpv-debug-checker' ).show();
						if ( ! $( '.js-wpv-debug-checker-enabler' ).is( ':visible' ) ) {
							$( '.js-wpv-debug-checker-before, .js-wpv-debug-checker-actions' ).show();
							$( '.js-wpv-debug-checker-results' ).hide();
						}
					}
				}
			},
			error: function (ajaxContext) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});
		return false;
	});
	
	$( document ).on( 'click', '.js-wpv-debug-checker-action', function() {
		var target = $( this ).data( 'target' );
		window.location = target;
	});
	
	$( document ).on( 'click', '.js-wpv-debug-checker-dismiss', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertAfter( thiz ).show(),
		data = {
			action: 'wpv_switch_debug_check',
			result: 'dismiss',
			wpnonce: $('#wpv_debug_tool_nonce').val()
		};
		$.ajax({
            async: false,
            type: "POST",
			dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
					$( '.js-wpv-debug-checker-results, .js-wpv-debug-checker-after, .js-wpv-debug-checker-before, .js-wpv-debug-checker-actions' ).hide();
					$( '.js-wpv-debug-checker-enabler' ).show();
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
	
	$( document ).on( 'click', '.js-wpv-debug-checker-recover', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertAfter( thiz ).show(),
		data = {
			action: 'wpv_switch_debug_check',
			result: 'recover',
			wpnonce: $('#wpv_debug_tool_nonce').val()
		};
		$.ajax({
            async: false,
            type: "POST",
			dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
					$( '.js-wpv-debug-checker-results, .js-wpv-debug-checker-after, .js-wpv-debug-checker-enabler' ).hide();
					$( '.js-wpv-debug-checker-before, .js-wpv-debug-checker-actions' ).show();
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
	
	$( document ).on( 'click', '.js-wpv-debug-checker-success', function( e ) {
		e.preventDefault();
		$( '.js-wpv-debug-checker-results' ).addClass( 'hidden' );
		$( '.js-wpv-debug-checker-message-success' ).fadeIn( 'fast', function() {
			$( '.js-wpv-debug-checker-dismiss' ).click();
		});
	});
	
	$( document ).on( 'click', '.js-wpv-debug-checker-failure', function( e ) {
		e.preventDefault();
		$( '.js-wpv-debug-checker-results' ).addClass( 'hidden' );
		$( '.js-wpv-debug-checker-message-failure' ).fadeIn( 'fast' );
		$( '.js-wpv-debug-checker-actions' ).fadeIn( 'fast' );
	});
	
	self.init = function() {
		
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.views_settings_screen = new WPViews.ViewsSettingsScreen( $ );
    if( /#toolset-admin-bar-settings$/.test( window.location.href ) ) {
        $( '#toolset-admin-bar-settings' ).parent().css( 'background-color', '#ffffca' );
    }
});