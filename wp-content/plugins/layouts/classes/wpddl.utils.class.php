<?php

define('Rows', 'Rows');
define('Cells', 'Cells');
define('Cell', 'Cell');

final class WPDD_Utils{

    public static final function toolsetCellTypes() {

        return apply_filters('ddl-toolset-types', array(
            "cell-content-template" => array( "type" => "view-template", "property" => "ddl_view_template_id", "label" => "Content template"),
            "post-loop-views-cell" => array("type" => "view", "property" => "ddl_layout_view_id", "label" => "Archive view"),
            "views-content-grid-cell" => array("type" => "view", "property" => "ddl_layout_view_id", "label" => "View"),
            "cred-cell" => array("type" => "cred-form", "property" => "ddl_layout_cred_id", "label" => "CRED Post Form"),
            "cred-user-cell" => array("type" => "cred-user-form", "property" => "ddl_layout_cred_user_id", "label" => "CRED User form")
        ) );

    }

    public static function get_property_from_cell_type( $type, $property ){
        $infos = self::toolsetCellTypes();

        if( !isset($infos[$type]) ) return null;

        if( !isset( $infos[$type][$property] ) ) return null;

        return $infos[$type][$property];
    }

    public static final function assign_layout_to_post_object( $post_id, $layout_slug, $template = null, $meta = '' ){
        $ret = update_post_meta($post_id, WPDDL_LAYOUTS_META_KEY, $layout_slug, $meta);
        if( $ret && $template !== null ){
            update_post_meta($post_id, '_wp_page_template', $template);
        }
        return apply_filters('assign_layout_to_post_object', $ret, $post_id, $layout_slug, $template, $meta);
    }

    public static final function remove_layout_assignment_to_post_object( $post_id, $meta = '', $and_template = true ){
        $ret = delete_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, $meta );
        if( $ret && $and_template ){
            delete_post_meta($post_id, '_wp_page_template');
        }
        return apply_filters('remove_layout_assignment_to_post_object', $ret, $post_id, $meta, $and_template);
    }

    public static final function get_all_settings(){
        global $wpdb;

        $query = $wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s", WPDDL_LAYOUTS_SETTINGS);

        return $wpdb->get_col( $query );
    }

    public static final function layout_has_one_of_type( $layout_json ){

        $types = array_keys( self::toolsetCellTypes() );
        $builder = new WPDD_json2layout();
        $layout = $builder->json_decode( $layout_json );
        $bool = false;

        foreach( $types as $type ){
            if( $layout->has_cell_of_type($type) ){
                $bool = true;
                break;
            }
        }
        return $bool;

    }

    public static final function page_has_layout( $post_id )
    {
        $meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

        if( $meta === '' ) {
            $ret = false;
        }
        elseif( $meta == '0' ){
            $ret = false;
        }
        else{
            $ret = $meta;
        }
        return $ret;
    }

    public static final function template_have_layout( $file )
    {

        $bool = false;
        $file_data = false;

        $file_abs = get_template_directory() . '/' . $file;

        if ( file_exists( $file_abs ) ) {
            $file_data = @file_get_contents( $file_abs );

        } else {
            if( file_exists( get_stylesheet_directory() . '/' . $file )  ){
                $file_data = @file_get_contents(get_stylesheet_directory() . '/' . $file);
            }
        }

        if ($file_data !== false) {
            if (strpos($file_data, 'the_ddlayout') !== false) {
                $bool = true;
            }
        }

        return apply_filters('ddl_template_have_layout', $bool, $file);
    }

    public static final function page_template_has_layout( $post_id )
    {
        $template = get_post_meta($post_id, '_wp_page_template', true);
        return self::template_have_layout( $template );
    }

    public static final function property_exists( $object, $property){
            return is_object( $object ) ? property_exists($object, $property) : false;
    }

    public static final function str_replace_once($str_pattern, $str_replacement, $string){

        if (strpos($string, $str_pattern) !== false){
            $occurrence = strpos($string, $str_pattern);
            return substr_replace($string, $str_replacement, $occurrence, strlen($str_pattern));
        }

        return $string;
    }

    public static function where( $array, $property, $value ){
        return array_filter($array, array( new Toolset_ArrayUtils($property, $value ), 'filter_array'));
    }

    public static function ajax_nonce_fail( $method ){
        return wp_json_encode( array('Data' => array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', $method ), 'ddl-layouts') ) ) );
    }

    public static function ajax_caps_fail( $method ){
        return wp_json_encode( array( 'Data' => array( 'error' =>  __( sprintf('I am sorry but you don\'t have the necessary privileges to perform this action. %s', $method ), 'ddl-layouts') ) ) );
    }

    public static function user_not_admin(){
        return !current_user_can( DDL_CREATE );
    }

    public static function get_image_sizes( $size = '' ) {

        global $_wp_additional_image_sizes;

        $sizes = array();
        $get_intermediate_image_sizes = get_intermediate_image_sizes();

        // Create the full array with sizes and crop info
        foreach( $get_intermediate_image_sizes as $_size ) {

            if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

                $sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
                $sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
                $sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );

            } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

                $sizes[ $_size ] = array(
                    'width' => $_wp_additional_image_sizes[ $_size ]['width'],
                    'height' => $_wp_additional_image_sizes[ $_size ]['height'],
                    'crop' =>  $_wp_additional_image_sizes[ $_size ]['crop']
                );

            }

        }

        // Get only 1 size if found
        if ( $size ) {

            if( isset( $sizes[ $size ] ) ) {
                return $sizes[ $size ];
            } else {
                return false;
            }

        }

        return $sizes;
    }

    public static function create_cell($name, $divider = 1, $cell_type = 'spacer', $options = array() )
    {
        // create most complex id possible
        $id = (string)uniqid('s', true);
        // het only the latest numeric only part
        $id = explode('.', $id);
        $id = "s" . $id[1];
        // keep only 5 chars to help base64_encode slowness
        $id = substr($id, 0, 5);
        // build a spacer and return it

        return (object) wp_parse_args( $options, array(
            'name' => $name,
            'cell_type' => $cell_type,
            'row_divider' => $divider,
            'content' => '',
            'cssClass' => '',
            'cssId' => 'span1',
            'tag' => 'div',
            'width' => 1,
            'additionalCssClasses' => '',
            'editorVisualTemplateID' => '',
            'id' => $id,
            'kind' => 'Cell'
        ) );
    }


    public static function create_cells($amount, $divider = 1, $cell_type = 'spacer')
    {
        $spacers = array();
        for ($i = 0; $i < $amount; $i++) {
            $spacers[] = self::create_cell($i + 1, $divider, $cell_type);
        }
        return $spacers;
    }

    public static function is_post_published( $id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE ID = '%s' AND post_status = 'publish'", $id) ) > 0;
    }
}


