var WPViews = WPViews || {};

WPViews.ViewEditScreenPaginationUX = function( $ ) {
	
	var self = this;
	self.pagination_pointer = null;
	self.pagination_insert_newline = false;
	self.codemirror_highlight_options = {
		className: 'wpv-codemirror-highlight'
	};
	
	self.pagination_dialog = null;
	
	// ---------------------------------
	// Dialogs
	// ---------------------------------
	
	self.init_dialogs = function() {
		var dialog_height = $( window ).height() - 100;
		self.pagination_dialog = $( "#js-hidden-messages-boxes-pointers-container .js-wpv-pagination-form-dialog" ).dialog({
			autoOpen: false,
			modal: true,
			title: wpv_pagination_texts.add_pagination_dialog_title,
			minWidth: 720,
			maxHeight: dialog_height,
			draggable: false,
			resizable: false,
			position: { my: "center top+50", at: "center top", of: window },
			show: { 
				effect: "blind", 
				duration: 800 
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-wpv-insert-pagination' )
					.prop( 'disabled', true )
					.removeClass( 'button-primary' )
					.addClass( 'button-secondary' );
				$( 'input.js-wpv-pagination-dialog-display, input.js-wpv-pagination-dialog-control' ).prop( 'checked', false );
				$( '.js-wpv-dialog-pagination-wizard-item-extra' ).hide();
				$( '.js-wpv-dialog-pagination-wizard-item-extra-nav-links' ).val( '' );
				$( '.js-wpv-dialog-pagination-wizard-preview' ).addClass( 'disabled' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary',
					text: wpv_pagination_texts.add_pagination_dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'button-primary js-wpv-insert-pagination',
					text: wpv_pagination_texts.add_pagination_dialog_insert,
					click: function() {

					}
				}
			]
		});
		self.pagination_infinite_dialog = $( "#js-hidden-messages-boxes-pointers-container .js-wpv-pagination-infinite-form-dialog" ).dialog({
			autoOpen: false,
			modal: true,
			title: wpv_pagination_texts.add_pagination_dialog_title,
			minWidth: 720,
			maxHeight: dialog_height,
			draggable: false,
			resizable: false,
			position: { my: "center top+50", at: "center top", of: window },
			show: { 
				effect: "blind", 
				duration: 800 
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-wpv-insert-pagination-infinite' )
					.prop( 'disabled', true )
					.removeClass( 'button-primary' )
					.addClass( 'button-secondary' );
				$( 'input.js-wpv-pagination-infinite-dialog-control' ).prop( 'checked', false );
				$( '.js-wpv-dialog-pagination-wizard-preview' ).addClass( 'disabled' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-primary',
					text: wpv_pagination_texts.add_pagination_dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}/*,
				{
					class: 'button-primary js-wpv-insert-pagination-infinite',
					text: wpv_pagination_texts.add_pagination_dialog_insert,
					click: function() {

					}
				}*/
			]
		});
	};
	
	// ---------------------------------
	// Functions
	// ---------------------------------
	
	self.pagination_insert_pointer = function() {
		self.pagination_pointer.pointer('close');
		if ( ! $( '.js-wpv-enabled-view-pagination-pointer' ).hasClass( 'js-wpv-pointer-dismissed' ) ) {
			var filter_html = codemirror_views_query.getValue();
			if (
				$( '.js-wpv-pagination-mode:checked' ).val() != 'none'
				&& filter_html.search('wpv-pager-current-page') == -1
				&& filter_html.search('wpv-pager-prev-page') == -1
				&& filter_html.search('wpv-pager-next-page') == -1
			) {
				self.pagination_pointer.pointer('open');
				self.pagination_pointer.pointer('reposition');
			}
		}
	};

	self.get_pagination_shortcode = function() {
		var output = '';
		$.each( $( 'input.js-wpv-pagination-dialog-control:checked' ), function() {
			var thiz = $( this ),
			value = thiz.val();
			switch ( value ) {
				case 'page_num':
					output += '[wpv-pager-current-page]';
					break;
				case 'page_controls':
					output += '[wpv-pager-prev-page][wpml-string context="wpv-views"]Previous[/wpml-string][/wpv-pager-prev-page][wpv-pager-next-page][wpml-string context="wpv-views"]Next[/wpml-string][/wpv-pager-next-page]';
					break;
				case 'page_nav_dropdown':
					output += '[wpv-pager-nav-dropdown]';
					break;
				case 'page_nav_links':
					output += '[wpv-pager-nav-links';
					$( '.js-wpv-dialog-pagination-wizard-item-extra-nav-links' ).each( function() {
						var thiz = $( this ),
						thiz_val = thiz.val(),
						thiz_attr = thiz.data( 'attr' );
						if ( thiz_val != '' ) {
							output += ' ' + thiz_attr + '="' + thiz_val + '"';
						}
					});
					output += ']';
					break;
				case 'page_nav_dots':
					output += '[wpv-pager-nav-links ul_class="wpv_pagination_dots" li_class="wpv_pagination_dots_item" current_type="link"]';
					break;
				case 'page_total':
					output += '[wpv-pager-total-pages]';
					break;
			}
		});
		return output;
	};
	
	self.get_pagination_infinite_shortcode = function() {
		var output = '';
		if ( $( '.js-wpv-pagination-infinite-dialog-control:checked' ) ) {
			output += '[wpv-pager-next-page][wpml-string context="wpv-views"]Load more[/wpml-string][/wpv-pager-next-page]';
		}
		return output;
	};
	
	// ---------------------------------
	// Events
	// ---------------------------------
	
	$( document ).on( 'change', '.js-wpv-pagination-mode', function() {
		self.pagination_insert_pointer();
	});
	
	// Insert pagination shortcode
	
	$( document ).on( 'click', '.js-wpv-pagination-popup', function() {
		var thiz = $( this ),
		active_textarea = thiz.data( 'content' ),
		current_cursor,
		text_before,
		text_after,
		insert_position;
		window.wpcfActiveEditor = active_textarea;
		self.pagination_pointer.pointer('close');
		if ( active_textarea == 'wpv_filter_meta_html_content' ) {
			current_cursor = codemirror_views_query.getCursor(true);
			text_before = codemirror_views_query.getRange({line:0,ch:0}, current_cursor);
			text_after = codemirror_views_query.getRange(current_cursor, {line:codemirror_views_query.lastLine(),ch:null});
			if ( 
				text_before.search(/\[wpv-filter-start.*?\]/g) == -1 
				|| text_after.search(/\[wpv-filter-end.*?\]/g) == -1 
			) {
				// Set the cursor at the end and open popup
				insert_position = codemirror_views_query.getSearchCursor( '[wpv-filter-end]', false );
				insert_position.findNext();
				codemirror_views_query.setSelection( insert_position.from(), insert_position.from() );
				self.pagination_insert_newline = true;
			}
		}
		if ( active_textarea == 'wpv_layout_meta_html_content' ) {
			current_cursor = codemirror_views_layout.getCursor(true);
			text_before = codemirror_views_layout.getRange({line:0,ch:0}, current_cursor);
			text_after = codemirror_views_layout.getRange(current_cursor, {line:codemirror_views_layout.lastLine(),ch:null});
			if ( 
				text_before.search(/\[wpv-layout-start.*?\]/g) == -1 
				|| text_after.search(/\[wpv-layout-end.*?\]/g) == -1 
			) {
				// Set the cursor at the end and open popup
				insert_position = codemirror_views_layout.getSearchCursor( '[wpv-layout-end]', false );
				insert_position.findNext();
				codemirror_views_layout.setSelection( insert_position.from(), insert_position.from() );
				self.pagination_insert_newline = true;
			}
		}
		if (
			$( '#wpv-settings-manual-pagination' ).prop( 'checked' ) 
			&& $( '#wpv-settings-ajax-pagination-enabled' ).prop( 'checked' ) 
			&& $( '#wpv-settings-ajax-pagination-effect' ).val() == 'infinite'
		) {
			self.pagination_infinite_dialog.dialog( 'open' );
		} else {
			self.pagination_dialog.dialog( 'open' );
		}
	});
	
	$( document ).on( 'change', 'input.js-wpv-pagination-dialog-control', function() {
		var thiz = $( this ),
		thiz_checked = thiz.prop( 'checked' ),
		thiz_container = thiz.closest( '.js-wpv-dialog-pagination-wizard-item' ),
		thiz_preview = thiz_container.find( '.js-wpv-dialog-pagination-wizard-preview' ),
		thiz_extra = thiz_container.find( '.js-wpv-dialog-pagination-wizard-item-extra' ),
		options_checked = $( '.js-wpv-pagination-dialog-control:checked' );
		preview_elements = $( '.js-wpv-pagination-preview-element' );
		if ( options_checked.length > 0 ) {
			$( '.js-wpv-insert-pagination' )
				.prop( 'disabled', false )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' );
		} else {
			$( '.js-wpv-insert-pagination' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		}
		if ( thiz_checked ) {
			thiz_preview.removeClass( 'disabled' );
			thiz_extra.slideDown();
		} else {
			thiz_preview.addClass( 'disabled' );
			thiz_extra.slideUp();
		}
	});
	
	$( document ).on( 'change', '.js-wpv-pagination-infinite-dialog-control', function() {
		var thiz = $( this ),
		thiz_checked = thiz.prop( 'checked' ),
		thiz_button = $( '.js-wpv-insert-pagination-infinite' );
		if ( thiz_checked ) {
			thiz_button
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		} else {
			thiz_button
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
		}
	});
	
	$( document ).on( 'click', '.js-wpv-insert-pagination', function() {
		var shortcode = '',
		wrap = $( 'input.js-wpv-pagination-dialog-display' ).prop('checked'),
		current_cursor,
		end_cursor,
		pagination_marker;
		shortcode = self.get_pagination_shortcode();
		if ( wrap ) {
			shortcode = '[wpv-pagination]' +  shortcode + '[/wpv-pagination]';
		}
		if ( self.pagination_insert_newline ) {
			shortcode += '\n';
			self.pagination_insert_newline = false;
		}
		if ( window.wpcfActiveEditor == 'wpv_filter_meta_html_content' ) {
			current_cursor = codemirror_views_query.getCursor( true );
			codemirror_views_query.setSelection( current_cursor, current_cursor );
			codemirror_views_query.replaceSelection( shortcode, 'end' );
			end_cursor = codemirror_views_query.getCursor( true );
			pagination_marker = codemirror_views_query.markText( current_cursor, end_cursor, self.codemirror_highlight_options );
			self.pagination_dialog.dialog( 'close' );
			codemirror_views_query.focus();
			setTimeout( function() {
				  pagination_marker.clear();
			}, 2000);
		}
		if ( window.wpcfActiveEditor == 'wpv_layout_meta_html_content' ) {
			current_cursor = codemirror_views_layout.getCursor( true );
			codemirror_views_layout.setSelection( current_cursor, current_cursor );
			codemirror_views_layout.replaceSelection( shortcode, 'end' );
			end_cursor = codemirror_views_layout.getCursor( true );
			pagination_marker = codemirror_views_layout.markText( current_cursor, end_cursor, self.codemirror_highlight_options );
			self.pagination_dialog.dialog( 'close' );
			codemirror_views_layout.focus();
			setTimeout( function() {
				  pagination_marker.clear();
			}, 2000);
		}
	});
	
	$( document ).on( 'click', '.js-wpv-insert-pagination-infinite', function() {
		var shortcode = '',
		current_cursor,
		end_cursor,
		pagination_marker;
		shortcode = self.get_pagination_infinite_shortcode();
		if ( self.pagination_insert_newline ) {
			shortcode += '\n';
			self.pagination_insert_newline = false;
		}
		if ( window.wpcfActiveEditor == 'wpv_filter_meta_html_content' ) {
			current_cursor = codemirror_views_query.getCursor( true );
			codemirror_views_query.setSelection( current_cursor, current_cursor );
			codemirror_views_query.replaceSelection( shortcode, 'end' );
			end_cursor = codemirror_views_query.getCursor( true );
			pagination_marker = codemirror_views_query.markText( current_cursor, end_cursor, self.codemirror_highlight_options );
			self.pagination_infinite_dialog.dialog( 'close' );
			codemirror_views_query.focus();
			setTimeout( function() {
				  pagination_marker.clear();
			}, 2000);
		}
		if ( window.wpcfActiveEditor == 'wpv_layout_meta_html_content' ) {
			current_cursor = codemirror_views_layout.getCursor( true );
			codemirror_views_layout.setSelection( current_cursor, current_cursor );
			codemirror_views_layout.replaceSelection( shortcode, 'end' );
			end_cursor = codemirror_views_layout.getCursor( true );
			pagination_marker = codemirror_views_layout.markText( current_cursor, end_cursor, self.codemirror_highlight_options );
			self.pagination_infinite_dialog.dialog( 'close' );
			codemirror_views_layout.focus();
			setTimeout( function() {
				  pagination_marker.clear();
			}, 2000);
		}
	});
	
	// Helper
	
	$( document ).on( 'click','.js-disable-events, .js-wpv-disable-events', function( e ) {
		e.preventDefault();
		return false;
	});
	
	// ---------------------------------
	// Init
	// ---------------------------------
	
	self.init = function() {
		self.pagination_pointer = $('.filter-html-editor .js-wpv-pagination-popup').first().pointer({
			pointerClass: 'wp-toolset-pointer wp-toolset-views-pointer',
			pointerWidth: 400,
			content: $( '.js-wpv-enabled-view-pagination-pointer' ).html(),
			position: {
				edge: 'bottom',
				align: 'left'
			},
			buttons: function( event, t ) {
				var button_close = $('<button class="button button-primary-toolset alignright js-wpv-close-this">' + wpv_pagination_texts.close + '</button>');
				button_close.bind( 'click.pointer', function( e ) {
					e.preventDefault();
					if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
						var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
						$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
					}
					t.element.pointer('close');
					codemirror_views_query.focus();
				});
				return button_close;
			}
		});
		// Init pagination insert pointer
		// Ugly solution, but otherwise we can not be sure it will be added to the right position
		// due to some sections being shown/hidden on document.ready too, after this one
		setTimeout( function() {
			self.pagination_insert_pointer();
		}, 3000);
		self.init_dialogs();
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.view_edit_screen_pagination_ux = new WPViews.ViewEditScreenPaginationUX( $ );
});