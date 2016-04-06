<?php

class WPDDL_Admin_Pages extends WPDDL_Admin
{
    protected static $instance = null;

    function __construct()
    {
        parent::getInstance();

        if( is_admin() ){

            $this->admin_init();
            add_action('admin_menu', array($this, 'add_layouts_admin_menu'));
            add_action('admin_menu', array($this, 'add_layouts_import_export_admin_menu'), 11);
            add_action('admin_menu', array($this, 'add_layouts_admin_create_layout_auto'), 12); // Fake menu for Toolbar link
            add_action('ddl_create_layout_button', array(&$this, 'create_layout_button'));
            add_action('ddl_create_layout_for_this_page', array(&$this, 'create_layout_for_this_page'));
            add_action('ddl_create_layout_for_this_cpt', array(&$this, 'create_layout_for_this_cpt'));
            add_action('wpddl_render_editor', array($this,'render_editor'), 10, 1);
            if (isset( $_GET['page'] ) && ( $_GET['page']==WPDDL_LAYOUTS_POST_TYPE ||
                    $_GET['page']=='dd_layout_theme_export' ||
                    $_GET['page'] == 'dd_layouts_edit' )) {

                add_action('admin_enqueue_scripts', array($this, 'preload_scripts'));

            }
            if (isset($_GET['page']) && $_GET['page']=='dd_layout_theme_export') {
                add_action('admin_enqueue_scripts', array($this, 'import_export_enqueue_script'));
            }
            add_action('ddl_include_creation_box', array(&$this, 'include_creation_box' ));
			
			add_action('wp_ajax_ddl_remove_layouts_loop_pagination_links', array($this,'remove_layouts_loop_pagination_links'));
        }
        // loads admin helper (duplicates layouts)
        if( class_exists('WPDDL_Plugin_Layouts_Helper') ){
            $this->helper = new WPDDL_Plugin_Layouts_Helper();
        }
    }

    function import_export_enqueue_script()
    {
        global $wpddlayout;
        $wpddlayout->enqueue_scripts('dd-layout-theme-import-export');

        $wpddlayout->localize_script('dd-layout-theme-import-export', 'DDLayout_settings', array(
            'DDL_JS' => array(
                'no_file_selected' => __('No file selected. Please select one file to import Layouts data from.', 'ddl-layouts'),
                'file_to_big' => __('File is bigger than maximum allowed in your php configuration.', 'ddl-layouts'),
                'file_type_wrong' => __('Only .zip and .ddl can be imported.', 'ddl-layouts')
            )
        ));
    }

