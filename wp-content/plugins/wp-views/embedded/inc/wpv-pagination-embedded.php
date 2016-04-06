<?php
/**
* wpv-pagination-embedded.php
*
* @package Views
*
* @since unknown
*/

// @todo DEPRECATED
add_filter( 'wpv_view_settings_save', 'wpv_pager_defaults_save', 10 );
function wpv_pager_defaults_save( $view_settings ) {
    // we need to set 0 for the checkboxes that aren't checked and are missing for the $_POST.
    $defaults = array(
        'pagination' => array(
            'preload_images' => 0,
            'cache_pages' => 0,
            'preload_pages' => 0,
        ),
        'rollover' => array(
            'preload_images' => 0,
        ),
    );
    $view_settings = wpv_parse_args_recursive( $view_settings, $defaults );
    return $view_settings;
}

function wpv_get_view_pagination_data( $view_settings ) {
	$pagination_data = array();
	// AJAX
	$pagination_data['ajax'] = $view_settings['ajax_pagination'][0] == 'enable' ? 'true' : 'false';
	// AJAX effect
	$pagination_data['effect'] = isset( $view_settings['ajax_pagination']['style'] ) ? $view_settings['ajax_pagination']['style'] : 'fade';
	// AJAX duration
	$pagination_data['duration'] = isset( $view_settings['ajax_pagination']['duration'] ) ? $view_settings['ajax_pagination']['duration'] : '500';
	// Rollover
	$pagination_data['stop_rollover'] = 'false';
	// Adjust for rollover
	if ( $view_settings['pagination']['mode'] == 'rollover' ) {
		$pagination_data['ajax'] = 'true';
		$pagination_data['effect'] = isset( $view_settings['rollover']['effect'] ) ? $view_settings['rollover']['effect'] : $pagination_data['effect'];
		$pagination_data['duration'] = isset( $view_settings['rollover']['duration'] ) ? $view_settings['rollover']['duration'] : $pagination_data['duration'];
		$pagination_data['stop_rollover'] = 'true';
	}
	// Cache & preload
	$pagination_data['cache_pages'] = $view_settings['pagination']['cache_pages'];
	$pagination_data['preload_pages'] = $view_settings['pagination']['preload_pages'];
	$pagination_data['pre_reach'] = ( isset( $view_settings['pagination']['pre_reach'] ) ) ? $view_settings['pagination']['pre_reach'] : '1';
	// Spinner & spinner image
	$pagination_data['spinner'] = ( isset( $view_settings['pagination']['spinner'] ) ) ? $view_settings['pagination']['spinner'] : 'no';
	$pagination_data['spinner_image'] = ( isset( $view_settings['pagination']['spinner_image'] ) ) ? $view_settings['pagination']['spinner_image'] : '';
	// $spinner_image might contain SSL traces, adjust if needed
	if ( ! is_ssl() ) {
		$pagination_data['spinner_image'] = str_replace( 'https://', 'http://', $pagination_data['spinner_image'] );
	}
	// Callback next
	$pagination_data['callback_next'] = ( isset( $view_settings['pagination']['callback_next'] ) ) ? $view_settings['pagination']['callback_next'] : '';
	
	return $pagination_data;
}

/**
* Views-Shortcode: wpv-pager-current-page
*
* Description: Display the current page number. It can be displayed as a single number
* or as a drop-down list or series of dots to select another page.
*
* Parameters:
* 'style' => leave empty to display a number.
* 'style' => 'drop_down' to display a selector to select another page.
* 'style' => 'link' to display a series of links to each page
*
* Example usage:
*
* Link:
*
* Note:
*
*/

add_shortcode( 'wpv-pager-current-page', 'wpv_pager_current_page_shortcode' );

