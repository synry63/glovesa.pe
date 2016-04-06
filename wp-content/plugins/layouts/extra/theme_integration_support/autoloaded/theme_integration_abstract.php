<?php

/**
 * Base class for main classes of integration plugins.
 *
 * It handles singleton functionality and executing the integration only when all conditions are met, which means
 * - the relevant theme is active
 * - no other integration was executed before
 *
 * IMPORTANT: Whenever you introduce any change to interface of this class, you need to increase Theme integration API
 * version, otherwise integration plugins might break. Please try to avoid these changes if possible.
 */
abstract class WPDDL_Theme_Integration_Abstract {

	/**
	 * Singleton parent.
	 *
	 * @link http://stackoverflow.com/questions/3126130/extending-singletons-in-php
	 * @return WPDDL_Theme_Integration_Abstract Instance of calling class.
	 */
	final public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}
		return $instances[ $called_class ];
	}


	protected function __construct() {
		$this->base_initialize();
	}


	protected function __clone() {}


	private function base_initialize() {

		if( ! $this->is_theme_active() ) {
			$this->fail( sprintf( __( 'Theme %s is not active.', 'ddl-layouts' ), sanitize_text_field( $this->get_theme_name() ) ), true );
			return;
		}

		// Abort if another integration is already active
		if( defined( 'LAYOUTS_INTEGRATION_THEME_NAME' ) ) {
			$this->fail(
				sprintf(
					__( 'Another Layouts integration plugin is already active (integration with "%s").', 'ddl-layouts' ),
					sanitize_text_field( LAYOUTS_INTEGRATION_THEME_NAME )
				),
				true
			);
			return;
		}

		// Now it's official.
		define( 'LAYOUTS_INTEGRATION_THEME_NAME', $this->get_theme_name() );

		// Run plugin-specific initialization.
		$init_result = $this->initialize();

		// Check the result.
		if( is_wp_error( $init_result ) ) {
			/** @noinspection PhpUndefinedMethodInspection */
			$this->fail( $init_result->get_error_message(), true );
		}
	}


	/**
	 * Determine whether the expected theme is active and the integration can begin.
	 *
	 * @return bool
	 */
	abstract protected function is_theme_active();


	/**
	 * Name of the theme. It will be used as an unique identifier of the integration plugin.
	 *
	 * @return string Theme name
	 */
	protected function get_theme_name(){
		return wp_get_theme();
	}


	/**
	 * @var string Basename of the integration plugin.
	 */
	private $plugin_basename;

	/**
	 * @void set puglin basename
	 **/

	public function set_plugin_base_name( $path ){
		$this->plugin_basename = $path;
	}

	/**
	 * @string path to plugin folder
	 */
	public function get_plugin_base_name(  ){
		return $this->plugin_basename;
	}

	/**
	 * @return string Theme name that can be displayed to the user.
	 */
	protected function get_theme_display_name() {
		return $this->get_theme_name();
	}


	/**
	 * Theme-specific initialization.
	 *
	 * @return bool|WP_Error True when the integration was successful or a WP_Error with a sensible message
	 *     (which can be displayed to the user directly).
	 */
	abstract protected function initialize();


	/**
	 * Show an error message and deactivate the plugin.
	 *
	 * @param string $inner_message Specific description of the failure.
	 * @param bool $deactivate Should the plugin be deactivated? This works only in backend and if called before
	 *    admin_init. In frontend, nothing will happen.
	 */
	protected function fail( $inner_message, $deactivate = false ) {

		$message = sprintf(
			'<p>%s:</p><p><strong>%s</strong></p>',
			sprintf( __( 'The integration of Layouts with the %s theme has failed', 'ddl-layouts' ), sanitize_text_field( $this->get_theme_display_name() ) ),
			$inner_message
		);

		if( $deactivate ) {
			$message .= sprintf( '<p>%s</p>', __( 'The integration plugin has been deactivated.', 'ddl-layouts' ) );
		}

		$this->add_admin_notice( 'error', $message );

		// Deactivate plugin only if we're in the backend. Only there we can display the message.
		if( $deactivate ) {
			add_action( 'admin_init', array( $this, 'deactivate_plugin' ) );
		}
	}


	public function deactivate_plugin() {
		deactivate_plugins( $this->get_plugin_base_name(  ), false, false );
	}


	/**
	 * Enqueued admin notices.
	 *
	 * @var array Array of associative arrays with keys 'type' and 'message'. Value of 'type' can be any of those
	 *     accepted by WPDDL_Messages::display_message.
	 */
	protected $admin_notices = array();


	/**
	 * Enqueue an admin notice to be displayed at the right time.
	 *
	 * @param string $type Can be any of values accepted by WPDDL_Messages::display_message.
	 * @param string $message Text of the message. Needs to be already sanitized!
	 * @param bool $wrap_p Determines if p tag should be added around the message. Default is true.
	 * @todo Consider moving this functionality to WPDDL_Messages.
	 */
	protected function add_admin_notice( $type, $message, $wrap_p = true ) {
		if( empty( $this->admin_notices ) ) {
			add_action( 'admin_notices', array( $this, 'print_admin_notices' ) );
		}
		$this->admin_notices[] = array( 'type' => $type, 'message' => $message, 'wrap_p' => (bool)$wrap_p );
	}


	/**
	 * Print enqueued admin notices.
	 */
	public function print_admin_notices() {
		foreach( $this->admin_notices as $notice ) {

			$message = wpddl_getarr( $notice, 'message' );

			if( wpddl_getarr( $notice, 'wrap_p', true ) ) {
				$message = sprintf( '<p>%s</p>', $message );
			}

			printf( '<div class="%s">%s</div>', wpddl_getarr( $notice, 'type', 'error' ), $message );
		}
	}

}
