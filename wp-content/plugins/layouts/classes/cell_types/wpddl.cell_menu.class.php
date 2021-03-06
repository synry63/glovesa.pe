<?php
/*
 * Menu cell.
 * Displays Bootstrap theme navigation menu.
 *
 */

if( ddl_has_feature('menu-cell') === false ){
	return;
}


if (!class_exists('Layouts_cell_Menu')) {
    class Layouts_cell_Menu{
        
        
        // define cell name
        private $cell_type = 'menu-cell';

        function __construct() {
            add_action( 'init', array(&$this, 'register_menu_cell_init'), 12);
        }
        
        
        function register_menu_cell_init() {
            if (function_exists('register_dd_layout_cell_type')) {
                register_dd_layout_cell_type($this->cell_type , 
                    array(
                        'name' => __('Menu', 'ddl-layouts'),
                        'cell-image-url' => DDL_ICONS_SVG_REL_PATH . 'layouts-menu-cell.svg',
                        'description' => __('Display one of the WordPress menus that exist in your site.', 'ddl-layouts'),
                        'category' => __('Site elements', 'ddl-layouts'),
                        'button-text' => __('Assign menu cell', 'ddl-layouts'),
                        'dialog-title-create' => __('Create a new menu cell', 'ddl-layouts'),
                        'dialog-title-edit' => __('Edit menu cell', 'ddl-layouts'),
                        'dialog-template-callback' => array(&$this,'menu_cell_dialog_template_callback'),
                        'cell-content-callback' => array(&$this,'menu_cell_content_callback'),
                        'cell-template-callback' => array(&$this,'menu_cell_template_callback'),
                        'has_settings' => true,
                        'preview-image-url' => DDL_ICONS_PNG_REL_PATH . 'menu_expand-image.png'
                    )
                );
            }
        }
        
        
        function menu_cell_dialog_template_callback() {
		ob_start();
		?>

		<div class="ddl-form">
                    <p>
                        <label for="<?php the_ddl_name_attr('menu_name'); ?>"><?php _e('Select menu', 'ddl-layouts'); ?></label>
                        <?php 
                        $menus = get_terms('nav_menu');
                        if( count($menus) > 0 ):
                        ?>
                            <select name="<?php the_ddl_name_attr('menu_name'); ?>">
                                <?php
                                foreach ( $menus as $menu ) :
                                    $name = $menu->name;
                                ?>
                                    <option value="<?php echo $name; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else:?>
                            <span class="toolset-alert toolset-alert-info ddl-dialog-form-message"><?php printf( __('There are no menus available, create your menus in %sEdit Menus%s page first.', 'ddl-layouts'), '<a href="'.admin_url('nav-menus.php', 'admin').'">', '</a>' );?></span>
                        <?php endif; ?>
                    </p>
                    <p>
                        <label for="<?php the_ddl_name_attr('menu_dir'); ?>"><?php _e('Menu style', 'ddl-layouts'); ?></label>
                        <select name="<?php the_ddl_name_attr('menu_dir'); ?>">
                            <option value="nav-horizontal"><?php _e('Top Bar menu (horizontal)', 'ddl-layouts' ); ?></option>
                            <option value="nav-stacked"><?php _e('Sidebar menu (vertical)', 'ddl-layouts' ); ?></option>
                        </select>
                    </p>


                    <p>
                        <label for="<?php the_ddl_name_attr('menu_depth'); ?>"><?php _e('Navigation levels', 'ddl-layouts'); ?></label>
                        <select name="<?php the_ddl_name_attr('menu_depth'); ?>">
                            <option value="-1"><?php _e('All items in a flat menu', 'ddl-layouts');?></option>
                            <option value="0"><?php _e('All levels', 'ddl-layouts');?></option>
                            <option value="1"><?php _e('Level one', 'ddl-layouts');?></option>
                            <option value="2"><?php _e('Level two', 'ddl-layouts');?></option>
                            <option value="3"><?php _e('Level three', 'ddl-layouts');?></option>
                        </select>
                    </p>
                    <!--<p>
                        <label for="<?php the_ddl_name_attr('menu_drop_down'); ?>"><?php _e('Sub menu style', 'ddl-layouts'); ?></label>
                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="<?php the_ddl_name_attr('menu_drop_down'); ?>" value="bootstrap" checked />Drop down menu &nbsp;
                        <input type="radio" name="<?php the_ddl_name_attr('menu_drop_down'); ?>" value="default" />Flat menu
                    </p>-->
		</div>

		<?php
		return ob_get_clean();
	}
        
        function menu_cell_content_callback() {
            global $wpddlayout;
            $id = 'ddl-navbar-collapse-'.md5(time()+rand(0,100));
            $menu_name	= get_ddl_field('menu_name');
            $menu_dir	= get_ddl_field('menu_dir');
            $menu_depth	= get_ddl_field('menu_depth');
            $menu_class	= 'ddl-nav ddl-navbar-nav ' . 'ddl-'.$menu_dir;
            $container_class = 'collapse ddl-navbar-collapse '.$id;
            $menu_style = 'bootstrap';
            $wpddlayout->enqueue_scripts('ddl-menu-cell-front-end');

            ob_start();
            ?>

            <nav class="ddl-nav-wrap ddl-navbar ddl-navbar-default ddl-<?php echo $menu_dir?>">
                     <button type="button" class="ddl-navbar-toggle" data-toggle="collapse" data-target=".<?php echo $id?>">		      
                    <span class="ddl-icon-bar"></span>
                    <span class="ddl-icon-bar"></span>
                    <span class="ddl-icon-bar"></span>
                 </button>
                    <?php
                    $args = array(
                            'menu'       => $menu_name,
                            'menu_class' => $menu_class,
                            'container_class' => $container_class,
                            'depth'	     =>	$menu_depth,
                            'walker' => $this->get_walker( $menu_style )
                    );
                    wp_nav_menu( $args );
                    ?>
            </nav>        

            <?php

            return ob_get_clean();
	}


	function menu_cell_template_callback() {
            ob_start();
            ?>
            <div class="cell-content">

                <p class="cell-name"><?php _e('Menu', 'ddl-layouts'); ?></p>
                <div class="cell-preview">
                    <div class="ddl-menu-preview">
                        <# if(content){ #><p><strong>{{content.menu_name}}</strong></p><# } #>
                        <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/menu-preview.svg'; ?>" height="130px">
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
	}

	function get_walker( $style ){
            global $wpddlayout;

            if( strpos( $wpddlayout->get_css_framework(), 'bootstrap' ) === false ) return null;

            if( 'bootstrap' == $style )
            {
                    return new DDL_Wpbootstrap_Nav_Walker();
            }
            elseif( 'default' == $style )
            {
                    return null;
            }

            return null;
	}
        
        
        

    }
    new Layouts_cell_Menu();
}