class WPDDL_LayoutsCleaner
{
    private $layout_id;
    private $layout;
    private $cell_type;
    private $to_remove;
    private $removed;
    private $remapped = false;

    public function __construct($layout_id)
    {
        $this->remapped = false;
        $this->removed = array();
        $this->layout_id = $layout_id;
        $this->layout = WPDD_Layouts::get_layout_settings($this->layout_id, true);
    }

    public function remove_orphaned_ct_cells($cell_type, $property)
    {
        $this->remapped = false;
        $this->cell_type = $cell_type;
        $this->property = $property;
        $rows = $this->get_rows();
        $rows = $this->remap_rows($rows);

        if( null !== $rows ){
            $this->layout->Rows = $rows;
            WPDD_Layouts::save_layout_settings( $this->layout_id, $this->layout );
        }

        return $this->removed;
    }


    function remove_unwanted($row, $remove)
    {
        $this->to_remove = $remove;

        if (in_array($remove, $row->Cells)) {

            $width = $remove->width;
            $divider = $remove->row_divider;
            $index = array_search($remove, $row->Cells);
            $spacers = WPDD_Utils::create_cells($width, $divider);
            array_splice($row->Cells, $index, 1, $spacers);

        }

        return $row;
    }


    public function remap_rows( $rows )
    {
        foreach ($rows as $key => $row) {
            //$filtered = array_filter($row->Cells, array(&$this, 'filter_orphaned_cells_of_type'));
            if( !is_object($row) || property_exists($row, 'Cells') === false ){
                return null;
            }
            $filtered = $this->filtered_orphaned_cells_recurse( $row->Cells );
            if (empty($filtered) === false) {
                foreach ($filtered as $ret) {
                    $this->remapped = true;
                    $this->removed[] = $ret->name;
                    $rows[$key] = $this->remove_unwanted($row, $ret);
                }
            }
        }

        if ($this->remapped === true) {
            return $rows;
        }
        return null;
    }

