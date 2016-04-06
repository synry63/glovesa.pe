<?php

class WPDDL_style
{

	public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $media = 'screen')
	{
		$this->handle = $handle;
		$this->path = $path;
		$this->deps = $deps;
		$this->ver = $ver;
		$this->media = $media;

		if ( $this->compare_versions() && $this->path != 'wordpress_default') {
			wp_register_style($this->handle, $this->path, $this->deps, $this->ver, $this->media );
		}
	}

	public function enqueue()
	{
		if ($this->is_enqueued() === false) {
			wp_enqueue_style($this->handle);
		}
	}

	private function compare_versions( ){
			global $wp_styles;

			if( isset($wp_styles->registered) && isset($wp_styles->registered[$this->handle]) ){
				$registered = $wp_styles->registered[$this->handle];
				if( (float) $registered->ver < (float) $this->ver ){
					$wp_styles->remove( $this->handle );
					return true;
				} else {
					return false;
				}
			}

			return $this->is_registered() === false;
	}

	public function deregister()
	{
		if ($this->is_registered() !== false) wp_deregister_style($this->handle);
	}


	private function is_registered()
	{
		return wp_style_is($this->handle, 'registered');
	}

	private function is_enqueued()
	{
		return wp_style_is($this->handle, 'enqueued');
	}
}

class WPDDL_script
{
	public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $in_footer = false)
	{
		$this->handle = $handle;
		$this->path = $path;
		$this->deps = $deps;
		$this->ver = $ver;
		$this->in_footer = $in_footer;

		if ( $this->compare_versions( ) && $this->path != 'wordpress_default' ) {
			wp_register_script($this->handle, $this->path, $this->deps, $this->ver, $this->in_footer);
		}
	}

	public function enqueue()
	{
		if ($this->is_enqueued() === false) {
			wp_enqueue_script($this->handle);
		}
	}

    private function compare_versions( ){
        global $wp_scripts;

        if( isset($wp_scripts->registered) && isset($wp_scripts->registered[$this->handle]) ){
            $registered = $wp_scripts->registered[$this->handle];
            if( (float) $registered->ver < (float) $this->ver ){
                $wp_scripts->remove( $this->handle );
                return true;
            } else {
                return false;
            }
        }

        return $this->is_registered() === false;
    }

	public function localize($object, $args)
	{
		if ($this->is_registered()) {
			wp_localize_script($this->handle, $object, $args);
		}
	}

	public function deregister()
	{
		if ($this->is_registered() !== false) wp_deregister_script($this->handle);
	}

	private function is_registered()
	{
		return wp_script_is($this->handle, 'registered');
	}

	private function is_enqueued()
	{
		return wp_script_is($this->handle, 'enqueued');
	}
}

class WPDDL_scripts_manager
{
	private static $instance;
	private $styles = array();
	private $scripts = array();

	private function __construct()
	{
		add_action( 'init', array($this, 'init') );
		//be
		add_action( 'admin_enqueue_scripts', array($this, 'get_rid_of_default_scripts') );
		add_action( 'admin_enqueue_scripts', array($this, 'get_rid_of_default_styles') );
		//fe
		add_action( 'wp_enqueue_scripts', array($this, 'get_rid_of_default_scripts') );
		add_action( 'wp_enqueue_scripts', array($this, 'get_rid_of_default_styles') );
	}

	public static function getInstance( )
	{
		if (!self::$instance)
		{
			self::$instance = new WPDDL_scripts_manager( );
		}

		return self::$instance;
	}

	public function init()
	{
		$this->__initialize_styles();
		$this->__initialize_scripts();
	}

	/*
	 * @return void
	 * pushes to our scripts array other scripts so we can enqueue using our methods
	 */
	public function get_rid_of_default_scripts()
	{
		global $wp_scripts;
		if( is_array($wp_scripts->registered) )
		{
			foreach ($wp_scripts->registered as $registered) {
				$this->scripts[$registered->handle] = new WPDDL_script($registered->handle);
			}
		}
	}

