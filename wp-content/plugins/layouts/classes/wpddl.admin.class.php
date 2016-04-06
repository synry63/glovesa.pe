<?php
class WPDDL_Admin{
    protected $layouts_editor_page = false;
    protected $layouts_settings = null;
    protected static $instance = null;

    function __construct(){
        if( is_admin() ){
            // common things to run upon init here
            if ( isset( $_GET['page'] ) && ( $_GET['page'] == WPDDL_LAYOUTS_POST_TYPE || $_GET['page'] == 'dd_layouts_edit' ) ){
                    do_action('ddl-wpml-switcher-scripts');
            } elseif ( isset( $_GET['page'] ) &&  $_GET['page'] == 'dd_layout_CSS' ){
                add_action('admin_init', array(&$this, 'init_layouts_css_gui') );
            }
            $this->layouts_settings = WPDDL_Settings::getInstance();
            // init gui for settings
            $this->layouts_settings->init();
            // Include "Settings" admin menu
            add_action( 'admin_menu', array( $this, 'add_layouts_settings_admin_menu' ), 11 );
            add_action( 'admin_menu', array( $this, 'add_layouts_CSS_admin_menu' ), 11 );
            $this->init_layouts_css();
        }
    }

    private function init_layouts_css(){
        include WPDDL_GUI_ABSPATH . 'CSS/wpddl.css-editor.class.php';
        WPDDL_CSSEditor::getInstance();
    }

    function init_layouts_css_gui(){
        WPDDL_CSSEditor::getInstance()->init_gui();
    }

    public function create_layout_button()
    {
        if( user_can_create_layouts() ):
            ?>
            <a href="#" class="add-new-h2 js-layout-add-new-top"><?php _e('Add new layout', 'ddl-layouts');?></a>
        <?php

        else: ?>
            <button disabled class="add-new-disabled"><?php _e('Add new layout', 'ddl-layouts');?></button>
        <?php
        endif;
    }

    protected function add_layout_menu()
    {
        if( user_can_create_layouts() === false || user_can_edit_layouts() === false  ){
            return array();
        }
        return array('admin.php?page=dd_layouts&amp;new_layout=true' => array(
            'title' => __('Add new layout', 'ddl-layouts'),
        )
        );
    }

    protected function add_edit_menu()
    {
        if( user_can_edit_layouts() === false ){
            return array();
        }
        return array('dd_layouts_edit' => array(
            'title' => __('Edit layout', 'ddl-layouts'),
            'function' => array($this, 'dd_layouts_edit'),
        ));
    }

    protected function add_tutorial_video()
    {
        return array('dd_tutorial_videos' => array(
            'title' => __('Help', 'ddl-layouts'),
            'function' => array($this, 'dd_layouts_help'),
            'subpages' => array(
                'dd_layouts_debug' => array(
                    'title' => __('Debug information', 'ddl-layouts'),
                    'function' => array(__CLASS__, 'dd_layouts_debug')
                ),
            ),
        ),);
    }

    protected function add_troubleshoot_menu()
    {
        if( isset( $_GET['page'] ) && 'dd_layouts_troubleshoot' == $_GET['page'] ){
            return array('dd_layouts_troubleshoot' => array(
                'title' => __('Troubleshoot', 'ddl-layouts'),
                'function' => array(__CLASS__, 'dd_layouts_troubleshoot'),
            ));
        }
        return array();
    }

    function admin_init()
    {
        if (isset($_GET['page']) and $_GET['page'] == 'dd_layouts_edit') {
            if (isset($_GET['layout_id']) and $_GET['layout_id'] > 0) {
                $this->layouts_editor_page = true;
            }
        }
    }

    protected function add_sub_pages()
    {
        $menus = array_merge(
            array(),
            $this->add_layout_menu(),
            $this->add_edit_menu(),
            $this->add_tutorial_video(),
            $this->add_troubleshoot_menu()
        );

        return $menus;
    }

    /**
     * Adds items to admin menu.
     *
     * @param array $menu array of menu items
     * @param string $parent_slug menu slug, if exist item is added as submenu
     *
     * @return void function do not return anything
     *
     */
    public function add_to_menu($menu, $parent_slug = null)
    {
        foreach ($menu as $menu_slug => $data) {
            $slug = null;
            if (empty($parent_slug)) {
                $slug = add_menu_page(
                    $data['title'],
                    isset($data['menu']) ? $data['menu'] : $data['title'],
                    WPDD_Layouts_Users_Profiles::get_cap_for_page( $menu_slug ),
                    $menu_slug,
                    isset($data['function']) ? $data['function'] : null
                );
            } else {
                $slug = add_submenu_page(
                    $parent_slug,
                    $data['title'],
                    isset($data['menu']) ? $data['menu'] : $data['title'],
                    WPDD_Layouts_Users_Profiles::get_cap_for_page( $menu_slug ),
                    $menu_slug,
                    isset($data['function']) ? $data['function'] : null
                );
            }
            /**
             * add load hook if is defined
             */
            if (!empty($slug) && isset($data['load_hook'])) {
                add_action('load-' . $slug, $data['load_hook']);
            }
            /**
             * add subpages
             */
            if (isset($data['subpages'])) {
                $this->add_to_menu($data['subpages'], $menu_slug);
            }
        }
    }

