<?php
/**********************************************************************
 *          Define textdomain
 ********************************************************************/
load_theme_textdomain( "toolset_starter", get_template_directory() );


/**********************************************************************
 *            Load Bootstrap functions and Theme Customization
 ********************************************************************/
require_once( get_template_directory() . '/functions/bootstrap-wordpress.php' );
require_once( get_template_directory() . '/functions/theme-customizer.php' );

/******************************************************************************************
 * Enqueue styles and scripts
 *****************************************************************************************/

// used in different places
define( 'THEME_CSS', get_template_directory_uri() . '/css/theme.css' );
define( 'THEME_CSS_WOO', get_template_directory_uri() . '/css/woocommerce.css' );
define( 'THEME_CSS_BOOTSTRAP', get_template_directory_uri() . '/bootstrap/css/bootstrap.min.css' );

if (!function_exists('ref_enqueue_main_stylesheet')) {
	function ref_enqueue_main_stylesheet()
	{
		if (!is_admin()) {
			wp_enqueue_style('main', get_template_directory_uri() . '/style.css', array(), null);
		}
	}

	add_action('wp_enqueue_scripts', 'ref_enqueue_main_stylesheet');
}

if ( ! function_exists( 'ref_register_scripts' ) ) {

	function ref_register_scripts() {
		if ( ! is_admin() ) {

			// Register  CSS
			wp_register_style( 'bootstrap_css', THEME_CSS_BOOTSTRAP , array(), null );
			wp_register_style( 'theme', THEME_CSS, array(), null );
			wp_register_style( 'ref_woocommerce', THEME_CSS_WOO, array(), null );
			wp_register_style( 'font_awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', array(), null );

			if(get_theme_mod( 'ref_theme_styles',1) == 1 ) {
				wp_enqueue_style( 'theme' );
			} else {
				wp_enqueue_style( 'bootstrap_css' );
			}

			if(get_theme_mod( 'ref_wc_styles',1) == 1 ) {
				wp_enqueue_style( 'ref_woocommerce' );
			}

			wp_enqueue_style( 'font_awesome' );

			// Register  JS
			wp_register_script( 'wpbootstrap_bootstrap_js', get_template_directory_uri() . '/bootstrap/js/bootstrap.min.js', array( 'jquery' ), null, true );
			wp_register_script( 'theme_js', get_template_directory_uri() . '/js/theme.min.js', array( 'jquery' ), null, true );

			// Enqueue JS
			wp_enqueue_script( 'wpbootstrap_bootstrap_js' );
			wp_enqueue_script( 'theme_js' );


			if ( is_single() && comments_open() && get_option( 'thread_comments' ) ) {
				wp_enqueue_script( 'comment-reply' );
			}
		}
	}

	add_action( 'wp_enqueue_scripts', 'ref_register_scripts' );
}


/******************************************************************************************
 * Theme support
 *****************************************************************************************/
add_theme_support( 'woocommerce' );
add_theme_support( "title-tag" );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'nav-menus' );
register_nav_menus( array(
	'header-menu' => __( 'Header Menu', "toolset_starter" ),
) );


add_theme_support( 'html5', array(
	'search-form',
	'comment-form',
	'comment-list',
	'gallery',
	'caption',
	'video'
) );

/**********************************************************
 * The Archive Title Filter
 ********************************************************/

if (!function_exists('ref_custom_archive_title')) {
	add_filter('get_the_archive_title', 'ref_custom_archive_title');

	function ref_custom_archive_title($title)
	{
		if (is_post_type_archive()) {

			$title = post_type_archive_title('', false);

		}
		return $title;
	}
}
/******************************************************************************************
 * Add Open Sans font variants for admin and front-end
 *****************************************************************************************/

if ( ! function_exists( 'replace_open_sans' ) ) {

	function replace_open_sans() {
		wp_deregister_style( 'open-sans' );
		wp_register_style( 'open-sans', '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800&subset=latin,latin-ext' );
		wp_enqueue_style( 'open-sans' );
	}

	add_action( 'wp_enqueue_scripts', 'replace_open_sans' );
}


/**********************************************************************
 *            Add image sizes
 ********************************************************************/

add_image_size( 'product-thumbnail', 260, 330, true );

/**********************************************************************
 *            Register sidebars
 ********************************************************************/
if ( ! defined( 'WPDDL_VERSION' ) ) {
	if (!function_exists('wpbootstrap_register_widget_areas')) {
		function wpbootstrap_register_widget_areas()
		{
			register_sidebar(array(
					'name' => __('Widgets in Footer', "toolset_starter"),
					'id' => 'sidebar-footer',
					'before_widget' => '<div id="%1$s" class="widget %2$s">',
					'after_widget' => '</div>',
					'before_title' => '<h3 class="widgettitle">',
					'after_title' => '</h3>'
			));

			register_sidebar(array(
					'name' => __('Widgets in Header', "toolset_starter"),
					'id' => 'sidebar-header',
					'before_widget' => '<div id="%1$s" class="widget %2$s">',
					'after_widget' => '</div>',
					'before_title' => '<h3 class="widgettitle">',
					'after_title' => '</h3>'
			));

			register_sidebar(array(
					'name' => __('Default Sidebar', "toolset_starter"),
					'id' => 'sidebar-default',
					'before_widget' => '<div id="%1$s" class="widget %2$s">',
					'after_widget' => '</div>',
					'before_title' => '<h3 class="widgettitle">',
					'after_title' => '</h3>'
			));
		}

		add_action('widgets_init', 'wpbootstrap_register_widget_areas');
	}
}

/**************************************************
 * Load custom cells types for Layouts plugin from the /dd-layouts-cells/ directory
 **************************************************/
if (class_exists('WPDD_Layouts') && !function_exists('include_ddl_layouts')) {

	function include_ddl_layouts( $tpls_dir = '' ) {
		$dir_str = dirname( __FILE__ ) . $tpls_dir;
		$dir     = opendir( $dir_str );

		while ( ( $currentFile = readdir( $dir ) ) !== false ) {
			if (is_file($dir_str . $currentFile)) {
				include $dir_str . $currentFile;
			}
		}
		closedir( $dir );
	}

	include_ddl_layouts( '/dd-layouts-cells/' );
}


/**************************************************
 * Allow to Import/Export Layouts
 **************************************************/
if (defined('WPDDL_VERSION')) {
	require_once WPDDL_ABSPATH . '/ddl-theme.php';
}

if (function_exists('ddl_import_layouts_from_theme_dir')) {
	function ref_import_layouts()
	{
		ddl_import_layouts_from_theme_dir();
	}

	add_action('init', 'ref_import_layouts', 99);
}


/**********************************************************************
 *            Page Slug Body Class
 ********************************************************************/

function add_slug_body_class( $classes ) {
	global $post;

	if ( isset( $post ) ) {
		$classes[] = $post->post_type . '-' . $post->post_name;
	}

	return $classes;
}

add_filter( 'body_class', 'add_slug_body_class' );


add_filter('get_layout_id_for_render', 'toolset_base_theme_fix_attachment', 2, 99);
function toolset_base_theme_fix_attachment( $layout_id, $layout ){
    // if the page is rendering with layouts fix attachment if that's the case
    if( $layout_id !== 0 ){
        add_filter('the_content', 'prepend_attachment', 1, 999);
    }
    return $layout_id;
}


/** If has_header_image not exists */
if( ! function_exists( 'has_header_image' ) ) {
	function has_header_image() {
		return (bool) get_header_image();
	}
}
?>