	/*
	 * @return void
	 * pushes to our scripts array other scripts so we can enqueue using our methods
	 */
	public function get_rid_of_default_styles()
	{
		global $wp_styles;

		if( is_array($wp_styles->registered) )
		{
			foreach ($wp_styles->registered as $registered) {
				$this->styles[$registered->handle] = new WPDDL_style($registered->handle);
			}
		}
	}

	private function __initialize_styles()
	{
        #common backend

        $this->styles['wpt-toolset-backend'] = new WPDDL_style('wpt-toolset-backend', WPDDL_TOOLSET_COMMON_RELPATH . '/toolset-forms/css/wpt-toolset-backend.css');
		$this->styles['toolset-select2-css'] = new WPDDL_style('toolset-select2-css', WPDDL_TOOLSET_COMMON_RELPATH . '/utility/css/select2/select2.css');
		$this->styles['layouts-select2-overrides-css'] = new WPDDL_style('layouts-select2-overrides-css', WPDDL_TOOLSET_COMMON_RELPATH . '/utility/css/select2/select2-overrides.css');
		$this->styles['ddl_post_edit_page_css'] = new WPDDL_style('wp-layouts-pages', WPDDL_RES_RELPATH . '/css/dd-general.css');
		$this->styles['progress-bar-css'] = new WPDDL_style('progress-bar-css', WPDDL_RES_RELPATH . '/css/progress.css');
		$this->styles['toolset-colorbox'] = new WPDDL_style('toolset-colorbox', WPDDL_RES_RELPATH . '/css/colorbox.css');
		//$this->styles['font-awesome'] = new WPDDL_style('font-awesome', WPDDL_RES_RELPATH . '/css/external_libraries/font-awesome/css/font-awesome.min.css');
		$this->styles['font-awesome'] = new WPDDL_style('font-awesome', WPDDL_TOOLSET_COMMON_RELPATH . '/utility/css/font-awesome/css/font-awesome.min.css', array(), '4.4.0', 'screen');

		$this->styles['toolset-notifications-css'] = new WPDDL_style('toolset-notifications-css', WPDDL_TOOLSET_COMMON_RELPATH . '/utility/css/notifications.css');
		$this->styles['layouts-global-css'] = new WPDDL_style('layouts-global-css', WPDDL_GUI_RELPATH . 'global/css/dd-general.css');
		$this->styles['wp-editor-layouts-css'] = new WPDDL_style('wp-editor-layouts-css', WPDDL_GUI_RELPATH . 'editor/css/editor.css', array('wp-jquery-ui-dialog'));
		$this->styles['wp-layouts-pages'] = new WPDDL_style('wp-layouts-pages', WPDDL_RES_RELPATH . '/css/dd-general.css');
		$this->styles['toolset-common'] = new WPDDL_style('toolset-common', WPDDL_TOOLSET_COMMON_RELPATH. '/res/css/toolset-common.css');
		$this->styles['layouts-settings-admin-css'] = new WPDDL_style(  'layouts-settings-admin-css', WPDDL_RES_RELPATH . '/css/ddl-settings.css', array(), WPDDL_VERSION );
		$this->styles['layouts-css-admin-css'] = new WPDDL_style(  'layouts-css-admin-css', WPDDL_GUI_RELPATH . 'CSS/css/css-editor-style.css', array(), WPDDL_VERSION );

		# dialogs css
		$this->styles['toolset-meta-html-codemirror-css'] = new WPDDL_style('toolset-meta-html-codemirror-css', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/lib/codemirror.css', array(), "5.5.0");
		$this->styles['toolset-meta-html-codemirror-css-hint-css'] = new WPDDL_style('toolset-meta-html-codemirror-css-hint-css', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/addon/hint/show-hint.css', array(), "5.5.0");

		$this->styles['wp-layouts-jquery-ui-slider'] = new WPDDL_style('wp-layouts-jquery-ui-slider', WPDDL_GUI_RELPATH . 'dialogs/css/jquery-ui-slider.css');
		$this->styles['ddl-dialogs-forms-css'] = new WPDDL_style('ddl-dialogs-forms-css', WPDDL_RES_RELPATH  . '/css/dd-dialogs-forms.css');
		if (defined('WPV_URL_EMBEDDED')) {
			$this->styles['views-admin-dialogs-css'] = new WPDDL_style('views-admin-dialogs-css', WPV_URL_EMBEDDED . '/res/css/dialogs.css', array( 'wp-jquery-ui-dialog' ), WPV_VERSION);
		}
		$this->styles['ddl-dialogs-general-css'] = new WPDDL_style('ddl-dialogs-general-css', WPDDL_RES_RELPATH . "/css/dd-dialogs-general.css");
		$this->styles['ddl-dialogs-css'] = new WPDDL_style('ddl-dialogs-css', WPDDL_RES_RELPATH . "/css/dd-dialogs.css", array('ddl-dialogs-general-css'), WPDDL_VERSION );

		# common
		if (defined('WPV_URL_EMBEDDED_FRONTEND')) {
			$this->styles['views-pagination-style'] = new WPDDL_style( 'views-pagination-style', WPV_URL_EMBEDDED_FRONTEND . '/res/css/wpv-pagination.css');
		}
		

		#listing pages

		$this->styles['dd-listing-page-style'] = new WPDDL_style('dd-listing-page-style', WPDDL_RES_RELPATH . '/css/dd-listing-page-style.css', array());

		#FE styles

        $this->styles['ddl-front-end'] = new WPDDL_style('ddl-front-end', WPDDL_RES_RELPATH . "/css/ddl-front-end.css");
		$this->styles['menu-cells-front-end'] = new WPDDL_style('menu-cells-front-end', WPDDL_RES_RELPATH . "/css/cell-menu-css.css");

        return apply_filters('add_registered_styles', $this->styles );
    }


	private function __initialize_scripts()
	{
		global $pagenow;
		
		//dependencies///////
        $this->scripts['layouts-prototypes'] = new WPDDL_script('layouts-prototypes', WPDDL_RES_RELPATH . "/js/external_libraries/prototypes.js", array('underscore', 'backbone'), WPDDL_VERSION, true);
        $this->scripts['headjs'] = new WPDDL_script('headjs', (WPDDL_TOOLSET_COMMON_RELPATH . "/utility/js/head.min.js"), array(), WPDDL_VERSION, true);
		$this->scripts['ddl_common_scripts'] = new WPDDL_script('ddl_common_scripts', WPDDL_RES_RELPATH . "/js/dd_layouts_common_scripts.js", array('jquery', 'headjs', 'underscore'), WPDDL_VERSION, true);
        $this->scripts['jstorage'] = new WPDDL_script('jstorage', WPDDL_TOOLSET_COMMON_RELPATH . "/utility/js/jstorage.min.js", array(), WPDDL_VERSION, true);
		$this->scripts['toolset-utils'] = new WPDDL_script('toolset-utils', WPDDL_TOOLSET_COMMON_RELPATH . "/utility/js/utils.js", array('jquery', 'underscore', 'backbone', 'jquery-ui-core','jquery-ui-widget'), '1.2.2', true);

        $this->scripts['wp-events-manager'] = new WPDDL_script('wp-events-manager', WPDDL_TOOLSET_COMMON_RELPATH . "/utility/js/events-manager/event-manager.min.js", array(), '1.0', true);

        $this->scripts['jquery-ui-cell-sortable'] = new WPDDL_script('jquery-ui-cell-sortable', WPDDL_RES_RELPATH . '/js/external_libraries/jquery.ui.cell-sortable.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable'), WPDDL_VERSION, true);
		$this->scripts['jquery-ui-custom-sortable'] = new WPDDL_script('jquery-ui-custom-sortable', WPDDL_RES_RELPATH . '/js/jquery.ui.custom-sortable.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable'), WPDDL_VERSION, true);
		$this->scripts['select2'] = new WPDDL_script('select2', (WPDDL_TOOLSET_COMMON_RELPATH . "/utility/js/select2.min.js"), array('jquery'), '3.4.5', true);
        $this->scripts['parents-watcher'] = new WPDDL_script('parents-watcher', WPDDL_RES_RELPATH . '/js/dd-layouts-parents-watcher.js', array('jquery', 'backbone', 'underscore'), WPDDL_VERSION, true );

		//listing//////
		$this->scripts['ddl_create_new_layout'] = new WPDDL_script('ddl_create_new_layout', (WPDDL_RES_RELPATH . "/js/dd_create_new_layout.js"), array('jquery'), WPDDL_VERSION, true);
		$this->scripts['wp-layouts-colorbox-script'] = new WPDDL_script('wp-layouts-colorbox-script', WPDDL_RES_RELPATH . '/js/external_libraries/jquery.colorbox-min.js', array('jquery'), WPDDL_VERSION);
		$this->scripts['ddl_post_edit_page'] = new WPDDL_script('ddl_post_edit_page', (WPDDL_RES_RELPATH . "/js/dd_layouts_post_edit_page.js"), array('jquery', 'toolset-utils'), WPDDL_VERSION, true);

		$this->scripts['wp-layouts-dialogs-script'] = new WPDDL_script('wp-layouts-dialogs-script', WPDDL_GUI_RELPATH . 'dialogs/js/dialogs.js', array('jquery', 'editor', 'thickbox', 'media-upload', 'toolset-utils', 'select2'));

		$this->scripts['ddl-post-types'] = new WPDDL_script('ddl-post-types', WPDDL_RES_RELPATH . '/js/ddl-post-types.js', array('jquery'));


		// media
		$this->scripts['media_uploader_js'] = new WPDDL_script('ddl_media_uploader_js', WPDDL_RES_RELPATH . '/js/ddl-media-uploader.js', array('jquery'), WPDDL_VERSION, true);

		// settings page and scripts
		$this->scripts['ddl-cssframework-settings-script'] = new WPDDL_script('ddl-cssframework-settings-script', WPDDL_RES_RELPATH . '/js/dd_layouts_cssframework_settings.js',array('jquery','underscore'), WPDDL_VERSION, true);

        $this->scripts['ddl-wpml-switcher'] = new WPDDL_script('ddl-wpml-switcher', WPDDL_RES_RELPATH . '/js/ddl-wpml-switcher.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-selectmenu'), WPDDL_VERSION, true);
		$this->scripts['layouts-settings-admin-js'] = new WPDDL_script( 'layouts-settings-admin-js',  WPDDL_RES_RELPATH . '/js/ddl-settings.js', array( 'jquery', 'toolset-utils' ), WPDDL_VERSION, true );
		$this->scripts['ddl-css-editor-main'] = new WPDDL_script('ddl-css-editor-main', WPDDL_GUI_RELPATH . "CSS/js/main.js", array('headjs', 'jquery', 'toolset-utils', 'underscore', 'backbone'), WPDDL_VERSION, true);