function wpv_pager_current_page_shortcode( $atts ) {
    extract(
        shortcode_atts(
			array(
				'force'		=> 'false'
			), 
			$atts
		)
    );

    global $WP_Views;
	$view_id = $WP_Views->get_current_view();
    
    if ( $WP_Views->get_max_pages() <= 1.0 ) {
        return ( $force == 'true' ) ? '1' : '';
    }

    $page = $WP_Views->get_current_page_number();

    if ( isset( $atts['style'] ) ) {
		
		/**
		* Deprecated on Views 1.11, keep for backwards compatibility
		*/
        
        $view_settings = $WP_Views->get_view_settings();
        $cache_pages = $view_settings['pagination']['cache_pages'];
        $preload_pages = $view_settings['pagination']['preload_pages'];
        $spinner = $view_settings['pagination']['spinner'];
        $spinner_image = $view_settings['pagination']['spinner_image'];
		// $spinner_image might contain SSL traces, adjust if needed
		if ( ! is_ssl() ) {
			$spinner_image = str_replace( 'https://', 'http://', $spinner_image );
		}
        $callback_next = $view_settings['pagination']['callback_next'];
        
        if ( $view_settings['pagination']['mode'] == 'paged' ) {
            $ajax = $view_settings['ajax_pagination'][0] == 'enable' ? 'true' : 'false';
            $effect = isset( $view_settings['ajax_pagination']['style'] ) ? $view_settings['ajax_pagination']['style'] : 'fade';
        }
        
        if ( $view_settings['pagination']['mode'] == 'rollover' ) {
            $ajax = 'true';
            $effect = $view_settings['rollover']['effect'];
            // convert rollover to slide effect if the user clicks on a page.
            
            if ( $effect == 'slideleft' || $effect == 'slideright' ) {
                $effect = 'slideh';
            }
            if ( $effect == 'slideup' || $effect == 'slidedown' ) {
                $effect = 'slidev';
            }
        }

        switch( $atts['style'] ) {
            case 'drop_down':
                $out = '';
                $out .= '<select id="wpv-page-selector-' . $WP_Views->get_view_count() . '" class="js-wpv-page-selector" data-viewnumber="' . $WP_Views->get_view_count() . '">' . "\n";
        
                $max_page = intval( $WP_Views->get_max_pages() );
                for ($i = 1; $i < $max_page + 1; $i++) {
                    $is_selected = $i == $page ? ' selected="selected"' : '';
                    $page_number = apply_filters( 'wpv_pagination_page_number', $i, $atts['style'], $view_id ) ;
                    $out .= '<option value="' . $i . '" ' . $is_selected . '>' . $page_number . "</option>\n";
                }
                $out .= "</select>\n";
        
                return $out;
                    
            case 'link':
                $page_count = intval( $WP_Views->get_max_pages() );
                // output a series of dots linking to each page.
                $classname = '';
                $out = '<div class="wpv_pagination_links">';
				$classname = 'wpv_pagination_dots';
				$classname = apply_filters( 'wpv_pagination_container_classname', $classname, $atts['style'], $view_id );
				$out .= '<ul class="' . $classname . '">';
                
                for ( $i = 1; $i < $page_count + 1; $i++ ) {
                    $page_title = sprintf( __( 'Page %s', 'wpv-views' ), $i );
                    $page_title = esc_attr( apply_filters( 'wpv_pagination_page_title', $page_title, $i, $atts['style'], $view_id ) );
                    $page_number = apply_filters( 'wpv_pagination_page_number', $i, $atts['style'], $view_id );
                    $link = '<a title="' . $page_title . '" href="#" class="wpv-filter-pagination-link js-wpv-pagination-link" data-viewnumber="' . $WP_Views->get_view_count() . '" data-page="' . $i . '">' . $page_number . '</a>';
                    $link_id = 'wpv-page-link-' . $WP_Views->get_view_count() . '-' . $i;
                    $item = '';
					if ( $i == $page ) {
                        $item .= '<li id="' . $link_id . '" class="' . $classname . '_item wpv_page_current">' . $link . '</li>';
                    } else {
                        $item .= '<li id="' . $link_id . '" class="' . $classname . '_item">' . $link . '</li>';
                    }
					$item = apply_filters( 'wpv_pagination_page_item', $item, $i, $page, $page_count, $atts['style'], $view_id );
					$out .= $item;
                }
                $out .= '</ul>';
                $out .= '</div>';
                //$out .= '<br />'; NOTE: this extra br tag was removed in Views 1.5
                return $out;

        }
    } else {
        // show the page number.
        return sprintf( '%d', $page );
    }
}

/**
* WPV_Pagination_Embedded
*
* @since 1.11
*/

