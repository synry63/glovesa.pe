<?php

class WPDD_layout_render {

    protected $layout;
    protected $child_renderer;
    protected $output;
    protected $offset = 0; //set offset member to 0
    protected $current_layout;
    protected $current_row_mode;
    protected $is_child;
    protected $layout_args = array();
    protected $context;

    // TODO: this constant should be set from settings
    const MAX_WIDTH = 12;


    function __construct($layout, $child_renderer = null){
        $this->layout = $layout;
        $this->child_renderer = $child_renderer;
        $this->output = '';
        $this->current_layout = array($layout);

        $this->current_row_mode = array();

        $this->is_child = false;

        $this->handle_full_widths();
    }

    function handle_full_widths(){

        if(  $this->child_renderer === null ) return;

        $child = $this->child_renderer->current_layout[0];

        if( $child === null ) return;

        // check if the child row is set to max width
        if( self::MAX_WIDTH === $this->layout->get_width_of_child_layout_cell() )
        {
            // remove parent and inject children
            if( $this->layout->change_full_width_child_layout_row( $child ) )
            {
                // remove unneeded renderer, children rows will render with parent renderer
                if ($this->child_renderer->child_renderer) {
                    $this->child_renderer = $this->child_renderer->child_renderer;
                } else {
                    $this->child_renderer = null;
                }
            }
        }
    }

    function has_child_renderer() {
        return $this->child_renderer != null;
    }
    
    function render_child() {
        if ($this->child_renderer) {
            return $this->child_renderer->render_to_html(false);
        } else {
            return '';
        }
    }
    
    function get_container_class($mode){
        return WPDDL_Framework::get_container_class($mode);
    }

    function get_container_fluid_class($mode){
        return WPDDL_Framework::get_container_fluid_class($mode);
    }

    function get_row_class($mode){
        return WPDDL_Framework::get_row_class($mode);
    }

    function get_offset_prefix(){
        return WPDDL_Framework::get_offset_prefix();
    }

    function get_image_responsive_class(){
        return WPDDL_Framework::get_image_responsive_class();
    }

    function get_additional_column_class(){
        return WPDDL_Framework::get_additional_column_class();
    }

    function render_to_html($render_parent = true) {

        if ($render_parent) {
            $parent_layout = $this->layout->get_parent_layout();
            $this->is_child = false;
        } else {
            $parent_layout = false;
            $this->is_child = true;
        }

        if ($parent_layout) {
            $manager = new WPDD_layout_render_manager($parent_layout, $this);
            $parent_render = $manager->get_renderer( );
            $parent_render->set_layout_arguments($this->layout_args);
            return $parent_render->render_to_html();
        } else {
            $this->layout->frontend_render($this);
            return $this->output;
        }
    }