    function filtered_orphaned_cells_recurse( $cells ){
            $array = array();
            foreach( $cells as $key => $cell ){
                if( is_object($cell) && $cell->kind === 'Container' ){
                    $container_rows = $this->remap_rows( $cell->Rows );
                    if( null !== $container_rows ){
                        $cell->Rows = $container_rows;
                    }
                } else if(
                    is_object($cell) &&
                    property_exists($cell, 'cell_type') &&
                    $cell->cell_type === $this->cell_type &&
                    $cell->content &&
                    $cell->content->{$this->property} &&
                    WPDD_Utils::is_post_published($cell->content->{$this->property}) === false
                ){
                    $array[] = $cell;
                }
            }

            return $array;
    }

    private function get_rows()
    {
        if( $this->layout && $this->layout->Rows ){
            return $this->layout->Rows;
        } else {
            return array();
        }
    }

    function filter_orphaned_cells_of_type($cell)
    {
        if (is_object($cell) && property_exists($cell, 'cell_type') && $cell->cell_type === $this->cell_type && $cell->content && $cell->content->{$this->property}) {
            return WPDD_Utils::is_post_published($cell->content->{$this->property}) === false;
        }
    }
}

class WPDDL_RemapLayouts{
    protected $layout;
    protected $poperty;
    protected $new_value;
    protected $cell_id;
    protected $old_value;
    protected $type;
    protected $remapped = false;
    protected $results = array();
    protected $old_name;
    protected $new_name;

    public function __construct( $args = array() ){

            $this->layout = $args['layout'];
            $this->property = $args['property'];
            $this->cell_id = $args['cell_id'];
            $this->new_value = $args['new_value'];
            $this->old_value = $args['old_value'];
            $this->type = $args['cell_type'];
            $this->old_name = $args['old_name'];
            $this->new_name = $args['new_name'];
    }

    function get_layout(){
        return $this->layout;
    }

    function get_process_results(){
        return $this->results;
    }

    private function get_rows()
    {
        if( $this->layout && $this->layout[Rows] ){
            return $this->layout[Rows];
        } else {
            return array();
        }
    }

    public function process_layouts_properties( )
    {
        $this->remapped = false;
        $rows = $this->get_rows();
        $rows = $this->remap_rows($rows);

        if( null !== $rows ){
            $this->layout[Rows] = $rows;
        }

        return $this->layout;
    }

    private function remap_rows( $rows ){
        foreach ($rows as $key => $row) {

            if( !is_array($row) || isset( $row['Cells'] ) === false ){
                return null;
            }
            $filtered = $this->filtered_cells_recurse( $row[Cells] );
            if (empty($filtered) === false) {
                foreach ($filtered as $ret) {
                    $this->remapped = true;
                    $rows[$key] = $this->replace_cell($row, $ret);
                }
            }
        }

        if ($this->remapped === true) {
            return $rows;
        }
        return null;
    }

    private function filtered_cells_recurse( $cells ){
        $array = array();
        foreach( $cells as $key => $cell ){
            if( is_array( $cell ) && $cell['kind'] === 'Container' ){
                $container_rows = $this->remap_rows( $cell[Rows] );
                if( null !== $container_rows ){
                    $cell[Rows] = $container_rows;
                }
            } else if(
                is_array($cell) &&
                isset( $cell['cell_type'] ) &&
                $cell['cell_type'] === $this->type &&
                isset( $cell['content'] ) &&
                isset( $cell['content'][$this->property] ) &&
                $cell['content'][$this->property] == $this->old_value
            ){
                $cell['content'][$this->property] = $this->new_value;
                $array[] = array('cell' => $cell, 'key' => $key, 'new_name' => $this->new_name);
                $this->results[] = (object) array(
                    'old_value' => $this->old_value,
                    'new_value' => $this->new_value,
                    'property' => $this->property,
                    'cell_type' => $this->type,
                    'id' => $cell['id']
                );
            }
        }

        return $array;
    }

    function replace_cell($row, $cell_data)
    {
        $index = $cell_data['key'];
        $cell = $cell_data['cell'];
        $cell['name'] = $cell_data['new_name'];
        $row[Cells][$index] = $cell;
        return $row;
    }

}

class WPDDL_Layouts_WPML{

    private static $instance = null;
    static $languages = null;
    static $current_language = 'en';
    static $default_language = 'en';