class WPV_Pagination_Embedded {
	
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );		
    }
	
	function init() {
		$this->register_shortcodes();
		
		add_filter( 'wpv_view_settings', array( $this, 'pagination_defaults' ), 10 );
		
		// add a filter so we can set the correct language in WPML during pagination
		add_filter( 'icl_current_language', array( $this, 'wpv_ajax_pagination_lang' ) );
	}
	
	function register_shortcodes() {
		add_shortcode( 'wpv-pagination', array( $this, 'wpv_pagination_shortcode_callback' ) );
		
		add_shortcode( 'wpv-pager-num-page', array( $this, 'wpv_pager_total_pages_shortcode' ) );
		add_shortcode( 'wpv-pager-total-pages', array( $this, 'wpv_pager_total_pages_shortcode' ) );
		
		add_shortcode( 'wpv-pager-prev-page', array( $this, 'wpv_pager_prev_page_callback' ) );
		add_shortcode( 'wpv-pager-next-page', array( $this, 'wpv_pager_next_page_callback' ) );
		
		add_shortcode( 'wpv-pager-nav-dropdown', array( $this, 'wpv_pager_nav_dropdown_callback' ) );
		add_shortcode( 'wpv-pager-nav-links', array( $this, 'wpv_pager_nav_links_callback' ) );
		
		//add_shortcode( 'wpv-pager-pause-rollover', array( $this, 'wpv_pager_pause_rollover_callback' ) );
		//add_shortcode( 'wpv-pager-resume-rollover', array( $this, 'wpv_pager_resume_rollover_callback' ) );
	}
	
	function pagination_defaults( $view_settings ) {
		$defaults = array(
			'posts_per_page' => 10,
			'pagination' => array(
				'mode' => 'paged',
				'preload_images' => 1,
				'cache_pages' => 1,
				'preload_pages' => 1,
				'spinner' => 'default',
				'spinner_image' => WPV_URL_EMBEDDED . '/res/img/ajax-loader.gif',
				'spinner_image_uploaded' => '',
				'callback_next' => '',
				'page_selector_control_type' => 'drop_down',
			),
			'ajax_pagination' => array(
				'style' => 'fade',
			),
			'rollover' => array(
				'posts_per_page' => 1,
				'speed' => 5,
				'effect' => 'fade',
				'preload_images' => 1,
				'include_page_selector' => 0,
				'include_prev_next_page_controls' => 0,
			),
		);
		$view_settings = wpv_parse_args_recursive( $view_settings, $defaults );
		// Move the 0-indexed items out of the recursive parsing: it breaks!
		if ( ! isset( $view_settings['pagination'][0] ) ) {
			$view_settings['pagination'][0] = 'disable';
		}
		if ( ! isset( $view_settings['ajax_pagination'][0] ) ) {
			$view_settings['ajax_pagination'][0] = 'disable';
		}
		if ( $view_settings['pagination']['spinner'] == 'uploaded' ) {
			$view_settings['pagination']['spinner_image'] = $view_settings['pagination']['spinner_image_uploaded'];
		}

		return $view_settings;
	}

	function wpv_pagination_shortcode_callback( $atts, $value ) {
		extract(
			shortcode_atts(
				array(), 
				$atts
			)
		);
		global $WP_Views;
		if ( $WP_Views->get_max_pages() > 1.0 ) {
			return wpv_do_shortcode( $value );
		} else {
			return '';
		}
	}

	function wpv_pager_total_pages_shortcode( $atts ) {
		extract(
			shortcode_atts(
				array(
					'force'	=> 'false'
				), 
				$atts
			)
		);
		global $WP_Views;
		if ( $WP_Views->get_max_pages() > 1.0 ) {
			return sprintf( '%1.0f', $WP_Views->get_max_pages() );
		} else {
			return ( $force == 'true' ) ? '1' : '';
		}
	}

	function wpv_pager_prev_page_callback( $atts, $value ) {
		extract(
			shortcode_atts(
				array(
					'style' => '',
					'class' => '',
					'force'	=> 'false'
				),
				$atts
			)
		);
		
		global $WP_Views;
		$page = $WP_Views->get_current_page_number();
		$max_page = intval( $WP_Views->get_max_pages() );
		$view_settings = $WP_Views->get_view_settings();
		
		$display = false;
		if ( 
			$max_page > 1.0 
			&& (
				$view_settings['pagination']['mode'] == 'rollover' 
				|| $page > 1
			)
		) {
			$display = true;
		}
		
		if ( ! empty( $class) ) {
			$class = ' ' . esc_attr( $class );
		}
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style )  .'"';
		}

		if ( $display ) {
			$page--;
			$value = wpv_do_shortcode( $value );
			
			$pagination_data = wpv_get_view_pagination_data( $view_settings );
			$view_count = $WP_Views->get_view_count();
			
			if ( $view_settings['pagination']['mode'] == 'rollover' ) {
				if ( $pagination_data['effect'] == 'slideleft' ) {
					$pagination_data['effect'] = 'slideright';
				} else if ( $pagination_data['effect'] == 'slidedown' ) {
					$pagination_data['effect'] = 'slideup';
				}
			}
			
			if ( $page <= 0 ) {
				$page = $max_page;
			} else if ( $page > $max_page ) {
				$page = 1;
			}
			
			$return = '<a'
				. ' class="wpv-filter-previous-link js-wpv-pagination-previous-link'. $class .'"' . $style 
				. ' href="'					. esc_url( $this->get_pager_permalink( $page, $view_count ) ) . '"'
				. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
				. ' data-page="' 			. esc_attr( $page ) . '"'
				. '>' 
				. $value 
				. '</a>';
				
			return $return;
		} else {
			if ( $force == 'true' ) {
				$value = wpv_do_shortcode( $value );
				return '<span class="wpv-filter-previous-link' . $class . '"' . $style . '>' . $value . '</span>';
			} else {
				return '';
			}
		}
	}

	function wpv_pager_next_page_callback( $atts, $value ) {
		extract(
			shortcode_atts(
				array(
					'style' => '',
					'class' => '',
					'force'	=> 'false'
				), 
				$atts
			)
		);

		global $WP_Views;
		$page = $WP_Views->get_current_page_number();
		$max_page = intval( $WP_Views->get_max_pages() );
		$view_settings = $WP_Views->get_view_settings();
		
		$display = false;
		if ( 
			$max_page > 1.0 
			&& (
				$view_settings['pagination']['mode'] == 'rollover'
				|| $page < $max_page
			)
		) {
			$display = true;
		}
		
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style ) .'"';
		}
		if ( ! empty( $class ) ) {
			$class = ' ' . esc_attr( $class );
		} 

		if ( $display ) {
			$page++;
			$value = wpv_do_shortcode( $value );
			
			$pagination_data = wpv_get_view_pagination_data( $view_settings );
			$view_count = $WP_Views->get_view_count();
			
			if ( $view_settings['pagination']['mode'] == 'rollover' ) {
				if ( $pagination_data['effect'] == 'slideright' ) {
					$pagination_data['effect'] = 'slideleft';
				} else if ( $pagination_data['effect'] == 'slideup' ) {
					$pagination_data['effect'] = 'slidedown';
				}
			}
			
			if ( $page <= 0 ) {
				$page = $max_page;
			} else if ( $page > $max_page ) {
				$page = 1;
			}
			
			$return = '<a'
				. ' class="wpv-filter-next-link js-wpv-pagination-next-link'. $class . '"' . $style 
				. ' href="'					. esc_url( $this->get_pager_permalink( $page, $view_count ) ) . '"'
				. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
				. ' data-page="' 			. esc_attr( $page ) . '"'
				.'>' 
				. $value 
				. '</a>';
				
			return $return;
		} else {
			if ( $force == 'true' ) {
				$value = wpv_do_shortcode( $value );
				return '<span class="wpv-filter-next-link' . $class . '"' . $style . '>' . $value . '</span>';
			} else {
				return '';
			}
		}
	}
	
	/**
	* [wpv-pager-nav-dropdown]
	*
	* Displays a select dropdown for Views pagination
	*
	* @param class
	*
	* @since 1.11
	*
	* @todo remember that the classname js-wpv-pagination-selector does nothing as of now...
	*/
	
	function wpv_pager_nav_dropdown_callback( $atts ) {
		extract(
			shortcode_atts(
				array(
					'class'	=> '',
				), 
				$atts
			)
		);
		
		global $WP_Views;
		$return = '';
		
		$view_settings = $WP_Views->get_view_settings();
		$view_count = $WP_Views->get_view_count();
		$pagination_data = wpv_get_view_pagination_data( $view_settings );
		$max_page = intval( $WP_Views->get_max_pages() );
		$page = $WP_Views->get_current_page_number();
		
		if ( ! empty( $class ) ) {
			$class = ' ' . $class;
		}
		
		$return .= '<select id="wpv-page-selector-' . esc_attr( $view_count ) . '"' 
			. ' class="js-wpv-page-selector' . $class . '"'
			. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
			. '>';
		for ( $i = 1; $i < $max_page + 1; $i++ ) {
			$return .= '<option value="' . esc_attr( $i ) . '" ' . selected( $i, $page, false ) . '>' . esc_html( $i ) . '</option>';
		}
		$return .= '</select>';
		
		return $return;
	}
	
	/**
	* [wpv-pager-nav-links]
	*
	* @param wrapper
	* @param anchor_text
	* @param anchor_title
	*
	* @since 1.11
	*/
	
	function wpv_pager_nav_links_callback( $atts ) {
		extract(
			shortcode_atts(
				array(
					'ul_class'		=> '',
					'li_class'		=> '',
					'current_type'	=> 'text',
					
					'anchor_text'	=> __( '%%PAGE%%', 'wpv-views' ),
					'anchor_title'	=> __( '%%PAGE%%', 'wpv-views' ),
					
					'sticky_first'			=> 'true',
					'sticky_last'			=> 'true',
					'step'					=> false,
					'reach'					=> false,
					'ellipsis'				=> '...'
				), 
				$atts
			)
		);
		
		global $WP_Views;
		$return = '';
		
		$view_settings = $WP_Views->get_view_settings();
		$view_count = $WP_Views->get_view_count();
		$pagination_data = wpv_get_view_pagination_data( $view_settings );
		$max_page = intval( $WP_Views->get_max_pages() );
		$page = $WP_Views->get_current_page_number();
		
		if ( ! empty( $ul_class ) ) {
			$ul_class = ' class="' . esc_attr( $ul_class ) . '"';
		}
		$li_class_array = array();
		if ( ! empty( $li_class ) ) {
			$li_class_array = array_map( 'esc_attr', explode( ' ', $li_class ) );
		}
		$li_class_string = ( empty( $li_class_array ) ) ? '' : ' class="' . implode( ' ', $li_class_array ) . '"';
		
		$step = ( $step === false ) ? $step : intval( $step );
		$reach = ( $reach === false ) ? $reach : intval( $reach );
		$needs_ellipsis = true;
		
		$return .= '<ul' . $ul_class . '>';
                
		for ( $i = 1; $i < $max_page + 1; $i++ ) {
			$is_visible = false;
			if ( 
				(
					$i == 1 
					&& $sticky_first == 'true'
				) || (
					$i == $max_page 
					&& $sticky_last == 'true'
				)
			) {
				$is_visible = true;
			}
			if ( $step === false ) {
				if ( $reach === false ) {
					$is_visible = true;
				} else {
					if (
						( $i >= ( $page - $reach ) )
						&& ( $i <= ( $page + $reach ) )
					) {
						$is_visible = true;
					}
				}
			} else {
				if ( $i % $step == 0 ) {
					$is_visible = true;
				}
				if ( 
					$reach !== false 
					&& ( $i >= ( $page - $reach ) )
					&& ( $i <= ( $page + $reach ) )
				) {
					$is_visible = true;
 				}
			}
			if ( $is_visible ) {
				$needs_ellipsis = true;
				$anchor_text_i = str_replace( '%%PAGE%%', $i, $anchor_text );
				$anchor_title_i = str_replace( '%%PAGE%%', $i, $anchor_title );
				$li_current_id = 'wpv-page-link-' . $view_count . '-' . $i;
				$li_current_class_array = $li_class_array;
				
				$li_current_content = '<a'
					. ' class="wpv-filter-pagination-link js-wpv-pagination-link"'
					. ' title="'				. $anchor_title_i . '"'
					. ' href="'					. esc_url( $this->get_pager_permalink( $i, $view_count ) ) . '"'
					. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
					. ' data-page="' 			. esc_attr( $i ) . '"'
					. '>'
					. $anchor_text_i 
					. '</a>';
				
				if ( $i == $page ) {
					$li_current_class_array[] = 'wpv_page_current';
					if ( $current_type == 'text' ) {
						$li_current_content = '<span'
						. ' class="wpv-filter-pagination-link"'
						. '>'
						. $anchor_text_i
						. '</span>';
					}
				}
				
				$li_current_class_string = ( empty( $li_current_class_array ) ) ? '' : ' class="' . implode( ' ', $li_current_class_array ) . '"';
				$return  .= '<li id="' . esc_attr( $li_current_id ) . '"' . $li_current_class_string . '>' 
					. $li_current_content 
					. '</li>';
			} else if ( $needs_ellipsis ) {
				$needs_ellipsis = false;
				$return  .= '<li' . $li_class_string . '>' 
					. '<span class="wpv_page_ellipsis">' . $ellipsis . '</span>'
					. '</li>';
			}
		}
		
		$return .= '</ul>';
		
		return $return;
	}
	
	function wpv_pager_pause_rollover_callback( $atts, $content ) {
		extract(
			shortcode_atts(
				array(
					'style' => '',
					'class' => '',
					'force'	=> 'false'
				),
				$atts
			)
		);
		global $WP_Views;
		$view_count = $WP_Views->get_view_count();
		$content = wpv_do_shortcode( $content );
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style ) .'"';
		}
		if ( ! empty( $class ) ) {
			$class = ' ' . esc_attr( $class );
		} 
			
		$return = '<a'
			. ' class="wpv-filter-previous-link js-wpv-pagination-pause-rollover'. $class .'"' . $style 
			. ' href="#"'
			. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
			. '>' 
			. $content 
			. '</a>';
			
		return $return;
	}
	
	function wpv_pager_resume_rollover_callback( $atts, $content ) {
		extract(
			shortcode_atts(
				array(
					'style' => '',
					'class' => '',
					'force'	=> 'false'
				),
				$atts
			)
		);
		global $WP_Views;
		$view_count = $WP_Views->get_view_count();
		$content = wpv_do_shortcode( $content );
		if ( ! empty( $style ) ) {
			$style = ' style="'. esc_attr( $style ) .'"';
		}
		if ( ! empty( $class ) ) {
			$class = ' ' . esc_attr( $class );
		} 
			
		$return = '<a'
			. ' class="wpv-filter-previous-link js-wpv-pagination-resume-rollover'. $class .'"' . $style 
			. ' href="#"'
			. ' data-viewnumber="' 		. esc_attr( $view_count ) . '"'
			. '>' 
			. $content 
			. '</a>';
			
		return $return;
	}

	function wpv_ajax_pagination_lang( $lang ) {
		if (
			isset( $_POST['action'] ) 
			&& esc_attr( $_POST['action'] ) == 'wpv_get_page' 
			&& isset( $_POST['lang'] )
		) {
			$lang = esc_attr( $_POST['lang'] );
		}
		return $lang;
	}
	
	function get_pager_permalink( $page, $view_hash ) {
		global $WP_Views;
		$view_id				= $WP_Views->get_current_view();
		$view_url_data			= get_view_allowed_url_parameters( $view_id );
		$view_url_parameters	= wp_list_pluck( $view_url_data, 'attribute' );
		$view_url_parameters[]	= 'lang';
		$view_url_parameters[]	= 'wpv_column_sort_id';
		$view_url_parameters[]	= 'wpv_column_sort_dir';
		$view_url_parameters[]	= 'wpv_post_id';
		$view_url_parameters[]	= 'wpv_aux_parent_term_id';
		$view_url_parameters[]	= 'wpv_aux_parent_user_id';
		$origin					= false;
		$url_request			= $_SERVER['REQUEST_URI'];
		$query_args				= array();
		$query_args_remove		= array();
		
		if ( 
			strpos( $url_request, 'wpv-ajax-pagination' ) !== false
			|| ( 
				defined( 'DOING_AJAX' )
				&& DOING_AJAX
				&& isset( $_REQUEST['action'] )
				&& $_REQUEST['action'] == 'wpv_update_parametric_search'
			) 
		) {
			$origin = wp_get_referer();
		}
		
		foreach ( $view_url_parameters as $param ) {
			if ( isset( $_GET[ $param ] ) ) {
				$query_args[ $param ] = $_GET[ $param ];
			} else {
				$query_args_remove[] = $param;
			}
		}
		$query_args['wpv_view_count']	= $view_hash;
		$query_args['wpv_paged']		= $page;
		
		$url = add_query_arg(
			$query_args,
			$origin
		);
		
		$url = remove_query_arg(
			$query_args_remove,
			$url
		);
		
		// Avoid problems with array-ed posted data
		// We must remove the numeric indexes, or the history API will add them and break further AJAX calls
		$url = preg_replace( '/\[(\d+)]/is', '[]', $url );
		$url = preg_replace( '/%5B(\d+)%5D/is', '%5B%5D', $url );
		
		return $url;
	}
	
}

