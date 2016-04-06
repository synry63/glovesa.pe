<?php
if ( ! function_exists( 'ref_enqueue_main_stylesheet' ) ) {
	function ref_enqueue_main_stylesheet() {
		if ( ! is_admin() ) {
			wp_enqueue_style( 'main', get_template_directory_uri() . '/style.css', array(), null );
			wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array(), null );
		}
	}
	add_action( 'wp_enqueue_scripts', 'ref_enqueue_main_stylesheet', 100 );
}


/**************************************************
 * Load custom cells types for Layouts plugin from the /dd-layouts-cells/ directory
 **************************************************/
if ( class_exists( 'WPDD_Layouts' ) && !function_exists( 'include_ddl_layouts' ) ) {

	function include_ddl_layouts( $tpls_dir = '' ) {
		$dir_str = dirname( __FILE__ ) . $tpls_dir;
		$dir     = opendir( $dir_str );

		while ( ( $currentFile = readdir( $dir ) ) !== false ) {
			if ( is_file( $dir_str . $currentFile ) ) {
				include $dir_str . $currentFile;
			}
		}
		closedir( $dir );
	}

	include_ddl_layouts( '/dd-layouts-cells/' );
}
// load custom font
add_action('wp_print_styles', 'load_fonts',10);
function load_fonts() {
    wp_register_style('Comfortaa', 'https://fonts.googleapis.com/css?family=Comfortaa:400,300,700,800');
    wp_enqueue_style( 'Comfortaa');

    wp_register_style('Dosis', 'https://fonts.googleapis.com/css?family=Dosis:400,700,600,500');
    wp_enqueue_style( 'Dosis');
}
add_filter('wp_enqueue_scripts', 'enqueue_my_scripts', 20);
function enqueue_my_scripts() {
    //var_dump('my id '. get_the_ID());

    //slick
    wp_enqueue_style( 'slick-css',get_stylesheet_directory_uri() . '/libs/slick/slick.css');
    wp_enqueue_style( 'slick-theme-css',get_stylesheet_directory_uri() . '/libs/slick/slick-theme.css');
    wp_enqueue_script( 'slick-js', get_stylesheet_directory_uri() . '/libs/slick/slick.min.js', array('jquery'), '1.0.0', true );

    //zoom
    wp_enqueue_script( 'elevatezoom-js', get_stylesheet_directory_uri() . '/libs/elevatezoom/jquery.elevatezoom.js', array('jquery'), '1.0.0', true );
}




?>