    private function __construct(){

        self::$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=name&order=asc' );
        self::$current_language = apply_filters( 'wpml_current_language', NULL );
        self::$default_language = apply_filters('wpml_default_language', NULL );

        add_filter('assign_layout_to_post_object', array(&$this, 'handle_save_update_assignment'), 99, 5 );

        add_filter('remove_layout_assignment_to_post_object', array(&$this, 'handle_remove_assignment'), 99, 4 );

        add_action('ddl-add-wpml-custom-switcher', array(&$this, 'print_wpml_custom_switcher') );

        add_action('ddl-wpml-switch-language', array(&$this, 'ddl_wpml_switch_language'), 10, 1 );

        add_action( 'ddl-wpml-switcher-scripts', array(&$this, 'enqueue_language_switcher_script') );
    }

    public function ddl_wpml_switch_language( $lang ){
        self::$current_language = isset( $lang ) && $lang ? $lang :self::$default_language;
        do_action( 'wpml_switch_language', self::$current_language );
    }

    public function enqueue_language_switcher_script(){
        add_action('admin_print_scripts', array(&$this, 'enqueue_wpml_selector_script'));
    }

    function enqueue_wpml_selector_script(){

        if( null === self::wpml_languages() ) return;

        global $wpddlayout;

        $wpddlayout->enqueue_scripts('ddl-wpml-switcher');
        $wpddlayout->localize_script('ddl-wpml-switcher', 'DDLayout_LangSwitch_Settings', apply_filters( 'ddl-wpml-localize-switcher', array(
            'default_language' => self::$default_language
        ) ) );
    }

    public function print_wpml_custom_switcher(){
        $languages = self::wpml_languages();
        if( null === $languages ) return;

        ob_start();
        include_once WPDDL_GUI_ABSPATH . 'templates/layout-language-switcher.tpl.php';
        echo ob_get_clean();
    }

    public static function wpml_languages(){

        if( count(self::$languages) === 0 ) return null;

        return self::$languages;
    }

    public function handle_save_update_assignment(  $ret, $post_id, $layout_slug, $template, $meta ){
        if( $ret === false ) return $ret;

        $post_type = get_post_type( $post_id );
        $is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', null, $post_type );
        if( $is_translated_post_type === false ){
            return $ret;
        }

        $translations  = apply_filters('wpml_content_translations', null, $post_id, $post_type);

        if( !$translations ){
            return $ret;
        }

        foreach( $translations as $translation){
            if( $translation->element_id !== $post_id ){
                $up = update_post_meta($translation->element_id, WPDDL_LAYOUTS_META_KEY, $layout_slug, $meta);
                if( $up && $template !== null ){
                    update_post_meta($translation->element_id, '_wp_page_template', $template);
                }
            }
        }

        return $ret;
    }

    public function handle_remove_assignment( $ret, $post_id, $meta, $and_template ){
        if( $ret === false ) return $ret;

        $post_type = get_post_type( $post_id );
        $is_translated_post_type = apply_filters( 'wpml_is_translated_post_type', null, $post_type );
        if( $is_translated_post_type === false ){
            return $ret;
        }
        $translations  = apply_filters('wpml_content_translations', null, $post_id, $post_type);

        if( !$translations ){
            return $ret;
        }

        foreach( $translations as $translation){

            if( $translation->element_id !== $post_id ){
                $up = delete_post_meta( $translation->element_id, WPDDL_LAYOUTS_META_KEY, $meta );
                if( $up && $and_template ){
                    delete_post_meta($translation->element_id, '_wp_page_template');
                }
            }
        }

        return $ret;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Layouts_WPML();
        }

        return self::$instance;
    }
}

/**
 * Class WPDDL_Framework
 *
 * Framework API Basic elements
 */
final class WPDDL_Framework{
    static function get_container_class($mode){
        return apply_filters('ddl-get_container_class', 'container', $mode);
    }

    static function get_container_fluid_class($mode){
        return apply_filters('ddl-get_container_fluid_class', 'container-fluid', $mode);
    }

    static function get_row_class($mode){
        return apply_filters('ddl-get_row_class', 'row', $mode);
    }

    static function get_offset_prefix(){
        return apply_filters('ddl-get_offset_prefix', 'offset-');
    }

    static function get_image_responsive_class(){
        return apply_filters('ddl-get_image_responsive_class', 'img-responsive');
    }

    static function get_column_prefix(){
        return apply_filters('ddl-get-column-prefix', 'col-sm-');
    }

    static function get_additional_column_class(){
        return apply_filters('ddl-get_additional_column_class', '');
    }

    static function get_thumbnail_class(){
        return apply_filters('ddl-get_thumbnail_class', 'thumbnail');
    }

    static function framework_supports_responsive_images(){
        return apply_filters( 'ddl-framework_supports_responsive_images', true );
    }
}