global $WPV_Pagination_Embedded;
$WPV_Pagination_Embedded = new WPV_Pagination_Embedded();

function wpv_pagination_rollover_shortcode() {
    global $WP_Views;
    $view_settings = $WP_Views->get_view_settings();
    $view_settings['rollover']['count'] = $WP_Views->get_max_pages();
    wpv_pagination_rollover_add_slide( $WP_Views->get_view_count(), $view_settings );
    add_action( 'wp_footer', 'wpv_pagination_rollover_js', 30 ); // Set priority higher than 20, when all the footer scripts are loaded
}

function wpv_pagination_rollover_add_slide( $id, $settings = array() ) {
    static $rollovers = array();
    if ( $id == 'get' ) {
        return $rollovers;
    }
    $rollovers[$id] = $settings;
}

// @todo - refactor and pass only the data, manage it on the pagination script directly
function wpv_pagination_rollover_js() {
    $rollovers = wpv_pagination_rollover_add_slide( 'get' );
	$rollovers_ids = array();
    if ( ! empty( $rollovers ) ) {
        global $WP_Views;
        $out = '';
        ?>
        <script type="text/javascript">
			var WPViews = WPViews || {};
            jQuery( document ).ready( function() {
				<?php
				foreach ( $rollovers as $id => $rollover ) {
					// Make sure we have all the needed data
					if ( 
						! isset( $rollover['rollover']['effect'] ) 
						|| empty ( $rollover['rollover']['effect'] )
					) {
						$rollover['rollover']['effect'] = 'fade';
					}
					if ( 
						! isset( $rollover['rollover']['speed'] ) 
						|| empty( $rollover['rollover']['speed'] )
					) {
						$rollover['rollover']['speed'] = 5;
					}
					if ( ! isset( $rollover['rollover']['count'] ) ) {
						$rollover['rollover']['count'] = 0;
					}
					$out .= 'jQuery("#wpv-view-layout-' . $id . '").wpvRollover({'
							. 'id: "'		. $id . '", '
							. 'effect: "'	. esc_js( $rollover['rollover']['effect'] ) . '", '
							. 'speed: '		. esc_js( $rollover['rollover']['speed'] ) . ', '
							. 'page: 1'		. ', '
							. 'count: '		. esc_js( $rollover['rollover']['count'] )
							. '});'
							. "\r\n";
					
					$rollovers_ids[] = $id;
				}
				$out .= "WPViews.rollower_ids = " . json_encode( $rollovers_ids ) . ";\r\n";
				echo $out;
				?>
            });
        </script>
        <?php
    }
}