if( !class_exists('DDL_Wpbootstrap_Nav_Walker') ){
    class DDL_Wpbootstrap_Nav_Walker extends Walker_Nav_Menu {

        function check_current( $classes ) {
            return preg_match( '/(current[-_])|active|dropdown/', $classes );
        }

        function start_lvl( &$output, $depth = 0, $args = array() ) {
            $output .= "\n<ul class=\"ddl-dropdown-menu\">\n";
        }

        function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
            $item_html = '';
            parent::start_el( $item_html, $item, $depth, $args );

            if( is_object($args) ){
                $set_depth = (int) $args->depth;
                $depth     = (int) $depth;
                $diff      = $set_depth - $depth;

                if ( $set_depth === 0 ) {
                    if ( $item->is_dropdown && $depth === 0 ) {
                        $item_html = str_replace( '<a', '<a class="ddl-dropdown-toggle" data-toggle="dropdown" data-target="#"', $item_html );
                        $item_html = str_replace( '</a>', ' <b class="caret"></b></a>', $item_html );
                    }
                    elseif ( stristr( $item_html, 'li class="divider') ) {
                        $item_html = preg_replace( '/<a[^>]*>.*?<\/a>/iU', '', $item_html );
                    }
                    elseif ( stristr( $item_html, 'li class="nav-header') ) {
                            $item_html = preg_replace( '/<a[^>]*>(.*)<\/a>/iU', '$1', $item_html );
                        }
                } else {
                    if ( $item->is_dropdown && $set_depth !== 1 && $depth === 0 && $depth < $diff )  {
                        $item_html = str_replace( '<a', '<a class="ddl-dropdown-toggle" data-toggle="dropdown" data-target="#"', $item_html );
                        $item_html = str_replace( '</a>', ' <b class="caret"></b></a>', $item_html );
                    }
                    elseif ( stristr( $item_html, 'li class="divider') ) {
                        $item_html = preg_replace('/<a[^>]*>.*?<\/a>/iU', '', $item_html );
                    }
                    elseif ( stristr( $item_html, 'li class="ddl-nav-header') ) {
                        $item_html = preg_replace('/<a[^>]*>(.*)<\/a>/iU', '$1', $item_html );
                    }
                }

                $item_html = str_replace( 'current_page_item', 'current_page_item active', $item_html );
                $item_html = str_replace( 'current-menu-parent', 'current-menu-parent active', $item_html );


                $output .= $item_html;
            }

        }

        function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args, &$output ) {
            $element->is_dropdown = ! empty( $children_elements[$element->ID] );

            if( is_array( $args ) && is_object( $args[0] ) ){
                $set_depth = (int) $args[0]->depth;
                $depth     = (int) $depth;
                $diff      = $set_depth - $depth;


                if ( $set_depth === 0  ) {
                    if ( $element->is_dropdown ) {
                        if ( $depth === 0 ) {
                                $element->classes[] = 'ddl-dropdown';
                        }
                        elseif ( $depth > 0 ) {
                                $element->classes[] = 'ddl-dropdown-submenu-layouts';
                        }
                    }
                } else {
                    if ( $element->is_dropdown ) {
                        if ( $depth === 0 ) {
                                $element->classes[] = 'ddl-dropdown';
                        }
                        elseif ( $depth > 0 && $depth < $diff ) {
                                $element->classes[] = 'ddl-dropdown-submenu-layouts';
                        }
                    }
                }
                parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
            }
        }


        /**
         * Menu Fallback
         * =============
         * If this function is assigned to the wp_nav_menu's fallback_cb variable
         * and a manu has not been assigned to the theme location in the WordPress
         * menu manager the function with display nothing to a non-logged in user,
         * and will add a link to the WordPress menu manager if logged in as an admin.
         *
         * @param array $args passed from the wp_nav_menu function.
         *
         */
        public static function fallback( $args ) {
            if ( current_user_can( 'manage_options' ) ) {

                extract( $args );

                $fb_output = null;

                if ( $container ) {
                    $fb_output = '<' . $container;

                    if ( $container_id )
                            $fb_output .= ' id="' . $container_id . '"';

                    if ( $container_class )
                            $fb_output .= ' class="' . $container_class . '"';

                    $fb_output .= '>';
                }

                $fb_output .= '<ul';

                if ( $menu_id )
                    $fb_output .= ' id="' . $menu_id . '"';

                if ( $menu_class )
                    $fb_output .= ' class="' . $menu_class . '"';

                $fb_output .= '>';
                $fb_output .= '<li><a href="' . admin_url( 'nav-menus.php' ) . '">Add a menu</a></li>';
                $fb_output .= '</ul>';

                if ( $container )
                    $fb_output .= '</' . $container . '>';

                echo $fb_output;
            }
        }
    }
}