    function dd_layouts_help(){
        include WPDDL_GUI_ABSPATH . 'templates/layout_help.tpl.php';
        include WPDDL_GUI_ABSPATH . 'dialogs/dialog_video_player.tpl.php';
    }

    function dd_layouts_list()
    {
        global $wpddlayout;
        $wpddlayout->listing_page->init();
    }

    function dd_layouts_edit()
    {
        global $wpddlayout;
        $wpddlayout->dd_layouts_edit();
    }

    function dd_layouts_theme_export(){
        include WPDDL_SUPPORT_THEME_PATH . 'templates/layout_theme_export.tpl.php';
    }

    function add_layouts_import_export_admin_menu() {

        add_submenu_page(WPDDL_LAYOUTS_POST_TYPE, __('Import/Export', 'ddl-layouts'), __('Import/Export', 'ddl-layouts'), DDL_ASSIGN, 'dd_layout_theme_export', array($this, 'dd_layouts_theme_export'));
    }

    function add_layouts_CSS_admin_menu() {

        add_submenu_page(WPDDL_LAYOUTS_POST_TYPE, __('Layouts CSS', 'ddl-layouts'), __('Layouts CSS', 'ddl-layouts'), DDL_EDIT, 'dd_layout_CSS', array($this, 'dd_layout_CSS'));
    }

    public function dd_layout_CSS(){
        WPDDL_CSSEditor::getInstance()->load_template();
    }

    /**
     * debug page render hook.
     */
    public static function dd_layouts_debug()
    {
        include_once WPDDL_TOOLSET_COMMON_ABSPATH . DIRECTORY_SEPARATOR.'debug/debug-information.php';
    }
    /**
     * troubleshoot page render hook
     */
    public static function dd_layouts_troubleshoot()
    {
        include WPDDL_GUI_ABSPATH . 'templates/layout_troubleshoot.tpl.php';
    }

    function remove_layouts_loop_pagination_links()
    {
        if( user_can_create_layouts() === false ){
            $data = array(
                'type' => 'capability',
                'message' => __( 'You do not have permissions for that.', 'ddl-layouts' )
            );
            wp_send_json_error($data);
        }
        if(	!isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'ddl_remove_layouts_loop_pagination_links') ){
            $data = array(
                'type' => 'nonce',
                'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'ddl-layouts' )
            );
            wp_send_json_error($data);
        }
        if( function_exists('wpv_check_views_exists') ){
            $ddl_archive_loop_ids = wpv_check_views_exists( 'layouts-loop' );
            if( $ddl_archive_loop_ids ){
                $ddl_archive_loop_ids = array_map('esc_attr', $ddl_archive_loop_ids);
                $ddl_archive_loop_ids = array_map('trim', $ddl_archive_loop_ids);
                $ddl_archive_loop_ids = array_filter($ddl_archive_loop_ids, 'is_numeric');
                $ddl_archive_loop_ids = array_map('intval', $ddl_archive_loop_ids);
                if( count($ddl_archive_loop_ids) ){
                    global $wpdb;
                    $final_post_content = "[wpv-filter-meta-html]\n[wpv-layout-meta-html]";
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->posts}
							SET post_content = %s
							WHERE ID IN ('" . implode("','", $ddl_archive_loop_ids) . "')",
                            $final_post_content
                        )
                    );
                }
            }
            $data = array(
                'message' => __( 'Pagination links deleted.', 'ddl-layouts' )
            );
            wp_send_json_success( $data );
        } else {
            $data = array(
                'type' => 'missing',
                'message' => __( 'You need Views to perform this action.', 'ddl-layouts' )
            );
            wp_send_json_error($data);
        }
    }

    /**
     * Add 'Settings' item to admin menu
     */
    function add_layouts_settings_admin_menu() {

        add_submenu_page( WPDDL_LAYOUTS_POST_TYPE, __( 'Settings', 'ddl-layouts' ), __( 'Settings', 'ddl-layouts' ), 'manage_options', 'dd_layout_settings', array( $this, 'dd_layouts_show_settings_page' ) );

    }
    
    /**
     * Show Layouts Settings page
     */
    public function dd_layouts_show_settings_page() {
        
        $this->layouts_settings->show();
    }
    

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Admin();
        }

        return self::$instance;
    }

}