// Gets the new page for a view.

function wpv_ajax_get_page( $post_data ) {
    global $WP_Views, $post, $authordata, $id;
    
    // Fix a problem with WPML using cookie language when DOING_AJAX is set.
    $cookie_lang = null;
    if (
		isset( $_COOKIE['_icl_current_language'] ) 
		&& isset( $post_data['lang'] )
	) {
        $cookie_lang = $_COOKIE['_icl_current_language'];
        $_COOKIE['_icl_current_language'] = $post_data['lang'];
    }
    
    // Switch WPML to the correct language.
    if ( isset( $post_data['lang'] ) ) {
        global $sitepress;
        if ( method_exists( $sitepress, 'switch_lang' ) ) {
            $sitepress->switch_lang( $post_data['lang'] );
        }
    }


    $_GET['wpv_paged'] = intval( esc_attr( $post_data['page'] ) );
    $_GET['wpv_view_count'] = esc_attr( $post_data['view_number'] );
    if (
		isset( $post_data['wpv_column_sort_id'] ) 
		&& esc_attr( $post_data['wpv_column_sort_id'] ) != 'undefined' 
		&& esc_attr( $post_data['wpv_column_sort_id'] ) != '' 
	) {
        $_GET['wpv_column_sort_id'] = esc_attr( $post_data['wpv_column_sort_id'] );
    }
    if (
		isset( $post_data['wpv_column_sort_dir'] ) 
		&& esc_attr( $post_data['wpv_column_sort_dir'] ) != 'undefined' 
		&& esc_attr( $post_data['wpv_column_sort_dir'] ) != ''
	) {
        $_GET['wpv_column_sort_dir'] = esc_attr( $post_data['wpv_column_sort_dir'] );
    }
    
	// $post_data['get_params'] holds arbitrary URL parameters from the page triggering the pagination
	// We have a hacky solution to keep array URL parameters 
	// by using the flag ##URLARRAYVALHACK## as the glue of the imploded array
    if ( isset( $post_data['get_params'] ) ) {
        foreach( $post_data['get_params'] as $key => $param ) {
            if ( ! isset( $_GET[$key] ) ) {
                $param_san = esc_attr( $param );
				// @hack alert!! We can not avoid this :-(
				if ( strpos( $param_san, '##URLARRAYVALHACK##' ) !== false ) {
					$_GET[$key] = explode( '##URLARRAYVALHACK##', $param_san );
				} else {
					$_GET[$key] = $param_san;
				}
            }
        }
    }
    
	// In other $post_data items, we are keeping the [] brackets for array flagging
    if ( isset( $post_data['dps_pr'] ) ) {
		foreach ( $post_data['dps_pr'] as $dps_pr_item ) {
			if ( is_array( $dps_pr_item ) && isset( $dps_pr_item['name'] ) && isset( $dps_pr_item['value'] ) ) {
				if ( strlen( $dps_pr_item['name'] ) < 2 ) {
					if ( !isset( $_GET[$dps_pr_item['name']] ) ) {
						$_GET[$dps_pr_item['name']] = esc_attr( $dps_pr_item['value'] );
					}
				} else {
					if ( strpos( $dps_pr_item['name'], '[]' ) === strlen( $dps_pr_item['name'] ) - 2 ) {
						$name = str_replace( '[]', '', $dps_pr_item['name'] );
						if ( !isset( $_GET[$name] ) ) {
							$_GET[$name] = array( esc_attr( $dps_pr_item['value'] ) );
						} else if ( is_array( $_GET[$name] ) ) {
							$_GET[$name][] = esc_attr( $dps_pr_item['value'] );
						}
					} else {
						if ( !isset( $_GET[$dps_pr_item['name']] ) ) {
							$_GET[$dps_pr_item['name']] = esc_attr( $dps_pr_item['value'] );
						}
					}
				}
			}
		}
    }
    
    if ( isset( $post_data['dps_general'] ) ) {
		$corrected_item = array();
		foreach ( $post_data['dps_general'] as $dps_pr_item ) {
			if ( is_array( $dps_pr_item ) && isset( $dps_pr_item['name'] ) && isset( $dps_pr_item['value'] ) ) {
				if ( strlen( $dps_pr_item['name'] ) < 2 ) {
					$_GET[$dps_pr_item['name']] = esc_attr( $dps_pr_item['value'] );
				} else {
					if ( strpos( $dps_pr_item['name'], '[]' ) === strlen( $dps_pr_item['name'] ) - 2 ) {
						$name = str_replace( '[]', '', $dps_pr_item['name'] );
						if ( !in_array( $name, $corrected_item ) ) {
							$corrected_item[] = $name;
							if ( isset( $_GET[$name] ) ) {
								unset( $_GET[$name] );
							}
						}
						if ( !isset( $_GET[$name] ) ) {
							$_GET[$name] = array( esc_attr( $dps_pr_item['value'] ) );
						} else if ( is_array( $_GET[$name] ) ) {
							$_GET[$name][] = esc_attr( $dps_pr_item['value'] );
						}
					} else {
						$_GET[$dps_pr_item['name']] = esc_attr( $dps_pr_item['value'] );
					}
				}
			}
		}
    }

	$view_data = json_decode( base64_decode( $post_data['view_hash'] ), true );
	
	// Adjust wpv_post_id, wpv_aux_parent_term_id, wpv_aux_parent_user_id
	// Needed for filters based on the current page or on nested Views

    if ( 
		isset( $post_data['post_id'] ) 
		&& is_numeric( $post_data['post_id'] )
	) {
		$_GET['wpv_post_id'] = esc_attr( $post_data['post_id'] );
        $post_id = esc_attr( $post_data['post_id'] );
        $post = get_post( $post_id );
        $authordata = new WP_User( $post->post_author );
        $id = $post->ID;
    }
	
	if ( 
		isset( $post_data['wpv_aux_parent_term_id'] ) 
		&& is_numeric( $post_data['wpv_aux_parent_term_id'] )
	) {
		$_GET['wpv_aux_parent_term_id'] = esc_attr( $post_data['wpv_aux_parent_term_id'] );
        $WP_Views->parent_taxonomy = esc_attr( $post_data['wpv_aux_parent_term_id'] );
    }
	
	if ( 
		isset( $post_data['wpv_aux_parent_user_id'] ) 
		&& is_numeric( $post_data['wpv_aux_parent_user_id'] )
	) {
		$_GET['wpv_aux_parent_user_id'] = esc_attr( $post_data['wpv_aux_parent_user_id'] );
        $WP_Views->parent_user = esc_attr( $post_data['wpv_aux_parent_user_id'] );
    }

    if ( esc_attr( $post_data['wpv_view_widget_id'] ) == 0 ) {
        // set the view count so we return the right view number after rendering.
        $view_id = $WP_Views->get_view_id( $view_data );
        $WP_Views->set_view_count( intval( esc_attr( $post_data['view_number'] ) ), $view_id );
        echo $WP_Views->short_tag_wpv_view( $view_data );
    } else {
        // set the view count so we return the right view number after rendering.
        $WP_Views->set_view_count( intval( esc_attr( $post_data['view_number'] ) ), esc_attr( $post_data['wpv_view_widget_id'] ) );
        $widget = new WPV_Widget();
        $args = array(
			'before_widget' => '',
            'before_title' => '',
            'after_title' => '',
            'after_widget' => ''
		);
        $widget->widget(
			$args, 
			array(
				'title' => '',
                'view' => esc_attr( $post_data['wpv_view_widget_id'] )
			)
		);
        echo $WP_Views->get_max_pages();
    }

    if ( $cookie_lang ) {
        // reset language cookie.
        $_COOKIE['_icl_current_language'] = $cookie_lang;
    }
}

