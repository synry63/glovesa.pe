<?php

class WPDDL_Settings {

    private static $instance;
    const MAX_POSTS_OPTION_NAME = WPDDL_MAX_POSTS_OPTION_NAME;
    const MAX_POSTS_OPTION_DEFAULT = WPDDL_MAX_POSTS_OPTION_DEFAULT;

    public static $max_posts_num_option = self::MAX_POSTS_OPTION_DEFAULT;

    public function __construct() {

        self::set_max_num_posts( self::get_option_max_num_posts() );

        add_filter( 'ddl_default_support_features', array($this, 'add_supported_featured_cells'), 8, 1 );
        add_action( 'wp_ajax_ddl_update_toolset_admin_bar_menu_status', array( $this, 'ddl_update_toolset_admin_bar_menu_status' ) );
        add_action( 'wp_ajax_ddl_set_max_posts_amount', array( __CLASS__, 'ddl_set_max_posts_amount' ) );
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Settings();
        }

        return self::$instance;
    }

    public function init(){
        add_action( 'init', array( $this, 'init_gui' ) );
    }

    /**
     * Layouts Settings page set up
     */
    function init_gui() {
        

        add_action( 'ddl_action_layouts_settings_features_section', array( $this, 'ddl_show_hidden_toolset_admin_bar_menu' ), 50 );
        add_action( 'ddl_action_layouts_settings_features_section', array( $this, 'ddl_set_max_query_size' ), 51 );
        $settings_script_texts = array(
            'setting_saved' => __( 'Settings saved', 'ddl-layouts' )
        );

        if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'dd_layout_settings' ) {

            global $wpddlayout;
            $wpddlayout->enqueue_styles( 'layouts-settings-admin-css' );
            $wpddlayout->enqueue_scripts( 'layouts-settings-admin-js' );
            $wpddlayout->localize_script( 'layouts-settings-admin-js', 'ddl_settings_texts', $settings_script_texts );
        }
        
    }

    function ddl_update_toolset_admin_bar_menu_status() {
        
        if ( ! current_user_can( 'manage_options' ) ) {
            $data = array(
                'type' => 'capability',
                'message' => __( 'You do not have permissions for that.', 'ddl-layouts' )
            );
            wp_send_json_error( $data );
        }
        if (
                ! isset( $_POST["wpnonce"] ) || ! wp_verify_nonce( $_POST["wpnonce"], 'ddl_toolset_admin_bar_menu_nonce' )
        ) {
            $data = array(
                'type' => 'nonce',
                'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'ddl-layouts' )
            );
            wp_send_json_error( $data );
        }
        
        $status = ( isset( $_POST['status'] ) ) ? sanitize_text_field( $_POST['status'] ) : 'true';
        $toolset_options = get_option( 'toolset_options', array() );
        $toolset_options['show_admin_bar_shortcut'] = ( $status == 'true' ) ? 'on' : 'off';
        update_option( 'toolset_options', $toolset_options );
        wp_send_json_success();
        
    }

    ////////////////////////////////////////////////////////////////////////////
    //
    // Layouts Settings Page - GUI Code
    //
    ////////////////////////////////////////////////////////////////////////////

    function show() {
        // Which tab is selected?
        // First tab by default: features
        $tab = 'features';

        if ( isset( $_GET['tab'] ) && preg_match( '#^(features|compatibility|development)$#', $_GET['tab'], $selected_tab ) ) {
            $tab = $selected_tab[1];
        }
        ob_start();
        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-main-box.tpl.php';
        echo ob_get_clean();
    }

    public function ddl_show_hidden_toolset_admin_bar_menu( $options ) {
        $toolset_options = get_option( 'toolset_options', array() );
        $toolset_admin_bar_menu_show = ( isset( $toolset_options['show_admin_bar_shortcut'] ) && $toolset_options['show_admin_bar_shortcut'] == 'off' ) ? false : true;
        ob_start();
        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-admin_bar.tpl.php';
        echo ob_get_clean();
    }

    function ddl_set_max_query_size( $options ){
        self::$max_posts_num_option = self::get_option_max_num_posts();
        ob_start();

        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-wp_query.tpl.php';

        echo ob_get_clean();
    }

    public static function get_option_max_num_posts(){
            return get_option( self::MAX_POSTS_OPTION_NAME, self::MAX_POSTS_OPTION_DEFAULT );
    }

    public static function set_option_max_num_posts( $num ){
        return update_option( self::MAX_POSTS_OPTION_NAME, $num );
    }

    public static function get_max_posts_num( ){
        return self::$max_posts_num_option;
    }

    public static function set_max_num_posts( $num ){
        return self::$max_posts_num_option = $num;
    }

    public static function ddl_set_max_posts_amount( ){
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if( $_POST && wp_verify_nonce( $_POST['ddl_max-posts-num_nonce'], 'ddl_max-posts-num_nonce' ) )
        {
            $update = false;
            $amount = isset( $_POST['amount_posts'] ) ? $_POST['amount_posts'] : self::$max_posts_num_option;

            if( $amount !==  self::$max_posts_num_option ){
                self::$max_posts_num_option = $amount;
                $update = self::set_option_max_num_posts( $amount );
            }


            if( $update )
            {
                $send = wp_json_encode( array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts'), 'amount' => $amount  ) )  );

            } else {
                $send = wp_json_encode( array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts'), 'amount' => $amount  ) ) );

            }
        }
        else
        {
            $send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
        }

        die($send);
    }

    public static function ddl_featured_cells(){
        return apply_filters( 'ddl_default_featured_cells', array(
            'child-layout',
            'cell-widget-area',
            'widget-cell',
            'post-loop-views-cell',
            'cell-content-template',
            'views-content-grid-cell',
            'video-cell',
            'cell-text',
            'slider-cell',
            'post-loop-cell',
            'menu-cell',
            'imagebox-cell',
            'cred-user-cell',
            'cred-cell',
            'comments-cell',
            'grid-cell',
            'ddl_missing_cell_type',
            'ddl-container'
        ) );
    }

    public function add_supported_featured_cells( $features ){
            $cells = self::ddl_featured_cells();

            return array_merge( $features, $cells);
    }
}
WPDDL_Settings::getInstance();