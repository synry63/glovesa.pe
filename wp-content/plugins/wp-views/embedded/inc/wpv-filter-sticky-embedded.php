<?php

/**
 * Add filter to include a "sticky post" clause in the $query
 */
add_filter( 'wpv_filter_query', 'wpv_filter_post_sticky', 900, 2 );

function wpv_filter_post_sticky( $query, $view_settings ) {

    if ( isset( $view_settings['post_sticky'] ) ) {
		$sticky = get_option( 'sticky_posts' ) ? get_option( 'sticky_posts' ) : array();
		switch ( $view_settings['post_sticky'] ) {
			case 'include':
				$query['post__in'] = isset( $query['post__in'] ) ? array_unique( $query['post__in'], $sticky ) : $sticky;
				if ( empty( $query['post__in'] ) ) {
					$query['post__in'] = array( '0' );
				}
				break;
			case 'exclude':
				$query['post__not_in'] = isset( $query['post__in'] ) ? array_merge( $query['post__in'], $sticky ) : $sticky;
				break;
		}
    }

    return $query;
}
