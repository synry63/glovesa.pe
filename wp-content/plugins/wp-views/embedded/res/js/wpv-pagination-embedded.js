var WPViews = WPViews || {};

var wpv_stop_rollover = {};
window.wpvPaginationAjaxLoaded = {};
window.wpvPaginationAnimationFinished = {};
window.wpvPaginationQueue = {};

// ------------------------------------
// Clone
// ------------------------------------

// Textarea and select clone() bug workaround | Spencer Tipping
// Licensed under the terms of the MIT source code license
// Motivation.
// jQuery's clone() method works in most cases, but it fails to copy the value of textareas and select elements. This patch replaces jQuery's clone() method with a wrapper that fills in the
// values after the fact.
// An interesting error case submitted by Piotr Przybyl: If two <select> options had the same value, the clone() method would select the wrong one in the cloned box. The fix, suggested by Piotr
// and implemented here, is to use the selectedIndex property on the <select> box itself rather than relying on jQuery's value-based val().
;( function() {
    jQuery.fn.wpv_clone = function() {
        var result = jQuery.fn.clone.apply( this, arguments ),
		my_textareas = this.find( 'textarea' ).add( this.filter( 'textarea' ) ),
		result_textareas = result.find( 'textarea' ).add( result.filter( 'textarea' ) ),
		my_selects = this.find( 'select' ).add( this.filter( 'select' ) ),
		result_selects = result.find( 'select' ).add( result.filter( 'select' ) );
		for ( var i = 0, l = my_textareas.length; i < l; ++i ) {
			jQuery( result_textareas[i] ).val( jQuery( my_textareas[i] ).val() );
		}
		for ( var i = 0, l = my_selects.length; i < l; ++i ) {
			for ( var j = 0, m = my_selects[i].options.length; j < m; ++j ) {
				if ( my_selects[i].options[j].selected === true ) {
					result_selects[i].options[j].selected = true;
				} else {
					result_selects[i].options[j].selected = false;
				}
			}
		}
        return result;
    };
})();

// ------------------------------------
// Rollover
// ------------------------------------

jQuery.fn.wpvRollover = function( data ) {
	var args = jQuery.extend( {}, {id: 1, effect: "fade", speed: 5, page: 1, count: 1}, data ),
	id = args.id,
	effect = args.effect,
	speed = args.speed*1000,
	page = args.page,
	count = args.count,
	wpvInfiniteLoop;
	if ( count > 1 ) {
		if ( 
			window.wpvPaginationAjaxLoaded.hasOwnProperty( id ) 
			&& window.wpvPaginationAjaxLoaded[id] === false 
		) {
			setTimeout( function() {
					jQuery( this ).wpvRollover( {
						id:id,
						effect:effect,
						speed:speed/1000,
						page:page,
						count:count,
					} );
				}, 
				100 );
			return false;
		}
		window.wpvPaginationAjaxLoaded[id] = false;
		wpvInfiniteLoop = setTimeout( function() {
			if ( effect === 'slideright' || effect === 'slidedown' ) {
				if ( page <= 1 ) {
					page = count;
				} else {
					page--;
				}
			} else {
				if ( page === count ) {
					page = 1;
				} else {
					page++;
				}
			}
			if ( ! wpv_stop_rollover.hasOwnProperty( id ) ) {
				WPViews.view_pagination.trigger_pagination( id, page );
				jQuery( this ).wpvRollover( {
					id:id,
					effect:effect,
					speed:speed/1000,
					page:page,
					count:count,
				} );
			}
		}, speed);
	}
};


////////////////////////////////////////////////////
// Table sorting head click
////////////////////////////////////////////////////

// TODO create a table sorting object to wrap all related code

jQuery( document ).on( 'click', '.js-wpv-column-header-click', function( e ) {
	e.preventDefault();
	var thiz = jQuery( this ),
	view_number = thiz.data( 'viewnumber' ),
	name = thiz.data( 'name' ),
	direction = thiz.data( 'direction' ),
	data = {},
	innerthis;
	jQuery( 'form[name="wpv-filter-' + view_number + '"]' ).each( function() {
		innerthis = jQuery( this );
		innerthis.find( '#wpv_column_sort_id' ).val( name );
		innerthis.find( '#wpv_column_sort_dir' ).val( direction );
		WPViews.view_frontend_utils.add_url_controls_for_column_sort( data, innerthis );
	});
	jQuery( 'form[name="wpv-filter-' + view_number + '"]' ).submit();
	return false;
});

WPViews.ViewFrontendUtils = function( $ ) {
	
	// ------------------------------------
	// Constants and variables
	// ------------------------------------
	
	var self = this;
	
	// ------------------------------------
	// Methods
	// ------------------------------------
	
	self.just_return = function() {
		return;
	};
	
	/**
	* extract_url_query_parameters
	*
	* Extracts parameters from a query string, managing arrays, and returns an array of pairs key => value
	*
	* @param string query_string
	*
	* @return array
	*
	* @note ##URLARRAYVALHACK## is a hacky constant
	*
	* @uses decodeURIComponent
	*
	* @since 1.9.0
	*/
	
	self.extract_url_query_parameters = function( query_string ) {
		var query_string_pairs = {};
		if ( query_string == "" ) {
			return query_string_pairs;
		}
		var query_string_split = query_string.split( '&' ),
		query_string_split_length = query_string_split.length;
		for ( var i = 0; i < query_string_split_length; ++i ) {
			var qs_part = query_string_split[i].split( '=' );
			if ( qs_part.length != 2 ) {
				continue;
			};
			var thiz_key = qs_part[0],
			thiz_val = decodeURIComponent( qs_part[1].replace( /\+/g, " " ) );
			// Adjust thiz_key to work with POSTed arrays
			thiz_key = thiz_key.replace( "[]", "" );
			thiz_key = thiz_key.replace( "%5B%5D", "" );
			if ( query_string_pairs.hasOwnProperty( thiz_key ) ) {
				if ( query_string_pairs[thiz_key] != thiz_val ) {
					// @hack alert!! WE can not avoid using this :-(
					query_string_pairs[thiz_key] += '##URLARRAYVALHACK##' + thiz_val;
				} else {
					query_string_pairs[thiz_key] = thiz_val;
				}
			} else {
				query_string_pairs[thiz_key] = thiz_val;
			}
		}
		return query_string_pairs;
	};
	
	/**
	* add_url_query_parameters
	*
	* Adds the current URL query parameters to the data array, on the get_params key
	*
	* @param array data
	*
	* @return array
	*
	* @uses self.extract_url_query_parameters
	*
	* @since 1.9.0
	*/

	self.add_url_query_parameters = function( data ) {
		var query_s = self.extract_url_query_parameters( window.location.search.substr( 1 ) );
		data['get_params'] = {};
		for ( var prop in query_s ) {
			if ( 
				query_s.hasOwnProperty( prop ) 
				&& ! data.hasOwnProperty( prop )
			) {
				data['get_params'][prop] = query_s[prop];
			}
		}
		return data;
	};
	
	/**
	* add_url_controls_for_column_sort
	*
	* @param object form
	*
	* @since 1.9
	*/
	
	self.add_url_controls_for_column_sort = function( data, form ) {
		data = self.add_url_query_parameters( data );
		$.each( data['get_params'], function( key, value ) {
			if ( form.find( '[name=' + key + '], [name=' + key + '\\[\\]]' ).length === 0 ) {
				// @hack alert!! WE can not avoid this :-(
				var pieces = value.split( '##URLARRAYVALHACK##' ),
				pieces_length = pieces.length;
				if ( pieces_length < 2 ) {
					$( '<input>' ).attr({
						type: 'hidden',
						name: key,
						value: value
					})
					.appendTo( form );
				} else {
					for ( var iter = 0; iter < pieces_length; iter++ ) {
						$( '<input>' ).attr({
							type: 'hidden',
							name: key + "[]",
							value: pieces[iter]
						})
						.appendTo( form );
					}
				}
			}
		});
		return data;
	};
	
	/**
	* utf8_encode
	*
	* @param string argString
	*
	* @return string
	*
	* @since 1.9.0
	*
	* @author Webtoolkit.info (http://www.webtoolkit.info/)
	* @improved Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	* @improved sowberry
	* @tweaked Jack
	* @bugfixed Onno Marsman
	* @improved Yves Sucaet
	* @bugfixed Onno Marsman
	* @bugfixed Ulrich
	* @bugfixed Rafal Kukawski
	* @improved kirilloid
	*/
	
	self.utf8_encode = function( argString ) {
		if (
			argString === null 
			|| typeof argString === "undefined"
		) {
			return "";
		}
		var string = ( argString + '' ),
		utftext = '',
		start = 0, 
		end = 0, 
		stringl = string.length;
		for ( var n = 0; n < stringl; n++ ) {
			var c1 = string.charCodeAt( n ),
			enc = null;
			if ( c1 < 128 ) {
				end++;
			} else if ( c1 > 127 && c1 < 2048 ) {
				enc = String.fromCharCode( ( c1 >> 6 ) | 192, ( c1 & 63 ) | 128 );
			} else {
				enc = String.fromCharCode( ( c1 >> 12 ) | 224, ( ( c1 >> 6 ) & 63 ) | 128, ( c1 & 63 ) | 128 );
			}
			if ( enc !== null ) {
				if ( end > start ) {
					utftext += string.slice( start, end );
				}
				utftext += enc;
				start = end = n + 1;
			}
		}
		if ( end > start ) {
			utftext += string.slice( start, stringl );
		}
		return utftext;
	};
	
	/**
	* encodeToHex
	*
	* Converts the given data structure to a JSON string.
	*
	* @param string str
	*
	* @return string
	*
	* @since 1.9.0
	*/
	
	self.encodeToHex = function( str ) {
		var r="",
		e = str.length,
		c = 0,
		h;
		while( c < e ) {
			h = str.charCodeAt( c++ ).toString( 16 );
			while( h.length < 2 ) {
				h= "0" + h;
			}
			r += h;
		}
		return r;
	};
	
	/**
	* array2json
	*
	* Converts the given data structure to a JSON string
	*
	* @param array arr
	*
	* @return string
	*
	* @since 1.9.0
	*
	* @uses self.array2json
	*
	* @url http://www.openjs.com/scripts/data/json_encode.php
	*
	* @example var json_string = array2json(['e', {pluribus: 'unum'}]);
	* @example var json = array2json({"success":"Sweet","failure":false,"empty_array":[],"numbers":[1,2,3],"info":{"name":"Binny","site":"http:\/\/www.openjs.com\/"}});
	*/
	self.array2json = function( arr ) {
		var parts = [],
		is_list = ( Object.prototype.toString.apply( arr ) === '[object Array]' );
		for( var key in arr ) {
			var value = arr[key];
			if ( typeof value == "object" ) { //Custom handling for arrays
				if ( is_list ) {
					parts.push( self.array2json( value ) ); /* :RECURSION: */
				} else {
					parts.push( '"' + key + '":' + self.array2json( value ) ); /* :RECURSION: */
				}
			} else {
				var str = "";
				if ( ! is_list ) {
					str = '"' + key + '":';
				}
				//Custom handling for multiple data types
				if ( typeof value == "number" ) {
					str += value; //Numbers
				} else if ( value === false ) {
					str += 'false'; //The booleans
				} else if ( value === true ) {
					str += 'true';
				} else {
					str += '"' + self.utf8_encode( value ) + '"'; //All other things
				}
				// :TODO: Is there any more datatype we should be in the lookout for? (Functions?)
				parts.push( str );
			}
		}
		var json = parts.join( "," );
		if ( is_list ) {
			return '[' + json + ']';//Return numerical JSON
		}
		return '{' + json + '}';//Return associative JSON
	};
	
	/**
	* serialize_array
	*
	* @param array data
	*
	* @return string
	*
	* @since 1.9.0
	*
	* @uses self.encodeToHex
	* @uses self.array2json
	*/
	
	self.serialize_array = function( data ) {
		return self.encodeToHex( self.array2json( data ) );
	};
	
	/**
	* render_frontend_datepicker
	*
	* Adds a datepicker to a selector but only if it has not been added before.
	*
	* Fired on document.ready, after AJAX pagination and after AJAX parametric search events.
	*
	* @since 1.9
	*/
	
	self.render_frontend_datepicker = function() {
		$( '.js-wpv-frontend-datepicker:not(.js-wpv-frontend-datepicker-inited)' ).each( function() {
			var thiz = $( this );
			thiz
				.addClass( 'js-wpv-frontend-datepicker-inited' )
				.datepicker({
					onSelect: function( dateText, inst ) {
						var url_param = thiz.data( 'param' ),
						data = 'date=' + dateText,
						form = thiz.closest( 'form' );
						data += '&date-format=' + $( '.js-wpv-date-param-' + url_param + '-format' ).val();
						data += '&action=wpv_format_date';
						$.post( wpv_pagination_local.front_ajaxurl, data, function( response ) {
							response = $.parseJSON( response );
							form.find('.js-wpv-date-param-' + url_param ).html( response['display'] );
							form.find('.js-wpv-date-front-end-clear-' + url_param ).show();
							form.find('.js-wpv-date-param-' + url_param + '-value' ).val( response['timestamp'] ).trigger( 'change' );
						});
					},
					dateFormat: 'ddmmyy',
					minDate: wpv_pagination_local.datepicker_min_date,
					maxDate: wpv_pagination_local.datepicker_max_date,
					showOn: "button",
					buttonImage: wpv_pagination_local.calendar_image,
					buttonText: wpv_pagination_local.calendar_text,
					buttonImageOnly: true,
					changeMonth: true,
					changeYear: true
				});
		});
	};
	
	/**
	* clone_form
	*
	* Clones a form using the fixed clone() method that covers select and textarea elements
	*
	* @param object fil
	* @param array targets
	*
	* @since 1.9
	*/
	
	self.clone_form = function( fil, targets ) {
		var cloned = fil.wpv_clone();
		targets.each( function() {
			$( this ).replaceWith( cloned );
		});
	};
	
	/**
	* render_frontend_media_shortcodes
	*
	* Render the WordPress media players for items inside a container.
	*
	* @param object container
	*
	* @since 1.9
	*/
	
	self.render_frontend_media_shortcodes = function( container ) {
		container.find( '.wp-audio-shortcode, .wp-video-shortcode' ).each( function() {
			var thiz = $( this );
			thiz.mediaelementplayer();
		});
		container.find( '.wp-playlist' ).each( function() {
			var thiz = $( this );
			return new WPPlaylistView({ el: this });
		});
	};
	
	// ------------------------------------
	// Events
	// ------------------------------------
	
	/**
	* Window resize event
	*
	* Make Views layouts responsive
	*
	* @since 1.9
	* @since 1.11 added debounce
	*/
	
	$( window ).on( 'resize', _.debounce(
		function() {
			$( '.js-wpv-layout-responsive' ).each( function() {
				$( this ).css( 'width', $( this ).parent().width() );
			});
		},
		1000
	));
	
	// ------------------------------------
	// Init
	// ------------------------------------
	
	self.init = function() {
		self.render_frontend_datepicker();
	};
	
	self.init();

};

