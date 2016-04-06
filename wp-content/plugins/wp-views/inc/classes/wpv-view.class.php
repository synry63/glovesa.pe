<?php

/**
 * Represents a single View.
 *
 * Full version with setters & co.
 *
 * @since 1.9
 */
class WPV_View extends WPV_View_Embedded {


    /**
     * See parent class constructor description.
     *
     * @param int|WP_Post $view View post object or ID.
     */
    public function __construct( $view ) {
        parent::__construct( $view );
    }


    /* ************************************************************************* *\
            Static methods
    \* ************************************************************************* */


    /**
     * Create an instance of WPV_View from View ID or a WP_Post object.
     *
     * See WPV_View_Embedded constructor for details.
     *
     * @param int|WP_Post $view View ID or a WP_Post object.
     *
     * @return null|WPV_View
     */
    public static function get_instance( $view ) {
        try{
            $view = new WPV_View( $view );
            return $view;
        } catch( Exception $e ) {
            return null;
        }
    }


    /**
     * Create a new View.
     *
     * If the View purpose is set to "slider", also automatically create new Loop template.
     *
     * @param string $title New View title. Must be unique and valid (see validate_title()).
     * @param array $args (
     *          @type array $view_settings View settings that should override the default ones. Optional.
     *          @type array $loop_settings Loop settings that should override the default ones. Optional.
     *          @type bool $forbid_loop_template Never create a Loop template for this View. Optional, default is false.
     *     )
     *
     * @return WPV_View New View object.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws WPV_RuntimeExceptionWithMessage
     *
     * @note overriding default Views settings and layout settings must provide complete data when the element is an
     * array, because it overrides them all. For example, $args['settings']['pagination'] can not override just the
     * "postsper page" options: it must provide a complete pagination implementation. This might change and be corrected
     * in the future, keeping backwards compatibility.
     *
     * @since 1.10
     */
    public static function create( $title, $args ) {

        $view_id = WPV_View_Base::create_post( $title );

        $view = new WPV_View( $view_id );

        $view->defer_after_update_actions();

        // Construct default View settings and Loop settings based on View purpose
        $view_settings = wpv_getarr( $args, 'view_settings', array() );

        $view_settings[ WPV_View_Base::VIEW_SETTINGS_QUERY_MODE ] = 'normal';

        $view_purpose = wpv_getarr( $view_settings, WPV_View_Embedded::VIEW_SETTINGS_PURPOSE, 'full', array( 'full', 'pagination', 'parametric', 'slider', 'all' ) );
        $view_settings[ WPV_View_Embedded::VIEW_SETTINGS_PURPOSE ] = $view_purpose;

        $view_settings_default = wpv_view_default_settings( $view_purpose );
        $view_settings = wp_parse_args( $view_settings, $view_settings_default );

        $view->update_postmeta( WPV_View_Base::POSTMETA_VIEW_SETTINGS, $view_settings );

        $loop_settings_default = wpv_view_default_layout_settings( $view_purpose );

        $loop_settings = wpv_getarr( $args, 'loop_settings', array() );
        $loop_settings = wp_parse_args( $loop_settings, $loop_settings_default );

        $view->update_postmeta( WPV_View_Base::POSTMETA_LOOP_SETTINGS, $loop_settings );

        // For the Slider purpose, automatically create a Loop template
        $forbid_loop_template = wpv_getarr( $args, 'forbid_loop_template', false );
        if ( ! $forbid_loop_template && ( 'slider' == $view_purpose ) ) {

            $ct_title = sprintf( '%s - %s', $title, __( 'slide', 'wpv-views' ) );

            $view->create_loop_template( $ct_title, '[wpv-post-link]' );

            // I really hate this solution
            $view->update_postmeta( '_wpv_first_time_load', 'on' );
        }

        $view->resume_after_update_actions();

        return $view;
    }


    /* ************************************************************************* *\
            View duplication
    \* ************************************************************************* */


