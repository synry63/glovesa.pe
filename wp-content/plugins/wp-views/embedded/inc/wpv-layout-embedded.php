<?php

/*
 
    Shortcode for sorting by the column heading in
    table layout mode.
    
*/

add_shortcode('wpv-heading', 'wpv_header_shortcode');
function wpv_header_shortcode($atts, $value){
    extract(
        shortcode_atts( array(
            'name' => '',
            'style' => '',
            'class' => ''
            ), $atts )
    );

    if (isset($atts['name']) && strpos($atts['name'], 'types-field-')) {
        $atts['name'] = strtolower($atts['name']);
    }
    
    if ( ! empty( $style ) ) {
        $style = ' style="'. esc_attr( $style ).'"';
    }
    if ( ! empty( $class) ) {
        $class = ' ' . esc_attr( $class );
    }
        
    global $WP_Views;
    $view_settings = $WP_Views->get_view_settings();
    
    //'wpv_column_sort_id'
    $order_class = 'wpv-header-no-sort';
    
    if (
		$view_settings['view-query-mode'] == 'normal' 
		&& !empty( $atts['name'] ) 
		&& $atts['name'] != 'post-body' 
		&& $atts['name'] != 'wpv-post-taxonomy' 
		// remove table column sorting for certain fields un Views listing users
		&& ( 
			$view_settings['query_type'][0] != 'users' 
			|| in_array( $atts['name'], array( 'user_email', 'user_login', 'display_name', 'user_url', 'user_registered' ) ) 
		)
    ) {
	
	$head_name = $atts['name'];
	if ( strpos( $head_name, 'types-field') === 0 ) {
		$field_name = 'wpcf-' . strtolower( substr( $head_name, 12 ) );
		if ( !function_exists( '_wpv_is_field_of_type' ) ) include_once( WPV_PATH_EMBEDDED . '/inc/wpv-filter-embedded.php');
		if ( _wpv_is_field_of_type( $field_name, 'checkboxes' ) || _wpv_is_field_of_type( $field_name, 'skype' ) ) {
			return wpv_do_shortcode( $value );
		}
	}

        if (isset($_GET['wpv_column_sort_id']) && esc_attr($_GET['wpv_column_sort_id']) == $atts['name'] && isset($_GET['wpv_view_count']) && $WP_Views->get_view_count() == esc_attr($_GET['wpv_view_count']) ) {
            
            if (isset($_GET['wpv_column_sort_dir']) && esc_attr($_GET['wpv_column_sort_dir']) != '') {
                if (esc_attr($_GET['wpv_column_sort_dir']) == 'asc') {
                    $order_class = 'wpv-header-asc';
                } else {
                    $order_class = 'wpv-header-desc';
                }
            } else {
                // use the default order
                $order_selected = $view_settings['order'];
                if ($order_selected == 'ASC') {
                    $order_class = 'wpv-header-asc';
                } else {
                    $order_class = 'wpv-header-desc';
                }
            }
        }
        if ($order_class == 'wpv-header-asc') {
            $dir = "desc";
        } else {
            $dir = "asc";
        }
        $link = '<a href="#" class="' . $order_class . ' js-wpv-column-header-click'. $class .'"'. $style .' data-viewnumber="' . $WP_Views->get_view_count() . '" data-name="' . $atts['name'] . '" data-direction="' . $dir . '">' . wpv_do_shortcode( $value ) . '<span class="wpv-sorting-indicator"></span></a>';
        return $link;
    } else {
        return wpv_do_shortcode( $value );
    }
}