WPViews.ViewPagination = function( $ ) {
	
	// ------------------------------------
	// Constants and variables
	// ------------------------------------
	
	var self = this;
	
	self.did_stop_rollover = {};
	self.pagination_queue = {};
	
	self.pagination_effects = {};
	self.pagination_effects_conditions = {};
	self.pagination_effects_spinner = {};
	self.paged_views = {};
	self.paged_views_initial_page = {};
	
	self.last_paginated_view = [];
	self.paginated_history_reach = 0;
	self.add_paginated_history = true;
	self.pagination_effect_state_push = [ 'fade', 'slidev', 'slideh' ];
	self.pagination_effect_state_replace = [];
	self.pagination_effect_state_keep = [ 'infinite' ];
		
	self.slide_data_defaults = { 
		view_number:		'',
		page:				0,
		max_pages:			0,
		speed:				500,
		next:				true,
		effect:				'fade',
		response:			null,
		wpvPaginatorFilter: null,
		wpvPaginatorLayout: null,
		responseFilter:		null,
		responseView:		null,
		callback_next_func:	WPViews.view_frontend_utils.just_return
	};
		
	// ------------------------------------
	// Methods
	// ------------------------------------
	
	/**
	* get_ajax_pagination_url
	*
	* Build the pagination URL to get the given page based on data
	*
	* @param object data
	*
	* @since 1.9
	*/
	
	self.get_ajax_pagination_url = function( data ) {
		var url;
		if ( wpv_pagination_local.ajax_pagination_url.slice( -'.php'.length ) === '.php' ) {
			url = wpv_pagination_local.ajax_pagination_url + '?wpv-ajax-pagination=' + WPViews.view_frontend_utils.serialize_array( data );
		} else {
			url = wpv_pagination_local.ajax_pagination_url + WPViews.view_frontend_utils.serialize_array( data );
		}
		return url;
	};
	
	/**
	* add_view_parameters
	*
	* Add several information to the data used to get pagination pages.
	* For example, add column sorting data, parametric search data and parent View data.
	*
	* @since 1.9
	*/
	
	self.add_view_parameters = function( data, page, view_number ) {
		data['action'] = 'wpv_get_page';
		data['page'] = page;
		data['view_number'] = view_number;
		var this_form = $( 'form.js-wpv-filter-form-' + view_number );
		data['wpv_column_sort_id'] = this_form.find( 'input[name=wpv_column_sort_id]' ).val();
		data['wpv_column_sort_dir'] = this_form.find( 'input[name=wpv_column_sort_dir]' ).val();
		data['wpv_view_widget_id'] = this_form.data( 'viewwidgetid' );
		data['view_hash'] = this_form.data( 'viewhash' );
		if ( this_form.find( 'input[name=wpv_post_id]' ).length > 0 ) {
			data['post_id'] = this_form.find( 'input[name=wpv_post_id]' ).val();
		}
		if ( this_form.find( 'input[name=wpv_aux_parent_term_id]' ).length > 0 ) {
			data['wpv_aux_parent_term_id'] = this_form.find( 'input[name=wpv_aux_parent_term_id]' ).val();
		}
		if ( this_form.find( 'input[name=wpv_aux_parent_user_id]' ).length > 0 ) {
			data['wpv_aux_parent_user_id'] = this_form.find( 'input[name=wpv_aux_parent_user_id]' ).val();
		}
		data['dps_pr'] = {};
		data['dps_general'] = {};
		var this_prelements = this_form.find( '.js-wpv-post-relationship-update' );
		if ( this_prelements.length ) {
			data['dps_pr'] = this_prelements.serializeArray();
		}
		if ( this_form.hasClass( 'js-wpv-dps-enabled' ) || this_form.hasClass( 'js-wpv-ajax-results-enabled' ) ) {
			data['dps_general'] = this_form.find( '.js-wpv-filter-trigger, .js-wpv-filter-trigger-delayed' ).serializeArray();
		}
		return data;
	};
	
	/**
	* pagination_preload_pages
	*
	* Preload pages to a reach.
	*
	* @param object preload_data
	*
	* @since 1.9
	*/
	
	self.pagination_preload_pages = function( preload_data ) {
		var page = parseInt( preload_data.page, 10 ),
		max_pages = parseInt( preload_data.max_pages, 10 ),
		max_reach = parseInt( preload_data.max_reach, 10 );
		
		if ( max_reach > max_pages ) {
			max_reach = max_pages;
		}
		
		if ( preload_data.preload_pages ) {
			var reach = 1;
			while ( reach < max_reach ) {
				self.pagination_preload_next_page( preload_data.view_number, page, max_pages, reach );
				self.pagination_preload_previous_page( preload_data.view_number, page, max_pages, reach );
				reach++;
			}
		}
		if ( preload_data.cache_pages ) {
			self.pagination_cache_current_page( preload_data.view_number, page );
		}
	};
	
	/**
	* pagination_cache_current_page
	*
	* Cache current page.
	*
	* @param string	view_number
	* @param int	page
	*
	* @since 1.9
	*/
	
	self.pagination_cache_current_page = function( view_number, page ) {
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[ view_number ] || [];
		var dataCurrent = {},
		content;
		icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
		if ( ! window.wpvCachedPages[view_number].hasOwnProperty( page ) ) {
			dataCurrent = self.add_view_parameters( dataCurrent, page, view_number );
			dataCurrent = WPViews.view_frontend_utils.add_url_query_parameters( dataCurrent );
			if ( icl_lang !== false ) {
				dataCurrent['lang'] = icl_lang;
			}
			$.get( self.get_ajax_pagination_url( dataCurrent ), '', function( response ) {
				window.wpvCachedPages[view_number][page] = response;
				content = $( response ).find( 'img' );
				content.each( function() {
					window.wpvCachedImages.push( this.src );
				});
			});
		}
	};
	
	/**
	* pagination_preload_next_page
	*
	* Load the next page, or the next one counting "reach" pages.
	*
	* @param string	view_number
	* @param int	page
	* @param int	max_pages
	* @param int	reach
	*
	* @since 1.9
	*/
	
	self.pagination_preload_next_page = function( view_number, page, max_pages, reach ) {
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[ view_number ] || [];
		var next_page = page + reach;
		icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
		if ( ! window.wpvCachedPages[view_number].hasOwnProperty( next_page ) ) {
			if ( ( next_page - 1 ) < max_pages ) {
				var dataNext = {};
				dataNext = self.add_view_parameters( dataNext, next_page, view_number );
				dataNext = WPViews.view_frontend_utils.add_url_query_parameters( dataNext );
				if ( icl_lang !== false ) {
					dataNext['lang'] = icl_lang;
				}
				$.get( self.get_ajax_pagination_url( dataNext ), '', function( response ) {
					window.wpvCachedPages[view_number][next_page] = response;
					var content = $( response ).find( 'img' );
					content.each( function() {
						window.wpvCachedImages.push( this.src );
					});
				});
			}
		}
	};
	
	/**
	* pagination_preload_previous_page
	*
	* Load the previous page, or the previous one counting "reach" pages.
	*
	* @param string	view_number
	* @param int	page
	* @param int	max_pages
	* @param int	reach
	*
	* @since 1.9
	*/
	
	self.pagination_preload_previous_page = function(view_number, page, max_pages, reach) {
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[ view_number ] || [];
		var previous_page = page - reach,
		dataPrevious = {},
		content;
		icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
		if ( ! window.wpvCachedPages[view_number].hasOwnProperty( previous_page ) ) {
			// LOAD PREVIOUS
			if ( ( previous_page + 1 ) > 1 ) {
				dataPrevious = self.add_view_parameters( dataPrevious, previous_page, view_number );
				dataPrevious = WPViews.view_frontend_utils.add_url_query_parameters( dataPrevious );
				if ( icl_lang !== false ) {
					dataPrevious['lang'] = icl_lang;
				}
				$.get( self.get_ajax_pagination_url( dataPrevious ), '', function( response ) {
				window.wpvCachedPages[view_number][previous_page] = response;
				content = $( response ).find( 'img' );
					content.each( function() {
						window.wpvCachedImages.push( this.src );
					});
				});
			} else if ( (previous_page + 1 ) === 1 ) { // LOAD LAST PAGE IF ON FIRST PAGE
				dataPrevious = self.add_view_parameters( dataPrevious, max_pages, view_number );
				dataPrevious = WPViews.view_frontend_utils.add_url_query_parameters( dataPrevious );
				if ( icl_lang !== false ) {
					dataPrevious['lang'] = icl_lang;
				}
				$.get( self.get_ajax_pagination_url( dataPrevious ), '', function( response ) {
					window.wpvCachedPages[view_number][max_pages] = response;
					window.wpvCachedPages[view_number][0] = response;
					content = $( response ).find( 'img' );
					content.each( function() {
						window.wpvCachedImages.push( this.src );
					});
				});
			}
		}
	};
	
	/**
	* trigger_pagination
	*
	* Manage the View pagination after a control has been inited
	*
	* @param string	view_number
	* @param int	page
	*
	* @since 1.9
	*/
	
	self.trigger_pagination = function( view_number, page ) {
		if ( ! window.wpvPaginationAnimationFinished.hasOwnProperty( view_number ) ) {
			window.wpvPaginationAnimationFinished[ view_number ] = false;
		} else if ( window.wpvPaginationAnimationFinished[ view_number ] !== true ) {
			if ( ! window.wpvPaginationQueue.hasOwnProperty( view_number ) ) {
				window.wpvPaginationQueue[view_number] = [];
			}
			window.wpvPaginationQueue[ view_number ].push( arguments );
			return false;
		}
		if ( ! view_number in self.paged_views ) {
			window.wpvPaginationAnimationFinished[ view_number ] = true;
			return false;
		}
		window.wpvPaginationAnimationFinished[ view_number ] = false;
		
		if ( self.paged_views[ view_number ].effect in self.pagination_effects_conditions ) {
			if ( ! self.pagination_effects_conditions[ self.paged_views[ view_number ].effect ]( self.paged_views[ view_number ], page ) ) {
				window.wpvPaginationAnimationFinished[ view_number ] = true;
				return;
			}
		}
		
		var data = {},
		wpvPaginatorLayout = $( '#wpv-view-layout-' + view_number ),
		wpvPaginatorFilter = $( 'form[name=wpv-filter-' + view_number + ']' ),
		speed = 500,
		next = true,
		max_reach = parseInt( self.paged_views[ view_number ].pre_reach ) + 1,
		callback_next_func = WPViews.view_frontend_utils.just_return,
		data_for_get_page,
		img;
		
		// Not using AJAX pagination
		if ( self.paged_views[ view_number ].ajax == 'false' ) {
			
			data = WPViews.view_frontend_utils.add_url_controls_for_column_sort( data, wpvPaginatorFilter );
			// Adjust the wpv_paged hidden input to the page that we want to show
			if ( $( 'input[name=wpv_paged]' ).length > 0 ) {
				$( 'input[name=wpv_paged]' ).attr( 'value', page );
			} else {
				$( '<input>')
					.attr({
						type: 'hidden',
						name: 'wpv_paged',
						value: page
					})
					.appendTo( wpvPaginatorFilter );
			}
			wpvPaginatorFilter[0].submit();
			return false;
		}
		// Using AJAX pagination
		window.wpvPaginationAjaxLoaded[view_number] = false;
		window.wpvCachedPages[ view_number ] = window.wpvCachedPages[view_number] || [];
		
		if ( this.historyP.hasOwnProperty( view_number ) ) {
			next = ( this.historyP[ view_number ] < page ) ? true : false;
		}
		
		if ( self.paged_views[ view_number ].callback_next !== '' ) {
			callback_next_func = window[ self.paged_views[ view_number ].callback_next ];
			if ( typeof callback_next_func !== "function" ) {
				callback_next_func = WPViews.view_frontend_utils.just_return;
			}
		}
		
		data_for_get_page = { 
			view_number:		view_number,
			page:				page,
			max_pages:			parseInt( self.paged_views[ view_number ].max_pages, 10 ),
			speed:				parseFloat( self.paged_views[ view_number ].duration ),
			next:				next,
			effect:				self.paged_views[ view_number ].effect,
			wpvPaginatorFilter: wpvPaginatorFilter,
			wpvPaginatorLayout: wpvPaginatorLayout,
			responseView:		null,
			callback_next_func:	callback_next_func
		};
		
		if ( 
			window.wpvCachedPages[ view_number ].hasOwnProperty( page ) 
		) {
			data_for_get_page.response = window.wpvCachedPages[ view_number ][ page ];
			self.prepare_slide( data_for_get_page );
		} else {
			// Set loading class
			if ( self.paged_views[ view_number ].spinner !== 'no' ) {
				if ( self.paged_views[ view_number ].effect in self.pagination_effects_spinner ) {
					self.pagination_effects_spinner[ self.paged_views[ view_number ].effect ]( view_number, wpvPaginatorLayout );
				} else {
					self.pagination_effects_spinner[ 'fade' ]( view_number, wpvPaginatorLayout );
				}
			}
			
			data = self.add_view_parameters( data, page, view_number );
			data = WPViews.view_frontend_utils.add_url_controls_for_column_sort( data, wpvPaginatorFilter );
			icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
			if ( icl_lang !== false ) {
				data['lang'] = icl_lang;
			}
			$.get( self.get_ajax_pagination_url( data ), '', function( response ) {
				data_for_get_page.response = response;
				self.prepare_slide( data_for_get_page );
			});
		}
		self.pagination_preload_pages({
			view_number:	view_number, 
			cache_pages:	self.paged_views[ view_number ].cache_pages, 
			preload_pages:	self.paged_views[ view_number ].preload_pages, 
			page:			page, 
			max_reach:		max_reach, 
			max_pages:		self.paged_views[ view_number ].max_pages 
		});
		this.historyP[ view_number ] = page;
		return false;
	};
	
	/**
	* prepare_slide
	*
	* Wrap the old layout into a div.wpv_slide_remove and change its ID to ~-response
	* Preload images on the new page if needed
	* Fire self.pagination_slide
	*
	* @param object data
	*
	* @since 1.9
	*/
	
	self.prepare_slide = function( data ) {
		var slide_data = $.extend( {}, self.slide_data_defaults, data ),
		width = slide_data.wpvPaginatorLayout.width(),
		outer_width = slide_data.wpvPaginatorLayout.outerWidth(),
		height = slide_data.wpvPaginatorLayout.height(),
		outer_height = slide_data.wpvPaginatorLayout.outerHeight(),
		responseObj = $( '<div></div>' ).append( slide_data.response ),
		preloadedImages,
		images;
		
		slide_data.responseView = responseObj.find( '#wpv-view-layout-' + slide_data.view_number );
		slide_data.responseFilter = responseObj.find( 'form[name=wpv-filter-' + slide_data.view_number + ']' ).html();
		slide_data.pagination_page_permalink = responseObj.find( '#js-wpv-pagination-page-permalink' ).val();
		
		// Wrap old layout in a div.wpv_slide_remove nd change its ID to ~-response
		slide_data.wpvPaginatorLayout
			.attr( 'id', 'wpv-view-layout-' + slide_data.view_number + '-response' )
			.wrap( '<div class="wpv_slide_remove" style="width:' + outer_width + 'px;height:' + outer_height + 'px;overflow:hidden;" />' )
			.css( 'width', width );
		// Add an ID attribute to the upcoming new layout, and hide it
		slide_data.responseView
			.attr( 'id', 'wpv-view-layout-' + slide_data.view_number )
			.css( {
				'visibility': 'hidden',
				'width': width
			} );
		
		// Preload images if needed
		if ( slide_data.wpvPaginatorLayout.hasClass( 'js-wpv-layout-preload-images' ) ) {
			preloadedImages = [];
			images = slide_data.responseView.find( 'img' );
			if ( images.length < 1 ) {
				self.pagination_slide( slide_data );
			} else {
				images.one( 'load', function() {
					preloadedImages.push( $( this ).attr( 'src' ) );
					if ( preloadedImages.length === images.length ) {
						self.pagination_slide( slide_data );
					}
				}).each( function() {
					$( this ).load();
				});
			}
			// Fix inner nested Views with AJAX pagination: the inner View, when preloading mages, was rendered with visibility:hidden by default
			slide_data.responseView
				.find( '.js-wpv-layout-preload-images' )
					.css( 'visibility', 'visible' );
		} else {
			self.pagination_slide( slide_data );
		}
		WPViews.view_frontend_utils.render_frontend_datepicker();
	};
	
	/**
	* pagination_slide
	*
	* Routes to the right pagination effect callback
	*
	* @param object data
	*
	* @since 1.9
	*/
	
	self.pagination_slide = function( data ) {
		var slide_data = $.extend( {}, self.slide_data_defaults, data );
		
		switch ( slide_data.effect ) {
			case 'slideleft':
				slide_data.next = true;
				slide_data.effect = 'slideh';
				break;
			case 'slideright':
				slide_data.next = false;
				slide_data.effect = 'slideh';
				break;
			case 'slidedown':
				slide_data.next = false;
				slide_data.effect = 'slidev';
				break;
			case 'slideup':
				slide_data.next = true;
				slide_data.effect = 'slidev';
				break;
		}
		
		if ( ! slide_data.effect in self.pagination_effects ) {
			slide_data.effect = 'fade';
		}
		
		self.pagination_effects[ slide_data.effect ]( slide_data );
	};
	
	/**
	* pagination_queue_trigger
	*
	* Manage multiple and fast pagination requests.
	*
	* @param int		view_number
	* @param boolean	next
	* @param object		wpvPaginatorFilter
	*
	* @todo why do we pass next and wpvPaginatorFilter here?
	*
	* @since 1.9
	*/
	
	self.pagination_queue_trigger = function( view_number, next, wpvPaginatorFilter ) {
		var args,
		page,
		max_pages;
		if ( window.wpvPaginationQueue.hasOwnProperty( view_number ) && window.wpvPaginationQueue[view_number].length > 0 ) {
		// when double clicking,we have set window.wpvPaginationQueue[view_number][1] and maybe we could tweak it to change the page number. Maybe checkin historyP
			window.wpvPaginationQueue[view_number].sort();
			args = window.wpvPaginationQueue[view_number][0];
			window.wpvPaginationQueue[view_number].splice(0, 1);
			page = args[1];
			max_pages = args[4];
			if ( page > max_pages ) {
				page = 1;
			} else if ( page < 1 ) {
				page = max_pages;
			}
			self.trigger_pagination( view_number, page );
		}
	};
	
	// ------------------------------------
	// Events
	// ------------------------------------
	
	/**
	* Manage pagination triggered from prev/next links
	*
	* @since 1.9
	*/
	
	$( document ).on( 'click', '.js-wpv-pagination-next-link, .js-wpv-pagination-previous-link', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		view_number = thiz.data( 'viewnumber' ),
		page = thiz.data( 'page' );
		wpv_stop_rollover[ view_number ] = true;
		return self.trigger_pagination( view_number, page );
	});
	
	/**
	* Manage pagination triggered by a change in the page selector dropdown
	*
	* @since 1.9
	*/
	
	$( document ).on( 'change', '.js-wpv-page-selector', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		view_number = thiz.data( 'viewnumber' ),
		page = thiz.val();
		wpv_stop_rollover[ view_number ] = true
		return self.trigger_pagination( view_number, page );
	});
	
	/**
	* Manage pagination triggered by a click on a pagination link.
	*
	* @since 1.9
	*
	* @note Safari on iOS might need to also listen to the touchstart event. Investigate this!
	*/
	
	$( document ).on( 'click', '.js-wpv-pagination-link', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		data_collected = {};
		data_collected.view_number = thiz.data( 'viewnumber');
		data_collected.page = thiz.data( 'page' );
		var i;
		// TODO this can be improved: we should not need a loop here at all
		for ( i = 1; i <= data_collected.max_pages; i++ ) {
			if ( i === data_collected.page ) {
				$( '#wpv-page-link-' + data_collected.view_number + '-' + i ).addClass( 'wpv_page_current' );
			} else {
				$( '#wpv-page-link-' + data_collected.view_number + '-' + i ).removeClass( 'wpv_page_current' );
			}
			
		}
		wpv_stop_rollover[ data_collected.view_number ] = true
		self.trigger_pagination( data_collected.view_number, data_collected.page );
	});
	
	/*
	$( document ).on( 'click', '.js-wpv-pagination-pause-rollover', function( e ) {
		e.preventDefault();
		var view_num = $( this ).data( 'viewnumber' );
		wpv_stop_rollover[view_num] = true;
	});
	
	$( document ).on( 'click', '.js-wpv-pagination-resume-rollover', function( e ) {
		e.preventDefault();
		var view_num = $( this ).data( 'viewnumber' );
		delete wpv_stop_rollover[view_num];
	});
	*/
	
	// ------------------------------------
	// Custom events
	// ------------------------------------
	
	/**
	* js_event_wpv_pagination_completed
	*
	* Event fired after a pagination transition has been completed
	*
	* @param data
	* 	- view_unique_id
	* 	- effect
	* 	- speed
	* 	- layout
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_pagination_completed', function( event, data ) {
		WPViews.view_frontend_utils.render_frontend_media_shortcodes( data.layout );
	});
	
	// ------------------------------------
	// Init
	// ------------------------------------
	
	/**
	* init_effects
	*
	* Define the default pagination effects, can be extended by third parties.
	* Each callback gets the foloring parameters:
	* @param object slide_data
	*
	* @since 1.11
	*/
	
	self.init_effects = function() {
		self.pagination_effects = {
			infinite: function( slide_data ) {
				
				if ( slide_data.page != ( self.paged_views[ slide_data.view_number ].page + 1 ) ) {
					// This should never happen! See self.pagination_effects_conditions
					$( '#wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					slide_data.wpvPaginatorLayout
						.animate( { opacity: 1 }, 300 )
						.unwrap()// If it got here, it has a -response suffix in the ID and is wrapped in an auxiliar div
						.attr( 'id', 'wpv-view-layout-' + slide_data.view_number  );
					window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
					window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
					
				} else {
				
					// content.match(/<!-- HOOK OPEN -->(W?)\<!-- HOOK CLOSE -->/);
					var data_for_events = {},
					data_for_history = {};
					
					data_for_events.view_unique_id	= slide_data.view_number;
					data_for_events.effect			= 'infinite';
					data_for_events.speed			= slide_data.speed;
					
					data_for_history.view_number				= slide_data.view_number;
					data_for_history.page						= slide_data.page;
					data_for_history.effect						= 'infinite';
					data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
					
					if (
						slide_data.wpvPaginatorLayout.find( '.js-wpv-loop' ).length > 0 
						&& slide_data.responseView.find( '.js-wpv-loop' ).length > 0 
					) {
						slide_data.responseView
							.find( '.js-wpv-loop' )
							.children()
								.addClass( 'wpv-loop-item-blink' )
								.css( { 'opacity': '0.3' } );
						slide_data.responseView
							.find( '.js-wpv-loop' )
							.prepend( 
								slide_data.wpvPaginatorLayout
									.find( '.js-wpv-loop' )
									.html() 
							);
						slide_data.wpvPaginatorLayout.html( slide_data.responseView.html() );
						slide_data.wpvPaginatorLayout
							.find( '.wpv-loop-item-blink' )
								.removeClass( 'wpv-loop-item-blink' )
								.animate( { opacity: 1 }, slide_data.speed );
								
					} else {
						var oldHTML = slide_data.wpvPaginatorLayout.html(),
						oldArray = oldHTML.split( '<!-- WPV_Infinite_Scroll -->', 3 ),
						oldReplace = ( oldArray.length > 2 ) ? oldArray[1] : '';
						slide_data.wpvPaginatorLayout.html(
							slide_data.responseView.html().replace( 
								'<!-- WPV_Infinite_Scroll_Insert -->',
								oldReplace
							)
						);
					}
					data_for_events.layout = slide_data.wpvPaginatorLayout;
					$( '#wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					slide_data.wpvPaginatorLayout
						.animate( { opacity: 1 }, 300 )
						.unwrap()
						.attr( 'id', 'wpv-view-layout-' + slide_data.view_number  );
										
					slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
					
					self.paged_views[ slide_data.view_number ].page = slide_data.page;
					
					window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
					window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
					slide_data.callback_next_func();
					self.manage_browser_history( data_for_history );
					$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
					self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
				}
			},
			slideh: function( slide_data ) {
				var height = slide_data.wpvPaginatorLayout.height(),
				old_height = slide_data.wpvPaginatorLayout.outerHeight(),
				new_height,
				data_for_events = {},
				data_for_history = {};
				
				data_for_events.view_unique_id	= slide_data.view_number;
				data_for_events.effect			= 'slideh';
				data_for_events.speed			= slide_data.speed;
				data_for_events.layout			= slide_data.responseView;
				
				data_for_history.view_number				= slide_data.view_number;
				data_for_history.page						= slide_data.page;
				data_for_history.effect						= 'slideh';
				data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
				
				if ( slide_data.next === true ) {
					slide_data.wpvPaginatorLayout.css( 'float', 'left' );
					slide_data.responseView.css( {"float": "left", "visibility": "visible"} );
					slide_data.wpvPaginatorLayout
						.after( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div style="width:5000px;" />' );
					$( '#wpv_slide_loading_img_' + slide_data.view_number ).fadeOut(function() {
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '-' + slide_data.wpvPaginatorLayout.outerWidth()+'px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'float': 'none'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '-' + slide_data.wpvPaginatorLayout.outerWidth()+'px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginLeft: '-' + slide_data.wpvPaginatorLayout.outerWidth()+'px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				} else {
					slide_data.wpvPaginatorLayout.css( 'float', 'right' );
					slide_data.responseView.css( {'float': 'right', 'visibility': 'visible'} );
					slide_data.wpvPaginatorLayout
						.after( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div style="height:' + height +  ';width:' + ( slide_data.responseView.outerWidth() + slide_data.wpvPaginatorLayout.outerWidth() ) + 'px; margin-left:-' + ( slide_data.wpvPaginatorLayout.outerWidth() ) + 'px;" />' );
					$( '#wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '0px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'margin': '0px', 'float': 'none'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginLeft: '0px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginLeft: '0px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px', 'float': 'none'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				}
			},
			slidev: function( slide_data ) {
				var old_height = slide_data.wpvPaginatorLayout.outerHeight(),
				new_height,
				data_for_events = {},
				data_for_history = {};
				
				data_for_events.view_unique_id	= slide_data.view_number;
				data_for_events.effect			= 'slidev';
				data_for_events.speed			= slide_data.speed;
				data_for_events.layout			= slide_data.responseView;
				
				data_for_history.view_number				= slide_data.view_number;
				data_for_history.page						= slide_data.page;
				data_for_history.effect						= 'slidev';
				data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
				
				if ( slide_data.next === true ) {
					slide_data.responseView.css( 'visibility', 'visible' );
					slide_data.wpvPaginatorLayout
						.after( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div />' );
					$( '#wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function(){
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '-' + slide_data.responseView.outerHeight()+'px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '-'+old_height+'px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginTop: '-'+old_height+'px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				} else {
					slide_data.responseView.css( 'visibility', 'visible' );
					slide_data.wpvPaginatorLayout
						.before( slide_data.responseView )
						.parent()
							.children()
								.wrapAll( '<div />' );
					$( '#wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
						$( this ).remove();
					});
					new_height = slide_data.responseView.outerHeight();
					slide_data.wpvPaginatorLayout.parent().css( {'position': 'relative', 'margin-top': '-' + slide_data.responseView.outerHeight() + 'px'} );
					if ( old_height === new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '0px'}, slide_data.speed+500, function() {
									slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
									slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
								});
					} else if ( old_height > new_height ) {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {marginTop: '0px'}, slide_data.speed+500, function() {
									slide_data.wpvPaginatorLayout
										.parent().parent()
											.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
						});
					} else {
						slide_data.wpvPaginatorLayout
							.parent().parent()
								.animate( {height: slide_data.responseView.outerHeight()+'px'}, slide_data.speed/2, function() {
									slide_data.wpvPaginatorLayout
										.parent()
											.animate( {marginTop: '0px'}, slide_data.speed+500, function() {
												slide_data.responseView.css( {'position': 'static', 'margin': '0px'} );
												slide_data.wpvPaginatorLayout.unwrap().unwrap().remove();
												
												slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
												
												window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
												window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
												slide_data.callback_next_func();
												self.manage_browser_history( data_for_history );
												$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
												self.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
											});
								});
					}
				}
			},
			fade: function( slide_data ) {
				var old_height = slide_data.wpvPaginatorLayout.outerHeight(),
				new_height,
				data_for_events = {},
				data_for_history = {};

				data_for_events.view_unique_id	= slide_data.view_number;
				data_for_events.effect			= 'fade';
				data_for_events.speed			= slide_data.speed;
				data_for_events.layout			= slide_data.responseView;
				
				data_for_history.view_number				= slide_data.view_number;
				data_for_history.page						= slide_data.page;
				data_for_history.effect						= 'fade';
				data_for_history.pagination_page_permalink	= slide_data.pagination_page_permalink;
				
				$( '#wpv_slide_loading_img_' + slide_data.view_number ).fadeOut( function() {
					$( this ).remove();
				});
				
				slide_data.wpvPaginatorLayout
					.css( {'position': 'absolute', 'z-index': '5'} )
					.after( slide_data.responseView )
						.next()
						.css( 'position', 'static' );
				new_height = slide_data.responseView.outerHeight();
				if ( old_height === new_height ) {
					slide_data.wpvPaginatorLayout.fadeOut( slide_data.speed, function() {
						slide_data.wpvPaginatorLayout.unwrap().remove();
						
						slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
						
						window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
						window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
						slide_data.callback_next_func();
						self.manage_browser_history( data_for_history );
						$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
						WPViews.view_pagination.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
					});
					slide_data.responseView
						.hide()
						.css( 'visibility', 'visible' )
						.fadeIn( slide_data.speed );
				} else {
					slide_data.wpvPaginatorLayout.fadeOut( slide_data.speed, function() {
						slide_data.wpvPaginatorLayout
							.parent()
								.animate( {height: new_height+'px'}, slide_data.speed, function() {
									slide_data.wpvPaginatorLayout.unwrap().remove();
									
									slide_data.wpvPaginatorFilter.html( slide_data.responseFilter );
									
									window.wpvPaginationAjaxLoaded[slide_data.view_number] = true;
									window.wpvPaginationAnimationFinished[slide_data.view_number] = true;
									slide_data.callback_next_func();
									self.manage_browser_history( data_for_history );
									$( document ).trigger( 'js_event_wpv_pagination_completed', [ data_for_events ] );
									WPViews.view_pagination.pagination_queue_trigger( slide_data.view_number, slide_data.next, slide_data.wpvPaginatorFilter );
									slide_data.responseView
										.hide()
										.css( 'visibility', 'visible' )
										.fadeIn( slide_data.speed );
								});
					});
				}
			}
		};
	
	};
	
	/**
	* manage_browser_history
	*
	* Makes the history in the browser work with AJAX pagination, except infinite scrolling and sliders
	*
	* @param object data
	*
	* @since 1.11
	*/
	
	self.manage_browser_history = function( data ) {
		if ( 
			! _.has( WPViews, 'rollower_ids' )
			|| ! _.contains( WPViews.rollower_ids, data.view_number ) 
		) {
			if ( self.add_paginated_history == true ) {
				if ( ! _.contains( self.pagination_effect_state_keep, data.effect ) ) {
					if ( _.contains( self.pagination_effect_state_replace, data.effect ) ) {
						history.replaceState( null, '', data.pagination_page_permalink );
					} else {
						self.last_paginated_view.push( data.view_number );
						state_obj = { 
							view_number: data.view_number, 
							page: data.page
						};
						history.pushState( state_obj, '', data.pagination_page_permalink );
						// http://scrollsample.appspot.com/items
						// http://html5.gingerhost.com/
						self.paginated_history_reach = self.paginated_history_reach + 1;
					}
				}
			} else {
				self.add_paginated_history = true;
			}
		}
	};
	
	/**
	* window.onpopstate
	*
	* Manages the browser back button click based on daya added by Views pagination
	*
	* @since 1.11
	*/
	
	window.onpopstate = function( event ) {
		if ( event.state == null ) {
			var last_paginated_view_number = self.last_paginated_view.pop();
			if ( last_paginated_view_number != undefined ) {
				self.add_paginated_history = false;
				self.paged_views_initial_page[ last_paginated_view_number ] = self.paged_views_initial_page[ last_paginated_view_number ] || 1;
				self.trigger_pagination( last_paginated_view_number, self.paged_views_initial_page[ last_paginated_view_number ] );
			}
		} else {
			if (
				_.has( event.state, 'view_number' )
				&& _.has( event.state, 'page' )
			) {
				self.add_paginated_history = false;
				self.trigger_pagination( event.state.view_number, event.state.page );
			}
		}
	};
	
	/**
	* When the parametric search with automatic results has been completed, reset the pagination history and add a state with the current URL
	*
	* @since 1.11
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_results_updated', function( event, data ) {
		var pagination_page_permalink = data.layout.find( '#js-wpv-pagination-page-permalink' ).val();
		self.paged_views[ data.view_unique_id ] = data.layout.data( 'pagination' );
		window.wpvCachedPages[ data.view_unique_id ] = [];
		self.last_paginated_view = [];
		if ( self.paginated_history_reach > 0 ) {
			window.history.go( -Math.abs( self.paginated_history_reach ) );
			self.paginated_history_reach = 0;
		}
		// HACK! HACK! HACK!
		// Chrome and Safari execute history.replaceState before history.go so we end up with a mess
		// That is why we need to set a timeout here
		// See https://code.google.com/p/chromium/issues/detail?id=529810
		setTimeout( function() {
			history.replaceState( null, '', pagination_page_permalink );
		}, 100 );
	});
	
	/**
	* init_effects_conditions
	*
	* Lets you define a condition that an effect must meet to be applied, doing nothing otherwise.
	* Each callback gets the following parameters:
	* @param object	view_pagination_data
	* @param int	page
	*
	* @since 1.11
	*/
	
	self.init_effects_conditions = function() {
		self.pagination_effects_conditions = {
			infinite: function( view_pagination_data, page ) {
				if ( page != ( view_pagination_data.page + 1 ) ) {
					return false;
				}
				return true;				
			}
		};
	};
	
	/**
	* init_effects_conditions
	*
	* Lets you define a condition that an effect must meet to be applied, doing nothing otherwise.
	* Each callback gets the following parameters:
	* @param string	view_number
	* @param object	wpvPaginatorLayout
	*
	* @since 1.11
	*/
	
	self.init_effects_spinner = function() {
		self.pagination_effects_spinner['fade']			= 
		self.pagination_effects_spinner['slideleft']	= 
		self.pagination_effects_spinner['slideright']	= 
		self.pagination_effects_spinner['slideh']		= 
		self.pagination_effects_spinner['slideup']		=
		self.pagination_effects_spinner['slidedown']	= 
		self.pagination_effects_spinner['slidev']		= function( view_number, wpvPaginatorLayout ) {
			var img = new Image();
			img.src = self.paged_views[ view_number ].spinner_image;
			img.onload = function() {
				var wpvPaginatorLayoutOffset = wpvPaginatorLayout.position(),
				wpvPaginatorSpinner = '<div style="'
					+ 'width:' + img.width + 'px;'
					+ 'height:' + img.height + 'px;'
					+ 'border:none;'
					+ 'background:transparent 50% 50% no-repeat url(' + self.paged_views[ view_number ].spinner_image + ');'
					+ 'position:absolute;'
					+ 'z-index:99;'
					+ 'top:' + ( Math.round( wpvPaginatorLayoutOffset.top ) + ( Math.round( wpvPaginatorLayout.height()/2 ) ) - ( Math.round( img.height/2 ) ) ) + 'px;'
					+ 'left:' + ( Math.round( wpvPaginatorLayoutOffset.left ) + ( Math.round( wpvPaginatorLayout.width()/2 ) ) - ( Math.round( img.width/2 ) ) ) + 'px;'
					+ '" '
					+ 'id="wpv_slide_loading_img_' + view_number + '" '
					+ 'class="wpv_slide_loading_img"'
					+ '>'
					+ '</div>';
				wpvPaginatorLayout
					.before( wpvPaginatorSpinner )
						.animate( { opacity: 0.5 }, 300 );
			};
		};
		self.pagination_effects_spinner['infinite'] = function( view_number, wpvPaginatorLayout ) {
			var img = new Image();
			img.src = self.paged_views[ view_number ].spinner_image;
			img.onload = function() {
				var wpvPaginatorLayoutOffset = wpvPaginatorLayout.position(),
				wpvPaginatorSpinner = '<div style="'
					+ 'width:' + img.width + 'px;'
					+ 'height:' + img.height + 'px;'
					+ 'border:none;'
					+ 'background:transparent 50% 50% no-repeat url(' + self.paged_views[ view_number ].spinner_image + ');'
					+ 'position:absolute;'
					+ 'z-index:99;'
					+ 'top:' + ( Math.round( wpvPaginatorLayoutOffset.top ) + ( wpvPaginatorLayout.height() ) - ( Math.round( img.height/2 ) ) ) + 'px;'
					+ 'left:' + ( Math.round( wpvPaginatorLayoutOffset.left ) + ( Math.round( wpvPaginatorLayout.width()/2 ) ) - ( Math.round( img.width/2 ) ) ) + 'px;'
					+ '" '
					+ 'id="wpv_slide_loading_img_' + view_number + '" '
					+ 'class="wpv_slide_loading_img"'
					+ '>'
					+ '</div>';
				wpvPaginatorLayout
					.before( wpvPaginatorSpinner )
						.animate( { opacity: 0.5 }, 300 );
			};
		};
	};
	
	/**
	* init_paged_views
	*
	* Gather the data for paginating each of the Views rendered in a page
	*
	* @since 1.11
	*/
	
	self.init_paged_views = function() {
		var init_scrolling_event = false;
		this.historyP = this.historyP || [];
		window.wpvCachedPages = window.wpvCachedPages || [];
		window.wpvCachedImages = window.wpvCachedImages || [];
		$( '.js-wpv-view-layout' ).each( function() {
			var thiz = $( this ),
			view_number = thiz.data( 'viewnumber' );
			self.init_paged_view( view_number );
			if (
				self.paged_views[ view_number ].effect == 'infinite' 
				&& self.paged_views[ view_number ].page == 1
			) {
				init_scrolling_event = true;
			}
		});
		if ( init_scrolling_event ) {
			self.init_scrolling_event_callback();
		}
	};
	
	/**
	* init_paged_view
	*
	* Gather pagination info for a specific View rendered in a page.
	* Note that this is also used to init the View pagination data after a parametric search change.
	*
	* @since 1.11
	*/
	
	self.init_paged_view = function( view_number ) {
		self.paged_views[ view_number ] = $( '#wpv-view-layout-' + view_number ).data( 'pagination' );
		self.paged_views_initial_page[ view_number ] = self.paged_views[ view_number ].page;
		window.wpvCachedPages[ view_number ] = [];
		if ( 
			self.paged_views[ view_number ].ajax != 'false' 
			&& self.paged_views[ view_number ].page > 1 
		) {
			// Infinite scrolling only can br triggered from the first page - individual URLs can not have that effect
			$( '#wpv-view-layout-' + view_number ).removeClass( 'js-wpv-layout-infinite-scrolling' );
		}
	};
	
	/**
	* is_infinite_triggable
	*
	* Auxiliar method to check whether the scroll got to a point where a pagination event should be triggered
	*
	* @param object	view_layout
	*
	* @since 1.11
	*/
	
	self.is_infinite_triggable = function( view_layout ) {
		var flag_element = view_layout;
		if ( view_layout.find( '.js-wpv-loop' ).length > 0 ) {
			flag_element = view_layout.find( '.js-wpv-loop' );
		}
		return (
			( flag_element.offset().top + flag_element.outerHeight() ) 
			<=
			( $( window ).scrollTop() + $( window ).height() )
		);
	};
	
	/**
	* init_scrolling_event_callback
	*
	* Init the scrolling event callback, only when there is a View with infinite scrlling in a page
	*
	* @since 1.11
	*/
	
	self.init_scrolling_event_callback = function() {
		$( window ).on( 'scroll', _.debounce( 
			_.throttle( 
				function() {
					$( '.js-wpv-layout-infinite-scrolling' ).each( function() {
						var thiz = $( this ),
						thiz_view_number = thiz.data( 'viewnumber' );
						if ( 
							self.paged_views[ thiz_view_number ].page < self.paged_views[ thiz_view_number ].max_pages 
							&& self.is_infinite_triggable( thiz )
						) {
							return self.trigger_pagination( thiz_view_number, self.paged_views[ thiz_view_number ].page + 1 );
						}
					});
				},
				1000
			),
			1000
		));
	};
	
	/**
	* pagination_init_preload_images
	*
	* Init-preload images.
	*
	* @since 1.9
	*/
	
	self.init_preload_images = function() {
		$('.js-wpv-layout-preload-images').css('visibility', 'hidden'); // TODO move it to the CSS file and test
		$( '.js-wpv-layout-preload-images' ).each( function() {
			var preloadedImages = [],
			element = $( this ),
			images = element.find( 'img' );
			if ( images.length < 1 ) {
				element.css( 'visibility', 'visible' );
			} else {
				images.one( 'load', function() {
					preloadedImages.push( $( this ).attr( 'src' ) );
					if ( preloadedImages.length === images.length ) {
						element.css( 'visibility', 'visible' );
					}
				}).each( function() {
					if( this.complete ) {
						$( this ).load();
					}
				});
				setTimeout( function() {
					element.css( 'visibility', 'visible' );
				}, 3000 );
			}
		});
	};
	
	self.init_preload_pages = function() {
		$( '.js-wpv-layout-preload-pages' ).each( function() {
			var thiz = $( this ),
			view_number = thiz.data( 'viewnumber' ),
			max_pages = parseInt( self.paged_views[ view_number ].max_pages, 10 ),
			max_reach = parseInt( self.paged_views[ view_number ].pre_reach, 10 ) + 1;
			
			self.pagination_preload_pages({
				view_number:	view_number, 
				cache_pages:	false, 
				preload_pages:	true, 
				page:			1, 
				max_reach:		max_reach,
				max_pages:		max_pages 
			});
		});
	};
	
	self.init = function() {
		self.init_effects();
		self.init_effects_conditions();
		self.init_effects_spinner();
		self.init_paged_views();
		self.init_preload_images();
		self.init_preload_pages();
	}
	
	self.init();

};

WPViews.ViewParametricSearch = function( $ ) {
	
	// ------------------------------------
	// Constants and variables
	// ------------------------------------
	
	var self = this;
	
	// ------------------------------------
	// Methods
	// ------------------------------------
	
	/**
	* manage_update_form
	*
	*
	*
	* @since 1.9
	*
	* @todo we are not handling 3rd party URL parameters here
	*/

	self.manage_update_form = function( fil, ajax_get ) {
		var view_num = fil.data( 'viewnumber' ),
		view_id = fil.data( 'viewid' ),
		aux_fil,
		data = {
			action: 'wpv_update_parametric_search',
			valz: fil.serializeArray(),
			viewid: view_id,
			getthis: ajax_get
		},
		attr_data = fil.find('.js-wpv-view-attributes'),
		ajax_data = {};
		
		wpv_stop_rollover[view_num] = true;
		if ( attr_data.length > 0 ) {
			data['attributes'] = attr_data.data();
		}
		
		if ( fil.attr( 'data-targetid' ) ) {
			data.targetid = fil.data( 'targetid' );
		} else if ( ajax_get == 'both' ) {
			aux_fil = $( '.js-wpv-form-only.js-wpv-filter-form-' + view_num );
			data.targetid = aux_fil.data( 'targetid' );
		}
		
		icl_lang = ( typeof icl_lang == 'undefined' ) ? false : icl_lang;
		
		ajax_data.type = "POST";
		ajax_data.url = wpv_pagination_local.front_ajaxurl;
		ajax_data.data = data;
		
		if ( icl_lang !== false ) {
			ajax_data.xhrFields = {
				withCredentials: true
			};
		}
		
		return $.ajax( ajax_data );
	};
	
	self.manage_update_results = function( lay, new_lay, view_num, ajax_before, ajax_after ) {
		if ( ajax_before !== '' ) {
			var ajax_before_func = window[ajax_before];
			if ( typeof ajax_before_func === "function" ) {
				ajax_before_func( view_num );
			}
		}
		var data_for_events = {};
		data_for_events.view_unique_id = view_num;
		lay.fadeOut( 200, function() {
			lay.html( new_lay.html() )
				.attr( 'data-pagination', new_lay.attr( 'data-pagination' ) )
				.data( 'pagination', new_lay.data( 'pagination' ) )
				.fadeIn( 'fast', function() {
					var ajax_after_func = window[ajax_after];
					if ( typeof ajax_after_func === "function" ) {
						ajax_after_func( view_num );
					}
					data_for_events.layout = lay;
					$( document ).trigger( 'js_event_wpv_parametric_search_results_updated', [ data_for_events ] );
				});		
		});
	};
	
	/**
	* manage_changed_form
	*
	* @param
	*
	* @since 1.9
	*/
	
	self.manage_changed_form = function( fil, force_form_update, force_results_update ) {
		var view_num = fil.data( 'viewnumber' ),
		lay = $( '#wpv-view-layout-' + view_num ),
		full_data = fil.find( '.js-wpv-filter-data-for-this-form' ),
		ajax_pre_before = full_data.data( 'ajaxprebefore' ),
		ajax_before = full_data.data( 'ajaxbefore' ),
		ajax_after = full_data.data( 'ajaxafter' ),
		view_type = 'full',
		additional_forms = $( '.js-wpv-filter-form-' + view_num ).not( fil ),
		additional_forms_only,
		additional_forms_full,
		ajax_get = 'both',
		new_content_form,
		new_content_form_filter,
		new_content_full,
		new_content_full_filter,
		new_content_full_layout,
		spinnerContainer = fil.find( '.js-wpv-dps-spinner' ).add( additional_forms.find( '.js-wpv-dps-spinner' ) ),//TODO maybe add a view_num here to select all spinnerContainers
		spinnerItems = spinnerContainer.length
		data_for_events = {};
		data_for_events.view_unique_id = view_num;
		if ( fil.hasClass( 'js-wpv-form-only' ) ) {
			view_type = 'form';
		}
		if ( fil.hasClass( 'js-wpv-dps-enabled' ) || force_form_update === true ) {
			if ( additional_forms.length > 0 ) {
				additional_forms_only = additional_forms.not( '.js-wpv-form-full' );
				additional_forms_full = additional_forms.not( '.js-wpv-form-only' );
				if ( view_type == 'form' ) {
					if ( additional_forms_full.length > 0 || ( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 ) ) {
						ajax_get = 'both';					
					} else {
						ajax_get = 'form';
					}
					if (
						( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
						|| force_results_update
					) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					self.manage_update_form( fil, ajax_get ).done(function(result) {
						decoded_response = $.parseJSON(result);
						new_content_form = $( '<div></div>' ).append( decoded_response.form );
						new_content_full = $( '<div></div>' ).append( decoded_response.full );
						new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' );
						new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' );
						new_content_full_layout = new_content_full.find( '.js-wpv-view-layout' );
						
						fil.html( new_content_form_filter.html() );
						$( ".js-wpv-frontend-datepicker" )
							.removeClass( 'js-wpv-frontend-datepicker-inited' )
							.datepicker( "destroy" );
						WPViews.view_frontend_utils.clone_form( fil, additional_forms_only );
						additional_forms_full.each( function() {
							$( this ).html( new_content_full_filter.html() );
						});
						data_for_events.view_changed_form = fil;
						data_for_events.view_changed_form_additional_forms_only = additional_forms_only;
						data_for_events.view_changed_form_additional_forms_full = additional_forms_full;
						$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
						if (
							( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
							|| force_results_update
						) {
							self.manage_update_results( lay, new_content_full_layout, view_num, ajax_before, ajax_after );
						}
						spinnerContainer.hide();
					}).fail(function() {
						// an error occurred
					});
				} else {
					if ( additional_forms_only.length > 0 ) {
						ajax_get = 'both';
					} else {
						ajax_get = 'full';
					}
					if (
						( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
						|| force_results_update
					) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					self.manage_update_form( fil, ajax_get ).done(function(result) {
						decoded_response = $.parseJSON(result);
						new_content_form = $( '<div></div>' ).append( decoded_response.form );
						new_content_full = $( '<div></div>' ).append( decoded_response.full );
						new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' );
						new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' );
						new_content_full_layout = new_content_full.find( '.js-wpv-view-layout' );
						
						fil.html( new_content_full_filter.html() );
						$( ".js-wpv-frontend-datepicker" )
							.removeClass( 'js-wpv-frontend-datepicker-inited' )
							.datepicker( "destroy" );
						WPViews.view_frontend_utils.clone_form( fil, additional_forms_full );
						additional_forms_only.each( function() {
							$( this ).html( new_content_form_filter.html() );
						});
						data_for_events.view_changed_form = fil;
						data_for_events.view_changed_form_additional_forms_only = additional_forms_only;
						data_for_events.view_changed_form_additional_forms_full = additional_forms_full;
						$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
						if (
							( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
							|| force_results_update
						) {
							self.manage_update_results( lay, new_content_full_layout, view_num, ajax_before, ajax_after );
						}
						spinnerContainer.hide();
					}).fail(function() {
						// an error occurred
					});
				}
			} else {
				if ( view_type == 'form' ) {
					if ( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 ) {
						ajax_get = 'both';
						// NOTE this should never happen:
						// If change is done on an only-form and there is no extra form, there is no full form thus there is no layout
						// WARNING this can be executed on an only-form form from a View with automatic results
						// I might want to avoid this branch completely
						// NOTE-2 might be a good idea to keep-on-clear// As we might be displaying the layout in non-standard ways
						// So keeping the check for lay.length should suffice
						
					} else {
						ajax_get = 'form';
					}
					
					if (
						( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
						|| force_results_update
					) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					self.manage_update_form( fil, ajax_get ).done(function(result) {
						decoded_response = $.parseJSON(result);
						new_content_form = $( '<div></div>' ).append( decoded_response.form );
						new_content_full = $( '<div></div>' ).append( decoded_response.full );
						new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' );
						//new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' ).html();
						new_content_full_layout = new_content_full.find( '.js-wpv-view-layout' );
						fil.html( new_content_form_filter.html() );
						data_for_events.view_changed_form = fil;
						data_for_events.view_changed_form_additional_forms_only = additional_forms_only;
						data_for_events.view_changed_form_additional_forms_full = additional_forms_full;
						$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
						if (
							( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
							|| force_results_update
						) {
							self.manage_update_results( lay, new_content_full_layout, view_num, ajax_before, ajax_after );
						}
						spinnerContainer.hide();
					}).fail(function() {
						// an error occurred
					});
				} else {
					if (
						( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
						|| force_results_update
					) {
						if ( ajax_pre_before !== '' ) {
							var ajax_pre_before_func = window[ajax_pre_before];
							if ( typeof ajax_pre_before_func === "function" ) {
								ajax_pre_before_func( view_num );
							}
						}
						$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					}
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					self.manage_update_form( fil, 'full' ).done(function(result) {
						decoded_response = $.parseJSON(result);
						//new_content_form = $( '<div></div>' ).append( ajax_result.form );
						new_content_full = $( '<div></div>' ).append( decoded_response.full );
						//new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' ).html();
						new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' );
						new_content_full_layout = new_content_full.find( '.js-wpv-view-layout' );
						fil.html( new_content_full_filter.html() );
						data_for_events.view_changed_form = fil;
						data_for_events.view_changed_form_additional_forms_only = additional_forms_only;
						data_for_events.view_changed_form_additional_forms_full = additional_forms_full;
						$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
						if (
							( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
							|| force_results_update
						) {
							self.manage_update_results( lay, new_content_full_layout, view_num, ajax_before, ajax_after );
						}
						spinnerContainer.hide();
					}).fail(function() {
						// an error occurred
					});
				}
			}
		} else {
			if ( additional_forms.length > 0 ) {
				additional_forms_only = additional_forms.not( '.js-wpv-form-full' );
				additional_forms_full = additional_forms.not( '.js-wpv-form-only' );
				if ( view_type == 'form' ) {
					$( ".js-wpv-frontend-datepicker" )
						.removeClass( 'js-wpv-frontend-datepicker-inited' )
						.datepicker( "destroy" );
					WPViews.view_frontend_utils.clone_form( fil, additional_forms_only );
					if ( additional_forms_full.length > 0 || ( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 ) ) {
						if (
							( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
							|| force_results_update
						) {
							if ( ajax_pre_before !== '' ) {
								var ajax_pre_before_func = window[ajax_pre_before];
								if ( typeof ajax_pre_before_func === "function" ) {
									ajax_pre_before_func( view_num );
								}
							}
							$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
						}
						if ( spinnerItems ) {// TODO maybe only when updating results
							$( spinnerContainer ).fadeIn( 'fast' );
						}
						self.manage_update_form( fil, 'full' ).done(function(result) {
							decoded_response = $.parseJSON(result);
							//new_content_form = $( '<div></div>' ).append( decoded_response.form );
							new_content_full = $( '<div></div>' ).append( decoded_response.full );
							//new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' ).html();
							new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' );
							new_content_full_layout = new_content_full.find( '.js-wpv-view-layout' );
							
							additional_forms_full.each( function() {
								$( this ).html( new_content_full_filter.html() );
							});
							data_for_events.view_changed_form = fil;
							data_for_events.view_changed_form_additional_forms_only = additional_forms_only;
							data_for_events.view_changed_form_additional_forms_full = additional_forms_full;
							$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
							if (
								( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
								|| force_results_update
							) {
								self.manage_update_results( lay, new_content_full_layout, view_num, ajax_before, ajax_after );
							}
							spinnerContainer.hide();
						}).fail(function() {
							// an error occurred
						});
					} else {
						data_for_events.view_changed_form = fil;
						data_for_events.view_changed_form_additional_forms_only = additional_forms_only;
						data_for_events.view_changed_form_additional_forms_full = additional_forms_full;
						$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
					}
				} else {
					$( ".js-wpv-frontend-datepicker" )
						.removeClass( 'js-wpv-frontend-datepicker-inited' )
						.datepicker( "destroy" );
					WPViews.view_frontend_utils.clone_form( fil, additional_forms_full );
					WPViews.view_frontend_utils.render_frontend_datepicker();
					if ( additional_forms_only.length > 0 || ( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 ) ) {
						if ( additional_forms_only.length > 0 ) {
							ajax_get = 'both';
						} else {
							ajax_get = 'full';
						}
						
						if (
							( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
							|| force_results_update
						) {
							if ( ajax_pre_before !== '' ) {
								var ajax_pre_before_func = window[ajax_pre_before];
								if ( typeof ajax_pre_before_func === "function" ) {
									ajax_pre_before_func( view_num );
								}
							}
							$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
						}
						if ( spinnerItems ) {// TODO maybe only when updating results
							$( spinnerContainer ).fadeIn( 'fast' );
						}
						self.manage_update_form( fil, ajax_get ).done(function(result) {
							decoded_response = $.parseJSON(result);
							new_content_form = $( '<div></div>' ).append( decoded_response.form );
							new_content_full = $( '<div></div>' ).append( decoded_response.full );
							new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' );
							//new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' ).html();
							new_content_full_layout = new_content_full.find( '.js-wpv-view-layout' );
							additional_forms_only.each( function() {
								$( this ).html( new_content_form_filter.html() );
							});
							data_for_events.view_changed_form = fil;
							data_for_events.view_changed_form_additional_forms_only = additional_forms_only;
							data_for_events.view_changed_form_additional_forms_full = additional_forms_full;
							$( document ).trigger( 'js_event_wpv_parametric_search_form_updated', [ data_for_events ] );
							if (
								( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
								|| force_results_update
							) {
								self.manage_update_results( lay, new_content_full_layout, view_num, ajax_before, ajax_after );
							}
							spinnerContainer.hide();
						}).fail(function() {
							// an error occurred
						});
					}
				}
			} else {
				if (
					( fil.hasClass( 'js-wpv-ajax-results-enabled' ) && lay.length > 0 )
					|| force_results_update
				) {
					if ( ajax_pre_before !== '' ) {
						var ajax_pre_before_func = window[ajax_pre_before];
						if ( typeof ajax_pre_before_func === "function" ) {
							ajax_pre_before_func( view_num );
						}
					}
					$( document ).trigger( 'js_event_wpv_parametric_search_started', [ data_for_events ] );
					if ( spinnerItems ) {// TODO maybe only when updating results
						$( spinnerContainer ).fadeIn( 'fast' );
					}
					self.manage_update_form( fil, 'full' ).done(function(result) {
						decoded_response = $.parseJSON(result);
						//new_content_form = $( '<div></div>' ).append( decoded_response.form );
						new_content_full = $( '<div></div>' ).append( decoded_response.full );
						//new_content_form_filter = new_content_form.find( '.js-wpv-filter-form' ).html();
						//new_content_full_filter = new_content_full.find( '.js-wpv-filter-form' ).html();
						new_content_full_layout = new_content_full.find( '.js-wpv-view-layout' );
						self.manage_update_results( lay, new_content_full_layout, view_num, ajax_before, ajax_after );
						spinnerContainer.hide();
					}).fail(function() {
						// an error occurred
					});
				}
			}
		}
	};
	
	// ------------------------------------
	// Events
	// ------------------------------------
	
	// Show datepicker on date string click
	$( document ).on( 'click', '.js-wpv-date-display', function() {
		var url_param = $( this ).data( 'param' );
		$( '.js-wpv-date-front-end-' + url_param ).datepicker( 'show' );
	});

	// Remove current selected date
	$( document ).on( 'click', '.js-wpv-date-front-end-clear', function(e) {
		e.preventDefault();
		var thiz = $( this ),
		url_param = thiz.data( 'param' ),
		form = thiz.closest( 'form' );
		form.find( '.js-wpv-date-param-' + url_param ).html( '' );
		form.find( '.js-wpv-date-front-end-' + url_param ).val( '' );
		thiz.hide();
		form.find('.js-wpv-date-param-' + url_param + '-value' )
			.val( '' )
			.trigger( 'change' );
	});
	
	$( document ).on( 'change', '.js-wpv-post-relationship-update', function() {
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' ),
		additional_forms = $( '.js-wpv-filter-form-' + view_number ).not( fil ),
		currentposttype = thiz.data( 'currentposttype' ),
		watchers = fil.find( '.js-wpv-' + currentposttype + '-watch' ).add( additional_forms.find( '.js-wpv-' + currentposttype + '-watch' ) ),
		watcherslength = watchers.length,
		i;
		if ( watcherslength ) {
			for( i = 0; i < watcherslength; i++ ) {
				$( watchers[i] )
					.attr( 'disabled', true )
					.removeAttr( 'checked' )
					.removeAttr( 'selected' )
					.not( ':button, :submit, :reset, :hidden, :radio, :checkbox' )
					.val( '0' );
			}
		}
		$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ { view_unique_id: view_number, form: fil, force_form_update: true } ] );
	});

	$( document ).on( 'change', '.js-wpv-filter-trigger', function() {
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' );
		$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ { view_unique_id: view_number, form: fil } ] );
	});

	$( document ).on( 'click', '.js-wpv-ajax-results-enabled .js-wpv-submit-trigger, .js-wpv-ajax-results-submit-enabled .js-wpv-submit-trigger', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' );
		$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ { view_unique_id: view_number, form: fil, force_form_update: false, force_results_update: true } ] );
	});

	$( document).on( 'keypress', '.js-wpv-ajax-results-enabled .js-wpv-filter-trigger-delayed, .js-wpv-ajax-results-submit-enabled .js-wpv-filter-trigger-delayed', function( e ) {
		// Enter pressed?
		if ( e.which == 13 ) {
			e.preventDefault();
			var thiz = $( this ),
			fil = thiz.closest( 'form' ),
			view_number = fil.data( 'viewnumber' );
			$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ { view_unique_id: view_number, form: fil, force_results_update: true } ] );
		}
	});

	$( document ).on( 'click', '.js-wpv-reset-trigger', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_number = fil.data( 'viewnumber' ),
		additional_forms = $( '.js-wpv-filter-form-' + view_number ).not( fil ),
		watchers,
		watcherslength,
		i,
		target = fil.attr( 'action' );
		if ( fil.hasClass( 'js-wpv-ajax-results-enabled' ) || fil.hasClass( 'js-wpv-ajax-results-submit-enabled' ) ) {
			watchers = fil.find( 'input, select' ).add( additional_forms.find( 'input, select' ) );
			watcherslength = watchers.length;
			if ( watcherslength ) {
				for ( i = 0; i < watcherslength; i++ ) {
					if ( ! $( watchers[i] ).hasClass( 'js-wpv-keep-on-clear' ) ) {
						$( watchers[i] )
							.attr( 'disabled', true )
							.removeAttr( 'checked' )
							.removeAttr( 'selected' )
							.not( ':button, :submit, :reset, :hidden, :radio, :checkbox' )
							.val( '' );
					}
				}
			}
			$( document ).trigger( 'js_event_wpv_parametric_search_triggered', [ { view_unique_id: view_number, form: fil, force_form_update: true, force_results_update: true } ] );
		} else {
			window.location.href = target;
		}
	});
	
	$( document ).on( 'js_event_wpv_parametric_search_triggered', function( event, data ) {
		var defaults = { 
			force_form_update: false, 
			force_results_update: false
		},
		settings = $.extend( {}, defaults, data );
		self.manage_changed_form( settings.form, settings.force_form_update, settings.force_results_update );
	});

	// Also, stop the rollover if we do any modification on the parametric search form

	$( document ).on( 'change', '.js-wpv-filter-trigger, .js-wpv-filter-trigger-delayed', function() {
		var thiz = $( this ),
		fil = thiz.closest( 'form' ),
		view_num = fil.data( 'viewnumber' );
		wpv_stop_rollover[view_num] = true;
	});
	
	// ------------------------------------
	// Custom events
	// ------------------------------------
	
	/**
	* js_event_wpv_parametric_search_started
	*
	* Event fired before updating the parametric search forms and results.
	*
	* @param data
	* 	- view_unique_id
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_started', function( event, data ) {
		
	});
	
	
	/**
	* js_event_wpv_parametric_search_form_updated
	*
	* Event fired after updating the parametric search forms.
	*
	* @param data
	* 	- view_unique_id
	* 	- view_changed_form
	* 	- view_changed_form_additional_forms_only
	* 	- view_changed_form_additional_forms_full
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_form_updated', function( event, data ) {
		WPViews.view_frontend_utils.render_frontend_datepicker();
	});
	
	/**
	* js_event_wpv_parametric_search_results_updated
	*
	* Event fired after updating the parametric search results.
	*
	* @param data
	* 	- view_unique_id
	* 	- layout
	*
	* @since 1.9
	*/
	
	$( document ).on( 'js_event_wpv_parametric_search_results_updated', function( event, data ) {
		WPViews.view_frontend_utils.render_frontend_media_shortcodes( data.layout );
	});
	
	// ------------------------------------
	// Init
	// ------------------------------------
	
	self.init = function() {
		
	}
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
	WPViews.view_frontend_utils = new WPViews.ViewFrontendUtils( $ );
	WPViews.view_pagination = new WPViews.ViewPagination( $ );
    WPViews.view_parametric_search = new WPViews.ViewParametricSearch( $ );
});