        #codemirror.js and related
        $this->scripts['toolset-codemirror-script'] = new WPDDL_script('toolset-codemirror-script', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/lib/codemirror.js', array('jquery'), "5.5.0");
        $this->scripts['toolset-meta-html-codemirror-overlay-script'] = new WPDDL_script('toolset-meta-html-codemirror-overlay-script', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/addon/mode/overlay.js', array('toolset-codemirror-script'), "5.5.0");
        $this->scripts['toolset-meta-html-codemirror-xml-script'] = new WPDDL_script('toolset-meta-html-codemirror-xml-script', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/mode/xml/xml.js', array('toolset-meta-html-codemirror-overlay-script'), "5.5.0");
        $this->scripts['toolset-meta-html-codemirror-css-script'] = new WPDDL_script('toolset-meta-html-codemirror-css-script', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/mode/css/css.js', array('toolset-meta-html-codemirror-overlay-script'), "5.5.0");
        $this->scripts['toolset-meta-html-codemirror-js-script'] = new WPDDL_script('toolset-meta-html-codemirror-js-script', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/mode/javascript/javascript.js', array('toolset-meta-html-codemirror-overlay-script'), "5.5.0");
        $this->scripts['toolset-meta-html-codemirror-utils-search'] = new WPDDL_script('toolset-meta-html-codemirror-utils-search', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/addon/search/search.js', array(), "5.5.0" );
        $this->scripts['toolset-meta-html-codemirror-utils-search-cursor'] = new WPDDL_script('toolset-meta-html-codemirror-utils-search-cursor', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/addon/search/searchcursor.js', array(), "5.5.0" );
        $this->scripts['toolset-meta-html-codemirror-utils-hint'] = new WPDDL_script('toolset-meta-html-codemirror-utils-hint', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/addon/hint/show-hint.js', array(), "5.5.0" );
        $this->scripts['toolset-meta-html-codemirror-utils-hint-css'] = new WPDDL_script('toolset-meta-html-codemirror-utils-hint-css', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/codemirror/addon/hint/css-hint.js', array(), "5.5.0" );
        $this->scripts['icl_editor-script'] = new WPDDL_script('icl_editor-script', WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/icl_editor_addon_plugin.js', array('toolset-codemirror-script'), '1.2');
		$this->localize_script( 
			'icl_editor-script', 
			'icl_editor_localization_texts', 
			array(
				'wpv_insert_conditional_shortcode' => __( 'Insert conditional shortcode', 'wpv-views' ),
				'wpv_conditional_button' => __( 'conditional output', 'wpv-views' ),
				'wpv_editor_callback_nonce' => wp_create_nonce( 'wpv_editor_callback' )
			) 
		);

        if( isset( $_GET['page'] ) && 'dd_layouts_edit' == $_GET['page'] )
		{
            $this->scripts['icl_media-manager-js'] = new WPDDL_script('icl_media-manager-js',
				WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/icl_media_manager.js',
				array('toolset-codemirror-script'), '1.2');
			#editor
			$this->scripts['ddl-editor-main'] = new WPDDL_script('ddl-editor-main', (WPDDL_GUI_RELPATH . "editor/js/main.js"), array('headjs', 'jquery', 'backbone', 'toolset-utils','jquery-ui-tabs','icl_media-manager-js', 'jquery-effects-core', 'jquery-effects-highlight', 'jquery-effects-pulsate', 'jquery-ui-dialog', 'wp-events-manager'), null, true);

			$this->scripts['ddl-sanitize-html'] = new WPDDL_script('ddl-sanitize-html', WPDDL_RES_RELPATH . '/js/external_libraries/sanitize/sanitize.js', array() );
			$this->scripts['ddl-sanitize-helper'] = new WPDDL_script('ddl-sanitize-helper', WPDDL_GUI_RELPATH . 'editor/js/ddl-sanitize-helper.js', array('underscore', 'ddl-sanitize-html', 'jquery') );
		}
		// listing
		if( isset($_GET['page']) && $_GET['page'] === WPDDL_LAYOUTS_POST_TYPE )
		{
			$this->scripts['dd-listing-page-main'] = new WPDDL_script('dd-listing-page-main', (WPDDL_GUI_RELPATH . "listing/js/main.js"), array('headjs', 'jquery', 'backbone', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-dialog', 'jquery-ui-tooltip', 'jquery', 'jquery-ui-tabs', 'wp-events-manager'), WPDDL_VERSION, true);
		}

		// Common scripts
		if (defined('WPV_URL_EMBEDDED')) {
			// TODO this is also not useful as our localize_script method seems to require the script to be registered and enqueued
			// The native wp_localize_script function only requires it to be registered
			// We might want to change our method for consistency, in which case this will actually add the localization
			$this->localize_script(
				'toolset-utils',
				'wpv_help_box_texts',
				array(
					'wpv_dont_show_it_again' => __("Got it! Don't show this message again", 'wpv-views'),
					'wpv_close' => __("Close", 'wpv-views')
			));
		}

		// Front End Scripts
		$this->scripts['ddl-layouts-frontend'] = new WPDDL_script('ddl-layouts-frontend', WPDDL_RES_RELPATH . '/js/ddl-layouts-frontend.js', array('jquery'), WPDDL_VERSION);
		$this->scripts['ddl-layouts-toolset-support'] = new WPDDL_script('ddl-layouts-toolset-support', WPDDL_RES_RELPATH . '/js/ddl-layouts-toolset-support.js', array('jquery', 'suggest'), WPDDL_VERSION);

		// Views support
		if( isset( $_GET['in-iframe-for-layout']) && $_GET['in-iframe-for-layout'] == 1 &&
			(isset( $_GET['page'] ) && (('views-editor' == $_GET['page']) ||
										('views-embedded' == $_GET['page']) ||
										('view-archives-embedded' == $_GET['page']) ||
										('view-archives-editor' == $_GET['page']) ))) {
			$this->scripts['ddl-layouts-views-support'] = new WPDDL_script('ddl-layouts-views-support', WPDDL_RES_RELPATH . '/js/dd-layouts-views-support.js', array('jquery', 'suggest', 'ddl-layouts-toolset-support'), WPDDL_VERSION);
		}
		
		// CRED support
		if (isset( $_GET['in-iframe-for-layout']) &&
					$_GET['in-iframe-for-layout'] == 1 &&
					defined('CRED_FORMS_CUSTOM_POST_NAME') &&
					$pagenow == 'post.php' &&
					isset($_GET['post'])) {
			
			$post_id = $_GET['post'];
			$post = get_post($post_id);
			if ($post->post_type == CRED_FORMS_CUSTOM_POST_NAME) {
				$this->scripts['ddl-layouts-cred-support'] = new WPDDL_script('ddl-layouts-cred-support', WPDDL_RES_RELPATH . '/js/dd-layouts-cred-support.js', array('jquery', 'ddl-layouts-toolset-support'), WPDDL_VERSION);
			}
		}

        // CRED support
        if (isset( $_GET['in-iframe-for-layout']) &&
            $_GET['in-iframe-for-layout'] == 1 &&
            defined('CRED_USER_FORMS_CUSTOM_POST_NAME') &&
            $pagenow == 'post.php' &&
            isset($_GET['post'])) {

            $post_id = $_GET['post'];
            $post = get_post($post_id);
            if ($post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) {
                $this->scripts['ddl-layouts-cred-user-support'] = new WPDDL_script('ddl-layouts-cred-user-support', WPDDL_RES_RELPATH . '/js/dd-layouts-cred-user-support.js', array('jquery', 'ddl-layouts-toolset-support'), WPDDL_VERSION);
            }
        }
		

        # import export
        if( isset($_GET['page']) && $_GET['page'] === 'dd_layout_theme_export' )
        {
            
            $this->scripts['dd-layout-theme-import-export'] = new WPDDL_script('dd-layout-theme-import-export', WPDDL_RES_RELPATH . '/js/ddl-import-export-script.js', array( 'jquery'), WPDDL_VERSION, true);
            $this->localize_script(
				'dd-layout-theme-import-export',
				'ddl_import_texts',
				array(
					'start_import' => __("Import started", 'ddl-layouts'),
					'upload_another_file' => __("Upload another file", 'ddl-layouts'),
                    'incorrect_answer' => __("Incorrect answer from server", 'ddl-layouts'),
                    'working_with' => __("Working with file {1} of {2}", 'ddl-layouts'),
                    'working_with_fail' => __("File {1} timeout", 'ddl-layouts'),
                    'saved_layouts' => __("Saved Layouts", 'ddl-layouts'),
                    'deleted_layouts' => __("Deleted Layouts", 'ddl-layouts'),
                    'saved_css' => __("Saved CSS", 'ddl-layouts'),
                    'overwritten_layouts' => __("Overwritten Layouts", 'ddl-layouts'),
                    'server_timeout' => __("Server timeout, please try again later.", 'ddl-layouts'),
                    'import_finished' => __("Import finished", 'ddl-layouts'),
                    'in_queue' => __("in queue", 'ddl-layouts'),
                    'error_timeout' => __("Error, timeout", 'ddl-layouts'),
                    'skipped_layouts' => __("Skipped Layouts", 'ddl-layouts'),
                    'ok' => __("Ok", 'ddl-layouts'),
			));
        }

        #post edit page
    	$this->scripts['ddl-create-for-pages'] = new WPDDL_script('ddl-create-for-pages', WPDDL_RES_RELPATH . '/js/dd-layouts-create-for-single-pages.js', array('jquery',  'toolset-utils', /*'layouts-prototypes',*/ 'wp-layouts-dialogs-script', 'wp-layouts-colorbox-script'), WPDDL_VERSION, true);
		$this->scripts['ddl-comment-cell-front-end'] = new WPDDL_script('ddl-comment-cell-front-end', WPDDL_RES_RELPATH . '/js/ddl-comment-cell-front-end.js', array('jquery', 'toolset-utils', 'comment-reply'), WPDDL_VERSION, true);
        $this->scripts['ddl-post-editor-overrides'] = new WPDDL_script('ddl-post-editor-overrides', WPDDL_RES_RELPATH . '/js/ddl-post-editor-overrides.js', array('jquery', 'toolset-utils', 'jstorage', /*'layouts-prototypes',*/ 'wp-layouts-dialogs-script', 'wp-layouts-colorbox-script'), WPDDL_VERSION, true);
        $this->scripts['ddl-menu-cell-front-end'] = new WPDDL_script('ddl-menu-cell-front-end', WPDDL_RES_RELPATH . '/js/ddl-menu-cell-front-end.js', array('jquery', 'toolset-utils'), WPDDL_VERSION, true);

        #embedded mode only
        $this->scripts['ddl-embedded-mode'] = new WPDDL_script('ddl-embedded-mode', WPDDL_RES_RELPATH . '/js/dd-layouts-embedded.js', array('jquery',  'toolset-utils', 'layouts-prototypes', 'wp-layouts-dialogs-script', 'wp-layouts-colorbox-script', 'select2' ), WPDDL_VERSION, true);

        return apply_filters('add_registered_script', $this->scripts );
    }

	public function enqueue_scripts($handles)
	{
		if (is_array($handles)) {
			foreach ($handles as $handle) {
				if (isset($this->scripts[$handle])) {
					$this->scripts[$handle]->enqueue();
				}
			}
		} else if (is_string($handles)) {
			if (isset($this->scripts[$handles])) {
				$this->scripts[$handles]->enqueue();
			}
		}
	}

	public function enqueue_styles($handles)
	{
		if (is_array($handles)) {
			foreach ($handles as $handle) {
				if (isset($this->styles[$handle])) {
					$this->styles[$handle]->enqueue();
				}
			}
		} else if (is_string($handles)) {
			if (isset($this->styles[$handles])) $this->styles[$handles]->enqueue();
		}
	}

	public function deregister_scripts($handles)
	{
		if (is_array($handles)) {
			foreach ($handles as $handle) {
				if (isset($this->scripts[$handle])) {
					$this->scripts[$handle]->deregister();
					unset($this->scripts[$handle]);
				}
			}
		} else if (is_string($handles)) {
			if (isset($this->scripts[$handles])) {
				$this->scripts[$handles]->deregister();
				unset($this->scripts[$handles]);
			}
		}
	}

	public function deregister_styles($handles)
	{
		if (is_array($handles)) {
			foreach ($handles as $handle) {
				if (isset($this->styles[$handle])) {
					$this->styles[$handle]->deregister();
					unset($this->styles[$handle]);
				}
			}
		} else if (is_string($handles)) {
			if (isset($this->styles[$handles])) {
				$this->styles[$handles]->deregister();
				unset($this->styles[$handles]);
			}
		}
	}

	public function register_script( $handle, $path = '', $deps = array(), $ver = false, $in_footer = false )
	{
		if( !isset( $this->scripts[$handle] ) )
		{
			$this->scripts[$handle] = new WPDDL_script( $handle, $path, $deps, $ver, $in_footer );
		}
	}

	public function register_style( $handle, $path = '', $deps = array(), $ver = false, $media = 'screen' )
	{
		if( !isset( $this->styles[$handle] ) )
		{
			$this->scripts[$handle] = new WPDDL_style( $handle, $path, $deps, $ver, $media );
		}
	}

	public function localize_script($handle, $object, $args)
	{
		if (isset($this->scripts[$handle])) $this->scripts[$handle]->localize($object, $args);
	}
}