    public function include_creation_box()
    {
        if( file_exists( WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php' ) ){
            include WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php';
        }
    }

    public function render_editor(){
        if( file_exists( WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php' ) ){
            include WPDDL_GUI_ABSPATH . 'templates/create_new_layout.php';
        }
    }

    public function preload_scripts(){
        global $wpddlayout;

        $wpddlayout->enqueue_scripts(
            array(
                'ddl_create_new_layout'
            )
        );
        $wpddlayout->localize_script('ddl_create_new_layout', 'DDLayout_settings_editor', array(
            'user_can_create' => user_can_create_layouts(),
            'strings' => array(
                'associate_layout_to_page' => __('To create an association between this Layout and a single page open....', 'ddl-layouts')
            )
        ) );
    }

    public function create_layout_for_this_page()
    {
        global $post;
        if( user_can_create_layouts() ):
        ?>
        <a href="#" class="add-new-h2 js-create-layout-for-page create-layout-for-page"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></a>
        <?php

    else: ?>
        <button disabled class="add-new-disabled"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></button><br>
        <?php
        endif;
    }

    public function create_layout_for_this_cpt()
    {
        global $post;
        if( user_can_create_layouts() ):
        ?>
        <a href="#" class="add-new-h2 js-create-layout-for-post-custom create-layout-for-page"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></a>
        <?php

    else: ?>
        <button disabled class="add-new-disabled"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></button><br>
        <?php
        endif;
    }


    public function add_layouts_admin_menu()
    {
        $pages = array(
            WPDDL_LAYOUTS_POST_TYPE => array(
                'title' => __('Layouts', 'ddl-layouts'),
                'function' => array($this, 'dd_layouts_list'),
                'subpages' => $this->add_sub_pages()
            ),
        );
        if (!$this->layouts_editor_page) {
            unset($pages[WPDDL_LAYOUTS_POST_TYPE]['subpages']['dd_layouts_edit']);
        }
        $this->add_to_menu($pages);
    }
    
    public function add_layouts_admin_create_layout_auto() {
        $parent_slug = 'options.php'; // Invisible
        $page_title = __( 'Create a new Layout', 'toolset' );
        $menu_title = __( 'Create a new Layout', 'toolset' );
        $capability = DDL_CREATE;
        $menu_slug = 'dd_layouts_create_auto';
        $function = array( $this, 'create_layout_auto' );
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    }

    public function create_layout_auto() {
        
        // verify permissions
        if( ! current_user_can( 'manage_options' ) && WPDD_Layouts_Users_Profiles::user_can_create() && WPDD_Layouts_Users_Profiles::user_can_assign() ) {
            die( __( 'Untrusted user', 'ddl-layouts' ) );
        }
        
        // verify nonce
        check_admin_referer( 'create_auto' );
        
        // validate parameters
        $b_type = isset( $_GET['type'] ) && preg_match( '/^([-a-z0-9_]+)$/', $_GET['type'] );
        $b_class = isset( $_GET['class'] ) && preg_match( '/^(archive|page)$/', $_GET['class'] );
        $b_post_id = isset( $_GET['post'] ) && (int) $_GET['post'] >= 0;

        // validate request
        if( ! ( $b_type && $b_class && $b_post_id ) ) {
            die( __( 'Invalid parameters', 'ddl-layouts' ) );
        }
        
        // get parameters
        $type = $_GET['type'];
        $class = $_GET['class'];
        $post_id = (int) $_GET['post'];
        
        // enforce rules
        $b_page_archive = 'page' === $type && 'archive' === $class;
        if( $b_page_archive ) {
            die( __( 'Not allowed', 'ddl-layouts' ) );
        }
        
        // prepare processing
        if( $post_id === 0 ) {
            $post_id = null;
        }
        
        $layout = null;
        $layout_id = 0;
        
        global $toolset_admin_bar_menu;
        $post_title = $toolset_admin_bar_menu->get_name_auto( 'layouts', $type, $class, $post_id );
        $title = sanitize_text_field( stripslashes_deep( $post_title ) );
        
        $taxonomy = get_taxonomy( $type );
        $is_tax = $taxonomy !== false;

        $post_type_object = get_post_type_object( $type );
        $is_cpt = $post_type_object != null;
        
        
        /* Create a new Layout */
        global $wpddlayout;
        
        // Is there another Layout with the same name?
        $already_exists = $wpddlayout->does_layout_with_this_name_exist( $title );
        if( $already_exists ) {
            die( __( 'A layout with this name already exists. Please use a different name.', 'ddl-layouts' ) );
        }
        
        // Create a empty layout. No preset.
        // TODO: Pick the preset best suited (and check if Views is installed)
        $layout = $wpddlayout->create_layout( 12 /* colums */, 'fluid' /* layout_type */ );
        
        // Define layout parameters
        $layout['type'] = 'fluid'; // layout_type
        $layout['cssframework'] = $wpddlayout->get_css_framework();
        $layout['template'] = '';
        $layout['parent'] = '';
        $layout['name'] = $title;
        
        $args = array(
            'post_title'	=> $title,
            'post_content'	=> '',
            'post_status'	=> 'publish',
            'post_type'     => WPDDL_LAYOUTS_POST_TYPE
        );
        $layout_id = wp_insert_post( $args );

        // force layout object to take right ID
        // @see WPDD_Layouts::create_layout_callback() @ wpddl.class.php
        $layout_post = get_post( $layout_id );
        $layout['id'] = $layout_id;
        $layout['slug'] = $layout_post->post_name;
        
        // assign layout
        if( 'archive' === $class ) {
            
            if( preg_match( '/^(home-blog|search|author|year|month|day)$/', $type ) ) {
                
                // Create a new Layout for X archives
                
                /* assign Layout to X archives */
                $layouts_wordpress_loop = sprintf( 'layouts_%s-page', $type );
                $wordpress_archive_loops = array( $layouts_wordpress_loop );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );
                
            } else if( $is_tax ) {
                
                // Create a new Layout for Y archives
                
                /* assign Layout to Y archives */
                $layouts_taxonomy_loop = sprintf( 'layouts_taxonomy_loop_%s', $type );
                $wordpress_archive_loops = array( $layouts_taxonomy_loop );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );
                 
                
            } else if( $is_cpt ) {
                
                // Create a new Layout for Z archives
                
                /* assign Layout to Z archives */
                $layouts_cpt = sprintf( 'layouts_cpt_%s', $type );
                $wordpress_archive_loops = array( $layouts_cpt );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );
                
            } else {
                die( __( 'An unexpected error happened.', 'ddl-layouts' ) );
            }
            
        } else if( 'page' === $class ) {
            
            if( '404' === $type ) {
                
                // Create a new Layout for Error 404 page
                
                /* assign Layout to 404 page */
                $wordpress_others_section = array( 'layouts_404_page' );
                $wpddlayout->layout_post_loop_cell_manager->handle_others_data_save( $wordpress_others_section, $layout_id );
                
            } else if( 'page' === $type ) {
                
                // Create a new Layout for 'Page Title'
                
                /* assign Layout to Page */
                $posts = array( $post_id );
                $wpddlayout->post_types_manager->update_post_meta_for_post_type( $posts, $layout_id );
                
            } else if( $is_cpt ) {
                
                // Create a new Layout for Ys
                
                /* assign Layout to Y */
                $post_types = array( $type );
                $wpddlayout->post_types_manager->handle_post_type_data_save( $layout_id, $post_types, $post_types );
                //$wpddlayout->post_types_manager->handle_set_option_and_bulk_at_once( $layout_id, $post_types, $post_types );
                
            } else {
                die( __( 'An unexpected error happened.', 'ddl-layouts' ) );
            }
            
        }
        
        // update changes
        WPDD_Layouts::save_layout_settings( $layout_id, $layout );
        
        // redirect to editor (headers already sent)
        $edit_link = $toolset_admin_bar_menu->get_edit_link( 'layouts', false, $type, $class, $layout_id );
        $exit_string = '<script type="text/javascript">'.'window.location = "' . $edit_link . '";'.'</script>';
        exit( $exit_string );
        
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Admin_Pages();
        }
        return self::$instance;
    }
}