function wpv_get_pagination_page_permalink( $page, $view_count ) {
	global $WPV_Pagination_Embedded;
	return $WPV_Pagination_Embedded->get_pager_permalink( $page, $view_count );
}

/**
* wpv_pagination_router
*
* Renders the requested page for the requested View
*
* Check if the current loaded URL contains 'wpv-ajax-pagination' and if so load the View page requested
*
* @since unknown
*
* @note using a priority of 1 in the template_redirect action so we fire this early and no other can call this a 404
*/

add_action( 'template_redirect', 'wpv_pagination_router', 1 );

function wpv_pagination_router() {
    $bits = explode( "/", esc_attr( $_SERVER['REQUEST_URI'] ) );
    for ( $i = 0; $i < count( $bits ) - 1; $i++ ) {
        if ( $bits[$i] == 'wpv-ajax-pagination' ) {
            // get the post data. It's hex encoded json
            $post_data = $bits[$i + 1];
            $post_data = pack( 'H*', $post_data );
            
            $post_data = json_decode( $post_data, true );
            $charset = get_bloginfo( 'charset' );
			
			global $wp_query;
			if ( $wp_query->is_404 ) {
                $wp_query->is_404 = false;
            }
            
            header( 'HTTP/1.1 200 OK' );
            header( 'Content-Type: text/html;charset=' . $charset );
            echo '<html><body>';
            
            wpv_ajax_get_page( $post_data );
            
            echo '</body></html>';
            
            exit;
        }
    }
}
