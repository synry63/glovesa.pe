<?php

class WPDDL_DialogBoxes
{

    private $screens;

    public function __construct( $screens = array() )
    {
        $this->screens = apply_filters( 'ddl-boxes_screen_ids', $screens );

        add_filter('add_registered_script', array(&$this, 'register_scripts'), 10, 1);
        add_filter('add_registered_styles', array(&$this, 'register_styles'), 10, 1);

    }

    function init_screen_render(){

        if (empty( $this->screens )) {
            return;
        }

        $screen = get_current_screen();

        if ( !in_array( $screen->id, $this->screens ) ) {
            return;
        }

        add_action( 'admin_print_scripts', array(&$this, 'enqueue_scripts'), 999 );
        add_action('admin_footer', array(&$this,'template'));
    }

    function register_scripts($scripts)
    {
        $scripts['ddl-abstract-dialog'] = new WPDDL_script( 'ddl-abstract-dialog', WPDDL_GUI_RELPATH . '/dialogs/dialog-boxes/js/views/abstract/ddl-abstract-dialog.js', array('jquery','wpdialogs'), '0.1', true );
        $scripts['ddl-dialog-boxes'] = new WPDDL_script( 'ddl-dialog-boxes', WPDDL_GUI_RELPATH . '/dialogs/dialog-boxes/js/views/abstract/dialog-view.js', array('jquery','ddl-abstract-dialog'), '0.1', true );

        return $scripts;
    }

    function register_styles($styles)
    {

            return $styles;
    }

    public function enqueue_scripts()
    {
        global $wpddlayout;

        $wpddlayout->enqueue_styles(array(
            'ddl-dialogs-css',
            'ddl-dialogs-general-css',
            'ddl-dialogs-forms-css'
        ));

        $wpddlayout->enqueue_scripts(apply_filters('ddl-dialog-boxes_enqueue_scripts',array(
            'ddl-dialog-boxes'
        )));
    }

    public function template(){
        ob_start();?>

            <script type="text/html" id="ddl-cell-dialog-tpl">
                <div id="js-dialog-dialog-container">
                <div class="ddl-dialog-content" id="js-dialog-content-dialog">
                    <?php printf(__('This is %s cell.', 'ddl-layouts'), '{{{ cell_type }}}'); ?>
                </div>

                <div class="ddl-dialog-footer" id="js-dialog-footer-dialog">
                    <?php printf(__('This is %s cell.', 'ddl-layouts'), '{{{ cell_type }}}'); ?>
                </div>
                </div>
            </script>
        <?php
        echo ob_get_clean();
    }
}