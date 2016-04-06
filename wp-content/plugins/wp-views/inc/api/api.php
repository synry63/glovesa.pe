<?php
/**
 * Public filter hook API to be used by other Toolset plugins.
 *
 * In optimal case, all interaction with Views would happen through these hooks.
 *
 * @todo Turn this to a namespace when PHP 5.3 is allowed.
 */
class WPV_API {

	private static $instance;


	public static function initialize() {
		self::$instance = new WPV_API();
	}


	private function __construct() {
		$this->register_hooks();
	}


	/**
	 * Register all API hooks.
	 *
	 * Filter hooks are defined by their name and number of arguments. Each filter gets the wpv_prefix.
	 * Name of the handler function equals filter name.
	 *
	 * @since 1.11
	 */
	private function register_hooks() {
		$filter_hooks = array(
			array( 'duplicate_wordpress_archive', 4 )
		);

		foreach( $filter_hooks as $filter_hook ) {
			$hook_name = $filter_hook[0];
			$argument_count = $filter_hook[1];
			add_filter( 'wpv_' . $hook_name, array( $this, $hook_name ), 10, $argument_count );
		}
	}


	/**
	 * wpv_duplicate_wordpress_archive handler. Duplicate a WordPress archive and return ID of the duplicate.
	 *
	 * Note that this may also involve duplication of it's loop template. Refer to WPV_View_Base::duplicate() for
	 * detailed description.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param int $original_wpa_id ID of the original WPA. It must exist and must be a WPA.
	 * @param string $new_title Unique title for the duplicate.
	 * @param bool $ignored Might be needed in the future. Please pass false for now.
	 *
	 * @return mixed|int ID of the duplicate or $default_result on error.
	 *
	 * @since 1.11
	 */
	public function duplicate_wordpress_archive( $default_result, $original_wpa_id, $new_title,
		/** @noinspection PhpUnusedParameterInspection */ $ignored = false ) {

		$original_wpa = WPV_View_Base::get_instance( $original_wpa_id );
		if( null == $original_wpa || !( $original_wpa instanceof WPV_WordPress_Archive ) ) {
			return $default_result;
		}

		$duplicate_wpa_id = $original_wpa->duplicate( $new_title );

		return ( false == $duplicate_wpa_id ) ? $default_result : $duplicate_wpa_id;
	}

}


WPV_API::initialize();