    function row_start_callback( $cssClass, $layout_type = 'fixed', $cssId = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal') {
        $this->offset = 0; // reset offset at the beginning of the row

        // if this is not a top level row then we should force full width.
        if (sizeof($this->current_row_mode) > 0 || $this->is_child) {
            $mode = 'sub-row';
        }

        array_push($this->current_row_mode, $mode);

        switch ($layout_type) {
            case 'fixed':
            case '';
                $type = '';
                break;

            default:
                $type = '-'.$layout_type;
                break;
        }

        ob_start();

        $type = apply_filters('ddl-get_fluid_type_class_suffix', $type, $mode );
        $additionalCssClasses = apply_filters('ddl-get_row_additional_css_classes', $additionalCssClasses, $mode);

        switch ($mode) {
            case 'normal':

                ?>
                <div class="<?php printf('%s', $this->get_container_class($mode)); ?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode) . $type;
                if ($additionalCssClasses) {
                    echo ' ' . $additionalCssClasses;
                } ?>"<?php if ($cssId) {
                echo ' id="' . $cssId . '"';
            } ?>>

                <?php
                break;

            case 'full-width-background':

                ?>
                <<?php echo $tag; ?> class="full-bg <?php if ($additionalCssClasses) {
                echo $additionalCssClasses;
            } ?>"<?php if ($cssId) {
                echo ' id="' . $cssId . '"';
            } ?>>

                <div class="<?php printf('%s', $this->get_container_class($mode)); ?>">
                <div class="<?php echo $this->get_row_class($mode) . $type; ?>">

                <?php
                break;

            case 'full-width':

                ?>
                <div class="<?php printf('%s', $this->get_container_fluid_class($mode)); ?>">
                <<?php echo $tag; ?> class="ddl-full-width-row <?php echo $this->get_row_class($mode) . $type;
                if ($additionalCssClasses) {
                    echo ' ' . $additionalCssClasses;
                } ?>"<?php if ($cssId) {
                echo ' id="' . $cssId . '"';
            } ?>>
                <?php
                break;

            case 'sub-row':
                ?>
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode) . $type;
                if ($additionalCssClasses) {
                    echo ' ' . $additionalCssClasses;
                } ?>"<?php if ($cssId) {
                echo ' id="' . $cssId . '"';
            } ?>>
                <?php
                break;
            default:
                ?>
                <div class="<?php printf('%s', $this->get_container_class($mode)); ?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode) . $type;
                if ($additionalCssClasses) {
                    echo ' ' . $additionalCssClasses;
                } ?>"<?php if ($cssId) {
                echo ' id="' . $cssId . '"';
            } ?>>
            <?php
                break;
        }

        $args = array(
            'mode' => $mode,
            'type' => $type,
            'tag' => $tag,
            'additionalCssClasses' => $additionalCssClasses,
            'cssId' => $cssId,
            'container_class' => $this->get_container_class($mode),
            'row_class' => $this->get_row_class($mode),
            'container_fluid_class' => $this->get_container_fluid_class($mode)
        );

        $this->output .= apply_filters( 'ddl_render_row_start', ob_get_clean(), $args );
    }

    function row_end_callback($tag = 'div') {
        $mode = end($this->current_row_mode);
        $output = '';

        switch($mode) {
            case 'normal':
                $output .= '</' . $tag . '>';
                $output .= '</div>';
                break;

            case 'full-width-background':
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</' . $tag . '>';
                break;

            case 'full-width':
                $output .= '</' . $tag . '>';
                $output .= '</div>';
                break;

            case 'sub-row':
                $output .= '</' . $tag . '>';
                break;
            default:
                $output .= '</' . $tag . '>';
                $output .= '</div>';
                break;
        }

        $this->output .= apply_filters('ddl_render_row_end', $output, $mode, $tag);

        array_pop($this->current_row_mode);
    }

    function cell_start_callback($cssClass, $width, $cssId = '', $tag = 'div') {

        $this->output .= '<' . $tag . ' class="' . $this->get_class_name_for_width($width);
        if ($cssClass) {
            $this->output .= ' ' . $cssClass;
        }
        $this->output .= $this->set_cell_offset_class().'"';

        if( $cssId )
        {
            $this->output .= ' id="' . $cssId .'"';
        }

        $this->output .= '>';
    }

    function get_class_name_for_width ($width) {
        return 'span' . (string)$width;
    }

    function cell_end_callback($tag = 'div') {
        $this->output .= '</' . $tag . '>';
        $this->offset = 0; //reset offset after the cell is rendered
    }

    function cell_content_callback($content, $cell = null) {
        $this->output .= apply_filters( 'ddl_render_cell_content', $content, $cell, $this );
    }

    function theme_section_content_callback($content)
    {
        $this->output .=  $content;
    }

    function spacer_start_callback($width){
        $this->offset += $width; //keep track of the spaces and calculate offset for following content cell
    }

    function set_cell_offset_class( )
    {
        $offset_class = '';

        if( $this->offset > 0 )
        {
            switch( $this->layout->get_css_framework() )
            {
                case 'bootstrap':
                    $offset_class .= ' offset'.$this->offset;
                    break;
                case 'bootstrap3':
                    $offset_class .= ' '.$this->get_offset_prefix().$this->offset;
                    break;
                default:
                    $offset_class .= ' '.$this->get_offset_prefix().$this->offset;
                    break;
            }
        }
        return $offset_class;
    }

    function push_current_layout($layout) {
        array_push($this->current_layout, $layout);
    }

    function pop_current_layout() {
        array_pop($this->current_layout);
    }

    function get_row_count() {
        $last = end($this->current_layout);
        return $last->get_row_count();
    }

    function make_images_responsive ($content) {
        return $content;
    }

    function set_property( $property, $value )
    {
        if( is_numeric($property) )
        {
           throw new InvalidArgumentException('Property should be valid string and not a numeric index. Input was: '.$property);
        }
        $this->{$property} = $value;
    }

    function set_layout_arguments( $args ) {
        $this->layout_args = $args;
    }

    function get_layout_arguments( $property ) {
        if (isset($this->layout_args[$property])) {
            return $this->layout_args[$property];
        } else {
            return null;
        }
    }

    function is_layout_argument_set( $property )
    {
        return isset( $this->layout_args[$property] );
    }

    function render( )
    {
        return $this->render_to_html();
    }
    
    function set_context($context) {
        $this->context = $context;
    }
    
    function get_context() {
        return $this->context;
    }
    
}