add_shortcode('wpv-layout-start', 'wpv_layout_start_shortcode');
function wpv_layout_start_shortcode($atts){
    
    global $WP_Views;
    
    $view_settings = $WP_Views->get_view_settings();
	$view_number = $WP_Views->get_view_count();
	$pagination_data = wpv_get_view_pagination_data( $view_settings );
    $class = array( 
		'js-wpv-view-layout', 
		'js-wpv-layout-responsive' 
	);
    $style = array();
	$add = '';
	
    if (
		(
			$view_settings['pagination'][0] == 'enable' 
			&& $view_settings['ajax_pagination'][0] == 'enable'
		)
		|| $view_settings['pagination']['mode'] == 'rollover'
	) {
        $class[] = 'wpv-pagination';
		$class[] = 'js-wpv-layout-has-pagination';
		
		if ( $pagination_data['effect'] == 'infinite' ) {
			$class[] = 'js-wpv-layout-infinite-scrolling';
		}
		
        if ( ! isset( $view_settings['pagination']['preload_images'] ) ) {
            $view_settings['pagination']['preload_images'] = false;
        }
        if ( ! isset( $view_settings['rollover']['preload_images'] ) ) {
            $view_settings['rollover']['preload_images'] = false;
        }
        if (
			(
				$view_settings['pagination']['mode'] == 'paged' 
				&& $view_settings['pagination']['preload_images']
			) || (
				$view_settings['pagination']['mode'] == 'rollover' 
				&& $view_settings['rollover']['preload_images']
			)
		) {
            $class[] = 'wpv-pagination-preload-images';
			$class[] = 'js-wpv-layout-preload-images';
            $style[] = 'visibility:hidden;';
        }
        if (
			(
				$view_settings['pagination']['mode'] == 'paged' 
				&& $view_settings['pagination']['preload_pages']
			) || (
				$view_settings['pagination']['mode'] == 'rollover' 
				&& $view_settings['pagination']['preload_pages']
			)
		) {
            $class[] = 'wpv-pagination-preload-pages';
			$class[] = 'js-wpv-layout-preload-pages';
        }
	}
        
	if ( ! empty( $class ) ) {
		$add .= ' class="' . implode(' ', $class) . '"';
	}
	if ( ! empty( $style ) ) {
		$add .= ' style="' . implode(' ', $style) . '"';
	}
		
	$add .= ' data-viewnumber="' . esc_attr( $view_number ) . '"';
	
	$pagination_data['max_pages'] = intval( $WP_Views->get_max_pages() );
	$pagination_data['page'] = $WP_Views->get_current_page_number();
	
	if ( $pagination_data['effect'] == 'fadeslow' ) {
		$pagination_data['effect'] = 'fade';
		$pagination_data['duration'] = '1500';
	} else if ( $pagination_data['effect'] == 'fadefast' ) {
		$pagination_data['effect'] = 'fade';
		$pagination_data['duration'] = '1';
	}
	
	$return = '<div'
		. ' id="wpv-view-layout-' . esc_attr( $view_number ) . '"'
		. $add
		. ' data-pagination="' . esc_js( wp_json_encode( $pagination_data ) ) . '"'
		. ">\n";
	
	$return .= '<input type="hidden" id="js-wpv-pagination-page-permalink" value="' . esc_url( wpv_get_pagination_page_permalink( $pagination_data['page'], $view_number ) ) . '" />';
	
	return $return;
}

add_shortcode('wpv-layout-end', 'wpv_layout_end_shortcode');
function wpv_layout_end_shortcode($atts){
	return '</div>';
}

add_shortcode('wpv-layout-row', 'wpv_layout_row');
function wpv_layout_row( $atts, $value ){
	extract(
		shortcode_atts( array(
			'framework' => 'bootstrap',
			'cols' => 12,
			'col_options' => '',
		), $atts )
	);
	if ( 'bootstrap' == $framework ) {
		$elements = substr_count( $value, '[wpv-layout-cell-span]' );
		$counter = 1;
		$pattern = array();

		// if we have col_options
		preg_match_all('/\{([^}]*)\}/', $col_options, $pieces);
		foreach($pieces[1] as $match) {
			$piece = explode(',', $match);
			if ( ( count( $piece ) == $elements ) && ( array_sum( $piece ) == $cols ) ) {
				$pattern = $piece;
			}
		}
		while(preg_match('#\\[wpv-layout-cell-span]#', $value, $matches)) {
			$pos = strpos( $value, $matches[0] );
			$len = strlen( $matches[0] );
			if ( 0 < count( $pattern ) ) {
				$value = substr_replace( $value, 'span' . $pattern[$counter - 1], $pos, $len );
				$counter++;
			} elseif ( $counter < $elements ) {
				$counter++;
				$value = substr_replace( $value, 'span' . floor( $cols/$elements ), $pos, $len );
			} else {
				$value = substr_replace( $value, 'span' . ( $cols - ( ( $elements -1 ) * ( floor( $cols/$elements ) ) ) ), $pos, $len );
			}
		}
	}
	
	return wpv_do_shortcode( $value );
        
}

add_shortcode('wpv-layout-meta-html', 'wpv_layout_meta_html');
function wpv_layout_meta_html($atts) {
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;
    $view_layout_settings = $WP_Views->get_view_layout_settings();
    
    if (isset($view_layout_settings['layout_meta_html'])) {
        
        $content = wpml_content_fix_links_to_translated_content($view_layout_settings['layout_meta_html']);
        
        return wpv_do_shortcode($content);
    } else {
        return '';
    }
}