    /**
     * Create a duplicate of this View.
     *
     * Clone the View and most of it's postmeta. If there is a Loop Template assigned,
     * duplicate that as well and update references (in the appropriate postmeta,
     * in shortcodes in loop output, etc.) in the duplicated View.
     *
     * @todo more detailed description
     * @todo refactor this to use "Generic View/WPA duplication support" in WPV_View_Base
     *
     * @param string $new_post_title Title of the new View. Must not be used in any
     *     existing View or WPA.
     *
     * @return bool|int ID of the new View or false on error.
     */
    public function duplicate( $new_post_title ) {

        // Sanitize and validate
		$new_post_name = sanitize_text_field( sanitize_title( $new_post_title ) );
        $new_post_title = sanitize_text_field( $new_post_title );
		
        if( empty( $new_post_title ) ) {
            return false;
        }

        if( WPV_View_Base::is_name_used( $new_post_title ) ) {
            return false;
        }

        // Clone existing View post object
        $new_post = (array) clone( $this->post() );
        $new_post['post_title'] = $new_post_title;

        $keys_to_unset = array( 'ID', 'post_name', 'post_date', 'post_date_gmt' );
        foreach( $keys_to_unset as $key ) {
            unset( $new_post[ $key ] );
        }
		
		$new_post['post_name'] = $new_post_name;

        $new_post_id = wp_insert_post( $new_post );

        // Clone existing View postmeta
        $postmeta_keys_to_copy = array( '_wpv_settings', '_wpv_layout_settings', '_wpv_description' );

        $new_postmeta_values = array();
        foreach ( $postmeta_keys_to_copy as $key ) {
            $new_postmeta_values[ $key ] = $this->get_postmeta( $key );
        }

        // If this View has a loop Template, we need to clone it and adjust the layout settings.
        if ( $this->has_loop_template ) {
            $new_postmeta_values = $this->duplicate_loop_template( $new_postmeta_values, $new_post_id, $new_post_title );
        }

        // Update postmeta of the new View.
        foreach ( $new_postmeta_values as $meta_key => $meta_value ) {
            update_post_meta( $new_post_id, $meta_key, $meta_value );
        }

        return $new_post_id;
    }



    /* ************************************************************************* *\
        Setters (& validators)
    \* ************************************************************************* */


    /**
     * Validate Filter meta HTML before saving it to database.
     *
     * Perform syntax check to ensure mandatory elements are all present exactly once and in the right order.
     * If that's not the case, throw an exception containing a message - this time very user-friendly one,
     * with thorough description of what's wrong and with minimal demo content.
     *
     * @param string $value The value to be sanitized. It *must* have added slashes (especially before quotes), otherwise
     *     the validation has undefined result.
     * @return string The same value if validation has passed.
     * @throws WPV_RuntimeExceptionWithMessage if validation fails.
     * @since 1.10
     */
    protected function _validate_filter_meta_html( $value ) {

        // List of separate elements to match, each with a match pattern and label and indent level for display purposes.
        $elements = array(
            array( 'label' => '[wpv-filter-start]', 'pattern' => "\\[wpv-filter-start(\\ +[a-z]+\\=\\\\\\\"[a-z0-9]*\\\\\\\")*\\ *\\]", 'indent' => 0 ),
            array( 'label' => '[wpv-filter-end]', 'pattern' => "\\[wpv-filter-end\\]", 'indent' => 0 )
        );

        $this->validate_meta_html_content( $value, __( 'Filter', 'wpv-views' ), $elements );

        return $value;
    }


    /**
     * Set Filter meta HTML.
     *
     * This is a View setting.
     *
     * Also registers strings and labels in wpv-control* shortcodes for WPML translation if the value has changed.
     *
     * @param string $value The value to be sanitized. It *must* have added slashes (especially before quotes), otherwise
     *     the validation has undefined result.
     * @throws WPV_RuntimeExceptionWithMessage if validation fails.
     * @since 1.10
     */
    protected function _set_filter_meta_html( $value ) {
        $value = $this->_validate_filter_meta_html($value);
        if( $this->filter_meta_html != $value ) {
            $this->set_view_setting( WPV_View_Embedded::VIEW_SETTINS_FILTER_META_HTML, $value );
            wpv_register_wpml_strings( $value );
            wpv_add_controls_labels_to_translation( $value, $this->id );
        }
    }


    /**
     * Set Filter extra CSS code.
     *
     * This is a View setting.
     *
     * @param string $value
     * @since 1.10
     */
    protected function _set_filter_css( $value ) {
        $this->set_view_setting( WPV_View_Embedded::VIEW_SETTINS_FILTER_CSS, $value );
    }


    /**
     * Set Filter extra JS code.
     *
     * This is a View setting.
     *
     * @param string $value
     * @since 1.10
     */
    protected function _set_filter_js( $value ) {
        $this->set_view_setting( WPV_View_Embedded::VIEW_SETTINS_FILTER_JS, $value );
    }




}