// for rendering presets in the new layout dialog
class WPDD_layout_preset_render extends WPDD_layout_render {

    function __construct($layout){
        $layout->convert_sidebar_grid_for_preset();
        parent::__construct($layout);
    }

    function cell_start_callback($cssClass, $width, $cssId = '', $tag = 'div') {

        return parent::cell_start_callback($cssClass . ' holder', $width, $cssId, $tag);
    }

    function get_class_name_for_width ($width) {
        return 'span-preset' . (string)$width;
    }

    function row_start_callback( $cssClass, $layout_type = 'fixed', $cssId = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal') {
        $row_count = $this->get_row_count();
        $additionalCssClasses .= ' row-count-' . $row_count;
        $this->offset = 0; // reset offset at the beginning of the row

        $this->output .= '<' . $tag . ' class="row-fluid ' . $additionalCssClasses . '">';

    }

    function row_end_callback($tag = 'div') {
        $this->output .= '</' . $tag . '>';
    }
}

class WPDD_BootstrapTwo_render extends WPDD_layout_render
{

    function __construct($layout, $child_layout = null){

        parent::__construct($layout, $child_layout);
    }

    function get_class_name_for_width ($width) {
        return 'span' . (string)$width;
    }

    function set_cell_offset_class( )
    {
        $offset_class = '';

        if( $this->offset > 0 )
        {
            $offset_class .= ' offset'.$this->offset;
        }
        return $offset_class;
    }

    function row_start_callback( $cssClass, $layout_type = 'fixed', $cssId = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal') {
        $this->offset = 0; // reset offset at the beginning of the row

        // if this is not a top level row then we should force full width.
        if (sizeof($this->current_row_mode) > 0) {
            $mode = 'full-width';
        }

        array_push($this->current_row_mode, $mode);

        $type = '';
        switch ($layout_type) {
            case 'fixed':
            case '';
                if ($mode == 'full-width' && count($this->current_row_mode) == 1) {
                    $type = '-fluid';
                } else {
                    $type = '';
                }
                break;

            default:
                $type = '-'.$layout_type;
                break;
        }

        $type = apply_filters('ddl-get_fluid_type_class_suffix', $type, $mode );

        ob_start();

        switch($mode) {
            case 'normal':
                ?>
                <div class="<?php printf( '%s', $this->get_container_class($mode) );?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode).$type; if( $additionalCssClasses ) {echo ' '.$additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <?php
                break;

            case 'full-width-background':
                ?>
                <<?php echo $tag; ?> class="<?php if( $additionalCssClasses ) {echo $additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <div class="<?php printf( '%s', $this->get_container_class($mode) );?>">
                <div class="<?php echo $this->get_row_class($mode).$type; ?>">
                <?php
                break;
    
            case 'full-width':
                ?>
                <div class="<?php printf( '%s', $this->get_container_fluid_class($mode) );?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode).$type; if( $additionalCssClasses ) {echo ' '.$additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <?php
                break;
            default:?>
                <div class="<?php printf( '%s', $this->get_container_class($mode) );?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode).$type; if( $additionalCssClasses ) {echo ' '.$additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <?php
                break;
        }

        $this->output .= ob_get_clean();
    }

   
}


class WPDD_BootstrapThree_render extends WPDD_layout_render
{

    protected $column_prefix;

    function __construct($layout, $child_layout = null){

        parent::__construct($layout, $child_layout);

        $this->column_prefix = WPDDL_Framework::get_column_prefix();
    }

    function row_start_callback( $cssClass, $layout_type = '', $cssId = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal') {
        parent::row_start_callback($cssClass, '', $cssId, $additionalCssClasses, $tag, $mode);
    }

    function get_class_name_for_width ($width) {
        $ret = '';

        // Set column to sm. This will causes cells to be stacked on mobile devices
        // and then becomes horizontal on tablets and desktops.

        if( is_array( $this->column_prefix ) ){

            foreach( $this->column_prefix as $column_prefix ){

                $w = apply_filters('ddl-get_column_width', $width, $column_prefix, $this );

                $ret .= $column_prefix.(string)$w.' ';
            }

        } else if( is_string( $this->column_prefix ) ){

            $w = apply_filters('ddl-get_column_width', $width, $this->column_prefix, $this );

            $ret = $this->column_prefix.(string)$w;

        }

        $ret .= $this->get_additional_column_class();

        return $ret;
    }

    function set_cell_offset_class( )
    {
        $offset_class = '';

        if( $this->offset > 0 )
        {
            if( is_array( $this->column_prefix ) ){

                foreach( $this->column_prefix as $column_prefix ){

                    $o = apply_filters('ddl-get_column_offset', $this->offset, $column_prefix, $this );

                    $offset_class .= sprintf(' %s%s%s ',  $column_prefix, $this->get_offset_prefix(), (string) $o);

                }

            } else if( is_string( $this->column_prefix ) ){

                $o = apply_filters('ddl-get_column_offset', $this->offset, $this->column_prefix, $this );

                $offset_class .= sprintf(' %s%s%s',  $this->column_prefix, $this->get_offset_prefix(), (string) $o);

            }

        }
        return $offset_class;
    }

    function make_images_responsive ($content) {

        $regex = '/<img[^>]*?/siU';
        if(preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $image) {
                $image = $image[0];
                $regex = '/<img[^>]*?class="([^"]*)"/siU';
                if(preg_match_all($regex, $image, $image_match, PREG_SET_ORDER)) {
                    foreach ($image_match as $val) {
                        // add img-responsive to the class.
                        $new_image = str_replace($val[1], $val[1] . ' '.$this->get_image_responsive_class(), $val[0]);
                        $content = str_replace($val[0], $new_image, $content);
                    }
                } else {
                    // no class attribute on img. we need to add one.
                    $new_image = str_replace('<img ', '<img class="'.$this->get_image_responsive_class().'" ', $image);
                    $content = str_replace($image, $new_image, $content);
                }
            }
        }

        return $content;
    }

}

class WPDD_layout_render_manager{

    private $layout = null;
    private $child_renderer = null;

    public function __construct($layout, $child_renderer = null)
    {
        $this->layout = $layout;
        $this->child_renderer = $child_renderer;
    }

    public function get_renderer( )
    {
        $framework = $this->layout->get_css_framework();

        $renderer = null;

        switch( $framework )
        {
            case 'bootstrap-2':
                $renderer = new WPDD_BootstrapTwo_render(  $this->layout, $this->child_renderer );
                break;
            case 'bootstrap-3':
                $renderer = new WPDD_BootstrapThree_render(  $this->layout, $this->child_renderer );
                break;
            default:
                $renderer = new WPDD_BootstrapThree_render(  $this->layout, $this->child_renderer );
        }

        return apply_filters('get_renderer',$renderer, $this);
    }
}